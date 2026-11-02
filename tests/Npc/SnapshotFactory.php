<?php

/**
 * Hand-built snapshots for the NPC tests.
 */
class SnapshotFactory
{
	const NOW = 1700000000;
	const NPC_VID = 100;

	public static function make (array $overrides = [])
	{
		$s = new Dolumar_Npc_Snapshot ();
		$s->now = self::NOW;
		$s->vid = self::NPC_VID;
		$s->aggression = Dolumar_Npc_Config::AGGRESSION_COUNTER;
		$s->resources = ['gold' => 1000, 'wood' => 1000, 'stone' => 1000, 'iron' => 1000, 'grain' => 1000, 'gems' => 0];
		$s->capacity = ['gold' => 5000, 'wood' => 5000, 'stone' => 5000, 'iron' => 5000, 'grain' => 5000, 'gems' => 500];
		$s->runes = ['available' => ['water' => 2, 'wind' => 1, 'earth' => 1, 'fire' => 1], 'availableAmount' => 5, 'used' => 5, 'total' => 10];
		$s->buildings = [
			'Dolumar_Buildings_Silo' => ['count' => 0, 'runes' => 0, 'rune' => 'random', 'canBuild' => true],
			'Dolumar_Buildings_Farm' => ['count' => 1, 'runes' => 1, 'rune' => 'water', 'canBuild' => true],
			'Dolumar_Buildings_Lumber' => ['count' => 1, 'runes' => 1, 'rune' => 'wind', 'canBuild' => true],
			'Dolumar_Buildings_StoneMine' => ['count' => 1, 'runes' => 1, 'rune' => 'earth', 'canBuild' => true],
			'Dolumar_Buildings_IronMine' => ['count' => 1, 'runes' => 1, 'rune' => 'fire', 'canBuild' => true],
			'Dolumar_Buildings_Barrack' => ['count' => 1, 'runes' => 1, 'rune' => 'fire', 'canBuild' => true],
			'Dolumar_Buildings_Archery' => ['count' => 0, 'runes' => 0, 'rune' => 'wind', 'canBuild' => true],
			'Dolumar_Buildings_Stable' => ['count' => 0, 'runes' => 0, 'rune' => 'water', 'canBuild' => true],
			'Dolumar_Buildings_Tower' => ['count' => 0, 'runes' => 0, 'rune' => 'earth', 'canBuild' => true],
		];
		$s->constructing = 0;
		$s->scouting = false;
		$s->strongestNeighbourRunes = 40;
		$s->units = [
			1 => ['class' => 'Dolumar_Units_Spearmen', 'amount' => 100, 'squadless' => 100, 'inSquads' => 0, 'space' => 1, 'melee' => 45, 'defIn' => 30, 'defCav' => 60],
			3 => ['class' => 'Dolumar_Units_Archers', 'amount' => 50, 'squadless' => 50, 'inSquads' => 0, 'space' => 1, 'melee' => 30, 'defIn' => 25, 'defCav' => 20],
		];
		$s->training = [];
		$s->squads = [];
		$s->tribe = [
			'vid' => self::NPC_VID,
			'tribe_name' => 'Grimheads of Murz Hollow',
			'mul_threshold' => 1.0,
			'mul_decay' => 1.0,
			'mul_hoard' => 1.0,
			'last_report_id' => 0,
			'last_transfer_id' => 0,
			'request_res' => '',
			'request_amount' => 0,
			'request_created' => 0,
		];
		$s->standings = [];
		$s->reports = [];
		$s->transfers = [];
		$s->neighbours = [
			10 => self::neighbour (10, 1, 20.0),
			11 => self::neighbour (11, 1, 35.0),
			20 => self::neighbour (20, 2, 50.0),
		];
		$s->incoming = [];
		$s->thirdPartyAttacks = [];

		foreach ($overrides as $key => $value) {
			$s->$key = $value;
		}
		return $s;
	}

	public static function neighbour ($vid, $plid, $distance, array $overrides = [])
	{
		return array_merge ([
			'vid' => $vid,
			'plid' => $plid,
			'distance' => $distance,
			'reachable' => true,
			'runes' => 30,
			'playerRunes' => 60,
			'playerCreated' => self::NOW - 30 * 86400,
			'vacation' => false,
		], $overrides);
	}

	public static function report ($reportId, $fromId, $fromPlid, $targetId, $targetPlid, $victory, $date = self::NOW, $battleId = 0)
	{
		return [
			'reportId' => $reportId,
			'battleId' => $battleId ?: $reportId,
			'fromId' => $fromId,
			'targetId' => $targetId,
			'fromPlid' => $fromPlid,
			'targetPlid' => $targetPlid,
			'victory' => $victory,
			'date' => $date,
		];
	}

	public static function transfer ($id, $fromVid, $plid, array $items, $date = self::NOW)
	{
		return [
			'id' => $id,
			'fromVid' => $fromVid,
			'plid' => $plid,
			'date' => $date,
			'items' => $items,
			'value' => array_sum ($items),
		];
	}

	public static function row ($plid, $standing, array $overrides = [])
	{
		$row = Dolumar_Npc_Standing::newRow (self::NPC_VID, $plid, self::NOW);
		$row['standing'] = (float) $standing;
		return array_merge ($row, $overrides);
	}
}
