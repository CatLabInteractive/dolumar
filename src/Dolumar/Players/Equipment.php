<?php
class Dolumar_Players_Equipment implements Neuron_GameServer_Interfaces_Logable
{
	public static function getItemTypes ()
	{
		return array
		(
			'weapon',
			'armour'
		);
	}

	public static function getEquipmentName ($key)
	{
		$text = Neuron_Core_Text::__getInstance ();
		return $text->get ($key, 'types', 'equipment');
	}

	public static function getEquipmentId ($name)
	{
		$db = Neuron_Core_Database::__getInstance ();
	
		// Fetch equipment name
		$e_id = $db->select
		(
			'equipment',
			array ('e_id'),
			"e_name = '".$name."'"
		);

		if (count ($e_id) == 0)
		{
			$e_id = $db->insert
			(
				'equipment',
				array
				(
					'e_name' => $name
				)
			);
		}
		else
		{
			$e_id = $e_id[0]['e_id'];
		}

		return $e_id;
	}
	
	public static function getAllEquipment ()
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$data = $db->query
		("
			SELECT	
				*
			FROM
				equipment
		");
		
		$out = array ();
		foreach ($data as $v)
		{
			$out[] = new self ($v['e_name']);
		}
		
		return $out;
	}
	
	public static function getFromId ($id)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$data = explode (':', $id);
		
		$id = intval ($data[0]);
		$level = isset ($data[1]) ? intval ($data[1]) : 1;
		$amount = isset ($data[2]) ? intval ($data[2]) : 0;
		
		$data = $db->query
		("
			SELECT
				*
			FROM
				equipment
			WHERE
				e_id = {$id}
		");
		
		if (count ($data) == 1)
		{
			$equipment = new self ($data[0]['e_name'], $level);
			$equipment->addAmount ($amount);
			return $equipment;
		}
		else
		{
			return false;
		}
	}

	public static function getItemTypeId_static ($name)
	{
		$id = array_search ($name, self::getItemTypes ());
		return (int)$id;
	}

	private $id, $data, $amount = 0, $usedAmount = 0, $cost, $duration, $stats;
	
	private $requirements;
	private $level;
	
	public function __construct ($id, $level = 1)
	{
		$this->id = $id;
		$this->level = intval ($level);
		
		$this->init ();
	}
	
	public function getLogArray ()
	{
		return array 
		(
			'equipment' => $this->getName ()
		);
	}
	
	public function getDisplayName ()
	{
		$desc = str_replace ("\n", " ", $this->getStats_text ());
		return '<span class="equipment" title="'.$desc.'">'.$this->getName ().'</span>';
	}
	
	public function __toString ()
	{
		return $this->getDisplayName ();
	}
	
	private function init ()
	{
		// TODO: put in seperate function and only load if needed.
		$stats = Neuron_Core_Stats::__getInstance ();
		
		$this->stats = array ();
		
		$data = $stats->getSection ($this->id, 'equipment');
		$data['name'] = $this->id;

		$this->cost = array ();
		$this->duration = isset ($data['duration']) ? ceil ($data['duration'] * 300) : 300;
		
		$this->requirements = array ();
		$this->requirements['technologies'] = array ();
		
		// Only resources are costs
		$resources = array ('gold', 'grain', 'wood', 'stone', 'iron', 'gems');

		foreach ($data as $k => $v)
		{
			if (in_array ($k, $resources))
			{
				$this->cost[$k] = $v;
			}
			elseif ($k == 'requires')
			{
				$techs = explode (',', $v);
				foreach ($techs as $tech)
				{
					$this->requirements['technologies'][] = $tech;
				}
			}
			elseif ($k == 'level')
			{
				$this->requirements['level'] = intval ($v);
			}
			
			elseif ($k == 'race')
			{
				$this->requirement['race'] = $v;
			}
			
			else
			{
				$this->stats[$k] = $v;
			}
		}

		if (count ($this->cost) == 0)
		{
			$this->cost = array ('iron' => 15);
		}
		
		$this->adaptStatsToLevel ();
	}
	
	private function adaptStatsToLevel ()
	{
		foreach ($this->stats as $k => $v)
		{		
			if (is_numeric ($v))
			{
				$this->stats[$k] = $this->calculateBonusedAmount ($v);
			}
			
			else if (strpos ($v, '%') > 0)
			{
				$this->stats[$k] = $this->calculateBonusedAmount (intval ($v)) . '%';
			}
		}
	}
	
