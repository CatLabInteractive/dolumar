<?php
class Dolumar_Windows_Welcome extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('450px', '330px');
		$this->setTitle ($text->get ('welcome', 'menu', 'main'));
		
		$this->setAllowOnlyOnce ();
		
		$this->setCentered ();
		//$this->setModal ();
	}
	
	public function getContent ()
	{
		$player = Neuron_GameServer::getPlayer ();
		
		if ($player && $player->isPlaying ())
		{
			$page = new Neuron_Core_Template ();
			
			// Check for welcome message
			if (isset ($_SESSION['welcome_html']))
			{
				$page->set ('welcome', $_SESSION['welcome_html']);
			}
			
			$server = Neuron_GameServer_Server::getInstance ();
			
			$txt = $server->getText ('headline');
			$page->set ('headline', isset ($txt) ? Neuron_Core_Tools::output_text ($txt) : null);
			
			$page->set ('nickname', $player->getDisplayName ());
			$page->set ('isPremium', $player->isPremium ());
			$page->set ('date', date (DATE, $player->getPremiumEndDate ()));
			$page->set ('isFreePremium', !$player->isProperPremium ());
			
			$msg = Neuron_GameServer_Mappers_CachedChatMapper::getInstance ();
			$messages = $msg->countUnreadMessages ($player);
			
			$page->set ('inbox', $messages);
			
			$clans = $player->getClans ();
			
			$page->set ('hasclan', count ($clans) > 0);
			
			if (count ($clans) > 0)
			{
				$objlogs = Dolumar_Players_ClanLogs::getInstance ();
				
				$objlogs->clearMyVillages ();
				foreach ($player->getVillages () as $village)
				{
					$objlogs->addMyVillage ($village);
				}
				
				$logs = $objlogs->getClanLogs ($clans, 0, 3);
				
				foreach ($logs as $v)
				{
					$page->addListValue
					(
						'logs',
						array
						(
							'date' => date (DATETIME, $v['timestamp']),
							'text' => $objlogs->getLogText ($v)
						)
					);
				}
			}
			
			// Fetch thze news
			if (defined ('GAMENEWS_RSS_URL'))
			{
				$text = Neuron_Core_Text::getInstance ();
				$localized_link = str_replace ('{lang}', $text->getCurrentLanguage (), GAMENEWS_RSS_URL);
			
				$rss = new Neuron_Core_RSSParser ($localized_link);
				
				$rss->setCache (Neuron_Core_Cache::getInstance ('newsfeed/'));
				
				foreach ($rss->getItems (3) as $v)
				{
					$page->addListValue
					(
						'gamenews',
						array
						(
							'date' => $v['date'] ? date (DATETIME, $v['date']) : null,
							'title' => $v['title'],
							'url' => $v['url']
						)
					);
				}
			}
			
			return $page->parse ('dolumar/welcome/welcome.phpt');
		}
		
		return false;
	}
}
?>
