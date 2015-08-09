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

class Dolumar_Windows_Battle extends Neuron_GameServer_Windows_Window
{
	private $village;
	private $error;

	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$login = Neuron_Core_Login::__getInstance ();

		$data = $this->getRequestData ();
		$this->village = Dolumar_Players_Village::getMyVillage ($data['vid']);
	
		// Window settings
		$this->setSize ('558px', '400px');

		if ($login->isLogin () && $this->village && $this->village->isActive ())
		{
			$this->setTitle ($text->get ('battle', 'menu', 'main').
				' ('.Neuron_Core_Tools::output_varchar ($this->village->getName ()).')');
		}

		else
		{
			$this->setTitle ($text->get ('battle', 'menu', 'main'));
		}
		
		// Onload
		$this->setOnload ('initBattleWindow');
		$this->setClassName ('battle');
	}
	
	public function getContent ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$data = $this->getRequestData ();
		$input = $this->getInputData ();
		
		if (!isset ($input['report']) && isset ($data['report']) && !isset ($input['overview']))
		{
			$input['report'] = $data['report'];
		}

		$login = Neuron_Core_Login::__getInstance ();
		
		if ($this->village && $this->village->isActive () && $this->village->getOwner()->getId() == $login->getUserId ())
		{
			// Make sure you are not in vacation mode.
			if ($this->village->getOwner()->inVacationMode ())
			{
				$this->setAjaxPollSeconds (0);
				$this->updateRequestData (array ('vid' => $this->village->getId ()));
				return '<p class="false">'.$text->get ('vacationMode', 'main', 'main').'</p>';
			}
		
			elseif (isset ($data['target']))
			{
				$this->setAjaxPollSeconds (0);
				//$this->updateRequestData (array ('vid' => $this->village->getId ()));
				return $this->getChallenge ($data['target']);
			}
			
			elseif (isset ($input['action']))
			{
				$this->setAjaxPollSeconds (0);
				//$this->updateRequestData (array ('vid' => $this->village->getId ()));
				return $this->getChooseTarget ();
			}
			
			elseif (isset ($input['report']))
			{
				// Refresh this window every xx seconds
				$log = isset ($input['log']) ? $input['log'] : null;
				$report = isset ($input['fightlog']) ? $input['fightlog'] == 1 : false;
			
				return $this->getBattleReport ($input['report'], $log, $report);
			}
			else
			{
				$this->setAjaxPollSeconds (0);
				$this->updateRequestData (array ('vid' => $this->village->getId ()));
				return $this->getBattleOverview ();
			}
		}

		else
		{
			return '<p class="false">'.$text->get ('login', 'login', 'account').'</p>';
		}
	}
	
	private function getChooseTarget ()
	{
		$structure = new Neuron_Structure_ChooseTarget ($this->getInputData (), $this->village, false, true);
		return $structure->getHTML ();
	}
	
	private function getChallenge ($targetId, $error = null)
	{		
		$text = Neuron_Core_Text::__getInstance ();

		$target = Dolumar_Players_Village::getVillage ($targetId);
		
		$distance = Dolumar_Map_Map::getDistanceBetweenVillages ($this->village, $target, false);

		if (
			$target->isFound () && 
			$target->isActive () && 
			$target->getId () != $this->village->getId () &&
			!$target->getOwner ()->inVacationMode () &&
			$distance !== false
		)
		{
			// Different actions
			$data = $this->getInputData ();
			
			$action = isset ($data['command']) ? $data['command'] : 'attack';
			
			switch ($action)
			{
				case 'support':
					return $this->getSendSupport ($target);
				break;
			
				case 'attack':
				default:
					return $this->getAttackVillage ($target);
				break;
			}
		}
		
		elseif ($target->isFound () 
			&& $target->isActive () 
			&& $target->getOwner ()->getId () != $this->village->getOwner ()->getId ()
			&& $distance !== false
		)
		{
			// User is currently in vacation mode
			return '<p class="false">'.$text->get ('vacationMode', 'challenge', 'battle').'</p>';
		}
		
		elseif ($target->isFound () && $target->isActive () && $distance !== false)
		{
			return '<p class="false">'.$text->get ('ownVillage', 'challenge', 'battle').'</p>';
		}
		
		elseif ($distance === false)
		{
			return '<p class="false">'.$text->get ('unreachable', 'challenge', 'battle').'</p>';
		}

		else
		{
			return '<p class="false">'.$text->get ('teamNotFound', 'challenge', 'battle').'</p>';
		}
	}
	
	/*
		Return the html to attack a village
	*/
	private function getAttackVillage ($target)
	{
		$text = Neuron_Core_Text::getInstance ();
	
		// Cannot attack yourself.
		if ($target->getOwner ()->equals ($this->village->getOwner ()))
		{
			return $this->getChooseUnits ($target);
		}
		
		$canAttack = true;
		if ($target->getOwner ()->isClanMember ($this->village->getOwner ()))
		{
			$this->error = $text->get ('isClanmember', 'challenge', 'battle');
			$canAttack = false;
		}
	
		$input = $this->getInputData ();
		$confirmed = isset ($input['confirm']) ? $input['confirm'] == 'yes' : false;
	
		$squads = $this->getSquadsFromInput ($target);
		$specials = $this->getSpecialUnitsFromInput ();
		
		if ($confirmed && count ($squads) > 0 && $canAttack)
		{
			if ($this->village->attackVillage ($target, $squads, $specials))
			{
				reloadStatusCounters ();
				return ('<p>'.$text->get ('bdone', 'challenge', 'battle').'</p>');
			}
			else
			{			
				return $this->getChooseSpecialUnits ($target, $squads, $this->village->getError ());
			}
		}
		
		// Squads selected: special units & confirm
		elseif (count ($squads) > 0)
		{
			return $this->getChooseSpecialUnits ($target, $squads);
		}
		
		// Not enough toops
		/*
		elseif (isset ($input['attack']))
		{
			return $this->getChooseUnits ($target, 'noUnits');
		}
		*/
		
		// No troops at all ;-)
		else
		{
			$text = Neuron_Core_Text::getInstance ();
		
			if (isset ($input['selected_troops']))
			{
				$this->alert ($text->get ('dragTroops', 'challenge', 'battle'));
			}
		
			return $this->getChooseUnits ($target);
		}
	}
	
	/*
		Send support units
	*/
	private function getSendSupport ($target)
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		$page = new Neuron_Core_Template ();
		$page->set ('target', Neuron_Core_Tools::output_varchar ($target->getName ()));		
		$page->set ('action', 'support');
		
		$input = $this->getInputData ();
		
		$hasSend = false;
		if (isset ($input['sendSquads']))
		{
			// The form has been submitted. Loop & find troops
			$squads = $this->getSquads ();
			
			foreach ($squads as $v)
			{
				if (isset ($input['squad_'.$v->getId ()]) && $input['squad_'.$v->getId ()] == 'yup')
				{
					$v->sendToVillage ($target);
					$hasSend = true;
				}
			}
		}
		
		$page->set ('hasSend', $hasSend);
	
		$distance = Dolumar_Map_Map::getDistanceBetweenVillages ($this->village, $target, false);
		$page->set 
		(
			'distance',
			Neuron_Core_Tools::putIntoText
			(
				$text->get ('distance', 'challenge', 'battle'),
				array
				(
					Neuron_Core_Tools::output_distance ($distance)
				)
			)
		);
		
		// Get my troops
		$squads = $this->getSquads ();
		
		foreach ($squads as $v)
		{
			if ($v->getUnitsAmount () > 0)
			{
				$page->addListValue
				(
					'squads',
					array
					(
						'sName' => Neuron_Core_Tools::output_varchar ($v->getName ()),
						'oUnits' => $v->getUnits (),
						'id' => $v->getId ()
					)
				);
			}
		}
		
		return $page->parse ('battle/sendSupport.phpt');
	}
	
	private function getChooseUnits ($target, $error = null)
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('battle');
		$text->setSection ('challenge');
	
		$page = new Neuron_Core_Template ();
		
		$page->set ('target', Neuron_Core_Tools::output_varchar ($target->getName ()));		
		$page->set ('action', 'attack');
		$page->set ('target_id', $target->getId ());
		
		$showForm = true;
		
		if ($target->getOwner ()->equals ($this->village->getOwner ()))
		{
			$page->set ('error', $text->get ('ownVillage'));
			$showForm = false;
		}
		
		$page->set ('showForm', $showForm);
		
		foreach ($this->village->getAttackSlots ($target) as $k => $v)
		{
			$page->addListValue
			(
				'slots',
				array
				(
					'id' => $k,
					'sType' => $text->get ($v->getName (), 'slots', 'battle'),
					'sName' => $v->getName ()
				)
			);
		}

		if (!empty ($error))
		{
			$page->set ('error', $text->get ($error));
		}
		
		$page->set
		(
			'challenge',
			Neuron_Core_Tools::putIntoText
			(
				$text->get ('challenge'),
				array (Neuron_Core_Tools::output_varchar ($target->getName ()))
			)
		);

		// Calculate distance
		$afstand = Dolumar_Map_Map::getDistanceBetweenVillages ($this->village, $target, false);

		$page->set 
		(
			'distance',
			Neuron_Core_Tools::putIntoText
			(
				$text->get ('distance'),
				array
				(
					Neuron_Core_Tools::output_distance ($afstand)
				)
			)
		);

		// Make a list of available squads
		$squads = $this->getSquads ();

		foreach ($squads as $v)
		{
			if ($v->getUnitsAmount () > 0)
			{
				$page->addListValue
				(
					'squads',
					array
					(
						'sName' => Neuron_Core_Tools::output_varchar ($v->getName ()),
						'oUnits' => $v->getUnits (),
						'id' => $v->getId ()
					)
				);
			}
		}

		// Sort the list
		$page->sortList ('units');
		
		if (isset ($this->error))
		{
			$page->set ('error', $this->error);
		}
		
		return $page->parse ('battle/chooseUnits.tpl');
	}
	
	private function getChooseSpecialUnits ($target, $squads, $error = null)
	{
		$page = new Neuron_Core_Template ();
		$page->setTextSection ('specialUnits', 'battle');
		
		$page->set ('error', $error);
		
		$page->set ('target', Neuron_Core_Tools::output_varchar ($target->getName ()));
		$page->set ('targetId', $target->getId ());
		
		$distance = Dolumar_Map_Map::getDistanceBetweenVillages ($this->village, $target, false);
		
		$page->set ('distance', Neuron_Core_Tools::output_distance ($distance));
		
		foreach ($this->village->getAttackSlots ($target) as $k => $v)
		{
			if (isset ($squads[$k]))
			{
				$unitId = $squads[$k]->getSquad ()->getId () . '_' . $squads[$k]->getUnitId ();
				
				$page->addListValue
				(
					'slots',
					array
					(
						'id' => $k,
						'unit' => $unitId
					)
				);
			}
		}
		
		$duration = $this->village->battle->getMoveDuration ($squads, $distance);
		if ($duration > 60 * 60 * 24)
		{
			$page->set ('duration', $duration);
		}
		
		$honour = Dolumar_Battle_Battle::getHonourPenalty ($this->village, $target);
		if ($honour > 0)
		{
			//$bigger = round ( ($this->village->getScore () / $target->getScore ()) * 100) - 100;
			$bigger = round ((Dolumar_Battle_Battle::getSizeDifference ($this->village, $target) * 100) - 100);
		
			$page->set ('honour', $honour);
			$page->set ('size', $bigger);
		}
		
		// Fetch thze special units
		$units = $this->village->getSpecialUnits ();
		
		foreach ($units as $v)
		{
			$actions = $v->getEffects ();
			
			// Prepare the actions
			$aActions = array ();
			foreach ($actions as $action)
			{
				if ($action instanceof Dolumar_Effects_Battle)
				{
					$aActions[] = array
					(
						'name' => $action->getName (),
						'id' => $action->getId (),
						'cost' => Neuron_Core_Tools::resourceToText 
						(
							$action->getCost ($v, $target), 
							false, 
							false, 
							false, 
							'rune', 
							false
						)
					);
				}
			}

			if (count ($aActions) > 0)
			{
				asort ($aActions);
			
				// Add the special unit to the list
				$page->addListValue
				(
					'specialunits',
					array
					(
						'id' => $v->getId (),
						'name' => Neuron_Core_Tools::output_varchar ($v->getName (false, true)),
						'actions' => $aActions
					)
				);
			} 
		}
		
		return $page->parse ('battle/specialUnits.phpt');
	}

	private function processChallengeInput ($target)
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('battle');
		$text->setSection ('challenge');
	
		$db = Neuron_Core_Database::__getInstance ();
		
		if ($target->isFound ())
		{			
			$this->updateContent ();
		}
		else
		{
			$this->updateContent ('<p class="false">'.$text->get ('teamNotFound').'</p>');
		}
	}
	
	private function getSquadsFromInput ($target)
	{
		$input = $this->getInputData ();

		// Insert troops
		$addSquads = array ();
		
		/*
		$squads = $this->village->getSquads (false, true);
		foreach ($squads as $v)
		{
			$key = 'cb_bat_'.$v->getId ();
			if
			(
				isset ($input[$key])
				&& $input[$key] = md5 ($v->getId () . "-" . $v->getName ())
				&& $v->getUnitsAmount () > 0
			)
			{	
				$addSquads[] = $v;
			}
		}
		*/
		
		// Load all village squads and put them in asoc array
		$squads = $this->getSquads (false, true);
		
		$oSquads = array ();
		$oDuplicate = array ();
		
		$rSquads = array ();
		
		foreach ($squads as $i => $squad)
		{
			foreach ($squad->getUnits () as $unit)
			{
				$oSquads[$squad->getId ().'_'.$unit->getUnitId ()] = $unit;
			}
		}
		
		foreach ($this->village->getAttackSlots ($target) as $slot => $objSlot)
		{
			$key = 'slot'.$slot;
			
			if (isset ($input[$key]) && $input[$key] > 0)
			{
				// Check if troop exists
				if (!isset ($oDuplicate[$input[$key]]) && isset ($oSquads[$input[$key]]))
				{
					$rSquads[$slot] = $oSquads[$input[$key]];
					$oDuplicate[$input[$key]] = true;
				}
			}
		}
		
		return $rSquads;
	}
	
	private function getSpecialUnitsFromInput ()
	{
		$units = $this->village->getSpecialUnits ();	
		$input = $this->getInputData ();
		
		$specialUnits = array ();
		
		foreach ($units as $unit)
		{
			$id = $unit->getId ();
			
			if (isset ($input['special_'.$id]) && $input['special_'.$id] == 'send')
			{
				// Fetch action
				if (isset ($input['action_'.$id]))
				{
					$specialUnits[] = array ($unit, $input['action_'.$id]);
				}
			}
		}
		
		return $specialUnits;
	}

	public function processInput ()
	{
		$data = $this->getRequestData ();
		$input = $this->getInputData ();
		
		if ($this->village->isFound ())
		{
			if (isset ($input['target']))
			{
				$data['target'] = $input['target'];
				$this->updateRequestData
				(
					array
					(
						'vid' => $this->village->getId (),
						'target' => intval ($input['target'])
					)
				);
			}
		
			if (!isset ($data['target']))
			{
				$this->updateContent ();
			}
			else
			{
				$target = Dolumar_Players_Village::getVillage ($data['target']);
				
				if ($target->isFound () && $target->getId () != $this->village->getId ())
				{
					$this->processChallengeInput ($target);
				}

				elseif ($target->isFound ())
				{
					$this->updateContent ($this->getChallenge ($target->getId (), 'ownVillage'));
				}

				else
				{
					$this->updateContent ($this->getChallenge ($target->getId (), 'villageNotFound'));
				}
			}
		}
	}
	
	public function getRefresh ()
	{
		if ($this->village && $this->village->isFound ())
		{
			$data = $this->getRequestData ();
			$input = $this->getInputData ();

			if (isset ($data['report']) && !isset ($input['overview']))
			{
				$this->updateContent ($this->getBattleReport ($data['report'], null, true));
			}
		}
	}

	private function getBattleReport ($id, $logid = null, $showrep = false)
	{	
		$report = new Dolumar_Battle_Report ($id);
		
		if (!$report->isFinished ())
		{
			$this->setAjaxPollSeconds (15);
			$this->updateRequestData (array ('vid' => $this->village->getId (), 'report' => $id));
		}
		else
		{
			$this->setAjaxPollSeconds (0);
			$this->updateRequestData (array ('vid' => $this->village->getId ()));
		}
		
		return $report->getReport ($this->village, $logid, $showrep);
	}

	private function getBattleOverview ()
	{
		//$db = Neuron_Core_Database::__getInstance ();
		
		$text = Neuron_Core_Text::__getInstance ();
		$text->setSection ('overview');
		$text->setFile ('battle');
		
		$input = $this->getInputData ();
		
		// Get logs from this village
		$objLogs = Dolumar_Players_Logs::getInstance ();
		
		$objLogs->addShowOnly ('attack');
		$objLogs->addShowOnly ('defend');
		
		$iPage = isset ($input['page']) ? $input['page'] : 0;
		
		$page = new Neuron_Core_Template ();
		
		// Split in pages
		$limit = Neuron_Core_Tools::splitInPages 
		(
			$page, 
			$objLogs->countLogs ($this->village), 
			$iPage, 
			10
		);
		
		$objLogs->addMyVillage ($this->village);
		
		$logs = $objLogs->getLogs ($this->village, $limit['start'], $limit['perpage'], 'DESC');
		$loghtml = $this->getLogHTML ($page, $objLogs, $logs);
		
		$output = new Neuron_Core_Template ();
		
		$output->set ('vid', $this->village->getId ());
		$output->set ('title', $text->get ('overview'));
		$output->set ('loghtml', $loghtml);
		return $output->parse ('battle/overview.tpl');
	}
	
	protected function getLogHTML ($page, $objLogs, $logs)
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setSection ('overview');
		$text->setFile ('battle');
	
		foreach ($logs as $v)
		{
			$link = Neuron_URLBuilder::getInstance ()->getUpdateUrl 
			(
				'Battle', 
				$text->get ('watchreport'),
				array
				(
					'report' => $v['reportid']
				)
			);
		
			$txt = $objLogs->getLogText ($v, true);
		
			$page->addListValue
			(
				'logs',
				array
				(
					'date' => date (DATETIME, $v['timestamp']),
					'text' => $txt,
					'link' => $link
				)
			);
		}
		
		return $page->parse ('logbook.phpt');	
	}
	
	private function getSquads ()
	{
		return $this->village->getSquads (false, true);
	}
}

?>
