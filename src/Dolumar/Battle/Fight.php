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

class Dolumar_Battle_Fight
{
	const STATUS_PREFIGHT = 0;
	const STATUS_SHOOTING = 1;
	const STATUS_MELEE = 2;
	const STATUS_FINISHED = 3;
	
	const MAX_FIGHT_ITERATIONS = 50;

	private $attackers;
	private $defenders;
	private $result;
	
	private $attackingVillage;
	private $defendingVillage;
	
	private $sDebugLog = "Battle Report:\n";
	private $iRound = 0;
	private $aSlots = array ();
	private $specialUnits = array ();
	
	private $objLogger;
	
	private $attArmy;
	private $defArmy;
	
	private $isProcessed = false;

	public function __construct ($objAttVil, $objDefVil, $objAttUnits, $objDefUnits, $slots, $specialUnits, $objLogger)
	{
		$this->attackers = $objAttUnits;
		$this->defenders = $objDefUnits;
		
		$this->attArmy = new Dolumar_Battle_Army ($objAttVil, true);
		$this->defArmy = new Dolumar_Battle_Army ($objDefVil, false);
		
		// Reset the stats + add to correct army.
		foreach ($this->attackers as $v)
		{
			if (isset ($v))
			{
				$v->reloadStats ();
				$this->attArmy->addUnit ($v);
			}
		}
		
		foreach ($this->defenders as $v)
		{
			if (isset ($v))
			{
				$v->reloadStats ();
				$this->defArmy->addUnit ($v);
			}
		}
		
		$this->objLogger = $objLogger;
		
		$this->objLogger->setArmies ($this->attArmy, $this->defArmy);
		
		// Set slots
		$this->objLogger->setSlots ($slots);

		$this->attackingVillage = $objAttVil;
		$this->defendingVillage = $objDefVil;
		
		$this->specialUnits = $specialUnits;
		
		foreach ($this->specialUnits as $v)
		{
			$this->attArmy->addSpecialUnit ($v[0]);
		}
		
		$this->aSlots = $slots;
		
		// Let's start with updating the frontages
	}

	/*
		Public functions (for use with the effects!)
	*/	
	
	/*
		Return the result in decimal between 0 and 1
	*/
	public function getResult ()
	{
		$this->fight ();
		return $this->result;
	}

	public function getAttackers ()
	{
		$this->fight ();
		return $this->attackers;
	}

	public function getDefenders ()
	{
		$this->fight ();
		return $this->defenders;
	}

	/**
	* Method is called after battle is processsed to actually kill the units
	*/
	public function killUnits ()
	{
		// Kill stuff
		foreach ($this->getAttackers () as $v)
		{
			$killed = $v->getKillUnitsQue ();
			
			if ($killed > 0)
			{
				$v->getVillage ()->removeUnits ($v, $killed);
			}
		}

		foreach ($this->getDefenders () as $v)
		{
			$aantal = $v->getDefendingAmount ();

			if ($aantal > 0)
			{
				$killed = $v->getKillUnitsQue (true);

				if ($killed > 0 && $v->hasEternalLife () == false)
				{
					$v->getVillage ()->removeUnits ($v, $killed);
				}
			}
		}
	}
	
