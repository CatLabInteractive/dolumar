<?php
class Dolumar_Effects_Boost_Earthquake extends Dolumar_Effects_Boost
{
	protected $sType = 'magic';
	protected $iDuration = 21600;

	protected function getBonusFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 10;
			break;
			
			case 2:
				return 15;
			break;
			
			case 3:
				return 20;
			break;
			
			case 4:
				return 25;
			break;
			
			case 5:
				return 30;
			break;
		}
	}
	
	public function procDefenseBonus ($bonus)
	{
		$bonus -= ($bonus / 100) * $this->getBonusFromLevel ();
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
		return 5;
	}
}
?>
