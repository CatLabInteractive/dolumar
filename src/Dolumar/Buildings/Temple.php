<?php

class Dolumar_Buildings_Temple extends Dolumar_Buildings_Building
{
	public function canBuildBuilding (Dolumar_Players_Village $village)
	{
		return false;
	}
}

?>
