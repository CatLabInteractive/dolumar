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

if (!defined ('MAX_MEMORY_USAGE'))
{
	define ('MAX_MEMORY_USAGE', 20971520);
}

class Dolumar_Map_CacheMySQL extends Neuron_Core_Cache
{
	private $dbObj;
	private $aTiles = array ();
	
	private $iServer;
	
	const AREA_SIDE = 25;
	
	const USE_MYSQL = false;

	public static function __getInstance ($dir = null)
	{
		static $in;
		if (!isset ($in))
		{
			$in = new Dolumar_Map_CacheMySQL ('z_cache_tiles');
		}
		return $in;
	}
	
	private $sTable;
	private $objMemcache;

	public function __construct ($sTable)
	{
		$this->sTable = $sTable;
		
		$db = Neuron_DB_Database::getInstance ();
		$this->dbObj = $db->getConnection ();
		
		//$this->dbObj = new Mysqli (DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		
		$this->objMemcache = Neuron_Core_Memcache::getInstance ();
		
		$server = Neuron_GameServer::getServer();
		$this->iServer = $server->getServerId ();
	}
	
	public function hasLocationCache ($x, $y)
	{
		//return parent::hasCache ('map'.$x.'x'.$y);
		return $this->getCache ($x, $y) !== false;
	}
	
	public function getLocationCache ($x, $y)
	{		
		$x = intval ($x);
		$y = intval ($y);
		
		// First: check the memcache
		return $this->loadCache ($x, $y);
	}
	
	private function loadCache ($x, $y)
	{
		if (!isset ($this->aTiles[$x]) || !isset ($this->aTiles[$x][$y]))
		{	
			// Check the size of aTiles!
			if (memory_get_usage () > MAX_MEMORY_USAGE)
			{
				$this->aTiles = array ();
			}
			
			$memcachename = $this->getCacheName ($x, $y);
			
			list ($startX, $startY, $endX, $endY) = $this->getArea ($x, $y);
			
			$c = $this->objMemcache->getCache ($memcachename);
			if ($c)
			{
				$c = $this->unpack ($c, $startX, $startY);
			
				foreach ($c as $x => $v)
				{
					if (!isset ($this->aTiles[$x]))
						$this->aTiles[$x] = array ();
					
					foreach ($v as $y => $vv)
					{
						$this->aTiles[$x][$y] = $vv;
					}
				}
			}
			else
			{
				if (!self::USE_MYSQL)
				{
					return false;
				}
				/*
				$startX = floor ($x / 10) * 10;
				$startY = floor ($y / 10) * 10;
				$endX = $startX + 10;
				$endY = $startY + 10;
				*/
			
				$profiler = Neuron_Profiler_Profiler::__getInstance ();
				$profiler->start ('Loading cache from MySQL: ['.$startX.','.$startY.'] - ['.$endX.','.$endY.']');
			
				// Load a region of tiles (not just one)
				$query = "
					SELECT
						t_ix,
						t_iy,
						t_tile,
						t_random,
						t_height
					FROM
						".$this->sTable."
					WHERE
						t_ix BETWEEN ".($startX)." AND ".($endX)."
						AND t_iy BETWEEN ".($startY)." AND ".($endY)."
				";
			
				$result = $this->dbObj->query ($query, MYSQLI_USE_RESULT);
				
				// Here we store the stuff for memcache.
				$tmparray = array ();
			
				if ($result)
				{
					$this->aTiles[$x][$y] = false;
					while ($data = $result->fetch_assoc ())
					{
						$this->aTiles[$data['t_ix']][$data['t_iy']] = array
						(
							$data['t_tile'],
							$data['t_random'],
							$data['t_height']
						);
						
						$tmparray[$data['t_ix']][$data['t_iy']] = 
							$this->aTiles[$data['t_ix']][$data['t_iy']];
					}
					$result->close ();
				}
			
				// Fill the non existing with falses
				for ($i = $startX; $i < $endX; $i ++)
				{
					for ($j = $startY; $j < $endY; $j ++)
					{
						if (!isset ($tmparray[$i][$j]))
						{
							$this->aTiles[$i][$j] = false;
							$tmparray[$i][$j] = false;
						}
					}
				}
							
				// Put these results in memcache
				$this->objMemcache->setCache ($memcachename, $this->pack ($tmparray, $startX, $startY));
			
				$profiler->stop ();
			}
		}
		
		return $this->aTiles[$x][$y];
	}
	
	/*
		Returns the 4 coordinates of the area
		where this location is in.
	*/
	private function getArea ($x, $y)
	{
		$sx = floor ($x / self::AREA_SIDE) * self::AREA_SIDE;
		$sy = floor ($y / self::AREA_SIDE) * self::AREA_SIDE;
	
		return array
		(
			$sx, $sy,
			$sx + self::AREA_SIDE, $sy + self::AREA_SIDE
		);
	}
	
	public function setLocationCache ($x, $y, $data)
	{
		if (!self::USE_MYSQL)
		{
			return;
		}
	
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		$profiler->start ('Setting cache for ('.$x.','.$y.')');
		
		$x = intval ($x);
		$y = intval ($y);
		
		$this->dbObj->query
		("
			INSERT INTO
				".$this->sTable."
			(
				t_ix, t_iy, t_tile, t_random, t_height, t_distance
			)
			VALUES
			(
				$x,
				$y,
				'{$data[0]}',
				'{$data[1]}',
				'{$data[2]}',
				SQRT(({$x}*{$x})+({$y}*{$y}))
			)
		");
		
		$this->aTiles[$x][$y] = $data;
		
		// Now it's in mysql, we remove the memcached area.
		$this->objMemcache->removeCache ($this->getCacheName ($x, $y));
		
		$profiler->stop ();
	}
	
	private function pack ($data, $sx, $sy)
	{
		$tmp = "";
	
		for ($x = 0; $x < self::AREA_SIDE; $x ++)
		{
			for ($y = 0; $y < self::AREA_SIDE; $y ++)
			{
				if (!isset ($data[$sx+$x]) || !isset ($data[$sx+$x][$sy+$y]))
				{
					throw new Neuron_Core_Error 
						('Could not pack array, missing '.($sx + $x).','.($sy + $y) . ' (from '.$sx.','.$sy.')');
				}
				
				$d = $data[$sx+$x][$sy+$y];
				$valid = 1;
				
				if (!$d)
				{
					$d = array (0, 0, 0.0);
					$valid = 0;
				}
								
				$tmp .= pack ('siif', $valid, $d[0], $d[1], $d[2]);
			}
		}
	
		return $tmp;
	}
	
	private function unpack ($data, $sx, $sy)
	{
		$length = strlen (pack ('siif', 1, 1, 1, 0.1));
		
		$input = str_split ($data, $length);
		$data = array ();
		
		$i = 0;
		for ($x = 0; $x < self::AREA_SIDE; $x ++)
		{
			$data[$x+$sx] = array ();
			for ($y = 0; $y < self::AREA_SIDE; $y ++)
			{
				$d = unpack ('svalid/i2int/ffloat', $input[$i]);
				
				if ($d['valid'])
				{
					$data[$x+$sx][$y+$sy] = array ($d['int1'], $d['int2'], $d['float']);
				}
				else
				{
					$data[$x+$sx][$y+$sy] = false;
				}
				
				$i ++;
			}
		}
	
		return $data;
	}
	
	private function getCacheName ($x, $y)
	{
		$area = $this->getArea ($x, $y);
		return 'map_'.RANDMAPFACTOR.'_'.$area[0].','.$area[1].
			'_'.$area[2].','.$area[3].'_'.$this->iServer.'_area_p2';
	}
}
?>
