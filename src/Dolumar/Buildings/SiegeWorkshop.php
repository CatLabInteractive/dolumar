<?php

class Dolumar_Buildings_SiegeWorkshop extends Dolumar_Buildings_Building
{
	public function canBuildBuilding (Dolumar_Players_Village $village)
	{
		return false;
		return $village->buildings->getBuildingAmount (31) > 0;
	}
}

?>
