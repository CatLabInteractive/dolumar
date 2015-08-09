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

class Dolumar_Effects_Instant_Tornado extends Dolumar_Effects_Instant
{
	public function requiresTarget ()
	{
		return true;
	}

	/*
		Destroy one random building
	*/
	public function execute ($a = null, $b = null, $c = null)
	{
		$buildings = $this->getTarget ()->buildings->getBuildings ();
		shuffle ($buildings);
		
		$level = $this->getLevel ();
		
		foreach ($buildings as $v)
		{
			if ($v->getLevel () <= $level)
			{
				$v->doDestructBuilding (false, NOW, false);
				
				Dolumar_Players_Logs::getInstance ()
					->addDestroyBuildingLog 
					(
						$this->getVillage (), 
						$this->getTarget (), 
						$v
					);
				
				return true;
			}
		}
		
		return false;
	}
	
	protected function getCostFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
				return 20;
			break;
			
			case 2:
				return 35;			
			break;
			
			case 3:
				return 55;
			break;
			
			case 4:
				return 80;
			break;
			
			case 5:
				return 100;
			break;
		}
	}
	
	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'level' => $this->getLevel ()
			)
		);
	}
	
	public function getDifficulty ($iBaseAmount = 40)
	{
		return parent::getDifficulty (70);
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 7;
	}
}
?>
