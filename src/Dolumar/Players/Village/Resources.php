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

class Dolumar_Players_Village_Resources
{
	private $objMember;
	private $data;
	
	private $runes = null;
	private $resUpdated = false;
	
	private $totalRunes = null;
	
	private $sError;
	
	private $usedRunes = null;
	
	private $lastReload = null;
	private $shortagefactor;
	
	private $areTransfersProcessed = false;

	public function __construct ($objMember)
	{
		$this->objMember = $objMember;
		
		// Make sure the battles are processed.
		$objMember->processBattles ();
	}
	
	/*
		We are going to load the data from the objMember
	*/
	private function loadData ()
	{
		$this->data = $this->objMember->getData ();
	}
	
	/*
		BONUS time!
	*/
	public function getInitialRunes ()
	{
		$amount = 4;
		if (isset ($_COOKIE['player_bonus']))
		{
			$amount ++;
		}
	
		return array
		(
			'wind' => $amount,
			'water' => $amount,
			'fire' => $amount,
			'earth' => $amount
		);
	}

	public function getResources ()
	{
		$this->loadData ();
		
		if (!$this->resUpdated)
		{
			$db = Neuron_Core_Database::__getInstance ();
			
			// Calculate income
			$lastReload = $this->data['lastResRefresh'];
			$this->lastReload = $lastReload;
			
			if ($lastReload == 0)
			{
				$lastReload = NOW;
			}
			
			if ($this->data['lastResRefresh'] < NOW)
			{
				// Check if this user isn't on holiday
				$owner = $this->objMember->getOwner ();
			
				if (!$owner->inVacationMode ())
				{
					$income = $this->getIncomeOverTime ($lastReload, NOW);
			
					// Get capacity
					$capacity = $this->getCapacity ();
			
					$totalSum = 0;
			
					$resShortage = array ();
					foreach ($income as $k => $v)
					{				
						// Capacity
						if ( ($v + $this->data[$k]) > $capacity[$k])
						{
							// Here's where it's going wrong... I think
							$checked = $capacity[$k] - $this->data[$k];
							$v = max (0, $checked);
						}
						
						// Add it to the som
						$totalSum += abs ($v);

						$this->data[$k] = $this->data[$k] + $v;
						if ($this->data[$k] < 0)
						{
							$resShortage[$k] = abs ($this->data[$k]);
							//$this->data[$k] = 0;
						}
					}

					if (count ($resShortage) > 0 && isset ($this->shortagefactor))
					{
						$this->removeUnitsFromFamine ($resShortage);
					}
			
					// Only update if bigger then zero.
					if (($this->lastReload + 60 < NOW && $totalSum > 0) || $this->data['lastResRefresh'] == 0)
					{
						// Update
						if ($db->update
						(
							'villages',
							array
							(
								'gold' => $this->data['gold'],
								'grain' => $this->data['grain'],
								'wood' => $this->data['wood'],
								'stone' => $this->data['stone'],
								'iron' => $this->data['iron'],
								'gems' => $this->data['gems'],
								'lastResRefresh' => NOW
							),
							"vid = {$this->objMember->getId ()} AND lastResRefresh = {$this->lastReload}"
						) == 1)
						{
							$this->lastReload = NOW;
						}
						
						//echo $db->getLatestQuery () . "\n\n";
					}
				}
			}
			
			// Make sure this doesn't happen again (in this script)
			$this->resUpdated = true;
			
			$this->processTransfers ();
		}
		
		return array
		(
			'gold'	=> floor (max (0, $this->data['gold'])),
			'grain'	=> floor (max (0, $this->data['grain'])),
			'wood'	=> floor (max (0, $this->data['wood'])),
			'stone'	=> floor (max (0, $this->data['stone'])),
			'iron'	=> floor (max (0, $this->data['iron'])),
			'gems'	=> floor (max (0, $this->data['gems']))
		);
	}
	
