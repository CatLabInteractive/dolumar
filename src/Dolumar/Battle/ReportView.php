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
 * The battle report as the Battle window shows it: a header with the verdict from the reader's side and three
 * views (summary, replay, chronicle). See .ai/superpowers/specs/2026-11-04-battle-report-redesign.md.
 */
class Dolumar_Battle_ReportView
{
	const VIEW_SUMMARY = 'summary';
	const VIEW_REPLAY = 'replay';
	const VIEW_CHRONICLE = 'chronicle';

	private $report;
	private $data;
	private $reader;
	private $readerTeam;
	private $analysis;
	private $text;
	private $now;

	private $battleUnits = array ();

	public function __construct (Dolumar_Battle_Report $report, $reader = null, $now = null)
	{
		$this->report = $report;
		$this->data = $report->getRawData ();
		$this->reader = $reader;
		$this->now = $now === null ? time () : $now;
		$this->text = Neuron_Core_Text::getInstance ();

		$this->readerTeam = ($reader && $this->data && intval ($this->data['targetId']) === intval ($reader->getId ())
			&& intval ($this->data['fromId']) !== intval ($reader->getId ()))
			? Dolumar_Battle_ReportAnalysis::TEAM_DEFENDER
			: Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER;
	}

	public function isFound ()
	{
		return is_array ($this->data);
	}

	public function isDone ()
	{
		return $this->data['fightDate'] + $this->data['fightDuration'] < $this->now;
	}

	public function getReaderTeam ()
	{
		return $this->readerTeam;
	}

	private function getEnemyTeam ()
	{
		return $this->readerTeam === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER
			? Dolumar_Battle_ReportAnalysis::TEAM_DEFENDER
			: Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER;
	}

	public function getAnalysis ()
	{
		if (!isset ($this->analysis))
		{
			$cutoff = $this->isDone () ? null : max (0, $this->now - $this->data['fightDate']);
			$this->analysis = new Dolumar_Battle_ReportAnalysis
			(
				$this->data['battleLog'],
				$this->isDone () ? $this->data['fightLog'] : '',
				$this->data['slots'],
				$cutoff
			);
		}
		return $this->analysis;
	}

	/**
	 * victory, repelled, heldoff, raided, fighting (reader attacks) or defending (reader is attacked).
	 * The attacker wins from 1% victory, as the old report did.
	 */
	public function getOutcome ()
	{
		$attacker = $this->readerTeam === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER;

		if (!$this->isDone ())
		{
			return $attacker ? 'fighting' : 'defending';
		}

		$won = $this->data['victory'] >= 0.01;
		if ($attacker)
		{
			return $won ? 'victory' : 'repelled';
		}
		return $won ? 'raided' : 'heldoff';
	}

	public function isReaderWin ()
	{
		return in_array ($this->getOutcome (), array ('victory', 'heldoff'));
	}

	private function getLoserTeam ()
	{
		return $this->data['victory'] >= 0.01 ? Dolumar_Battle_ReportAnalysis::TEAM_DEFENDER : Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER;
	}

	public function getPercent ()
	{
		return floor ($this->data['victory'] * 100);
	}

	/*
		Names
	*/
	private function getOpponent ()
	{
		return $this->readerTeam === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER ? $this->report->getDefender () : $this->report->getAttacker ();
	}

	private function villageName ($village)
	{
		if (!$village || $village instanceof Dolumar_Players_DummyVillage)
		{
			return '?';
		}
		return Neuron_Core_Tools::output_varchar ($village->getName ());
	}

	private function villageLink ($village)
	{
		if (!$village || $village instanceof Dolumar_Players_DummyVillage)
		{
			return '?';
		}
		return '<a href="javascript:void(0);" onclick="openWindow(\'villageProfile\', {\'village\':' . intval ($village->getId ()) . '});">'
			. $this->villageName ($village) . '</a>';
	}

	private function getBattleUnit ($json)
	{
		if (!isset ($this->battleUnits[$json]))
		{
			$unit = null;
			try
			{
				$unit = $this->report->getUnitData ($json);
			}
			catch (Exception $e)
			{
				$unit = null;
			}
			$this->battleUnits[$json] = ($unit instanceof Dolumar_Battle_Unit && $unit->getUnit ()) ? $unit : null;
		}
		return $this->battleUnits[$json];
	}

