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

abstract class Dolumar_Underworld_Map_Object
	extends Neuron_GameServer_Map_MapObject
{
	public function __construct ()
	{
		$id = $this->getId ();
	
		$onclick = "openWindow ('Army', {'id':".$id."});";
		$this->observe ('click', $onclick);
	}
	
	public function getDisplayObject ()
	{
		$url = STATIC_URL . 'images/underworld/objects/team' . (($this->getSide ()->getId () % 4) + 1) . '.png';
	
		$offset = new Neuron_GameServer_Map_Offset (0, 0);
		$image = new Neuron_GameServer_Map_Display_Sprite ($url, $offset);
		
		return $image;
	}
	
	public function getName ()
	{
		return 'Army';
	}
}
?>
