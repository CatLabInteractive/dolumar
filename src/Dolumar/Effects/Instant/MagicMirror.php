<?php
class Dolumar_Effects_Instant_MagicMirror extends Dolumar_Effects_Instant
{
	private $report = false;

	public function requiresTarget ()
	{
		return true;
	}

	public function execute ($a = null, $b = null, $c = null)
	{
		$buildings = $this->getTarget ()->buildings->getBuildings ();
		
		$report = new Dolumar_Report_BuildingReport ($this->getVillage ());
		
		$report->setTarget ($this->getTarget ());
		
		foreach ($buildings as $v)
		{
			$report->addBuilding ($v);
		}
		
		$report->store ();
		
		$this->addLogData ($report);
		
		$this->setMessageParameters
		(
			array
			(
				'report' => $report->getDisplayName ()
			)
		);
		
		$this->report = $report;
	}
	
	public function getExtraContent ()
	{
		if (isset ($this->report))
		{
			return $this->report->__toString ();
		}
	}
	
	protected function getCostFromLevel ()
	{
		return 10;
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 2;
	}
}
?>
