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

class Dolumar_Buildings_Portal 
	extends Dolumar_Buildings_Building
{
	public function canBuildBuilding (Dolumar_Players_Village $village)
	{
		return false;
	}
	
	public function getUsedAssets ($includeUpgradeRunes = false)
	{
		return array
		(
			'runes' => array (),
			'resources' => array ()
		);
	}
	
	public function isUpgradeable ()
	{
		return false;
	}
	
	public function isDestructable ()
	{
		return false;
	}
	
	public function getMyContent ($input, $original = false)
	{
		if ($original)
		{
			return parent::getMyContent ($input);
		}
		else
		{
			return $this->getGeneralContent ();
		}
	}
	
	public function getGeneralContent ($showAll = false)
	{
		// Fetch thze portal
		$portals = Dolumar_Map_Portal::getFromBuilding ($this);
		
		/*
		if (count ($portals) == 0)
		{
			return '<p class="false">This portal leads to nowhere...</p>';
		}
		*/
		
		$targets = array ();
		foreach ($portals as $v)
		{
			$village = $v->getOtherSide ($this->getVillage ());
			
			$targets[] = $village->getDisplayName ();
		}
	
		$page = new Neuron_Core_Template ();
		
		$destroydate = $this->getDestroyDate ();
		if ($destroydate)
		{
			$page->set ('timeleft', Neuron_Core_Tools::getCountdown ($this->getDestroyDate ()));
		}
		
		$page->set ('targets', $targets);
		
		return $page->parse ('buildings/portal.phpt');
	}
	
	public function getMapColor ()
	{
		return array (0, 0, 255);
	}
	
	public function getScore ()
	{
		return 0;
	}
}
?>
