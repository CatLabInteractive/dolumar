<?php
/*
	This class keeps track of actions
	performed during the battle
	that affect an army.
*/
class Dolumar_Battle_Army
{
	private $village;
	
	private $units = array ();
	private $isAttacking;
	
	private $iHealthSum = 0;
	
	private $initialSlots = array ();
	
	private $specialUnits = array ();

	public function __construct ($village, $isAttacking)
	{
		$this->village = $village;
		$this->isAttacking = $isAttacking;
	}
	
	public function addUnit ($unit)
	{
		$this->units[] = $unit;
		
		$this->iHealthSum += $unit->getAmountInCombat (!$this->isAttacking) * $unit->getStat ('hp');
			
		$this->initialSlots[$unit->getBattleSlot ()->getId ()] = $unit;
	}
	
	/*
		Get the troop that (initially) stood on this slot.
	*/
	public function getUnitOnSlot ($slot)
	{
		if ($slot instanceof Dolumar_Battle_Slot_Grass)
		{
			$slot = $slot->getId ();
		}
	
		if (isset ($this->initialSlots[$slot]))
		{
			return $this->initialSlots[$slot];
		}
		else
		{
			return false;
		}
	}
	
	public function getVillage ()
	{
		return $this->village;
	}
	
	/*
		Also store special untis...
	*/
	public function addSpecialUnit ($specialunit)
	{
		$this->specialUnits[] = $specialunit;
	}
	
	public function getSpecialUnits ()
	{
		return $this->specialUnits;
	}
	
	/*
		Count the amount of units that have fled the battlefield.
	*/
	public function countFledUnits ()
	{
		return $this->countUnits (Dolumar_Units_Unit::BATTLE_STATUS_FLED);
	}
	
	/*
		Count the amount of units that were whiped from the map.
	*/
	public function countWhipedUnits ()
	{
		return $this->countUnits (Dolumar_Units_Unit::BATTLE_STATUS_WHIPED);
	}
	
	/*
		Count the actual amount of killed units.
	*/
	public function countKilledHealth ()
	{
		$sum = 0;
		foreach ($this->units as $v)
		{
			$sum += ($v->getKilledUnits () * $v->getStat ('hp'));
		}
		
		return $sum;
	}
	
	/*
		Return the initial health
	*/
	public function getInitialHealth ()
	{
		return $this->iHealthSum;
	}
	
	/*
		Return the percentage of killed units (in health)
	*/
	public function getKilledPercentage ()
	{
		$initial = $this->getInitialHealth ();
		
		if ($initial == 0)
		{
			$initial = 1;
		}
		
		return ($this->countKilledHealth () / $initial) * 100;
	}
	
	private function countUnits ($status)
	{
		$i = 0;
		foreach ($this->units as $v)
		{
			if ($v->isBattleStatus ($status))
			{
				$i ++;
			}
		}
		
		return $i;		
	}
	
	public function __destruct ()
	{
		unset ($this->village);
		unset ($this->units);
		unset ($this->initialSlots);
		unset ($this->specialUnits);
	}
}
?>
