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

class Dolumar_Windows_Simulator extends Neuron_GameServer_Windows_Window
{
	private $village;
	private $error;

	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$login = Neuron_Core_Login::__getInstance ();

		$data = $this->getRequestData ();
	
		// Window settings
		$this->setSize ('558px', '650px');


		$this->setTitle ($text->get ('battle', 'menu', 'main'));
		
		// Onload
		$this->setOnload ('initBattleSimulator');
		$this->setClassName ('battle simulator');
	}
	
	public function getContent ()
	{
		$page = new Neuron_Core_Template ();
		
		$input = $this->getInputData ();
		
		if (isset ($input['reset']) && $input['reset'] == 'reset')
		{
			$input['action'] = 'reset';
		}
		
		//return print_r ($input, true);
		if (isset ($input['log']))
		{
			// Update current report
			return $this->showReportFromRequest ($input['log']);
		}
		
		if (isset ($input['action']) && $input['action'] == 'simulate')
		{
			return $this->simulate ();
		}
		
		$slots = isset ($input['slots']) ? intval ($input['slots']) : 7;
		if ($slots < 3 || $slots > 99)
		{
			$slots = 7;
		}
		
		$page->set ('slots', $slots);
		
		$races = Dolumar_Races_Race::getRaceObjects ();
		
		$units = array ();
		
		foreach ($races as $race)
		{
			$tmp = array ();

			$unitsInput = Dolumar_Units_Unit::getAllUnits ($race);
			
			foreach ($unitsInput as $v)
			{
				$tmp[] = array
				(
					'id' => $v->getUnitId (),
					'name' => $v->getName (),
					'img' => $v->getImageUrl ()
				);
			}
			
			$units[] = array
			(
				'id' => $race->getId (),
				'name' => $race->getName (),
				'units' => $tmp
			);
		}
		
		$page->set ('units', $units);
		
		return $page->parse ('battle/simulator/simulator.phpt');
	}
	
	/*
		This method is called when there is already a report
		in requestdata
	*/
	private function showReportFromRequest ($fightlogid)
	{
		$requestdata = $this->getRequestData ();
		$report = Dolumar_Battle_SimulatorReport::unserialize ($requestdata['report']);
		
		return $this->showReport ($report, $fightlogid);
	}
	
	private function showReport (Dolumar_Battle_SimulatorReport $report, $fightlogid = 0)
	{
		return $this->getResetForm () . $report->getReport ($fightlogid);
	}
	
	public function simulate ()
	{
		$input = $this->getInputData ();
		
		//return '<pre>' . print_r ($input, true) . '</pre>';
		$slots = array ();
		
		// Collect thze slots
		foreach (array ('att', 'def') as $side)
		{
			$units = array ();
			
			$hasnext = true;
			$counter = 1;
			
			while ($hasnext)
			{
				$dummy = new Dolumar_Players_DummyVillage ();
				
				$unit = isset ($input['slot_'.$side.'_unit_'.$counter]) ? $input['slot_'.$side.'_unit_'.$counter] : false;
				$slot = isset ($input['slot_'.$side.'_slot_'.$counter]) ? $input['slot_'.$side.'_slot_'.$counter] : false;
				$amount = isset ($input['slot_'.$side.'_amount_'.$counter]) ? $input['slot_'.$side.'_amount_'.$counter] : false;
				
				if ($unit === false || $slot === false || $amount === false)
				{
					$hasnext = false;
					break;
				}
				
				if (!isset ($slots[$counter]))
				{
					$slots[$counter] = Dolumar_Battle_Slot_Grass::getFromId ($slot, $counter, $dummy);
				}
				
				$unitdata = explode ('_', $unit);
				if (count ($unitdata) == 2)
				{
					$race = Dolumar_Races_Race::getFromId ($unitdata[0]);
					
					//echo $race->getName () . "\n";
					
					$unitobj = Dolumar_Units_Unit::getUnitFromId ($unitdata[1], $race, $dummy);
					
					//echo $unitobj->getName () . "\n";
					
					$unitobj->addAmount ($amount, $amount, $amount);
					$unitobj->setBattleSlot ($slots[$counter]);
					
					$units[$counter] = $unitobj;
				}
				
				$counter ++;
			}
			
			$$side = $units;
		}
		
		//return print_r ($att, true) . ' ' . print_r ($def, true);
		
		$logger = new Dolumar_Battle_Logger ();
		
		// __construct ($objAttVil, $objDefVil, $objAttUnits, $objDefUnits, $slots, $specialUnits, $objLogger)
		$fight = new Dolumar_Battle_Fight ($dummy, $dummy, $att, $def, $slots, array (), $logger);
		
		$result = $fight->getResult ();
		
		$out = '<p>Fight result: '.ceil ($result*100) .'%</p>';
		
		$report = Dolumar_Battle_SimulatorReport::getFromLogger ($logger);
		
		$out .= $this->showReport ($report);
		
		// Set the request data
		$this->updateRequestData (array ('report' => $report->serialize ()));
		
		// We puts the report in thze session
		//$_SESSION['tmp_report'] = $report;
		// NEVER DO THAT AGAIN!
		
		return  $out;
	}
	
	private function getResetForm ()
	{
		$input = $this->getInputData ();
		
		$againform = '';
		
		if (isset ($input['action']))
		{
			$againform  = '<form method="post" onsubmit="return submitForm(this);">';
			foreach ($input as $k => $v)
			{
				$againform .= '<input type="hidden" class="hidden" name="'.$k.'" value="'.$v.'" />';
			}
			
			$againform .= '<button type="submit"><span>Simulate again</span></button>';
			$againform .= '</form>';
		}
		
		$againform .= '<form method="post" onsubmit="return submitForm(this);">';
		$againform .= '<input type="hidden" class="hidden" name="reset" value="reset" />';
		$againform .= '<button type="submit"><span>New battle</span></button>';
		$againform .= '</form>';
		
		return $againform;
	}
}

?>
