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
 * The NPC village module ($village->ai): a thin orchestrator over the Dolumar_Npc_* classes.
 * One run = snapshot, standing update, planning, execution, save.
 * See docs/superpowers/specs/2026-11-02-npc-standing-ai-design.md.
 */
class Dolumar_Players_Village_Ai
{
	const NPC_PLAYER_DISTANCE = Dolumar_Npc_Config::NPC_PLAYER_DISTANCE;

	/**
	 * @var Dolumar_Players_Village
	 */
	private $objProfile;

	/**
	 * @var callable|null
	 */
	private $logger;

	/**
	 * @param Dolumar_Players_Village $profile
	 */
	public function __construct ($profile)
	{
		$this->objProfile = $profile;
	}

	/**
	 * @return Dolumar_Players_Village
	 */
	private function getVillage ()
	{
		return $this->objProfile;
	}

	/**
	 * @param callable|null $logger
	 * @param int|null $now
	 */
	public function run ($logger = null, $now = null)
	{
		$this->logger = $logger;

		if (!$this->getVillage ()->isNpc ()) {
			return;
		}

		$now = $now === null ? NOW : (int) $now;
		$village = $this->getVillage ();
		$repository = Dolumar_Npc_StandingRepository::getInstance ();

		$s = Dolumar_Npc_SnapshotBuilder::build ($village, $now);
		$this->log ($s->tribe['tribe_name'] . ' reporting: ' . Dolumar_Npc_Tribe::hoardWord ($s->resourceSum (), $s->capacitySum ())
			. ' hoard of ' . $s->resourceSum () . ', ' . $s->runes['total'] . ' runes, ' . $s->availableArmy () . ' units, '
			. count ($s->neighbours) . ' neighbours, ' . count ($s->reports) . ' new reports, ' . count ($s->transfers) . ' new transfers');

		// 1. What happened since the last run.
		$update = Dolumar_Npc_StandingUpdater::apply ($s);
		$rows = $update['rows'];

		// 2. What to do about it.
		$war = new Dolumar_Npc_Planner_War ();
		$warActions = $war->plan ($s, $rows, $update['transitions']);
		$rows = $war->rows ();

		$actions = array_merge (
			Dolumar_Npc_Planner_Economy::plan ($s, $rows),
			Dolumar_Npc_Planner_Army::plan ($s, $rows),
			$warActions
		);
		if (!$update['request_delivered']) {
			$request = Dolumar_Npc_Planner_Economy::request ($s);
			if ($request !== null) {
				$actions[] = $request;
			}
		}

		// 3. Do it.
		$executor = new Dolumar_Npc_Executor ($village, $s, $logger);
		$executor->announce ($update['transitions']);
		foreach ($actions as $action) {
			$this->log ('action: ' . $action);
			$executor->execute ($action);
		}

		// 4. Remember.
		foreach ($executor->rowUpdates () as $plid => $changes) {
			if (!isset ($rows[$plid])) {
				$rows[$plid] = Dolumar_Npc_Standing::newRow ($s->vid, $plid, $now);
			}
			$rows[$plid] = array_merge ($rows[$plid], $changes);
		}
		$repository->saveAll ($s->vid, $rows);

		$tribe = array_merge ($executor->tribeUpdates (), [
			'vid' => $s->vid,
			'last_report_id' => $update['last_report_id'],
			'last_transfer_id' => $update['last_transfer_id'],
			'last_run' => $now,
		]);
		$repository->saveTribe ($tribe);
	}

	/**
	 * @param string $message
	 */
	private function log ($message)
	{
		if ($this->logger) {
			call_user_func ($this->logger, '[' . $this->getVillage ()->getId () . '] ' . $message);
		}
	}

	public function __destruct ()
	{
		unset ($this->objProfile);
	}
}
