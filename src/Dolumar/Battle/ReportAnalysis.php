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
 * Reads the raw strings of a battle report (battleLog, fightLog, slots, squads) into structured data:
 * events with phases and melee rounds, per-unit totals and fates, key moments, chronicle facts and replay frames.
 *
 * Pure: no database or text access, so it can be unit tested. Names, images and texts are added by
 * Dolumar_Battle_ReportView. A unit is identified by "unit_village_race_squad" (see unitKey()).
 */
class Dolumar_Battle_ReportAnalysis
{
	const TEAM_ATTACKER = 1;
	const TEAM_DEFENDER = 2;

	// battleLog action ids, see Dolumar_Battle_Logger.
	const LOG_STATUS = 0;
	const LOG_MOVE = 1;
	const LOG_STUNNED = 2;
	const LOG_MAGIC = 5;
	const LOG_MAGIC_FAILED = 6;
	const LOG_MAGE_DIED = 7;
	const LOG_WIPED = 8;
	const LOG_FLED = 9;
	const LOG_DAMAGE = 10;
	const LOG_MELEE = 11;
	const LOG_SHOOTING = 12;

	// Phases, as logged by Dolumar_Battle_Fight (STATUS_*).
	const PHASE_PREFIGHT = 'prefight';
	const PHASE_SHOOTING = 'shooting';
	const PHASE_MELEE = 'melee';
	const PHASE_FINISHED = 'finished';

	private static $phaseNames = array (
		0 => self::PHASE_PREFIGHT,
		1 => self::PHASE_SHOOTING,
		2 => self::PHASE_MELEE,
		3 => self::PHASE_FINISHED
	);

	private $events = array ();
	private $units = array ();
	private $slots = array ();
	private $totals = array (self::TEAM_ATTACKER => array (), self::TEAM_DEFENDER => array ());
	private $complete = true;
	private $rounds = 0;
	private $emptySlots = array (self::TEAM_ATTACKER => 0, self::TEAM_DEFENDER => 0);

	/**
	 * @param string $battleLog the battle_report.battleLog column
	 * @param string $fightLog the battle_report.fightLog column (start and death counts per unit type)
	 * @param string|null $slots the battle_report.slots column (JSON, initial placement)
	 * @param int|null $cutoff only read events up to this many seconds into the fight (live reports), null for all
	 */
	public function __construct ($battleLog, $fightLog, $slots = null, $cutoff = null)
	{
		$this->readSlots ($slots);
		$this->readTotals ($fightLog);
		$this->readEvents ($battleLog, $cutoff);
	}

	/*
		Unit identity
	*/
	public static function parseUnitData ($json)
	{
		if (!is_string ($json) || $json === '')
		{
			return null;
		}

		$data = json_decode ($json, true);
		if (!is_array ($data) || count ($data) < 3)
		{
			return null;
		}

		$data = array_values ($data);

		return array
		(
			'unit' => intval ($data[0]),
			'village' => intval ($data[1]),
			'slot' => intval ($data[2]),
			'squad' => isset ($data[4]) ? intval ($data[4]) : 0,
			'amount' => isset ($data[5]) ? intval ($data[5]) : 0,
			'race' => isset ($data[6]) ? intval ($data[6]) : 0,
			'frontage' => isset ($data[7]) ? $data[7] : 0,
			'json' => $json
		);
	}

	public static function unitKey ($data)
	{
		return $data['unit'] . '_' . $data['village'] . '_' . $data['race'] . '_' . $data['squad'];
	}

	public static function typeKey ($data)
	{
		return $data['unit'] . '_' . $data['village'] . '_' . $data['race'];
	}

