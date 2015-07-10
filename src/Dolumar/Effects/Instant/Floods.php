<?php
class Dolumar_Effects_Instant_Floods extends Dolumar_Effects_Instant
{
	public function requiresTarget ()
	{
		return true;
	}
	
	protected function getBonusFromLevel ()
	{
		//return 10 + $this->getLevel () * 15;
		
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 4 * 60 * 60;
			break;
			
			case 2:
				return 4.5 * 60 * 60;
			break;
			
			case 3:
				return 5 * 60 * 60;
			break;
			
			case 4:
				return 5.5 * 60 * 60;
			break;
			
			case 5:
				return 6 * 60 * 60;
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

	public function execute ($a = null, $b = null, $c = null)
	{
		$buildings = $this->getTarget ()->buildings->getBuildings ();
		
		foreach ($buildings as $v)
		{
			if (!$v->isFinished ())
			{
				$v->delayBuild ($this->getBonusFromLevel ());
			}
		}
	}
	
	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'delay' => Neuron_Core_Tools::getDurationText ($this->getBonusFromLevel (), false)
			)
		);
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 6;
	}
}
?>
