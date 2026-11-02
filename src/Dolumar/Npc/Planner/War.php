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
 * The war drums and the friendships. Given the snapshot and the standing rows after the updater ran, decides
 * on scouts, envoys, war parties, support squads, recalls, allied strikes and thank-you squads, and enforces
 * the daily cap, the protection rules, the truce and the home-army rule.
 *
 * Pure: it never touches the database or NOW. It updates the rows it decides on (timestamps and counters);
 * read them back with rows() after plan().
 */
class Dolumar_Npc_Planner_War
{
	/** @var Dolumar_Npc_Snapshot */
	private $s;

	/** @var array plid => row */
	private $rows = [];

	/** @var Dolumar_Npc_Action[] */
	private $actions = [];

	/** @var int attacks planned in this run, so that the whole army is never promised twice */
	private $attacksPlanned = 0;

	/**
	 * @param Dolumar_Npc_Snapshot $s
	 * @param array $rows plid => standing row (after Dolumar_Npc_StandingUpdater::apply)
	 * @param array $transitions the updater's transitions
	 * @return Dolumar_Npc_Action[]
	 */
	public function plan (Dolumar_Npc_Snapshot $s, array $rows, array $transitions = [])
	{
		$this->s = $s;
		$this->rows = $rows;
		$this->actions = [];
		$this->attacksPlanned = 0;

		foreach ($transitions as $transition) {
			if ($transition['kind'] === 'request_delivered') {
				$this->planThankYou ((int) $transition['plid'], (int) $transition['vid']);
			}
		}

		foreach (array_keys ($this->rows) as $plid) {
			$this->planSupport ($plid);
		}

		if ($s->aggression === Dolumar_Npc_Config::AGGRESSION_OFF) {
			return $this->actions;
		}

		foreach (array_keys ($this->rows) as $plid) {
			$this->planLadder ($plid);
		}

		if ($s->aggression === Dolumar_Npc_Config::AGGRESSION_FULL) {
			foreach (array_keys ($this->rows) as $plid) {
				$this->planAlliedStrike ($plid);
			}
		}

		return $this->actions;
	}

	/**
	 * @return array plid => row, with the counters and timestamps of the planned actions applied
	 */
	public function rows ()
	{
		return $this->rows;
	}

	/**
	 * @param int $plid
	 * @return string
	 */
	private function tier ($plid)
	{
		return Dolumar_Npc_Standing::tier ($this->rows[$plid], $this->s->now, $this->s->mul ('mul_threshold'), $this->s->mul ('mul_decay'));
	}

	/**
	 * @param int $plid
	 * @return float
	 */
	private function value ($plid)
	{
		return Dolumar_Npc_Standing::valueAt ($this->rows[$plid], $this->s->now, $this->s->mul ('mul_decay'));
	}

	/**
	 * Scouts, envoy, war party.
	 * @param int $plid
	 */
	private function planLadder ($plid)
	{
		$s = $this->s;
		$row = $this->rows[$plid];
		$tier = $this->tier ($plid);

		if (!Dolumar_Npc_Standing::isUnfriendly ($tier) || Dolumar_Npc_Standing::inTruce ($row, $s->now)) {
			return;
		}

		$target = $this->resolveTarget ($plid, (int) $row['last_attack_vid']);

		if ((int) $row['scouts_at'] === 0) {
			if ($target === null || !$this->mayAttack ($plid, $target)) {
				return;
			}
			$this->actions[] = new Dolumar_Npc_Action (Dolumar_Npc_Action::SCOUT_RAID, [
				'plid' => $plid,
				'target' => (int) $target['vid'],
				'units' => Dolumar_Npc_Config::SCOUT_RAID_UNITS,
			]);
			$this->rows[$plid]['scouts_at'] = $s->now;
			$this->attacksPlanned ++;
			return;
		}

		if ((int) $row['envoy_at'] === 0) {
			if ($s->now - (int) $row['scouts_at'] < Dolumar_Npc_Config::ENVOY_DELAY) {
				return;
			}
			$price = self::envoyPrice ($this->value ($plid));
			$this->actions[] = new Dolumar_Npc_Action (Dolumar_Npc_Action::ENVOY, [
				'plid' => $plid,
				'target' => $target === null ? 0 : (int) $target['vid'],
				'price' => $price,
			]);
			$this->rows[$plid]['envoy_at'] = $s->now;
			$this->rows[$plid]['envoy_price'] = $price;
			return;
		}

		if ($tier !== Dolumar_Npc_Standing::TIER_HOSTILE) {
			return;
		}
		if ($s->now - (int) $row['envoy_at'] < Dolumar_Npc_Config::WAR_PARTY_DELAY) {
			return;
		}
		if ($s->now - (int) $row['last_npc_attack_at'] < Dolumar_Npc_Config::WAR_PARTY_DELAY) {
			return;
		}
		if ($target === null || !$this->mayAttack ($plid, $target)) {
			return;
		}

		$fraction = self::warPartyFraction ((float) $row['last_penalty'], (int) $row['war_parties_sent']);
		$this->actions[] = new Dolumar_Npc_Action (Dolumar_Npc_Action::WAR_PARTY, [
			'plid' => $plid,
			'target' => (int) $target['vid'],
			'fraction' => $fraction,
		]);
		$this->rows[$plid]['war_parties_sent'] = (int) $row['war_parties_sent'] + 1;
		$this->countAttack ($plid);
	}

