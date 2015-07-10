<?php
abstract class Dolumar_Buildings_Crafting extends Dolumar_Buildings_Building
{
	public function getCustomContent ($input)
	{
		$id = isset ($input['crafting']) ? $input['crafting'] : false;
		$page = isset ($input['action']) ? $input['action'] : false;

		// Quick check for upgrade
		if ($page == 'do-upgrade' && $id)
		{
			$item = $this->getCraftableItem ($id);
			if ($item)
			{
				if (!$this->getVillage ()->equipment->increaseEquipmentLevel ($item))
				{
					$error = $this->getVillage ()->equipment->getError ();
					return $this->getUpgradeItem ($id, $input, $error);
				}
			}
		}

		if ($page == 'craft' && $id)
		{
			return $this->getCraftItem ($id, $input);
		}
		
		elseif ($page == 'upgrade' && $id)
		{
			return $this->getUpgradeItem ($id, $input);
		}
		
		else
		{
			return $this->getOverview ();
		}
	}

	private function getOverview ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('buildings');
		$text->setSection ('crafting');
	
		$page = new Neuron_Core_Template ();

		$page->set ('title', $text->get ('title'));

		$page->set ('section', 'overview');
		$page->set ('about', $text->get ('about'));

		$equipment = $this->getCraftableItems ();
		foreach ($equipment as $v)
		{
			$page->addListValue
			(
				'equipment',
				array
				(
					$v->getName (),
					$v->getId ()
				)
			);
		}
		
		foreach ($equipment as $v)
		{
			if ($this->getVillage ()->equipment->canIncreaseLevel ($v))
			{
				$page->addListValue
				(
					'upgrade_equipment',
					array
					(
						$v->getName (),
						$v->getId ()
					)
				);
			}
		}
		
		// Equipment upgrades
		$unused = $this->getVillage ()->equipment->getUnusedLevels ();
		$page->set ('unused', $unused);

