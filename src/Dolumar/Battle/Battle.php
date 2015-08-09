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

class Dolumar_Battle_Battle
{
	private $data = false;
	private $attackingVillage, $defendingVillage;
	private $logId;
	
	private $loadedSquads = array ();
	
	private $objLogger;
	
	private $id;
	
	const CLANMEMBERS_MAY_ATTACK_SMALL_OFFENDERS = false;
	const MAX_HONOUR_LOSS = 90;
	
	public static function getBattle ($id)
	{		
		return new self ($id);
	}
	
	public function __construct ($id)
	{		
		$this->id = $id;
		$this->objLogger = new Dolumar_Battle_Logger ();
	}
	
	public static function getMaxStolenRunesPercentage ($defender, $debug = false)
	{
		$percentage = 10;
		
		$logs = Dolumar_Players_Logs::getInstance ();
		$logs->clearFilters ();
		$logs->addShowOnly ('defend');
		
		$totalrunes = $defender->resources->getTotalRunes ();
		$maxperday = $totalrunes * 0.25;
		
		if ($maxperday < 1)
		{
			return $percentage;
		}
		
		$logs->setTimeInterval (NOW - 60*60*24, NOW);
		
		$stolenrunes = 0;
		foreach ($logs->getLogs ($defender, 0, 100) as $v)
		{
			$stolenrunes += $v['stolen_runes'];
		}
		
		$calc = 1 - $stolenrunes / $maxperday;
		$npercentage = max (0, $calc * $percentage);
		
		if ($debug)
		{
			echo "Village: ".$defender->getName ()."\n";
			echo "Total runes: ".$totalrunes."\n";
			echo "Normal steal percentage: ".$percentage . "\n";
			echo "Stolen today: ".$stolenrunes."\n";
			echo "Max runes per day ($totalrunes * 0.25) = ".$maxperday."\n";
			echo "x = 1 - (". $stolenrunes . " / " . $maxperday . ") = " . $calc . "\n";
			echo "Normal percentage * x: ".$npercentage . "\n";
		}	
		
		return $npercentage;	
	}
	
	/*
		Check if this target was attacked by this clan earlier.
	*/
	public static function didClanAttackTargetEarlier ($attacker, $target)
	{
		$clanlogs = Dolumar_Players_ClanLogs::getInstance ();
		
		$clanlogs->clearFilters ();
		$clanlogs->addShowOnly ('attack');
		
		$clanlogs->setTimeInterval (time () - 60*60*24);
		
		$logs = $clanlogs->getClanLogs ($attacker->getOwner ()->getClans (), 0, 500, 'DESC');
		
		foreach ($logs as $v)
		{
			if ($v['action'] == 'attack')
			{
				if (!$v['attacker']->equals ($attacker) && $v['defender']->equals ($target))
				{
					return true;
				}
			}
		}
		
		//print_r ($logs);
	
		return false;
	}
	
	/*
		Helper function to calculate the size difference
		between two villages.
	*/
	public static function getSizeDifference ($attackingVillage, $defendingVillage)
	{
		//$score_attacker = $attackingVillage->resources->getTotalRunes ();
		//$score_defender = $defendingVillage->resources->getTotalRunes ();
		$score_attacker = $attackingVillage->getOwner ()->getTotalRunes ();
		$score_defender = $defendingVillage->getOwner ()->getTotalRunes ();
	
		// Update the honour
		return $score_attacker / max (1, $score_defender);
	}
	
