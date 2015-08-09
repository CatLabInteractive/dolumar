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

class Dolumar_Windows_Thievery extends Dolumar_Windows_Magic
{
	protected $buildingType = "Dolumar_Buildings_ThievesDen";
	protected $sTextFile = 'thievery';
	
	public function getContent ()
	{
		if (!$this->village)
		{
			$text = Neuron_Core_Text::__getInstance ();
			return '<p class="false">'.$text->get ('login', 'login', 'account').'</p>';
		}
		
		$data = $this->getRequestData ();
		$building = isset ($data['building']) ? $data['building'] : 0;
		
		$input = $this->getInputData ();
		
		if ($building > 0 && isset ($input['unit']))
		{
			return $this->commandUnit ($building, $input['unit']);
		}
		
		elseif ($building > 0)
		{
			return $this->getChooseUnit ($building);
		}
		else
		{
			return $this->getOverview ();
		}
	}
	
	public function getOverview ()
	{
		return '<p>No overview available yet.</p>';
	}
	
	protected function getChooseUnit ($building)
	{
		$building = $this->village->buildings->getBuilding ($building);
		if (!$building instanceof $this->buildingType)
		{
			return '<p>Invalid input: building not found.</p>';
		}
		
		// Count the thieves in this building
		$allUnits = $building->getUnits ();
		
		$page = new Neuron_Core_Template ();
		
		$page->setTextSection ('chooseUnit', 'thievery');
		
		$units = array ();
		foreach ($allUnits as $v)
		{
			$location = $v->getLocation ();
		
			$units[] = array
			(
				'id' => $v->getId (),
				'name' => $v->getName (false, true),
				'location' => Neuron_Core_Tools::output_varchar ($location->getName ()),
				'location_id' => $location->getId (),
				'moving' => $v->isMoving () ? Neuron_Core_Tools::getCountdown ($v->getArrivalDate ()) : null
			);
		}
		
		$page->set ('units', $units);
		
		return $page->parse ('thieves/chooseThief.phpt');
	}
	
	/*
		Command unit to do various stuff
	*/
	private function commandUnit ($building, $unitId)
	{
		$building = $this->village->buildings->getBuilding ($building);
		if (!$building instanceof $this->buildingType)
		{
			return '<p>Invalid input: building not found.</p>';
		}
	
		$text = Neuron_Core_Text::__getInstance ();
	
		$input = $this->getInputData ();
		$action = isset ($input['action']) ? $input['action'] : null;
		
		$unit = $this->village->getSpecialUnit ($unitId);
		
		if (!$unit)
		{
			return '<p>Invalid input: unit not found.</p>';
		}
		
		// Check if this unit is moving
		elseif ($unit->isMoving ())
		{
			return '<p class="false">'.$text->get ('isMoving', 'commandUnit', 'thievery').'</p>';
		}
		
		switch ($action)
		{
			case 'move':
				return $this->commandMoveUnit ($building, $unit);
			break;
			
			default:
				// Check if a spell is selected
				$spell = isset ($input['spell']) ? $input['spell'] : null;
				if (isset ($spell))
				{
					$spell = $unit->getEffect ($spell);
					
					if ($spell && isset ($input['confirm']))
					{
						return $this->doCastSpell ($unit, $spell, $unit->getLocation (), false);
					}
					else
					{
						return $this->getConfirmCast ($unit, $spell, $unit->getLocation (), $this->getInputData ());
					}
				}
				
				// Show a list of spells
				else
				{
					return $this->getCastSpell ($unit, array ('unit' => $unit->getId ()), array ('building' => $building->getId ()));
				}
			break;
		}
	}
	
	/*
		Command a unit to move
	*/
	private function commandMoveUnit ($building, $unit)
	{
		$input = $this->getInputData ();
		
		$id = isset ($input['target']) ? intval ($input['target']) : null;
		
		if ($id > 0 && $id != $unit->getLocation ()->getId ())
		{
			// Calculate distance & duration
			$target = Dolumar_Players_Village::getVillage ($id);
			if (!$target)
			{
				return '<p>Invalid input: target village not found.</p>';
			}
		
			if (isset ($input['confirm']))
			{
				$page = new Neuron_Core_Template ();
				$page->setTextSection ('moveUnit', 'thievery');
				
				$page->set ('input', array ('building' => $building->getId ()));
				
				// Move the bloody unit
				$unit->moveUnit ($target);
				
				return $page->parse ('thieves/doneMoving.phpt');
			}
			else
			{
				$page = new Neuron_Core_Template ();
				$page->setTextSection ('moveUnit', 'thievery');
				
				$distance = Dolumar_Map_Map::getDistanceBetweenVillages ($unit->getLocation (), $target);
			
				// Calculate duration
				$duration = $unit->getTravelDuration ($distance);
				
				$page->set ('input', array_merge ($input, array ('confirm' => 'true')));
				
				$page->set ('distance', Neuron_Core_Tools::output_distance ($distance));
				$page->set ('duration', Neuron_Core_Tools::getDuration ($duration));
				
				return $page->parse ('thieves/moveThief.phpt');
			}
		}
		else
		{
			$structure = new Neuron_Structure_ChooseTarget ($this->getInputData (), $this->village, true, true);
			
			$structure->setReturnData 
			(
				array
				(
					'building' => $building->getId ()
				)
			);
			
			return $structure->getHTML ();
		}
	}
	
	/*
		Overwrite the magic's processInput method.
	*/
	public function processInput ()
	{
		return $this->updateContent ();
	}
}
?>
