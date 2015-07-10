<?php
abstract class Dolumar_Units_Unit implements Neuron_GameServer_Interfaces_Logable
{
	private $classname;
	private $village;
	
	private $availableAmount = 0;
	private $totalAmount = 0;
	private $killedAmount = 0;
	private $toFeed = 0;
	private $inSquads = 0;

	private $eternalLife = false;
	private $objSquad = false;

	private $equipment = array ();
	private $effects = array ();
	
	private $iDefaultSlot = 0;
	private $iPriority = 0;
	
	private $stats;
	
	private $curSlot = null;
	
	private $killedInRound = 0;
	
	private $iSkipTurn = 0;
	
	private $iBattleStatus = 0;
	
	private $race;
	
	private $currentfrontage = 0; // This will change during batltes
	
	const BATTLE_STATUS_NORMAL = 0;
	const BATTLE_STATUS_FLED = 1;
	const BATTLE_STATUS_WHIPED = 2;
	
	public static function getUnitFromName ($name, $race, $village)
	{
		$rn = $race->getName ();
		
		if ($village instanceof Dolumar_Players_DummyVillage)
		{
			//echo $race->getName ();
		
			$village = new Dolumar_Players_DummyVillage ();
			$village->setRace ($race);
		}
		
		if ('Dolumar_Units_' . $name == __CLASS__)
		{
			return false;
		}
		
		if (class_exists ('Dolumar_Races_'.$rn.'_Dolumar_Units_'.$name))
		{
			eval ('$c = new Dolumar_Races_'.$rn.'_Dolumar_Units_'.$name.' ($village);');
		}
		
		elseif (class_exists ('Dolumar_Units_'.$name))
		{
			eval ('$c = new Dolumar_Units_'.$name.' ($village);');
		}

		else
		{
			$c = false;
		}
		
		return $c;
	}
	
	public static function getFromId ($id)
	{
		$id = explode ('|', $id);
		
		$uid = $id[0];
		$village = Dolumar_Players_Village::getFromId ($id[1]);
		$race = Dolumar_Races_Race::getFromId ($id[2]);
	
		return self::getUnitFromId ($uid, $race, $village);
	}

	public static function getUnitFromId ($id, $race, $village)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$uName = $db->select
		(
			'units',
			array ('unitName'),
			"unitId = '$id'"
		);

		if (count ($uName) == 1)
		{
			return self::getUnitFromName ($uName[0]['unitName'], $race, $village);
		}

