<?php
class Dolumar_Effects_Boost_BattleSlotForest extends Dolumar_Effects_Boost
{
	protected $sType = 'terrain';
	
	public function procUnitStats (&$stats, $unit)
	{
		$stats['defAr'] += 60;
	}
}
?>