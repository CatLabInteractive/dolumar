<?php
class Dolumar_Effects_Boost_SummonGhosts extends Dolumar_Effects_Boost
{
	protected $sType = 'magic';
	protected $iDuration = 43200;

	protected function getBonusFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 8;
			break;
			
			case 2:
				return 9;
			break;
			
			case 3:
				return 10;
			break;
			
			case 4:
				return 11;
			break;
			
			case 5:
				return 12;
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
				return 11;			
			break;
			
			case 3:
				return 13;
			break;
			
			case 4:
				return 16;
			break;
			
			case 5:
				return 20;
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
		$stats['hp'] -= ($stats['hp'] / 100) * $this->getBonusFromLevel ();
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
		return 2;
	}
}
?>
