<?php

class Dolumar_Windows_Invitations extends Neuron_GameServer_Windows_Window
{

	public function setSettings ()
	{
	
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('250px', '105px');
		$this->setTitle ($text->get ('invitations', 'menu', 'main'));
		
		$this->setAllowOnlyOnce ();
	
	}
	
	public function getContent ()
	{
		$login = Neuron_Core_Login::__getInstance ();
		$db = Neuron_Core_Database::__getInstance ();
		$text = Neuron_Core_Text::__getInstance ();
		
		if ($login->isLogin ())
		{
		
			// Check for invitation key
			$key = $db->select
			(
				'invitation_codes',
				array ('invCode', 'invLeft'),
				"plid = '".$login->getUserId ()."'"
			);
			
			if (count ($key) < 1)
			{
				$this->generateNewKey ($login->getUserId ());
			}
			
			else {
				$this->invKey = $key[0]['invCode'];
				$this->invLeft = $key[0]['invLeft'];
			}
			
			$page = new Neuron_Core_Template ();
			
			$page->setVariable ('invKey', $this->invKey);
			$page->setVariable ('invLeft', $this->invLeft);
			
			return $page->parse ('invitations.tpl');
		
		}
		
		else {
			return '<p class="false">'.$text->get ('login', 'login', 'account').'</p>';
		}
	}
	
	private function generateNewKey ($plid)
	{
	
		$db = Neuron_Core_Database::__getInstance ();
		
		$okay = false;
		while (!$okay)
		{
		
			// Let's go mad.
			$key = substr (md5 (rand (-999999999999999, 999999999999999)), rand (0, 15), 15);
			
			$check = $db->select
			(
				'invitation_codes',
				array ('invCode'),
				"invCode = '$key'"
			);
			
			if (count ($check) == 0)
			{
				$db->insert
				(
					'invitation_codes',
					array
					(
						'plid' => $plid,
						'invCode' => $key,
						'invLeft' => 15
					)
				);
				
				$okay = true;
			}
		
		}
	
		$this->invKey = $key;
		$this->invLeft = 15;
	}

	public function getRefresh ()
	{
	

	
	}

}

?>
