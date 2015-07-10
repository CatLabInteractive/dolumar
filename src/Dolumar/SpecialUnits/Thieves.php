<?php
class Dolumar_SpecialUnits_Thieves extends Dolumar_SpecialUnits_SpecialUnits
{
	public function getWindowAction ()
	{
		return 'Thievery';
	}

	/*
	public function getEffects ()
	{
		return array_merge
		(
			parent::getEffects (),
			array 
			(
				new Dolumar_Effects_Boost_PoisonWater ()
			)
		);
	}
	*/

	public function getTrainingCost ()
	{
		return array 
		(
			'gold' => 200,
			'grain' => 500
		);
	}
	
	protected function getAvailableEffects ()
	{
		return array ();
	}
	
	// On fail = kill unit!
	public function onFail () 
	{
		$this->killUnit ();
	}
}
?>
