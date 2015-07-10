<?php
class Dolumar_Effects_Battle_Frost extends Dolumar_Effects_Battle
{
	protected $iLevel;

	public function __construct ($iLevel = 1)
	{
		$this->iLevel = $iLevel;
	}
	
	private function getDamageFromLevel ()
	{
		switch ($this->getLevel ())
		{
			default:
				return 100 + (100 * $this->getLevel ());
			break;
		}
	}
	
	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'damage' => $this->getDamageFromLevel ()
			)
		);
	}
	
	/*
		This is a fireball. Just do 500 random damage.
	*/
	public function doAction ($fight, &$attackers, &$defenders)
	{
		$damage = $this->getDamageFromLevel ();
		return $this->doRandomDamage ($defenders, $damage, 'defMag', $this->iLevel);
	}
	
	public function getBattleLog ($report, $unit, $probability, $data, $html = true)
	{
		$text = Neuron_Core_Text::__getInstance ();
		return Neuron_Core_Tools::putIntoText
		(
			$text->get ('onSuccess', $this->getClassName (), 'effects'),
			array
			(
				'target' => $report->getUnitData ($data[0]),
				'casualties' => $data[1],
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
				$gems = 20;
			break;
			
			case 2:
				$gems = 60;
			break;
			
			case 3:
				$gems = 150;
			break;
			
			case 4:
				$gems = 400;
			break;
			
			case 5:
				$gems = 1000;
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
		return 40;
	}
}
?>
