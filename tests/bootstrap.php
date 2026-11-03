<?php

/*
 * PHPUnit bootstrap for the game repository.
 *
 * The "unit" suite (tests/Npc) covers code that works without a database: the NPC decision classes. It only
 * needs the autoloader and the few constants those classes read.
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
}

if (!defined ('NOW')) {
	define ('NOW', 1700000000);
}
