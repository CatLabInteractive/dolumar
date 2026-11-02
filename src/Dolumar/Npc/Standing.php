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
 * Pure arithmetic over one npc_standings row (a plain array). Every function returns a new row and never
 * touches the database or NOW; the caller passes the time. Decay is computed from changed_at, so a missed
 * cron run costs nothing.
 */
class Dolumar_Npc_Standing
{
	const TIER_HOSTILE = 'hostile';
	const TIER_WARY = 'wary';
	const TIER_NEUTRAL = 'neutral';
	const TIER_FRIENDLY = 'friendly';
	const TIER_ALLIED = 'allied';

	/**
	 * @param int $vid
	 * @param int $plid
	 * @param int $now
	 * @return array
	 */
	public static function newRow ($vid, $plid, $now)
	{
		return [
			'vid' => (int) $vid,
			'plid' => (int) $plid,
			'standing' => 0.0,
			'changed_at' => (int) $now,
			'attacks_window_start' => 0,
			'attacks_in_window' => 0,
			'last_attack_at' => 0,
			'last_attack_vid' => 0,
			'last_penalty' => 0.0,
			'scouts_at' => 0,
			'envoy_at' => 0,
			'envoy_price' => 0,
			'war_parties_sent' => 0,
			'npc_attacks_day' => 0,
			'npc_attacks_today' => 0,
			'last_npc_attack_at' => 0,
			'last_npc_battle_id' => 0,
			'truce_until' => 0,
			'request_delivered_at' => 0,
			'gift_window_start' => 0,
			'gift_best' => 0,
			'support_squad' => 0,
			'support_until' => 0,
			'pact' => 0,
		];
	}

	/**
	 * Cast a row that came from the database to the types newRow() uses.
	 * @param array $row
	 * @return array
	 */
	public static function fromDb (array $row)
	{
		$out = self::newRow ($row['vid'], $row['plid'], 0);
		foreach ($out as $key => $default) {
			if (!array_key_exists ($key, $row)) {
				continue;
			}
			$out[$key] = is_float ($default) ? (float) $row[$key] : (int) $row[$key];
		}
		return $out;
	}

	/**
	 * @param float|int $value
	 * @return float
	 */
	public static function clamp ($value)
	{
		return (float) max (Dolumar_Npc_Config::STANDING_MIN, min (Dolumar_Npc_Config::STANDING_MAX, $value));
	}

	/**
	 * The standing at $now, after decay toward 0 since changed_at.
	 * @param array $row
	 * @param int $now
	 * @param float $mulDecay the village's mul_decay
	 * @return float
	 */
	public static function valueAt (array $row, $now, $mulDecay = 1.0)
	{
		$standing = (float) $row['standing'];
		$elapsed = max (0, (int) $now - (int) $row['changed_at']);

		if ($standing < 0) {
			$standing = min (0.0, $standing + $elapsed * Dolumar_Npc_Config::DECAY_HOSTILE_PER_SECOND * $mulDecay);
		} elseif ($standing > 0) {
			$standing = max (0.0, $standing - $elapsed * Dolumar_Npc_Config::DECAY_FRIENDLY_PER_SECOND * $mulDecay);
		}

		return self::clamp ($standing);
	}

	/**
	 * Write the decayed value into the row so that a change can be applied on top of it.
	 * @param array $row
	 * @param int $now
	 * @param float $mulDecay
	 * @return array
	 */
	public static function settle (array $row, $now, $mulDecay = 1.0)
	{
		$row['standing'] = self::valueAt ($row, $now, $mulDecay);
		$row['changed_at'] = (int) $now;
		return $row;
	}

	/**
	 * @param array $row
	 * @param int $now
	 * @param float $mulThreshold the village's mul_threshold
	 * @param float $mulDecay the village's mul_decay
	 * @return string one of the TIER_* values
	 */
	public static function tier (array $row, $now, $mulThreshold = 1.0, $mulDecay = 1.0)
	{
		return self::tierOf (self::valueAt ($row, $now, $mulDecay), $mulThreshold);
	}

	/**
	 * @param float $value
	 * @param float $mulThreshold
	 * @return string
	 */
	public static function tierOf ($value, $mulThreshold = 1.0)
	{
		if ($value < Dolumar_Npc_Config::TIER_HOSTILE_BELOW * $mulThreshold) {
			return self::TIER_HOSTILE;
		}
		if ($value < Dolumar_Npc_Config::TIER_WARY_BELOW * $mulThreshold) {
			return self::TIER_WARY;
		}
		if ($value > Dolumar_Npc_Config::TIER_ALLIED_ABOVE * $mulThreshold) {
			return self::TIER_ALLIED;
		}
		if ($value > Dolumar_Npc_Config::TIER_FRIENDLY_ABOVE * $mulThreshold) {
			return self::TIER_FRIENDLY;
		}
		return self::TIER_NEUTRAL;
	}

	/**
	 * @param string $tier
	 * @return bool wary or hostile
	 */
	public static function isUnfriendly ($tier)
	{
		return $tier === self::TIER_WARY || $tier === self::TIER_HOSTILE;
	}

