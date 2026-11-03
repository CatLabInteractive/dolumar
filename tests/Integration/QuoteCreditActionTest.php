<?php

use PHPUnit\Framework\TestCase;

class QuoteCreditActionTest extends TestCase
{
	/** @var Dolumar_Players_Player */
	private $player;
	private $vid;

	protected function setUp (): void
	{
		$this->player = Neuron_GameServer::getPlayer (9);
		$villages = $this->player->getVillages ();
		$this->assertNotEmpty ($villages, 'Snapshot player 9 needs a village');
		$this->vid = $villages[0]->getId ();
	}

	private $cleanup = array ();
	private $tileFiles = array ();
	private $maxLogId;

	protected function tearDown (): void
	{
		$db = Neuron_DB_Database::getInstance ();
		foreach ($this->cleanup as $q)
		{
			$db->query ($q);
		}
		foreach ($this->tileFiles as $f)
		{
			@unlink ($f);
		}
		if ($this->maxLogId !== null)
		{
			$db->query ("DELETE FROM game_log WHERE l_id > " . (int) $this->maxLogId);
		}
	}

	private function insert ($sql, $deleteSql)
	{
		$db = Neuron_DB_Database::getInstance ();
		$db->query ($sql);
		$id = (int) $db->getInsertId ();
		$this->cleanup[] = str_replace ('{id}', $id, $deleteSql);
		return $id;
	}

	private function addMoveVillageLog ()
	{
		$db = Neuron_DB_Database::getInstance ();
		$rows = $db->query ("SELECT MAX(l_id) AS m FROM game_log");
		$this->maxLogId = (int) $rows[0]['m'];
		Dolumar_Players_Logs::getInstance ()->addPremiumMoveVillage ($this->player->getMyVillage ($this->vid), 1, 1, 2, 2);
	}

	private function otherVillageId ()
	{
		$db = Neuron_DB_Database::getInstance ();
		$rows = $db->query ("SELECT vid FROM villages WHERE plid != 9 AND plid > 0 LIMIT 1");
		$this->assertNotEmpty ($rows);
		return (int) $rows[0]['vid'];
	}

	public function testPremiumQuote ()
	{
		$q = $this->player->quoteCreditAction (array ('action' => 'premium'));
		$this->assertSame (100, $q['credits']);
		$q = $this->player->quoteCreditAction (array ());
		$this->assertSame (100, $q['credits']);
	}

	public function testRunesQuote ()
	{
		$runes = array_keys ($this->player->getMainVillage ()->resources->getInitialRunes ());
		$q = $this->player->quoteCreditAction (array ('action' => 'buyrunes', 'village' => $this->vid, $runes[0] => 2));
		$this->assertSame (150, $q['credits']);
	}

	public function testRunesOverCapIsNull ()
	{
		$runes = array_keys ($this->player->getMainVillage ()->resources->getInitialRunes ());
		$left = $this->player->getBuyableRunesLeft ();
		$this->assertNull ($this->player->quoteCreditAction (array ('action' => 'buyrunes', 'village' => $this->vid, $runes[0] => $left + 1)));
		$this->assertNull ($this->player->quoteCreditAction (array ('action' => 'buyrunes', 'village' => $this->vid, $runes[0] => 0)));
		$this->assertNull ($this->player->quoteCreditAction (array ('action' => 'buyrunes', 'village' => $this->vid, $runes[0] => -3)));
	}

	public function testResourcesQuote ()
	{
		$res = array_keys ($this->player->getMyVillage ($this->vid)->resources->getResources ());
		$one = $this->player->quoteCreditAction (array ('action' => 'buyresources', 'village' => $this->vid, 'resource' => $res[0]));
		$all = $this->player->quoteCreditAction (array ('action' => 'buyresources', 'village' => $this->vid, 'resource' => 'all'));
		$this->assertSame (150, $one['credits']);
		$this->assertSame (900, $all['credits']);
		$this->assertNull ($this->player->quoteCreditAction (array ('action' => 'buyresources', 'village' => $this->vid, 'resource' => 'nonsense')));
	}

	public function testMoveVillageQuote ()
	{
		$q = $this->player->quoteCreditAction (array ('action' => 'movevillage', 'village' => $this->vid, 'x' => 5, 'y' => 6));
		$this->assertSame (750, $q['credits']);
	}

	private function speedupData ($duration)
	{
		$db = Neuron_DB_Database::getInstance ();
		$rows = $db->query ("SELECT bid FROM map_buildings WHERE village = " . (int) $this->vid . " LIMIT 1");
		$this->assertNotEmpty ($rows);
		return array ('action' => 'speedup', 'type' => 'building', 'building' => $rows[0]['bid'], 'duration' => $duration);
	}

