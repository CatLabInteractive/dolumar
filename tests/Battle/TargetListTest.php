<?php

use PHPUnit\Framework\TestCase;

/**
 * The target picker only knows two modes. (Leagues are tested in tests/Integration/BattleTargetListTest: loading
 * Dolumar_Map_Map here would fix the map seed before tests/Map/MapGenerationTest sets it.)
 */
class TargetListTest extends TestCase
{
	public function testUnknownModeIsAttack (): void
	{
		$list = new Dolumar_Battle_TargetList (null, 'something', false, 0);
		$this->assertSame ('attack', $list->getMode ());
		$this->assertSame ('support', (new Dolumar_Battle_TargetList (null, 'support', false, 0))->getMode ());
	}
}
