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

class Dolumar_Underworld_Mappers_LoggerMapper
{
	/**
	* Return "logger" mission id and create if not defined
	*/
	private static function getMissionId (Dolumar_Underworld_Models_Mission $mission)
	{
		$db = Neuron_DB_Database::getInstance ();

		$data = $db->query
		("
			SELECT
				ul_m_id
			FROM
				underworld_log_mission
			WHERE
				um_id = {$mission->getId ()}
		");

		if (count ($data) > 0)
		{
			$id = $data[0]['ul_m_id'];
		}

		else
		{
			$id = $db->query
			("
				INSERT INTO
					underworld_log_mission
				SET
					um_id = {$mission->getId ()},
					ul_m_map = '{$db->escape ($mission->getMapName ())}'
			");
		}

		return $id;
	}

	private static function getArmyId 
	(
		Dolumar_Underworld_Models_Mission $mission,
		Dolumar_Underworld_Models_Army $army, 
		$forceUpdate = false
	)
	{
		$db = Neuron_DB_Database::getInstance ();

		$data = $db->query
		("
			SELECT
				ul_a_vid,
				ul_a_id,
				ul_a_version
			FROM
				underworld_log_armies
			WHERE
				ua_id = {$army->getId ()}
			ORDER BY
				ul_a_version DESC
			LIMIT 1
		");

		if ($forceUpdate || count ($data) === 0)
		{
			if (count ($data) > 0)
			{
				$logArmyId = $data[0]['ul_a_id'];
				$version = $data[0]['ul_a_version'] + 1;
			}
			else
			{
				$lock = Neuron_Core_Lock::getInstance ();

				if ($lock->setLock ('underworld_army_id', 1))
				{
					$chk = $db->query 
					("
						SELECT
							MAX(ul_a_id) AS id
						FROM
							underworld_log_armies
					");

					$logArmyId = intval ($chk[0]['id']) + 1;
					$version = 1;

					$lock->releaseLock ('underworld_army_id', 1);
				}
			}

			$id = self::createArmy ($mission, $army, $logArmyId, $version);
		}
		else
		{
			$id = $data[0]['ul_a_vid'];
		}

		return $id;
	}

	private static function createArmy 
	(
		Dolumar_Underworld_Models_Mission $mission,
		Dolumar_Underworld_Models_Army $army,
		$armyid,
		$version
	)
	{
		$db = Neuron_DB_Database::getInstance ();

		$armyid = intval ($armyid);
		$version = intval ($version);

		// TODO SQUADS!

		$vid = $db->query 
		("
			INSERT INTO
				underworld_log_armies
			SET
				ul_a_id = {$armyid},
				ul_a_version = {$version},
				ua_id = {$army->getId ()},
				ul_a_squads = '',
				ul_a_side = {$army->getSide ()->getId ()}
		");

		// Leaders
		foreach ($army->getLeaders () as $v)
		{
			$db->query 
			("
				INSERT INTO
					underworld_log_armies_leaders
				SET
					ul_a_vid = {$vid},
					plid = {$v->getId ()}
			");
		}

		return $vid;
	}

	public static function updateArmy 
	(
		Dolumar_Underworld_Models_Mission $mission,
		Dolumar_Underworld_Models_Army $army
	)
	{
		self::getArmyId ($mission, $army, true);
	}

	public static function addMoveLog 
	(
		Dolumar_Underworld_Models_Mission $mission, 
		Neuron_GameServer_Player $player, 
		Dolumar_Underworld_Models_Army $army, 
		Neuron_GameServer_Map_Location $location,
		Neuron_GameServer_Map_Path $path
	)
	{
		$db = Neuron_DB_Database::getInstance ();

		$missionId = self::getMissionId ($mission);
		$armyId = self::getArmyId ($mission, $army);

		$pathtxt = $path->serialize ();

		$sql = "
			INSERT INTO
				underworld_log_event
			SET
				ul_m_id = '$missionId',
				plid = '{$player->getId ()}',
				ul_a_vid = '{$armyId}',
				ul_e_action = 'MOVE',
				ul_e_x = '{$location->x ()}',
				ul_e_y = '{$location->y ()}',
				ul_e_extra = '{$db->escape ($pathtxt)}',
				ul_e_date = NOW()
		";

		$db->query ($sql);
	}

	public static function addSpawnLog 
	(
		Dolumar_Underworld_Models_Mission $mission,
		Neuron_GameServer_Player $player,
		Dolumar_Underworld_Models_Army $army,
		Neuron_GameServer_Map_Location $location,
		Dolumar_Players_Clan $clan
	)
	{
		$db = Neuron_DB_Database::getInstance ();

		self::addClan ($mission, $clan, $army->getSide ());

		$missionId = self::getMissionId ($mission);
		$armyId = self::getArmyId ($mission, $army);

		$sql = "
			INSERT INTO
				underworld_log_event
			SET
				ul_m_id = '$missionId',
				plid = '{$player->getId ()}',
				ul_a_vid = '{$armyId}',
				ul_e_action = 'SPAWN',
				ul_e_x = '{$location->x ()}',
				ul_e_y = '{$location->y ()}',
				ul_e_date = NOW()
		";

		$db->query ($sql);
	}

	public static function addAttackLog 
	(
		Dolumar_Underworld_Models_Mission $mission,
		Neuron_GameServer_Player $player,
		Dolumar_Underworld_Models_Army $attacker,
		Dolumar_Underworld_Models_Army $defender,
		Neuron_GameServer_Map_Location $location,
		Neuron_GameServer_Map_Path $path,
		Dolumar_Underworld_Models_Battle $battle
	)
	{
		$db = Neuron_DB_Database::getInstance ();

		$missionId = self::getMissionId ($mission);
		
		$armyId = self::getArmyId ($mission, $attacker);
		$armyId2 = self::getArmyId ($mission, $defender);

		$pathtxt = $path->serialize ();

		$sql = "
			INSERT INTO
				underworld_log_event
			SET
				ul_m_id = '$missionId',
				plid = '{$player->getId ()}',
				ul_a_vid = '{$armyId}',
				ul_e_action = 'ATTACK',
				ul_e_x = '{$location->x ()}',
				ul_e_y = '{$location->y ()}',
				ul_a2_vid = '{$armyId2}',
				ul_e_extra = '{$db->escape ($pathtxt)}',
				uat_id = {$battle->getId ()},
				ul_e_date = NOW()
		";

		$db->query ($sql);
	}

	public static function split
	(
		Dolumar_Underworld_Models_Mission $mission,
		Neuron_GameServer_Player $player,
		Dolumar_Underworld_Models_Army $oldarmy,
		Dolumar_Underworld_Models_Army $newarmy,
		Neuron_GameServer_Map_Location $location	
	)
	{
		$db = Neuron_DB_Database::getInstance ();

		$missionId = self::getMissionId ($mission);
		
		$armyId = self::getArmyId ($mission, $oldarmy, true);
		$armyId2 = self::getArmyId ($mission, $newarmy);		

		$sql = "
			INSERT INTO
				underworld_log_event
			SET
				ul_m_id = '$missionId',
				plid = '{$player->getId ()}',
				ul_a_vid = '{$armyId}',
				ul_e_action = 'SPLIT',
				ul_e_x = '{$location->x ()}',
				ul_e_y = '{$location->y ()}',
				ul_a2_vid = '{$armyId2}',
				ul_e_date = NOW()
		";

		$db->query ($sql);
	}

	public static function merge
	(
		Dolumar_Underworld_Models_Mission $mission,
		Neuron_GameServer_Player $player,
		Dolumar_Underworld_Models_Army $destroyedArmy,
		Dolumar_Underworld_Models_Army $mergedArmy,
		Neuron_GameServer_Map_Location $location, 
		Neuron_GameServer_Map_Path $path
	)
	{
		$db = Neuron_DB_Database::getInstance ();

		$missionId = self::getMissionId ($mission);
		
		$armyId = self::getArmyId ($mission, $destroyedArmy);
		$armyId2 = self::getArmyId ($mission, $mergedArmy);

		$pathtxt = $path->serialize ();

		$sql = "
			INSERT INTO
				underworld_log_event
			SET
				ul_m_id = '$missionId',
				plid = '{$player->getId ()}',
				ul_a_vid = '{$armyId}',
				ul_e_action = 'MERGE',
				ul_e_x = '{$location->x ()}',
				ul_e_y = '{$location->y ()}',
				ul_a2_vid = '{$armyId2}',
				ul_e_extra = '{$db->escape ($pathtxt)}',
				ul_e_date = NOW()
		";

		$db->query ($sql);
	}

	public static function withdraw
	(
		Dolumar_Underworld_Models_Mission $mission,
		Neuron_GameServer_Player $player,
		Dolumar_Underworld_Models_Army $army,
		Neuron_GameServer_Map_Location $location
	)
	{
				$db = Neuron_DB_Database::getInstance ();

		$missionId = self::getMissionId ($mission);
		$armyId = self::getArmyId ($mission, $army);

		$sql = "
			INSERT INTO
				underworld_log_event
			SET
				ul_m_id = '$missionId',
				plid = '{$player->getId ()}',
				ul_a_vid = '{$armyId}',
				ul_e_action = 'WITHDRAW',
				ul_e_x = '{$location->x ()}',
				ul_e_y = '{$location->y ()}',
				ul_e_date = NOW()
		";

		$db->query ($sql);
	}

	private static function addClan 
	(
		Dolumar_Underworld_Models_Mission $mission, 
		Dolumar_Players_Clan $clan, 
		Dolumar_Underworld_Models_Side $side
	)
	{
		$db = Neuron_DB_Database::getInstance ();

		$missionId = self::getMissionId ($mission);

		$chk = $db->query
		("
			SELECT
				us_id
			FROM
				underworld_log_clans
			WHERE
				um_id = '{$missionId}' AND
				us_clan = '{$clan->getId ()}'
		");

		if (count ($chk) === 0)
		{
			$chk = $db->query
			("
				INSERT INTO
					underworld_log_clans
				SET
					um_id = '{$missionId}',
					us_clan = '{$clan->getId ()}',
					us_side = '{$clan->getId ()}'
			");
		}
	}

	public static function win 
	(
		Dolumar_Underworld_Models_Mission $mission, 
		Dolumar_Underworld_Models_Side $side
	)
	{
		$db = Neuron_DB_Database::getInstance ();

		$missionId = self::getMissionId ($mission);

		$sql = "
			INSERT INTO
				underworld_log_event
			SET
				ul_m_id = '$missionId',
				ul_e_action = 'WIN',
				ul_e_date = NOW(),
				ul_side = {$side->getId ()}
		";

		$db->query ($sql);
	}
}
