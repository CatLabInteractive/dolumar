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
		
			// Back to the list, from any step: forget the target the window remembers.
			elseif (isset ($input['overview']) && !isset ($input['report']))
			{
				$this->setAjaxPollSeconds (0);
				$this->updateRequestData (array ('vid' => $this->village->getId ()));
				return $this->getBattleOverview ();
			}

			// Choose a (new) target. Picking one sends the target along, which goes to the next step.
			elseif (isset ($input['action']) && !isset ($input['target']))
			{
				$this->setAjaxPollSeconds (0);
				$this->updateRequestData (array ('vid' => $this->village->getId ()));
				return $this->getChooseTarget ();
			}

			elseif (isset ($data['target']))
			{
				$this->setAjaxPollSeconds (0);
				return $this->getChallenge ($data['target']);
			}
			
			elseif (isset ($input['report']))
			{
				$log = isset ($input['log']) ? $input['log'] : null;

				// Old links ask for the fight log with fightlog=1: that is the replay now.
				$view = isset ($input['view']) ? $input['view'] : null;
				if ($view === null && isset ($input['fightlog']) && $input['fightlog'] == 1)
				{
					$view = Dolumar_Battle_ReportView::VIEW_REPLAY;
				}
				if ($view === null && isset ($data['view']))
				{
					$view = $data['view'];
				}

				return $this->getBattleReport ($input['report'], $log, $view);
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
	
	/*
		Choose a target for an attack or support: tabs Suggested (attacks only), Nearby, Recent and Search.
		Nearby and the honour cost are premium (Dolumar_Battle_TargetList::canSeeNeighbourhood).
	*/
	private function getChooseTarget ()
	{
		$input = $this->getInputData ();
		$mode = isset ($input['command']) && $input['command'] == 'support' ? 'support' : 'attack';

		$premium = Dolumar_Battle_TargetList::canSeeNeighbourhood ($this->village->getOwner ());
		$list = new Dolumar_Battle_TargetList ($this->village, $mode, $premium);

		$suggestions = $list->getSuggestions ();

		$tabs = array ();
		if (count ($suggestions) > 0)
		{
			$tabs[] = 'suggested';
		}
		$tabs[] = 'nearby';
		$tabs[] = 'recent';
		$tabs[] = 'search';

		$query = isset ($input['sVillageName']) ? (string) $input['sVillageName'] : '';

		// Free players start on Recent rather than on the locked Nearby tab.
		$default = $tabs[0] === 'nearby' && !$premium ? 'recent' : $tabs[0];
		$tab = isset ($input['tab']) && in_array ($input['tab'], $tabs) ? $input['tab'] : $default;
		if ($query !== '')
		{
			$tab = 'search';
		}

		$filter = isset ($input['filter']) && in_array ($input['filter'], array ('players', 'tribes')) ? $input['filter'] : 'all';

		$page = new Neuron_Core_Template ();
		$page->setTextSection ('targets', 'battle');

		$page->set ('mode', $mode);
		$page->set ('tabs', $tabs);
		$page->set ('tab', $tab);
		$page->set ('filter', $filter);
		$page->set ('premium', $premium);
		$page->set ('query', Neuron_Core_Tools::output_varchar ($query));
		$page->set ('nearbyCount', Dolumar_Battle_TargetList::NEARBY);

		$sections = array ();
		switch ($tab)
		{
			case 'suggested':
				$sections[] = array ('title' => null, 'rows' => $suggestions);
			break;

			case 'nearby':
				if ($mode === 'support')
				{
					$sections[] = array ('title' => 'ownVillages', 'rows' => $list->getOwnVillages ());
					$sections[] = array ('title' => 'clanVillages', 'rows' => $list->getClanVillages ());
					$sections[] = array ('title' => 'otherVillages', 'rows' => $list->getNearby ($filter), 'nearby' => true);
				}
				else
				{
					$sections[] = array ('title' => null, 'rows' => $list->getNearby ($filter), 'nearby' => true);
				}
			break;

			case 'recent':
				if ($mode === 'attack')
				{
					$sections[] = array ('title' => 'attackedYou', 'rows' => $list->getAttackers ());
				}
				$sections[] = array ('title' => 'fought', 'rows' => $list->getFought ());
				$sections[] = array ('title' => 'visited', 'rows' => $list->getVisited ());
			break;

			case 'search':
				$sections[] = array ('title' => null, 'rows' => $list->search ($query), 'search' => $query !== '');
			break;
		}

		$page->set ('sections', $sections);

		return $page->parse ('battle/chooseTarget.phpt');
	}

	/*
		The way back from every step after a target was picked: choose another target, or the battle list.
	*/
	private function getBackLinks ($action = null)
	{
		if ($action === null)
		{
			$data = $this->getInputData ();
			$action = isset ($data['command']) && $data['command'] == 'support' ? 'support' : 'attack';
		}

		$page = new Neuron_Core_Template ();
		$page->setTextSection ('targets', 'battle');
		$page->set ('action', $action);
		return $page->parse ('battle/backLinks.phpt');
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
			return $this->getBackLinks () . '<p class="false">'.$text->get ('vacationMode', 'challenge', 'battle').'</p>';
		}
		
		elseif ($target->isFound () && $target->isActive () && $distance !== false)
		{
			return $this->getBackLinks () . '<p class="false">'.$text->get ('ownVillage', 'challenge', 'battle').'</p>';
		}
		
		elseif ($distance === false)
		{
			return $this->getBackLinks () . '<p class="false">'.$text->get ('unreachable', 'challenge', 'battle').'</p>';
		}

		else
		{
			return $this->getBackLinks () . '<p class="false">'.$text->get ('teamNotFound', 'challenge', 'battle').'</p>';
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
				return $this->getBackLinks ('attack') . '<p class="true">'.$text->get ('bdone', 'challenge', 'battle').'</p>';
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
		$page->set ('backLinks', $this->getBackLinks ('support'));
		
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
		$page->set ('backLinks', $this->getBackLinks ('attack'));
		
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
		$page->set ('backLinks', $this->getBackLinks ('attack'));
		
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
			$this->updateContent ($this->getBackLinks () . '<p class="false">'.$text->get ('teamNotFound').'</p>');
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
				$view = isset ($data['view']) ? $data['view'] : null;
				$this->updateContent ($this->getBattleReport ($data['report'], null, $view));
			}
		}
	}

	private function getBattleReport ($id, $logid = null, $view = null)
	{
		$text = Neuron_Core_Text::getInstance ();

		$report = new Dolumar_Battle_Report ($id);
		$report->setVillageFilter ($this->village);

		$reportView = new Dolumar_Battle_ReportView ($report, $this->village);
		if (!$reportView->isFound ())
		{
			$this->setAjaxPollSeconds (0);
			return '<p class="false">' . $text->get ('notFound', 'report', 'battle') . '</p>';
		}

		if (!in_array ($view, array (Dolumar_Battle_ReportView::VIEW_SUMMARY, Dolumar_Battle_ReportView::VIEW_REPLAY, Dolumar_Battle_ReportView::VIEW_CHRONICLE)))
		{
			$view = $reportView->isDone () ? Dolumar_Battle_ReportView::VIEW_SUMMARY : Dolumar_Battle_ReportView::VIEW_REPLAY;
		}

		if (!$reportView->isDone ())
		{
			// A fight that is still playing: refresh every 15 seconds, in the view the player is looking at.
			$this->setAjaxPollSeconds (15);
			$this->updateRequestData (array ('vid' => $this->village->getId (), 'report' => $id, 'view' => $view));
		}
		else
		{
			$this->setAjaxPollSeconds (0);
			$this->updateRequestData (array ('vid' => $this->village->getId ()));
		}

		return $reportView->render ($view, $logid, $this->village->getId ());
	}

	private function getBattleOverview ()
	{
		$input = $this->getInputData ();

		$filter = isset ($input['filter']) && in_array ($input['filter'], array ('attacks', 'defences')) ? $input['filter'] : 'all';
		$iPage = isset ($input['page']) ? $input['page'] : 0;

		$page = new Neuron_Core_Template ();
		$page->setTextSection ('overview2', 'battle');

		// Fights still playing, on top.
		$live = array ();
		foreach (Dolumar_Battle_Report::getVillageReports ($this->village, 'live', 0, 5) as $report)
		{
			$view = new Dolumar_Battle_ReportView ($report, $this->village);
			$live[] = $view->getListRow ();
		}

		$total = Dolumar_Battle_Report::countVillageReports ($this->village, $filter);
		$limit = Neuron_Core_Tools::splitInPages ($page, $total, $iPage, 10, 4, array ('filter' => $filter));

		$rows = array ();
		foreach (Dolumar_Battle_Report::getVillageReports ($this->village, $filter, $limit['start'], $limit['perpage']) as $report)
		{
			$view = new Dolumar_Battle_ReportView ($report, $this->village);
			$rows[] = $view->getListRow ();
		}

		$page->set ('vid', $this->village->getId ());
		$page->set ('filter', $filter);
		$page->set ('total', $total);
		$page->set ('live', $live);
		$page->set ('rows', $rows);

		return $page->parse ('battle/overview2.phpt');
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
