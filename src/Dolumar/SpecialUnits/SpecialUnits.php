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

abstract class Dolumar_SpecialUnits_SpecialUnits
{
	abstract public function getWindowAction ();

	private $objBuilding;
	private $id;
	
	private $level;
	private $village;
	
	private $objLocation = null;
	
	private $iMoveStart = null;
	private $iMoveEnd = null;
	
	private $isAlive = true;
	
	private $race;
	
	public function __construct ($objBuilding = false)
	{
		$this->objBuilding = $objBuilding;
		if ($objBuilding)
		{
			$this->level = $objBuilding->getLevel ();
			$this->village = $objBuilding->getVillage ();
			
			$this->setRace ($this->village->getRace ());
		}
	}
	
	public static function getFromId ($id)
	{
		static $data;
		
		if (!isset ($data))
		{
			$db = Neuron_Core_Database::__getInstance ();
			
			$data = array ();
			
			$l = $db->select ('specialUnits', array ('*'));
			foreach ($l as $v)
			{
				$data[$v['s_id']] = 'Dolumar_SpecialUnits_' . $v['s_name'];
			}
		}
		
		if (isset ($data[$id]))
		{
			return new $data[$id] ();
		}
		else
		{
			return false;
		}
	}
	
	public function getUnitId ()
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$l = $db->select
		(
			'specialUnits',
			array ('s_id'),
			"s_name = '".$this->getClassName ()."' "
		);
		
		if (count ($l) == 0)
		{
			return $db->insert
			(
				'specialUnits',
				array ('s_name' => $this->getClassName ())
			);
		}
		else
		{
			return $l[0]['s_id'];
		}
	}
	
	
	public function setId ($id)
	{
		$this->id = $id;
	}
	
	public function getId ()
	{
		return intval ($this->id);
	}
	
	public function setLevel ($level)
	{
		$this->level = $level;
	}
	
	public function setVillage ($objVillage)
	{
		$this->village = $objVillage;
	}
	
	public function getLevel ()
	{
		return $this->level;
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
	
	public function setRace ($objRace)
	{
		$this->race = $objRace;
	}
	
	/*
		Return the race of this unit.
	*/
	public function getRace ()
	{
		return $this->race;
	}
	
	public function getName ($multiple = false, $includeLevel = false)
	{
		$text = Neuron_Core_Text::__getInstance ();
		
		$class = $this->getClassName () . ($multiple ? '2' : '1');
		
		$sRace = isset ($this->race) ? $this->race->getName () : null;
		
		$out = $text->get ($class, $sRace, 'specialunits', 
			$text->get ($class, 'global', 'specialunits', $class));
			
		if ($includeLevel)
		{
			$out .= ' lvl ' . $this->getLevel ();
		}
		
		return $out;
	}
	
	public function setCustomName ($name)
	{
		
	}
	
	public function getCustomName ()
	{
		return null;
	}
	
	public function getDisplayName ()
	{
		$name = $this->getCustomName ();
		if (!empty ($name))
		{
			$name .= ' (' . $this->getName (false, true) . ')';
		}
		else
		{
			$name = $this->getName (false, true);
		}
		
		return $name;
	}
	
	public function getBuilding ()
	{
		return $this->objBuilding;
	}
	
	public function getTrainDuration ()
	{
		return (60 * 20) / GAME_SPEED_RESOURCES;
	}

	public function getTrainingCost ()
	{
		return array ('gems' => 50);
	}
	
	/*
		Should return a list of battle actions.
		Return empty array if not able to send in battle.
	*/
	public function getEffects ()
	{
		$building = $this->getBuilding ();
	
		if ($building instanceof Dolumar_Buildings_SpecialUnits)
		{
			return $building->getKnownEffects ();
		}
		else
		{
			return array ();
		}
	}
	
	public function getEffect ($data)
	{
		if (! $data instanceof Dolumar_Effects_Effect)
		{
			$data = Dolumar_Effects_Effect::getFromId ($data);
		}
	
		foreach ($this->getEffects () as $v)
		{
			if ($data->equals ($v))
			{
				return $v;
			}
			/*
			if (strtolower ($v->getClassName ()) == $sName || $v instanceof $sOrgName)
			{
				return $v;
			}
			*/
		}
		
		return false;
	}
	
	/*
	public function getBattleAction ($sName)
	{
		$sName = 'Dolumar_Effects_Battle_'.str_replace ('Dolumar_Effects_Battle_', '', $sName);
		foreach ($this->getBattleActions () as $v)
		{
			if ($v instanceof $sName)
			{
				return $v;
			}
		}
		return false;
	}
	*/
	
	/*
		Set the current location.
		This is important for thieves.
	*/
	public function setLocation ($objVillage, $moveStart = 0, $moveEnd = 0)
	{	
		$this->objLocation = $objVillage;
		$this->iMoveStart = $moveStart;
		$this->iMoveEnd = $moveEnd;
	}
	
	public function getLocation ()
	{
		if (!empty ($this->objLocation))
		{
			return $this->objLocation;
		}
		else
		{
			return $this->objBuilding->getVillage ();
		}
	}
	
	/*
		Move the unit
	*/
	public function moveUnit ($target)
	{
		// Calculate distance
		$distance = Dolumar_Map_Map::getDistanceBetweenVillages ($this->getLocation (), $target);
		$duration = $this->getTravelDuration ($distance);
		
		// Update
		$db = Neuron_Core_Database::__getInstance ();
		
		$db->update
		(
			'villages_specialunits',
			array
			(
				'vsu_moveStart' => Neuron_Core_Tools::timestampToMysqlDatetime (time ()),
				'vsu_moveEnd' => Neuron_Core_Tools::timestampToMysqlDatetime (time () + $duration),
				'vsu_location' => $target->getId ()
			),
			"vsu_id = ".$this->getId ()
		);
	}
	
	/*
		Returns true if this unit is moving
	*/
	public function isMoving ()
	{
		return $this->iMoveEnd > time ();
	}
	
	public function getArrivalDate ()
	{
		return $this->iMoveEnd;
	}
	
	/*
		Travel duration
	*/
	public function getTravelDuration ($distance)
	{
		return ceil (($distance * 20) / GAME_SPEED_MOVEMENT);
	}
	
	/*
		This function just changes the boolean.
	*/
	public function setAlive ($alive)
	{
		$this->isAlive = $alive;
	}
	
	public function killUnit ()
	{
		$db = Neuron_Core_Database::__getInstance ();
	
		$id = $this->getId ();
		if ($id > 0)
		{
			$db->remove
			(
				'villages_specialunits',
				"vsu_id = ".$id
			);
		}
		
		$this->isAlive = false;
	}
	
	/*
		Check if this special unit is still alive.
	*/
	public function isAlive ()
	{
		return $this->isAlive;
	}
	
	/*
		Triggers
	*/
	public function onSuccess () {}
	
	public function onFail () {}
	
	public function onBattleSuccess () {}
	
	public function onBattleFail () {}
	
	public function __destruct ()
	{
		unset ($this->race);
		unset ($this->village);
		unset ($this->objBuilding);
	}
}
?>
