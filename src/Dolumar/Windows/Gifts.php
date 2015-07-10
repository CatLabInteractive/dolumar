<?php
class Dolumar_Windows_Gifts 
	extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('300px', '150px');
		$this->setTitle ($text->get ('gifts', 'menu', 'main'));
		
		$this->setAllowOnlyOnce ();
		
		$this->setCentered ();
		//$this->setModal ();
	}
	
	public function getContent ()
	{
		$player = Neuron_GameServer::getPlayer ();

		if (!$player)
		{
			return '<p>Please login first.</p>';
		}

		$input = $this->getInput ('send');

		switch ($input)
		{
			case 'send':

				return $this->getSend ();

			break;
		}
		
		if ($player && $player->isPlaying ())
		{
			$page = new Neuron_Core_Template ();
			
			return $page->parse ('dolumar/gifts/gifts.phpt');
		}
		
		return false;
	}

	private function getSend ()
	{
		$text = Neuron_Core_Text::getInstance ();
		
		$page = new Neuron_Core_Template ();

		$player = Neuron_GameServer::getPlayer ();

		$results = $player->invitePeople 
		(
			'runesender', 
			'gifts', 
			'runereceiver',
			'gifts'
		);

		if ($results['success'])
		{
			if (!empty ($results['iframe']))
			{
				$width = isset ($results['width']) ? $results['width'] : 500;
				$height = isset ($results['height']) ? $results['height'] : 400;

				$this->closeWindow ();
				Neuron_GameServer::getInstance ()->openWindow 
				(
					'Iframe',
					array 
					(
						'title' => $text->get ('gifts', 'menu', 'main'), 
						'url' => $results['iframe'], 
						'width' => $width, 
						'height' => $height
					)
				);
			}
			else
			{
				return $page->parse ('dolumar/gifts/done.phpt');
			}
		}
		else
		{
			return '<p class="false">' . $results['error'] . '</p>';
		}
	}
}
?>
