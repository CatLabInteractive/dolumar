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

/*
	Runs the migrations in dump/migrations/ that are not yet in the `migrations` table, in file name order,
	and records each one. Dokku runs this as the predeploy script (app.json) in the new image before it takes
	traffic, so a failing statement exits 1 and aborts the deploy while the old release keeps serving.

	An empty database (no n_players table) is left alone: tools/setup.php installs dump/dump.sql, which already
	contains every migration, and records them all as applied.
*/

if (PHP_SAPI !== 'cli') {
	http_response_code (404);
	exit;
}

require_once __DIR__ . '/../bootstrap/bootstrap.php';

$path = realpath (__DIR__ . '/../dump/migrations');
if ($path === false) {
	fwrite (STDERR, "dump/migrations does not exist\n");
	exit (1);
}

try {
	$db = Neuron_DB_Database::getInstance ();

	if (count ($db->query ("SHOW TABLES LIKE 'n_players'")) === 0) {
		echo "Empty database: nothing to migrate, tools/setup.php installs dump/dump.sql.\n";
		exit (0);
	}

	Dolumar_Db_Migrations::ensureTable ($db);

	$applied = Dolumar_Db_Migrations::applied ($db);
	$pending = Dolumar_Db_MigrationPlan::pending (scandir ($path), $applied);

	if (count ($pending) === 0) {
		echo "Database is up to date (" . count ($applied) . " migrations applied).\n";
		exit (0);
	}

	foreach ($pending as $fileName) {
		echo '- Running ' . $fileName . "\n";

		$content = file_get_contents ($path . '/' . $fileName);
		if ($content === false) {
			throw new RuntimeException ('Could not read ' . $fileName);
		}

		foreach (Dolumar_Db_SqlScript::splitStatements ($content) as $statement) {
			$db->query ($statement);
		}

		Dolumar_Db_Migrations::record ($db, $fileName);
	}
} catch (Throwable $e) {
	// MySQL commits every DDL statement at once: a migration that failed halfway must be finished or reverted by
	// hand before the next deploy, because its file is run again from the top.
	fwrite (STDERR, 'Migration failed: ' . $e->getMessage () . "\n");
	exit (1);
}

echo "All done!\n";
