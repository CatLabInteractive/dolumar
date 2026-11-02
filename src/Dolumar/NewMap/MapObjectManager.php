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

class Dolumar_NewMap_MapObjectManager extends Neuron_GameServer_Map_Managers_MapObjectManager
{
	private $map;
	
	public function __construct (Dolumar_Map $map)
	{
		$this->map = $map;
	}
	
	/**
	*	Return all display objects that are within $radius
	*	of $location (so basically loading a bubble)
	*/
	public function getDisplayObjects (Neuron_GameServer_Map_Area $area)
	{
		$profiler = Neuron_Profiler_Profiler::getInstance ();
	
		// Let's replace this :)
		$out = array ();
		
		$squarePoints = array
		(
			array ($area->getCenter ()->x (), $area->getCenter ()->y ())
		);
		
		$buildingSQL = Dolumar_Map_Map::getBuildingsFromLocations ($squarePoints, max ($area->getRadius (), 3));
	
		$objects = array ();
		foreach ($buildingSQL as $buildingV)
		{
			$profiler->start ('Initializing building');
		
			$profiler->start ('Fetching building race object');
			$race = Dolumar_Races_Race::getRace ($buildingV['race']);
			$profiler->stop ();
		
			$profiler->start ('Fetching building object');
			
			$b = Dolumar_Buildings_Building::getBuilding 
			(
				$buildingV['buildingType'], 
				$race, 
				$buildingV['xas'], $buildingV['yas']
			);
			
			$village = Dolumar_Players_Village::getVillage ($buildingV['village']);
			
			$b->setVillage ($village);
			
			$profiler->stop ();
		
			$profiler->start ('Setting data');
			$b->setData ($buildingV['bid'], $buildingV);

			if ($buildingV['destroyDate'] > 0 && $buildingV['destroyDate'] < NOW)
			{
				$b->setDestroyed (true);
			}
			
			$profiler->stop ();
		
			$profiler->start ('Assigning building to array');
			//$buildings[floor ($buildingV['xas'])][floor ($buildingV['yas'])][] = $b;
			
			//$objects[] = new Dolumar_Map_Object ($b);
			list ($x, $y) = $b->getLocation ();
			
			$object = new Dolumar_NewMap_Object ($b);
			$object->setLocation (new Neuron_GameServer_Map_Location ($x, $y));
			$objects[] = $object;
			
			
			$profiler->stop ();
		
			$profiler->stop ();
		}
		
		return $objects;		
	}
	
	const MOVING_ROWS_CACHE_KEY = 'dolumar:movingrows';
	const MOVING_ROWS_CACHE_TTL = 20;
	const POLL_SECONDS = 30;

	/**
	*	Armies (attacks, returns, squad moves) touching any of $areas, for
	*	the map/objects endpoint. $viewer is the player looking, or null.
	*
	*	@param Neuron_GameServer_Map_Area[] $areas
	*	@return Dolumar_NewMap_Army[]
	*/
	public function getMovingObjects (array $areas, $viewer = null)
	{
		$now = NOW;
		$rows = $this->loadMovingRows ($now);

		$out = array ();

		foreach ($rows['battles'] as $row)
		{
			if (!Dolumar_NewMap_MovingRows::touchesAreas (Dolumar_NewMap_MovingRows::pointsOfRow ($row, 'route'), $areas))
			{
				continue;
			}

			$army = Dolumar_NewMap_ArmyFactory::fromBattleRow ($row, $now, self::POLL_SECONDS);
			if ($army)
			{
				$out[] = $army;
			}
		}

		foreach ($rows['squads'] as $row)
		{
			if (!Dolumar_NewMap_MovingRows::touchesAreas (Dolumar_NewMap_MovingRows::pointsOfRow ($row, 'sc_route'), $areas))
			{
				continue;
			}

			$army = Dolumar_NewMap_ArmyFactory::fromSquadRow ($row, $now);
			if ($army)
			{
				$out[] = $army;
			}
		}

		$related = is_object ($viewer) ? self::relatedVillageIds ($viewer) : array ();

		return self::filterForViewer ($out, $related, $areas, $now);
	}

	/**
	*	Related armies are sent whenever their route touches the viewed areas
	*	(the client draws the whole route). An unrelated army is only sent
	*	while its current position is inside those areas, so a viewer cannot
	*	learn from the response that an army is heading their way.
	*
	*	@param Dolumar_NewMap_Army[] $armies
	*	@param int[] $relatedVillageIds
	*	@param Neuron_GameServer_Map_Area[] $areas
	*	@return Dolumar_NewMap_Army[]
	*/
	public static function filterForViewer (array $armies, array $relatedVillageIds, array $areas, $now)
	{
		$out = array ();
		foreach ($armies as $army)
		{
			if (!$army->isRelatedTo ($relatedVillageIds))
			{
				$location = $army->getLocation ($now);
				if (!Dolumar_NewMap_MovingRows::touchesAreas (array (array ($location->x (), $location->y ())), $areas))
				{
					continue;
				}
			}
			$out[] = $army;
		}
		return $out;
	}

	/**
	*	The in-flight battle and squad rows, shared by all viewers and cached
	*	for a few seconds (the list is global; relatedness is applied later).
	*/
	private function loadMovingRows ($now)
	{
		$cache = Neuron_Core_Memcache::getInstance ();

		return Dolumar_NewMap_MovingRows::cached
		(
			$cache,
			self::MOVING_ROWS_CACHE_KEY,
			self::MOVING_ROWS_CACHE_TTL,
			$now,
			function () use ($now)
			{
				$db = Neuron_DB_Database::getInstance ();
				return array
				(
					'battles' => Dolumar_NewMap_MovingRows::battles ($db, $now),
					'squads' => Dolumar_NewMap_MovingRows::squads ($db, $now)
				);
			}
		);
	}

	/**
	*	Village ids the player is related to (own + clan mates'), memoised per request.
	*	@return int[]
	*/
	public static function relatedVillageIds ($player)
	{
		static $memo = array ();

		$plid = intval ($player->getId ());
		if (!isset ($memo[$plid]))
		{
			$memo[$plid] = Dolumar_NewMap_MovingRows::relatedVillageIds (Neuron_DB_Database::getInstance (), $plid);
		}

		return $memo[$plid];
	}

	/**
		Move an object to another position
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
	}
}
?>
