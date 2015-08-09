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
	Logger object:
	Instanciated in the battle object and used in the battle_fight object.
*/
class Dolumar_Battle_Logger
{
	private $fightSummary = "";
	private $sFightLog = "";
	private $iTimeSec = 0;
	
	private $sDebugLog;
	
	private $aSquadCache = array ();
	private $sSquadCache = "";
	
	private $attArmy;
	private $defArmy;
	
	private $slots;
	private $victory;
	
	public function setVictory ($victory)
	{
		$this->victory = $victory;
	}
	
	public function getVictory ()
	{
		return $this->victory;
	}
	
	public function setFightlog ($fightlog)
	{
		if ($fightlog instanceof Dolumar_Battle_Fight)
		{
			$this->fightSummary = $this->getFightlogFromFight ($fightlog);
		}
		else
		{
			$this->fightSummary = $fightlog;
		}
	}
	
	public function getFightlogFromFight ($fight)
	{
		// Kill stuff
		$txt = '';
		foreach ($fight->getAttackers () as $v)
		{
			$killed = $v->getKillUnitsQue ();
		
			$txt .= $v->getUnitId ().':'.$v->getVillage ()->getId ().':'.$v->getRace ()->getId () .':'.$v->getAvailableAmount ().':'.$killed.':'.$this->getEquipmentLog ($v).';';
		}
		$txt = substr ($txt, 0, -1).'&';

		foreach ($fight->getDefenders () as $v)
		{
			$aantal = $v->getDefendingAmount ();

			if ($aantal > 0)
			{
				$killed = $v->getKillUnitsQue (true);
				
				//$txt .= $v->getUnitId ().':'.$v->getVillage ()->getId ().':'.$aantal.':'.$killed.':'.$this->getEquipmentLog ($v).';';
				$txt .= $v->getUnitId ().':'.$v->getVillage ()->getId ().':'.$v->getRace ()->getId () .':'.$aantal.':'.$killed.':'.$this->getEquipmentLog ($v).';';
			}
		}

		return substr ($txt, 0, -1);
	}
	
	public function getFightSummary ()
	{
		return $this->fightSummary;
	}
	
	private function getEquipmentLog ($unit)
	{
		$log = '';
		foreach ($unit->getEquipment () as $v)
		{
			$log .= $v->getMysqlId () . '+';
		}
		return substr ($log, 0, -1);
	}
	
	public function setArmies ($attArmy, $defArmy)
	{
		$this->attArmy = $attArmy;
		$this->defArmy = $defArmy;
	}
	
	public function getAttackingArmy ()
	{
		return $this->attArmy;
	}
	
	public function getDefendingArmy ()
	{
		return $this->defArmy;
	}
	
	public function getDebugLog ()
	{
		return $this->sDebugLog;
	}
	
	public function getFightLog ()
	{
		return $this->sFightLog;
	}
	
	public function getSquads ()
	{
		return substr ($this->sSquadCache, 0, -1);
	}
	
	public function getDuration ()
	{
		return $this->iTimeSec;
	}
	
	private function getSquadCacheId ($squad)
	{
		if (!isset ($this->aSquadCache[$squad->getId ()]))
		{
			$this->sSquadCache .= $squad->getName () . ';';
			$this->aSquadCache[$squad->getId ()] = count ($this->aSquadCache) + 1;
		}
		
		return $this->aSquadCache[$squad->getId ()];
	}

	private function addLog ($iType, $iTeam, $oValues)
	{
		// First: check $oValues for objects
		foreach ($oValues as $k => $v)
		{
			if ($v instanceof Dolumar_Units_Unit)
			{
				$oValues[$k] = $this->log_getUnitData ($v);
			}
			elseif ($v instanceof Dolumar_SpecialUnits_SpecialUnits)
			{
				$oValues[$k] = $this->log_getSpecialUnitData ($v);
			}
		}
	
		$this->sFightLog .= $this->iTimeSec.'|'.$iType.'|'.mt_rand (0, 100).'|'.$iTeam.'|'.implode ($oValues, '|').';';
		
		// Increase time
		$this->iTimeSec += ceil (mt_rand (15, 25) / GAME_SPEED_MOVEMENT);
		//$this->iTimeSec += 1;
	}
	
	private function log_getUnitData ($unit)
	{
		if (!$unit)
		{
			return null;
		}
		
		$equipment = array ();
		foreach ($unit->getEquipment () as $v)
		{
			$equipment[] = $v->getId ();
		}
		
		$squad = $unit->getSquad ();
		$squad = $squad ? $this->getSquadCacheId ($squad) : 0;
		
		$data = array ();
		
		$data['unit'] = $unit->getUnitId ();
		$data['village'] = $unit->getVillage ()->getId ();
		$data['slot'] = $unit->getBattleSlot ()->getId ();
		$data['equipment'] = $equipment;
		$data['squad'] = $squad;
		$data['amount'] = $unit->getAmount () - $unit->getKillUnitsQue () + $unit->getKilledInRound ();
		$data['race'] = $unit->getRace ()->getId (); // This one is new, let's throw it at the bottom
		
		
		$data['frontage'] = $unit->getCurrentFrontage(); // This one is new as well
		
		return json_encode ($data);
	}
	
	private function log_getSpecialUnitData ($unit)
	{
		return $unit->getUnitId () . ':' . $unit->getLevel ();
	}
	
