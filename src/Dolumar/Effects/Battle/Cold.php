<?php
class Dolumar_Effects_Battle_Cold extends Dolumar_Effects_Battle
{
	protected $iLevel;

	public function __construct ($iLevel = 1)
	{
		$this->iLevel = $iLevel;
	}
	
	protected function getBonusFromLevel ()
	{
		//return 10 + $this->getLevel () * 15;
		
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 10;
			break;
			
			case 2:
				return 20;
			break;
			
			case 3:
				return 30;
			break;
			
			case 4:
				return 40;
			break;
			
			case 5:
				return 50;
			break;
		}
	}

	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'damage' => $this->getBonusFromLevel ()
			)
		);
	}
	
	/*
		This is a fireball. Just do 500 random damage.
	*/
	public function doAction ($fight, &$attackers, &$defenders)
	{
		$unit = $this->getRandomUnit ($defenders);
	
		$hp = $unit->getStat ('hp');
		
		$hp -= ($hp / 100) * $this->getBonusFromLevel ();
	
		$unit->setStat ('hp', $hp);
		
		return array ($unit);
	}
	
	public function getBattleLog ($report, $unit, $probability, $data, $html = true)
	{	
		$data[0] = isset ($data[0]) ? $data[0] : null;
	
		$text = Neuron_Core_Text::__getInstance ();
		return Neuron_Core_Tools::putIntoText
		(
			$text->get ('onSuccess', $this->getClassName (), 'effects'),
			array
			(
				'bonus' => $this->getBonusFromLevel (),
				'target' => $report->getUnitData ($data[0]),
				'unit' => $unit->getName (),
				'probability' => $probability,
				'level' => $this->getLevel (),
				'spell' => $html ? $this->getDisplayName () : $this->getName ()
			)
		);
	}
	
	public function getCost ($objUnit, $objTarget, $cost = null)
	{
		switch ($this->getLevel ())
		{
			case 1:
			default:
				$gems = 100;
			break;
			
			case 2:
				$gems = 300;
			break;
			
			case 3:
				$gems = 600;
			break;
			
			case 4:
				$gems = 1200;
			break;
			
			case 5:
				$gems = 2500;
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
	
	protected function getMinimalBuildingLevel ()
	{
		return 2;
	}
}
?>
