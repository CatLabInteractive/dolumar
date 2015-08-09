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

class Dolumar_Windows_Preferences extends Neuron_GameServer_Windows_Window
{

	// Let's store the interface interactive stuff in cookies ;-)
	public function setSettings ()
	{
	
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('200px', '200px');
		$this->setTitle ($text->get ('preferences', 'menu', 'main'));
		
		$this->setAllowOnlyOnce ();
	
	}
	
	public function getContent ($language = false)
	{
		$login = Neuron_Core_Login::__getInstance ();
		$text = Neuron_Core_Text::__getInstance ();
		
		$text->setFile ('account');
		$text->setSection ('preferences');
		
		if ($login->isLogin ())
		{
			$player = Neuron_GameServer::getPlayer ();
			
			$page = new Neuron_Core_Template ();
			
			// Click option
			$page->set ('preferences', $text->get ('preferences'));
			
			$page->set ('openBuilding', $text->get ('openBuilding'));
			$page->set ('singleClick', $text->get ('singleClick'));
			$page->set ('doubleClick', $text->get ('doubleClick'));
			
			$page->set ('advertisement', $text->get ('advertisement'));
			$page->set ('showAdvertisement', $text->get ('showAdvertisement'));
			$page->set ('hideAdvertisement', $text->get ('hideAdvertisement'));
			
			$pref = $player->getPreferences ();
			
			if ($pref['buildingClick'] == 1)
			{
				$page->set ('openBuilding_value', 'doubleClick');
			}
			
			else
			{
				$page->set ('openBuilding_value', 'singleClick');
			}
			
			// Mini map position
			$page->set ('minimap', $text->get ('minimap'));
			$page->set ('change', $text->get ('change'));
			
			$c = array
			(
				1 => 'bottomLeft',
				3 => 'upperRight',
				4 => 'bottomRight',
				2 => 'upperLeft',
				5 => 'dragable'
			);
			
			$page->set ('minimap_value', $pref['minimapPosition']);
			foreach ($c as $k => $v)
			{
				$page->addListValue ('minimap', array ($k, $text->get ($v)));
			}

			$page->set ('doShowAdvertisement', $player->showAdvertisement ());
			
			return $page->parse ('preferences.tpl');
		}
		
		else
		{
			return '<p class="false">'.$text->get ('login', 'login', 'account').'</p>';
		}
	}
	
	public function processInput ()
	{
		$login = Neuron_Core_Login::__getInstance ();
		$data = $this->getInputData ();
		
		if ($login->isLogin () && isset ($data['openBuilding']) && isset ($data['minimap']) && isset ($data['advertisement']))
		{
			$myself = Neuron_GameServer::getPlayer ();
			$myself->setPreferences ($data['openBuilding'], $data['minimap'], $data['advertisement'] == '0');

			if ($data['advertisement'] == '1' && !$myself->isPremium ())
			{
				$this->alert ('You must have a premium account to disable the advertisment.');
			}
			
			$this->reloadWindow ();
		}
	}

}

?>
