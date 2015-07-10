<?php
class Dolumar_Effects_Instant_Tornado extends Dolumar_Effects_Instant
{
	public function requiresTarget ()
	{
		return true;
	}

	/*
		Destroy one random building
	*/
	public function execute ($a = null, $b = null, $c = null)
	{
		$buildings = $this->getTarget ()->buildings->getBuildings ();
		shuffle ($buildings);
		
		$level = $this->getLevel ();
		
		foreach ($buildings as $v)
		{
			if ($v->getLevel () <= $level)
			{
				$v->doDestructBuilding (false, NOW, false);
				
				Dolumar_Players_Logs::getInstance ()
					->addDestroyBuildingLog 
					(
						$this->getVillage (), 
						$this->getTarget (), 
						$v
					);
				
				return true;
			}
		}
		
		return false;
	}
	
	protected function getCostFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
				return 20;
			break;
			
			case 2:
				return 35;			
			break;
			
			case 3:
				return 55;
			break;
			
			case 4:
				return 80;
			break;
			
			case 5:
				return 100;
			break;
		}
	}
	
	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'level' => $this->getLevel ()
			)
		);
	}
	
	public function getDifficulty ($iBaseAmount = 40)
	{
		return parent::getDifficulty (70);
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 7;
	}
}
?>
