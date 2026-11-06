<?php

use PHPUnit\Framework\TestCase;

/**
 * Resource building bonuses shown to the player (building window, placement preview).
 * The numbers must stay the ones the game has always used.
 */
class ResourceBonusTest extends TestCase
{
	private const SEED = 'Funky Dolumar Game 2008';

	private $previousSeed;

	public static function setUpBeforeClass (): void
	{
		// The constants bootstrap/bootstrap.php defines; the unit suite runs without it.
		if (!defined ('GAME_SPEED_RESOURCES')) {
			define ('GAME_SPEED_RESOURCES', 1);
		}
		if (!defined ('MAXMAPSTRAAL')) {
			define ('MAXMAPSTRAAL', 15000);
		}
		if (!defined ('RANDMAPFACTOR')) {
			define ('RANDMAPFACTOR', self::SEED);
		}
	}

	protected function setUp (): void
	{
		class_exists ('Dolumar_Map_Map');
		$this->previousSeed = $GLOBALS['MAP_CONFIG_RANDMAPFACTOR'];
		$GLOBALS['MAP_CONFIG_RANDMAPFACTOR'] = self::SEED;
	}

	protected function tearDown (): void
	{
		if (isset ($this->previousSeed)) {
			$GLOBALS['MAP_CONFIG_RANDMAPFACTOR'] = $this->previousSeed;
		}
	}

	/**
	 * A village with the given farm and mill level totals, honour 100, no technologies or spells.
	 */
	private function village (int $farms, int $mills): object
	{
		$buildings = new class ($farms, $mills) {
			public function __construct (private int $farms, private int $mills) {}

			public function getBuildingLevelSum ($id)
			{
				return $id == 10 ? $this->farms : ($id == 16 ? $this->mills : 0);
			}
		};

		return new class ($buildings) {
			public function __construct (public object $buildings) {}

			public function procBonusses ($function, $arguments = array (), $now = NOW, $since = NOW, ?array &$steps = null)
			{
				return $arguments[0];
			}
		};
	}

	private function mill (int $level, int $farms, int $mills): Dolumar_Buildings_Mill
	{
		$mill = Dolumar_Buildings_Building::getBuildingFromName ('Mill', Dolumar_Races_Race::getRace ('Humans'));
		$mill->setVillage ($this->village ($farms, $mills));
		$mill->setData (1, array ('bLevel' => $level));
		return $mill;
	}

	public function testMillIncomeIsItsShareOfTheFarms (): void
	{
		// 1.7 x 120 grain per farm level, shared by 10 farm levels and 3 mill levels; this mill has all 3.
		$this->assertSame (floor (1.7 * 120 * 10 * 3 / 13), $this->mill (3, 10, 3)->calculateIncome ());
	}

	public function testMillUpgradePreviewCountsTheExtraMillLevel (): void
	{
		// After upgrading, the village has 4 mill levels, not 3.
		$this->assertSame (floor (1.7 * 120 * 10 * 4 / 14), $this->mill (3, 10, 3)->calculateIncome (4));
	}

	public function testMillBreakdownAddsUpToItsIncome (): void
	{
		$mill = $this->mill (1, 5, 1);
		$rows = $mill->getIncomeBreakdown ();

		$this->assertCount (1, $rows);
		$this->assertSame (170.0, (float) $rows[0]['amount']);
		$this->assertStringContainsString ('farm levels: 5', $rows[0]['label']);
	}

	public function testMillHasNoPlacementPreview (): void
	{
		$village = (new ReflectionClass ('Dolumar_Players_Village'))->newInstanceWithoutConstructor ();
		$this->assertNull ($this->mill (1, 5, 1)->getPlacementPreview ($village));
	}

	public function testBonusTilesAroundAreTheTilesWithAnIncomeBonus (): void
	{
		$cx = 230;
		$cy = -310;
		$radius = 12;

		$expected = array ();
		for ($x = $cx - $radius; $x <= $cx + $radius; $x ++) {
			for ($y = $cy - $radius; $y <= $cy + $radius; $y ++) {
				if (pow ($x - $cx, 2) + pow ($y - $cy, 2) > $radius * $radius) {
					continue;
				}

				$data = Dolumar_Map_Map::getFreshTileData ($x, $y);
				if (in_array ($data[0], array (Dolumar_Map_Map::TILE_TREE, Dolumar_Map_Map::TILE_HAYSTACK, Dolumar_Map_Map::TILE_RESOURCE))) {
					$expected[] = $x . ',' . $y;
				}
			}
		}

		$found = array ();
		foreach (Dolumar_Map_Map::getBonusTilesAround ($cx, $cy, $radius) as [$x, $y, $location]) {
			$found[] = $x . ',' . $y;
			$this->assertNotEmpty ($location->getIncomeBonus ());
			$this->assertContains ($location->getBonusKey (), array ('trees', 'haystack', 'stone', 'iron', 'gems'));
		}

		$this->assertNotEmpty ($expected);
		$this->assertSame ($expected, $found);
	}

	public function testOnlyBonusTilesHaveABonusKey (): void
	{
		$this->assertNull ((new Dolumar_Map_Location (1, 0, 0, 3))->getBonusKey ());
		$this->assertSame ('trees', (new Dolumar_Map_Trees (1, 0, 0, 3))->getBonusKey ());
		$this->assertSame ('haystack', (new Dolumar_Map_Haystack (1, 0, 0, 3))->getBonusKey ());
		$this->assertSame ('iron', (new Dolumar_Map_Resource (2, 0, 0, 3))->getBonusKey ());
		$this->assertSame (array ('iron' => 20), (new Dolumar_Map_Resource (2, 0, 0, 3))->getIncomeBonus ());
	}
}
