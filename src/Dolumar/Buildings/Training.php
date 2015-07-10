<?php
abstract class Dolumar_Buildings_Training extends Dolumar_Buildings_Building
{
	protected $availableUnitIds = array ();
	private $myErrors = array ();

	protected $CAPACITY = 20;
	
	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		$this->addRequiresBuilding (10);
		$this->addRequiresBuilding (11);
		$this->addRequiresBuilding (12);
	}	

	public function getCustomContent ($input = array ())
	{
		if (isset ($input['unit']) && !empty ($input['unit']))
		{
			return $this->getTrainUnit ($input);
		}

		else
		{
			return $this->getUnitOverview ($input);
		}
	}
	
	public function isTrainingUnits ()
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$l = $db->select
		(
			'villages_units',
			array ('uid'),
			"bid = '".$this->getId ()."' AND endTraining > '".time()."'"
		);
		
		return count ($l) > 0;
	}

	private function getTrainUnit ($input)
	{
		$db = Neuron_Core_Database::__getInstance ();

		if ($this->isTrainingUnits ())
		{
			$text = Neuron_Core_Text::__getInstance ();
			$page = new Neuron_Core_Template ();
			$page->set ('working', $text->get ('working', 'training', 'buildings'));
			$page->set ('toReturn', $text->getClickTo ($text->get ('toReturn', 'training', 'buildings')));
			return $page->parse ('buildings/working.tpl');
		}
		else
		{
			$page = new Neuron_Core_Template ();
			$unit = Dolumar_Units_Unit::getUnitFromName ($input['unit'], $this->getVillage ()->getRace (), $this->getVillage ());
			
			// Check if this unit is trainable here
			$checkUnit = false;
			foreach ($this->getCheckedAvailableUnits () as $v)
			{
				if ($v->getUnitId () == $unit->getUnitId ())
				{
					$checkUnit = true;
				}
			}

			if ($unit && $checkUnit)
			{
				$showForm = true;
				if (isset ($input['amount']) && is_numeric ($input['amount']) && $input['amount'] > 0)
				{
					$showForm = !$this->processTrainUnits ($unit, ceil ($input['amount']), isset ($input['queue']));
				}

				if ($showForm)
				{
					return $this->getChooseAmount ($input, $unit);
				}

				// Done
				else
				{
					return $this->getCustomContent ();
				}
			}

			else
			{
				$page = new Neuron_Core_Template ();

				$text = Neuron_Core_Text::__getInstance ();
				$text->setFile ('buildings');
				$text->setSection ('training');

				// Title
				$page->set ('train', $text->get ('train'));
				$page->set ('section', 'notFound');
				$page->set ('error', $text->get ('unitNotFound'));

				return $page->parse ('buildings/training.tpl');
			}
		}
	}

	private function getChooseAmount ($input, $unit)
	{
		$page = new Neuron_Core_Template ();
		Dolumar_Units_Unit::printStatNames ($page);

		$page->set ('section', 'train');

		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('buildings');
		$text->setSection ('training');

		foreach ($this->myErrors as $v)
		{
			$page->addListValue ('errors', array ($text->get ($v)));
		}

		// Title
		$page->set ('train', $text->get ('train'));
		
		// Only for premium users
		$player = $this->getVillage()->getOwner ();
		
		if ($player->isPremium ())
		{
			$page->set ('maxtrainable_value', Neuron_Core_Tools::putIntoText
			(
				$text->get ('maxtrainable'),
				array
				(
					$this->calculateMaxTrainable ($unit),
					$this->calculateOptimalTrainable ($unit)
				)
			));
		}

		$page->set ('otherUnit', $text->getClickTo ($text->get ('toOtherUnit')));

		// Stats
		$stats = $unit->getStats ();

		$page->set ('unit',$unit->getName ());
		$page->set ('stats', $stats);

		$page->set ('trainingCost', $text->get ('trainingCost'));
		$page->set ('trainingCost_value', $unit->getTrainingCost_text ());

		$page->set ('consCost', $text->get ('consCost'));
		$page->set ('consCost_value', $unit->getConsumption_text ());

		$page->set ('trainUnits', $text->get ('trainUnits'));
		$page->set ('trainSubmit', $text->get ('trainSubmits'));
		
		$page->set ('amount', $text->get ('amount'));

		$page->set ('unit_value', $input['unit']);

		return $page->parse ('buildings/training.tpl');
	}

	private function calculateMaxTrainable ($unit)
	{
		// Max according to capacity
		$capacity = $this->getVillage ()->getUnitCapacity ($this, 'absolute');
		$currentUnits = $this->getVillage()->getUnitBuildingCount ($this, 'absolute');

		$maxCapacity = floor (($capacity - $currentUnits) / $unit->getRequiredSpace ());

		// Max according to resources
		$res = $unit->getTrainingCost ();
		
		if (count ($res) == 0)
		{
			return max (0, $maxCapacity);
		}
		
		$myRes = $this->getVillage()->resources->getResources ();

		foreach ($res as $k => $v)
		{
			if (!isset ($maxRes) || ($myRes[$k] / $v) < $maxRes)
			{
				$maxRes = floor ($myRes[$k] / $v);
			}
		}

		return max (0, min ($maxCapacity, $maxRes));
		
	}
	
	private function calculateOptimalTrainable ($unit)
	{
		$amount = $this->calculateMaxTrainable ($unit);
		$buildings = 0;
		
		foreach ($this->getVillage ()->buildings->getBuildings () as $v)
		{
			if ($v->isFinishedBuilding ())
			{
				if ($capacity = $v->getUnitCapacity ())
				{
					$classname = get_class ($this);
					if ($v instanceof $classname)
					{
						if (!$v->isTrainingUnits ())
						{
							$buildings ++;
						}
					}
				}
			}
		}
		
		if ($buildings < 1)
		{
			return 0;
		}
		
		return ceil ($amount / $buildings);
	}

	private function getUnitOverview ($input)
	{
		$page = new Neuron_Core_Template ();

		$page->set ('section', 'overview');

		// Print stat names
		Dolumar_Units_Unit::printStatNames ($page);

		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('buildings');
		$text->setSection ('training');

		// Title
		$page->set ('train', $text->get ('train'));

		// "About" text: depends on building
		$page->set ('about', $text->get ('training', strtolower ($this->getClassName ())));

		$page->set ('noUnits', $text->get ('noUnits'));
		$page->set ('trainUnits', $text->get ('trainUnits'));

		$minsize = $this->getMinimumSize ();

		// Capacity
		$capacity = $this->getVillage ()->getUnitCapacity ($this);
		$capacity /= $minsize;
		
		$bcapacity = $this->getUnitCapacity ();
		$bcapacity /= $minsize;
		
		$capacityStatus = $this->getVillage ()->getUnitCapacityStatus ($this);

		$page->set ('capacity', $text->get ('capacity'));
		$page->set ('buildingCapacity', $text->get ('buildingCapacity'));
		$page->set ('totalCapacity', $text->get ('totalCapacity'));
		$page->set ('filling', $text->get ('filling'));

		$page->set ('capacity_left', $capacityStatus);
		$page->set ('capacity_right', 100 - $capacityStatus);

		$page->set ('totalCapacity_value', $capacity);
		$page->set ('capacity_value', $bcapacity);

		$units = $this->getCheckedAvailableUnits ();
		
		foreach ($units as $v)
		{
			$stats = $v->getStats ();

			$page->addListValue
			(
				'units',
				array
				(
					$v->getName (),
					$stats,
					$v->getClassName ()
				)
			);
		}

		return $page->parse ('buildings/training.tpl');
	}
	
	private function getMinimumSize ()
	{
		$max = 1;
		foreach ($this->getCheckedAvailableUnits () as $v)
		{
			$mi = $v->getRequiredSpace ();
			if ($mi > $max)
			{
				$max = $mi;
			}
		}
		return $max;
	}
	
	/*
		Get all units (even those who aren't trainable here yet)
	*/
	public function getUnits ()
	{
		$out = array ();
		foreach ($this->getAvailableUnits () as $v)
		{
			$out[] = Dolumar_Units_Unit::getUnitFromName ($v, $this->getRace (), $this->getVillage ());
		}
		return $out;
	}

	private function getCheckedAvailableUnits ()
	{
		// Loop trough units
		$units = array ();
		foreach ($this->getAvailableUnits () as $v)
		{
			// Get the unit
			$unit = Dolumar_Units_Unit::getUnitFromName ($v, $this->getVillage ()->getRace (), $this->getVillage ());

			if ($unit->canTrainUnit ())
			{
				$units[] = $unit;
			}
		}

		return $units;
	}

	public function getUnitCapacity ()
	{
		//return floor ($this->CAPACITY + ( ($this->CAPACITY / 10) * ($this->getLevel () - 1)));
		return floor ($this->CAPACITY * $this->getLevel ());
	}
	
	public function doTrainUnits ($unit, $amount)
	{
		$village = $this->getVillage ();
		
		$res = $unit->multiplyCost ($unit->getTrainingCost (), $amount);
	
		// Check building capacity and income etc
		$capacity = $this->getVillage ()->getUnitCapacity ($this, 'absolute');
		$currentUnits = $this->getVillage()->getUnitBuildingCount ($this, 'absolute');

		// Add the new amount
		$currentUnits += $amount * $unit->getRequiredSpace ();
		
		$success = false;
		
		if ($currentUnits > $capacity)
		{
			$this->myErrors[] = 'noRoom';
		}
		
		elseif (!$village->resources->takeResourcesAndRunes ($res))
		{
			$this->myErrors[] = 'noResources';
		}
		
		else
		{
			$village->trainUnits ($unit, $amount, $this);
			$village->reloadUnits ();
			$success = true;
		}
		
		return $success;
	}

	private function processTrainUnits ($unit, $amount, $queue = false)
	{
		// Get resources
		$village = $this->getVillage ();
		
		$res = $unit->multiplyCost ($unit->getTrainingCost (), $amount);

		// Check building capacity and income etc
		$capacity = $this->getVillage ()->getUnitCapacity ($this, 'absolute');
		$currentUnits = $this->getVillage()->getUnitBuildingCount ($this, 'absolute');

		// Add the new amount
		$currentUnits += $amount * $unit->getRequiredSpace ();
		
		$success = false;

		if ($queue)
		{
			$success = true;
			
			$this->getVillage ()->premium->addQueueAction
			(
				'training',
				array
				(
					'building' => $this->getId (),
					'unit' => $unit->getUnitId (),
					'amount' => $amount
				)
			);
			
			reloadStatusCounters ();
		}

		elseif ($currentUnits > $capacity)
		{
			$this->myErrors[] = 'noRoom';
		}
		
		elseif (!$village->resources->takeResourcesAndRunes ($res))
		{
			$this->myErrors[] = 'noResources';
		}
		
		else
		{
			$village->trainUnits ($unit, $amount, $this);
			$village->reloadUnits ();
			$success = true;
		}
		
		if (!$success)
		{
			$text = Neuron_Core_Text::getInstance ();
		
			$error = $this->myErrors[count ($this->myErrors) - 1];
			$txterr = $text->get ($error, 'training', 'buildings');
			
			$jsondata = json_encode
			(
				array
				(
					'amount' => $amount,
					'unit' => $unit->getClassName (),
					'queue' => true
				)
			);
			
			// Propose a queued solution.
			$this->objWindow->dialog 
			(
				$txterr, 
				$text->get ('queueTraining', 'training', 'buildings'), 
				'windowAction (this, '.$jsondata.');', 
				$text->get ('okay', 'main', 'main'), 
				'void(0);'
			);
		}
		
		return $success;
	}
	
	public function getTrainError ()
	{
		return $this->myErrors[count ($this->myErrors) - 1];
	}
}
