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

/*
	This container groups a bunch of resources / runes.
*/
class Dolumar_Logable_Building extends Dolumar_Logable_Container
{
	public function __construct ($data)
	{
		if ($data instanceof Dolumar_Buildings_Building)
		{
			list ($x, $y) = $data->getLocation ();
		
			$data = array
			(
				'building' => $data->getBuildingId (),
				'level' => $data->getLevel (),
				'race' => $data->getVillage ()->getRace ()->getId (),
				'x' => $x,
				'y' => $y
			);
		}
		
		parent::__construct ($data);
	}

	public static function getFromId ($id)
	{
		$res = self::getDataFromId ($id);
		return new self ($res);
	}
	
	public function getName ()
	{
		$data = $this->getLogArray ();
		
		//print_r ($data);
		
		$id = $data['building'];
		$race = Dolumar_Races_Race::getFromId ($data['race']);
		
		list ($locationX, $locationY) = array ($data['x'], $data['y']);
		$level = $data['level'];
	
		$building = Dolumar_Buildings_Building::getBuilding ($id, $race, $locationX, $locationY);
		
		if ($building)
		{
			$text = Neuron_Core_Text::getInstance ();
			return $building->getName () . ' '.$text->get ('lvl', 'building', 'building').' '. $level;
		}
		else
		{
			return 'Building not found: '.print_r ($data);
		}
	}
}
?>
