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
 * Applies the snapshot's new battle reports and received transfers to the standing rows. Pure: takes a
 * snapshot, returns the rows, the transitions the executor should announce, and the ids processed so far.
 *
 * Transition shapes:
 *   ['kind' => 'tier', 'plid' => int, 'from' => string, 'to' => string]
 *   ['kind' => 'peace', 'plid' => int, 'by' => 'payment'|'defence']
 *   ['kind' => 'pact', 'plid' => int]
 *   ['kind' => 'pact_broken', 'plid' => int]
 *   ['kind' => 'request_delivered', 'plid' => int, 'vid' => int delivering village]
 *   ['kind' => 'attacked', 'plid' => int, 'vid' => int attacking village]
 */
class Dolumar_Npc_StandingUpdater
{
	/**
	 * @param Dolumar_Npc_Snapshot $s
	 * @return array{rows: array, transitions: array, last_report_id: int, last_transfer_id: int,
	 *               request_delivered: bool}
	 */
	public static function apply (Dolumar_Npc_Snapshot $s)
	{
		$rows = [];
		foreach ($s->standings as $plid => $row) {
			$rows[(int) $plid] = $row;
		}

		$mulThreshold = $s->mul ('mul_threshold');
		$mulDecay = $s->mul ('mul_decay');

		$before = [];
		foreach ($rows as $plid => $row) {
			$before[$plid] = Dolumar_Npc_Standing::tier ($row, $s->now, $mulThreshold, $mulDecay);
		}

		$transitions = [];
		$lastReportId = (int) ($s->tribe['last_report_id'] ?? 0);
		$lastTransferId = (int) ($s->tribe['last_transfer_id'] ?? 0);
		$requestDelivered = false;

		$events = self::events ($s, $lastReportId, $lastTransferId);

		foreach ($events as $event) {
			if ($event['type'] === 'report') {
				$report = $event['data'];
				$lastReportId = max ($lastReportId, (int) $report['reportId']);

				if ((int) $report['targetId'] === (int) $s->vid) {
					$plid = (int) $report['fromPlid'];
					if ($plid <= 0) {
						continue;
					}
					$row = self::row ($rows, $s, $plid, $before);
					$hadPact = (int) $row['pact'] === 1;
					$row = Dolumar_Npc_Standing::applyAttack ($row, (int) $report['date'], (float) $report['victory'], $mulDecay);
					$row['last_attack_vid'] = (int) $report['fromId'];
					$rows[$plid] = $row;
					$transitions[] = ['kind' => 'attacked', 'plid' => $plid, 'vid' => (int) $report['fromId']];
					if ($hadPact) {
						$transitions[] = ['kind' => 'pact_broken', 'plid' => $plid];
					}
				} elseif ((int) $report['fromId'] === (int) $s->vid) {
					// The NPC attacked. A war party beaten at the player's home ends the grudge.
					$plid = (int) $report['targetPlid'];
					if ($plid <= 0 || !isset ($rows[$plid])) {
						continue;
					}
					$row = $rows[$plid];
					if ((int) $report['battleId'] === (int) $row['last_npc_battle_id']
						&& (float) $report['victory'] < Dolumar_Npc_Config::DEFENCE_VICTORY_BELOW
						&& Dolumar_Npc_Standing::valueAt ($row, (int) $report['date'], $mulDecay) < 0
						&& !Dolumar_Npc_Standing::inTruce ($row, (int) $report['date'])
					) {
						$rows[$plid] = Dolumar_Npc_Standing::makePeace ($row, (int) $report['date'], $mulThreshold);
						$transitions[] = ['kind' => 'peace', 'plid' => $plid, 'by' => 'defence'];
					}
				}
			} else {
				$transfer = $event['data'];
				$lastTransferId = max ($lastTransferId, (int) $transfer['id']);

				$plid = (int) $transfer['plid'];
				if ($plid <= 0 || $transfer['value'] <= 0) {
					continue;
				}
				$date = (int) $transfer['date'];
				$row = self::row ($rows, $s, $plid, $before);

				// 1. The envoy's price, paid while the envoy is out.
				if ((int) $row['envoy_at'] > 0 && (int) $row['envoy_price'] > 0
					&& $transfer['value'] >= (int) $row['envoy_price']
					&& Dolumar_Npc_Standing::valueAt ($row, $date, $mulDecay) < 0
				) {
					$rows[$plid] = Dolumar_Npc_Standing::makePeace ($row, $date, $mulThreshold);
					$transitions[] = ['kind' => 'peace', 'plid' => $plid, 'by' => 'payment'];
					continue;
				}

				// 2. The tribe's current request.
				$res = (string) ($s->tribe['request_res'] ?? '');
				$amount = (int) ($s->tribe['request_amount'] ?? 0);
				if ($res !== '' && $amount > 0 && !$requestDelivered
					&& isset ($transfer['items'][$res]) && $transfer['items'][$res] >= $amount
					&& $date - (int) $row['request_delivered_at'] >= Dolumar_Npc_Config::REQUEST_COOLDOWN
				) {
					$rows[$plid] = Dolumar_Npc_Standing::applyRequestDelivered ($row, $date, $mulDecay);
					$transitions[] = ['kind' => 'request_delivered', 'plid' => $plid, 'vid' => (int) $transfer['fromVid']];
					$requestDelivered = true;
					continue;
				}

				// 3. A gift.
				$rows[$plid] = Dolumar_Npc_Standing::applyGift ($row, $date, $transfer['value'], $s->resourceSum (), $mulDecay);
			}
		}

		// Tier changes and pacts.
		foreach ($rows as $plid => $row) {
			$from = isset ($before[$plid]) ? $before[$plid] : Dolumar_Npc_Standing::TIER_NEUTRAL;
			$to = Dolumar_Npc_Standing::tier ($row, $s->now, $mulThreshold, $mulDecay);

			if ($to === Dolumar_Npc_Standing::TIER_ALLIED && (int) $row['pact'] === 0) {
				$rows[$plid]['pact'] = 1;
				$transitions[] = ['kind' => 'pact', 'plid' => $plid];
			}

			if ($from !== $to) {
				$transitions[] = ['kind' => 'tier', 'plid' => $plid, 'from' => $from, 'to' => $to];
			}
		}

		return [
			'rows' => $rows,
			'transitions' => $transitions,
			'last_report_id' => $lastReportId,
			'last_transfer_id' => $lastTransferId,
			'request_delivered' => $requestDelivered,
		];
	}

