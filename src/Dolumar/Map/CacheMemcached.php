<?php
if (!defined ('MAX_MEMORY_USAGE'))
{
	define ('MAX_MEMORY_USAGE', 50000000);
}

class Dolumar_Map_CacheMemcached
{
	private $iServer;
	private $sTable;
	private $objMemcache;
	
	const AREA_SIDE = 25;

	public static function getInstance ()
	{
		return self::__getInstance ();
	}

	public static function __getInstance ()
	{
		static $in;
		if (!isset ($in))
		{
			$in = new Dolumar_Map_CacheMemcached ();
		}
		return $in;
	}

	private function __construct ()
	{		
		$this->objMemcache = Neuron_Core_Memcache::getInstance ();
		
		$server = Neuron_GameServer::getServer();
		$this->iServer = $server->getServerId ();
	}
	
	public function hasLocationCache ($x, $y)
	{
		//return parent::hasCache ('map'.$x.'x'.$y);
		return $this->getLocationCache ($x, $y) !== false;
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
				$tmparray = array ();
			
				// Load all the data for this area
				for ($i = $startX; $i < $endX; $i ++)
				{
					$this->aTiles[$i] = array ();
					$tmparray[$i] = array ();
					
					for ($j = $startY; $j < $endY; $j ++)
					{

						$data = Dolumar_Map_Map::getFreshTileData ($i, $j);
					
						$this->aTiles[$i][$j] = $data;
						$tmparray[$i][$j] = $data;
					}
				}
							
				// Put these results in memcache
				$this->objMemcache->setCache ($memcachename, $this->pack ($tmparray, $startX, $startY));
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
		return;
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
