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

class Dolumar_Buildings_Tower extends Dolumar_Buildings_Building
{
	protected function getCustomContent ($input)
	{
		$page = new Neuron_Core_Template ();
		
		$page->set ('description', $this->getDescription ());
		
		$runes = $this->getVillage ()->resources->getUsedRunes_amount ();
		$percentage = $this->getVillage ()->battle->getTowerPercentage ();
		$bonus = $this->getVillage ()->battle->getDefenseBonus ();
		
		$page->set ('runes', $runes);
		$page->set ('percentage', round ($percentage));
		$page->set ('bonus', round ($bonus));
		
		return $page->parse ('buildings/tower.phpt');
	}
	
	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		$this->addRequiresBuilding (11);
	}	
}

?>
