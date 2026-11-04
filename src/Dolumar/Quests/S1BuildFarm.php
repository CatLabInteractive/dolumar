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

class Dolumar_Quests_S1BuildFarm extends Dolumar_Quests_Quest
{
	public function onStart (Neuron_GameServer_Player $player)
	{
		$player->guide->setAllRead ();

		// The goal first, then the first move.
		$player->guide->addMessage ('welcome', array (), 'guide', 'proud');
		$player->guide->addMessage ('s1buildfarm', array (), 'guide', 'happy');
	}

	public function getObjective (Neuron_GameServer_Player $player)
	{
		return 'buildfarm';
	}

	public function getAction (Neuron_GameServer_Player $player)
	{
		return $this->openBuildWindow ($player);
	}

	public function isFinished (Neuron_GameServer_Player $player)
	{
		return $this->hasBuilding ($player, 10);
	}

	public function onComplete (Neuron_GameServer_Player $player)
	{
		$player->quests->addQuest (new Dolumar_Quests_S2BuildLumber ());
	}
}