	public function addLog_casualties ($oAttacker, $oDefender, $iAmount, $sAction = 'melee', $team = 1)
	{
		$logId = 10;
		switch ($sAction)
		{
			case 'melee':
				$logId = 11;
			break;
			
			case 'shooting':
				$logId = 12;
			break;
		}
	
		$this->addLog 
		(
			$logId, 
			$team, 
			array 
			(
				0 => $this->log_getUnitData ($oAttacker), 
				1 => $this->log_getUnitData ($oDefender), 
				2 => $iAmount
			)
		);
	
		$this->sDebugLog .= $this->debug_getUnitName ($oAttacker) . ' caused ' . $iAmount . ' casualties to ' . 
			$this->debug_getUnitName ($oDefender) . ' with a '.$sAction.' attack.' . "\n";
	}
	
	public function addLog_status ($iStatus)
	{
		$this->sDebugLog .= 'Moving to next status: '.$iStatus.".\n";
		$this->addLog (0, 0, array ($iStatus));
	}
	
	public function addLog_action ($oActor, $sAction)
	{
		$this->sDebugLog .= $this->debug_getUnitName ($oActor) . ' did action '.$sAction.".\n";
	}
	
	public function addLog_move ($oActor, $iStart, $iEnd, $team = 1)
	{
		$this->addLog (1, $team, array ($this->log_getUnitData ($oActor), $iStart, $iEnd));
	
		$this->sDebugLog .= $this->debug_getUnitName ($oActor) . ' moved from '.$iStart." to ".$iEnd.".\n";
	}
	
	public function addLog_flee ($oActor, $team = 1)
	{
		$this->addLog (9, $team, array ($this->log_getUnitData ($oActor)));
		
		$this->sDebugLog .= $this->debug_getUnitName ($oActor) . " fled the battlefield.\n";
	}
	
	public function addLog_whipe ($oActor, $team = 1)
	{
		$this->addLog (8, $team, array ($this->log_getUnitData ($oActor)));
		
		$this->sDebugLog .= $this->debug_getUnitName ($oActor) . " fled the battlefield.\n";
	}
	
	public function addLog_stunned ($oActor, $team = 1)
	{
		$this->addLog (2, $team, array ($this->log_getUnitData ($oActor)));
	}
	
	public function addLog_effect ($unit, $effect, $probability, $data = array ())
	{
		$out = array 
		(
			$unit, 
			$effect->getId (), 
			$probability
		);
		
		foreach ($data as $v)
		{
			$out[] = $v;
		}
		
		$this->addLog (5, 1, $out);
		
		//$this->sDebugLog .= 'Battle effect: '.print_r ($out, true);
	}
	
	public function addLog_failedEffect ($unit, $effect, $probability)
	{
		$this->addLog (6, 1, array ($unit, $effect->getId (), $probability));
	}
	
	public function addLog_specialunitdied ($unit)
	{
		$this->addLog (7, 1, array ($unit));
	}
	
	public function setSlots ($slots)
	{
		$this->slots = $slots;
	}
	
	public function getSlots ()
	{
		/*
		if (!isset ($this->slots))
		{
			$this->slots = $this->defArmy->getVillage ()->getDefenseSlots (7);
		}
		*/
	
		return $this->slots;
	}
	
	/*
		Return the initial troop placemenet on the slots.
	*/
	public function getInitialSlots ()
	{
		//$slots = $this->attArmy->getVillage ()->getAttackSlots ($this->defArmy->getVillage ());
		$slots = $this->getSlots ();
		
		$out = array 
		(
			'attacking' => array (),
			'defending' => array ()
		);
		
		foreach ($slots as $v)
		{
			$out['attacking'][] = array
			(
				array
				(
					$v->getSlotId (),
					$v->getId ()
				),
				$this->log_getUnitData ($this->attArmy->getUnitOnSlot ($v))
			);
			
			$out['defending'][] = array
			(
				array
				(
					$v->getSlotId (),
					$v->getId ()
				),
				$this->log_getUnitData ($this->defArmy->getUnitOnSlot ($v))
			);
		}
		
		return json_encode ($out);
	}
	
	/*
		Return the special units
	*/
	public function getSpecialUnits ()
	{
		$out = array
		(
			'attacking' => array (),
			'defending' => array ()
		);
		
		foreach ($this->attArmy->getSpecialUnits () as $v)
		{
			$out['attacking'][] = $this->getSpecialUnitData ($v);
		}
		
		return json_encode ($out);
	}
	
	private function getSpecialUnitData ($unit)
	{
		return array
		(
			$unit->getUnitId (),
			$unit->getLevel (),
			$unit->getCustomName (),
			$unit->isAlive (),
			$unit->getRace ()->getId ()
		);
	}
	
	/*
		Debug functions
	*/
	private function debug_getUnitName ($unit)
	{
		return '['.$unit->getBattleSlot ()->getId ().'] '.$unit->getAmount () . ' ' . $unit->getName () . ' (' . $unit->getVillage ()->getName () . ')';
	}
	
	public function debug_showTroops ($attackers, $defenders)
	{
		$this->sDebugLog .= "\nAttacking troops: \n";
		foreach ($attackers as $k => $v)
		{
			$this->sDebugLog .= '- ['.$k.'] '.$v->getName () . "\n";
		}
		
		$this->sDebugLog .= "\nDefending troops: \n";
		foreach ($defenders as $k => $v)
		{
			$this->sDebugLog .= '- ['.$k.'] '.$v->getName () . "\n";
		}
		
		$this->sDebugLog .= "\n";
	}
	
	public function __destruct ()
	{
		unset ($this->sFightLog);
		unset ($this->iTimeSec);
	
		unset ($this->sDebugLog);
	
		unset ($this->aSquadCache);
		unset ($this->sSquadCache);

	/*		
		if (isset ($this->attArmy))
		{
			$this->attArmy->__destruct ();
		}
		
		if (isset ($this->defArmy))
		{
			$this->defArmy->__destruct ();
		}
		*/
		
		unset ($this->attArmy);
		unset ($this->defArmy);
	}
}
?>
