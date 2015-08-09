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

class Dolumar_Technology_ResourceBonus extends Dolumar_Technology_Technology
{
	private $sResource = 'grain';
	private $iBonusPercentage = 10;
	private $iBonusAmount = 0;

	public function setStats ($stats)
	{
		if (isset ($stats['bonus']))
		{
			// Check for percentage sign:
			if (substr ($stats['bonus'], -1) == '%')
			{
				$this->iBonusPercentage = intval (substr ($stats['bonus'], 0, -1));
			}
			else
			{
				$this->iBonusAmount = intval ($stats['bonus']);
			}
		}
		
		// Check for resource
		if (isset ($stats['resource']))
		{
			$this->sResource = $stats['resource'];
		}
	}

	/*
		Raise the selected resoruce with the selected bonus.
		@return array of resources
	*/
	public function procIncome ($resources, $building)
	{
		/*
		if ($building->getClassName () == 'Farm')
		{
			foreach ($resources as $k => $v)
			{
				$resources[$k] = $v + ($v / 20);
			}
		}
		return $resources;
		*/
		
		// Add percentage to resource
		if (isset ($resources[$this->sResource]) && $resources[$this->sResource] > 0)
		{
			$resources[$this->sResource] += ($resources[$this->sResource] * ($this->iBonusPercentage / 100)) + $this->iBonusAmount;
		}
		
		return $resources;
	}
}
?>
