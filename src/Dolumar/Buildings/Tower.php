<?php

class Dolumar_Buildings_Tower extends Dolumar_Buildings_Building
{
	protected function getCustomContent ($input)
	{
		$page = new Neuron_Core_Template ();
		
		$page->set ('description', $this->getDescription ());
		
		$runes = $this->getVillage ()->resources->getUsedRunes_amount ();
		$percentage = $this->getVillage ()->battle->getTowerPercentage ();
		$bonus = $this->getVillage ()->battle->getDefenseBonus ();
		
		$page->set ('runes', $runes);
		$page->set ('percentage', round ($percentage));
		$page->set ('bonus', round ($bonus));
		
		return $page->parse ('buildings/tower.phpt');
	}
	
	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		$this->addRequiresBuilding (11);
	}	
}

?>
