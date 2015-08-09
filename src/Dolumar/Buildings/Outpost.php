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

class Dolumar_Buildings_Outpost extends Dolumar_Buildings_TownCenter
{
	public function canBuildBuilding (Dolumar_Players_Village $village)
	{
		$tc = $village->buildings->getTownCenter ();
		$villages = count ($village->getOwner ()->getVillages ());
		
		if (!$tc)
		{
			return false;
		}
		
		$level = $tc->getLevel ();
		
		$max = 1;
		
		if ($level >= 10)
		{
			$max ++;
		}
		
		if ($level >= 15)
		{
			$max ++;
		}
		
		if ($level >= 18)
		{
			$max ++;
		}
		
		if ($level >= 20)
		{
			$max += ($level - 19);
		}
		
		return $villages < $max;
	}

	public function isUpgradeable ()
	{
		return false;
	}
	
	public function getMapColor ()
	{
		return array (200, 0, 0);
	}
	
	/*
		Buildinjg costs
	*/
	public function getBuildingCost ($village)
	{
		$towncenter = $village->buildings->getTownCenter ();
		
		//$vils = count ($village->getOwner ()->getVillages ());
		
		if (!$towncenter)
		{
			return array ();
		}
		
		$amount = $towncenter->getLevel ();
		
		$resources = 500000;
		
		$out = array
		(
			'runeAmount' => $amount,
			
			'gold' => $resources,
			'wood' => $resources,
			'stone' => $resources
		);
		
		return $out;
		
		//return array ();
	}
	
	public function getScore ()
	{
		return 2000;
	}
}
?>