	public function testSpeedupNegativeOrZeroUnitsIsNull ()
	{
		$this->assertNull ($this->player->quoteCreditAction ($this->speedupData (0)));
		$this->assertNull ($this->player->quoteCreditAction ($this->speedupData (-1800)));
		$this->assertNull ($this->player->quoteCreditAction ($this->speedupData (900)));
		$this->assertNull ($this->player->quoteCreditAction ($this->speedupData ('abc')));
	}

	public function testSpeedupHugeUnitsIsNull ()
	{
		$this->assertNull ($this->player->quoteCreditAction ($this->speedupData(1800 * 1000000)));
	}

	public function testOtherPlayersVillageIsNull ()
	{
		$other = $this->otherVillageId ();
		$this->assertNull ($this->player->quoteCreditAction (array ('action' => 'buyresources', 'village' => $other, 'resource' => 'all')));
		$this->assertNull ($this->player->quoteCreditAction (array ('action' => 'movevillage', 'village' => $other, 'x' => 1, 'y' => 1)));
		$this->assertNull ($this->player->quoteCreditAction (array ('action' => 'bonusbuilding', 'village' => $other, 'building' => 100, 'x' => 1, 'y' => 1)));
	}

	public function testUnknownActionIsNull ()
	{
		$this->assertNull ($this->player->quoteCreditAction (array ('action' => 'nope')));
	}

	public function testUseCreditReportsFailure ()
	{
		$village = $this->player->getMyVillage ($this->vid);
		$buildings = array_values ($village->buildings->getBuildings ());
		$this->assertGreaterThan (1, count ($buildings));

		// Move a building onto another building's tile: must be refused.
		$moving = $buildings[0];
		list ($tx, $ty) = $buildings[1]->getLocation ();

		$db = Neuron_DB_Database::getInstance ();
		$before = $db->query ("SELECT xas, yas FROM map_buildings WHERE bid = " . (int) $moving->getId ());

		$result = $this->player->useCredit (100, array
		(
			'action' => 'movebuilding',
			'village' => $this->vid,
			'building' => $moving->getId (),
			'x' => $tx,
			'y' => $ty
		));

		$this->assertFalse ($result);
		$after = $db->query ("SELECT xas, yas FROM map_buildings WHERE bid = " . (int) $moving->getId ());
		$this->assertEquals ($before, $after);
	}

	public function testUseCreditRejectsForeignVillage ()
	{
		$this->assertFalse ($this->player->useCredit (900, array ('action' => 'buyresources', 'village' => $this->otherVillageId (), 'resource' => 'all')));
	}

	/* ---- speedup ---- */

	private function nonTownCenterType ()
	{
		$village = $this->player->getMyVillage ($this->vid);
		foreach ($village->buildings->getBuildings () as $b)
		{
			if ($b->isMoveable ())
			{
				$db = Neuron_DB_Database::getInstance ();
				$rows = $db->query ("SELECT buildingType FROM map_buildings WHERE bid = " . (int) $b->getId ());
				return (int) $rows[0]['buildingType'];
			}
		}
		$this->fail ('no moveable building');
	}

	private function makeBuilding ($left)
	{
		return $this->insert
		(
			"INSERT INTO map_buildings (xas, yas, sizeX, sizeY, buildingType, village, startDate, readyDate, lastUpgradeDate, usedResources, destroyDate, bLevel)
			VALUES (500, 500, 1, 1, " . $this->nonTownCenterType () . ", {$this->vid}, " . (NOW - 10) . ", " . (NOW + $left) . ", 0, '', 0, 1)",
			"DELETE FROM map_buildings WHERE bid = {id}"
		);
	}

	private function makeTraining ($left)
	{
		return $this->insert
		(
			"INSERT INTO villages_units (vid, unitId, buildingId, village, amount, startTraining, endTraining, killedAmount, bid)
			VALUES ({$this->vid}, 1, 0, {$this->vid}, 1, " . (NOW - 10) . ", " . (NOW + $left) . ", 0, 0)",
			"DELETE FROM villages_units WHERE uid = {id}"
		);
	}

	private function makeScouting ($left)
	{
		return $this->insert
		(
			"INSERT INTO villages_scouting (vid, finishDate, runes) VALUES ({$this->vid}, " . (NOW + $left) . ", '')",
			"DELETE FROM villages_scouting WHERE scoutId = {id}"
		);
	}

	public static function speedupProvider ()
	{
		return array
		(
			'building' => array ('building', 'makeBuilding'),
			'training' => array ('training', 'makeTraining'),
			'scouting' => array ('scouting', 'makeScouting'),
		);
	}

