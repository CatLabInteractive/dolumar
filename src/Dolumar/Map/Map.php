<?php
if (!defined ('RUINS_DURATION'))
{
	define ('RUINS_DURATION', 60*60*24);
}

// Dirty hack to allow debugging.
if (!isset ($GLOBALS['MAP_CONFIG_RANDMAPFACTOR']))
{
	$GLOBALS['MAP_CONFIG_RANDMAPFACTOR'] = RANDMAPFACTOR;
}

if (!defined ('ISLAND_SIZE'))
{
	define ('ISLAND_SIZE', false);
}
elseif (!defined ('ISLANDS'))
{
	define ('ISLANDS', true);
}

if (!defined ('ISLANDS'))
{
	define ('ISLANDS', false);
}

class Dolumar_Map_Map
{
	const TILE_GRASS = 0;
	const TILE_WATER = 1;
	const TILE_BUSH = 2;
	const TILE_TREE = 3;
	const TILE_HAYSTACK = 4;
	const TILE_RESOURCE = 5;
	const TILE_SHORE = 6;
	
	const ISLANDS = ISLANDS;
	const ISLAND_SIZE = ISLAND_SIZE;
	
	// Return the location of a tile
	public static function getLocation ($x, $y, $hasBuilding = false, $useCache = true)
	{

		// Off the map! return water.
		if ( ($x*$x) + ($y*$y) > ( MAXMAPSTRAAL * MAXMAPSTRAAL ) )
		{
			return new Dolumar_Map_Water (1, $x, $y, 0);
		}
		
		elseif ($hasBuilding)
		{
			$data = self::getLocationData ($x, $y, $useCache);
			return new Dolumar_Map_Location ($data[1], $x, $y, $data[2]);
		}
		
		else
		{
			$data = self::getLocationData ($x, $y, $useCache);
			switch ($data[0])
			{
				case self::TILE_GRASS:
					return new Dolumar_Map_Location ($data[1], $x, $y, $data[2]);
				break;
				
				case self::TILE_WATER:
					return new Dolumar_Map_Water ($data[1], $x, $y, $data[2]);
				break;
				
				case self::TILE_BUSH:
					return new Dolumar_Map_Bush ($data[1], $x, $y, $data[2]);
				break;
				
				case self::TILE_TREE:
					return new Dolumar_Map_Trees ($data[1], $x, $y, $data[2]);
				break;
				
				case self::TILE_HAYSTACK:
					return new Dolumar_Map_Haystack ($data[1], $x, $y, $data[2]);
				break;
				
				case self::TILE_RESOURCE:
					return new Dolumar_Map_Resource ($data[1], $x, $y, $data[2]);
				break;
				
				case self::TILE_SHORE:
					return new Dolumar_Map_Shore ($data[1], $x, $y, $data[2]);
				break;
			}
		}
	}
	
	public static function isWater ($x, $y)
	{
		/*
			Every tile in this map now has a height.
			You can fetch it by calling the self::getHeight function.
			It's range varies from 0 to 9.

			The commented function does not work: it simply turns all my land
			(except a few tiles) in water.
		*/
		$now = self::getNewHeight ($x, $y);

		return $now < 1;

		/*
		if ($now < 1)
		{
			return true;
		}
		elseif ($now < 3)
		{
			$d = 2; // tweakable
			$h = 0;

			if (self::getNewHeight ($x + $d, $y + $d) < $now) { $h ++; }
			if (self::getNewHeight ($x + $d, $y - $d) < $now) { $h ++; }
			if (self::getNewHeight ($x + $d, $y     ) < $now) { $h ++; }
			
			if (self::getNewHeight ($x - $d, $y + $d) < $now) { $h ++; }
			if (self::getNewHeight ($x - $d, $y - $d) < $now) { $h ++; }
			if (self::getNewHeight ($x - $d, $y     ) < $now) { $h ++; }
			
			if (self::getNewHeight ($x     , $y + $d) < $now) { $h ++; }
			if (self::getNewHeight ($x     , $y - $d) < $now) { $h ++; }

			return $h != 4;
		}
		*/
		
		//return $now < 2;
	}
	
