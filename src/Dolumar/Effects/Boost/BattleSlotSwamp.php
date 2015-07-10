<?php
class Dolumar_Effects_Boost_BattleSlotSwamp extends Dolumar_Effects_Boost
{
	protected $sType = 'terrain';
	
	public function procUnitStats (&$stats, $unit)
	{
		if ($stats['atType'] == 'defCav')
		{
			$stats['melee'] -= floor ( ( $stats['melee'] / 100) * 35 );
		}
	}
}
?>