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
 * The villages offered when a player picks a target for an attack or support in the Battle window:
 * nearby villages, recent opponents, profiles visited, a name search, and suggestions with a reason.
 *
 * The nearby list and the honour cost are premium (see canSeeNeighbourhood()); everything else is free.
 * Rows are plain arrays for battle/chooseTarget.phpt; names in them are already escaped.
 */
class Dolumar_Battle_TargetList
{
	const NEARBY = 20;
	const RECENT = 5;
	const SEARCH = 10;
	const SUGGESTIONS = 4;

	/** How far back "they attacked you" and suggestions look. */
	const RECENT_DAYS = 7;

	const STATUS_OK = 'ok';
	const STATUS_OWN = 'own';
	const STATUS_CLAN = 'clan';
	const STATUS_HOLIDAY = 'holiday';

	/** @var Dolumar_Players_Village */
	private $village;

	/** @var string attack or support */
	private $mode;

	/** @var bool */
	private $premium;

	/** @var int */
	private $now;

	/** @var int[]|null player ids of everyone in one of my clans (me excluded) */
	private $clanPlayers = null;

	/** @var array|null [x, y] */
	private $origin = null;

	/**
	 * @param Dolumar_Players_Village $village the village that attacks or sends support
	 * @param string $mode attack or support
	 * @param bool $premium whether the nearby list and honour costs are unlocked
	 * @param int|null $now
	 */
	public function __construct ($village, $mode, $premium, $now = null)
	{
		$this->village = $village;
		$this->mode = $mode === 'support' ? 'support' : 'attack';
		$this->premium = (bool) $premium;
		$this->now = $now === null ? NOW : $now;
	}

	/**
	 * Premium unlocks the nearby list and the honour cost. When premium cannot be bought on this server, nobody could
	 * unlock it, so it is open to everyone.
	 * @param Dolumar_Players_Player $player
	 * @return bool
	 */
	public static function canSeeNeighbourhood ($player)
	{
		if (!Dolumar_Premium_Prices::creditsEnabled ())
		{
			return true;
		}
		return $player && $player->isPremium ();
	}

	public function isPremium ()
	{
		return $this->premium;
	}

	public function getMode ()
	{
		return $this->mode;
	}

	/**
	 * The closest active villages (straight line between town centres), closest first.
	 * @param string $filter all, players or tribes
	 * @param int $limit
	 * @return array[] rows, empty without premium
	 */
	public function getNearby ($filter = 'all', $limit = self::NEARBY)
	{
		if (!$this->premium)
		{
			return array ();
		}

		$exclude = array ($this->village->getId ());
		if ($this->mode === 'support')
		{
			// Own and clan villages have their own sections in support mode.
			foreach ($this->getOwnVillageIds () as $id)
			{
				$exclude[] = $id;
			}
		}

		$where = '';
		if ($filter === 'players')
		{
			$where = ' AND v.plid > 0';
		}
		elseif ($filter === 'tribes')
		{
			$where = ' AND v.plid = 0';
		}

		return $this->queryByDistance ($where . ' AND v.vid NOT IN (' . implode (', ', array_map ('intval', $exclude)) . ')', $limit);
	}

	/**
	 * Support mode: my other villages, closest first.
	 * @return array[]
	 */
	public function getOwnVillages ()
	{
		$ids = array_diff ($this->getOwnVillageIds (), array ($this->village->getId ()));
		if (count ($ids) === 0)
		{
			return array ();
		}
		return $this->queryByDistance (' AND v.vid IN (' . implode (', ', array_map ('intval', $ids)) . ')', 50);
	}

	/**
	 * Support mode: villages of my clan mates, closest first.
	 * @return array[]
	 */
	public function getClanVillages ()
	{
		$players = $this->getClanPlayers ();
		if (count ($players) === 0)
		{
			return array ();
		}
		return $this->queryByDistance (' AND v.plid IN (' . implode (', ', array_map ('intval', $players)) . ')', 50);
	}

	/**
	 * Villages that attacked me in the last days, newest first, one row per village.
	 * @return array[]
	 */
	public function getAttackers ()
	{
		$rows = $this->queryReports ('targetId', 'fromId', $this->village->getId (), $this->now - self::RECENT_DAYS * 86400);

		$out = array ();
		foreach ($rows as $r)
		{
			$row = $this->getRow (Dolumar_Players_Village::getVillage ($r['vid']));
			if ($row)
			{
				$row['note'] = $this->text ('attackedYou', array ('when' => $this->formatWhen ($r['fightDate'])));
				$row['lastAttack'] = (int) $r['fightDate'];
				$out[] = $row;
			}
		}
		return $out;
	}

