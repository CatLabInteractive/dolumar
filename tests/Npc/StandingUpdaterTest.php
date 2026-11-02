<?php

use PHPUnit\Framework\TestCase;

class StandingUpdaterTest extends TestCase
{
	private function kinds (array $transitions)
	{
		return array_map (function ($t) {
			return $t['kind'];
		}, $transitions);
	}

	public function testAttackByPlayerCreatesRowAndLowersStanding (): void
	{
		$s = SnapshotFactory::make ([
			'reports' => [SnapshotFactory::report (5, 10, 1, SnapshotFactory::NPC_VID, 0, 1.0)],
		]);
		$result = Dolumar_Npc_StandingUpdater::apply ($s);

		$this->assertArrayHasKey (1, $result['rows']);
		$row = $result['rows'][1];
		$this->assertEqualsWithDelta (-40.0, $row['standing'], 0.001);
		$this->assertSame (10, $row['last_attack_vid']);
		$this->assertSame (5, $result['last_report_id']);
		$this->assertSame (0, $result['last_transfer_id']);
		$this->assertContains ('attacked', $this->kinds ($result['transitions']));
		$this->assertContains (['kind' => 'tier', 'plid' => 1, 'from' => 'neutral', 'to' => 'wary'], $result['transitions']);
	}

	public function testReportsBelowLastProcessedIdAreIgnored (): void
	{
		$tribe = SnapshotFactory::make ()->tribe;
		$tribe['last_report_id'] = 5;
		$s = SnapshotFactory::make ([
			'tribe' => $tribe,
			'reports' => [
				SnapshotFactory::report (5, 10, 1, SnapshotFactory::NPC_VID, 0, 1.0),
				SnapshotFactory::report (6, 10, 1, SnapshotFactory::NPC_VID, 0, 0.0),
			],
		]);
		$result = Dolumar_Npc_StandingUpdater::apply ($s);
		$this->assertEqualsWithDelta (-15.0, $result['rows'][1]['standing'], 0.001);
		$this->assertSame (6, $result['last_report_id']);

		// Running the same snapshot again with the new id changes nothing.
		$s->tribe['last_report_id'] = $result['last_report_id'];
		$s->standings = $result['rows'];
		$again = Dolumar_Npc_StandingUpdater::apply ($s);
		$this->assertEqualsWithDelta (-15.0, $again['rows'][1]['standing'], 0.001);
		$this->assertSame ([], $again['transitions']);
	}

	public function testDefeatedWarPartyMakesPeace (): void
	{
		$s = SnapshotFactory::make ([
			'standings' => [1 => SnapshotFactory::row (1, -80.0, ['last_npc_battle_id' => 77, 'war_parties_sent' => 1])],
			'reports' => [SnapshotFactory::report (9, SnapshotFactory::NPC_VID, 0, 10, 1, 0.2, SnapshotFactory::NOW, 77)],
		]);
		$result = Dolumar_Npc_StandingUpdater::apply ($s);
		$row = $result['rows'][1];
		$this->assertEqualsWithDelta (-40.0, $row['standing'], 0.001);
		$this->assertTrue (Dolumar_Npc_Standing::inTruce ($row, SnapshotFactory::NOW + 1));
		$this->assertContains (['kind' => 'peace', 'plid' => 1, 'by' => 'defence'], $result['transitions']);
	}

	public function testWonWarPartyOrScoutRaidDoesNotMakePeace (): void
	{
		$s = SnapshotFactory::make ([
			'standings' => [1 => SnapshotFactory::row (1, -80.0, ['last_npc_battle_id' => 77])],
			'reports' => [
				// won war party
				SnapshotFactory::report (9, SnapshotFactory::NPC_VID, 0, 10, 1, 0.9, SnapshotFactory::NOW, 77),
				// lost scout raid (another battle id)
				SnapshotFactory::report (10, SnapshotFactory::NPC_VID, 0, 10, 1, 0.0, SnapshotFactory::NOW, 78),
			],
		]);
		$result = Dolumar_Npc_StandingUpdater::apply ($s);
		$this->assertEqualsWithDelta (-80.0, $result['rows'][1]['standing'], 0.001);
		$this->assertNotContains ('peace', $this->kinds ($result['transitions']));
	}

	public function testPaymentIsNotAlsoAGift (): void
	{
		$s = SnapshotFactory::make ([
			'standings' => [1 => SnapshotFactory::row (1, -60.0, ['envoy_at' => SnapshotFactory::NOW - 100, 'envoy_price' => 1500])],
			'transfers' => [SnapshotFactory::transfer (3, 10, 1, ['wood' => 1000, 'stone' => 600])],
		]);
		$result = Dolumar_Npc_StandingUpdater::apply ($s);
		$row = $result['rows'][1];
		$this->assertEqualsWithDelta (-40.0, $row['standing'], 0.001);
		$this->assertSame (0, $row['gift_best']);
		$this->assertSame (3, $result['last_transfer_id']);
		$this->assertContains (['kind' => 'peace', 'plid' => 1, 'by' => 'payment'], $result['transitions']);

		// Too little is just a gift.
		$s->standings[1]['envoy_price'] = 5000;
		$result = Dolumar_Npc_StandingUpdater::apply ($s);
		$this->assertSame (1600, $result['rows'][1]['gift_best']);
		$this->assertNotContains ('peace', $this->kinds ($result['transitions']));
	}