	/**
	 * Name (regiment name, else the unit name), unit name, image and side of a unit key from the analysis.
	 */
	public function describeUnit ($key)
	{
		$unit = $this->getAnalysis ()->getUnit ($key);
		$battleUnit = $unit ? $this->getBattleUnit ($unit['json']) : null;

		if (!$battleUnit)
		{
			return array ('name' => '?', 'unitName' => '?', 'img' => '', 'side' => 'them', 'html' => '<b class="them">?</b>');
		}

		$unitName = $battleUnit->getUnit ()->getName ();
		$squad = $battleUnit->getName ();
		$name = !empty ($squad) ? Neuron_Core_Tools::output_varchar ($squad) : $unitName;
		$side = $unit['team'] === $this->readerTeam ? 'us' : 'them';

		return array
		(
			'name' => $name,
			'unitName' => $unitName,
			'img' => $battleUnit->getUnit ()->getImageUrl (),
			'side' => $side,
			'html' => '<b class="' . $side . '">' . $name . '</b>'
		);
	}

	private function unitHtml ($key)
	{
		if ($key === null)
		{
			return '<b class="them">?</b>';
		}
		$d = $this->describeUnit ($key);
		return $d['html'];
	}

	private function line ($section, $seed, $values)
	{
		$line = $this->text->getRandomLine ($section, 'battlereport', $seed);
		return Neuron_Core_Tools::putIntoText ($line === null ? '' : $line, $values);
	}

	private function getSpecialUnit ($data)
	{
		$data = explode (':', (string) $data);
		$unit = Dolumar_SpecialUnits_SpecialUnits::getFromId (intval ($data[0]));
		if ($unit)
		{
			$unit->setLevel (isset ($data[1]) ? intval ($data[1]) : 1);
			$unit->setVillage ($this->report->getAttacker ());
		}
		return $unit;
	}

	/**
	 * The fight log line of one event, as HTML (names are escaped).
	 */
	public function getEventText ($e)
	{
		$seed = $e['random'] + $e['index'];

		switch ($e['type'])
		{
			case 'move':
				$slot = $this->getSlotObject ($e['to']);
				return $this->line ('log_move', $seed, array (
					'unit' => $this->unitHtml ($e['actor']),
					'terrain' => $slot ? $this->text->get ($slot->getName (), 'slots', 'battle') : '?'
				));

			case 'stunned':
				return $this->line ('log_stunned', $seed, array ('unit' => $this->unitHtml ($e['actor'])));

			case 'shooting':
				return $this->line ('log_shooting', $seed, array ('attacker' => $this->unitHtml ($e['actor']), 'defender' => $this->unitHtml ($e['target'])));

			case 'melee':
				return $this->line ('log_melee', $seed, array ('attacker' => $this->unitHtml ($e['actor']), 'defender' => $this->unitHtml ($e['target'])));

			case 'fled':
				return $this->line ('log_fled', $seed, array ('unit' => $this->unitHtml ($e['actor'])));

			case 'wiped':
				return $this->line ('log_wiped', $seed, array ('unit' => $this->unitHtml ($e['actor'])));

			case 'magic':
			case 'magicfailed':
				$unit = $this->getSpecialUnit ($e['special']);
				$effect = $e['effect'] ? Dolumar_Effects_Effect::getFromId ($e['effect']) : null;
				if (!$unit || !($effect instanceof Dolumar_Effects_Battle))
				{
					return '';
				}
				$facts = array_slice ($e['facts'], 3);
				return $e['type'] === 'magic'
					? $effect->getBattleLog ($this->report, $unit, $e['probability'], $facts)
					: $effect->getFailedLog ($this->report, $unit, $e['probability'], $facts);

			case 'magedied':
				$unit = $this->getSpecialUnit ($e['special']);
				return $unit ? $this->line ('log_magedied', $seed, array ('unit' => $unit->getName (), 'level' => $unit->getLevel ())) : '';
		}

		return '';
	}

	private $slotObjects;

	private function getSlotObject ($id)
	{
		if (!isset ($this->slotObjects))
		{
			$this->slotObjects = array ();
			foreach ($this->getAnalysis ()->getSlots () as $slot)
			{
				$object = Dolumar_Battle_Slot_Grass::getFromId ($slot['type'], $slot['id']);
				if ($object)
				{
					$this->slotObjects[$slot['id']] = $object;
				}
			}
		}
		return isset ($this->slotObjects[$id]) ? $this->slotObjects[$id] : null;
	}

	private function getPhaseLabel ($phase, $round)
	{
		if ($phase === Dolumar_Battle_ReportAnalysis::PHASE_MELEE)
		{
			return Neuron_Core_Tools::putIntoText ($this->text->get ('phase_melee', 'report2', 'battle'), array (max (1, $round)));
		}
		return $this->text->get ('phase_' . $phase, 'report2', 'battle');
	}

