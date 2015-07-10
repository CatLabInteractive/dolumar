<?php
class Dolumar_Players_Player extends Neuron_GameServer_Player
{
	private $data;
	private $villages;

	public function init ()
	{
		new Dolumar_Guide ($this);
		
		//$this->quests->evaluate ();
	}

	/*
		Calculate a new location.
		
		@param $d: can be array or string.
		If string, it will search for a free spot in direction $d
		If array, it will search for a location close to $d = array ('x', 'y');
	*/
	public function calculateNewStartLocation ($d, $race, $maxrange = MAXMAPSTRAAL, $minrange = null, $fCheckCallback = null)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$minbuildingradius = MAXBUILDINGRADIUS;

		if (is_array ($d) && count ($d) == 2 && is_numeric ($d[0]) && is_numeric ($d[1]))
		{
			// Location: find location close to this location
			$startX = floor ($d[0]);
			$startY = floor ($d[1]);

			if (sqrt (($startX * $startX) + ($startY * $startY)) > MAXMAPSTRAAL)
			{
				$startX = 0;
				$startY = 0;
			}

				
			$startRad = 0;
			$endRad = 2;
			
			// Radius should start at minimal distant between 2 villages ;-)
			if (isset ($minrange))
			{
				$radius = $minrange;
			}
			else
			{
				$radius = MAXBUILDINGRADIUS * 2;
			}
		}
		else
		{
			// $d is a string, search in one direction
			
			// e ne n wn w sw s es
			$radials = Dolumar_Map_Map::getRadialFromDirection ($d);
			
			$startRad = $radials[0];
			$endRad = $radials[1];

			$startX = 0;
			$startY = 0;
			
			// Do the area trick
			// Count amount of town centers on the map
			$buildings = $db->select
			(
				'map_buildings',
				array ('COUNT(*) AS aantal'),
				"buildingType = 1"
			);
			
			$towns = count ($buildings) == 1 ? $buildings[0]['aantal'] : 0;
			$radius = ceil (abs (sqrt ($towns / pi ())) * MAXBUILDINGRADIUS * 2);
			
			$radius = min ($radius, MAXMAPSTRAAL * 0.75);
			
			$fradius = $radius;
			
			// Now let's find the very first town center built
			$firstbuilding = $db->select
			(
				'map_buildings',
				array ('MIN(startDate) AS mStartDate')
			);
			
			$firstbuilding = count ($firstbuilding) == 1 ? $firstbuilding[0]['mStartDate'] : 0;
			
			$month = 60*60*24*31;
			$month2 = 60*60*24*31 * 2;
			
			// Elke 2 maanden springen we terug naar het midden.
			$radiusChange = (abs (time () - $firstbuilding + $month) % $month2) / $month2;
			$radius *= sin ($radiusChange * pi ());
			
			// Minimal building radius between non clan members
			$minbuildingradius = $minbuildingradius * 5;
		}
		
		// Fetch towncenter
		$townCenter = Dolumar_Buildings_Building::getBuilding (1, $race);
		
		$okay = false;
		
		$startRad *= pi ();
		$endRad *= pi ();
		
		$maxruns = 5000;
		
		$incr = ceil (MAXBUILDINGRADIUS / 5);
		
		while (!$okay)
		{
			// Max radius check
			if ($radius > $maxrange)
			{
				return false;
			}
			
			if ($maxruns < 0)
			{
				return false;
			}
			
			$maxruns --;
		
			// Random radiation
			$rad = mt_rand ($startRad * 100000, $endRad * 100000) / 100000;
			
			$x = $startX + floor (sin ($rad) * $radius);
			$y = $startY + floor (cos ($rad) * $radius);
			
			// Check if location is valid
			$okay = $townCenter->checkBuildLocation (null, $x, $y, $minbuildingradius);
			$okay = $okay[0];
			
			if ($okay && isset ($fCheckCallback))
			{
				$okay = call_user_func ($fCheckCallback, $x, $y);
			}
			
			// It not right: increase randius and take new random rad.
			$radius += $incr;
		}
		