	public function testRequestDeliveryGivesThirtyAndTransition (): void
	{
		$tribe = SnapshotFactory::make ()->tribe;
		$tribe['request_res'] = 'stone';
		$tribe['request_amount'] = 800;
		$s = SnapshotFactory::make ([
			'tribe' => $tribe,
			'transfers' => [SnapshotFactory::transfer (3, 10, 1, ['stone' => 800])],
		]);
		$result = Dolumar_Npc_StandingUpdater::apply ($s);
		$this->assertEqualsWithDelta (30.0, $result['rows'][1]['standing'], 0.001);
		$this->assertTrue ($result['request_delivered']);
		$this->assertContains (['kind' => 'request_delivered', 'plid' => 1, 'vid' => 10], $result['transitions']);

		// Within the cooldown a second delivery is only a gift.
		$s->standings = $result['rows'];
		$s->tribe['last_transfer_id'] = 3;
		$s->transfers = [SnapshotFactory::transfer (4, 10, 1, ['stone' => 800], SnapshotFactory::NOW + 60)];
		$result = Dolumar_Npc_StandingUpdater::apply ($s);
		$this->assertFalse ($result['request_delivered']);
		$this->assertSame (800, $result['rows'][1]['gift_best']);
	}

	public function testOtherTransferIsAGift (): void
	{
		$s = SnapshotFactory::make ([
			'transfers' => [SnapshotFactory::transfer (3, 10, 1, ['gold' => 2500])],
		]);
		$result = Dolumar_Npc_StandingUpdater::apply ($s);
		// hoard is 5000: 5 + 10 * 0.5
		$this->assertEqualsWithDelta (10.0, $result['rows'][1]['standing'], 0.001);
		$this->assertSame (2500, $result['rows'][1]['gift_best']);
	}

	public function testTransfersFromNpcVillagesAreIgnored (): void
	{
		$s = SnapshotFactory::make ([
			'transfers' => [SnapshotFactory::transfer (3, 101, 0, ['gold' => 2500])],
		]);
		$result = Dolumar_Npc_StandingUpdater::apply ($s);
		$this->assertSame ([], $result['rows']);
		$this->assertSame (3, $result['last_transfer_id']);
	}

	public function testAlliedRowAttackedBreaksPact (): void
	{
		$s = SnapshotFactory::make ([
			'standings' => [1 => SnapshotFactory::row (1, 90.0, ['pact' => 1])],
			'reports' => [SnapshotFactory::report (5, 10, 1, SnapshotFactory::NPC_VID, 0, 0.5)],
		]);
		$result = Dolumar_Npc_StandingUpdater::apply ($s);
		$this->assertSame (0, $result['rows'][1]['pact']);
		$this->assertContains (['kind' => 'pact_broken', 'plid' => 1], $result['transitions']);
		$this->assertContains (['kind' => 'tier', 'plid' => 1, 'from' => 'allied', 'to' => 'friendly'], $result['transitions']);
	}

	public function testReachingAlliedMakesPact (): void
	{
		$s = SnapshotFactory::make ([
			'standings' => [1 => SnapshotFactory::row (1, 65.0)],
			'transfers' => [SnapshotFactory::transfer (3, 10, 1, ['gold' => 5000])],
		]);
		$result = Dolumar_Npc_StandingUpdater::apply ($s);
		$this->assertSame (1, $result['rows'][1]['pact']);
		$this->assertContains (['kind' => 'pact', 'plid' => 1], $result['transitions']);
	}

	public function testEventsApplyInDateOrder (): void
	{
		$s = SnapshotFactory::make ([
			'reports' => [SnapshotFactory::report (5, 10, 1, SnapshotFactory::NPC_VID, 0, 1.0, SnapshotFactory::NOW - 7200)],
			'transfers' => [SnapshotFactory::transfer (3, 10, 1, ['gold' => 500], SnapshotFactory::NOW - 3600)],
		]);
		$result = Dolumar_Npc_StandingUpdater::apply ($s);
		// -40, one hour of decay (+1), then a gift of 5 + 10 * 0.1 = 6: changed_at is the transfer's date.
		$this->assertSame (SnapshotFactory::NOW - 3600, $result['rows'][1]['changed_at']);
		$this->assertEqualsWithDelta (-33.0, $result['rows'][1]['standing'], 0.001);
	}
}
