<?php

use PHPUnit\Framework\TestCase;

/**
 * The target picker of the Battle window (Dolumar_Battle_TargetList): nearby villages are premium, closest first and
 * at most 20; recent attackers show up and are suggested as "strike back"; the name search finds villages.
 */
class BattleTargetListTest extends TestCase
{
	/** @var Dolumar_Players_Village */
	private $me;

	/** @var Dolumar_Players_Village */
	private $tribe;

	private $reportId;

	protected function setUp (): void
	{
		$db = Neuron_DB_Database::getInstance ();

		$rows = $db->query ("
			SELECT v.vid FROM villages v
			INNER JOIN map_buildings b ON b.village = v.vid AND b.buildingType = 1 AND b.destroyDate = 0
			WHERE v.isActive = '1' AND v.plid > 0
			ORDER BY v.vid LIMIT 1
		");
		$this->assertNotEmpty ($rows, 'Needs an active player village');
		$this->me = Dolumar_Players_Village::getVillage ((int) $rows[0]['vid']);

		$rows = $db->query ("
			SELECT v.vid FROM villages v
			INNER JOIN map_buildings b ON b.village = v.vid AND b.buildingType = 1 AND b.destroyDate = 0
			WHERE v.isActive = '1' AND v.plid = 0
			ORDER BY v.vid LIMIT 1
		");
		if (count ($rows) === 0)
		{
			$this->markTestSkipped ('Needs an active tribe village');
		}
		$this->tribe = Dolumar_Players_Village::getVillage ((int) $rows[0]['vid']);
	}

	protected function tearDown (): void
	{
		if ($this->reportId)
		{
			Neuron_DB_Database::getInstance ()->query ("DELETE FROM battle_report WHERE reportId = " . (int) $this->reportId);
		}
	}

	public function testNearbyNeedsPremium (): void
	{
		$list = new Dolumar_Battle_TargetList ($this->me, 'attack', false);
		$this->assertSame (array (), $list->getNearby ());
	}

	public function testNearbyIsClosestFirstAndAtMostTwenty (): void
	{
		$list = new Dolumar_Battle_TargetList ($this->me, 'attack', true);
		$rows = $list->getNearby ();

		$this->assertNotEmpty ($rows);
		$this->assertLessThanOrEqual (Dolumar_Battle_TargetList::NEARBY, count ($rows));

		$last = -1;
		foreach ($rows as $r)
		{
			$this->assertNotSame ((int) $this->me->getId (), $r['id']);
			$this->assertGreaterThanOrEqual ($last, $r['distance']);
			$last = $r['distance'];
		}

		foreach ($list->getNearby ('tribes') as $r)
		{
			$this->assertTrue ($r['npc']);
			$this->assertSame (0, $r['honour'], 'Raids on tribes cost no honour');
		}
	}

	public function testHonourIsHiddenWithoutPremium (): void
	{
		$list = new Dolumar_Battle_TargetList ($this->me, 'attack', false);
		$row = $list->getRow ($this->tribe);
		$this->assertNull ($row['honour']);
		$this->assertTrue ($row['canPick']);
	}

	public function testAttackerIsListedAndSuggested (): void
	{
		$now = time ();
		$this->reportId = Neuron_DB_Database::getInstance ()->query ("
			INSERT INTO battle_report SET battleId = 0, fightDate = " . ($now - 3600) . ", fightDuration = 0,
				fromId = " . (int) $this->tribe->getId () . ", targetId = " . (int) $this->me->getId () . ",
				squads = '', slots = '', fightLog = '', battleLog = '', resultLog = '', victory = 0.5, execDate = NOW()
		");

		$list = new Dolumar_Battle_TargetList ($this->me, 'attack', false, $now);

		$ids = array_column ($list->getAttackers (), 'id');
		$this->assertContains ((int) $this->tribe->getId (), $ids);

		$suggestions = $list->getSuggestions ();
		$this->assertNotEmpty ($suggestions);
		$this->assertSame ((int) $this->tribe->getId (), $suggestions[0]['id']);

		// Support never suggests.
		$support = new Dolumar_Battle_TargetList ($this->me, 'support', false, $now);
		$this->assertSame (array (), $support->getSuggestions ());
	}

	public function testDistanceIsInLeagues (): void
	{
		$list = new Dolumar_Battle_TargetList ($this->me, 'attack', false);
		$this->assertSame ('0.5', $list->formatLeagues (5));
		$this->assertSame ('8.9', $list->formatLeagues (88.6));
		$this->assertSame ('14', $list->formatLeagues (140.2));
	}

	public function testSearchFindsVillageByPartOfItsName (): void
	{
		$list = new Dolumar_Battle_TargetList ($this->me, 'attack', false);
		$name = $this->tribe->getName ();
		$ids = array_column ($list->search (substr ($name, 0, 6)), 'id');
		$this->assertContains ((int) $this->tribe->getId (), $ids);
		$this->assertSame (array (), $list->search ('   '));
	}
}