	/*
		Reading
	*/
	private function readSlots ($slots)
	{
		$slots = is_string ($slots) ? json_decode ($slots, true) : null;
		if (!is_array ($slots) || !isset ($slots['attacking']) || !isset ($slots['defending']))
		{
			return;
		}

		foreach (array (self::TEAM_ATTACKER => 'attacking', self::TEAM_DEFENDER => 'defending') as $team => $name)
		{
			foreach ($slots[$name] as $v)
			{
				$slot = isset ($v[0]) ? $v[0] : null;
				$type = is_array ($slot) ? intval ($slot[0]) : intval ($slot);
				$id = is_array ($slot) ? intval ($slot[1]) : intval ($slot);

				if ($team === self::TEAM_DEFENDER)
				{
					$this->slots[] = array ('type' => $type, 'id' => $id);
				}

				$unit = isset ($v[1]) ? self::parseUnitData ($v[1]) : null;
				if ($unit)
				{
					$unit = $this->registerUnit ($unit, $team);
					$this->units[$unit]['initialSlot'] = $id;
				}
				else
				{
					$this->emptySlots[$team] ++;
				}
			}
		}
	}

	private function readTotals ($fightLog)
	{
		if (!is_string ($fightLog) || $fightLog === '')
		{
			return;
		}

		$sides = explode ('&', $fightLog);
		foreach (array (self::TEAM_ATTACKER => 0, self::TEAM_DEFENDER => 1) as $team => $i)
		{
			if (!isset ($sides[$i]) || $sides[$i] === '')
			{
				continue;
			}

			foreach (explode (';', $sides[$i]) as $row)
			{
				$l = explode (':', $row);
				if (count ($l) < 5)
				{
					continue;
				}

				$key = intval ($l[0]) . '_' . intval ($l[1]) . '_' . intval ($l[2]);
				if (!isset ($this->totals[$team][$key]))
				{
					$this->totals[$team][$key] = array
					(
						'key' => $key,
						'unit' => intval ($l[0]),
						'village' => intval ($l[1]),
						'race' => intval ($l[2]),
						'amount' => 0,
						'died' => 0
					);
				}

				// fightLog is written after the kills: the amount is what survived.
				$this->totals[$team][$key]['amount'] += intval ($l[3]) + intval ($l[4]);
				$this->totals[$team][$key]['died'] += intval ($l[4]);
			}
		}
	}

	private function registerUnit ($data, $team)
	{
		$key = self::unitKey ($data);
		if (!isset ($this->units[$key]))
		{
			$this->units[$key] = array
			(
				'key' => $key,
				'typeKey' => self::typeKey ($data),
				'team' => $team,
				'unit' => $data['unit'],
				'village' => $data['village'],
				'race' => $data['race'],
				'squad' => $data['squad'],
				'json' => $data['json'],
				'start' => $data['amount'],
				'initialSlot' => null,
				'kills' => 0,
				'lost' => 0,
				'fate' => 'held',
				'fateEvent' => null
			);
		}
		elseif ($data['amount'] > $this->units[$key]['start'])
		{
			// Amounts only go down during a fight; the initial placement can log 0 for defenders.
			$this->units[$key]['start'] = $data['amount'];
		}

		return $key;
	}

