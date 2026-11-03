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
 * Builds the Dolumar_Npc_Snapshot of one NPC village from the village object and a few direct queries.
 * This is the only place (besides the executor) where the NPC code reads the game.
 */
class Dolumar_Npc_SnapshotBuilder
{
	/**
	 * @param Dolumar_Players_Village $village
	 * @param int $now
	 * @return Dolumar_Npc_Snapshot
	 */
	public static function build (Dolumar_Players_Village $village, $now)
	{
		$s = new Dolumar_Npc_Snapshot ();
		$s->now = (int) $now;
		$s->vid = (int) $village->getId ();
		$s->aggression = Dolumar_Npc_Config::aggression ();
		$s->protectionRunes = Dolumar_Npc_Config::protectionRunes ();

		$repository = Dolumar_Npc_StandingRepository::getInstance ();
		$s->tribe = $repository->ensureTribe ($s->vid, $now);
		$s->standings = $repository->standings ($s->vid);

		self::addEconomy ($s, $village);
		self::addArmy ($s, $village);
		self::addNeighbours ($s, $village);
		self::addEvents ($s);

		return $s;
	}

	/**
	 * @param Dolumar_Npc_Snapshot $s
	 * @param Dolumar_Players_Village $village
	 */
	private static function addEconomy (Dolumar_Npc_Snapshot $s, Dolumar_Players_Village $village)
	{
		$resources = $village->resources;

		$s->resources = self::numeric ($resources->getResources ());
		$s->capacity = self::numeric ($resources->getCapacity ());

		$available = [];
		foreach ($resources->getRunes () as $runeId => $amount) {
			$available[$runeId] = (int) $amount;
		}
		$s->runes = [
			'available' => $available,
			'availableAmount' => (int) $resources->getAvailableRunes_amount (),
			'used' => (int) $resources->getUsedRunes_amount (),
			'total' => (int) $resources->getTotalRunes (),
		];

		$race = $village->getRace ();
		foreach (array_keys (Dolumar_Npc_Planner_Economy::$ratios) as $class) {
			$building = Dolumar_Buildings_Building::getBuildingFromName ($class, $race);
			$cost = $building->getBuildingCost ($village);

			$s->buildings[$class] = [
				'count' => (int) $village->buildings->getBuildingAmount ($class),
				'runes' => (int) $village->buildings->getBuildingsRuneAmount ($class),
				'rune' => isset ($cost['runeId']) ? $cost['runeId'] : 'random',
				'canBuild' => (bool) $building->canBuildBuilding ($village),
			];
		}

		$s->constructing = count ($village->buildings->getBuildingBuildings ());
		$s->scouting = (bool) $village->isScouting ();
	}

	/**
	 * @param Dolumar_Npc_Snapshot $s
	 * @param Dolumar_Players_Village $village
	 */
	private static function addArmy (Dolumar_Npc_Snapshot $s, Dolumar_Players_Village $village)
	{
		foreach ($village->getAllUnits ($s->now) as $unit) {
			if ($unit->hasEternalLife ()) {
				continue;
			}
			$s->units[(int) $unit->getUnitId ()] = self::unitEntry ($unit, (int) $unit->getTotalAmount (), (int) $unit->getSquadlessAmount ());
		}

		foreach ($village->buildings->getBuildings () as $building) {
			if (!$building instanceof Dolumar_Buildings_Training) {
				continue;
			}
			if (!$building->isFinished ($s->now) || $building->isTrainingUnits ()) {
				continue;
			}

			$units = [];
			foreach ($building->getCheckedAvailableUnits () as $unit) {
				$entry = self::unitEntry ($unit, 0, 0);
				$entry['amount'] = (int) $building->calculateOptimalTrainable ($unit);
				unset ($entry['squadless'], $entry['inSquads']);
				$units[] = $entry;
			}

			$s->training[(int) $building->getId ()] = [
				'class' => get_class ($building),
				'units' => $units,
			];
		}

		foreach ($village->getSquads (false, false, true) as $squad) {
			$s->squads[(int) $squad->getId ()] = [
				'unitId' => (int) $squad->getUnitType (),
				'amount' => (int) $squad->getUnitsAmount (),
				'home' => (bool) $squad->isHome (),
				'idle' => (bool) $squad->isIdle (),
				'location' => (int) $squad->getCurrentLocation ()->getId (),
			];
		}
	}