	/**
	*	Kill units from famine.
	*	@param $shortageFactor: the percentage of "unfed" troops.
	*/
	public function removeUnitsFromFamine ($resShortage)
	{
		// Defenatly still got a little problem here.
		// $resShortage contains an array with the resources < 0
		$chk = array ('gold', 'grain', 'wood', 'stone', 'iron', 'gems');
		$sum = 0;
		
		foreach ($resShortage as $v)
		{
			$sum += $v;
		}
		
		$reslimit = 25 * GAME_SPEED_RESOURCES;
		
		if ($sum > $reslimit)
		{		
			// First send the supporting units back
			/*
			if ($this->objMember->withdrawAllUnits ())
			{
				// If there were supporting units, don't do anything in this round.
				return;
			}
			*/
			
			// Send all supporting units home
			if ($this->objMember->sendSupportingUnitsHome ())
			{
				// If there were supporting units, don't do anything in this round.
				return;
			}
			
			$died = array ();
			
			// Now load squads
			foreach ($this->objMember->getSquads (false, true, true) as $squad)
			{
				$units = $squad->getUnits ();
			
				// Kill a bunch of units
				foreach ($units as $v)
				{
					$this->killUnits ($v, $v->getAmount (), $died);
				}
			}
			
			// Let's see if there are any units that are not in a squad
			foreach ($this->objMember->getMyUnits () as $v)
			{
				if ($v->getSquadlessAmount () > 0)
				{
					$this->killUnits ($v, $v->getSquadlessAmount (), $died);
				}
			}
			
			foreach ($died as $v)
			{
				// Notify the village
				$objLogs = Dolumar_Players_Logs::__getInstance ();
				$objLogs->addTroopDiedOfHunger ($this->objMember, $v['unit'], $v['died']);
			}
		
			// Reset all resources to 0
			foreach ($chk as $k)
			{
				if (isset ($resShortage[$k]))
				{
					$this->data[$k] = 0;
				}
			}
		}		
	}
	
	private function killUnits (Dolumar_Units_Unit $v, $amount, &$died)
	{
		$sumcons = 0;
		foreach ($v->getConsumption () as $vv)
		{
			$sumcons += $vv;
		}
	
		// Kill half the amount that is required to even out the consumption
		$tokill = ceil (($amount * (1 - min (1, $this->shortagefactor))) * 0.5);
	
		if ($tokill > 0)
		{
			$uid = $v->getId ();
		
			if (!isset ($died[$uid]))
			{
				$died[$uid] = array ('unit' => $v, 'died' => 0);
			}
			
			$died[$uid]['died'] += $tokill;
		
			$this->objMember->removeUnits ($v, $tokill);
		}
	}
	
	private function getIncomeOverTime ($since = NOW, $now = NOW)
	{
		$income = $this->getIncome ($since, $now);
		$duration = $now - $since;
		
		$out = array ();
		foreach ($income as $k => $v)
		{
			$out[$k] = ( $v / (60 * 60) ) * $duration ;
		}
		return $out;
	}
	
	/*
		Get (average) hourly income between $since and $now
	*/
	public function getIncome ($since = NOW, $now = NOW)
	{	
		$profiler = Neuron_Profiler_Profiler::getInstance ();
		$profiler->start ('Calculating income');
	
		$this->shortagefactor = 1;
	
		$output = $this->getBrutoIncome ($since, $now);

		// Now let's withdraw the unit's resources
		$unitcosts = $this->getUnitConsumption ($now);
		foreach ($unitcosts as $k2 => $v2)
		{
			// Negative income?
			if ($output[$k2] < $v2)
			{
				$tmpfactor = $output[$k2] / $v2;
				
				// If the shortage factor is smaller than
				// the current shortage factor, take the new one.
				if ($tmpfactor < $this->shortagefactor)
				{
					$this->shortagefactor = $tmpfactor;
				}
			}
		
			$output[$k2] -= ceil ($v2);
		}
		
		$profiler->stop ();
		
		return $output;
	}
	
	public function getBrutoIncome ($since = NOW, $now = NOW)
	{
		$profiler = Neuron_Profiler_Profiler::getInstance ();
		$profiler->start ('Calculating bruto income');
	
		// Output
		$output = array ();
		
		// Put everything to 0
		$k = array ('gold', 'grain', 'wood', 'stone', 'iron', 'gems');
		foreach ($k as $v)
		{
			$output[$v] = 0;
		}
		
		if (!$this->objMember->moduleExists ('buildings'))
		{		
			throw new Neuron_Core_Error ('Module could not be loaded: buildings');
		}
		
		$obuildings = $this->objMember->buildings;
		
		if (!$obuildings instanceof Dolumar_Players_Village_Buildings)
		{
			throw new Neuron_Core_Error ('Module could not be loaded: buildings');
		}
		
		$buildings = $obuildings->getBuildings ();
		
		foreach ($buildings as $v)
		{
			if ($v->isFinished ())
			{
				$income = $v->getIncome ($since, $now);
				if ($income)
				{
					foreach ($income as $k => $v)
					{
						$output[$k] += $v;
					}
				}
			}
		}
		
		$profiler->stop ();
		
		return $output;
	}

