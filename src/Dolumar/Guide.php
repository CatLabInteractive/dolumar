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

class Dolumar_Guide
{
	public function __construct (Neuron_GameServer_Player $player)
	{
		$player->events->observe ('register', array (__CLASS__, 'onRegister'));
	}
	
	public static function onRegister (Neuron_GameServer_Player $player)
	{
		// Remove all messages
		$player->guide->removeMessages ();
	
		// Remove all pending quests
		$player->quests->removeQuests ();
		
		// Add the first quest
		$quest = new Dolumar_Quests_S1BuildFarm ();
		$player->quests->addQuest ($quest);
	}
}
?>
