<?php
class Dolumar_Effects_Instant_KillWizard extends Dolumar_Effects_Instant
{
	protected $sType = 'thievery';

	public function requiresTarget ()
	{
		return true;
	}

	/*
		Destroy one random building
	*/
	public function execute ($a = null, $b = null, $c = null)
	{
		$units = $this->getTarget ()->getSpecialUnits ();
		shuffle ($units);
		
		foreach ($units as $v)
		{
			if ($v instanceof Dolumar_SpecialUnits_Mages)
			{
				$v->killUnit ();
				return true;
			}
		}
		
		return false;
	}
	
	public function getDifficulty ($iBaseAmount = 40)
	{
		return parent::getDifficulty (70);
	}
}
?>
