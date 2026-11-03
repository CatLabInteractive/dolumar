<?php

/**
 * Test double for Neuron_DB_Database: answers each query with the rows
 * registered for the first substring that matches the SQL.
 */
class FakeDatabase extends Neuron_DB_Database
{
	private $answers = [];
	public $queries = [];

	public function __construct(array $answers = [])
	{
		$this->answers = $answers;
	}

	public function multiQuery($sSQL) { return []; }

	public function query($sSQL)
	{
		$this->queries[] = $sSQL;
		foreach ($this->answers as $needle => $rows) {
			if (strpos($sSQL, $needle) !== false) {
				return $rows;
			}
		}
		return [];
	}

	public function escape($txt) { return addslashes((string) $txt); }
	public function fromUnixtime($timestamp) { return "FROM_UNIXTIME($timestamp)"; }
	public function toUnixtime($date) { return "UNIX_TIMESTAMP($date)"; }
}
