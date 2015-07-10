<?php

class Dolumar_Buildings_Stable extends Dolumar_Buildings_Training
{
	// Units
	protected function getAvailableUnits ()
	{
		return array ('Cavalry', 'Knights');
	}

	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		$this->addRequiresBuilding (20);
	}	
}

?>
