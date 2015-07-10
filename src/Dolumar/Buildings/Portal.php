<?php
class Dolumar_Buildings_Portal 
	extends Dolumar_Buildings_Building
{
	public function canBuildBuilding (Dolumar_Players_Village $village)
	{
		return false;
	}
	
	public function getUsedAssets ($includeUpgradeRunes = false)
	{
		return array
		(
			'runes' => array (),
			'resources' => array ()
		);
	}
	
	public function isUpgradeable ()
	{
		return false;
	}
	
	public function isDestructable ()
	{
		return false;
	}
	
	public function getMyContent ($input, $original = false)
	{
		if ($original)
		{
			return parent::getMyContent ($input);
		}
		else
		{
			return $this->getGeneralContent ();
		}
	}
	
	public function getGeneralContent ($showAll = false)
	{
		// Fetch thze portal
		$portals = Dolumar_Map_Portal::getFromBuilding ($this);
		
		/*
		if (count ($portals) == 0)
		{
			return '<p class="false">This portal leads to nowhere...</p>';
		}
		*/
		
		$targets = array ();
		foreach ($portals as $v)
		{
			$village = $v->getOtherSide ($this->getVillage ());
			
			$targets[] = $village->getDisplayName ();
		}
	
		$page = new Neuron_Core_Template ();
		
		$destroydate = $this->getDestroyDate ();
		if ($destroydate)
		{
			$page->set ('timeleft', Neuron_Core_Tools::getCountdown ($this->getDestroyDate ()));
		}
		
		$page->set ('targets', $targets);
		
		return $page->parse ('buildings/portal.phpt');
	}
	
	public function getMapColor ()
	{
		return array (0, 0, 255);
	}
	
	public function getScore ()
	{
		return 0;
	}
}
?>