	/*
		Calculate the height of a tile
	*/
	public static function getNewHeight ($x, $y)
	{
		$modifier = 1;
		
		$islands = self::ISLANDS;
		
		$x2 = $x * $x;
		$y2 = $y * $y;
		
		if ($islands)
		{
			$grid = self::ISLAND_SIZE;
			$halfgrid = $grid / 2;
		
			$rx = abs (($x + $halfgrid) % $grid);
			$ry = abs (($y + $halfgrid) % $grid);
		
			$cx = $grid / 2;
			$cy = $grid / 2;
		
		
			$dx = $rx - $cx;
			$dy = $ry - $cy;
		
			$hardcoast = $grid * 0.4;
			$softcoast = $grid * 0.2;
		
			$diff = $hardcoast - $softcoast;

			$rdx = sqrt (($dx * $dx) + ($dy * $dy));
		}
		
		if ( $x2 + $y2 > ( MAXMAPSTRAAL * MAXMAPSTRAAL ) )
		{
			$height = 0;
		}
		
		else if ($islands && $rdx > $hardcoast)
		{
			$height = 0;
		}
		
		else if ($islands && $rdx > $softcoast)
		{
			$height = self::calculateHeight ($x, $y);
			
			$modifier = 1 - (($rdx - $softcoast) / $diff);
			
			$height *= $modifier;
		}

		else
		{
			$height = self::calculateHeight ($x, $y);
		}
		
		return $height;
	}
	
	private static function calculateHeight ($x, $y)
	{
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
	
		$profiler->start ('Calculating new height for ('.$x.','.$y.')');
		$height = Dolumar_Map_PerlinGenerator::getPerlinNoise ($x, $y);
		
		$profiler->stop ();
		
		return $height;
	}
	
	// Get a random int based on $x, $y and $base
	public static function getRandom ($x, $y, $base)
	{	
		$in = md5 ($x . $base . $y . $GLOBALS['MAP_CONFIG_RANDMAPFACTOR']);
		
		$chars = 5;
		$in = substr ($in, ($x * $y) % (32 - $chars), $chars);
		
		return round ((base_convert ($in, 16, 10) % $base) + 1);
		
		/*
		mt_srand (abs (10000 - ( $x * 1000) + $y) + RANDMAPFACTOR);
		return mt_rand (1, $base);
		*/
	}
	
	// Get a random boolean based on $x, $y and $dif
	public static function boolRandom ($x, $y, $dif)
	{
		return abs (($x * self::getRandom ($x, $y, 3)) % $dif) == 0 
			&& abs (($y * self::getRandom ($x, $y, 3)) % $dif) == 0;
	}
	
	/*
		Determine what tile should be used here
		and return its data
	*/
	public static function getFreshTileData ($x, $y)
	{
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		$profiler->start ('Generating new location data for ('.$x.','.$y.')');
		
		$x = floor ($x);
		$y = floor ($y);

		$chanse = 1000;
		
		$randomNumbers = intval (self::getRandom ($x, $y, $chanse));
		
		$height = self::getNewHeight ($x, $y);

		// Get location data & store cache		
		if (self::isWater ($x, $y))
		{
			$iTile = self::TILE_WATER;
			$iRandom =  ($randomNumbers % 100) + 1;
		}

		elseif ($randomNumbers < 5)
		{
			$iTile = self::TILE_HAYSTACK;
			$iRandom =  1;
		}
		
		elseif ($randomNumbers  > 16 && $randomNumbers < 20)
		{
			$iTile = self::TILE_RESOURCE;
			$iRandom =  ($randomNumbers % 3) + 1;
		}
	
		elseif ($randomNumbers > 20 && $randomNumbers < 50)
		{			
			$iTile = self::TILE_BUSH;
			$iRandom =  ($randomNumbers % 4) + 1;
		}
		
		elseif ($randomNumbers > 50 && $randomNumbers < 150)
		{
			$iTile = self::TILE_TREE;
			$iRandom = ($randomNumbers % 3) + 1;
		}
		
		elseif ($height < 1.5)
		{
			$iTile = self::TILE_SHORE;
			$iRandom =  ($randomNumbers % 15) + 1;
		}
		
		else 
		{
			$iTile = self::TILE_GRASS;
			$iRandom =  ($randomNumbers % 15) + 1;
		}
		
		$profiler->stop ();
		
		// Return thze data
		return array
		(
			$iTile,
			$iRandom,
			$height
		);
	}
	
