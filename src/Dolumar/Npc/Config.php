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
 * Every tunable number of the NPC behaviour, in one place.
 * See .ai/superpowers/specs/2026-11-02-npc-standing-ai-design.md for what each one means.
 */
class Dolumar_Npc_Config
{
	const AGGRESSION_OFF = 'off';
	const AGGRESSION_COUNTER = 'counter';
	const AGGRESSION_FULL = 'full';

	/* Standing range and base tier thresholds (multiplied per village by mul_threshold). */
	const STANDING_MIN = -100.0;
	const STANDING_MAX = 100.0;
	const TIER_HOSTILE_BELOW = -40.0;
	const TIER_WARY_BELOW = -10.0;
	const TIER_FRIENDLY_ABOVE = 30.0;
	const TIER_ALLIED_ABOVE = 70.0;

	/* Decay toward 0, in points per second (multiplied per village by mul_decay). */
	const DECAY_HOSTILE_PER_SECOND = 1.0 / 3600;
	const DECAY_FRIENDLY_PER_SECOND = 1.0 / (4 * 3600);

	/* Attack penalty: -(ATTACK_BASE + ATTACK_VICTORY * victory) * (1 + ATTACK_REPEAT * n). */
	const ATTACK_BASE = 15.0;
	const ATTACK_VICTORY = 25.0;
	const ATTACK_REPEAT = 0.5;
	const ATTACK_WINDOW = 86400;

	/* Gifts and requests. */
	const GIFT_BASE = 5.0;
	const GIFT_SCALED = 10.0;
	const GIFT_WINDOW = 7 * 86400;
	const REQUEST_DELIVERED = 30.0;
	const REQUEST_COOLDOWN = 2 * 86400;
	const REQUEST_MIN = 300;
	const REQUEST_MAX = 5000;
	const REQUEST_SHARE = 0.25;

	/* Peace and truce. */
	const TRUCE_SECONDS = 3 * 86400;

	/* Loot factor on NPC defenders: 1.0 at standing 0, LOOT_MIN at STANDING_MIN. */
	const LOOT_MIN = 0.25;

	/* Counter-attacks. */
	const ATTACKS_PER_DAY = 2;
	const PROTECTION_DAYS = 7;
	const PROTECTION_RUNES_DEFAULT = 10;
	const MAX_ARMY_FRACTION = 0.6;
	const FIRST_WAR_PARTY_SCALE = 0.3;
	const WAR_PARTY_ESCALATION = 1.5;
	const SCOUT_RAID_UNITS = 2;
	const ENVOY_DELAY = 3600;
	const ENVOY_PRICE_SHARE = 0.5;
	const ENVOY_PRICE_MIN = 500;
	const WAR_PARTY_DELAY = 3 * 3600;
	const DEFENCE_VICTORY_BELOW = 0.5;

	/* Friendship. */
	const SUPPORT_FRACTION = 0.5;
	const SUPPORT_SECONDS = 86400;
	const THANK_YOU_UNITS = 5;
	const ALLIED_STRIKE_WINDOW = 86400;

	/* Economy. */
	const RESERVE_PER_RUNE = 400;
	const MAX_CONSTRUCTING = 2;
	const MAX_AVAILABLE_RUNES = 20;
	const SCOUT_RUNES_AMOUNT = 4;
	const MAX_STRENGTH_FACTOR = 1.2;
	const NEIGHBOUR_RADIUS = 100;
	const NPC_PLAYER_DISTANCE = 50;

	/* Hoard words, as a share of capacity. */
	const HOARD_LEAN_BELOW = 0.25;
	const HOARD_OVERFLOWING_ABOVE = 0.75;

	/**
	 * @return string one of the AGGRESSION_* values, read from NPC_AGGRESSION.
	 */
	public static function aggression ()
	{
		$value = getenv ('NPC_AGGRESSION');
		if ($value === false) {
			return self::AGGRESSION_COUNTER;
		}

		$value = strtolower (trim ($value));
		if (in_array ($value, [self::AGGRESSION_OFF, self::AGGRESSION_COUNTER, self::AGGRESSION_FULL], true)) {
			return $value;
		}

		return self::AGGRESSION_COUNTER;
	}

	/**
	 * @return int players below this many total runes are never attacked by an NPC.
	 */
	public static function protectionRunes ()
	{
		$value = getenv ('NPC_PROTECTION_RUNES');
		if ($value === false || !is_numeric ($value)) {
			return self::PROTECTION_RUNES_DEFAULT;
		}
		return max (0, (int) $value);
	}
}
