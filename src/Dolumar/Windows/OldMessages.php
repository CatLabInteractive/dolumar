<?php
class Dolumar_Windows_OldMessages extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('500px', '300px');
		$this->setTitle ($text->get ('messages', 'menu', 'main'));
		
		$this->setClass ('messages');
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		// Fetch thze model
		$login = Neuron_Core_Login::__getInstance ();
		$text = Neuron_Core_Text::__getInstance ();
		
		if ($login->isLogin ())
		{
			$player = Neuron_GameServer::getPlayer ();
			
			if ($player->isBanned ('messages'))
			{
				$end = $player->getBanDuration ('messages');						
				$duration = Neuron_Core_Tools::getCountdown ($end);
			
				return '<p class="false">'.
				(
					Neuron_Core_Tools::putIntoText
					(
						$text->get ('banned', 'messages', 'messages'),
						array
						(
							'duration' => $duration
						)
					)
				).'</p>';
			}
			
			elseif (!$player->isEmailVerified ())
			{
				return '<p class="false">'.$text->get ('validateEmail', 'main', 'account').'</p>';
			}
			
			else
			{
				$objMessages = new Neuron_Structure_Messages ($player);
				return $objMessages->getPageHTML ($this->getInputData ());
			}
		}
		else
		{
			$this->throwError ($text->get ('noLogin', 'main', 'main'));
		}
	}
	
	public function processInput ()
	{
		$this->updateContent ();
	}
}
?>
