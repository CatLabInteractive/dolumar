<?php

use PHPUnit\Framework\TestCase;

/**
 * Dolumar_Players_Village_Buildings::build() must report why a location was refused, so the Build window
 * can show the translated message (it used to store the reason in an undeclared property and show nothing).
 */
class BuildErrorTest extends TestCase
{
	private function buildings (): Dolumar_Players_Village_Buildings
	{
		$village = $this->createMock (Dolumar_Players_Village::class);
		$village->method ('readyToBuild')->willReturn (true);

		return new Dolumar_Players_Village_Buildings ($village);
	}

	private function building (array $locationCheck): Dolumar_Buildings_Building
	{
		$building = $this->createMock (Dolumar_Buildings_Building::class);
		$building->method ('canBuildBuilding')->willReturn (true);
		$building->method ('checkBuildingLevels')->willReturn (true);
		$building->method ('checkBuildLocation')->willReturn ($locationCheck);
		$building->expects ($this->never ())->method ('build');

		return $building;
	}

	public function testOutOfRangeReportsErrorAndRange (): void
	{
		$buildings = $this->buildings ();

		$this->assertFalse ($buildings->build ($this->building ([false, 'outOfRange', [12]]), 40, 40));
		$this->assertSame ('outOfRange', $buildings->getError ());
		$this->assertSame ([12], $buildings->getErrorParams ());
	}

	public function testOverlapReportsErrorWithoutParams (): void
	{
		$buildings = $this->buildings ();

		$this->assertFalse ($buildings->build ($this->building ([false, 'buildingOverlap']), 1, 1));
		$this->assertSame ('buildingOverlap', $buildings->getError ());
		$this->assertSame ([], $buildings->getErrorParams ());
	}

	public function testEveryLocationErrorHasATranslation (): void
	{
		foreach (['en', 'nl', 'de', 'fr', 'pl'] as $language) {
			$lng = parse_ini_file (__DIR__ . '/../../resources/language/' . $language . '/building.lng', true, INI_SCANNER_RAW);
			foreach (['outOfRange', 'buildingOverlap', 'townCenterNotFound', 'stillConstructing'] as $key) {
				$this->assertNotEmpty ($lng['buildError'][$key] ?? null, $language . ': buildError.' . $key);
			}
		}
	}
}
