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

class Dolumar_Quests_S6ScoutRunes extends Dolumar_Quests_Quest
{
	public function onStart (Neuron_GameServer_Player $player)
	{
		$this->say ($player, 's6scout', 'neutral');
	}

	public function getObjective (Neuron_GameServer_Player $player)
	{
		return 'scout';
	}

	public function getAction (Neuron_GameServer_Player $player)
	{
		return $this->openBuildingOfType ($player, 1);
	}

	public function isFinished (Neuron_GameServer_Player $player)
	{
		return $this->countVillageRows ($player, 'villages_scouting', 'vid') > 0 ||
			$this->countVillageRows ($player, 'villages', 'vid', 'AND runeScoutsDone > 0') > 0;
	}

	public function onComplete (Neuron_GameServer_Player $player)
	{
		$player->quests->addQuest (new Dolumar_Quests_S7CreateRegiment ());
	}
}
