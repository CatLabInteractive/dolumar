<?php

class Dolumar_Buildings_ArmourSmith extends Dolumar_Buildings_Crafting
{
	public function getEquipment ()
	{
		/*
		$o = array ();
		$o[] = new Dolumar_Players_Equipment ('leatherArmour');
		return $o;
		*/
		
		return $this->getItemsFromType ('armour');
	}

	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		$this->addRequiresBuilding (31);
	}	
}

?>
