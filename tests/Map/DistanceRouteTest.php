<?php

use PHPUnit\Framework\TestCase;

class DistanceRouteTest extends TestCase
{
	protected function setUp(): void
	{
		// A lake from x = 5 to 15, y = -10 to 10; everything else is land.
		Dolumar_Map_LandRoute::setCache(false);
		Dolumar_Map_LandRoute::setWaterTest(fn ($x, $y) => $x >= 5 && $x <= 15 && $y >= -10 && $y <= 10);
	}

	protected function tearDown(): void
	{
		Dolumar_Map_LandRoute::setWaterTest(null);
		Dolumar_Map_LandRoute::setCache(null);
	}

	public function testStraightRouteIsOneLegWithEuclideanDistance(): void
	{
		$r = Dolumar_Map_Distance::getDistanceAndRoute([0, 20], [3, 24], [], true);
		$this->assertEqualsWithDelta(5.0, $r['distance'], 0.001);
		$this->assertSame([[[0, 20], [3, 24]]], $r['route']);
	}

	public function testSamePointIsZeroWithOnePoint(): void
	{
		$this->assertSame(['distance' => 0, 'route' => [[[1, 1]]]], Dolumar_Map_Distance::getDistanceAndRoute([1, 1], [1, 1], [], true));
	}

	public function testGetDistanceStillReturnsNumber(): void
	{
		$this->assertEqualsWithDelta(5.0, Dolumar_Map_Distance::getDistance([0, 20], [3, 24], [], true), 0.001);
	}

	public function testRouteGoesAroundTheLake(): void
	{
		$r = Dolumar_Map_Distance::getDistanceAndRoute([0, 0], [20, 0], [], true);
		$this->assertGreaterThan(30, $r['distance']);
		$this->assertCount(1, $r['route']);
		$leg = $r['route'][0];
		$this->assertSame([0, 0], $leg[0]);
		$this->assertSame([20, 0], end($leg));
		$this->assertGreaterThan(2, count($leg));
	}

	public function testStraightLineWhenNotOverLand(): void
	{
		$r = Dolumar_Map_Distance::getDistanceAndRoute([0, 0], [20, 0], [], true, false);
		$this->assertEqualsWithDelta(20.0, $r['distance'], 0.001);
		$this->assertSame([[[0, 0], [20, 0]]], $r['route']);
	}

	public function testPortalJumpStartsANewLeg(): void
	{
		$portal = new class {
			public function getId() { return 1; }
			public function getCasterLocation() { return [1, 30]; }
			public function getTargetLocation() { return [100, 30]; }
			public function getDistancePenalty() { return 2; }
		};

		$r = Dolumar_Map_Distance::getDistanceAndRoute([0, 30], [101, 30], [$portal], true);
		$this->assertEqualsWithDelta(4.0, $r['distance'], 0.001);
		$this->assertSame([[[0, 30], [1, 30]], [[100, 30], [101, 30]]], $r['route']);
	}

	public function testPortalApproachGoesAroundTheLake(): void
	{
		$portal = new class {
			public function getId() { return 1; }
			public function getCasterLocation() { return [20, 0]; }
			public function getTargetLocation() { return [500, 0]; }
			public function getDistancePenalty() { return 1; }
		};

		$r = Dolumar_Map_Distance::getDistanceAndRoute([0, 0], [501, 0], [$portal], true);
		$this->assertCount(2, $r['route']);
		$this->assertGreaterThan(2, count($r['route'][0]));
		$this->assertGreaterThan(30 + 1 + 1, $r['distance']);
	}
}
