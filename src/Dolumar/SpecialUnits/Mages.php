<?php
class Dolumar_SpecialUnits_Mages extends Dolumar_SpecialUnits_SpecialUnits
{
	/*
		General special unit actions
	*/
	public function getWindowAction ()
	{
		return 'Magic';
	}
	
	/*
	public function getEffects ()
	{
		return array_merge
		(
			parent::getEffects (),
			array 
			(
				new Dolumar_Effects_Boost_RainSeason (),
				new Dolumar_Effects_Boost_DryLands ()
			)
		);
	}
	*/
	
	public function getTrainingCost ()
	{
		return array 
		(
			'gems' => 50,
			'gold' => 200
		);
	}
}
?>
