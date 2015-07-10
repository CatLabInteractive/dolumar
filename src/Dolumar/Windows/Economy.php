<?php

class Dolumar_Windows_Economy extends Neuron_GameServer_Windows_Window
{

	private $village;
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$login = Neuron_Core_Login::__getInstance ();
		
		// Window settings
		$this->setSize ('400px', '380px');
		
		$this->setAllowOnlyOnce ();
		
		$data = $this->getRequestData ();
		
		// Construct village
		if (isset ($data['vid']) && $login->isLogin ())
		{
			$this->village = Dolumar_Players_Village::getVillage ($data['vid']);
			$this->setTitle ($text->get ('economy', 'menu', 'main') . ' (' .
				Neuron_Core_Tools::output_varchar ($this->village->getName ()).')');
		}
		
		else 
		{
			$this->village = false;
			$this->setTitle ($text->get ('economy', 'menu', 'main'));
		}
	}
	
	public function getContent ()
	{
		$login = Neuron_Core_Login::__getInstance ();
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('village');
		$text->setSection ('economics');
		
		if ($login->isLogin () && $this->village && $this->village->isFound ())
		{
			$me = Neuron_GameServer::getPlayer ();
			
			if ($this->village->isActive () && ($me->getId () == $this->village->getOwner ()->getId () || $me->isModerator ()))
			{
				$page = new Neuron_Core_Template ();
				
				$page->set ('resources', $text->get ('resources'));
				$page->set ('stock', $text->get ('stock'));
				$page->set ('max', $text->get ('max'));
				$page->set ('income', $text->get ('income'));
				$page->set ('norunes', $text->get ('norunes'));
				
				$page->set ('bruto', $text->get ('bruto'));
				$page->set ('consuming', $text->get ('consuming'));
				
				$page->set ('honour', $this->village->honour->getHonour ());
				
				$page->set ('hourly', $text->get ('hourly'));
				
				$res = $this->village->resources->getResources ();
				
				$income = $this->village->resources->getIncome ();
				$capacity = $this->village->resources->getCapacity ();
				$consumption = $this->village->resources->getUnitConsumption ();
				$bruto = $this->village->resources->getBrutoIncome ();
				
				foreach ($res as $k => $v)
				{
					$page->addListValue ('resources',
						array
						(
							ucfirst ($text->get ($k, 'resources', 'main')),
							$v,
							$capacity[$k],
							$income[$k],
							'resource' => $k,
							'bruto' => isset ($bruto[$k]) ? $bruto[$k] : 0,
							'consuming' => isset ($consumption[$k]) ? $consumption[$k] : 0
						)
					);
				}
				
				// Runes
				$page->set ('runes', $text->get ('runes'));
				
				$runes = $this->village->resources->getRuneSummary ();
				foreach ($runes as $k => $v)
				{
					if ($v > 0)
					{
						if ($k == 'random')
						{
							$k = 'randomrune';
						}
					
						$page->addListValue 
						(
							'runes',
							array
							(
								'name' => ucfirst ($text->get ($k, 'runeDouble', 'main')),
								'available' => $v['available'],
								'key' => $k,
								'used' => $v['used'],
								'used_percentage' => ($v['used_percentage'] * 100)
							)
						);
					}
				}
				
				$page->sortList ('runes');
				
				return $page->parse ('economics.tpl');
			}
			
			else 
			{
				return '<p>You are not authorized to view this information: '.$this->village->getId ().' != '.$me->getId ().'</p>';
			}
		}
		
		else {
			return '<p class="false">'.$text->get ('login', 'login', 'account').'</p>';
		}
	}

	/*
	public function getRefresh ()
	{
		$this->updateContent ();
	}
	*/
}

?>
