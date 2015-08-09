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

class Dolumar_Buildings_TownCenter extends Dolumar_Buildings_Building
{
	public function getCapacity ()
	{
		$o = array ();
		
		$o['grain'] = 10000;
		
		$o['wood'] = 5000;
		$o['stone'] = 5000;
		$o['iron'] = 5000;
		
		$o['gold'] = 2000;
		$o['gems'] = 50;
		
		return $o;
	}
	
	public function getIncome () 
	{
		$o['grain'] = 45 * GAME_SPEED_RESOURCES;
		$o['wood'] = 30 * GAME_SPEED_RESOURCES;
		$o['stone'] = 30 * GAME_SPEED_RESOURCES;
		$o['iron'] = 30 * GAME_SPEED_RESOURCES;
		
		return $o;
	}
	
	public function getConstructionTime ($level)
	{
		return 0;
	}
	
	public function getMapColor ()
	{
		return array (255, 0, 0);
	}

	public function checkBuildLocation ($village, $x, $y, $bradius = MAXBUILDINGRADIUS)
	{		
		$db = Neuron_DB_Database::getInstance ();
		
		$x = floor ($x);
		$y = floor ($y);
		
		$distance = MAXBUILDINGRADIUS * MAXBUILDINGRADIUS;
		
		/*
		$l = $db->query
		("
			SELECT
				1
			FROM 
				(SELECT 1) t
			WHERE EXISTS
			(
				SELECT
					*
				FROM
					map_buildings
				WHERE
					((xas - $x) * (xas - $x)) + ((yas - $y) * (yas - $y)) < ($distance)
					AND buildingType = 1 
					AND destroyDate = 0
			)
		");
		*/
		
		$query = "
			SELECT
				1
			FROM 
				(SELECT 1) t
			WHERE EXISTS
			(
				SELECT
					xas
				FROM
					map_buildings
				WHERE
					xas BETWEEN ".($x - $bradius)." AND ".($x + $bradius)."
					AND yas BETWEEN ".($y - $bradius)." AND ".($y + $bradius)."
					AND (buildingType = 1 OR buildingType = 3) 
					AND destroyDate = 0
			)
		";
		
		$l = $db->query	($query);
		
		if (count ($l) > 0)
		{
			return array (false, 'minimalRange');
		}
		
		$location = Dolumar_Map_Location::getLocation ($x, $y);
		
		if ($location->canBuildBuilding ())
		{
			return array (true, array ($x, $y));
		}
		
		else
		{
			return array (false, 'minimalRange');
		}
	}
	
	public function build ($village, $x, $y, $owner = null, $race = null)
	{
		$db = Neuron_Core_Database::__getInstance ();
		$text = Neuron_Core_Text::__getInstance ();
		
		// We'll have to create a village
		if ($village != null)
		{
			$owner = $village->getOwner ();
			$race = $village->getRace ();
		}
		
		if (!empty ($owner))
		{
			// Add new Village
			
			// Load start resources
			$data = $this->getCapacity ();
			
			//$name = $owner->getNickname () . $text->get ('village', 'main', 'main');
			$name = Neuron_Core_Tools::putIntoText
			(
				$text->get ('playervillage', 'main', 'main'),
				array
				(
					'player' => $owner->getNickname ()
				)
			);
			
			// Count the existing villages
			$villages = count ($owner->getVillages ());
			if ($villages >= 1)
			{
				$name .= ' ' . ($villages + 1);
			}
			
			// Insert other datas.
			$data['vname'] = $name;
			$data['plid'] = $owner->getId ();
			$data['race'] = $race->getId ();
			
			$vid = $db->insert
			(
				'villages',
				$data
			);
			
			$village = Dolumar_Players_Village::getVillage ($vid);
			$building = parent::build ($village, $x, $y, false);
			
			$owner->reloadVillages ();
			
			return $building;
			//return array (true, $vid);
		}
		
		else
		{
			return false;
			return array (false, null);
		}
	}
	