	/*
		Private functions
	*/
	private function fight ()
	{
		if (!isset ($this->result) && !$this->isProcessed)
		{
			$pr = Neuron_Profiler_Profiler::getInstance ();
			
			$this->isProcessed = true;
			
			$pr->start ('Fighting battle between '.$this->attackingVillage->getName ().' and '.$this->defendingVillage->getName ());
		
			// Fetch a local copy of the troops to toss around
			$attackers = $this->attackers;
			$defenders = $this->defenders;
			
			$this->updateFrontages ($attackers, $defenders);

			$this->objLogger->debug_showTroops ($attackers, $defenders);
			
			$this->objLogger->addLog_status (self::STATUS_PREFIGHT);
		
			// Effects!
			$pr->start ('Executing battle effects');
			$this->doBattleEffects ($attackers, $defenders);
			$pr->stop ();
			
			if (!$this->checkBattleEnd ($attackers, $defenders))
			{
				$pr->start ('Executing ranged attacks');
				
				$this->objLogger->addLog_status (self::STATUS_SHOOTING);
			
				// Ranged attacks
				$this->doRangedAttacks ($attackers, $defenders, 1);
				$this->doRangedAttacks ($defenders, $attackers, 2);
				
				$pr->stop ();
			
				$i = 0;
				
				$this->objLogger->addLog_status (self::STATUS_MELEE);
			
				// Melee attacks until the battle is done
				while (!$this->checkBattleEnd ($attackers, $defenders))
				{
					$pr->start ('Executing movement round '.($i + 1));
					$this->doMoveUnits ($attackers, 1);
					$this->doMoveUnits ($defenders, 2);
					$pr->stop ();
					
					$pr->start ('Calculating new frontages');
					$this->updateFrontages ($attackers, $defenders);
					$pr->stop ();
					
					$pr->start ('Executing melee round '.($i + 1));
					$this->doMeleeBattle ($attackers, $defenders, 1);
					$this->doMeleeBattle ($defenders, $attackers, 2);
					$pr->stop ();
					
					$i ++;
				}
			}
			
			$this->objLogger->addLog_status (self::STATUS_FINISHED);
			
			$pr->start ('Calculating victory');
			$this->result = $this->calculateVictory ();
			$pr->stop ();
			
			$pr->start ('Killing special units');
			
			// Check if mages died in combat
			if ($this->result < 0.60)
			{
				// A number between 0 and 30
				//$chanse = (((1 - $this->result) / 2) - 0.20) * 100;
				
				$chanse = 50;
				
				foreach ($this->specialUnits as $v)
				{
					// At total loss, 30% chanse of dead.
					if (mt_rand (0, 100) < $chanse)
					{
						$v[0]->killUnit ();
						$this->objLogger->addLog_specialunitdied ($v[0]);
					}
				}
			}
			
			$this->objLogger->setVictory ($this->result);
			
			$this->objLogger->setFightlog ($this);
			
			$pr->stop ();
			
			$pr->stop ();
		}
	}
	
	// Update the frontages of all slots
	private function updateFrontages (&$attackers, &$defenders)
	{	
		foreach ($this->aSlots as $k => $v)
		{
			if (isset ($attackers[$k]) && isset ($defenders[$k]))
			{
				$actor = $attackers[$k];
				$target = $defenders[$k];
				
				$frontages = $this->getFrontages ($actor, $target);
				
				$frontage = $frontages[0];
				$targetfrontage = $frontages[1];
				
				$actor->setCurrentFrontage ($frontage);
				$target->setCurrentFrontage ($targetfrontage);
			}
			else
			{
				if (isset ($attackers[$k]))
					$attackers[$k]->setCurrentFrontage ($attackers[$k]->getStat ('frontage'));
				
				if (isset ($defenders[$k]))
					$defenders[$k]->setCurrentFrontage ($defenders[$k]->getStat ('frontage'));
			}
		}
	}
	
	private function doBattleEffects (&$attackers, &$defenders)
	{
		foreach ($this->specialUnits as $v)
		{
			if ($this->checkBattleEnd ($attackers, $defenders))
			{
				break;
			}
		
			$unit = $v[0];
			$effect = $v[1];
			
			// Process that effect!
			if ($effect instanceof Dolumar_Effects_Battle)
			{
				// Check if this would work out...
				$probability = $effect->getProbability ($unit, $this->defendingVillage, true);
				
				if (mt_rand (0, 100) < $probability)
				{
					$data = $effect->execute ($this, $attackers, $defenders);
					$this->objLogger->addLog_effect ($unit, $effect, $probability, $data);
					
					// Call triggers!
					$unit->onBattleSuccess ();
				}
				else
				{
					$this->objLogger->addLog_failedEffect ($unit, $effect, $probability);
					
					// Call the triggers
					$unit->onBattleFail ();
				}
			}
		}
	}
	