	public static function getHonourPenalty ($attackingVillage, $defendingVillage)
	{		
		$max_honour_loss = self::MAX_HONOUR_LOSS;

		// If the defending village has been offline for 31 days, honour doesn't count
		$lastRefresh = $defendingVillage->getOwner ()->getLastRefresh ();
		if (abs (time () - $lastRefresh) > 60*60*24*31)
		{
			return 0;
		}
		
		$rankDiff = self::getSizeDifference ($attackingVillage, $defendingVillage);

		//$score_attacker = $attackingVillage->getScore ();
		//$score_defender = $defendingVillage->getOwner ()->getScore ();
		
		//$score_attacker = $attackingVillage->resources->getTotalRunes ();
		//$score_defender = $defendingVillage->resources->getTotalRunes ();

		$score_attacker = $attackingVillage->getOwner ()->getTotalRunes ();
		$score_defender = $defendingVillage->getOwner ()->getTotalRunes ();
		
		if ($rankDiff > 1.25) // Only react when attacker is 1.5 times bigger
		{
			//$honour = min (30, ceil ($rankDiff * 15));
			$honour = ((($score_attacker * 0.8) / max (1, $score_defender)) - 1) * 100;
			
			$honour = min (ceil ($honour), $max_honour_loss);
			
			return $honour;
		}
		
		return 0;
	}
	
	public function setData ($data)
	{
		if (!isset ($data['vid']))
		{
			throw new Exception ('Wrong battle data set: '.print_r ($data, true));
		}
		
		$this->data = $data;
		
		$fightdate = min (NOW, $data['fightDate']);
		
		$a = Dolumar_Players_Village::getVillage ($data['vid'],      $fightdate, false, true);
		$d = Dolumar_Players_Village::getVillage ($data['targetId'], $fightdate, false, true);
		
		$this->attackingVillage = $a;
		$a->processBattles ();
		
		$this->defendingVillage = $d;
		$d->processBattles ();
	}
	
	public function getData ()
	{
		$this->loadData ();
		return $this->data;
	}
	
	private function loadData ()
	{
		if (!is_array ($this->data))
		{
			$db = Neuron_Core_Database::__getInstance ();
	
			$l = $db->select ('battle', array ('*'), "battleId = ".$this->getId ());
			
			if (count ($l) == 1)
			{
				$this->setData ($l[0]);
			}
			else
			{
				throw new Exception ('Battle could not be loaded: '.$this->getId ());
			}
		}
	}
	
	/*
		Force the object to fetch new data from MySQL.
	*/
	public function reloadData ()
	{
		$this->data = null;
	}
	
	public function getId ()
	{
		return intval ($this->id);
	}

	public function getAttacker ()
	{
		$this->loadData ();
		return $this->attackingVillage;
	}

	public function getDefender ()
	{
		$this->loadData ();
		return $this->defendingVillage;
	}
	
	public function isAttacker ($village)
	{
		return $this->getAttacker ()->equals ($village);
	}
	
	public function isDefender ($village)
	{
		return !$this->isAttacker ($village);
	}

