<?php
class Dolumar_Underworld_Mappers_MissionMapper
{
	public static function getFromId ($id)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$id = intval ($id);
		
		$data = $db->query
		("
			SELECT
				*
			FROM
				underworld_missions
			WHERE
				um_id = {$id}
		");
		
		if (count ($data) == 1)
		{
			return self::getObjectFromReader ($data[0]);
		}
		
		else
		{
			return null;
			//throw new Neuron_Exceptions_DataNotFound ("Could not find mission: " . $id);
		}
	}

	public static function getGlobalMissions ()
	{
		$db = Neuron_DB_Database::getInstance ();

		// Data
		$data = $db->query
		("
			SELECT
				*
			FROM
				underworld_missions
			WHERE
				um_global = 1
		");
		
		return self::getObjectsFromReader ($data);
	}

	public static function create (Dolumar_Underworld_Models_Mission $mission, $global = 0)
	{
		$db = Neuron_DB_Database::getInstance ();

		$global = $global ? '1' : '0';

		$id = $db->query
		("
			INSERT INTO
				underworld_missions
			SET
				um_map = '{$db->escape ($mission->getMapName ())}',
				um_mission = '{$db->escape ($mission->getObjectiveName ())}',
				um_global = '{$global}'
		");

		$mission->setId ($id);

		return $mission;
	}

	public static function hasClan (Dolumar_Underworld_Models_Mission $mission, Dolumar_Players_Clan $clan)
	{
		$db = Neuron_DB_Database::getInstance ();

		$chk = $db->query
		("
			SELECT
				*
			FROM
				underworld_missions_clans
			WHERE
				um_id = {$mission->getId ()} AND
				c_id = {$clan->getId ()}
		");

		return count ($chk) > 0;
	}

	public static function addClan 
	(
		Dolumar_Underworld_Models_Mission $mission, 
		Dolumar_Players_Clan $clan, 
		Dolumar_Underworld_Models_Side $side
	)
	{

		if (!self::hasClan ($mission, $clan))
		{
			$db = Neuron_DB_Database::getInstance ();

			$db->query
			("
				INSERT INTO
					underworld_missions_clans
				SET
					um_id = {$mission->getId ()},
					c_id = {$clan->getId ()},
					umc_side = {$side->getId ()}
			");
		}
	}
	
	public static function getFromClans ($clans)
	{
		if (count ($clans) === 0)
		{
			return array ();
		}

		$db = Neuron_DB_Database::getInstance ();
		
		$clansint = array ();
		foreach ($clans as $v)
		{
			$clansint[] = $v->getId ();
		}
		
		$clanslist = implode ($clansint, ',');
		
		// Data
		$data = $db->query
		("
			SELECT
				*
			FROM
				underworld_missions
			LEFT JOIN
				underworld_missions_clans USING(um_id)
			WHERE
				underworld_missions_clans.c_id IN ({$clanslist})
			GROUP BY
				underworld_missions.um_id
		");
		
		return self::getObjectsFromReader ($data);
	}

	public static function getSides (Dolumar_Underworld_Models_Mission $mission)
	{
		$tmp = array ();

		/*
		// First, load the sides from the mission
		$sides = $mission->getMap ()->getSideIds ();

		foreach ($sides as $v)
		{
			$tmp[$v] = new Dolumar_Underworld_Models_Side ($v);
		}
		*/

		// Now load all the clans
		$db = Neuron_DB_Database::getInstance ();

		$data = $db->query
		("
			SELECT
				*
			FROM
				underworld_missions_clans
			WHERE
				um_id = {$mission->getId ()}
		");

		foreach ($data as $v)
		{
			if (!isset ($tmp[$v['umc_side']]))
			{
				$tmp[$v['umc_side']] = new Dolumar_Underworld_Models_Side ($v['umc_side']);
			}

			$clan = Dolumar_Players_Clan::getFromId ($v['c_id']);
			$tmp[$v['umc_side']]->addClan ($clan);
		}

		return array_values ($tmp);;
	}

	public static function remove (Dolumar_Underworld_Models_Mission $mission)
	{
		Dolumar_Underworld_Mappers_ArmyMapper::removeFromMission ($mission);
		Dolumar_Underworld_Mappers_CheckpointMapper::removeFromMission ($mission);
		Dolumar_Underworld_Mappers_ExploredMapper::removeFromMission ($mission);
		Dolumar_Underworld_Mappers_ScoreMapper::removeFromMission ($mission);

		$db = Neuron_DB_Database::getInstance ();

		$db->query 
		("
			DELETE FROM
				underworld_missions_clans
			WHERE
				um_id = {$mission->getId ()}
		");

		$db->query 
		("
			DELETE FROM
				underworld_missions
			WHERE
				um_id = {$mission->getId ()}
		");
	}
	
	private static function getObjectsFromReader ($data)
	{
		$out = array ();
		foreach ($data as $v)
		{
			$out[] = self::getObjectFromReader ($v);
		}
		return $out;
	}
	
	private static function getObjectFromReader ($data)
	{
		$out = new Dolumar_Underworld_Models_Mission ($data['um_id']);
		$out->setData ($data);
		return $out;
	}
}
?>
