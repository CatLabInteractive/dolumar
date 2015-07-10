<?php
class Dolumar_Effects_Battle_Fear extends Dolumar_Effects_Battle implements Dolumar_Players_iBoost
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
				return 1;
			break;
			
			case 2:
				return 2;
			break;
			
			case 3:
				return 3;
			break;
			
			case 4:
				return 4;
			break;
			
			case 5:
				return 5;
			break;
		}
	}
	
	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'penalty' => $this->getBonusFromLevel ()
			)
		);
	}
	
	/*
		Meteor rain
	*/
	public function doAction ($fight, &$attackers, &$defenders)
	{
		foreach ($defenders as $v)
		{
			$v->addEffect ($this);
		}
		
		return array ($this->getBonusFromLevel ());
		
		//return $this->doRandomDamage ($defenders, $damage, 'defMag', $this->iLevel);
	}
	
	public function getBattleLog ($report, $unit, $probability, $data, $html = true)
	{
		$text = Neuron_Core_Text::__getInstance ();
		return Neuron_Core_Tools::putIntoText
		(
			$text->get ('onSuccess', $this->getClassName (), 'effects'),
			array
			(
				'penalty' => $data[0],
				'unit' => $unit->getName (),
				'probability' => $probability,
				'level' => $this->getLevel (),
				'spell' => $html ? $this->getDisplayName () : $this->getName ()
			)
		);
	}
	
	public function procBuildingCost 	($resources, $objBuilding) { return $resources; }
	public function procBuildCost 		($resources, $objBuilding) { return $resources; }
	public function procUpgradeCost 	($resources, $objBuilding) { return $resources;  }
	public function procCapacity 		($resources, $objBuilding) { return $resources; }
	public function procIncome 		($resources, $objBuilding) { return $resources; }
	public function procEffectDifficulty 	($difficulty, $effect) { return $difficulty; }
	public function procDefenseBonus 	($def)	{ return $def; }
	public function procBattleVisible 	($battle) { return true; }
	public function procEquipmentDuration	($duration, $item) { return $duration; }
	public function procEquipmentCost	($cost, $item) { return $cost; }
	public function procUnitStats 		(&$stats, $unit) {}
	
	public function onBatteFought ($battle) {}
	
	public function procMoraleCheck 	($morale, $fight)
	{
		//$morale -= ($morale / 100) * $this->getBonusFromLevel ();
		$morale -= ($this->getBonusFromLevel () / 100);
	
		return $morale;
	}
	
	public function getCost ($objUnit, $objTarget, $cost = null)
	{
		switch ($this->getLevel ())
		{
			case 1:
			default:
				$gems = 200;
			break;
			
			case 2:
				$gems = 400;
			break;
			
			case 3:
				$gems = 650;
			break;
			
			case 4:
				$gems = 950;
			break;
			
			case 5:
				$gems = 1300;
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
	
	protected function getMinimalBuildingLevel ()
	{
		return 4;
	}
}
?>
