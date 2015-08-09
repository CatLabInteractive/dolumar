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

class Dolumar_NewMap_Object
	extends Neuron_GameServer_Map_MapObject
{
	private $building;

	public function __construct (Dolumar_Buildings_Building $building)
	{
		$this->building = $building;
		
		$id = $this->building->getId ();
		$onclick = "openWindow ('building', {'bid':".$id."});";
		
		$this->observe ('click', $onclick);
	}
	
	public function getDisplayObject ()
	{
		return $this->building->getDisplayObject ();
	}
	
	public function getName ()
	{
		return $this->building->getName ();
	}
}
?>
