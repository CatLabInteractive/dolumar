<?php

use PHPUnit\Framework\TestCase;

class StandingTest extends TestCase
{
	const T0 = 1700000000;

	private function row ($standing = 0.0, $changedAt = self::T0)
	{
		$row = Dolumar_Npc_Standing::newRow (1, 2, $changedAt);
		$row['standing'] = $standing;
		return $row;
	}

	public function testNewRowIsNeutral (): void
	{
		$row = Dolumar_Npc_Standing::newRow (7, 9, self::T0);
		$this->assertSame (7, $row['vid']);
		$this->assertSame (9, $row['plid']);
		$this->assertSame (0.0, $row['standing']);
		$this->assertSame (self::T0, $row['changed_at']);
		$this->assertSame ('neutral', Dolumar_Npc_Standing::tier ($row, self::T0));
		$this->assertSame (0, $row['pact']);
	}

	public function testDecayHostileSideOnePointPerHour (): void
	{
		$row = $this->row (-50.0);
		$this->assertEqualsWithDelta (-50.0, Dolumar_Npc_Standing::valueAt ($row, self::T0), 0.001);
		$this->assertEqualsWithDelta (-40.0, Dolumar_Npc_Standing::valueAt ($row, self::T0 + 10 * 3600), 0.001);
		$this->assertEqualsWithDelta (0.0, Dolumar_Npc_Standing::valueAt ($row, self::T0 + 100 * 3600), 0.001);
		// mul_decay 2.0 doubles the speed
		$this->assertEqualsWithDelta (-30.0, Dolumar_Npc_Standing::valueAt ($row, self::T0 + 10 * 3600, 2.0), 0.001);
	}

	public function testDecayFriendlySideOnePointPerFourHours (): void
	{
		$row = $this->row (50.0);
		$this->assertEqualsWithDelta (40.0, Dolumar_Npc_Standing::valueAt ($row, self::T0 + 40 * 3600), 0.001);
		$this->assertEqualsWithDelta (0.0, Dolumar_Npc_Standing::valueAt ($row, self::T0 + 1000 * 3600), 0.001);
	}

	public function testAttackPenaltyFormula (): void
	{
		$row = $this->row ();
		$row = Dolumar_Npc_Standing::applyAttack ($row, self::T0, 1.0);
		$this->assertEqualsWithDelta (40.0, $row['last_penalty'], 0.001);
		$this->assertEqualsWithDelta (-40.0, $row['standing'], 0.001);
		$this->assertSame (1, $row['attacks_in_window']);

		$row = Dolumar_Npc_Standing::applyAttack ($row, self::T0 + 3600, 1.0);
		$this->assertEqualsWithDelta (60.0, $row['last_penalty'], 0.001);
		// one hour of decay (+1) then -60
		$this->assertEqualsWithDelta (-99.0, $row['standing'], 0.001);
		$this->assertSame (2, $row['attacks_in_window']);

		$row = Dolumar_Npc_Standing::applyAttack ($this->row (), self::T0, 0.0);
		$this->assertEqualsWithDelta (15.0, $row['last_penalty'], 0.001);
	}

	public function testWindowResets (): void
	{
		$row = Dolumar_Npc_Standing::applyAttack ($this->row (), self::T0, 1.0);
		$row = Dolumar_Npc_Standing::applyAttack ($row, self::T0 + Dolumar_Npc_Config::ATTACK_WINDOW + 1, 1.0);
		$this->assertEqualsWithDelta (40.0, $row['last_penalty'], 0.001);
		$this->assertSame (1, $row['attacks_in_window']);
	}

	public function testAttackBreaksPact (): void
	{
		$row = $this->row (90.0);
		$row['pact'] = 1;
		$row = Dolumar_Npc_Standing::applyAttack ($row, self::T0, 0.5);
		$this->assertSame (0, $row['pact']);
	}

	public function testGiftOnlyBestInWindowCounts (): void
	{
		$row = $this->row ();
		$row = Dolumar_Npc_Standing::applyGift ($row, self::T0, 500, 1000);
		// 5 + 10 * 0.5
		$this->assertEqualsWithDelta (10.0, $row['standing'], 0.001);
		$this->assertSame (500, $row['gift_best']);

		// A smaller gift in the same window adds nothing.
		$row = Dolumar_Npc_Standing::applyGift ($row, self::T0, 200, 1000);
		$this->assertEqualsWithDelta (10.0, $row['standing'], 0.001);

		// A bigger gift replaces the earlier one: total equals the points of the best gift (5 + 10 * 1).
		$row = Dolumar_Npc_Standing::applyGift ($row, self::T0, 2000, 1000);
		$this->assertEqualsWithDelta (15.0, $row['standing'], 0.001);
		$this->assertSame (2000, $row['gift_best']);

		// After the window a new gift counts in full again (minus a bit of decay).
		$later = self::T0 + Dolumar_Npc_Config::GIFT_WINDOW + 3600;
		$row = Dolumar_Npc_Standing::applyGift ($row, $later, 500, 1000);
		$this->assertSame (500, $row['gift_best']);
		$this->assertEqualsWithDelta (Dolumar_Npc_Standing::valueAt ($row, $later), $row['standing'], 0.001);
		$this->assertEqualsWithDelta (10.0, $row['standing'], 0.001);
	}

