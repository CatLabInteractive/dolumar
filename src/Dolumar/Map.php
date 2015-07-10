<?php
class Dolumar_Map implements Neuron_GameServer_Interfaces_Map
{
	public function getObjects ($squarePoints, $radius)
	{
		$profiler = Neuron_Profiler_Profiler::getInstance ();
	
		// Let's replace this :)
		$out = array ();
		
		$buildingSQL = Dolumar_Map_Map::getBuildingsFromLocations ($squarePoints, max ($radius, 3));
	
		$objects = array ();
		foreach ($buildingSQL as $buildingV)
		{
			$profiler->start ('Initializing building');
		
			$profiler->start ('Fetching building race object');
			$race = Dolumar_Races_Race::getRace ($buildingV['race']);
			$profiler->stop ();
		
			$profiler->start ('Fetching building object');
			
			$b = Dolumar_Buildings_Building::getBuilding 
			(
				$buildingV['buildingType'], 
				$race, 
				$buildingV['xas'], $buildingV['yas']
			);
			
			$village = Dolumar_Players_Village::getVillage ($buildingV['village']);
			
			$b->setVillage ($village);
			
			$profiler->stop ();
		
			$profiler->start ('Setting data');
			$b->setData ($buildingV['bid'], $buildingV);

			if ($buildingV['destroyDate'] > 0 && $buildingV['destroyDate'] < NOW)
			{
				$b->setDestroyed (true);
			}
			
			$profiler->stop ();
		
			$profiler->start ('Assigning building to array');
			//$buildings[floor ($buildingV['xas'])][floor ($buildingV['yas'])][] = $b;
			
			$objects[] = new Dolumar_Map_Object ($b);
			
			$profiler->stop ();
		
			$profiler->stop ();
		}
		
		return $objects;
	}
	
	public function getLocation ($x, $y, $hasObjects)
	{
		return Dolumar_Map_Location::getLocation ($x, $y, $hasObjects);
	}
}
?>
