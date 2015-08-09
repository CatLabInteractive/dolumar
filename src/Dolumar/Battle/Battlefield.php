<?php
/**
 *  Dolumar, browser based strategy game
 *  Copyright (C) 2009 Thijs Van der Schaeghe
 *  CatLab Interactive bvba, Gent, Belgium
 *  http://www.catlab.eu/
 *  http://www.dolumar.com/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

/*
	This class simulates a battlefield, used
	by the report feature.
*/
class Dolumar_Battle_Battlefield
{
	private $attacking;
	private $defending;
	
	private $dropnextround = array ();
	
	// Here we'll store the (temporary) frontages
	private $frontages = array ();

	public function __construct ($attTroops, $defTroops)
	{
		$this->attacking = $this->parseSlots ($attTroops);
		$this->defending = $this->parseSlots ($defTroops);
		
		$this->dropnextround = array ();
	}
	
	private function parseSlots ($troops)
	{
		$out = array ();
		
		if (!isset ($troops))
		{
			return $out;
		}
		
		foreach ($troops as $v)
		{
			$troop = isset ($v[1]) ? $v[1] : null;
			$slot = $this->parseSlot ($v[0]);
			
			if ($troop)
			{
				$troop->setCurrentSlot ($slot);
			}
		
			$out[$slot->getId ()] = array
			(
				'unit' => $troop,
				'slot' => $slot
			);
		}
		
		return $out;
	}
	
	private function resetStatus ()
	{
		foreach (array ($this->attacking, $this->defending) as $v)
		{
			foreach ($v as $vv)
			{
				if (isset ($vv['unit']))
				{
					$vv['unit']->setIdle ();
				}
			}
		}
	}
	
	private function dropnextround (&$slot, $key)
	{
		$this->dropnextround[] = array (&$slot, $key);
	}
	
	public function nextround ()
	{
		$next = array_shift ($this->dropnextround);
		if ($next)
		{
			$slot = &$next[0];
			
			$unit = $this->getUnitOnSlot ($slot, $next[1]);
			$unit->setCurrentSlot (null);
			
			//echo 'yes';
			
			//unset ($slot[$next[1]]);
		}
		
		$this->resetStatus ();
	}
	
	private function parseSlot ($slot)
	{
		if (!is_array ($slot))
		{
			$slotId = $slot;
			$oid = 0;
		}
		else
		{
			$slotId = $slot[0];
			$oid = $slot[1];
		}
	
		return Dolumar_Battle_Slot_Grass::getFromId ($slotId, $oid);
	}
	
	/*
		Actions from the fightlog
	*/
	public function move ($isAttacker, $from, $to, Dolumar_Battle_Unit $unit)
	{
		$this->nextround ();
		$this->doMove ($isAttacker ? $this->attacking : $this->defending, $from, $to, $unit);
		
		// TODO update unit
	}
	
	private function doMove ($slots, $from, $to, Dolumar_Battle_Unit $newunit)
	{
		$unit = $this->getUnitOnSlot ($slots, $from);
		$slot = $this->getSlot ($slots, $to);
		
		if ($unit && $slot)
		{
			$unit->setCurrentSlot ($slot);
			$this->updateUnit ($unit, $newunit);
		}
	}
	
	private function getUnitOnSlot ($slots, $slot)
	{
		$slot = $this->getSlotId ($slot);
	
		foreach ($slots as $v)
		{
			if ($v['unit'])
			{
				$uslot = $v['unit']->getCurrentSlot ();
				if ($uslot && $uslot->getId () == $slot)
				{
					return $v['unit'];
				}
			}
		}
		
		return false;
	}
	
	private function getSlot ($slots, $slot)
	{
		$slot = $this->getSlotId ($slot);
	
		foreach ($slots as $v)
		{
			if ($v['slot']->getId () == $slot)
			{
				return $v['slot'];
			}
		}
		
		return false;
	}
	
	private function getSlotId ($slot)
	{
		if ($slot instanceof Dolumar_Battle_Slot_Grass)
		{
			$slot = $slot->getId ();
		}
		
		return $slot;
	}
	
	public function stunned ($isAttacker, $slot, Dolumar_Battle_Unit $unit)
	{
		$this->nextround ();
		$this->doStun ($isAttacker ? $this->attacking : $this->defending, $slot, $unit);
	}
	
	private function doStun ($slots, $slot, Dolumar_Battle_Unit $newunit)
	{
		$unit = $this->getUnitOnSlot ($slots, $slot);
		if ($unit)
		{
			$unit->setStunned ();
			$this->updateUnit ($unit, $newunit);
		}
	}
	
	public function specialunit_action ($isAttacker, $unit, $oEffect, $success)
	{
		$this->nextround ();
	}
	
