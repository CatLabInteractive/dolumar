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
