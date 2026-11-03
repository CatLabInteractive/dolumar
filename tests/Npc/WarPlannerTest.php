<?php

use PHPUnit\Framework\TestCase;

class WarPlannerTest extends TestCase
{
	private function types (array $actions)
	{
		return array_map (function ($a) {
			return $a->type;
		}, $actions);
	}

	private function plan (Dolumar_Npc_Snapshot $s, array $rows, array $transitions = [])
	{
		$planner = new Dolumar_Npc_Planner_War ();
		$actions = $planner->plan ($s, $rows, $transitions);
		return [$actions, $planner->rows ()];
	}

	public function testLadderOrder (): void
	{
		$s = SnapshotFactory::make ();
		$rows = [1 => SnapshotFactory::row (1, -60.0, ['last_attack_vid' => 11, 'last_penalty' => 40.0])];

		// Step 1: scouts, once.
		list ($actions, $rows) = $this->plan ($s, $rows);
		$this->assertSame ([Dolumar_Npc_Action::SCOUT_RAID], $this->types ($actions));
		$this->assertSame (11, $actions[0]->get ('target'));
		$this->assertSame (2, $actions[0]->get ('units'));
		$this->assertSame (SnapshotFactory::NOW, $rows[1]['scouts_at']);

		// Nothing more in the same run or before the envoy delay.
		list ($actions, $rows) = $this->plan ($s, $rows);
		$this->assertSame ([], $actions);

		// Step 2: the envoy, after the delay, once.
		$s->now += Dolumar_Npc_Config::ENVOY_DELAY;
		list ($actions, $rows) = $this->plan ($s, $rows);
		$this->assertSame ([Dolumar_Npc_Action::ENVOY], $this->types ($actions));
		$this->assertSame (2950, $actions[0]->get ('price'));
		$this->assertSame (2950, $rows[1]['envoy_price']);
		$this->assertSame ($s->now, $rows[1]['envoy_at']);

		// Step 3: the war party, after the delay.
		list ($actions, $rows) = $this->plan ($s, $rows);
		$this->assertSame ([], $actions);
		$s->now += Dolumar_Npc_Config::WAR_PARTY_DELAY;
		list ($actions, $rows) = $this->plan ($s, $rows);
		$this->assertSame ([Dolumar_Npc_Action::WAR_PARTY], $this->types ($actions));
		$this->assertSame (11, $actions[0]->get ('target'));
		$this->assertSame (1, $rows[1]['war_parties_sent']);
		$this->assertSame (1, $rows[1]['npc_attacks_today']);
		$this->assertSame ($s->now, $rows[1]['last_npc_attack_at']);
	}

	public function testWaryPlayerGetsScoutsAndEnvoyButNoWarParty (): void
	{
		$s = SnapshotFactory::make ();
		$rows = [1 => SnapshotFactory::row (1, -20.0, ['scouts_at' => SnapshotFactory::NOW - 10 * 3600, 'envoy_at' => SnapshotFactory::NOW - 9 * 3600, 'envoy_price' => 500])];
		list ($actions) = $this->plan ($s, $rows);
		$this->assertSame ([], $actions);
	}

	public function testWarPartyFraction (): void
	{
		$this->assertEqualsWithDelta (0.3, Dolumar_Npc_Planner_War::warPartyFraction (40.0, 0), 0.0001);
		$this->assertEqualsWithDelta (0.45, Dolumar_Npc_Planner_War::warPartyFraction (40.0, 1), 0.0001);
		$this->assertEqualsWithDelta (0.6, Dolumar_Npc_Planner_War::warPartyFraction (40.0, 2), 0.0001);
		$this->assertEqualsWithDelta (0.6, Dolumar_Npc_Planner_War::warPartyFraction (40.0, 9), 0.0001);
		$this->assertEqualsWithDelta (0.1125, Dolumar_Npc_Planner_War::warPartyFraction (15.0, 0), 0.0001);
		$this->assertEqualsWithDelta (0.1, Dolumar_Npc_Planner_War::warPartyFraction (0.0, 0), 0.0001);
		$this->assertEqualsWithDelta (0.6, Dolumar_Npc_Planner_War::warPartyFraction (400.0, 0), 0.0001);
	}

	private function hostileRow ($plid, array $overrides = [])
	{
		return SnapshotFactory::row ($plid, -80.0, array_merge ([
			'last_attack_vid' => 10,
			'last_penalty' => 40.0,
			'scouts_at' => SnapshotFactory::NOW - 10 * 3600,
			'envoy_at' => SnapshotFactory::NOW - 9 * 3600,
			'envoy_price' => 2000,
		], $overrides));
	}

