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
*	Builds Dolumar_NewMap_Army objects (with their movements) from a
*	battle row or a squad_commands row joined to both town centres.
*	Pure: no database access, so it can be unit tested.
*/
class Dolumar_NewMap_ArmyFactory
{
	const KIND_ATTACK = 'attack';
	const KIND_WAITING = 'waiting';
	const KIND_RETURNING = 'returning';
	const KIND_SQUAD = 'squad';

	/** Map object id of a battle (attack, wait, return) */
	public static function battleId ($battleId)
	{
		return 'b:' . intval ($battleId);
	}

	/** Map object id of a squad move */
	public static function squadId ($scId)
	{
		return 's:' . intval ($scId);
	}

	/**
	*	Onclick script that centres the map on an army. $fallback (x, y) is where
	*	to look when the army is not loaded on the map yet (a town centre it
	*	passes); the map then follows the army once it is loaded.
	*	Safe inside a double-quoted HTML attribute.
	*/
	public static function focusScript ($id, array $fallback)
	{
		$id = preg_replace ('/[^a-z0-9:]/i', '', $id);
		$x = is_numeric ($fallback[0]) ? floatval ($fallback[0]) : 0;
		$y = is_numeric ($fallback[1]) ? floatval ($fallback[1]) : 0;

		return "Game.dolumar.focusArmy(event, '" . $id . "', " . $x . ", " . $y . ");";
	}

	/**
	*	@param array $row battle columns plus fromX, fromY, toX, toY, fromName, toName
	*	@param int $now
	*	@param int $poll client poll interval; an overdue, unprocessed fight waits this long
	*	@return Dolumar_NewMap_Army|null null when a town centre is missing
	*/
	public static function fromBattleRow (array $row, $now, $poll = 30)
	{
		if (!self::hasCoordinates ($row))
		{
			return null;
		}

		$now = intval ($now);
		$from = array ((float) $row['fromX'], (float) $row['fromY']);
		$to = array ((float) $row['toX'], (float) $row['toY']);
		$segments = self::segments (self::routeFromJson ($row['route'] ?? null, $from, $to));

		$startDate = intval ($row['startDate']);
		$arriveDate = intval ($row['arriveDate']);
		$fightDate = intval ($row['fightDate']);
		$isFought = intval ($row['isFought']) > 0;
		$endFightDate = isset ($row['endFightDate']) ? intval ($row['endFightDate']) : null;
		$endDate = intval ($row['endDate']);

		// Waiting at the gates until the fight is over. When the fight is overdue
		// but nobody has processed it yet, keep waiting until the next poll.
		if ($isFought && $endFightDate !== null)
		{
			$waitEnd = $endFightDate;
		}
		else
		{
			$waitEnd = max ($fightDate, $now + intval ($poll));
		}
		$waitEnd = max ($waitEnd, $arriveDate + 1);

		$returns = $isFought && $endFightDate !== null && $endDate > $endFightDate + 1;

		if ($now < $arriveDate)
		{
			$kind = self::KIND_ATTACK;
		}
		elseif ($now < $waitEnd || !$returns)
		{
			$kind = self::KIND_WAITING;
		}
		else
		{
			$kind = self::KIND_RETURNING;
		}

		$vid = intval ($row['vid']);
		$army = new Dolumar_NewMap_Army
		(
			self::battleId ($row['battleId']),
			$kind,
			array ($vid, intval ($row['targetId'])),
			$row['fromName'] . ' → ' . $row['toName'],
			"openWindow ('battle', {'vid': " . $vid . "});",
			self::spriteUrl ($kind)
		);
		$army->setLocation (new Neuron_GameServer_Map_Location ($from[0], $from[1]));

		self::addPhase ($army, $segments, $startDate, $arriveDate);
		self::addPhase ($army, array (array ($to, $to)), $arriveDate, $waitEnd);

		if ($returns)
		{
			self::addPhase ($army, self::reverse ($segments), $waitEnd, $endDate);
		}

		return $army;
	}

