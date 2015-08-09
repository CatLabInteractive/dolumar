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

class Dolumar_Underworld_Map_ObjectManager 
	extends Neuron_GameServer_Map_Managers_MapObjectManager
{
	private $mission;
	private $fogofwar;

	public function __construct (Dolumar_Underworld_Models_Mission $mission)
	{
		$this->mission = $mission;
	}
	
	public function setFogOfWar (Dolumar_Underworld_Map_FogOfWar $fog)
	{
		$this->fogofwar = $fog;
	}
	
	private function getExploreStatus (Neuron_GameServer_Map_Location $location)
	{
		if (isset ($this->fogofwar))
		{
			return $this->fogofwar->getExploreStatus ($location);
		}
		
		return Dolumar_Underworld_Map_FogOfWar::VISIBLE;
	}

	/**
	*	Return all objects on one specific location
	*/
	public function getFromLocation (Neuron_GameServer_Map_Location $location, $useFogOfWar = true)
	{
		$area = new Neuron_GameServer_Map_Area ($location, 1);
		
		$objects = $this->getDisplayObjects ($area, $useFogOfWar);
		$out = array ();
		
		foreach ($objects as $v)
		{
			if ($v->getLocation ()->equals ($location))
			{
				$out[] = $v;
			}
		}
		
		return $out;
	}

	/**
	*	Return all display objects that are within $radius
	*	of $location (so basically loading a bubble)
	*/
	public function getDisplayObjects (Neuron_GameServer_Map_Area $area, $useFogOfWar = true)
	{		
		$objects = array ();
		
		$armies = Dolumar_Underworld_Mappers_ArmyMapper::getFromArea ($this->mission, $area);
		
		foreach ($armies as $v)
		{
			if (!$useFogOfWar || $this->getExploreStatus ($v->getLocation ()) == Dolumar_Underworld_Map_FogOfWar::VISIBLE)
			{
				$objects[] = $v;
			}
		}
		
		return $objects;
	}
	
	/**
	*	Move an object to another position
	*/	
	public function move 
	(
		Neuron_GameServer_Map_MapObject $object,
		Neuron_GameServer_Map_Location $location,
		Neuron_GameServer_Map_Date $start, 
		Neuron_GameServer_Map_Date $end
	)
	{
		// not implemented in this game.
		echo "yihaa";
	}
}
?>
