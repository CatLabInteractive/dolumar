<?php

class Dolumar_Windows_PlayerProfile extends Neuron_GameServer_Windows_Window
{
	protected $player;

	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$db = Neuron_Core_Database::__getInstance ();
		
		// Window settings
		$this->setSize ('250px', '295px');
		
		$this->setPlayer ();
	}
	
	protected function setPlayer ()
	{
		$o = $this->getRequestData ();	
		$this->player = Neuron_GameServer::getPlayer ($o['plid']);
		$this->setTitle (Neuron_Core_Tools::output_varchar ($this->player->getNickname ()));
	}
	
	public function getContent ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setSection ('profile');
		$text->setFile ('account');
	
		if ($this->player->isFound ())
		{
			$page = new Neuron_Core_Template ();

			$page->set ('playerProfile', $text->get ('playerProfile'));
			
			$page->set ('creation', $text->get ('creation'));
			$page->set ('removal', $text->get ('removal'));
			$page->set ('lastRef', $text->get ('lastRef'));

			$page->set ('id', $this->player->getId ());

			$page->set ('player', Neuron_Core_Tools::output_varchar ($this->player->getNickname ()));

			// Date values
			$creation = $this->player->getCreationDate ();
			if ($creation > 0)
			{
				$page->set ('creation_value', date (DATETIME, $creation));
			}

			// Removal
			$removal = $this->player->getRemovalDate ();
			if ($removal > 0)
			{
				$page->set ('removal_value', date (DATETIME, $removal));
			}

			// Online now
			$online = null;
			$lastRefresh = $this->player->getLastRefresh ();

			if ($lastRefresh > (time() - 60 * 5))
			{
				$online = $text->get ('onlineNow');
			}
			else
			{
				$online = Neuron_Core_Tools::putIntoText
				(
					$text->get ('onlineAgo'),
					array
					(
						Neuron_Core_Tools::getDurationText (time () - $lastRefresh)
					)
				);
			}
			$page->set ('lastRef_value', $online);

			$iv = 0;
			foreach ($this->player->getVillages () as $v)
			{
				$page->addListValue
				(
					'villages',
					array
					(
						Neuron_Core_Tools::output_varchar ($v->getName ()),
						$v->getId ()
					)
				);
				
				$iv ++;
			}
			
			$page->set ('villages', $text->get ($iv > 1 ? 'villages' : 'village'));
			
			$page->set ('clans', $text->get ('clans'));
			
			// Add status field
			$page->set ('status', $text->get ('status'));
			
			if (!$this->player->isPlaying ())
			{
				$status = $text->get ('removed');
			}
			
			elseif ($this->player->inVacationMode ())
			{
				$status = $text->get ('vacation');
			}
			
			elseif ($this->player->isOnline ())
			{
				$status = $text->get ('online');
			}
			
			else
			{
				$status = $text->get ('offline');
			}
			
			$page->set ('status_value', $status);
			
			$me = Neuron_GameServer::getPlayer ();
			if ($me && $me->isModerator ())
			{
				$page->set 
				(
					'admin_url', 
					Neuron_Core_Tools::output_varchar
					(
						Neuron_GameServer_Pages_Admin_Page::getUrl
						(
							'user',
							array
							(
								'id' => $this->player->getId ()
							)
						)
					)
				);
			}
			
			// Fetch clans
			foreach ($this->player->getClans () as $v)
			{
				$page->addListValue
				(
					'clans',
					array
					(
						'id' => $v->getId (),
						'name' => Neuron_Core_Tools::output_varchar ($v->getName ())
					)
				);
			}

			$out = $page->parse ('playerProfile.tpl');
			
			foreach ($this->player->getVillages () as $v)
			{
				$out .= $this->getVillageProfile ($v);
			}
			
			return $out;
		}
	}
	
	public function getVillageProfile ($objVillage)
	{
		if (!$objVillage || !$objVillage->isFound ())
		{
			return '<p>Village not found.</p>';
			return null;
		}
	
		$text = Neuron_Core_Text::__getInstance ();
		
		$text->setFile ('village');
		$text->setSection ('profile');
		
		$townCenter = $objVillage->buildings->getTownCenter ();
		if ($townCenter)
		{
			$l = $townCenter->getLocation ();
		}
		else
		{
			$l = array ('?', '?');
		}
		
		$page = new Neuron_Core_Template ();
		
		$page->set ('village', Neuron_Core_Tools::output_varchar ($objVillage->getName ()));
		
		$page->set ('location', $text->get ('location'));
		$page->set ('villageProfile', $text->get ('villageProfile'));
		
		$page->set ('location_value', '['.$l[0].','.$l[1].']');
		$page->set ('locX', $l[0]);
		$page->set ('locY', $l[1]);
		
		// Owner
		$owner = $objVillage->getOwner ();
		
		$page->set ('owner', $text->get ('owner'));
		$page->set ('owner_value', Neuron_Core_Tools::output_varchar ($owner->getNickname ()));
		$page->set ('pid', $owner->getId ());
		
		// Ranking
		$rank = $objVillage->getRank ();
		
		$page->set ('rank', $text->get ('rank'));
		$page->set ('rank_value', Neuron_Core_Tools::putIntoText ($text->get ('ranking'), array ($rank[0], $rank[1])));

		// Race
		$race = $objVillage->getRace ();

		$page->set ('race', $text->get ('race'));
		$page->set ('race_value', Neuron_Core_Tools::output_varchar ($race->getRaceName ()));
		
		$page->set ('score', $objVillage->getNetworth ());

		$me = Neuron_GameServer::getPlayer ();
		if ($me && $objVillage->isActive ())
		{
			foreach ($me->getVillages () as $v)
			{
				if (!$v->equals ($objVillage))
				{
					// Register the visit
					$v->visits->registerVisit ($objVillage);
			
					$page->addListValue
					(
						'challenges',
						array
						(
							Neuron_Core_Tools::putIntoText
							(
								$text->get ('challenge'),
								array (Neuron_Core_Tools::output_varchar ($v->getName ()))
							),
							htmlentities
							(
								json_encode 
								(
									array
									(
										'vid' => $v->getId (),
										'target' => $objVillage->getId()
									)
								)
							)
						)
					);
				
					$distance = Dolumar_Map_Map::getDistanceBetweenVillages ($v, $objVillage);
				
					$page->addListValue
					(
						'distances',
						array
						(
							'id' => $v->getId (),
							'name' => Neuron_Core_Tools::output_varchar ($v->getName ()),
							'distance' => Neuron_Core_Tools::output_distance ($distance, false, false)
						)
					);
				}
			}
		}
		
		elseif (!$objVillage->isActive ())
		{
			$page->set ('notActive', $text->get ('notActive'));
		}
		
		// Set honour
		$page->set ('honour_value', $objVillage->honour->getHonour ());
		
		return $page->parse ('villageProfile.tpl');
	}
}

?>
