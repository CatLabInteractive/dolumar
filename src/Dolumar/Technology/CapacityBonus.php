<?php
class Dolumar_Technology_CapacityBonus extends Dolumar_Technology_Technology
{
	private $iBonusPercentage = 10;
	private $iBonusAmount = 0;
	
	public function setStats ($stats)
	{
		if (isset ($stats['bonus']))
		{
			// Check for percentage sign:
			if (substr ($stats['bonus'], -1) == '%')
			{
				$this->iBonusPercentage = intval (substr ($stats['bonus'], 0, -1));
			}
			else
			{
				$this->iBonusAmount = intval ($stats['bonus']);
			}
		}
	}

	public function procCapacity ($resources, $objBuilding)
	{
		// Add 10%
		$o = array ();
		foreach ($resources as $k => $v)
		{
			$o[$k] = $v + ($v * ($this->iBonusPercentage / 100)) + $this->iBonusAmount;
		}		
		return $o;
	}
}
?>
