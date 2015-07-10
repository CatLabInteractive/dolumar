<?php
class Dolumar_Effects_Boost_LightningStorm extends Dolumar_Effects_Boost
{
	protected $sType = 'magic';
	protected $iDuration = 21600;

	protected function getBonusFromLevel ()
	{
		//return 10 + $this->getLevel () * 15;
		
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 5;
			break;
			
			case 2:
				return 6;
			break;
			
			case 3:
				return 7;
			break;
			
			case 4:
				return 8;
			break;
			
			case 5:
				return 9;
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

	public function procMoraleCheck ($morale, $fight) 
	{
		$morale -= ($morale / 100) * $this->getBonusFromLevel ();
		
		return $morale;
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
