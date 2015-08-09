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

class Dolumar_Underworld_Map_FogOfWar
{
	// CONFIG
	const SIGHT_DISTANCE = 3.5;
	const EXPLORE_DISTANCE = 5.5;

	// ENUM
	const VISIBLE = 1;
	const EXPLORED = 2;
	const UNEXPLORED = 3;
	
	private $side;
	private $mission;
	
	private $allMyLocations = null;
	private $exploredLocations = null;
	
	public function __construct (Dolumar_Underworld_Models_Mission $mission, Dolumar_Underworld_Models_Side $side)
	{
		$this->side = $side;
		$this->mission = $mission;
	}
	
	private function loadAllMyLocations ()
	{
		if (!isset ($this->allMyLocations))
		{
			$this->allMyLocations = array ();
		
			// Load all me currents objects
			$all = Dolumar_Underworld_Mappers_ArmyMapper::
				getFromSide ($this->mission, $this->side);
		
			foreach ($all as $v)
			{
				$this->allMyLocations[] = $v->getLocation ();
			}
			
			$this->exploredLocations = Dolumar_Underworld_Mappers_ExploredMapper::
				getExploredLocations ($this->mission, $this->side);
		}
	}
	
	public function getExploreStatus (Neuron_GameServer_Map_Location $location)
	{
		$this->loadAllMyLocations ();
	
		// Is within visible area?
		foreach ($this->allMyLocations as $v)
		{
			$distance = $v->getDistance ($location);
		
			if ($distance <= self::SIGHT_DISTANCE)
			{
				return self::VISIBLE;
			}
		}
		
		// Is within visible area?
		foreach ($this->allMyLocations as $v)
		{
			$distance = $v->getDistance ($location);

			if ($distance <= self::EXPLORE_DISTANCE)
			{
				return self::EXPLORED;
			}
		}

		// Is within explored area?
		foreach ($this->exploredLocations as $v)
		{
			if ($v->getDistance ($location) <= self::EXPLORE_DISTANCE)
			{
				return self::EXPLORED;
			}
		}
	
		return self::UNEXPLORED;
	}
	
	public function setExplored (Neuron_GameServer_Map_Location $location)
	{
		Dolumar_Underworld_Mappers_ExploredMapper::insert ($this->mission, $this->side, $location);
	}
}
?>