	private function readEvents ($battleLog, $cutoff)
	{
		if (!is_string ($battleLog) || $battleLog === '')
		{
			return;
		}

		$rows = explode (';', $battleLog);
		array_pop ($rows);

		$phase = self::PHASE_PREFIGHT;
		$round = 0;
		$roundOpen = false;

		foreach ($rows as $row)
		{
			$facts = explode ('|', $row);
			if (count ($facts) <= 3)
			{
				continue;
			}

			$time = intval (array_shift ($facts));
			if ($cutoff !== null && $time > $cutoff)
			{
				$this->complete = false;
				break;
			}

			$action = intval (array_shift ($facts));
			$random = intval (array_shift ($facts));
			$team = intval (array_shift ($facts));

			$event = array
			(
				'index' => count ($this->events),
				'time' => $time,
				'action' => $action,
				'random' => $random,
				'team' => $team,
				'type' => null,
				'phase' => $phase,
				'round' => 0,
				'actor' => null,
				'target' => null,
				'kills' => 0,
				'from' => null,
				'to' => null,
				'facts' => $facts
			);

			$otherTeam = $team === self::TEAM_ATTACKER ? self::TEAM_DEFENDER : self::TEAM_ATTACKER;

			switch ($action)
			{
				case self::LOG_STATUS:
					$status = isset ($facts[0]) ? intval ($facts[0]) : 0;
					$phase = isset (self::$phaseNames[$status]) ? self::$phaseNames[$status] : $phase;
					$event['type'] = 'phase';
					$event['phase'] = $phase;
					$roundOpen = false;
				break;

				case self::LOG_MOVE:
					$event['type'] = 'move';
					$event['actor'] = $this->eventUnit ($facts, 0, $team);
					$event['from'] = isset ($facts[1]) ? intval ($facts[1]) : null;
					$event['to'] = isset ($facts[2]) ? intval ($facts[2]) : null;
				break;

				case self::LOG_STUNNED:
					$event['type'] = 'stunned';
					$event['actor'] = $this->eventUnit ($facts, 0, $team);
				break;

				case self::LOG_MAGIC:
				case self::LOG_MAGIC_FAILED:
					$event['type'] = $action === self::LOG_MAGIC ? 'magic' : 'magicfailed';
					$event['special'] = isset ($facts[0]) ? $facts[0] : null;
					$event['effect'] = isset ($facts[1]) ? intval ($facts[1]) : null;
					$event['probability'] = isset ($facts[2]) ? $facts[2] : null;
				break;

				case self::LOG_MAGE_DIED:
					$event['type'] = 'magedied';
					$event['special'] = isset ($facts[0]) ? $facts[0] : null;
				break;

				case self::LOG_WIPED:
				case self::LOG_FLED:
					$event['type'] = $action === self::LOG_WIPED ? 'wiped' : 'fled';
					$event['actor'] = $this->eventUnit ($facts, 0, $team);
					if ($event['actor'] !== null && $this->units[$event['actor']]['fateEvent'] === null)
					{
						$this->units[$event['actor']]['fate'] = $event['type'];
						$this->units[$event['actor']]['fateEvent'] = $event['index'];
					}
				break;

				case self::LOG_DAMAGE:
				case self::LOG_MELEE:
				case self::LOG_SHOOTING:
					$event['type'] = $action === self::LOG_SHOOTING ? 'shooting' : 'melee';
					$event['actor'] = $this->eventUnit ($facts, 0, $team);
					$event['target'] = $this->eventUnit ($facts, 1, $otherTeam);
					$event['kills'] = isset ($facts[2]) ? max (0, intval (round (floatval ($facts[2])))) : 0;

					if ($event['actor'] !== null)
					{
						$this->units[$event['actor']]['kills'] += $event['kills'];
					}
					if ($event['target'] !== null)
					{
						$this->units[$event['target']]['lost'] += $event['kills'];
					}
				break;

				default:
					$event['type'] = 'unknown';
				break;
			}

			// Melee rounds: Fight runs morale check, moves, attacker melee, defender melee, repeat.
			if ($phase === self::PHASE_MELEE && $event['type'] !== 'phase')
			{
				$isAction = in_array ($event['type'], array ('move', 'melee', 'stunned'));
				$closesRound = $team === self::TEAM_DEFENDER && in_array ($event['type'], array ('melee', 'stunned'))
					|| in_array ($event['type'], array ('fled', 'wiped'));

				$startsRound = !$roundOpen
					|| ($this->lastClosedRound && ($team === self::TEAM_ATTACKER || $event['type'] === 'move'));

				if ($isAction && $startsRound)
				{
					$round ++;
					$roundOpen = true;
				}

				$this->lastClosedRound = $closesRound;
				$event['round'] = max (1, $round);
			}
			else
			{
				$this->lastClosedRound = false;
			}

			$this->events[] = $event;
		}

		$this->rounds = $round;
	}