		else
		{
			return false;
		}
	}
	
	public static function getAllUnits ($race)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$data = $db->select
		(
			'units',
			array ('unitName')
		);
		
		$village = new Dolumar_Players_DummyVillage ($race);

		$out = array ();
		foreach ($data as $v)
		{
			$unit = self::getUnitFromName ($v['unitName'], $race, $village);
			
			if ($unit)
			{
				$out[] = $unit;
			}
		}
		
		return $out;
	}

	public static function getUnitId_static ($classname)
	{
		static $ids;

		if (!isset ($ids[$classname]))
		{
			$db = Neuron_Core_Database::__getInstance ();
			
			$l = $db->getDataFromQuery ($db->customQuery
			("
				SELECT
					unitId
				FROM
					units
				WHERE
					unitName = '".$classname."'
				FOR UPDATE
			"));

			if (count ($l) == 1)
			{
				$id = $l[0]['unitId'];
			}

			else
			{
				$id = $db->insert
				(
					'units',
					array ('unitName' => $classname)
				);
			}
			
			$ids[$classname] = $id;
		}

		return $ids[$classname];
	}

	public function __construct ($village)
	{
		$this->village = $village;
		$this->race = $village->getRace ();
	}
	
	public function getId ()
	{
		$out = $this->getUnitId ();
		
		$out .= '|' . $this->village->getId ();
		$out .= '|' . $this->race->getId ();
	
		return $out;
	}
	
	public function setDefaultSlot ($iDefaultSlot, $iPriority)
	{
		$this->iDefaultSlot = $iDefaultSlot;
		$this->iPriority = $iPriority;	
	}
	
	public function getDefaultSlot ()
	{
		return $this->iDefaultSlot;
	}
	
	public function getSlotPriority ()
	{
		return $this->iPriority;
	}
	
	public function setEternalLife ($con = true)
	{
		$this->eternalLife = $con;
	}

	public function getRace ()
	{
		return $this->village->getRace ();
	}

	public function getVillage ()
	{
		return $this->village;
	}

	/*
		Warning! This function must accept 
		negative numbers as well. Some calculations
		put negative values first before filling them up again.
	*/
	public function addAmount ($available, $toFeed, $total)
	{
		$this->availableAmount += $available;
		$this->totalAmount += $total;
		$this->toFeed += $toFeed;
	}

	public function putInSquads ($amount)
	{
		$this->inSquads += $amount;
	}
	
	public function removeAmount ($available)
	{
		$this->availableAmount -= $available;
		$this->totalAmount -= $available;
		$this->toFeed -= $available;
	}

	/*
		This is a synonym for getDefendingAmount.
		This basically returns the actual amount of troops.
	*/
	public function getAmount ()
	{
		return $this->getDefendingAmount ();
	}

	/*
		This returns the amount of defending units.
		If this unit is not in a squad, the amount of squad units will be withdrawed.
	*/
	public function getDefendingAmount ()
	{
		if ($this->objSquad)
		{
			return $this->getAvailableAmount ();
		}
		else
		{
			return $this->getSquadlessAmount ();
		}
	}

	public function getAvailableAmount ()
	{
		return floor ($this->availableAmount);
	}

	public function getTotalAmount ()
	{
		return floor ($this->totalAmount);
	}

	public function getTotalToFeed ()
	{
		return floor ($this->toFeed);
	}

	public function getSquadlessAmount ()
	{
		return floor ($this->getTotalAmount () - $this->inSquads);
	}

	public static function printStatNames ($page)
	{
		$text = Neuron_Core_Text::__getInstance ();

		$page->set ('unit_defCav', $text->get ('defCav', 'unitStats', 'main'));
		$page->set ('unit_defIn', $text->get ('defIn', 'unitStats', 'main'));
		$page->set ('unit_defAr', $text->get ('defAr', 'unitStats', 'main'));
		$page->set ('unit_defMag', $text->get ('defMag', 'unitStats', 'main'));
		$page->set ('unit_melee', $text->get ('melee', 'unitStats', 'main'));
		$page->set ('unit_shooting', $text->get ('shooting', 'unitStats', 'main'));
		$page->set ('unit_health', $text->get ('health', 'unitStats', 'main'));
		$page->set ('unit_amount', $text->get ('amount', 'unitStats', 'main'));
	}

	public function getClassName ()
	{
		if (empty ($this->classname))
		{
			$name = get_class ($this);
			$name = explode ('_', $name);
			$this->classname = $name[count ($name) - 1];
		}

		return $this->classname;
	}
	
	/*
		Return an untranslated name for this unit, used
		in images etc. It's defined in the stats.
	*/
	public function getUnitName ()
	{
		return $this->getStat ('name', $this->getClassName ());
	}

	public function getAPIName ()
	{
		return $this->getClassName () . '[' . $this->village->getRace ()->getName ().']';
	}

	public function canTrainUnit ()
	{
		foreach ($this->getRequiredTechnologies () as $v)
		{
			if (!$this->getVillage ()->technology->hasTechnology ($v))
			{
				return false;
			}
		}
		
		return true;
	}
	
	public function getRequiredTechnologies ()
	{
		$requires = $this->getStat ('requires');
		if (isset ($requires) && count ($requires) > 0)
		{
			$out = array ();
			foreach ($requires as $v)
			{
				$out[] = Dolumar_Technology_Technology::getTechnology ($v);
			}
			
			return $out;
		}
		return array ();
	}
	
	public function getStat ($stat, $default = 0)
	{
		$stats = $this->getStats ();
		return isset ($stats[$stat]) ? $stats[$stat] : $default;
	}
	
	public function setStat ($stat, $value)
	{
		$stats = $this->getStats ();
		$this->stats[$stat] = $value;
	}
	
	public function reloadStats ()
	{
		$this->stats = null;
	}

	public function getStats ()
	{
		if ($this->stats === null)
		{
			$costs = array ();
			$upkeep = array ();
		
			$stats = Neuron_Core_Stats::__getInstance ();

			$text = Neuron_Core_Text::__getInstance ();

			$default = array
			(
				'hp' => 90,
				'melee' => 50,
				'frontage' => 10,
				'shooting' => 0,
				'defIn' => 30,
				'defAr' => 20,
				'defCav' => 60,
				'defMag' => 20,
				'speed' => 10,
				
				// Costs
				/*
				'cost_grain' => 180,
				'cost_iron' = 125,
				'cost_gold' => 50,
				
				// Upkeep
				'upkeep_grain' => 1
				*/
			);

			$statistics = array_merge 
			(
				// Defaults
				$default, 
				
				// Global stats
				$stats->getSection ($this->getClassname (), 'units'),
				
				// Race specific stats
				$stats->getSection ($this->getClassname (), $this->getRace ()->getName () . '/units')
			);
			
			$requires = array ();
			
			$name = null;
			$atType = 'defIn';
			
			// Fetch upkeep etc
			foreach ($statistics as $k => $v)
			{
				//$k = strtolower ($k);
			
				if (substr ($k, 0, 4) == 'cost')
				{
					unset ($costs[substr ($k, 5)]);
				
					if ($v > 0)
						$costs[substr ($k, 5)] = $v;
					
					unset ($statistics[$k]);
				}
				
				elseif (substr ($k, 0, 6) == 'upkeep')
				{
					unset ($upkeep[substr ($k, 7)]);
				
					if ($v > 0)
						$upkeep[substr ($k, 7)] = $v;
					
					unset ($statistics[$k]);
				}
				
				elseif ($k == 'requires')
				{
					$requires = explode (',', $v);
					unset ($statistics[$k]);
				}
				
				elseif ($k == 'name')
				{
					$name = $v;
					unset ($statistics[$k]);
				}
			}

			foreach ($this->getEquipment () as $v)
			{
				foreach ($v->getStats () as $stat => $value)
				{
					if (isset ($statistics[$stat]))
					{
						if (strpos ($value, '%') > 0)
						{
							$statistics[$stat] *= ((100 + ((int)$value)) / 100);
						}
						else
						{
							$statistics[$stat] += ((int)$value);
						}
					}
				}
			}
			
			// Process the effects
			foreach ($this->getEffects () as $v)
			{
				$v->procUnitStats ($statistics, $this);
			}
			
			// addapt HP to village honour
			if ($this->village)
			{
				$honour = $this->village->honour->getHonour ();
				$statistics['hp'] = ceil ($statistics['hp'] * ($honour / 100));
			}

			$this->stats = $statistics;
			
			foreach ($this->stats as $k => $v)
			{
				$this->stats[$k] = round ($v);
			}
			
			// In order to preserve OOP design
			$this->stats['name'] = $name;
			$this->stats['atType'] = isset ($statistics['atType']) ? $statistics['atType'] : 'defIn';
			$this->stats['atTypeTrans'] = $text->get ($this->stats['atType'], 'attackTypes', 'main');
			$this->stats['image'] = $this->getImageUrl ();
			
			// Upkeep should be linked to speed
			foreach ($upkeep as $k => $v)
			{
				$upkeep[$k] = $v * GAME_SPEED_RESOURCES;
			}
			
			// Add the costs as well
			$this->stats['cost'] = $costs;
			$this->stats['upkeep'] = $upkeep;
			
			// Technology requirements.
			$this->stats['requires'] = $requires;
			
			// Limit some stats
			$this->stats['defIn'] = min ($this->stats['defIn'], 90);
			$this->stats['defAr'] = min ($this->stats['defAr'], 90);
			$this->stats['defCav'] = min ($this->stats['defCav'], 90);
			$this->stats['defMag'] = min ($this->stats['defMag'], 90);
		}
		
		return $this->stats;
	}
	
	public function getStealingStat ()
	{
		return 1;
	}

	public function getAttackType ()
	{
		// Return: defIn, defAr, defCav or defMag
		//$stats = Neuron_Core_Stats::__getInstance ();
		//return $stats->get ('atType', $this->getClassname (), 'units', 'defIn');
		//return 'defIn';
		$stats = $this->getStats ();
		return $stats['atType'];
	}

	public function getAttackType_text ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		return $text->get ($this->getAttackType (), 'unitTypes', 'main');
	}

	public function multiplyCost ($res, $amount)
	{
		$o = array ();
		foreach ($res as $k => $v)
		{
			if (is_numeric ($v) && $k != 'runeAmount')
			{
				$o[$k] = $v * $amount;
			}

			else
			{
				$o[$k] = $v;
			}
		}
		return $o;
	}

	public function getTrainingCost ()
	{
		/*
		return array
		(
			'iron' => 125,
			'grain' => 180,
			'gold' => 50
		);
		*/
		
		$stats = $this->getStats ();
		return $stats['cost'];
	}
	
	public function getTrainingDuration ()
	{
		return ceil ((60 * $this->getStat ('training_time', 20)) / GAME_SPEED_RESOURCES);
	}

	public function getConsumption ()
	{
		$stats = $this->getStats ();
		return $stats['upkeep'];
	}
	
	// Returns the amount of squares travelled per... I'm not sure per what ;)
	public function getSpeed ()
	{
		$stats = $this->getStats ();
		return $stats['speed'];
	}

	public function getCurrentConsumption ()
	{
		return $this->multiplyCost ($this->getConsumption ($this->village), $this->getTotalToFeed ());
	}

	public function getTrainingCost_text ()
	{
		$res = $this->getTrainingCost ($this->village);
		return $this->resourceToText ($res);
	}

	public function getConsumption_text ()
	{
		$res = $this->getConsumption ($this->village);
		return $this->resourceToText ($res);
	}

	public function getCurrentConsumption_text ()
	{
		$res = $this->getCurrentConsumption ($this->village);
		return $this->resourceToText ($res);
	}
	
	public function getDisplayName ($multiple = true)
	{
		return '<span class="unit">'.$this->getName ($multiple).'</span>';
	}

	public function getName ($multiple = true)
	{
		$text = Neuron_Core_Text::__getInstance ();
		
		$classname = $this->getUnitName ();
		$race = $this->getRace ()->getName ();
		
		$srealname = $classname;
		
		if (!$multiple)
		{
			$srealname .= '_1';
		}
		
		return $text->get ($srealname, $race, 'units',
				$text->get ($srealname, 'global', 'units',
					$text->get ($classname, $race, 'units',
						$text->get ($classname, 'global', 'units', $classname))));
	}

	public function resourceToText ($res, $showRunes = true, $dot = true)
	{
		return Dolumar_Buildings_Building::resourceToText ($res, $showRunes, $dot, $this->village);
	}
	
	public function getUnitId ()
	{
		return $this->getUnitId_static ($this->getClassName ());
	}

	/* A little solution for "hugh" monsters */
	public function getRequiredSpace ()
	{
		return $this->getStat ('size', 1);
	}
	
	public function getSize ()
	{
		return $this->getRequiredSpace ();
	}
	
	/*
		Return the amount of spaces these troops
		are currently using.
	*/
	public function getCurrentAmount ()
	{
		return $this->getCurrentSize ();
	}
	
	public function getCurrentSize ()
	{
		return $this->getTotalToFeed () * $this->getRequiredSpace ();
	}

	/*
		Que kill a unit and return how many actual units died on this shot
		(important for decimal numbers = "half dead" units)
	*/
	public function queKillUnits ($amount)
	{
		$floor = floor ($this->killedAmount + $amount - floor ($this->killedAmount));
	
		$this->killedAmount += $amount;
		$this->killedInRound += $amount;
		
		return $floor;
	}

	public function hasEternalLife ()
	{
		return $this->eternalLife;
	}
	
	public function getAmountInCombat ($defending = false)
	{
		if ($defending)
		{
			return $this->getDefendingAmount ();
		}
		else
		{
			return $this->getAvailableAmount ();
		}
	}
	
	public function getKilledUnits ()
	{
		return $this->killedAmount;
	}

	public function getKillUnitsQue ($defending = false)
	{
		return min ($this->getAmountInCombat ($defending), floor ($this->killedAmount));
	}
	
	public function resetKilledInRound ()
	{
		$this->killedInRound = 0;
	}
	
	public function getKilledInRound ($detailed = false)
	{
		$meh = $this->killedInRound;
		
		if (!$detailed)
		{
			$meh = floor ($meh);
		}
	
		return min ($this->getAmount (), $meh);
	}

	public function onKillUnits ($amount)
	{
		if ($this->objSquad)
		{
			$this->objSquad->withdrawUnits ($this, $amount);
		}
	}
	
	/*
		Battle status
	*/
	public function setBattleStatus ($iStatus)
	{
		$this->iBattleStatus = $iStatus;
	}
	
	public function getBattleStatus ()
	{
		return $this->iBattleStatus;
	}
	
	public function isBattleStatus ($status)
	{
		return $this->getBattleStatus () == $status;
	}
	
	public function isActiveInBattle ()
	{
		return $this->isBattleStatus (self::BATTLE_STATUS_NORMAL);
	}

	/*
		Merge the troops with these troops
		should only be used with idential troops ;-)
	*/
	public function mergeTroops ($objUnit)
	{
		$this->addAmount
		(
			$objUnit->getAvailableAmount (),
			$objUnit->getTotalAmount (),
			$objUnit->getTotalToFeed ()
		);
	}

	public function getImageUrl ()
	{
		$race = $this->getRace ()->getName ();
		
		$filename = strtolower ($race) . '_' . strtolower ($this->getUnitName ()).'.jpg';
		
		//return UNITIMAGE_URL . $filename;
		
		if (file_exists (UNITIMAGE_PATH . $filename))
		{
			return UNITIMAGE_URL . $filename;
		}
	
		return UNITIMAGE_URL . strtolower ($this->getUnitName ()).'.jpg';
	}

	public function setSquad ($squad)
	{
		$this->objSquad = $squad;
	}
	
	public function getSquad ()
	{
		return $this->objSquad;
	}

	/*
		Equipment
	*/
	public function addEquipment ($equipment)
	{
		$this->equipment[$equipment->getItemType ()] = $equipment;
	}

	public function getEquipment ()
	{
		return $this->equipment;
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
		
		4. The current slot (if there is one.)
		
		(last two are defined in the squad object)
	*/
	public function getEffects ()
	{
		$effs = $this->effects;
		
		if ($squad = $this->getSquad ())
		{
			$effs = array_merge ($effs, $squad->getEffects ());
		}
		else
		{
			$effs = array_merge ($effs, $this->getVillage ()->getEffects ());
		}
		
		$slot = $this->getBattleSlot ();
		if (isset ($slot))
		{
			foreach ($slot->getEffects () as $v)
			{
				$effs[] = $v;
			}
		}
		
		return $effs;
	}
	
	/*
		Battle slot
	*/
	public function setBattleSlot ($objSlot)
	{
		// When a squad moves from one slot to another,
		// do NOT recalculate the stats. Original stats are kept.
		// UPDATE 26/4/2011: DO calculate new stats.
		if ($this->curSlot === null)
		{
			//$this->reloadStats ();
			$this->setBattleStatus (self::BATTLE_STATUS_NORMAL);
		}
		
		$this->curSlot = $objSlot;
		
		$this->reloadStats ();
	}
	
	public function getBattleSlot ()
	{
		return $this->curSlot;
	}
	
	public function getMaxPerSquad ()
	{
		return 50;
	}
	
	/*
		Morale
	*/
	public function getMorale ()
	{
		return 100;
	}
	
	/*
		Battle: skip turn
	*/
	public function addSkipTurns ($amount)
	{
		$this->iSkipTurn += $amount;
	}
	
	public function isReadyForAction ()
	{
		if ($this->iSkipTurn > 0)
		{
			$this->iSkipTurn --;
			return false;
		}
		
		else
		{
			return true;
		}
	}
	
	public function setCurrentFrontage ($frontage)
	{
		$this->currentfrontage = $frontage;
	}
	
	public function getCurrentFrontage ()
	{
		return $this->currentfrontage;
	}
	
	public function getLogArray ()
	{
		return array ();
	}

	public function __destruct ()
	{
	
	}
	
	public function __toString ()
	{
		return $this->getDisplayName ();
	}
}
?>
