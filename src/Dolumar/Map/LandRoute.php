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

/**
*	The walking part of a trip over land: a straight line, except where that
*	line crosses water, where it goes around the lake along the shore.
*
*	Only the stretches of the line that are under water are searched (a grid
*	A* inside a box around that crossing), so the cost depends on the size of
*	the lakes crossed and not on the length of the trip. The result is
*	smoothed to straight legs, so a leg over open land is exactly as long as
*	the straight line was. When no way around is found, the straight line is
*	used, as before: a village never becomes unreachable.
*
*	Water never changes, so results are cached in memcache without expiry.
*/
class Dolumar_Map_LandRoute
{
	/** Points tested per tile along a line. */
	const SAMPLES_PER_TILE = 3;

	/** Tiles added around a crossing for the search box; doubled on each retry. */
	const MARGIN = 16;
	const RETRIES = 3;

	/** Search budget per crossing (tiles expanded). */
	const MAX_NODES = 60000;

	const CACHE_VERSION = 1;

	/** @var callable|null fn (int $x, int $y): bool, for tests */
	private static $waterTest = null;

	/** @var object|false|null memcache (getCache/setCache), false = no cache */
	private static $cache = null;

	private static $memo = array ();
	private static $waterMemo = array ();

	/**
	*	@param array $from (x, y)
	*	@param array $to (x, y)
	*	@return array ('distance' => float, 'points' => array of array (x, y))
	*		from $from to $to inclusive.
	*/
	public static function walk (array $from, array $to)
	{
		$a = array ($from[0], $from[1]);
		$b = array ($to[0], $to[1]);

		$straight = Dolumar_Map_Distance::calculateDistance ($a[0], $a[1], $b[0], $b[1]);
		if ($straight == 0)
		{
			return array ('distance' => 0, 'points' => array ($a, $b));
		}

		// Search one direction only, so a trip back takes exactly as long as the trip out.
		if ($b[0] < $a[0] || ($b[0] == $a[0] && $b[1] < $a[1]))
		{
			$back = self::walk ($b, $a);
			return array ('distance' => $back['distance'], 'points' => array_reverse ($back['points']));
		}

		$key = self::cacheKey ($a, $b);
		if (isset (self::$memo[$key]))
		{
			return self::$memo[$key];
		}

		$cache = self::getCache ();
		if ($cache)
		{
			$cached = $cache->getCache ($key);
			if (is_string ($cached))
			{
				$points = json_decode ($cached, true);
				if (is_array ($points) && count ($points) >= 2)
				{
					return self::remember ($key, self::result ($points));
				}
			}
		}

		$points = self::findPoints ($a, $b);
		$result = self::result ($points);

		if ($cache)
		{
			$cache->setCache ($key, json_encode ($points));
		}

		return self::remember ($key, $result);
	}

	/**
	*	@param callable|null $test fn (int $x, int $y): bool; null = the real map
	*/
	public static function setWaterTest ($test)
	{
		self::$waterTest = $test;
		self::reset ();
	}

	/**
	*	@param object|false|null $cache false disables caching, null = memcache
	*/
	public static function setCache ($cache)
	{
		self::$cache = $cache;
		self::reset ();
	}

	public static function reset ()
	{
		self::$memo = array ();
		self::$waterMemo = array ();
	}

	private static function remember ($key, array $result)
	{
		if (count (self::$memo) > 1000)
		{
			self::$memo = array ();
		}
		self::$memo[$key] = $result;
		return $result;
	}

	private static function result (array $points)
	{
		$distance = 0;
		for ($i = 1; $i < count ($points); $i ++)
		{
			$distance += Dolumar_Map_Distance::calculateDistance
			(
				$points[$i - 1][0], $points[$i - 1][1],
				$points[$i][0], $points[$i][1]
			);
		}
		return array ('distance' => $distance, 'points' => $points);
	}

	private static function getCache ()
	{
		if (self::$cache === null)
		{
			self::$cache = class_exists ('Neuron_Core_Memcache') && defined ('RANDMAPFACTOR')
				? Neuron_Core_Memcache::getInstance () : false;
		}
		return self::$cache;
	}

