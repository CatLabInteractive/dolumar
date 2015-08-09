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

class Dolumar_Underworld_Models_Objectives_TakeAndHold
	extends Dolumar_Underworld_Models_Objectives_Objectives
{	
	/**
	* Check the requirements of the checkpoint
	*/
	public function isValidSpawnPoint (Dolumar_Underworld_Models_Side $side, Dolumar_Underworld_Map_Locations_Location $location)
	{
		return $this->checkSpawnpointRequirements ($side, $location);
	}

	public function getName ()
	{
		return 'Dor Daedeloth';
	}

	public function getHoldDuration ()
	{
		return 60 * 60 * 24 * 7;
	}

	public function isPersistentHold ()
	{
		return true;
	}

	protected function doesCheckpointCount (Neuron_GameServer_Map_Location $location)
	{
		return $this->getMission ()->getMap ()->getBackgroundManager ()->isCheckpointObjective ($location);
	}

	public function checkVictoryConditions ()
	{
		// Check if one of the sides has won
		$scores = $this->getScores ();

		foreach ($scores as $v)
		{
			if ($v['score'] > $this->getHoldDuration ())
			{
				// Winner!
				$this->onWin ($v['side']);
			}
		}
	}

	protected function winnerBenefits (Dolumar_Underworld_Models_Side $side)
	{
		
	}

	protected function onChangeCheckpointSide 
	(
		Neuron_GameServer_Map_Location $location, 
		Dolumar_Underworld_Models_Side $originalside = null, 
		Dolumar_Underworld_Models_Side $newside = null,
		$time = 0
	)
	{
		if (isset ($originalside))
		{
			if ($this->isPersistentHold () && $this->doesCheckpointCount ($location))
			{
				$score = $this->getScore ($originalside);
				$score += $time;
				$this->setScore ($originalside, $score);
			}
		}
	}
}