	public function execute ()
	{				
		$db = Neuron_Core_Database::__getInstance ();
	
		$this->loadData ();
		
		// Check if there are survivors
		$doesGoHome = true;
		
		// Check for delay!
		$chk = $db->select
		(
			'battle',
			array ('MAX(endFightDate) AS maxEndFightDate,COUNT(battleId) AS amPendingBattles'),
			"arriveDate < ".$this->data['arriveDate']." AND targetId = ".$this->data['targetId']
		);
		
		if ($chk[0]['maxEndFightDate'] > $this->data['fightDate'])
		{
			// Delay this battle!
			$this->delayFightDate ($chk[0]['maxEndFightDate'], $chk[0]['amPendingBattles']);

			return false;
		}
		else
		{
			// Update honouer
			$this->updateHonour ($this->attackingVillage, $this->defendingVillage);
		
			// Defending village
			$this->defendingVillage->clearEffects ();
			$this->defendingVillage->addEffect (new Dolumar_Effects_Boost_TowerBonus ($this->getDefendBonus () / 100));
		
			$profiler = Neuron_Profiler_Profiler::__getInstance ();
			$profiler->start ('Fighting battle '.$this->data['battleId']);
		
			$battle = $this->data;
			if ($battle['attackType'] == 'attack')
			{
				// Load the attack slots
				//$slots = $this->attackingVillage->getAttackSlots ($this->defendingVillage);
				$slots = $this->defendingVillage->getDefenseSlots ($battle['iBattleSlots']);

				// Load attacking troops
				$squads = $db->select
				(
					'battle_squads',
					array ('*'),
					"bs_bid = {$battle['battleId']}"
				);

				$attacking = array ();

				foreach ($squads as $v)
				{
					$squad = $this->getSquad ($v['bs_squadId']);
					$unit = $squad->getUnit ($v['bs_unitId']);
					if ($unit && isset ($slots[$v['bs_slot']]))
					{
						$attacking[$v['bs_slot']] = $unit;
						$attacking[$v['bs_slot']]->setBattleSlot ($slots[$v['bs_slot']]);
					}
				}
			
				// Load defending units
				$defending = $this->defendingVillage->getDefendingUnits ($this->data['fightDate'], $battle['iBattleSlots']);

				// Let's do the fight
				$fight = new Dolumar_Battle_Fight
				(
					$this->attackingVillage,
					$this->defendingVillage,
					$attacking,
					$defending,
					$slots,
					$this->getSpecialUnits (),
					$this->objLogger
				);
			
				$fightLog = $this->killAndLog ($fight);
				$resultLog = json_encode ($this->takeAndDestroy ($fight));
			
				$this->logId = $db->insert
				(
					'battle_report',
					array
					(
						'fightDate' => $battle['fightDate'],
						'fightDuration' => $this->objLogger->getDuration (),
						'battleId' => $battle['battleId'],
						'fromId' => $this->attackingVillage->getId (),
						'targetId' => $this->defendingVillage->getId (),
						'fightLog' => $fightLog,
						'resultLog' => $resultLog,
						'battleLog' => $this->objLogger->getFightLog (),
						'squads' => $this->objLogger->getSquads (),
						'slots' => $this->objLogger->getInitialSlots ($battle['iBattleSlots']),
						'victory' => $fight->getResult (),
						'specialUnits' => $this->objLogger->getSpecialUnits (),
						'execDate' => 'NOW()'
					)
				);
				
				if ($this->objLogger->getAttackingArmy ()->getKilledPercentage () >= 100)
				{
					$doesGoHome = false;
				}
			
				//mail ('daedelson@gmail.com', 'Battle test', $fightLog);

				// Add log
				$objLogs = Dolumar_Players_Logs::__getInstance ();
				$objLogs->addBattleReport ($this);
				
				$fight->__destruct ();
				unset ($fight);
			}
		
			$duration = $this->objLogger->getDuration ();

			// Update local data
			$this->data['isFought'] = 1;
			$this->data['endFightDate'] = $this->data['fightDate'] + $duration;
			
			// Check if there are survivors
			if ($doesGoHome)
			{
				$this->data['endDate'] = $this->data['endFightDate'] + $this->data['goHomeDuration'];
			}
			else
			{
				$this->data['endDate'] = $this->data['endFightDate'] + 1;
			}
			
			$this->data['bLogId'] = $this->logId;

			// Update mysql
			$db->update
			(
				'battle',
				array 
				(
					'isFought' => 1,
					'endDate' => $this->data['endDate'],
					'endFightDate' => $this->data['endFightDate'],
					'bLogId' => $this->logId
				),
				"battleId = '{$battle['battleId']}'"
			);
			
			// Reload units
			$this->attackingVillage->reloadUnits ();
			$this->defendingVillage->reloadUnits ();
			
			$this->defendingVillage->recalculateNetworth ();
			
			$this->defendingVillage->onBattleFought ($this);
			$this->attackingVillage->onBattleFought ($this);
		
			$profiler->stop ();
		}
	}
	
	private function updateHonour ($attackingVillage, $defendingVillage)
	{
		$data = $this->getData ();
		$honour = intval ($data['iHonourLose']);
		
		if ($honour > 0 && $this->doesHonourCount ())
		{
			if ($this->didClanAttackTargetEarlier ($attackingVillage, $defendingVillage))
			{
				// Clan members only lose half.
				$honour = $honour / 2;
				
				// We will withdraw 2 times from attacker.
				$this->attackingVillage->honour->withdrawHonour ($honour);
				
				$clans = $attackingVillage->getOwner ()->getClans ();
				foreach ($clans as $clan)
				{
					foreach ($clan->getMembers () as $member)
					{
						foreach ($member->getVillages () as $v)
						{
							$v->honour->withdrawHonour ($honour);
						}
					} 
				}
			}
			else
			{
				// Withdraw this honour
				$this->attackingVillage->honour->withdrawHonour ($honour);
			}
		}
	}
	
