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

class Dolumar_Underworld_Map_Map
	extends Neuron_GameServer_Map_Map2D
{
	private $mission;
	private $fogofwar;
	private $sides;

	public function __construct (Dolumar_Underworld_Models_Mission $mission)
	{
		$this->mission = $mission;
	}

	public function getMission ()
	{
		return $this->mission;
	}

	/**
	* Return all spawn points
	*/
	public function getSpawnPoints (Dolumar_Underworld_Models_Side $side)
	{
		$out = array ();
		foreach ($this->getBackgroundManager ()->getSpawnpoints () as $v)
		{
			//if ($v->isSide ($side))

			// We need to call the objective to see if this is a good space
			if ($this->getMission ()->getObjective ()->isValidSpawnPoint ($side, $v))
			{
				$out[] = $v;
			}
		}
		return $out;
	}

	public function getFreeSpawnPoints (Dolumar_Underworld_Models_Side $side)
	{
		$out = array ();
		foreach ($this->getSpawnpoints ($side) as $v)
		{
			if (!$this->isSpawnPointOccupied($v))
			{
				$out[] = $v;
			}
		}

		return $out;
	}

    /**
     * @param Neuron_GameServer_Map_Location $location
     * @return bool
     */
	public function isSpawnPointOccupied(Neuron_GameServer_Map_Location $location)
    {
        return count ($this->getMapObjectManager ()->getFromLocation ($location)) > 0;
    }

	/**
	* Check all adjacent spots and return those that are free
	*/
	public function getFreeAdjacentSpots (Neuron_GameServer_Map_Location $location)
	{
		$locations = array ();
		$locations[] = $this->getBackgroundManager ()->getSingleLocation ($location->x () + 0, $location->y () + 1);
		$locations[] = $this->getBackgroundManager ()->getSingleLocation ($location->x () + 0, $location->y () - 1);
		$locations[] = $this->getBackgroundManager ()->getSingleLocation ($location->x () + 1, $location->y () + 0);
		$locations[] = $this->getBackgroundManager ()->getSingleLocation ($location->x () - 1, $location->y () + 0);

		$out = array ();

		foreach ($locations as $v)
		{
			if ($v->isPassable ())
			{
				if (count ($this->getMapObjectManager ()->getFromLocation ($v)) === 0)
				{
					$out[] = $v;
				}
			}
		}

		return $out;
	}
	
	public function getMinimalDistance ($start, $end)
	{
		return sqrt (pow ($start->x() - $end->x(), 2) + pow ($start->y() - $end->y(), 2) );
	}
	
	public function setFogOfWar (Dolumar_Underworld_Map_FogOfWar $fog)
	{
		$this->fogofwar = $fog;
		$this->getBackgroundManager ()->setFogOfWar ($fog);
		$this->getMapObjectManager ()->setFogOfWar ($fog);
	}
	
	/**
	*	Use these methods to declare the loaders.
	*/
	public function setBackgroundManager (Neuron_GameServer_Map_Managers_BackgroundManager $loader)
	{
		parent::setBackgroundManager ($loader);
		if (isset ($this->fogofwar))
		{
			$this->setFogOfWar ($fog);
		}
	}
	
	public function setExplored (Neuron_GameServer_Map_Location $location)
	{
		if (isset ($this->fogofwar))
		{
			$this->fogofwar->setExplored ($location);
		}
	}
	
	/**
	*	Return the correct path between two points,
	*	avoiding all objects (except same side objects) and
	*	unpassable locations.
	*/
	public function getPath 
	(
		Dolumar_Underworld_Models_Side $side, 
		Neuron_GameServer_Map_Location $start,
		Neuron_GameServer_Map_Location $end,
		$maxRadius,
		$isTargetAnObject = false
	)
	{
		// Prepare thze area that needs to be inspected
		$minx = $start->x () - $maxRadius;
		$miny = $start->y () - $maxRadius;
		
		$maxx = $start->x () + $maxRadius;
		$maxy = $start->y () + $maxRadius;
		
		$area = array ();
		for ($y = 0; $y < $maxRadius * 2; $y ++)
		{
			$area[$y] = array ();
			for ($x = 0; $x < $maxRadius * 2; $x ++)
			{ 
				$area[$y][$x] = $this->getPassCost ($minx + $x, $miny + $y);
			}
		}
		
		// Mark all objects as not passable
		$center = array 
		(
			$minx + (($maxx - $minx) / 2),
			$miny + (($maxy - $miny) / 2)
		);
		
		$range = $maxRadius * 2;
		
		$objarea = new Neuron_GameServer_Map_Area 
		(
			new Neuron_GameServer_Map_Location ($center[0], $center[1]),
			$range
		);
		
		$objects = $this->getMapObjectManager ()->getDisplayObjects ($objarea);
		
		foreach ($objects as $v)
		{
			$location = $v->getLocation ();
			$area[floor ($location[1]) - $miny][floor ($location[0]) - $minx] = 0;
		}
		
		$transformed_start = $start->transform (- $minx, - $miny);
		$transformed_end = $end->transform (- $minx, - $miny);
		
		// If the target is an object,
		// it should always be passable
		if ($isTargetAnObject)
		{
			$area[$transformed_end->y ()][$transformed_end->x ()] = 1;
		}
		
		// Path finder
		$pathfinder = new Neuron_GameServer_Map_Pathfinder ();
		$pathfinder->setMap ($area);
		$path = $pathfinder->getPath ($transformed_start, $transformed_end, $maxRadius);
		
		if ($path)
		{
			$path->transform (+ $minx, + $miny);
		}
		
		return $path;
	}
	
	/**
	*	Return the cost to pass trough this area
	*/
	private function getPassCost ($x, $y)
	{
		$location = $this->getBackgroundManager ()->getSingleLocation ($x, $y);
		
		if ($location->isPassable ())
		{
			return 1;
		}
		
		else
		{
			return 0;
		}
	}

	public function getInitialLocation ()
	{
		$player = Neuron_GameServer::getPlayer ();

		if ($player)
		{
			$side = $this->getMission ()->getPlayerSide ($player);

			$armies = Dolumar_Underworld_Mappers_ArmyMapper::getFromSide ($this->getMission (), $side);

			foreach ($armies as $v)
			{
				if ($v->isLeader ($player))
				{
					$location = $v->getLocation ();
					$l = array ($location->x (), $location->y ());

					return $l;
				}
			}

			if (count ($armies) > 0)
			{
				$location = $armies[0]->getLocation ();
				return array ($location->x (), $location->y ());
			}
		}

		return array (0, 0);
	}
}
