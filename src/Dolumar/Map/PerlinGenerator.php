<?php
if (!defined ('MAX_MEMORY_USAGE'))
{
	define ('MAX_MEMORY_USAGE', 50000000);
}

class Dolumar_Map_PerlinGenerator
{
	const PAR_FILE = 25;
	
	const ZOOM = 8;
	const OCTAVES = 2;
	const PERSISTENCE = 0.6;

	public static function getPerlinNoise ($x, $y)
	{
		if (defined ('MAP_PERLIN_NO_CACHE'))
		{
			$data = new self ();
			return $data->getOneSingleLocation ($x, $y);
		}
	
		static $in;
		
		if (!isset ($in))
		{
			$in = new self ();
		}
		
		return $in->getLocalNoise ($x, $y);
	}
	
	private $cache;
	private $maxmapstraal;
	private $randCache;
	
	public function __construct ()
	{
		$this->maxmapstraal = (MAXMAPSTRAAL / self::ZOOM) * (MAXMAPSTRAAL / self::ZOOM);
		$this->cache = array ();
		$this->randCache = array ();
	}
	
	public function getLocalNoise ($x, $y)
	{
		// Load a certain area
		if (!isset ($this->cache[$x]) || !isset ($this->cache[$x][$y]))
		{
			if (memory_get_usage () > MAX_MEMORY_USAGE)
			{
				$this->cache = array ();
				$this->randCache = array ();
			}
		
			// Generate a new square
			$this->generateSquare (floor ($x / self::PAR_FILE), floor ($y / self::PAR_FILE));
		}
		
		return $this->cache[$x][$y];
	}
	
	private function generateSquare ($x, $y)
	{
		// Clear some space:
		$this->randCache = array ();
	
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		
		$profiler->start ('Generating perlin noise square ('.$x.','.$y.')');
	
		$lx = ($x + 1) * self::PAR_FILE;
		$ly = ($y + 1) * self::PAR_FILE;
	
		for ($i = $x * self::PAR_FILE; $i < $lx; $i ++)
		{
			for ($j = $y * self::PAR_FILE; $j < $ly; $j ++)
			{
				self::getOneSingleLocation ($i, $j);
			}
		}
		
		$profiler->stop ();
	}

	private function getOneSingleLocation ($i, $j)
	{
		$amp = 1;
		$freq = 1;
		$total = 0;
		
		for ($o = 0; $o < self::OCTAVES; $o ++)
		{
			$total += $this->getInterpolatedNoise ($i * $freq / self::ZOOM, $j * $freq / self::ZOOM, $freq) * $amp;
			//$total += $this->getSmoothNoise ($i * $freq / self::ZOOM, $j * $freq / self::ZOOM, $freq) * $amp;
			
			$freq *= 2;
			$amp *= self::PERSISTENCE;
		}
		
		$color = round (($total * 5) + 4, 4);

		if ($color > 999) $color = 999;
		if ($color < 0) $color = 0;
		
		if (!isset ($this->cache[$i]))
		{
			$this->cache[$i] = array ();
		}
		
		$this->cache[$i][$j] = $color;

		return $this->cache[$i][$j];
	}
	
	// Perlin noise
	private function getNoise ($x, $y, $freq)
	{
		if (!isset ($this->randCache[$x]) || !isset ($this->randCache[$x][$y]))
		{
			if (!isset ($this->randCache[$x]))
			{
				$this->randCache[$x] = array ();
			}
			
			if ( ($x*$x) + ($y*$y) > ( $this->maxmapstraal * $freq * $freq ))
			{
				$this->randCache[$x][$y] = -1;
			}
		
			else
			{			

				$this->randCache[$x][$y] = self::getRandomNumber ($x, $y);
			}
		}
	
		return $this->randCache[$x][$y];
	}
	
	/*
		Returns a random number between -1 and 1
	*/
	public static function getRandomNumber ($x, $y)
	{
		/*
		srand (abs (10000 - ( $x * 1000) + $y) + RANDMAPFACTOR);
		return rand (-10000, 10000) / 10000;
		*/
		
		return (Dolumar_Map_Map::getRandom ($x, $y, 20000) - 10000) / 10000;
	}

	private function getSmoothNoise ($x, $y, $freq)
	{
		$corners = (
			self::getNoise ($x-1, $y-1, $freq)
			+ self::getNoise ($x+1, $y-1, $freq)
			+ self::getNoise ($x-1, $y+1, $freq)
			+ self::getNoise ($x+1, $y+1, $freq)
			) / 16;
			
		$sides = (
			self::getNoise ($x-1, $y, $freq)
			+ self::getNoise ($x+1, $y, $freq)
			+ self::getNoise ($x, $y-1, $freq)
			+ self::getNoise ($x, $y+1, $freq)
			) / 8;
			
		$center = self::getNoise ($x, $y, $freq) / 4;
		
		return $corners + $sides + $center;
	}

	private function getInterpolatedNoise ($x, $y, $freq)
	{
		$inX = floor ($x);
		$faX = $x - $inX;
		
		$inY = floor ($y);
		$faY = $y - $inY;
		
		$v1 = self::getSmoothNoise ($inX, $inY, $freq);
		$v2 = self::getSmoothNoise ($inX + 1, $inY, $freq);
		$v3 = self::getSmoothNoise ($inX, $inY + 1, $freq);
		$v4 = self::getSmoothNoise ($inX + 1, $inY + 1, $freq);
		
		// Interpolate
		$i1 = self::getInterpolation ($v1, $v2, $faX);
		$i2 = self::getInterpolation ($v3, $v4, $faX);
		
		return self::getInterpolation ($i1, $i2, $faY);
	}

	/* Standard consine interpolation */
	private function getInterpolation ($a, $b, $x)
	{
		$ft = $x * 3.14;
		$f = round ((1 - round (cos($ft), 8)) / 2, 8);
		return round ($a * (1-$f) + $b*$f, 8);
	}
}