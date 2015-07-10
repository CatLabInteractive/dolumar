<?php
class Dolumar_Windows_Statusbar 
	extends Neuron_GameServer_Windows_Window
{
	private $counters = array ();
	private $bShowVillageNames = false;
	
	public function setSettings ()
	{
		// Window settings
		$this->setSize ('500px', 'auto');
		$this->setPosition ('2px', '32px', 'auto', 'auto');
		
		$this->setFixed ();
		$this->setNoBorder ();

		$this->setZ (9999);
		$this->setClass ('status');
		
		$this->setType ('panel');
		
		$this->setAllowOnlyOnce ();
	
	}

	/*
		What can be speeded up?
	*/
	public static function canSpeedUp ($sType)
	{
		switch ($sType)
		{
			case 'building':
				return defined ('PREMIUM_SPEEDUP_BUILDINGS') && PREMIUM_SPEEDUP_BUILDINGS;
			break;

			case 'training':
				return defined ('PREMIUM_SPEEDUP_TRAINING') && PREMIUM_SPEEDUP_TRAINING;
			break;

			case 'scouting':
				return defined ('PREMIUM_SPEEDUP_SCOUTING') && PREMIUM_SPEEDUP_SCOUTING;
			break;

			default:
				return false;
			break;
		}
	}

	/*
		Put one counter to the array.
	*/
	private function addCounter ($futureTime, $village, $txt, $type, $showVillage = false, $speedUpData = null)
	{
		$this->counters[] = array 
		(
			$futureTime, 
			$this->getVillageName ($village), 
			$txt, 
			$type, 
			$showVillage,
			$speedUpData
		);
	}

	/*
		Return the HTML.
	*/
	public function getContent ()
	{
		//reloadStatusCounters ();
	
		$login = Neuron_Core_Login::__getInstance ();

		if ($login->isLogin ())
		{
			return $this->getCounters ();
		}
		else
		{
			reloadStatusCounters ();
			return ' ';
		}
	}
	
	/*
		Fetch the counters.
	*/
	public function getCounters ()
	{		
		$this->counters = array ();

		$player = Neuron_GameServer::getPlayer ();

		$refreshFlag = false;
		if ($player && $player->updates->getFlag ('refresh-statusbar'))
		{
			$refreshFlag = true;
		}

		if
		(
			defined ('RELOAD') ||
			!isset ($_SESSION['status_counters']) ||
			!is_array ($_SESSION['status_counters']) ||
			!isset ($_SESSION['status_lastupdate']) ||
			$_SESSION['status_lastupdate'] < (time () - 60*1) ||
			$refreshFlag
		)
		{
			$this->getFreshCounters ();
		}
		else
		{
			$this->counters = $_SESSION['status_counters'];
			$this->bShowVillageNames = $_SESSION['status_villagenames'] == 1;
			
			foreach ($this->counters as $v)
			{
				if ($v[0] <= NOW && $v[0] !== null)
				{
					$this->getFreshCounters ();
					break;
				}
			}
		}

		return $this->getCounterHTML ();
	}
	
	/*
		Return fresh counters.
	*/
	private function getFreshCounters ()
	{
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		
		$profiler->start ('Fetching a fresh list of counters');
	
		$this->counters = array ();
	
		// Get all villages
		$villages = Neuron_GameServer::getPlayer ()->getVillages ();
	
		$text = Neuron_Core_Text::__getInstance ();
	
		$text->setFile ('statusbar');
		$text->setSection ('status');

		if (count ($villages) > 0)
		{
			// Village selector
			$vils = ' AND (';
			$vilsId = $vils;
			$vilsIdOrTarget = $vils;

			foreach ($villages as $v)
			{
				$v->processBattles ();
			
				$vils .= "village = ".$v->getId ()." OR ";
				$vilsId .= "vid = ".$v->getId ()." OR ";
				$vilsIdOrTarget .= "vid = ".$v->getId ()." OR targetId = ".$v->getId()." OR ";
			}

			$vils = substr ($vils, 0, -4) . ')';
			$vilsId = substr ($vilsId, 0, -4) . ')';
			$vilsIdOrTarget = substr ($vilsIdOrTarget, 0, -4) . ')';

			$this->loadBuildingCounters ($vils);
			$this->loadScoutCounters ($vilsId);
			$this->loadTroopCounters ($vilsId);
			$this->loadBattleCounters ($vilsIdOrTarget);
			$this->loadMovingSquads ($villages);
			$this->loadCraftingCounters ($vilsId);
			$this->loadTechnologyCounters ($vilsId);
			$this->loadBoostCounters ($villages);
			
			$this->loadQueues ($vilsId);
			
			// A special one: the queues

			$_SESSION['status_counters'] = $this->counters;
			$_SESSION['status_lastupdate'] = time ();
			$_SESSION['status_villagenames'] = count ($villages) > 1 ? 1 : 0;
			$this->bShowVillageNames = $_SESSION['status_villagenames'];

		}
		
		$profiler->stop ();
	}
	
	/*
		Return all queued events.
	*/
	private function loadQueues ($vilsId)
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Replace the selector with a proper one for this table
		$vilsId = str_replace ('vid', 'pq_vid', $vilsId);
	
		// Cancel confirmation
		$cancel = $text->get ('cancel', 'queue', 'statusbar');
		$confirm = addslashes ($text->get ('confirm', 'queue', 'statusbar'));
	
		$db = Neuron_DB_Database::__getInstance ();
		
		$row = $db->query
		("
			SELECT
				pq_id,
				pq_vid,
				pq_action,
				pq_data
			FROM
				premium_queue
			WHERE
				TRUE $vilsId
		");

		foreach ($row as $v)
		{
			$village = Dolumar_Players_Village::getFromId ($v['pq_vid']);
			$data = json_decode ($v['pq_data'], true);
			
			$txt = array
			(
				'cancel' => '<a href="javascript:void(0);" onclick="confirmAction(this,{\'cancelQueue\':'.$v['pq_id'].'}, \''.$confirm.'\');">'.$cancel.'</a>'
			);
		
			// Find the right queues
			switch ($v['pq_action'])
			{
				case 'build':
					$building = Dolumar_Buildings_Building::getBuilding ($data['building'], $village->getRace ());
				
					$txt['building'] = $building->getName ();
					$txt['x'] = floor ($data['x']);
					$txt['y'] = floor ($data['y']);
				break;
				
				case 'upgrade':
					$building = Dolumar_Buildings_Building::getFromId ($data['building']);
					
					$txt['building'] = $building->getName ();
					$txt['level'] = $data['level'];
					list ($txt['x'], $txt['y']) = $building->getLocation ();
				break;
				
				case 'training':
					$unit = Dolumar_Units_Unit::getUnitFromId 
					(
						$data['unit'],
						$village->getRace (),
						$village
					);
					
					$txt['unit'] = $unit->getName ($data['amount'] > 1);
					$txt['amount'] = $data['amount'];
				break;
			}
			
			$this->addCounter 
			(
				null, 
				$village, 
				Neuron_Core_Tools::putIntoText
				(
					$text->get ($v['pq_action'], 'queue', 'statusbar'),
					$txt
				),
				'queue',
				false
			);	
		}
	}
	
	private function loadMovingSquads ($villages)
	{
		$where = "s_end > FROM_UNIXTIME(".NOW.") AND (";
		foreach ($villages as $v)
		{
			$where .= "s_from = ".$v->getId ()." OR s_to = ".$v->getId ()." OR ";
		}
		$where = substr ($where, 0, -4) . ")";
		
		// Load the messages
		$db = Neuron_DB_Database::__getInstance ();
		
		$movements = $db->query
		("
			SELECT
				s_from,
				s_to,
				s_id,
				UNIX_TIMESTAMP(s_end) AS aankomst
			FROM
				squad_commands
			WHERE
				$where
			GROUP BY
				s_id
		");
		
		$text = Neuron_Core_Text::getInstance ();
		$text->setSection ('status', 'statusbar');
		
		foreach ($movements as $v)
		{
			$target = Dolumar_Players_Village::getVillage ($v['s_to']);
			$squad = new Dolumar_Players_Squad ($v['s_id']);
			
			//$txt = 'Moving troops to '.$target->getName ();
			
			$txt = Neuron_Core_Tools::putIntoText
			(
				$text->get ('moveTroops'),
				array
				(
					'target' => $this->getVillageName ($target),
					'squad' => $squad->getName ()
				)
			);
		
			$this->addCounter
			(
				$v['aankomst'],
				Dolumar_Players_Village::getVillage ($v['s_from']),
				$txt,
				'moving'
			);
		}
	}
	
	private function loadTechnologyCounters ($where)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$rows = $db->select
		(
			'villages_tech',
			array ('*'),
			'endDate > '.time().' '.$where
		);
		
		$text = Neuron_Core_Text::getInstance ();
		$text->setSection ('status', 'statusbar');
		
		foreach ($rows as $v)
		{
			$village = Dolumar_Players_Village::getVillage ($v['vid']);
			$technology = Dolumar_Technology_Technology::getFromId ($v['techId'], $village->getRace ());
			
			$txt = Neuron_Core_Tools::putIntoText
			(
				$text->get ('research'),
				array
				(
					'technology' => $technology->getName ()
				)
			);
			
			$this->addCounter ($v['endDate'], $village, $txt, 'technology');
		}
	}

	private function loadBuildingCounters ($vils)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$text = Neuron_Core_Text::__getInstance ();
		
		$b = $db->select
		(
			'map_buildings',
			array ('*'),
			"
				(map_buildings.readyDate > '".time()."'
				OR map_buildings.lastUpgradeDate > '".time()."')
				AND destroyDate = '0'
				$vils
			"
		);

		foreach ($b as $v)
		{
			$village = Dolumar_Players_Village::getVillage ($v['village']);
			$race = $village->getRace ();

			// Building stuff
			$building = Dolumar_Buildings_Building::getBuilding ($v['buildingType'], $race);
			$building->setData ($v['bid'], $v);

			$speedUp = null;
			if ($this->canSpeedUp ('building'))
			{
				$speedUp = array
				(
					'type' => 'building',
					'building' => $building->getId ()
				);
			}

			if ($v['readyDate'] > time ())
			{
				$t = Neuron_Core_Tools::putIntoText ($text->get ('construct'), array ($building->getName ()));
				$this->addCounter ($v['readyDate'], $village, $t, 'construct', false, $speedUp);
			}

			else
			{
				$t = Neuron_Core_Tools::putIntoText ($text->get ('upgrade'), array ($building->getName ()));
				$this->addCounter ($v['lastUpgradeDate'], $village, $t, 'upgrade', false, $speedUp);
			}
		}
	}

	private function loadScoutCounters ($vilsId)
	{
		$db = Neuron_Core_Database::__getInstance ();
		$text = Neuron_Core_Text::__getInstance ();
		
		$s = $db->select
		(
			'villages_scouting',
			array ('*'),
			"finishDate > '".time()."' $vilsId"
		);

		foreach ($s as $v)
		{
			$village = Dolumar_Players_Village::getVillage ($v['vid']);

			$speedUp = null;
			if ($this->canSpeedUp ('scouting'))
			{
				$speedUp = array
				(
					'type' => 'scouting',
					'village' => $village->getId (),
					'scoutid' => $v['scoutId']
				);
			}

			$this->addCounter ($v['finishDate'], $village, $text->get ('scout'), 'scouting', false, $speedUp);
		}
	}

	private function loadTroopCounters ($vilsId)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$t = $db->select
		(
			'villages_units',
			array ('*'),
			"endTraining > '".time()."' $vilsId"
		);
		
		$text = Neuron_Core_Text::getInstance ();
		$text->setSection ('status', 'statusbar');

		foreach ($t as $v)
		{
			$village = Dolumar_Players_Village::getVillage ($v['vid']);
			$troop = Dolumar_Units_Unit::getUnitFromId ($v['unitId'], $village->getRace (), $village);

			$speedUp = null;
			if ($this->canSpeedUp ('training'))
			{
				$speedUp = array
				(
					'type' => 'training',
					'village' => $village->getId (),
					'order' => $v['uid'],
					'unit' => $troop->getId ()
				);
			}
			
			$txt = Neuron_Core_Tools::putIntoText
			(
				$text->get ('training'),
				array
				(
					'unit' => $troop->getName ($v['amount'] > 1),
					'amount' => $v['amount']
				)
			);
			
			$this->addCounter ($v['endTraining'], $village, $txt, 'training', false, $speedUp);
		}
	}

	private function loadBattleCounters ($vilsIdOrTarget)
	{
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
	
		$profiler->start ('Generating new battle counters');
	
		$db = Neuron_Core_Database::__getInstance ();
		
		// Load battles
		$t = $db->select
		(
			'battle',
			array ('*'),
			"endDate > '".NOW."' $vilsIdOrTarget"
		);
		
		$text = Neuron_Core_Text::getInstance ();
		$text->setSection ('status', 'statusbar');

		foreach ($t as $v)
		{
			$profiler->start ('Loaded battle counter: '.$v['battleId']);
		
			$village = Dolumar_Players_Village::getVillage ($v['vid']);
			$target = Dolumar_Players_Village::getVillage ($v['targetId']);
			
			$output = array
			(
				'target' => $this->getVillageName ($target)
			);
			
			$battle = Dolumar_Battle_Battle::getBattle ($v['battleId']);

			if ($v['arriveDate'] > NOW)
			{
				if ($battle->isVisible ())
				{
					$txt = Neuron_Core_Tools::putIntoText ($text->get ('battle_sending'), $output);
				
					if ($battle->canWithdraw (Neuron_GameServer::getPlayer ()))
					{
						$confirm = $text->get ('confirmWithdraw', 'battle');
				
						$txt .= ' (<a href="javascript:void(0);" class="action" '.
							'onclick="confirmAction(this,{\'action\':\'withdrawbattle\',\'battle\':'.
							$v['battleId'].'},\''.$confirm.'\');">'.$text->get ('withdraw', 'battle').'</a>)';
					}
			
					$this->addCounter
					(
						$v['arriveDate'],
						$village,
						$txt, //'Sending troops to '.$target->getName (),
						'troops sending',
						true
					);
				}
			}
			
			elseif ($v['fightDate'] >= NOW)
			{
				$this->addCounter
				(
					$v['fightDate'],
					$village,
					Neuron_Core_Tools::putIntoText ($text->get ('battle_preparing'), $output), //'(aprox) Preparing troops to fight in '.$target->getName (),
					'troops preparing',
					true
				);
			}
			
			elseif ($v['endFightDate'] > NOW)
			{
				// Get my village id
				$me = Neuron_GameServer::getPlayer ();
				
				if ($me->getId () == $village->getOwner ()->getId ())
				{
					$sOnClick = 'openWindow(\'battle\', {\'vid\':\''.$village->getId ().'\',\'report\':'.$v['bLogId'].'});';
				}
				else
				{
					$sOnClick = 'openWindow(\'battle\', {\'vid\':\''.$target->getId ().'\',\'report\':'.$v['bLogId'].'});';
				}
			
				$this->addCounter
				(
					$v['endFightDate'],
					$village,
					'<a href="javascript:void(0);" onclick="'.$sOnClick.'">'.
						Neuron_Core_Tools::putIntoText ($text->get ('battle_fighting'), $output).//'Fighting in '.$target->getName ().
					'</a>.',
					'troops fighting',
					true
				);
			}

			elseif ($v['isFought'])
			{
				$this->addCounter
				(
					$v['endDate'],
					$village,
					Neuron_Core_Tools::putIntoText ($text->get ('battle_returning'), $output), //'Returning troops from '.$target->getName (),
					'troops returning',
					true
				);
			}
			
			$profiler->stop ();
		}
		
		$profiler->stop ();
	}

	private function loadCraftingCounters ($vilsId)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$l = $db->select
		(
			'villages_items',
			array ('*'),
			"i_endCraft > '".time()."' $vilsId"
		);
		
		$text = Neuron_Core_Text::getInstance ();
		$text->setSection ('status', 'statusbar');

		foreach ($l as $v)
		{
			$village = Dolumar_Players_Village::getVillage ($v['vid']);
			$equipment = Dolumar_Players_Equipment::getFromId ($v['i_itemId']);
			
			if ($equipment)
			{
				$txt = Neuron_Core_Tools::putIntoText
				(
					$text->get ('crafting'),
					array
					(
						'amount' => $v['i_amount'],
						'equipment' => $equipment->getName ($v['i_amount'] > 1)
					)
				);
			}
			else
			{
				$txt = '*** equipment not found: '.$v['i_itemId'].' ***';
			}
			
			$this->addCounter
			(
				$v['i_endCraft'],
				$village,
				$txt,
				'items'
			);
		}
	}
	
	private function loadBoostCounters ($villages)
	{
		$text = Neuron_Core_Text::getInstance ();
	
		foreach ($villages as $village)
		{
			$status = $village->getActiveBoosts ();
			
			foreach ($status as $v)
			{
				$data = array
				(
					'effect' => '<span title="'.$v->getDescription ().'">'.$v->getName ().'</span>',
					'actor' => $this->getVillageName ($v->getActor ())
				);
			
				if ($v->isSecret ())
				{
					$sname = 'effect_unknown';
				}
				else
				{
					$sname = 'effect_known';
				}
			
				$this->addCounter
				(
					$v->getEndDate (),
					$village,
					Neuron_Core_Tools::putIntoText ($text->get ($sname), $data),
					'effects'
				);
			}
		}
	}

	private function getCounterHTML ()
	{
		$text = Neuron_Core_Text::getInstance (); 

		// Loop trough the list
		$p = '';
		$l = '';

		$showVillageName = $this->bShowVillageNames;

		asort ($this->counters);
		
		foreach ($this->counters as $v)
		{
			// Can speed up?
			if (isset ($v[5]))
			{
				$s = ' <a class="speedup" href="javascript:void(0);" onclick="openWindow(\'Speedup\', ' 
					. htmlentities (json_encode ($v[5])) . ');">' . $text->get ('speedup', 'status', 'statusbar') . '</a>';
			}
			else
			{
				$s = '';
			}

			if ($v[0] > time ())
			{
				if ($showVillageName || $v[4])
				{
					$p .= '<li class="'.$v[3].'">['
						. Neuron_Core_Tools::getCountdown ($v[0]).'] '
						. $v[1] . ': '
						. $v[2].$s.'</li>';
				}

				else
				{
					$p .= '<li class="'.$v[3].'">['.Neuron_Core_Tools::getCountdown ($v[0]).'] '
						. $v[2].$s.'</li>';
				}
			}
			
			elseif ($v[0] === null)
			{
				if ($showVillageName || $v[4])
				{
					$l .= '<li class="'.$v[3].'">'.$v[1]. ': '.$v[2].$s.'</li>';
				}

				else
				{
					$l .= '<li class="'.$v[3].'">'.$v[2].$s.'</li>';
				}
			}
		}

		return '<ul class="statusbar">'.$p.$l.'</ul>';
	}
	
	/*
		Process input: at the moment only used for queue.
	*/
	public function processInput ()
	{
		$player = Neuron_GameServer::getPlayer ();
		
		if (!$player)
		{
			return;
		}
	
		$input = $this->getInputData ();
		
		if (isset ($input['cancelQueue']))
		{
			$db = Neuron_DB_Database::__getInstance ();
		
			$queue = intval ($input['cancelQueue']);
			
			// Get all villages
			$villages = Neuron_GameServer::getPlayer ()->getVillages ();
			
			if (count ($villages) > 0)
			{
				$villageSelector = '';
				foreach ($villages as $v)
				{
					// Make sure the battles are processed.
					$v->processBattles ();
				
					$villageSelector .= 'pq_vid = '.$v->getId().' OR ';
				}
				$villageSelector = substr ($villageSelector, 0, -3);
			
				// Make sure you can only remove your own queues
				$db->query
				("
					DELETE FROM
						premium_queue
					WHERE
						pq_id = $queue AND
						$villageSelector
				");
			}
		}
		
		elseif (isset ($input['action']))
		{
			switch ($input['action'])
			{
				case 'withdrawbattle':
			
					$battleid = isset ($input['battle']) ? $input['battle'] : 0;
					if ($battleid)
					{
						// Fetch battle
						$battle = Dolumar_Battle_Battle::getBattle ($battleid);
						if ($battle && $battle->getAttacker ()->getOwner ()->equals ($player))
						{
							$battle->withdraw ();
						}
					}
			
				break;
			}
		}
		
		$this->getFreshCounters ();
		$this->updateContent ();
	}
	
	private function getVillageName ($village)
	{
		return '<a href="javascript:void(0);" onclick="openWindow(\'villageProfile\',{\'village\':'.$village->getId().'});">'.
			Neuron_Core_Tools::output_varchar ($village->getName ())
			.'</a>';
	}
	
	/*
		Process a regular refresh
	*/
	public function getRefresh ()
	{
		$this->updateContent ();
	}
}
?>