	/**
	 * "us" when the reader's side lost men in this event, "them" when the enemy did.
	 */
	private function getKillSide ($e)
	{
		if ($e['type'] === 'fled' || $e['type'] === 'wiped')
		{
			return $e['team'] === $this->readerTeam ? 'us' : 'them';
		}
		return $e['team'] === $this->readerTeam ? 'them' : 'us';
	}

	/*
		Header data shared by all views
	*/
	public function getHeader ()
	{
		$outcome = $this->getOutcome ();
		$opponent = $this->getOpponent ();
		$date = date (DATETIME, $this->data['fightDate']);

		if ($this->isDone ())
		{
			$when = Neuron_Core_Tools::putIntoText ($this->text->get ('when', 'report2', 'battle'), array (
				'date' => $date,
				'duration' => Neuron_Core_Tools::getDurationText ($this->data['fightDuration'])
			));
		}
		else
		{
			$left = $this->data['fightDate'] + $this->data['fightDuration'] - $this->now;
			$when = Neuron_Core_Tools::putIntoText ($this->text->get ('when_live', 'report2', 'battle'), array (
				'date' => $date,
				'duration' => Neuron_Core_Tools::getDurationText (max (1, $left))
			));
		}

		return array
		(
			'outcome' => $outcome,
			'win' => $this->isReaderWin (),
			'live' => !$this->isDone (),
			'side' => $this->readerTeam === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER ? 'attack' : 'defence',
			'title' => $this->text->get ('v_' . $outcome, 'report2', 'battle'),
			'subtitle' => Neuron_Core_Tools::putIntoText ($this->text->get ('sub_' . $outcome, 'report2', 'battle'), array (
				'percent' => $this->getPercent (),
				'village' => $this->villageLink ($opponent)
			)),
			'when' => $when,
			'percent' => $this->isDone () ? $this->getPercent () : null,
			'opponent' => $this->villageName ($opponent),
			'opponentId' => $opponent ? $opponent->getId () : 0,
			'spoils' => $this->getSpoils ()
		);
	}

	/**
	 * Resources and runes taken, signed from the reader's side: [['class' => 'resource gold', 'title', 'amount' => '+240']].
	 */
	public function getSpoils ()
	{
		if (!$this->isDone ())
		{
			return array ();
		}

		$sign = $this->readerTeam === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER ? '+' : '−';
		$out = array ();

		foreach ($this->report->getStolenResources () as $k => $v)
		{
			if ($v > 0)
			{
				$out[] = array ('class' => 'resource ' . $k, 'title' => ucfirst ($this->text->get ($k, 'resources', 'main')), 'amount' => $sign . self::number ($v));
			}
		}

		foreach ($this->report->getStolenRunes () as $k => $v)
		{
			if ($v > 0)
			{
				$out[] = array ('class' => 'rune ' . $k, 'title' => ucfirst ($this->text->get ($k, $v > 1 ? 'runeDouble' : 'runeSingle', 'main')), 'amount' => $sign . $v);
			}
		}

		return $out;
	}

	/*
		A: summary
	*/
	public function getArmies ()
	{
		$out = array ();
		foreach (array ($this->readerTeam, $this->getEnemyTeam ()) as $team)
		{
			$rows = array ();
			foreach ($this->getAnalysis ()->getArmy ($team) as $row)
			{
				$rows[] = $this->describeArmyRow ($row, $team);
			}

			$totals = $this->getAnalysis ()->getArmyTotals ($team);
			$isAttacker = $team === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER;

			$out[] = array
			(
				'label' => $team === $this->readerTeam
					? $this->text->get ('yours', 'report2', 'battle')
					: $this->villageName ($isAttacker ? $this->report->getAttacker () : $this->report->getDefender ()),
				'side' => $team === $this->readerTeam ? 'us' : 'them',
				'died' => $totals['died'],
				'rows' => $rows
			);
		}
		return $out;
	}

