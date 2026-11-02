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

/**
 * The only writer of the NPC code: turns actions into game API calls, announces transitions in the players'
 * logs, and keeps a decision log in npc_log and on the cron output.
 */
class Dolumar_Npc_Executor
{
	/** @var Dolumar_Players_Village */
	private $village;

	/** @var Dolumar_Npc_Snapshot */
	private $s;

	/** @var callable|null */
	private $logger;

	/** @var Dolumar_Npc_StandingRepository */
	private $repository;

	/** @var bool one successful build per run */
	private $built = false;

	/** @var array plid => [column => value] changes the actions produced (squad ids, battle ids) */
	private $rowUpdates = [];

	/** @var array tribe row changes (requests) */
	private $tribeUpdates = [];

	/**
	 * @param Dolumar_Players_Village $village
	 * @param Dolumar_Npc_Snapshot $s
	 * @param callable|null $logger
	 */
	public function __construct (Dolumar_Players_Village $village, Dolumar_Npc_Snapshot $s, $logger = null)
	{
		$this->village = $village;
		$this->s = $s;
		$this->logger = $logger;
		$this->repository = Dolumar_Npc_StandingRepository::getInstance ();
	}

	/**
	 * @return array plid => [column => value]
	 */
	public function rowUpdates ()
	{
		return $this->rowUpdates;
	}

	/**
	 * @return array
	 */
	public function tribeUpdates ()
	{
		return $this->tribeUpdates;
	}

	/**
	 * Tell the players about peace, pacts and deliveries.
	 * @param array $transitions from Dolumar_Npc_StandingUpdater
	 */
	public function announce (array $transitions)
	{
		foreach ($transitions as $transition) {
			$plid = (int) $transition['plid'];
			switch ($transition['kind']) {
				case 'peace':
					$this->notify ($plid, 0, 'npc_peace', [$this->tribeName ()]);
					$this->log ('peace with player ' . $plid . ' by ' . $transition['by'], $plid);
					break;
				case 'pact':
					$this->notify ($plid, 0, 'npc_pact', [$this->tribeName ()]);
					$this->log ('pact with player ' . $plid, $plid);
					break;
				case 'pact_broken':
					$this->notify ($plid, 0, 'npc_pact_broken', [$this->tribeName ()]);
					$this->log ('pact with player ' . $plid . ' broken', $plid);
					break;
				case 'request_delivered':
					$this->notify ($plid, (int) $transition['vid'], 'npc_thanks', [$this->tribeName ()]);
					$this->log ('request delivered by player ' . $plid . ' from village ' . $transition['vid'], $plid);
					$this->tribeUpdates['request_res'] = '';
					$this->tribeUpdates['request_amount'] = 0;
					break;
				case 'tier':
					$this->log ('player ' . $plid . ' is now ' . $transition['to'] . ' (was ' . $transition['from'] . ')', $plid);
					break;
			}
		}
	}

	/**
	 * @param Dolumar_Npc_Action $action
	 * @return bool
	 */
	public function execute (Dolumar_Npc_Action $action)
	{
		try {
			switch ($action->type) {
				case Dolumar_Npc_Action::SCOUT:
					return $this->scout ($action);
				case Dolumar_Npc_Action::BUILD:
					return $this->build ($action);
				case Dolumar_Npc_Action::TRAIN:
					return $this->train ($action);
				case Dolumar_Npc_Action::REQUEST:
					return $this->request ($action);
				case Dolumar_Npc_Action::SCOUT_RAID:
					return $this->scoutRaid ($action);
				case Dolumar_Npc_Action::ENVOY:
					return $this->envoy ($action);
				case Dolumar_Npc_Action::WAR_PARTY:
					return $this->warParty ($action);
				case Dolumar_Npc_Action::ALLIED_STRIKE:
					return $this->alliedStrike ($action);
				case Dolumar_Npc_Action::SUPPORT:
					return $this->support ($action);
				case Dolumar_Npc_Action::THANK_YOU:
					return $this->thankYou ($action);
				case Dolumar_Npc_Action::RECALL_SUPPORT:
					return $this->recallSupport ($action);
			}
			$this->log ('unknown action ' . $action);
			return false;
		} catch (Exception $e) {
			$this->log ('error on ' . $action . ': ' . $e->getMessage ());
			return false;
		}
	}

	/* Economy ------------------------------------------------------------------------------------------ */

	private function scout (Dolumar_Npc_Action $action)
	{
		if ($this->village->scout ((int) $action->get ('runes', Dolumar_Npc_Config::SCOUT_RUNES_AMOUNT))) {
			$this->log ('scouting for runes');
			return true;
		}
		$this->log ('scouting for runes failed (not enough resources?)');
		return false;
	}

