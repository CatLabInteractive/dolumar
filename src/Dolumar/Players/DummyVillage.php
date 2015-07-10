<?php
class Dolumar_Players_DummyVillage 
	extends Dolumar_Players_Village
{
	private $race;

	public function __construct ($race = null)
	{
		parent::__construct (null);
		
		$this->race = $race;
	}
	
	public function getName ()
	{
		return 'Dummy village';
	}
	
	public function setRace ($race)
	{
		$this->race = $race;
	}
	
	public function getRace ()
	{
		return $this->race;
	}
	
	public function getActiveBoosts ($since = NOW, $now = NOW)
	{
		return array ();
	}
	
	public function getDefenseSlots ($amount = null)
	{
		$out = array ();
		for ($i = 1; $i <= $amount; $i ++)
		{
			$out[$i] = Dolumar_Battle_Slot_Grass::getRandomSlot ($i, $this);
		}
		return $out;
	}

	public function getOwner ()
	{
		return new Dolumar_Players_NPCPlayer (null);
	}
}
?>
