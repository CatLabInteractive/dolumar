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

class Dolumar_Pages_Report extends Neuron_GameServer_Pages_Page
{
	public function getBody ()
	{
		$unit = $this->getParameter (2);
		
		switch ($unit)
		{
			case 'units':
				return $this->getUnitStats ();
			break;
			
			case 'equipment':
				return $this->getEquipmentStats ();
			break;
			
			case 'effects':
				return $this->getEffectStats ();
			break;
		}

		$module = Neuron_Core_Tools::getInput ('_REQUEST', 'module', 'varchar');
		$data = explode ('/', $module);

		var_dump ($data);

		return '<p>Report not found: ' . $unit . '</p>';
	}
	
	private function getEquipmentStats ()
	{
		$equipment = Dolumar_Players_Equipment::getAllEquipment ();
		
		$types = array ();
		
		foreach ($equipment as $v)
		{
			$type = $v->getItemType ();
			if (!isset ($types[$type]))
			{
				$types[$type] = array ();
			}
			
			$types[$type][] = $v;
		}
		
		$html = '<div class="equipment-report fancybox">';
		
		foreach ($types as $type => $data)
		{
			$this->sortLogables ($data);
			
			$html .= '<div class="equipment-type">';
			$html .= '<h2>' . $type . '</h2>';
		
			foreach ($data as $equipment)
			{
				$html .= '<div class="equipment">';
				
				$html .= '<h3>'.$equipment->getName ().' (' . $equipment->getCategory () . ')</h3>';
				
				for ($i = 1; $i <= Dolumar_Players_Village_Equipment::EQUIPMENT_MAX_LEVEL; $i ++)
				{
					$equipment->setLevel ($i);
					$html .= '<div class="equipment-level">';
					$html .= '<h4>'.$i.': '.$equipment->getName ().'</h4>';
					$html .= Neuron_Core_Tools::output_text ($equipment->getStats_text ());
					$html .= '<p class="equipment-costs"><span class="stat-title">Cost: </span>' . $equipment->getCraftCost_text () .'</p>';
					$html .= '</div>';
				}
				
				$html .= '</div>';
			}
			
			$html .= '</div><div class="clearer"></div>';
		}
		
		return $html . '</div>';
	}
	
	private function sortLogables (&$array)
	{
	
	}
	
	private function getUnitStats ()
	{
		$html = "";
	
		foreach (Dolumar_Races_Race::getRaceObjects () as $race)
		{
			$html .= '<div style="width: 450px; float: left; margin: 0px 0px 0px 0px;">';
		
			$html .= '<h2>'.$race->getDisplayName ().'</h2>';
		
			$units = Dolumar_Units_Unit::getAllUnits ($race);
			
			$page = new Neuron_Core_Template ();
			
			$page->set ('showConsumption', true);
			$page->set ('showCost', true);
			$page->set ('showSpeed', true);
			
			Dolumar_Units_Unit::printStatNames ($page);
			
			foreach ($units as $v)
			{
				$data = array
				(
					'name' => $v->getName (),
					'stats' => $v->getStats (),
					'available' => $v->getAvailableAmount (),
					'total' => $v->getTotalAmount (),
					'consumption' => Dolumar_Tools::resourceToText ($v->getConsumption ()),
					'cost' => Dolumar_Tools::resourceToText ($v->getTrainingCost ()),
					'type' => $v->getAttackType_text (),
					'image' => $v->getImageUrl ()
				);
				
				$page->addListValue ('units', $data);
			}
			
			$html .= $page->parse ('structure/unitstats.phpt');
			
			$html .= '</div>';
		}
		
		return $html;
	}
	
	private function getEffectStats ()
	{
		$effects = Dolumar_Effects_Effect::getEffects ();
		
		$html = '<h2>Effects</h2>';
		$html .= '<div class="effect-report">';
		
		foreach ($effects as $v)
		{
			$html .= '<div class="effect fancybox">';
			
			$html .= '<h3>' . $v->getName () . '</h3>';
			
			for ($i = 1; $i <= 5; $i ++)
			{
				$v->setLevel ($i);
			
				$html .= '<div class="effect-level">';
				$html .= '<h4>Level ' . $i . ' ' . $v->getType_text () . '</h4>';
				$html .= '<p>' . $v->getDescription () . '</p>';
				$html .= '<p class="difficulty">Difficulty: ' . $v->getDifficulty () . '</p>';
				$html .= '</div>';
			}
			
			$html .= '</div>';
		}
		
		$html .= '</div>';
		
		return $html;
	}
}
?>