	private function build (Dolumar_Npc_Action $action)
	{
		if ($this->built) {
			return false;
		}

		$class = (string) $action->get ('building');
		$rune = $action->get ('rune');
		$village = $this->village;

		$targetLevel = $village->buildings->getBuildingAmount ($class) + 1;
		foreach ($village->buildings->getBuildings () as $building) {
			if (!$building instanceof $class || $building->getLevel () >= $targetLevel) {
				continue;
			}
			$status = $building->upgrade ();
			if ($status === true) {
				$this->log ('upgraded ' . $building->getClassName () . ' ' . $building->getId () . ' to level ' . ($building->getLevel () + 1));
				$this->built = true;
				return true;
			}
			$this->log ('upgrade of ' . $building->getClassName () . ' ' . $building->getId () . ' failed: ' . $status);
		}

		$building = Dolumar_Buildings_Building::getBuildingFromName ($class, $village->getRace ());
		if ($rune && $building->canSelectRune ($village)) {
			$building->setChosenRune ($village, $rune);
		}

		$location = $village->buildings->getEmptyLocationNearPosition ($village->buildings->getTownCenterLocation ());
		if ($village->buildings->build ($building, $location[0], $location[1])) {
			$this->log ('built ' . $building->getClassName () . ' at [' . $location[0] . ',' . $location[1] . ']');
			$this->built = true;
			return true;
		}

		$this->log ('building ' . $building->getClassName () . ' failed: ' . $village->buildings->getError ());
		return false;
	}

	private function train (Dolumar_Npc_Action $action)
	{
		$buildingId = (int) $action->get ('building');
		$class = (string) $action->get ('unit');
		$amount = (int) $action->get ('amount');

		foreach ($this->village->buildings->getBuildings () as $building) {
			if (!$building instanceof Dolumar_Buildings_Training || (int) $building->getId () !== $buildingId) {
				continue;
			}
			foreach ($building->getCheckedAvailableUnits () as $unit) {
				if (get_class ($unit) !== $class) {
					continue;
				}
				if ($building->doTrainUnits ($unit, $amount)) {
					$this->log ('training ' . $amount . ' ' . $unit->getClassName () . ' in ' . $building->getClassName () . ' ' . $buildingId);
					return true;
				}
				$this->log ('training ' . $amount . ' ' . $unit->getClassName () . ' failed: ' . $building->getTrainError ());
				return false;
			}
		}

		$this->log ('training building ' . $buildingId . ' or unit ' . $class . ' not found');
		return false;
	}

	private function request (Dolumar_Npc_Action $action)
	{
		$this->tribeUpdates['request_res'] = (string) $action->get ('res');
		$this->tribeUpdates['request_amount'] = (int) $action->get ('amount');
		$this->tribeUpdates['request_created'] = $this->s->now;
		$this->log ('asking the neighbours for ' . $action->get ('amount') . ' ' . $action->get ('res'));
		return true;
	}

	/* War ---------------------------------------------------------------------------------------------- */

	private function scoutRaid (Dolumar_Npc_Action $action)
	{
		$plid = (int) $action->get ('plid');
		$target = $this->targetVillage ((int) $action->get ('target'));
		if ($target === null) {
			return false;
		}

		$battleId = $this->sendAttack ($target, 0.0, (int) $action->get ('units', Dolumar_Npc_Config::SCOUT_RAID_UNITS));
		if ($battleId === null) {
			return false;
		}

		$this->notify ($plid, $target->getId (), 'npc_scouts', [$this->tribeName ()]);
		$this->log ('scouts sent to village ' . $target->getId () . ' of player ' . $plid . ' (battle ' . $battleId . ')', $plid);
		return true;
	}

	private function envoy (Dolumar_Npc_Action $action)
	{
		$plid = (int) $action->get ('plid');
		$price = (int) $action->get ('price');
		$this->notify ($plid, (int) $action->get ('target'), 'npc_envoy', [$this->tribeName (), $price]);
		$this->log ('envoy sent to player ' . $plid . ' asking ' . $price . ' resources', $plid);
		return true;
	}

	private function warParty (Dolumar_Npc_Action $action)
	{
		$plid = (int) $action->get ('plid');
		$target = $this->targetVillage ((int) $action->get ('target'));
		if ($target === null) {
			return false;
		}

		$battleId = $this->sendAttack ($target, (float) $action->get ('fraction'), 0);
		if ($battleId === null) {
			return false;
		}

		$this->rowUpdates[$plid]['last_npc_battle_id'] = $battleId;
		$this->notify ($plid, $target->getId (), 'npc_warparty', [$this->tribeName ()]);
		$this->log ('war party (' . round ($action->get ('fraction') * 100) . '% of the army) sent to village ' . $target->getId () . ' of player ' . $plid . ' (battle ' . $battleId . ')', $plid);
		return true;
	}