	private function speedupRequest ($type, $id, $duration)
	{
		$d = array ('action' => 'speedup', 'type' => $type, 'duration' => $duration, 'village' => $this->vid);
		switch ($type)
		{
			case 'building': $d['building'] = $id; break;
			case 'training': $d['order'] = $id; break;
			case 'scouting': $d['scoutid'] = $id; break;
		}
		return $d;
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('speedupProvider')]
	public function testSpeedupPricingAndCap ($type, $maker)
	{
		$s = Dolumar_Premium_Prices::getSpeedup ($type);
		$unit = $s['unit'];
		// 2.5 units left: needs 3 units to finish.
		$id = $this->$maker ((int) ($unit * 2.5));

		foreach (array (1, 2, 3) as $units)
		{
			$q = $this->player->quoteCreditAction ($this->speedupRequest ($type, $id, $units * $unit));
			$this->assertNotNull ($q, "$type $units units");
			$this->assertSame ($s['price'] * $units, $q['credits']);
		}

		$this->assertNull ($this->player->quoteCreditAction ($this->speedupRequest ($type, $id, 4 * $unit)), 'needed+1');
		$this->assertNull ($this->player->quoteCreditAction ($this->speedupRequest ($type, $id, 0)));
		$this->assertNull ($this->player->quoteCreditAction ($this->speedupRequest ($type, $id, -$unit)));
		$this->assertNull ($this->player->quoteCreditAction ($this->speedupRequest ($type, $id, $unit + 1)));
		$this->assertNull ($this->player->quoteCreditAction ($this->speedupRequest ($type, $id, array (1))));
	}

	public function testSpeedupOfOtherPlayersBuildingIsNull ()
	{
		$other = $this->otherVillageId ();
		$db = Neuron_DB_Database::getInstance ();
		$bid = $this->insert
		(
			"INSERT INTO map_buildings (xas, yas, sizeX, sizeY, buildingType, village, startDate, readyDate, lastUpgradeDate, usedResources, destroyDate, bLevel)
			VALUES (501, 501, 1, 1, " . $this->nonTownCenterType () . ", {$other}, " . (NOW - 10) . ", " . (NOW + 3600) . ", 0, '', 0, 1)",
			"DELETE FROM map_buildings WHERE bid = {id}"
		);
		$this->assertNull ($this->player->quoteCreditAction ($this->speedupRequest ('building', $bid, 1800)));
	}

	public function testUseCreditSpeedupAppliesAndRefusesFinished ()
	{
		$id = $this->makeScouting (3600);
		$this->assertTrue ($this->player->useCredit (100, $this->speedupRequest ('scouting', $id, 1800)));
		$db = Neuron_DB_Database::getInstance ();
		$rows = $db->query ("SELECT finishDate FROM villages_scouting WHERE scoutId = $id");
		$this->assertSame (NOW + 1800, (int) $rows[0]['finishDate']);

		$done = $this->makeScouting (-100);
		$this->assertFalse ($this->player->useCredit (100, $this->speedupRequest ('scouting', $done, 1800)));
	}

	/* ---- movebuilding ---- */

	private function moveBuildingData ($bid)
	{
		return array ('action' => 'movebuilding', 'village' => $this->vid, 'building' => $bid, 'x' => 3, 'y' => 4);
	}

	private function plainBuildingId ()
	{
		$village = $this->player->getMyVillage ($this->vid);
		foreach ($village->buildings->getBuildings () as $b)
		{
			if ($b->isMoveable ())
			{
				return $b->getId ();
			}
		}
		$this->fail ('no moveable building');
	}

	public function testMoveBuildingQuote ()
	{
		$q = $this->player->quoteCreditAction ($this->moveBuildingData ($this->plainBuildingId ()));
		$this->assertSame (100, $q['credits']);
	}

	public function testMoveBuildingFreeHourIsNull ()
	{
		$this->addMoveVillageLog ();
		$this->assertNull ($this->player->quoteCreditAction ($this->moveBuildingData ($this->plainBuildingId ())));
	}

	public function testMoveBuildingTownCenterIsNull ()
	{
		$tc = $this->player->getMyVillage ($this->vid)->buildings->getTownCenter ();
		$this->assertNull ($this->player->quoteCreditAction ($this->moveBuildingData ($tc->getId ())));
		$this->assertFalse ($this->player->useCredit (100, $this->moveBuildingData ($tc->getId ())));
	}