	private $lastClosedRound = false;

	private function eventUnit ($facts, $index, $team)
	{
		if (!isset ($facts[$index]))
		{
			return null;
		}

		$data = self::parseUnitData ($facts[$index]);
		if (!$data)
		{
			return null;
		}

		return $this->registerUnit ($data, $team);
	}

	/*
		Getters
	*/
	public function getEvents ()
	{
		return $this->events;
	}

	public function getUnits ()
	{
		return $this->units;
	}

	public function getUnit ($key)
	{
		return isset ($this->units[$key]) ? $this->units[$key] : null;
	}

	public function getSlots ()
	{
		return $this->slots;
	}

	/**
	 * How many slots a side left empty at the start of the fight.
	 */
	public function countEmptySlots ($team)
	{
		return $this->emptySlots[$team];
	}

	public function isComplete ()
	{
		return $this->complete;
	}

	public function countRounds ()
	{
		return $this->rounds;
	}

	public function getLastEvent ()
	{
		return count ($this->events) > 0 ? $this->events[count ($this->events) - 1] : null;
	}

	/**
	 * The armies, one row per unit type and village (as in fightLog), with the regiments that made it up,
	 * survivors and fate. Falls back to the units seen in the log when fightLog is empty (live reports).
	 */
	public function getArmy ($team)
	{
		$rows = array ();

		foreach ($this->totals[$team] as $key => $total)
		{
			$rows[$key] = $total + array ('squads' => array (), 'kills' => 0, 'fate' => 'held');
		}

		$hasTotals = count ($this->totals[$team]) > 0;

		foreach ($this->units as $unit)
		{
			if ($unit['team'] !== $team)
			{
				continue;
			}

			$key = $unit['typeKey'];
			if (!isset ($rows[$key]))
			{
				if ($hasTotals)
				{
					// Not in the final tally (for example a unit that was not on the field).
					continue;
				}

				$rows[$key] = array
				(
					'key' => $key,
					'unit' => $unit['unit'],
					'village' => $unit['village'],
					'race' => $unit['race'],
					'amount' => 0,
					'died' => 0,
					'squads' => array (),
					'kills' => 0,
					'fate' => 'held'
				);
			}

			// Live reports have no tally yet: count from the log.
			if (!$hasTotals)
			{
				$rows[$key]['amount'] += $unit['start'];
				$rows[$key]['died'] += min ($unit['start'], $unit['lost']);
			}

			$rows[$key]['squads'][] = $unit['squad'];
			$rows[$key]['kills'] += $unit['kills'];
			if (!isset ($rows[$key]['json']))
			{
				$rows[$key]['json'] = $unit['json'];
			}

			if ($unit['fate'] === 'fled')
			{
				$rows[$key]['fate'] = 'fled';
			}
		}

		foreach ($rows as $key => $row)
		{
			$rows[$key]['died'] = min ($row['amount'], $row['died']);
			$rows[$key]['left'] = $row['amount'] - $rows[$key]['died'];
			if ($row['amount'] > 0 && $rows[$key]['left'] <= 0)
			{
				$rows[$key]['fate'] = 'wiped';
			}
			$rows[$key]['squads'] = array_values (array_unique ($row['squads']));
		}

		return array_values ($rows);
	}

	public function getArmyTotals ($team)
	{
		$amount = 0;
		$died = 0;
		foreach ($this->getArmy ($team) as $row)
		{
			$amount += $row['amount'];
			$died += $row['died'];
		}
		return array ('amount' => $amount, 'died' => $died, 'left' => $amount - $died);
	}

