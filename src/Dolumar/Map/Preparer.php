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

class Dolumar_Map_Preparer
{
	const MAX_EXECUTION_TIME = 21600; // 6 HOURS

	/*
		Prepare the map for fresh blood!
		Make a radius around the currently playing players
		and make sure very single location in that radius
		is loaded.
	*/
	public function prepare ()
	{
		$db = Neuron_Core_Database::__getInstance ();
	
		// Do the area trick
		// Count amount of town centers on the map
		/*
		$buildings = $db->select
		(
			'map_buildings',
			array ('COUNT(*) AS aantal'),
			"buildingType = 1"
		);
		
		$towns = $buildings[0]['aantal'];
		$radius = ceil (abs (sqrt ($towns / pi ())) * MAXBUILDINGRADIUS * 2);
		*/
		
		// Count the farest building
		$buildings = $db->select
		(
			'map_buildings',
			array ('MAX((SQRT((xas*yas)+(xas*yas)))) AS distance'),
			"buildingType = 1"
		);
		
		$radius = count ($buildings) > 0 ? $buildings[0]['distance'] : 100;
		
		// Extend the radius
		$radius *= 1.25;
		
		// Max sure the radius doesn't exceed the maximum radius
		$radius = max (250, ceil ($radius));
		$radius = min (MAXMAPSTRAAL, $radius);
		
		// Load the start value
		$server = Neuron_GameServer::getServer();
		
		$start = $server->getData ('prepRadD');
		$start = isset ($start) ? $start : 0;
		
		echo "\n\nPreparing map for new players!\n";
		
		// Now let's prepare this radius!
		$this->prepareCircle ($start, $radius);
	}
	
	/*
		Fetch every location in this radius
	*/
	private function prepareCircle ($start, $end)
	{
		$server = Neuron_GameServer::getServer();
		
		$this->checkForCorrectDistances ();
	
		echo "GOAL: Preparing a ".$end." radius, starting from ".$start."...\n";
	
		// Move in small steps (25 radius max)
		// this makes sure that the results are saved
		// even if the process stops.
		$inc = ceil (1000 / max (30, $start));
		
		// Only run this for 60 minutes
		$time = time ();
		
		for ($s = $start; $s < $end; $s += $inc)
		{
			echo "[".date ("d/m/Y H:i:s")."]" . " Preparing a ".($s+$inc)." radius, starting from ".($s)."...\n";

			$tiles = $this->countTiles ();
			$this->doPrepareCircle ($s, $s + $inc);
			$newtiles = $this->countTiles ();
			
			echo "[".date ("d/m/Y H:i:s")."]" . " There are $newtiles tiles now, that's ".($newtiles-$tiles)." new onces!\n";
			
			// Done? Let's put this in the database!
			$server->setData ('prepRadD', $s);
			
			$inc = ceil (1000 / max (30, $s));
			
			if ($time < (time() - self::MAX_EXECUTION_TIME))
			{
				break;
			}
		}
	}
	
	private function checkForCorrectDistances ()
	{
		echo "Checking for z_cache_tile rows that do not have a correct distance: ";
		$db = Neuron_DB_Database::getInstance ();
		
		$db->query
		("
			UPDATE
				z_cache_tiles
			SET
				t_distance = (SQRT((t_ix*t_ix)+(t_iy*t_iy)))
			WHERE
				t_distance IS NULL
		");
		
		echo "done!\n";
	}
	
	/*
		Count the amount of tiles in the cache table
	*/
	private function countTiles ()
	{
		$db = Neuron_DB_Database::getInstance ();
		$count = $db->query
		("
			SELECT
				COUNT(*) AS aantal
			FROM
				z_cache_tiles
		");
		
		return $count[0]['aantal'];
	}
	
	private function doPrepareCircle ($start, $end)
	{
		$start = intval ($start);
		$end = intval ($end);
		
		// Power the start & end to make everything run faster
		$pStart = $start * $start;
		$pEnd = $end * $end;
		
		$pi = pi();
		
		// Count the values in the database, 
		// maybe we don't have to run this check!
		$toFind = 0;
		
		for ($x = 0 - $end; $x < $end; $x ++)
		{
			for ($y = 0 - $end; $y < $end; $y ++)
			{
				$distance = ($x*$x + $y*$y);
			
				if ($distance > $pStart && $distance < $pEnd)
				{
					$toFind ++;
				}
			}
		}
		
		// Now count all values in the circle
		$db = Neuron_DB_Database::getInstance ();
		
		$check = $db->query
		("
			SELECT
				t_distance AS distance
			FROM
				z_cache_tiles
			HAVING
				distance > {$start} AND 
				distance < {$end}
		");
		
		$aantal = count ($check);
		
		if ($aantal == $toFind)
		{
			echo "[".date ("d/m/Y H:i:s")."]" . " Skipping since the database contains enough tiles...\n";
			return;
		}
		
		for ($x = 0 - $end; $x < $end; $x ++)
		{
			for ($y = 0 - $end; $y < $end; $y ++)
			{
				// Check the distance!
				$distance = ($x*$x + $y*$y);
			
				if ($distance > $pStart && $distance < $pEnd)
				{
					Dolumar_Map_Map::getLocation ($x, $y);
				}
			}
		}
	}
}
?>
