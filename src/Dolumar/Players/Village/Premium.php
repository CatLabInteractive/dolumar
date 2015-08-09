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

class Dolumar_Players_Village_Premium
{
	private $village;
	private $error;

	public function __construct ($objMember)
	{
		$this->village = $objMember;
	}
	
	/*
		Add a queue action
	*/
	public function addQueueAction ($sAction, $aData)
	{
		$owner = $this->village->getOwner ();
	
		if (!$owner->isPremium ())
		{
			$this->error = 'not_premium';
			return false;
		}
	
		$db = Neuron_DB_Database::__getInstance ();
		
		// Check for maximum
		$chk = $db->query
		("
			SELECT
				COUNT(pq_id) AS aantal
			FROM
				premium_queue
			WHERE
				pq_vid = {$this->village->getId()}
		");
		
		if (count ($chk) == 1 && $chk[0]['aantal'] < 3)
		{
			$result = $db->query
			("
				INSERT INTO
					premium_queue
				SET
					pq_vid = {$this->village->getId()},
					pq_action = '{$db->escape($sAction)}',
					pq_data = '".$db->escape (json_encode ($aData))."',
					pq_date = FROM_UNIXTIME(".time().")
			");
			
			return $result > 0;
		}
		else
		{
			$this->error = 'queuelimit';
			return false;
		}
	}
	
	/*
		Executed a queued event.
		Return TRUE if the processes has been completed.
		In this case the que will be removed.
	*/
	public function executeQueuedAction ($sAction, $aData)
	{
	
		switch ($sAction)
		{
			case 'build':
				return $this->executeBuildQueue ($aData);
			break;
			
			case 'upgrade':
				return $this->executeUpgradeQueue ($aData);
			break;
			
			case 'training':
				return $this->executeTrainQueue ($aData);
			break;
		}
	}
	
	/*
		Handlers for the various actions
	*/
	private function executeBuildQueue ($data)
	{
		$building = Dolumar_Buildings_Building::getBuilding ($data['building'], $this->village->getRace ());
		
		$building->setVillage ($this->village);
		
		$x = $data['x'];
		$y = $data['y'];
		
		if (isset ($data['rune']))
		{
			$building->setChosenRune ($this->village, $data['rune']);
		}
		

		if (!$this->village->readyToBuild ())
		{
			echo "Cannot build building: all workers are working.\n";
		}

		elseif (!$building->canBuildBuilding ($this->village))
		{			
			echo "Cannot build building: not possible.\n";
		}
		
		/*
			Limit the amount of buildings
		*/
		elseif (!$building->checkBuildingLevels ($this->village))
		{
			echo "Cannot build building: building levels not working.\n";
		}
		elseif ($this->village->readyToBuild ())
		{
			$chk = $building->checkBuildLocation ($this->village, $x, $y);
			
			if ($chk[0])
			{
				$x = $chk[1][0];
				$y = $chk[1][1];
			
				$res = $building->getBuildingCost ($this->village);
			
				// Take resources & runes
				if 
				(
					$this->village->resources->takeResourcesAndRunes ($res)
				)
				{	
					$building = $building->build ($this->village, $x, $y);
				
					// Reload buildings & runes
					$this->village->buildings->reloadBuildings ();
					$this->village->buildings->reloadBuildingLevels ();
					$this->village->onBuild ($building);
					
					echo "Building built!\n";
					
					return true;
				}
				else
				{
					echo "Cannot build building: not enough resources.\n";
				}
			}
			else
			{
				echo "Cannot build building: location mismatch.\n";
				return true;
			}
		}
		
		return false;
	}
	
	private function executeUpgradeQueue ($aData)
	{
		$building = Dolumar_Buildings_Building::getFromId ($aData['building']);
		
		$building->setVillage ($this->village);
		
		if (isset ($aData['rune']))
		{
			$building->setChosenRune ($building->getVillage (), $aData['rune']);
		}
		
		if ($building)
		{
			if 
			(
				$building->isUpgradeable () && 
				$building->getLevel () < $aData['level']
			)
			{
				// Try to upgrade building
				if ($building->doUpgradeBuilding ())
				{
					echo "Upgrading building.\n";
					return true;
				}
				else
				{
					echo "Not upgrading building: ".$building->getBuildingError ().".\n";
					return false;
				}
			}
			else
			{
				return true;
			}
		}
		else
		{
			return true;
		}
	}
	
	private function executeTrainQueue ($data)
	{
		$unit = Dolumar_Units_Unit::getUnitFromId 
		(
			$data['unit'],
			$this->village->getRace (),
			$this->village
		);
		
		$amount = $data['amount'];
		$building = $data['building'];
		
		$building = Dolumar_Buildings_Building::getFromId ($building);
		$building->setVillage ($this->village);
		
		if ($building && $unit)
		{
			if ($building->doTrainUnits ($unit, $amount))
			{
				echo "Training ".$unit->getName ()."\n";
				return true;
			}
			else
			{
				echo "No training units: ".$building->getTrainError ()."\n";
				return false;
			}
		}
		else
		{
			return true;
		}
	}
	
	/*
		Return the error
	*/
	public function getError ($translate = false)
	{
		$out = $this->error;
		
		if ($translate)
		{
			$text = Neuron_Core_Text::__getInstance ();
			$out = $text->get ($out, 'queue', 'village');
		}
		
		return $out;
	}
	
	public function __destruct ()
	{
		unset ($this->village);
		unset ($this->error);
	}
}
?>
