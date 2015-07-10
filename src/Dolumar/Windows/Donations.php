<?php
class Dolumar_Windows_Donations extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('250px', '300px');
		$this->setTitle ('Donations');
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		$page = new Neuron_Core_Template ();

		$page->set ('donation_url', Neuron_GameServer::getServer()->getDonationUrl ());

		return $page->parse ('donations.tpl');
	}
}
?>
