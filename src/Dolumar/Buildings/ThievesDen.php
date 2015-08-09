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

class Dolumar_Buildings_ThievesDen extends Dolumar_Buildings_SpecialUnits
{
	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		$this->addRequiresBuilding (30);
	}	
	
	function getSpecialUnit ()
	{
		return new Dolumar_SpecialUnits_Thieves ($this);
	}
	
	function getSpecialUnitCapacity ()
	{
		return $this->getLevel () + 2;
	}
	
	protected function getAvailableEffects ()
	{
		return array
		(
			new Dolumar_Effects_Boost_PoisonWater (1),
			new Dolumar_Effects_Boost_PoisonWater (2),
			new Dolumar_Effects_Boost_PoisonWater (3),
			new Dolumar_Effects_Boost_PoisonWater (4),
			new Dolumar_Effects_Boost_PoisonWater (5),
			
			new Dolumar_Effects_Boost_SabotageWizards (1),
			new Dolumar_Effects_Boost_SabotageWizards (2),
			new Dolumar_Effects_Boost_SabotageWizards (3),
			new Dolumar_Effects_Boost_SabotageWizards (4),
			new Dolumar_Effects_Boost_SabotageWizards (5),
			
			new Dolumar_Effects_Boost_StealMaps (1),
			new Dolumar_Effects_Boost_StealMaps (2),
			new Dolumar_Effects_Boost_StealMaps (3),
			new Dolumar_Effects_Boost_StealMaps (4),
			new Dolumar_Effects_Boost_StealMaps (5),
			
			new Dolumar_Effects_Boost_Demoralize (1),
			new Dolumar_Effects_Boost_Demoralize (2),
			new Dolumar_Effects_Boost_Demoralize (3),
			new Dolumar_Effects_Boost_Demoralize (4),
			new Dolumar_Effects_Boost_Demoralize (5),
			
			new Dolumar_Effects_Instant_KillWizard ()
		);
	}
	
	protected function getFreeEffects ()
	{
		return array
		(
			new Dolumar_Effects_Boost_PoisonWater ()
		);
	}
}

?>
