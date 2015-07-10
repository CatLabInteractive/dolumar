<?php

class Dolumar_Buildings_Lumber extends Dolumar_Buildings_Producing
{
	protected $RESOURCE = 'wood';
	
	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		$this->addRequiresBuilding (10);
	}	
}

?>
