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

class Dolumar_Map_Object implements Neuron_GameServer_Interfaces_Map_Object
{
	private $building;
	
	public function __construct ($building)
	{
		$this->building = $building;
	}
	
	public function getLocation ()
	{
		return $this->building->getLocation ();
	}
	
	public function getName ()
	{
		return $this->building->getName ();
	}
	
	public function getTileOffset ()
	{
		return $this->building->getTileOffset ();
	}
	
	public function getOnClick ()
	{
		$id = $this->building->getId ();
		return "openWindow ('building', {'bid':".$id."});";
	}
	
	public function getImageURL ()
	{
		return $this->building->getImageUrl ();
	}
	
	public function getMapStatus ()
	{
		return $this->building->getMapStatus ();
	}
	
	public function getImageName ()
	{
		$race = strtolower ($this->building->getVillage()->getRace()->getName());
		$sName = $this->building->getIsoImage ();
			
		// Check if file exists		
		if (!empty ($race) && file_exists (IMAGE_PATH.'sprites/png/'.$race.'_'.$sName.'.png'))
		{
			$id = $race.'_'.$sName;
		}
		else
		{
			$id = $sName;
		}
	
		return $id;
	}
}
?>
