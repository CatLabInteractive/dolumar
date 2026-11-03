<?php
/**
 *  Dolumar, browser based strategy game
 *  Copyright (C) 2009 Thijs Van der Schaeghe
 *  CatLab Interactive bvba, Gent, Belgium
 *  http://www.catlab.eu/
 *  http://www.dolumar.com/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */


/*
	Browser notifications for timed events.

	Building, battle and travel progress is computed lazily from timestamps, so these
	events never "happen" in code. scan() runs every minute from cron/push.php and
	notifies the owners of everything that completed since the previous run (the
	watermark in n_server_data). A cancelled building or a recalled army therefore
	never sends a stale notification.

	The messages are queued with sendPushNotification (push only, no e-mail) and
	delivered by Neuron_GameServer_Push_Sender in the same cron run.
*/
class Dolumar_Push_Events
{
	const WATERMARK = 'push_scan';

	// After downtime, notify about at most this many seconds of missed events.
	const MAX_CATCH_UP = 900;

	/*
		@return int number of notifications queued
	*/
	public static function scan ($now = null)
	{
		if (!Neuron_GameServer_Push_Config::isEnabled ())
		{
			return 0;
		}

		$now = $now === null ? time () : intval ($now);

		$last = self::getWatermark ();
		$from = $last === null ? $now - 60 : max ($last, $now - self::MAX_CATCH_UP);

		if ($from >= $now)
		{
			return 0;
		}

		$count = self::scanBuildings ($from, $now) + self::scanBattles ($from, $now);

		self::setWatermark ($now);

		return $count;
	}

	/*
		Called when an army's battle is removed (removeBattle, on its end date). The row is
		deleted then, so when that happens before the next scan the scanner would never
		see it: notify here instead. Battles that ended before the watermark were notified
		by the scanner already.
	*/
	public static function onBattleRemoved (Dolumar_Battle_Battle $battle)
	{
		if (!Neuron_GameServer_Push_Config::isEnabled ())
		{
			return;
		}

		$last = self::getWatermark ();
		if ($last === null || $battle->getEndDate () <= $last)
		{
			return;
		}

		self::notifyReturned ($battle->getId (), $battle->getAttacker (), $battle->getDefender ());
	}

	private static function scanBuildings ($from, $to)
	{
		$db = Neuron_DB_Database::getInstance ();

		$from = intval ($from);
		$to = intval ($to);

		$rows = $db->query
		("
			SELECT
				bid,
				village,
				buildingType,
				bLevel,
				readyDate,
				lastUpgradeDate
			FROM
				map_buildings
			WHERE
				destroyDate = 0 AND
				(
					(readyDate > {$from} AND readyDate <= {$to}) OR
					(lastUpgradeDate > {$from} AND lastUpgradeDate <= {$to})
				)
		");

		$count = 0;
		foreach ($rows as $row)
		{
			$village = self::getVillage ($row['village']);
			$owner = $village ? self::getSubscribedOwner ($village) : null;
			if (!$owner)
			{
				continue;
			}

			$building = Dolumar_Buildings_Building::getBuilding ($row['buildingType'], $village->getRace ());
			if (!$building)
			{
				continue;
			}

			$className = $building->getClassName ();
			$name = $owner->getRightLanguage ()->get ($className, 'buildings', 'buildings', $className);

			$upgraded =
				$row['lastUpgradeDate'] > $from && $row['lastUpgradeDate'] <= $to &&
				$row['lastUpgradeDate'] > $row['readyDate'];

			$queued = $owner->sendPushNotification
			(
				$upgraded ? 'upgraded' : 'ready',
				'building',
				array
				(
					'building' => $name,
					'village' => $village,
					'level' => intval ($row['bLevel'])
				),
				'building-' . $row['bid']
			);

			if ($queued)
			{
				$count ++;
			}
		}

		return $count;
	}

	private static function scanBattles ($from, $to)
	{
		$db = Neuron_DB_Database::getInstance ();

		$from = intval ($from);
		$to = intval ($to);

		$count = 0;

		// Fights. A withdrawn attack also gets its fightDate set to "now", with bLogId 0.
		$rows = $db->query
		("
			SELECT
				battleId,
				vid,
				targetId
			FROM
				battle
			WHERE
				fightDate > {$from} AND
				fightDate <= {$to} AND
				(bLogId IS NULL OR bLogId > 0)
		");

		foreach ($rows as $row)
		{
			$attacker = self::getVillage ($row['vid']);
			$defender = self::getVillage ($row['targetId']);
			if (!$attacker || !$defender)
			{
				continue;
			}

			$tag = 'battle-' . $row['battleId'];

			$owner = self::getSubscribedOwner ($attacker);
			if ($owner && $owner->sendPushNotification ('fought_attacker', 'battle', array ('village' => $attacker, 'target' => $defender), $tag))
			{
				$count ++;
			}

			$owner = self::getSubscribedOwner ($defender);
			if ($owner && $owner->sendPushNotification ('fought_defender', 'battle', array ('village' => $defender, 'attacker' => $attacker), $tag . '-defender'))
			{
				$count ++;
			}
		}

		// Armies back home (also after a withdrawn attack).
		$rows = $db->query
		("
			SELECT
				battleId,
				vid,
				targetId
			FROM
				battle
			WHERE
				endDate > {$from} AND
				endDate <= {$to}
		");

		foreach ($rows as $row)
		{
			if (self::notifyReturned ($row['battleId'], self::getVillage ($row['vid']), self::getVillage ($row['targetId'])))
			{
				$count ++;
			}
		}

		return $count;
	}

	private static function notifyReturned ($battleId, $attacker, $defender)
	{
		if (!$attacker)
		{
			return false;
		}

		$owner = self::getSubscribedOwner ($attacker);
		if (!$owner)
		{
			return false;
		}

		return $owner->sendPushNotification
		(
			'returned',
			'battle',
			array ('village' => $attacker, 'target' => $defender ? $defender : ''),
			'battle-' . intval ($battleId)
		);
	}

	private static function getVillage ($id)
	{
		$village = Dolumar_Players_Village::getFromId ($id);
		if (!$village || $village instanceof Dolumar_Players_DummyVillage || !$village->isFound ())
		{
			return null;
		}
		return $village;
	}

	/*
		The village's owner, when they have a browser subscribed. Checked before any
		text is rendered, since most villages (and every NPC) have none.
	*/
	private static function getSubscribedOwner ($village)
	{
		$owner = $village->getOwner ();
		if (!($owner instanceof Neuron_GameServer_Player) || !$owner->getId ())
		{
			return null;
		}

		if (!Neuron_GameServer_Mappers_PushMapper::getInstance ()->hasSubscriptions ($owner->getId ()))
		{
			return null;
		}

		return $owner;
	}

	private static function getWatermark ()
	{
		$db = Neuron_DB_Database::getInstance ();

		$data = $db->query
		("
			SELECT
				s_value
			FROM
				n_server_data
			WHERE
				s_name = '" . self::WATERMARK . "'
		");

		return count ($data) > 0 ? intval ($data[0]['s_value']) : null;
	}

	private static function setWatermark ($time)
	{
		$db = Neuron_DB_Database::getInstance ();

		$time = intval ($time);

		$db->query
		("
			INSERT INTO
				n_server_data
			SET
				s_name = '" . self::WATERMARK . "',
				s_value = '{$time}'
			ON DUPLICATE KEY UPDATE
				s_value = '{$time}'
		");
	}
}
