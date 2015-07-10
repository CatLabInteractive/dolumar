<?php
class Dolumar_Effects_Boost_BattleSlotRuins extends Dolumar_Effects_Boost
{
	protected $sType = 'terrain';
	
	public function procUnitStats (&$stats, $unit)
	{
		$stats['defMag'] += 50;
	}
}
?>