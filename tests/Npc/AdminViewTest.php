<?php

use PHPUnit\Framework\TestCase;

class AdminViewTest extends TestCase
{
	const T0 = 1700000000;

	private function tribe (array $over = [])
	{
		return array_merge (['vid' => 1, 'mul_threshold' => 1.0, 'mul_decay' => 1.0], $over);
	}

	private function row (array $over = [])
	{
		return array_merge (Dolumar_Npc_Standing::newRow (1, 2, self::T0), $over);
	}

	public function testNeutralRowWithoutLadder (): void
	{
		$view = Dolumar_Npc_AdminView::standing ($this->row (), $this->tribe (), self::T0);
		$this->assertSame (0.0, $view['value']);
		$this->assertSame ('neutral', $view['tier']);
		$this->assertSame ('-', $view['stage']);
		$this->assertSame ('', $view['truce']);
		$this->assertFalse ($view['pact']);
		$this->assertSame ('', $view['support']);
		$this->assertSame ('', $view['last_attack']);
	}

	public function testValueIsDecayedAndRounded (): void
	{
		// -50 two hours ago decays one point per hour.
		$row = $this->row (['standing' => -50.04, 'changed_at' => self::T0 - 7200]);
		$view = Dolumar_Npc_AdminView::standing ($row, $this->tribe (), self::T0);
		$this->assertSame (-48.0, $view['value']);
		$this->assertSame ('hostile', $view['tier']);
	}

	public function testTierUsesTheTribeMultipliers (): void
	{
		// -35 is wary at threshold 1.0 but hostile at 0.8 (hostile below -32).
		$row = $this->row (['standing' => -35.0]);
		$this->assertSame ('wary', Dolumar_Npc_AdminView::standing ($row, $this->tribe (), self::T0)['tier']);
		$this->assertSame ('hostile', Dolumar_Npc_AdminView::standing ($row, $this->tribe (['mul_threshold' => 0.8]), self::T0)['tier']);
	}

	public function testLadderStages (): void
	{
		$tribe = $this->tribe ();
		$this->assertSame ('scouts sent', Dolumar_Npc_AdminView::standing ($this->row (['scouts_at' => self::T0 - 60]), $tribe, self::T0)['stage']);
		$this->assertSame ('envoy sent, price 1500', Dolumar_Npc_AdminView::standing ($this->row (['scouts_at' => 1, 'envoy_at' => 2, 'envoy_price' => 1500]), $tribe, self::T0)['stage']);
		$this->assertSame ('war parties sent: 2', Dolumar_Npc_AdminView::standing ($this->row (['scouts_at' => 1, 'envoy_at' => 2, 'war_parties_sent' => 2]), $tribe, self::T0)['stage']);
	}

	public function testTruceWinsOverLadderAndShowsRemainingTime (): void
	{
		$row = $this->row (['truce_until' => self::T0 + 2 * 86400 + 3 * 3600, 'scouts_at' => 1]);
		$view = Dolumar_Npc_AdminView::standing ($row, $this->tribe (), self::T0);
		$this->assertSame ('truce', $view['stage']);
		$this->assertSame ('2d 3h', $view['truce']);
	}

	public function testExpiredTruceAndSupportAreEmpty (): void
	{
		$row = $this->row (['truce_until' => self::T0 - 1, 'support_until' => self::T0 - 1, 'support_squad' => 5]);
		$view = Dolumar_Npc_AdminView::standing ($row, $this->tribe (), self::T0);
		$this->assertSame ('', $view['truce']);
		$this->assertSame ('', $view['support']);
	}

	public function testActiveSupportPactAndLastAttack (): void
	{
		$row = $this->row (['support_squad' => 5, 'support_until' => self::T0 + 1800, 'pact' => 1, 'last_attack_at' => self::T0 - 90]);
		$view = Dolumar_Npc_AdminView::standing ($row, $this->tribe (), self::T0);
		$this->assertSame ('30m', $view['support']);
		$this->assertTrue ($view['pact']);
		$this->assertSame ('1m ago', $view['last_attack']);
	}

	public function testDuration (): void
	{
		$this->assertSame ('0m', Dolumar_Npc_AdminView::duration (0));
		$this->assertSame ('0m', Dolumar_Npc_AdminView::duration (-50));
		$this->assertSame ('59m', Dolumar_Npc_AdminView::duration (3599));
		$this->assertSame ('1h 0m', Dolumar_Npc_AdminView::duration (3600));
		$this->assertSame ('5h 7m', Dolumar_Npc_AdminView::duration (5 * 3600 + 7 * 60 + 30));
		$this->assertSame ('3d 0h', Dolumar_Npc_AdminView::duration (3 * 86400));
	}
}
