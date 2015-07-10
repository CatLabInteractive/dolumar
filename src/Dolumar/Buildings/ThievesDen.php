<?php

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