	private function describeArmyRow ($row, $team)
	{
		$battleUnit = isset ($row['json']) ? $this->getBattleUnit ($row['json']) : null;
		$unit = $battleUnit ? $battleUnit->getUnit () : null;

		if (!$unit)
		{
			$village = Dolumar_Players_Village::getVillage ($row['village']);
			$race = Dolumar_Races_Race::getFromId ($row['race']);
			$unit = Dolumar_Units_Unit::getUnitFromId ($row['unit'], $race, $village);
		}

		// Regiment names: the analysis knows the squad index of each regiment of this type.
		$names = array ();
		$squadNames = explode (';', (string) $this->data['squads']);
		foreach ($row['squads'] as $squad)
		{
			if ($squad > 0 && isset ($squadNames[$squad - 1]) && $squadNames[$squad - 1] !== '')
			{
				$names[] = Neuron_Core_Tools::output_varchar ($squadNames[$squad - 1]);
			}
		}

		$unitName = $unit ? $unit->getName () : '?';
		$name = count ($names) === 1 ? $names[0] : $unitName;

		$support = null;
		$home = $team === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER ? $this->data['fromId'] : $this->data['targetId'];
		if (intval ($row['village']) !== intval ($home))
		{
			$support = Neuron_Core_Tools::putIntoText ($this->text->get ('support', 'report2', 'battle'), array (
				$this->villageName (Dolumar_Players_Village::getVillage ($row['village']))
			));
		}

		$percent = $row['amount'] > 0 ? round ($row['left'] / $row['amount'] * 100) : 0;

		return array
		(
			'name' => $name,
			'unitName' => $unitName,
			'img' => $unit ? $unit->getImageUrl () : '',
			'left' => $row['left'],
			'start' => $row['amount'],
			'died' => $row['died'],
			'percent' => $percent,
			'fate' => $row['fate'],
			'fateText' => $this->text->get ('fate_' . $row['fate'], 'report2', 'battle'),
			'support' => $support,
			'leftText' => Neuron_Core_Tools::putIntoText ($this->text->get ('left', 'report2', 'battle'), array (
				'left' => self::number ($row['left']),
				'start' => self::number ($row['amount'])
			))
		);
	}

	public function getMoments ()
	{
		$analysis = $this->getAnalysis ();
		$out = array ();

		foreach ($analysis->getMoments ($this->getLoserTeam (), $this->readerTeam) as $m)
		{
			$e = $m['event'];
			$seed = $e['random'];

			switch ($m['kind'])
			{
				case 'magic':
					$label = $this->text->get ('moment_magic', 'report2', 'battle');
					$text = $this->getEventText ($e);
					$icon = 'magic';
				break;

				case 'volley':
					$label = $this->text->get ('moment_volley', 'report2', 'battle');
					$text = $this->line ('moment_volley', $seed, array ('attacker' => $this->unitHtml ($e['actor']), 'defender' => $this->unitHtml ($e['target']), 'amount' => $e['kills']));
					$icon = 'volley';
				break;

				case 'turningpoint':
					$label = $e['phase'] === Dolumar_Battle_ReportAnalysis::PHASE_MELEE
						? Neuron_Core_Tools::putIntoText ($this->text->get ('moment_turningpoint_round', 'report2', 'battle'), array ($e['round']))
						: $this->text->get ('moment_turningpoint', 'report2', 'battle');
					$text = $this->line ($e['type'] === 'fled' ? 'moment_fled' : 'moment_wiped', $seed, array ('unit' => $this->unitHtml ($e['actor'])));
					$icon = 'turningpoint';
				break;

				default:
					$label = $this->text->get ('moment_heaviestloss', 'report2', 'battle');
					$text = $this->line ('moment_loss', $seed, array ('attacker' => $this->unitHtml ($e['actor']), 'defender' => $this->unitHtml ($e['target']), 'amount' => $e['kills']));
					$icon = 'loss';
				break;
			}

			if ($text === '')
			{
				continue;
			}

			$out[] = array ('kind' => $m['kind'], 'icon' => $icon, 'label' => $label, 'text' => $text, 'event' => $e['index']);
		}

		return $out;
	}

	/**
	 * For a lost defence: how many defence slots were empty.
	 */
	public function getChange ()
	{
		if ($this->readerTeam !== Dolumar_Battle_ReportAnalysis::TEAM_DEFENDER || !$this->isDone ())
		{
			return null;
		}

		$empty = $this->getAnalysis ()->countEmptySlots (Dolumar_Battle_ReportAnalysis::TEAM_DEFENDER);
		$total = count ($this->getAnalysis ()->getSlots ());

		if ($empty > 0)
		{
			return Neuron_Core_Tools::putIntoText ($this->text->get ('change_slots', 'report2', 'battle'), array ('empty' => $empty, 'total' => $total));
		}

		return $this->isReaderWin () ? null : $this->text->get ('change_formation', 'report2', 'battle');
	}

