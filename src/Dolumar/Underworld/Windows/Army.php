<?php
class Dolumar_Underworld_Windows_Army 
	extends Neuron_GameServer_Windows_Window
{
	private $map;

	private $army;
	private $me;

	public function setSettings ()
	{
		$this->setSize ('250px', '350px');
		$this->setTitle ('Regiment');
		
		$this->setAllowOnlyOnce ();
		
		$armyid = $this->getInput ('id');
		$this->army = Dolumar_Underworld_Mappers_ArmyMapper::getFromId ($armyid);

		if ($this->army)
		{
			$this->army->setMap ($this->getServer ()->getMap ());
		}

		$this->setClass ('underworld army');
	}
	
	public function getContent ()
	{
		$this->me = Neuron_GameServer::getPlayer ();
		
		if (!$this->me)
		{
			return '<p>Please login.</p>';
		}
	
		if (!isset ($this->army))
		{
			return '<p>Army not found.</p>';
		}

		if (!$this->army->canView ($this->me))
		{
			return $this->getOutsiderView ();
		}
	
		$action = $this->getInput ('action');
		
		switch ($action)
		{
			case 'move':
				$this->prcMove ();
			break;
			
			case 'player':
				$this->prcPlayer ();
			break;
			
			case 'merge':
				$this->prcMerge ();
			break;
			
			case 'attack':
				$this->prcAttack ();
			break;

			case 'split':
				return $this->prcSplit ();
			break;

			case 'withdraw':
				$this->prcWithdraw ();
			break;
		}
	
		$page = new Neuron_Core_Template ();
		
		// Show them a list of all squads
		$squads = $this->army->getSquads ();
		
		foreach ($squads as $v)
		{
			$data = array
			(
				'name' => $v->getDisplayName (),
				'units' => array (),
				'owner' => $v->getVillage ()->getOwner ()->getDisplayName ()
			);
			
			foreach ($v->getUnits () as $vv)
			{
				$data['units'][] = array
				(
					'name' => $vv->getDisplayName (),
					'amount' => $vv->getAmount (),
					'image' => $vv->getImageUrl (),
					'numberedname' => $vv->getAmount () . ' ' . $vv->getDisplayName ($vv->getAmount () > 1),
					'morale' => $vv->getMorale ()
				);
			}
			
			$page->addListValue ('squads', $data);
		}
		
		// Players
		$players = $this->army->getPlayers ();
		
		foreach ($players as $v)
		{
			$page->addListValue
			(
				'players',
				array
				(
					'status' => ($this->army->isLeader ($v) ? 'leader' : 'normal'),
					'id' => $v->getId (),
					'name' => $v->getDisplayName (),
					'canPromote' => $this->army->canPromote ($this->me, $v),
					'canDemote' => $this->army->canDemote ($this->me, $v)
				)
			);
			
			$page->sortList ('players');
		}
		
		$page->set ('movepoints', $this->army->getMovepoints ());
		
		$nextpoint = $this->army->getNextMovepointDate ();
		if (isset ($nextpoint) && $this->me->isPremium ())
		{
			$page->set ('nextpoint', Neuron_Core_Tools::getCountdown ($nextpoint));
		}
		
		return $page->parse ('dolumar/underworld/windows/regiment.phpt');
	}

	private function getOutsiderView ()
	{
		$this->setSize ('250px', '150px');

		$mySide = $this->army->getMap ()->getMission ()->getPlayerSide ($this->me);

		$battles = Dolumar_Underworld_Mappers_BattleMapper::getFromArmy 
		(
			$this->army->getMap ()->getMission (), 
			$mySide, 
			$this->army
		);

		if (count ($battles) > 0)
		{
			// Get latest battles
			$battle = null;
			foreach ($battles as $v)
			{
				if ($battle == null || $v->getEnddate () > $battle->getEnddate ())
				{
					$battle = $v;
				}
			}

			// Now show this content
			$page = new Neuron_Core_Template ();

			$page->set ('date', date ('d/m/Y H:i:s', $battle->getEnddate ()));
			$page->set ('troops', $battle->getHistoricalArmyData ($this->army));

			return $page->parse ('dolumar/underworld/windows/regiment_historical.phpt');
		}

		else
		{
			$page = new Neuron_Core_Template ();
			return $page->parse ('dolumar/underworld/windows/regiment_nohistory.phpt');
		}
	}

	private function prcWithdraw ()
	{
		$text = Neuron_Core_Text::getInstance ();

		$confirmed = $this->getInput ('confirmed');

		// Dialog to confirm
		if (!$confirmed == '1')
		{
			$this->dialog
			(
				$text->get ('withdraw', 'squad', 'underworld'),
				$text->get ('dyes', 'main', 'main'),
				"windowAction (this, {'id':".$this->army->getId().",'action':'withdraw','confirmed':1});",
				$text->get ('dno', 'main', 'main'),
				'void(0);'
			);
		}
		
		else
		{
		
			if (!$this->army->withdraw ($this->me))
			{
				$this->alert ($this->army->getError ());
			}
		
			else
			{
				$this->closeWindow ();
			}
		}
	}
	
	private function prcMove ()
	{
		// Check if location contains army
		$objectManager = $this->getServer ()->getMap ()->getMapObjectManager ();
	
		$x = floor ($this->getInput ('x'));
		$y = floor ($this->getInput ('y'));
		
		if (isset ($x) && isset ($y))
		{
			$doMove = true;
		
			$location = new Neuron_GameServer_Map_Location ($x, $y);
			
			// If there are already units on this location,
			// we propose to merge
			$objects = $objectManager->getFromLocation ($location, false);
			
			// I only want the army
			$armies = array ();
			foreach ($objects as $v)
			{
				if ($v instanceof Dolumar_Underworld_Models_Army)
				{
					$armies[] = $v;
				}
			}
			
			if (count ($armies) > 0)
			{
				// There should only be one army
				$army = $armies[0];
				
				// If it's an ally, propose to merge
				if ($army->isAlly ($this->army) && !$this->army->equals ($army))
				{
					$doMove = false;
					$this->prcMerge ($army);
				}
				
				// If it's an enemy, propose to attack
				elseif ($army->isEnemy ($this->army) && !$this->army->equals ($army))
				{
					$doMove = false;
					$this->prcAttack ($army);
				}
			}
			
			// Just move the unit
			if ($doMove)
			{
				if (!$this->army->moveArmy ($this->me, $location))
				{
					$this->alert ($this->army->getError ());
				}
			}
		}
	}
	
	private function prcPlayer ()
	{
		$do = $this->getInput ('do');
		$playerid = $this->getInput ('player');
		
		$player = Neuron_GameServer::getPlayer ($playerid);
		$text = Neuron_Core_Text::getInstance ();
		
		if ($player)
		{
			switch ($do)
			{
				case 'promote':
					if (!$this->army->promote ($this->me, $player))
					{
						$this->alert ($text->get ($this->army->getError (), 'errors', 'underworld'));
					}
				break;
			
				case 'demote':
					if (!$this->army->demote ($this->me, $player))
					{
						$this->alert ($text->get ($this->army->getError (), 'errors', 'underworld'));
					}
				break;
			}
		}
	}
	
	private function prcMerge (Dolumar_Underworld_Models_Army $target = null)
	{
		$text = Neuron_Core_Text::getInstance ();
	
		if (!isset ($target))
		{
			$target = Dolumar_Underworld_Mappers_ArmyMapper::getFromId ($this->getInput ('target'), $this->army->getMap ());
			if (!isset ($target))
			{
				return '<p>Invalid input.</p>';
			}
		}
		
		if (!$this->army->isAlly ($target))
		{
			$this->alert ('Invalid input: not an ally.');
		}
		
		$confirmed = $this->getInput ('confirmed');
		
		// Dialog to confirm
		if (!$confirmed == '1')
		{
			$this->dialog
			(
				Neuron_Core_Tools::putIntoText
				(
					$text->get ('merge', 'squad', 'underworld'),
					array
					(
						$target->getDisplayName ()
					)
				),
				$text->get ('dyes', 'main', 'main'),
				"windowAction (this, {'id':".$this->army->getId().",'action':'merge','confirmed':1,'target':".$target->getId ()."});",
				$text->get ('dno', 'main', 'main'),
				'void(0);'
			);
		}
		
		else
		{
		
			if (!$target->merge ($this->me, $this->army))
			{
				$this->alert ($target->getError ());
			}
		
			else
			{
				$this->closeWindow ();
			}
		}
	}

	private function prcSplit ()
	{
		$page = new Neuron_Core_Template ();

		// Show them a list of all squads
		$squads = $this->army->getSquads ();

		$selectedSquads = array ();
		$selectedSquadsData = array ();
		
		foreach ($squads as $v)
		{
			$data = array
			(
				'id' => $v->getId (),
				'name' => $v->getDisplayName (),
				'units' => array (),
				'owner' => $v->getVillage ()->getOwner ()->getDisplayName ()
			);
			
			foreach ($v->getUnits () as $vv)
			{
				$data['units'][] = array
				(
					'name' => $vv->getDisplayName (),
					'amount' => $vv->getAmount (),
					'image' => $vv->getImageUrl (),
					'numberedname' => $vv->getAmount () . ' ' . $vv->getDisplayName ($vv->getAmount () > 1),
					'morale' => $vv->getMorale ()
				);
			}

			// Check input for selected squads
			if ($this->getInput ('squad_' . $v->getId ()))
			{
				$selectedSquads[] = $v;
				$selectedSquadsData[] = $data;
			}
			else
			{
				$page->addListValue ('squads', $data);
			}
		}

		if (count ($selectedSquads) > 0)
		{
			if (!$this->army->split ($this->me, $selectedSquads))
			{
				$this->alert ($this->army->getError ());

				foreach ($selectedSquadsData as $v)
				{
					$page->addListValue ('squads', $v);
				}
			}
		}

		return $page->parse ('dolumar/underworld/windows/split.phpt');
	}
	
	private function prcAttack (Dolumar_Underworld_Models_Army $target = null)
	{
		$text = Neuron_Core_Text::getInstance ();
	
		if (!isset ($target))
		{
			$target = Dolumar_Underworld_Mappers_ArmyMapper::getFromId 
			(
				$this->getInput ('target'), 
				$this->getServer ()->getMap ()
			);
			if (!isset ($target))
			{
				$this->alert ('Invalid input, target not found.');
			}
		}
		
		if (!$this->army->isEnemy ($target))
		{
			$this->alert ('Invalid input: not an enemy.');
		}
		
		$confirmed = $this->getInput ('confirmed');
		
		// Dialog to confirm
		if (!$confirmed == '1')
		{
			$this->dialog
			(
				Neuron_Core_Tools::putIntoText
				(
					$text->get ('attack', 'squad', 'underworld'),
					array
					(
						$target->getDisplayName ()
					)
				),
				$text->get ('dyes', 'main', 'main'),
				"windowAction (this, {'id':".$this->army->getId().",'action':'attack','confirmed':1,'target':".$target->getId ()."});",
				$text->get ('dno', 'main', 'main'),
				'void(0);'
			);
		}
		
		else
		{
			if (!$this->army->attack ($this->me, $target))
			{
				$this->alert ($this->army->getError ());
			}
		}
	}

}
