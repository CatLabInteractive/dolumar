<?php
class Dolumar_Effects_Boost_FireWeapons extends Dolumar_Effects_Boost
{
	protected $sType = 'magic';
	protected $iDuration = 43200;

	protected function getBonusFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 10;
			break;
			
			case 2:
				return 12;
			break;
			
			case 3:
				return 14;
			break;
			
			case 4:
				return 16;
			break;
			
			case 5:
				return 18;
			break;
		}
	}
	
	protected function getCostFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
				return 10;
			break;
			
			case 2:
				return 12;			
			break;
			
			case 3:
				return 15;
			break;
			
			case 4:
				return 19;
			break;
			
			case 5:
				return 25;
			break;
		}
	}

	public function onBattleFought ($battle)
	{
		if ($battle->isDefender ($this->getVillage ()))
		{
			$this->cancel ();
		}
	}
	
	public function procUnitStats (&$stats, $unit)
	{
		$stats['hp'] += ($stats['hp'] / 100) * $this->getBonusFromLevel ();
	}
	
	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'bonus' => $this->getBonusFromLevel ()
			)
		);
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 3;
	}
}
?>