	public function testDailyCapOfTwo (): void
	{
		$s = SnapshotFactory::make ();
		$rows = [1 => $this->hostileRow (1, ['npc_attacks_day' => SnapshotFactory::NOW - 3600, 'npc_attacks_today' => 2])];
		list ($actions) = $this->plan ($s, $rows);
		$this->assertSame ([], $actions);

		// A day later the window resets.
		$s->now += 86400;
		list ($actions, $rows) = $this->plan ($s, $rows);
		$this->assertSame ([Dolumar_Npc_Action::WAR_PARTY], $this->types ($actions));
		$this->assertSame (1, $rows[1]['npc_attacks_today']);
		$this->assertSame ($s->now, $rows[1]['npc_attacks_day']);
	}

	public function testWarPartiesAreSpacedByTheDelay (): void
	{
		$s = SnapshotFactory::make ();
		$rows = [1 => $this->hostileRow (1, ['last_npc_attack_at' => SnapshotFactory::NOW - 3600, 'npc_attacks_day' => SnapshotFactory::NOW - 3600, 'npc_attacks_today' => 1])];
		list ($actions) = $this->plan ($s, $rows);
		$this->assertSame ([], $actions);
	}

	public function testProtectionByAgeAndRunes (): void
	{
		$s = SnapshotFactory::make ();
		$s->neighbours[10]['playerCreated'] = SnapshotFactory::NOW - 3 * 86400;
		$s->neighbours[11]['playerCreated'] = SnapshotFactory::NOW - 3 * 86400;
		list ($actions) = $this->plan ($s, [1 => $this->hostileRow (1)]);
		$this->assertSame ([], $actions);

		$s = SnapshotFactory::make ();
		$s->neighbours[10]['playerRunes'] = 5;
		$s->neighbours[11]['playerRunes'] = 5;
		list ($actions) = $this->plan ($s, [1 => $this->hostileRow (1)]);
		$this->assertSame ([], $actions);

		$s->protectionRunes = 4;
		list ($actions) = $this->plan ($s, [1 => $this->hostileRow (1)]);
		$this->assertSame ([Dolumar_Npc_Action::WAR_PARTY], $this->types ($actions));

		// Scouts respect the same rules.
		$s = SnapshotFactory::make ();
		$s->neighbours[10]['vacation'] = true;
		$s->neighbours[11]['vacation'] = true;
		list ($actions) = $this->plan ($s, [1 => SnapshotFactory::row (1, -60.0, ['last_attack_vid' => 10])]);
		$this->assertSame ([], $actions);
	}

	public function testTruceBlocksTheLadder (): void
	{
		$s = SnapshotFactory::make ();
		$row = Dolumar_Npc_Standing::makePeace ($this->hostileRow (1), SnapshotFactory::NOW - 10, 1.0);
		list ($actions) = $this->plan ($s, [1 => $row]);
		$this->assertSame ([], $actions);
	}

	public function testWarPartyFallsBackToNearestVillage (): void
	{
		$s = SnapshotFactory::make ();
		// The attacking village 99 is gone: the nearest village of player 1 is 10.
		list ($actions) = $this->plan ($s, [1 => $this->hostileRow (1, ['last_attack_vid' => 99])]);
		$this->assertSame ([Dolumar_Npc_Action::WAR_PARTY], $this->types ($actions));
		$this->assertSame (10, $actions[0]->get ('target'));

		// Unreachable villages are skipped.
		$s->neighbours[10]['reachable'] = false;
		list ($actions) = $this->plan ($s, [1 => $this->hostileRow (1, ['last_attack_vid' => 99])]);
		$this->assertSame (11, $actions[0]->get ('target'));

		// A player without villages in range is skipped without error.
		$s->neighbours[11]['reachable'] = false;
		list ($actions, $rows) = $this->plan ($s, [1 => $this->hostileRow (1, ['last_attack_vid' => 99])]);
		$this->assertSame ([], $actions);
		$this->assertSame (0, $rows[1]['war_parties_sent']);
	}

	public function testNoArmyNoAttack (): void
	{
		$s = SnapshotFactory::make (['units' => []]);
		list ($actions, $rows) = $this->plan ($s, [1 => $this->hostileRow (1)]);
		$this->assertSame ([], $actions);
		$this->assertSame (0, $rows[1]['npc_attacks_today']);
	}

	public function testOneAttackPerRun (): void
	{
		$s = SnapshotFactory::make ();
		$rows = [1 => $this->hostileRow (1), 2 => $this->hostileRow (2, ['last_attack_vid' => 20])];
		list ($actions) = $this->plan ($s, $rows);
		$this->assertCount (1, $actions);
	}

	public function testAggressionOffYieldsNoLadder (): void
	{
		$s = SnapshotFactory::make (['aggression' => Dolumar_Npc_Config::AGGRESSION_OFF]);
		list ($actions, $rows) = $this->plan ($s, [1 => $this->hostileRow (1)]);
		$this->assertSame ([], $actions);
		$this->assertSame (0, $rows[1]['war_parties_sent']);
	}

