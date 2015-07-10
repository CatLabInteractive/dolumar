<?php
/*
	This class handles everything that has to do with battles.
*/
class Dolumar_Players_Village_Battle
{
	private $objProfile;
	
	private $iLastBattleCheck = 0;

	public function __construct ($profile)
	{
		$this->objProfile = $profile;
	}

	private function getVillage ()
	{
		return $this->objProfile;
	}
	
	/*
		Return the % of towers in the defending village.
	*/
	public function getTowerPercentage ()
	{
		$towers = $this->objProfile->buildings->getBuildingsRuneAmount ('Dolumar_Buildings_Tower');
		$runes = $this->objProfile->resources->getUsedRunes_amount ();
		
		// Calculate percentage
		if ($runes > 0)
		{
			$out = ($towers / $runes) * 100;
		}
		else
		{
			$out = 0;
		}
		
		return $out;
	}
	
	public function getDefenseBonus ()
	{
		$bonus = $this->getTowerPercentage ();
		$bonus *= 4;
		
		return min (100, $bonus);
	}
	
	/*
		Process battles.
	*/
	public function processBattles ($now)
	{
		// Check if it has been checked for this
		if ($now <= $this->iLastBattleCheck)
		{
			return;
		}
		
		$this->iLastBattleCheck = $now;
		
		// To not replace all this shit.
		$villageId = $this->objProfile->getId ();
	
		// Start profiler & stuff
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		
		$profiler->start ('Calculating battles for village '.$villageId.' ('.$now.')');

		// The lock will make sure that every battle will only be calculated one time.
		$lock = Neuron_Core_Lock::__getInstance ();

		// First: make sure this village hasn't been calculated yet
		/*
		if ($lock->setSoftLock ('battle_village', $villageId.'_'.$now))
		{
		*/			
			$dbi = Neuron_DB_Database::getInstance ();
			
			$villageId = intval ($villageId);
			
			$battles = $dbi->query
			("
				SELECT
					battleId
				FROM
					battle
				WHERE
					(vid = {$villageId} OR targetId = {$villageId})
					AND (
						(fightDate < {$now} AND isFought = '0') OR
						endDate < {$now}
					)
				ORDER BY
					fightDate ASC,
					endDate ASC,
					battleId ASC
			");
			
			$profiler->start ('Processing ' . count ($battles) . ' battles.');
			
			foreach ($battles as $bdata)
			{
				$profiler->start ('Processing battle '.$bdata['battleId']);
			
				// Only process every battle ones
				if ($lock->setLock ('battle', $bdata['battleId']))
				{
					$profiler->start ('Lock set, process battles.');
									
					$battle = Dolumar_Battle_Battle::getBattle ($bdata['battleId']);
					//$battle->setData ($aBattle, $this->objProfile);
		
					// Check for fight execution
					if ($battle->getFightDate () <= $now && !$battle->isFought ())
					{
						$profiler->start ('Executing battle #'.$battle->getId ());
									
						// Execute battle
						$battle->execute ();
			
						$profiler->stop ();
					}

					// Check for fight removal
					if ($battle->getEndDate () <= $now && $battle->isFought ())
					{
						$profiler->start ('Removing battle #'.$battle->getId ());
		
						// Do finish battle stuff
						$battle->removeBattle ();
			
						$profiler->stop ();
					}
				
					//$battle->__destruct ();
					unset ($battle);
					
					//}
					
					$lock->releaseLock ('battle', $bdata['battleId']);
					
					$profiler->stop ();
					
					$this->objProfile->reloadData ();
					
					reloadEverything ();
				}
				else
				{
					$profiler->start ('Battle is already locked, not doing anything.');
					$profiler->stop ();
				}
				
				$profiler->stop ();
			}
			
			$profiler->stop ();
		
		$profiler->stop ();
	}
	
	public function getMoveDuration ($oUnits, $afstand, $isMoveTo = true)
	{
		if (count ($oUnits) == 0)
		{
			return 10;
		}
		
		foreach ($oUnits as $unit)
		{
			$u_speed = $unit->getSpeed ();
			if (!isset ($speed) || $u_speed < $speed)
			{
				$speed = $u_speed;
			}
		}
		
		$parameter = 1;
		if (!$isMoveTo)
		{
			$parameter = 1.33;
		}
		
		return ($afstand * $parameter * 60 * 10) / (GAME_SPEED_MOVEMENT * $speed);
	}
	
	/*
		$specialUnits = array (array ($objUnit, $sAction));
	*/
	public function attackVillage ($oTarget, $oUnits, $specialUnits = array ())
	{
		if (count ($oUnits) > 0)
		{
			$db = Neuron_Core_Database::__getInstance ();
			
			// First: check if special units are valid
			$aSpecialUnits = array ();
			foreach ($specialUnits as $v)
			{
				$action = $v[0]->getEffect ($v[1]);
				if ($action && $action instanceof Dolumar_Effects_Battle)
				{
					$aSpecialUnits[] = array ($v[0], $action);
				}
			}
			
			// Second: calculate the sum of all resource cost
			$aResources = array ();
			foreach ($aSpecialUnits as $v)
			{
				foreach ($v[1]->getCost ($v, $oTarget) as $res => $am)
				{
					if (isset ($aResources[$res]))
					{
						$aResources[$res] += $am;
					}
					else
					{
						$aResources[$res] = $am;
					}
				}
			}
			
			// Thirdly: withdraw the required amount
			if (!$this->objProfile->resources->takeResourcesAndRunes ($aResources))
			{
				$this->error = 'no_resources';
			}
			
			else
			{
				$afstand = Dolumar_Map_Map::getDistanceBetweenVillages ($this->objProfile, $oTarget);
				
				$naarVillage = $this->getMoveDuration ($oUnits, $afstand, true  );
				$naarHuis    = $this->getMoveDuration ($oUnits, $afstand, false );
		
				$fightDate = NOW + $naarVillage;
				
				// Honour!!!
				$honour = Dolumar_Battle_Battle::getHonourPenalty ($this->objProfile, $oTarget);
				
				$slotsamount = count ($this->getAttackSlots ($oTarget));
		
				// Insert battle
				$battleId = $db->insert
				(
					'battle',
					array
					(
						'vid' => $this->objProfile->getId (),
						'targetId' => $oTarget->getId (),
						'startDate' => NOW,
						'arriveDate' => (NOW + $naarVillage),
						'fightDate' => $fightDate,
						'endDate' => $fightDate + $naarHuis + 1,
						'goHomeDuration' => $naarHuis,
						'iHonourLose' => $honour,
						'iBattleSlots' => $slotsamount
					)
				);
			
				// Add troops
				foreach ($oUnits as $slot => $unit)
				{
					$db->insert
					(
						'battle_squads',
						array
						(
							'bs_bid' => $battleId,
							'bs_squadId' => $unit->getSquad ()->getId (),
							'bs_unitId' => $unit->getUnitId (),
							'bs_vid' => $unit->getVillage ()->getId (),
							'bs_slot' => $slot
						)
					);
				}
				
				// Add special troops
				foreach ($aSpecialUnits as $v)
				{
					$db->insert
					(
						'battle_specialunits',
						array
						(
							'bsu_bid' => $battleId,
							'bsu_vsu_id' => $v[0]->getId (),
							'bsu_ba_id' => $v[1]->getId (),
							'bsu_vid' => $this->objProfile->getId ()
						)
					);
				}
		
				// Notify players
				$pl_attacker = $this->objProfile->getOwner ();
				$pl_defender = $oTarget->getOwner ();

				$notExtra = array 
				(
					'attacker' => $this->objProfile, 
					'defender' => $oTarget,
					'pl_attacker' => $this->objProfile->getOwner (),
					'pl_defender' => $oTarget->getOwner ()
				);

				$pl_attacker->sendNotification 
				(
					'attacking', 
					'battle', 
					array
					(
						'defender' => $oTarget,
						'pl_defender' => $oTarget->getOwner (),
						'village' => $this->objProfile,
						'player' => $this->objProfile->getOwner ()
					),
					$pl_attacker,
					true // This is a public message
				);
				
				
				$pl_defender->sendNotification 
				(
					'defending', 
					'battle', 
					array
					(
						'attacker' => $this->objProfile,
						'pl_attacker' => $this->objProfile->getOwner (),
						'village' => $oTarget,
						'player' => $oTarget
					),
					$pl_attacker,
					false
				);
			
				// Done
				return true;
			}
		}
		else
		{
			$this->error = 'no_troops';
			return false;
		}
	}
	
	public function getAttackSlots ($oTargetVillage)
	{
		return $oTargetVillage->getDefenseSlots ();
	}
	
	public function getDefenseSlots ($amount = null)
	{
		if (!isset ($amount) || $amount < 1)
		{
			$runes = $this->objProfile->resources->getTotalRunes ();
			$amount = 1 + (ceil ($runes / 75) * 2);
		}
	
		// Load the slots
		$db = Neuron_Core_Database::__getInstance ();
		
		$l = $db->select
		(
			'villages_slots',
			array ('*'),
			"vs_vid = ".$this->objProfile->getId ()
		);
		
		$out = array ();
		foreach ($l as $v)
		{
			$out[$v['vs_slot']] = Dolumar_Battle_Slot_Grass::getFromId ($v['vs_slotId'], $v['vs_slot'], $this->objProfile);
		}
		
		if (count ($out) < $amount)
		{
			for ($i = 1; $i <= $amount; $i ++)
			{
				if (!isset ($out[$i]))
				{
					$out[$i] = Dolumar_Battle_Slot_Grass::getRandomSlot ($i, $this->objProfile);
					
					$db->insert
					(
						'villages_slots',
						array
						(
							'vs_vid' => $this->objProfile->getId (),
							'vs_slot' => $i,
							'vs_slotId' => $out[$i]->getSlotId ()
						)
					);
				}
			}
		}
		
		while (count ($out) > $amount)
		{
			array_pop ($out);
		}
		
		return $out;
	}

	public function countBattles ()
	{
		$db = Neuron_DB_Database::getInstance ();

		$attacking = $db->query ("SELECT COUNT(*) AS aantal FROM battle_report WHERE fromId = {$this->getVillage ()->getId ()}");
		$defending = $db->query ("SELECT COUNT(*) AS aantal FROM battle_report WHERE targetId = {$this->getVillage ()->getId ()}");
		$won = $db->query ("SELECT COUNT(*) AS aantal FROM battle_report WHERE (fromId = {$this->getVillage ()->getId ()} OR targetId = {$this->getVillage ()->getId ()}) AND victory > 0.1");
		$lost = $db->query ("SELECT COUNT(*) AS aantal FROM battle_report WHERE (fromId = {$this->getVillage ()->getId ()} OR targetId = {$this->getVillage ()->getId ()}) AND victory <= 0.1");

		$attacking = $attacking[0]['aantal'];
		$defending = $defending[0]['aantal'];
		$won = $won[0]['aantal'];
		$lost = $lost[0]['aantal'];

		$total = $attacking + $defending;

		return array 
		(
			'total' => $total,
			'attacking' => $attacking,
			'defending' => $defending,
			'won' => $won,
			'lost' => $lost
		);
	}
	
	public function __destruct ()
	{
		unset ($this->objProfile);
	}
}
?>
