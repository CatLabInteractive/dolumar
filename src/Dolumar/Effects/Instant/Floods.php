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

class Dolumar_Effects_Instant_Floods extends Dolumar_Effects_Instant
{
	public function requiresTarget ()
	{
		return true;
	}
	
	protected function getBonusFromLevel ()
	{
		//return 10 + $this->getLevel () * 15;
		
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 4 * 60 * 60;
			break;
			
			case 2:
				return 4.5 * 60 * 60;
			break;
			
			case 3:
				return 5 * 60 * 60;
			break;
			
			case 4:
				return 5.5 * 60 * 60;
			break;
			
			case 5:
				return 6 * 60 * 60;
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

	public function execute ($a = null, $b = null, $c = null)
	{
		$buildings = $this->getTarget ()->buildings->getBuildings ();
		
		foreach ($buildings as $v)
		{
			if (!$v->isFinished ())
			{
				$v->delayBuild ($this->getBonusFromLevel ());
			}
		}
	}
	
	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'delay' => Neuron_Core_Tools::getDurationText ($this->getBonusFromLevel (), false)
			)
		);
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 6;
	}
}
?>
