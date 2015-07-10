<?php
class Dolumar_Players_Squad
{
	private $id, $data = null, $items = array (), $error = null, $isIdle = null;
	private $aUnits = false;
	private $effects = array ();
	
	private $oVillage;
	private $oCurrentVillage;
	
	public function __construct ($id)
	{
		$this->id = $id;
	}

	public function getId ()
	{
		return (int)$this->id;
	}

	public function setData ($data)
	{
		$this->data = $data;
	}

	private function loadData ()
	{
		if ($this->data === null)
		{
			// Load thze data
			$db = Neuron_Core_Database::__getInstance ();

			$data = $db->select
			(
				'villages_squads',
				array ('*'),
				"s_id = '".$this->getId ()."'"
			);

			$this->data = $data[0];
		}
	}

	public function getName ()
	{
		$this->loadData ();
		return $this->data['s_name'];
	}
	
	public function getDisplayName ()
	{
		return Neuron_Core_Tools::output_varchar ($this->getName ());
	}
	
	public function setVillage ($oVillage)
	{
		$this->oVillage = $oVillage;
	}

	public function getVillage ()
	{
		$this->loadData ();
		
		if (!isset ($this->oVillage))
		{
			$this->setVillage (Dolumar_Players_Village::getVillage ($this->data['v_id']));
		}
		
		return $this->oVillage;
	}
	
	public function setCurrentLocation ($village)
	{
		$this->oCurrentVillage = $village;
	}
	
	/*
		Returns the current location for this squad.
	*/
	public function getCurrentLocation ()
	{
		if (!isset ($this->oCurrentVillage))
		{
			$this->loadData ();
			if ($this->data['s_village'] > 0)
				$this->setCurrentLocation (Dolumar_Players_Village::getVillage ($this->data['s_village']));
			else
				$this->setCurrentLocation ($this->getVillage ());
		}
		
		return $this->oCurrentVillage;
	}
	
	public function getUnitType ()
	{
		$this->loadData ();
		return $this->data['v_type'];
	}
	
	public function reloadUnits ()
	{
		$this->aUnits = false;
	}
	
