<?php

class Dolumar_Windows_Ranking extends Neuron_GameServer_Windows_Window
{

	public function setSettings ()
	{
	
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('300px', '350px');
		$this->setTitle ('Ranking');
		
		$this->setAllowOnlyOnce ();
	
	}
	
	public function getContent ()
	{
		$data = $this->getRequestData ();
		$input = $this->getInputData ();
		
		$sDPage = isset ($data['ranking']) ? $data['ranking'] : 'players';
		$sPage = isset ($input['ranking']) ? $input['ranking'] : $sDPage;
		
		switch ($sPage)
		{
			case 'clans':
				$this->updateRequestData (array ('ranking' => 'clans'));
				return $this->getClansRanking ();
			break;
			
			case 'villages':
				$this->updateRequestData (array ('ranking' => 'villages'));
				return $this->getVillageRanking ();
			break;
			
			case 'players':
			default:
				$this->updateRequestData (array ('ranking' => 'players'));
				return $this->getPlayerRanking ();			
			break;
		}
	}
	
	public function getClansRanking ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$db = Neuron_Core_Database::__getInstance ();

		$input = $this->getInputData ();
	
		$page = new Neuron_Core_Template ();
		
		$text->setFile ('ranking');
		$text->setSection ('ranking');
		
		$page->set ('village', $text->get ('clan'));
		$page->set ('value', $text->get ('value'));

		$data = $this->getRequestData ();

		$perPage = 25;

		$currentPage = isset ($input['page']) ? $input['page'] : 1;
		
		$limit = Neuron_Core_Tools::splitInPages 
		(
			$page, 
			Dolumar_Players_Ranking::countClanRanking (), 
			$currentPage, 
			$perPage, 
			6
		);
		
		$myclans = array ();
		$player = Neuron_GameServer::getPlayer ();
		if ($player)
		{
			foreach ($player->getClans () as $v)
			{
				$myclans[$v->getId ()] = true;
			}
		}

		$l = Dolumar_Players_Ranking::getClanRanking ($limit['start'], $limit['perpage']);
		
		$i = $limit['start'];
		foreach ($l as $village)
		{
			$i ++;
			
			$page->addListValue
			(
				'ranking',
				array
				(
					$i,
					Neuron_Core_Tools::output_varchar ($village->getName ()),
					$village->getId (),
					$village->getNetworth (),
					isset ($myclans[$village->getId ()])
				)
			);
		}
		
		return $page->parse ('ranking/clanRanking.phpt');
	}
	
	public function getPlayerRanking ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$db = Neuron_Core_Database::__getInstance ();

		$input = $this->getInputData ();
	
		$page = new Neuron_Core_Template ();
		
		$text->setFile ('ranking');
		$text->setSection ('ranking');
		
		$page->set ('title', $text->get ('villageRating'));
		$page->set ('village', $text->get ('village'));
		$page->set ('value', $text->get ('value'));

		$data = $this->getRequestData ();

		$perPage = 25;

		$myDefaultPage = 1;

		$myVillageId = 0;
	
		// Load "main village" from this user
		$myself = Neuron_GameServer::getPlayer ();
		if ($myself)
		{
			$me = $myself->getId ();
			$rank = $myself->getRank ();
			$myDefaultPage = floor ($rank[0] / $perPage) + 1;
		}
		else
		{
			$me = 0;
		}

		$currentPage = isset ($input['page']) ? $input['page'] : $myDefaultPage;
		
		$limit = Neuron_Core_Tools::splitInPages 
		(
			$page, 
			Dolumar_Players_Ranking::countPlayerRanking (), 
			$currentPage, 
			$perPage, 
			6
		);

		$l = Dolumar_Players_Ranking::getPlayerRanking ($limit['start'], $limit['perpage']);
		
		$i = $limit['start'];
		foreach ($l as $v)
		{
			$i ++;
			
			$page->addListValue
			(
				'ranking',
				array
				(
					$i,
					Neuron_Core_Tools::output_varchar ($v->getName ()),
					$v->getId (),
					$v->getScore (),
					$v->getId () == $me
				)
			);
			
			//$v->__destruct ();
		}
		
		return $page->parse ('ranking/players.tpl');
	}
	
	public function getVillageRanking ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$db = Neuron_Core_Database::__getInstance ();

		$input = $this->getInputData ();
	
		$page = new Neuron_Core_Template ();
		
		$text->setFile ('ranking');
		$text->setSection ('ranking');
		
		$page->set ('title', $text->get ('villageRating'));
		$page->set ('village', $text->get ('village'));
		$page->set ('value', $text->get ('value'));

		$data = $this->getRequestData ();

		$perPage = 25;

		$myDefaultPage = 1;
		if (isset ($data['village']))
		{
			$village = Dolumar_Players_Village::getVillageFromId ($data['village']);
			$myVillageId = $data['village'];
			$myRank = $village->getRank ();
			$myDefaultPage = floor ($myRank[0] / $perPage) + 1;
		}
		else
		{
			$myVillageId = 0;
		
			// Load "main village" from this user
			$myself = Neuron_GameServer::getPlayer ();
			if ($myself)
			{
				$village = $myself->getMainVillage ();
				if ($village)
				{
					$myVillageId = $village->getId ();
					$myRank = $village->getRank ();
					$myDefaultPage = floor ($myRank[0] / $perPage) + 1;
				}
			}
		}

		$currentPage = isset ($input['page']) ? $input['page'] : $myDefaultPage;
		
		$limit = Neuron_Core_Tools::splitInPages 
		(
			$page, 
			Dolumar_Players_Ranking::countRanking (), 
			$currentPage, 
			$perPage, 
			6
		);

		$l = Dolumar_Players_Ranking::getRanking ($limit['start'], $limit['perpage']);
		
		// Get my villages
		$myself = Neuron_GameServer::getPlayer ();
		if ($myself && $myself->isPremium ())
		{
			$distances = $myself->getVillages ();
		}
		else
		{
			$distances = array ();
		}
		
		$i = $limit['start'];
		foreach ($l as $v)
		{
			$i ++;
			
			// Calcualte the distances
			$w_distances = array ();
			foreach ($distances as $k => $vv)
			{
				$w_distances[$k] = Neuron_Core_Tools::output_distance 
				(
					Dolumar_Map_Map::getDistanceBetweenVillages ($vv, $v), 
					true, 
					true
				);
			}
			
			$page->addListValue
			(
				'ranking',
				array
				(
					$i,
					Neuron_Core_Tools::output_varchar ($v->getName ()),
					$v->getId (),
					$v->getNetworth (),
					$v->getId () == $myVillageId,
					$w_distances
				)
			);
			
			//$v->__destruct ();
		}
		
		// Add the footnote
		$t_distances = array ();
		foreach ($distances as $k => $v)
		{
			$t_distances[$k] = Neuron_Core_Tools::output_varchar ($v->getName ());
		}
		$page->set ('distances', $t_distances);
		
		return $page->parse ('ranking/ranking.tpl');
	}

	public function processInput ()
	{
		$this->updateContent ();
	}

}

?>
