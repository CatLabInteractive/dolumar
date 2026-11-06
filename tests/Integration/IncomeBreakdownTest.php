<?php

use PHPUnit\Framework\TestCase;

/**
 * The income breakdown in the building window must add up to what the building really produces,
 * whatever honour, map tiles, technologies and spells the village has.
 */
class IncomeBreakdownTest extends TestCase
{
	public function testLumberCampBreakdownAddsUpToItsIncome (): void
	{
		$db = Neuron_DB_Database::getInstance ();
		$rows = $db->query ("SELECT village FROM map_buildings WHERE buildingType = 1 AND destroyDate = 0 ORDER BY bid LIMIT 1");
		$this->assertNotEmpty ($rows, 'The database needs a village with a town centre');

		$village = Dolumar_Players_Village::getVillage ($rows[0]['village']);
		list ($cx, $cy) = $village->buildings->getTownCenter ()->getLocation ();

		// A spot on a forest tile: the forest itself counts too.
		$forest = null;
		foreach (Dolumar_Map_Map::getBonusTilesAround ($cx, $cy, $village->getBuildingRadius ()) as $v)
		{
			if ($v[2]->getBonusKey () === 'trees')
			{
				$forest = $v;
				break;
			}
		}
		$this->assertNotNull ($forest, 'No forest near the town centre');

		$lumber = Dolumar_Buildings_Building::getBuilding (12, $village->getRace (), $forest[0], $forest[1]);
		$lumber->setVillage ($village);
		$lumber->setData (0, array ('bLevel' => 2));

		$breakdown = $lumber->getIncomeBreakdown ();
		$income = $lumber->getIncome ();

		$sum = 0;
		$tiles = 0;
		foreach ($breakdown as $row)
		{
			$sum += $row['amount'];
			if (!empty ($row['image']))
			{
				$tiles ++;
				$this->assertStringContainsString ('Forest', $row['label']);
			}
		}

		$this->assertSame (1, $tiles);
		$this->assertEquals ($income['wood'], $sum);

		// The placement preview lists that forest tile with its bonus.
		$preview = $lumber->getPlacementPreview ($village);
		$this->assertContains (array ((int) $forest[0], (int) $forest[1], 20, 'Forest'), $preview['tiles']);
	}
}
