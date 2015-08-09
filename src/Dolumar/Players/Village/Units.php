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
	This class handles everything that has to do with honour.
*/
class Dolumar_Players_Village_Units
{
	private $objProfile;
	private $honour = null;

	public function __construct ($profile)
	{
		$this->objProfile = $profile;
	}
	
	/**
	* Return all data from one given order ID
	*/
	public function getTrainingStatus ($id)
	{
		$db = Neuron_DB_Database::getInstance ();

		$id = intval ($id);

		$data = $db->query
		("
			SELECT
				*
			FROM
				villages_units
			WHERE
				uid = {$id}
		");

		if (count ($data) > 0)
		{
			$v = $data[0];

			return array
			(
				'id' => $v['uid'],
				'village' => $v['vid'],
				'unit' => $v['unitId'],
				'amount' => $v['amount'],
				'startTraining' => $v['startTraining'],
				'endTraining' => $v['endTraining'],
				'timeLeft' => $v['endTraining'] - NOW
			);
		}
	}

	/**
	* Return all data from one given order ID
	*/
	public function speedupBuild ($id, $amount)
	{
		$db = Neuron_DB_Database::getInstance ();

		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		
		$profiler->start ('Speeding up training of ' . $id . ' with ' . $amount . ' seconds.');

		$data = $this->getTrainingStatus ($id);

		if ($data)
		{
			$timeLeft = $data['timeLeft'];
			if ($amount > $timeLeft)
			{
				$amount = $timeLeft;
			}
		}

		$db->query
		("
			UPDATE
				villages_units
			SET
				endTraining = endTraining - {$amount}
			WHERE
				uid = {$id}
		");

		$profiler->stop ();

		return;
	}
	
	public function __destruct ()
	{
		unset ($this->objProfile);
		unset ($this->honour);
	}
}