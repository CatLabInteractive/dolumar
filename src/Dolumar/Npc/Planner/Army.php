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
 * Decides what each idle training building trains: the unit type the army has least of, so the army rotates
 * through its types instead of stacking the first one. When the war drums beat, defensive stats come first.
 * Pure: snapshot in, actions out.
 */
class Dolumar_Npc_Planner_Army
{
	/**
	 * @param Dolumar_Npc_Snapshot $s
	 * @param array|null $rows standing rows after the updater ran; defaults to the snapshot's rows
	 * @return Dolumar_Npc_Action[] one train action per idle training building with something to train
	 */
	public static function plan (Dolumar_Npc_Snapshot $s, ?array $rows = null)
	{
		$defensive = Dolumar_Npc_Planner_Economy::isThreatened ($s, $rows === null ? $s->standings : $rows);

		// Count the army per unit id, including what earlier actions in this run will add.
		$owned = [];
		foreach ($s->units as $unitId => $unit) {
			$owned[(int) $unitId] = (int) $unit['amount'];
		}

		$actions = [];
		foreach ($s->training as $buildingId => $building) {
			$choice = self::choose ($building['units'], $owned, $defensive);
			if ($choice === null) {
				continue;
			}

			$actions[] = new Dolumar_Npc_Action (Dolumar_Npc_Action::TRAIN, [
				'building' => (int) $buildingId,
				'unit' => $choice['class'],
				'unitId' => (int) $choice['unitId'],
				'amount' => (int) $choice['amount'],
			]);

			$owned[(int) $choice['unitId']] = (isset ($owned[(int) $choice['unitId']]) ? $owned[(int) $choice['unitId']] : 0) + (int) $choice['amount'];
		}

		return $actions;
	}

	/**
	 * @param array $units trainable units of one building (see Dolumar_Npc_Snapshot::$training)
	 * @param array $owned unit id => amount in the army
	 * @param bool $defensive
	 * @return array|null the chosen unit entry
	 */
	public static function choose (array $units, array $owned, $defensive)
	{
		$candidates = array_values (array_filter ($units, function ($unit) {
			return (int) $unit['amount'] >= 1;
		}));
		if (count ($candidates) === 0) {
			return null;
		}

		usort ($candidates, function ($a, $b) use ($owned, $defensive) {
			if ($defensive) {
				$defA = (int) $a['defIn'] + (int) $a['defCav'];
				$defB = (int) $b['defIn'] + (int) $b['defCav'];
				if ($defA !== $defB) {
					return $defB <=> $defA;
				}
			}
			$ownedA = isset ($owned[(int) $a['unitId']]) ? $owned[(int) $a['unitId']] : 0;
			$ownedB = isset ($owned[(int) $b['unitId']]) ? $owned[(int) $b['unitId']] : 0;
			return $ownedA <=> $ownedB;
		});

		return $candidates[0];
	}
}
