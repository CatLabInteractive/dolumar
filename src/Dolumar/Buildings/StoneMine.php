<?php

class Dolumar_Buildings_StoneMine extends Dolumar_Buildings_Producing
{
	protected $RESOURCE = 'stone';
	
	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		$this->addRequiresBuilding (10);
		$this->addRequiresBuilding (12);
	}	
}
?>