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
 * Reads and writes npc_villages, npc_standings and npc_log.
 */
class Dolumar_Npc_StandingRepository
{
	/** @var Dolumar_Npc_StandingRepository */
	private static $instance;

	/**
	 * @return Dolumar_Npc_StandingRepository
	 */
	public static function getInstance ()
	{
		if (!isset (self::$instance)) {
			self::$instance = new self ();
		}
		return self::$instance;
	}

	/**
	 * @return Neuron_DB_Database
	 */
	private function db ()
	{
		return Neuron_DB_Database::getInstance ();
	}

	/**
	 * @param int $vid
	 * @return array|null the npc_villages row
	 */
	public function tribe ($vid)
	{
		$rows = $this->db ()->query ("SELECT * FROM npc_villages WHERE vid = " . (int) $vid);
		foreach ($rows as $row) {
			return $this->castTribe ($row);
		}
		return null;
	}

	/**
	 * The tribe row, created with a fresh name and multipliers when the village has none yet.
	 * @param int $vid
	 * @param int $now
	 * @return array
	 */
	public function ensureTribe ($vid, $now)
	{
		$row = $this->tribe ($vid);
		if ($row !== null) {
			return $row;
		}

		$db = $this->db ();
		$multipliers = Dolumar_Npc_Tribe::multipliers ();
		$name = Dolumar_Npc_Tribe::generateName ();

		$db->query ("
			INSERT IGNORE INTO npc_villages
				(vid, tribe_name, mul_threshold, mul_decay, mul_hoard, created)
			VALUES
				(" . (int) $vid . ", '" . $db->escape ($name) . "', " . (float) $multipliers['mul_threshold'] . ",
				 " . (float) $multipliers['mul_decay'] . ", " . (float) $multipliers['mul_hoard'] . ", " . (int) $now . ")
		");

		return $this->tribe ($vid);
	}

	/**
	 * @param array $row an npc_villages row (vid required)
	 */
	public function saveTribe (array $row)
	{
		$db = $this->db ();
		$sets = [];
		foreach (['tribe_name', 'request_res'] as $key) {
			if (array_key_exists ($key, $row)) {
				$sets[] = $key . " = '" . $db->escape ((string) $row[$key]) . "'";
			}
		}
		foreach (['mul_threshold', 'mul_decay', 'mul_hoard'] as $key) {
			if (array_key_exists ($key, $row)) {
				$sets[] = $key . ' = ' . (float) $row[$key];
			}
		}
		foreach (['last_report_id', 'last_transfer_id', 'request_amount', 'request_created', 'created', 'last_run'] as $key) {
			if (array_key_exists ($key, $row)) {
				$sets[] = $key . ' = ' . (int) $row[$key];
			}
		}
		if (count ($sets) === 0) {
			return;
		}
		$db->query ("UPDATE npc_villages SET " . implode (', ', $sets) . " WHERE vid = " . (int) $row['vid']);
	}

	/**
	 * @param int $vid
	 * @return array plid => standing row
	 */
	public function standings ($vid)
	{
		$out = [];
		$rows = $this->db ()->query ("SELECT * FROM npc_standings WHERE vid = " . (int) $vid);
		foreach ($rows as $row) {
			$out[(int) $row['plid']] = Dolumar_Npc_Standing::fromDb ($row);
		}
		return $out;
	}

	/**
	 * @param int $vid
	 * @param int $plid
	 * @return array|null
	 */
	public function standing ($vid, $plid)
	{
		$rows = $this->db ()->query ("SELECT * FROM npc_standings WHERE vid = " . (int) $vid . " AND plid = " . (int) $plid);
		foreach ($rows as $row) {
			return Dolumar_Npc_Standing::fromDb ($row);
		}
		return null;
	}

	/**
	 * Insert or update one standing row.
	 * @param array $row
	 */
	public function save (array $row)
	{
		$row = Dolumar_Npc_Standing::fromDb ($row);

		$columns = [];
		$values = [];
		$updates = [];
		foreach ($row as $key => $value) {
			$columns[] = '`' . $key . '`';
			$values[] = is_float ($value) ? (string) $value : (string) (int) $value;
			if ($key !== 'vid' && $key !== 'plid') {
				$updates[] = '`' . $key . '` = VALUES(`' . $key . '`)';
			}
		}

		$this->db ()->query ("
			INSERT INTO npc_standings (" . implode (', ', $columns) . ")
			VALUES (" . implode (', ', $values) . ")
			ON DUPLICATE KEY UPDATE " . implode (', ', $updates) . "
		");
	}

	/**
	 * @param int $vid
	 * @param int $now
	 * @param array $rows plid => row
	 */
	public function saveAll ($vid, array $rows)
	{
		foreach ($rows as $plid => $row) {
			$row['vid'] = $vid;
			$row['plid'] = $plid;
			$this->save ($row);
		}
	}

	/**
	 * @param int $vid
	 * @param int|null $plid
	 * @param int $now
	 * @param string $message
	 */
	public function log ($vid, $plid, $now, $message)
	{
		$db = $this->db ();
		$db->query ("
			INSERT INTO npc_log (vid, plid, date, message)
			VALUES (" . (int) $vid . ", " . ($plid === null ? 'NULL' : (int) $plid) . ", " . (int) $now . ",
				'" . $db->escape (mb_substr ($message, 0, 255)) . "')
		");
	}

	/**
	 * Tribes for the admin page, most recently run first, with how many standing rows each has.
	 * @param int $offset
	 * @param int $limit
	 * @return array npc_villages rows plus `standings`
	 */
	public function tribesPage ($offset, $limit)
	{
		$out = [];
		$rows = $this->db ()->query ("
			SELECT t.*, (SELECT COUNT(*) FROM npc_standings s WHERE s.vid = t.vid) AS standings
			FROM npc_villages t
			ORDER BY t.last_run DESC, t.vid ASC
			LIMIT " . max (0, (int) $offset) . ", " . max (1, (int) $limit)
		);
		foreach ($rows as $row) {
			$row = $this->castTribe ($row);
			$row['standings'] = (int) $row['standings'];
			$out[] = $row;
		}
		return $out;
	}

	/**
	 * @return int
	 */
	public function countTribes ()
	{
		$rows = $this->db ()->query ("SELECT COUNT(*) AS c FROM npc_villages");
		foreach ($rows as $row) {
			return (int) $row['c'];
		}
		return 0;
	}

	/**
	 * Standing rows for the admin page, most recently changed first.
	 * @param int|null $vid only this tribe
	 * @param int|null $plid only this player
	 * @param int $limit
	 * @return array standing rows
	 */
	public function standingsFiltered ($vid, $plid, $limit)
	{
		$out = [];
		$rows = $this->db ()->query ("
			SELECT * FROM npc_standings
			WHERE " . $this->filter ($vid, $plid) . "
			ORDER BY changed_at DESC
			LIMIT " . max (1, (int) $limit)
		);
		foreach ($rows as $row) {
			$out[] = Dolumar_Npc_Standing::fromDb ($row);
		}
		return $out;
	}

	/**
	 * Decision log entries for the admin page, newest first.
	 * @param int|null $vid only this tribe
	 * @param int|null $plid only entries about this player
	 * @param int $offset
	 * @param int $limit
	 * @return array rows with id, vid, plid (int or null), date, message
	 */
	public function logPage ($vid, $plid, $offset, $limit)
	{
		$out = [];
		$rows = $this->db ()->query ("
			SELECT * FROM npc_log
			WHERE " . $this->filter ($vid, $plid) . "
			ORDER BY id DESC
			LIMIT " . max (0, (int) $offset) . ", " . max (1, (int) $limit)
		);
		foreach ($rows as $row) {
			$out[] = [
				'id' => (int) $row['id'],
				'vid' => (int) $row['vid'],
				'plid' => $row['plid'] === null ? null : (int) $row['plid'],
				'date' => (int) $row['date'],
				'message' => (string) $row['message'],
			];
		}
		return $out;
	}

	/**
	 * @param int|null $vid
	 * @param int|null $plid
	 * @return string a WHERE clause on vid and plid
	 */
	private function filter ($vid, $plid)
	{
		$where = ['1 = 1'];
		if ($vid !== null) {
			$where[] = 'vid = ' . (int) $vid;
		}
		if ($plid !== null) {
			$where[] = 'plid = ' . (int) $plid;
		}
		return implode (' AND ', $where);
	}

	/**
	 * @param array $row
	 * @return array
	 */
	private function castTribe (array $row)
	{
		foreach (['vid', 'last_report_id', 'last_transfer_id', 'request_amount', 'request_created', 'created', 'last_run'] as $key) {
			if (isset ($row[$key])) {
				$row[$key] = (int) $row[$key];
			}
		}
		foreach (['mul_threshold', 'mul_decay', 'mul_hoard'] as $key) {
			if (isset ($row[$key])) {
				$row[$key] = (float) $row[$key];
			}
		}
		return $row;
	}
}
