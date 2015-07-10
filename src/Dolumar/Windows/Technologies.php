<?php
class Dolumar_Windows_Technologies extends Neuron_GameServer_Windows_Window
{
	private $village;
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$login = Neuron_Core_Login::__getInstance ();
		
		// Window settings
		$this->setSize ('250px', '245px');
		
		$this->setAllowOnlyOnce ();
		
		$data = $this->getRequestData ();
		
		// Construct village
		$this->village = Dolumar_Players_Village::getVillage ($data['vid']);
		
		if ($login->isLogin () && $this->village->isFound ())
		{
			$this->setTitle
			(
				$text->get ('technologies', 'menu', 'main') . ' (' .
				Neuron_Core_Tools::output_varchar ($this->village->getName ()).')'
			);
		}
		
		else 
		{
			$this->village = false;
			$this->setTitle ($text->get ('technologies', 'menu', 'main'));
		}
	}
	
	public function getContent ()
	{
		$login = Neuron_Core_Login::__getInstance ();
		if ($this->village->isActive () && $this->village->getOwner ()->getId () == $login->getUserId ())
		{
			$page = new Neuron_Core_Template ();

			foreach ($this->village->getTechnologies () as $v)
			{
				$page->addListValue ('technologies', array ($v->getName ()));
			}

			return $page->parse ('technologies.tpl');
		}
		else
		{
			return false;
		}
	}
}
?>
