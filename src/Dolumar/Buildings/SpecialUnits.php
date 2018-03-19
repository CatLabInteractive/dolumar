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

abstract class Dolumar_Buildings_SpecialUnits extends Dolumar_Buildings_Building
{
	abstract function getSpecialUnit ();
	abstract function getSpecialUnitCapacity ();
	
	private $sError;
	
	private $iUnitCount = array ();
	private $aTrainingUnits = null;
	private $aKnownActions = null;
	
	private $aAvailableUnits = null;
	
	// This variable determines what textfile to use for the "learn" dialog.
	protected $sCastTextFile = 'magic';
	
	public function getCustomContent ($input)
	{
		$runes = $this->getUsedRunes ();
		$runes = array_keys ($runes);
	
		// Fetch the unit
		$objUnit = $this->getSpecialUnit ();
		
		$page = new Neuron_Core_Template ();
		
		if ($this instanceof Dolumar_Buildings_WizardTower)
		{
			$page->set ('runetype', $runes[0]);
		}
		
		$page->set ('bid', $this->getId ());
		$page->set ('vid', $this->getVillage ()->getId ());
		
		// Check for actions
		$sAction = isset ($input['action']) ? $input['action'] : null;
		switch ($sAction)
		{
			case 'train':
				if ($this->trainUnit ())
				{
					$page->set ('isTrained', true);
				}
				else
				{
					$page->set ('isTrained', false);
					$page->set ('trainError', $this->getError ());
				}
			break;
			
			case 'learn':
				$spell = isset ($input['effect']) ? $input['effect'] : false;
				
				$spells = count ($this->getKnownEffects ()) - count ($this->getFreeEffects ());
				if ($spell && $spells < $this->getLevel ())
				{
					$spell = $this->getAvailableEffect ($spell);
					
					if ($spell && !$this->doesKnowEffect ($spell) && $spell->canLearnSpell ($this))
					{
						$db = Neuron_Core_Database::__getInstance ();
						
						$db->insert
						(
							'specialunits_effects',
							array
							(
								'b_id' => $this->getId (),
								'e_id' => $spell->getId ()
							)
						);
						
						$this->aKnownActions = null;
					}
				}
			break;
		}
		
		$page->setTextSection (strtolower ($this->getClassName ()), 'buildings');
		
		// Textfile
		$page->set ('textfile', $this->sCastTextFile);
		
		$page->set ('canTrain', $this->canTrainUnits ());
		
		$page->set ('capacity', $this->getSpecialUnitCapacity ());
		$page->set ('training_cost', Neuron_Core_Tools::resourceToText ($objUnit->getTrainingCost ()));
		
		// Show duration countdown if training
		if ($this->isTraining ())
		{
			$units = $this->getTrainingUnits ();
			$page->set ('training_countdown', Neuron_Core_Tools::getCountdown ($units[0]['vsu_tEndDate']));
		}
		
		// Inhabitans
		$page->set ('inhabitans', $this->getUnitCount (true));
		$page->set ('inhabitans_in', $this->getUnitCount (false));
		
		// Window
		$page->set ('actionWindow', $objUnit->getWindowAction ());
		
		$page->set ('freeSpellSlots', $this->countFreeEffectSlots ());
		
		$page->set ('unitname', $this->getSpecialunit ()->getName (false));
		$page->set ('unitsname', $this->getSpecialunit ()->getName (true));
		
		// See if you can choose a new spell
		$spells = $this->countFreeEffectSlots ();
		
		if ($spells > 0)
		{
			// Player has to choose a new spell!
			foreach ($this->getAvailableEffects () as $v)
			{
				if (!$this->doesKnowEffect ($v) && $v->canLearnSpell ($this))
				{
					$page->addListValue
					(
						'spells',
						$v->getOutputData ($this->getSpecialUnit (), $this->getVillage ())
						/*
						array
						(
							'id' => $v->getId (),
							'name' => $v->getName (),
							'description' => $v->getDescription ()
						)
						*/
					);
				}
			}
		}
		
		return $page->parse ('buildings/specialunits.phpt');
	}
	
	/*
		Returns the amount of spells you can still learn.
	*/
	private function countFreeEffectSlots ()
	{
		return $this->getLevel () - (count ($this->getKnownEffects ()) - count ($this->getFreeEffects ()));
	}
	
	public function doesKnowEffect ($spell)
	{
		foreach ($this->getKnownEffects () as $v)
		{
			if ($v->equals ($spell))
			{
				return true;
			}
		}
		
		return false;
	}
	
