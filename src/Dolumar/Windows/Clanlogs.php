<?php
class Dolumar_Windows_Clanlogs extends Dolumar_Windows_Logbook
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('550px', '280px');
		$this->setTitle ($text->get ('clanlogs', 'menu', 'main'));
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		$player = Neuron_GameServer::getPlayer ();
		$input = $this->getInputData ();
		
		if (!$player)
		{
			return false;
		}
		
		$page = new Neuron_Core_Template ();
		
		$clans = $player->getClans ();
		
		if (count ($clans) > 0)
		{
			$objlogs = Dolumar_Players_ClanLogs::getInstance ();
			
			$iPage = isset ($input['page']) ? $input['page'] : 0;
		
			// Split in pages
			$limit = Neuron_Core_Tools::splitInPages 
			(
				$page, 
				$objlogs->countClanLogs ($clans), 
				$iPage, 
				10
			);
			
			$objlogs->clearMyVillages ();
			foreach ($player->getVillages () as $village)
			{
				$objlogs->addMyVillage ($village);
			}
			
			$logs = $objlogs->getClanLogs ($clans, $limit['start'], $limit['perpage'], 'DESC');
			
			return $this->getLogHTML ($page, $objlogs, $logs);
		}
		
		return false;
	}
	
	protected function getLogHTML ($page, $objLogs, $logs)
	{
		foreach ($logs as $v)
		{
			$page->addListValue
			(
				'logs',
				array
				(
					'date' => date (DATETIME, $v['timestamp']),
					'text' => $objLogs->getLogText ($v, true)
				)
			);
		}
		
		return $page->parse ('clan/clanlogs.phpt');	
	}
}
?>
