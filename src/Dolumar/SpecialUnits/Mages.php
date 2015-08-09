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

class Dolumar_SpecialUnits_Mages extends Dolumar_SpecialUnits_SpecialUnits
{
	/*
		General special unit actions
	*/
	public function getWindowAction ()
	{
		return 'Magic';
	}
	
	/*
	public function getEffects ()
	{
		return array_merge
		(
			parent::getEffects (),
			array 
			(
				new Dolumar_Effects_Boost_RainSeason (),
				new Dolumar_Effects_Boost_DryLands ()
			)
		);
	}
	*/
	
	public function getTrainingCost ()
	{
		return array 
		(
			'gems' => 50,
			'gold' => 200
		);
	}
}
?>
