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

class Dolumar_Effects_Boost_SandStorm extends Dolumar_Effects_Boost
{
	protected function getBonusFromLevel ()
	{
		//return 10 + $this->getLevel () * 15;
		return 30;
	}
	
	public function getDuration ()
	{
		switch ($this->getLevel ())
		{
			case 1:
			default:
				$duration = 6;
			break;
			
			case 2:
				$duration = 7;
			break;
			
			case 3:
				$duration = 8;
			break;
			
			case 4:
				$duration = 9;
			break;
			
			case 5:
				$duration = 10;
			break;
		}
	
		return ($duration * 60) / GAME_SPEED_EFFECTS;
	}

	public function procUnitStats (&$stats, $unit)
	{
		$stats['speed'] -= ($stats['speed'] / 100) * $this->getBonusFromLevel ();
	}
	
	protected function getCostFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
				return 10;
			break;
			
			case 2:
				return 12;
			break;
			
			case 3:
				return 15;
			break;
			
			case 4:
				return 19;
			break;
			
			case 5:
				return 25;
			break;
		}
	}
	
	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'bonus' => $this->getBonusFromLevel ()
			)
		);
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 4;
	}
}
?>
