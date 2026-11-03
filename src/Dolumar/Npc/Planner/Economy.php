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
 * Decides whether the tribe scouts for runes, what it builds, and what it asks the neighbours for.
 * Pure: snapshot in, actions out.
 */
class Dolumar_Npc_Planner_Economy
{
	/** How many build candidates to hand the executor, in order of preference. */
	const BUILD_CANDIDATES = 3;

	/**
	 * The building ratio a calm tribe aims for (rune share per building type).
	 * @var array
	 */
	public static $ratios = [
		'Dolumar_Buildings_Silo' => 0.8,
		'Dolumar_Buildings_Farm' => 1.5,
		'Dolumar_Buildings_Lumber' => 0.8,
		'Dolumar_Buildings_StoneMine' => 0.5,
		'Dolumar_Buildings_IronMine' => 0.8,
		'Dolumar_Buildings_Barrack' => 1.0,
		'Dolumar_Buildings_Archery' => 0.6,
		'Dolumar_Buildings_Stable' => 0.5,
		'Dolumar_Buildings_Tower' => 0.4,
	];

	/**
	 * The ratio when the war drums beat: towers and barracks first, the stable last.
	 * @var array
	 */
	public static $defensiveRatios = [
		'Dolumar_Buildings_Silo' => 0.6,
		'Dolumar_Buildings_Farm' => 1.2,
		'Dolumar_Buildings_Lumber' => 0.6,
		'Dolumar_Buildings_StoneMine' => 0.5,
		'Dolumar_Buildings_IronMine' => 0.8,
		'Dolumar_Buildings_Barrack' => 1.2,
		'Dolumar_Buildings_Archery' => 0.8,
		'Dolumar_Buildings_Stable' => 0.2,
		'Dolumar_Buildings_Tower' => 1.4,
	];

	/**
	 * @param Dolumar_Npc_Snapshot $s
	 * @param array|null $rows standing rows after the updater ran; defaults to the snapshot's rows
	 * @return Dolumar_Npc_Action[] at most one scout and BUILD_CANDIDATES build actions
	 */
	public static function plan (Dolumar_Npc_Snapshot $s, ?array $rows = null)
	{
		$actions = [];

		if (self::shouldScout ($s)) {
			$actions[] = new Dolumar_Npc_Action (Dolumar_Npc_Action::SCOUT, ['runes' => Dolumar_Npc_Config::SCOUT_RUNES_AMOUNT]);
		}

		foreach (self::buildCandidates ($s, $rows === null ? $s->standings : $rows) as $candidate) {
			$actions[] = new Dolumar_Npc_Action (Dolumar_Npc_Action::BUILD, $candidate);
		}

		return $actions;
	}

	/**
	 * A request action when the tribe has none, or its current one is stale.
	 * @param Dolumar_Npc_Snapshot $s
	 * @return Dolumar_Npc_Action|null
	 */
	public static function request (Dolumar_Npc_Snapshot $s)
	{
		$current = (string) ($s->tribe['request_res'] ?? '');
		$created = (int) ($s->tribe['request_created'] ?? 0);

		if ($current !== '' && $s->now - $created < Dolumar_Npc_Config::REQUEST_COOLDOWN) {
			return null;
		}

		$request = Dolumar_Npc_Tribe::makeRequest ($s->resources, $s->mul ('mul_hoard'));
		if ($request === null) {
			return null;
		}

		return new Dolumar_Npc_Action (Dolumar_Npc_Action::REQUEST, $request);
	}

	/**
	 * @param Dolumar_Npc_Snapshot $s
	 * @return bool
	 */
	public static function shouldScout (Dolumar_Npc_Snapshot $s)
	{
		if ($s->scouting) {
			return false;
		}
		if ($s->runes['availableAmount'] > Dolumar_Npc_Config::MAX_AVAILABLE_RUNES) {
			return false;
		}
		// The growth cap: never grow past MAX_STRENGTH_FACTOR times the strongest human neighbour.
		if ($s->runes['total'] / Dolumar_Npc_Config::MAX_STRENGTH_FACTOR > max (10, $s->strongestNeighbourRunes)) {
			return false;
		}
		return true;
	}

	/**
	 * @param Dolumar_Npc_Snapshot $s
	 * @return bool resources above the reserve floor
	 */
	public static function aboveReserve (Dolumar_Npc_Snapshot $s)
	{
		$floor = Dolumar_Npc_Config::RESERVE_PER_RUNE * max (1, $s->runes['used']) * $s->mul ('mul_hoard');
		return $s->resourceSum () > $floor;
	}

	/**
	 * @param array $rows
	 * @param Dolumar_Npc_Snapshot $s
	 * @return bool any player is wary or worse
	 */
	public static function isThreatened (Dolumar_Npc_Snapshot $s, array $rows)
	{
		foreach ($rows as $row) {
			$tier = Dolumar_Npc_Standing::tier ($row, $s->now, $s->mul ('mul_threshold'), $s->mul ('mul_decay'));
			if (Dolumar_Npc_Standing::isUnfriendly ($tier)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Building types in order of preference, each with the rune to use.
	 * @param Dolumar_Npc_Snapshot $s
	 * @param array $rows
	 * @return array [['building' => class, 'rune' => runeId], ...]
	 */
	public static function buildCandidates (Dolumar_Npc_Snapshot $s, array $rows)
	{
		if ($s->constructing >= Dolumar_Npc_Config::MAX_CONSTRUCTING) {
			return [];
		}
		if (!self::aboveReserve ($s)) {
			return [];
		}

		$ratios = self::isThreatened ($s, $rows) ? self::$defensiveRatios : self::$ratios;
		$order = self::buildOrder ($s, $ratios);

		$available = array_filter ($s->runes['available'], function ($amount) {
			return $amount > 0;
		});
		if (count ($available) === 0) {
			return [];
		}
		arsort ($available);
		$mostPlentiful = array_key_first ($available);

		$candidates = [];
		foreach ($order as $class) {
			$info = isset ($s->buildings[$class]) ? $s->buildings[$class] : null;
			if ($info === null || empty ($info['canBuild'])) {
				continue;
			}

			$rune = isset ($info['rune']) ? $info['rune'] : 'random';
			if ($rune === 'random') {
				$rune = $mostPlentiful;
			} elseif (!isset ($available[$rune])) {
				continue;
			}

			$candidates[] = ['building' => $class, 'rune' => $rune];
			if (count ($candidates) >= self::BUILD_CANDIDATES) {
				break;
			}
		}

		return $candidates;
	}

	/**
	 * Sort the building types by how far below their desired rune share they are.
	 * @param Dolumar_Npc_Snapshot $s
	 * @param array $ratios
	 * @return string[] class names
	 */
	public static function buildOrder (Dolumar_Npc_Snapshot $s, array $ratios)
	{
		$total = array_sum ($ratios);
		$used = max (1, $s->runes['used']);

		$diffs = [];
		foreach ($ratios as $class => $ratio) {
			$current = isset ($s->buildings[$class]) ? $s->buildings[$class]['runes'] : 0;
			$diffs[$class] = ($ratio / $total) - ($current / $used);
		}

		uksort ($diffs, function ($a, $b) use ($diffs, $ratios) {
			if ($diffs[$a] == $diffs[$b]) {
				return $ratios[$b] <=> $ratios[$a];
			}
			return $diffs[$b] <=> $diffs[$a];
		});

		return array_keys ($diffs);
	}
}
