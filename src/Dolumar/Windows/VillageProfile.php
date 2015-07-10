<?php

class Dolumar_Windows_VillageProfile extends Dolumar_Windows_PlayerProfile
{	
	private $thisVillage;

	protected function setPlayer ()
	{
		$o = $this->getRequestData ();
		
		if (!isset ($o['village']) && isset ($o[0]))
		{
			$o['village'] = $o[0];
		}
		elseif (!isset ($o['village']))
		{
			$o['village'] = null;
		}
		
		$village = Dolumar_Players_Village::getVillage ($o['village']);
		if ($village)
		{
			$this->player = $village->getOwner ();
			$this->thisVillage = $village;
		}
		
		$this->setTitle (Neuron_Core_Tools::output_varchar ($this->player->getNickname ()));
	}
	
	public function getContent ()
	{
		if (
			(!isset ($this->thisVillage) || $this->thisVillage->isActive ())
			&& (! $this->thisVillage->getOwner () instanceof Dolumar_Players_NPCPlayer)
		)
		{
			return parent::getContent ();
		}
		
		else
		{
			// Inactive village, just show information.
			return $this->getVillageProfile ($this->thisVillage);
		}
	}
}

?>
