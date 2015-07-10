<?php
class Dolumar_Players_Village_Buildings
{
	private $buildings = null;
	private $buildingLevels = null;
	
	private $buildingCount = null;
	private $buildingCount_all = null;
	
	private $buildingLevelSum = null;
	
	private $townCenter = null;
	
	private $objMember;
	
	public function __construct ($objMember)
	{
		$this->objMember = $objMember;
		
		// Make sure the battles are processed.
		$objMember->processBattles ();
	}
	
	public function getBuilding ($id)
	{
		$this->loadBuildings ();
		if (isset ($this->buildings[$id]))
		{
			return $this->buildings[$id];
		}
		else
		{
			return false;
		}
	}
	
	public function isBuildingOnLocation ($lx, $ly)
	{
		foreach ($this->getBuildings () as $v)
		{
			list ($x, $y) = $v->getLocation ();
			if ($lx == $x && $ly == $y)
			{
				return true;
			}
		}
		return false;
	}
	
	public function getBuildings ()
	{
		$this->loadBuildings ();
		return $this->buildings;
	}
	
	private function loadBuildings ()
	{
		if (!isset ($this->buildings))
		{
			$profiler = Neuron_Profiler_Profiler::__getInstance ();
			
			$profiler->start ('Loading buildings');
			
			$db = Neuron_Core_Database::__getInstance ();
			
			$buildingsDBData = $db->getDataFromQuery ($db->customQuery
			("
				SELECT
					*
				FROM
					map_buildings
				WHERE
					village = '".$this->objMember->getId ()."'
					AND (destroyDate = 0 OR destroyDate > ".NOW.")
			"));
			
			$this->buildings = array ();
			$this->buildingCount = array ();
			$this->buildingCount_all = array ();
			$this->buildingLevelSum = array ();
			
			foreach ($buildingsDBData as $v)
			{
				$b = Dolumar_Buildings_Building::getBuilding 
				(
					$v['buildingType'], 
					$this->objMember->getRace (), 
					$v['xas'], $v['yas']
				);
				
				$b->setData ($v['bid'], $v);
				$b->setVillage ($this->objMember);
				
				$this->buildings[$v['bid']] = $b;

				if ($b->isFinishedBuilding ())
				{
					// Count the buildings
					if (!isset ($this->buildingCount[$v['buildingType']]))
					{
						$this->buildingCount[$v['buildingType']] = 0;
					}
					$this->buildingCount[$v['buildingType']] ++;
					
					if (!isset ($this->buildingLevelSum[$v['buildingType']]))
					{
						$this->buildingLevelSum[$v['buildingType']] = 0;
					}
					$this->buildingLevelSum[$v['buildingType']] += $v['bLevel'];
				}
				
				// Count the buildings
				if (!isset ($this->buildingCount_all[$v['buildingType']]))
				{
					$this->buildingCount_all[$v['buildingType']] = 0;
				}
				
				$this->buildingCount_all[$v['buildingType']] ++;
			}
			
			$profiler->stop ();
		}
	}
	
	public function getTownCenter ()
	{
		if (!isset ($this->townCenter))
		{
			$this->loadBuildings ();
			
			$found = false;
			
			foreach ($this->buildings as $v)
			{
				$building = $v->getBuildingId ();
				if ($building == 1 || $building == 3)
				{
					$found = $v;
					break;
				}
			}
			
			$this->townCenter = $found;
		}
		
		return $this->townCenter;
	}
	
	/*
		TODO: Cache these results.
	*/
	public function getTownCenterLocation ()
	{
		$tc = $this->getTownCenter ();
		if ($tc)
		{
			return $tc->getLocation ();
		}
		else
		{
			return array (0, 0);
		}
	}
	
	/*
	public function getTowncenter ()
	{
			$db = Neuron_Core_Database::__getInstance ();
			
			$buildingsDBData = $db->getDataFromQuery ($db->customQuery
			("
				SELECT
					*
				FROM
					map_buildings
				WHERE
					village = '".$this->objMember->getId ()."'
					AND buildingType = '1'
			"));
			
			$tc = false;
			
			foreach ($buildingsDBData as $v)
			{
				$tc = Dolumar_Buildings_Building::getBuilding 
				(
					$v['buildingType'], 
					$this->getRace (), 
					$v['xas'], $v['yas']
				);
				
				$tc->setData ($v['bid'], $v);
			}
			
			return $tc;
	}
	*/
	
	public function reloadBuildings () 
	{
		$this->buildings = null;  
	}
	
	private function loadBuildingLevels ()
	{
		if ($this->buildingLevels === null)
		{
			$db = Neuron_Core_Database::__getInstance ();
			
			$this->buildingLevels = array ();
			
			$l = $db->select
			(
				'villages_blevel',
				array ('bid', 'lvl'),
				"vid = '".$this->objMember->getId ()."'"
			);
			
			foreach ($l as $v)
			{
				$this->buildingLevels[$v['bid']] = $v['lvl'];
			}
		}
	}
	
	public function reloadBuildingLevels () 
	{ 
		$this->buildingLevels = null;  
	}
	
	public function increaseBuildingLevel ($building, $setToMax = false)
	{
		$this->loadBuildingLevels ();
		
		$db = Neuron_Core_Database::__getInstance ();
		$id = $building->getBuildingId ();

		if ($setToMax)
		{
			$u = $db->update
			(
				'villages_blevel',
				array
				(
					'lvl' => $setToMax
				),
				"vid = '".$this->objMember->getId ()."' AND bid = '$id' AND lvl < '".$setToMax."'"
			);
		}
		else
		{
			$u = $db->update
			(
				'villages_blevel',
				array
				(
					'lvl' => '++'
				),
				"vid = '".$this->objMember->getId ()."' AND bid = '$id'"
			);
		}
		
		if ($u == 0)
		{

			$chk = $db->select
			(
				'villages_blevel',
				array ('lvl'),
				"vid = '".$this->objMember->getId ()."' AND bid = '$id'"
			);

			if (count ($chk) == 0)
			{
				$db->insert
				(
					'villages_blevel',
					array
					(
						'vid' => $this->objMember->getId (),
						'bid' => $id,
						'lvl' => 1
					)
				);
			}
		}
		
		if (isset ($this->buildingLevels[$id]))
		{
			$this->buildingLevels[$id] ++;
		}
		
		$this->objMember->recalculateNetworth ();

		// Add log
		$objLogs = Dolumar_Players_Logs::__getInstance ();
		$objLogs->addUpgradeBuilding ($this->objMember, $building);
	}
	
	public function getBuildingLevel ($id)
	{
		if (is_object ($id))
		{
			$id = $id->getBuildingId ();
		}
		
		$this->loadBuildingLevels ();
		
		if (isset ($this->buildingLevels[$id]))
		{
			return $this->buildingLevels[$id];
		}
		
		else 
		{
			// Initiate level
			$db = Neuron_Core_Database::__getInstance ();
			
			$db->insert
			(
				'villages_blevel',
				array
				(
					'vid' => $this->objMember->getId (),
					'bid' => $id,
					'lvl' => 1
				)
			);
			
			$this->buildingLevels[$id] = 1;
			
			return 1;
		}
	}
	
	public function hasBuilding ($building, $includeConstructing = true)
	{
		return $this->getBuildingAmount ($building, $includeConstructing) > 0;
	}
	
	public function getBuildingAmount ($id, $includeConstructing = true)
	{
		if (is_object ($id))
		{
			$id = $id->getBuildingId ();
		}

		$this->loadBuildings ();
		
		if (!$includeConstructing)
		{
			if (isset ($this->buildingCount[$id]))
			{
				return $this->buildingCount[$id];
			}
			else 
			{
				return 0;
			}
		}
		else
		{
			if (isset ($this->buildingCount_all[$id]))
			{
				return $this->buildingCount_all[$id];
			}
			else 
			{
				return 0;
			}
		}
	}

	public function getAllBuildingAmounts ($includeConstructing = true)
	{
		$this->loadBuildings ();

		$buildingnames = Dolumar_Buildings_Building::getAllBuildings ();

		$out = array ();
		foreach ($this->buildingCount as $k => $v)
		{
			$key = isset ($buildingnames[$k]) ? $buildingnames[$k] : 'unknown';
			$out[$key] = $v;
		}
		return $out;
	}
	
	/*
		Sum the levels of one specific building type;
	*/
	public function getBuildingLevelSum ($id)
	{
		if (is_object ($id))
		{
			$id = $id->getBuildingId ();
		}

		$this->loadBuildings ();
		
		if (!isset ($this->buildingLevelSum[$id]))
		{
			return 0;
		}
		
		return $this->buildingLevelSum[$id];
	}
	
	/*
		Calculate the amount of used runes for this 
		type of building.

		USE WITH CARE! This is a rather server intensive function
	*/
	public function getBuildingsRuneAmount ($id)
	{
		$this->loadBuildings ();
		
		$runes = 0;
		foreach ($this->buildings as $v)
		{
			if ($v->getBuildingId () == $id || $v instanceof $id)
			{
				$res = $v->getBuildingCost ($this->objMember);
				$runes += isset ($res['runeAmount']) ? $res['runeAmount'] : 0;
				
				$level = $v->getLevel ();
				
				for ($i = 1; $i < $level; $i ++)
				{
					$res = $v->getUpgradeCost ($this->objMember, ($i + 1));
					$runes += isset ($res['runeAmount']) ? $res['runeAmount'] : 0;
				}
			}
		}
		
		return $runes;
	}
	
	public function getBuildingBuildings ()
	{
		// Load unfinished buildings
		$db = Neuron_Core_Database::__getInstance ();
		
		$buildings = $db->select
		(
			'map_buildings',
			array ('*'),
			"(readyDate > '".time()."' OR lastUpgradeDate > '".time()."') AND destroyDate = '0' AND village = '".$this->objMember->getId ()."'"
		);

		return $buildings;
	}
	
	public function burnTheVillage ()
	{
		$buildings = $this->getBuildings ();
		
		foreach ($buildings as $v)
		{
			$v->doDestructBuilding (true);
		}
	}
	
	public function __destruct ()
	{
		if (isset ($this->buildings))
		{
			foreach ($this->buildings as $v)
			{
				//$v->__destruct ();
				unset ($v);
			}
		}
	
		unset ($this->buildings);
		unset ($this->buildingLevels);
	
		unset ($this->buildingCount);
		unset ($this->buildingCount_all);
	
		unset ($this->townCenter);
	
		unset ($this->objMember);
	}
}
?>
