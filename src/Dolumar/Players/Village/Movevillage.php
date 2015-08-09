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

class Dolumar_Players_Village_Movevillage
{
	private $village;
	private $error;

	public function __construct ($objMember)
	{
		$this->village = $objMember;
	}
	
	/*
		Return a valid location close to a disred location.
		
		@return array (x, y, offset)
	*/
	public function getValidLocation ($desired_x, $desired_y, $minimal_offset = 0)
	{
		// First, look for a proper location
		$player = $this->village->getOwner ();
		$race = $this->village->getRace ();
		
		$location = $player->calculateNewStartLocation 
		(
			array ($desired_x, $desired_y), 
			$race,
			MAXMAPSTRAAL, 
			$minimal_offset,
			array ($this, 'checkLocation')
		);
		
		return $location;
	}
	
	public function moveVillage ($x, $y)
	{
		$profiler = Neuron_Profiler_Profiler::getInstance ();
		
		$profiler->start ('Moving village '.$this->village->getName ());
	
		$profiler->message ('Desired location: ('.$x.','.$y.')');
	
		$location = $this->getValidLocation ($x, $y);
		
		if ($location)
		{
			list ($x, $y) = $location;
		
			$profiler->message ('Actual location: ('.$x.','.$y.')');
			
			$tc = $this->village->buildings->getTownCenterLocation ();
			
			$profiler->message ('Current castle location: ('.$tc[0].','.$tc[1].')');
			
			// First, calculate the relative position
			$profiler->start ('Calculating relative position.');
			
			$dx = $x - $tc[0];
			$dy = $y - $tc[1];
			
			$profiler->message ('Relative position: ('.$dx.','.$dy.')');
			
			$profiler->stop ();
			
			$profiler->start ('Moving buildings');
		
			// Fetch all thze buildings & move 'em.
			$buildings = $this->village->buildings->getBuildings ();
			
			foreach ($buildings as $v)
			{
				// Fetch original location
				list ($ox, $oy) = $v->getLocation ();
				
				$nx = $ox + $dx;
				$ny = $oy + $dy;
				
				$profiler->start ('Moving '.$v->getName ().' from ('.$ox.','.$oy.') to ('.$nx.','.$ny.')');
				
				$v->setLocation ($nx, $ny);
				
				$profiler->stop ();
			}
			
			$profiler->stop ();
		}
		
		$profiler->stop ();
	}
	
	/*
		Check if all buildings can be placed here.
	*/
	public function checkLocation ($x, $y)
	{
		// Check if all buildings are still in a buildable area
		$tc = $this->village->buildings->getTownCenterLocation ();
		
		$dx = $x - $tc[0];
		$dy = $y - $tc[1];
		
		$buildings = $this->village->buildings->getBuildings ();
		
		foreach ($buildings as $v)
		{
			// Fetch original location
			list ($ox, $oy) = $v->getLocation ();
			
			$nx = $ox + $dx;
			$ny = $oy + $dy;
			
			if (!$v->checkMapLocation ($this->village, $nx, $ny))
			{
				return false;
			}
		}
	
		return true;
	}

	public function __destruct ()
	{
		unset ($this->village);
		unset ($this->error);
	}
}
?>
