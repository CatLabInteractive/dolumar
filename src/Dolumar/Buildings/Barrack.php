<?php

class Dolumar_Buildings_Barrack extends Dolumar_Buildings_Training
{
	// Units
	protected function getAvailableUnits ()
	{	
		return array ('Spearmen', 'Longswordsmen');
	}

	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		$this->addRequiresBuilding (13);
	}	
}

?>
