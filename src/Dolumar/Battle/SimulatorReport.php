<?php
class Dolumar_Battle_SimulatorReport extends Dolumar_Battle_Report
{
	public function getReport ($objVillage = NULL, $logid = NULL, $bShowFight = false)
	{
		//return parent::getReport (null, null, true);
		$out = $this->getFightlog (0, true);
		//$out .= $this->getFightReport ();
		return $out;
	}
}
?>
