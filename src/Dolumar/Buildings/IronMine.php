<?php

class Dolumar_Buildings_IronMine extends Dolumar_Buildings_Producing
{
	protected $RESOURCE = 'iron';
	
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