	private static function cacheKey (array $a, array $b)
	{
		// Loading Dolumar_Map_Map sets the seed global from RANDMAPFACTOR (tests change it).
		if (defined ('RANDMAPFACTOR'))
		{
			class_exists ('Dolumar_Map_Map');
		}
		$seed = isset ($GLOBALS['MAP_CONFIG_RANDMAPFACTOR']) ? $GLOBALS['MAP_CONFIG_RANDMAPFACTOR'] : '';
		return 'landroute_' . self::CACHE_VERSION . '_' . md5 ($seed) . '_' .
			$a[0] . '_' . $a[1] . '_' . $b[0] . '_' . $b[1];
	}

	/*
		The route itself, uncached.
	*/
	private static function findPoints (array $a, array $b)
	{
		$straight = array ($a, $b);

		// A town on a water tile (should not happen): keep the old behaviour.
		if (self::isWater (self::tile ($a)) || self::isWater (self::tile ($b)))
		{
			return $straight;
		}

		$crossings = self::crossings ($a, $b);
		if (count ($crossings) == 0)
		{
			return $straight;
		}

		$points = array ($a);
		foreach ($crossings as $crossing)
		{
			list ($shoreIn, $shoreOut) = $crossing;

			$detour = self::searchAround ($shoreIn, $shoreOut);
			if ($detour === false)
			{
				return $straight;
			}

			foreach (self::smooth ($detour) as $p)
			{
				$points[] = $p;
			}
		}
		$points[] = $b;

		return self::shortcut (self::withoutRepeats ($points));
	}

	/**
	*	The stretches of the straight line from $a to $b that are under water,
	*	as array (last land tile before, first land tile after).
	*/
	private static function crossings (array $a, array $b)
	{
		$out = array ();

		$length = Dolumar_Map_Distance::calculateDistance ($a[0], $a[1], $b[0], $b[1]);
		$n = max (1, (int) ceil ($length * self::SAMPLES_PER_TILE));

		$lastLand = self::tile ($a);
		$inWater = false;

		for ($i = 0; $i <= $n; $i ++)
		{
			$t = $i / $n;
			$tile = self::tile (array ($a[0] + ($b[0] - $a[0]) * $t, $a[1] + ($b[1] - $a[1]) * $t));

			if (self::isWater ($tile))
			{
				$inWater = true;
			}
			else
			{
				if ($inWater)
				{
					$out[] = array ($lastLand, $tile);
					$inWater = false;
				}
				$lastLand = $tile;
			}
		}

		return $out;
	}

	/**
	*	Grid A* from $start to $goal over land tiles inside a box around both,
	*	growing the box when no way is found. Returns the tiles walked, or false.
	*/
	private static function searchAround (array $start, array $goal)
	{
		$margin = self::MARGIN + (int) ceil (Dolumar_Map_Distance::calculateDistance ($start[0], $start[1], $goal[0], $goal[1]) / 2);

		for ($try = 0; $try < self::RETRIES; $try ++)
		{
			$box = array
			(
				min ($start[0], $goal[0]) - $margin,
				min ($start[1], $goal[1]) - $margin,
				max ($start[0], $goal[0]) + $margin,
				max ($start[1], $goal[1]) + $margin
			);

			$path = self::astar ($start, $goal, $box);
			if ($path === null)
			{
				// Out of budget: a bigger box will not help.
				return false;
			}
			if ($path !== false)
			{
				return $path;
			}

			$margin *= 2;
		}

		return false;
	}