	public function getLogGroups ()
	{
		$out = array ();
		$first = true;
		$groups = $this->getAnalysis ()->getGroups ();

		// The intro line opens the log, under "Before the fight".
		if (count ($groups) === 0 || $groups[0]['phase'] !== Dolumar_Battle_ReportAnalysis::PHASE_PREFIGHT)
		{
			array_unshift ($groups, array ('phase' => Dolumar_Battle_ReportAnalysis::PHASE_PREFIGHT, 'round' => 0, 'time' => 0, 'events' => array ()));
		}

		foreach ($groups as $group)
		{
			$rows = array ();

			if ($first)
			{
				$rows[] = array ('event' => -1, 'img' => null, 'text' => $this->getIntro (), 'kills' => null, 'side' => null);
				$first = false;
			}

			foreach ($group['events'] as $e)
			{
				$text = $this->getEventText ($e);
				if ($text === '')
				{
					continue;
				}

				$img = null;
				if ($e['actor'] !== null)
				{
					$d = $this->describeUnit ($e['actor']);
					$img = $d['img'];
				}

				$kills = null;
				if (($e['type'] === 'melee' || $e['type'] === 'shooting') && $e['kills'] > 0)
				{
					$kills = '−' . $e['kills'];
				}
				elseif ($e['type'] === 'fled' || $e['type'] === 'wiped')
				{
					$kills = $this->text->get ('fate_' . $e['type'], 'report2', 'battle');
				}

				$rows[] = array ('event' => $e['index'], 'img' => $img, 'text' => $text, 'kills' => $kills, 'side' => $kills !== null ? $this->getKillSide ($e) : null);
			}

			$out[] = array
			(
				'label' => $this->getPhaseLabel ($group['phase'], $group['round']),
				'time' => Neuron_Core_Tools::getDuration ($group['time']),
				'rows' => $rows
			);
		}

		return $out;
	}

	private function getIntro ()
	{
		return Neuron_Core_Tools::putIntoText
		(
			$this->text->getRandomLine ('introtext', 'battlereport', $this->report->getId ()),
			array
			(
				'attacker' => $this->villageName ($this->report->getAttacker ()),
				'defender' => $this->villageName ($this->report->getDefender ())
			)
		);
	}

	/*
		B: replay
	*/
	public function getReplayData ($start = null)
	{
		$analysis = $this->getAnalysis ();
		$replay = $analysis->getReplay ();

		$tokens = array ();
		foreach ($replay['tokens'] as $t)
		{
			$d = $this->describeUnit ($t['key']);
			$tokens[] = array
			(
				'team' => $t['team'] === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER ? 'att' : 'def',
				'side' => $d['side'],
				'name' => $d['name'],
				'img' => $d['img']
			);
		}

		$slots = array ();
		foreach ($analysis->getSlots () as $slot)
		{
			$object = $this->getSlotObject ($slot['id']);
			$slots[] = array
			(
				'id' => $slot['id'],
				'name' => $object ? $this->text->get ($object->getName (), 'slots', 'battle') : '',
				// Slot art lives with the game's own images (the battlefield CSS uses it), not under IMAGE_URL.
				'img' => $object ? STATIC_ABSOLUTE_URL . 'dolumar/images/slots/' . $object->getName () . '.png' : ''
			);
		}

		$events = array ();
		$phases = array ();
		$lastPhase = null;
		$events[] = array ('text' => $this->text->get ('start', 'report2', 'battle'), 'phase' => '', 'kills' => null, 'side' => null);

		$eventsById = array ();
		foreach ($analysis->getEvents () as $e)
		{
			$eventsById[$e['index']] = $e;
		}

		$frames = array ();
		foreach ($replay['frames'] as $i => $frame)
		{
			$e = $eventsById[$frame['event']];
			if ($e['type'] === 'phase' && $e['phase'] !== Dolumar_Battle_ReportAnalysis::PHASE_FINISHED)
			{
				continue;
			}

			$text = $e['type'] === 'phase' ? $this->text->get ('phase_finished', 'report2', 'battle') : $this->getEventText ($e);
			if ($text === '')
			{
				continue;
			}

			$label = $e['type'] === 'phase' ? $text : $this->getPhaseLabel ($e['phase'], $e['round']);
			$phaseKey = $e['phase'] . ':' . $e['round'];
			if ($phaseKey !== $lastPhase)
			{
				$phases[] = array ('label' => $this->getShortPhaseLabel ($e['phase'], $e['round']), 'title' => $label, 'frame' => count ($frames) + 1);
				$lastPhase = $phaseKey;
			}

			$kills = null;
			if (($e['type'] === 'melee' || $e['type'] === 'shooting') && $e['kills'] > 0)
			{
				$kills = '−' . $e['kills'];
			}

			$events[] = array ('text' => $text, 'phase' => $label . ' · ' . Neuron_Core_Tools::getDuration ($e['time']), 'kills' => $kills, 'side' => $kills ? $this->getKillSide ($e) : null, 'event' => $e['index']);
			$frames[] = array ('a' => $frame['actor'], 't' => $frame['target'], 's' => $frame['state']);
		}

		// The frame to open on: the requested event, the end of a finished fight, or the newest event of a live one.
		$startFrame = 0;
		if ($start !== null)
		{
			foreach ($events as $i => $ev)
			{
				if (isset ($ev['event']) && $ev['event'] >= $start)
				{
					$startFrame = $i;
					break;
				}
			}
		}
		elseif (!$this->isDone ())
		{
			$startFrame = count ($frames);
		}

		// Frame 0 is the starting position.
		$initial = array ();
		foreach ($replay['tokens'] as $i => $t)
		{
			$unit = $analysis->getUnit ($t['key']);
			$initial[] = array ($unit['initialSlot'], $unit['start'], '');
		}
		array_unshift ($frames, array ('a' => null, 't' => null, 's' => $initial));

		return array
		(
			'report' => intval ($this->report->getId ()),
			'live' => !$this->isDone (),
			'start' => $startFrame,
			'slots' => $slots,
			'tokens' => $tokens,
			'frames' => $frames,
			'events' => $events,
			'phases' => $phases,
			'labels' => array
			(
				'play' => $this->text->get ('play', 'report2', 'battle'),
				'pause' => $this->text->get ('pause', 'report2', 'battle'),
				'fled' => $this->text->get ('fate_fled', 'report2', 'battle'),
				'wiped' => $this->text->get ('fate_wiped', 'report2', 'battle')
			)
		);
	}

