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


/**
*	TODO: Add some bloody caching here!
*/

class Dolumar_Underworld_Mappers_CheckpointMapper
{
	public static function getCheckpointSides 
	(
		Dolumar_Underworld_Models_Mission $mission
	)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$data = $db->query
		("
			SELECT
				uc_id,
				uc_x,
				uc_y,
				uc_side,
				uc_date
			FROM
				underworld_checkpoints
			WHERE
				um_id = {$mission->getId ()}
		");
		
		$out = array ();
		foreach ($data as $v)
		{
			$out[] = array
			(
				'location' => new Neuron_GameServer_Map_Location ($v['uc_x'], $v['uc_y']),
				'side' => new Dolumar_Underworld_Models_Side ($v['uc_side'])
			);
		}
		return $out;
	}
	
	public static function set 
	(
		Dolumar_Underworld_Models_Mission $mission, 
		Neuron_GameServer_Map_Location $location,
		Dolumar_Underworld_Models_Side $side
	)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$side = intval ($side->getId ());

		$db->query
		("
			DELETE FROM
				underworld_checkpoints
			WHERE
				um_id = {$mission->getId ()} AND
				uc_x = {$location->x ()} AND
				uc_y = {$location->y ()}
		");
		
		$db->query
		("
			INSERT INTO
				underworld_checkpoints
			SET
				uc_side = {$side},
				um_id = {$mission->getId ()},
				uc_x = {$location->x ()},
				uc_y = {$location->y ()},
				uc_date = NOW()
		");
	}

	public static function removeFromMission (Dolumar_Underworld_Models_Mission $mission)
	{
		$db = Neuron_DB_Database::getInstance ();

		$db->query 
		("
			DELETE FROM
				underworld_checkpoints
			WHERE
				um_id = {$mission->getId ()}
		");
	}

	public static function getTimeSinceLastCheckpointSide (Dolumar_Underworld_Models_Mission $mission, Neuron_GameServer_Map_Location $location)
	{
		$db = Neuron_DB_Database::getInstance ();

		$time = $db->query 
		("
			SELECT
				UNIX_TIMESTAMP(uc_date) AS datum
			FROM
				underworld_checkpoints
			WHERE
				um_id = {$mission->getId ()} AND
				uc_x = {$location->x()} AND
				uc_y = {$location->y()}
		");

		if (count ($time) > 0)
		{
			return NOW - $time[0]['datum'];
		}
		else
		{
			return 0;
		}
	}
}
?>
