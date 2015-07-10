<?php
class Dolumar_Windows_Invite extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('250px', '300px');
		$this->setTitle ('Village Overview');
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		$player = Neuron_GameServer::getPlayer ();
	
		if (!$player)
		{
			$text = Neuron_Core_Text::__getInstance ();
			return '<p class="false">'.$text->get ('login', 'login', 'account').'</p>';
		}
		
		$page = new Neuron_Core_Template ();
		
		$page->set ('sUrl', ABSOLUTE_URL.'?pref='.$player->getId ());
		
		return $page->parse ('account/invite.phpt');
	}
}
?>