	public function specialunit_dead ($isAttacker, $unit)
	{
		$this->nextround ();	
	}
	
	public function whipe ($isAttacker, $slot, Dolumar_Battle_Unit $unit)
	{
		$this->nextround ();
		$this->doWhipe ($isAttacker ? $this->attacking : $this->defending, $slot, $unit);
	}
	
	private function doWhipe ($slots, $slot, Dolumar_Battle_Unit $newunit)
	{
		$unit = $this->getUnitOnSlot ($slots, $slot);
		if ($unit)
		{
			$unit->setWhiped ();
			$this->dropnextround ($slots, $slot);
			$this->updateUnit ($unit, $newunit);
		}
	}
	
	public function flee ($isAttacker, $slot, Dolumar_Battle_Unit $unit)
	{
		$this->nextround ();
		$this->doFlee ($isAttacker ? $this->attacking : $this->defending, $slot, $unit);
	}
	
	private function doFlee ($slots, $slot, Dolumar_Battle_Unit $newunit)
	{
		$unit = $this->getUnitOnSlot ($slots, $slot);
		
		if ($unit)
		{
			$unit->setFled ();
			$this->dropnextround ($slots, $slot);
			$this->updateUnit ($unit, $newunit);
		}
	}
	
	public function damage ($isAttacker, $attslot, $defslot, $dead, Dolumar_Battle_Unit $attacker, Dolumar_Battle_Unit $defender)
	{
		$this->nextround ();
		
		$this->doDamage 
		(
			$isAttacker ? $this->attacking : $this->defending, 
			$isAttacker ? $this->defending : $this->attacking, 
			$attslot,
			$defslot,
			$dead,
			$attacker,
			$defender
		);
	}
	
	private function doDamage 
	(
		$myslots,
		$hisslots,
		$attslot,
		$defslot,
		$dead,
		Dolumar_Battle_Unit $newattacker, 
		Dolumar_Battle_Unit $newdefender
	)
	{
		$attacker = $this->getUnitOnSlot ($myslots, $attslot);
		$defender = $this->getUnitOnSlot ($hisslots, $defslot);
		
		if ($attacker)
		{
			$attacker->setAttacking ();
			$this->updateUnit ($attacker, $newattacker);
		}
		
		if ($defender)
		{
			$defender->setDying ($dead);
			$this->updateUnit ($defender, $newdefender);
		}
	}
	
	public function getOutput ()
	{
		try
		{
			$page = new Neuron_Core_Template ();
		
			$slots = array ();
			foreach ($this->defending as $v)
			{
				$slot = array
				(
					'img' => $v['slot']->getImageUrl (),
					'sName' => $v['slot']->getName (),
					'units' => $this->getUnitsOutput ($v['slot'])
				);
			
				$slots[] = $slot;
			}
		
			$page->set ('slots', $slots);
		
			return $page->parse ('battle/battlefield.phpt');
		}
		catch (Exception $e)
		{
			return '<p>This report is no longer available.</p>';
		}
	}
	
	public function updateUnit (Dolumar_Battle_Unit $oldunit, Dolumar_Battle_Unit $newunit)
	{
		$oldunit->setFrontage ($newunit->getFrontage ());
	}
	
	private function getUnitsOutput ($slot)
	{
		$out = array ();
		
		$attacker = $this->getUnitOutput ($this->attacking, $slot, 'attacker');
		if ($attacker)
		{
			$out[] = $attacker;
		}
		
		$defender = $this->getUnitOutput ($this->defending, $slot, 'defender');
		if ($defender)
		{
			$out[] = $defender;
		}
		
		return $out;
	}
	
	private function getUnitOutput ($slots, $slot, $side = 'attacker')
	{
		$unit = $this->getUnitOnSlot ($slots, $slot);
		if ($unit)
		{
			$squad = $unit->getName ();
		
			return array
			(
				'name' => $unit->getUnit ()->getName (),
				'squad' => !empty ($squad) ? $squad : $unit->getUnit ()->getName (),
				'status' => $unit->getStatus (),
				'img' => $unit->getUnit ()->getImageUrl (),
				'side' => $side,
				'frontage' => $unit->getFrontage ()
			);
		}
		
		return $unit;
	}
	
	public function __toString ()
	{
		return $this->getOutput ();
	}
	
	public function __destruct ()
	{
		/*
		if (isset ($this->attacking))
		{
			foreach ($this->attacking as $v)
			{
				foreach ($v as $vv)
				{
					if ($vv)
						$vv->__destruct ();
				}
			}
		}
		
		if (isset ($this->defending))
		{
			foreach ($this->defending as $v)
			{
				foreach ($v as $vv)
				{
					if ($vv)
						$vv->__destruct ();
				}
			}
		}
		*/

		unset ($this->attacking);
		unset ($this->defending);
	}
}
?>
