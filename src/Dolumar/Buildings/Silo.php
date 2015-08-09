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

class Dolumar_Buildings_Silo extends Dolumar_Buildings_Building
{

	private $capacity = null;

	/*
	protected function calculateCapacity ($extra = 1)
	{
		$level = $this->getLevel ();
		$income = 1000;
		$income *= $extra;
		
		// For each level : +110%
		$income += ($income / 100) * $level * 110;
		
		$income = floor ($income);
		
		return $income;
	}
	*/

	private function loadCapacity ()
	{
		// (A2 = level ;; (A2*8000)+((A2*8000)/100)*(A2-1)*10
		if ($this->capacity === null)
		{
			$this->capacity = $this->calculateCapacity ();
		}
	}
	
	/*
		Calculate the capacity
	*/
	private function calculateCapacity ($l = null)
	{
		$o = array ();
		
		if (!isset ($l))
		{
			$l = $this->getLevel ();
		}
		
		$c = 8000;
		
		// Percentage that the capacity "grows" per level
		$g = 10;
		
		$capacity = ($l * $c) + ((($l * $c) / 100) * ($l - 1) * $g);

		$o['gold'] = $capacity;			
		$o['grain'] = $capacity;
		$o['wood'] = $capacity;
		$o['stone'] = $capacity;
		$o['iron'] = $capacity;
		$o['gems'] = floor ($capacity / 10);

		return Neuron_Core_Tools::floor_array
		(
			$this->getVillage ()->procBonusses ('procCapacity', array ($o, $this))
		);
	}

	public function getCapacity ()
	{
		$this->loadCapacity ();
		return $this->capacity;
	}

	protected function getCustomContent ($input)
	{
		$text = Neuron_Core_Text::__getInstance ();
		
		$text->setFile ('buildings');
		$text->setSection ('silo');
		
		$page = new Neuron_Core_Template ();
		$page->set ('silo', $text->get ('silo'));
		
		$capacity = $this->getCapacity ();
		$cs = $this->getVillage ()->resources->getCapacityStatus ();
		
		$page->set ('resource', $text->get ('resource'));
		$page->set ('capacity', $text->get ('capacity'));
		$page->set ('filling', $text->get ('filling'));
		
		foreach ($capacity as $k => $v)
		{
			$page->addListValue
			(
				'resources',
				array
				(
					ucfirst ($text->get ($k, 'resources', 'main')),
					$cs[$k],
					$cs[$k],
					(100 - $cs[$k]),
					$v
				)
			);
		}
		
		return $page->parse ('buildings/silo.tpl');
	}

	public function canBuildBuilding (Dolumar_Players_Village $village)
	{
		//return $village->buildings->getBuildingAmount (11) > 0;
		return true;
	}
	
	/*
		Returns a small upgrade message
	*/
	protected function getUpgradeInformation ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		$page = new Neuron_Core_Template ();
		
		$now = $this->calculateCapacity ($this->getLevel () + 0);
		$later = $this->calculateCapacity ($this->getLevel () + 1);
		
		$page->set ('current_level', $this->getLevel ());
		$page->set ('next_level', ($this->getLevel () + 1));
		
		foreach ($now as $k => $v)
		{
			$page->addListValue
			(
				'resources',
				array
				(
					'name' => ucfirst ($text->get ($k, 'resources', 'main')),
					'now' => $now[$k],
					'later' => $later[$k]
				)
			);
		}
		
		return $page->parse ('buildings/silo_up.phpt');
	}
}

?>