	/*
		Load a location from cache OR generate a new one.
	*/
	public static function getLocationData ($x, $y, $useCache = true)
	{
		// Bypass cache if not required.
		if (!$useCache)
		{
			return self::getFreshTileData ($x, $y);
		}
	
		$cache = Dolumar_Map_CacheMemcached::getInstance ();
		
		if ($cache->hasLocationCache ($x, $y))
		{
			return $cache->getLocationCache ($x, $y);
		}
		else
		{
			$data = self::getFreshTileData ($x, $y);
			$cache->setLocationCache ($x, $y, $data);
			return $data;
		}
	}
	
	public static function getBuildings ($x, $y, $range)
	{
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		$profiler->start ('Loading all builings within '.$range.' tiles from ('.$x.','.$y.')');
		
		$hr = ceil ($range / 2);
	
		$db = Neuron_Core_Database::__getInstance ();
		$buildingSQL = $db->getDataFromQuery 
		(
			$db->customQuery
			("
				SELECT
					map_buildings.*,
					villages.race
				FROM
					map_buildings
				LEFT JOIN
					villages ON map_buildings.village = villages.vid
				WHERE
					(
						map_buildings.destroyDate = '0'
						OR map_buildings.destroyDate > '".(time () - RUINS_DURATION)."'
					)
					AND
					(
						xas < '".($x + $hr)."' AND xas > '".($x - $hr)."'
						AND yas < '".($y + $hr)."' AND yas > '".($y - $hr)."'
					)
				ORDER BY
					map_buildings.destroyDate = '0' DESC,
					map_buildings.destroyDate ASC
			")
		);
		
		$profiler->stop ();
		
		return $buildingSQL;
	}

	public static function getBuildingsFromLocations ($points = array (), $range = 5)
	{
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		$profiler->start ('Loading buildings from multiple points');

		$db = Neuron_Core_Database::__getInstance ();

		$select = "FALSE ";

		foreach ($points as $v)
		{
			$select .= "OR ( xas < '".($v[0] + $range)."' AND xas > '".($v[0] - $range)."' AND yas < '".($v[1] + $range)."' AND yas > '".($v[1] - $range)."') ";
		}

		$sql =
		"
			SELECT
				map_buildings.*,
				villages.race
			FROM
				map_buildings
			LEFT JOIN
				villages ON map_buildings.village = villages.vid
			WHERE
				(
					map_buildings.destroyDate = '0'
					OR map_buildings.destroyDate > '".(time () - RUINS_DURATION)."'
				)
				AND
				(
					$select
				)
			ORDER BY
				map_buildings.destroyDate = '0' DESC,
				map_buildings.destroyDate DESC
		";
		
		$buildingSQL = $db->getDataFromQuery ($db->customQuery ($sql));
		
		$profiler->stop ();
		
		return $buildingSQL;

	}

	public static function getSnapshot ($x, $y, $width, $height, $zoom)
	{	
		$stats = Neuron_Core_Stats::__getInstance ();
		
		$fZoom = $zoom / 100;
		
		// Make a bigger image
		$width = $width / $fZoom;
		$height = $height / $fZoom;
		
		$floatZoom = 1;
		
		$tileSizeX = 200 * $floatZoom;
		$tileSizeY = $tileSizeX / 2;
		
		$halfTileX = $tileSizeX / 2;
		$halfTileY = $tileSizeY / 2;
		
		$offsetX = ceil ($tileSizeX / 2);
		$offsetY = ceil ($tileSizeY / 2);
		
		$loadExtra = 1;
		
		$switchpoint = max
		(
			ceil ($width / ($tileSizeX * 1)),
			ceil ($height / $tileSizeY)
		);
		
		$im = imagecreatetruecolor ($width, $height);
		
		
		list ($startX, $startY) = self::getStartposition ($x, $y, $width, $height, $tileSizeX, $tileSizeY);
		
		$locations = array
		(
			array
			(
				$startX + ($switchpoint/2),
				$startY - ($switchpoint/2)
			)
		);
		
		// Load buildings from SQL
		$buildingSQL = Dolumar_Map_Map::getBuildingsFromLocations ($locations, $switchpoint + 15);
		$buildings = array ();
		foreach ($buildingSQL as $buildingV)
		{
			$race = Dolumar_Races_Race::getRace ($buildingV['race']);
			
			$b = Dolumar_Buildings_Building::getBuilding 
			(
				$buildingV['buildingType'], 
				$race, 
				$buildingV['xas'], $buildingV['yas']
			);
			
			$village = Dolumar_Players_Village::getVillage ($buildingV['village']);
			
			$b->setVillage ($village);
			
			$b->setData ($buildingV['bid'], $buildingV);	
			$buildings[floor ($buildingV['xas'])][floor ($buildingV['yas'])][] = $b;
		}
		
		for ($i = (0 - $loadExtra); $i <= ($switchpoint * 2); $i ++)
		{
			if ($i > $switchpoint)
			{
				$offset = ($i - $switchpoint + 1) * 2;
			}
			else 
			{
				$offset = 0;
			}
			
			$colStart = 0 - $i  + $offset - $loadExtra;
			$colEnd = $i - $offset + $loadExtra + 1;
			
			//$output['regions'][$sQ]['tiles'][$i] = array ();
			
			$tx = $startX + $i;
			
			for ($j = $colStart; $j < $colEnd; $j ++)
			{
				$ty = $startY - $j;
				
				$px = (($i - $j) * $offsetX);
				$py = (($i + $j) * $offsetY);

				
				// Check for building
				$hasBuildings = isset ($buildings[$tx]) && isset ($buildings[$tx][$ty]);
				
				$location = Dolumar_Map_Location::getLocation ($tx, $ty, $hasBuildings);
				$image = $location->getImage ();
				
				$sImagePath = IMAGE_PATH.'tiles/'.$image['image'].'.gif';
				
				//die ($sImagePath);
				
				self::drawSnapshotImage
				(
					$im,
					$sImagePath,
					$px,
					$py,
					$floatZoom
				);
				
				//checkBuildings ($buildings, $sQ, $i, $j, $tx, $ty);
				if ($hasBuildings)
				{
					foreach ($buildings[$tx][$ty] as $building)
					{
						$short = $building->getIsoImage ();
						$url = $building->getImagePath ();
						$offset = $building->getTileOffset ();
						
						$fakeurl = IMAGE_PATH.'sprites/'.$short.'.png';
						
						//echo "---\n";
						//echo $url . "\n";
						//echo $fakeurl . "\n";

						$oi = $i + $offset[0];
						$oj = $j + $offset[1];

						$pox = round (($oi - $oj) * floor ($tileSizeX / 2));
						$poy = round (($oi + $oj) * floor ($tileSizeY / 2));

						self::drawSnapshotImage
						(
							$im,
							//$fakeurl,
							$url,
							$pox + ($stats->get ('offsetx', $short, 'images', 0) * $floatZoom),
							$poy + ($stats->get ('offsety', $short, 'images', 0) * $floatZoom),
							$floatZoom,
							false
						);
					}
				}
			}
		}
		
		// Resize the image
		$newwidth = $width * $fZoom;
		$newheight = $height * $fZoom;
		
		$newimg = imagecreatetruecolor ($newwidth, $newheight);
		
		imagecopyresampled 
		(
			$newimg, $im, 
			0, 0,
			0, 0,
			$newwidth, $newheight,
			$width, $height
		);

		return $newimg;
	}
	
	/*
		Return the start position for snapshot
		from a width & height & x & y
	*/
	private static function getStartPosition ($x, $y, $width, $height, $tileSizeX, $tileSizeY)
	{
		$xTiles = ($width / $tileSizeX) / 2;
		$yTiles = ($height / $tileSizeY) / 2;
		
		/*
			Math.round (( gx - gy ) * Game.map.iMapOffsetX), 
			Math.round (( gx + gy) * Game.map.iMapOffsetY)
		*/

		// Align vertical
		$x -= $yTiles;
		$y += $yTiles;
		
		// Align horizontal
		$x -= ($xTiles * 1) - 1;
		$y -= $xTiles / 1;
		
		//die ('tx: '.$tx.', ty:'.$ty);
	
		$startX = floor ($x);
		$startY = ceil ($y);
		
		return array ($startX, $startY);	
	}

	public static function drawSnapshotImage ($im, $imgurl, $px, $py, $floatZoom, $moveOnHeight = true)
	{
		$ext = strtolower (substr ($imgurl, -3));
		
		switch ($ext)
		{
			case 'png':
				$frim = @imagecreatefrompng ($imgurl);
			break;
			
			case 'gif':
				$frim = @imagecreatefromgif ($imgurl);
			break;
		}

		if (!$frim)
			return;
		
		$size = getimagesize ($imgurl);

		if ($moveOnHeight)
		{
			$py -= ceil (max (0, ($size[1] - 100) * $floatZoom));
		}
		
		//$extraPixels = ($floatZoom == 1 ? 0 : 1); 
		$extraPixels = 0;

		imagecopyresampled
		(
			$im, $frim,
			$px, $py,
			0, 0,
			($size[0] * $floatZoom)+$extraPixels,  ($size[1] * $floatZoom)+$extraPixels,
			$size[0], $size[1]
		);
	}
	
	public static function isPassable ($x1, $y1, $x2, $y2)
	{
		$chk = self::isOnSameIsland ($x1, $y1, $x2, $y2);
		return $chk;
	}
	
	public static function isOnSameIsland ($x1, $y1, $x2, $y2)
	{
		if (self::ISLANDS && self::ISLAND_SIZE)
		{
			$half = self::ISLAND_SIZE / 2;
		
			$ix1 = floor (($x1 + $half) / self::ISLAND_SIZE);
			$iy1 = floor (($y1 + $half) / self::ISLAND_SIZE);
			
			$ix2 = floor (($x2 + $half) / self::ISLAND_SIZE);
			$iy2 = floor (($y2 + $half) / self::ISLAND_SIZE);
			
			if ($ix1 != $ix2 || $iy1 != $iy2)
			{
				return false;
			}
		}
		
		return true;
	}

	public static function calculateDistance ($x1, $y1, $x2, $y2, $ignoreIslands = true)
	{
		//return sqrt (pow ($x1 - $x2, 2) + pow ($y1 - $y2, 2) );
		if (!$ignoreIslands && !self::isOnSameIsland ($x1, $y1, $x2, $y2))
		{
			return false;
		}
		
		return Dolumar_Map_Distance::calculateDistance ($x1, $y1, $x2, $y2);
	}

	public static function getDistanceBetweenVillages ($village1, $village2, $ignoreImpassable = true)
	{
		$tc1 = $village1->buildings->getTownCenter ();
		if (!$tc1)
		{
			return false;
		}
		
		$tc2 = $village2->buildings->getTownCenter ();
		if (!$tc2)
		{
			return false;
		}
		
		//return self::getDistanceBetweenVillages_simple ($village1, $village2);
		return self::getDistanceBetweenVillages_pathbased ($village1, $village2, $ignoreImpassable);
	}
	
	private static function getDistanceBetweenVillages_simple ($village1, $village2, $ignoreImpassable = true)
	{
		$tc1 = $village1->buildings->getTownCenter ();
		$tc2 = $village2->buildings->getTownCenter ();	
		$loc1 = $tc1->getLocation ();
		$loc2 = $tc2->getLocation ();
		
		// Fetch portals
		$portals = Dolumar_Map_Portal::getBetweenVillages ($village1, $village2);
		$distance = Dolumar_Map_Distance::calculateDistance ($loc1[0], $loc1[1], $loc2[0], $loc2[1], $ignoreImpassable);

		if (count ($portals) > 0)
		{
			$minpenalty = $portals[0]->getDistancePenalty ();
			
			foreach ($portals as $v)
			{
				$np = $v->getDistancePenalty ();
				if ($np < $minpenalty)
				{
					$minpenalty = $v->getDistancePenalty ();
				}
			}
			
			if ($minpenalty < $distance)
			{
				$distance = $minpenalty;
			}
		}
		
		return $distance;
	}
	
	private static function getDistanceBetweenVillages_pathbased ($village1, $village2, $ignoreImpassable = true)
	{		
		$tc1 = $village1->buildings->getTownCenter ();
		$tc2 = $village2->buildings->getTownCenter ();	
		$loc1 = $tc1->getLocation ();
		$loc2 = $tc2->getLocation ();
		
		// Fetch portals
		$portals = Dolumar_Map_Portal::getFromVillages (array ($village1, $village2));
		return Dolumar_Map_Distance::getDistance ($loc1, $loc2, $portals, $ignoreImpassable);
	}
	
	/*
		Locations and directions
	*/
	public static function getDirection ($x, $y)
	{
		$angle = (atan2 ($x, $y) / pi ());
		$angle += 1;
		
		// Rotate back
		while ($angle >= 2) { $angle -= 2; }
		
		// Little shift
		$angle -= 0.125;
		if ($angle < 0)
		{
			$angle += 2;
		}
		
		// e ne n wn w sw s es
		
		// so, counter clockwise:
		// sw w wn n ne e es s 

		if ($angle >= 0 && $angle < 0.25) { return 'w'; }
		elseif ($angle < 0.50) { return 'wn'; }
		elseif ($angle < 0.75) { return 'n'; }
		elseif ($angle < 1.00) { return 'ne'; }
		elseif ($angle < 1.25) { return 'e'; }
		elseif ($angle < 1.50) { return 'es'; }
		elseif ($angle < 1.75) { return 's'; }
		else { return 'sw'; }
	}
	
	/*
		Turn a string direction into start & finish radials
		$d is "random"
	*/
	public static function getRadialFromDirection ($d)
	{
		// ne e es s sw w wn n
	
		$offset = 0 - 0.125;
		switch ($d)
		{
			case 'ne':
				$startRad = 0 + $offset;
				$endRad = $startRad + 0.25;
			break;
			case 'e':
				$startRad = 0.25 + $offset;
				$endRad = $startRad + 0.25;
			break;
			case 'es':
				$startRad = 0.5 + $offset;
				$endRad = $startRad + 0.25;
			break;
			case 's':
				$startRad = 0.75 + $offset;
				$endRad = $startRad + 0.25;
			break;
			case 'sw':
				$startRad = 1 + $offset;
				$endRad = $startRad + 0.25;
			break;
			case 'w':
				$startRad = 1.25 + $offset;
				$endRad = $startRad + 0.25;
			break;
			case 'wn':
				$startRad = 1.5 + $offset;
				$endRad = $startRad + 0.25;
			break;
			case 'n':
				$startRad = 1.75 + $offset;
				$endRad = $startRad + 0.25;
			break;
			
			case 'r':
			default:
				$startRad = 0;
				$endRad = 2;
			break;
		}
		
		if ($startRad < 0)
		{
			$startRad += 2;
		}
		
		return array ($startRad, $endRad);
	}
	
	/*
		League convertion
	*/
	public static function league2tile ($distance)
	{
		return $distance * 10;
	}
	
	public static function tile2league ($distance)
	{
		return $distance * 0.1;
	}
}
?>
