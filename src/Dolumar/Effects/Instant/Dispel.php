<?php
class Dolumar_Effects_Instant_Dispel extends Dolumar_Effects_Instant
{
	public function requiresTarget ()
	{
		return true;
	}

	public function execute ($a = null, $b = null, $c = null)
	{
		$effects = $this->getTarget ()->getEffects ();
		
		foreach ($effects as $v)
		{
			if ($v->getEffectType () == 'magic')
			{
				$v->cancel ();
			}
		}
	}
	
	public function getDifficulty ($iBaseAmount = 40)
	{
		return 80;
	}
	
	protected function getCostFromLevel ()
	{
		return 20;
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 3;
	}
}
?>
