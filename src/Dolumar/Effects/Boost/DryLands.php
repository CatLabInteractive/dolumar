<?php
class Dolumar_Effects_Boost_DryLands extends Dolumar_Effects_Boost
{
	protected $sType = 'magic';

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

	public function procIncome ($resources, $objBuilding) 
	{
		if (isset ($resources['grain']))
		{	
			$persistence = $this->getPersistence ();	
			$resources['grain'] -= ($resources['grain'] / 100) * $this->getBonusFromLevel () * $persistence;
		}
		
		return $resources; 
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
}
?>
