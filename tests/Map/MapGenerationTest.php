<?php

use PHPUnit\Framework\TestCase;

/*
	The generated map must never change: every kingdom sits on it. The expected values were
	taken from the generator before it was optimised (2026-11), with the default map seed.
*/
class MapGenerationTest extends TestCase
{
	private const SEED = 'Funky Dolumar Game 2008';

	private $previousSeed;

	public static function setUpBeforeClass(): void
	{
		// The constants bootstrap/bootstrap.php defines; the unit suite runs without it.
		if (!defined ('MAXMAPSTRAAL')) {
			define ('MAXMAPSTRAAL', 15000);
		}
		if (!defined ('RANDMAPFACTOR')) {
			define ('RANDMAPFACTOR', self::SEED);
		}
	}

	protected function setUp(): void
	{
		if (MAXMAPSTRAAL !== 15000) {
			$this->markTestSkipped ('The expected values are for a map radius of 15000.');
		}

		// Loads Dolumar_Map_Map, which reads the seed into this global.
		class_exists ('Dolumar_Map_Map');
		$this->previousSeed = $GLOBALS['MAP_CONFIG_RANDMAPFACTOR'];
		$GLOBALS['MAP_CONFIG_RANDMAPFACTOR'] = self::SEED;
	}

	protected function tearDown(): void
	{
		if (isset ($this->previousSeed)) {
			$GLOBALS['MAP_CONFIG_RANDMAPFACTOR'] = $this->previousSeed;
		}
	}

	public static function tiles(): array
	{
		return [
			'origin' => [0, 0, [0, 10, 5.9621]],
			'next to origin' => [1, 0, [0, 11, 5.7312]],
			'low grass' => [-7, 13, [0, 13, 1.7355]],
			'grass' => [230, -310, [0, 4, 4.4754]],
			'trees' => [-1234, 987, [3, 1, 3.1686]],
			'off the map' => [10600, -10620, [1, 52, 0]],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('tiles')]
	public function testKnownTiles(int $x, int $y, array $expected): void
	{
		$this->assertSame ($expected, Dolumar_Map_Map::getFreshTileData ($x, $y));
	}

	public function testAreasAreUnchanged(): void
	{
		// 30x30 blocks that cross the 25x25 generator squares, around the origin, at negative
		// coordinates and on the edge of the map.
		$hash = hash_init ('sha256');
		foreach ([[-15, -15], [230, -310], [-1234, 987], [10600, -10620], [14980, -40]] as [$bx, $by]) {
			for ($x = $bx; $x < $bx + 30; $x ++) {
				for ($y = $by; $y < $by + 30; $y ++) {
					hash_update ($hash, serialize (Dolumar_Map_Map::getFreshTileData ($x, $y)));
				}
			}
		}

		$this->assertSame ('e787c47e9cc3ff7807f136848f13c381567e192c59a3f0db3450651a0717773c', hash_final ($hash));
	}
}
