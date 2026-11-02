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

// Autoload.
if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

require_once __DIR__ . '/../bootstrap/bootstrap.php';

$scripts = file_get_contents (__DIR__ . '/../dump/dump.sql');
if ($scripts === false) {
	fwrite (STDERR, "Could not read dump/dump.sql\n");
	exit (1);
}

echo 'Checking setup.' . "\n";

// Only install when the database is reachable AND n_players demonstrably does not exist.
// Any error while checking aborts without touching the database.
try {
	$db = Neuron_Core_Database::__getInstance();
	$exists = $db->query ("SHOW TABLES LIKE 'n_players'")->count () > 0;
}
catch (Throwable $e) {
	fwrite (STDERR, 'Could not check database state, not installing: ' . $e->getMessage () . "\n");
	exit (1);
}

if ($exists) {
	echo 'Database already installed, nothing to do.' . "\n";
	exit (0);
}

echo 'Installing database' . "\n";

// Neuron_Core_Database::multiQuery() does not drain the result sets, so only the first
// statement of the dump would run. Use mysqli directly and execute all of them.
// Note: the dump contains DROP TABLE IF EXISTS statements; this is only safe because
// we only get here when n_players does not exist.
try {
	mysqli_report (MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$mysqli = new mysqli (DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE, defined ('DB_PORT') ? (int)DB_PORT : 3306);
	$mysqli->set_charset ('utf8mb4');
	$mysqli->multi_query ($scripts);
	do {
		if ($result = $mysqli->store_result ()) {
			$result->free ();
		}
	} while ($mysqli->more_results () && $mysqli->next_result ());
}
catch (Throwable $e) {
	fwrite (STDERR, 'Install failed: ' . $e->getMessage () . "\n");
	exit (1);
}
echo 'Done!' . "\n";
