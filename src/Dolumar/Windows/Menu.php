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

class Dolumar_Windows_Menu extends Neuron_GameServer_Windows_Window
{

	private $SHOW_ONLY_VILLAGE = false;

	public function setSettings ()
	{

		// Window settings
		$this->setNoBorder ();
		$this->setSize ('100%', '41px');
		$this->setPosition ('0px', '0px');
		$this->setFixed ();
		$this->setZ (10000);
		$this->setClass ('topmenu');
		
		$this->setType ('panel');
		
		$this->setAllowOnlyOnce ();
	
	}

	public function getRefresh ()
	{
		//$this->updateContent ();
	}
	
	public function getContent ()
	{
		$login = Neuron_Core_Login::__getInstance ();
	
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('main');
		$text->setSection ('menu');
	
		$page = new Neuron_Core_Template ();
		
		// Add text elements
		$page->set ('myAccount', Neuron_Core_Tools::output_varchar ($text->get ('myAccount')));
		$page->set ('build', Neuron_Core_Tools::output_varchar ($text->get ('build')));
		$page->set ('bonusbuild', Neuron_Core_Tools::output_varchar ($text->get ('bonusbuild')));
		$page->set ('economy', Neuron_Core_Tools::output_varchar ($text->get ('economy')));
		$page->set ('preferences', Neuron_Core_Tools::output_varchar ($text->get ('preferences')));
		$page->set ('help', Neuron_Core_Tools::output_varchar ($text->get ('help')));
		$page->set ('language', Neuron_Core_Tools::output_varchar ($text->get ('language')));
		$page->set ('ranking', Neuron_Core_Tools::output_varchar ($text->get ('ranking')));
		$page->set ('units', Neuron_Core_Tools::output_varchar ($text->get ('units')));
		$page->set ('chat', Neuron_Core_Tools::output_varchar ($text->get ('chat')));
		$page->set ('battle', Neuron_Core_Tools::output_varchar ($text->get ('battle')));
		$page->set ('battleCalc', Neuron_Core_Tools::output_varchar ($text->get ('battleCalc')));
		$page->set ('forum', Neuron_Core_Tools::output_varchar ($text->get ('forum')));
		$page->set ('ingameForum', Neuron_Core_Tools::output_varchar ($text->get ('ingameForum')));
		$page->set ('contact', Neuron_Core_Tools::output_varchar ($text->get ('contact')));
		$page->set ('equipment', Neuron_Core_Tools::output_varchar ($text->get ('equipment')));
		$page->set ('magic', Neuron_Core_Tools::output_varchar ($text->get ('magic')));
		$page->set ('invite', Neuron_Core_Tools::output_varchar ($text->get ('friendinvite')));

		if (!defined ('HIDE_IMPRINT') || !HIDE_IMPRINT)
		{
			$page->set ('imprint', Neuron_Core_Tools::output_varchar ($text->get ('imprint')));
		}

		$page->set ('simulator', Neuron_Core_Tools::output_varchar ($text->get ('simulator')));
		
		$localized_forum = str_replace ('{lang}', $text->getCurrentLanguage (), FORUM_URL);
		
		$page->set ('forum_url', $localized_forum);
		
		$page->set ('ignorelist', $text->get ('ignorelist'));
		
		$page->set ('flag', $text->getCurrentLanguage ());
		
		$noVillage = true;
		if ($login->isLogin ())
		{
			$me = Neuron_GameServer::getPlayer ();

			$page->set ('messages', $text->get ('messages'));
			
			$villages = $me->getVillages ();
			if (count ($villages) > 0)
			{
				$noVillage = false;
			
				if (count ($villages) > 1 || ($this->SHOW_ONLY_VILLAGE && count ($villages) > 0))
				{
					foreach ($villages as $v)
					{
						// Add the links
						$page->addListValue ('villages', array 
						(
							Neuron_Core_Tools::output_varchar ($v->getName ()), 
							$v->getId ()
						));
					}
					$page->sortList ('villages');
				}
				
				// Set the current village ID.
				$page->set ('vid', $me->getCurrentVillage ()->getId ());
			}

			// Administration links
			if ($me->isModerator ())
			{
				$page->set ('adminForum', 'Admin Forum');
			}
			
			// Clans
			foreach ($me->getClans () as $v)
			{
				$page->addListValue
				(
					'clans',
					array
					(
						'id' => $v->getId (),
						'name' => Neuron_Core_Tools::output_varchar ($v->getName ())
					)
				);
			}
			
			$page->set ('donation', $text->get ('donation'));
		}
		
		if ($noVillage)
		{
			$page->set ('vid', 0);
		}

		// Premium
		/*
		$player = Neuron_GameServer::getPlayer ();
		
		if ($player)
		{
			$credits = $player->getCredits ();
			$page->set ('premium', $credits);
		}
		*/
		
		return $page->parse ('menu.tpl');
	
	}

}

?>
