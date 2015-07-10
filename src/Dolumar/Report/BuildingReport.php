<?php
/*
	This container groups a bunch of resources / runes.
*/
class Dolumar_Report_BuildingReport extends Dolumar_Report_Report
{
	public function addBuilding ($building)
	{
		// Make a (static) logable
		$this->addItem (new Dolumar_Logable_Building ($building));
	}
}
?>