	private function calculateBonusedAmount ($number)
	{
		if ($number < 0)
		{
			return $number;
		}
	
		$bonus1 = $this->getLevel () - 1;
		$bonus2 = $number - ceil ($number * (1 + ( ($this->getLevel () - 1) * 0.05)));
		
		return $number + max ($bonus1, $bonus2);
	}
	
	public function setLevel ($level)
	{
		$this->level = $level;
		$this->init ();
	}
	
	public function getLevel ()
	{
		return $this->level;
	}

	public function getId ($incLevel = true)
	{
		$id = $this->getMysqlId ($incLevel);
		
		/*
		if ($incLevel && $this->getLevel () > 1)
		{
			$id .= ':' . $this->getLevel ();
		}
		*/
		
		return $id;
	}

	public function getStats ()
	{
		return $this->stats;
	}
	
	public function getCategory ()
	{
		return isset ($this->stats['category']) ? $this->stats['category'] : 'misc';
	}

	public function getStats_text ()
	{
		$ignore = array ('type', 'name', 'duration', 'category', 'race');

		$text = Neuron_Core_Text::__getInstance ();
	
		$out = '';
		foreach ($this->stats as $k => $v)
		{
			if (!in_array ($k, $ignore))
			{
				$amount = ($v > 0 ? '+' : '-') . abs ($v);
				$out .= $text->get ($k, 'unitStats', 'main') . ": " . $amount . "\n";
			}
		}
		$out = substr ($out, 0, -1);
		
		$races = $this->getEquipableRaces ();
		if ($races)
		{
			$i = 0;
			$max = count ($races);
			$last = $max - 1;
			
			$out .= "\n";
		
			$txt = "";
			foreach ($races as $v)
			{
				$txt .= $v->getRaceName ();
				
				if ($i == $last - 1)
				{
					$txt .= ' '.$text->get ('and', 'main', 'main').' ';
				}
				elseif ($i == $last)
				{
					$txt .= '.';
				}
				else
				{
					$txt .= ', ';
				}
				
				$i ++;
			}
			//$txt .= "[/i]";
			
			$out .= Neuron_Core_Tools::putIntoText 
			(
				$text->get ('racelimit', 'stats', 'equipment'),
				array
				(
					'races' => $txt
				)
			);
		}
		
		return $out;
	}

	public function getMysqlId ($incLevel = true)
	{
		$item = self::getEquipmentId ($this->getItemName ());
		
		$level = $this->getLevel ();
		if ($level > 1 && $incLevel)
		{
			$item .= ':' . $level;
		}
		
		return $item;
	}

	public function addAmount ($amount)
	{
		$this->amount += $amount;
	}

	public function addUsedAmount ($amount)
	{
		$this->usedAmount += $amount;
	}

	public function getAvailableAmount ()
	{
		return $this->amount - $this->usedAmount;
	}

	public function getAmount ()
	{
		return $this->amount;
	}

	public function getItemType ()
	{
		return isset ($this->stats['type']) ? $this->stats['type'] : 'weapon';
	}

	public function getItemTypeId ($classname = false)
	{
		return self::getItemTypeId_static ($this->getItemType ());
	}