	/**
	 * Villages I attacked, newest first, one row per village.
	 * @return array[]
	 */
	public function getFought ()
	{
		$rows = $this->queryReports ('fromId', 'targetId', $this->village->getId (), 0);

		$out = array ();
		foreach ($rows as $r)
		{
			$row = $this->getRow (Dolumar_Players_Village::getVillage ($r['vid']));
			if ($row)
			{
				$key = $r['victory'] >= 0.01 ? 'youWon' : 'youLost';
				$row['note'] = $this->text ($key, array ('when' => $this->formatWhen ($r['fightDate'])));
				$out[] = $row;
			}
		}
		return $out;
	}

	/**
	 * Village profiles opened from this village (what the old picker called "your latest targets").
	 * @return array[]
	 */
	public function getVisited ()
	{
		$out = array ();
		$seen = array ();
		foreach ($this->village->visits->getLastVisits (self::RECENT * 2) as $v)
		{
			if (isset ($seen[$v->getId ()]))
			{
				continue;
			}
			$seen[$v->getId ()] = true;

			$row = $this->getRow ($v);
			if ($row)
			{
				$out[] = $row;
			}

			if (count ($out) >= self::RECENT)
			{
				break;
			}
		}
		return $out;
	}

	/**
	 * Name search, exact match first.
	 * @param string $query
	 * @return array[]
	 */
	public function search ($query)
	{
		$query = trim ((string) $query);
		if ($query === '')
		{
			return array ();
		}

		$db = Neuron_DB_Database::getInstance ();
		$q = $db->escape ($query);
		$like = $db->escape (addcslashes ($query, '%_\\'));

		$rows = $db->query ("
			SELECT vid
			FROM villages
			WHERE vname LIKE '%" . $like . "%' AND isActive = '1' AND vid != " . intval ($this->village->getId ()) . "
			ORDER BY vname = '" . $q . "' DESC, vname ASC
			LIMIT " . self::SEARCH
		);

		$out = array ();
		foreach ($rows as $r)
		{
			$row = $this->getRow (Dolumar_Players_Village::getVillage ($r['vid']));
			if ($row)
			{
				$out[] = $row;
			}
		}
		return $out;
	}

	/**
	 * A few villages worth attacking, each with its reason. Free: villages that attacked me or my clan. Premium adds the
	 * closest tribe and the closest player whose attack costs no honour. Never suggests a village that costs honour or
	 * cannot be attacked.
	 * @return array[]
	 */
	public function getSuggestions ()
	{
		if ($this->mode !== 'attack')
		{
			return array ();
		}

		$out = array ();
		$add = function ($row, $reason) use (&$out) {
			if ($row && !isset ($out[$row['id']]) && $row['status'] === self::STATUS_OK && !$row['costsHonour']
				&& count ($out) < self::SUGGESTIONS)
			{
				$row['note'] = $reason;
				$out[$row['id']] = $row;
			}
		};

		// Strike back.
		foreach (array_slice ($this->getAttackers (), 0, 2) as $row)
		{
			$add ($row, $this->text ('whyStrikeBack', array ('when' => $this->formatWhen ($row['lastAttack']))));
		}

		// Avenge a clan mate.
		$clanAttack = $this->getClanAttacker ();
		if ($clanAttack)
		{
			$add ($clanAttack['row'], $this->text ('whyClan', array (
				'village' => $clanAttack['victim'],
				'when' => $this->formatWhen ($clanAttack['fightDate'])
			)));
		}

		if ($this->premium)
		{
			$nearby = $this->getNearby ('all', self::NEARBY);

			foreach ($nearby as $row)
			{
				if ($row['npc'])
				{
					$add ($row, $this->text ('whyTribe'));
					break;
				}
			}

			$recent = array ();
			foreach ($this->queryReports ('fromId', 'targetId', $this->village->getId (), $this->now - self::RECENT_DAYS * 86400) as $r)
			{
				$recent[$r['vid']] = true;
			}

			foreach ($nearby as $row)
			{
				if (!$row['npc'] && !isset ($recent[$row['id']]) && $row['honour'] === 0)
				{
					$add ($row, $this->text ('whyPlayer'));
					break;
				}
			}
		}

		return array_values ($out);
	}

