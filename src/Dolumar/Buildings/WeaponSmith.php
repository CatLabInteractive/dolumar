<?php

class Dolumar_Buildings_WeaponSmith extends Dolumar_Buildings_Crafting
{
	public function getEquipment ()
	{
		/*
		$o = array ();
		$o[] = new Dolumar_Players_Equipment ('dagger');
		return $o;
		*/
		
		return $this->getItemsFromType ('weapon');
	}

	protected function initRequirements ()
	{
		$this->addRequiresBuilding (13);
	}	
}

?>
