<?php
class Dolumar_Effects_Boost_Resources extends Dolumar_Effects_Boost
{
	protected $sType = 'magic';
	protected $sResource = 'grain';
	
	protected function getBonusFromLevel ()
	{
		//return 10 + $this->getLevel () * 15;
		
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 35;
			break;
			
			case 2:
				return 45;
			break;
			
			case 3:
				return 55;
			break;
			
			case 4:
				return 65;
			break;
			
			case 5:
				return 75;
			break;
		}
	}

	public function procIncome ($resources, $objBuilding) 
	{
		if (isset ($resources[$this->sResource]))
		{	
			$persistence = $this->getPersistence ();	
			$resources[$this->sResource] += ($resources[$this->sResource] / 100) * $this->getBonusFromLevel () * $persistence;
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