	/**
	 * One row for the picker, or null when the village is gone.
	 * @param Dolumar_Players_Village $target
	 * @param float|null $distance tiles, computed when null
	 * @return array|null
	 */
	public function getRow ($target, $distance = null)
	{
		if (!$target || !$target->isFound () || !$target->isActive ())
		{
			return null;
		}

		$owner = $target->getOwner ();
		$npc = $target->isNpc ();
		$me = $this->village->getOwner ();

		if ($distance === null)
		{
			$distance = $this->getDistance ($target);
		}

		if (!$npc && $owner->equals ($me))
		{
			$status = self::STATUS_OWN;
		}
		elseif (!$npc && in_array ($owner->getId (), $this->getClanPlayers ()))
		{
			$status = self::STATUS_CLAN;
		}
		elseif (!$npc && $owner->inVacationMode ())
		{
			$status = self::STATUS_HOLIDAY;
		}
		else
		{
			$status = self::STATUS_OK;
		}

		// Honour cost of an attack that is possible. Only shown with premium, but suggestions always avoid it.
		$cost = 0;
		if ($this->mode === 'attack' && $status === self::STATUS_OK)
		{
			$cost = (int) Dolumar_Battle_Battle::getHonourPenalty ($this->village, $target);
		}
		$honour = $this->premium && $this->mode === 'attack' && $status === self::STATUS_OK ? $cost : null;

		$race = $target->getRace ();

		return array
		(
			'id' => (int) $target->getId (),
			'name' => Neuron_Core_Tools::output_varchar ($target->getName ()),
			'owner' => $npc ? Neuron_Core_Text::getInstance ()->get ('tribe', 'targets', 'battle') : Neuron_Core_Tools::output_varchar ($owner->getNickname ()),
			'race' => $race ? Neuron_Core_Tools::output_varchar ($race->getRaceName ()) : '',
			'npc' => $npc,
			'networth' => (int) $target->getNetworth (),
			'distance' => $distance,
			'leagues' => $distance === null ? null : $this->formatLeagues ($distance),
			'status' => $status,
			'canPick' => $this->mode === 'support' ? $status !== self::STATUS_HOLIDAY : $status === self::STATUS_OK,
			'honour' => $honour,
			'costsHonour' => $cost > 0,
			'image' => $this->getImage ($target, $npc),
			'note' => null,
			'lastAttack' => null
		);
	}

	/**
	 * Straight-line distance in tiles between the two town centres.
	 * @param Dolumar_Players_Village $target
	 * @return float|null
	 */
	public function getDistance ($target)
	{
		$to = $target->buildings->getTownCenter ();
		if (!$to)
		{
			return null;
		}
		list ($x, $y) = $this->getOrigin ();
		list ($tx, $ty) = $to->getLocation ();
		return sqrt (pow ($tx - $x, 2) + pow ($ty - $y, 2));
	}

	/**
	 * @param float $tiles
	 * @return string "1.4"
	 */
	public function formatLeagues ($tiles)
	{
		$l = Dolumar_Map_Map::tile2league ($tiles);
		return number_format ($l, $l < 10 ? 1 : 0, '.', '');
	}

	private function getOrigin ()
	{
		if ($this->origin === null)
		{
			$this->origin = $this->village->buildings->getTownCenterLocation ();
		}
		return $this->origin;
	}

	private function getImage ($target, $npc)
	{
		if ($npc)
		{
			return 'sprites/goblins_towncenter.png';
		}
		$race = $target->getRace ();
		if ($race && strtolower ($race->getName ()) === 'darkelves')
		{
			return 'buildings/darkelves_towncenter.png';
		}
		return 'buildings/towncenter.png';
	}

	/**
	 * Active villages joined with their town centre, closest first.
	 * @param string $where extra SQL conditions on v (villages) starting with AND
	 * @param int $limit
	 * @return array[]
	 */
	private function queryByDistance ($where, $limit)
	{
		list ($x, $y) = $this->getOrigin ();
		$db = Neuron_DB_Database::getInstance ();

		$rows = $db->query ("
			SELECT
				v.vid,
				SQRT(POW(b.xas - " . (float) $x . ", 2) + POW(b.yas - " . (float) $y . ", 2)) AS distance
			FROM
				map_buildings b
			INNER JOIN
				villages v ON v.vid = b.village
			WHERE
				b.buildingType = 1
				AND b.destroyDate = 0
				AND v.isActive = '1'
				" . $where . "
			ORDER BY
				distance ASC
			LIMIT " . intval ($limit)
		);

		$out = array ();
		foreach ($rows as $r)
		{
			$row = $this->getRow (Dolumar_Players_Village::getVillage ($r['vid']), (float) $r['distance']);
			if ($row)
			{
				$out[] = $row;
			}
		}
		return $out;
	}