	public function testMoveBuildingOtherPlayersBuildingIsNull ()
	{
		$other = $this->otherVillageId ();
		$db = Neuron_DB_Database::getInstance ();
		$rows = $db->query ("SELECT bid FROM map_buildings WHERE village = $other LIMIT 1");
		$this->assertNotEmpty ($rows);
		$this->assertNull ($this->player->quoteCreditAction ($this->moveBuildingData ($rows[0]['bid'])));
		$this->assertFalse ($this->player->useCredit (100, $this->moveBuildingData ($rows[0]['bid'])));
	}

	/* ---- movevillage ---- */

	public function testMoveVillageIntervalIsEnforced ()
	{
		$data = array ('action' => 'movevillage', 'village' => $this->vid, 'x' => 5, 'y' => 6);
		$this->assertSame (750, $this->player->quoteCreditAction ($data)['credits']);

		$this->addMoveVillageLog ();
		$this->assertNull ($this->player->quoteCreditAction ($data));
		$this->assertFalse ($this->player->useCredit (750, $data));
	}

	/* ---- bonusbuilding ---- */

	private function makeTile ($isPublic, $userid, $endDateSql = 'NULL')
	{
		$name = 'test_tile_' . uniqid () . '.png';
		file_put_contents (PUBLIC_PATH . $name, 'x');
		$this->tileFiles[] = PUBLIC_PATH . $name;
		return $this->insert
		(
			"INSERT INTO players_tiles (t_userid, t_imagename, t_isPublic, t_startDate, t_endDate) VALUES ($userid, '$name', $isPublic, NULL, $endDateSql)",
			"DELETE FROM players_tiles WHERE t_id = {id}"
		);
	}

	private function bonusData ($building, $tile)
	{
		return array ('action' => 'bonusbuilding', 'village' => $this->vid, 'building' => $building, 'tile' => $tile, 'x' => 5, 'y' => 6);
	}

	public function testBonusBuildingRules ()
	{
		$tile = $this->makeTile (1, 999999);
		$this->assertSame (250, $this->player->quoteCreditAction ($this->bonusData (100, $tile))['credits']);

		// Only building 100 is a bonus building.
		$this->assertNull ($this->player->quoteCreditAction ($this->bonusData (1, $tile)));
		$this->assertFalse ($this->player->useCredit (250, $this->bonusData (1, $tile)));

		// Expired public tile, someone else's private tile, missing tile.
		$expired = $this->makeTile (1, 999999, "FROM_UNIXTIME(" . (NOW - 100) . ")");
		$private = $this->makeTile (0, 999999);
		$this->assertNull ($this->player->quoteCreditAction ($this->bonusData (100, $expired)));
		$this->assertNull ($this->player->quoteCreditAction ($this->bonusData (100, $private)));
		$this->assertNull ($this->player->quoteCreditAction ($this->bonusData (100, 0)));
		$this->assertNull ($this->player->quoteCreditAction ($this->bonusData (100, 99999999)));
		$this->assertFalse ($this->player->useCredit (250, $this->bonusData (100, $expired)));
		$this->assertFalse ($this->player->useCredit (250, $this->bonusData (100, 0)));
	}

	/* ---- malformed input ---- */

	public function testMalformedIdsAreNullNotExceptions ()
	{
		$bid = $this->plainBuildingId ();
		$cases = array
		(
			array ('action' => 'movebuilding', 'village' => $this->vid, 'building' => array (1), 'x' => 1, 'y' => 1),
			array ('action' => 'movebuilding', 'village' => array (1), 'building' => $bid, 'x' => 1, 'y' => 1),
			array ('action' => 'buyresources', 'village' => array (1), 'resource' => 'all'),
			array ('action' => 'buyresources', 'village' => $this->vid, 'resource' => array ('all')),
			array ('action' => 'movevillage', 'village' => array (1), 'x' => 1, 'y' => 1),
			array ('action' => 'bonusbuilding', 'village' => $this->vid, 'building' => array (100), 'tile' => 1, 'x' => 1, 'y' => 1),
			array ('action' => 'bonusbuilding', 'village' => $this->vid, 'building' => 100, 'tile' => array (1), 'x' => 1, 'y' => 1),
			array ('action' => 'speedup', 'type' => 'building', 'building' => array (1), 'duration' => 1800),
			array ('action' => 'speedup', 'type' => 'building', 'building' => $bid, 'duration' => array (1)),
			array ('action' => 'speedup', 'type' => 'training', 'village' => array (1), 'order' => 1, 'duration' => 1800),
			array ('action' => 'speedup', 'type' => array ('building'), 'duration' => 1800),
		);
		foreach ($cases as $i => $data)
		{
			$this->assertNull ($this->player->quoteCreditAction ($data), "quote case $i");
			$this->assertFalse ($this->player->useCredit (1, $data), "apply case $i");
		}
	}
}