	/**
	*	@return array|false|null tiles from $start to $goal; false when the box
	*		holds no way; null when the search budget ran out.
	*/
	private static function astar (array $start, array $goal, array $box)
	{
		static $steps = array
		(
			array (1, 0), array (-1, 0), array (0, 1), array (0, -1),
			array (1, 1), array (1, -1), array (-1, 1), array (-1, -1)
		);

		$open = new SplPriorityQueue ();
		$open->setExtractFlags (SplPriorityQueue::EXTR_DATA);

		$startKey = $start[0] . '|' . $start[1];
		$g = array ($startKey => 0.0);
		$from = array ();
		$closed = array ();

		// Ties broken by insertion order, so the result does not depend on the
		// queue's internal order.
		$serial = PHP_INT_MAX;
		$open->insert ($start, array (-self::octile ($start, $goal), $serial --));
		$expanded = 0;

		while (!$open->isEmpty ())
		{
			$c = $open->extract ();
			$ck = $c[0] . '|' . $c[1];
			if (isset ($closed[$ck]))
			{
				continue;
			}
			$closed[$ck] = true;

			if ($c[0] == $goal[0] && $c[1] == $goal[1])
			{
				$path = array ($c);
				while (isset ($from[$ck]))
				{
					$c = $from[$ck];
					$ck = $c[0] . '|' . $c[1];
					$path[] = $c;
				}
				return array_reverse ($path);
			}

			if (++ $expanded > self::MAX_NODES)
			{
				return null;
			}

			foreach ($steps as $d)
			{
				$n = array ($c[0] + $d[0], $c[1] + $d[1]);
				$nk = $n[0] . '|' . $n[1];

				if (isset ($closed[$nk]))
				{
					continue;
				}
				if ($n[0] < $box[0] || $n[1] < $box[1] || $n[0] > $box[2] || $n[1] > $box[3])
				{
					continue;
				}
				if (self::isWater ($n))
				{
					continue;
				}

				$diagonal = $d[0] != 0 && $d[1] != 0;

				// No cutting a corner of a lake.
				if ($diagonal && (self::isWater (array ($c[0] + $d[0], $c[1])) || self::isWater (array ($c[0], $c[1] + $d[1]))))
				{
					continue;
				}

				$cost = $g[$ck] + ($diagonal ? M_SQRT2 : 1);
				if (!isset ($g[$nk]) || $cost < $g[$nk])
				{
					$g[$nk] = $cost;
					$from[$nk] = $c;
					$open->insert ($n, array (-($cost + self::octile ($n, $goal)), $serial --));
				}
			}
		}

		return false;
	}

	private static function octile (array $a, array $b)
	{
		$dx = abs ($a[0] - $b[0]);
		$dy = abs ($a[1] - $b[1]);
		return max ($dx, $dy) + (M_SQRT2 - 1) * min ($dx, $dy);
	}

	/**
	*	Keep only the points needed to walk the same way in straight lines
	*	over land (from each kept point, the farthest point still in sight).
	*/
	private static function smooth (array $points)
	{
		$count = count ($points);
		if ($count <= 2)
		{
			return $points;
		}

		$out = array ($points[0]);
		$anchor = 0;
		$i = 1;

		while ($i < $count - 1)
		{
			if (!self::inSight ($points[$anchor], $points[$i + 1]))
			{
				$out[] = $points[$i];
				$anchor = $i;
			}
			$i ++;
		}

		$out[] = $points[$count - 1];
		return $out;
	}

	/**
	*	Like smooth (), but from each kept point jump to the farthest point in
	*	sight, not the last one of an unbroken run. Quadratic, so only used on
	*	the few points left after smooth ().
	*/
	private static function shortcut (array $points)
	{
		$count = count ($points);
		if ($count <= 2)
		{
			return $points;
		}

		$out = array ($points[0]);
		$i = 0;
		while ($i < $count - 1)
		{
			$next = $i + 1;
			for ($j = $count - 1; $j > $i + 1; $j --)
			{
				if (self::inSight ($points[$i], $points[$j]))
				{
					$next = $j;
					break;
				}
			}
			$out[] = $points[$next];
			$i = $next;
		}

		return $out;
	}

	private static function inSight (array $a, array $b)
	{
		$length = Dolumar_Map_Distance::calculateDistance ($a[0], $a[1], $b[0], $b[1]);
		$n = max (1, (int) ceil ($length * self::SAMPLES_PER_TILE));

		for ($i = 0; $i <= $n; $i ++)
		{
			$t = $i / $n;
			if (self::isWater (self::tile (array ($a[0] + ($b[0] - $a[0]) * $t, $a[1] + ($b[1] - $a[1]) * $t))))
			{
				return false;
			}
		}
		return true;
	}

	private static function withoutRepeats (array $points)
	{
		$out = array ();
		foreach ($points as $p)
		{
			$last = end ($out);
			if ($last === false || $last[0] != $p[0] || $last[1] != $p[1])
			{
				$out[] = $p;
			}
		}
		return $out;
	}

	private static function tile (array $p)
	{
		return array ((int) round ($p[0]), (int) round ($p[1]));
	}

	private static function isWater (array $tile)
	{
		$k = $tile[0] . '|' . $tile[1];
		if (!isset (self::$waterMemo[$k]))
		{
			if (count (self::$waterMemo) > 500000)
			{
				self::$waterMemo = array ();
			}

			self::$waterMemo[$k] = self::$waterTest
				? (bool) call_user_func (self::$waterTest, $tile[0], $tile[1])
				: Dolumar_Map_Map::isWater ($tile[0], $tile[1]);
		}
		return self::$waterMemo[$k];
	}
}
