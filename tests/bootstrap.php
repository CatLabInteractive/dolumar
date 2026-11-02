<?php

/*
 * PHPUnit bootstrap for the game repository.
 *
 * The suite only covers code that works without a database: the NPC decision classes under
 * src/Dolumar/Npc/. It defines the few constants those classes read.
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined ('NOW')) {
	define ('NOW', 1700000000);
}
