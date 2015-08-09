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

class Dolumar_Quests_S1BuildFarm extends Neuron_GameServer_Quest
{
	public function onStart (Neuron_GameServer_Player $player)
	{
		$player->guide->setAllRead ();
		
		$player->guide->addMessage ('welcome', array (), 'guide', 'proud');
		$player->guide->addMessage ('interface', array (), 'guide', 'neutral');
		$player->guide->addMessage ('resources', array (), 'guide', 'neutral', 'economics');
		$player->guide->addMessage ('s1buildfarm', array (), 'guide', 'happy', 'build');
	}
	
	public function isFinished (Neuron_GameServer_Player $player)
	{
		$village = $player->getMainVillage ();
		
		// Check if we already have a farm
		return $village->buildings->hasBuilding 
		(
			Dolumar_Buildings_Building::getBuilding (10, $village->getRace ()), 
			true
		);
	}
	
	public function onComplete (Neuron_GameServer_Player $player)
	{
		$quest = new Dolumar_Quests_S2BuildLumber ();
		$player->quests->addQuest ($quest);
	}
}
?>
