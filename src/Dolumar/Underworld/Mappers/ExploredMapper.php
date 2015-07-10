<?php

/**
*	TODO: Add some bloody caching here!
*/

class Dolumar_Underworld_Mappers_ExploredMapper
{
	public static function getExploredLocations 
	(
		Dolumar_Underworld_Models_Mission $mission, 
		Dolumar_Underworld_Models_Side $side
	)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$side = intval ($side->getId ());
		
		$data = $db->query
		("
			SELECT
				*
			FROM
				underworld_explored
			WHERE
				ue_side = {$side} AND
				um_id = {$mission->getId ()}
		");
		
		$out = array ();
		foreach ($data as $v)
		{
			$out[] = new Neuron_GameServer_Map_Location ($v['ue_x'], $v['ue_y']);
		}
		return $out;
	}
	
	public static function insert 
	(
		Dolumar_Underworld_Models_Mission $mission, 
		Dolumar_Underworld_Models_Side $side, 
		Neuron_GameServer_Map_Location $location
	)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$side = intval ($side->getId ());
		
		$db->query
		("
			INSERT INTO
				underworld_explored
			SET
				ue_side = {$side},
				um_id = {$mission->getId ()},
				ue_x = {$location->x ()},
				ue_y = {$location->y ()}
		");
	}

	public static function removeFromMission (Dolumar_Underworld_Models_Mission $mission)
	{
		$db = Neuron_DB_Database::getInstance ();

		$db->query
		("
			DELETE FROM
				underworld_explored
			WHERE
				um_id = {$mission->getId ()}
		");
	}
}
?>
