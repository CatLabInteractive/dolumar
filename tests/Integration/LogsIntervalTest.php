<?php

use PHPUnit\Framework\TestCase;

/**
 * Dolumar_Players_Logs is a shared instance: a time interval set by one caller
 * (canMoveVillage, Battle, ...) must not change what the credits helpers count.
 */
class LogsIntervalTest extends TestCase
{
	/** @var Dolumar_Players_Player */
	private $player;
	private $village;
	private $maxLogId;

	protected function setUp (): void
	{
		$this->player = Neuron_GameServer::getPlayer (9);
		$villages = $this->player->getVillages ();
		$this->assertNotEmpty ($villages, 'Snapshot player 9 needs a village');
		$this->village = $villages[0];

		$rows = Neuron_DB_Database::getInstance ()->query ("SELECT MAX(l_id) AS m FROM game_log");
		$this->maxLogId = (int) $rows[0]['m'];
	}

	protected function tearDown (): void
	{
		Neuron_DB_Database::getInstance ()->query ("DELETE FROM game_log WHERE l_id > " . (int) $this->maxLogId);
		Dolumar_Players_Logs::getInstance ()->clearFilters ();
	}

	/**
	 * Add a log and move it back in time.
	 */
	private function backdate (int $timestamp): void
	{
		$db = Neuron_DB_Database::getInstance ();
		$db->query ("UPDATE game_log SET l_date = FROM_UNIXTIME(" . (int) $timestamp . ") WHERE l_id > " . (int) $this->maxLogId);
	}

	public function testRuneCapCountsWholeMonthAfterShortInterval (): void
	{
		$monthStart = mktime (0, 0, 0, date ('n', NOW), 1, date ('Y', NOW));
		$when = max ($monthStart + 60, NOW - 8 * 3600);
		if ($when > NOW - 7 * 3600)
		{
			$this->markTestSkipped ('Less than 7 hours into the month.');
		}

		$before = $this->player->getBuyableRunesLeft ();
		$runes = array_keys ($this->player->getMainVillage ()->resources->getInitialRunes ());
		Dolumar_Players_Logs::getInstance ()->addPremiumRunesBoughtLog ($this->village, array ($runes[0] => 1));
		$this->backdate ($when);

		// Some other code sets a 7 hour window on the shared instance (as canMoveVillage does).
		$this->player->canMoveVillage ($this->village);
		$this->assertSame (max (0, $before - 1), $this->player->getBuyableRunesLeft ());

		$logs = Dolumar_Players_Logs::getInstance ();
		$logs->clearFilters ();
		$logs->setTimeInterval (NOW - 7 * 3600);
		$this->assertSame (max (0, $before - 1), $this->player->getBuyableRunesLeft ());
	}

	public function testRuneCapIgnoresLastMonth (): void
	{
		$monthStart = mktime (0, 0, 0, date ('n', NOW), 1, date ('Y', NOW));
		$before = $this->player->getBuyableRunesLeft ();
		$runes = array_keys ($this->player->getMainVillage ()->resources->getInitialRunes ());
		Dolumar_Players_Logs::getInstance ()->addPremiumRunesBoughtLog ($this->village, array ($runes[0] => 1));
		$this->backdate ($monthStart - 3600);
		$this->assertSame ($before, $this->player->getBuyableRunesLeft ());
	}

	public function testRuneCapCountsRunesBoughtInThisRequest (): void
	{
		$before = $this->player->getBuyableRunesLeft ();
		if ($before < 1)
		{
			$this->markTestSkipped ('Player 9 has no runes left to buy this month.');
		}
		$runes = array_keys ($this->player->getMainVillage ()->resources->getInitialRunes ());
		$logs = Dolumar_Players_Logs::getInstance ();
		$logs->clearFilters ();
		$logs->setTimeInterval (0, NOW - 3600);
		$logs->addPremiumRunesBoughtLog ($this->village, array ($runes[0] => 1));
		$this->assertSame ($before - 1, $this->player->getBuyableRunesLeft ());
	}

	public function testFreeBuildingMoveIgnoresLeakedInterval (): void
	{
		Dolumar_Players_Logs::getInstance ()->addPremiumMoveVillage ($this->village, 1, 1, 2, 2);
		$logs = Dolumar_Players_Logs::getInstance ();
		$logs->clearFilters ();
		$logs->setTimeInterval (0, NOW - 86400);
		$this->assertGreaterThan (NOW, $this->player->getFreeBuildingMoveEndDate ($this->village));
	}

	public function testClearFiltersResetsInterval (): void
	{
		Dolumar_Players_Logs::getInstance ()->addPremiumMoveVillage ($this->village, 1, 1, 2, 2);
		$logs = Dolumar_Players_Logs::getInstance ();
		$logs->setTimeInterval (0, NOW - 86400);
		$logs->clearFilters ();
		$logs->addShowOnly ('premium_movevillage');
		$this->assertCount (1, $logs->getLogs ($this->village, 0, 1, 'DESC'));
		$logs->clearFilters ();
	}
}
