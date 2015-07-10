<?php
class Dolumar_Effects_Boost_HotSmithyFires extends Dolumar_Effects_Boost
{
	protected $sType = 'magic';
	protected $iDuration = 43200;

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

	public function procEquipmentDuration ($duration, $item) 
	{
		$duration -= ($duration / 100) * $this->getBonusFromLevel ();
		return $duration;
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
