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

class Dolumar_Windows_Equipment extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$login = Neuron_Core_Login::__getInstance ();

		$data = $this->getRequestData ();
		if (isset ($data['vid']))
		{
			$this->village = Dolumar_Players_Village::getMyVillage ($data['vid']);
		}
		else
		{
			$this->village = false;
		}
		
		if ($login->isLogin () && $this->village && $this->village->isActive ())
		{
			$this->setTitle ($text->get ('equipment', 'menu', 'main').
				' ('.Neuron_Core_Tools::output_varchar ($this->village->getName ()).')');
		}

		else
		{
			$this->setTitle ($text->get ('equipment', 'menu', 'main'));
		}
	
		// Window settings
		$this->setSize ('350px', '400px');
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		$me = Neuron_GameServer::getPlayer ();

		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('unit');
		$text->setSection ('equipment');

		if ($this->village && $this->village->isActive () && $this->village->getOwner() && $this->village->getOwner()->getId() == $me->getId ())
		{
			$page = new Neuron_Core_Template ();

			$page->set ('noItems', $text->get ('noItems'));
			$page->set ('available', $text->get ('available'));
			$page->set ('total', $text->get ('total'));

			$equipment = $this->village->getEquipment ();
			$yourEquipment = array ();
			foreach ($equipment as $type => $items)
			{
				if (count ($items) > 0)
				{
					$yourEquipment[$type] = array
					(
						'type' => $text->get ($type, 'types', 'equipment'),
						'items' => array ()
					);

					foreach ($items as $item)
					{
						$yourEquipment[$type]['items'][] = array
						(
							$item->getName (true),
							$item->getAvailableAmount (),
							$item->getAmount (),
							Neuron_Core_Tools::output_text ($item->getStats_text ())
						);
					}
				}
			}

			$page->set ('equipment', $yourEquipment);

			return $page->parse ('equipment.tpl');
		}
		else
		{
			return '<p class="false">'.$text->get ('login', 'login', 'account').'</p>';
		}
	}
}
?>
