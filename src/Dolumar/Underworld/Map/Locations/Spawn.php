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

class Dolumar_Underworld_Map_Locations_Spawn
	extends Dolumar_Underworld_Map_Locations_Floor
{
	private $group;

	public function __construct ($x, $y, Dolumar_Underworld_Map_Spawngroup $group)
	{
		parent::__construct ($x, $y);
		$this->setGroup ($group);
	}

	public function isSide (Dolumar_Underworld_Models_Side $side)
	{
		if ($side->getId () == $this->group->getId ())
		{
			return true;
		}
		return false;
	}

	public function getTile (Dolumar_Underworld_Map_BackgroundManager $map)
	{
		return new Neuron_GameServer_Map_Display_Sprite ($this->getTileDir () . 'spawn.png');
	}

	public function setGroup (Dolumar_Underworld_Map_Spawngroup $group)
	{
		$this->group = $group;
	}

	public function getGroup ()
	{
		return $this->group;
	}
}