	/*
		Ranged attack!
	*/
	private function doRangedAttacks ($actors, $targets, $team)
	{
		foreach ($actors as $slotId => $actor)
		{
			$shooting = $actor->getStat ('shooting');
		
			if ($shooting > 0 && $actor->isReadyForAction ())
			{
				$this->objLogger->addLog_action ($actor, 'salvo');
			
				// Check for targets in adjacent spots
				$victims = array 
				(
					array (0.25, isset ($targets[$slotId-1]) ? $targets[$slotId-1] : false),
					array (0.50, isset ($targets[$slotId]) ? $targets[$slotId] : false),
					array (0.25, isset ($targets[$slotId+1]) ? $targets[$slotId+1] : false)
				);
			
				// Calculate total attack value
				$attack = $actor->getAmount () * $shooting;
			
				// Cause casualties
				foreach ($victims as $v)
				{
					if ($v[1])
					{
						// Calculate the actual amount of damage dealt
						$damage = $attack * $v[0];
						
						// Get the amount of defending units
						$defending = $v[1]->getAmount () - $v[1]->getKillUnitsQue ();
						
						if ($defending > 0)
						{
							// Don't forget the artilery defense!
							$damage -= $damage * $v[1]->getStat ('defAr') / 100;
					
							// Now how many units is that?
							$casualties = min ($defending, $damage / $v[1]->getStat ('hp'));
					
							$casualties = $v[1]->queKillUnits ($casualties);
					
							// Log this action
							$this->objLogger->addLog_casualties ($actor, $v[1], $casualties, 'shooting', $team);
						}
					}
				}
			}
			elseif ($shooting > 0)
			{
				$this->objLogger->addLog_stunned ($actor, $team);
			}
		}
	}
	
	/*
		Move units to the middle.
	*/
	private function doMoveUnits (&$units, $team = 1)
	{
		$iSlots = count ($this->aSlots);
		
		//echo "Slots: ".$iSlots . "\n";
		
		$middle = ceil ($iSlots / 2);
		
		for ($i = $middle, $inc = 1, $sign = -1; $i >= 0 && $i <= $middle * 2; $i += $inc * $sign, $sign *= -1, $inc ++)
		{		
			//echo $i . " ";
		
			// Check for move
			if ($i != $middle && isset ($units[$i]))
			{
				if (!isset ($units[$i+$sign]))
				{
					$units[$i]->setBattleSlot ($this->aSlots[$i+$sign]);
					$units[$i+$sign] = $units[$i];
					unset ($units[$i]);
					
					$this->objLogger->addLog_move ($units[$i+$sign], $i, $i + $sign, $team);
				}
			}
		}
		
		//echo "\n";
	}
	
	/*
		$attackers and $defenders fight against eachother
	*/
	private function doMeleeBattle ($actors, $targets, $team)
	{
		$middle = ceil (count ($this->aSlots) / 2);
		foreach ($actors as $slotId => $actor)
		{
			if ($actor->isReadyForAction ())
			{
				// Unit is facing enemy. Charge!
				if (isset ($targets[$slotId]))
				{
					// Only plain-in-the-middle attack!
					$this->doMeleeAttack ($actor, $targets[$slotId], $team, true, true);
				}
			
				// Unit is already in the middle, check 2 adjacent spaces
				elseif ($slotId == $middle)
				{
					if (isset ($targets[$slotId - 1]))
					{
						$isTargetFacingEnemy = isset ($actors[$slotId - 1]);
						$this->doMeleeAttack ($actor, $targets[$slotId - 1], $team, true, $isTargetFacingEnemy);
					}
					elseif (isset ($targets[$slotId + 1]))
					{
						$isTargetFacingEnemy = isset ($actors[$slotId + 1]);
						$this->doMeleeAttack ($actor, $targets[$slotId + 1], $team, true, $isTargetFacingEnemy);
					}
				}
			
				// Unit is not in the middle. Try to attack adjacent unit.
				else
				{
					// Check adjacent space
					$adjacent = $slotId > $middle ? $slotId - 1 : $slotId + 1;
				
					if (isset ($targets[$adjacent]))
					{
						$isTargetFacingEnemy = isset ($actors[$adjacent]);
						$this->doMeleeAttack ($actor, $targets[$adjacent], $team, true, $isTargetFacingEnemy);
					}
				}
			}
			else
			{
				$this->objLogger->addLog_stunned ($actor, $team);
			}
		}
	}
	
	private function getFrontageMultiplier(Dolumar_Units_Unit $unit)
	{
		$frontage = $unit->getStat ('frontage');
		$amount = $unit->getAmount () - $unit->getKillUnitsQue ();
		
		if ($amount < ($frontage * 10))
		{
			return 1;
		}
		else
		{
			$multiplier = floor (pow (2, (1 + log (ceil ($amount / ($frontage * 10)), 2))));
			return $multiplier;
		}
	}
	
	/*
		Return the frontage of 2 regiments against eachother
	*/
	private function getFrontage ($actor, $target)
	{
		$out = $this->getFrontages ($actor, $target);
		return $out[0];
	}
	