	/*
		Calculate that complete amount of resources 
		consumed by units in this village.
		
		This amount is altered by the percentage of
		"over housing".
	*/	
	public function getUnitConsumption ($now = NOW)
	{
		$profiler = Neuron_Profiler_Profiler::getInstance ();
		$profiler->start ('Calculating unit consumption');
	
		$profiler->start ('Loading local consumption');
		$units = $this->objMember->getConsumingUnits ($now);
		
		$output = array ();
		
		// Count the amount of spots taken by these troops.
		$troops = 0;
		
		foreach ($units as $v)
		{
			$kosten = $v->getCurrentConsumption ($this->objMember);
			foreach ($kosten as $k2 => $v2)
			{
				if (!isset ($output[$k2]))
				{
					$output[$k2] = 0;
				}
			
				$output[$k2] += ceil ($v2);
			}
			
			$troops += $v->getCurrentSize ();
		}
		
		$profiler->stop ();
		
		$profiler->start ('Calculating supporting unit consumption');
		
		// Get squads of someone else
		$squads = $this->objMember->getSupportingSquads ();
		
		foreach ($squads as $squad)
		{
			foreach ($squad->getUnits () as $unit)
			{			
				$kosten = $unit->getCurrentConsumption ($this->objMember);
				
				foreach ($kosten as $k2 => $v2)
				{
					if (!isset ($output[$k2]))
					{
						$output[$k2] = 0;
					}
			
					$output[$k2] += ceil ($v2);
				}
				
				$troops += $unit->getCurrentSize ();
			}
		}
		
		$profiler->stop ();
		
		// Overhousing alert!
		// For every unit that has no room to live,
		// increase the hourly consumption
		/*
		$capacity = $this->objMember->getOverallUnitCapacity ();
		
		if ($capacity < 1)
		{
			$capacity = 1;
		}
		
		$penalty = $troops / $capacity;
		if ($penalty > 2)
		{
			$penalty = 2;
		}
		
		if ($penalty > 1)
		{
			foreach ($output as $k => $v)
			{
				$output[$k] *= $penalty;
			}
		}
		*/
		
		$profiler->stop ();
		
		return $output;
	}

	public function getCapacity ()
	{
		$profiler = Neuron_Profiler_Profiler::getInstance ();
		$profiler->start ('Calculating resource capacity');
	
		// Output
		$o = array ();
		
		// Put everything to 0
		$k = array ('gold', 'grain', 'wood', 'stone', 'iron', 'gems');
		foreach ($k as $v)
		{
			$o[$v] = 0;
		}
		
		$buildings = $this->objMember->buildings->getBuildings ();
		
		foreach ($buildings as $v)
		{
			$capacity = $v->getCapacity ();
			
			if ($capacity)
			{
				foreach ($capacity as $k => $v)
				{
					$o[$k] += $v;
				}
			}
		}
		
		$profiler->stop ();
		
		return $o;
	}

	public function getCapacityStatus ()
	{
		$o = array ();
		$res = $this->getResources ();
		$capacity = $this->getCapacity ();
		
		foreach ($res as $k => $v)
		{
			$o[$k] = max (0, min (100, floor ( ($v / $capacity[$k]) * 100 )));
		}
		
		return $o;
	}
	
	private function checkResources ($gold, $wood = null, $stone = null, $iron = null, $grain = null, $gems = null)
	{
		$res = $this->getResources ();
		$okay = true;
		
		$costs = array ();
		
		if (!is_array ($gold))
		{
			$dats = array ('gold', 'wood', 'stone', 'iron', 'grain', 'gems');
			$out = array ();
			
			foreach ($dats as $v)
			{
				$out[$v] = $$v;
			}
			
			$gold = $out;
		}
		
		foreach ($gold as $k => $v)
		{
			if (!isset ($res[$k]) || $res[$k] < $v)
			{
				return false;
			}
		}
		
		return true;
	}
	
	/*
		Resources and runes handlers
	*/
	private function _takeResources ( $gold, $wood, $stone, $iron, $grain, $gems )
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$profiler = Neuron_Profiler_Profiler::getInstance ();
		
		$profiler->start ('Taking resources away: '.$gold.' '.$wood.' '.$stone.' '.$iron.' '.$grain.' '.$gems);

		$this->loadData ();
		
		// Make sure this doesn't go over the capacity
		$capacity = $this->getCapacity ();
		$resources = $this->getResources ();
		
		// Build a WHERE clause according to "the positives"
		$where = "(vid = {$this->objMember->getId ()} AND lastResRefresh = {$this->lastReload})";
		
		//echo "Taking resources:\n";
		
		$a = array ('gold', 'wood', 'stone', 'iron', 'grain', 'gems');
		foreach ($a as $v)
		{
			//echo "- " . $v . " = " . $$v . " CAP " . $capacity[$v] . " ";
			//echo " CUR " . $resources[$v] . " ";
		
			// If $v is negative (= ADDING!) AND it's bigger then our currenct capacity
			if ( $$v < 0 && (abs ($$v) + $resources[$v]) > $capacity[$v] )
			{
				$$v = 0 - (max (0, $capacity[$v] - $resources[$v]));
				$profiler->start ('Capacity overflow for '.$v.', taking '.$$v.' instead');
				$profiler->stop ();
			}
			
			// Else if $v is posive and thus a withdrawal
			elseif ($$v > 0)
			{
				$where .= " AND ".$v." >= ".$$v;
			}
			
			//echo "CALC " . $$v . "\n";
		}
		