	/**
	 * The player attacked the NPC and the battle was fought.
	 * Penalty: (ATTACK_BASE + ATTACK_VICTORY * victory) * (1 + ATTACK_REPEAT * earlier attacks in the window).
	 * @param array $row
	 * @param int $now time of the battle
	 * @param float $victory 0..1
	 * @param float $mulDecay
	 * @return array
	 */
	public static function applyAttack (array $row, $now, $victory, $mulDecay = 1.0)
	{
		$row = self::settle ($row, $now, $mulDecay);

		if ($now - $row['attacks_window_start'] > Dolumar_Npc_Config::ATTACK_WINDOW) {
			$row['attacks_window_start'] = (int) $now;
			$row['attacks_in_window'] = 0;
		}

		$victory = max (0.0, min (1.0, (float) $victory));
		$penalty = (Dolumar_Npc_Config::ATTACK_BASE + Dolumar_Npc_Config::ATTACK_VICTORY * $victory)
			* (1 + Dolumar_Npc_Config::ATTACK_REPEAT * $row['attacks_in_window']);

		$row['standing'] = self::clamp ($row['standing'] - $penalty);
		$row['attacks_in_window'] ++;
		$row['last_attack_at'] = (int) $now;
		$row['last_penalty'] = (float) $penalty;
		$row['pact'] = 0;

		return $row;
	}

	/**
	 * Points for a gift of $value against a hoard of $hoard.
	 * @param int|float $value
	 * @param int|float $hoard
	 * @return float
	 */
	public static function giftPoints ($value, $hoard)
	{
		if ($value <= 0) {
			return 0.0;
		}
		return Dolumar_Npc_Config::GIFT_BASE + Dolumar_Npc_Config::GIFT_SCALED * min (1.0, $value / max (1, $hoard));
	}

	/**
	 * An unprompted gift. Only the largest gift in a rolling GIFT_WINDOW counts: a smaller one adds nothing,
	 * a larger one adds the difference.
	 * @param array $row
	 * @param int $now
	 * @param int|float $value resource sum of the transfer
	 * @param int|float $hoard resource sum of the NPC village
	 * @param float $mulDecay
	 * @return array
	 */
	public static function applyGift (array $row, $now, $value, $hoard, $mulDecay = 1.0)
	{
		$row = self::settle ($row, $now, $mulDecay);

		if ($now - $row['gift_window_start'] > Dolumar_Npc_Config::GIFT_WINDOW) {
			$row['gift_window_start'] = (int) $now;
			$row['gift_best'] = 0;
		}

		if ($value > $row['gift_best']) {
			$delta = self::giftPoints ($value, $hoard) - self::giftPoints ($row['gift_best'], $hoard);
			$row['standing'] = self::clamp ($row['standing'] + $delta);
			$row['gift_best'] = (int) $value;
		}

		return $row;
	}

	/**
	 * The player delivered the tribe's request.
	 * @param array $row
	 * @param int $now
	 * @param float $mulDecay
	 * @return array
	 */
	public static function applyRequestDelivered (array $row, $now, $mulDecay = 1.0)
	{
		$row = self::settle ($row, $now, $mulDecay);
		$row['standing'] = self::clamp ($row['standing'] + Dolumar_Npc_Config::REQUEST_DELIVERED);
		$row['request_delivered_at'] = (int) $now;
		return $row;
	}

	/**
	 * The grudge ends: standing at the wary floor, a truce, and the war drums reset.
	 * @param array $row
	 * @param int $now
	 * @param float $mulThreshold
	 * @return array
	 */
	public static function makePeace (array $row, $now, $mulThreshold = 1.0)
	{
		$row['standing'] = self::clamp (Dolumar_Npc_Config::TIER_HOSTILE_BELOW * $mulThreshold);
		$row['changed_at'] = (int) $now;
		$row['truce_until'] = (int) $now + Dolumar_Npc_Config::TRUCE_SECONDS;
		$row['scouts_at'] = 0;
		$row['envoy_at'] = 0;
		$row['envoy_price'] = 0;
		$row['war_parties_sent'] = 0;
		return $row;
	}

	/**
	 * @param array $row
	 * @param int $now
	 * @return bool
	 */
	public static function inTruce (array $row, $now)
	{
		return (int) $row['truce_until'] > (int) $now;
	}

	/**
	 * Multiplier on loot taken from the NPC by this player: 0 in a truce, 1.0 at standing >= 0,
	 * falling linearly to LOOT_MIN at STANDING_MIN.
	 * @param array $row
	 * @param int $now
	 * @param float $mulDecay
	 * @return float
	 */
	public static function lootFactor (array $row, $now, $mulDecay = 1.0)
	{
		if (self::inTruce ($row, $now)) {
			return 0.0;
		}

		$value = self::valueAt ($row, $now, $mulDecay);
		if ($value >= 0) {
			return 1.0;
		}

		$share = -$value / -Dolumar_Npc_Config::STANDING_MIN;
		return 1.0 - (1.0 - Dolumar_Npc_Config::LOOT_MIN) * $share;
	}
}