	/**
	 * @param Dolumar_Units_Unit $unit
	 * @param int $amount
	 * @param int $squadless
	 * @return array
	 */
	private static function unitEntry (Dolumar_Units_Unit $unit, $amount, $squadless)
	{
		return [
			'unitId' => (int) $unit->getUnitId (),
			'class' => get_class ($unit),
			'amount' => $amount,
			'squadless' => $squadless,
			'inSquads' => max (0, $amount - $squadless),
			'space' => (int) $unit->getRequiredSpace (),
			'melee' => (int) $unit->getStat ('melee'),
			'defIn' => (int) $unit->getStat ('defIn'),
			'defCav' => (int) $unit->getStat ('defCav'),
		];
	}

	/**
	 * Human villages within NEIGHBOUR_RADIUS of the town centre, with their owner's age and strength.
	 * @param Dolumar_Npc_Snapshot $s
	 * @param Dolumar_Players_Village $village
	 */
	private static function addNeighbours (Dolumar_Npc_Snapshot $s, Dolumar_Players_Village $village)
	{
		list ($x, $y) = $village->buildings->getTownCenterLocation ();
		$radius = Dolumar_Npc_Config::NEIGHBOUR_RADIUS;
		$db = Neuron_DB_Database::getInstance ();

		$rows = $db->query ("
			SELECT
				b.village AS vid,
				v.plid,
				SQRT(POW(b.xas - " . (float) $x . ", 2) + POW(b.yas - " . (float) $y . ", 2)) AS distance
			FROM
				map_buildings b
			INNER JOIN
				villages v ON v.vid = b.village
			WHERE
				b.buildingType = 1
				AND b.destroyDate = 0
				AND v.isActive = 1
				AND v.plid > 0
				AND b.village != " . (int) $s->vid . "
				AND b.xas BETWEEN " . ((float) $x - $radius) . " AND " . ((float) $x + $radius) . "
				AND b.yas BETWEEN " . ((float) $y - $radius) . " AND " . ((float) $y + $radius) . "
		");

		$players = [];
		$strongest = 10;

		foreach ($rows as $row) {
			if ((float) $row['distance'] > $radius) {
				continue;
			}

			$plid = (int) $row['plid'];
			if (!isset ($players[$plid])) {
				$player = Neuron_GameServer::getPlayer ($plid);
				$players[$plid] = [
					'playerRunes' => $player ? (int) $player->getTotalRunes () : 0,
					'playerCreated' => $player ? (int) $player->getCreationDate () : 0,
					'vacation' => $player ? (bool) $player->inVacationMode () : false,
				];
			}

			$neighbour = Dolumar_Players_Village::getVillage ((int) $row['vid']);
			$runes = (int) $neighbour->resources->getTotalRunes ();
			$strongest = max ($strongest, $runes);

			$s->neighbours[(int) $row['vid']] = [
				'vid' => (int) $row['vid'],
				'plid' => $plid,
				'distance' => (float) $row['distance'],
				'reachable' => true,
				'runes' => $runes,
				'playerRunes' => $players[$plid]['playerRunes'],
				'playerCreated' => $players[$plid]['playerCreated'],
				'vacation' => $players[$plid]['vacation'],
			];
		}

		$s->strongestNeighbourRunes = $strongest;
	}

	/**
	 * New reports and transfers for the NPC, plus what happens to the neighbours.
	 * @param Dolumar_Npc_Snapshot $s
	 */
	private static function addEvents (Dolumar_Npc_Snapshot $s)
	{
		$db = Neuron_DB_Database::getInstance ();
		$vid = (int) $s->vid;

		$rows = $db->query ("
			SELECT
				r.reportId, r.battleId, r.fromId, r.targetId, r.victory, r.fightDate,
				vf.plid AS fromPlid, vt.plid AS targetPlid
			FROM
				battle_report r
			LEFT JOIN villages vf ON vf.vid = r.fromId
			LEFT JOIN villages vt ON vt.vid = r.targetId
			WHERE
				(r.fromId = " . $vid . " OR r.targetId = " . $vid . ")
				AND r.reportId > " . (int) $s->tribe['last_report_id'] . "
			ORDER BY
				r.reportId ASC
		");
		foreach ($rows as $row) {
			$s->reports[] = [
				'reportId' => (int) $row['reportId'],
				'battleId' => (int) $row['battleId'],
				'fromId' => (int) $row['fromId'],
				'targetId' => (int) $row['targetId'],
				'fromPlid' => (int) $row['fromPlid'],
				'targetPlid' => (int) $row['targetPlid'],
				'victory' => (float) $row['victory'],
				'date' => (int) $row['fightDate'],
			];
		}

		$transfers = [];
		$rows = $db->query ("
			SELECT
				t.t_id, t.from_vid, v.plid, UNIX_TIMESTAMP(t.t_date_received) AS date
			FROM
				villages_transfers t
			LEFT JOIN villages v ON v.vid = t.from_vid
			WHERE
				t.to_vid = " . $vid . "
				AND t.t_id > " . (int) $s->tribe['last_transfer_id'] . "
				AND t.t_isReceived = '1'
			ORDER BY
				t.t_id ASC
		");
		foreach ($rows as $row) {
			$transfers[(int) $row['t_id']] = [
				'id' => (int) $row['t_id'],
				'fromVid' => (int) $row['from_vid'],
				'plid' => (int) $row['plid'],
				'date' => (int) $row['date'],
				'items' => [],
				'value' => 0,
			];
		}
		if (count ($transfers) > 0) {
			$items = $db->query ("
				SELECT t_id, ti_key, ti_amount
				FROM villages_transfers_items
				WHERE t_id IN (" . implode (', ', array_keys ($transfers)) . ") AND ti_type = 'RESOURCE'
			");
			foreach ($items as $item) {
				$id = (int) $item['t_id'];
				$key = (string) $item['ti_key'];
				$amount = (int) $item['ti_amount'];
				$transfers[$id]['items'][$key] = (isset ($transfers[$id]['items'][$key]) ? $transfers[$id]['items'][$key] : 0) + $amount;
				$transfers[$id]['value'] += $amount;
			}
		}
		$s->transfers = array_values ($transfers);

		if (count ($s->neighbours) === 0) {
			return;
		}
		$neighbourIds = implode (', ', array_keys ($s->neighbours));

		$rows = $db->query ("
			SELECT battleId, vid, targetId, fightDate
			FROM battle
			WHERE targetId IN (" . $neighbourIds . ") AND fightDate > " . (int) $s->now . " AND isFought = 0
		");
		foreach ($rows as $row) {
			$s->incoming[] = [
				'battleId' => (int) $row['battleId'],
				'fromId' => (int) $row['vid'],
				'targetId' => (int) $row['targetId'],
				'fightDate' => (int) $row['fightDate'],
			];
		}

		$rows = $db->query ("
			SELECT
				r.fromId, r.targetId, r.fightDate, vf.plid AS fromPlid, vt.plid AS targetPlid
			FROM
				battle_report r
			LEFT JOIN villages vf ON vf.vid = r.fromId
			LEFT JOIN villages vt ON vt.vid = r.targetId
			WHERE
				r.targetId IN (" . $neighbourIds . ")
				AND r.fromId != " . $vid . "
				AND r.fightDate > " . ((int) $s->now - Dolumar_Npc_Config::ALLIED_STRIKE_WINDOW) . "
			ORDER BY
				r.fightDate DESC
		");
		foreach ($rows as $row) {
			$s->thirdPartyAttacks[] = [
				'fromId' => (int) $row['fromId'],
				'fromPlid' => (int) $row['fromPlid'],
				'targetId' => (int) $row['targetId'],
				'targetPlid' => (int) $row['targetPlid'],
				'date' => (int) $row['fightDate'],
			];
		}
	}

	/**
	 * @param array $values
	 * @return array the same keys with numeric values
	 */
	private static function numeric (array $values)
	{
		$out = [];
		foreach ($values as $key => $value) {
			$out[$key] = is_numeric ($value) ? $value + 0 : 0;
		}
		return $out;
	}
}
