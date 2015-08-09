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

class Dolumar_Underworld_Map_Locations_Explored 
	extends Dolumar_Underworld_Map_Locations_Location
{
	public function isPassable ()
	{
		return false;
	}

	private function isExplored (Dolumar_Underworld_Map_BackgroundManager $map, Neuron_GameServer_Map_Location $location)
	{
		$status = $map->getExploreStatus ($location);
		return $status !== Dolumar_Underworld_Map_FogOfWar::VISIBLE;
	}
	
	public function getTile (Dolumar_Underworld_Map_BackgroundManager $map)
	{	
		$lb = intval ($this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () - 1, $this->y () + 0)));
		$rb = intval ($this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () + 0, $this->y () + 1)));
		$ro = intval ($this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () + 1, $this->y () + 0)));
		$lo = intval ($this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () + 0, $this->y () - 1)));

		$l = intval ($lb && $rb && $this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () - 1, $this->y () + 1)));
		$d = intval ($rb && $ro && $this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () + 1, $this->y () + 1)));
		$r = intval ($ro && $lo && $this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () + 1, $this->y () - 1)));
		$u = intval ($lb && $lo && $this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () - 1, $this->y () - 1)));

		/*
		$hLinksBoven = !($l && $b && $lb->isWater ()) ? '0' : '1';
		$hRechtsBoven = !($r && $b && $rb->isWater ()) ? '0' : '1';
		$hRechtsOnder = !($r && $o && $ro->isWater ()) ? '0' : '1';
		$hLinksOnder = !($l && $o && $lo->isWater ()) ? '0' : '1';
		*/

		$bits  = $l . $rb . $d;
		$bits .= $lb . $ro;
		$bits .= $u . $lo . $r;

		return new Neuron_GameServer_Map_Display_Sprite (STATIC_URL . 'images/underworld/myst/' . 'myst_' . $bits . '.png');
	}
}
?>
