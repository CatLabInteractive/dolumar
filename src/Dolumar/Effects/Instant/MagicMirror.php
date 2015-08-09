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

class Dolumar_Effects_Instant_MagicMirror extends Dolumar_Effects_Instant
{
	private $report = false;

	public function requiresTarget ()
	{
		return true;
	}

	public function execute ($a = null, $b = null, $c = null)
	{
		$buildings = $this->getTarget ()->buildings->getBuildings ();
		
		$report = new Dolumar_Report_BuildingReport ($this->getVillage ());
		
		$report->setTarget ($this->getTarget ());
		
		foreach ($buildings as $v)
		{
			$report->addBuilding ($v);
		}
		
		$report->store ();
		
		$this->addLogData ($report);
		
		$this->setMessageParameters
		(
			array
			(
				'report' => $report->getDisplayName ()
			)
		);
		
		$this->report = $report;
	}
	
	public function getExtraContent ()
	{
		if (isset ($this->report))
		{
			return $this->report->__toString ();
		}
	}
	
	protected function getCostFromLevel ()
	{
		return 10;
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 2;
	}
}
?>