	/**
	 * Reports and transfers above the processed ids, merged by date.
	 * @param Dolumar_Npc_Snapshot $s
	 * @param int $lastReportId
	 * @param int $lastTransferId
	 * @return array
	 */
	private static function events (Dolumar_Npc_Snapshot $s, $lastReportId, $lastTransferId)
	{
		$events = [];
		foreach ($s->reports as $report) {
			if ((int) $report['reportId'] > $lastReportId) {
				$events[] = ['type' => 'report', 'date' => (int) $report['date'], 'data' => $report];
			}
		}
		foreach ($s->transfers as $transfer) {
			if ((int) $transfer['id'] > $lastTransferId) {
				$events[] = ['type' => 'transfer', 'date' => (int) $transfer['date'], 'data' => $transfer];
			}
		}
		usort ($events, function ($a, $b) {
			return $a['date'] <=> $b['date'];
		});
		return $events;
	}

	/**
	 * @param array $rows
	 * @param Dolumar_Npc_Snapshot $s
	 * @param int $plid
	 * @param array $before
	 * @return array
	 */
	private static function row (array &$rows, Dolumar_Npc_Snapshot $s, $plid, array &$before)
	{
		if (!isset ($rows[$plid])) {
			$rows[$plid] = Dolumar_Npc_Standing::newRow ($s->vid, $plid, $s->now);
			$before[$plid] = Dolumar_Npc_Standing::TIER_NEUTRAL;
		}
		return $rows[$plid];
	}
}
