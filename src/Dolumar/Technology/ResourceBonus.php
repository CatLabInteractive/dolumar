<?php
class Dolumar_Technology_ResourceBonus extends Dolumar_Technology_Technology
{
	private $sResource = 'grain';
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
		
		// Check for resource
		if (isset ($stats['resource']))
		{
			$this->sResource = $stats['resource'];
		}
	}

	/*
		Raise the selected resoruce with the selected bonus.
		@return array of resources
	*/
	public function procIncome ($resources, $building)
	{
		/*
		if ($building->getClassName () == 'Farm')
		{
			foreach ($resources as $k => $v)
			{
				$resources[$k] = $v + ($v / 20);
			}
		}
		return $resources;
		*/
		
		// Add percentage to resource
		if (isset ($resources[$this->sResource]) && $resources[$this->sResource] > 0)
		{
			$resources[$this->sResource] += ($resources[$this->sResource] * ($this->iBonusPercentage / 100)) + $this->iBonusAmount;
		}
		
		return $resources;
	}
}
?>