	/**
	 * Friends get a support squad when one of their villages is under attack, and get it back after a day.
	 * @param int $plid
	 */
	private function planSupport ($plid)
	{
		$s = $this->s;
		$row = $this->rows[$plid];

		if ((int) $row['support_squad'] > 0) {
			if ($s->now >= (int) $row['support_until']) {
				$this->actions[] = new Dolumar_Npc_Action (Dolumar_Npc_Action::RECALL_SUPPORT, [
					'plid' => $plid,
					'squad' => (int) $row['support_squad'],
				]);
				$this->rows[$plid]['support_squad'] = 0;
				$this->rows[$plid]['support_until'] = 0;
			}
			return;
		}

		if ((int) $row['support_until'] > $s->now) {
			// A squad was promised this run or a previous one; wait for it to be sent or to expire.
			return;
		}

		$tier = $this->tier ($plid);
		if ($tier !== Dolumar_Npc_Standing::TIER_FRIENDLY && $tier !== Dolumar_Npc_Standing::TIER_ALLIED) {
			return;
		}
		if ($s->availableArmy () < 1) {
			return;
		}

		foreach ($s->incoming as $battle) {
			if ((int) $battle['fromId'] === (int) $s->vid || (int) $battle['fightDate'] <= $s->now) {
				continue;
			}
			$village = isset ($s->neighbours[(int) $battle['targetId']]) ? $s->neighbours[(int) $battle['targetId']] : null;
			if ($village === null || (int) $village['plid'] !== (int) $plid || empty ($village['reachable'])) {
				continue;
			}

			$this->actions[] = new Dolumar_Npc_Action (Dolumar_Npc_Action::SUPPORT, [
				'plid' => $plid,
				'target' => (int) $village['vid'],
				'fraction' => Dolumar_Npc_Config::SUPPORT_FRACTION,
			]);
			$this->rows[$plid]['support_until'] = $s->now + Dolumar_Npc_Config::SUPPORT_SECONDS;
			return;
		}
	}

	/**
	 * A delivered request earns a small squad of goblins at the delivering village for a day.
	 * @param int $plid
	 * @param int $vid
	 */
	private function planThankYou ($plid, $vid)
	{
		$s = $this->s;
		if (!isset ($this->rows[$plid])) {
			return;
		}
		$row = $this->rows[$plid];
		if ((int) $row['support_squad'] > 0 || (int) $row['support_until'] > $s->now) {
			return;
		}
		$village = isset ($s->neighbours[$vid]) ? $s->neighbours[$vid] : null;
		if ($village === null || empty ($village['reachable'])) {
			return;
		}
		if ($s->availableArmy () < 1) {
			return;
		}

		$this->actions[] = new Dolumar_Npc_Action (Dolumar_Npc_Action::THANK_YOU, [
			'plid' => $plid,
			'target' => $vid,
			'units' => Dolumar_Npc_Config::THANK_YOU_UNITS,
		]);
		$this->rows[$plid]['support_until'] = $s->now + Dolumar_Npc_Config::SUPPORT_SECONDS;
	}

