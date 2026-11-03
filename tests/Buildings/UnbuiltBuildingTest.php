<?php

use PHPUnit\Framework\TestCase;

/**
 * A building object that is not built yet (Build window, NPC planner) has no database row.
 * Errbit reported "Trying to access array offset on null" for these reads (dolumar #27-#29, #34, #37-#43).
 */
class UnbuiltBuildingTest extends TestCase
{
	protected function setUp (): void
	{
		set_error_handler (static function (int $no, string $msg, string $file, int $line): bool {
			throw new ErrorException ($msg, 0, $no, $file, $line);
		}, E_WARNING);
	}

	protected function tearDown (): void
	{
		restore_error_handler ();
	}

	public function testLevelOfUnbuiltBuildingIsNull (): void
	{
		$building = Dolumar_Buildings_Building::getBuildingFromName ('Farm', Dolumar_Races_Race::getRace ('Humans'));
		$this->assertNull ($building->getLevel ());
	}

	public function testUnbuiltBuildingHasUsedNothing (): void
	{
		$building = Dolumar_Buildings_Building::getBuildingFromName ('Farm', Dolumar_Races_Race::getRace ('Humans'));
		$this->assertSame (array ('resources' => array (), 'runes' => array ()), $building->getUsedAssets ());
		$this->assertSame (array (), $building->getUsedRunes (true));
	}
}
