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

class Dolumar_Buildings_Clanportal 
	extends Dolumar_Buildings_Portal
{
	public function canBuildBuilding (Dolumar_Players_Village $village)
	{
		// Only one portal allowed
		if ($village->buildings->hasBuilding ($this))
		{
			return false;
		}
		
		if (!defined ('ALLOW_CLANPORTALS'))
		{
			return false;
		}

		if (count ($village->getOwner ()->getClans ()) > 0)
		{
			return $this->buildRequirementCheck ($village);
		}

		return false;
	}
	
	/**
	*	Function that is called whenever the
	*	building gets destructed.
	*/
	protected function onDestruct ()
	{
		// Destroy all portals that connect to this building
		Dolumar_Map_Portal::removeFromBuilding ($this);
	}

	public function onClanLeave ()
	{
		$this->doDestructBuilding ();
	}
	
	/**
	*	Method that is called after this building was build
	*/
	protected function onBuild ()
	{
		// Connect this portal to all other portals
		$portals = $this->getVillage ()->getOwner ()->getMainClan ()->getClanportals ();
		
		foreach ($portals as $v)
		{
			if (!$v->equals ($this))
			{
				$portal = new Dolumar_Map_Portal (null);
				
				$portal->setCasterBuilding ($this);
				$portal->setTargetBuilding ($v);
				
				Dolumar_Map_Portal::insert ($portal);
			}
		}
	}
	
	public function isUpgradeable ()
	{
		return false;
	}
	
	public function isDestructable ()
	{
		return true;
	}
	
	public function getMyContent ($input, $original = false)
	{
		$action = isset ($input['action']) ? $input['action'] : null;
		switch ($action)
		{
			case 'mission':
				return $this->getMissionOverview ($input);
			break;
		}
	
		$out = $this->getMissionManager ($input);
	
		// General content
		$out .= parent::getMyContent ($input, true);
		return $out;
	}

	private function createExploreMission ()
	{
		$mission = new Dolumar_Underworld_Models_Mission (null);
		$mission->setMapName ('explore.map');
		$mission->setObjectiveName ('Explore');

		return Dolumar_Underworld_Mappers_MissionMapper::create ($mission, true);
	}
	
	private function getMissionManager ($input)
	{
		$page = new Neuron_Core_Template ();

		//  See if we have global missions that we should join
		$globalMissions = Dolumar_Underworld_Mappers_MissionMapper::getGlobalMissions ();

		if (count ($globalMissions) === 0)
		{
			// At least create the "explore" mission
			$globalMissions = array ();
			$globalMissions[] = $this->createExploreMission ();
		}

		// Check if we already joined this game
		foreach ($this->getVillage ()->getOwner ()->getClans () as $clan)
		{
			foreach ($globalMissions as $v)
			{
				if (!$v->hasJoined ($clan))
				{
					// In global missions, clans always join their "own" side
					$v->join ($clan);
				}
			}
		}
		
		$missionsdata = Dolumar_Underworld_Mappers_MissionMapper::
			getFromClans ($this->getVillage ()->getOwner ()->getClans ());
		
		$missions = array ();
		foreach ($missionsdata as $v)
		{
			$missions[] = Neuron_URLBuilder::getInstance ()->getUpdateUrl 
			(
				'Building', 
				$v->getName (), 
				array 
				(
					'action' => 'mission',
					'id' => $v->getId ()
				)
			);
		}
		
		$page->set ('missions', $missions);
		
		return $page->parse ('buildings/clanportals_missions.phpt');
	}
	
	private function getMissionOverview ($input)
	{
		if (isset ($input['id']))
		{
			$mission = Dolumar_Underworld_Mappers_MissionMapper::
				getFromId ($input['id']);
		}
		
		if (!isset ($mission))
		{
			return '<p class="false">Mission not found.</p>';
		}
	
		$text = Neuron_Core_Text::getInstance ();
		$page = new Neuron_Core_Template ();
		
		$page->set ('id', $mission->getId ());
		$page->set ('mission', $mission->getName ());
		
		$page->set 
		(
			'openmission', 
			Neuron_URLBuilder::getInstance ()->getOpenUrl
			(
				'Underworld',
				$text->get ('openmission', 'clanportal', 'buildings'),
				array
				(
					'id' => $mission->getId ()
				),
				null,
				$mission->getUrl ()
			)
		);
		
		
		$page->set 
		(
			'return', 
			Neuron_URLBuilder::getInstance ()->getUpdateUrl 
			(
				'Building', 
				$text->get ('return', 'clanportal', 'buildings'),
				array
				(
					'action' => 'overview'
				)
			)
		);
		
		// Get free regiments
		$units = array ();
		$addunits = array ();
		
		$unitsdata = $this->getVillage ()->getSquads (false, true, true);
		foreach ($unitsdata as $v)
		{
			if (!$v->isIdle ())
			{
				continue;
			}
			
			$add = true;

			// Check for adding
			if (isset ($input['unit_' . $v->getId ()]) && $input['unit_' . $v->getId ()])
			{
				$addunits[] = $v;
				$add = false;
			}

			if ($add)
			{
				$units[] = array
				(
					'id' => $v->getId (),
					'name' => $v->getDisplayName ()
				);
			}
		}

		// The spawnpionts
		$side = $mission->getPlayerSide (Neuron_GameServer::getPlayer ());
		$spawnpoints = $mission->getSpawnpoints ($side);

		// Group the spawnpoints
		$groupedspawnpoints = array ();
		foreach ($spawnpoints as $v)
		{
			if (!isset ($groupedspawnpoints[$v->getGroup ()->getId ()]))
			{
				$groupedspawnpoints[$v->getGroup ()->getId ()] = $v->getGroup ();
			}
		}

		$page->set ('spawnpoints', $groupedspawnpoints);

		// Add the units
		if (count ($addunits) > 0)
		{
			$spanwpointId = null;
			if (isset ($input['spawnpoint']))
			{
				$spanwpointId = $input['spawnpoint'];
			}

			$army = $mission->addSquads (Neuron_GameServer::getPlayer (), $side, $addunits, $spanwpointId);
		}
		
		$page->set ('units', $units);
		
		return $page->parse ('buildings/clanportals_mission.phpt');
	}
	
	/*
		Return the amount of used resources 
		(only the amount used for actually building the building)
	*/
	public function getUsedAssets ($includeUpgradeRunes = false)
	{
		return $this->calcUsedAssets ($includeUpgradeRunes);
	}
	
	public function getCustomContent ($input)
	{
		return $this->getGeneralContent ();
	}

	public function getConstructionTime ($village)
	{
		return 0;
	}
}
?>
