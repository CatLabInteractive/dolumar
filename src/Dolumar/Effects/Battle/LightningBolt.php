<?php
class Dolumar_Effects_Battle_LightningBolt extends Dolumar_Effects_Battle
{
	protected $iLevel;
	
	protected $COST_BASE = 10;
	protected $COST_PERLEVEL = 30;

	public function __construct ($iLevel = 1)
	{
		$this->iLevel = $iLevel;
	}
	
	private function getDamageFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 400;
			break;
			
			case 2:
				return 700;
			break;
			
			case 3:
				return 1000;
			break;
			
			case 4:
				return 1300;
			break;
			
			case 5:
				return 1600;
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
		return $this->doRandomDamage ($defenders, $damage, 'defMag');
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
	
	public function getDifficulty ($iBaseAmount = 40)
	{
		return 40;
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 2;
	}
}
?>