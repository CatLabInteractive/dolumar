<?php

class Dolumar_Buildings_GemMine extends Dolumar_Buildings_Producing
{
	protected $RESOURCE = 'gems';
	protected $INCOME = 6;
	
	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		$this->addRequiresBuilding (2);
	}	
}
?>