	private function getShortPhaseLabel ($phase, $round)
	{
		switch ($phase)
		{
			case Dolumar_Battle_ReportAnalysis::PHASE_PREFIGHT:
				return '✦';
			case Dolumar_Battle_ReportAnalysis::PHASE_SHOOTING:
				return '➶';
			case Dolumar_Battle_ReportAnalysis::PHASE_MELEE:
				return (string) max (1, $round);
		}
		return '■';
	}

	/*
		C: chronicle
	*/
	public function getChronicle ()
	{
		$analysis = $this->getAnalysis ();
		$facts = $analysis->getChronicle ();
		$seed = intval ($this->report->getId ());
		$attackerName = $this->villageName ($this->report->getAttacker ());
		$defenderName = $this->villageName ($this->report->getDefender ());
		$attacker = '<b class="' . ($this->readerTeam === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER ? 'us' : 'them') . '">' . $attackerName . '</b>';
		$defender = '<b class="' . ($this->readerTeam === Dolumar_Battle_ReportAnalysis::TEAM_DEFENDER ? 'us' : 'them') . '">' . $defenderName . '</b>';

		$chapters = array ();

		// The approach: intro and magic.
		$p = array ($this->line ('chronicle_intro', $seed, array ('attacker' => $attacker, 'defender' => $defender)));
		foreach ($facts['magic'] as $e)
		{
			$unit = $this->getSpecialUnit ($e['special']);
			$effect = $e['effect'] ? Dolumar_Effects_Effect::getFromId ($e['effect']) : null;
			if ($unit && $effect)
			{
				$p[] = $this->line ('chronicle_' . $e['type'], $seed + $e['index'], array ('unit' => $unit->getName (), 'spell' => $effect->getName ()));
			}
		}
		$chapters[] = array ('title' => $this->text->get ('chap_approach', 'report2', 'battle'), 'text' => implode (' ', $p), 'note' => null);

		// The volley.
		$att = Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER;
		$def = Dolumar_Battle_ReportAnalysis::TEAM_DEFENDER;
		if ($facts['shootingBest'])
		{
			$best = $facts['shootingBest'];
			$p = array ($this->line ('chronicle_volley', $seed, array ('unit' => $this->unitHtml ($best['actor']), 'amount' => $best['kills'])));
			$p[] = $this->line ('chronicle_volley_total', $seed, array (
				'attacker' => $attacker, 'defender' => $defender,
				'attlost' => $facts['shooting'][$def], 'deflost' => $facts['shooting'][$att]
			));
			$note = $this->noteLosses ($facts['shooting'][$def], $facts['shooting'][$att]);
		}
		else
		{
			$p = array ($this->line ('chronicle_novolley', $seed, array ()));
			$note = null;
		}
		$chapters[] = array ('title' => $this->text->get ('chap_volley', 'report2', 'battle'), 'text' => implode (' ', $p), 'note' => $note);

		// The melee.
		if ($facts['rounds'] > 0)
		{
			$p = array ();
			foreach (array ($this->readerTeam, $this->getEnemyTeam ()) as $team)
			{
				if ($facts['meleeBest'][$team])
				{
					$p[] = $this->line ('chronicle_melee', $seed + $team, array ('unit' => $this->unitHtml ($facts['meleeBest'][$team]['unit']), 'amount' => $facts['meleeBest'][$team]['kills']));
					break;
				}
			}
			foreach (array_slice ($facts['broke'], 0, 4) as $e)
			{
				$p[] = $this->line ('chronicle_broke_' . $e['type'], $seed + $e['index'], array ('unit' => $this->unitHtml ($e['actor'])));
			}

			if ($this->isDone ())
			{
				$p[] = $this->line ($this->data['victory'] >= 0.01 ? 'chronicle_end_win' : 'chronicle_end_loss', $seed, array ('attacker' => $attacker, 'defender' => $defender));
			}

			$title = $facts['rounds'] === 1
				? $this->text->get ('chap_melee_one', 'report2', 'battle')
				: Neuron_Core_Tools::putIntoText ($this->text->get ('chap_melee', 'report2', 'battle'), array ($facts['rounds']));

			$totalsAtt = $analysis->getArmyTotals ($att);
			$totalsDef = $analysis->getArmyTotals ($def);
			$chapters[] = array ('title' => $title, 'text' => implode (' ', $p), 'note' => $this->noteLosses ($totalsAtt['died'], $totalsDef['died']));
		}

		// The spoils.
		$resources = 0;
		foreach ($this->report->getStolenResources () as $v)
		{
			$resources += $v;
		}
		$runes = $this->report->countStolenRunes ();

		if ($this->isDone ())
		{
			$text = ($resources > 0 || $runes > 0)
				? $this->line ('chronicle_spoils', $seed, array ('resources' => self::number ($resources), 'runes' => $runes))
				: $this->line ('chronicle_nospoils', $seed, array ());
			$chapters[] = array ('title' => $this->text->get ('chap_spoils', 'report2', 'battle'), 'text' => $text, 'note' => null, 'spoils' => true);
		}

		// Hero and verdict.
		$hero = null;
		$heroTeam = $this->readerTeam;
		$heroUnit = $analysis->getHero ($heroTeam);
		if (!$heroUnit)
		{
			$heroTeam = $this->getEnemyTeam ();
			$heroUnit = $analysis->getHero ($heroTeam);
		}
		if ($heroUnit)
		{
			$d = $this->describeUnit ($heroUnit['key']);
			$hero = array
			(
				'name' => $d['name'],
				'img' => $d['img'],
				'title' => Neuron_Core_Tools::putIntoText ($this->text->get ('hero', 'report2', 'battle'), array ($d['name'])),
				'line' => Neuron_Core_Tools::putIntoText ($this->text->get ('heroLine', 'report2', 'battle'), array (
					'kills' => $heroUnit['kills'], 'lost' => min ($heroUnit['start'], $heroUnit['lost'])
				))
			);
		}

		$verdict = null;
		if ($this->isDone ())
		{
			$win = $this->data['victory'] >= 0.01;
			if ($hero)
			{
				$verdict = $this->line ($win ? 'chronicle_verdict_win' : 'chronicle_verdict_loss', $seed, array ('percent' => $this->getPercent (), 'hero' => $hero['name']));
			}
			else
			{
				$verdict = $this->line ('chronicle_verdict_plain', $seed, array ('percent' => $this->getPercent ()));
			}
		}

		$name = $this->villageName ($this->report->getDefender ());
		$winner = $this->data['victory'] >= 0.01 ? $attackerName : $defenderName;

		return array
		(
			'title' => Neuron_Core_Tools::putIntoText ($this->text->get ('chronicleTitle', 'report2', 'battle'), array ($name)),
			'dateline' => $this->isDone ()
				? Neuron_Core_Tools::putIntoText ($this->text->get ($this->data['victory'] >= 0.01 ? 'dateline_win' : 'dateline_loss', 'report2', 'battle'), array (
					'date' => date (DATETIME, $this->data['fightDate']),
					'village' => $winner
				))
				: $this->text->get ('chronicleLive', 'report2', 'battle'),
			'chapters' => $chapters,
			'hero' => $hero,
			'verdict' => $verdict
		);
	}

