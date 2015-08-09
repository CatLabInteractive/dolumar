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

class Dolumar_Map_Distance
{
	const DEBUG = false;

	public static function getDistance ($loc1, $loc2, $portals, $ignoreImpassable = true)
	{
		if ($loc1[0] == $loc2[0] && $loc1[1] == $loc2[1])
		{
			return 0;
		}
	
		// Show the route
		$profiler = Neuron_Profiler_Profiler::getInstance ();
		$profiler->start ('Calculating map distance between '.$loc1[0].','.$loc1[1].' and '.$loc2[0].','.$loc2[1]);
	
		$locations = array ();
		foreach ($portals as $v)
		{
			$l1 = $v->getCasterLocation ();
			$l2 = $v->getTargetLocation ();
			
			$locations[$v->getId ()] = array ($l1, $l2);
		}
		
		$profiler->message ('Straight line distance: '.
			round (self::calculateDistance ($loc1[0], $loc1[1], $loc2[0], $loc2[1])));
	
		// Debug portals
		$profiler->start ('Available portals');
		foreach ($locations as $k => $v)
		{
			list ($l1, $l2) = $v;
			$profiler->message ('Portal '.$k.' goes from '.$l1[0].','.$l1[1].' to '.$l2[0].','.$l2[1]);
		}
		$profiler->stop ();
	
		$distance = self::getShortestDistance ($loc1, $loc2, $portals, $ignoreImpassable);
		
		if ($distance === false)
		{
			return false;
		}
		
		$pd = 0;
		
		$profiler->start ('Route debug... this is what we did');
		foreach ($distance[1] as $v)
		{
			$profiler->message ('Arriving at '.$v[0].','.$v[1].' ('.round ($v['d']).').');
		}
		$profiler->stop ();
		
		$profiler->stop ();
		
		return $distance[0];
	}

	/*
		Check the distance between two points, using the portals provided in $portals
		When max_distance is reached, skip any other calculations.
		We are only interested in the shortest route.
	*/
	private function getShortestDistance ($start, $destination, $portals, $ignoreIslands)
	{		
		$came_from = array ();
		
		$g = array ();
		$h = array ();
		$f = array ();
		$d = array ();
		
		$k = self::getKeyName ($start);
		
		$closed = array ();
		$open = array ($k => $start);
		
		$g[$k] = 0;
		$h[$k] = self::calculateDistance ($start[0], $start[1], $destination[0], $destination[1]);
		$f[$k] = $h[$k];
		$d[$k] = 0;
		
		$i = 0;
		
		while (count ($open) > 0)
		{
			// Fetch the lowest $f
			$tmp = array_keys ($open);
			$fk = $tmp[0];
			
			foreach ($open as $k => $v)
			{
				if ($f[$k] < $f[$fk])
				{
					$fk = $k;
				}
			}
			
			$x = $open[$fk];
			
			if (self::DEBUG)
			{
				echo '<h2>Round '.$i.'</h2>';
				echo "Using\n";
				print_r ($x);
			
				echo $g[$fk];
			}
			
			// This is the end... my only friend, the end.
			if ($x[0] == $destination[0] && $x[1] == $destination[1])
			{
				$route = array ($destination);	
				$route[0]['d'] = 0;
				
				self::getRoute ($came_from, $fk, $route);
				
				return array ($g[$fk], $route);
			}
			
			else
			{
				// Remove from open, put in closed.
				unset ($open[$fk]);
				$closed[$fk] = $x;
				
				$neighbours = self::getNeighbourNodes ($x, $destination, $portals, $ignoreIslands);
				
				foreach ($neighbours as $k => $v)
				{
					$cost = $g[$fk] + $v['cost'];
					
					if (isset ($open[$k]) && $cost < $g[$k])
					{
						unset ($open[$k]);
					}
					
					if (isset ($closed[$k]) && $cost < $g[$k])
					{
						unset ($closed[$k]);
					}
					
					if (!isset ($open[$k]) && !isset ($closed[$k]))
					{	
						$g[$k] = $cost;				
						$open[$k] = $v['key'];
						$f[$k] = $g[$k] + $v['heuristics'];

						
						$came_from[$k] = $x;
						$came_from[$k]['d'] = $v['cost'];
					}
				}
			}
			
			if (self::DEBUG)
			{
				echo '<h3>Open:</h3>';
				echo '<pre>';
				foreach ($open as $k => $v)
				{
					//echo $f[$k] . "\n";
					//print_r ($open[$k]);
					echo 'Location ' . $v[0] . ',' . $v[1] . '<br />';
					echo 'F: ' .$f[$k] . '<br />';
					echo '<br />';
				}
				echo '</pre>';
			}
		}
		
		return false;
	}
	