	public function getTypeName ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		return $text->get ($this->getItemType (), 'types', 'equipment');
	}

	public function getItemName ()
	{
		return isset ($this->stats['name']) ? $this->stats['name'] : $this->getItemType ();
	}

	public function getName ($multiple = false)
	{
		$text = Neuron_Core_Text::__getInstance ();

		$singular = $text->get ($this->getItemName (), 'equipment', 'equipment', $this->getItemName ());

		if ($multiple)
		{
			$txt = $text->get ($this->getItemName (), 'equipments', 'equipment', $singular);
		}
		else
		{
			$txt = $singular;
		}
		
		$level = $this->getLevel ();
		
		// One or more?
		$wtext = $text->get ('level'.$level, 'quality', 'equipment', false);
		if ($multiple)
		{
			$wtext = $text->get ('level'.$level, 'qualities', 'equipment', $wtext);
		}
		
		// Types
		$type = $this->getItemType ();
		
		$wtext = $text->get ('level'.$level, $type.'-quality', 'equipment', $wtext);
		if ($multiple)
		{
			$wtext = $text->get ('level'.$level, $type.'-qualities', 'equipment', $wtext);
		}
		
		if ($wtext)
		{
			$wtext = Neuron_Core_Tools::putIntoText
			(
				$wtext,
				array
				(
					'equipment' => $txt
				)
			);
		}
		else
		{
			$wtext = $txt . ' ' . $level;
		}
		
		return $wtext;
	}

	public function getCraftCost ($village = null, $amount = 1)
	{
		if ($amount == 1)
		{
			$cost = $this->cost;
		}
		else
		{
			$o = array ();
			foreach ($this->cost as $k => $v)
			{
				if (is_numeric ($v))
				{
					$o[$k] = floor ($v * $amount);
				}
				else
				{
					$o[$k] = $v;
				}
			}
			$cost = $o;
		}
		
		if (isset ($village))
		{
			foreach ($village->getEffects () as $v)
			{
				$cost = $v->procEquipmentCost ($cost, $this);
			}
		}
		
		return $cost;
	}
	
	/*
		Calculate the cost to upgrade this
		equipment to level $level
		
		(will use current level + 1 if level not defined)
	*/
	public function getUpgradeCost ($village, $level = null)
	{
		if (!isset ($level))
		{
			$level = $this->getLevel () + 1;
		}
		
		$cost = $this->getCraftCost ($village);
		
		foreach ($cost as $k => $v)
		{
			$cost[$k] = (($v + 2000) * 5 * $level) + 10000;
		}
		
		return $cost;
	}
	
	/*
		Return TRUE if this building is upgradable to
		$level. Use current level + 1 if level is not defined.
	*/
	public function isUpgradeable ($village, $level = null)
	{
		if (!isset ($level))
		{
			$level = $this->getLevel () + 1;
		}
		
		return $level <= 10;
	}

	public function getCraftDuration ($village, $amount = 1)
	{
		$duration = ($this->duration * $amount) / GAME_SPEED_RESOURCES;
		
		foreach ($village->getEffects () as $v)
		{
			$duration = $v->procEquipmentDuration ($duration, $this);
		}
		
		return $duration;
	}

	public function getCraftCost_text ($village = null)
	{
		return $this->resourceToText ($this->getCraftCost ($village), true, false, $village);
	}

	public function resourceToText ($res, $showRunes = true, $dot = true, $village)
	{
		return Dolumar_Buildings_Building::resourceToText ($res, $showRunes, $dot, $village);
	}
	
	/*
		This function returns TRUE if an item can be crafted
		in selected building. Requirements are (at the moment) solely based
		on the technologies.
	*/	
	public function canCraftItem ($objBuilding)
	{
		return 
			$this->checkLevel ($objBuilding) && 
			$this->checkRace ($objBuilding->getVillage ()->getRace ()) && 
			$this->checkRequirements ($objBuilding) 
		;
	}
	
	public function canEquip (Dolumar_Units_Unit $unit)
	{
		return $this->checkRace ($unit->getVillage ()->getRace ());
	}
	
	private function getEquipableRaces ()
	{
		if (isset ($this->requirement['race']))
		{
			$races = explode (',', $this->requirement['race']);
			
			$out = array ();
			
			foreach ($races as $v)
			{
				$out[] = Dolumar_Races_Race::getRace ($v);
			}
			
			return $out;
		}
		
		return false;
	}
	
	private function checkRace (Dolumar_Races_Race $race)
	{
		if (isset ($this->requirement['race']))
		{
			$race = strtolower ($race->getName ());
			
			$races = explode (',', $this->requirement['race']);
			
			$found = false;
			foreach ($races as $v)
			{
				if ($race == strtolower ($v))
				{
					$found = true;
				}
			}
			
			return $found;
		}
		
		return true;
	}
	
	public function getRequiredLevel ()
	{
		return isset ($this->requirements['level']) ? $this->requirements['level'] : 0;
	}
	
	private function checkLevel ($objBuilding)
	{
		return $objBuilding->getLevel () >= $this->getRequiredLevel ();
	}
	
	public function getRequiredTechnologies ()
	{
		if (isset ($this->requirements['technologies']))
		{
			$out = array ();
			foreach ($this->requirements['technologies'] as $v)
			{
				$out[] = Dolumar_Technology_Technology::getTechnology ($v);
			}
			return $out;
		}
		return array ();
	}	
	
	private function checkRequirements ($objBuilding)
	{	
		$objVillage = $objBuilding->getVillage ();
		foreach ($this->getRequiredTechnologies () as $v)
		{
			if (!$objVillage->hasTechnology ($v))
			{
				return false;
			}
		}
			
		return true;
	}
	
	
	public function getNextLevel ()
	{
		return new self ($this->id, $this->getLevel () + 1);
	}
}
?>
