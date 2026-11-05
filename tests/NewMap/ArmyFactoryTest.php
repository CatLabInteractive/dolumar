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

	public function testNeutralSpriteDoesNotNameTheKind(): void
	{
		$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(), 1050);
		$this->assertStringEndsWith('images/sprites/armies/neutral.png', $a->getNeutralDisplayObject()->getURI());
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

	public function testFocusScriptCentresTheMapOnTheArmy(): void
	{
		$this->assertSame("Game.dolumar.focusArmy(event, 'b:7', 10, -3);", Dolumar_NewMap_ArmyFactory::focusScript(Dolumar_NewMap_ArmyFactory::battleId(7), [10, -3]));
		$this->assertSame("Game.dolumar.focusArmy(event, 's:12', 0, 0);", Dolumar_NewMap_ArmyFactory::focusScript(Dolumar_NewMap_ArmyFactory::squadId('12'), [0, 0]));
	}

	public function testFocusScriptIsSafeInAnHtmlAttribute(): void
	{
		$js = Dolumar_NewMap_ArmyFactory::focusScript("b:1'\"<x>", ['1.5', 'y']);
		$this->assertStringNotContainsString('"', $js);
		$this->assertStringNotContainsString('<', $js);
		$this->assertStringContainsString(', 1.5, 0);', $js);
	}

	public function testIdsMatchTheExportedObjects(): void
	{
		$this->assertSame(Dolumar_NewMap_ArmyFactory::battleId(7), Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(), 1050)->getUOID());
	}

	public function testRecallRouteStartsWhereTheRegimentTurnedAround(): void
	{
		// 0,0 -> 10,0 between 1000 and 1100; recalled at 1030: back from 3,0 to 0,0
		$route = Dolumar_NewMap_ArmyFactory::recallRoute(null, [0, 0], [10, 0], 1000, 1100, 1030);
		$this->assertEqualsWithDelta([[3, 0], [0, 0]], $route, 0.0001);
	}

	public function testRecallRouteWalksBackAlongTheWaypoints(): void
	{
		// two equal legs 0,0 -> 10,0 -> 10,10 (odd point count = walked point to point); at 3/4 of the time
		$route = Dolumar_NewMap_ArmyFactory::recallRoute('[[0,0],[10,0],[10,10]]', [0, 0], [10, 10], 1000, 1200, 1150);
		$this->assertEqualsWithDelta([[10, 5], [10, 0], [10, 0], [0, 0]], $route, 0.0001);
	}

	public function testRecallRouteJumpsBackThroughAPortal(): void
	{
		// portal pairs: walk 0,0->10,0, jump to 100,0, walk to 110,0; recalled on the second leg
		$route = Dolumar_NewMap_ArmyFactory::recallRoute('[[0,0],[10,0],[100,0],[110,0]]', [0, 0], [110, 0], 1000, 1100, 1075);
		$this->assertEqualsWithDelta([[105, 0], [100, 0], [10, 0], [0, 0]], $route, 0.0001);
	}

	public function testRecallRouteBeforeLeavingStaysHome(): void
	{
		$this->assertEqualsWithDelta([[0, 0], [0, 0]], Dolumar_NewMap_ArmyFactory::recallRoute(null, [0, 0], [10, 0], 1000, 1100, 1000), 0.0001);
	}

	public function testRouteStoredAsLegsWalksAroundAndJumpsBetweenLegs(): void
	{
		// leg one walks (0,0) -> (5,5) -> (10,0), then a portal jump to (100,0) -> (110,0)
		$segments = Dolumar_NewMap_ArmyFactory::routeSegments('[[[0,0],[5,5],[10,0]],[[100,0],[110,0]]]', [0, 0], [110, 0]);
		$this->assertSame([[[0.0, 0.0], [5.0, 5.0]], [[5.0, 5.0], [10.0, 0.0]], [[100.0, 0.0], [110.0, 0.0]]], $segments);
	}

	public function testOldFlatRoutesStillRead(): void
	{
		$this->assertSame([[[0.0, 0.0], [10.0, 0.0]], [[100.0, 0.0], [110.0, 0.0]]],
			Dolumar_NewMap_ArmyFactory::routeSegments('[[0,0],[10,0],[100,0],[110,0]]', [0, 0], [110, 0]));
		$this->assertSame([[[0, 0], [10, 0]]], Dolumar_NewMap_ArmyFactory::routeSegments('[[[0,0]],"x"]', [0, 0], [10, 0]));
	}

	public function testBattleAroundALakeFollowsTheShore(): void
	{
		// 20 + 20 tiles via (10,10): at the halfway time the army is at the corner, not on the straight line.
		$a = Dolumar_NewMap_ArmyFactory::fromBattleRow($this->row(['route' => '[[[0,0],[10,10],[20,0]]]', 'toX' => 20]), 1050);
		$this->assertEqualsWithDelta(10.0, $a->getLocation(1050)->x(), 0.01);
		$this->assertEqualsWithDelta(10.0, $a->getLocation(1050)->y(), 0.01);
	}

	private function thiefRow(array $o = []): array
	{
		return $o + ['vsu_id' => 4, 'vsu_bid' => 55, 'homeVid' => 1, 'vsu_from' => 1, 'vsu_location' => 2, 'vsu_route' => null,
			'moveStart' => 1000, 'moveEnd' => 1100, 'fromX' => 0, 'fromY' => 0, 'toX' => 10, 'toY' => 0, 'fromName' => 'A', 'toName' => 'B'];
	}

	public function testThiefOnTheMove(): void
	{
		$a = Dolumar_NewMap_ArmyFactory::fromThiefRow($this->thiefRow(), 1050);
		$this->assertSame('t:4', $a->getUOID());
		$this->assertSame('thief', $a->getKind());
		$this->assertTrue($a->isSecret());
		$this->assertSame([1], $a->getRelatedVillageIds());
		$this->assertEqualsWithDelta(5.0, $a->getLocation(1050)->x(), 0.001);
		$this->assertSame("openWindow ('Thievery', {'village': 1, 'building': 55});", implode(' ', $a->getEvents('click')));
		$this->assertStringEndsWith('images/sprites/armies/thief.png', $a->getDisplayObject()->getURI());
	}

	public function testThiefIsOnlySentToItsOwnerAndClan(): void
	{
		$a = Dolumar_NewMap_ArmyFactory::fromThiefRow($this->thiefRow(), 1050);
		$areas = Neuron_GameServer_Map_Regions::toAreas([[0, 0]], 10);
		$this->assertCount(1, Dolumar_NewMap_MapObjectManager::filterForViewer([$a], [1], $areas, 1050));
		// the target (village 2) and strangers never see it, even when it walks through what they look at
		$this->assertCount(0, Dolumar_NewMap_MapObjectManager::filterForViewer([$a], [2], $areas, 1050));
		$this->assertCount(0, Dolumar_NewMap_MapObjectManager::filterForViewer([$a], [], $areas, 1050));
	}
}