		return $page->parse ('buildings/crafting.tpl');
	}
	
	private function getUpgradeItem ($id, $input, $error = null)
	{
		$this->hideGeneralOptions ();
	
		$equipment = $this->getCraftableItem ($id);
		
		if (!$equipment)
		{
			return '<p>Item not found.</p>';
		}
		
		$page = new Neuron_Core_Template ();
		
		if (isset ($error))
		{
			$page->set ('error', $error);
		}
		
		$page->set 
		(
			'upgradecost', 
			Neuron_Core_Tools::getResourceToText 
			(
				$this->getVillage ()->equipment->getIncreaseLevelCost ($equipment)
			)
		);
		
		$page->set 
		(
			'current_level',
			array
			(
				'name' => $equipment->getName (),
				'cost' => $this->getCost ($equipment),
				'stats' => Neuron_Core_Tools::output_text ($equipment->getStats_text (), false)
			)
		);
		
		//$equipment->setLevel ($equipment->getLevel () + 1);
		$equipment2 = $equipment->getNextLevel ();
		
		$page->set 
		(
			'next_level',
			array
			(
				'name' => $equipment2->getName (),
				'cost' => $this->getCost ($equipment2),
				'stats' => Neuron_Core_Tools::output_text ($equipment2->getStats_text (), false)
			)
		);
		
		$page->set ('buildingid', $this->getId ());
		$page->set ('id', $equipment->getId ());
		
		return $page->parse ('buildings/crafting_upgrade.phpt');
	}
	
	private function getCraftableItem ($id)
	{
		$equipment = $this->getCraftableItems ();
		
		$id = explode (':', $id);
		$id = $id[0];
		
		// Search for key
		$key = false;
		$equips = $this->getCraftableItems ();
		foreach ($equips as $k => $v)
		{
			if ($v->getId (false) == $id)
			{
				return $v;
			}
		}
		
		return false;
	}

	private function getCraftItem ($id, $input)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		// Check if this building is crafting already
		$l = $db->select
		(
			'villages_items',
			array ('i_id'),
			"i_bid = '".$this->getId ()."' AND i_endCraft > '".time()."'"
		);

		if (count ($l) > 0)
		{
			$text = Neuron_Core_Text::__getInstance ();
			$page = new Neuron_Core_Template ();
			$page->set ('working', $text->get ('working', 'crafting', 'buildings'));
			$page->set ('toReturn', $text->getClickTo ($text->get ('toReturn', 'crafting', 'buildings')));
			return $page->parse ('buildings/working.tpl');
		}
		else
		{
			$equipment = $this->getCraftableItems ();
			
			// Search for key
			$key = false;
			$equips = $this->getCraftableItems ();
			foreach ($equips as $k => $v)
			{
				if ($v->getId () == $id)
				{
					$key = $k;
				}
			}
			
			if ($key !== false)
			{
				$item = $equips[$key];

				$text = Neuron_Core_Text::__getInstance ();
				$text->setFile ('buildings');
				$text->setSection ('crafting');

				// Check for input
				if (isset ($input['amount']) && is_numeric ($input['amount']))
				{
					$aantal = abs (floor ($input['amount']));
					
					$cost = $item->getCraftCost ($this->getVillage (), $aantal);

					// Remove resources
					if ($this->getVillage()->resources->takeResourcesAndRunes($cost))
					{
						$this->getVillage()->craftEquipment
						(
							$this,
							$item,
							$item->getCraftDuration ($this->getVillage (), $aantal),
							$aantal
						);

						//return $this->getOverview ();
						return '<p>'.$text->get ('done').'</p>';
					}
					else
					{
						$error = $text->get ('noResources');
					}
				}
				
				$page = new Neuron_Core_Template ();
				
				// Get max craftable
				$player = $this->getVillage()->getOwner ();
		
				if ($player->isPremium ())
				{
					$page->set ('maxcraftable', Neuron_Core_Tools::putIntoText
					(
						$text->get ('maxcraftable'),
						array
						(
							'amount' => $this->calculateMaxCraftable ($item),
							'items' => $item->getName (true)
						)
					));
				}

				if (isset ($error))
				{
					$page->set ('error', $error);
				}

				$page->set ('title', $text->get ('title'));
				$page->set ('amount', $text->get ('amount'));
				$page->set ('submit', $text->get ('submit'));
				$page->set ('itemId', $item->getId ());
				
				$page->set ('section', 'crafting');
				
				$page->set ('cost',
					$this->getCost ($item)
				);

				$page->set ('duration',
					Neuron_Core_Tools::putIntoText (
						$text->get ('duration'),
						array
						(
							$item->getName (),
							Neuron_Core_Tools::getDuration ($item->getCraftDuration ($this->getVillage ()))
						)
					)
				);

				$page->set ('stats', Neuron_Core_Tools::output_text ($item->getStats_text (), false));
				
				$page->set ('about', Neuron_Core_Tools::putIntoText ($text->get ('craft'), array ($item->getName (true))));
				$page->set ('return', $text->getClickTo ($text->get ('toReturn')));
				
				return $page->parse ('buildings/crafting.tpl');
			}
			else
			{
				return $this->getOverview ();
			}
		}
	}
	
	private function getCost ($item)
	{
		$text = Neuron_Core_Text::getInstance ();
		return Neuron_Core_Tools::putIntoText (
			$text->get ('cost', 'crafting', 'buildings'),
			array
			(
				$item->getName (),
				$item->getCraftCost_text ($this->getVillage ())
			)
		);
	}
	
	protected function onDestruct ()
	{
		$village = $this->getVillage ();
		$village->equipment->cancelCrafting ($this);
	}
	
	private function calculateMaxCraftable ($item)
	{
		$res = $item->getCraftCost ($this->getVillage ());
		$myRes = $this->getVillage()->resources->getResources ();

		foreach ($res as $k => $v)
		{
			if (!isset ($maxRes) || ($myRes[$k] / $v) < $maxRes)
			{
				$maxRes = floor ($myRes[$k] / $v);
			}
		}
		
		return $maxRes;
	}
	
	/*
		Return all item names of one specific type.
		This will use the items described in the INI file equipment.ini
	*/
	protected function getItemsFromType ($sType)
	{
		$objStats = Neuron_Core_Stats::__getInstance ();
		
		$data = $objStats->getFile ('equipment');
		
		if (!is_array ($data))
		{
			return array ();
		}
		
		$out = array ();
		
		// Loop trough the items
		foreach ($data as $id => $item)
		{
			if (is_array ($item))
			{
				if (isset ($item['type']) && $item['type'] == $sType)
				{
					$equipment = new Dolumar_Players_Equipment ($id);
					$out[] = $equipment;
				}
			}
		}
		
		return $out;
	}
	
	/*
		Return all equipment that can be crafted here
		(but not especialy now.) Also, does not include levels
	*/
	abstract public function getEquipment ();

	public final function getCraftableItems ()
	{
		$out = array ();
		foreach ($this->getEquipment () as $equipment)
		{
			$level = $this->getVillage ()->equipment->getEquipmentLevel ($equipment);
			$equipment->setLevel ($level);
			
			if ($equipment->canCraftItem ($this))
			{
				$out[] = $equipment;
			}
		}
		return $out;
	}
}
?>