	/*
		This function will only be called when the difference
		between the attacker and the defender is big enough
		to cause a honour penalty.
	*/
	private function doesHonourCount ()
	{
		// Check if attacker (or one of attackers friends) have been
		// under attack within the last 48 hours (REAL TIME, no game time.)
		$owner = $this->attackingVillage->getOwner ();
		if ($owner)
		{
			$clans = $owner->getClans ();
			
			// No clans: only your own village counts.
			if (self::CLANMEMBERS_MAY_ATTACK_SMALL_OFFENDERS || count ($clans) == 0)
			{
				$sWhere = "targetId = ".$this->attackingVillage->getId ();
			}
			
			// Player is in at least one clan. 
			// Loop trough clans, players and villages
			else
			{
				$sWhere = "";
				foreach ($clans as $clan)
				{
					foreach ($clan->getMembers () as $player)
					{
						foreach ($player->getVillages () as $v)
						{
							$sWhere .= "targetId = ".$v->getId ()." OR ";
						}
					}
				}
				$sWhere = substr ($sWhere, 0, -4);
			}
		}
		else
		{
			$sWhere = "targetId = ".$this->attackingVillage->getId ();
		}
		
		// Use this where to find out if these players has been under attack
		$db = Neuron_DB_Database::__getInstance ();
		
		$startdate = $this->data['startDate'];
		
		$chk = $db->query
		("
			SELECT
				reportId
			FROM
				battle_report
			WHERE
				fromId = ".$this->defendingVillage->getId()." AND 
				execDate > FROM_UNIXTIME(".($startdate - 60*60*24*1).") AND
				($sWhere)
		");
	
		return count ($chk) == 0;
	}
	
	private function getSpecialUnits ()
	{
		$db = Neuron_Core_Database::__getInstance ();
	
		// Load the special units
		$actions = array ();
		
		$specials = $db->getDataFromQuery 
		(
			$db->customQuery
			("
				SELECT
					*
				FROM
					battle_specialunits
				WHERE
					bsu_bid = ".$this->data['battleId']."
			")
		);
		
		// Fetch all units 
		$aAllSpecials = $this->attackingVillage->getSpecialUnits (true);
		
		foreach ($specials as $special)
		{
			if (isset ($aAllSpecials[$special['bsu_vsu_id']]))
			{
				$action = $aAllSpecials[$special['bsu_vsu_id']]->getEffect ($special['bsu_ba_id']);
				
				if ($action)
				{
					if ($action instanceof Dolumar_Effects_Battle)
					{
						$actions[] = array ($aAllSpecials[$special['bsu_vsu_id']], $action);
					}
				}
			}
		}
		
		return $actions;
	}
	
	/*
		Delay the fight to $duration time.
		($duration = timestamp)
	*/
	public function delayFightDate ($duration, $pendingBattles)
	{
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		$profiler->start ('Delaying fight');
	
		//echo 'Delaying fight '.$this->getId ().' to '.$duration."\n";
		$aproxBattleDuration = ceil ((60 * 10) / GAME_SPEED_MOVEMENT);
		
		$pendingBattles = $pendingBattles - 1;
	
		$this->loadData ();
	
		$db = Neuron_Core_Database::__getInstance ();

		if ($this->data['fightDate'] <= $duration)
		{
			$timestamp = $duration + ($aproxBattleDuration /* * $pendingBattles */);
		
			$db->update
			(
				'battle',
				array 
				(
					'fightDate' => $timestamp,
					'endFightDate' => $timestamp + $aproxBattleDuration,
					'endDate' => $timestamp + $this->data['goHomeDuration'] + 1
				),
				"battleId = ".$this->getId ()
			);
		
			$this->data['fightDate'] = $timestamp;
			$this->data['endDate'] = $timestamp + $this->data['goHomeDuration'];
		}
		
		$profiler->stop ();
	}
	
	private function getSquad ($id)
	{
		if (!isset ($this->loadedSquads[$id]))
		{
			$this->loadedSquads[$id] = new Dolumar_Players_Squad ($id);
		}
		return $this->loadedSquads[$id];
	}

	private function killAndLog ($fight)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$fight->killUnits ();

		return $this->objLogger->getFightlogFromFight ($fight);;
	}

	private function takeAndDestroy ($fight)
	{	
		$result = $fight->getResult ();
		
		if ($result < 0.1)
		{
			return array 
			(
				'resources' => array (),
				'runes' => array ()
			);
		}

		$output = array ();

		// Give a rank bonus
		/*
		$rankingBonus =
		(
			$this->defendingVillage->getNetworth () /
			max ($this->attackingVillage->getNetworth (), 1)
		);
		*/
		
		$rankingBonus =
		(
			$this->defendingVillage->resources->getTotalRunes () / 
			max ($this->attackingVillage->resources->getTotalRunes (), 1)
		);

		// Modify bonus
		$iRankingBonus = min ($rankingBonus, 1);
		$iRankingBonus = max (0.10, $iRankingBonus);

		$procent = $result * 15;
		
		$resources = $this->defendingVillage->resources->getResources ();

		$output['resources'] = array ();
		foreach ($resources as $k => $v)
		{
			$proc = max (0, rand ($procent - 5, $procent + 5));
			$am = floor ($v * ($proc / 100) );

			if ($am > 0)
			{
				$output['resources'][$k] = $am;
			}
		}

		// take resources
		if (!$this->defendingVillage->resources->takeResources ($output['resources']))
		{
			$output['resources'] = array ();
		}
		
		// Procent
		$iRuneStealPercentage = $this->getMaximumStolenRunesPercentage () * $iRankingBonus;
		
		$fVictory = $result;
		$fVictory += 0.03;
		
		if ($fVictory > 1)
		{
			$fVictory = 1;
		}
		
		$procent = ($fVictory * $iRuneStealPercentage) / 100;
		
		$stolenRunes = floor ($this->defendingVillage->resources->getTotalRunes () * $procent);
		
		//$stolenRunes = 0;
		
		$output['runes'] = $this->defendingVillage->stealRunes ($stolenRunes);

		return $output;
	}
	
	/*
		Return the percentage of runes that is stolen
		from the defender in a 100% victory in case
		both villages are of equal size.
		
		(Used to be 10.)
	*/
	private function getMaximumStolenRunesPercentage ()
	{
		return self::getMaxStolenRunesPercentage ($this->getDefender ());
	}

	public function removeBattle ()
	{
		$this->loadData ();
		$battle = $this->data;

		$db = Neuron_Core_Database::__getInstance ();

		// Get result log
		$log = $db->select
		(
			'battle_report',
			array ('*'),
			"reportId = '{$battle['bLogId']}'"
		);
		
		//$debug_str = "Battle debug log: \n\n";

		if (count ($log) == 1)
		{
			$log = $log[0];
			
			// JSON for the win!
			$loot = json_decode ($log['resultLog'], true);
			
			/*
			$debug_str .= "Loot:\n".print_r ($loot, true)."\n\n";
			$debug_str .= "Current resources:\n".print_r ($this->attackingVillage->resources->getResources (), true)."\n\n";
			$debug_str .= "Capacity:\n".print_r ($this->attackingVillage->resources->getCapacity (), true)."\n\n";
			*/
			
			if (isset ($loot['resources']) && is_array ($loot['resources']) && count ($loot['resources']) > 0)
			{
				if (!$this->attackingVillage->resources->giveResourcesAndRunes ($loot['resources']))
				{
					throw new Neuron_Core_Error 
					(
						'Failed to give resources to attacking village in battle report '.$battle['bLogId']."\n".
						print_r ($loot['resources'], true)."\n".
						$this->attackingVillage->getError ()
					);
				}
			}
			
			if (isset ($loot['runes']) && is_array ($loot['runes']))
			{
				foreach ($loot['runes'] as $k => $v)
				{
					$this->attackingVillage->resources->giveRune ($k, $v);
				}
			}
		}
		
		/*
		else
		{
			throw new Neuron_Core_Error ('Battle report not found!');
		}
		*/
		
		$this->doRemoveBattle ();
		
		//$debug_str .= implode ("\n-------------------------------------------\n", $db->getAllQueries ());
	}
	
	/*
		Remove the complete battle
	*/
	public function doRemoveBattle ()
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$this->loadData ();
		$battle = $this->data;
	
		// Remove battle
		$db->remove
		(
			'battle',
			"battleId = '{$battle['battleId']}'"
		);

		// Remove squads
		$db->remove
		(
			'battle_squads',
			"bs_bid = '{$battle['battleId']}'"
		);	
		
		// Remove special units
		$db->remove
		(
			'battle_specialunits',
			"bsu_bid = '{$battle['battleId']}'"
		);
	}

