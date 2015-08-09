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

class Dolumar_Underworld_Mappers_ArmyMapper
{
	public static function getFromId ($id, Neuron_GameServer_Map_Map2D $map = null)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$id = intval ($id);
		
		$data = $db->query
		("
			SELECT	
				*,
				UNIX_TIMESTAMP(ua_lastrefresh) AS ua_lastrefresh_timestamp
			FROM
				underworld_armies
			WHERE
				ua_id = {$id}
		");
		
		if (count ($data) > 0)
		{
			$out = self::getObjectFromReader ($data[0]);
			
			if (isset ($map))
			{
				$out->setMap ($map);
			}
			
			return $out;
		}
		else
		{
			return null;
		}
	}
	
	public static function getFromSide (Dolumar_Underworld_Models_Mission $mission, Dolumar_Underworld_Models_Side $side)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$side = intval ($side->getId ());
		
		$data = $db->query
		("
			SELECT	
				*,
				UNIX_TIMESTAMP(ua_lastrefresh) AS ua_lastrefresh_timestamp
			FROM
				underworld_armies
			WHERE
				ua_side = {$side} AND
				um_id = {$mission->getId ()}
		");
		
		return self::getObjectsFromReader ($data);
	}
	
	public static function getFromArea (Dolumar_Underworld_Models_Mission $mission, Neuron_GameServer_Map_Area $area)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$sx = $area->getCenter ()->x () - $area->getRadius ();
		$ex = $area->getCenter ()->x () + $area->getRadius ();
		$sy = $area->getCenter ()->y () - $area->getRadius ();
		$ey = $area->getCenter ()->y () + $area->getRadius ();
		
		$sql = "
			SELECT
				*,
				UNIX_TIMESTAMP(ua_lastrefresh) AS ua_lastrefresh_timestamp
			FROM
				underworld_armies
			WHERE
				ua_x >= $sx AND ua_x <= $ex AND 
				ua_y >= $sy AND ua_y <= $ey AND
				um_id = {$mission->getId ()}
		";
		
		$data = $db->query ($sql);
		
		return self::getObjectsFromReader ($data);
	}
	
	public static function save (Dolumar_Underworld_Models_Army $army)
	{
		$loc = $army->getLocation ();
		$x = $loc->x ();
		$y = $loc->y ();
		
		$db = Neuron_DB_Database::getInstance ();
		
		$db->query
		("
			UPDATE
				underworld_armies
			SET
				ua_x = {$x},
				ua_y = {$y},
				ua_movepoints = {$army->getMovepoints (false)},
				ua_lastrefresh = FROM_UNIXTIME({$army->getLastRefresh ()})
			WHERE
				ua_id = {$army->getId ()}
		");
	}

	public static function create (Dolumar_Underworld_Models_Mission $mission, Dolumar_Underworld_Models_Army $army)
	{
		$loc = $army->getLocation ();
		$x = $loc->x ();
		$y = $loc->y ();
		
		$db = Neuron_DB_Database::getInstance ();
		
		$id = $db->query
		("
			INSERT INTO
				underworld_armies
			SET
				ua_x = {$x},
				ua_y = {$y},
				ua_movepoints = {$army->getMovepoints (false)},
				ua_lastrefresh = FROM_UNIXTIME({$army->getLastRefresh ()}),
				ua_side = {$army->getSide ()->getId ()},
				um_id = {$mission->getId ()}
		");

		$army->setId ($id);

		return $army;
	}
	
	public static function remove (Dolumar_Underworld_Models_Army $army)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$db->query
		("
			DELETE FROM
				underworld_armies_squads
			WHERE
				ua_id = {$army->getId ()}
		");
		
		$db->query
		("
			DELETE FROM
				underworld_armies_leaders
			WHERE
				ua_id = {$army->getId ()}
		");
		
		$db->query
		("
			DELETE FROM
				underworld_armies
			WHERE
				ua_id = {$army->getId ()}
		");
	}
	
	public static function getSquads (Dolumar_Underworld_Models_Army $army)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$data = $db->query
		("
			SELECT
				villages_squads.*
			FROM
				underworld_armies_squads
			LEFT JOIN
				villages_squads USING(s_id)
			WHERE
				underworld_armies_squads.ua_id = {$army->getId ()}
		");
		
		$out = array ();
		foreach ($data as $v)
		{
			$squad = new Dolumar_Players_Squad ($v['s_id']);
			$squad->setData ($v);
			$out[] = $squad;
		}
		
		return $out;
	}
	
	public static function addSquad (Dolumar_Underworld_Models_Army $army, Dolumar_Players_Squad $squad)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$db->query
		("
			INSERT INTO
				underworld_armies_squads
			SET
				ua_id = {$army->getId ()},
				s_id = {$squad->getId ()}
		");
	}

	public static function removeSquad (Dolumar_Underworld_Models_Army $army, Dolumar_Players_Squad $squad)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$db->query
		("
			DELETE FROM
				underworld_armies_squads
			WHERE
				ua_id = {$army->getId ()} AND
				s_id = {$squad->getId ()}
		");
	}
	
	public static function getLeaders (Dolumar_Underworld_Models_Army $army)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$data = $db->query
		("
			SELECT
				*
			FROM
				underworld_armies_leaders
			WHERE
				ua_id = {$army->getId ()}
		");
		
		$out = array ();
		foreach ($data as $v)
		{
			$out[] = Neuron_GameServer::getPlayer ($v['plid']);
		}
		return $out;
	}
	
	public static function addLeader (Dolumar_Underworld_Models_Army $army, Dolumar_Players_Player $player)
	{
		Neuron_DB_Database::getInstance ()->query
		("
			INSERT INTO
				underworld_armies_leaders
			SET
				ua_id = {$army->getId ()},
				plid = {$player->getId ()}
		");
	}
	
	public static function removeLeader (Dolumar_Underworld_Models_Army $army, Dolumar_Players_Player $player)
	{
		Neuron_DB_Database::getInstance ()->query
		("
			DELETE FROM
				underworld_armies_leaders
			WHERE
				ua_id = {$army->getId ()} AND
				plid = {$player->getId ()}
		");
	}

	public static function removeFromMission (Dolumar_Underworld_Models_Mission $mission)
	{
		$db = Neuron_DB_Database::getInstance ();

		$armies = $db->query
		("
			SELECT
				ua_id
			FROM
				underworld_armies
			WHERE
				um_id = {$mission->getId ()}
		");

		foreach ($armies as $v)
		{
			$db->query 
			("
				DELETE FROM
					underworld_armies_leaders
				WHERE
					ua_id = {$v['ua_id']}
			");

			$db->query 
			("
				DELETE FROM
					underworld_armies_squads
				WHERE
					ua_id = {$v['ua_id']}
			");
		}

		$db->query 
		("
			DELETE FROM
				underworld_armies
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
		$out = new Dolumar_Underworld_Models_Army ($data['ua_id']);
		$out->setData ($data);
		$out->setLocation (new Neuron_GameServer_Map_Location ($data['ua_x'], $data['ua_y']));
		
		$out->setSide (new Dolumar_Underworld_Models_Side ($data['ua_side']));
		
		return $out;
	}
}
?>
