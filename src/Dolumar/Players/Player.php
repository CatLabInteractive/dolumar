<?php
/**
 *  Dolumar, browser based strategy game
 *  Copyright (C) 2009 Thijs Van der Schaeghe
 *  CatLab Interactive bvba, Gent, Belgium
 *  http://www.catlab.eu/
 *  http://www.dolumar.com/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

class Dolumar_Players_Player extends Neuron_GameServer_Player
{
	private $data;
	private $villages;

	/**
	 * @return Dolumar_Players_NPCPlayer
	 */
	public static function getNpcPlayer()
	{
		return new Dolumar_Players_NPCPlayer(null);
	}

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
			$buildings = $vil->buildings;
			if ($buildings) {
                $loc = $buildings->getTownCenterLocation();

                $locations[] = array
                (
                    'location_id' => $vil->getId(),
                    'location_url' => ABSOLUTE_URL . '#' . $loc[0] . ',' . $loc[1],
                    'name' => $vil->getName(),
                    'loc-x' => $loc[0],
                    'loc-y' => $loc[1],
                    'score' => $vil->getNetworth()
                );
            }
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
	
	/**
	 * Runes this player may still buy this month.
	 */
	public function getBuyableRunesLeft ()
	{
		$maximum = Dolumar_Premium_Prices::MAX_BUY_RUNES * GAME_SPEED_RESOURCES;

		$logs = Dolumar_Players_Logs::getInstance ();

		$maxdate = mktime (0, 0, 0, date ('n', NOW), 1, date ('Y', NOW));

		// Set the window explicitly (the logs instance is shared): this month,
		// including runes bought earlier in this same request (cron reconcile).
		$logs->clearFilters ();
		$logs->addShowOnly ('premium_runes_bought');
		$logs->setTimeInterval ($maxdate, null);

		$amount = 0;
		foreach ($logs->getLogs ($this->getVillages (), 0, $maximum, 'DESC') as $v)
		{
			if ($v['unixtime'] < $maxdate)
			{
				break;
			}

			if ($v['action'] == 'premium_runes_bought')
			{
				foreach ($v['data'][0]->getLogArray () as $n)
				{
					$amount += $n;
				}
			}
		}

		return max (0, $maximum - $amount);
	}

	/**
	 * End of the free building-move period after a village move (or false).
	 */
	public function getFreeBuildingMoveEndDate ($village)
	{
		$objLogs = Dolumar_Players_Logs::getInstance ();

		$objLogs->clearFilters ();
		$objLogs->addShowOnly ('premium_movevillage');
		$objLogs->setTimeInterval (null, null);

		foreach ($objLogs->getLogs ($village, 0, 1, 'DESC') as $v)
		{
			return $v['timestamp'] + Dolumar_Premium_Prices::FREE_MOVEBUILDING_DURATION;
		}

		return false;
	}

	public function isBuildingMoveFree ($village)
	{
		return $this->getFreeBuildingMoveEndDate ($village) > NOW;
	}

	/**
	 * Parse the rune amounts of a buyrunes request: [rune => count], only counts > 0.
	 */
	private function parseRuneAmounts ($data)
	{
		$main = $this->getMainVillage ();
		if (!$main)
		{
			return array ();
		}

		$out = array ();
		foreach (array_keys ($main->resources->getInitialRunes ()) as $v)
		{
			if (isset ($data[$v]))
			{
				$amount = $this->strictInt ($data[$v]);
				if ($amount === null || $amount < 0)
				{
					return null;
				}
				if ($amount > 0)
				{
					$out[$v] = $amount;
				}
			}
		}
		return $out;
	}

	private function getVillageFromData ($data)
	{
		$id = isset ($data['village']) ? $this->strictInt ($data['village']) : null;
		return $id === null ? false : $this->getMyVillage ($id);
	}

	/**
	 * May this village be moved again? (Behaviour kept as in the shop window.)
	 */
	public function canMoveVillage ($village)
	{
		$objLogs = Dolumar_Players_Logs::getInstance ();

		$objLogs->clearFilters ();
		$objLogs->addShowOnly ('premium_movevillage');
		// No upper bound: a move applied earlier in this request (cron reconcile) counts too.
		$objLogs->setTimeInterval (time () - 60 * 60 * Dolumar_Premium_Prices::MIN_MOVEVILLAGE_INTERVAL_DAYS, null);

		return count ($objLogs->getLogs ($village, 0, 5, 'DESC')) == 0;
	}

	/**
	 * Is this a bonus tile that may be used with a bonus building? Public tiles
	 * inside their date range (as listed by the bonus building window) or this
	 * player's own uploaded tiles.
	 */
	public function isValidBonusTile ($tile)
	{
		$tile = $this->strictInt ($tile);
		if ($tile === null || $tile < 1)
		{
			return false;
		}

		$db = Neuron_DB_Database::getInstance ();
		$rows = $db->query
		("
			SELECT
				t_id, t_imagename
			FROM
				players_tiles
			WHERE
				t_id = {$tile} AND
				(
					(
						t_isPublic = 1 AND
						(t_startDate IS NULL OR t_startDate < FROM_UNIXTIME(".NOW.")) AND
						(t_endDate IS NULL OR t_endDate > FROM_UNIXTIME(".NOW."))
					)
					OR t_userid = " . intval ($this->getId ()) . "
				)
		");

		return count ($rows) == 1 && file_exists (PUBLIC_PATH . $rows[0]['t_imagename']);
	}

	private function strictInt ($value)
	{
		if (is_int ($value))
		{
			return $value;
		}
		if (is_string ($value) && preg_match ('/^-?[0-9]{1,9}$/', $value))
		{
			return (int) $value;
		}
		return null;
	}

	/**
	 * Remaining time (seconds) of a speed-up target owned by this player, or null.
	 */
	private function getSpeedupTarget ($type, $data)
	{
		switch ($type)
		{
			case 'building':
				$id = isset ($data['building']) ? $this->strictInt ($data['building']) : null;
				if (!$id)
				{
					return null;
				}
				$building = Dolumar_Buildings_Building::getFromId ($id);
				if (!$building)
				{
					return null;
				}
				$village = $building->getVillageForced ();
				if (!$village || !$this->getMyVillage ($village->getId ()))
				{
					return null;
				}
				return array ('left' => $building->getTimeLeft (), 'object' => $building);

			case 'training':
				$id = isset ($data['order']) ? $this->strictInt ($data['order']) : null;
				$village = $this->getVillageFromData ($data);
				if (!$village || !$id)
				{
					return null;
				}
				$order = $village->units->getTrainingStatus ($id);
				if (!$order || $order['village'] != $village->getId ())
				{
					return null;
				}
				return array ('left' => $order['timeLeft'], 'village' => $village);

			case 'scouting':
				$id = isset ($data['scoutid']) ? $this->strictInt ($data['scoutid']) : null;
				$village = $this->getVillageFromData ($data);
				if (!$village || $id === null)
				{
					return null;
				}
				$scout = $village->getScoutData ($id);
				if (!$scout)
				{
					return null;
				}
				return array ('left' => $scout['finishDate'] - NOW, 'village' => $village);
		}

		return null;
	}

	/**
	 * Units (of the type's unit size) of a validated speed-up request, or null.
	 */
	private function getSpeedupUnits ($type, $data, $target)
	{
		$speedup = Dolumar_Premium_Prices::getSpeedup ($type);
		if (!$speedup || !$target || $speedup['unit'] < 1)
		{
			return null;
		}

		$duration = isset ($data['duration']) ? $this->strictInt ($data['duration']) : null;
		if ($duration === null || $duration < 1 || $duration % $speedup['unit'] !== 0)
		{
			return null;
		}

		$units = intdiv ($duration, $speedup['unit']);
		$needed = (int) ceil ($target['left'] / $speedup['unit']);

		if ($units < 1 || $units > $needed)
		{
			return null;
		}

		return $units;
	}

	/**
	 * Authoritative price of a credit action. Null when the request is invalid,
	 * not owned by this player, or does not use credits.
	 */
	public function quoteCreditAction (array $data): ?array
	{
		$action = isset ($data['action']) ? $data['action'] : '';

		switch ($action)
		{
			case 'buyrunes':
				$village = $this->getVillageFromData ($data);
				$runes = $this->parseRuneAmounts ($data);
				if (!$village || !$runes)
				{
					return null;
				}
				$sum = array_sum ($runes);
				if ($sum < 1 || $sum > $this->getBuyableRunesLeft ())
				{
					return null;
				}
				return array ('credits' => $sum * Dolumar_Premium_Prices::RUNE, 'description' => $sum . ' runes');

			case 'buyresources':
				$village = $this->getVillageFromData ($data);
				$resource = isset ($data['resource']) ? $data['resource'] : null;
				if (!$village || !is_string ($resource))
				{
					return null;
				}
				if ($resource === 'all')
				{
					return array ('credits' => Dolumar_Premium_Prices::RESOURCES_ALL, 'description' => 'Fill all resources');
				}
				if (!array_key_exists ($resource, $village->resources->getResources ()))
				{
					return null;
				}
				return array ('credits' => Dolumar_Premium_Prices::RESOURCES_ONE, 'description' => 'Fill resources');

			case 'movevillage':
				$village = $this->getVillageFromData ($data);
				if (!$village || !isset ($data['x']) || !isset ($data['y'])
					|| $this->strictInt ($data['x']) === null || $this->strictInt ($data['y']) === null
					|| !$this->canMoveVillage ($village))
				{
					return null;
				}
				return array
				(
					'credits' => Dolumar_Premium_Prices::MOVE_VILLAGE,
					'description' => 'Move village ' . $village->getName () . ' to ' . $this->strictInt ($data['x']) . ',' . $this->strictInt ($data['y'])
				);

			case 'movebuilding':
				$village = $this->getVillageFromData ($data);
				if (!$village || !isset ($data['building']) || !isset ($data['x']) || !isset ($data['y'])
					|| $this->strictInt ($data['x']) === null || $this->strictInt ($data['y']) === null)
				{
					return null;
				}
				$bid = $this->strictInt ($data['building']);
				$building = $bid === null ? null : $village->buildings->getBuilding ($bid);
				if (!$building || !$building->isMoveable ())
				{
					return null;
				}
				if ($this->isBuildingMoveFree ($village))
				{
					return null;
				}
				return array ('credits' => Dolumar_Premium_Prices::MOVE_BUILDING, 'description' => 'Move building');

			case 'bonusbuilding':
				$village = $this->getVillageFromData ($data);
				if (!$village || !isset ($data['building']) || !isset ($data['x']) || !isset ($data['y'])
					|| $this->strictInt ($data['building']) !== 100
					|| $this->strictInt ($data['x']) === null || $this->strictInt ($data['y']) === null
					|| !isset ($data['tile']) || !$this->isValidBonusTile ($data['tile']))
				{
					return null;
				}
				return array ('credits' => Dolumar_Premium_Prices::BONUS_BUILDING, 'description' => 'Bonus building');

			case 'speedup':
				$type = isset ($data['type']) ? $data['type'] : null;
				if (!is_string ($type))
				{
					return null;
				}
				$target = $this->getSpeedupTarget ($type, $data);
				$units = $this->getSpeedupUnits ($type, $data, $target);
				if ($units === null)
				{
					return null;
				}
				return array
				(
					'credits' => Dolumar_Premium_Prices::getSpeedupCredits ($type, $units),
					'description' => 'Speed-up (' . $type . ')'
				);
		}

		return parent::quoteCreditAction ($data);
	}

	/**
	 * Apply a purchased action. Returns true only when it was actually applied.
	 */
	public function useCredit ($amount, $data)
	{
		$action = isset ($data['action']) ? $data['action'] : null;
		$logs = Dolumar_Players_Logs::getInstance ();

		switch ($action)
		{
			case 'buyrunes':

				$village = $this->getVillageFromData ($data);
				$out = $this->parseRuneAmounts ($data);

				if (!$village || !$out)
				{
					return false;
				}

				if (array_sum ($out) > $this->getBuyableRunesLeft ())
				{
					return false;
				}

				$village->resources->giveRunes ($out);
				$logs->addPremiumRunesBoughtLog ($village, $out);

				return true;

			case 'movevillage':

				$village = $this->getVillageFromData ($data);
				$x = isset ($data['x']) ? $this->strictInt ($data['x']) : null;
				$y = isset ($data['y']) ? $this->strictInt ($data['y']) : null;

				if (!$village || !isset ($x) || !isset ($y) || !$this->canMoveVillage ($village))
				{
					return false;
				}

				list ($ox, $oy) = $village->buildings->getTownCenterLocation ();

				if (!$village->movevillage->moveVillage ($x, $y))
				{
					return false;
				}

				$logs->addPremiumMoveVillage ($village, $x, $y, $ox, $oy);
				return true;

			case 'movebuilding':

				$village = $this->getVillageFromData ($data);
				$building = isset ($data['building']) ? $this->strictInt ($data['building']) : null;
				$x = isset ($data['x']) ? $this->strictInt ($data['x']) : null;
				$y = isset ($data['y']) ? $this->strictInt ($data['y']) : null;

				if (!$village || !isset ($x) || !isset ($y) || !isset ($building))
				{
					return false;
				}

				$building = $village->buildings->getBuilding ($building);

				if (!$building || !$building->isMoveable ())
				{
					return false;
				}

				$chk = $building->checkBuildLocation ($village, $x, $y);
				if (!$chk || !$chk[0])
				{
					return false;
				}

				list ($ox, $oy) = $building->getLocation ();

				$building->setLocation ($x, $y);
				$logs->addPremiumMoveBuilding ($building, $x, $y, $ox, $oy);

				return true;

			case 'bonusbuilding':

				$village = $this->getVillageFromData ($data);
				if (!$village)
				{
					return false;
				}

				$x = isset ($data['x']) ? $this->strictInt ($data['x']) : null;
				$y = isset ($data['y']) ? $this->strictInt ($data['y']) : null;

				if ($x === null || $y === null || !isset ($data['building']) || $this->strictInt ($data['building']) !== 100
					|| !isset ($data['tile']) || !$this->isValidBonusTile ($data['tile']))
				{
					return false;
				}

				$building = Dolumar_Buildings_Building::getBuilding (100, $village->getRace ());

				if (!$building)
				{
					return false;
				}

				$extra = $this->strictInt ($data['tile']);

				$chk = $building->checkBuildLocation ($village, $x, $y);

				if (!$chk || !$chk[0])
				{
					return false;
				}

				$building = $building->build ($village, $x, $y, $extra);
				if (!$building)
				{
					return false;
				}

				$logs->addPremiumBonusBuilding ($building, $x, $y);
				return true;

			case 'speedup':

				$type = isset ($data['type']) ? $data['type'] : null;
				if (!is_string ($type))
				{
					return false;
				}

				$target = $this->getSpeedupTarget ($type, $data);
				if (!$target || $target['left'] <= 0)
				{
					return false;
				}

				$duration = isset ($data['duration']) ? $this->strictInt ($data['duration']) : null;
				if ($duration === null || $duration < 1)
				{
					return false;
				}

				switch ($type)
				{
					case 'building':
						$target['object']->speedupBuild ($duration);
					break;

					case 'training':
						$target['village']->units->speedupBuild (intval ($data['order']), $duration);
					break;

					case 'scouting':
						$target['village']->speedupScouting (intval ($data['scoutid']), $duration);
					break;

					default:
						return false;
				}

				// Reload the status bar
				$this->updates->setFlag ('refresh-statusbar');
				return true;

			case 'buyresources':

				$village = $this->getVillageFromData ($data);
				$resource = isset ($data['resource']) ? $data['resource'] : null;

				if (!$village || !is_string ($resource))
				{
					return false;
				}

				if ($resource === 'all')
				{
					$village->resources->fillAll ();
					return true;
				}

				if (!array_key_exists ($resource, $village->resources->getResources ()))
				{
					return false;
				}

				$village->resources->fill ($resource);
				return true;

			default:
				$this->extendPremiumAccount (60*60*24*15);
				return true;
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

	/**
	 * @return mixed|string|string[]|null
	 * @throws Exception
	 */
	public function getNewVillageName()
	{
		$text = Neuron_Core_Text::__getInstance ();

		$name = Neuron_Core_Tools::putIntoText
		(
			$text->get ('playervillage', 'main', 'main'),
			array
			(
				'player' => $this->getNickname ()
			)
		);

		// Count the existing villages
		$villages = count ($this->getVillages ());
		if ($villages >= 1) {
			$name .= ' ' . ($villages + 1);
		}

		return $name;
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