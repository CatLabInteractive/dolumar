<?php

use PHPUnit\Framework\TestCase;

class LandRouteTest extends TestCase
{
	protected function tearDown(): void
	{
		Dolumar_Map_LandRoute::setWaterTest(null);
		Dolumar_Map_LandRoute::setCache(null);
	}

	private function lake(callable $water): void
	{
		Dolumar_Map_LandRoute::setCache(false);
		Dolumar_Map_LandRoute::setWaterTest($water);
	}

	private function assertStaysOnLand(array $points, callable $water): void
	{
		for ($i = 1; $i < count($points); $i ++) {
			[$a, $b] = [$points[$i - 1], $points[$i]];
			$n = (int) ceil(hypot($b[0] - $a[0], $b[1] - $a[1]) * 3);
			for ($j = 0; $j <= $n; $j ++) {
				$t = $j / max(1, $n);
				$x = (int) round($a[0] + ($b[0] - $a[0]) * $t);
				$y = (int) round($a[1] + ($b[1] - $a[1]) * $t);
				$this->assertFalse($water($x, $y), "leg $i passes water at $x,$y");
			}
		}
	}

	public function testOpenLandIsTheStraightLine(): void
	{
		$this->lake(fn ($x, $y) => false);
		$r = Dolumar_Map_LandRoute::walk([0, 0], [30, 40]);
		$this->assertSame(50.0, (float) $r['distance']);
		$this->assertSame([[0, 0], [30, 40]], $r['points']);
	}

	public function testGoesAroundALake(): void
	{
		$water = fn ($x, $y) => $x >= 5 && $x <= 15 && $y >= -10 && $y <= 10;
		$this->lake($water);

		$r = Dolumar_Map_LandRoute::walk([0, 0], [20, 0]);

		$this->assertSame([0, 0], $r['points'][0]);
		$this->assertSame([20, 0], end($r['points']));
		$this->assertStaysOnLand($r['points'], $water);
		// Around the corner (4,11)/(16,11) or below: a bit more than 2 * hypot(4, 11) + 12
		$this->assertGreaterThan(35, $r['distance']);
		$this->assertLessThan(38, $r['distance']);
		// Smoothed: a handful of corners, not every tile of the shore
		$this->assertLessThanOrEqual(6, count($r['points']));
	}

	public function testWayBackIsTheSameRoute(): void
	{
		$this->lake(fn ($x, $y) => $x >= 5 && $x <= 15 && $y >= -10 && $y <= 7);
		$out = Dolumar_Map_LandRoute::walk([0, 0], [20, 3]);
		$back = Dolumar_Map_LandRoute::walk([20, 3], [0, 0]);
		$this->assertSame($out['distance'], $back['distance']);
		$this->assertSame(array_reverse($out['points']), $back['points']);
	}

	public function testCrossesTwoLakes(): void
	{
		$water = fn ($x, $y) => ($x >= 5 && $x <= 8 && abs($y) <= 6) || ($x >= 20 && $x <= 25 && $y >= -3 && $y <= 9);
		$this->lake($water);

		$r = Dolumar_Map_LandRoute::walk([0, 0], [40, 0]);
		$this->assertStaysOnLand($r['points'], $water);
		$this->assertGreaterThan(40, $r['distance']);
	}

	public function testNoWayAroundFallsBackToTheStraightLine(): void
	{
		// A wall of water across the whole map.
		$this->lake(fn ($x, $y) => $x >= 5 && $x <= 8);
		$r = Dolumar_Map_LandRoute::walk([0, 0], [20, 0]);
		$this->assertSame([[0, 0], [20, 0]], $r['points']);
		$this->assertEqualsWithDelta(20.0, $r['distance'], 0.0001);
	}

	public function testResultIsCached(): void
	{
		$cache = new class {
			public $store = [];
			public function getCache($k) { return $this->store[$k] ?? false; }
			public function setCache($k, $v) { $this->store[$k] = $v; return true; }
		};
		Dolumar_Map_LandRoute::setWaterTest(fn ($x, $y) => $x >= 5 && $x <= 15 && abs($y) <= 10);
		Dolumar_Map_LandRoute::setCache($cache);

		$first = Dolumar_Map_LandRoute::walk([0, 0], [20, 0]);
		$this->assertCount(1, $cache->store);

		// A fresh process (memo cleared) with all land would give a straight line; the cache wins.
		Dolumar_Map_LandRoute::setWaterTest(fn ($x, $y) => false);
		Dolumar_Map_LandRoute::setCache($cache);
		$this->assertEqualsWithDelta($first['distance'], Dolumar_Map_LandRoute::walk([0, 0], [20, 0])['distance'], 0.0001);
	}

	public static function realMapRoutes(): array
	{
		return [
			'short, across a lake' => [[431, -430], [491, -448]],
			'long, several lakes' => [[-88, 336], [-741, 291]],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('realMapRoutes')]
	public function testRealMapRouteStaysOnLand(array $from, array $to): void
	{
		if (!defined('MAXMAPSTRAAL')) {
			define('MAXMAPSTRAAL', 15000);
		}
		if (!defined('RANDMAPFACTOR')) {
			define('RANDMAPFACTOR', 'Funky Dolumar Game 2008');
		}
		class_exists('Dolumar_Map_Map');
		$previous = $GLOBALS['MAP_CONFIG_RANDMAPFACTOR'];
		$GLOBALS['MAP_CONFIG_RANDMAPFACTOR'] = 'Funky Dolumar Game 2008';

		try {
			$water = fn ($x, $y) => Dolumar_Map_Map::isWater($x, $y);
			$this->lake($water);
			Dolumar_Map_LandRoute::setWaterTest(null);

			$this->assertTrue($this->crossesWater($from, $to, $water), 'the straight line should cross water');

			$started = microtime(true);
			$r = Dolumar_Map_LandRoute::walk($from, $to);
			$this->assertLessThan(2.0, microtime(true) - $started, 'route search too slow');

			$straight = hypot($to[0] - $from[0], $to[1] - $from[1]);
			$this->assertGreaterThan($straight, $r['distance']);
			$this->assertLessThan($straight * 1.25, $r['distance']);
			$this->assertStaysOnLand($r['points'], $water);
		} finally {
			$GLOBALS['MAP_CONFIG_RANDMAPFACTOR'] = $previous;
		}
	}

	private function crossesWater(array $a, array $b, callable $water): bool
	{
		$n = (int) ceil(hypot($b[0] - $a[0], $b[1] - $a[1]) * 3);
		for ($j = 0; $j <= $n; $j ++) {
			if ($water((int) round($a[0] + ($b[0] - $a[0]) * $j / $n), (int) round($a[1] + ($b[1] - $a[1]) * $j / $n))) {
				return true;
			}
		}
		return false;
	}
}