	private function noteLosses ($attackerLost, $defenderLost)
	{
		$reader = $this->readerTeam === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER ? $attackerLost : $defenderLost;
		$enemy = $this->readerTeam === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER ? $defenderLost : $attackerLost;

		return array
		(
			'title' => $this->text->get ('note_losses', 'report2', 'battle'),
			'us' => $reader,
			'them' => $enemy,
			'usLabel' => $this->text->get ('yours', 'report2', 'battle'),
			'themLabel' => $this->villageName ($this->getOpponent ())
		);
	}

	/**
	 * One row of the Reports list.
	 */
	public function getListRow ()
	{
		$outcome = $this->getOutcome ();
		$opponent = $this->getOpponent ();
		$parts = array ();

		if ($this->isDone ())
		{
			if ($outcome === 'victory' || $outcome === 'raided')
			{
				$parts[] = $this->getPercent () . '%';
			}

			$resources = 0;
			foreach ($this->report->getStolenResources () as $v)
			{
				$resources += $v;
			}
			$runes = $this->report->countStolenRunes ();
			$sign = $this->readerTeam === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER ? '+' : '−';
			$class = $sign === '+' ? 'good' : 'bad';

			$loot = array ();
			if ($resources > 0)
			{
				$loot[] = $sign . Neuron_Core_Tools::putIntoText ($this->text->get ('loot', 'overview2', 'battle'), array (self::number ($resources)));
			}
			if ($runes > 0)
			{
				$loot[] = $sign . ($runes === 1 ? $this->text->get ('rune', 'overview2', 'battle') : Neuron_Core_Tools::putIntoText ($this->text->get ('runes', 'overview2', 'battle'), array ($runes)));
			}
			if (count ($loot) > 0)
			{
				$parts[] = '<span class="' . $class . '">' . implode (', ', $loot) . '</span>';
			}

			$mine = $this->getAnalysis ()->getArmyTotals ($this->readerTeam);
			$theirs = $this->getAnalysis ()->getArmyTotals ($this->getEnemyTeam ());
			$parts[] = Neuron_Core_Tools::putIntoText ($this->text->get ('lost', 'overview2', 'battle'), array (self::number ($mine['died'])));
			$parts[] = Neuron_Core_Tools::putIntoText ($this->text->get ('killed', 'overview2', 'battle'), array (self::number ($theirs['died'])));
		}
		else
		{
			$last = $this->getAnalysis ()->getLastEvent ();
			if ($last && $last['phase'] !== Dolumar_Battle_ReportAnalysis::PHASE_FINISHED)
			{
				$parts[] = $this->getPhaseLabel ($last['phase'], $last['round']);
			}
			$left = $this->data['fightDate'] + $this->data['fightDuration'] - $this->now;
			$parts[] = Neuron_Core_Tools::putIntoText ($this->text->get ('endsIn', 'overview2', 'battle'), array (Neuron_Core_Tools::getDurationText (max (1, $left))));
		}

		$date = $this->data['fightDate'];
		$when = date ('Y-m-d', $date) === date ('Y-m-d', $this->now) ? date ('H:i', $date) : date ('d.m', $date);

		return array
		(
			'id' => intval ($this->report->getId ()),
			'outcome' => $outcome,
			'badge' => !$this->isDone () ? 'live' : ($this->isReaderWin () ? ($this->readerTeam === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER ? 'win' : 'held') : 'loss'),
			'icon' => $this->readerTeam === Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER ? '⚔' : '🛡',
			'title' => Neuron_Core_Tools::putIntoText ($this->text->get ('t_' . $outcome, 'overview2', 'battle'), array ($this->villageName ($opponent))),
			'line' => implode (' · ', $parts),
			'when' => $when,
			'live' => !$this->isDone ()
		);
	}

