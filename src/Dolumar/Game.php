<?php
class Dolumar_Game implements Neuron_GameServer_Interfaces_Game
{
	public function __construct ()
	{
		
	}

	/*
		getWindow SHOULD return a Window object if the name matches.
		If this function returns FALSE (or NOT a Window object), the engine
		will check it's own library for a suiting window.
		
		@param $sWindowName: the name of the window.
	*/
	public function getWindow ($sWindow)
	{
		$sClassname = 'Dolumar_Windows_'.ucfirst ($sWindow);
		if (class_exists ($sClassname))
		{
			return new $sClassname ();
		}
		return false;
	}
	
	public function getMap ()
	{
		//return new Dolumar_Map ();
		
		$oldmap = new Dolumar_Map ();
		
		$map = new Dolumar_NewMap_Map ();
		
		$map->setBackgroundManager (new Dolumar_NewMap_BackgroundManager ($oldmap));
		$map->setMapObjectManager (new Dolumar_NewMap_MapObjectManager ($oldmap));
		
		return $map;
	}
	
	public function getServer ()
	{
		return new Dolumar_Players_Server ();
	}
	
	public function getInitialWindows ($objServer)
	{
		$out = array ();
		
		$out[] = $objServer->getWindow ('Welcome');
		$out[] = $objServer->getWindow ('ChatPopper');
		$out[] = $objServer->getWindow ('MiniMap');
		$out[] = $objServer->getWindow ('Menu');
		$out[] = $objServer->getWindow ('Newsbar');
		$out[] = $objServer->getWindow ('Statusbar');		
		$out[] = $objServer->getWindow ('Guide');

		// Only show chat window if player is logged in
		$player = Neuron_GameServer::getPlayer ();
		if ($player && (!isset ($_SESSION['hide_chat']) || !$_SESSION['hide_chat']))
		{
			$out[] = $objServer->getWindow ('Chat');
		}
		
		//$out[] = $objServer->getWindow ('AprilFool');
		
		$account = $objServer->getWindow ('MyAccount');
		$account->setRequestData (array ('load' => 'autoload'));
		$out[] = $account;
		
		/*
		$loadTutorial = true;
		
		$player = Neuron_GameServer::getPlayer ();
		if ($player)
		{
			$loadTutorial = intval ($player->getPreference ('closeTutorial')) != 1;
		}
		
		if ($loadTutorial)
		{
			$tutorial = $objServer->getWindow ('Tutorial');
			$tutorial->setRequestData (array ('page' => 'Tutorial1'));
			$out[] = $tutorial;
		}
		*/
		
		return $out;
	}
	
	/*
		Return a page
	*/
	public function getPage ($sPage)
	{
		$sClassname = 'Dolumar_Pages_'.ucfirst (strtolower ($sPage));
		if (class_exists ($sClassname))
		{
			return new $sClassname ();
		}
		else
		{
			return false;
		}
	}
	
	public function getAdminPage ($sPage)
	{
		$sClassname = 'Dolumar_Pages_Admin_'.ucfirst (strtolower ($sPage));
		if (class_exists ($sClassname))
		{
			return new $sClassname ();
		}
		else
		{
			return false;
		}
	}
	
	/*
		Return the right Player object.
		No need for singletons here.
	*/
	public function getPlayer ($id)
	{
		if ($id > 0)
		{
			return new Dolumar_Players_Player ($id);
		}
		else
		{
			return new Dolumar_Players_NPCPlayer (0);
		}
	}

	/*
	* After the game has finished, this method should return the winner
	*/
	private function getWinner ()
	{
		$server = Neuron_GameServer::getServer ();

		$winner = $server->getData ('winner');
		if ($winner)
		{
			return Dolumar_Players_Clan::getFromId ($winner);
		}
		return null;
	}

	public function getCustomOutput ()
	{
		$server = Neuron_GameServer::getServer ();

		$launchdate = null;
		if (defined ('LAUNCH_DATE'))
		{
			$launchdate = LAUNCH_DATE;
		}

		// Get launch date in db
		$dblaunchdate = $server->getData ('launchdate');
		if ($dblaunchdate && $dblaunchdate > $launchdate)
		{
			$launchdate = $dblaunchdate;
		}

		// Check for the Final Countdown!
		if (isset ($launchdate) && !isset ($_GET['DEBUG']) && !isset ($_SESSION['debug']))
		{
			header("Content-Type:text/html;charset=utf-8");

			if (isset ($_GET['debug']))
			{
				$_SESSION['debug'] = true;
			}

			if ($launchdate > time ())
			{
				$page = new Neuron_Core_Template ();
				
				$page->set ('launchdate', Neuron_Core_Tools::getCountdown ($launchdate));
				$page->set ('name', $server->getData ('servername'));
				
				return $page->parse ('launchdate.phpt');
			}
		}

		if ($server->getData ('gamestate') >= Dolumar_Players_Server::GAMESTATE_ENDGAME_FINISHED)
		{
			header("Content-Type:text/html;charset=utf-8");
			
			$page = new Neuron_Core_Template ();
			$page->set ('name', $server->getData ('servername'));

			$winner = $this->getWinner ();
			$page->set ('winner', $winner);

			return $page->parse ('finished.phpt');
		}
	}
}
