<?php
class Dolumar_Underworld_Models_Objectives_Explore
	extends Dolumar_Underworld_Models_Objectives_Objectives
{
	/**
	* Check the requirements of the checkpoint
	*/
	public function isValidSpawnPoint (Dolumar_Underworld_Models_Side $side, Dolumar_Underworld_Map_Locations_Location $location)
	{
		return $this->checkSpawnpointRequirements ($side, $location);
	}
}