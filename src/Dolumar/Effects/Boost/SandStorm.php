<?php
class Dolumar_Effects_Boost_SandStorm extends Dolumar_Effects_Boost
{
	protected function getBonusFromLevel ()
	{
		//return 10 + $this->getLevel () * 15;
		return 30;
	}
	
	public function getDuration ()
	{
		switch ($this->getLevel ())
		{
			case 1:
			default:
				$duration = 6;
			break;
			
			case 2:
				$duration = 7;
			break;
			
			case 3:
				$duration = 8;
			break;
			
			case 4:
				$duration = 9;
			break;
			
			case 5:
				$duration = 10;
			break;
		}
	
		return ($duration * 60) / GAME_SPEED_EFFECTS;
	}

	public function procUnitStats (&$stats, $unit)
	{
		$stats['speed'] -= ($stats['speed'] / 100) * $this->getBonusFromLevel ();
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
		return 4;
	}
}
?>
