<?php
class Dolumar_Buildings_Outpost extends Dolumar_Buildings_TownCenter
{
	public function canBuildBuilding (Dolumar_Players_Village $village)
	{
		$tc = $village->buildings->getTownCenter ();
		$villages = count ($village->getOwner ()->getVillages ());
		
		if (!$tc)
		{
			return false;
		}
		
		$level = $tc->getLevel ();
		
		$max = 1;
		
		if ($level >= 10)
		{
			$max ++;
		}
		
		if ($level >= 15)
		{
			$max ++;
		}
		
		if ($level >= 18)
		{
			$max ++;
		}
		
		if ($level >= 20)
		{
			$max += ($level - 19);
		}
		
		return $villages < $max;
	}

	public function isUpgradeable ()
	{
		return false;
	}
	
	public function getMapColor ()
	{
		return array (200, 0, 0);
	}
	
	/*
		Buildinjg costs
	*/
	public function getBuildingCost ($village)
	{
		$towncenter = $village->buildings->getTownCenter ();
		
		//$vils = count ($village->getOwner ()->getVillages ());
		
		if (!$towncenter)
		{
			return array ();
		}
		
		$amount = $towncenter->getLevel ();
		
		$resources = 500000;
		
		$out = array
		(
			'runeAmount' => $amount,
			
			'gold' => $resources,
			'wood' => $resources,
			'stone' => $resources
		);
		
		return $out;
		
		//return array ();
	}
	
	public function getScore ()
	{
		return 2000;
	}
}
?>
