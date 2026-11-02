<?php

use PHPUnit\Framework\TestCase;

class DistanceRouteTest extends TestCase
{
	public function testStraightRouteHasTwoPointsAndEuclideanDistance(): void
	{
		$r = Dolumar_Map_Distance::getDistanceAndRoute([0, 0], [3, 4], [], true);
		$this->assertEqualsWithDelta(5.0, $r['distance'], 0.001);
		$this->assertSame([[0, 0], [3, 4]], $r['route']);
	}

	public function testSamePointIsZeroWithOnePoint(): void
	{
		$this->assertSame(['distance' => 0, 'route' => [[1, 1]]], Dolumar_Map_Distance::getDistanceAndRoute([1, 1], [1, 1], [], true));
	}

	public function testGetDistanceStillReturnsNumber(): void
	{
		$this->assertEqualsWithDelta(5.0, Dolumar_Map_Distance::getDistance([0, 0], [3, 4], [], true), 0.001);
	}
}
