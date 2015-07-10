<?php
class Dolumar_Players_Logs
{
	public static function __getInstance ()
	{
		static $in;
		if (!isset ($in) || empty ($in))
		{
			$in = new self ();
		}
		return $in;
	}
	
	public static function getInstance ()
	{
		return self::__getInstance ();
	}
	
	private $sClassnames = null;
	private $sClassIds = null;
	
	private $iLastLog = null;
	
	private $filters = null;
	private $showonly = null;
	
	private $myvillages = array ();
	
	private $startdate;
	private $enddate;
	
	public function clearFilters ()
	{
		$this->filters = null;
		$this->showonly = null;
	}
	
	public function addMyVillage ($village)
	{
		$this->myvillages[$village->getId ()] = $village;
	}
	
	public function clearMyVillages ()
	{
		$this->myvillages = array ();
	}
	
	public function addFilter ($sFilter)
	{
		if (!isset ($this->filters))
		{
			$this->filters = array ();
		}
	
		$this->filters[] = $sFilter;
	}
	
	public function addShowOnly ($sFilter)
	{
		if (!isset ($this->showonly))
		{
			$this->showonly = array ();
		}
	
		$this->showonly[] = $sFilter;
	}

	private function addLogEntry ($objVillage, $sAction, $sId = 0, $data = array (), $notification = false)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$out = $this->getLogFromObjects ($data);
		
		if (! $objVillage instanceof Dolumar_Players_Village)
		{
			throw new Exception ('$objVillage in Dolumar_Players_Logs should be a village object. ('.$sAction.')');
		}

