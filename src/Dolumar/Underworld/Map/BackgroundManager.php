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

class Dolumar_Underworld_Map_BackgroundManager
	implements Neuron_GameServer_Map_Managers_BackgroundManager
{

	private $name;
	private $mission;
	
	private $data;
	private $fogofwar;

	private $spawngroups = array ();
	private $spawnpoints = array ();

	private $checkpionts = array ();
	private $objectives = array ();
	
	const MAP_LOCATION_CLEAR = ' ';
	const MAP_LOCATION_WATER = 'x';
	const MAP_LOCATION_WALL = 'w';
	const MAP_LOCATION_SPAWN = 's';
	const MAP_LOCATION_BORDER = 'b';
	const MAP_LOCATION_CHECKPOINT = 'c';

	public function __construct ($name, Dolumar_Underworld_Models_Mission $mission)
	{
		$this->name = $name;
		$this->mission = $mission;
	}
	
	public function setFogOfWar (Dolumar_Underworld_Map_FogOfWar $fog)
	{
		$this->fogofwar = $fog;
	}

	public function getSpawnpoints ()
	{
		$this->loadMap ();
		return $this->spawnpoints;
	}

	public function isInsideMap (Neuron_GameServer_Map_Location $location)
	{
		$this->loadMap ();
		return isset ($this->data[$location->x ()]) && isset ($this->data[$location->x ()][$location-> y ()]);
	}
	
	public function getExploreStatus (Neuron_GameServer_Map_Location $location)
	{
		if (!$this->isInsideMap ($location))
		{
			return Dolumar_Underworld_Map_FogOfWar::VISIBLE;	
		}

		if (isset ($this->fogofwar))
		{
			return $this->fogofwar->getExploreStatus ($location);
		}
		
		return Dolumar_Underworld_Map_FogOfWar::VISIBLE;
	}

	private function getSpawnGroup ($oid)
	{
		if (!isset ($this->spawngroups[$oid]))
		{
			$id = $this->getSideFromInput ($oid);

			$this->spawngroups[$oid] = new Dolumar_Underworld_Map_Spawngroup ($id);
			$this->spawngroups[$oid]->setName ('Spawn area ' + $oid);
		}

		return $this->spawngroups[$oid];
	}
	
	private function loadMap ()
	{
		if (!isset ($this->data))
		{
			$profiler = Neuron_Profiler_Profiler::__getInstance ();
			$profiler->start ('Loading map');

			// Only used for testing. DO NOT USE FOR REAL
			if ($this->name === 'random')
			{
				$input = $this->loadRandomMap ();
			}
			else
			{
				// We load thze map
				$map_path = STATIC_PATH . 'maps/' . $this->name;

				$profiler->start ('Loading ' . $map_path);

				$input = file_get_contents ($map_path);

				$profiler->stop ();
			}
			
			$this->data = array ();
			
			$row = 0;
			$this->data[$row] = array ();
			
			$col = 0;

			$data = str_split ($input);

			$profiler->start ('Processing map input');
			
			while ($v = array_shift ($data))
			{
				if ($v == PHP_EOL)
				{
					// End of map?
					if ($col === 0)
					{
						break;
					}

					$col = 0;
					$row ++;
					$this->data[$row] = array ();
				}
				else
				{
					$this->data[$row][] = $this->getLocationFromCharacter ($v, $row, $col);
					$col ++;
				}
			}

			$profiler->stop ();

			$profiler->start ('Processing map meta data');
			$this->processMetaData ($data);
			$profiler->stop ();

			$profiler->stop ();
		}
	}

	private function processMetaData ($data)
	{
		$data = implode ($data);
		$mode = null;

		foreach (explode (PHP_EOL, $data) as $v)
		{
			if (empty ($v))
			{
				continue;
			}

			switch ($v)
			{
				case 'Spawnpoint names':
					$mode = 'spnames';
					continue 2;
				break;

				case 'Spawnpoint requirements':
					$mode = 'sprequirements';
					continue 2;
				break;

				case 'Objectives':
					$mode = 'objectives';
					continue 2;
				break;
			}

			switch ($mode)
			{
				case 'spnames':
					$data = explode ('=', $v, 2);
					if (count ($data) === 2)
					{
						$group = $this->getSpawnGroup ($data[0]);
						$group->setName ($data[1]);
						//$this->spawnnames[$this->getSideFromInput ($data[0])] = $data[1];
					}
				break;

				case 'sprequirements';

					$data = explode ('=', $v, 2);
					if (count ($data) === 2)
					{
						//$k = $this->getSideFromInput ($data[0]);
						
						$group = $this->getSpawnGroup ($data[0]);

						foreach (explode (";", $data[1]) as $req)
						{
							if (substr ($req, 0, 11) === 'checkpoint(')
							{
								$data = substr ($req, 11, strpos ($req, ')') - 11);
								$data = explode (',', $data);

								$requirement = array (
									'type' => 'checkpoint',
									'location' => new Neuron_GameServer_Map_Location ($data[0], $data[1])
								);

								$group->addRequirement ($requirement);

								//$this->spawnrequirements[$k][] = $requirement;
							}
						}
					}

				break;

				case 'objectives':

					$req = $v;
					if (substr ($req, 0, 11) === 'checkpoint(')
					{
						$data = substr ($req, 11, strpos ($req, ')') - 11);
						$data = explode (',', $data);

						$requirement = array (
							'type' => 'checkpoint',
							'location' => new Neuron_GameServer_Map_Location ($data[0], $data[1])
						);

						$this->addObjective ($requirement);

						//$this->spawnrequirements[$k][] = $requirement;
					}

				break;
			}
		}
	}

	private function addObjective ($objective)
	{
		$this->objectives[] = $objective;
	}

	public function getCheckpointObjectives ()
	{
		$this->loadMap ();
		$out = array ();
		foreach ($this->objectives as $v)
		{
			switch ($v['type'])
			{
				case 'checkpoint':
					$out[] = $v['location'];
				break;
			}
		}

		return $out;
	}

	public function isCheckpointObjective (Neuron_GameServer_Map_Location $location)
	{
		foreach ($this->getCheckpointObjectives () as $v)
		{
			if ($v->equals ($location))
			{
				return true;
			}
		}
		return false;
	}

	private function loadRandomMap ()
	{
		if (!isset ($_SESSION['tmp']))
		{
			$_SESSION['tmp'] = array ();
		}

		if (isset ($_SESSION['tmp']['randommap']))
		{
			return $_SESSION['tmp']['randommap'];
		}
		else
		{
			$size = 75;

			$_SESSION['tmp']['randommap'] = "";

			// Please ignore this.
			$fields = array 
			(
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_CLEAR,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_WALL,
				self::MAP_LOCATION_SPAWN,
				self::MAP_LOCATION_CHECKPOINT
			);

			$length = count ($fields) - 1;

			for ($i = 0; $i < $size; $i ++)
			{
				$_SESSION['tmp']['randommap'] .= "\n";
				for ($j = 0; $j < $size; $j ++)
				{
					if ($i === 0 || $i === ($size-1) || $j === 0 || $j === ($size-1))
					{
						$_SESSION['tmp']['randommap'] .= self::MAP_LOCATION_BORDER;
					}
					else
					{
						$_SESSION['tmp']['randommap'] .= $fields[mt_rand (0, $length)];
					}
				}
			}

			$_SESSION['tmp']['randommap'] = substr ($_SESSION['tmp']['randommap'], 1);

			return $_SESSION['tmp']['randommap'];
		}
	}
	
	private function getLocationFromCharacter ($inchar, $x, $y)
	{
		$char = $inchar;

		switch ($char)
		{
			case self::MAP_LOCATION_CLEAR:
				return new Dolumar_Underworld_Map_Locations_Floor ($x, $y);
			break;
			
			case self::MAP_LOCATION_WALL:
				return new Dolumar_Underworld_Map_Locations_Wall ($x, $y);
			break;

			case self::MAP_LOCATION_WATER:
				return new Dolumar_Underworld_Map_Locations_Water ($x, $y);
			break;

			case self::MAP_LOCATION_BORDER:
				return new Dolumar_Underworld_Map_Locations_Border ($x, $y);
			break;

			case self::MAP_LOCATION_CHECKPOINT:
				$spawnpoint = new Dolumar_Underworld_Map_Locations_Checkpoint ($x, $y);
				$this->checkpoints[] = $spawnpoint;
				return $spawnpoint;
			break;
		}

		if (empty ($char))
		{
			return new Dolumar_Underworld_Map_Locations_Floor ($x, $y);
		}
		else
		{
			$group = $this->getSpawnGroup ($inchar);

			$spawnpoint = new Dolumar_Underworld_Map_Locations_Spawn ($x, $y, $group);
			$this->spawnpoints[] = $spawnpoint;
			return $spawnpoint;
		}
	}

	private function getSideFromInput ($side)
	{
		$sides = array ('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
		$search = array_search ($side, $sides);

		return $search;
	}
	
	public function getSingleLocation ($x, $y)
	{
		$this->loadMap ();
		if (isset ($this->data[$x]) && isset ($this->data[$x][$y]))
		{
			return $this->data[$x][$y];
		}
		else
		{
			return new Dolumar_Underworld_Map_Locations_Black ($x, $y);
		}
	}

	public function isLocationOfType (Neuron_GameServer_Map_Location $location, $class)
	{
		foreach ($this->getLocation ($location) as $v)
		{
			if ($v instanceof $class)
			{
				return true;
			}
		}
		return false;
	}

	/**
	*	Get location and check the explored fog of war status
	*/
	public function getLocation (Neuron_GameServer_Map_Location $location, $objectcount = 0)
	{
		$x = $location->x ();
		$y = $location->y ();

		$out = array ();
		
		$location = $this->getSingleLocation ($x, $y);

		if ($location instanceof Dolumar_Underworld_Map_Locations_Black)
		{
			$out[] = $location->getTile ($this);
		}
		else
		{
			switch ($this->getExploreStatus ($location))
			{
				case Dolumar_Underworld_Map_FogOfWar::VISIBLE:
				
					$out[] = $location->getTile ($this);
					
				break;
				
				case Dolumar_Underworld_Map_FogOfWar::EXPLORED:

					$out[] = $location->getTile ($this);
					
					$loc = new Dolumar_Underworld_Map_Locations_Explored ($x, $y);
					$out[] = $loc->getTile ($this);

				break;
							
				case Dolumar_Underworld_Map_FogOfWar::UNEXPLORED:
				
					$loc = new Dolumar_Underworld_Map_Locations_Black ($x, $y);
					$out[] = $loc->getTile ($this);
				
				break;
			}
		}
		
		return $out;
	}
	
	public function getTileSize ()
	{
		//return array (88, 44);
		return array (100, 50);
	}
}
?>
