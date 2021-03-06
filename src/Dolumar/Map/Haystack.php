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

class Dolumar_Map_Haystack extends Dolumar_Map_Location
{
	public function getImage ()
	{
		return array
		(
			'image' 	=> 'haystack',
			'width'		=> 200,
			'height'	=> 200
		);
	}

	public function getMapColor ()
	{
		return array (255, 255, 100);
	}
	
	public function canBuildBuilding ()
	{
		return false;
	}

	public function getIncomeBonus ()
	{
		//return array ();
		return array ('grain' => 20);
	}
}

?>