	/**
	 * An ally's recent attacker gets a visit, under the attacker's own cap and protection.
	 * @param int $plid the ally
	 */
	private function planAlliedStrike ($plid)
	{
		$s = $this->s;
		if ($this->tier ($plid) !== Dolumar_Npc_Standing::TIER_ALLIED || (int) $this->rows[$plid]['pact'] !== 1) {
			return;
		}

		foreach ($s->thirdPartyAttacks as $attack) {
			if ((int) $attack['targetPlid'] !== (int) $plid) {
				continue;
			}
			$attacker = (int) $attack['fromPlid'];
			if ($attacker <= 0 || $attacker === (int) $plid) {
				continue;
			}
			if ($s->now - (int) $attack['date'] > Dolumar_Npc_Config::ALLIED_STRIKE_WINDOW) {
				continue;
			}
			if (!isset ($this->rows[$attacker])) {
				$this->rows[$attacker] = Dolumar_Npc_Standing::newRow ($s->vid, $attacker, $s->now);
			}
			$attackerRow = $this->rows[$attacker];
			if ((int) $attackerRow['last_npc_attack_at'] >= (int) $attack['date']) {
				// Already answered this attack.
				continue;
			}
			$target = $this->resolveTarget ($attacker, (int) $attack['fromId']);
			if ($target === null || !$this->mayAttack ($attacker, $target)) {
				continue;
			}

			$this->actions[] = new Dolumar_Npc_Action (Dolumar_Npc_Action::ALLIED_STRIKE, [
				'plid' => $plid,
				'attacker' => $attacker,
				'target' => (int) $target['vid'],
				'fraction' => Dolumar_Npc_Config::FIRST_WAR_PARTY_SCALE,
			]);
			$this->countAttack ($attacker);
			return;
		}
	}

	/**
	 * The attacking village if it is still a neighbour of that player, else their nearest reachable village.
	 * @param int $plid
	 * @param int $preferredVid
	 * @return array|null neighbour entry
	 */
	private function resolveTarget ($plid, $preferredVid)
	{
		$s = $this->s;
		if ($preferredVid > 0 && isset ($s->neighbours[$preferredVid])) {
			$village = $s->neighbours[$preferredVid];
			if ((int) $village['plid'] === (int) $plid && !empty ($village['reachable'])) {
				return $village;
			}
		}
		foreach ($s->villagesOf ($plid) as $village) {
			if (!empty ($village['reachable'])) {
				return $village;
			}
		}
		return null;
	}

	/**
	 * The safety rails: an army to send, the daily cap, the protection rules.
	 * @param int $plid
	 * @param array $village neighbour entry of the target
	 * @return bool
	 */
	private function mayAttack ($plid, array $village)
	{
		$s = $this->s;
		if ($s->availableArmy () < 1 || $this->attacksPlanned > 0) {
			return false;
		}
		if (!empty ($village['vacation'])) {
			return false;
		}
		if ($s->now - (int) $village['playerCreated'] < Dolumar_Npc_Config::PROTECTION_DAYS * 86400) {
			return false;
		}
		if ((int) $village['playerRunes'] < $s->protectionRunes) {
			return false;
		}

		$row = $this->rows[$plid];
		$today = (int) $row['npc_attacks_today'];
		if ($s->now - (int) $row['npc_attacks_day'] >= 86400) {
			$today = 0;
		}
		return $today < Dolumar_Npc_Config::ATTACKS_PER_DAY;
	}

	/**
	 * @param int $plid
	 */
	private function countAttack ($plid)
	{
		$row = $this->rows[$plid];
		if ($this->s->now - (int) $row['npc_attacks_day'] >= 86400) {
			$row['npc_attacks_day'] = $this->s->now;
			$row['npc_attacks_today'] = 0;
		}
		$row['npc_attacks_today'] = (int) $row['npc_attacks_today'] + 1;
		$row['last_npc_attack_at'] = $this->s->now;
		$this->rows[$plid] = $row;
		$this->attacksPlanned ++;
	}

	/**
	 * The share of the home army the n-th war party takes: the first from the player's last penalty,
	 * each later one scaled up, never above MAX_ARMY_FRACTION.
	 * @param float $lastPenalty
	 * @param int $sent war parties sent so far in this grudge
	 * @return float
	 */
	public static function warPartyFraction ($lastPenalty, $sent)
	{
		$first = $lastPenalty / (Dolumar_Npc_Config::ATTACK_BASE + Dolumar_Npc_Config::ATTACK_VICTORY) * Dolumar_Npc_Config::FIRST_WAR_PARTY_SCALE;
		$first = max (0.1, min (Dolumar_Npc_Config::MAX_ARMY_FRACTION, $first));
		$fraction = $first * pow (Dolumar_Npc_Config::WAR_PARTY_ESCALATION, max (0, (int) $sent));
		return round (min (Dolumar_Npc_Config::MAX_ARMY_FRACTION, $fraction), 4);
	}

	/**
	 * @param float $value the standing (negative)
	 * @return int resource sum the envoy asks for, in steps of 50
	 */
	public static function envoyPrice ($value)
	{
		$price = -$value * Dolumar_Npc_Config::ENVOY_PRICE_PER_POINT;
		$price = max (Dolumar_Npc_Config::ENVOY_PRICE_MIN, $price);
		return (int) (round ($price / 50) * 50);
	}
}