	public function testRequestDeliveredPlus30 (): void
	{
		$row = Dolumar_Npc_Standing::applyRequestDelivered ($this->row (), self::T0 + 10);
		$this->assertEqualsWithDelta (30.0, $row['standing'], 0.001);
		$this->assertSame (self::T0 + 10, $row['request_delivered_at']);
	}

	public function testPeaceSetsWaryFloorAndTruce (): void
	{
		$row = $this->row (-90.0);
		$row['scouts_at'] = 5;
		$row['envoy_at'] = 6;
		$row['envoy_price'] = 700;
		$row['war_parties_sent'] = 2;
		$row = Dolumar_Npc_Standing::makePeace ($row, self::T0, 1.0);
		$this->assertEqualsWithDelta (-40.0, $row['standing'], 0.001);
		$this->assertSame (self::T0 + Dolumar_Npc_Config::TRUCE_SECONDS, $row['truce_until']);
		$this->assertTrue (Dolumar_Npc_Standing::inTruce ($row, self::T0 + 1));
		$this->assertFalse (Dolumar_Npc_Standing::inTruce ($row, self::T0 + Dolumar_Npc_Config::TRUCE_SECONDS));
		$this->assertSame (0, $row['scouts_at']);
		$this->assertSame (0, $row['envoy_at']);
		$this->assertSame (0, $row['envoy_price']);
		$this->assertSame (0, $row['war_parties_sent']);
		$this->assertSame ('wary', Dolumar_Npc_Standing::tier ($row, self::T0));

		$row = Dolumar_Npc_Standing::makePeace ($this->row (-90.0), self::T0, 1.2);
		$this->assertEqualsWithDelta (-48.0, $row['standing'], 0.001);
	}

	public function testLootFactor (): void
	{
		$this->assertEqualsWithDelta (1.0, Dolumar_Npc_Standing::lootFactor ($this->row (0.0), self::T0), 0.001);
		$this->assertEqualsWithDelta (0.25, Dolumar_Npc_Standing::lootFactor ($this->row (-100.0), self::T0), 0.001);
		$this->assertEqualsWithDelta (0.625, Dolumar_Npc_Standing::lootFactor ($this->row (-50.0), self::T0), 0.001);
		$this->assertEqualsWithDelta (1.0, Dolumar_Npc_Standing::lootFactor ($this->row (50.0), self::T0), 0.001);

		$row = Dolumar_Npc_Standing::makePeace ($this->row (-90.0), self::T0, 1.0);
		$this->assertEqualsWithDelta (0.0, Dolumar_Npc_Standing::lootFactor ($row, self::T0 + 10), 0.001);
	}

	public function testTiersWithMultiplier (): void
	{
		$this->assertSame ('hostile', Dolumar_Npc_Standing::tier ($this->row (-41.0), self::T0));
		$this->assertSame ('wary', Dolumar_Npc_Standing::tier ($this->row (-40.0), self::T0));
		$this->assertSame ('wary', Dolumar_Npc_Standing::tier ($this->row (-11.0), self::T0));
		$this->assertSame ('neutral', Dolumar_Npc_Standing::tier ($this->row (-10.0), self::T0));
		$this->assertSame ('neutral', Dolumar_Npc_Standing::tier ($this->row (30.0), self::T0));
		$this->assertSame ('friendly', Dolumar_Npc_Standing::tier ($this->row (31.0), self::T0));
		$this->assertSame ('friendly', Dolumar_Npc_Standing::tier ($this->row (70.0), self::T0));
		$this->assertSame ('allied', Dolumar_Npc_Standing::tier ($this->row (71.0), self::T0));

		// With mul_threshold 1.2 the hostile line sits at -48 and allied at 84.
		$this->assertSame ('wary', Dolumar_Npc_Standing::tier ($this->row (-45.0), self::T0, 1.2));
		$this->assertSame ('friendly', Dolumar_Npc_Standing::tier ($this->row (80.0), self::T0, 1.2));

		// Decay is taken into account: -41 is wary again an hour later.
		$this->assertSame ('wary', Dolumar_Npc_Standing::tier ($this->row (-41.0), self::T0 + 7200));
	}

	public function testClamp (): void
	{
		$this->assertSame (100.0, Dolumar_Npc_Standing::clamp (250));
		$this->assertSame (-100.0, Dolumar_Npc_Standing::clamp (-250));
		$this->assertSame (12.5, Dolumar_Npc_Standing::clamp (12.5));

		$row = Dolumar_Npc_Standing::applyAttack ($this->row (-90.0), self::T0, 1.0);
		$this->assertSame (-100.0, $row['standing']);

		$row = $this->row (95.0);
		$row = Dolumar_Npc_Standing::applyRequestDelivered ($row, self::T0);
		$this->assertSame (100.0, $row['standing']);
	}
}