	/**
	 * Latest fought report per opponent.
	 * @param string $mine column holding my village id
	 * @param string $theirs column holding the opponent
	 * @param int $vid
	 * @param int $since
	 * @return array[] vid, fightDate, victory
	 */
	private function queryReports ($mine, $theirs, $vid, $since)
	{
		$db = Neuron_DB_Database::getInstance ();

		$rows = $db->query ("
			SELECT r.{$theirs} AS vid, r.fightDate, r.victory
			FROM battle_report r
			INNER JOIN (
				SELECT {$theirs} AS opp, MAX(fightDate) AS last
				FROM battle_report
				WHERE {$mine} = " . intval ($vid) . " AND fightDate <= " . intval ($this->now) . " AND fightDate >= " . intval ($since) . "
				GROUP BY {$theirs}
			) l ON l.opp = r.{$theirs} AND l.last = r.fightDate
			WHERE r.{$mine} = " . intval ($vid) . "
			ORDER BY r.fightDate DESC
			LIMIT " . self::RECENT
		);

		$out = array ();
		$seen = array ();
		foreach ($rows as $r)
		{
			if (!isset ($seen[$r['vid']]))
			{
				$seen[$r['vid']] = true;
				$out[] = $r;
			}
		}
		return $out;
	}

	/**
	 * The latest attack on a clan mate (not on me) by someone outside the clan.
	 * @return array|null row, victim name, fightDate
	 */
	private function getClanAttacker ()
	{
		$players = $this->getClanPlayers ();
		if (count ($players) === 0)
		{
			return null;
		}

		$db = Neuron_DB_Database::getInstance ();
		$rows = $db->query ("
			SELECT r.fromId, r.targetId, r.fightDate
			FROM battle_report r
			INNER JOIN villages t ON t.vid = r.targetId
			INNER JOIN villages a ON a.vid = r.fromId
			WHERE t.plid IN (" . implode (', ', array_map ('intval', $players)) . ")
				AND a.plid NOT IN (" . implode (', ', array_map ('intval', array_merge ($players, array ($this->village->getOwner ()->getId ())))) . ")
				AND r.fightDate <= " . intval ($this->now) . "
				AND r.fightDate >= " . intval ($this->now - self::RECENT_DAYS * 86400) . "
			ORDER BY r.fightDate DESC
			LIMIT 1
		");

		if (count ($rows) === 0)
		{
			return null;
		}

		$row = $this->getRow (Dolumar_Players_Village::getVillage ($rows[0]['fromId']));
		$victim = Dolumar_Players_Village::getVillage ($rows[0]['targetId']);
		if (!$row || !$victim->isFound ())
		{
			return null;
		}

		return array
		(
			'row' => $row,
			'victim' => Neuron_Core_Tools::output_varchar ($victim->getName ()),
			'fightDate' => (int) $rows[0]['fightDate']
		);
	}

	/**
	 * @return int[]
	 */
	private function getClanPlayers ()
	{
		if ($this->clanPlayers === null)
		{
			$this->clanPlayers = array ();
			$me = (int) $this->village->getOwner ()->getId ();

			$db = Neuron_DB_Database::getInstance ();
			$rows = $db->query ("
				SELECT DISTINCT m.plid
				FROM clan_members m
				INNER JOIN clan_members mine ON mine.c_id = m.c_id AND mine.plid = " . $me . " AND mine.cm_active = 1
				WHERE m.cm_active = 1 AND m.plid != " . $me
			);

			foreach ($rows as $r)
			{
				$this->clanPlayers[] = (int) $r['plid'];
			}
		}
		return $this->clanPlayers;
	}

	/**
	 * @return int[]
	 */
	private function getOwnVillageIds ()
	{
		$out = array ();
		foreach ($this->village->getOwner ()->getVillages () as $v)
		{
			$out[] = (int) $v->getId ();
		}
		return $out;
	}

	private function formatWhen ($date)
	{
		$date = (int) $date;
		return date ('Y-m-d', $date) === date ('Y-m-d', $this->now) ? date ('H:i', $date) : date ('d.m', $date);
	}

	private function text ($key, $params = array ())
	{
		$text = Neuron_Core_Text::getInstance ();
		return Neuron_Core_Tools::putIntoText ($text->get ($key, 'targets', 'battle'), $params);
	}
}