	private function alliedStrike (Dolumar_Npc_Action $action)
	{
		$plid = (int) $action->get ('plid');
		$attacker = (int) $action->get ('attacker');
		$target = $this->targetVillage ((int) $action->get ('target'));
		if ($target === null) {
			return false;
		}

		$battleId = $this->sendAttack ($target, (float) $action->get ('fraction'), 0);
		if ($battleId === null) {
			return false;
		}

		$this->rowUpdates[$attacker]['last_npc_battle_id'] = $battleId;
		$this->notify ($plid, 0, 'npc_strike', [$this->tribeName (), $target->getName ()]);
		$this->log ('striking village ' . $target->getId () . ' of player ' . $attacker . ' for ally ' . $plid . ' (battle ' . $battleId . ')', $plid);
		return true;
	}

	/* Friendship --------------------------------------------------------------------------------------- */

	private function support (Dolumar_Npc_Action $action)
	{
		return $this->sendSupport ((int) $action->get ('plid'), (int) $action->get ('target'), (float) $action->get ('fraction'), 0, 'npc_support');
	}

	private function thankYou (Dolumar_Npc_Action $action)
	{
		return $this->sendSupport ((int) $action->get ('plid'), (int) $action->get ('target'), 0.0, (int) $action->get ('units', Dolumar_Npc_Config::THANK_YOU_UNITS), 'npc_thanks_squad');
	}

	private function sendSupport ($plid, $targetVid, $fraction, $units, $logAction)
	{
		$target = $this->targetVillage ($targetVid);
		if ($target === null) {
			return false;
		}

		$squads = $this->assembleSquads ($fraction, $units, 1);
		if (count ($squads) === 0) {
			$this->log ('no units to support village ' . $targetVid . ' of player ' . $plid, $plid);
			return false;
		}

		$squad = $squads[0];
		$squad->sendToVillage ($target);

		$this->rowUpdates[$plid]['support_squad'] = (int) $squad->getId ();
		$this->notify ($plid, $target->getId (), $logAction, [$this->tribeName ()]);
		$this->log ('squad ' . $squad->getId () . ' (' . $squad->getUnitsAmount () . ' units) sent to support village ' . $targetVid . ' of player ' . $plid, $plid);
		return true;
	}

	private function recallSupport (Dolumar_Npc_Action $action)
	{
		$squadId = (int) $action->get ('squad');
		$squad = new Dolumar_Players_Squad ($squadId);
		$squad->setVillage ($this->village);

		if (!isset ($this->s->squads[$squadId])) {
			$this->log ('support squad ' . $squadId . ' no longer exists');
			return false;
		}

		$squad->goHome ();
		$this->log ('support squad ' . $squadId . ' recalled', (int) $action->get ('plid'));
		return true;
	}

	/* Helpers ------------------------------------------------------------------------------------------ */

	/**
	 * Build squads from the home army and send them against a village.
	 * @param Dolumar_Players_Village $target
	 * @param float $fraction share of the available army (0 to use $units)
	 * @param int $units fixed number of units
	 * @return int|null the battle id
	 */
	private function sendAttack (Dolumar_Players_Village $target, $fraction, $units)
	{
		$village = $this->village;

		$distance = Dolumar_Map_Map::getDistanceBetweenVillages ($village, $target);
		if ($distance === false) {
			$this->log ('village ' . $target->getId () . ' is unreachable');
			return null;
		}

		$slots = count ($village->getAttackSlots ($target));
		$squads = $this->assembleSquads ($fraction, $units, $slots);
		if (count ($squads) === 0) {
			$this->log ('no units available to attack village ' . $target->getId ());
			return null;
		}

		$oUnits = [];
		$slot = 1;
		foreach ($squads as $squad) {
			$unit = $squad->getUnit ($squad->getUnitType ());
			if (!$unit) {
				continue;
			}
			$oUnits[$slot ++] = $unit;
		}

		if (count ($oUnits) === 0 || !$village->attackVillage ($target, $oUnits)) {
			$this->log ('attack on village ' . $target->getId () . ' failed: ' . $village->getError ());
			return null;
		}

		$db = Neuron_DB_Database::getInstance ();
		$rows = $db->query ("SELECT MAX(battleId) AS id FROM battle WHERE vid = " . (int) $village->getId () . " AND targetId = " . (int) $target->getId ());
		foreach ($rows as $row) {
			return (int) $row['id'];
		}
		return 0;
	}