	private static function getRoute ($came_from, $k, &$array)
	{
		if (isset ($came_from[$k]))
		{
			$array[] = $came_from[$k];			
			self::getRoute ($came_from, self::getKeyName ($came_from[$k]), $array);
		}
		return;
	}
	
	private static function getKeyName ($loc)
	{
		return $loc[0] . '|' . $loc[1];
	}
	
	/*
		Return an array with all the neighbour locations
		(output = array (array (x, y), distance))
	*/
	private static function getNeighbourNodes ($loc, $des, $portals, $ignoreIslands)
	{
		$out = array ();
		
		// We can always walk to the destination...
		if ($ignoreIslands || Dolumar_Map_Map::isPassable ($loc[0], $loc[1], $des[0], $des[1]))
		{
			$out[self::getKeyName ($des)] = array 
			(
				'key' => $des, 
				'cost' => self::calculateDistance ($loc[0], $loc[1], $des[0], $des[1]),
				/* self::calculateDistance ($loc[0], $loc[1], $des[0], $des[1]) */
				'heuristics' => self::calculateDistance ($loc[0], $loc[1], $des[0], $des[1])
			);
		}
		
		foreach ($portals as $v)
		{			
			self::addPortalNodes ($out, $v, $loc, $des);
		}
		
		return $out;
	}
	
	private static function getHeuristics ($loc1, $loc2, $isPortal = false)
	{
		$h = self::calculateDistance ($loc1[0], $loc1[1], $loc2[0], $loc2[1]);
		
		if ($isPortal)
		{
			$h = 0;
		}
		
		return $h;
	}
	
	private static function addPortalNodes (&$out, $portal, $loc, $des)
	{
		$loc1 = $portal->getCasterLocation ();
		$loc2 = $portal->getTargetLocation ();
		
		// Only link to closest portal
		$d1 = floor (self::calculateDistance ($loc1[0], $loc1[1], $loc[0], $loc[1]));
		$d2 = floor (self::calculateDistance ($loc2[0], $loc2[1], $loc[0], $loc[1]));
		
		$mind = min ($d1, $d2);
		
		$toloc1 = $mind == $d1;
		$toloc2 = !$toloc1;
		
		// If we are on the teleport, we can teleport to the other location
		if ($loc1[0] == $loc[0] && $loc1[1] == $loc[1])
		{
			$out[self::getKeyName ($loc2)] = array 
			(
				'key' => $loc2, 
				'cost' => $portal->getDistancePenalty (),
				'heuristics' => self::getHeuristics ($loc2, $des, true)
			);
		}
		
		// Otherwise we can move to a portal start/end point.
		elseif ($toloc1)
		{
			$out[self::getKeyName ($loc1)] = array 
			(
				'key' => $loc1, 
				'cost' => self::calculateDistance ($loc1[0], $loc1[1], $loc[0], $loc[1]),
				'heuristics' => self::getHeuristics ($loc1, $des, true)
			);
		}

		// If we are on the teleport, we can teleport to the other location			
		if ($loc2[0] == $loc[0] && $loc2[1] == $loc[1])
		{
			$out[self::getKeyName ($loc1)] = array 
			(
				'key' => $loc1, 
				'cost' => $portal->getDistancePenalty (),
				'heuristics' => self::getHeuristics ($loc1, $des, true)
			);
		}
		
		// Otherwise we can move to a portal start/end point.
		elseif ($toloc2)
		{
			$out[self::getKeyName ($loc2)] = array 
			(
				'key' => $loc2, 
				'cost' => self::calculateDistance ($loc2[0], $loc2[1], $loc[0], $loc[1]),
				'heuristics' => self::getHeuristics ($loc2, $des, true)
			);
		}
	}
	
	public static function calculateDistance ($x1, $y1, $x2, $y2)
	{
		return sqrt (pow ($x1 - $x2, 2) + pow ($y1 - $y2, 2) );
	}
}
?>