		return array ($x, $y, $radius);
	}
	
	private function loadData ()
	{
		$this->data = $this->getData ();
	}
	
	/*
		Initialize account:
		@param $race: Race object (selected)
		@param $direction: If string: direction. If array: location
		@param $clan: A clan object
	*/
	public function initializeAccount ($race, $direction, $clan = false)
	{
		if (true)
		{
			$db = Neuron_Core_Database::__getInstance ();
			$text = Neuron_Core_Text::__getInstance ();
			
			if ($this->isFound () && !$this->isPlaying ())
			{
				$this->loadData ();
				if (empty ($this->data['nickname']))
				{
					$this->error = 'nickname_not_set';
					return false;
				}
				else
				{
					$race = Dolumar_Races_Race::getRace ($race);

					if (!$race->canPlayerSelect ($this))
					{
						$this->error = 'race_not_found';
						return false;
					}
					
					// If a clan is specified, make sure it's not out of range
					if ($clan)
					{
						// Check if the clan is found
						if ($clan->isFull ())
						{
							$this->error = 'clan_is_full';
							return false;
						}
					
						$map = $this->calculateNewStartLocation ($direction, $race, MAXCLANDISTANCE);
					}
					else
					{
						$map = $this->calculateNewStartLocation ($direction, $race);
					}
					
					// Map can actually fail, if no location is found.
					if (!$map)
					{
						$this->error = 'no_location_found';
						return false;
					}
					
					$building = Dolumar_Buildings_Building::getBuilding (1, $race);
					$building = $building->build (null, $map[0], $map[1], $this, $race);
					
					$village = $building->getVillage ();

					// Update field
					$db->update
					(
						'n_players',
						array
						(
							'startX' => $map[0],
							'startY' => $map[1],
							'isPlaying' => 1
						),
						"plid = {$this->getId ()}"
					);
					
					$this->data['startX'] = $map[0];
					$this->data['startY'] = $map[1];

					$this->data['isPlaying'] = 1;
					
					// Blah
					$this->village_insert_id = $village->getId ();
					
					// Now join the clan
					if ($clan)
					{
						$clan->doJoinClan ($this);
					}
					
					$this->reloadData ();
					$this->reloadVillages ();
					
					$village->buildings->reloadBuildings ();
					
					// Invoke the command
					$this->events->invoke ('register');

					return true;
				}
			}
			else
			{
				$this->error = 'already_initialized';
				return false;
			}
		}
		else
		{
			$this->error = 'game_not_open';
			return false;
		}
	}
	
	public function getVillageInsertId ()
	{
		return $this->village_insert_id;
	}
	
	public function getHomeLocation ()
	{
		/*
		$this->loadData ();
		if ($this->isPlaying ())
		{
			return array ($this->data['startX'], $this->data['startY']);
		}
		
		else {
			return array (0, 0);
		}
		*/
		
		$cv = $this->getCurrentVillage ();
		
		if ($cv)
		{
			return $cv->buildings->getTownCenterLocation ();
		}
		else
		{
			return array (0, 0);
		}
	}
	
	public function reloadVillages ()
	{
		$this->villages = null;
	}

	/**
	* Count all runes this player has
	*/
	public function getTotalRunes ()
	{
		$total = 0;
		foreach ($this->getVillages () as $v)
		{
			$total += $v->resources->getTotalRunes ();
		}
		return $total;
	}
	
	public function getVillages ($syncBattles = true)
	{
		if (!isset ($this->villages))
		{
			//$db = Neuron_Core_Database::__getInstance ();
			$db = Neuron_DB_Database::getInstance ();
		
			$vs = $db->query
			("
				SELECT
					*
				FROM
					villages
				WHERE
					plid = {$this->getId ()} AND isActive = 1
			");
		
			$o = array (); $i = 0;
			foreach ($vs as $v)
			{
				$o[$i] = Dolumar_Players_Village::getVillage ($v['vid'], $syncBattles ? NOW : false);
				$o[$i]->setData ($v);
				$i ++;
			}
			
			$this->villages = $o;
		}
		
		return $this->villages;
	}
	
	public function getMyVillage ($id)
	{
		foreach ($this->getVillages () as $v)
		{
			if ($v->getId () == $id)
			{
				return $v;
			}
		}
		
		return false;
	}
	
	/*
		Returns the main village. Some actions require "player logs",
		but since logs are made on village level, these logs are stored
		in the "main village". 
		
		Your main village is basically your first village.
	*/
	public function getMainVillage ()
	{
		$villages = $this->getVillages ();
		if (count ($villages) > 0)
		{
			return $villages[0];
		}
		else
		{
			return null;
		}
	}
	
	/*
		Return the village that is currently selected by the player.
		(This is session based)
	*/
	public function getCurrentVillage ()
	{
		if (isset ($_SESSION['current_village']))
		{
			foreach ($this->getVillages () as $v)
			{
				if ($v->getId () == $_SESSION['current_village'])
					return $v;
			}
		}
		
		return $this->getMainVillage ();
	}
	
	/*
		Set the current village for this session
	*/
	public function setCurrentVillage ($objVillage)
	{
		// check if this is one of your villages
		$chk = false;
		foreach ($this->getVillages () as $v)
		{
			if ($v->getId () == $objVillage->getId ())
				$chk = true;
		}
		
		$_SESSION['current_village'] = $objVillage->getId ();
	}

	/* Preferences are from now on stored in cookies ;-) */
	public function getPreferences ()
	{
		return array
		(
			'buildingClick' => Neuron_Core_Tools::getInput ('_COOKIE', COOKIE_PREFIX . 'prefBC', 'int', 0),
			'minimapPosition' => Neuron_Core_Tools::getInput ('_COOKIE', COOKIE_PREFIX . 'prefMP', 'int', 0)
		);
	}
	
	public function setPreferences ($bc, $mp, $ads = false)
	{
		setCookie (COOKIE_PREFIX . 'prefBC', $bc, time () + COOKIE_LIFE);
		setCookie (COOKIE_PREFIX . 'prefMP', $mp, time () + COOKIE_LIFE);

		$db = Neuron_Core_Database::__getInstance ();
		$db->update
		(
			'n_players',
			array
			(
				'showAdvertisement' => ($ads ? '1' : '0')
			),
			"plid = '".$this->getId ()."'"
		);
	}
	
	/*
		Destroys all buildings of this players villages.
		(on killed accounts only ofcourse ;-))
	*/
	
	/*
	public function destroyVillages ()
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$this->loadData ();

		$villages = $this->getVillages ();
		
		foreach ($villages as $v)
		{
			$v->destroyVillage ();
		}
		
		// Save that!
		$db->update
		(
			'players',
			array ('isKillVillages' => '1'),
			"plid = '".$this->getId ()."'"
		);
	}
	*/
	
	/*
		Calculate the score for this player
	*/
	public function updateScore ()
	{
		$villages = $this->getVillages (false);
		
		$sum = 0;
		foreach ($villages as $v)
		{
			$sum += $v->getNetworth ();
		}
		
		$this->setScore ($sum);
		
		parent::updateScore ();
	}
	
	/*
		Give a bonus for refering a friend.
	*/
	public function giveReferralBonus ($objUser)
	{
		$village = $this->getMainVillage ();
		if ($village)
		{
			$runes = Neuron_Core_Tools::getRandomRuneOptions ();
			shuffle ($runes);
			
			$randomRune = $runes[0];
			
			$runes = array ();
			$runes[$randomRune] = 1;
			
			$village->resources->giveRunes ($runes);
			
			// Make it a log
			$objLogs = Dolumar_Players_Logs::__getInstance ();
			$objLogs->addReferreeBonusLog ($village, $objUser, $runes);
		}
	}
	
	public function getBrowserBasedGamesData ($data = null)
	{
		$out = parent::getBrowserBasedGamesData ($data);
		
		$locations = array ();
		foreach ($this->getVillages (false) as $vil)
		{
			$loc = $vil->buildings->getTownCenterLocation ();
			
			$lkey = '#'.$loc[0].','.$loc[1];
		
			$locations[] = array
			(
				'location_id' => $vil->getId (),
				'location_url' => ABSOLUTE_URL . '#' . $loc[0] . ',' . $loc[1],
				'name' => $vil->getName (),
				'loc-x' => $loc[0],
				'loc-y' => $loc[1],
				'score' => $vil->getNetworth ()
			);
		}
		
		$out['locations'] = $locations;
		
		return $out;
	}
	
	/*
		Return logs for this player.
	*/
	public function getLogs ($iStart, $iEnd)
	{
		$logs = Dolumar_Players_Logs::getInstance ();
		return $logs->getLogs ($this->getVillages ());
	}
	
	/*
		Reset actions
	*/
	public function doResetAccount ()
	{
		$this->onResetAccount ();	
		return parent::doResetAccount ();
	}
	
	private function onResetAccount ()
	{
		$db = Neuron_Core_Database::__getInstance ();
	
		foreach ($this->getVillages () as $v)
		{
			$v->withdrawAllUnits ();
		}
		
		$this->deactivateVillages ();
		
		// De-activate all clans
		$db->update
		(
			'clan_members',
			array
			(
				'cm_active' => 0
			),
			"plid = ".$this->getId ()
		);
	}
	
	/*
		Deactivate the villages of this player.
	*/
	public function deactivateVillages ()
	{
		$villages = $this->getVillages ();
		foreach ($villages as $v)
		{
			$v->deactivate ();
		}
	}
	
	public function useCredit ($amount, $data)
	{
		$action = isset ($data['action']) ? $data['action'] : null;
		$logs = Dolumar_Players_Logs::getInstance ();
		
		switch ($action)
		{
			case 'buyrunes':
			
				$runes = array_keys ($this->getMainVillage ()->resources->getInitialRunes ());
				
				$out = array ();
				foreach ($runes as $v)
				{
					if (isset ($data[$v]))
					{
						$out[$v] = intval ($data[$v]);
					}
				}
				
				$village = isset ($data['village']) ? $data['village'] : null;
				
				$village = $this->getMyVillage ($village);
				
				if ($village)
				{
					$village->resources->giveRunes ($out);
					$logs->addPremiumRunesBoughtLog ($village, $out);
				}
				
			break;
			
			case 'movevillage':
			
				$village = isset ($data['village']) ? $data['village'] : null;
				$village = $this->getMyVillage ($village);
				
				list ($ox, $oy) = $village->buildings->getTownCenterLocation ();
				
				$x = isset ($data['x']) ? $data['x'] : null;
				$y = isset ($data['y']) ? $data['y'] : null;
				
				if ($village && isset ($x) && isset ($y))
				{
					$village->movevillage->moveVillage ($x, $y);
					$logs->addPremiumMoveVillage ($village, $x, $y, $ox, $oy);
				}
			
			break;
			
			case 'movebuilding':
			
				$building = isset ($data['building']) ? $data['building'] : null;
			
				$village = isset ($data['village']) ? $data['village'] : null;
				$village = $this->getMyVillage ($village);
				
				$x = isset ($data['x']) ? intval ($data['x']) : null;
				$y = isset ($data['y']) ? intval ($data['y']) : null;
				
				if ($village && isset ($x) && isset ($y) && isset ($building))
				{
					$building = $village->buildings->getBuilding ($building);
					
					if ($building && $building->checkBuildLocation ($village, $x, $y))
					{
						list ($ox, $oy) = $building->getLocation ();
						
						$building->setLocation ($x, $y);
						$logs->addPremiumMoveBuilding ($building, $x, $y, $ox, $oy);
					}
				}
			
			break;
			
			case 'bonusbuilding':
			
				$village = isset ($data['village']) ? $data['village'] : null;
				$village = $this->getMyVillage ($village);
				
				$x = isset ($data['x']) ? intval ($data['x']) : null;
				$y = isset ($data['y']) ? intval ($data['y']) : null;
				
				$building = isset ($data['building']) ? intval ($data['building']) : null;
				$building = Dolumar_Buildings_Building::getBuilding ($building, $village->getRace ());
				
				$extra = isset ($data['tile']) ? intval ($data['tile']) : null;
				
				$chk = $building->checkBuildLocation ($village, $x, $y);
				
				if ($chk[0])
				{					
					$building = $building->build ($village, $x, $y, $extra);
					
					$logs->addPremiumBonusBuilding ($building, $x, $y);
				}
				
			break;

			case 'speedup':

				$type = isset ($data['type']) ? $data['type'] : null;
				$duration = isset ($data['duration']) ? $data['duration'] : null;

				switch ($type)
				{
					case 'building':

						$id = isset ($data['building']) ? $data['building'] : null;

						$building = Dolumar_Buildings_Building::getFromId ($id);
						$building->speedupBuild ($duration);

						// Reload the status bar
						$player = $building->getVillageForced ()->getOwner ();
						$player->updates->setFlag ('refresh-statusbar');

					break;

					case 'training':

						$id = isset ($data['order']) ? $data['order'] : null;
						$village = isset ($data['village']) ? $data['village'] : null;

						$village = Dolumar_Players_Village::getFromId ($village);
						$village->units->speedupBuild ($id, $data['duration']);

						$player = $village->getOwner ();
						$player->updates->setFlag ('refresh-statusbar');

					break;

					case 'scouting':

						$id = isset ($data['scoutid']) ? $data['scoutid'] : null;
						$village = isset ($data['village']) ? $data['village'] : null;

						$village = Dolumar_Players_Village::getFromId ($village);
						$village->speedupScouting ($id, $data['duration']);

						$player = $village->getOwner ();
						$player->updates->setFlag ('refresh-statusbar');

					break;
				}

			break;

			case 'buyresources':

				$village = isset ($data['village']) ? $data['village'] : null;
				$resource = isset ($data['resource']) ? $data['resource'] : null;

				$village = $this->getMyVillage ($village);

				if ($village)
				{
					if ($resource === 'all')
					{
						$village->resources->fillAll ();
					}
					else
					{
						$village->resources->fill ($resource);
					}
				}

			break;
		
			default: 
				$this->extendPremiumAccount (60*60*24*15);
			break;
		}
	}
	
	/*
		Clan
	*/
	public function getClans ()
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$chk = $db->getDataFromQuery
		(
			$db->customQuery
			("
				SELECT
					*
				FROM
					clan_members
				LEFT JOIN
					clans ON clan_members.c_id = clans.c_id
				WHERE
					clan_members.plid = ".$this->getId ()."
					AND cm_active = 1
			")
		);
		
		$out = array ();
		foreach ($chk as $v)
		{
			$out[] = new Dolumar_Players_Clan ($v['c_id'], $v);
		}
		
		return $out;
	}
	
	public function isClanmember (Dolumar_Players_Player $player)
	{
		foreach ($this->getClans () as $v)
		{
			if ($v->isMember ($player))
			{
				return true;
			}
		}
		return false;
	}
	
	/*
		Get main clan
	*/
	public function getMainClan ()
	{
		$clans = $this->getClans ();
		if (count ($clans) > 0)
		{
			return $clans[0];
		}
		else
		{
			return false;
		}
	}
	
	/*
		Vacation
	*/
	public function inVacationMode ()
	{
		if ($this->getVacationStart () != null)
		{
			if ($this->getVacationStart () < (time () - 60 * 60 * 24 * 31))
			{
				$this->endVacationMode ();
			}
		}
		return parent::inVacationMode ();
	}


	public function startVacationMode ()
	{
		$logs = Dolumar_Players_Logs::getInstance ();
		
		$logs->clearFilters ();
		$logs->addShowOnly ('vacation_end');
		$logs->setTimeInterval (time () - 60*60*24*14);
		
		$delogs = $logs->getLogs ($this);
	
		$canStartVacationmode = count ($delogs) == 0;
		if ($canStartVacationmode)
		{
			$this->onStartVacationMode ();
			return parent::startVacationMode ();
		}
		else
		{
			$this->error = 'vacation_limit';
			return false;
		}
	}
	
	private function onStartVacationMode ()
	{
		foreach ($this->getVillages () as $v)
		{
			$v->withdrawAllUnits ();
		}
	}
	
	public function endVacationMode ()
	{
		if (parent::endVacationMode ())
		{
			$logs = Dolumar_Players_Logs::getInstance ();
			$logs->addEndVacationLog ($this);
			
			return true;
		}
		else
		{
			return false;
		}
	}
	
	protected function onSendNotifications (BrowserGamesHub_Notification $notification)
	{
		foreach ($this->getVillages () as $v)
		{
			$loc = $v->buildings->getTownCenterLocation ();
			
			$width = 600;
			$height = 400;
			
			$zoom = mt_rand (50, 100);
			$loc[0] += (mt_rand (0, 10) - 5);
			$loc[1] += (mt_rand (0, 10) - 5);
		
			$notification->addImage 
			(
				ABSOLUTE_URL . 'image/snapshot/?zoom='.$zoom.'&x='.$loc[0].'&y='.$loc[1].'&width='.$width.'&height='.$height, 
				$v->getName (),
				ABSOLUTE_URL . '#' . $loc[0] . ',' . $loc[1]
			);
		}
	}

	/*
	* Called when someone sent you a gift.
	*/
	public function invitationGiftReceiver ($data, Neuron_GameServer_Player $from)
	{
		$runes = array ('wind', 'water', 'fire', 'earth');
		$village = $this->getMainVillage ();
		if ($village)
		{
			$rune = $runes[mt_rand (0, count ($runes) - 1)];

			$data = array ();
			$data['runes'] = new Dolumar_Logable_RuneContainer (array ($rune => 1));
			$data['from'] = $from;

			$village->resources->giveRune ($rune, 1);
			Dolumar_Players_Logs::getInstance ()->addLog ($village, 'invitegift_receive', $data, true);
		}
	}

	/*
	* Called when someone accepts your gift.
	*/
	public function invitationGiftSender ($data, Neuron_GameServer_Player $to)
	{
		$village = $this->getMainVillage ();

		if ($village)
		{
			$data = array ();
			$data['to'] = $to;
			Dolumar_Players_Logs::getInstance ()->addLog ($village, 'invitegift_received', $data, true);
		}
	}

	public function getStatistics ()
	{
		$buildings = array ();
		$total_buildings = 0;

		foreach ($this->getVillages () as $v)
		{
			foreach ($v->buildings->getAllBuildingAmounts () as $kk => $vv)
			{
				if (!isset ($buildings[$kk]))
				{
					$buildings[$kk] = 0;
				}

				$buildings[$kk] += $vv;
				$total_buildings += $vv;
			}
		}

		$battles = array ();

		foreach ($this->getVillages () as $v)
		{
			foreach ($v->battle->countBattles () as $kk => $vv)
			{
				if (!isset ($battles[$kk]))
				{
					$battles[$kk] = 0;
				}

				$battles[$kk] += $vv;
			}
		}

		return array
		(
			'score' => $this->getScore (),
			'buildings' => $buildings,
			'initialized' => $this->isPlaying (),
			'total_buildings' => $total_buildings,
			'battles' => $battles,
			'premium' => $this->isPremium () ? '1' : '0',
			'logins' => $this->countLogins (),
			'clan' => count ($this->getClans ()) > 0 ? '1' : '0'
		);
	}

	protected function doEndVacationMode ()
	{
		$db = Neuron_DB_Database::__getInstance ();
		
		// Reset all resource times
		$db->query
		("
			UPDATE
				villages
			SET
				lastResRefresh = '".time()."'
			WHERE
				plid = {$this->getId()} AND
				isActive = '1'
		");
		
		parent::doEndVacationMode ();
	}
	
	public function __destruct ()
	{
		unset ($this->data);
	
		if (isset ($this->villages))
		{
			/*
			foreach ($this->villages as $k => $v)
			{
				$v->__destruct ();
			}
			*/
		}
		
		unset ($this->villages);
		
		parent::__destruct ();
	}
}
?>
