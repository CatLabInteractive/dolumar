<?php

class Dolumar_Buildings_Archery extends Dolumar_Buildings_Training
{
	// Units
	protected function getAvailableUnits ()
	{
		return array ('Archers');
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
