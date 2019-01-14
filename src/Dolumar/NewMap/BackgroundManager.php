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

class Dolumar_NewMap_BackgroundManager
	implements Neuron_GameServer_Map_Managers_BackgroundManager
{
	private $map;

	public function __construct (Dolumar_Map $map)
	{
		$this->map = $map;
	}

	public function getLocation (Neuron_GameServer_Map_Location $location, $objectcount = 0)
	{
		// Fetch the image from the old map object
		$l = $this->map->getLocation ($location->x (), $location->y (), $objectcount > 0);
		
		$color = $l->getMapColor ();
		$image = $l->getImage ();
		
		// Make a new sprite
		$out = new Neuron_GameServer_Map_Display_Sprite (STATIC_URL . 'images/tiles/' . $image['image'] . '.gif');
		
		$out->setColor (new Neuron_GameServer_Map_Color ($color[0], $color[1], $color[2]));
		
		return array ($out);
	}
	
	public function getTileSize ()
	{
		return array (200, 100);
	}
}