	public static function resourceToText ($res, $showRunes = true, $dot = true)
	{
		return Dolumar_Buildings_Building::resourceToText ($res, $showRunes, $dot);
	}

	public function getLogId ()
	{
		return $this->logId;
	}
	
	public function getStartDate ()
	{
		$this->loadData ();
		return $this->data['startDate'];
	}
	
	public function getArriveDate ()
	{
		$this->loadData ();
		return $this->data['arriveDate'];
	}
	
	public function getFightDate ()
	{
		$this->loadData ();
		return $this->data['fightDate'];
	}
	
	public function getEndDate ()
	{
		$this->loadData ();
		return $this->data['endDate'];
	}
	
	public function isFought ()
	{
		$this->loadData ();
		return intval ($this->data['isFought']) == 1;
	}
	
	private function getDefendBonus ()
	{
		$bonus = $this->defendingVillage->battle->getDefenseBonus ();
		
		foreach ($this->getDefender ()->getEffects ($this->getFightDate ()) as $v)
		{
			$bonus = $v->procDefenseBonus ($bonus);
		}
		
		return $bonus;
	}
	
	/*
		Normally, an ongoing battle is visible.
		Some effects, though, have the ability to hide them.
	*/
	public function isVisible ()
	{
		if ($this->isFought ())
		{
			return true;
		}
		
		foreach ($this->getDefender ()->getEffects () as $v)
		{
			if (!$v->procBattleVisible ($this))
			{
				return false;
			}
		}
		
		return true;
	}
	
