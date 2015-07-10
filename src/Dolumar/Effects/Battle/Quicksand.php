<?php
class Dolumar_Effects_Battle_Quicksand extends Dolumar_Effects_Battle
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
				'damage' => $this->getBonusFromLevel ()
			)
		);
	}
	
	/*
		This is a fireball. Just do 500 random damage.
	*/
	public function doAction ($fight, &$attackers, &$defenders)
	{
		$damagepercentage = $this->getBonusFromLevel ();
		//return $this->doRandomDamage ($defenders, $damage, 'defMag');
		$unit = $this->getRandomUnit ($defenders);
		
		$units = $unit->getAmount () - $unit->getKillUnitsQue () + $unit->getKilledInRound ();
		
		$damage = (($unit->getStat ('hp') * $units) / 100) * $damagepercentage;
		
		return $this->doDamage ($unit, $damage);
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
				$gems = 100;
			break;
			
			case 2:
				$gems = 300;
			break;
			
			case 3:
				$gems = 750;
			break;
			
			case 4:
				$gems = 1700;
			break;
			
			case 5:
				$gems = 3600;
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
		return 70;
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 4;
	}
}
?>
