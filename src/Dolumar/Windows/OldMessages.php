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

class Dolumar_Windows_OldMessages extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('500px', '300px');
		$this->setTitle ($text->get ('messages', 'menu', 'main'));
		
		$this->setClass ('messages');
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		// Fetch thze model
		$login = Neuron_Core_Login::__getInstance ();
		$text = Neuron_Core_Text::__getInstance ();
		
		if ($login->isLogin ())
		{
			$player = Neuron_GameServer::getPlayer ();
			
			if ($player->isBanned ('messages'))
			{
				$end = $player->getBanDuration ('messages');						
				$duration = Neuron_Core_Tools::getCountdown ($end);
			
				return '<p class="false">'.
				(
					Neuron_Core_Tools::putIntoText
					(
						$text->get ('banned', 'messages', 'messages'),
						array
						(
							'duration' => $duration
						)
					)
				).'</p>';
			}
			
			elseif (!$player->isEmailVerified ())
			{
				return '<p class="false">'.$text->get ('validateEmail', 'main', 'account').'</p>';
			}
			
			else
			{
				$objMessages = new Neuron_Structure_Messages ($player);
				return $objMessages->getPageHTML ($this->getInputData ());
			}
		}
		else
		{
			$this->throwError ($text->get ('noLogin', 'main', 'main'));
		}
	}
	
	public function processInput ()
	{
		$this->updateContent ();
	}
}
?>
