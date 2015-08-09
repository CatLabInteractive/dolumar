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

class Dolumar_Windows_Premiumshop extends Neuron_GameServer_Windows_Window
{
	const COST_RUNES = 75;
	const COST_MOVEVILLAGE = 750;
	const COST_MOVEBUILDING = 100;

	const COST_RESOURCES_ONE = 150;
	const COST_RESOURCES_ALL = 900;
	
	const FREE_MOVEBUILDING_DURATION = 3600;
	
	const MAX_BUY_RUNES = 10;
	
	const MIN_MOVEVILLAGE_INTERVAL_DAYS = 7;

	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize (500, 350);
		$this->setTitle ($text->get ('premiumshop', 'menu', 'main'));
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		$input = $this->getInputData ();
		
		$action = isset ($input['action']) ? $input['action'] : null;
		
		$player = Neuron_GameServer::getPlayer ();
		
		if (isset ($action) && (!$player || !$player->isPlaying ()))
		{
			$text = Neuron_Core_Text::getInstance ();
			return '<p class="false">'.$text->get ('noLogin', 'main', 'main').'</p>';
		}
		
		switch ($action)
		{
			case 'buyrunes':
				return $this->getBuyRunes ();
			break;
			
			case 'movevillage':
				return $this->getMoveVillage ();
			break;
			
			case 'movebuilding':
				return $this->getMoveBuilding ();
			break;

			case 'buyresources':
				return $this->getBuyResources ();
			break;
		
			default:
				return $this->getOverview ();
			break;
		}
	}
	
	private function getOverview ()
	{
		$page = new Neuron_Core_Template ();

		// Premium
		$player = Neuron_GameServer::getPlayer ();
		
		if ($player)
		{
			$credits = $player->getCredits ();
			$page->set ('premium', $credits);
			$page->set ('buy_url', htmlentities ($player->getCreditBuyUrl ()));
		}

		$page->set ('cost_runes', $this->convertCredits (self::COST_RUNES));
		$page->set ('cost_resources', $this->convertCredits (self::COST_RESOURCES_ONE));
		$page->set ('cost_movebuilding', $this->convertCredits (self::COST_MOVEBUILDING));
		$page->set ('cost_movevillage', $this->convertCredits (self::COST_MOVEVILLAGE));
		
		$page->set ('buyable', $this->getBuyableRunesLeft ());
		$page->set ('maximum', $this->getBuyableRunes ());
		
		$page->set ('bonusbuilding', false);
		
		return $page->parse ('dolumar/premium/premiumshop.phpt');
	}
	
	private function getBuyableRunes ()
	{
		//return 5;
		return self::MAX_BUY_RUNES * GAME_SPEED_RESOURCES;
	}
	
	private function getBuyableRunesLeft ()
	{
		$runes = $this->getBuyableRunes ();
		
		$player = Neuron_GameServer::getPlayer ();
		$logs = Dolumar_Players_Logs::getInstance ();
		
		$logs->clearFilters ();
		$logs->addShowOnly ('premium_runes_bought');
		
		//$maxdate = NOW - 60*60*24*30;
		$maxdate = mktime (0, 0, 0, date ('n', NOW), 1, date ('Y', NOW));
		
		$maximum = $this->getBuyableRunes ();
		
		$amount = 0;
		foreach ($logs->getLogs ($player->getVillages (), 0, $maximum, 'DESC') as $v)
		{
			if ($v['unixtime'] < $maxdate)
			{
				break;
			}
		
			if ($v['action'] == 'premium_runes_bought')
			{
				$runes = $v['data'][0];
			
				foreach ($runes->getLogArray () as $v)
				{
					$amount += $v;
				}
			}
		}
		
		return max (0, $maximum - $amount);
	}

	private function getBuyResources ()
	{
		$text = Neuron_Core_Text::getInstance ();
		$input = $this->getInputData ();

		$player = Neuron_GameServer::getPlayer ();

		$page = new Neuron_Core_Template ();

		$villages = $player->getVillages ();
		if (count ($villages) === 1)
		{
			$input['village'] = $villages[0]->getId ();
		}

		$village = $player->getMyVillage (isset ($input['village']) ? $input['village'] : false);
		if ($village)
		{
			$res = $village->resources->getResources ();

			$income = $village->resources->getIncome ();
			$capacity = $village->resources->getCapacity ();
			$consumption = $village->resources->getUnitConsumption ();
			$bruto = $village->resources->getBrutoIncome ();
			
			foreach ($res as $k => $v)
			{
				$data = array
				(
					'action' => 'buyresources',
					'village' => $village->getId (),
					'resource' => $k
				);

				$page->addListValue ('resources',
					array
					(
						ucfirst ($text->get ($k, 'resources', 'main')),
						$v,
						$capacity[$k],
						$income[$k],
						'resource' => $k,
						'bruto' => isset ($bruto[$k]) ? $bruto[$k] : 0,
						'consuming' => isset ($consumption[$k]) ? $consumption[$k] : 0,
						'fillup' => htmlentities ($player->getCreditUseUrl (self::COST_RESOURCES_ONE, $data, 'Fill resources')),
						'cost' => $this->convertCredits (self::COST_RESOURCES_ONE),
						'full' => $v >= $capacity[$k]
					)
				);
			}

			$data = array
			(
				'action' => 'buyresources',
				'village' => $village->getId (),
				'resource' => 'all'
			);

			$page->set ('costAll', $this->convertCredits (self::COST_RESOURCES_ALL));
			$page->set ('fillup_all', htmlentities ($player->getCreditUseUrl (self::COST_RESOURCES_ALL, $data, 'Fill all resources')));
			$page->set ('fillup_all_cost', $this->convertCredits (self::COST_RESOURCES_ALL));

			return $page->parse ('dolumar/premium/buyresources.phpt');
		}

		else
		{	
			foreach ($villages as $v)
			{
				$page->addListValue
				(
					'villages',
					array
					(
						'id' => $v->getId (),
						'name' => Neuron_Core_Tools::output_varchar ($v->getName ())
					)
				);
			}
			
			$page->set ('village', isset ($village) && $village ? $village->getId () : $player->getCurrentVillage ()->getId ());
			$page->set ('action', 'buyresources');
		
			return $page->parse ('dolumar/premium/selectvillage.phpt');
		}
	}
	
	private function getBuyRunes ()
	{
		$input = $this->getInputData ();
	
		$page = new Neuron_Core_Template ();
		
		$player = Neuron_GameServer::getPlayer ();
		$runes = array_keys ($player->getMainVillage ()->resources->getInitialRunes ());
		
		$villages = array ();
		foreach ($player->getVillages () as $v)
		{
			$villages[] = array
			(
				'name' => $v->getName (),
				'id' => $v->getId ()
			);
		}
		
		$page->set ('villages', $villages);
		
		// Fetch thze input
		$data = array ();
		
		$sum = 0;
		foreach ($runes as $v)
		{
			if (isset ($input[$v]))
			{
				$amount = intval ($input[$v]);
				if ($amount > 0)
				{
					$sum += $amount;
					$data[$v] = $amount;
				}
			}
		}
		
		$page->set ('runes', $runes);
		
		$runesleft = $this->getBuyableRunesLeft ();
		
		$page->set ('buyable', $runesleft);
		$page->set ('maximum', $this->getBuyableRunes ());
		
		if ($sum > 0 && $sum <= $runesleft)
		{
			// Fetch confirm link
			$page->set ('myrunes', $data);
		
			$data['village'] = isset ($input['village']) ? $input['village'] : null;
			$data['action'] = 'buyrunes';
			
			$credits = $this->convertCredits ($sum * self::COST_RUNES);
			
			$page->set ('credits', $credits);
			
			$page->set ('confirm_url', htmlentities ($player->getCreditUseUrl ($sum * self::COST_RUNES, $data, $sum . ' runes')));
			
			return $page->parse ('dolumar/premium/buyrunes_confirm.phpt');
		}
		else
		{
			if ($sum > $runesleft)
			{
				$page->set ('error', 'maxrunes');
			}
		
			return $page->parse ('dolumar/premium/buyrunes.phpt');
		}
	}
	
	private function isMoveFree ($village)
	{
		$date = $this->getFreeMoveEndDate ($village);		
		$bool = $date > NOW;
		
		return $bool;
	}
	
	private function getFreeMoveEndDate ($village)
	{
		// Fetch logs
		$objLogs = Dolumar_Players_Logs::getInstance ();
		
		$objLogs->clearFilters ();
		$objLogs->addShowOnly ('premium_movevillage');
		
		$logs = $objLogs->getLogs ($village, 0, 1, 'DESC');
		
		foreach ($logs as $v)
		{
			// One hour free!
			return $v['timestamp'] + self::FREE_MOVEBUILDING_DURATION;
		}
		
		return false;
	}
	
	private function getMoveBuilding ()
	{
		$page = new Neuron_Core_Template ();
		$input = $this->getInputData ();
		
		$player = Neuron_GameServer::getPlayer ();
		
		$creditcost = $this->convertCredits (self::COST_MOVEBUILDING);
		
		$village = $player->getMyVillage (isset ($input['village']) ? $input['village'] : false);
		if ($village)
		{
			// Select building
			$building = isset ($input['building']) ? $input['building'] : false;
			$building = $building ? $village->buildings->getBuilding ($building) : false;
			
			$page->set ('village', $village->getId ());
			
			if ($this->isMoveFree ($village))
			{
				$page->set ('freetime', Neuron_Core_Tools::getCountdown ($this->getFreeMoveEndDate ($village)));
			}
			
			if ($building && $building->isMoveable ())
			{
				$page->set ('buildingname', $building->getName (false));
				$page->set ('credits', $creditcost);
			
				$loc = array 
				(
					'x' => isset ($input['x']) ? floor ($input['x']) : null, 
					'y' => isset ($input['y']) ? floor ($input['y']) : null
				);
				
				if (isset ($loc['x']) && isset ($loc['y']))
				{
					// Let's check this location
					$chk = $building->checkBuildLocation ($village, $loc['x'], $loc['y']);
					
					if ($chk[0])
					{					
						if ($this->isMoveFree ($village))
						{
							// Move thze building
							$oldloc = $building->getLocation ();
						
							$building->setLocation ($loc['x'], $loc['y']);
							$page->set ('moved', true);
							
							$logs = Dolumar_Players_Logs::getInstance ();
							$logs->addPremiumMoveBuilding 
							(
								$building, 
								$loc['x'], $loc['y'], 
								$oldloc[0], $oldloc[1]
							);
						
							$this->reloadLocation ($loc['x'], $loc['y']);
							$this->reloadLocation ($oldloc[0], $oldloc[1]);
						}
						else
						{
							$data = array
							(
								'village' => $village->getId (),
								'action' => 'movebuilding',
								'building' => $building->getId (),
								'x' => $loc['x'],
								'y' => $loc['y']
							);
							
							// Generate approve URL
							$page->set 
							(
								'confirm_url', 
								htmlentities
								(
									$player->getCreditUseUrl 
									(
										self::COST_MOVEBUILDING, 
										$data, 
										'Move building'
									)
								)
							);
						}
					}
					
					else
					{
						$page->set ('error', $chk[1]);
					}
				}
				
				$page->set ('building', $building->getId ());
				
				$page->set ('locx', $loc['x']);
				$page->set ('locy', $loc['y']);
				
				return $page->parse ('dolumar/premium/selectlocation.phpt');
			}
			else
			{
				// Show a list of all buildings				
				$buildings = $village->buildings->getBuildings ();
				foreach ($buildings as $v)
				{
					if ($v->isMoveable ())
					{
						$loc = $v->getLocation ();
				
						$page->addListValue
						(
							'buildings',
							array
							(
								'id' => $v->getId (),
								'name' => $v->getName (false),
								'location' => '['.$loc[0] . ',' . $loc[1].']'
							)
						);
					}
				}
				
				return $page->parse ('dolumar/premium/selectbuildings.phpt');
			}
		}
		else
		{	
			$villages = $player->getVillages ();
			
			foreach ($villages as $v)
			{
				$page->addListValue
				(
					'villages',
					array
					(
						'id' => $v->getId (),
						'name' => Neuron_Core_Tools::output_varchar ($v->getName ())
					)
				);
			}
			
			$page->set ('action', 'movebuilding');
			$page->set ('village', isset ($village) && $village ? $village->getId () : $player->getCurrentVillage ()->getId ());
		
			return $page->parse ('dolumar/premium/selectvillage.phpt');
		}
	}
	
	private function canMoveVillage (Dolumar_Players_Village $village)
	{
		// Fetch logs
		$objLogs = Dolumar_Players_Logs::getInstance ();
		
		$objLogs->clearFilters ();
		$objLogs->addShowOnly ('premium_movevillage');
		$objLogs->setTimeInterval (time () - 60 * 60 * self::MIN_MOVEVILLAGE_INTERVAL_DAYS);
		
		$logs = $objLogs->getLogs ($village, 0, 5, 'DESC');
		
		if (count ($logs) > 0)
		{
			return false;
		}
		
		return true;
	}
	
	private function getMoveVillage ()
	{
		$page = new Neuron_Core_Template ();
		$player = Neuron_GameServer::getPlayer ();
		
		$input = $this->getInputData ();
		
		$x = isset ($input['x']) ? intval ($input['x']) : null;
		$y = isset ($input['y']) ? intval ($input['y']) : null;
		
		if (isset ($input['village']))
		{
			$village = $player->getMyVillage ($input['village']);
		}
		
		if (isset ($input['village']) && isset ($input['x']) && isset ($input['y']) && !isset ($input['do']))
		{			
			$offset = isset ($input['offset']) ? $input['offset'] : 0;
			
			if ($village)
			{
				if ($this->canMoveVillage ($village))
				{
					$location = $village->movevillage->getValidLocation ($x, $y, $offset);
				
					if ($location)
					{
						list ($nx, $ny, $new_offset) = $location;
					
						// Jump to this location
						$this->mapJump ($nx, $ny);
					
						$page->set ('proposal', true);
						$page->set ('x', $nx);
						$page->set ('y', $ny);
						$page->set ('offset', $new_offset);
					
						$data = array
						(
							'action' => 'movevillage',
							'village' => $village->getId (),
							'x' => $nx,
							'y' => $ny
						);
					
						// Generate approve URL
						$page->set 
						(
							'confirm_url', 
							htmlentities 
							(
								$player->getCreditUseUrl 
								(
									self::COST_MOVEVILLAGE, 
									$data, 
									'Move village ' .$village->getName () .' to '.$nx.','.$ny 
								)
							)
						);
					
						//return $page->parse ('dolumar/premium/movevillage_proposal.phpt');
					}
					else
					{
						$page->set ('error', 'no_location_found');
					}
				}
				else
				{
					$page->set ('error', 'village_timeout');
				}
			}
		}
		
		$page->set ('desired_x', isset ($x) ? $x : '');
		$page->set ('desired_y', isset ($y) ? $y : '');
		
		$page->set ('village', isset ($village) ? $village->getId () : $player->getCurrentVillage ()->getId ());
		
		$page->set ('days', self::MIN_MOVEVILLAGE_INTERVAL_DAYS);
		
		$villages = $player->getVillages ();
		
		foreach ($villages as $v)
		{
			$page->addListValue
			(
				'villages',
				array
				(
					'id' => $v->getId (),
					'name' => Neuron_Core_Tools::output_varchar ($v->getName ())
				)
			);
		}
	
		return $page->parse ('dolumar/premium/movevillage.phpt');
	}
	
	private function convertCredits ($amount = 100)
	{
		//return $amount;
		$player = Neuron_GameServer::getPlayer ();
		
		if ($player)
		{
			$amount = $player->getCreditDisplay ($amount);
		}
		
		return $amount;
	}
}
?>
