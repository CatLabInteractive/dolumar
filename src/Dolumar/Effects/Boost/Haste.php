<?php
class Dolumar_Effects_Boost_Haste extends Dolumar_Effects_Boost
{
	protected function getBonusFromLevel ()
	{
		//return 10 + $this->getLevel () * 15;
		
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 30;
			break;
			
			case 2:
				return 35;
			break;
			
			case 3:
				return 40;
			break;
			
			case 4:
				return 45;
			break;
			
			case 5:
				return 50;
			break;
		}
	}
	
	protected function getCostFromLevel ()
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

	public function procUnitStats (&$stats, $unit)
	{
		$stats['speed'] += ($stats['speed'] / 100) * $this->getBonusFromLevel ();
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
