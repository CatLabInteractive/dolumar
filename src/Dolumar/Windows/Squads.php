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

class Dolumar_Windows_Squads extends Neuron_GameServer_Windows_Window
{
	private $village;

	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$login = Neuron_Core_Login::__getInstance ();
		
		$data = $this->getRequestData ();
		$this->village = Dolumar_Players_Village::getVillage ($data['vid']);
	
		// Window settings
		$this->setSize ('325px', '275px');
		
		$this->setAllowOnlyOnce ();

		if ($login->isLogin () && $this->village->isFound ())
		{
			$this->setTitle ($text->get ('squads', 'menu', 'main').
				' ('.Neuron_Core_Tools::output_varchar ($this->village->getName ()).')');
		}

		else
		{
			$this->setTitle ($text->get ('squads', 'menu', 'main'));
		}
		
		$this->setClassName ('squads');
	}
	
	public function getContent ()
	{
		$input = $this->getInputData ();

		$login = Neuron_Core_Login::__getInstance ();
		
		$page = isset ($input['page']) ? $input['page'] : (isset ($input['action']) ? $input['action'] : 'overview');
		$id = isset ($input['id']) ? $input['id'] : 0;
	
		$text = Neuron_Core_Text::__getInstance ();
		if (
			$this->village &&
			$this->village->isActive () &&
			$this->village->getOwner()->getId () == $login->getUserId ()
		)
		{
			if ($id > 0)
			{
				$squad = $this->village->getSquads ($id);
				if (count ($squad) == 1)
				{
					switch ($page)
					{
						case 'squad':
							return $this->getSquadOverview ($squad[0]);
						break;

						case 'addUnits':
							return $this->getAddUnits ($squad[0]);
						break;
						
						case 'removeUnits':
							return $this->getRemoveUnits ($squad[0]);
						break;

						default:
							return $this->getOverview ();
						break;
					}
				}
				else
				{
					return $this->getOverview ();
				}
			}
			elseif ($page == 'add')
			{
				// Add a squad
				return $this->getAddSquad ();
			}
			else
			{
				return $this->getOverview ();
			}
		}
		else
		{
			return '<p class="false">'.$text->get ('login', 'login', 'account').'</p>';
		}
	}

	public function processInput ()
	{
		$this->updateContent ();
	}

	private function getOverview ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('squads');
		$text->setSection ('overview');
		
		$page = new Neuron_Core_Template ();
		
		$page->set ('vid', $this->village->getId ());

		$input = $this->getInputData ();
		if (isset ($input['remove']))
		{
			$squad = $this->village->getSquads ($input['remove']);
			if (count ($squad) == 1 && $squad[0]->isIdle ())
			{
				$this->village->removeSquad ((int)$input['remove']);
			}
			else
			{
				$this->alert ($text->get ('notIdle'));
			}
		}
		
		// Check for recalls
		if (isset ($input['recall']))
		{
			$squad = $this->village->getSquads ($input['recall'], false, false);
			if (count ($squad) == 1)
			{
				$squad[0]->goHome ();
			}
		}

		// Show a list of squads
		$squads = $this->village->getSquads (false, false, false);
		
		foreach ($squads as $v)
		{
			// Check if this squad is "home" or "supporting"
			$village = $v->getVillage ();
			$location = $v->getCurrentLocation ();
			
			// Check for supporting
			if ($this->village->getId () != $location->getId ())
			{
				$status = 'away';
			}
			
			elseif ($village->getId () != $this->village->getId ())
			{
				$status = 'rent';
			}
			
			else
			{
				$status = 'home';
			}
		
			$page->addListValue
			(
				'squads',
				array
				(
					'name' => Neuron_Core_Tools::output_varchar ($v->getName ()),
					'units' => $v->getUnits (),
					'id' => $v->getId (),
					'status' => $status,
					'village' => Neuron_Core_Tools::putIntoText ($village->getName ()),
					'village_id' => $village->getId (),
					'location' => Neuron_Core_Tools::putIntoText ($location->getName ()),
					'location_id' => $location->getId (),
					'isMine' => $v->getCurrentLocation()->getId() == $v->getVillage()->getId()
				)
			);
		}

		$page->set ('squads', $text->get ('squads'));
		$page->set ('noSquads', $text->get ('noSquads'));
		$page->set ('toAdd', $text->getClickTo ($text->get ('toAdd')));
		$page->set ('toAll', $text->getClickTo ($text->get ('toAll')));
		$page->set ('edit', $text->get ('edit'));
		$page->set ('remove', $text->get ('remove'));
		$page->set ('confirm', $text->get ('conRemove'));
		$page->set ('noUnits', $text->get ('noUnits'));

		return $page->parse ('squads/squads.tpl');
	}

	private function getSquadOverview ($objSquad)
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('squads');
		$text->setSection ('squad');
		
		$page = new Neuron_Core_Template ();

		$page->set ('squadId', $objSquad->getId ());
		
		$page->set ('squads', $text->get ('squads'));
		$page->set ('remove', $text->get ('remove'));
		$page->set ('confirm', addslashes ($text->get ('conRemove')));
		$page->set ('noUnits', $text->get ('noUnits'));
		$page->set ('noItem', $text->get ('noItem'));
		$page->set ('toAdd', $text->getClickTo ($text->get ('toAdd')));
		$page->set ('toRemove', $text->getClickTo ($text->get ('toRemove')));
		$page->set ('toReturn', $text->getClickTo ($text->get ('toReturn')));
		$page->set ('name', Neuron_Core_Tools::output_varchar ($objSquad->getName ()));

		$input = $this->getInputData ();
		if (isset ($input['remove']))
		{
			if ($objSquad->isIdle ())
			{
				if ($objSquad->removeUnit ((int)$input['remove']))
				{
					$this->setInputData ('{}');
					return $this->getOverview ();
				}
			}
			else
			{
				$this->alert ($text->get ('notIdle'));
			}
		}

		// Handle equipment input
		$equipment = $this->village->getEquipment();

		$reloadEquipment = false;
		foreach ($objSquad->getUnits () as $unit)
		{
			// Loop trough equipment to seek input
			foreach ($equipment as $type => $items)
			{
				$inputKey = $unit->getUnitId () . '_' . $type;
				if (isset ($input[$inputKey]))
				{				
					if ($input[$inputKey] == "0")
					{
						$itemid = Dolumar_Players_Equipment::getItemTypeId_static ($type);
						$objSquad->unequipItem ($unit, $itemid);
					}
					else
					{
						// Loop trough your equipment and search for selected item
						foreach ($items as $name => $item)
						{
							if ($input[$inputKey] == $item->getId ())
							{
								if (!$objSquad->addEquipment ($unit, $item))
								{
									//$this->alert ($text->get ($objSquad->getError ()));
									$page->set ('error', $text->get ($objSquad->getError ()));
								}
								
								break 1;
							}
						}
					}

					$reloadEquipment = true;
				}
			}
		}

		//return '<pre>'.print_r ($equipment, true).'</pre>';

		//return $input[$inputKey];
		//return $inputKey . '<pre>'.print_r ($input, true).'</pre>';

		if ($reloadEquipment)
		{
			reloadEverything ();
			$objSquad->reloadUnits ();
		}
		
		foreach ($objSquad->getUnits () as $v)
		{
			// And do the actual output
			$items = array ();
			foreach ($v->getEquipment () as $eq)
			{
				$items[$eq->getItemType ()] = $eq->getId ();
			}
			
			$page->addListValue
			(
				'units',
				array
				(
					Neuron_Core_Tools::output_varchar ($v->getName ()),
					$v->getSquadlessAmount (),
					$v->getImageUrl (),
					$v->getUnitId (),
					$items,
					'stats' => Dolumar_Windows_Units::getUnitStatsHTML ($v)
				)
			);
		}

		$o = array ();
		$e = $this->village->getEquipment();
		
		foreach ($e as $k => $v)
		{
			$page->addListValue
			(
				'equipment',
				array
				(
					Neuron_Core_Tools::output_varchar (Dolumar_Players_Equipment::getEquipmentName ($k)),
					$v,
					$k
				)
			);
		}

		return $page->parse ('squads/view.tpl');
	}

	private function getAddSquad ()
	{
		$input = $this->getInputData ();
		if 
		(
			isset ($input['squadName']) 
			&& Neuron_Core_Tools::checkInput ($input['squadName'], 'unitname')
			&& isset ($input['squadUnit'])
		)
		{
			// Check if unit type exists
			$unit = Dolumar_Units_Unit::getUnitFromId ($input['squadUnit'], $this->village->getRace (), $this->village);
		
			if ($unit)
			{
				// Add the squad
				$objSquad = $this->village->addSquad ($input['squadName'], $input['squadUnit']);

				if ($objSquad)
				{
					return $this->getAddUnits ($objSquad);
				}
				else
				{
					return $this->getOverview ();
				}
			}
			else
			{
				$this->alert ('Invalid unit type: '.$input['squadUnit']);
			}
		}
		else
		{		
			$text = Neuron_Core_Text::__getInstance ();
			$text->setFile ('squads');
			$text->setSection ('addSquad');

			$page = new Neuron_Core_Template ();

			if (isset ($input['squadName']))
			{
				$page->set ('warning', $text->get ('squadNameSyntax'));
			}

			$page->set ('title', $text->get ('title'));
			$page->set ('name', $text->get ('name'));
			$page->set ('submit', $text->get ('submit'));
			$page->set ('toReturn', $text->getClickTo ($text->get ('toReturn')));
			
			// Load all units
			$units = $this->village->getMyUnits ();
			
			foreach ($units as $unit)
			{
				if ($unit->getSquadlessAmount () > 0)
				{
					$page->addListValue
					(
						'units',
						array
						(
							'name' => $unit->getName (),
							'id' => $unit->getUnitId ()
						)
					);
				}
			}

			return $page->parse ('squads/add.tpl');
		}
	}

	private function getAddUnits ($objSquad)
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('squads');
		$text->setSection ('addUnits');
		
		$units = $this->village->getMyUnits ();
		$input = $this->getInputData ();
		
		//return print_r ($units, true);

		$selectedUnits = array ();
		$iCount = 0;
		foreach ($units as $v)
		{
			$key = 'unit_'.$v->getUnitId ();
			if (isset ($input[$key]) && ((int)$input[$key]) > 0)
			{
				$objSquad->addUnits ($v, ((int)$input[$key]));
				$iCount ++;
			}
		}

		if ($iCount > 0)
		{
			$error = $objSquad->getError ();
			
			if (!empty ($error))
			{
				$tError = $text->get ($error);
			}
			else
			{
				return $this->getSquadOverview ($objSquad);
			}

			$units = $this->village->getMyUnits ();
		}
		
		$page = new Neuron_Core_Template ();
		
		$page->set ('title', $text->get ('title'));
		$page->set ('toReturn', $text->getClickTo ($text->get ('toReturn')));

		$page->set ('noUnits', $text->get ('noUnits'));
		$page->set ('squadId', $objSquad->getId ());
		$page->set
		(
			'about',
			Neuron_Core_Tools::putIntoText
			(
				$text->get ('about'),
				array (Neuron_Core_Tools::output_varchar ($objSquad->getName ()))
			)
		);

		if (isset ($tError))
		{
			$page->set ('error', $tError);
		}

		foreach ($units as $v)
		{			
			if ($v->getSquadlessAmount () > 0 && $objSquad->getUnitType () == $v->getUnitId ())
			{
				$page->addListValue
				(
					'units',
					array
					(
						Neuron_Core_Tools::output_varchar ($v->getName ()),
						$v->getSquadlessAmount (),
						$v->getUnitId ()
					)
				);
			}
		}

		return $page->parse ('squads/units.tpl');
	}
	
	private function getRemoveUnits ($objSquad)
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('squads');
		$text->setSection ('removeUnits');
		
		$units = $objSquad->getUnits ();
		$input = $this->getInputData ();
		
		//return print_r ($units, true);

		$selectedUnits = array ();
		$iCount = 0;
		foreach ($units as $v)
		{
			$key = 'unit_'.$v->getUnitId ();
			if (isset ($input[$key]) && ((int)$input[$key]) > 0)
			{
				$objSquad->removeUnits ($v, ((int)$input[$key]), false);
				$iCount ++;
			}
		}

		if ($iCount > 0)
		{
			$error = $objSquad->getError ();
			
			if (!empty ($error))
			{
				$tError = $text->get ($error);
			}
			else
			{
				$objSquad->reloadUnits ();
				return $this->getSquadOverview ($objSquad);
			}

			$units = $objSquad->getUnits ();
		}
		
		$page = new Neuron_Core_Template ();
		
		$page->set ('title', $text->get ('title'));
		$page->set ('toReturn', $text->getClickTo ($text->get ('toReturn')));

		$page->set ('noUnits', $text->get ('noUnits'));
		$page->set ('squadId', $objSquad->getId ());
		$page->set
		(
			'about',
			Neuron_Core_Tools::putIntoText
			(
				$text->get ('about'),
				array (Neuron_Core_Tools::output_varchar ($objSquad->getName ()))
			)
		);

		if (isset ($tError))
		{
			$page->set ('error', $tError);
		}

		foreach ($units as $v)
		{			
			if ($v->getAmount () > 0 && $objSquad->getUnitType () == $v->getUnitId ())
			{
				$page->addListValue
				(
					'units',
					array
					(
						Neuron_Core_Tools::output_varchar ($v->getName ()),
						$v->getAmount (),
						$v->getUnitId ()
					)
				);
			}
		}

		return $page->parse ('squads/removeunits.tpl');
	}
}
?>