	/*
		Method to return both the frontage for the
		actor as the target
	*/
	private function getFrontages ($actor, $target)
	{
		$multiplier_actor = $this->getFrontageMultiplier ($actor);
		$multiplier_target = $this->getFrontageMultiplier ($target);
		
		// What actual multiplier are we going to us?
		$multiplier = min ($multiplier_actor, $multiplier_target);
		
		// Now return the actual frontage
		$frontage = $multiplier * $actor->getStat ('frontage');
		$targetfrontage = $multiplier * $target->getStat ('frontage');
		
		return array ($frontage, $targetfrontage);
	}
	
	/*
		Handle one melee attack
	*/
	private function doMeleeAttack ($actor, $target, $team, $isFlank = false, $isTargetFacingEnemy = false)
	{
		$pr = Neuron_Profiler_Profiler::getInstance ();
		
		$pr->start ('Executing a melee attack.');
	
		// Get amount of attacking units
		$amount = $actor->getAmount () - $actor->getKillUnitsQue () + $actor->getKilledInRound ();
		
		
		// Current frontage is calculated on moving.
		// except when this is a flank attack
		// In that case something weird happens: everyone
		// in the flanking unit fights to a total amount
		// of the defending frontage multiplier
		if ($isFlank)
		{
			// Is this a flank to flank attack?
			if ($isTargetFacingEnemy)
			{
				// No. Calculate the frontage based on the frontage
				// multiplier used by the target
				$multiplier = $target->getCurrentFrontage () / $target->getStat ('frontage');
			
				$frontage = $actor->getStat ('frontage') * $multiplier;
				$actor->setCurrentFrontage ($frontage);
			}
			else
			{
				// Yes. Just recalculate the frontage
				$frontages = $this->getFrontages ($actor, $target);
				$actor->setCurrentFrontage ($frontages[0]);
				$target->setCurrentFrontage ($frontages[1]);
			}
		}
		
		// Make sure only the first row fights
		$frontage = $actor->getCurrentFrontage ();
		
		// Shouldn't happen, but well, just to make sure.
		$frontage = max (1, $frontage);
		
		$amount = min ($frontage, $amount);
		
		// Get the amount of defending units
		$defending = $target->getAmount () - $target->getKillUnitsQue ();
		
		// Calculate the total amount of damage
		$damage = $amount * $actor->getStat ('melee');
		
		// Don't forget the resistance!
		$damage -= $damage * $target->getStat ($actor->getAttackType ()) / 100;
	
		// Now how many units is that?
		$casualties = min ($defending, $damage / $target->getStat ('hp'));
	
		$casualties = $target->queKillUnits ($casualties);
	
		// Log this action
		$this->objLogger->addLog_casualties ($actor, $target, $casualties, 'melee', $team);
		
		$pr->stop ();
	}
	
	/*
		Check if the battle is done. Return TRUE if finished.
	*/
	private function checkBattleEnd (&$attackers, &$defenders)
	{
		$this->iRound ++;
		
		// Check for run-away-troops
		$attackers = $this->checkForRunningUnits ($attackers, 1);
		$defenders = $this->checkForRunningUnits ($defenders, 2);
		
		if ($this->iRound > self::MAX_FIGHT_ITERATIONS)
		{
			return true;
		}
		else
		{
			return count ($attackers) == 0 || count ($defenders) == 0;
		}
	}
	
	/*
		Remove all units that run away due to fear (pussies!)
	*/
	private function checkForRunningUnits ($units, $team = 1)
	{
		foreach ($units as $k => $v)
		{
			$morale = $v->getMorale ();
			$orgAmount = $v->getAmount ();
			$curAmount = $orgAmount - $v->getKillUnitsQue ();

			$round = $v->getKilledInRound (true);
			
			if ($curAmount <= 0)
			{
				$this->objLogger->addLog_whipe ($units[$k], $team);
				$units[$k]->setBattleStatus (Dolumar_Units_Unit::BATTLE_STATUS_WHIPED);
				unset ($units[$k]);
			}
			
			elseif (!$this->checkMorale ($v, $morale, $orgAmount, $curAmount, $round))
			{
				$this->objLogger->addLog_flee ($units[$k], $team);
				$units[$k]->setBattleStatus (Dolumar_Units_Unit::BATTLE_STATUS_FLED);
				unset ($units[$k]);
			}
			
			$v->resetKilledInRound ();
		}
		
		return $units;
	}
	
