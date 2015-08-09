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

class Dolumar_Windows_Gifts 
	extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('300px', '150px');
		$this->setTitle ($text->get ('gifts', 'menu', 'main'));
		
		$this->setAllowOnlyOnce ();
		
		$this->setCentered ();
		//$this->setModal ();
	}
	
	public function getContent ()
	{
		$player = Neuron_GameServer::getPlayer ();

		if (!$player)
		{
			return '<p>Please login first.</p>';
		}

		$input = $this->getInput ('send');

		switch ($input)
		{
			case 'send':

				return $this->getSend ();

			break;
		}
		
		if ($player && $player->isPlaying ())
		{
			$page = new Neuron_Core_Template ();
			
			return $page->parse ('dolumar/gifts/gifts.phpt');
		}
		
		return false;
	}

	private function getSend ()
	{
		$text = Neuron_Core_Text::getInstance ();
		
		$page = new Neuron_Core_Template ();

		$player = Neuron_GameServer::getPlayer ();

		$results = $player->invitePeople 
		(
			'runesender', 
			'gifts', 
			'runereceiver',
			'gifts'
		);

		if ($results['success'])
		{
			if (!empty ($results['iframe']))
			{
				$width = isset ($results['width']) ? $results['width'] : 500;
				$height = isset ($results['height']) ? $results['height'] : 400;

				$this->closeWindow ();
				Neuron_GameServer::getInstance ()->openWindow 
				(
					'Iframe',
					array 
					(
						'title' => $text->get ('gifts', 'menu', 'main'), 
						'url' => $results['iframe'], 
						'width' => $width, 
						'height' => $height
					)
				);
			}
			else
			{
				return $page->parse ('dolumar/gifts/done.phpt');
			}
		}
		else
		{
			return '<p class="false">' . $results['error'] . '</p>';
		}
	}
}
?>
