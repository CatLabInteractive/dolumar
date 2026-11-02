<?php

use PHPUnit\Framework\TestCase;

class ArmyFactoryTest extends TestCase
{
	private function row(array $o = []): array
	{
		return $o + ['battleId' => 7, 'vid' => 1, 'targetId' => 2, 'startDate' => 1000, 'arriveDate' => 1100,
			'fightDate' => 1100, 'endFightDate' => null, 'endDate' => 1234, 'isFought' => 0, 'route' => null,
			'fromX' => 0, 'fromY' => 0, 'toX' => 10, 'toY' => 0, 'fromName' => 'A', 'toName' => 'B'];
	}

	public function testStraightMarchOut(): void
	{
		$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(), 1050);
		$this->assertSame('b:7', $a->getUOID());
		$this->assertSame('attack', $a->getKind());
		$this->assertSame('A → B', $a->getName());
		$this->assertEqualsWithDelta(5.0, $a->getLocation(1050)->x(), 0.001);
		$this->assertSame("openWindow ('battle', {'vid': 1});", implode(' ', $a->getEvents('click')));
		$this->assertStringEndsWith('images/sprites/armies/attack.png', $a->getDisplayObject()->getURI());
	}

	public function testPortalRouteJumpsAtPortal(): void
	{
		$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(['route' => '[[0,0],[10,0],[100,0],[110,0]]', 'toX' => 110]), 1050);
		// two 10-tile segments share 100 s equally: at 1049 still on segment one, at 1051 on segment two
		$this->assertLessThan(11, $a->getLocation(1049)->x());
		$this->assertGreaterThan(99, $a->getLocation(1051)->x());
	}

	public function testOverdueUnprocessedFightWaitsAtTarget(): void
	{
		$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(), 1500, 30);
		$this->assertSame('waiting', $a->getKind());
		$this->assertEqualsWithDelta(10.0, $a->getLocation(1500)->x(), 0.001);
		$m = $a->getMovements();
		$this->assertSame(1530, end($m)->getEndTime());
	}

	public function testFoughtArmyReturnsHome(): void
	{
		$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(['isFought' => 1, 'endFightDate' => 1200, 'endDate' => 1333]), 1266);
		$this->assertSame('returning', $a->getKind());
		$this->assertEqualsWithDelta(5.0, $a->getLocation(1266)->x(), 0.05);
	}

	public function testWipedOutArmyHasNoReturn(): void
	{
		$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(['isFought' => 1, 'endFightDate' => 1200, 'endDate' => 1201]), 1150);
		$ends = array_map(fn ($m) => $m->getEndTime(), $a->getMovements());
		$this->assertSame(1200, max($ends));
	}

	public function testSquadMove(): void
	{
		$a = Dolumar_NewMap_ArmyFactory::fromSquadRow(['sc_id' => 3, 's_id' => 9, 's_from' => 1, 's_to' => 2, 's_start' => 1000, 's_end' => 1100,
			'sc_route' => null, 'fromX' => 0, 'fromY' => 0, 'toX' => 0, 'toY' => 10, 'squadName' => 'First', 'toName' => 'B', 'homeVid' => 1], 1050);
		$this->assertSame('s:3', $a->getUOID());
		$this->assertSame('squad', $a->getKind());
		$this->assertSame('First → B', $a->getName());
		$this->assertEqualsWithDelta(5.0, $a->getLocation(1050)->y(), 0.001);
		$this->assertSame("openWindow ('squads', {'vid': 1});", implode(' ', $a->getEvents('click')));
	}

	public function testMissingTownCentreGivesNull(): void
	{
		$this->assertNull(Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(['toX' => null]), 1050));
	}

	public function testRelatedness(): void
	{
		$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(), 1050);
		$this->assertTrue($a->isRelatedTo([2, 5]));
		$this->assertFalse($a->isRelatedTo([5]));
		$this->assertFalse($a->isRelatedTo(null));
	}

	public function testBadRouteJsonFallsBackToStraightLine(): void
	{
		$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(['route' => '{not json']), 1050);
		$this->assertEqualsWithDelta(5.0, $a->getLocation(1050)->x(), 0.001);
	}

	public function testSegmentsSplitPortalRoutes(): void
	{
		$this->assertSame([[[0, 0], [3, 4]]], Dolumar_NewMap_ArmyFactory::segments([[0, 0], [3, 4]]));
		$this->assertSame([[[0, 0], [1, 0]], [[5, 0], [9, 0]]], Dolumar_NewMap_ArmyFactory::segments([[0, 0], [1, 0], [5, 0], [9, 0]]));
	}
}
