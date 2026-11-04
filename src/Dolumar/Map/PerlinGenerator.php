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

	/*
		Smoothed noise per lattice point. Every value is computed from
		randCache values that are fixed once read, so it is cleared
		together with randCache to keep the results identical.
	*/
	private $smoothCache;

	/*
		Cosine factor per fraction. Fractions are always multiples of
		1 / (ZOOM * 2^octave), so there are only a handful of them.
	*/
	private $cosineCache;
	
	public function __construct ()
	{
		$this->maxmapstraal = (MAXMAPSTRAAL / self::ZOOM) * (MAXMAPSTRAAL / self::ZOOM);
		$this->cache = array ();
		$this->randCache = array ();
		$this->smoothCache = array ();
		$this->cosineCache = array ();
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
				$this->smoothCache = array ();
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
		$this->smoothCache = array ();
	
		$lx = ($x + 1) * self::PAR_FILE;
		$ly = ($y + 1) * self::PAR_FILE;
	
		for ($i = $x * self::PAR_FILE; $i < $lx; $i ++)
		{
			for ($j = $y * self::PAR_FILE; $j < $ly; $j ++)
			{
				$this->getOneSingleLocation ($i, $j);
			}
		}
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
		if (isset ($this->smoothCache[$x][$y]))
		{
			return $this->smoothCache[$x][$y];
		}

		$corners = (
			$this->getNoise ($x-1, $y-1, $freq)
			+ $this->getNoise ($x+1, $y-1, $freq)
			+ $this->getNoise ($x-1, $y+1, $freq)
			+ $this->getNoise ($x+1, $y+1, $freq)
			) / 16;
			
		$sides = (
			$this->getNoise ($x-1, $y, $freq)
			+ $this->getNoise ($x+1, $y, $freq)
			+ $this->getNoise ($x, $y-1, $freq)
			+ $this->getNoise ($x, $y+1, $freq)
			) / 8;
			
		$center = $this->getNoise ($x, $y, $freq) / 4;
		
		return $this->smoothCache[$x][$y] = $corners + $sides + $center;
	}

	private function getInterpolatedNoise ($x, $y, $freq)
	{
		$inX = floor ($x);
		$faX = $x - $inX;
		
		$inY = floor ($y);
		$faY = $y - $inY;
		
		$v1 = $this->getSmoothNoise ($inX, $inY, $freq);
		$v2 = $this->getSmoothNoise ($inX + 1, $inY, $freq);
		$v3 = $this->getSmoothNoise ($inX, $inY + 1, $freq);
		$v4 = $this->getSmoothNoise ($inX + 1, $inY + 1, $freq);
		
		// Interpolate
		$fX = $this->getCosineFactor ($faX);
		$i1 = round ($v1 * (1-$fX) + $v2*$fX, 8);
		$i2 = round ($v3 * (1-$fX) + $v4*$fX, 8);

		$fY = $this->getCosineFactor ($faY);
		return round ($i1 * (1-$fY) + $i2*$fY, 8);
	}

	/*
		The weight of the cosine interpolation for fraction $x.
		Cached by the exact fraction; a fraction that is not a multiple
		of 1/65536 is computed every time.
	*/
	private function getCosineFactor ($x)
	{
		$key = $x * 65536;

		if ($key == (int) $key)
		{
			$key = (int) $key;

			if (!isset ($this->cosineCache[$key]))
			{
				$this->cosineCache[$key] = self::calculateCosineFactor ($x);
			}

			return $this->cosineCache[$key];
		}

		return self::calculateCosineFactor ($x);
	}

	private static function calculateCosineFactor ($x)
	{
		$ft = $x * 3.14;
		return round ((1 - round (cos($ft), 8)) / 2, 8);
	}
}