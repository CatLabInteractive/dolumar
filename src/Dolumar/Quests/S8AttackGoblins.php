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

class Dolumar_Quests_S8AttackGoblins extends Dolumar_Quests_Quest
{
	public function onStart (Neuron_GameServer_Player $player)
	{
		$this->say ($player, 's8attack', 'happy');
	}

	public function getObjective (Neuron_GameServer_Player $player)
	{
		return 'attack';
	}

	/*
		Opens the attack screen against the nearest goblin village, or the target list.
	*/
	public function getAction (Neuron_GameServer_Player $player)
	{
		$village = $this->getVillage ($player);
		if (!$village)
		{
			return null;
		}

		$vid = intval ($village->getId ());
		$target = $this->getNearestGoblins ($village);

		if ($target)
		{
			return "openWindow('battle', {'vid':" . $vid . ", 'target':" . intval ($target->getId ()) . "});";
		}

		return "openWindow('battle', {'vid':" . $vid . "});";
	}

	/*
		Unlike Dolumar_Players_Village::lookForNPCs this never spawns a village:
		it runs every time the guide is drawn.
	*/
	private function getNearestGoblins (Dolumar_Players_Village $village)
	{
		$center = $village->buildings->getTownCenterLocation ();
		if (!$center)
		{
			return null;
		}

		$mapper = Dolumar_Mappers_BuildingMapper::getInstance ();
		$castles = $mapper->getBuildingsFromTypeWithinRadius
		(
			$center[0], $center[1],
			1,
			Dolumar_Players_Village_Ai::NPC_PLAYER_DISTANCE
		);

		$best = null;
		$bestDistance = null;

		foreach ($castles as $castle)
		{
			$target = $castle->getVillage ();
			if (!$target || !$target->isNpc ())
			{
				continue;
			}

			$location = $castle->getLocation ();
			$distance = pow ($location[0] - $center[0], 2) + pow ($location[1] - $center[1], 2);

			if ($bestDistance === null || $distance < $bestDistance)
			{
				$best = $target;
				$bestDistance = $distance;
			}
		}

		return $best;
	}

	public function isFinished (Neuron_GameServer_Player $player)
	{
		return $this->countVillageRows ($player, 'battle', 'vid') > 0;
	}

	public function onComplete (Neuron_GameServer_Player $player)
	{
		$player->quests->addQuest (new Dolumar_Quests_S9BattleReport ());
	}
}
