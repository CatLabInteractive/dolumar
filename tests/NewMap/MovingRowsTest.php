<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/FakeDatabase.php';

class FakeCache
{
	public $store = [];
	public $working = true;
	public function hasCache($k) { return $this->working && isset($this->store[$k]); }
	public function getCache($k) { return $this->working && isset($this->store[$k]) ? $this->store[$k] : false; }
	public function setCache($k, $v) { if ($this->working) { $this->store[$k] = $v; } return $this->working; }
}

class MovingRowsTest extends TestCase
{
	public function testBoxOverlappingAreaTouches(): void
	{
		$areas = Neuron_GameServer_Map_Regions::toAreas([[0, 0]], 10);   // centre (5,-5), radius 20
		$this->assertTrue(Dolumar_NewMap_MovingRows::touchesAreas([[0, 0], [100, 100]], $areas));
		$this->assertTrue(Dolumar_NewMap_MovingRows::touchesAreas([[26, 0], [30, 0]], $areas)); // padding makes 25 touch 25
		$this->assertFalse(Dolumar_NewMap_MovingRows::touchesAreas([[40, 40], [50, 50]], $areas));
		$this->assertFalse(Dolumar_NewMap_MovingRows::touchesAreas([[0, 0]], []));
	}

	public function testClanMemberOfDefenderIsRelated(): void
	{
		// player 9 is in a clan with player 2, who owns village 2 (the defender)
		$db = new FakeDatabase([
			'FROM villages' => [['vid' => 90]],
			'FROM clan_members' => [['vid' => 2], ['vid' => 90]],
		]);
		$ids = Dolumar_NewMap_MovingRows::relatedVillageIds($db, 9);
		$this->assertSame([90, 2], $ids);

		$army = Dolumar_NewMap_ArmyFactory::fromBattleRow(['battleId' => 1, 'vid' => 1, 'targetId' => 2, 'startDate' => 0, 'arriveDate' => 10,
			'fightDate' => 10, 'endFightDate' => null, 'endDate' => 20, 'isFought' => 0, 'route' => null,
			'fromX' => 0, 'fromY' => 0, 'toX' => 1, 'toY' => 1, 'fromName' => 'A', 'toName' => 'B'], 5);
		$this->assertTrue($army->isRelatedTo($ids));
	}

	public function testStrangerIsNotRelated(): void
	{
		$db = new FakeDatabase(['FROM villages' => [['vid' => 77]], 'FROM clan_members' => []]);
		$ids = Dolumar_NewMap_MovingRows::relatedVillageIds($db, 7);
		$this->assertSame([77], $ids);
		$army = Dolumar_NewMap_ArmyFactory::fromBattleRow(['battleId' => 1, 'vid' => 1, 'targetId' => 2, 'startDate' => 0, 'arriveDate' => 10,
			'fightDate' => 10, 'endFightDate' => null, 'endDate' => 20, 'isFought' => 0, 'route' => null,
			'fromX' => 0, 'fromY' => 0, 'toX' => 1, 'toY' => 1, 'fromName' => 'A', 'toName' => 'B'], 5);
		$this->assertFalse($army->isRelatedTo($ids));
	}

	public function testBattleRowsSelectTheFactoryAliases(): void
	{
		$db = new FakeDatabase(['battle b' => [['battleId' => 1]]]);
		$rows = Dolumar_NewMap_MovingRows::battles($db, 1000);
		$this->assertSame([['battleId' => 1]], $rows);
		$sql = $db->queries[0];
		foreach (['fromX', 'fromY', 'toX', 'toY', 'fromName', 'toName', 'endDate > 1000', 'isFought = 0', 'buildingType IN (1,3)'] as $needle) {
			$this->assertStringContainsString($needle, $sql);
		}
	}

	public function testSquadRowsSelectTheFactoryAliases(): void
	{
		$db = new FakeDatabase(['squad_commands sc' => [['sc_id' => 1]]]);
		Dolumar_NewMap_MovingRows::squads($db, 1000);
		$sql = $db->queries[0];
		foreach (['sc_id', 'UNIX_TIMESTAMP(sc.s_start) AS s_start', 'UNIX_TIMESTAMP(sc.s_end) AS s_end', 'sc_route', 'fromX', 'toY', 'squadName', 'toName', 'homeVid', 'FROM_UNIXTIME(1000)'] as $needle) {
			$this->assertStringContainsString($needle, $sql);
		}
	}

	public function testCacheMissWithoutMemcacheStillAnswers(): void
	{
		$cache = new FakeCache();
		$cache->working = false;
		$calls = 0;
		$loader = function () use (&$calls) { $calls ++; return ['x' => 1]; };
		$this->assertSame(['x' => 1], Dolumar_NewMap_MovingRows::cached($cache, 'k', 20, 1000, $loader));
		$this->assertSame(['x' => 1], Dolumar_NewMap_MovingRows::cached($cache, 'k', 20, 1001, $loader));
		$this->assertSame(2, $calls);
	}

	public function testCacheServesWithinTtlAndReloadsAfter(): void
	{
		$cache = new FakeCache();
		$calls = 0;
		$loader = function () use (&$calls) { $calls ++; return ['n' => $calls]; };
		$this->assertSame(['n' => 1], Dolumar_NewMap_MovingRows::cached($cache, 'k', 20, 1000, $loader));
		$this->assertSame(['n' => 1], Dolumar_NewMap_MovingRows::cached($cache, 'k', 20, 1019, $loader));
		$this->assertSame(['n' => 2], Dolumar_NewMap_MovingRows::cached($cache, 'k', 20, 1021, $loader));
	}
}
