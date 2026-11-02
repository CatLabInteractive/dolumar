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
 * Everything the NPC decision classes may look at, as plain arrays. Built once per village per cron run by
 * Dolumar_Npc_SnapshotBuilder; the planners and the standing updater never touch the database or NOW.
 */
class Dolumar_Npc_Snapshot
{
	/** @var int unix timestamp of this run */
	public $now = 0;

	/** @var int the NPC village id */
	public $vid = 0;

	/** @var string one of Dolumar_Npc_Config::AGGRESSION_* */
	public $aggression = Dolumar_Npc_Config::AGGRESSION_COUNTER;

	/** @var array resource name => amount in the NPC village */
	public $resources = [];

	/** @var array resource name => capacity */
	public $capacity = [];

	/**
	 * @var array ['available' => [runeId => amount], 'availableAmount' => int, 'used' => int, 'total' => int]
	 */
	public $runes = ['available' => [], 'availableAmount' => 0, 'used' => 0, 'total' => 0];

	/**
	 * @var array building class name => ['count' => int, 'runes' => int runes spent on it,
	 *            'rune' => runeId|'random', 'canBuild' => bool]
	 */
	public $buildings = [];

	/** @var int buildings under construction or upgrade */
	public $constructing = 0;

	/** @var bool a rune scout is under way */
	public $scouting = false;

	/** @var int runes of the strongest human village within NEIGHBOUR_RADIUS (at least 10) */
	public $strongestNeighbourRunes = 10;

	/**
	 * @var array unitId => ['class' => string, 'amount' => int, 'squadless' => int, 'inSquads' => int,
	 *            'space' => int, 'melee' => int, 'defIn' => int, 'defCav' => int]
	 */
	public $units = [];

	/**
	 * @var array idle, finished training buildings: buildingId => ['class' => string, 'units' => [
	 *            ['unitId' => int, 'class' => string, 'amount' => int max trainable now,
	 *             'melee' => int, 'defIn' => int, 'defCav' => int], ...]]
	 */
	public $training = [];

	/**
	 * @var array own squads: squadId => ['unitId' => int, 'amount' => int, 'home' => bool, 'idle' => bool,
	 *            'location' => int village id where it is]
	 */
	public $squads = [];

	/** @var array the npc_villages row (tribe_name, mul_*, last_report_id, last_transfer_id, request_*) */
	public $tribe = [];

	/** @var array plid => npc_standings row */
	public $standings = [];

	/**
	 * @var array new battle reports involving the NPC, oldest first:
	 *            ['reportId' => int, 'battleId' => int, 'fromId' => int, 'targetId' => int,
	 *             'fromPlid' => int, 'targetPlid' => int, 'victory' => float, 'date' => int]
	 */
	public $reports = [];

	/**
	 * @var array new received transfers to the NPC, oldest first:
	 *            ['id' => int, 'fromVid' => int, 'plid' => int, 'date' => int, 'items' => [res => amount],
	 *             'value' => int resource sum]
	 */
	public $transfers = [];

	/**
	 * @var array human villages within NEIGHBOUR_RADIUS: vid => ['vid' => int, 'plid' => int,
	 *            'distance' => float, 'reachable' => bool, 'runes' => int village runes,
	 *            'playerRunes' => int, 'playerCreated' => int, 'vacation' => bool]
	 */
	public $neighbours = [];

	/**
	 * @var array attacks under way against neighbour villages: ['battleId' => int, 'fromId' => int,
	 *            'targetId' => int, 'fightDate' => int]
	 */
	public $incoming = [];

	/**
	 * @var array battles fought against neighbour villages in the last ALLIED_STRIKE_WINDOW by villages other
	 *            than the NPC: ['fromId' => int, 'fromPlid' => int, 'targetId' => int, 'targetPlid' => int,
	 *            'date' => int]
	 */
	public $thirdPartyAttacks = [];

	/**
	 * @return int|float sum of all resources
	 */
	public function resourceSum ()
	{
		return array_sum ($this->resources);
	}

	/**
	 * @return int|float sum of all capacity
	 */
	public function capacitySum ()
	{
		return array_sum ($this->capacity);
	}

	/**
	 * @return int units at home that are not in a squad plus units in idle home squads
	 */
	public function availableArmy ()
	{
		$total = 0;
		foreach ($this->units as $unit) {
			$total += $unit['squadless'];
		}
		foreach ($this->squads as $squad) {
			if ($squad['home'] && $squad['idle']) {
				$total += $squad['amount'];
			}
		}
		return $total;
	}

	/**
	 * @param string $name mul_threshold, mul_decay or mul_hoard
	 * @return float
	 */
	public function mul ($name)
	{
		return isset ($this->tribe[$name]) ? (float) $this->tribe[$name] : 1.0;
	}

	/**
	 * Villages of a player among the neighbours, nearest first.
	 * @param int $plid
	 * @return array
	 */
	public function villagesOf ($plid)
	{
		$out = [];
		foreach ($this->neighbours as $village) {
			if ((int) $village['plid'] === (int) $plid) {
				$out[] = $village;
			}
		}
		usort ($out, function ($a, $b) {
			return $a['distance'] <=> $b['distance'];
		});
		return $out;
	}
}