	public static function number ($n)
	{
		// Thin space as thousands separator: reads the same in every language.
		return number_format (intval ($n), 0, '', ' ');
	}

	/*
		Rendering
	*/
	public function render ($view, $logid = null, $vid = null)
	{
		if (!in_array ($view, array (self::VIEW_SUMMARY, self::VIEW_REPLAY, self::VIEW_CHRONICLE)))
		{
			$view = $this->isDone () ? self::VIEW_SUMMARY : self::VIEW_REPLAY;
		}

		$page = new Neuron_Core_Template ();
		$page->setTextSection ('report2', 'battle');

		$page->set ('reportid', intval ($this->report->getId ()));
		$page->set ('vid', intval ($vid));
		$page->set ('view', $view);
		$page->set ('header', $this->getHeader ());
		$page->set ('isDefender', $this->readerTeam === Dolumar_Battle_ReportAnalysis::TEAM_DEFENDER);

		switch ($view)
		{
			case self::VIEW_REPLAY:
				$page->set ('replay', $this->getReplayData ($logid === null ? null : intval ($logid)));
				$page->set ('armies', $this->getArmies ());
			break;

			case self::VIEW_CHRONICLE:
				$page->set ('chronicle', $this->getChronicle ());
			break;

			default:
				$page->set ('armies', $this->getArmies ());
				$page->set ('moments', $this->getMoments ());
				$page->set ('change', $this->getChange ());
				$page->set ('groups', $this->getLogGroups ());
				$page->set ('eventCount', count (array_filter ($this->getAnalysis ()->getEvents (), function ($e) {
					return $e['type'] !== 'phase';
				})));
			break;
		}

		return $page->parse ('battle/report/view.phpt');
	}
}
