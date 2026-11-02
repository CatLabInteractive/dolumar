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

	public function testMoveVillageAndBonusQuote ()
	{
		$q = $this->player->quoteCreditAction (array ('action' => 'movevillage', 'village' => $this->vid, 'x' => 5, 'y' => 6));
		$this->assertSame (750, $q['credits']);
		$q = $this->player->quoteCreditAction (array ('action' => 'bonusbuilding', 'village' => $this->vid, 'building' => 100, 'tile' => 1, 'x' => 5, 'y' => 6));
		$this->assertSame (250, $q['credits']);
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
}
