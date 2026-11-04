<?php

use PHPUnit\Framework\TestCase;

/**
 * A squad in an underworld army is busy while the army's mission runs. underworld_armies has no foreign key to
 * underworld_missions, so an army can outlive its mission; such a leftover must not keep the squad busy forever.
 */
class SquadUnderworldIdleTest extends TestCase
{
	private $squadId;
	private $missionId;
	private $armyId;

	protected function setUp (): void
	{
		$db = Neuron_DB_Database::getInstance ();

		$rows = $db->query ("SELECT vid FROM villages WHERE isActive = 1 ORDER BY vid LIMIT 1");
		$this->assertNotEmpty ($rows, 'Needs at least one active village');
		$village = Dolumar_Players_Village::getVillage ((int) $rows[0]['vid']);

		$this->squadId = $village->addSquad ('Underworld test', 1)->getId ();

		$this->missionId = $db->query
		("
			INSERT INTO underworld_missions SET um_map = 'test', um_mission = 'test', um_global = 0
		");

		$this->armyId = $db->query
		("
			INSERT INTO underworld_armies SET
				um_id = {$this->missionId}, ua_x = 0, ua_y = 0, ua_side = 1,
				ua_lastrefresh = NOW(), ua_movepoints = 0
		");

		$db->query ("INSERT INTO underworld_armies_squads SET ua_id = {$this->armyId}, s_id = {$this->squadId}");
	}

	protected function tearDown (): void
	{
		$db = Neuron_DB_Database::getInstance ();
		$db->query ("DELETE FROM underworld_armies WHERE ua_id = " . (int) $this->armyId);
		$db->query ("DELETE FROM underworld_missions WHERE um_id = " . (int) $this->missionId);
		$db->query ("DELETE FROM villages_squads WHERE s_id = " . (int) $this->squadId);
	}

	public function testSquadInRunningMissionIsNotIdle (): void
	{
		$squad = new Dolumar_Players_Squad ($this->squadId);
		$this->assertTrue ($squad->isInUnderworld ());
		$this->assertFalse ($squad->isIdle ());
	}

	public function testSquadInArmyOfRemovedMissionIsIdle (): void
	{
		Neuron_DB_Database::getInstance ()->query ("DELETE FROM underworld_missions WHERE um_id = " . (int) $this->missionId);

		$squad = new Dolumar_Players_Squad ($this->squadId);
		$this->assertFalse ($squad->isInUnderworld ());
		$this->assertTrue ($squad->isIdle ());
	}
}