	/*
		Train a unit and return TRUE if success.
	*/
	private function trainUnit ()
	{
		$db = Neuron_Core_Database::__getInstance ();

		$objUnit = $this->getSpecialUnit ();
		$objVillage = $this->getVillage ();
		
		// Check for capacity
		if ($this->isFull ())
		{
			$this->sError = 'err_full';
			return false;
		}
		
		elseif ($this->isTraining ())
		{
			$this->sError = 'err_training';
			return false;
		}
		
		elseif (!$this->getVillage ()->resources->takeResourcesAndRunes ($objUnit->getTrainingCost ()))
		{
			$this->sError = 'err_resources';
			return false;
		}
		
		else
		{
			$db->insert
			(
				'villages_specialunits',
				array 
				(
					'v_id' => $objVillage->getId (),
					'vsu_bid' => $this->getId (),
					'vsu_tStartDate' => time (),
					'vsu_tEndDate' => time () + $objUnit->getTrainDuration ()
				)
			);
			
			// Reload cache!
			$this->reloadCache ();
		
			return true;
		}
	}
	
	/*
		Return TRUE if this building is full	
	*/
	private function isFull ()
	{
		return $this->getUnitCount (true) >= $this->getSpecialUnitCapacity ();
	}
	
	/*
		Returns the amount of units in this building
	*/
	public function getUnitCount ($all = false)
	{
		$sKey = $all ? 0 : 1;
		if (!isset ($this->iUnitCount[$sKey]))
		{
			$units = $this->getVillage ()->getSpecialUnits ($all);
		
			$this->iUnitCount[$sKey] = 0;
			foreach ($units as $v)
			{
				if ($v->getBuilding ()->getId () == $this->getId ())
				{
					$this->iUnitCount[$sKey] ++;
				}
			}
		}
		
		return $this->iUnitCount[$sKey];
	}
	
	/*
		Returns all units in an array (one unit / row)
	*/
	public function getUnits ()
	{
		if (!isset ($this->aAvailableUnits))
		{
			$units = $this->getVillage ()->getSpecialUnits ();
		
			$this->aAvailableUnits = array ();
			foreach ($units as $v)
			{
				if ($v->getBuilding ()->getId () == $this->getId ())
				{
					$this->aAvailableUnits[] = $v;
				}
			}
		}
		
		return $this->aAvailableUnits;
	}
	
	/*
		Returns TRUE if some other troop is already training at the moment
	*/
	private function isTraining ()
	{
		return count ($this->getTrainingUnits ()) > 0;
	}
	
	private function getTrainingUnits ()
	{
		if ($this->aTrainingUnits === null)
		{
			$db = Neuron_Core_Database::__getInstance ();		
			$this->aTrainingUnits = $db->select 
			(
				'villages_specialunits', 
				array ('*'), 
				"vsu_tEndDate > ".time()." AND vsu_bid = ".$this->getId ()
			);
		}
		return $this->aTrainingUnits;
	}
	
	/*
		This function combines a bunch of functions.
		Returns TRUE if it's possible to train new untis
	*/
	private function canTrainUnits ()
	{
		return !$this->isFull () && !$this->isTraining ();
	}
	
	/*
		Force all member variables to reload from database
	*/
	private function reloadCache ()
	{
		$this->iUnitCount = null;
		$this->aTrainingUnits = null;
	}
		
	private function getError ()
	{
		return $this->sError;
	}
	
	/*
		Return a list of effects you know.
	*/
	public function getKnownEffects ()
	{
		if (!isset ($this->aKnownActions))
		{
			$db = Neuron_Core_Database::__getInstance ();
		
			$l = $db->getDataFromQuery
			(
				$db->customQuery
				("
					SELECT
						specialunits_effects.e_id
					FROM
						specialunits_effects
					WHERE
						specialunits_effects.b_id = ".$this->getId ()."
				")
			);
		
			$this->aKnownActions = $this->getFreeEffects ();
			foreach ($l as $v)
			{
				$this->aKnownActions[] = Dolumar_Effects_Effect::getFromId ($v['e_id']);
			}
		}
		
		return $this->aKnownActions;
	}
	
	/*
		This function may return a couple
		of "free spells". These spells are
		always known.
	*/
	protected function getFreeEffects ()
	{
		return array ();
	}
	
	/*
		Return all available effects.
		For every level a building has, you can choose one effect.
	*/
	public function getAvailableEffect ($objSpell)
	{
		if (! $objSpell instanceof Dolumar_Effects_Effect)
		{
			$objSpell = Dolumar_Effects_Effect::getFromId ($objSpell);
		}
		
		if ($objSpell)
		{
			foreach ($this->getAvailableEffects () as $v)
			{
				if ($v->equals ($objSpell))
				{
					return $v;
				}
			}
		}
		
		return false;
	}
	
	protected function getAvailableEffects ()
	{
		return array ();
	}
}
?>
