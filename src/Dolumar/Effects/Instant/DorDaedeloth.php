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

class Dolumar_Effects_Instant_DorDaedeloth extends Dolumar_Effects_Instant
{
	public function execute ($a = null, $b = null, $c = null)
	{
		$village = $this->getVillage ();

		// Add mission
		$mission = new Dolumar_Underworld_Models_Mission (null);
		$mission->setMapName ('dordaedeloth.map');
		$mission->setObjectiveName ('DorDaedeloth');

		Dolumar_Underworld_Mappers_MissionMapper::create ($mission, true);

		// Update server status
		$server = Neuron_GameServer::getServer ();
		$server->setData ('gamestate', Dolumar_Players_Server::GAMESTATE_ENDGAME_RUNNING);

		// And notify all players
		Neuron_GameServer_Player_Guide::addPublicMessage 
		(
			'end_casted', 
			array (
				$village->getOwner (),
				$village
			), 
			'guide', 
			'neutral'
		);
	}

	public function getCost ($objUnit, $objTarget, $cost = null)
	{
		return array (
			'gems' => 25000,
			'woods' => 100000,
			'stone' => 100000,
			'iron' => 100000,
			'grain' => 100000,
			'gold' => 100000
		);
	}

	public function getDifficulty ($iBaseAmount = 40)
	{
		return 150;
	}
}
?>