	protected function getCustomContent ($input)
	{
		$db = Neuron_Core_Database::__getInstance ();
		$text = Neuron_Core_Text::__getInstance ();
		
		$text->setFile ('buildings');
		$text->setSection ('townCenter');

		$page = new Neuron_Core_Template ();

		// Just to make sure we're not displaying old rune scouts.
		$this->getVillage ()->resources->getRunes ();

		// Change village name
		if (isset ($input['villageName']))
		{
			$input['villageName'] = strip_tags ($input['villageName']);
			
			if (!$this->getVillage()->setName ($input['villageName']))
			{
				$page->set ('changename_err', $text->get ($this->getVillage()->getError ()));
			}
			else
			{
				reloadEverything ();
			}
		}

		// Let's check if we are already searching for runes
		$runeCheck = $db->select
		(
			'villages_scouting',
			array ('count(scoutId)'),
			"vid = '".$this->getVillage()->getId()."'"
		);

		$alreadyScouting = $runeCheck[0]['count(scoutId)'] > 0;

		if (isset ($input['do']))
		{
			if ($input['do'] == 'scout')
			{
				$this->hideGeneralOptions ();
				$this->hideTechnologyUpgrades ();

				$currentPage = isset ($input['pageid']) ? $input['pageid'] : 1;

				if ($currentPage < 1)
				{
					$currentPage = 1;
				}

				Neuron_Core_Tools::splitInPages
				(
					$page,
					($currentPage * 4) + 8,
					$currentPage,
					4,
					4,
					array (
						'do' => 'scout'
					),
					null,
					'pageid'
				);

				if ($alreadyScouting)
				{
					$page->set ('scoutResults', $text->get ('alreadyScouting'));
					$page->set ('scoutResult_isGood', false);
				}
				else if (isset ($input['runes']) && is_numeric ($input['runes']))
				{
					$runes = intval ($input['runes']);

					// Let's start scouting
					$village = $this->getVillage ();
					//$cost = $village->getScoutLandsCost ($runes);

					if ($village->scout ($runes))
					{
						$page->set ('scoutResults', $text->get ('doneScouting'));
						$page->set ('scoutResult_isGood', true);
					}

					else
					{
						$page->set ('scoutResults', $text->get ('searchNoRunes'));
						$page->set ('scoutResult_isGood', false);
					}
				}
				else
				{
					$scoutoptions = array ();

					$start = ($currentPage - 1) * 4 + 1;
					$end = $start + 4;

					for ($i = $start; $i < $end; $i ++)
					{
						$duration = $this->getVillage()->getScoutLandsDuration ($i);
						$cost = $this->resourceToText($this->getVillage()->getScoutLandsCost ($i));
						$duration = Neuron_Core_Tools::getDuration ($duration);

						$scoutoptions[] = array (
							'runes' => $i,
							'scoutDuration' => $duration,
							'scoutCost' => $cost
						);
					}

					$page->set ('scoutoptions', $scoutoptions);
				}

				return $page->parse ('buildings/townCenter_scoutRunes.phpt');
			}

			else if ($input['do'] == 'explore')
			{
				/*
				 * TODO ;-)
				 *
				$village = $this->getVillage ();
				$village->lookForNPCs ();
				*/
			}
		}

		$page->set ('vid', $this->getVillage()->getId());

		// Search for new runes
		$page->set ('searchRunes', $text->get ('searchRunes'));
		$page->set ('scoutLands', $text->get ('scoutLands'));
		$page->set ('scoutCost', $text->get ('scoutCost'));


		// Change name
		$page->set ('changeName', $text->get ('changeName'));
		$page->set ('villageName', $text->get ('villageName'));
		$page->set ('change', $text->get ('change'));
		$page->set ('villageName_value', Neuron_Core_Tools::output_varchar ($this->getVillage()->getName()));
		
		$page->set ('overview', $text->getClickTo ($text->get ('overview')));
		$page->set ('techniques', $text->getClickTo ($text->get ('techniques')));
		
		$page->set ('toScout', $text->getClickTo ($text->get ('toScout')));
		
		return $page->parse ('buildings/townCenter.phpt');
	}

	public function canBuildBuilding (Dolumar_Players_Village $village)
	{
		//return $village->buildings->getBuildingLevel ($this) > 5;
		return false;
	}
	
	public function doDestructBuilding ($evenTowncenter = false, $date = NOW, $log = true)
	{
		if ($evenTowncenter)
		{
			parent::doDestructBuilding ($evenTowncenter);
		}
	}

	// Destruct building: not possible for town center!
	public function destructBuilding ($date = NOW, $log = true)
	{
		$text = Neuron_Core_Text::__getInstance ();
		$page = new Neuron_Core_Template ();
		$page->set ('done', $text->get ('destruct', 'townCenter', 'buildings'));
		return $page->parse ('buildings/general_destr.tpl');
	}

	public function isUpgradeable ()
	{
		return true;
	}

	public function getGuards ()
	{
		$a = array ();

		$a[0] = Dolumar_Units_Unit::getUnitFromName ('Guards', $this->getVillage ()->getRace (), $this->getVillage ());
		$a[0]->addAmount (10, 10, 10);

		return $a;
	}
	
	private function getRuneCostTowncenterUpgrade ($curLevel)
	{
		return ($curLevel + 1) * 2;
	}
	
	public function getBuildingCost ($village)
	{
		return array ();
	}
	
	public function getUpgradeCost ($village, $showRandom = false)
	{
		$out = array ();
		$out['runeId'] = isset ($this->upgradeRune) && !$showRandom ? $this->upgradeRune : 'random';
		$out['runeAmount'] = $this->getRuneCostTowncenterUpgrade ($this->getLevel ());
		
		// =1000*MACHT(10;(A20)/5)
		$res = 1000 * ceil (pow (10, $this->getLevel () / 5));
		
		$out['grain'] = $res;
		$out['wood'] = $res;
		$out['stone'] = $res;
		
		return $out;
	}
	
	/*
	public function getUsedResources ($includeUpgradeRunes = false)
	{
		$r['runeId'] = 'random';
		$r['runeAmount'] = 0;
		
		if ($includeUpgradeRunes && isset ($r['runeAmount']))
		{
			for ($i = 1; $i < $this->getLevel (); $i ++)
			{
				$r['runeAmount'] += $this->getRuneCostTowncenterUpgrade ($i);
			}
		}
		
		return $r;
	}
	*/
	
	public function getUpgradeTime ($village)
	{
		return ceil ((60 * 60 * 24 * $this->getLevel ()) / GAME_SPEED_RESOURCES);
	}
	
	public function isMoveable ()
	{
		return false;
	}
	
	public function isDestructable ()
	{
		return false;
	}
}
?>
