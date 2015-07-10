<?php

class Dolumar_Buildings_Mill extends Dolumar_Buildings_Farm
{
	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		$this->addRequiresBuilding (10);
	}	
	
	public function calculateIncome ($level = null)
	{
		if (!isset ($level))
		{
			$level = $this->getLevel ();
		}
	
		$farms = $this->getVillage()->buildings->getBuildingLevelSum (10);
		$mills = $this->getVillage()->buildings->getBuildingLevelSum (16);
		
		$income = $this->INCOME * GAME_SPEED_RESOURCES;
		
		$x = 1.7 * $income * $farms * $level;
		$y = max (1, $farms + $mills);
		
		return floor ($x / $y);
	}
}
?>
