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

class Dolumar_Effects_Boost_SummonGhosts extends Dolumar_Effects_Boost
{
	protected $sType = 'magic';
	protected $iDuration = 43200;

	protected function getBonusFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 8;
			break;
			
			case 2:
				return 9;
			break;
			
			case 3:
				return 10;
			break;
			
			case 4:
				return 11;
			break;
			
			case 5:
				return 12;
			break;
		}
	}
	
	protected function getCostFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
				return 10;
			break;
			
			case 2:
				return 11;			
			break;
			
			case 3:
				return 13;
			break;
			
			case 4:
				return 16;
			break;
			
			case 5:
				return 20;
			break;
		}
	}

	public function onBattleFought ($battle)
	{
		if ($battle->isDefender ($this->getVillage ()))
		{
			$this->cancel ();
		}
	}
	
	public function procUnitStats (&$stats, $unit)
	{
		$stats['hp'] -= ($stats['hp'] / 100) * $this->getBonusFromLevel ();
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
		return 2;
	}
}
?>
