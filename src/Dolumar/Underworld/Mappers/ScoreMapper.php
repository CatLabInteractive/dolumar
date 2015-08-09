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

class Dolumar_Underworld_Mappers_ScoreMapper
{
	public static function getScores (Dolumar_Underworld_Models_Mission $mission)
	{
		$db = Neuron_DB_Database::getInstance ();

		$chk = $db->query 
		("
			SELECT
				*
			FROM
				underworld_score
			WHERE
				um_id = {$mission->getId ()}
		");

		$out = array ();
		foreach ($chk as $v)
		{
			$side = new Dolumar_Underworld_Models_Side ($v['us_side']);

			$out[] = array 
			(
				'side' => $side,
				'score' => $v['us_score'],
				'increasing' => false
			);
		}
		return $out;
	}

	public static function getScore (Dolumar_Underworld_Models_Mission $mission, Dolumar_Underworld_Models_Side $side)
	{
		return 0;
	}

	public static function setScore (Dolumar_Underworld_Models_Mission $mission, Dolumar_Underworld_Models_Side $side, $score)
	{
		$db = Neuron_DB_Database::getInstance ();

		$chk = $db->query 
		("
			SELECT
				us_id
			FROM
				underworld_score
			WHERE
				um_id = {$mission->getId ()} AND
				us_side = {$side->getId ()}
		");

		if (count ($chk) === 0)
		{
			$db->query 
			("
				INSERT INTO
					underworld_score
				SET
					um_id = {$mission->getId ()},
					us_side = {$side->getId ()},
					us_score = '{$db->escape ($score)}'
			");
		}
		else
		{
			$db->query 
			("
				UPDATE
					underworld_score
				SET
					us_score = '{$db->escape ($score)}'
				WHERE
					um_id = {$mission->getId ()} AND
					us_side = {$side->getId ()}
			");
		}
	}

	public static function removeFromMission (Dolumar_Underworld_Models_Mission $mission)
	{
		$db = Neuron_DB_Database::getInstance ();

		$db->query 
		("
			DELETE FROM
				underworld_score
			WHERE
				um_id = {$mission->getId ()}
		");
	}
}
