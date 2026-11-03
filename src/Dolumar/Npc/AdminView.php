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
 * Turns npc_standings rows into what the "NPC tribes" admin page shows. Pure: no database, no NOW.
 */
class Dolumar_Npc_AdminView
{
	/**
	 * @param array $row an npc_standings row
	 * @param array $tribe the npc_villages row (mul_threshold, mul_decay)
	 * @param int $now
	 * @return array value (decayed, 1 decimal), tier, stage, truce, pact, support, last_attack
	 */
	public static function standing (array $row, array $tribe, $now)
	{
		$row = Dolumar_Npc_Standing::fromDb ($row);
		$mulThreshold = isset ($tribe['mul_threshold']) ? (float) $tribe['mul_threshold'] : 1.0;
		$mulDecay = isset ($tribe['mul_decay']) ? (float) $tribe['mul_decay'] : 1.0;

		$value = Dolumar_Npc_Standing::valueAt ($row, $now, $mulDecay);
		$inTruce = Dolumar_Npc_Standing::inTruce ($row, $now);

		return [
			'value' => round ($value, 1),
			'tier' => Dolumar_Npc_Standing::tierOf ($value, $mulThreshold),
			'stage' => $inTruce ? 'truce' : self::stage ($row),
			'truce' => $inTruce ? self::duration ($row['truce_until'] - $now) : '',
			'pact' => (int) $row['pact'] === 1,
			'support' => ($row['support_squad'] > 0 && $row['support_until'] > $now)
				? self::duration ($row['support_until'] - $now) : '',
			'last_attack' => $row['last_attack_at'] > 0 ? self::duration ($now - $row['last_attack_at']) . ' ago' : '',
		];
	}

	/**
	 * Where the player is on the scouts, envoy, war party ladder.
	 * @param array $row
	 * @return string
	 */
	private static function stage (array $row)
	{
		if ($row['war_parties_sent'] > 0) {
			return 'war parties sent: ' . (int) $row['war_parties_sent'];
		}
		if ($row['envoy_at'] > 0) {
			return 'envoy sent, price ' . (int) $row['envoy_price'];
		}
		if ($row['scouts_at'] > 0) {
			return 'scouts sent';
		}
		return '-';
	}

	/**
	 * @param int $seconds
	 * @return string "3d 4h", "5h 7m" or "12m"
	 */
	public static function duration ($seconds)
	{
		$seconds = max (0, (int) $seconds);
		$days = intdiv ($seconds, 86400);
		$hours = intdiv ($seconds % 86400, 3600);
		$minutes = intdiv ($seconds % 3600, 60);

		if ($days > 0) {
			return $days . 'd ' . $hours . 'h';
		}
		if ($hours > 0) {
			return $hours . 'h ' . $minutes . 'm';
		}
		return $minutes . 'm';
	}
}
