<?php

/*
 * PHPUnit bootstrap for the game repository.
 *
 * The "unit" suite (tests/Npc, tests/Map, tests/NewMap) covers code that works without a database: pure classes,
 * or code fed by tests/NewMap/FakeDatabase. It loads the game's constants but never connects.
 *
 * The "integration" suite (tests/Integration) runs the full game against the Docker database (production
 * snapshot), so the game is only bootstrapped when a database is configured. Run it with
 * `docker compose exec web vendor/bin/phpunit --testsuite integration`.
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (getenv ('DATABASE_URL')) {
	require_once __DIR__ . '/../bootstrap/bootstrap.php';

	$game = new Dolumar_Game ();
	$server = Neuron_GameServer::bootstrap ();
	$server->setGame ($game);
} else {
	// Unit suite without a database (bootstrap/bootstrap.php would connect): the few constants it reads.
	foreach ([
		'ABSOLUTE_URL' => 'http://localhost/',
		'BASE_URL' => 'http://localhost/',
		'STATIC_URL' => 'http://localhost/static/',
		'IMAGE_URL' => 'http://localhost/static/images/',
		'STATS_DIR' => dirname (__DIR__) . '/resources/stats/',
	] as $name => $value) {
		if (!defined ($name)) {
			define ($name, $value);
		}
	}
}

if (!defined ('NOW')) {
	define ('NOW', 1700000000);
}
