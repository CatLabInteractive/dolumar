<?php
class Dolumar_Players_Village 
	extends Neuron_Core_ModuleFactory 
	implements Neuron_GameServer_Interfaces_Logable
{
	private $id;
	private $data = null;

	private $myUnits = array ();
	private $unitCapacityCount = array ();
	private $unitCapacity = null;

	private $error = null;
	private $doCronStuff = true;
	
	private $isLoaded = array ();
	
	private $specialUnits = null;
	
	private $oBoosts = array ();
	
	private $oEffects = array ();
	
	private $bsync = NOW;
	private $isBattleProccessed = false;

	private $isInitialized = true;

	private static $instances = array ();
	
	/*
		To enable the factory to do it's work,
		overload the loadModule function
		
		Return an object of the required module
		The factory will make sure that it is only loaded once
	*/
	protected function loadModule ($sModule)
	{
		$classname = 'Dolumar_Players_Village_'.ucfirst ($sModule);
		if (!class_exists ($classname))
		{
			throw new Exception ('Module '.$sModule.' ('.$classname.') does not exist.');
		}
		return new $classname ($this);
	}
	
	public static function getVillage ($id, $syncBattle = NOW, $cronStuff = false, $noStatic = false)
	{
		$in = self::$instances;	
		
		$id = intval ($id);
		
		if (empty ($id) || $id == 0)
		{
			return new Dolumar_Players_DummyVillage ();
		}
		
		$lock = Neuron_Core_Lock::__getInstance ();
		
		if (!defined ('DISABLE_STATIC_FACTORY') && !$noStatic)
		{
			// Initialize the village
			if (!isset ($in[$id]) || !$in[$id]->isInitialized)
			{
				$in[$id] = new Dolumar_Players_Village ($id, $cronStuff);
			}
			
			$village = $in[$id];
		}
		else
		{
			$village = new Dolumar_Players_Village ($id, $cronStuff);
		}
		
		$village->setBattleProcessData ($syncBattle);
		
		return $village;
	}
	
	public function processBattles ()
	{
		if (!$this->isBattleProccessed)
		{
			$this->isBattleProccessed = true;
			$this->battle->processBattles ($this->bsync);
		}
	}
	
	public static function getMyVillage ($id)
	{
		$login = Neuron_Core_Login::__getInstance ();
		
		$village = self::getVillage ($id);
		if (!$village || $village instanceof Dolumar_Players_DummyVillage)
		{
			return false;
		}
		
		if ($village->getOwner () && $village->getOwner ()->getId () == $login->getUserId ())
		{
			return $village;
		}
		
		return false;
	}
	
	public static function getVillageFromId ($id)
	{
		return self::getVillage ($id, false);
	}
	
	public static function getFromId ($id)
	{
		return self::getVillage ($id, false);
	}
	
	public function __construct ($id, $cronStuff = true)
	{
		$this->id = $id;
		$this->doCronStuff = $cronStuff;
	}
	
	public function setBattleProcessData ($date)
	{
		$this->bsync = $date;
	}
	
	public function getScore ()
	{
		return $this->getNetworth ();
	}
	
	public function getNetworth ()
	{
		$this->loadData ();
		return $this->data['networth'];
	}
	
	public function recalculateNetworth ()
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$oldscore = $this->data['networth'];
		
		//$this->reloadBuildings ();
		$buildings = $this->buildings->getBuildings ();
		
		$score = 0;
		if (is_array ($buildings))
		{
			foreach ($buildings as $v)
			{
				$score += (int)$v->getScore ();
			}
		}
	
		$score = floor ($score);
		
		$db->update
		(
			'villages',
			array
			(
				'networth' => ((int)$score),
				'networth_date' => time ()
			),
			"vid = '".$this->id."'"
		);

		$this->data['networth'] = $score;
		
		// Check if we're in a clan
		$clans = $this->getOwner ()->getClans ();
		foreach ($clans as $v)
		{
			$v->recalculateScore ();
		}
		
		if ($score != $oldscore)
		{
			// Notify 3rd party services of a possible score change
			$this->getOwner ()->updateScore ($score);
		}
	}
	
	public function getId ()
	{
		return $this->id;
	}
	
	public function setData ($data, $cronStuff = false)
	{
		$this->isLoaded['data'] = true;
		$this->data = $data;
		
		// Networth: recalculate every xx hours
		if (/*$cronStuff && */$this->data['networth_date'] < (time() - 60*60*48))
		{
			$this->recalculateNetworth ();
		}
	}
	
	/*
		This function returns all data that is loaded. This should only
		be used by the sub-class resources.
	*/
	public function getData ()
	{
		$this->loadData();
		return $this->data;
	}
	
	private function loadData ()
	{
		$db = Neuron_Core_Database::__getInstance ();
		if (!isset ($this->isLoaded['data']))
		{
			$this->isLoaded['data'] = true;
			$l = $db->select
			(
				'villages',
				array ('*'),
				"vid = '".$this->id."'"
			);
			
			if (count ($l) == 1)
			{
				$this->setData ($l[0], $this->doCronStuff);
			}
			
			else {
				$this->data = false;
			}
		}
	}
	
	public function reloadData () 
	{ 
		$this->data = null; 
		unset ($this->isLoaded['data']); 
	}
	
	public function isFound ()
	{
		$this->loadData ();
		return $this->data != false;
	}
	
	/*
		Returns TRUE if this village is active.
	*/
	public function isActive ()
	{
		$this->loadData ();
		
		// Fetch towncenter
		$tc = $this->buildings->getTowncenter ();
		return $this->isFound () && $this->data['isActive'] && $tc !== false;
	}
	
	public function getName ()
	{
		$this->loadData ();
		return $this->data['vname'];
	}
	
	public function getDisplayName ()
	{
		/*
		return '<a href="javascript:void(0);" '.
			'onclick="openWindow (\'villageProfile\',{\'village\':'.$this->getId ().'});">'.
			Neuron_Core_Tools::output_varchar ($this->getName ()).'</a>';
		*/
		
		$nickname = Neuron_Core_Tools::output_varchar ($this->getName ());
		$string = Neuron_URLBuilder::getInstance ()->getOpenUrl ('VillageProfile', $nickname, array ('village' => $this->getId ()));
		
		return $string;
	}
	
	public function setName ($name)
	{
		$this->loadData ();

		if (Neuron_Core_Tools::checkInput ($name, 'village'))
		{
			if ($this->isFound ())
			{
				// Check if this name exists
				$db = Neuron_Core_Database::__getInstance ();
				
				$found = $db->select
				(
					'villages',
					array ('vid'),
					"vname = '{$db->escape($name)}' AND vid != {$this->getId()}"
				);
				
				if (count ($found) == 0)
				{
					$db->update
					(
						'villages',
						array ('vname' => $name),
						"vid = '".$this->id."'"
					);
				
					$this->data['vname'] = $name;
					
					return true;
				}
				else
				{
					$this->error = 'village_name_duplicate';
					return false;
				}
			}
			else
			{
				$this->error = 'village_not_found';
				return false;
			}
		}
		else
		{
			$this->error = 'invalid_village_syntax';
			return false;
		}
	}
	
	public function getOwner ()
	{
		$this->loadData ();
		
		$id = $this->data['plid'];
		
		if ($id)
		{
			return Neuron_GameServer::getPlayer ($id);
		}
		else
		{
			return new Dolumar_Players_NPCPlayer (0);

			throw new Neuron_Core_Error ('owner for this village not defined.');
			return false;
		}
	}
	
	public function getRace ()
	{
		$this->loadData ();
		return Dolumar_Races_Race::getRace ($this->data['race']);
	}
	
	public function getBuildingRadius ()
	{
		return min (MAXBUILDINGRADIUS, 8 + $this->buildings->getBuildingLevel($this->buildings->getTownCenter()));
	}

	public function readyToBuild ()
	{
		$race = $this->getRace ();
		
		// Call the race check to determine if a new building can be constructed
		return $race->readyToBuild ($this);
	}
	
	public function onBuild ($building)
	{
		$this->recalculateNetworth ();

		// Trigger onBuild gametrigger.
		$login = Neuron_Core_Login::__getInstance ();
		$owner = $this->getOwner ();

		// Only trigger this for active user.
		if ($login->getUserId () == $owner->getId ())
		{
			$owner->updateProfilebox ();
		}

		// Add log
		$objLogs = Dolumar_Players_Logs::__getInstance ();
		$objLogs->addBuildBuilding ($this, $building);
		
		// Let's check for quests
		$this->getOwner ()->quests->evaluate ();

		reloadStatusCounters ();
	}

	public function onDestroy ($building, $log = true)
	{
		// Trigger gametrigger.
		$login = Neuron_Core_Login::__getInstance ();
		$owner = $this->getOwner ();
		
		if ($log)
		{
			$objLogs = Dolumar_Players_Logs::__getInstance ();
			$objLogs->addDestructBuilding ($this, $building);
		}
		
		$this->recalculateNetworth ();
		
		$this->buildings->reloadBuildings ();

		reloadStatusCounters ();
	}
	
	public function onBattleFought ($battle)
	{
		foreach ($this->getEffects () as $v)
		{
			$v->onBattleFought ($battle);
		}
	}
	
	public function onUpgrade ($building)
	{
		$this->recalculateNetworth ();
	}
	
	public function getRank ()
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$rows = $db->getDataFromQuery ($db->customQuery
		("
			SELECT 
				COUNT(*) AS rank
			FROM
				villages a 
			INNER JOIN
				villages b ON (a.networth < b.networth OR (a.networth = b.networth AND a.vid < b.vid)) AND b.isActive = 1
			WHERE
				a.vid = '".$this->getId ()."'
			GROUP BY a.vid
		"));
		
		$total = $db->select
		(
			'villages',
			array ('count(vid) AS total')
		);

		if (count ($rows) > 0)
		{
			$rank = $rows[0]['rank'];
		}
		else
		{
			$rank = 0;
		}
		
		return array ($rank + 1, $total[0]['total']);
	}

	/**
	 * Returns the resources required for a scout
	 * @param int $runesToScout
	 * @return array
	 */
	public function getScoutLandsCost ($runesToScout = 4)
	{
		$runes = max (1, $this->resources->getTotalRunes (false));
		
		$res = (2 * $runes * $runes + 35 * $runes + 1000);

		$res = ($res / 4) * $runesToScout;
		
		return array
		(
			'grain'	=> ceil ($res * 1.5),
			'wood'	=> ceil ($res),
			'stone'	=> ceil ($res)
		);
	}

	/**
	 * @param int $runes
	 * @return float
	 */
	public function getScoutLandsDuration ($runes = 4)
	{		
		// Should be based ont he amout of runes you have
		// Very simple: total runes / 4 ;-)
		$done = $this->resources->getTotalRunes (false) / 4;
		$total = ceil (((60 * 60 * 1) + ($done * 60 * 60 * 0.5)) / GAME_SPEED_RESOURCES);

		return ($total / 4) * $runes;
	}

	public function speedupScouting ($id, $amount)
	{
		$db = Neuron_DB_Database::getInstance ();

		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		$profiler->start ('Speeding up scouting ' . $id . ' with ' . $amount . ' seconds.');

		$id = intval ($id);
		$data = $this->getScoutData ($id);

		$amount = intval ($amount);

		if ($data)
		{
			$timeLeft = $data['finishDate'] - NOW;

			if ($amount > $timeLeft)
			{
				$amount = $timeLeft;
			}

			$db->query
			("
				UPDATE
					villages_scouting
				SET
					finishDate = finishDate - {$amount}
				WHERE
					scoutId = {$id} AND
					vid = {$this->getId ()}
			");
		}

		else
		{
			$profiler->message ('Order was not found.');
		}

		$profiler->stop ();
	}

	public function getScoutData ($id)
	{
		$db = Neuron_DB_Database::getInstance ();

		$id = intval ($id);

		$data = $db->query
		("
			SELECT
				*
			FROM
				villages_scouting
			WHERE
				scoutId = {$id} AND
				vid = {$this->getId ()}
		");

		if (count ($data) > 0)
		{
			$v = $data[0];

			return array
			(
				'id' => $v['scoutId'],
				'finishDate' => $v['finishDate'],
				'runes' => $v['runes']
			);
		}

		return null;
	}

	/**
	 * @param int $runes
	 * @return bool
	 */
	public function scout ($runes)
	{
		$cost = $this->getScoutLandsCost ($runes);

		if ($this->resources->takeResourcesAndRunes ($cost))
		{
			$this->scoutForRunes ($runes);
			return true;
		}

		return false;
	}

	/**
	 * @param $runes
	 * @return array $runes
	 */
	private function getScoutRandomRunes ($runes)
	{
		$originalpool = array ('earth', 'fire', 'water', 'wind');
		$pool = array ();

		$out = array ();

		for ($i = 0; $i < $runes; $i ++)
		{
			if (count ($pool) == 0)
			{
				$pool = $originalpool;
				shuffle ($pool);
			}

			$key = array_shift ($pool);

			if (!isset ($out[$key]))
			{
				$out[$key] = 1;
			}
			else
			{
				$out[$key] ++;
			}
		}

		return $out;
	}

	/**
	 * @param int $amountRunes
	 */
	public function scoutForRunes ($amountRunes)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$runes = '';
		foreach ($this->getScoutRandomRunes ($amountRunes) as $k => $v)
		{
			$runes .= $k . ':' . $v . '|';
		}
		$runes = substr ($runes, 0, -1);

		// Let's search for runes!
		$db->insert
		(
			'villages_scouting',
			array
			(
				'vid' => $this->id,
				'finishDate' => time() + $this->getScoutLandsDuration ($amountRunes),
				'runes' => $runes
			)
		);

		// Update the count
		$db->update
		(
			'villages',
			array
			(
				'runeScoutsDone' => '++'
			),
			"vid = '".$this->id."'"
		);

		// Refresh cache
		if (isset ($this->data['runeScoutsDone']))
		{
			$this->data['runeScoutsDone'] ++;
		}

		reloadStatusCounters ();
	}

	/**
	 * Look for npc villages, generate npc villages, ...
	 */
	public function lookForNPCs ()
	{
		$mapper = Dolumar_Mappers_BuildingMapper::getInstance ();
		$center = $this->getTownCenterLocation ();

		$buildings = $mapper->getBuildingsFromTypeWithinRadius ($center[0], $center[1], 1, 100);

		$npcs = array ();
		foreach ($buildings as $v)
		{
			if ($v->getVillage () instanceof Dolumar_Players_NPCVillage)
			{
				$npcs[] = $v;
			}
		}

		// No villages? Spawn new one
		if (count ($npcs) == 0)
		{
			// TODO
			$this->spawnNPC ();
		}
	}

	public function spawnNPC ()
	{
		// TODO
	}
	
	public function trainUnits ($unit, $amount, $building)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$duration = floor ($unit->getTrainingDuration ($this) * $amount);
		
		$db->insert
		(
			'villages_units',
			array
			(
				'vid' => $this->getId (),
				'unitId' => $unit->getUnitId (),
				'buildingId' => $building->getBuildingId (),
				'bid' => $building->getId (),
				'amount' => $amount,
				'village' => $this->getId (),
				'startTraining' => time (),
				'endTraining' => (time () + $duration)
			)
		);

		// Log
		$objLogs = Dolumar_Players_Logs::__getInstance ();
		$objLogs->addUnitTrained ($this, $unit, $amount);

		reloadStatusCounters ();
	}
	
	/*
		Return all units that are currently consuming resources.
	*/
	public function getConsumingUnits ()
	{
		$units = $this->getMyUnits ();
		return $units;
	}

	public function getMyUnits ($now = NOW)
	{
		$this->loadUnits ($now);
		return $this->myUnits[$now];
	}

	/*
		Returns the defending units in their defense slots
		(first it gets the real squads, then it starts inventing new squads)
	*/
	public function getDefendingUnits ($now = NOW, $amountOfSlots = null)
	{		
		// Fetch the defense slots (all of them.)		
		$slots = $this->getDefenseSlots ($amountOfSlots);
		
		// Fetch the available squads (even those that aren't yours.)
		$squads = $this->getSquads (false, true, false);
		
		$toAssign = array ();
		$out = array ();
		
		// Loop trough the squads & find their default slot
		foreach ($squads as $squad)
		{
			foreach ($squad->getUnits () as $unit)
			{
				// Get the slot.
				$slot = $unit->getDefaultSlot ();
				if ($slot > 0 && isset ($slots[$slot]))
				{
					// Slot is free
					if (!isset ($out[$slot]))
					{
						$out[$slot] = $unit;
						$unit->setBattleSlot ($slots[$slot]);
					}
					
					// Slot is taken: check priority
					else
					{
						if ($out[$slot]->getSlotPriority () > $unit->getSlotPriority ())
						{
							// Move this unit to the "assign" pile.
							$toAssign[] = $out[$slot];
						
							$out[$slot] = $unit;
							$unit->setBattleSlot ($slots[$slot]);
						}
						else
						{
							// Assign to a free slot LATER! (when all "assigned" slots are taken)
							$toAssign[] = $unit;
						}
					}
				}
				else
				{
					// Assign to a free slot LATER! (when all "assigned" slots are taken)
					$toAssign[] = $unit;
				}
			}
		}
		
		// Assign the non-assigned units (randomly)
		$freeslots = array ();
		foreach ($slots as $k => $v)
		{
			if (!isset ($out[$k]))
			{
				$freeslots[] = $k;
			}
		}
		
		// Shuffle the array
		shuffle ($freeslots);
		
		foreach ($toAssign as $unit)
		{
			$i = array_pop ($freeslots);
			
			// Break out of the loop if freeslots is empty.
			if ($i === null)
			{
				break;
			}
			
			$out[$i] = $unit;
			$out[$i]->setBattleSlot ($slots[$i]);
		}
		
		// If not full: invent squads
		if (count ($freeslots) > 0)
		{
			// Time to invent squads!
			$genericSquads = array ();
			
			// Fetch all units
			$free_units = $this->getAllUnits ($now);
			
			foreach ($free_units as $unit)
			{
				$amount = $unit->getSquadlessAmount ();
				$classname = get_class ($unit);
				$max = $unit->getMaxPerSquad ();
				
				while ($amount > 0)
				{
					$i = count ($genericSquads);
					
					$insquad = ($amount > $max) ? $max : $amount;
					$amount -= $max;

					$genericSquads[$i] = new $classname ($this);
					$genericSquads[$i]->addAmount ($insquad, $insquad, $insquad);
				}
			}
			
			shuffle ($genericSquads);
			
			foreach ($genericSquads as $unit)
			{
				$i = array_pop ($freeslots);
				if ($i > 0)
				{
					$out[$i] = $unit;
					$out[$i]->setBattleSlot ($slots[$i]);
				}
				else
				{
					break;
				}
			}
		}
		
		return $out;
	}

	public function getAllUnits ($now = NOW)
	{
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		
		$profiler->start ('Getting all units');
	
		$db = Neuron_Core_Database::__getInstance ();
		
		$buildings = $this->buildings->getBuildings ();

		$profiler->start ('Fetching my units');
		$units = $this->getMyUnits ($now);
		$profiler->stop ();
		
		$profiler->start ('Looping trough buildings & adding guards');
		foreach ($buildings as $v)
		{
			//if ($v->isFinished ($now))
			//{
				$u = $v->getGuards ();
				if ($u)
				{
					foreach ($u as $vv)
					{
						$k = $vv->getClassName ();

						if (isset ($units[$k]))
						{
							$units[$k]->mergeTroops ($vv);
						}
						else
						{
							$units[$k] = $vv;
							$units[$k]->setEternalLife (true);
						}
					}
				}
			//}
		}
		$profiler->stop ();
		
		$profiler->stop ();

		return $units;
	}

	public function reloadUnits ()
	{
		unset ($this->isLoaded['units']);
		
		$this->myUnits = array ();
		$this->unitCapacityCount = array ();
		$this->unitCapacity = null;
	}

	private function loadUnits ($now = NOW)
	{
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
	
		if (!isset ($this->myUnits[$now]))
		{
			$profiler->start ('Loading units');
			
			$profiler->start ('Loading units from database');
		
			$db = Neuron_Core_Database::__getInstance ();
			$dbi = Neuron_DB_Database::getInstance ();
			
			$l = $db->getDataFromQuery ($db->customQuery
			("
				SELECT
					u.*,
					units.unitName,
					SUM(squad_units.s_amount) AS amountInSquads
				FROM
					villages_units u
				LEFT JOIN
					units ON u.unitId = units.unitId
				LEFT JOIN
					squad_units ON u.unitId = squad_units.u_id AND squad_units.v_id = u.vid
				WHERE
					u.vid = ".$this->getId ()."
				GROUP BY uid
			"));
			$profiler->stop ();
			
			$profiler->start ('Loading squads in battle');

			// Load thze units in thze battle
			$bts = $db->getDataFromQuery ($db->customQuery
			("
				SELECT
					*
				FROM
					battle_squads
				LEFT JOIN
					squad_units ON battle_squads.bs_squadId = squad_units.s_id
				WHERE
					battle_squads.bs_vid = ".$this->getId ()."
			"));

			$squadsIB = array ();
			foreach ($bts as $bt)
			{
				if (isset ($squadsIB[$bt['u_id']]))
				{
					$squadsIB[$bt['u_id']] += $bt['s_amount'];
				}
				else
				{
					$squadsIB[$bt['u_id']] = $bt['s_amount'];
				}
			}
			$profiler->stop ();
			
			$profiler->start ('Loading supporting squads.');
			
			// Load thze squads that are not at home.
			$query = 
			("
				SELECT
					squad_units.u_id,
					squad_units.s_amount
				FROM
					villages_squads
				LEFT JOIN
					squad_units ON villages_squads.s_id = squad_units.s_id
				LEFT JOIN
					squad_commands ON (villages_squads.s_id = squad_commands.s_id 
						AND squad_commands.s_end > FROM_UNIXTIME(".NOW."))
				WHERE
					(
						villages_squads.v_id = {$this->getId()} AND
						villages_squads.s_village != 0 AND
						villages_squads.s_village != {$this->getId()}
					) AND squad_commands.s_action IS NULL
			");
			
			$supportingdb = $dbi->query ($query);
			
			$supporting = array ();
			foreach ($supportingdb as $v)
			{
				if (isset ($supporting[$v['u_id']]))
				{
					$supporting[$v['u_id']] += $v['s_amount'];
				}
				else
				{
					$supporting[$v['u_id']] = $v['s_amount'];
				}
			}
			
			$profiler->stop ();
			
			$profiler->start ('Counting the units');

			$o = array ();
			foreach ($l as $v)
			{
				// Calculate the amount (for training)
				if ($v['endTraining'] < $now)
				{
					$amount = $v['amount'] -  $v['killedAmount'];
				}

				else
				{
					$duration = max (1, $v['endTraining'] - $v['startTraining']);
					$procent = max (0, $now - $v['startTraining']) / $duration;
					$amount = floor ($procent * $v['amount']) - $v['killedAmount'];
				}
				
				$toFeed = $amount;

				// Make new class + withdraw units in combat
				if (!isset ($o[$v['unitName']]))
				{
					$o[$v['unitName']] = Dolumar_Units_Unit::getUnitFromName 
					(
						$v['unitName'], 
						$this->getRace (), 
						$this
					);
					
					// Calculate the amount of "gone" troops".
					$away = isset ($supporting[$v['unitId']]) ? $supporting[$v['unitId']] : 0;
					$toFeed -= $away;

					if (isset ($squadsIB[$v['unitId']]))
					{
						$available = $amount - $squadsIB[$v['unitId']];
					}
					else
					{
						$available = $amount;
					}

					$o[$v['unitName']]->putInSquads ($v['amountInSquads']);
				}

				else
				{
					$available = $amount;
					$inSquads = 0;
				}

				// Increase amounts
				if ($v['village'] == $this->getId ())
				{
					$o[$v['unitName']]->addAmount ($available, $toFeed, $amount);
				}

				else
				{
					$o[$v['unitName']]->addAmount (0, $toFeed, $amount);
				}

				// Unit capacity counter
				if (isset ($this->unitCapacityCount[$v['buildingId']]))
				{
					$this->unitCapacityCount[$v['buildingId']]['current'] += $amount * $o[$v['unitName']]->getRequiredSpace();
					$this->unitCapacityCount[$v['buildingId']]['absolute'] += ($v['amount'] - $v['killedAmount']) * $o[$v['unitName']]->getRequiredSpace();
				}

				else
				{
					$this->unitCapacityCount[$v['buildingId']] = array
					(
						'current' => $amount * $o[$v['unitName']]->getRequiredSpace(),
						'absolute' => ($v['amount'] - $v['killedAmount']) * $o[$v['unitName']]->getRequiredSpace()
					);
				}
			}
			
			$profiler->stop ();

			$this->myUnits[$now] = $o;
			
			$profiler->stop ();
		}
	}

	public function removeUnits (Dolumar_Units_Unit $unit, $arAmount)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$amount = $arAmount;

		// Remove items
		foreach ($unit->getEquipment () as $v)
		{
			$this->removeEquipment ($v, $arAmount);
		}
		
		$unitid = $unit->getUnitId ();
		$vid = $this->getId ();

		// Lock the table
		$db->customQuery ('LOCK TABLES villages_units WRITE');

		// Select my units
		$units = $db->select
		(
			'villages_units',
			array ('amount', 'killedAmount', 'uid'),
			"vid = '".$vid."' AND unitId = '".$unitid."'",
			'endTraining ASC'
		);

		$removeWhere = "";
		$removes = 0;
		
		reset ($units);
		foreach ($units as $v)
		{
			if ($amount > 0)
			{
				$actAmount = $v['amount'] - $v['killedAmount'];
				if ( ($actAmount - $amount) <= 0)
				{
					$removeWhere .= "uid = '{$v['uid']}' OR ";
					$removes ++;
				}

				else
				{
					// withdraw
					$db->update
					(
						'villages_units',
						array ('killedAmount' => '++'.$amount),
						"uid = '{$v['uid']}'"
					);
				}

				$amount -= $actAmount;
			}

			else
			{
				break;
			}
		}

		// Remove them
		if ($removes > 0)
		{
			$removeWhere = substr ($removeWhere, 0, -4);
			$db->remove
			(
				'villages_units',
				$removeWhere
			);
		}

		$db->customQuery ('UNLOCK TABLES');
		
		// Now remove the units from the class
		$unit->removeAmount ($arAmount);

		// Now remove them from squads etc
		$unit->onKillUnits ($arAmount);
	}
	
	/*
		Return the number of spots available for
		troops.
	*/
	public function getOverallUnitCapacity ()
	{
		$this->loadUnitCapacity ();
		
		// Count the sum of all capacities
		$capacity = 0;
		foreach ($this->unitCapacity as $v)
		{
			$capacity += $v;
		}
		
		return $capacity;
	}

	public function getUnitCapacity ($building)
	{
		$this->loadUnitCapacity ();
		$bid = $building->getBuildingId ();
		if (isset ($this->unitCapacity[$bid]))
		{
			return $this->unitCapacity[$bid];
		}
		else
		{
			return 0;
		}
	}

	public function getUnitCapacityStatus ($building, $fetch = 'current')
	{	
		$capacity = $this->getUnitCapacity ($building);
		$units = $this->getUnitBuildingCount ($building, $fetch);
		return min (100, floor ( ($units / max (1, $capacity)) * 100 ));
	}

	public function getUnitBuildingCount ($building, $fetch = 'current')
	{
		if ($fetch != 'absolute')
		{
			$fetch = 'current';
		}
	
		$this->loadUnits ();
		$bid = $building->getBuildingId ();
		if (isset ($this->unitCapacityCount[$bid]))
		{
			$units = $this->unitCapacityCount[$bid][$fetch];
		}
		else
		{
			$units = 0;
		}

		return $units;
	}

	private function loadUnitCapacity ()
	{
		if ($this->unitCapacity == null)
		{
			$capacity = 0;
			$buildings = $this->buildings->getBuildings ();
			
			$this->unitCapacity = array ();
			
			foreach ($buildings as $v)
			{
				if ($v->isFinishedBuilding ())
				{
					$capacity = $v->getUnitCapacity ();
					if ($capacity)
					{
						$bid = $v->getBuildingId ();
						if ($capacity && isset ($this->unitCapacity[$bid]))
						{
							$this->unitCapacity[$bid] += $capacity;
						}
						else
						{
							$this->unitCapacity[$bid] = $capacity;
						}
					}
				}
			}
		}
	}
	
	/*
		Special units
	*/
	private function loadSpecialUnits ()
	{
		if ($this->specialUnits === null)
		{
			$buildings = $this->buildings->getBuildings ();
		
			$db = Neuron_Core_Database::__getInstance ();
			$db2 = Neuron_DB_Database::__getInstance ();
			
			$l = $db->select
			(
				'battle_specialunits',
				array ('*'),
				"bsu_vid = ".$this->getId ()
			);
			
			$inBattle = array ();
			foreach ($l as $v)
			{
				$inBattle[$v['bsu_vsu_id']] = true;
			}
			
			$l = $db->select
			(
				'villages_specialunits',
				array ('*'),
				"v_id = ".$this->getId ()." AND vsu_tEndDate < ".time()
			);
			
			$this->specialUnits = array 
			(
				'all' => array (),
				'available' => array ()
			);
			
			foreach ($l as $v)
			{
				if (isset ($buildings[$v['vsu_bid']]))
				{
					$obj = $buildings[$v['vsu_bid']]->getSpecialUnit ();
					$obj->setId (intval ($v['vsu_id']));
					
					// Set current location
					if (intval ($v['vsu_location']) > 0)
					{
						$obj->setLocation 
						(
							self::getVillage ($v['vsu_location']), 
							$db2->toUnixtime ($v['vsu_moveStart']), 
							$db2->toUnixtime ($v['vsu_moveEnd'])
						);
					}
					
					$this->specialUnits['all'][$v['vsu_id']] = $obj;
					
					if (!isset ($inBattle[$v['vsu_id']]))
					{
						$this->specialUnits['available'][$v['vsu_id']] = $obj;
					}
				}
				else
				{
					// Building is gone, destroy unit
					$db->remove ('villages_specialunits', "vsu_id = ".$v['vsu_id']);
				}
			}
		}
	}
	
	public function getSpecialUnits ($alsoUnavailable = false)
	{
		$this->loadSpecialUnits ();
		
		if ($alsoUnavailable)
		{
			return $this->specialUnits['all'];
		}
		else
		{
			return $this->specialUnits['available'];
		}
	}
	
	public function getSpecialUnit ($id, $alsoUnavailable = false)
	{
		$units = $this->getSpecialUnits ($alsoUnavailable);
		foreach ($units as $v)
		{
			if ($v->getId () === intval ($id))
			{
				return $v;
			}
		}
		return false;
	}
	
	/*
		Battle
	*/
	public function getAttackSlots ($oTargetVillage)
	{
		return $oTargetVillage->battle->getDefenseSlots ();
	}
	
	public function getDefenseSlots ($amount = null)
	{
		return $this->battle->getDefenseSlots ($amount);
	}
	
	/*
		$specialUnits = array (array ($objUnit, $sAction));
	*/
	public function attackVillage ($oTarget, $oUnits, $specialUnits = array ())
	{
		return $this->battle->attackVillage ($oTarget, $oUnits, $specialUnits);
	}

	public function getBuildingsToDestroy ($amount)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		if ($this->getOwner()->isPlaying ())
		{
			$tc = $this->buildings->getTownCenter ();
			$loc = $tc->getLocation ();
			
			// Load my buildings
			$buildings = $db->select
			(
				'map_buildings',
				array ('*', "SQRT(POW(xas-'{$loc[0]}',2)+POW(yas-'{$loc[1]}',2)) AS afstand"),
				"village = '".$this->getId ()."' AND buildingType > '1' AND destroyDate = '0'",
				'afstand DESC',
				ceil ($amount)
			);
	
			$o = array ();
			$i = 0;
			foreach ($buildings as $v)
			{
				$o[$i] = Dolumar_Buildings_Building::getBuilding ($v['buildingType'], $this->getRace (), $v['xas'], $v['yas']);
				$o[$i]->setData ($v['bid'], $v);
				$o[$i]->setVillage ($this);
				$i ++;
			}
			return $o;
		}
		else
		{
			return array ();
		}
	}

	public function stealRunes ($amount)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		// Shizzle the wizzle
		$amount = floor ($amount);
		
		$totalRunes = $this->resources->getTotalRunes ();

		// You shall not destroy the town center.
		if ($amount > ($totalRunes - 5))
		{
			$amount = max (0, $totalRunes - 5);
		}

		$runes = $this->resources->getRunes ();
		$okay = true;

		// sqrt (pow ($loc1[0] - $loc2[0], 2) + pow ($loc1[1] - $loc2[1], 2) )
		$buildings = $this->getBuildingsToDestroy ($amount);

		$buildingIndex = 0;

		$output = array ();
		$sum = 0;

		while ($sum < $amount && $okay)
		{
			if (count ($runes) > 0)
			{
				// Pick random rune.
				$keys = array_keys ($runes);
				$k = $keys[rand (0, count ($keys) - 1)];
				
				$v = $runes[$k];

				if ($v == 0)
				{
					unset ($runes[$k]);
				}

				else
				{
					// Remove - or at least lower - the amount of available runes.
					if ($v == 1)
						unset ($runes[$k]);
					else
						$runes[$k] --;

					// Add rune
					if (!isset ($output[$k]))
						$output[$k] = 1;
					else
						$output[$k] ++;

					$sum ++;
				}
			}

			elseif (count ($buildings) > 0)
			{
				// Start destructing
				if (isset ($buildings[$buildingIndex]))
				{
					//$runes = $buildings[$buildingIndex]->getUsedRunes ();
					
					$res = $buildings[$buildingIndex]->getUsedResources (true, true);

					if (isset ($res['runeId']) && $res['runeAmount'] > 0)
					{
						// destroy the building
						$buildings[$buildingIndex]->destructBuilding ();
						
						// calculate the amount of runes we still need to steal
						$tekort = $amount - $sum;
						
						// if this building generate more runes than needed, only give the needed
						$runeAmount = $res['runeAmount'];
						if ($tekort < $runeAmount)
						{
							$runeAmount = $tekort;
						}
					
						// Add rune to the "steal pool"
						if (!isset ($output[$res['runeId']]))
							$output[$res['runeId']] = $runeAmount;
						else
							$output[$res['runeId']] += $runeAmount;

						// Increment i so that it includes the last additions.
						$sum += $runeAmount;
					}
					
					$buildingIndex ++;
				}
			}

			// All runes are stolen. Forget it. Go home.
			else
			{
				$okay = false;
			}
		}
		
		$this->resources->reloadRunes ();

		// Remove the runes
		$stolenRunes = array ();
		foreach ($output as $k => $v)
		{
			if ($this->resources->removeRune ($k, $v))
			{
				$stolenRunes[$k] = $v;
			}
			else
			{
				customMail
				(
					'daedelson@gmail.com',
					'Dolumar: Not enough runes!',
					"Battle stealing error line 2143:\n\n".
					(Neuron_GameServer_Server::getInstance ()->getServerName ()).
					print_r ($output, true)."\n\n".
					print_r ($this, true)
				);
			}
		}
		
		// After the battle: reload runes
		//$this->resources->reloadRunes (); shouldn't  be necesarry. Let's limit the mysql queries.
		$this->buildings->reloadBuildings ();
		
		return $stolenRunes;
	}



	/*
		$since and $now are two timestamps.
		The bonus returned should be the average bonus between that time.
		
		The income bonus returns the hourly income bonus!
	*/
	public function procBonusses ($function, $arguments = array (), $now = NOW, $since = NOW)
	{
		$profiler = Neuron_Profiler_Profiler::getInstance ();
		
		$profiler->start ('Processing bonusses '.$function);
	
		// If $now is lower then $since, $since is probably empty.
		if ($since > $now)
		{
			$since = $now;
		}
	
		// First: technologies
		//$this->loadTechnology ();

		/*
		if (!isset ($arguments[0]))
		{
			$arguments[0] = array ();
		}
		*/
		
		$profiler->start ('Processing technologies');
		
		$profiler->start ('Loading technologies');
		$technologies = $this->getTechnologies ();
		$profiler->stop ();
		
		$profiler->start ('Processing technologies');
		foreach ($technologies as $technology)
		{
			if (method_exists ($technology, $function))
			{
				$arguments[0] = call_user_func_array (array ($technology, $function), $arguments);
			}
		}
		$profiler->stop ();
		$profiler->stop ();
		
		// Second: spells
		// This time, the $now and $then variables are higly important. We only want spells that are
		// active between $since and $now.
		$profiler->start ('Processing active effects');
		
		$profiler->start ('Loading effects');
		$boosts = $this->getActiveBoosts ($since, $now);
		$profiler->stop ();
		
		$profiler->start ('Processing effects');
		foreach ($boosts as $v)
		{
			if (method_exists ($v, $function))
			{
				$arguments[0] = call_user_func_array (array ($v, $function), $arguments);
			}
		}
		$profiler->stop ();
		$profiler->stop ();
		
		$profiler->stop ();
		
		return $arguments[0];
	}
	
	/**********************************************************************************
		TECHNOLOGY BACKWARDS COMPATIBEL
	***********************************************************************************/
	/*
		Return an array of technology objects
	*/
	public function getTechnologies ()
	{
		return $this->technology->getTechnologies ();
	}

	/* 
		Technology settings
	*/
	public function hasTechnology ($s_technology, $includeTraining = false)
	{
		return $this->technology->hasTechnology ($s_technology, $includeTraining);
	}

	/*
		Train a technology
	*/
	public function trainTechnology ($technology)
	{
		return $this->technology->trainTechnology ($technology);
	}
	
	/**********************************************************************************
		MAGIC
	***********************************************************************************/
	public function clearEffects ()
	{
		$this->oEffects = array ();
	}
	
	public function addEffect ($effect)
	{
		$this->oEffects[] = $effect;
	}
	
	public function getEffects ($since = NOW, $now = NOW)
	{
		return $this->getActiveBoosts ($since, $now);
	}
	
	public function getActiveBoosts ($since = NOW, $now = NOW)
	{
		if ($now < $since)
		{
			$since = $now;
		}
	
		if (!isset ($this->oBoosts[$now.'_'.$since]))
		{			
			$this->oBoosts[$now.'_'.$since] = array ();
		
			// Fetch all spells that were active
			$db = Neuron_Core_Database::__getInstance ();
		
			$l = $db->getDataFromQuery
			(
				$db->customQuery
				("
					SELECT
						*,
						SUBSTR( b_ba_id, (LOCATE( ':', b_ba_id )+1) ) AS level
					FROM
						boosts
					WHERE
						b_targetId = {$this->getId ()}
						AND 
						(
							(b_start <= {$now} AND b_end >= {$now}) 	/* All boosts that are active now */
							OR (b_start <= {$since} AND b_end >= {$since}) 	/* All boosts that have been active but ended */
							OR (b_start >= {$since} AND b_end <= {$now}) 	/* All boosts that are active and do not end yet */
						)
					ORDER BY
						level DESC,
						b_start
				")
			);

			$unique = array ();
			$timeDuration = $now - $since;
			
			$fkeyname = $now.'_'.$since;
			
			$setBoosts = array ();
			
			foreach ($l as $v)
			{
				if (isset ($setBoosts[$v['b_ba_id']]))
				{
					continue;
				}

				$setBoosts[$v['b_ba_id']] = true;
				
			
				// Check the boost start & end dates
				$start = $v['b_start'];
				$end = $v['b_end'];
				
				//echo 'Current time: ('.$since . ' - ' . $now . ")\n";
				//echo 'Boost time:   ('.$start . ' - ' . $end . ")\n";
			
				if ($since == $now)
				{
					$persistence = 1;
				}
				else
				{
					if ($since <= $start && $now >= $end)
					{
						$duration = $end - $since;
					}
					elseif ($since <= $start && $now <= $end)
					{
						$duration = $now - $start;
					}
					elseif ($since >= $start && $now >= $end)
					{
						$duration = $end - $since;
					}
					else
					{
						$duration = $now - $since;
					}
			
					// Persistence
					$persistence = $duration / $timeDuration;
				}
			
				switch ($v['b_type'])
				{
					case 'spell':
						$obj = Dolumar_Effects_Boost::getFromId ($v['b_ba_id']);
						
						$classname = $obj->getClassName ();
						
						if 
						(
							$obj &&
							(
								!isset ($this->oBoosts[$fkeyname][$classname]) ||
								$this->oBoosts[$fkeyname][$classname]->getLevel () < $obj->getLevel ()
							)
						)
						{
							$obj->setVillage ($this);
						
							$obj->setDates ($v['b_start'], $v['b_end']);
							$obj->setPersistence ($persistence);
							
							$actor = self::getFromId ($v['b_fromId']);
							
							$isPublic = intval ($v['b_secret']) == 0 /*|| $actor->getId () == $this->getId ()*/;
							
							$obj->setActor ($actor, !$isPublic);
							
							$obj->setBoostId ($v['b_id']);
							
							// Add to the array
							$this->oBoosts[$fkeyname][$classname] = $obj;
						}
					break;
				}
			}
		}
		
		return array_merge (array_values ($this->oBoosts[$now.'_'.$since]), $this->oEffects);
	}
	
	public function reloadEffects ()
	{
		$this->reloadActiveBoosts ();
	}
	
	public function reloadActiveBoosts ()
	{
		$this->oBoosts = array ();
	}

	/**********************************************************************************
		SQUADS
	***********************************************************************************/
	/*
		Called when vacation mode starts
	*/
	public function withdrawAllUnits ()
	{
		$wenthome = 0;
	
		$squads = $this->getSquads ();
		foreach ($squads as $v)
		{
			if (!$v->isHome ())
			{
				$v->goHome ();
				$wenthome ++;
			}
		}
		
		return $wenthome > 0;
	}
	
	/*
		Send all units that are currently supporting this village home
	*/
	public function sendSupportingUnitsHome ()
	{
		$wenthome = 0;
	
		$squads = $this->getSquads (false, false, false);
		foreach ($squads as $v)
		{
			if (!$v->getVillage ()->equals ($this) && !$v->isHome ())
			{
				$v->goHome ();
				$wenthome ++;
			}
		}
		
		return $wenthome > 0;
	}
	
	public function getSquads ($id = false, $onlyAvailable = false, $onlyMine = true)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		// Where clause
		if ($onlyMine)
		{
			if ($onlyAvailable)
			{
				$where = "(v_id = ".$this->getId ()." AND (s_village = ".$this->getId ()." OR s_village = 0))";
			}
			else
			{
				// Show all your units
				$where = "v_id = ".$this->getId ();
			}
		}
		elseif ($onlyAvailable)
		{
			// All units that are currently located in your village. (so not those who are away
			$where = "((v_id = ".$this->getId ()." AND s_village = 0) OR s_village = ".$this->getId ().")";
		}
		else
		{
			// ALL squads YOU own + all those that are in your vilage.
			$where = "((v_id = ".$this->getId ().") OR s_village = ".$this->getId ().")";
		}
		
		// ID
		if ($id)
		{
			$where .= " AND s_id = ".intval ($id);
		}

		$squads = $db->select
		(
			'villages_squads',
			array ('*'),
			$where
		);

		$notAvailable = array ();
		if ($onlyAvailable)
		{
			// Load "in combat" squads
			$inCombat = $db->select
			(
				'battle_squads',
				array ('bs_squadId'),
				"bs_vid = ".$this->getId ()
			);

			foreach ($inCombat as $v)
			{
				$notAvailable[$v['bs_squadId']] = true;
			}
			
			// Load all squads that are moving to this village
			// Mind that the troops that are moving to another location, 
			// will have been filtered before this point.
			$moving = $db->select
			(
				'squad_commands',
				array ('s_id'),
				"s_to = ".$this->getId ()." AND s_end > FROM_UNIXTIME(".NOW.")"
			);
			
			foreach ($moving as $v)
			{
				$notAvailable[$v['s_id']] = true;
			}
		}

		$o = array ();
		$i = 0;

		$okay = true;
		
		$villages = array ();
		$villages[$this->getId ()] = $this;
		
		foreach ($squads as $v)
		{
			if (!isset ($notAvailable[$v['s_id']]))
			{
				$o[$i] = new Dolumar_Players_Squad ($v['s_id']);
				$o[$i]->setData ($v);
			
				if (!isset ($villages[$v['v_id']]))
				{
					$villages[$v['v_id']] = 
						self::getVillage ($v['v_id'], NOW, false, true);
				}
				
				$o[$i]->setVillage ($villages[$v['v_id']]);
				
				if ($v['s_village'] > 0)
				{
					if (!isset ($villages[$v['s_village']]))
					{
						$villages[$v['s_village']] = 
							self::getVillage ($v['s_village'], NOW, false, true);
					}
				
					$o[$i]->setCurrentLocation ($villages[$v['s_village']]);
				}
				else
				{
					$o[$i]->setCurrentLocation ($this);
				}
				
				$i ++;
			}
		}
		return $o;
	}
	
	/*
		Return all squads that are currently supporting the village
		(so all squads that are not actually this players squad.
	*/
	public function getSupportingSquads ()
	{
		$squads = $this->getSquads (false, true, false);
		
		$out = array ();
		
		foreach ($squads as $v)
		{
			if (!$v->isHome ($this))
			{
				$out[] = $v;
			}
		}
		
		return $out;
	}

	public function addSquad ($sName, $iVType)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$id = $db->insert
		(
			'villages_squads',
			array
			(
				's_name' => $sName,
				'v_id' => $this->getId (),
				'v_type' => $iVType
			)
		);

		return new Dolumar_Players_Squad ($id);
	}

	public function removeSquad ($iSid)
	{
		$db = Neuron_Core_Database::__getInstance ();

		// Check if this squad exists
		$db = Neuron_Core_Database::__getInstance ();
		
		$l = $db->select
		(
			'villages_squads',
			array ('s_id'),
			"s_id = '".$iSid."' AND v_id = '".$this->getId ()."'"
		);

		if (count ($l) == 1)
		{
			// Remove units
			$db->remove
			(
				'squad_units',
				"s_id = '".$iSid."'"
			);

			// Remove squad
			$db->remove
			(
				'villages_squads',
				"s_id = '".$iSid."'"
			);

			$db->remove
			(
				'squad_equipment',
				"s_id = '".$iSid."'"
			);
		}
	}

	/**********************************************************************************
		ITEMS
	***********************************************************************************/
	public function getEquipment ()
	{
		return $this->equipment->getEquipment ();
	}

	public function craftEquipment ($objBuilding, $objEquipment, $duration, $amount)
	{
		return $this->equipment->craftEquipment ($objBuilding, $objEquipment, $duration, $amount);
	}

	public function removeEquipment ($objEquipment, $amount)
	{
		return $this->equipment->removeEquipment ($objEquipment, $amount);
	}

	/*
		API FUNCTION

		This function returns data according to the privacy settings of the player.
		(So naturally it returns all data at the moment)
	*/
	public function getAPIData ()
	{
		$owner = $this->getOwner ();
		return array
		(
			'name' => 	$this->getName (),
			'location' => 	$this->buildings->getTownCenterLocation (),
			'owner_id' =>	$owner->getId (),
			'owner_name' =>	$owner->getNickname (),
			'resources' =>	$this->resources->getResources (),
			'networth' =>	$this->getNetworth (),
			'ranking' => 	$this->getRank ()
		);
	}
	
	/*
		Return error in last action
	*/
	public function getError ()
	{
		return $this->error;
	}
	
	/*
		For logging and stuff
	*/
	public function getLogArray ()
	{
		return array ();
	}
	
	/*
		Check to see if there are any pending battles
	*/
	public function hasPendingBattles ()
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$chk = $db->query
		("
			SELECT
				battleId
			FROM
				battle
			WHERE
				vid = {$this->getId()}
				OR targetId = {$this->getId()}
		");
		
		return count ($chk) > 0;
	}
	
	/*
		Destroy the village
	*/
	public function destroyVillage ()
	{
		if (!$this->hasPendingBattles ())
		{
			$this->buildings->burnTheVillage ();
		
			// Store this in the db
			$db = Neuron_DB_Database::getInstance ();
		
			$db->query
			("
				UPDATE
					villages
				SET
					isDestroyed = 1
				WHERE
					vid = {$this->getId ()}
			");
		}
	}
	
	/*
		Deactiate the village
		this is basically queing the village for removal.
	*/
	public function deactivate ()
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$db->query
		("
			UPDATE
				villages
			SET
				isActive = '0',
				removalDate = NOW()
			WHERE
				vid = {$this->getId ()}
		");
	}
	
	public function equals ($objVillage)
	{
		if (!$objVillage)
		{
			return false;
		}
	
		return $objVillage->getId () == $this->getId ();
	}
	
	public function __toString ()
	{
		return $this->getDisplayName ();
	}
	
	/*
		Helper function
	*/
	public function getMainClan ()
	{
		return $this->getOwner () ? $this->getOwner ()->getMainClan () : false;
	}
	
	/*
		Destruct!
	*/
	public function __destruct ()
	{
		unset ($this->id);
		unset ($this->data);

		/*		
		if (isset ($this->myUnits))
		{
			foreach ($this->myUnits as $v)
			{
				foreach ($v as $vv)
				{
					$vv->__destruct ();
				}
			}
		}
		*/
		
		unset ($this->myUnits);
		
		unset ($this->unitCapacityCount);
		unset ($this->unitCapacity);
		
		unset ($this->error);
		unset ($this->doCronStuff);
		unset ($this->isLoaded);
		
		/*
		if (isset ($this->specialUnits))
		{
			foreach ($this->specialUnits as $v)
			{
				foreach ($v as $vv)
				{
					$vv->__destruct ();
					unset ($vv);
				}
			}
		}
		*/
		
		unset ($this->specialUnits);
	
		/*	
		if (isset ($this->oBoosts))
		{
			foreach ($this->oBoosts as $v)
			{
				foreach ($v as $vv)
				{
					$vv->__destruct ();
					unset ($vv);
				}
			}
		}
		*/
		
		unset ($this->oBoosts);
		unset ($this->oEffects);
		
		unset ($this->bsync);
		unset ($this->isBattleProccessed);
		
		$this->isInitialized = false;

		parent::__destruct ();
	}
}
?>
