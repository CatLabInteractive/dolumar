<?php
class Dolumar_Windows_Serversettings extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('150px', '100px');
		$this->setTitle ('Server settings');
		
		$this->setAllowOnlyOnce ();
		
		$this->setCentered ();
		//$this->setModal ();
	}
	
	public function getContent ()
	{
		$server = Neuron_GameServer::getServer ();

		$page = new Neuron_Core_Template ();

		if ($server->getEndgameStartDate () > time ())
		{
			$page->set ('endgame_start', Neuron_Core_Tools::getCountdown ($server->getEndgameStartDate ()));
		}
		else
		{
			$page->set ('endgame_start', '<strong>already started</strong>');
		}

		return $page->parse ('dolumar/serversettings.phpt');
	}
}
?>