		$db->insert
		(
			'game_log',
			array
			(
				'l_vid' => $objVillage->getId (),
				'l_action' => $sAction,
				'l_subId' => $sId,
				'l_date' => 'NOW()',
				'l_data' => $out,
				'l_notification' => $notification ? '1' : '0',
				'l_suspicious' => $this->isSuspicious 
				(
					$objVillage, 
					$sAction, 
					$data
				)
			)
		);
	}
	
	public function setTimeInterval ($start = 0, $end = NOW)
	{
		$this->startdate = $start;
		$this->enddate = $end;
	}
	
	public function getSuspiciousLogs ($startpoint = 0, $length = 50, $order = 'ASC', $html = true)
	{
		$where = $this->getSQLWhere ();
		$where .= "AND l_suspicious = 1";
		return $this->getLogsFromWhere ($where, $startpoint, $length, $order, $html);
	}
	
	public function getSuspiciousLogsCounter ()
	{
		$where = $this->getSQLWhere ();
		$where .= "AND l_suspicious = 1";
		return $this->countLogsFromWhere ($where);
	}

	public function getLogs ($vidId, $startpoint = 0, $length = 50, $order = 'ASC', $html = true)
	{
		$sWhere = $this->getSQLWhere ($vidId);
		return $this->getLogsFromWhere ($sWhere, $startpoint, $length, $order, $html);
	}
	
	private function getLogsFromWhere ($sWhere, $startPoint, $length, $order, $html = true)
	{
		$db = Neuron_Core_Database::__getInstance ();
	
		$order = ($order == 'ASC' || $order == 'DESC') ? $order : 'ASC';
		
		$startPoint = intval($startPoint);
		$length = intval($length);

		$l = $db->getDataFromQuery ($db->customQuery
		("
			SELECT
				*, UNIX_TIMESTAMP(game_log.l_date) AS timestamp
			FROM
				game_log
			LEFT JOIN
				battle_report ON
				(
					battle_report.reportId = game_log.l_subId
					AND (game_log.l_action = 'attack' || game_log.l_action = 'defend')
				)
			LEFT JOIN
				game_log_scouts ON
				(
					game_log_scouts.ls_id = game_log.l_subId
					AND game_log.l_action = 'scout'
				)
			LEFT JOIN
				game_log_training ON
				(
					game_log_training.lt_id = game_log.l_subId
					AND game_log.l_action = 'trained'
				)
			LEFT JOIN
				units ON
				(
					units.unitId = game_log_training.u_id
				)
			WHERE
				{$sWhere}
			ORDER BY
				l_date $order
			LIMIT
				$startPoint, $length
		"));
		
		//die ($db->getLatestQuery ());

		$output = array ();
		$i = 0;
		foreach ($l as $v)
		{
			$tmp = $this->getLogFromData_old ($v, $html);
			if ($tmp)
			{			
				$output[] = $tmp;
			}
		}

		return $output;
	}
	
	public function countLogs ($vidId)
	{		
		$sWhere = $this->getSQLWhere ($vidId);
		return $this->countLogsFromWhere ($sWhere);	
	}
	
	private function countLogsFromWhere ($sWhere)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$l = $db->getDataFromQuery ($db->customQuery
		("
			SELECT
				COUNT(l_id) AS aantal
			FROM
				game_log
			WHERE
				{$sWhere}
		"));
		
		//die ($db->getLatestQuery ());
		
		return $l[0]['aantal'];
	}
	
	private function getSQLWhere ($vidId = null)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$sWhere = "";
	
		if (isset ($vidId))
		{
			if (!is_array ($vidId))
			{
				$vidId = array ($vidId);
			}
		
			if (count ($vidId) == 0)
			{
				return "FALSE";
			}
	
			$sWhere .= " AND (";
			foreach ($vidId as $v)
			{
				if ($v instanceof Dolumar_Players_Village)
				{
					$v = $v->getId ();
					
					$sWhere .= "game_log.l_vid = ".intval ($v)." OR ";
				}
				
				else if ($v instanceof Dolumar_Players_Player)
				{
					foreach ($v->getVillages () as $vv)
					{
						$sWhere .= "game_log.l_vid = ".intval ($vv->getId ())." OR ";
					}
				}
				
				else if (is_int ($v))
				{
					$sWhere .= "game_log.l_vid = ".intval ($v)." OR ";
				}
			}
		
			$sWhere = substr ($sWhere, 0, -4).")";
		}
		
		// Add where
		if (isset ($this->showonly))
		{
			$sWhere .= " AND (";
			foreach ($this->showonly as $v)
			{
				$sWhere .= "game_log.l_action = '{$db->escape ($v)}' OR ";
			}
			$sWhere = substr ($sWhere, 0, -4) . ")";
		}
		
		if (isset ($this->startdate))
		{
			$sWhere .= " AND l_date >= FROM_UNIXTIME(".$this->startdate.") ";
		}
		
		if (isset ($this->enddate))
		{
			$sWhere .= " AND l_date <= FROM_UNIXTIME(".$this->enddate.") ";
		}
		
		$sWhere = substr ($sWhere, 4);
		
		return $sWhere;
	}
	
	private function getLogFromData_old ($v, $html = true)
	{	
		switch ($v['l_action'])
		{
			/*
			case 'build':
			case 'upgrade':
			case 'destruct':
				$output = $this->getBuildingLog ($v);
			break;
			*/

			case 'attack':
				$output = $this->getBattleLog ($v, $html);

				if ($output)
				{
					$output['targets'] = array ();
					$output['targets'][] = Dolumar_Players_Village::getVillage ($v['targetId']);
				}
			break;
			
			case 'defend':
				$output = $this->getBattleLog ($v, $html);
				
				if ($output)
				{
					$output['targets'] = array ();
					$output['targets'][] = Dolumar_Players_Village::getVillage ($v['fromId']);
				}
			break;

			case 'scout':
				$output = $this->getScoutLog ($v, $html);
				$output['targets'] = array ();
			break;

			/*
			case 'research':
				$output = $this->getResearchLog ($v);
			break;
			*/

			case 'trained':
				$output = $this->getUnitLog ($v, $html);
				$output['targets'] = array ();
			break;

			default:
				// Fetch the data and put them in array
				$output = array ();
				$output['data'] = $this->getObjectsFromLog ($v['l_data']);
				
				$output['targets'] = array ();
				
				/*
				foreach ($this->getObjectsFromLog ($v['l_data']) as $data)
				{
					if (is_object ($data))
					{
						$output['data'][] = $data->getLogArray ();
					}
				}
				*/
				
				$dats = array ();
				foreach ($output['data'] as $vv)
				{
					if ($vv instanceof Dolumar_Players_Village)
					{
						$dats[$vv->getOwner ()->getId ()] = $vv->getOwner ();
					}
					elseif ($vv instanceof Neuron_GameServer_Player)
					{
						$dats[$vv->getId ()] = $vv;
					}
				}
				
				$output['targets'] = array_values ($dats);
			break;
		}

		if ($output)
		{
			$output['village'] = $v['l_vid'];
			$output['action'] = $v['l_action'];
			$output['timestamp'] = $v['timestamp'];
			$output['gmdate'] = gmdate ('Y-m-d H:i:s', $v['timestamp']);
			$output['unixtime'] = $v['timestamp'];
			$output['logId'] = $v['l_id'];
		}		

		return $output;
	}
	
	/*
		Return all logs since $logId
		
		THIS FUNCTION ONLY WORKS FOR NEW LOGS! OLD LOGS
		WILL HAVE UNDEFINED DATA!
	*/
	public function getLastLogs ($objVillage, $logId, $onlyNotifications)
	{
		$db = Neuron_DB_Database::__getInstance ();
		
		$where = "AND l_vid = ".$objVillage->getId ();
		if ($onlyNotifications)
		{
			$where .= " AND l_notification = '1'";
		}
		
		$data = $db->query
		("
			SELECT
				*,
				UNIX_TIMESTAMP(l_date) AS timestamp
			FROM
				game_log
			WHERE
				l_id > ".intval($logId)." $where
		");
		
		$out = array ();
		foreach ($data as $v)
		{
			$tmp = $this->getLogFromData_old ($v);
			if ($tmp)
			{
				$out[] = $tmp;
			}
		}
		
		return $out;
	}
	
	/*
		Turn MySQL data in a log array.
	*/
	private function getLogFromData ($v)
	{
		$out = array ();
		$out['timestamp'] = $v['timestamp'];
		$out['action'] = $v['l_action'];
		$out['gmdate'] = gmdate ('Y-m-d H:i:s', $v['timestamp']);
		$out['unixtime'] = $v['timestamp'];
		$out['logId'] = $v['l_id'];
		
		// Now fetch all the data
		$out['data'] = $this->getObjectsFromLog ($v['l_data']);
		
		return $out;
	}
	
	/*
		Turn a log data (from getLogFromData) in a string!
		(works only for new logs)
	*/
	public function getLogText ($log, $showUrl = true, $html = true)
	{
		$text = Neuron_Core_Text::__getInstance ();
		
		// Secondly: old data (but they do have nice names...)
		$data = array ();
		
		foreach ($log as $k => $v)
		{
			$data[$k] = $v;
		}
		
		// Define an url for certain actions
		$sUrl = null;
		switch ($log['action'])
		{
			case 'sendMsg':
			case 'receiveMsg':
				$sUrl = "openWindow('messages');";
			break;
		}
		
		if (!isset ($log['village']))
		{
			return 'invalid log';
		}
		
		if (isset ($log['data']) && is_array ($log['data']))
		{
			foreach ($log['data'] as$v)
			{
				if ($v instanceof Neuron_GameServer_Interfaces_Logable)
				{
					if (isset ($sUrl) || $html == false)
					{
						$data[] = Neuron_Core_Tools::output_varchar ($v->getName ());
					}
					else
					{
						$data[] = $v->getDisplayName ();
					}
				}
				elseif (is_string ($v) || is_int ($v) || is_float ($v))
				{
					$data[] = $v;
				}
				else
				{
					$data[] = 'invalid data';
				}
			}
		}
		
		$mylog = isset ($this->myvillages[$log['village']]) ? 'mylogs' : 'hislogs';
		
		$village = Dolumar_Players_Village::getVillage ($log['village']);
		$data['village'] = $village->getName ();
		$data['player'] = $village->getOwner ()->getDisplayName ();

		$datafields = array ();
		foreach ($data as $k => $v)
		{
			if (!is_array ($v))
			{
				$datafields[$k] = $v;
			}
		}
		
		$txt = Neuron_Core_Tools::putIntoText
		(
			$text->get 
			(
				$log['action'], 
				$mylog, 
				'logs', 
				$text->get ($log['action'], 'mylogs', 'logs')
			),
			$datafields
		);
		
		if (isset ($sUrl) && $showUrl)
		{
			return '<a href="javascript:void(0);" onclick="'.$sUrl.'">'.$txt.'</a>';
		}
		else
		{
			return $txt;
		}
	}
	
	/*
		Return the bigest logId
	*/
	public function getLastLogId ()
	{
		if (!isset ($this->iLastLog))
		{
			// Calculate thze log.
			$db = Neuron_DB_Database::__getInstance ();
			
			$data = $db->query
			("
				SELECT
					MAX(l_id) AS maxi
				FROM
					game_log
			");
			
			$this->iLastLog = $data[0]['maxi'];
		}
		
		return $this->iLastLog;
	}

	private function getBuildingLog ($row)
	{
		$o = array ();

		$buildings = Dolumar_Buildings_Building::getAllBuildings ();

		// Building
		if (isset ($buildings[$row['l_subId']]))
		{
			$o['building'] = $buildings[$row['l_subId']];
		}
		else
		{
			$o['building'] = 'undefined';
		}

		return $o;
	}

	private function getBattleLog ($v, $html = true)
	{
		if (!isset ($v['reportId']))
		{
			return false;
		}

		$attacker = Dolumar_Players_Village::getVillage ($v['fromId']);
		$defender = Dolumar_Players_Village::getVillage ($v['targetId']);

		// Fetch thze troops!
		$report = new Dolumar_Battle_Report ($v['l_id'], $v);

		$inUnits = $report->getUnits ();

		$units = array ();
		foreach ($inUnits as $troop => $troops)
		{
			$units[$troop] = array ();

			if (is_array ($troops))
			{
				foreach ($troops as $t)
				{
					$units[$troop][] = array
					(
						'unit' => $t['unit']->getClassName (),
						'transname' => $t['unit']->getName (),
						'race' => $t['unit']->getRace ()->getName (),
						'amount' => $t['amount'],
						'died' => $t['died']
					);
				}
			}
		}

		return array
		(
			'reportid' => $v['reportId'],
			'attacker' => $attacker,
			'defender' => $defender,
			'attacker_name' => $html ? $attacker->getDisplayName () : $attacker->getName (),
			'attacker_id' => $attacker->getId (),
			'attacker_owner_name' => $html ? $attacker->getOwner ()->getDisplayName () : $attacker->getOwner ()->getName (),
			'attacker_owner_id' => $attacker->getOwner ()->getId (),
			'defender_name' => $html ? $defender->getDisplayName () : $defender->getName (),
			'defender_id' => $defender->getId (),
			'defender_owner_name' => $html ? $defender->getOwner ()->getDisplayName () : $defender->getOwner ()->getName (),
			'defender_owner_id' => $attacker->getOwner ()->getId (),
			'victory' => $report->getVictory (),
			'attacking_units' => $units['attacking'],
			'defending_units' => $units['defending'],
			'stolen_runes' => $report->isFinished () ? $report->countStolenRunes () : '?'
		);
	}

	private function getScoutLog ($row)
	{
		$runes = array ();
		$sRunes = explode ('|', $row['ls_runes']);

		foreach ($sRunes as $rune)
		{
			$s = explode (':', $rune);
			if (count ($s) == 2)
			{
				$runes[$s[0]] = $s[1];
			}
		}

		return array
		(
			'runes' => $runes
		);
	}

	private function getResearchLog ($row)
	{
		return array
		(
			'technology' => $row['techName']
		);
	}

	private function getUnitLog ($row)
	{
		$objVillage = Dolumar_Players_Village::getVillage ($row['l_vid']);
	
		$unitname = !empty ($row['unitName']) ? $row['unitName'] : $row['l_subId'];
		$objUnit = Dolumar_Units_Unit::getUnitFromName ($unitname, $objVillage->getRace (), $objVillage);
		
		if ($objUnit)
		{
			$unitname = $objUnit->getName ();
		}
	
		return array
		(
			'unit' => $unitname,
			'amount' => $row['lt_amount']
		);
	}

	public function addBattleReport ($objBattle)
	{
		$logId = $objBattle->getLogId ();

		$objAttacker = $objBattle->getAttacker ();
		$objDefender = $objBattle->getDefender ();
		
		$this->addLogEntry ($objAttacker, 'attack', $logId);
		$this->addLogEntry ($objDefender, 'defend', $logId);
	}
	
	public function addWithdrawBattle ($objBattle)
	{
		$objAttacker = $objBattle->getAttacker ();
		$objDefender = $objBattle->getDefender ();
		
		$this->addLog ($objAttacker, 'att_withdraw', array ($objAttacker, $objDefender));
		$this->addLog ($objDefender, 'def_withdraw', array ($objAttacker, $objDefender));
	}

	public function addBuildBuilding ($objVillage, $objBuilding)
	{
		//$this->addLogEntry ($objVillage, 'build', $objBuilding->getBuildingId ());
		$this->addLog 
		(
			$objVillage, 
			'build', 
			array 
			(
				$objBuilding
			)
		);
	}

	public function addDestructBuilding ($objVillage, $objBuilding)
	{
		//$this->addLogEntry ($objVillage, 'destruct', $objBuilding->getBuildingId ());
		$this->addLog 
		(
			$objVillage, 
			'destruct', 
			array 
			(
				$objBuilding
			)
		);
	}

	public function addUpgradeBuilding ($objVillage, $objBuilding)
	{
		//$this->addLogEntry ($objVillage, 'upgrade', $objBuilding->getBuildingId ());
		$this->addLog 
		(
			$objVillage, 
			'upgrade', 
			array 
			(
				$objBuilding
			)
		);
	}

	public function addResearchDone ($objVillage, $objResearch)
	{
		//$this->addLogEntry ($objVillage, 'research', $objResearch->getId ());
		$this->addLog 
		(
			$objVillage, 
			'research', 
			array 
			(
				$objResearch
			)
		);
	}

	public function addScoutDone ($objVillage, $runes = array ())
	{
		$s = '';
		foreach ($runes as $k => $v)
		{
			$s .= $k . ":" . $v . "|";
		}
		$s = substr ($s, 0, -1);

		$db = Neuron_Core_Database::__getInstance ();

		$id = $db->insert
		(
			'game_log_scouts',
			array
			(
				'ls_runes' => $s
			)
		);

		$this->addLogEntry ($objVillage, 'scout', $id);
	}

	public function addUnitTrained ($objVillage, $objUnit, $amount)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$id = $db->insert
		(
			'game_log_training',
			array
			(
				'u_id' => $objUnit->getUnitId (),
				'lt_amount' => $amount
			)
		);

		$this->addLogEntry ($objVillage, 'trained', $id);
	}
	
	private static function & getClasses ($reload = false)
	{
		static $classes;
	
		// Check if this log is found
		if (!isset ($classes) || $reload)
		{
			$classes = array ();
		
			// Load the classnames
			$db = Neuron_DB_Database::__getInstance ();
			
			$data = $db->query
			("
				SELECT
					*
				FROM
					n_logables
			");
			
			$classes['names'] = array ();
			$classes['ids'] = array ();
			
			// Put them in thze array
			foreach ($data as $v)
			{
				$classes['names'][$v['l_name']] = $v['l_id'];
				$classes['ids'][$v['l_id']] = $v['l_name'];
			}
		}
		
		return $classes;
	}
	
	/*
		Get classname ID
	*/
	public static function getClassId ($object)
	{
		$name = get_class ($object);
		
		$classes = self::getClasses ();
		
		$classnames = $classes['names'];
		
		// Check if isset!
		if (!isset ($classnames[$name]))
		{
			// Insert a new ID
			$db = Neuron_DB_Database::__getInstance ();
			
			$db->query
			("
				INSERT INTO
					n_logables
				SET
					l_name = '".$name."'
			");
			
			/*
			$id = $db->getInsertId ();
			
			$classes['names'][$name] = $id;
			$classes['ids'][$id] = $name;
			*/
			
			$classes = self::getClasses (true);
		}
		
		return $classes['names'][$name];
	}
	
	private static function getClassName ($id)
	{
		$classes = self::getClasses ();
	
		if (isset ($classes['ids'][$id]))
		{
			return $classes['ids'][$id];
		}
		return false;
	}
	
	/*
		Transfer resources
	*/
	public function addResourceTransferLog ($objVillage, $target, $resources = array (), $arrivedate = null)
	{
		$objResources = new Dolumar_Logable_ResourceContainer ($resources);
		
		if (isset ($arrivedate))
		{
			$arrivedate = new Dolumar_Logable_Date ($arrivedate);
		
			$this->addLog ($objVillage, 'res_sending', array ($objResources, $target, $arrivedate), false);		
			$this->addLog ($target, 'res_receiving', array ($objResources, $objVillage, $arrivedate), true);
		}
		else
		{
			$this->addLog ($objVillage, 'res_send', array ($objResources, $target), false);		
			$this->addLog ($target, 'res_received', array ($objResources, $objVillage), true);		
		}
		
		$target->getOwner()->sendNotification 
		(
			'received', 
			'resources', 
			array 
			(
				'resources' => $objResources, 
				'player' => $objVillage->getOwner ()
			), 
			$objVillage->getOwner ()
		);
	}
	
	public function addRuneTransferLog ($objVillage, $target, $runes = array (), $arrivedate = null)
	{
		$objResources = new Dolumar_Logable_RuneContainer ($runes);
		
		if (isset ($arrivedate))
		{
			$arrivedate = new Dolumar_Logable_Date ($arrivedate);
		
			$this->addLog ($objVillage, 'runes_sending', array ($objResources, $target, $arrivedate), false);
			$this->addLog ($target, 'runes_receiving', array ($objResources, $objVillage, $arrivedate), true);
		}
		else
		{
			$this->addLog ($objVillage, 'runes_send', array ($objResources, $target), false);
			$this->addLog ($target, 'runes_received', array ($objResources, $objVillage), true);
		}
		
		$target->getOwner ()->sendNotification 
		(
			'received', 
			'runes', 
			array 
			(
				'runes' => $objResources, 
				'player' => $objVillage->getOwner ()
			), 
			$objVillage->getOwner ()
		);
	}
	
	public function addCompleteTransferLog (Dolumar_Players_Village $village, Neuron_GameServer_Interfaces_Logable $object, Dolumar_Players_Village $from = null)
	{
		if (isset ($from))
		{
			$this->addLog ($village, 'transfer_complete_from', array ($object, $from), true);
		}
		else
		{
			$this->addLog ($village, 'transfer_complete', array ($object, $from), true);
		}
	}
	
	public function addTroopDiedOfHunger (Dolumar_Players_Village $village, Dolumar_Units_Unit $unit, $amount)
	{
		$this->addLog ($village, 'troops_starving', array ($unit, $amount), true);
	}
	
	public function addEquipmentTransferLog 
	(
		Dolumar_Players_Village $from, 
		Dolumar_Players_Village $target, 
		Dolumar_Players_Equipment $objEquipment, 
		$amount
	)
	{
		$this->addLog ($from, 'equipment_send', array ($objEquipment, $amount, $target));
		$this->addLog ($target, 'equipment_received', array ($objEquipment, $amount, $from));
		
		$target->getOwner ()->sendNotification 
		(
			'received', 
			'equipment', 
			array 
			(
				'equipment' => $objEquipment, 
				'player' => $from->getOwner (),
				'amount' => $amount
			), 
			$from->getOwner ()
		);
	}

	public function addPremiumResourcesBoughtLog ($objVillage, $resources)
	{
		$objResources = new Dolumar_Logable_ResourceContainer ($resources);
		
		$this->addLog ($objVillage, 'premium_res_bought', array ($objResources), true);
	}
	
	public function addPremiumRunesBoughtLog ($objVillage, $runes)
	{
		$objResources = new Dolumar_Logable_RuneContainer ($runes);
		
		$this->addLog ($objVillage, 'premium_runes_bought', array ($objResources), false);
	}
	
	public function addPremiumMoveVillage ($objVillage, $nx, $ny, $ox, $oy)
	{
		$new = new Dolumar_Logable_Location (array ($nx, $ny));
		$old = new Dolumar_Logable_Location (array ($ox, $oy));
		
		$this->addLog ($objVillage, 'premium_movevillage', array ($old, $new), true);
	}
	
	public function addPremiumMoveBuilding ($building, $nx, $ny, $ox, $oy)
	{
		$new = new Dolumar_Logable_Location (array ($nx, $ny));
		$old = new Dolumar_Logable_Location (array ($ox, $oy));
		
		$this->addLog ($building->getVillage (), 'premium_movebuilding', array ($building, $old, $new), true);	
	}
	
	public function addPremiumBonusBuilding ($building, $nx, $ny)
	{
		$new = new Dolumar_Logable_Location (array ($nx, $ny));
		
		$this->addLog ($building->getVillage (), 'premium_bonusbuild', array ($building, $new), true);	
		
		$objVillage = $building->getVillage ();
		
		$objVillage->getOwner ()->sendNotification 
		(
			'build', 
			'bonusbuilding', 
			array 
			(
				'building' => $building, 
				'village' => $objVillage
			), 
			$objVillage->getOwner (),
			true
		);
	}
	
	public function addReferreeBonusLog ($objVillage, $player, $runes = array ())
	{
		$objResources = new Dolumar_Logable_RuneContainer ($runes);
		
		$this->addLog ($objVillage, 'referal_bonus', array ($objResources, $player), true);
	}
	
	public function addOpenPortalLog ($objVillage, $objTarget, $stopDate)
	{
		$date = new Dolumar_Logable_Date ($stopDate);
		
		$oLogTarget = $objTarget->getMainClan () ? $objTarget->getMainClan () : $objTarget;
		$oLogVillage = $objVillage->getMainClan () ? $objVillage->getMainClan () : $objVillage;

		$this->addLog ($objVillage, 'portal_open', array ($oLogTarget, $date), false);
		$this->addLog ($objTarget, 'portal_opened', array ($oLogVillage, $date), true);
		
		// Now let's send some notifications
		// to all clan members
		
		$data = array
		(
			'village' => $objVillage,
			'player' => $objVillage->getOwner (),
			'target_village' => $objTarget,
			'target_player' => $objTarget->getOwner (),
			'date' => $date
		);
		
		$this->sendNotificationToClan 
		(
			$objVillage->getOwner (),
			'open',
			'portal',
			$data,
			$objVillage->getOwner (),
			false
		);
		
		$this->sendNotificationToClan 
		(
			$objTarget->getOwner (),
			'opened',
			'portal',
			$data,
			$objVillage->getOwner (),
			false
		);
	}
	
	private function sendNotificationToClan ($objPlayer, $msg, $bundle, $data, $sender, $isPublic)
	{
		$players = array ();
		
		$players[$objPlayer->getId ()] = $objPlayer;
		
		foreach ($objPlayer->getClans () as $clan)
		{
			foreach ($clan->getMembers () as $v)
			{
				$players[$v->getId ()] = $v;
			}
		}
		
		foreach ($players as $target)
		{
			$target->sendNotification 
			(
				$msg, 
				$bundle, 
				$data, 
				$sender
			);
		}
	}
	
	public function addDestroyBuildingLog ($village, $target, $building)
	{
		$this->addLog ($target, 'destroy_building', array ($village, $building), false);
	}
	
	/*
		Log a special action
	*/
	public function addEffectLog ($target, $effect, $village, $succes = true, $visible = false, $toTarget = false, $txt = 'magic')
	{
		if ($effect->doesHandleOwnLogs ($succes))
		{
			return;
		}
	
		$name = $succes ? 's' : 'f';
		$name .= '_';
		$name .= $visible ? 'v' : 'h';
		
		$logs = array
		(
			$effect,
			$target,
			$village
		);
		
		// Add extra data
		foreach ($effect->getLogData () as $v)
		{
			$logs[] = $v;
		}
		
		if (isset ($target) && $toTarget && !$village->equals ($target))
		{
			$this->addLog 
			(
				$target, 
				$txt.'_rec_'.$name, 
				$logs,
				$succes
			);
		}
		
		$this->addLog 
		(
			$village, 
			$txt.'_sent_'.$name, 
			$logs,
			false
		);
	}
	
	public function addJoinClanLog (Dolumar_Players_Player $member, Dolumar_Players_Clan $clan)
	{
		$village = $member->getMainVillage ();
		
		$this->addLog ($village, 'clan_join', array ('clan' => $clan));
	
		// Notify everyone
		$member->sendNotification 
		(
			'join', 
			'clan', 
			array
			(
				'clan' => $clan,
				'player' => $member
			),
			$member,
			true // This is a public message
		);
	}
	
	public function addLeaveClanLog (Dolumar_Players_Player $member, Dolumar_Players_Clan $clan, $sAction = 'leave')
	{
		$village = $member->getMainVillage ();
		
		switch ($sAction)
		{
			case 'leave':
			case 'kicked':
			
			break;
			
			default:
				$sAction = 'leave';
			break;
		}
		
		$this->addLog ($village, 'clan_'.$sAction, array ('clan' => $clan));
	
		// Notify everyone
		$member->sendNotification 
		(
			'leave', 
			'clan', 
			array
			(
				'clan' => $clan,
				'player' => $member
			),
			$member,
			true // This is a public message
		);
	}
	
	public function addStartVacationLog (Dolumar_Players_Player $member)
	{
		$village = $member->getMainVillage ();		
		$this->addLog ($village, 'vacation_start');
	}
	
	public function addEndVacationLog (Dolumar_Players_Player $member)
	{
		$village = $member->getMainVillage ();		
		$this->addLog ($village, 'vacation_end');
	}
	
	/*
		Gets data from log
	*/
	public static function getObjectsFromLog ($sData)
	{
		return Neuron_GameServer_LogSerializer::decode ($sData);
	}
	
	public static function getLogFromObjects ($data)
	{
		return Neuron_GameServer_LogSerializer::encode ($data);
	}
	
	/*
		Add a log (with Logable :p)
	*/
	public function addLog ($objVillage, $sAction, $data = array (), $notification = false)
	{
		if ($objVillage instanceof Dolumar_Players_Village)
		{
			// Insert the log
			$this->addLogEntry ($objVillage, $sAction, null, $data, $notification);
		}
		else
		{
			throw new Neuron_Core_Error ('Trying to add a log on a non Dolumar_Players_Village object.');
		}
	}
	
	private function isSuspicious ($objVillage, $sAction, $data)
	{
		switch ($sAction)
		{
			case 'runes_send':
				$runes = $data[0];
				if ($runes instanceof Dolumar_Logable_RuneContainer)
				{
					$sum = $runes->getSum ();
					
					if ($sum > 30)
					{
						return true;
					}
				}
			break;
		}
	
		return false;
	}
}
?>
