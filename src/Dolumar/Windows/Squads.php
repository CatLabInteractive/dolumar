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
		$this->setSize ('680px', '460px');
		
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
		$id = isset ($input['id']) ? (int)$input['id'] : 0;

		$text = Neuron_Core_Text::__getInstance ();
		if
		(
			$this->village &&
			$this->village->isActive () &&
			$this->village->getOwner()->getId () == $login->getUserId ()
		)
		{
			if ($id > 0 && $page != 'overview')
			{
				$squad = $this->village->getSquads ($id);
				if (count ($squad) == 1)
				{
					// 'squad', 'addUnits' and 'removeUnits' were separate pages; they all open the editor now.
					return $this->getEditor ($squad[0]);
				}

				return $this->getOverview ();
			}
			elseif ($page == 'add')
			{
				return $this->getEditor (null);
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

	/*
		The status of a regiment as seen from this village: home, away (supporting
		elsewhere), rent (support sent here by someone else) or underworld.
	*/
	private function getStatus ($objSquad)
	{
		$village = $objSquad->getVillage ();
		$location = $objSquad->getCurrentLocation ();

		if ($this->village->getId () != $location->getId ())
		{
			return 'away';
		}
		elseif ($village->getId () != $this->village->getId ())
		{
			return 'rent';
		}
		elseif ($objSquad->isInUnderworld ())
		{
			return 'underworld';
		}

		return 'home';
	}

	private function getStatusText ($objSquad, $status)
	{
		$text = Neuron_Core_Text::__getInstance ();

		switch ($status)
		{
			case 'away':
			case 'rent':
				$village = $objSquad->getVillage ();
				$location = $objSquad->getCurrentLocation ();

				return Neuron_Core_Tools::putIntoText
				(
					$text->get ($status, 'overview', 'squads'),
					array
					(
						'village' => '<a href="javascript:void(0);" onclick="openWindow (\'villageProfile\', {\'village\':'.$village->getId ().'});">'.Neuron_Core_Tools::output_varchar ($village->getName ()).'</a>',
						'location' => '<a href="javascript:void(0);" onclick="openWindow (\'villageProfile\', {\'village\':'.$location->getId ().'});">'.Neuron_Core_Tools::output_varchar ($location->getName ()).'</a>'
					)
				);

			case 'underworld':
				return $text->get ('underworld', 'overview', 'squads');
		}

		return $text->get ('home', 'overview', 'squads');
	}

	/*
		Village units that are not in any regiment, by unit id.
	*/
	private function getFreeUnits ()
	{
		$out = array ();
		foreach ($this->village->getMyUnits () as $unit)
		{
			if ($unit->getSquadlessAmount () > 0)
			{
				$out[$unit->getUnitId ()] = $unit;
			}
		}
		return $out;
	}

	/*
		Equipped items of a regiment unit, by item type.
	*/
	private static function getEquipped ($objUnit)
	{
		$out = array ();
		if ($objUnit)
		{
			foreach ($objUnit->getEquipment () as $item)
			{
				$out[$item->getItemType ()] = $item;
			}
		}
		return $out;
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

		if (isset ($input['created']))
		{
			$page->set ('notice', Neuron_Core_Tools::putIntoText ($text->get ('created'), array (Neuron_Core_Tools::output_varchar ($input['created']))));
		}

		foreach ($this->village->getSquads (false, false, false) as $v)
		{
			$status = $this->getStatus ($v);
			$units = $v->getUnits ();
			$unit = count ($units) > 0 ? reset ($units) : null;

			$equipped = array ();
			foreach (self::getEquipped ($unit) as $type => $item)
			{
				$equipped[$type] = Neuron_Core_Tools::output_varchar ($item->getName (true));
			}

			$page->addListValue
			(
				'squads',
				array
				(
					'id' => $v->getId (),
					'name' => Neuron_Core_Tools::output_varchar ($v->getName ()),
					'amount' => $v->getUnitsAmount (),
					'unit' => $unit ? Neuron_Core_Tools::output_varchar ($unit->getName ()) : null,
					'image' => $unit ? $unit->getImageUrl () : null,
					'equipment' => $equipped,
					'status' => $status,
					'statusText' => $status == 'home' ? null : $this->getStatusText ($v, $status),
					'isMine' => $v->getCurrentLocation()->getId() == $v->getVillage()->getId(),
					'canRecall' => $status == 'away' || $status == 'rent'
				)
			);
		}

		foreach ($this->getFreeUnits () as $unit)
		{
			$page->addListValue
			(
				'free',
				array
				(
					'amount' => $unit->getSquadlessAmount (),
					'name' => Neuron_Core_Tools::output_varchar ($unit->getName ())
				)
			);
		}

		foreach (Dolumar_Players_Equipment::getItemTypes () as $type)
		{
			$page->addListValue ('itemTypes', array ('type' => $type, 'name' => Dolumar_Players_Equipment::getEquipmentName ($type)));
		}

		return $page->parse ('squads/squads.tpl');
	}

	/*
		Create ($objSquad = null) or edit a regiment on one screen:
		unit type (create only), name, number of soldiers, weapon and armour.
	*/
	private function getEditor ($objSquad)
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('squads');
		$text->setSection ('editor');

		$input = $this->getInputData ();
		$errors = array ();
		$notices = array ();

		$isNew = $objSquad === null;

		if ($isNew && isset ($input['action']) && $input['action'] == 'create')
		{
			$objSquad = $this->createSquad ($input, $errors, $notices);
			if ($objSquad)
			{
				$this->setInputData (array ('page' => 'overview', 'created' => $objSquad->getName ()));
				return $this->getOverview ();
			}
		}
		elseif (!$isNew && isset ($input['action']) && $input['action'] == 'save')
		{
			if ($this->saveSquad ($objSquad, $input, $errors, $notices) && count ($errors) == 0)
			{
				array_unshift ($notices, $text->get ('saved'));
			}
		}

		$page = new Neuron_Core_Template ();
		$page->set ('vid', $this->village->getId ());
		$page->set ('isNew', $isNew);

		$free = $this->getFreeUnits ();
		$suggester = $this->getNameSuggester ();
		$takenNames = $this->getTakenNames ();

		if ($isNew)
		{
			// Unit tiles: every unit type with soldiers outside a regiment.
			$selected = isset ($input['squadUnit']) && isset ($free[(int)$input['squadUnit']]) ? (int)$input['squadUnit'] : null;

			foreach ($free as $unitId => $unit)
			{
				if ($selected === null)
				{
					$selected = $unitId;
				}

				$page->addListValue
				(
					'unitTypes',
					array
					(
						'id' => $unitId,
						'name' => Neuron_Core_Tools::output_varchar ($unit->getName ()),
						'image' => $unit->getImageUrl (),
						'free' => $unit->getSquadlessAmount (),
						'suggestion' => $suggester->suggest ($unit->getName (), $takenNames),
						'selected' => $unitId == $selected
					)
				);
			}

			if ($selected === null)
			{
				$page->set ('noUnits', $text->get ('noUnits', 'addSquad'));
				return $page->parse ('squads/editor.tpl');
			}

			$unit = $free[$selected];
			$current = 0;
			$max = $unit->getSquadlessAmount ();
			$size = isset ($input['size']) ? max (0, min ($max, (int)$input['size'])) : $max;
			$name = isset ($input['squadName']) ? $input['squadName'] : $suggester->suggest ($unit->getName (), $takenNames);
			$equipped = array ();
			$canEdit = true;
		}
		else
		{
			$objSquad->reloadUnits ();
			$unitId = $objSquad->getUnitType ();
			$unit = $objSquad->getUnit ($unitId);
			$current = $objSquad->getUnitsAmount ();

			$villageUnit = null;
			foreach ($this->village->getMyUnits () as $v)
			{
				if ($v->getUnitId () == $unitId)
				{
					$villageUnit = $v;
				}
			}

			$max = $current + ($villageUnit ? max (0, $villageUnit->getSquadlessAmount ()) : 0);
			$size = $current;
			$name = $objSquad->getName ();
			$equipped = self::getEquipped ($unit);

			$displayUnit = $unit ? $unit : $villageUnit;

			$status = $this->getStatus ($objSquad);
			$canEdit = $status == 'home' && $objSquad->isIdle ();

			if ($status == 'underworld')
			{
				$errors[] = $text->get ('underworld', 'squad');
			}
			elseif (!$canEdit && $status == 'home')
			{
				$errors[] = $text->get ('notIdle', 'squad');
			}

			$page->set ('squadId', $objSquad->getId ());
			$page->set ('statusText', $this->getStatusText ($objSquad, $status));
			$page->set ('canDisband', $objSquad->isIdle () && $status == 'home');
			$page->set ('confirm', addslashes ($text->get ('conRemove', 'squad')));

			if ($unit)
			{
				$page->set ('stats', Dolumar_Windows_Units::getUnitStatsHTML ($unit));
			}

			$unit = $displayUnit;
		}

		$page->set ('name', Neuron_Core_Tools::output_varchar ($name));
		$page->set ('unitName', $unit ? Neuron_Core_Tools::output_varchar ($unit->getName ()) : '');
		$page->set ('image', $unit ? $unit->getImageUrl () : null);
		$page->set ('size', $size);
		$page->set ('max', $max);
		$page->set ('current', $current);
		$page->set ('canEdit', $canEdit);

		// Equipment: one picker per item type, with the stock for this regiment.
		foreach ($this->village->getEquipment () as $type => $items)
		{
			$options = array ();
			foreach ($items as $item)
			{
				$isEquipped = isset ($equipped[$type]) && $equipped[$type]->getId () == $item->getId ();
				$left = $item->getAvailableAmount () + ($isEquipped ? $current : 0);

				if ($left <= 0 && !$isEquipped)
				{
					continue;
				}

				$options[] = array
				(
					'id' => $item->getId (),
					'name' => Neuron_Core_Tools::output_varchar ($item->getName (true)),
					'left' => $left,
					'selected' => $isEquipped
				);
			}

			$page->addListValue
			(
				'equipment',
				array
				(
					'type' => $type,
					'name' => Neuron_Core_Tools::output_varchar (Dolumar_Players_Equipment::getEquipmentName ($type)),
					'items' => $options
				)
			);
		}

		foreach ($errors as $v)
		{
			$page->addListValue ('errors', $v);
		}

		foreach ($notices as $v)
		{
			$page->addListValue ('notices', $v);
		}

		return $page->parse ('squads/editor.tpl');
	}

	private function getNameSuggester ()
	{
		$text = Neuron_Core_Text::__getInstance ();

		return new Dolumar_Squads_NameSuggester
		(
			$text->get ('suggestedName', 'editor', 'squads', '@@ordinal @@unit'),
			$text->getCurrentLanguage (),
			$text->get ('regiment', 'editor', 'squads', 'Regiment')
		);
	}

	private function getTakenNames ()
	{
		$out = array ();
		foreach ($this->village->getSquads (false, false, true) as $v)
		{
			$out[] = $v->getName ();
		}
		return $out;
	}

	private function createSquad ($input, &$errors, &$notices)
	{
		$text = Neuron_Core_Text::__getInstance ();

		$free = $this->getFreeUnits ();
		$unitId = isset ($input['squadUnit']) ? (int)$input['squadUnit'] : 0;
		$size = isset ($input['size']) ? (int)$input['size'] : 0;
		$name = isset ($input['squadName']) ? trim ($input['squadName']) : '';

		if (!isset ($free[$unitId]))
		{
			$errors[] = $text->get ('noUnits', 'addSquad');
			return null;
		}

		if (!Neuron_Core_Tools::checkInput ($name, 'unitname'))
		{
			$errors[] = $text->get ('squadNameSyntax', 'addSquad');
			return null;
		}

		$size = min ($size, $free[$unitId]->getSquadlessAmount ());
		if ($size < 1)
		{
			$errors[] = $text->get ('noSoldiers', 'editor');
			return null;
		}

		$objSquad = $this->village->addSquad ($name, $unitId);
		$objSquad->addUnits ($free[$unitId], $size);

		$error = $objSquad->getError ();
		if (!empty ($error))
		{
			$errors[] = $text->get ($error, 'addUnits');
		}

		$objSquad->reloadUnits ();
		$this->applyEquipment ($objSquad, $input, $errors);

		reloadEverything ();
		$this->village->reloadUnits ();

		return $objSquad;
	}

	/*
		Apply the editor form to an existing regiment. Returns false when nothing could be changed.
	*/
	private function saveSquad ($objSquad, $input, &$errors, &$notices)
	{
		$text = Neuron_Core_Text::__getInstance ();

		if (!$objSquad->isIdle () || $this->getStatus ($objSquad) != 'home')
		{
			$errors[] = $text->get ('notIdle', 'squad');
			return false;
		}

		// Name
		if (isset ($input['squadName']) && trim ($input['squadName']) != $objSquad->getName ())
		{
			$name = trim ($input['squadName']);
			if (Neuron_Core_Tools::checkInput ($name, 'unitname'))
			{
				$objSquad->rename ($name);
			}
			else
			{
				$errors[] = $text->get ('squadNameSyntax', 'addSquad');
			}
		}

		// Items taken off because there weren't enough for the new soldiers: don't try them again.
		$skip = array ();

		// Soldiers: the form holds the size the player wants, not a difference.
		if (isset ($input['size']))
		{
			$unitId = $objSquad->getUnitType ();
			$current = $objSquad->getUnitsAmount ();
			$target = max (0, (int)$input['size']);

			if ($target > $current)
			{
				foreach ($this->village->getMyUnits () as $v)
				{
					if ($v->getUnitId () == $unitId)
					{
						$add = min ($target - $current, $v->getSquadlessAmount ());
						if ($add > 0)
						{
							$objSquad->addUnits ($v, $add);

							$error = $objSquad->getError ();
							if (!empty ($error))
							{
								$errors[] = $text->get ($error, 'addUnits');
							}

							foreach ($objSquad->getUnequippedItems () as $item)
							{
								$skip[] = $item->getId ();
								$notices[] = Neuron_Core_Tools::putIntoText ($text->get ('unequipped'), array (Neuron_Core_Tools::output_varchar ($item->getName (true))));
							}
						}
					}
				}
			}
			elseif ($target < $current)
			{
				$unit = $objSquad->getUnit ($unitId);
				if ($unit)
				{
					$objSquad->removeUnits ($unit, $current - $target);
				}
			}

			$objSquad->reloadUnits ();
		}

		$this->applyEquipment ($objSquad, $input, $errors, $skip);

		reloadEverything ();
		$objSquad->reloadUnits ();
		$this->village->reloadUnits ();

		return true;
	}

	/*
		Form fields equip_<type> hold an item id, or 0 for none.
	*/
	private function applyEquipment ($objSquad, $input, &$errors, $skip = array ())
	{
		$text = Neuron_Core_Text::__getInstance ();

		$unit = $objSquad->getUnit ($objSquad->getUnitType ());
		if (!$unit)
		{
			return;
		}

		$equipped = self::getEquipped ($unit);

		foreach ($this->village->getEquipment () as $type => $items)
		{
			$key = 'equip_' . $type;
			if (!isset ($input[$key]))
			{
				continue;
			}

			if ($input[$key] == '0')
			{
				if (isset ($equipped[$type]))
				{
					$objSquad->unequipItem ($unit, Dolumar_Players_Equipment::getItemTypeId_static ($type));
				}
				continue;
			}

			if (in_array ($input[$key], $skip))
			{
				continue;
			}

			foreach ($items as $item)
			{
				if ($input[$key] == $item->getId ())
				{
					if (!$objSquad->addEquipment ($unit, $item))
					{
						$errors[] = $text->get ($objSquad->getError (), 'squad') .
							' (' . Neuron_Core_Tools::output_varchar ($item->getName (true)) . ')';
					}
					break;
				}
			}
		}
	}
}
?>