	/*
		Returns TRUE if this troop keeps fighting
	*/
	private function checkMorale ($unit, $morale, $orgAmount, $curAmount, $killedLastRound)
	{
		$percentage = $curAmount / $orgAmount;
		
		$upperlimit = 0.85;
		$lowerlimit = 0.75;
		
		// Invert these numbers since the morale checks
		// are going to substract from this!
		$upperlimit = 1 - $upperlimit; // (1 - 0.85 = 0.15)
		$lowerlimit = 1 - $lowerlimit; // (1 - 0.75 = 0.25)
				
		foreach ($unit->getEffects () as $v)
		{
			$upperlimit = $v->procMoraleCheck ($upperlimit, $this); // 0.15 - 0.05 = 0.10
			$lowerlimit = $v->procMoraleCheck ($lowerlimit, $this); // 0.25 - 0.05 = 0.20
		}
		
		// Invert again to get our limit
		$upperlimit = 1 - $upperlimit; // (1 - 0.10 = 0.90)
		$lowerlimit = 1 - $lowerlimit; // (1 - 0.20 = 0.80)
		
		if ($percentage < $lowerlimit)
		{
			return false;
		}
		
		elseif ($percentage > $upperlimit)
		{
			return true;
		}
		
		else
		{
			// Example:
			// $base = 0.8 - 0.75 (= 0.05)
			// $float = (0.05 / (0.85 - 0.75)) = 0.05 / 0.1 = 0.5
			$base = $percentage - $lowerlimit;
			$float = $base / ($upperlimit - $lowerlimit);
			
			// Now float has a number from 0 to 1 (0 = no damage at all, 1 = lower limit reached)
			// So let's calculate a chanse from that
			$chanse = (1 - $float) * 100;
			return mt_rand (0, 100) > $chanse;
		}
		
		/*
		$chk = true;
		
		if ($percentage < 0.70)
		{
			return false;
		}
		
		else if ($percentage < 0.9 && $killedLastRound > 0)
		{
			$chanse = 100;
				
			foreach ($unit->getEffects () as $v)
			{
				$chanse = $v->procMoraleCheck ($chanse, $this);
			}
			
			$rand = mt_rand (0, 200);			
			$chk = $rand < $chanse;
		}
		return $chk;
		*/
	}
	
	/*
		Calculate the victory percentage (0 to 1)
	*/
	private function calculateVictory ()
	{		
		if (count ($this->attackers) == 0)
		{
			return 0;
		}
	
		// Calculate total health: count and sum all hitpoints
		$totalAttackersHealth = $this->getTotalStat ($this->attackers, 'hp');
		$newTotalAttackersHealth = 0;
		
		// Loop trough all attackers and substract the dead troops from the total.
		foreach ($this->attackers as $v)
		{
			if ($v->isActiveInBattle ())
			{
				$total = $v->getAvailableAmount ();
				$aantal = $total - $v->getKillUnitsQue ();
				
				$stats = $v->getStats ();
			
				$newTotalAttackersHealth += $aantal * $stats['hp'];
			}
		}
		
		if ($newTotalAttackersHealth > 0)
		{
			return $newTotalAttackersHealth / $totalAttackersHealth;
		}
		else
		{
			return 0;
		}
	}

	/*
		Helper function: make a sum of the stats of all units
	*/
	private function getTotalStat ($units, $atType = 'melee')
	{
		$attack = 0;
		foreach ($units as $v)
		{
			$attack += $this->getStat ($v, $atType) * $v->getAvailableAmount ();
		}
		return max (1, $attack);
	}

	/*
		Helper function: return a stat of one unit.
	*/
	private function getStat ($unit, $stat)
	{
		$stats = $unit->getStats ();
		return isset ($stats[$stat]) ? $stats[$stat] : 0;
	}
	
	public function __destruct ()
	{
		unset ( $this->attackers );
		unset ( $this->defenders );
		unset ( $this->result );
	
		unset ( $this->attackingVillage );
		unset ( $this->defendingVillage );
	
		unset ( $this->sDebugLog );
		unset ( $this->iRound );
		unset ( $this->aSlots );
		unset ( $this->specialUnits );
	
		//unset ( $this->objLogger );
		
		unset ($this->attArmy);
		unset ($this->defArmy);
	}
}
?>
