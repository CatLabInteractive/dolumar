<?php
class Dolumar_Underworld_Models_Mission
{
	private $id;
	private $data = array ();
	
	private $map;
	private $objective;

	private $sides;

	private $logger;

	public function __construct ($id)
	{
		$this->setId ($id);
	}
	
	public function setData ($data)
	{
		$this->data = $data;
	}

	public function setId ($id)
	{
		$this->id = intval ($id);
	}
	
	public function getId ()
	{
		return $this->id;
	}
	
	public function getName ()
	{
		return $this->getObjective ()->getName ();
	}

	public function setMapName ($name)
	{
		$this->data['um_map'] = $name;
	}
	
	public function getMapName ()
	{
		return $this->data['um_map'];
	}

	public function setObjectiveName ($name)
	{
		$this->data['um_mission'] = $name;
	}

	public function getObjectiveName ()
	{
		return $this->data['um_mission'];
	}

	public function getUrl ()
	{
		return BASE_URL.'underworld.php?sessionId=' . session_id () . '&amp;id=' . $this->getId ();
	}

	public function hasJoined (Dolumar_Players_Clan $clan)
	{
		Dolumar_Underworld_Mappers_MissionMapper::hasClan ($this, $clan);
	}

	public function join (Dolumar_Players_Clan $clan)
	{
		$side = $this->getObjective ()->getJoinSide ($clan);
		Dolumar_Underworld_Mappers_MissionMapper::addClan ($this, $clan, $side);
	}

	private function loadClans ()
	{
		if (!isset ($this->sides))
		{
			$this->sides = Dolumar_Underworld_Mappers_MissionMapper::getSides ($this);
		}
	}

	public function getSides ()
	{
		$this->loadClans ();
		return $this->sides;
	}

	public function getSide (Dolumar_Players_Clan $clan)
	{
		$this->loadClans ();
		foreach ($this->sides as $v)
		{
			if ($v->hasClan ($clan))
			{
				return $v;
			}
		}

		return false;
	}

	public function getSideFromSide (Dolumar_Underworld_Models_Side $side)
	{
		foreach ($this->getSides () as $v)
		{
			if ($v->equals ($side))
			{
				return $v;
			}
		}

		return $side;
	}

	public function getPlayerSide (Dolumar_Players_Player $player)
	{
		//return new Dolumar_Underworld_Models_Side (1);
		$clan = $player->getMainClan ();
		if ($clan)
		{
			if ($side = $this->getSide ($clan))
			{
				return $side;
			}
			else
			{
				return new Dolumar_Underworld_Models_Side (0);		
			}
		}
		else
		{
			return new Dolumar_Underworld_Models_Side (0);
		}
	}

	public function createArmy (
		Dolumar_Underworld_Map_Locations_Location $location, 
		Dolumar_Underworld_Models_Side $side, 
		array $squads
	)
	{
		if (count ($squads) === 0)
		{
			return false;
		}

		$army = new Dolumar_Underworld_Models_Army (null);
		$army->setSide ($side);
		$army->setLocation ($location);

		Dolumar_Underworld_Mappers_ArmyMapper::create ($this, $army);

		$players = array ();
		foreach ($squads as $v)
		{
			$army->addSquad ($v);
			$player = $squads[0]->getVillage ()->getOwner ();
			$players[$player->getId ()] = $player;
		}

		foreach ($players as $player)
		{		
			$army->promote_nocheck ($player);
		}

		$this->getMap ()->addMapUpdate ($location, 'BUILD');

		// Notify objective
		$this->getObjective ()->onSpawn ($army);

		return $army;
	}

	public function getSpawnpoints (Dolumar_Underworld_Models_Side $side)
	{
		return $this->getMap ()->getFreeSpawnPoints ($side);
	}

	public function getSpawnpointsFromGroupId (Dolumar_Underworld_Models_Side $side, $id)
	{
		$out = array ();
		foreach ($this->getSpawnpoints ($side) as $v)
		{
			if ($v->getGroup ()->getId () == $id)
			{
				$out[] = $v;
			}
		}
		return $out;
	}

	/**
	* Should return an army object
	*/
	public function addSquads (Neuron_GameServer_Player $player, Dolumar_Underworld_Models_Side $side, array $squads, $spawnpointId = null)
	{
		if (!isset ($spawnpointId))
		{
			// Check for free spawn points
			$spawnpoints = $this->getSpawnpoints ($side);
		}
		else
		{
			// Load from id
			$spawnpoints = $this->getSpawnpointsFromGroupId ($side, $spawnpointId);
		}

		if (count ($spawnpoints) > 0)
		{
			// Create an army and put on random spawn point
			$spawnpoint = $spawnpoints[mt_rand (0, count ($spawnpoints) - 1)];
			$army = $this->createArmy ($spawnpoint, $side, $squads);

			// Log this shit
			$this->getLogger ()->spawn ($player, $army, $spawnpoint);

			return $army;
		}
		else
		{
			return false;
		}
	}

	public function getMap ()
	{
		if (!isset ($this->map))
		{
			$this->map = new Dolumar_Underworld_Map_Map ($this);
		
			$this->map->setBackgroundManager (new Dolumar_Underworld_Map_BackgroundManager ($this->getMapName (), $this));
			$this->map->setMapObjectManager (new Dolumar_Underworld_Map_ObjectManager ($this));
			
			$player = Neuron_GameServer::getPlayer ();
			
			if ($player)
			{
				$side = $this->getPlayerSide ($player);
			}
			else
			{
				$side = new Dolumar_Underworld_Models_Side (0);
			}
			
			// Fog of war
			if (!isset ($_SESSION['debug']))
			{
				$this->map->setFogOfWar (new Dolumar_Underworld_Map_FogOfWar ($this, $side));
			}
		}
		
		return $this->map;
	}

	public function getObjective ()
	{
		if (!isset ($this->objective))
		{
			$this->objective = Dolumar_Underworld_Models_Objectives_Objectives::getObjective ($this);
		}

		return $this->objective;
	}

	public function onMove (Dolumar_Underworld_Models_Army $army, Neuron_GameServer_Map_Location $old, Neuron_GameServer_Map_Location $new)
	{
		$this->getObjective ()->onMove ($army, $old, $new);
	}

	public function getLogger ()
	{
		if (!isset ($this->logger))
		{
			$this->logger = new Dolumar_Underworld_Models_Logger ($this);
		}

		return $this->logger;
	}

	/**
	* Remove
	*/
	public function destroy ()
	{
		Dolumar_Underworld_Mappers_MissionMapper::remove ($this);
	}
}