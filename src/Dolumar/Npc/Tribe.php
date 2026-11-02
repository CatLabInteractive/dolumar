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
 * Tribe identity of an NPC village: its name, its per-village multipliers, the words it uses for its hoard,
 * and the requests it posts. Pure functions; randomness is injected so tests are deterministic.
 */
class Dolumar_Npc_Tribe
{
	const HOARD_LEAN = 'lean';
	const HOARD_STOCKED = 'stocked';
	const HOARD_OVERFLOWING = 'overflowing';

	private static $firstNames = [
		'Qen', 'Kird', 'Gomirt', 'Roxeqi', 'Sanni', 'Kibo', 'Grals', 'Glus', 'Murz', 'Tikka', 'Vraal', 'Okk',
	];

	private static $familyNames = [
		'Slickbottoms', 'Grimheads', 'Pickscrews', 'Mantales', 'Grimflares', 'Shortsnipes', 'Fiercebuckets',
		'Shadowsnipes', 'Mudtongues', 'Rustfangs', 'Bogwhistles', 'Cinderpaws',
	];

	private static $holdings = [
		'Keep', 'Dump', 'Burrow', 'Hollow', 'Nook', 'Warren', 'Mound', 'Den',
	];

	/**
	 * @param callable|null $rand function (int $min, int $max): int
	 * @return string e.g. "Grimheads of Murz Hollow"
	 */
	public static function generateName ($rand = null)
	{
		$rand = self::rand ($rand);

		$family = self::$familyNames[$rand (0, count (self::$familyNames) - 1)];
		$first = self::$firstNames[$rand (0, count (self::$firstNames) - 1)];
		$holding = self::$holdings[$rand (0, count (self::$holdings) - 1)];

		return $family . ' of ' . $first . ' ' . $holding;
	}

	/**
	 * The fixed per-village multipliers, each between 0.8 and 1.2.
	 * @param callable|null $rand
	 * @return array{mul_threshold: float, mul_decay: float, mul_hoard: float}
	 */
	public static function multipliers ($rand = null)
	{
		$rand = self::rand ($rand);

		return [
			'mul_threshold' => $rand (80, 120) / 100,
			'mul_decay' => $rand (80, 120) / 100,
			'mul_hoard' => $rand (80, 120) / 100,
		];
	}

	/**
	 * @param int|float $sum resource sum of the village
	 * @param int|float $capacity resource capacity of the village
	 * @return string one of the HOARD_* words
	 */
	public static function hoardWord ($sum, $capacity)
	{
		if ($capacity <= 0) {
			return self::HOARD_LEAN;
		}

		$share = $sum / $capacity;
		if ($share < Dolumar_Npc_Config::HOARD_LEAN_BELOW) {
			return self::HOARD_LEAN;
		}
		if ($share >= Dolumar_Npc_Config::HOARD_OVERFLOWING_ABOVE) {
			return self::HOARD_OVERFLOWING;
		}
		return self::HOARD_STOCKED;
	}

	/**
	 * The tribe asks for the resource it has least of, sized to a share of its hoard.
	 * @param array $resources resource name => amount
	 * @param float $mulHoard the village's mul_hoard
	 * @return array{res: string, amount: int}|null null when there are no resources
	 */
	public static function makeRequest (array $resources, $mulHoard = 1.0)
	{
		// Gems are premium-ish; gold is the natural currency. Ask for the ordinary goods.
		$candidates = [];
		foreach (['wood', 'stone', 'iron', 'grain', 'gold'] as $res) {
			if (array_key_exists ($res, $resources)) {
				$candidates[$res] = (float) $resources[$res];
			}
		}

		if (count ($candidates) === 0) {
			return null;
		}

		asort ($candidates);
		$res = array_key_first ($candidates);

		$sum = array_sum ($candidates);
		$amount = $sum * Dolumar_Npc_Config::REQUEST_SHARE * $mulHoard;
		$amount = max (Dolumar_Npc_Config::REQUEST_MIN, min (Dolumar_Npc_Config::REQUEST_MAX, $amount));
		$amount = (int) (round ($amount / 50) * 50);

		return ['res' => $res, 'amount' => $amount];
	}

	/**
	 * @param callable|null $rand
	 * @return callable
	 */
	private static function rand ($rand)
	{
		if ($rand === null) {
			return function ($min, $max) {
				return mt_rand ($min, $max);
			};
		}
		return $rand;
	}
}
