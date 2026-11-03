<?php

use PHPUnit\Framework\TestCase;

class EconomyPlannerTest extends TestCase
{
	private function builds (array $actions)
	{
		$out = [];
		foreach ($actions as $action) {
			if ($action->type === Dolumar_Npc_Action::BUILD) {
				$out[] = $action;
			}
		}
		return $out;
	}

	private function types (array $actions)
	{
		return array_map (function ($a) {
			return $a->type;
		}, $actions);
	}

	public function testNoBuildBelowReserveFloor (): void
	{
		// 5 used runes * 400 = 2000; resources sum to 1500.
		$s = SnapshotFactory::make (['resources' => ['gold' => 300, 'wood' => 300, 'stone' => 300, 'iron' => 300, 'grain' => 300, 'gems' => 0]]);
		$this->assertSame ([], $this->builds (Dolumar_Npc_Planner_Economy::plan ($s)));

		$s->resources['gold'] = 1000;
		$this->assertNotEmpty ($this->builds (Dolumar_Npc_Planner_Economy::plan ($s)));

		// mul_hoard raises the floor.
		$s->tribe['mul_hoard'] = 1.2;
		$this->assertSame ([], $this->builds (Dolumar_Npc_Planner_Economy::plan ($s)));
	}

	public function testNoBuildWithTwoUnderConstruction (): void
	{
		$s = SnapshotFactory::make (['constructing' => 2]);
		$this->assertSame ([], $this->builds (Dolumar_Npc_Planner_Economy::plan ($s)));
	}

	public function testSkipsBuildingsWhoseRuneIsUnavailable (): void
	{
		$s = SnapshotFactory::make ();
		$s->runes['available'] = ['water' => 3];
		$s->runes['availableAmount'] = 3;
		foreach ($this->builds (Dolumar_Npc_Planner_Economy::plan ($s)) as $build) {
			$this->assertSame ('water', $build->get ('rune'), $build->get ('building'));
		}
	}

	public function testRandomRuneBuildingTakesTheMostPlentifulRune (): void
	{
		$s = SnapshotFactory::make ();
		$s->runes['available'] = ['water' => 1, 'wind' => 4, 'earth' => 1, 'fire' => 1];
		$s->runes['availableAmount'] = 7;
		$builds = $this->builds (Dolumar_Npc_Planner_Economy::plan ($s));
		$this->assertNotEmpty ($builds);
		$silo = null;
		foreach ($builds as $build) {
			if ($build->get ('building') === 'Dolumar_Buildings_Silo') {
				$silo = $build;
			}
		}
		$this->assertNotNull ($silo, 'the silo (0 of 0.8 desired) comes first');
		$this->assertSame ('wind', $silo->get ('rune'));
	}

	public function testSkipsBuildingsThatCannotBeBuiltYet (): void
	{
		$s = SnapshotFactory::make ();
		$s->buildings['Dolumar_Buildings_Silo']['canBuild'] = false;
		foreach ($this->builds (Dolumar_Npc_Planner_Economy::plan ($s)) as $build) {
			$this->assertNotSame ('Dolumar_Buildings_Silo', $build->get ('building'));
		}
	}

	public function testDefensiveRatioWhenARowIsWary (): void
	{
		$s = SnapshotFactory::make ();
		$calm = Dolumar_Npc_Planner_Economy::buildOrder ($s, Dolumar_Npc_Planner_Economy::$ratios);
		$this->assertNotSame ('Dolumar_Buildings_Tower', $calm[0]);

		$rows = [1 => SnapshotFactory::row (1, -20.0)];
		$this->assertTrue (Dolumar_Npc_Planner_Economy::isThreatened ($s, $rows));
		$builds = $this->builds (Dolumar_Npc_Planner_Economy::plan ($s, $rows));
		$this->assertSame ('Dolumar_Buildings_Tower', $builds[0]->get ('building'));

		$this->assertFalse (Dolumar_Npc_Planner_Economy::isThreatened ($s, [1 => SnapshotFactory::row (1, 20.0)]));
	}

	public function testBuildOrderPrefersTheMostUnderbuiltType (): void
	{
		$s = SnapshotFactory::make ();
		$order = Dolumar_Npc_Planner_Economy::buildOrder ($s, Dolumar_Npc_Planner_Economy::$ratios);
		// Nothing of silo (0.8), archery (0.6), stable (0.5), tower (0.4): silo is the most underbuilt.
		$this->assertSame ('Dolumar_Buildings_Silo', $order[0]);
		$this->assertCount (count (Dolumar_Npc_Planner_Economy::$ratios), $order);
	}

	public function testScoutRules (): void
	{
		$s = SnapshotFactory::make ();
		$this->assertContains (Dolumar_Npc_Action::SCOUT, $this->types (Dolumar_Npc_Planner_Economy::plan ($s)));

		$s = SnapshotFactory::make (['scouting' => true]);
		$this->assertNotContains (Dolumar_Npc_Action::SCOUT, $this->types (Dolumar_Npc_Planner_Economy::plan ($s)));

		$s = SnapshotFactory::make ();
		$s->runes['availableAmount'] = Dolumar_Npc_Config::MAX_AVAILABLE_RUNES + 1;
		$this->assertNotContains (Dolumar_Npc_Action::SCOUT, $this->types (Dolumar_Npc_Planner_Economy::plan ($s)));

		// Growth cap: 60 total runes against a strongest neighbour of 40 (60 / 1.2 = 50 > 40).
		$s = SnapshotFactory::make ();
		$s->runes['total'] = 60;
		$this->assertNotContains (Dolumar_Npc_Action::SCOUT, $this->types (Dolumar_Npc_Planner_Economy::plan ($s)));
		$s->runes['total'] = 48;
		$this->assertContains (Dolumar_Npc_Action::SCOUT, $this->types (Dolumar_Npc_Planner_Economy::plan ($s)));
	}

	public function testRequestIsPostedWhenMissingOrStale (): void
	{
		$s = SnapshotFactory::make ();
		$s->resources = ['gold' => 1000, 'wood' => 1000, 'stone' => 200, 'iron' => 1000, 'grain' => 1000, 'gems' => 0];
		$action = Dolumar_Npc_Planner_Economy::request ($s);
		$this->assertNotNull ($action);
		$this->assertSame (Dolumar_Npc_Action::REQUEST, $action->type);
		$this->assertSame ('stone', $action->get ('res'));
		$this->assertSame (1050, $action->get ('amount'));

		$s->tribe['request_res'] = 'stone';
		$s->tribe['request_amount'] = 1050;
		$s->tribe['request_created'] = SnapshotFactory::NOW - 3600;
		$this->assertNull (Dolumar_Npc_Planner_Economy::request ($s));

		$s->tribe['request_created'] = SnapshotFactory::NOW - Dolumar_Npc_Config::REQUEST_COOLDOWN;
		$this->assertNotNull (Dolumar_Npc_Planner_Economy::request ($s));
	}
}
