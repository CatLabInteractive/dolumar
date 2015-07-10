<?php
class Dolumar_Windows_Newsbar extends Neuron_GameServer_Windows_Window
{
	const AJAX_RELOAD_INT = 10;

	public function setSettings ()
	{
	
		// Window settings
		$this->setNoBorder ();
		$this->setSize ('575px', 'auto');
		$this->setPosition ('auto', 'auto', 'auto', '0px');
		$this->setFixed ();
		$this->setZ (10000);
		$this->setCentered ();
		$this->setClass ('newsbar');
		
		$this->setType ('panel');
		
		// Magical update
		$this->setAjaxPollSeconds (self::AJAX_RELOAD_INT);
				
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		//return $this->getCurrentResources ();
		$data = $this->getRequestData ();
		$page = isset ($data['page']) ? intval($data['page']) : 1;
		
		$myself = Neuron_GameServer::getPlayer ();
		
		// Sort of config ;-)
		$totalPages = 0;
		if ($myself)
		{
			$villages = $myself->getVillages ();
			$totalPages = count ($villages);
		}
		
		$content = $this->getResourcesPage ();
		
		// Next & previous pages
		$nextPage = $page + 1;
		$previousPage = $page - 1;
		
		if ($previousPage < 1)
		{
			// Highest page id
			$previousPage = $totalPages;
		}
		
		if ($nextPage > $totalPages)
		{
			$nextPage = 1;
		}

		$text = Neuron_Core_Text::__getInstance ();

		$page = new Neuron_Core_Template ();
		
		$page->set ('nextPage', $nextPage);
		$page->set ('previousPage', $previousPage);

		$page->set ('minimap', $text->get ('minimap', 'menu', 'main'));
		
		// Check for news messages
		$hasMessages = false;
		if ($myself)
		{
			$mapper = Neuron_GameServer_Mappers_CachedChatMapper::getInstance ();
			$messages = $mapper->countUnreadMessages ($myself);
			
			$hasMessages = $messages > 0;
			//return $messages;
			
			// Get current village
			$village = $myself->getCurrentVillage ();
			if ($village)
			{
				$page->set ('current_village', Neuron_Core_Tools::output_varchar ($village->getName ()));
				$page->set ('current_village_id', Neuron_Core_Tools::output_varchar ($village->getId ()));
			}
		}
		
		$page->set ('hasMessages', $hasMessages);
		$page->set ('inbox', $text->get ('inbox', 'menu', 'main'));
		$page->set ('home', $text->get ('home', 'menu', 'main'));
		
		$page->set ('homecors', $this->getHomeLocation ());
		
		$page->set ('content', $content);
		
		return $page->parse ('newsbar.tpl');
	}
	
	/*
	private function getLinksPage ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$page = new Neuron_Core_Template ();
	
		$l = $this->getHomeLocation ();
		
		$page->addListValue ('jumps', '<a href="javascript:void(0);" '.
			'onclick="mapIsoJump('.$l[0].','.$l[1].'); return false;">'.
			$text->get ('home', 'menu', 'main').'</a>'
		);
		
		return $page->parse ('newsbar/links.phpt');
	}
	*/
	
	private function getHomeLocation ()
	{
		$login = Neuron_Core_Login::__getInstance ();
		
		if ($login->isLogin () && Neuron_GameServer::getPlayer ()->isPlaying ())
		{
			$me = Neuron_GameServer::getPlayer ();
			$l = $me->getHomeLocation ();
		}
		
		else {
			$l = array (0, 0);
		}
		
		return $l;
	}
	
	private function getResourcesPage ()
	{
		// Fetch resources
		$login = Neuron_Core_Login::__getInstance ();
		if ($login->isLogin () && Neuron_GameServer::getPlayer ()->isPlaying ())
		{
			$me = Neuron_GameServer::getPlayer ();

			$village = $me->getCurrentVillage ();
			
			if ($village)
			{
				$resources = $village->resources->getResources ();
				$income = $village->resources->getIncome ();
				$capacity = $village->resources->getCapacity ();
				
				return Neuron_Core_Tools::getResourceToText ($resources, $income, $capacity, true);
			}
		}

		return null;
	}
	
	public function processInput ()
	{
		// update request ifno
		$input = $this->getInputData ();
		if (isset ($input['page']))
		{
			$this->updateRequestData (array ('page' => $input['page']));
			
			// update current village
			$player = Neuron_GameServer::getPlayer ();
			if ($player)
			{
				$villages = $player->getVillages ();
				$k = intval ($input['page'] - 1);
				
				if (isset ($villages[$k]))
				{
					$player->setCurrentVillage ($villages[$k]);
					reloadEverything ();
					
					$loc = $player->getCurrentVillage ()->buildings->getTownCenterLocation ();
					
					// Jump to the selected village
					$this->mapJump ($loc[0], $loc[1]);
				}
			}
		}
		$this->updateContent ();
	}
	
	private function getFreshData ()
	{
		// FIRST! Update the content.
		$this->updateContent ();
	
		// Check the latest logs for notifications
		$me = Neuron_GameServer::getPlayer ();
		if ($me)
		{
			$objLogs = Dolumar_Players_Logs::__getInstance ();
		
			// Data
			$data = $this->getRequestData ();
			$lastLogId = isset ($data['lastLog']) ? $data['lastLog'] : $objLogs->getLastLogId ();
		
			$logs = array ();
		
			$villages = $me->getVillages ();
			foreach ($villages as $v)
			{
				$logs = array_merge ($logs, $objLogs->getLastLogs ($v, $lastLogId, true));
			}
			
			// Update the last data
			if (count ($logs) > 0)
			{
				// You can only show one log, so let's show the first one.
				$this->showNewsflash ($objLogs->getLogText ($logs[0]));
				
				// Check for reload actions
				foreach ($logs as $v)
				{
					switch ($v['action'])
					{
						case 'premium_movevillage':
						case 'premium_movebuilding':
						case 'premium_bonusbuild':
							$this->reloadMap ();
						break;
					}
				}
			}
			
			// Update the data
			$data['lastLog'] = $objLogs->getLastLogId ();
			
			$this->updateRequestData ($data);
		}
	}
	
	public function getRefresh ()
	{
		if (!isset ($_SESSION['newsbar_last_refresh']))
		{
			$_SESSION['newsbar_last_refresh'] = 0;
		}
		
		if (defined ('RELOAD') || $_SESSION['newsbar_last_refresh'] < (time () - self::AJAX_RELOAD_INT - 1))
		{
			$this->getFreshData ();
			$_SESSION['newsbar_last_refresh'] = time ();
		}
		
		//$this->showNewsflash ('Maarten is nen zeveraar.');
	}
}
?>
