<?php
class Dolumar_Windows_Premium extends Neuron_GameServer_Windows_Premium
{
	protected function getBenefits ()
	{
		$page = new Neuron_Core_Template ();
		
		
		
		return $page->parse ('account/benefits.phpt');
	}
}
?>
