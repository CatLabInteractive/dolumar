<?php
/**
 *  Dolumar, browser based strategy game
 *  Copyright (C) 2009 Thijs Van der Schaeghe
 *  CatLab Interactive bvba, Gent, Belgium
 *  http://www.catlab.eu/
 *  http://www.dolumar.com/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

class Dolumar_Technology_Technology implements Dolumar_Players_iBoost, Neuron_GameServer_Interfaces_Logable
{
	public static function getTechnology ($technology, $objRace = null)
	{
		$oCost = array ('wood' => 1000);
		$duration = 60 * 5;

		$stats = Neuron_Core_Stats::__getInstance ();

		$cost = $stats->getSection ($technology, 'technology');

		// Only fetch the 6 resources
		$o = array ();

		$ini = array ('gold', 'grain', 'wood', 'stone', 'iron', 'gems');
		foreach ($ini as $v)
		{
			if (isset ($cost[$v]))
			{
				$o[$v] = $cost[$v];
			}
		}

		if (count ($o) != 0)
		{
			$oCost = $o;
		}

		// Check for duration
		if (isset ($cost['duration']))
		{
			$duration = $cost['duration'];
		}
		
		// Fetch requirements
		$requirements = array ();
		$requirements['level'] = isset ($cost['level']) ? intval ($cost['level']) : 0;
		
		// Check for other requirements
		$requirements['technologies'] = array ();
		if (isset ($cost['requires']))
		{
			$reqs = explode (',',$cost['requires']);
			foreach ($reqs as $v)
			{
				$requirements['technologies'][] = trim ($v);
			}
		}
		
		$requirements['races'] = array ();
		if (isset ($cost['race']))
		{
			$reqs = explode (',',$cost['race']);
			foreach ($reqs as $v)
			{
				$requirements['races'][] = Dolumar_Races_Race::getRace (trim ($v));
			}
		}
		
		// Check for class type
		if (isset ($cost['type']))
		{
			$classname = 'Dolumar_Technology_'.$cost['type'];
			if (class_exists ($classname))
			{
				$obj = new $classname ($technology, $oCost, $duration, $requirements);
				$obj->setStats ($cost);
				return $obj;
			}
		}
		
		// Not elseif, if not returned yet, there is something wrong.
		if ($objRace && class_exists ($objRace->getName ().'_Dolumar_Technology_'.$technology))
		{
			$classname = $objRace->getName ().'_Dolumar_Technology_'.$technology;
			return new $classname ($technology, $oCost, $duration, $requirements);
		}

		elseif (class_exists ('Dolumar_Technology_'.$technology))
		{
			$classname = 'Dolumar_Technology_'.$technology;
			return new $classname ($technology, $oCost, $duration, $requirements);
		}
		else
		{
			return new self ($technology, $oCost, $duration, $requirements);
		}
	}
	
	private $objRace;
	private $technology;
	private $researchCost;
	private $researchDuration;
	private $requirements = 0;
	private $level;

	public function __construct ($technology, $cost, $duration, $requirements = array (), $objRace = null, $level = 1)
	{
		if (!is_array ($cost))
		{
			$cost = array ($cost);
		}
	
		$this->technology = $technology;
		$this->researchCost = $cost;
		$this->researchDuration = $duration;
		$this->requirements = $requirements;
		$this->objRace = $objRace;
		
		$this->level = $level;
	}
	
	/*
		Return a technology object from ID
	*/
	public static function getFromId ($id, $inRace = null)
	{
		$id = explode ('.', $id);
		
		if (count ($id) > 1)
		{
			$race = Dolumar_Races_Race::getFromId (intval ($id[1]));
		}
		else
		{
			$race = $inRace;
		}
		
		$id = intval ($id[0]);
	
		$db = Neuron_Core_Database::__getInstance ();
		$l = $db->select
		(
			'technology',
			array ('techName'),
			"techId = {$id}"
		);

		if (count ($l) == 1)
		{
			return self::getTechnology ($l[0]['techName'], $race);
		}
		else
		{
			return false;
		}
	}
	
	/*
		This function is called right after construct.
		$stats contains the stats values found in technology.ini
	*/
	public function setStats ($stats)
	{
		// In default situation: do nothing.
	}

	public function getId ()
	{
		$db = Neuron_Core_Database::__getInstance ();
		$l = $db->select
		(
			'technology',
			array ('techId'),
			"techName = '".$db->escape ($this->technology)."'"
		);

		if (count ($l) == 1)
		{
			if ($this->objRace instanceof Dolumar_Races_Race)
			{
				return $l[0]['techId'].'.'.$this->objRace->getId ();
			}
			else
			{
				return $l[0]['techId'];
			}
		}
		else
		{
			return false;
		}
	}
	
	public function getDisplayName ()
	{
		return $this->getName ();
	}

	public function getName ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		return $text->get ($this->technology, 'technology', 'village', $this->technology);
	}

	public function getDescription ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		return $text->get ($this->technology, 'techdesc', 'village', false);
	}

	public function getString ()
	{
		return $this->technology;
	}

	public function getResearchCost ()
	{
		return $this->researchCost;
	}

	public function getDuration ()
	{
		return $this->researchDuration / GAME_SPEED_EFFECTS;
	}

	/*
		Return TRUE if the building level is
		good enough to research this technology.
	*/
	public function getMinLevel ()
	{
		return isset ($this->requirements['level']) && $this->requirements['level'] > 0 ? $this->requirements['level'] : 1;
	}
	
	private function checkLevel ($objBuilding)
	{
		if (isset ($this->requirements['level']))
		{
			return $objBuilding->getLevel () >= $this->requirements['level'];
		}
		return true;
	}
	
	public function checkRace ($objBuilding)
	{
		if (count ($this->requirements['races']) == 0)
		{
			return true;
		}
		
		$race = $objBuilding->getRace ();
		
		foreach ($this->requirements['races'] as $v)
		{
			if ($race->equals ($v))
			{
				return true;
			}
		}
		
		return false;
	}
	
	public function getRequiredTechnologies ()
	{
		$requires = $this->requirements['technologies'];
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
	
	/*
		Return TRUE if this village has the required
		technologies researched.
	*/
	private function checkRequirements ($objBuilding)
	{
		$objVillage = $objBuilding->getVillage ();
		foreach ($this->requirements['technologies'] as $v)
		{
			if (!$objVillage->hasTechnology ($v))
			{
				return false;
			}
		}
		return true;
	}

	public function canResearch ($building)
	{
		return $this->checkLevel ($building) 
			&& $this->checkRequirements ($building)
			&& $this->checkRace ($building)
		;
	}
	
	public function getLogArray ()
	{
		return array
		(
			'name' => $this->getName (),
			'description' => $this->getDescription ()
		);
	}
	
	// Have to be defined since we're using the interface iBoost
	public function procBuildingCost ($resources, $objBuilding) { return $resources; }
	public function procBuildCost ($resources, $objBuilding) { return $resources; }
	public function procUpgradeCost ($resources, $objBuilding) { return $resources;  }
	public function procCapacity ($resources, $objBuilding) { return $resources; }
	public function procIncome ($resources, $objBuilding) { return $resources; }
	public function procUnitStats (&$stats, $objUnit) {}
	
	public function __toString ()
	{
		return $this->getDisplayName ();
	}
	
	public function __destruct ()
	{
	
	}
}
?>
