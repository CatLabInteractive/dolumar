<?php

/**
 * PHPUnit bootstrap for the game's own tests.
 *
 * These tests cover pure game logic (route calculation, army movements) and
 * need no database, memcached or web server: only Composer's autoloader and
 * the few constants the code under test reads.
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined ('NOW')) {
	define ('NOW', time ());
}
if (!defined ('IMAGE_URL')) {
	define ('IMAGE_URL', 'http://localhost/static/images/');
}
