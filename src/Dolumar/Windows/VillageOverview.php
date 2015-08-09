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

class Dolumar_Windows_VillageOverview extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		
		$login = Neuron_Core_Login::__getInstance ();
	
		// Window settings
		$this->setSize ('250px', '350px');

		$data = $this->getRequestData ();

		if (!isset ($data['vid']))
		{
			$data['vid'] = 0;
		}

		$this->village = Dolumar_Players_Village::getMyVillage ($data['vid']);
	
		// Window settings
		if ($login->isLogin () && $this->village->isFound () && $this->village->getOwner ()->getId () == $login->getUserId ())
		{
			$this->setTitle ($text->get ('overview', 'menu', 'main').
				' ('.Neuron_Core_Tools::output_varchar ($this->village->getName ()).')');
		}

		else
		{
			$this->setTitle ($text->get ('overview', 'menu', 'main'));
		}
		
		$this->setAllowOnlyOnce ();
		$this->setClassName ('overview');
	}
	
	public function getContent ()
	{
		if (!$this->village || !$this->village->isFound ())
		{
			return null;
		}
	
		// Fetch all buildings
		$buildings = $this->village->buildings->getBuildings ();
		
		// Group buildings
		$groups = array ();
		
		foreach ($buildings as $v)
		{
			$key = $v->getBuildingId ();
			if (!isset ($groups[$key]))
			{
				$groups[$key] = array ();
			}
			
			$groups[$key][] = $v;
		}
		
		$page = new Neuron_Core_Template ();
		
		$page->set ('groups', $groups);
		
		return $page->parse ('village/overview.phpt');
	}
}
?>