	/**
	 * Idle home squads first, then fresh squads from squadless units, up to the wanted size and squad count.
	 * @param float $fraction
	 * @param int $units
	 * @param int $maxSquads
	 * @return Dolumar_Players_Squad[]
	 */
	private function assembleSquads ($fraction, $units, $maxSquads)
	{
		$village = $this->village;
		$village->reloadUnits ();

		$available = $this->s->availableArmy ();
		$want = $units > 0 ? $units : (int) floor ($available * min (Dolumar_Npc_Config::MAX_ARMY_FRACTION, max (0.0, $fraction)));
		$want = max (1, min ($want, $available));

		$squads = [];

		// Idle squads that are at home.
		if ($units === 0) {
			foreach ($village->getSquads (false, true, true) as $squad) {
				if (count ($squads) >= $maxSquads || $want <= 0) {
					break;
				}
				if (!$squad->isHome () || !$squad->isIdle ()) {
					continue;
				}
				$amount = (int) $squad->getUnitsAmount ();
				if ($amount < 1 || $amount > $want) {
					continue;
				}
				$squads[] = $squad;
				$want -= $amount;
			}
		}

		// Fresh squads from squadless units, cheapest units first for small parties.
		while ($want > 0 && count ($squads) < $maxSquads) {
			$unit = $this->pickSquadlessUnit ($units > 0);
			if ($unit === null) {
				break;
			}
			$amount = (int) min ($want, $unit->getSquadlessAmount (), $unit->getMaxPerSquad ());
			if ($amount < 1) {
				break;
			}

			$squad = $village->addSquad ($this->tribeName (), $unit->getUnitId ());
			$squad->setVillage ($village);
			$squad->addUnits ($unit, $amount);
			if ($squad->getError ()) {
				$this->log ('could not fill a squad of ' . $unit->getClassName () . ': ' . $squad->getError ());
				$village->removeSquad ($squad->getId ());
				break;
			}

			$squads[] = $squad;
			$want -= $amount;
			$village->reloadUnits ();
		}

		return $squads;
	}

	/**
	 * @param bool $weakestFirst
	 * @return Dolumar_Units_Unit|null a unit with squadless troops
	 */
	private function pickSquadlessUnit ($weakestFirst)
	{
		$candidates = [];
		foreach ($this->village->getAllUnits ($this->s->now) as $unit) {
			if ($unit->hasEternalLife () || $unit->getSquadlessAmount () < 1) {
				continue;
			}
			$candidates[] = $unit;
		}
		if (count ($candidates) === 0) {
			return null;
		}
		usort ($candidates, function ($a, $b) use ($weakestFirst) {
			if ($weakestFirst) {
				return $a->getStat ('melee') <=> $b->getStat ('melee');
			}
			return $b->getSquadlessAmount () <=> $a->getSquadlessAmount ();
		});
		return $candidates[0];
	}

	/**
	 * @param int $vid
	 * @return Dolumar_Players_Village|null an active village
	 */
	private function targetVillage ($vid)
	{
		if ($vid <= 0) {
			return null;
		}
		$village = Dolumar_Players_Village::getVillage ($vid);
		if (!$village || !$village->isFound () || !$village->isActive () || $village->isNpc ()) {
			$this->log ('village ' . $vid . ' is not a valid target');
			return null;
		}
		return $village;
	}

	/**
	 * Write a notification to the player's log, on the given village or else their first village.
	 * @param int $plid
	 * @param int $vid
	 * @param string $action
	 * @param array $data
	 */
	private function notify ($plid, $vid, $action, array $data)
	{
		$village = null;
		if ($vid > 0) {
			$village = Dolumar_Players_Village::getVillage ($vid);
			if (!$village || !$village->isFound () || !$village->isActive ()) {
				$village = null;
			}
		}
		if ($village === null) {
			$player = Neuron_GameServer::getPlayer ($plid);
			if (!$player) {
				return;
			}
			foreach ($player->getVillages (false) as $candidate) {
				$village = $candidate;
				break;
			}
		}
		if ($village === null) {
			return;
		}

		Dolumar_Players_Logs::__getInstance ()->addLog ($village, $action, $data, true);
	}

	/**
	 * @return string
	 */
	private function tribeName ()
	{
		return isset ($this->s->tribe['tribe_name']) ? (string) $this->s->tribe['tribe_name'] : 'Goblins';
	}

	/**
	 * @param string $message
	 * @param int|null $plid
	 */
	private function log ($message, $plid = null)
	{
		$this->repository->log ($this->s->vid, $plid, $this->s->now, $message);
		if ($this->logger) {
			call_user_func ($this->logger, '[' . $this->s->vid . '] ' . $message);
		}
	}
}
