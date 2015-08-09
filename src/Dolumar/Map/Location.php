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

class Dolumar_Map_Location implements Neuron_GameServer_Interfaces_Map_Location
{		
	public static function getLocation ($x, $y, $hasBuilding = false)
	{
		return Dolumar_Map_Map::getLocation ($x, $y, $hasBuilding);
	}
	
	protected $x, $y, $height, $randomNumber;
	
	public function __construct ($randomNumber, $x, $y, $iHeight)
	{
		$this->randomNumber = $randomNumber;
		$this->x = $x;
		$this->y = $y;
		$this->height = $iHeight;
	}
	
	public function getImage ()
	{
		return array
		(
			'image' 	=> 'grass'.(($this->randomNumber % 15) + 1)
		);
	}
	
	public function getImageName ()
	{
		$img = $this->getImage ();
		return $img['image'];
	}
	
	public function getHeight ()
	{
		return $this->height;
	}
	
	public function getMapColor ()
	{
		return array (105, 178, 0);
	}

	public function canBuildBuilding ()
	{
		return true;
	}

	public function getIncomeBonus ()
	{
		return array ();
	}

	public function isWater ()
	{
		return false;
	}

	public function getHeightIntencity ()
	{
		return min (((floor ($this->getHeight ())) / 12) + 0.4, 1);
	}
}

?>
