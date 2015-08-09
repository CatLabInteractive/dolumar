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

class Dolumar_Windows_Units extends Neuron_GameServer_Windows_Window
{
	private $village;

	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$login = Neuron_Core_Login::__getInstance ();
		
		$data = $this->getRequestData ();
		$this->village = Dolumar_Players_Village::getMyVillage ($data['vid']);
	
		// Window settings
		$this->setSize ('425px', '300px');
		
		$this->setAllowOnlyOnce ();

		if ($login->isLogin () && $this->village->isFound ())
		{
			$this->setTitle ($text->get ('units', 'menu', 'main').
				' ('.Neuron_Core_Tools::output_varchar ($this->village->getName ()).')');
		}

		else
		{
			$this->setTitle ($text->get ('units', 'menu', 'main'));
		}
	}
	
	public function getContent ()
	{
		$login = Neuron_Core_Login::__getInstance ();
		$text = Neuron_Core_Text::__getInstance ();
		if ($this->village && $this->village->isFound () && $this->village->getOwner()->getId() == $login->getUserId ())
		{
			return $this->getCurrentUnits ($this->village);
		}
		else
		{
			return '<p class="false">'.$text->get ('login', 'login', 'account').'</p>';
		}
	}

	private function getCurrentUnits ($village)
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('unit');
		$text->setSection ('units');
		
		//$units = $village->getDefendingUnits ();
		$units = $village->getAllUnits ();

		$page = new Neuron_Core_Template ();

		// Print stat names
		Dolumar_Units_Unit::printStatNames ($page);

		$page->set ('noUnits', $text->get ('noUnits'));
		$page->set ('about', $text->get ('about'));
		$page->set ('villageId', $this->village->getId ());
		$page->set ('squads', $text->getClickTo ($text->get ('toSquads')));

		foreach ($units as $v)
		{
			$page->addListValue
			(
				'units',
				array
				(
					'name' => Neuron_Core_Tools::output_varchar ($v->getName ()),
					'stats' => $v->getStats ($village),
					'available' => $v->getAvailableAmount (),
					'total' => $v->getTotalAmount (),
					'consumption' => $v->getCurrentConsumption_text ($village),
					'type' => $v->getAttackType_text (),
					'image' => $v->getImageUrl ()
				)
			);
		}

		$page->sortList ('units');

		return $page->parse ('units.tpl');
	}
	
	/*
		This is a HTML helper that will return the HTML
		to display unit stats.
	*/
	public static function getUnitStatsHTML ($objUnit)
	{
		$page = new Neuron_Core_Template ();
		
		// Ugly, but easy ;)
		Dolumar_Units_Unit::printStatNames ($page);
		
		// Ugly again, but easy (again)
		$v = array ();
		$v['stats'] = $objUnit->getStats ();
		
		$page->set ('unit', $v);
		
		return $page->parse ('units/stats.phpt');
	}
}

?>
