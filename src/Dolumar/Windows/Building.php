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

class Dolumar_Windows_Building extends Neuron_GameServer_Windows_Window
{
	private $building;

	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$db = Neuron_Core_Database::__getInstance ();
		
		// Window settings
		$this->setSize ('360px', '400px');
		
		$o = $this->getRequestData ();
		
		$l = $db->getDataFromQuery ($db->customQuery
		("
			SELECT
				map_buildings.*, villages.race
			FROM
				map_buildings
			LEFT JOIN
				villages ON map_buildings.village = villages.vid
			WHERE
				map_buildings.bid = '".$db->makeSafe ($o['bid'])."'
				AND (destroyDate = 0 OR destroyDate > ".NOW.")
		"));
		
		if (count ($l) == 1)
		{
			$race = Dolumar_Races_Race::getRace ($l[0]['race']);
			
			$this->building = Dolumar_Buildings_Building::getBuilding ($l[0]['buildingType'], $race, $l[0]['xas'], $l[0]['yas']);

			if ($l[0]['village'])
			{
				$this->building->setVillage (Dolumar_Players_Village::getVillage ($l[0]['village']));
			}

			$this->building->setWindow ($this);
			$this->building->setData ($l[0]['bid'], $l[0]);
			
			$this->setTitle ($this->getTitle ());
		}
		
		else 
		{
			$this->building = false;
			$this->setTitle ('Oh-Ow...');
		}
	}
	
	protected function getTitle ()
	{
		$me = Neuron_GameServer::getPlayer ();
		
		if ($me && $this->building->getOwner ()->getId () == $me->getId ())
		{
			return
			(
				$this->building->getName (false, true) .
				' (' . Neuron_Core_Tools::output_varchar ($this->building->getVillage ()->getName ()).')'
			);
		}
		else
		{
			return
			(
				$this->building->getName (false) .
				' (' . Neuron_Core_Tools::output_varchar ($this->building->getVillage ()->getName ()).')'
			);
		}
	}
	
	public function getContent ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		
		if ($this->building)
		{
			$input = $this->getInputData ();
			$me = Neuron_GameServer::getPlayer ();
			
			if ($me && $this->building->getOwner ()->getId () == $me->getId () && $this->building->getVillage ()->isActive ())
			{
				if (
					isset ($input['action'])
					&& $input['action'] == 'destruct'
					&& isset ($input['key'])
					&& Neuron_Core_Tools::checkConfirmLink ($input['key'])
				)
				{
					// Reload
					reloadEverything ();
					
					$loc = $this->building->getLocation ();
					//$this->reloadLocation ($loc[0], $loc[1]);
					
					return $this->building->destructBuilding ();
				}

				else
				{
					// Make sure you are not in vacation mode.
					if ($this->building->getVillage()->getOwner()->inVacationMode ())
					{
						return '<p class="false">'.$text->get ('vacationMode', 'main', 'main').'</p>';
					}
					
					return $this->building->getMyContent ($input);
				}
			}
			
			else 
			{
				return $this->building->getGeneralContent (false);
			}
		}
		
		else
		{
			return '<p class="false">'.$text->get ('notFound', 'building', 'building').'</p>';
		}
	}
	
	public function processInput ()
	{
		if ($this->building)
		{
			$me = Neuron_GameServer::getPlayer ();
			if ($me && $this->building->getOwner()->getId() == $me->getId())
			{
				$input = $this->getInputData ();
				
				if (!isset ($input['page']))
				{
					$input['page'] = 'home';
				}
				
				if ($input['page'] == 'general')
				{
					$this->updateContent ($this->building->getGeneralContent (true));
				}
				
				elseif ($input['page'] == 'upgrade' && $this->building->isUpgradeable ())
				{
					$okay = isset ($input['upgrade']) && $input['upgrade'] == 'confirm';
					$this->updateContent ($this->building->getUpgradeContent ($okay));
					
					// Reload title
					$this->updateTitle ($this->getTitle ());
				}

				elseif ($input['page'] == 'technology' && isset ($input['technology']))
				{
					$this->updateContent ($this->building->getUpgradeTechnology ($input));
				}
				
				elseif ($input['page'] == 'home')
				{
					$this->updateContent ();
				}
			}
		}
	}
}

?>
