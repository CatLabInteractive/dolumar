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

class Dolumar_Buildings_Market extends Dolumar_Buildings_Producing
{
	protected $RESOURCE = 'gold';
	private $aInput;
	
	private $sError = null;
	
	private $objTarget;
	
	const MAX_PERCENTAGE = 25;
	
	// Amount of transfers (premium & regular players)
	const TRANSPORTERS_BASE = 2;
	const TRANSPORTERS_BASE_PREMIUM = 3;
	
	const TRANSPORTERS_PER_LEVEL = 1;
	const TRANSPORTERS_PER_LEVEL_PREMIUM = 1;
	
	const RUNES_PER_TRANSPORTER = 2;
	const RESOURCES_PER_TRANSPORTER = 10000;
	const EQUIPMENT_PER_TRANSPORTER = 100;

	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		$this->addRequiresBuilding (2);
	}	
	
	public function getCustomContent ($input)
	{
		$text = Neuron_Core_Text::__getInstance ();
		$this->aInput = $input;
		
		$sPage = isset ($this->aInput['action']) ? $this->aInput['action'] : null;
		
		if ($sPage == 'donate')
		{
			$this->hideGeneralOptions ();
			return $this->getTradeContent ();
		}
		
		$page = new Neuron_Core_Template ();
		
		// Add the ongoing transfers
		$transfers = $this->getOngoingTransfers ();
		foreach ($transfers as $v)
		{
			if (isset ($v['resource']))
				$type = 'resources';
			else if (isset ($v['rune']))
				$type = 'runes';
			else if (isset ($v['equipment']))
				$type = 'equipment';
			else
				$type = 'unknown';
			
			$page->addListValue
			(
				'transfers',
				array
				(
					'countdown' => Neuron_Core_Tools::getCountdown ($v['receiveddate']),
					'type' => $type,
					'from' => $v['from']->getDisplayName (),
					'to' => $v['to']->getDisplayName (),
					'direction' => $v['type']
				)
			);
		}
		
		//return print_r ($transfers, true);
		
		$page->set ('overview', parent::getCustomContent ($input));
		return $page->parse ('buildings/market_overview.phpt');;
	}
	
	public function getOngoingTransfers ()
	{
		return $this->getVillage()->resources->getOngoingTransfers();
	}
	
	public function countOutgoingTransfers ()
	{
		$transfers = $this->getVillage()->resources->getOngoingTransfers();
		$i = 0;
		foreach ($transfers as $v)
		{
			if ($v['from']->equals ($this->getVillage ()))
			{
				$i ++;
			}
		}
		return $i;
	}
	
	public function countMaximumTransfers ($isPremium = false)
	{
		$level = $this->getVillage()->buildings->getBuildingLevelSum ($this);
		
		if ($isPremium)
			$amount = self::TRANSPORTERS_BASE_PREMIUM + (self::TRANSPORTERS_PER_LEVEL_PREMIUM * $level);
		else
			$amount = self::TRANSPORTERS_BASE + (self::TRANSPORTERS_PER_LEVEL * $level);
		
		return floor ($amount);
	}
	
	public function countTransfersLeft ($isPremium = false)
	{
		return $this->countMaximumTransfers ($isPremium) - $this->countOutgoingTransfers();
	}
	
	/*
		Return content for trading
	*/
	private function getTradeContent ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		
		// Can we do it already?
		if ($this->countMaximumTransfers () <= $this->countOutgoingTransfers ())
		{
			return $this->getMarketBusy ();
		}
	
		if (isset ($this->aInput['target']))
		{
			$objVillage = Dolumar_Players_Village::getFromId ($this->aInput['target']);
			if ($objVillage)
			{
				$this->objTarget = $objVillage;
			
				if ($this->objTarget->equals ($this->getVillage ()))
				{
					$msgs[] = array
					(
						$text->get ('error_ownvillage', 'market', 'buildings'),
						false
					);
					
					return $this->getChooseTarget ($msgs);
				}
			
				/*
				if (!$this->isValidTarget ($objVillage))
				{
					return $this->getChooseTarget 
					(
						array
						(
							array 
							(
								Neuron_Core_Tools::putIntoText
								(
									$text->get ($this->sError, 'market', 'buildings', $this->sError), 
									array
									(
										'percentage' => self::MAX_PERCENTAGE
									)
								),
								false
							)
						)
					);
				}
				*/
			
				// Check for selected resources
				$resources = $this->getResourcesFromInput ();
				$runes = $this->getRunesFromInput ();
				$equipment = $this->getEquipmentFromInput ();
				
				$confirmed = isset ($this->aInput['confirmed']);
				
				if ($this->isFilled ($resources) || $this->isFilled ($runes) || count ($equipment) > 0)
				{
					if ($confirmed)
					{
						return $this->getDonateResources ($objVillage, $resources, $runes, $equipment);
					}
					else
					{
						return $this->getConfirmDonation ($objVillage, $resources, $runes, $equipment);
					}
				}
				else
				{
					return $this->getChooseAmount ($objVillage);
				}
			}
		}

		return $this->getChooseTarget ();
	}
	
	private function getMarketBusy ()
	{
		$page = new Neuron_Core_Template ();
			
		$page->set ('outgoingtransfers', $this->countOutgoingTransfers ());
		$page->set ('maxtransfers', $this->countMaximumTransfers ());
		
		$page->set ('maxtransfers_premium', $this->countMaximumTransfers (true));
		$page->set ('ispremium', $this->isPremium ($this->getVillage ()));
			
		return $page->parse ('buildings/market_busy.phpt');
	}
	
	/*
		Return TRUE if this is a village.
	*/
	private function isValidTarget ($objVillage, $transfer = 'resources')
	{
		switch ($transfer)
		{
			case 'resources':
				return true;
			break;
			
			case 'equipment':
				$owner = $objVillage->getOwner ();
				if ($this->getVillage ()->getOwner ()->isClanmember ($owner))
				{
					return true;
				}
				else
				{
					$this->sError = 'clanmembers_only';
					return false;
				}
			break;
		}
	
		// Check the difference in size
		$difference = $objVillage->getNetworth () / max ($this->getVillage()->getNetworth(),1);
		
		$percentage = 1 + (self::MAX_PERCENTAGE / 100);

		if ($difference > $percentage)
		{
			$this->sError = 'difference_too_big';
			return false;
		}
		
		return true;
	}
	
	// Split in the correct amount of transactions
	
	/*
	const RUNES_PER_TRANSPORTER = 2;
	const RESOURCES_PER_TRANSPORTER = 10000;
	const EQUIPMENT_PER_TRANSPORTER = 100;
	*/
	
	public static function splitInTransactions ($resources, $limit)
	{
		// Limit cannot be null.
		if ($limit == 0)
			return false;
		
		/*
		switch ($type)
		{
			case "resources":
				$limit = self::RESOURCES_PER_TRANSPORTER;
			break;
			
			case "equipment":
				$limit = self::EQUIPMENT_PER_TRANSPORTER;
			break;
			
			case "runes":
				$limit = self::RUNES_PER_TRANSPORTER;
			break;
			
			default:
				return false;
			break;
		}
		*/
				
		// Initial values
		$out = array ();
		$current = array ();
		$sum = 0;
		
		// Loop trough the items
		foreach ($resources as $k => $v)
		{
			// Deplete the values
			while ($v > 0)
			{
				$rest = $limit - $sum;
				
				// We can put it in this transaction
				if ($rest > $v)
				{
					$current[$k] = $v;
					$sum += $v;
					$v = 0;
				}
				
				// We have to split this over multiple transactions
				else
				{
					$current[$k] = $rest;
					$v -= $rest;
					
					// Reset current
					$out[] = $current;
					$current = array ();
					$sum = 0;
				}
			}
		}
		
		// Don't add an empty array please
		if (count ($current) > 0)
			$out[] = $current;
			
		return $out;
	}
	
	/*
		Check if we can give premium bonus.
	*/
	private function isPremium ($target)
	{
		$player = $target->getOwner ();
		$myself = $this->getVillage ()->getOwner ();
	
		return $player->isPremium () && $myself->isPremium ();
	}
	
	private function calculateCosts ($target, $resources, $runes, $isPremium = false)
	{
		$sum = 0;
		
		foreach ($resources as $k => $v)
		{
			switch ($k)
			{
				case 'gems':
					$sum += ($v * 10);
				break;
			
				default:
					$sum += $v;
				break;
			}
		}
		
		// Check if we are clan members.
		$player = $target->getOwner ();
		$myself = $this->getVillage ()->getOwner ();
		
		$percentage = 15;
		
		foreach ($myself->getClans () as $v)
		{
			if ($v->isMember ($player))
			{
				$percentage = 10;
			}
		}
		
		// Check if this is a premium user		
		if ($isPremium)
		{
			$percentage = $percentage / 2;
		}
		
		
		$price = ceil ($sum / 100) * $percentage;
		
		if ($price <= 0)
		{
			return false;
		}
		
		$price = ceil ($price);
		
		return array
		(
			'gold' => $price
		);
	}
	
	private function getConfirmDonation ($target, $resources, $runes, $equipment)
	{
		$page = new Neuron_Core_Template ();
		
		$input = $this->aInput;
		$input['confirmed'] = 1;
		
		$page->set ('input', json_encode ($input));
		
		$premium = $this->isPremium ($target);
		
		$costs = $this->calculateCosts ($target, $resources, $runes, $premium);
		
		// Count transactions
		$t1 = $this->splitInTransactions ($resources, self::RESOURCES_PER_TRANSPORTER);
		$t2 = $this->splitInTransactions ($runes, self::RUNES_PER_TRANSPORTER);
		$t3 = $equipment;
		
		$max_transactions = $this->countMaximumTransfers ($premium);
		$total_transactions = count ($t1) + count ($t2) + count ($t3);
		
		// Get duration
		$duration = $this->getTransferDuration ($target);
		
		if (!$premium)
		{
			$premiumcost = $this->calculateCosts ($target, $resources, $runes, true);
			
			if ($premiumcost)
			{
				$page->set ('premiumcost', $this->resourceToText ($premiumcost));
			
				// Check who's fault it is.
				if ($this->getVillage ()->getOwner ()->isPremium ())
				{
					$page->set ('premiumerror', 'nothim');
				}
			
				elseif ($target->getOwner ()->isPremium ())
				{
					$page->set ('premiumerror', 'notyou');
				}
			
				else
				{
					$page->set ('premiumerror', 'notboth');
				}
			}
		}
		
		if ($costs)
		{
			$page->set ('costs', $this->resourceToText ($costs));
		}
		
		$page->set ('target', Neuron_Core_Tools::output_varchar ($target->getName ()));
		$page->set ('targetid', $target->getId ());
		
		$page->set ('resources', $this->resourceToText ($resources));
		
		$crunes = 0;
		foreach ($runes as $k => $v)
		{
			$crunes += $v;
		}
		
		$page->set ('hasResources', $this->isFilled ($resources));
		$page->set ('hasRunes', $this->isFilled ($runes));
		$page->set ('hasEquipment', count ($equipment) > 0);
		
		$page->set ('transactions', $total_transactions);
		$page->set ('maxtransactions', $max_transactions);
		$page->set ('duration', Neuron_Core_Tools::getDuration ($duration));
		
		$page->set ('canConfirm', $total_transactions <= $this->countTransfersLeft ($premium));
		
		$page->set ('runes', $crunes);
		$page->set ('tab', isset ($input['tab']) ? $input['tab'] : 'resources');
		
		foreach ($equipment as $v)
		{
			$page->addListValue
			(
				'equipment',
				array
				(
					'id' => $v['equipment']->getId (),
					'name' => $v['equipment']->getName ($v['amount'] > 1),
					'amount' => $v['amount']
				)
			);
		}
		
		return $page->parse ('buildings/market_confirm.phpt');
	}
	
	/*
		Do the actual donating.
	*/
	private function getDonateResources ($target, $resources, $runes, $equipment)
	{
		$text = Neuron_Core_Text::__getInstance ();
		$msgs = array ();
		
		$premium = $this->isPremium ($target);
		
		// Count transactions
		$t1 = $this->splitInTransactions ($resources, self::RESOURCES_PER_TRANSPORTER);
		$t2 = $this->splitInTransactions ($runes, self::RUNES_PER_TRANSPORTER);
		//$t3 = $this->splitInTransactions ($equipment, self::EQUIPMENT_PER_TRANSPORTER);
		$t3 = $equipment;
		
		$total_transactions = count ($t1) + count ($t2) + count ($t3);
		$max_transactions = $this->countTransfersLeft ($premium);
		
		if ($total_transactions > $max_transactions)
		{
			// No error, just show the confirm button again.
			return $this->getConfirmDonation ($target, $resources, $runes, $equipment);
		}
		
		// Calculate the price
		$costs = $this->calculateCosts ($target, $resources, $runes, $premium);
		
		$amount = 0;
		foreach ($resources as $v)
		{
			$amount += $v;
		}
		
		$duration = $this->getTransferDuration ($target);
		
		/*
			done_res = "You have sent resource to @@target."
			done_runes = "You have sent runes to @@target."
		*/
		
		// RESOURCES
		foreach ($t1 as $resources)
		{
			if ($this->getVillage()->resources->transferResources ($target, $resources, $costs, $duration))
			{
				$msgs[] = array
				(
					Neuron_Core_Tools::putIntoText
					(
						$text->get ('done_res', 'market', 'buildings'),
						array
						(
							'target' => Neuron_Core_Tools::output_varchar ($target->getName ()),
							'cost' => $this->resourceToText ($costs)
						)
					),
					true
				);
			}
			elseif ($amount > 0)
			{
				$msgs[] = array
				(
					Neuron_Core_Tools::putIntoText
					(
						$text->get ($this->getVillage()->resources->getError (), 'market', 'buildings'),
						array
						(
							'target' => Neuron_Core_Tools::output_varchar ($target->getName ()),
							'cost' => $this->resourceToText ($costs)
						)
					),
					false
				);
			}
			
			$costs = array ();
		}
	
		// RUNES
		foreach ($t2 as $runes)
		{
			if ($this->getVillage()->resources->transferRunes ($target, $runes, $duration))
			{
				$msgs[] = array
				(
					Neuron_Core_Tools::putIntoText
					(
						$text->get ('done_runes', 'market', 'buildings'),
						array
						(
							'target' => Neuron_Core_Tools::output_varchar ($target->getName ()),
							'cost' => $this->resourceToText ($costs)
						)
					),
					true
				);			
			}
			elseif (count ($runes) > 0)
			{
				$msgs[] = array
				(
					Neuron_Core_Tools::putIntoText
					(
						$text->get ($this->getVillage()->resources->getError (), 'market', 'buildings'),
						array
						(
							'target' => Neuron_Core_Tools::output_varchar ($target->getName ()),
							'cost' => $this->resourceToText ($costs)
						)
					),
					false
				);
			}
		}
		
		// Time for the equipment stuff
		$equips = 0;
		
		foreach ($equipment as $v)
		{
			if ($this->getVillage ()->equipment->transferEquipment ($target, $v['equipment'], $v['amount'], $duration))
			{
				$equips += $v['amount'];
			}
			else
			{
				$msgs[] = array
				(
					Neuron_Core_Tools::putIntoText
					(
						$text->get ($this->getVillage()->resources->getError (), 'market', 'buildings'),
						array
						(
							'target' => Neuron_Core_Tools::output_varchar ($target->getName ()),
							'cost' => $this->resourceToText ($costs)
						)
					),
					false
				);
			}
		}
		
		if ($equips > 0)
		{
			$msgs[] = array
			(
				Neuron_Core_Tools::putIntoText
				(
					$text->get ('done_equipment' . ($equips > 1 ? '2' : '1'), 'market', 'buildings'),
					array
					(
						'target' => Neuron_Core_Tools::output_varchar ($target->getName ()),
						'cost' => $this->resourceToText ($costs),
						'items' => $equips
					)
				),
				true
			);
		}
	
		reloadEverything ();
		
		$this->aInput = array ('action' => 'donate');
		
		return $this->getChooseTarget ($msgs);
	}
	
	public function getTransferDuration (Dolumar_Players_Village $target)
	{
		//return 30;
	
		$distance = Dolumar_Map_Map::getDistanceBetweenVillages ($this->getVillage (), $target, true);
		
		// Speed for the "marketing managers"
		$speed = 15;
		
		//return (GAME_SPEED_RESOURCES * $distance * 60 * 10) / (GAME_SPEED_RESOURCES * $speed);
		return ($distance * 60 * 10) / (GAME_SPEED_RESOURCES * $speed);
		
		//return $duration;
		//return 10;
	}
	
	/*
		Return resources
	*/
	private function getResourcesFromInput ()
	{
		if (!$this->isValidTarget ($this->objTarget, 'resources'))
		{
			return array ();
		}
	
		$resources = array ();
		foreach ($this->getVillage()->resources->getResources() as $k => $v)
		{
			$resources[$k] = isset ($this->aInput['res_'.$k]) ? intval ($this->aInput['res_'.$k]) : 0;
		}
		return $resources;
	}
	
	/*
		Return runes
	*/
	private function getRunesFromInput ()
	{
		if (!$this->isValidTarget ($this->objTarget, 'runes'))
		{
			return array ();
		}
	
		$runes = array ();
		foreach ($this->getVillage()->resources->getRunes() as $k => $v)
		{
			$runes[$k] = isset ($this->aInput['run_'.$k]) ? intval ($this->aInput['run_'.$k]) : 0;
		}
		return $runes;
	}
	
	private function getEquipmentFromInput ()
	{
		if (!$this->isValidTarget ($this->objTarget, 'equipment'))
		{
			return array ();
		}
	
		$equipment = $this->getVillage ()->equipment->getEquipment ();
		
		$out = array ();
		
		foreach ($equipment as $types)
		{
			foreach ($types as $v)
			{
				$k = 'equipment_'.$v->getId ();
				
				if (isset ($this->aInput[$k]))
				{
					$amount = intval ($this->aInput[$k]);
					if ($amount > 0)
					{
						if ($amount > $v->getAvailableAmount ())
						{
							$amount = $v->getAvailableAmount ();
						}
					
						if ($amount > 0)
						{
							$out[] = array
							(
								'equipment' => $v,
								'amount' => $amount
							);
						}
					}
				}
			}
		}
		
		return $out;
	}
	
	/*
		Return true if at least one of the values of the array is > 0
	*/
	private function isFilled ($resources)
	{
		foreach ($resources as $v)
		{
			if ($v > 0)
			{
				return true;
			}
		}
		return false;
	}
	
	private function setTabUrl ($page, $tab, $target)
	{
		$text = Neuron_Core_Text::getInstance ();
	
		// Tabs
		$page->set
		(
			'tab_'.$tab,
			Neuron_URLBuilder::getInstance ()->getUpdateUrl 
			(
				'building', 
				$text->get ($tab, 'market-tabs', 'buildings'),
				array
				(
					'building' => $this->getId (),
					'tab' => $tab,
					'target' => $target->getId (),
					'action' => 'donate'
				)
			)
		);
	}
	
	/*
		Choose the amount of resources to donate.
	*/
	private function getChooseAmount ($target)
	{
		$page = new Neuron_Core_Template ();
		$text = Neuron_Core_Text::getInstance ();
		
		$tab = isset ($this->aInput['tab']) ? $this->aInput['tab'] : 'resources';
		switch ($tab)
		{
			case 'resources':
			case 'runes':
			case 'equipment':
			
			break;
			
			default:
				$tab = 'resources';
			break;
		}
		
		$this->setTabUrl ($page, 'resources', $target);
		$this->setTabUrl ($page, 'runes', $target);
		$this->setTabUrl ($page, 'equipment', $target);
		
		$page->set ('target', $target->getId ());
		$page->set ('tab', $tab);
		
		switch ($tab)
		{
			case 'resources':
				$res = $this->getVillage()->resources->getResources ();
				foreach ($res as $k => $v)
				{
					$page->addListValue
					(
						'resources',
						array
						(
							'name' => $k,
							'amount' => 0,
							'available' => $v
						)
					);
				}
			break;
			
			case 'runes':
				$runes = $this->getVillage()->resources->getRunes ();
				foreach ($runes as $k => $v)
				{
					$page->addListValue
					(
						'runes',
						array
						(
							'name' => $k,
							'amount' => 0,
							'available' => $v
						)
					);
				}
			break;
			
			case 'equipment':
				$equipment = $this->getVillage ()->equipment->getEquipment ();
				
				$output = array ();
				
				foreach ($equipment as $type => $vv)
				{
					$output[$type] = array 
					(
						'name' => $text->get ($type, 'types', 'equipment'),
						'equipment' => array ()
					);
				
					foreach ($vv as $v)
					{
						$output[$type]['equipment'][] = array
						(
							'id' => $v->getId (),
							'name' => $v->getName (true),
							'amount' => 0,
							'available' => $v->getAmount (),
							'formid' => 'equipment_'.$v->getId ()
						);
					}
				}
				
				$page->set ('equipment', $output);
			break;
		}
		
		$page->set ('canTrade', $this->isValidTarget ($target, $tab));
		
		$page->set
		(
			'tradeerror',
			Neuron_Core_Tools::putIntoText
			(
				$text->get ($this->sError, 'market', 'buildings', $this->sError), 
				array
				(
					'percentage' => self::MAX_PERCENTAGE
				)
			)
		);

		
		return $page->parse ('buildings/market.phpt');
	}
	
	/*
		Choose a target
		@param $messages: contains a list arrays with a message and a boolean.
	*/
	private function getChooseTarget ($messages = array ())
	{	
		$structure = new Neuron_Structure_ChooseTarget ($this->aInput, $this->getVillage (), false);
		
		$page = new Neuron_Core_Template ();
		
		$page->set ('messages', $messages);
		$page->set ('choosetarget', $structure->getHTML ());
		
		return $page->parse ('buildings/market_target.phpt');
	}
}
?>
