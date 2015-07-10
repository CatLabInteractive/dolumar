<?php
abstract class Dolumar_Underworld_Models_Objectives_Objectives
{
	public static function getObjective (Dolumar_Underworld_Models_Mission $mission)
	{
		switch ($mission->getObjectiveName ())
		{
			case 'TakeAndHold':
				return new Dolumar_Underworld_Models_Objectives_TakeAndHold ($mission);
			break;

			case 'DorDaedeloth':
				return new Dolumar_Underworld_Models_Objectives_DorDaedeloth ($mission);
			break;

			default:
				return new Dolumar_Underworld_Models_Objectives_Explore ($mission);
			break;
		}
	}

	protected $mission;
	
	private $mappedcheckpoints;

	public function __construct (Dolumar_Underworld_Models_Mission $mission)
	{
		$this->mission = $mission;
	}

	public function getJoinSide (Dolumar_Players_Clan $clan)
	{
		$side = new Dolumar_Underworld_Models_Side ($clan->getId ());
		return $side;
	}

	public function isValidSpawnPoint (Dolumar_Underworld_Models_Side $side, Dolumar_Underworld_Map_Locations_Location $location)
	{
		return $location->isSide ($side);
	}

	protected function checkSpawnpointRequirements (Dolumar_Underworld_Models_Side $side, Dolumar_Underworld_Map_Locations_Location $location)
	{
		$requirements = $location->getGroup ()->getRequirements ();

		foreach ($requirements as $v)
		{
			switch ($v['type'])
			{
				case 'checkpoint':
					$checkpointside = $this->getCheckpointSide ($v['location']);
					if (!$checkpointside || !$checkpointside->equals ($side))
					{
						return false;
					}
				break;
			}
		}

		return true;
	}

	private function loadcheckpoints ()
	{
		if (!isset ($this->checkpoints))
		{
			$this->checkpoints = array ();
			$this->mappedcheckpoints = array ();

			$checkpoints = Dolumar_Underworld_Mappers_CheckpointMapper::getCheckpointSides ($this->getMission ());

			foreach ($checkpoints as $v)
			{
				$location = $v['location'];
	
				if (!isset ($this->mappedcheckpoints[$location->x ()]))
				{
					$this->mappedcheckpoints[$location->x ()] = array ();
				}

				$this->mappedcheckpoints[$location->x ()][$location->y ()] = $v['side'];
			}
		}
	}

	public function getConqueredCheckpoints (Dolumar_Underworld_Models_Side $side)
	{
		$checkpoints = Dolumar_Underworld_Mappers_CheckpointMapper::getCheckpointSides ($this->getMission ());

		$out = array ();

		foreach ($checkpoints as $v)
		{
			if ($side->equals ($v['side']))
			{
				$out[] = $v['location'];
			}
		}

		return $out;
	}

	public function getCheckpointSide (Neuron_GameServer_Map_Location $location)
	{
		$x = $location->x ();
		$y = $location->y ();

		$this->loadcheckpoints ();
		if (isset ($this->mappedcheckpoints[$x]) && isset ($this->mappedcheckpoints[$x][$y]))
		{
			return $this->mappedcheckpoints[$x][$y];
		}

		return null;
	}

	protected function getTimeSinceLastCheckpointSide (Neuron_GameServer_Map_Location $location)
	{
		$time = Dolumar_Underworld_Mappers_CheckpointMapper::getTimeSinceLastCheckpointSide ($this->getMission (), $location);
		return $time;
	}

	// Only change side if side is different
	public function setCheckpointSide (Neuron_GameServer_Map_Location $location, Dolumar_Underworld_Models_Side $side)
	{
		$currentside = $this->getCheckpointSide ($location);
		$time = 0;

		if (isset ($currentside))
		{
			// Only change if side is different
			if (!$currentside->equals ($side))
			{
				$time = $this->getTimeSinceLastCheckpointSide ($location);

				Dolumar_Underworld_Mappers_CheckpointMapper::set ($this->getMission (), $location, $side);
				$this->onChangeCheckpointSide ($location, $currentside, $side, $time);
			}
		}

		else
		{
			Dolumar_Underworld_Mappers_CheckpointMapper::set ($this->getMission (), $location, $side);
			$this->onChangeCheckpointSide ($location, null, $side, $time);
		}
	}

	protected function onChangeCheckpointSide 
	(
		Neuron_GameServer_Map_Location $location, 
		Dolumar_Underworld_Models_Side $originalside = null, 
		Dolumar_Underworld_Models_Side $newside = null,
		$time = 0
	)
	{
		// By default, do nothing.
	}

	public function getMission ()
	{
		return $this->mission;
	}

	// Events
	public function onSpawn (Dolumar_Underworld_Models_Army $army) 
	{

	}

	public function onMove (Dolumar_Underworld_Models_Army $army, Neuron_GameServer_Map_Location $old, Neuron_GameServer_Map_Location $new) 
	{
		if ($new instanceof Dolumar_Underworld_Map_Locations_Checkpoint)
		{
			$this->setCheckpointSide ($new, $army->getSide ());
		}
	}

	public function getName ()
	{
		return 'Exploring the underworld';
	}

	public function getScore (Dolumar_Underworld_Models_Side $side)
	{
		return Dolumar_Underworld_Mappers_ScoreMapper::getScore ($this->getMission (), $side);
	}

	public function setScore (Dolumar_Underworld_Models_Side $side, $score)
	{
		Dolumar_Underworld_Mappers_ScoreMapper::setScore ($this->getMission (), $side, $score);
	}

	public function checkVictoryConditions ()
	{
		// By default, nothing to do.
	}

	public function getScores ()
	{
		// And now the stored scores
		$scores = Dolumar_Underworld_Mappers_ScoreMapper::getScores ($this->getMission ());

		// Let's make the scores...
		$objectives = $this->getMission ()->getMap ()->getBackgroundManager ()->getCheckpointObjectives ();

		foreach ($objectives as $location)
		{
			$side = $this->getCheckpointSide ($location);

			if ($side)
			{
				$score = $this->getTimeSinceLastCheckpointSide ($location);
			
				foreach ($scores as $k => $v)
				{
					if ($v['side']->equals ($side))
					{
						$scores[$k]['score'] += $score;
						$scores[$k]['increasing'] = true;
						break 2;
					}
				}

				$scores[] = array
				(
					'side' => $side,
					'score' => $score,
					'increasing' => true
				);
			}
		}

		return $scores;
	}

	public function onWin (Dolumar_Underworld_Models_Side $side)
	{
		// Add log
		$lock = Neuron_Core_Lock::getInstance ();
		$lockname = 'uw_fin';

		if ($lock->setLock ($lockname, $this->getMission ()->getId ()))
		{
			// Reload mission, just to be sure
			$mission = Dolumar_Underworld_Mappers_MissionMapper::getFromId ($this->getMission ()->getId ());

			if ($mission)
			{
				// Logger
				$this->getMission ()->getLogger ()->win ($side);

				// Do whatever we need to do
				$this->winnerBenefits ($side);

				// Start removing mission
				$mission->destroy ();

				// Release the lock
				$lock->releaseLock ($lockname, $this->getMission ()->getId ());
			}
		}
	}

	protected function winnerBenefits (Dolumar_Underworld_Models_Side $side)
	{

	}
}