	/**
	 * Key moments, in fight order, at most four: first spell, biggest volley, the turning point (first flee or
	 * wipe on the losing side) and the reader's heaviest single loss.
	 *
	 * @param int $loserTeam the team that lost the battle
	 * @param int $readerTeam the team of the player reading the report
	 */
	public function getMoments ($loserTeam, $readerTeam)
	{
		$picked = array ();

		foreach ($this->events as $e)
		{
			if ($e['type'] === 'magic' || $e['type'] === 'magicfailed')
			{
				$picked['magic'] = $e;
				break;
			}
		}

		$best = null;
		foreach ($this->events as $e)
		{
			if ($e['type'] === 'shooting' && $e['kills'] > 0 && ($best === null || $e['kills'] > $best['kills']))
			{
				$best = $e;
			}
		}
		if ($best)
		{
			$picked['volley'] = $best;
		}

		foreach ($this->events as $e)
		{
			if (($e['type'] === 'fled' || $e['type'] === 'wiped') && $e['team'] === $loserTeam)
			{
				$picked['turningpoint'] = $e;
				break;
			}
		}

		$worst = null;
		foreach ($this->events as $e)
		{
			if (($e['type'] === 'melee' || $e['type'] === 'shooting') && $e['team'] !== $readerTeam && $e['kills'] > 0
				&& ($worst === null || $e['kills'] > $worst['kills']))
			{
				$worst = $e;
			}
		}
		if ($worst)
		{
			$picked['heaviestloss'] = $worst;
		}

		// Drop duplicates, sort in fight order.
		$out = array ();
		$seen = array ();
		foreach ($picked as $kind => $e)
		{
			if (isset ($seen[$e['index']]))
			{
				continue;
			}
			$seen[$e['index']] = true;
			$out[] = array ('kind' => $kind, 'event' => $e);
		}

		usort ($out, function ($a, $b) {
			return $a['event']['index'] - $b['event']['index'];
		});

		return array_slice ($out, 0, 4);
	}

	/**
	 * The unit (regiment) with the most kills on the given team, or null when it killed nobody.
	 */
	public function getHero ($team)
	{
		$hero = null;
		foreach ($this->units as $unit)
		{
			if ($unit['team'] === $team && $unit['kills'] > 0 && ($hero === null || $unit['kills'] > $hero['kills']))
			{
				$hero = $unit;
			}
		}
		return $hero;
	}

	/**
	 * Events grouped for display: one group per phase, melee split per round.
	 * Each group: phase, round, time (seconds into the fight), events (without the phase markers).
	 */
	public function getGroups ()
	{
		$groups = array ();
		$current = null;

		foreach ($this->events as $e)
		{
			if ($e['type'] === 'phase')
			{
				continue;
			}

			$id = $e['phase'] . ':' . $e['round'];
			if ($current === null || $current['id'] !== $id)
			{
				if ($current !== null)
				{
					$groups[] = $current;
				}
				$current = array ('id' => $id, 'phase' => $e['phase'], 'round' => $e['round'], 'time' => $e['time'], 'events' => array ());
			}
			$current['events'][] = $e;
		}

		if ($current !== null)
		{
			$groups[] = $current;
		}

		return $groups;
	}

