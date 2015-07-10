<?php
class Dolumar_Effects_Battle_SoakingRains extends Dolumar_Effects_Battle
{
	protected $iLevel;

	public function __construct ($iLevel = 1)
	{
		$this->iLevel = $iLevel;
	}
	
	private function getPenaltyFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
				return 40;
			break;
			
			case 2:
				return 55;
			break;
			
			case 3:
				return 70;
			break;
			
			case 4:
				return 85;
			break;
			
			case 5:
				return 100;
			break;
		}
	}
	
	/*
		This is a fireball. Just do 500 random damage.
	*/
	public function doAction ($fight, &$attackers, &$defenders)
	{
		foreach ($defenders as $v)
		{
			$shooting = $v->getStat ('shooting');
			$v->setStat ('shooting', ($shooting / 100) * $this->getPenaltyFromLevel ());
		}
	}
	
	public function getBattleLog ($report, $unit, $probability, $data, $html = true)
	{
		$text = Neuron_Core_Text::__getInstance ();
		return Neuron_Core_Tools::putIntoText
		(
			$text->get ('onSuccess', $this->getClassName (), 'effects'),
			array
			(
				'penalty' => $this->getPenaltyFromLevel (),
				'unit' => $unit->getName (),
				'probability' => $probability,
				'level' => $this->getLevel (),
				'spell' => $html ? $this->getDisplayName () : $this->getName ()
			)
		);
	}

	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'penalty' => $this->getPenaltyFromLevel ()
			)
		);
	}
	
	public function getCost ($objUnit, $objTarget, $cost = null)
	{
		switch ($this->getLevel ())
		{
			case 1:
			default:
				$gems = 20;
			break;
			
			case 2:
				$gems = 40;
			break;
			
			case 3:
				$gems = 80;
			break;
			
			case 4:
				$gems = 160;
			break;
			
			case 5:
				$gems = 300;
			break;
		}
	
		if (!isset ($cost))
		{
			$cost = array
			(
				'gems' => $gems
			);
		}
		
		return $cost;
	}
	
	public function getDifficulty ($iBaseAmount = 40)
	{
		return 50;
	}
}
?>
