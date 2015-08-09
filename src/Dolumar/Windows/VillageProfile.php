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

class Dolumar_Windows_VillageProfile extends Dolumar_Windows_PlayerProfile
{	
	private $thisVillage;

	protected function setPlayer ()
	{
		$o = $this->getRequestData ();
		
		if (!isset ($o['village']) && isset ($o[0]))
		{
			$o['village'] = $o[0];
		}
		elseif (!isset ($o['village']))
		{
			$o['village'] = null;
		}
		
		$village = Dolumar_Players_Village::getVillage ($o['village']);
		if ($village)
		{
			$this->player = $village->getOwner ();
			$this->thisVillage = $village;
		}
		
		$this->setTitle (Neuron_Core_Tools::output_varchar ($this->player->getNickname ()));
	}
	
	public function getContent ()
	{
		if (
			(!isset ($this->thisVillage) || $this->thisVillage->isActive ())
			&& (! $this->thisVillage->getOwner () instanceof Dolumar_Players_NPCPlayer)
		)
		{
			return parent::getContent ();
		}
		
		else
		{
			// Inactive village, just show information.
			return $this->getVillageProfile ($this->thisVillage);
		}
	}
}

?>
