<?php

use PHPUnit\Framework\TestCase;

class ArmyPlannerTest extends TestCase
{
	private function barrack ($spearmen = 20, $longswords = 20)
	{
		return [
			'class' => 'Dolumar_Buildings_Barrack',
			'units' => [
				['unitId' => 1, 'class' => 'Dolumar_Units_Spearmen', 'amount' => $spearmen, 'melee' => 45, 'defIn' => 30, 'defCav' => 60],
				['unitId' => 2, 'class' => 'Dolumar_Units_Longswordsmen', 'amount' => $longswords, 'melee' => 80, 'defIn' => 40, 'defCav' => 40],
			],
		];
	}

	public function testTrainsTheUnitTheArmyHasLeastOf (): void
	{
		// The army has 100 spearmen and no longswordsmen.
		$s = SnapshotFactory::make (['training' => [55 => $this->barrack ()]]);
		$actions = Dolumar_Npc_Planner_Army::plan ($s);
		$this->assertCount (1, $actions);
		$this->assertSame (Dolumar_Npc_Action::TRAIN, $actions[0]->type);
		$this->assertSame (55, $actions[0]->get ('building'));
		$this->assertSame ('Dolumar_Units_Longswordsmen', $actions[0]->get ('unit'));
		$this->assertSame (20, $actions[0]->get ('amount'));
	}

	public function testRespectsRemainingCapacity (): void
	{
		$s = SnapshotFactory::make (['training' => [55 => $this->barrack (20, 0)]]);
		$actions = Dolumar_Npc_Planner_Army::plan ($s);
		$this->assertCount (1, $actions);
		$this->assertSame ('Dolumar_Units_Spearmen', $actions[0]->get ('unit'));

		$s = SnapshotFactory::make (['training' => [55 => $this->barrack (0, 0)]]);
		$this->assertSame ([], Dolumar_Npc_Planner_Army::plan ($s));
	}

	public function testWaryRowsPreferDefensiveStats (): void
	{
		// Spearmen defend better (90) than longswordsmen (80) even though the army has plenty of them.
		$s = SnapshotFactory::make (['training' => [55 => $this->barrack ()]]);
		$rows = [1 => SnapshotFactory::row (1, -30.0)];
		$actions = Dolumar_Npc_Planner_Army::plan ($s, $rows);
		$this->assertSame ('Dolumar_Units_Spearmen', $actions[0]->get ('unit'));
	}

	public function testTwoBuildingsRotateThroughTypes (): void
	{
		$s = SnapshotFactory::make ([
			'units' => [],
			'training' => [55 => $this->barrack (10, 10), 56 => $this->barrack (10, 10)],
		]);
		$actions = Dolumar_Npc_Planner_Army::plan ($s);
		$this->assertCount (2, $actions);
		$this->assertNotSame ($actions[0]->get ('unit'), $actions[1]->get ('unit'));
	}
}