	/**
	 * Aggregated facts for the chronicle, per phase.
	 */
	public function getChronicle ()
	{
		$out = array
		(
			'magic' => array (),
			'shooting' => array (self::TEAM_ATTACKER => 0, self::TEAM_DEFENDER => 0),
			'shootingBest' => null,
			'melee' => array (self::TEAM_ATTACKER => 0, self::TEAM_DEFENDER => 0),
			'meleeBest' => array (self::TEAM_ATTACKER => null, self::TEAM_DEFENDER => null),
			'broke' => array (),
			'rounds' => $this->rounds
		);

		$meleeKills = array ();

		foreach ($this->events as $e)
		{
			switch ($e['type'])
			{
				case 'magic':
				case 'magicfailed':
					$out['magic'][] = $e;
				break;

				case 'shooting':
					$out['shooting'][$e['team']] += $e['kills'];
					if ($e['kills'] > 0 && ($out['shootingBest'] === null || $e['kills'] > $out['shootingBest']['kills']))
					{
						$out['shootingBest'] = $e;
					}
				break;

				case 'melee':
					$out['melee'][$e['team']] += $e['kills'];
					if ($e['actor'] !== null)
					{
						if (!isset ($meleeKills[$e['actor']]))
						{
							$meleeKills[$e['actor']] = 0;
						}
						$meleeKills[$e['actor']] += $e['kills'];
					}
				break;

				case 'fled':
				case 'wiped':
					$out['broke'][] = $e;
				break;
			}
		}

		foreach ($meleeKills as $key => $kills)
		{
			$team = $this->units[$key]['team'];
			if ($kills > 0 && ($out['meleeBest'][$team] === null || $kills > $out['meleeBest'][$team]['kills']))
			{
				$out['meleeBest'][$team] = array ('unit' => $key, 'kills' => $kills);
			}
		}

		return $out;
	}

	/**
	 * Replay data: the tokens on the field (one per unit that started in a slot) and one frame per event.
	 * A frame holds, per token, [slot id, alive, status]; status is '' (idle), 'acting', 'hit', 'stunned',
	 * 'fled', 'wiped' or 'gone' (fled or wiped in an earlier round, no longer drawn).
	 */
	public function getReplay ()
	{
		$tokens = array ();
		$index = array ();

		foreach ($this->units as $key => $unit)
		{
			if ($unit['initialSlot'] === null)
			{
				continue;
			}

			$index[$key] = count ($tokens);
			$tokens[] = array ('key' => $key, 'team' => $unit['team']);
		}

		$state = array ();
		foreach ($tokens as $i => $t)
		{
			$unit = $this->units[$t['key']];
			$state[$i] = array ($unit['initialSlot'], $unit['start'], '');
		}

		$frames = array ();
		$lastRound = 0;

		foreach ($this->events as $e)
		{
			// Clear the per-event highlights.
			foreach ($state as $i => $s)
			{
				if (in_array ($s[2], array ('acting', 'hit', 'stunned')))
				{
					$state[$i][2] = '';
				}
			}

			// Broken units leave the field when the next round starts.
			if ($e['round'] !== $lastRound || $e['type'] === 'phase')
			{
				foreach ($state as $i => $s)
				{
					if ($s[2] === 'fled' || $s[2] === 'wiped')
					{
						$state[$i][2] = 'gone';
					}
				}
				$lastRound = $e['round'];
			}

			$actor = $e['actor'] !== null && isset ($index[$e['actor']]) ? $index[$e['actor']] : null;
			$target = $e['target'] !== null && isset ($index[$e['target']]) ? $index[$e['target']] : null;

			switch ($e['type'])
			{
				case 'move':
					if ($actor !== null && $e['to'] !== null)
					{
						$state[$actor][0] = $e['to'];
						$state[$actor][2] = 'acting';
					}
				break;

				case 'stunned':
					if ($actor !== null)
					{
						$state[$actor][2] = 'stunned';
					}
				break;

				case 'melee':
				case 'shooting':
					if ($actor !== null)
					{
						$state[$actor][2] = 'acting';
					}
					if ($target !== null)
					{
						$state[$target][1] = max (0, $state[$target][1] - $e['kills']);
						$state[$target][2] = 'hit';
					}
				break;

				case 'fled':
				case 'wiped':
					if ($actor !== null)
					{
						$state[$actor][2] = $e['type'];
						if ($e['type'] === 'wiped')
						{
							$state[$actor][1] = 0;
						}
					}
				break;
			}

			$frames[] = array
			(
				'event' => $e['index'],
				'actor' => $actor,
				'target' => $target,
				'state' => array_values ($state)
			);
		}

		return array ('tokens' => $tokens, 'frames' => $frames);
	}
}
