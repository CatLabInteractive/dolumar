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

class Dolumar_Windows_MyAccount extends Neuron_GameServer_Windows_MyAccount
{
	protected function onLogin ()
	{
		openNewWindow ('Welcome');
		parent::onLogin ();
	}

	/*
		This player didn't select a race yet.
		This function handles the choosing of a race and the initializing
		of the player.
	*/
	protected function getPlayerInitialization ($registrationTracker = false)
	{
		// Check if we can actually register
		$server = Neuron_GameServer::getServer ();
		if (!$server->canRegister ())
		{
			return '<p>This server has gone into "endgame" mode. You can not register here anymore. But stay tuned, a new game will start soon.</p>';
		}

		$data = $this->getInputData ();
		
		$me = Neuron_GameServer::getPlayer ();
	
		if (isset ($data['race']))
		{
			// Check for clans
			$clan = isset ($data['clan']) ? intval ($data['clan']) : 0;
			$location = isset ($data['location']) ? $data['location'] : null;
		
			$objClan = false;
		
			if ($clan > 0)
			{
				$objClan = new Dolumar_Players_Clan ($clan);
				if ($objClan->isFound ())
				{
					// Check for password
					if ($objClan->isPasswordProtected ())
					{
						// Break out of the function if the password is not correct.
						if (!isset ($data['password']) || !$objClan->checkPassword ($data['password']))
						{
							return $this->requestClanPassword ($data['race'], $objClan);
						}
					}
				
					$members = $objClan->getMembers ();
				
					if (count ($members) > 0)
					{
						$member = $members[rand (0, count ($members) - 1)];
				
						// Fetch towncenter
						$village = $member->getMainVillage ();
					
						if ($village)
						{
							// Overwrite location with the location of this towncenter.
							$location = $village->buildings->getTownCenterLocation ();
						}
						else
						{
							$location = array (0, 0);
						}
					}
					else
					{
						$location = array (0, 0);
					}
				}
			}
		
			if ($me->initializeAccount ($data['race'], $location, $objClan))
			{
				// Scroll to the right location
				$me = Neuron_GameServer::getPlayer ();
				
				$home = $me->getHomeLocation ();
				$this->mapJump ($home[0], $home[1]);
		
				// Reload area
				$this->reloadLocation ($home[0], $home[1]);
		
				reloadEverything ();
				
				return $this->getContent (false);
			}
			else
			{
				//return $this->getPlayerInitialization ();
			}
		}
	
		$text = Neuron_Core_Text::__getInstance ();
	
		$text->setFile ('account');
		$text->setSection ('selectRace');
	
		$data = $this->getInputData ();
	
		// Show form
		$page = new Neuron_Core_Template ();

		$error = Neuron_GameServer::getPlayer ()->getError ();
		if (!empty ($error))
		{
			$page->set ('error', $text->get ($error, 'errors', 'account', $error));
		}
	
		// Loop trough races
		foreach (Dolumar_Races_Race::getRaces () as $k => $v)
		{
			$race = Dolumar_Races_Race::getFromId ($k);
			if ($race->canPlayerSelect (Neuron_GameServer::getPlayer ()))
			{
				$page->addListValue ('races', array
				(
					$text->get ($v, 'races', 'races', $v),
					$text->get ($v, 'desc', 'races', 'null'),
					$k
				));
			}
		}
		$page->sortList ('races');
	
		// Some text values
		$page->set ('submit', $text->get ('submit'));
		$page->set ('select', $text->get ('select'));
		$page->set ('location', $text->get ('location'));
	
		if ($registrationTracker === true)
		{
			$tracker = Neuron_GameServer::getPlayer ()->getTrackerUrl ('registration');
			$page->set ('tracker_url', htmlentities ($tracker));
		}
	
		// Locations
		$page->addListValue ('directions', array ($text->get ('r', 'directions', 'main'), 'r'));
		$page->addListValue ('directions', array ($text->get ('n', 'directions', 'main'), 'n'));
		$page->addListValue ('directions', array ($text->get ('ne', 'directions', 'main'), 'ne'));
		$page->addListValue ('directions', array ($text->get ('e', 'directions', 'main'), 'e'));
		$page->addListValue ('directions', array ($text->get ('es', 'directions', 'main'), 'es'));
		$page->addListValue ('directions', array ($text->get ('s', 'directions', 'main'), 's'));
		$page->addListValue ('directions', array ($text->get ('sw', 'directions', 'main'), 'sw'));
		$page->addListValue ('directions', array ($text->get ('w', 'directions', 'main'), 'w'));
		$page->addListValue ('directions', array ($text->get ('wn', 'directions', 'main'), 'wn'));
	
		// Fetch a list of all clans
		$db = Neuron_Core_Database::__getInstance ();
	
		$clans = $db->select
		(
			'clans',
			array ('*')
		);
	
		// Add a list of all clans ;-)
		foreach ($clans as $v)
		{
			$clan = new Dolumar_Players_Clan ($v['c_id'], $v);
		
			$page->addListValue
			(
				'clans',
				array
				(
					'id' => $clan->getId (),
					'name' => Neuron_Core_Tools::output_varchar ($clan->getName ()),
					'isLocked' => $clan->isPasswordProtected (),
					'isFull' => $clan->isFull ()
				)
			);
		}
	
		return $page->parse ('account/selectRace.phpt');
	}
	
	protected function requestClanPassword ($race, $objClan)
	{
		$data = $this->getInputData ();
		$page = new Neuron_Core_Template ();
		
		$page->set ('wrongPass', isset ($data['password']));
		
		$page->set ('race', $race);
		$page->set ('clan', $objClan->getId ());
		
		$page->set ('clanname', Neuron_Core_Tools::output_varchar ($objClan->getName ()));
		
		return $page->parse ('gameserver/account/clanPassword.phpt');
	}
}
?>
