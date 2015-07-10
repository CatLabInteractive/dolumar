<?php
class Dolumar_Underworld_Models_Army
	extends Dolumar_Underworld_Map_Object
{
	private $id;
	private $data;
	private $map;
	private $error = 'no error';
	
	private $players;
	private $squads;
	private $leaders;
	private $lastmovepoint;
	
	private $movepoints = null;
	
	const MOVEPOINT_GENERATE_DURATION = 900;
	const MOVEPOINT_MAXIMUM = 3;

	public function __construct ($id)
	{
		$this->setId ($id);
		
		parent::__construct ();
	}
	
	public function setData ($data)
	{
		$this->data = $data;
		$this->lastmovepoint = $data['ua_lastrefresh_timestamp'];
	}
	
	public function reloadData ()
	{
		$this->players = null;
		$this->squads = null;
		$this->leaders = null;
	}
	
	public function getId ()
	{
		return $this->id;
	}

	public function setId ($id)
	{
		$this->id = $id;
	}
	
	public function setMap (Dolumar_Underworld_Map_Map $map)
	{
		$this->map = $map;
	}
	
	public function getMap ()
	{
		if (!isset ($this->map))
		{
			throw new Neuron_Exceptions_DataNotSet ("Map is not set yet.");
		}
		
		return $this->map;
	}
	
	/* 
		Return the amount of unused movepoints
	*/
	public function getMovepoints ($rounded = true)
	{
		// Calculate new amount of movepoints
		$this->calculateMovepoints ();
	
		return $rounded ? floor ($this->movepoints) : $this->movepoints;
	}
	
	private function calculateMovepoints ()
	{
		if (!isset ($this->movepoints))
		{
			Neuron_Profiler_Profiler::getInstance ()->start ('Calculating movepoints');
		
			$timedif = NOW - $this->lastmovepoint;
			$income = $timedif / self::MOVEPOINT_GENERATE_DURATION;	
			
			$this->movepoints = min ($this->getMaxMovepoints (), $this->data['ua_movepoints'] + $income);
			$this->lastmovepoint = NOW;
			
			Neuron_Profiler_Profiler::getInstance ()->message ('Timedif: ' . $timedif . ', income: ' . $income . ', new value: ' . $this->movepoints);
			
			Neuron_Profiler_Profiler::getInstance ()->stop ();
		}
	}
	
	public function getNextMovepointDate ()
	{
		$out = null;
		Neuron_Profiler_Profiler::getInstance ()->start ('Calculating next movepoint');
	
		$nextbig = ceil ($this->getMovepoints (false));
		if ($nextbig < $this->getMaxMovepoints ())
		{
			$progress = $this->getMovepoints (false) - $this->getMovepoints (true);
			$timeleft = (1 - $progress) * self::MOVEPOINT_GENERATE_DURATION;
			
			Neuron_Profiler_Profiler::getInstance ()->message ('Value: ' . $this->getMovepoints (false) . ', progress: ' . $progress . ', time left: ' . $timeleft);
			
			$out = NOW + $timeleft;
		}
		
		Neuron_Profiler_Profiler::getInstance ()->stop ();
		
		return $out;
	}
	
	public function getMaxMovepoints ()
	{
		if (defined ('UNDERWORLD_MAX_MOVEPOINTS'))
		{
			return UNDERWORLD_MAX_MOVEPOINTS;
		}
		return self::MOVEPOINT_MAXIMUM;
	}
	
	public function getLastRefresh ()
	{
		return $this->lastmovepoint;
	}
	
	public function decreaseMovepoints ($amount, $save = true)
	{
		$this->movepoints = $this->getMovepoints (false) - $amount;
		
		if ($save)
		{
			Dolumar_Underworld_Mappers_ArmyMapper::save ($this);
		}
	}

	public function canView (Dolumar_Players_Player $me)
	{
		$mission = $this->getMap ()->getMission ();
		$side = $mission->getSide ($me->getMainClan ());

		return $this->getSide ()->equals ($side);
	}
	
	public function canMove (Dolumar_Players_Player $me)
	{
		return $this->isLeader ($me);
	}

	public function canAttack (Dolumar_Players_Player $me)
	{
		return $this->isLeader ($me);
	}

	public function canMerge (Dolumar_Players_Player $me)
	{
		return $this->isLeader ($me);
	}

	public function canSplit (Dolumar_Players_Player $me)
	{
		return $this->isLeader ($me);	
	}

	public function canWithdraw (Dolumar_Players_Player $me)
	{
		return $this->isLeader ($me);
	}
	
	public function getSide ()
	{
		if (!isset ($this->side))
		{
			throw new Neuron_Exceptions_DataNotSet ("Side is not set yet.");
		}
	
		return $this->side;
	}
	
	public function setSide (Dolumar_Underworld_Models_Side $side)
	{
		$this->side = $side;
	}
	
	public function moveArmy (Dolumar_Players_Player $me, Neuron_GameServer_Map_Location $location)
	{
		if (!$location instanceof Dolumar_Underworld_Map_Locations_Location)
		{
			$x = $location->x ();
			$y = $location->y ();

			$location = $this->getMap ()->getBackgroundManager ()->getSingleLocation ($x, $y);
		}

		if (!$this->canMove ($me))
		{
			$this->error = 'move_not_authorized';
			return false;
		}
		
		$path = $this->execMove ($location);

		if ($path)
		{
			$this->getMap ()->getMission ()->getLogger ()->move ($me, $this, $location, $path);
			return true;
		}
		else
		{
			return false;
		}
	}

	public function getUnderworldLocation ()
	{
		$location = parent::getLocation ();

		$x = $location->x ();
		$y = $location->y ();

		return $this->getMap ()->getBackgroundManager ()->getSingleLocation ($x, $y);
	}
	
	private function execMove (Dolumar_Underworld_Map_Locations_Location $location)
	{
		$oldlocation = $this->getUnderworldLocation ();
		
		if ($path = $this->canReach ($location))
		{
			$points = $path->getCost ();
		
			$this->setLocation ($location);
			$this->decreaseMovepoints ($points, false);
			
			Dolumar_Underworld_Mappers_ArmyMapper::save ($this);
		
			$this->getMap ()->addMapUpdate ($oldlocation, 'DESTROY');
			$this->getMap ()->addMapUpdate ($location, 'BUILD');
			
			// Now we should make the whole line "explored"
			foreach ($path->getPath () as $v)
			{
				$this->getMap ()->setExplored ($v);
			}

			// And notify the objective
			$this->getMap ()->getMission ()->onMove ($this, $oldlocation, $location);
			
			return $path;
		}
	
		else
		{
			return false;
		}
	}
	
	/**
	*	Check if a move can be executed (and set the correct error if not)
	*/
	private function canReach (Neuron_GameServer_Map_Location $end, $isTargetAnObject = false)
	{
		$start = $this->getLocation ();
		
		Neuron_Profiler_Profiler::getInstance ()->message ('Check if we can go from ' . $start . ' to ' . $end);
	
		// Too far? Don't bother about anything else
		if ($this->getMap ()->getMinimalDistance ($start, $end) > $this->getMovePoints ())
		{
			$this->error = 'not_enough_movepoints';
			Neuron_Profiler_Profiler::getInstance ()->message ('Precheck failed');
			return false;
		}
	
		// Otherwise: check the path
		$path = $this->getMap ()->getPath ($this->getSide (), $start, $end, $this->getMovePoints () + 1, $isTargetAnObject);
		
		if ($path === false)
		{
			Neuron_Profiler_Profiler::getInstance ()->message ('No path found.');
			$this->error = 'no_path_found';
		}
		
		else if ($path->getCost () > $this->getMovePoints ())
		{
			$this->error = 'not_enough_movepoints';
		}
		
		else
		{
			return $path;
		}
		
		return false;
	}
	
	/*
		Return the squads that are currently in the army
	*/
	public function getSquads ()
	{
		if (!isset ($this->squads))
		{
			$this->squads = Dolumar_Underworld_Mappers_ArmyMapper::getSquads ($this);
		}
		
		return $this->squads;
	}
	
	/*
		Add a squad
	*/
	public function addSquad (Dolumar_Players_Squad $squad)
	{
		Dolumar_Underworld_Mappers_ArmyMapper::addSquad ($this, $squad);
		$this->squads = null;
	}

	public function removeSquad (Dolumar_Players_Squad $squad)
	{
		Dolumar_Underworld_Mappers_ArmyMapper::removeSquad ($this, $squad);
		$this->squads = null;
	}
	
	/*
		Return the players that are currently in the army
	*/
	public function getPlayers ()
	{
		if (!isset ($this->players))
		{
			$out = array ();
		
			foreach ($this->getSquads () as $v)
			{
				$owner = $v->getVillage ()->getOwner ();
			
				if (!isset ($out[$owner->getId ()]))
				{
					$out[$owner->getId ()] = $owner;
				}
			}
		
			$this->players = array_values ($out);
		}
		
		return $this->players;
	}
	
	/*
		Get all leaders
	*/
	public function getLeaders ()
	{
		if (!isset ($this->leaders))
		{
			$this->leaders = Dolumar_Underworld_Mappers_ArmyMapper::getLeaders ($this);
		}
		
		return $this->leaders;
	}
	
	/*
		Check if a player is a leader
	*/
	public function isLeader (Dolumar_Players_Player $player)
	{
		foreach ($this->getLeaders () as $v)
		{
			if ($v->equals ($player))
			{
				return true;
			}
		}
		
		return false;
	}
	
	public function canPromote (Dolumar_Players_Player $me, Dolumar_Players_Player $target)
	{
		return !$this->isLeader ($target) && $this->canChangeStatus ($me, $target);
	}
	
	public function canDemote (Dolumar_Players_Player $me, Dolumar_Players_Player $target)
	{
		return $this->isLeader ($target) && $this->canChangeStatus ($me, $target);
	}
	
	public function canChangeStatus (Dolumar_Players_Player $player, Dolumar_Players_Player $target)
	{
		return $this->isLeader ($player) && !$player->equals ($target);
	}
	
	public function promote (Dolumar_Players_Player $me, Dolumar_Players_Player $target)
	{
		if ($this->canPromote ($me, $target))
		{
			$this->dopromote ($target);
			return true;
		}
		else
		{
			$this->error = 'cannot_promote';
			return false;
		}
	}

	public function promote_nocheck ($target)
	{
		Dolumar_Underworld_Mappers_ArmyMapper::addLeader ($this, $target);
		$this->reloadData ();
	}
	
	public function demote (Dolumar_Players_Player $me, Dolumar_Players_Player $target)
	{
		if ($this->canDemote ($me, $target))
		{
			Dolumar_Underworld_Mappers_ArmyMapper::removeLeader ($this, $target);
			$this->reloadData ();
			return true;
		}
		else
		{
			$this->error = 'cannot_demote';
			return false;
		}
	}
	
	/**
	*	Check if another army is an ally
	*/
	public function isAlly (Dolumar_Underworld_Models_Army $ally)
	{
		return $this->getSide ()->equals ($ally->getSide ());
	}
	
	/**
	*	Check if another army is an enemy
	*/
	public function isEnemy (Dolumar_Underworld_Models_Army $enemy)
	{
		return !$this->isAlly ($enemy);
	}
	
	/**
	*	Check if this is the same army
	*/
	public function equals (Dolumar_Underworld_Models_Army $tg)
	{
		return $tg->getId () == $this->getId ();
	}
	
	/**
	*	Get display name
	*/
	public function getDisplayName ()
	{
		// Return the name of the first squad
		$squads = $this->getSquads ();
		
		if (count ($squads) > 0)
		{
			return $squads[0]->getDisplayName ();
		}
		else
		{
			return 'Unknown';
		}
	}
	
	/**
	*	Merge $army with current army.
	*	$army will be destroyed.
	*/
	public function merge (Dolumar_Players_Player $me, Dolumar_Underworld_Models_Army $army)
	{
		if (!$army->canMerge ($me))
		{
			$this->error = 'move_not_authorized';
			return false;
		}

		$path = $army->canReach ($this->getLocation (), true);

		if (!$this->isAlly ($army))
		{
			$this->error = 'not_an_enemy';
			return false;
		}
	
		else if (!$path)
		{
			$this->error = 'cannot_reach_target';
			return false;
		}
	
		foreach ($army->getSquads () as $v)
		{
			$this->addSquad ($v);
		}
		$army->destroy ();

		$this->getMap ()->getMission ()->getLogger ()->merge 
		(
			$me,
			$army,
			$this,
			$this->getLocation (),
			$path
		);

		return true;
	}

	public function withdraw (Dolumar_Players_Player $me)
	{
		if (!$this->canWithdraw ($me))
		{
			$this->error = 'move_not_authorized';
			return false;
		}

		$this->getMap ()->getMission ()->getLogger ()->withdraw 
		(
			$me,
			$this,
			$this->getLocation ()
		);

		$this->destroy ();
		return true;
	}

	/**
	* Split $squads from this army
	*/
	public function split (Dolumar_Players_Player $me, array $squads)
	{
		if (!$this->canSplit ($me))
		{
			$this->error = 'move_not_authorized';
			return false;
		}
		
		if (count ($squads) === 0)
		{
			$this->error = 'split_no_squads_selected';
			return false;
		}

		else if (count ($squads) === count ($this->getSquads ()))
		{
			$this->error = 'split_all_squads_selected';
			return false;
		}

		$movepoints = $this->getMovepoints ();
		if ($movepoints < 1)
		{
			$this->error = 'split_no_movepoints';
			return false;
		}

		// Find free adjacent spot
		$freeSpots = $this->getMap ()->getFreeAdjacentSpots ($this->getLocation ());

		if (count ($freeSpots) === 0)
		{
			$this->error = 'split_no_free_spots';
			return false;
		}

		// Get random location
		$location = $freeSpots[mt_rand (0, count ($freeSpots) - 1)];

		foreach ($squads as $v)
		{
			$this->removeSquad ($v);
		}

		$army = $this->getMap ()->getMission ()->createArmy 
		(
			$location,
			$this->getSide (),
			$squads
		);

		// Reduce movepoints
		$reduce = ($this->getMaxMovepoints () - $movepoints) + 1;
		$army->decreaseMovepoints ($reduce);

		$this->getMap ()->addMapUpdate ($location, 'BUILD');

		$this->getMap ()->getMission ()->getLogger ()->split 
		(
			$me,
			$this,
			$army,
			$location
		);

		return true;
	}
	
	/**
	*	Return all units in their respected slot
	*/
	public function getUnits ($slots)
	{
		$units = array ();
		
		$amount = count ($slots);
		
		foreach ($this->getSquads () as $squad)
		{
			foreach ($squad->getUnits () as $unit)
			{
				$units[] = $unit;
			}
		}
		
		// Now shuffle that
		shuffle ($units);
		
		// Now we must center them
		$start = ceil ($amount / 2);
		
		$i = $start - 1;
		$step = 0;
		$right = true;
		
		$out = array ();
		
		// Direction switches every time, so we'll
		// get: 3 4 2 5 1 6 0 7		
		while ($i >= 0 && $i < $amount)
		{
			$step ++;
			
			$troop = array_pop ($units);
			if ($troop)
			{
				$out[$i] = $troop;
				if (isset ($out[$i]))
				{
					$out[$i]->setBattleSlot ($slots[$i]);
				}
			}
			
			if ($right)
			{
				$i += $step;
				$right = false;
			}
			
			else
			{
				$i -= $step;
				$right = true;
			}
		}
		
		return $out;
	}
	
	/**
	*	Attack
	*/
	public function attack (Dolumar_Players_Player $me, Dolumar_Underworld_Models_Army $army)
	{
		if (!$this->canAttack ($me))
		{
			$this->error = 'move_not_authorized';
			return false;
		}

		$path = $this->canReach ($army->getLocation (), true);

		if (!$this->isEnemy ($army))
		{
			$this->error = 'not_an_enemy';
			return false;
		}
	
		else if (!$path)
		{
			$this->error = 'cannot_reach_target';
			return false;
		}
	
		else if ($this->isFighting ())
		{
			$this->error = 'army_not_idle';
			return false;
		}
		
		else if ($army->isFighting ())
		{
			// This should be replaced by queing
			$this->error = 'target_not_idle';
			return false;
		}
		
		else
		{
			$fight = $this->prcFight ($army);

			$this->getMap ()->getMission ()->getLogger ()->attack
			(
				$me,
				$this,
				$army,
				$army->getLocation (),
				$path,
				$fight
			);

			return $fight;
		}
	}
	
	/**
	*	Slots
	*/
	public function getSlots ($amount = 7)
	{
		$out = array ();
		for ($i = 0; $i < $amount; $i ++)
		{
			$out[$i] = new Dolumar_Battle_Slot_Grass ($i);
		}
		return $out;
	}
	
	/**
	*	Prc fight
	*/
	private function prcFight (Dolumar_Underworld_Models_Army $target)
	{
		$battle = Dolumar_Underworld_Models_Battle::fight ($this, $target);
		
		Dolumar_Underworld_Mappers_BattleMapper::insert ($this->getMap ()->getMission (), $battle);
		
		// Kill the appropriate units
		// We do not do that yet
		
		// Check who won and remove the loser
		if ($battle->isWinner ($this))
		{
			$target->destroy ();
			$this->execMove ($target->getUnderworldLocation ());
		}
		
		else
		{
			$this->destroy ();
		}
		
		return $battle;
	}
	
	/**
	*	Is in battle?
	*/
	public function isFighting ()
	{
		$battles = Dolumar_Underworld_Mappers_BattleMapper::
			getActiveBattlesFromArmy ($this);
		
		return count ($battles) > 0;
	}
	
	/**
	*	Remove this squad
	*/
	public function destroy ()
	{
		$this->getMap ()->addMapUpdate ($this->getLocation (), 'DESTROY');
		Dolumar_Underworld_Mappers_ArmyMapper::remove ($this);
	}
	
	public function getError ()
	{
		return $this->error;
	}
}
?>
