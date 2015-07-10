<?php
/*
	This class handles everything that has to do with battles.
*/
class Dolumar_Players_Village_Equipment
{
	private $objProfile;
	private $equipmentlevels;
	private $error;
	
	const EQUIPMENT_MAX_LEVEL = 10;

	public function __construct ($profile)
	{
		$this->objProfile = $profile;
	}
	
	/**********************************************************************************
		ITEMS
	***********************************************************************************/
	public function getEquipment ()
	{
		$db = Neuron_Core_Database::__getInstance ();
	
		$l = $db->getDataFromQuery ($db->customQuery
		("
			SELECT
				villages_items.*
			FROM
				villages_items
			WHERE
				villages_items.vid = '".$this->objProfile->getId ()."'
		"));

		// Fetch the equiped items
		$eItems = $db->getDataFromQuery ($db->customQuery
		("
			SELECT
				squad_equipment.*,
				squad_units.s_amount
			FROM
				squad_equipment
			LEFT JOIN
				squad_units ON squad_units.s_id = squad_equipment.s_id AND squad_units.u_id = squad_equipment.u_id
			WHERE
				squad_equipment.v_id = '".$this->objProfile->getId ()."'
		"));

		$equipedItems = array ();
		foreach ($eItems as $v)
		{
			if (isset ($equipedItems[$v['e_id']]))
			{
				$equipedItems[$v['e_id']] += $v['s_amount'];
			}
			else
			{
				$equipedItems[$v['e_id']] = $v['s_amount'];
			}
		}

		$now = time ();

		$o = array ();
		foreach (Dolumar_Players_Equipment::getItemTypes () as $v)
		{
			$o[$v] = array ();
		}
		
		foreach ($l as $v)
		{
			$obj = Dolumar_Players_Equipment::getFromId ($v['i_itemId']);

			$type = $obj->getItemType ();
			if (!isset ($o[$type][$v['i_itemId']]))
			{
				$o[$type][$v['i_itemId']] = $obj;

				if (isset ($equipedItems[$v['i_itemId']]))
				{
					$o[$type][$v['i_itemId']]->addUsedAmount ($equipedItems[$v['i_itemId']]);
				}
			}

			if ($v['i_endCraft'] < time ())
			{
				$o[$type][$v['i_itemId']]->addAmount ($v['i_amount'] - $v['i_removed']);
			}
			else
			{
				$duration = max (1, $v['i_endCraft'] - $v['i_startCraft']);
				$procent = max (0, $now - $v['i_startCraft']) / $duration;
				$amount = floor ($procent * $v['i_amount']) - $v['i_removed'];
				
				$o[$type][$v['i_itemId']]->addAmount ($amount);
			}
		}
		return $o;
	}

	public function craftEquipment ($objBuilding, $objEquipment, $duration, $amount)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$e_id = $objEquipment->getMysqlId ();

		// Start training
		$db->insert
		(
			'villages_items',
			array
			(
				'vid' => $this->objProfile->getId (),
				'i_itemId' => $e_id,
				'i_amount' => $amount,
				'i_startCraft' => time (),
				'i_endCraft' => (time () + $duration),
				'i_removed' => 0,
				'i_buildingId' => $objBuilding->getBuildingId (),
				'i_bid' => $objBuilding->getId ()
			)
		);

		reloadStatusCounters ();
	}
	
	public function transferEquipment 
	(
		Dolumar_Players_Village $objTarget, 
		Dolumar_Players_Equipment $objEquipment, 
		$amount,
		$delay = 0
	)
	{
		if ($this->removeEquipment ($objEquipment, $amount))
		{
			if ($objTarget->equipment->addEquipment ($objEquipment, $amount, $delay, $this->objProfile))
			{
				$objLogs = Dolumar_Players_Logs::__getInstance ();
				$objLogs->addEquipmentTransferLog ($this->objProfile, $objTarget, $objEquipment, $amount);
			
				return true;
			}
		}
		
		$this->error = 'dont_have_equipment';
		return false;
	}
	
	public function addEquipment 
	(
		Dolumar_Players_Equipment $objEquipment, 
		$amount, 
		$delay = 0, 
		Dolumar_Players_Village $from = null
	)
	{
		$e_id = $objEquipment->getId ();
		
		if ($delay > 0 && $from != null)
		{
			$data = array ();
			$data[$e_id] = $amount;
			return $this->objProfile->resources->addDelayedTransfer ($data, 'EQUIPMENT', $delay, $from);
		}
		else
		{
			$db = Neuron_Core_Database::__getInstance ();
	
			// Start training
			$db->insert
			(
				'villages_items',
				array
				(
					'vid' => $this->objProfile->getId (),
					'i_itemId' => $e_id,
					'i_amount' => $amount,
					'i_startCraft' => 0,
					'i_endCraft' => 0,
					'i_removed' => 0,
					'i_buildingId' => 0,
					'i_bid' => 0
				)
			);
		
			return true;
		}
	}

	public function removeEquipment ($objEquipment, $amount)
	{
		// just do some stuff here. I'm too annoyed to do it right now.
		$db = Neuron_Core_Database::__getInstance ();

		$db->customQuery ("LOCK TABLES villages_items WRITE, equipment READ");

		$l = $db->select
		(
			'villages_items',
			array ('*'),
			"vid = '".$this->objProfile->getId ()."' AND i_itemId = '".$objEquipment->getMysqlId ()."'",
			"i_endCraft ASC"
		);

		$removeWhere = "";
		$removes = 0;

		foreach ($l as $v)
		{
			if ($amount > 0)
			{
				$actAmount = $v['i_amount'] - $v['i_removed'];
				if ( ($actAmount - $amount) <= 0)
				{
					$removeWhere .= "i_id = '{$v['i_id']}' OR ";
					$removes ++;
				}

				else
				{
					// withdraw
					$db->update
					(
						'villages_items',
						array ('i_removed' => '++'.$amount),
						"i_id = '{$v['i_id']}'"
					);
				}

				$amount -= $actAmount;
			}
		}

		if ($removes > 0)
		{
			$removeWhere = substr ($removeWhere, 0, -4);
			$db->remove
			(
				'villages_items',
				$removeWhere
			);
		}

		$db->customQuery ('UNLOCK TABLES');
		
		return true;
	}
	
	/*
		Cancel all craft operations from a building.
	*/
	public function cancelCrafting ($building, $now = NOW)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		// Fetch the crafting items
		$chk = $db->query
		("
			SELECT
				*
			FROM
				villages_items
			WHERE
				i_bid = {$building->getId ()} AND
				i_endCraft > " . $now . "
		");
		
		foreach ($chk as $v)
		{
			$duration = max (1, $v['i_endCraft'] - $v['i_startCraft']);
			$procent = max (0, $now - $v['i_startCraft']) / $duration;
			$amount = floor ($procent * $v['i_amount']);
			
			if ($amount > 0)
			{
				$db->query
				("
					UPDATE
						villages_items
					SET
						i_amount = {$amount},
						i_endCraft = " . $now . "
					WHERE
						i_id = {$v['i_id']}
				");
			}
			else
			{
				$db->query
				("
					DELETE FROM
						villages_items
					WHERE
						i_id = {$v['i_id']}
				");
			}
		}
		
		reloadStatusCounters ();
	}
	
	/*
		Equipment levels
	*/
	public function getEquipmentLevels ()
	{
		$db = Neuron_DB_Database::getInstance ();
		
		if (!isset ($this->equipmentlevels))
		{
			$data = $db->query
			("
				SELECT
					*
				FROM
					villages_itemlevels
				WHERE
					v_id = {$this->objProfile->getId ()}
			");
			
			$this->equipmentlevels = array ();
			foreach ($data as $v)
			{
				$this->equipmentlevels[$v['e_id']] = $v['vi_level'];
			}
		}
		
		return $this->equipmentlevels;
	}
	
	public function getEquipmentLevel ($objEquipment)
	{
		$eid = intval ($objEquipment->getMysqlId (false));
		$data = $this->getEquipmentLevels ();
		
		return isset ($data[$eid]) ? $data[$eid] : 1;
	}
	
	private function reloadEquipmentLevels ()
	{
		$this->equipmentlevels = null;
	}
	
	public function setEquipmentLevel ($objEquipment, $level = 1)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$eid = intval ($objEquipment->getMysqlId (false));
		$level = intval ($level);
		
		$data = $this->getEquipmentLevels ();
		if (!isset ($data[$eid]))
		{
			$db->query
			("
				INSERT INTO
					villages_itemlevels
				SET
					v_id = {$this->objProfile->getId ()},
					e_id = {$eid},
					vi_level = {$level}
			");
		}
		
		else
		{
			$db->query
			("
				UPDATE
					villages_itemlevels
				SET
					vi_level = {$level}
				WHERE
					e_id = {$eid} AND
					v_id = {$this->objProfile->getId ()}
			");
		}
		
		$this->reloadEquipmentLevels ();
		
		return true;
	}
	
	/*
		Return the amount of levels spend on equipment
	*/
	private function countSpendEquipmentLevels ()
	{
		$total = 0;
		foreach ($this->getEquipmentLevels () as $v)
		{
			$total += ($v - 1);
		}
		return $total;
	}
	
	/*
		Count the levels of the crafting buildings
	*/
	public function getUnusedLevels ()
	{
		$smithlevels = $this->objProfile->buildings->getBuildingLevelSum (31);
		$armourlevels = $this->objProfile->buildings->getBuildingLevelSum (32);
		
		$total = $smithlevels + $armourlevels;
		
		return $total - $this->countSpendEquipmentLevels ();
	}
	
	public function increaseEquipmentLevel ($objEquipment)
	{
		if ($this->getUnusedLevels () > 0)
		{
			if ($this->canIncreaseLevel ($objEquipment))
			{
				// Take resources
				$cost = $this->getIncreaseLevelCost ($objEquipment);
				
				if ($this->objProfile->resources->takeResources ($cost))
				{
					$level = $this->getEquipmentLevel ($objEquipment);
					return $this->setEquipmentLevel ($objEquipment, $level + 1);
				}
				else
				{
					$this->error = 'not_enough_resources';
					return false;
				}
			}
			else
			{
				$this->error = 'cannot_increase_level';
				return false;
			}
		}
		else
		{
			$this->error = 'no_fee_levels';
			return false;
		}
	}
	
	public function canIncreaseLevel ($objEquipment)
	{
		return $this->getEquipmentLevel ($objEquipment) < self::EQUIPMENT_MAX_LEVEL;
	}
	
	public function getIncreaseLevelCost ($objEquipment)
	{
		$costs = $objEquipment->getCraftCost ($this->objProfile);
		
		foreach ($costs as $k => $v)
		{
			$costs[$k] = (($v + 2000) * (5 * $this->getEquipmentLevel ($objEquipment))) + 10000;
		}
		
		return $costs;
	}
	
	public function getError ()
	{
		return $this->error;
	}
	
	public function __destruct ()
	{
		unset ($this->objProfile);
	}
}
?>