	public function canWithdraw ($objPlayer = null)
	{
		if (isset ($objPlayer))
		{
			if (!$this->getAttacker ()->getOwner ()->equals ($objPlayer))
			{
				return false;
			}
		}
	
		return $this->getFightDate () > time ();
	}
	
	/*
		Withdraw the battle
	*/
	public function withdraw ()
	{
		if ($this->canWithdraw ())
		{
			// Logs
			$logs = Dolumar_Players_Logs::getInstance ();
			$logs->addWithdrawBattle ($this);
			
			$db = Neuron_Core_Database::__getInstance ();
			
			//$battle = $this->getData ();
			
			// Calculate the "return home" time
			$duration = NOW - $this->getStartDate ();
			
			// Double return time
			$duration = $duration * 2;
			
			//die ('duration: '. $duration);
			
			// Update mysql
			$db->update
			(
				'battle',
				array 
				(
					'isFought' => 1,
					'arriveDate' => NOW - 1,
					'endDate' => NOW + $duration,
					'fightDate' => NOW - 1,
					'endFightDate' => NOW - 1,
					'bLogId' => 0
				),
				"battleId = '{$this->getId ()}'"
			);
			
			$this->reloadData ();
			
			// Removal
			//$this->doRemoveBattle ();
		}
	}
	
	public function __destruct ()
	{
		unset ($this->data);
		
		unset ($this->attackingVillage);
		unset ($this->defendingVillage);
		
		unset ($this->logId);
	
		unset ($this->loadedSquads);
	
		unset ($this->objLogger);
	
		unset ($this->id);
	}
}
?>
