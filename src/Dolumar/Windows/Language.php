<?php

class Dolumar_Windows_Language extends Neuron_GameServer_Windows_Window
{

	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('200px', '200px');
		$this->setTitle ($text->get ('language', 'menu', 'main'));
		
		$this->setAllowOnlyOnce ();
		
		$this->setClassName ('languages');
	}
	
	public function getContent ($language = false)
	{
		$login = Neuron_Core_Login::__getInstance ();
		$text = Neuron_Core_Text::__getInstance ();
		
		$page = new Neuron_Core_Template ();
		
		$text->setFile ('account');
		$text->setSection ('language');
		
		$page->set ('language', $text->get ('language'));
		$page->set ('submit', $text->get ('submit'));
		
		$page->set ('current_language', $language ? $language : $text->getCurrentLanguage ());
		
		// Get languages
		$lang = $text->getLanguages ();
		foreach ($lang as $v)
		{
			$text = new Neuron_Core_Text ($v);
		
			$page->addListValue
			(
				'languages',
				array
				(
					$v,
					$text->get ($v, 'languages', 'languages', $v)
				)
			);
			
			$page->sortList ('languages');
		}
		
		return $page->parse ('language.tpl');
	}
	
	public function processInput ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		$data = $this->getInputData ();
		$_SESSION['language'] = $data['language'];
		setcookie ('user_language', $data['language'], time () + COOKIE_LIFE, '/');
		
		$text->setLanguage ($data['language']);
		
		$user = Neuron_GameServer::getPlayer ();
		if ($user)
		{
			$user->setLanguage ($text->getCurrentLanguage ());
		}
		
		$this->updateContent ($this->getContent ($data['language']));
		
		reloadEverything ();
		
		$this->reloadMap ();
	}

}

?>
