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

class Dolumar_Underworld_Windows_Status
	extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$this->setSize ('350px', '60px');
		$this->setTitle ('Status');
		$this->setPosition ('0px', '0px');
		
		$this->setAllowOnlyOnce ();

		$this->setClass ('underworld status');

		$this->setFixed ();
		$this->setNoBorder ();

		$this->setType ('panel');
	}

	public function getContent ()
	{
		$map = $this->getServer ()->getMap ();
		if (! ($map instanceof Dolumar_Underworld_Map_Map))
		{
			$this->reloadWindow ();
			return '<p>Mission is finished.</p>';
		}

		$mission = $map->getMission ();

		$sides = $mission->getSides ();

		$objective = $mission->getObjective ();

		// Check the victory conditions
		$objective->checkVictoryConditions ();

		if ($objective instanceof Dolumar_Underworld_Models_Objectives_TakeAndHold)
		{
			$out = '<p>Hold the center castle until the counter reaches 0.</p>';

			$scores = $objective->getScores ();

			$out .= '<ul>';
			foreach ($scores as $v)
			{
				$timeleft = $objective->getHoldDuration () - $v['score'];

				if ($timeleft > 0)
				{
					if ($v['increasing'])
					{
						$timeleft = Neuron_Core_Tools::getCountdown (NOW + $timeleft);
					}
					else
					{
						$timeleft = Neuron_Core_Tools::getDuration ($timeleft);	
					}
				}
				else
				{
					$timeleft = 0;
				}

				$out .= '<li>' . $v['side']->getDisplayName () . ': ' . $timeleft . '</li>';
			}
			$out .= '</ul>';
		}

		else
		{
			$out = '<p>Just explore ^^</p>';
		}

		/*
		foreach ($sides as $v)
		{


			$checkpoints = $objective->getConqueredCheckpoints ($v);
			$score = count ($checkpoints);

			$out .= '<p>- Team ' . $v->getId () . ': ' . $score . '</p>';
		}*/

		$out .= '<p>' . Neuron_URLBuilder::getInstance()->getOpenUrl ('Battle', 'Battle reports', array ()) . '</p>';

		return $out;
	}

	public function getRefresh ()
	{
		$this->updateContent ();
	}
}