	public function testFriendlyPlayerUnderAttackGetsSupportAndRecall (): void
	{
		$s = SnapshotFactory::make ([
			'incoming' => [['battleId' => 500, 'fromId' => 20, 'targetId' => 11, 'fightDate' => SnapshotFactory::NOW + 1800]],
		]);
		list ($actions, $rows) = $this->plan ($s, [1 => SnapshotFactory::row (1, 50.0)]);
		$this->assertSame ([Dolumar_Npc_Action::SUPPORT], $this->types ($actions));
		$this->assertSame (11, $actions[0]->get ('target'));
		$this->assertEqualsWithDelta (0.5, $actions[0]->get ('fraction'), 0.0001);
		$this->assertSame (SnapshotFactory::NOW + Dolumar_Npc_Config::SUPPORT_SECONDS, $rows[1]['support_until']);

		// Nothing while the squad is promised or out.
		list ($actions) = $this->plan ($s, $rows);
		$this->assertSame ([], $actions);
		$rows[1]['support_squad'] = 77;
		list ($actions) = $this->plan ($s, $rows);
		$this->assertSame ([], $actions);

		// Recalled after a day.
		$s->now += Dolumar_Npc_Config::SUPPORT_SECONDS;
		list ($actions, $rows) = $this->plan ($s, $rows);
		$this->assertSame ([Dolumar_Npc_Action::RECALL_SUPPORT], $this->types ($actions));
		$this->assertSame (77, $actions[0]->get ('squad'));
		$this->assertSame (0, $rows[1]['support_squad']);
		$this->assertSame (0, $rows[1]['support_until']);
	}

	public function testNeutralPlayerGetsNoSupportAndNpcOwnAttackIsIgnored (): void
	{
		$s = SnapshotFactory::make ([
			'incoming' => [['battleId' => 500, 'fromId' => 20, 'targetId' => 11, 'fightDate' => SnapshotFactory::NOW + 1800]],
		]);
		list ($actions) = $this->plan ($s, [1 => SnapshotFactory::row (1, 10.0)]);
		$this->assertSame ([], $actions);

		$s->incoming[0]['fromId'] = SnapshotFactory::NPC_VID;
		list ($actions) = $this->plan ($s, [1 => SnapshotFactory::row (1, 50.0)]);
		$this->assertSame ([], $actions);
	}

	public function testAlliedStrikeOnlyOnFull (): void
	{
		$attack = ['fromId' => 20, 'fromPlid' => 2, 'targetId' => 10, 'targetPlid' => 1, 'date' => SnapshotFactory::NOW - 3600];
		$ally = SnapshotFactory::row (1, 90.0, ['pact' => 1]);

		$s = SnapshotFactory::make (['thirdPartyAttacks' => [$attack]]);
		list ($actions) = $this->plan ($s, [1 => $ally]);
		$this->assertSame ([], $actions, 'counter mode never strikes for an ally');

		$s = SnapshotFactory::make (['thirdPartyAttacks' => [$attack], 'aggression' => Dolumar_Npc_Config::AGGRESSION_FULL]);
		list ($actions, $rows) = $this->plan ($s, [1 => $ally]);
		$this->assertSame ([Dolumar_Npc_Action::ALLIED_STRIKE], $this->types ($actions));
		$this->assertSame (20, $actions[0]->get ('target'));
		$this->assertSame (2, $actions[0]->get ('attacker'));
		$this->assertSame (1, $actions[0]->get ('plid'));
		$this->assertSame (1, $rows[2]['npc_attacks_today']);

		// The same attack is not answered twice.
		list ($actions) = $this->plan ($s, $rows);
		$this->assertSame ([], $actions);

		// The attacker's protection applies.
		$s->neighbours[20]['playerCreated'] = SnapshotFactory::NOW - 86400;
		list ($actions) = $this->plan ($s, [1 => $ally]);
		$this->assertSame ([], $actions);
	}

	public function testRequestDeliveredEarnsThankYouSquad (): void
	{
		$s = SnapshotFactory::make ();
		$rows = [1 => SnapshotFactory::row (1, 30.0)];
		$transitions = [['kind' => 'request_delivered', 'plid' => 1, 'vid' => 11]];
		list ($actions, $rows) = $this->plan ($s, $rows, $transitions);
		$this->assertSame ([Dolumar_Npc_Action::THANK_YOU], $this->types ($actions));
		$this->assertSame (11, $actions[0]->get ('target'));
		$this->assertSame (Dolumar_Npc_Config::THANK_YOU_UNITS, $actions[0]->get ('units'));
		$this->assertSame (SnapshotFactory::NOW + Dolumar_Npc_Config::SUPPORT_SECONDS, $rows[1]['support_until']);
	}

	public function testEnvoyPrice (): void
	{
		$this->assertSame (500, Dolumar_Npc_Planner_War::envoyPrice (-5.0));
		$this->assertSame (2000, Dolumar_Npc_Planner_War::envoyPrice (-40.0));
		$this->assertSame (5000, Dolumar_Npc_Planner_War::envoyPrice (-100.0));
	}
}