	private function loadUnits ()
	{
		if (!is_array ($this->aUnits))
		{
			$db = Neuron_Core_Database::__getInstance ();
		
			$l = $db->getDataFromQuery ($db->customQuery
			("
				SELECT
					*
				FROM
					squad_units su
				LEFT JOIN
					units u ON u.unitId = su.u_id
				WHERE
					su.s_id = '".$this->getId ()."'
			"));

			// Load thze items
			$items = $db->getDataFromQuery ($db->customQuery
			("
				SELECT
					squad_equipment.*
				FROM
					squad_equipment
				WHERE
					squad_equipment.s_id = '".$this->getId ()."'
			"));

			$critItems = array ();
			foreach ($items as $v)
			{
				if (!isset ($critItems[$v['u_id']]))
				{
					$critItems[$v['u_id']] = array ();
				}
			
				$critItems[$v['u_id']][] = Dolumar_Players_Equipment::getFromId ($v['e_id']);
			}

			$o = array ();
			foreach ($l as $v)
			{
				$i = $v['unitId'];
			
				$o[$i] = Dolumar_Units_Unit::getUnitFromName
				(
					$v['unitName'],
					$this->getVillage ()->getRace (),
					$this->getVillage ()
				);
				
				$isMoving = $this->isMoving ();
			
				$o[$i]->addAmount ($v['s_amount'], $isMoving ? 0 : $v['s_amount'], $v['s_amount']);
				$o[$i]->setSquad ($this);
				
				// Set slot ID
				$o[$i]->setDefaultSlot ($v['s_slotId'], $v['s_priority']);

				if (isset ($critItems[$v['u_id']]))
				{
					foreach ($critItems[$v['u_id']] as $v)
					{
						$o[$i]->addEquipment ($v); 
					}
				}

				// Don't see why this would be required, but just to be sure...
				// $o[$i]->getStats ();
			}
			$this->aUnits = $o;
		}
	}

	public function getUnits ()
	{
		$this->loadUnits ();
		return $this->aUnits;
	}
	
	public function getUnit ($unitId)
	{
		$this->loadUnits ();
		if (isset ($this->aUnits[$unitId]))
		{
			return $this->aUnits[$unitId];
		}
		else
		{
			return false;
		}
	}

	public function addUnits ($objUnit, $amount)
	{
		if ($objUnit->getSquadlessAmount () < $amount)
		{
			$this->error = 'units_unavailable';
		}
		elseif (!$this->isIdle ())
		{
			$this->error = 'squad_not_idle';
		}
		elseif ($objUnit->getUnitId () != $this->getUnitType ())
		{
			$this->error = 'unit_squad_mismatch';
		}
		else
		{
			$db = Neuron_Core_Database::__getInstance ();

			$db->beginWork ();

			$l = $db->select
			(
				'squad_units',
				array ('su_id'),
				"u_id = '".$objUnit->getUnitId ()."' AND s_id = '".$this->getId ()."'"
			);

			if (count ($l) == 0)
			{
				// Insert it fresh & well
				$db->insert
				(
					'squad_units',
					array
					(
						's_id' => $this->getId (),
						'u_id' => $objUnit->getUnitId (),
						's_amount' => $amount,
						'v_id' => $this->getVillage()->getId()
					)
				);
			}

			else
			{
				$this->unequipItems ($objUnit);
				
				$db->update
				(
					'squad_units',
					array ('s_amount' => '++' . $amount),
					"su_id = '".$l[0]['su_id']."'"
				);
			}

			$db->commit ();
		}
	}
	
	/*
		Get the speed of this squad:
		basically: the smallest speed of all units
	*/
	public function getSpeed ()
	{
		$units = $this->getUnits ();
		
		if (count ($units) > 0)
		{
			foreach ($this->getUnits () as $v)
			{
				$u_speed = $v->getSpeed ();
				if (!isset ($speed) || $u_speed < $speed)
				{
					$speed = $u_speed;
				}
			}
			
			return $speed;
		}
		else
		{
			return 10;
		}
	}
	
	public function removeUnits ($objUnit, $amount)
	{
		if (!$this->isIdle ())
		{
			$this->error = 'squad_not_idle';
			return false;
		}
		
		else
		{
			return $this->withdrawUnits ($objUnit, $amount, false);
		}
	}	

	/*
		Returns TRUE if the squad has been removed (due to empty)
	*/
	public function withdrawUnits ($objUnit, $amount, $deletesquad = true)
	{
		$amount = abs ($amount);

		if ($amount > 0)
		{
			$db = Neuron_Core_Database::__getInstance ();
			$l = $db->select
			(
				'squad_units',
				array ('*'),
				"u_id = '".$objUnit->getUnitId ()."' AND s_id = '".$this->getId ()."'"
			);

			if (count ($l) == 1)
			{
				if ($l[0]['s_amount'] <= $amount)
				{
					return $this->removeUnit ($objUnit->getUnitId (), $deletesquad);
				}
				else
				{
					// Update the record
					$db->update
					(
						'squad_units',
						array ('s_amount' => '--' . $amount),
						"su_id = '".$l[0]['su_id']."'"
					);
					
					return false;
				}
			}
			else
			{
				return false;
			}
		}
	}

	/*
		Returns TRUE the squad has been destroyed.
		Returns FALSE if only the u nit has been removed.
	*/
	public function removeUnit ($unitId, $deletesquad = true)
	{
		$db = Neuron_Core_Database::__getInstance ();
		$db->remove
		(
			'squad_units',
			"u_id = '".$unitId."' AND s_id = '".$this->getId ()."'"
		);

		$db->remove
		(
			'squad_equipment',
			"u_id = '".$unitId."' AND s_id = '".$this->getId ()."'"
		);
		
		// This unit will be disbanded if there are no units left.
		$this->reloadUnits ();
		
		$units = $this->getUnits ();
		if (count ($units) == 0)
		{
			// Disband squad
			if ($deletesquad)
			{
				$this->getVillage ()->removeSquad ($this->getId ());
				return true;
			}
			else
			{
				return false;
			}
		}
		
		else
		{
			return false;
		}
	}

	public function addEquipment ($objUnit, $objEquipment)
	{
		$this->loadData ();

		$itemType = $objEquipment->getItemType ();
		$items = $objUnit->getEquipment ();
		
		if (!isset ($items[$itemType]) || $items[$itemType]->getId () != $objEquipment->getId ())
		{
			if (!$objEquipment->canEquip ($objUnit))
			{
				$this->error = 'not_compatible';
				return false;
			}
		
			else if ($this->isIdle ())
			{
				if ($objEquipment->getAvailableAmount () >= $objUnit->getAvailableAmount ())
				{
					$db = Neuron_Core_Database::__getInstance ();

					// Unequip
					$itemType = $objEquipment->getItemTypeId ();

					$this->unequipItem ($objUnit, $itemType);

					// Add the current one ;-)
					$db->insert
					(
						'squad_equipment',
						array
						(
							's_id' => $this->getId (),
							'u_id' => $objUnit->getUnitId (),
							'e_id' => $objEquipment->getMysqlId (),
							'v_id' => $this->getVillage ()->getId (),
							'i_itid' => $objEquipment->getItemTypeId ()
						)
					);

					return true;
				}
				else
				{
					$this->error = 'not_enough_items';
					return false;
				}
			}
			else
			{
				$this->error = 'squad_not_idle';
				return false;
			}
		}
		else
		{
			return true;
		}
	}

	public function unequipItems ($objUnit)
	{
		$this->unequipItem ($objUnit);
	}

	public function unequipItem ($objUnit, $itemType = false)
	{
		$db = Neuron_Core_Database::__getInstance ();

		if ($this->isIdle ())
		{
			// Remove current equipment
			if ($itemType !== false)
			{
				$db->remove
				(
					'squad_equipment',
					"s_id = '".$this->getId ()."' AND ".
					"u_id = '".$objUnit->getUnitId ()."' AND ".
					"i_itid = '".$itemType."'"
				);
			}
			else
			{
				$db->remove
				(
					'squad_equipment',
					"s_id = '".$this->getId ()."' AND ".
					"u_id = '".$objUnit->getUnitId ()."'"
				);
			}
		}
	}

	public function getUnitsAmount ()
	{
		$count = 0;
		foreach ($this->getUnits () as $v)
		{
			$count += $v->getAvailableAmount ();
		}
		return $count;
	}

	public function getError ()
	{
		return $this->error;
	}

	public function isIdle ()
	{
		if ($this->isIdle === null)
		{
			// If this troop is not home, it's not active :)
			if ($this->getCurrentLocation ()->getId () != $this->getVillage ()->getId ())
			{
				$this->isIdle = false;
			}
			else
			{
				$db = Neuron_Core_Database::__getInstance ();
				$chk = $db->select
				(
					'battle_squads',
					array ('bs_id'),
					"bs_squadId = ".$this->getId ()
				);

				$chk2 = $db->select
				(
					'underworld_armies_squads',
					array ('uas_id'),
					"s_id = ".$this->getId ()
				);
				
				if (count ($chk) == 0 && count ($chk2) == 0)
				{					
					$this->isIdle = !$this->isMoving ();
				}
				else
				{
					$this->isIdle = false;
				}
			}
		}

		return $this->isIdle;
	}
	
	/*
		Move to other villages (support)
	*/
	public function sendToVillage ($target)
	{
		// For now: quick & dirty "instant arrival"
		$db = Neuron_DB_Database::__getInstance ();
		
		// Take a look: is this squad traveling at the moment?
		$current = $db->query
		("
			SELECT
				UNIX_TIMESTAMP(s_start) AS vertrek,
				UNIX_TIMESTAMP(s_end) AS aankomst
			FROM
				squad_commands
			WHERE
				s_id = ".$this->getId ()."
				AND s_end > FROM_UNIXTIME(".NOW.")
			ORDER BY
				s_end DESC
			LIMIT 1
		");
		
		if (count ($current) > 0)
		{
			// This squad is currently travelling, this is a recall.
			// Moveing back takes as long as moving overthere.
			$duration = NOW - $current[0]['vertrek'];
			
			// Remove all others
			$db->query
			("
				DELETE FROM
					squad_commands
				WHERE
					s_end < FROM_UNIXTIME(".NOW.") OR
					s_id = ".$this->getId ()."
			");
		}
		else
		{
			// Calculate the distance & speed
			$speed = $this->getSpeed ();
			
			$distance = Dolumar_Map_Map::getDistanceBetweenVillages ($this->getCurrentLocation (), $target);
			
			$duration = ($distance * 60 * 10) / ($speed * GAME_SPEED_MOVEMENT);
		}
		
		// Do the actual move
		$db->query
		("
			UPDATE
				villages_squads
			SET
				s_village = ".$target->getId ()."
			WHERE
				s_id = ".$this->getId ()."
		");
		
		// Update the defense slots
		$db->query
		("
			UPDATE
				squad_units
			SET
				s_slotId = 0,
				s_priority = 0
			WHERE
				s_id = ".$this->getId ()."
		");
		
		// Add the commands
		$db->query
		("
			INSERT INTO
				squad_commands
			SET
				s_id = ".$this->getId ().",
				s_action = 'move',
				s_start = FROM_UNIXTIME(".NOW."),
				s_end = FROM_UNIXTIME(".(NOW + $duration)."),
				s_from = ".$this->getCurrentLocation()->getId().",
				s_to = ".$target->getId()."
		");
		
		reloadStatusCounters ();
		reloadEverything ();
	}
	
	/*
		Move squad home
	*/
	public function goHome ()
	{
		$this->sendToVillage ($this->getVillage ());
	}
	
	public function isHome ($home = null)
	{
		if (!isset ($home))
		{
			$home = $this->getVillage ();
		}
		
		$chk = $this->getVillage ()->getId () == $home->getId () &&
			$this->getCurrentLocation ()->getId () == $home->getId ();
		
		return $chk;
	}
	
	public function isMoving ()
	{
		$db = Neuron_Core_Database::__getInstance ();
	
		// Check if moving
		$chk = $db->select
		(
			'squad_commands',
			array ('sc_id'),
			"s_id = ".$this->getId ()." 
			AND s_end > FROM_UNIXTIME(".NOW.")"
		);
		
		return !count ($chk) == 0;
	}
	
	/*
		Effects
	*/
	public function addEffect ($effect)
	{
		$this->effects[] = $effect;
	}
	
	/*
		This function returns the effects on:
		1. the unit
		2. the squad
		3. the village
		
		(last two are defined in the squad object)
	*/
	public function getEffects ()
	{
		$effs = $this->effects;
		$effs = array_merge ($effs, $this->getVillage ()->getEffects ());		
		return $effs;
	}
}
?>
