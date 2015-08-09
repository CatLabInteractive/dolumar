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

class Dolumar_Underworld_Mappers_BattleMapper
{
	public static function getActiveBattlesFromArmy (Dolumar_Underworld_Models_Army $army)
	{
		
	}

	public static function countFromSide (Dolumar_Underworld_Models_Mission $mission, Dolumar_Underworld_Models_Side $side)
	{
		return 0;
	}

	public static function getFromId ($id)
	{
		$id = intval ($id);

		$db = Neuron_DB_Database::getInstance ();

		$data = $db->query
		("
			SELECT
				uat_id,
				uat_attacker,
				uat_defender,
				UNIX_TIMESTAMP(uat_startdate) AS startdate,
				UNIX_TIMESTAMP(uat_enddate) AS enddate,
				uat_fightlog,
				uat_from_x,
				uat_from_y,
				uat_to_x,
				uat_to_y,
				uat_attacker_side,
				uat_defender_side
			FROM 
				underworld_log_battles
			WHERE
				uat_id = {$id}
		");

		if (count ($data) > 0)
		{
			return self::getModelFromReader ($data[0]);
		}
		else
		{
			return null;
		}
	}

	public static function getFromSide (Dolumar_Underworld_Models_Mission $mission, Dolumar_Underworld_Models_Side $side)
	{
		$db = Neuron_DB_Database::getInstance ();

		$data = $db->query
		("
			SELECT
				uat_id,
				uat_attacker,
				uat_defender,
				UNIX_TIMESTAMP(uat_startdate) AS startdate,
				UNIX_TIMESTAMP(uat_enddate) AS enddate,
				uat_fightlog,
				uat_from_x,
				uat_from_y,
				uat_to_x,
				uat_to_y,
				uat_attacker_side,
				uat_defender_side
			FROM 
				underworld_log_battles
			WHERE
				um_id = {$mission->getId ()} AND
				(
					uat_attacker_side = {$side->getId ()} OR
					uat_defender_side = {$side->getId ()}
				)
			ORDER BY
				uat_startdate DESC
		");

		return self::getModelsFromReader ($data);
	}

	public static function getFromArmy 
	(
		Dolumar_Underworld_Models_Mission $mission, 
		Dolumar_Underworld_Models_Side $side,
		Dolumar_Underworld_Models_Army $army
	)
	{
		$db = Neuron_DB_Database::getInstance ();

		$data = $db->query
		("
			SELECT
				uat_id,
				uat_attacker,
				uat_defender,
				UNIX_TIMESTAMP(uat_startdate) AS startdate,
				UNIX_TIMESTAMP(uat_enddate) AS enddate,
				uat_fightlog,
				uat_from_x,
				uat_from_y,
				uat_to_x,
				uat_to_y,
				uat_attacker_side,
				uat_defender_side
			FROM 
				underworld_log_battles
			WHERE
				um_id = {$mission->getId ()} AND
				(
					uat_attacker = {$side->getId ()} OR
					uat_defender = {$side->getId ()}
				) AND
				(
					uat_attacker_side = {$side->getId ()} OR
					uat_defender_side = {$side->getId ()}
				)
			ORDER BY
				uat_startdate DESC
		");

		return self::getModelsFromReader ($data);
	}

	private static function getModelsFromReader ($data)
	{
		$out = array ();
		foreach ($data as $v)
		{
			$out[] = self::getModelFromReader ($v);
		}
		return $out;
	}

	private static function getModelFromReader ($data)
	{
		$battle = new Dolumar_Underworld_Models_Battle ($data['uat_id']);
		$battle->setData ($data);
		return $battle;
	}
	
	public static function insert (Dolumar_Underworld_Models_Mission $mission, Dolumar_Underworld_Models_Battle $battle)
	{
		$data = $battle->getData ();
		
		$db = Neuron_DB_Database::getInstance ();

		$f = $battle->getAttacker ()->getLocation ();
		$t = $battle->getDefender ()->getLocation ();

		$fx = $f->x ();
		$fy = $f->y ();

		$tx = $t->x ();
		$ty = $t->y ();

		$as = $battle->getAttacker ()->getSide ()->getId ();
		$ds = $battle->getDefender ()->getSide ()->getId ();
		
		$id = $db->query
		("
			INSERT INTO
				underworld_log_battles
			SET
				uat_attacker = {$data['uat_attacker']},
				uat_defender = {$data['uat_defender']},
				uat_startdate = FROM_UNIXTIME({$data['startdate']}),
				uat_enddate = FROM_UNIXTIME({$data['enddate']}),
				uat_fightlog = '{$db->escape($data['uat_fightlog'])}',
				uat_from_x = {$fx},
				uat_from_y = {$fy},
				uat_to_x = {$tx},
				uat_to_y = {$ty},
				um_id = {$mission->getId ()},
				uat_attacker_side = {$as},
				uat_defender_side = {$ds}
		");
		
		$battle->setId ($id);
	}
}
?>