	/**
	*	@param array $row squad_commands columns (s_start, s_end as unix timestamps)
	*		plus fromX, fromY, toX, toY, squadName, toName, homeVid
	*	@param int $now
	*	@return Dolumar_NewMap_Army|null
	*/
	public static function fromSquadRow (array $row, $now)
	{
		if (!self::hasCoordinates ($row))
		{
			return null;
		}

		$from = array ((float) $row['fromX'], (float) $row['fromY']);
		$to = array ((float) $row['toX'], (float) $row['toY']);
		$segments = self::segments (self::routeFromJson ($row['sc_route'] ?? null, $from, $to));

		$homeVid = intval ($row['homeVid']);
		$army = new Dolumar_NewMap_Army
		(
			self::squadId ($row['sc_id']),
			self::KIND_SQUAD,
			array (intval ($row['s_from']), intval ($row['s_to']), $homeVid),
			$row['squadName'] . ' → ' . $row['toName'],
			"openWindow ('squads', {'vid': " . $homeVid . "});",
			self::spriteUrl (self::KIND_SQUAD)
		);
		$army->setLocation (new Neuron_GameServer_Map_Location ($from[0], $from[1]));

		self::addPhase ($army, $segments, intval ($row['s_start']), intval ($row['s_end']));

		return $army;
	}

	/**
	*	Walking segments of a route. A route is start, [portal in, portal out, ...] end:
	*	an even number of points is walked in pairs (the gap between a pair and the
	*	next is a portal jump). An odd number of points is walked point to point.
	*
	*	@param array $route list of array (x, y)
	*	@return array list of array (array (x, y), array (x, y))
	*/
	public static function segments (array $route)
	{
		$out = array ();
		$count = count ($route);

		if ($count < 2)
		{
			$p = $count == 1 ? $route[0] : array (0, 0);
			return array (array ($p, $p));
		}

		if ($count % 2 == 0)
		{
			for ($i = 0; $i < $count; $i += 2)
			{
				$out[] = array ($route[$i], $route[$i + 1]);
			}
		}
		else
		{
			for ($i = 0; $i < $count - 1; $i ++)
			{
				$out[] = array ($route[$i], $route[$i + 1]);
			}
		}

		return $out;
	}

	public static function spriteUrl ($kind)
	{
		return IMAGE_URL . 'sprites/armies/' . $kind . '.png';
	}

	private static function hasCoordinates (array $row)
	{
		foreach (array ('fromX', 'fromY', 'toX', 'toY') as $k)
		{
			if (!isset ($row[$k]) || !is_numeric ($row[$k]))
			{
				return false;
			}
		}
		return true;
	}

	/**
	*	Decode a stored route; fall back to the straight line when missing or broken.
	*/
	private static function routeFromJson ($json, array $from, array $to)
	{
		$fallback = array ($from, $to);

		if ($json === null || $json === '')
		{
			return $fallback;
		}

		$route = json_decode ($json, true);
		if (!is_array ($route) || count ($route) < 2)
		{
			return $fallback;
		}

		$out = array ();
		foreach ($route as $p)
		{
			if (!is_array ($p) || !isset ($p[0], $p[1]) || !is_numeric ($p[0]) || !is_numeric ($p[1]))
			{
				return $fallback;
			}
			$out[] = array ((float) $p[0], (float) $p[1]);
		}

		return $out;
	}

	private static function reverse (array $segments)
	{
		$out = array ();
		foreach (array_reverse ($segments) as $s)
		{
			$out[] = array ($s[1], $s[0]);
		}
		return $out;
	}

	private static function length (array $segment)
	{
		return sqrt
		(
			pow ($segment[1][0] - $segment[0][0], 2) +
			pow ($segment[1][1] - $segment[0][1], 2)
		);
	}

	/**
	*	Spread [$start, $end] over the segments pro rata to their length,
	*	one straight movement per segment.
	*/
	private static function addPhase (Dolumar_NewMap_Army $army, array $segments, $start, $end)
	{
		$start = intval ($start);
		$end = intval ($end);

		$total = 0;
		foreach ($segments as $s)
		{
			$total += self::length ($s);
		}

		$t = $start;
		$count = count ($segments);
		foreach ($segments as $i => $s)
		{
			if ($i == $count - 1)
			{
				$te = $end;
			}
			elseif ($total > 0)
			{
				$te = $t + (int) round (($end - $start) * self::length ($s) / $total);
			}
			else
			{
				$te = $t;
			}

			$army->addMovement (new Neuron_GameServer_Map_Movement
			(
				$t,
				$te,
				new Neuron_GameServer_Map_Location ($s[0][0], $s[0][1]),
				new Neuron_GameServer_Map_Location ($s[1][0], $s[1][1])
			));

			$t = $te;
		}
	}
}
