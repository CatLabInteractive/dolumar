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

class Dolumar_Map_CacheSQLite extends Neuron_Core_Cache
{
	private $dbObj;
	private $aTiles = array ();

	public static function __getInstance ($dir = null)
	{
		static $in;
		if (!isset ($in))
		{
			$in = new Dolumar_Map_CacheSQLite ('map/');
		}
		return $in;
	}

	public function __construct ($sDir)
	{
		// Get profiler
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
	
		$profiler->start ('Constructing CacheSQLite object');
		
		$profiler->start ('Touching '.$sDir);
		$this->touchFolder ($sDir);
		$profiler->stop ();
		
		$profiler->start ('Opening database '.$sDir.'cache.db');
		$this->dbObj = new SQLiteDatabase (CACHE_DIR . $sDir . 'cache.db');
		$profiler->stop ();
		
		// Check if table exists
		$profiler->start ('Checking if table b_tiles exists');
		$query = $this->dbObj->query ("SELECT name FROM sqlite_master WHERE type='table' AND name='b_tiles'");
		
		if ($query->numRows() == 0)
		{
			$profiler->start ('Creating table b_tiles');
			$this->dbObj->query
			("
				CREATE TABLE b_tiles
				(
					t_ix INTEGER NOT NULL ,
					t_iy INTEGER NOT NULL ,
					t_tile INTEGER NOT NULL ,
					t_random INTEGER NOT NULL ,
					t_height REAL NOT NULL ,
					PRIMARY KEY ( t_ix , t_iy ) 
				)
			");
			$profiler->stop ();
		}
		$profiler->stop ();
		
		$profiler->stop ();
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
		
		if (!isset ($this->aTiles[$x]) || !isset ($this->aTiles[$x][$y]))
		{	
			$startX = $x - 10;
			$startY = $y - 10;
			$endX = $x + 15;
			$endY = $y + 15;
			
			$profiler = Neuron_Profiler_Profiler::__getInstance ();
			$profiler->start ('Loading cache from SQLite: ['.$startX.','.$startY.'] - ['.$endX.','.$endY.']');
			// Load a region of tiles (not just one)
			$query = "
				SELECT
					*
				FROM
					b_tiles
				WHERE
					t_ix > ".($startX)." AND t_ix < ".($endX)."
					AND t_iy > ".($startY)." AND t_iy < ".($endY)."
			";
			
			$result = $this->dbObj->query ($query);
			
			$this->aTiles[$x][$y] = false;
			while ($data = $result->fetch ())
			{
				$this->aTiles[intval($data['t_ix'])][intval($data['t_iy'])] = array
				(
					$data['t_tile'],
					$data['t_random'],
					$data['t_height']
				);
			}
			$profiler->stop ();
		}
		
		return $this->aTiles[$x][$y];
	}
	
	public function setLocationCache ($x, $y, $data)
	{
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		$profiler->start ('Setting cache for ('.$x.','.$y.')');
		
		$this->dbObj->query
		("
			INSERT INTO
				b_tiles
			(
				t_ix, t_iy, t_tile, t_random, t_height
			)
			VALUES
			(
				'$x',
				'$y',
				'{$data[0]}',
				'{$data[1]}',
				'{$data[2]}'
			)
		");
		
		$this->aTiles[$x][$y] = $data;
		
		$profiler->stop ();
	}
}
?>
