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
*	The database side of the armies layer: which battles and squad moves
*	are in flight, which of them touch the regions a client looks at,
*	and which villages a viewer is related to. Pure SQL + array logic so
*	it can be tested with a fake database.
*/
class Dolumar_NewMap_MovingRows
{
	/**
	*	Battles that are still on the map, joined to both town centres.
	*	Row keys match Dolumar_NewMap_ArmyFactory::fromBattleRow().
	*/
	public static function battles (Neuron_DB_Database $db, $now)
	{
		$now = intval ($now);

		return self::rows ($db->query
		("
			SELECT
				b.battleId, b.vid, b.targetId, b.startDate, b.arriveDate, b.fightDate,
				b.endFightDate, b.endDate, b.isFought, b.route,
				tc1.xas AS fromX, tc1.yas AS fromY,
				tc2.xas AS toX, tc2.yas AS toY,
				v1.vname AS fromName, v2.vname AS toName
			FROM
				battle b
			JOIN villages v1 ON v1.vid = b.vid
			JOIN villages v2 ON v2.vid = b.targetId
			LEFT JOIN map_buildings tc1 ON tc1.village = b.vid AND tc1.buildingType IN (1,3) AND tc1.destroyDate = 0
			LEFT JOIN map_buildings tc2 ON tc2.village = b.targetId AND tc2.buildingType IN (1,3) AND tc2.destroyDate = 0
			WHERE
				b.endDate > {$now} OR b.isFought = 0
		"));
	}

	/**
	*	Squad moves still in flight. Row keys match ArmyFactory::fromSquadRow().
	*/
	public static function squads (Neuron_DB_Database $db, $now)
	{
		$now = intval ($now);

		return self::rows ($db->query
		("
			SELECT
				sc.sc_id, sc.s_id, sc.s_from, sc.s_to, sc.sc_route,
				UNIX_TIMESTAMP(sc.s_start) AS s_start,
				UNIX_TIMESTAMP(sc.s_end) AS s_end,
				tc1.xas AS fromX, tc1.yas AS fromY,
				tc2.xas AS toX, tc2.yas AS toY,
				vs.s_name AS squadName, vs.v_id AS homeVid,
				v2.vname AS toName
			FROM
				squad_commands sc
			JOIN villages_squads vs ON vs.s_id = sc.s_id
			JOIN villages v2 ON v2.vid = sc.s_to
			LEFT JOIN map_buildings tc1 ON tc1.village = sc.s_from AND tc1.buildingType IN (1,3) AND tc1.destroyDate = 0
			LEFT JOIN map_buildings tc2 ON tc2.village = sc.s_to AND tc2.buildingType IN (1,3) AND tc2.destroyDate = 0
			WHERE
				sc.s_action = 'move'
				AND sc.s_end > FROM_UNIXTIME({$now})
		"));
	}

	/**
	*	Villages the player may see army details of: their own, plus every
	*	village of every member of every clan they are in.
	*	@return int[]
	*/
	public static function relatedVillageIds (Neuron_DB_Database $db, $plid)
	{
		$plid = intval ($plid);

		$own = $db->query
		("
			SELECT vid
			FROM villages
			WHERE plid = {$plid} AND isActive = 1
		");

		$clan = $db->query
		("
			SELECT v.vid
			FROM clan_members cm
			JOIN clan_members cm2 ON cm2.c_id = cm.c_id AND cm2.cm_active = 1
			JOIN villages v ON v.plid = cm2.plid AND v.isActive = 1
			WHERE cm.plid = {$plid} AND cm.cm_active = 1
		");

		$out = array ();
		foreach (array (self::rows ($own), self::rows ($clan)) as $rows)
		{
			foreach ($rows as $v)
			{
				$vid = intval ($v['vid']);
				if (!in_array ($vid, $out, true))
				{
					$out[] = $vid;
				}
			}
		}

		return $out;
	}

	/**
	*	Does the bounding box of $points (padded by one tile) overlap any area?
	*	@param array $points list of array (x, y)
	*	@param Neuron_GameServer_Map_Area[] $areas
	*/
	public static function touchesAreas (array $points, array $areas)
	{
		if (count ($points) == 0 || count ($areas) == 0)
		{
			return false;
		}

		$minX = $maxX = (float) $points[0][0];
		$minY = $maxY = (float) $points[0][1];
		foreach ($points as $p)
		{
			$minX = min ($minX, (float) $p[0]);
			$maxX = max ($maxX, (float) $p[0]);
			$minY = min ($minY, (float) $p[1]);
			$maxY = max ($maxY, (float) $p[1]);
		}
		$minX -= 1; $minY -= 1; $maxX += 1; $maxY += 1;

		foreach ($areas as $area)
		{
			$c = $area->getCenter ();
			$r = $area->getRadius ();

			$overlaps =
				$minX <= $c->x () + $r && $maxX >= $c->x () - $r &&
				$minY <= $c->y () + $r && $maxY >= $c->y () - $r;

			if ($overlaps)
			{
				return true;
			}
		}

		return false;
	}

	/**
	*	All points a row's army passes: both town centres and the stored route.
	*/
	public static function pointsOfRow (array $row, $routeKey)
	{
		$points = array ();
		if (isset ($row['fromX'], $row['fromY']))
		{
			$points[] = array ($row['fromX'], $row['fromY']);
		}
		if (isset ($row['toX'], $row['toY']))
		{
			$points[] = array ($row['toX'], $row['toY']);
		}

		$route = isset ($row[$routeKey]) ? json_decode ($row[$routeKey], true) : null;
		if (is_array ($route))
		{
			foreach ($route as $p)
			{
				if (is_array ($p) && isset ($p[0], $p[1]) && is_numeric ($p[0]) && is_numeric ($p[1]))
				{
					$points[] = array ($p[0], $p[1]);
				}
			}
		}

		return $points;
	}

	/**
	*	Serve $loader's result from $cache for $ttl seconds. The cache stores
	*	the load time itself because Neuron_Core_Memcache has no expiry.
	*	A broken or absent cache simply calls the loader every time.
	*
	*	@param object $cache anything with hasCache/getCache/setCache (Neuron_Core_Memcache)
	*/
	public static function cached ($cache, $key, $ttl, $now, $loader)
	{
		if ($cache && $cache->hasCache ($key))
		{
			$entry = $cache->getCache ($key);
			if (is_array ($entry) && isset ($entry['t'], $entry['data']) && $now - $entry['t'] < $ttl)
			{
				return $entry['data'];
			}
		}

		$data = $loader ();

		if ($cache)
		{
			$cache->setCache ($key, array ('t' => $now, 'data' => $data));
		}

		return $data;
	}

	private static function rows ($result)
	{
		$out = array ();
		if ($result)
		{
			foreach ($result as $v)
			{
				$out[] = $v;
			}
		}
		return $out;
	}
}
