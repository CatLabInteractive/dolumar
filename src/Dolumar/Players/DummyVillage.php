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

class Dolumar_Players_DummyVillage 
	extends Dolumar_Players_Village
{
	private $race;

	public function __construct ($race = null)
	{
		parent::__construct (null);
		
		$this->race = $race;
	}
	
	public function getName ()
	{
		return 'Dummy village';
	}
	
	public function setRace ($race)
	{
		$this->race = $race;
	}
	
	public function getRace ()
	{
		return $this->race;
	}
	
	public function getActiveBoosts ($since = NOW, $now = NOW)
	{
		return array ();
	}
	
	public function getDefenseSlots ($amount = null)
	{
		$out = array ();
		for ($i = 1; $i <= $amount; $i ++)
		{
			$out[$i] = Dolumar_Battle_Slot_Grass::getRandomSlot ($i, $this);
		}
		return $out;
	}

	public function getOwner ()
	{
		return new Dolumar_Players_NPCPlayer (null);
	}
}
?>