		// Show what we got:
		$data = array ($gold, $wood, $stone, $iron, $grain, $gems);
		
		if ($gold == 0 && $wood == 0 && $stone == 0 && $iron == 0 && $grain == 0 && $gems == 0)
		{
			$profiler->stop ();
			return true;
		}
		
		else
		{
			$profiler->start ('We are going to take: '.print_r ($data, true));
		
			// Update the database
			$a = $db->update
			(
				'villages',
				array
				(
					'gold'	=> $gold > 0 	? '--'.$gold 	: '++'.abs ($gold),
					'wood'	=> $wood > 0 	? '--'.$wood 	: '++'.abs ($wood),
					'stone'	=> $stone > 0 	? '--'.$stone 	: '++'.abs ($stone),
					'iron'	=> $iron > 0 	? '--'.$iron 	: '++'.abs ($iron),
					'grain'	=> $grain > 0 	? '--'.$grain 	: '++'.abs ($grain),
					'gems'	=> $gems > 0 	? '--'.$gems 	: '++'.abs ($gems)
				),
				$where
			) == 1;
			
			//customMail ('daedelson@gmail.com', 'res debug', $db->getLatestQuery ());
			
			$profiler->stop ();
			
			// Cached resources
			if ($a) 
			{
				$this->data['gold'] -= $gold;
				$this->data['wood'] -= $wood;
				$this->data['stone'] -= $stone;
				$this->data['iron'] -= $iron;
				$this->data['grain'] -= $grain;
				$this->data['gems'] -= $gems;
			}
			
			$profiler->stop ();
			return $a;
		}
	}

	private function _giveResources ($gold, $wood, $stone, $iron, $grain, $gems)
	{
		return $this->_takeResources (-$gold, -$wood, -$stone, -$iron, -$grain, -$gems);
	}

	public function giveResourcesAndRunes ($array)
	{	
		$gold = 0; $wood = 0; $stone = 0; $iron = 0; $grain = 0; $gems = 0; $runeId = false; $runeAmount = false;
		foreach ($array as $k => $v)
		{
			$$k = $v;
		}
		$chk = $this->_giveResources ($gold, $wood, $stone, $iron, $grain, $gems);

		if ($runeId && $runeAmount)
		{
			$chk = $chk && $this->giveRuneBack ($runeId, $runeAmount);
		}
		
		return $chk;
	}
	
	/*
		Clean functions for clean use
	*/
	public function takeResources ($resources = array ())
	{
		return $this->takeResourcesAndRunes ($resources);
	}
	
	public function takeRunes ($runes = array ())
	{
		$output = array ();
		foreach ($runes as $k => $v)
		{
			$output[] = array
			(
				'runeId' => $k,
				'runeAmount' => intval ($v)
			);
			
			if (!$this->checkRune ($k, intval ($v)))
			{
				return false;
			}
		}
		
		// Second run: do the actual stuff
		foreach ($output as $v)
		{
			if (!$this->takeResourcesAndRunes ($v))
			{
				return false;
			}
		}
		
		return true;
	}
	
	public function giveResources ($resources = array (), $delay = 0, Dolumar_Players_Village $from = null)
	{
		if ($delay > 0 && $from != null)
		{
			return $this->addDelayedTransfer ($resources, 'RESOURCE', $delay, $from);
		}
		else
		{
			// Give it straight away
			// Invert all resources
			$out = array ();
			foreach ($resources as $k => $v)
			{
				$out[$k] = 0 - intval ($v);
			}
			
			return $this->takeResources ($out);
		}
	}
	
	public function giveRunes ($runes, $delay = 0, Dolumar_Players_Village $from = null)
	{
		if ($delay > 0 && $from != null)
		{
			return $this->addDelayedTransfer ($runes, 'RUNE', $delay, $from);
		}
		else
		{
			$out = array ();
			foreach ($runes as $k => $v)
			{
				$out[$k] = 0 - intval ($v);
			}
			
			return $this->takeRunes ($out);
		}
	}
	
	/*
	 	A delayed transfer is, you guessed it right, a delayed transfer!
	 	Resources, runes or equipment will only be added after $delay
	 	time has passed. ($delay in seconds)
	 	$type must be RESOURCE, RUNE or EQUIPMENT
	 	$items must be an assoc array
	*/
	public function addDelayedTransfer ($items, $type, $delay, Dolumar_Players_Village $from)
	{
		// Delay the transfer
			$db = Neuron_DB_Database::getInstance ();
			
			$transfer_id = $db->query
			("
				INSERT INTO
					villages_transfers
				SET
					from_vid = {$from->getId()},
					to_vid = {$this->objMember->getId ()},
					t_date_sent = FROM_UNIXTIME(".NOW."),
					t_date_received = FROM_UNIXTIME(".(NOW + $delay)."),
					t_isReceived = '0'
			");
			
			// add the items themselves
			foreach ($items as $k => $v)
			{
				if ($v > 0)
				{
					$db->query
					("
						INSERT INTO
							villages_transfers_items
						SET
							t_id = {$transfer_id},
							ti_type = '{$type}',
							ti_key = '{$db->escape($k)}',
							ti_amount = '{$db->escape ($v)}'
					");
				}
			}
			
			return true;
	}
	
	/*
		Trading functions
	*/
	public function transferResources ($target, $resources, $costs = array (), $transferduration = 0)
	{
		if ($costs === false)
		{
			$costs = array ();
		}
	
		if ($target->getId () == $this->objMember->getId ())
		{
			return false;
		}
	
		// Since it's a transfer, it must be > 0
		$out = array ();
		$cout = array ();
		
		$sum = 0;
		
		foreach ($costs as $k => $v)
		{
			if (!isset ($resources[$k]))
			{
				$resources[$k] = 0;
			}
		}
		
		foreach ($resources as $k => $v)
		{
			$out[$k] = abs ($v);
			$cout[$k] = abs ($v) + (isset ($costs[$k]) ? $costs[$k] : 0);
			
			$sum += abs ($v);
		}

		if ($sum > 0)
		{
			if ($this->takeResources ($cout))
			{
				if ($target->resources->giveResources ($out, $transferduration, $this->objMember))
				{
					$objLogs = Dolumar_Players_Logs::__getInstance ();
					$objLogs->addResourceTransferLog ($this->objMember, $target, $out, NOW + $transferduration);
					
					return true;
				}
			}
			
			elseif ($this->checkResources ($out))
			{
				$this->sError = 'cant_pay_costs';
			}
		}
		
		return false;
	}
	
	public function transferRunes ($target, $runes, $transferduration = 0)
	{
		if ($target->getId () == $this->objMember->getId ())
		{
			return false;
		}
	
		// Since it's a transfer, it must be > 0
		$out = array ();
		$sum = 0;
		foreach ($runes as $k => $v)
		{
			$out[$k] = abs ($v);
			$sum += abs ($v);
		}
	
		if ($sum > 0)
		{
			if ($this->takeRunes ($out))
			{
				if ($target->resources->giveRunes ($out, $transferduration, $this->objMember))
				{
					$objLogs = Dolumar_Players_Logs::__getInstance ();
					$objLogs->addRuneTransferLog ($this->objMember, $target, $out, NOW + $transferduration);
					
					return true;
				}
				
				else
				{
					$this->sError = 'cant_send_runes';
					return false;
				}
			}
			else
			{
				$this->sError = 'dont_have_runes';
			}
		}
		else
		{
			$this->sError = 'no_resources';
		}
		
		return false;
	}
	
	/**
	*	@prcTransfers: Should we also process the "unprocessed" transfers?
	*/
	public function getOngoingTransfers ($prcTransfers = true)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		if ($prcTransfers)
			$this->processTransfers ();
		
		$data = $db->query
		("
			SELECT
				*,
				UNIX_TIMESTAMP(t_date_sent) AS sentdate,
				UNIX_TIMESTAMP(t_date_received) AS receiveddate
			FROM
				villages_transfers t
			LEFT JOIN
				villages_transfers_items i USING(t_id)
			WHERE
				t.t_isReceived = '0' AND
				(t.from_vid = {$this->objMember->getId()} OR t.to_vid = {$this->objMember->getId()})
		");
		
		$toProcess = array ();
		
		$transfers = array ();
		foreach ($data as $v)
		{
			// Group on t_id
			if (!isset ($transfers[$v['t_id']]))
			{
				$from = Dolumar_Players_Village::getFromId ($v['from_vid']);
				$to = Dolumar_Players_Village::getFromId ($v['to_vid']);
				
				if ($from->equals ($this->objMember))
				{
					$type = 'outgoing';
					if (!isset ($toProcess[$to->getId ()]) && $v['receiveddate'] < NOW)
					{
						$toProcess[$to->getId ()] = $to;
						
						// Well, if we need to process this anyway,
						// there is no use in continueing to load stuff.
						continue;
					}
				}
				else
				{
					$type = 'incoming';
				}
				
				$transfers[$v['t_id']] = array
				(
					'from' => $from,
					'to' => $to,
					'type' => $type,
					'senddate' => $v['sentdate'],
					'receiveddate' => $v['receiveddate']
				);
			}
			
			// Now add 'em resources
			$restype = strtolower ($v['ti_type']);
			
			if (!isset ($transfers[$v['t_id']][$restype]))
			{
				$transfers[$v['t_id']][$restype] = array ();
			}
			
			$transfers[$v['t_id']][$restype][$v['ti_key']] = $v['ti_amount'];
		}
		
		// Now, if we still need to process some,
		// we'll have to go trough this again...
		if ($prcTransfers && count ($toProcess) > 0)
		{
			foreach ($toProcess as $v)
			{
				$v->resources->processTransfers ();
			}
			
			// beware of the loop.
			return $this->getOngoingTransfers (false);
		}
		
		return array_values ($transfers);
	}

	/*
		Dirty fucntions for dirty use.
	*/
	public function takeResourcesAndRunes ($array)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$gold = 0; $wood = 0; $stone = 0; $iron = 0; $grain = 0; $gems = 0; $runeId = false; $runeAmount = false;
		foreach ($array as $k => $v)
		{
			$$k = $v;
		}

		if
		(
			// Check the resources
			$this->checkResources ($gold, $wood, $stone, $iron, $grain, $gems)
			&& (
				empty ($runeId)
				|| $runeAmount == 0
				|| $this->checkRune ($runeId, $runeAmount)
			)

			// Take the resources
			&& $this->_takeResources ($gold, $wood, $stone, $iron, $grain, $gems)
			&&  (
				empty ($runeId)
				|| $runeAmount == 0
				|| $this->takeRune ($runeId, $runeAmount)
			)
		)
		{
			return true;
		}

		else
		{
			if (!(empty ($runeId)
				|| $runeAmount == 0
				|| $this->checkRune ($runeId, $runeAmount)
			))
			{
				$this->sError = 'noRunes';
			}
			elseif (!$this->checkResources ($gold, $wood, $stone, $iron, $grain, $gems))
			{
				$this->sError = 'noResources';
			}
		
			return false;
		}
	}
	
	public function getRunes ()
	{
		$this->loadRunes ();
		return $this->runes;
	}
	
	private function checkRune ($runeId, $amount = 1)
	{
		$runes = $this->getRunes ();
		if (!isset ($runes[$runeId]))
		{
			return false;
		}
		
		else 
		{
			return $runes[$runeId] >= $amount;
		}
	}
	
	private function takeRune ($runeId, $amount = 1)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$l = $db->update
		(
			'villages_runes',
			array
			(
				'usedRunes' => '++'.$amount
			),
			"vid = '".$this->objMember->getId()."' AND (amount - usedRunes) >= '$amount' AND runeId = '$runeId' "
		);
		
		if ($l == 1)
		{
			if (isset ($this->runes[$runeId]))
			{
				$this->runes[$runeId] -= $amount;
			}
			return true;
		}
		
		else
		{
			return false;
		}
	}

	public function removeRune ($runeId, $amount = 1, $alsoTake = false)
	{
		return $this->takeRune ($runeId, $amount);
	}

	public function giveRune ($runeId, $amount = 1)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$row = $db->update
		(
			'villages_runes',
			array
			(
				'amount' => '++'.$amount
			),
			"vid = '".$this->objMember->getId()."' AND runeId = '$runeId'"
		);

		if ($row == 0)
		{
			// Insert one new rune
			$db->insert
			(
				'villages_runes',
				array
				(
					'vid' => $this->objMember->getId(),
					'runeId' => $runeId,
					'amount' => $amount,
					'usedRunes' => 0
				)
			);
		}

		// Cache
		if (isset ($this->runes[$runeId]))
		{
			$this->runes[$runeId] += $amount;
		}

		else
		{
			$this->runes[$runeId] = $amount;
		}
	}

	private function giveRuneBack ($runeId, $amount = 1)
	{
		return $this->takeRune ($runeId, -$amount);
	}
	
	private function loadRunes ()
	{
		if (!isset ($this->isLoaded['runes']))
		{
			$this->isLoaded['runes'] = true;
			$db = Neuron_Core_Database::__getInstance ();
			
			$objLock = Neuron_Core_Lock::__getInstance ();

			// Add found runes
			$newRunes = $db->select
			(
				'villages_scouting',
				array ('runes', 'scoutId'),
				"vid = '".$this->objMember->getId ()."' AND finishDate < '".time()."' "
			);
			
			if (count ($newRunes) > 0)
			{
				// Now do this again with locks
				if ($objLock->setLock ('newrunes', $this->objMember->getId ()))
				{
					$newRunes = $db->select
					(
						'villages_scouting',
						array ('runes', 'scoutId'),
						"vid = '".$this->objMember->getId ()."' AND finishDate < '".time()."' "
					);
				
					// Let's process the scouting
					$objLogs = Dolumar_Players_Logs::__getInstance ();
					foreach ($newRunes as $v)
					{			
						$runes = explode ('|', $v['runes']);
						$aRunes = array ();
						foreach ($runes as $rune)
						{
							$am = explode (':', $rune);
							if (isset ($am[0]) && isset ($am[1]))
							{
								$this->giveRune ($am[0], $am[1]);
								$aRunes[$am[0]] = $am[1];
							}
						}

						// Remove from database
						$db->remove
						(
							'villages_scouting',
							"scoutId = '".$v['scoutId']."'"
						);
				
						$objLogs->addScoutDone ($this->objMember, $aRunes);
					}
					
					$objLock->releaseLock ('newrunes', $this->objMember->getId ());
				}
			}
			
			$runes = $db->select
			(
				'villages_runes',
				array ('runeId', 'amount', 'usedRunes'),
				"vid = '".$this->objMember->getId()."'"
			);
			
			if (count ($runes) == 0)
			{
				// Initiate runes
				foreach ($this->getInitialRunes () as $k => $v)
				{
					$db->insert
					(
						'villages_runes',
						array
						(
							'vid' => $this->objMember->getId(),
							'runeId' => $k,
							'amount' => $v
						)
					);
				}
				
				$runes = $db->select
				(
					'villages_runes',
					array ('runeId', 'amount', 'usedRunes'),
					"vid = '".$this->objMember->getId()."'"
				);
			}
			
			foreach ($runes as $v)
			{
				// NOT RELIABLE ANYMORE
				// $this->totalRunes[$v['runeId']] = $v['amount'];
				
				
				$this->runes[$v['runeId']] = ($v['amount'] - $v['usedRunes']);
			}
			
			$this->processTransfers ();
		}
	}

	// process transfers
	// (another query that will run every bloody second)
	private function processTransfers ()
	{
		if ($this->areTransfersProcessed)
			return true;
			
		$db = Neuron_DB_Database::getInstance ();
		
		$this->areTransfersProcessed = true;
		
		$transfers = $db->query
		("
			SELECT
				*
			FROM
				villages_transfers
			WHERE
				to_vid = {$this->objMember->getId ()} AND
				t_date_received < FROM_UNIXTIME(".NOW.") AND
				t_isReceived = '0'
		");
		
		if (count ($transfers) > 0)
		{
			$objLock = Neuron_Core_Lock::__getInstance ();
			
			if ($objLock->setLock ('prcTransfer', $this->objMember->getId ()))
			{
				try
				{
					// Reload the transfers (lock bestendig)
					$transfers = $db->query
					("
						SELECT
							*
						FROM
							villages_transfers
						LEFT JOIN
							villages_transfers_items USING(t_id)
						WHERE
							to_vid = {$this->objMember->getId ()} AND
							t_date_received < FROM_UNIXTIME(".NOW.") AND
							t_isReceived = '0'
					");
				
					$resources = array ();
					$runes = array ();
					$equipment = array ();
				
					$transactions = array ();
				
					foreach ($transfers as $v)
					{
						switch ($v['ti_type'])
						{
							case 'RESOURCE':
								if (isset ($resources[$v['ti_key']]))
								{
									$resources[$v['ti_key']] += $v['ti_amount'];
								}
								else
								{
									$resources[$v['ti_key']] = $v['ti_amount'];
								}
							break;
						
							case 'RUNE':
								if (isset ($runes[$v['ti_key']]))
								{
									$runes[$v['ti_key']] += $v['ti_amount'];
								}
								else
								{
									$runes[$v['ti_key']] = $v['ti_amount'];
								}
							break;
						
							case 'EQUIPMENT':
								if (isset ($equipment[$v['ti_key']]))
								{
									$equipment[$v['ti_key']] += $v['ti_amount'];
								}
								else
								{
									$equipment[$v['ti_key']] = $v['ti_amount'];
								}
							break;
						}
					
						$transactions[$v['t_id']] = true;
					}
				
					foreach (array_keys ($transactions) as $v)
					{
						// Now make sure we don't give these resources again
						$db->query
						("
							UPDATE
								villages_transfers
							SET
								t_isReceived = '1'
							WHERE
								t_id = {$v}
						");
					}
				}
				catch (Exception $error)
				{
					error_log ("Transfer error: " . $error->getMessage ());
				}
				
				$objLock->releaseLock ('prcTransfer', $this->objMember->getId ());
				
				$logcontainer = new Dolumar_Logable_GeneralContainer ();
				
				// Now let's add 'em resources
				if (count ($resources) > 0)
				{
					$logcontainer->add (new Dolumar_Logable_ResourceContainer ($resources));
					$this->giveResources ($resources);
				}
				
				if (count ($runes) > 0)
				{
					$logcontainer->add (new Dolumar_Logable_RuneContainer ($runes));
					$this->giveRunes ($runes);
				}
				
				if (count ($equipment) > 0)
					$this->giveEquipment ($equipment);
					
				// Now we just need to log it
				$objLogs = Dolumar_Players_Logs::__getInstance ();
				$objLogs->addCompleteTransferLog ($this->objMember, $logcontainer);
			}
			
			else
			{
				error_log ("Could not lock for transfers: " . $this->objMember->getId ());
			}
		}
	}
	
	public function giveEquipment ($equipments)
	{
		foreach ($equipments as $k => $v)
		{
			$objEquipment = Dolumar_Players_Equipment::getFromId ($k);
			$this->objMember->equipment->addEquipment ($objEquipment, $v);
		}
	}
	
	public function getUsedRunes ($incTownCenter = true)
	{
		$this->loadRunes ();
		
		if (!isset ($this->usedRunes))
		{
			$this->usedRunes = array ();
			
			// Now, let's look what the buildings have used.
			$buildings = $this->objMember->buildings->getBuildings ();
			foreach ($buildings as $v)
			{
				if ($incTownCenter || !($v instanceof Dolumar_Buildings_TownCenter))
				{
					$res = $v->getUsedRunes (true);
					foreach ($res as $k => $v)
					{
						if (!isset ($this->usedRunes[$k]))
						{
							$this->usedRunes[$k] = $v;
						}
						else
						{
							$this->usedRunes[$k] += $v;
						}
					}
				}
			}
		}
		
		return $this->usedRunes;
	}
	
	public function getTotalRunes ($incTownCenter = true)
	{
		$this->loadRunes ();
		
		// Make a collection of all used runes in this village.
		// Basically, all runes used to build buildings.
		$totalRunes = 0;
		
		// Let's start with the runes we actually
		// have, so the available runes:
		foreach ($this->runes as $v)
		{
			$totalRunes += $v;
		}
	
		foreach ($this->getUsedRunes ($incTownCenter) as $k => $v)
		{
			$totalRunes += $v;
		}
		
		return $totalRunes;
	}
	
	/*
		Return the following array:
		$out = array
		(
			'fire' => array
			(
				'available' => 5,
				'used' => 10
				'used_percentage' => 20
			)
		)
	*/
	public function getRuneSummary ()
	{
		$used = $this->getUsedRunes ();
		$runes = $this->getRunes ();
		
		$total = $this->getUsedRunes_amount ();
		
		if ($total == 0)
		{
			$total = 1;
		}
		
		$keys = array_merge
		(
			array_keys ($used),
			array_keys ($runes)
		);
		
		$out = array ();
		
		foreach ($keys as $k)
		{		
			$iUsed = isset ($used[$k]) ? $used[$k] : 0;
		
			$out[$k] = array
			(
				'available' => isset ($runes[$k]) ? $runes[$k] : 0,
				'used' => $iUsed,
				'used_percentage' => $iUsed / $total
			);
		}
		
		return $out;
	}

	public function getTotalRunes_amount ($incTownCenter = true)
	{
		return $this->getTotalRunes ($incTownCenter = true);
	}

	private function getAvailableRunes_amount ()
	{
		$this->loadRunes ();

		$i = 0;
		foreach ($this->runes as $v)
		{
			$i += $v;
		}
		return $i;
	}

	public function getUsedRunes_amount ($incTownCenter = true)
	{
		//return $this->getTotalRunes_amount () - $this->getAvailableRunes_amount ();
		$sum = 0;
		foreach ($this->getUsedRunes ($incTownCenter) as $v)
		{
			$sum += $v;
		}
		return $sum;
	}
	
	public function reloadRunes () 
	{
		$this->runes = null; 
		$this->usedRunes = null;
		unset ($this->isLoaded['runes']); 
	}


	public function fill ($resource)
	{
		$capacity = $this->getCapacity ();
		$resources = $this->getResources ();

		$toAdd = array ();

		$v = $resource;

		if (isset ($resources[$v]))
		{
			$toAdd[$v] = $capacity[$v] - $resources[$v];
			if ($toAdd[$v] < 0)
			{
				$toAdd[$v] = 0;	
			}
		}

		$this->giveResources ($toAdd);

		$objLogs = Dolumar_Players_Logs::__getInstance ();
		$objLogs->addPremiumResourcesBoughtLog ($this->objMember, $toAdd);
	}

	public function fillAll ()
	{
		$capacity = $this->getCapacity ();
		$resources = $this->getResources ();

		$toAdd = array ();

		foreach ($resources as $v => $n)
		{
			$toAdd[$v] = $capacity[$v] - $resources[$v];
			if ($toAdd[$v] < 0)
			{
				$toAdd[$v] = 0;	
			}
		}

		$this->giveResources ($toAdd);

		$objLogs = Dolumar_Players_Logs::__getInstance ();
		$objLogs->addPremiumResourcesBoughtLog ($this->objMember, $toAdd);
	}
	
	public function getError ()
	{
		return $this->sError;
	}
	
	public function __destruct ()
	{
		unset ($this->objMember);
		unset ($this->data);
	
		unset ($this->runes);
		unset ($this->resUpdated);
	}
}
?>
