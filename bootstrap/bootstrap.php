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

error_reporting(E_ALL);
ini_set("display_errors", 1);

$loader = require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

define ('BASE_PATH', dirname(dirname(__FILE__)).'/');

ini_set('memory_limit', '512M');

if (getenv("ABSOLUTE_URL")) {
    define("ABSOLUTE_URL", getenv("ABSOLUTE_URL"));
}

if (!defined ('ABSOLUTE_URL')) {

    $protocol = getenv("PROTOCOL");
    if (!$protocol) {
        $protocol = 'http';
    }

	if (!isset ($_SERVER['SERVER_NAME'])) {
		define ('ABSOLUTE_URL', 'http://www.dolumar.com/');
	}

	else {
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https") {
			$protocol = 'https';
		} elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
			$protocol = 'https';
		}

		define ('ABSOLUTE_URL', $protocol . '://' . $_SERVER['SERVER_NAME'] . '/');
	}
}

if (file_exists (BASE_PATH . 'bootstrap/serverconfig.php')) {
	include BASE_PATH . 'bootstrap/serverconfig.php';
}
else {
	include BASE_PATH . 'bootstrap/serverconfig-default.php';
}

if (defined ('AIRBRAKE_TOKEN') && AIRBRAKE_TOKEN) {

    $options = array(
        'projectId' => AIRBRAKE_TOKEN,
        'projectKey' => AIRBRAKE_TOKEN
    );

    if (defined('AIRBRAKE_HOST')) {
        $options['host'] = AIRBRAKE_HOST;
    }

    // Create new Notifier instance.
    $notifier = new Airbrake\Notifier($options);

    // Set global notifier instance.
    Airbrake\Instance::set($notifier);

    // Register error and exception handlers.
    $handler = new Airbrake\ErrorHandler($notifier);
    $handler->register();
}

if (!defined ('SPEED_FACTOR'))
	define ('SPEED_FACTOR', 1);

if (!defined ('STATIC_ABSOLUTE_URL'))
	define ('STATIC_ABSOLUTE_URL', ABSOLUTE_URL);

if (!defined ('GAME_NAME'))
	define ('GAME_NAME', 'Dolumar');

// API
if (!defined ('API_FULL_URL'))
	define ('API_FULL_URL', ABSOLUTE_URL.'api/');

if (!defined ('API_OPENID_URL'))
	define ('API_OPENID_URL', ABSOLUTE_URL.'openid/');

if (!defined ('DEBUG_LOGS'))
	define ('DEBUG_LOGS', false);

if (!defined ('BASE_URL'))
	define ('BASE_URL', ABSOLUTE_URL);

if (!defined ('ONLINE_TIMEOUT'))
	define ('ONLINE_TIMEOUT', 90);

if (!defined ('DATE'))
	define ('DATE', 'd.m.Y');

if (!defined ('DATETIME'))
	define ('DATETIME', 'd.m.Y H:i');

// URLS
if (!defined ('STATIC_URL'))
	define ('STATIC_URL', BASE_URL . 'static/');

if (!defined ('STATIC_PATH'))
	define ('STATIC_PATH', BASE_PATH.'static/');

if (!defined ('MAPS_PATH'))
    define ('MAPS_PATH', BASE_PATH.'resources/maps/');

if (!defined ('IMAGE_URL'))
	define ('IMAGE_URL', STATIC_URL.'images/');

if (!defined ('IMAGE_PATH'))
	define ('IMAGE_PATH', BASE_PATH.'public/static/images/');

if (!defined ('SMILEY_DIR'))
	define ('SMILEY_DIR', IMAGE_URL.'smileys/blue/');

if (!defined ('SMILEY_PATH'))
	define ('SMILEY_PATH', IMAGE_PATH.'smileys/blue/');

if (!defined ('UNITIMAGE_URL'))
	define ('UNITIMAGE_URL', IMAGE_URL . 'units/');

if (!defined ('UNITIMAGE_PATH'))
	define ('UNITIMAGE_PATH', IMAGE_PATH . 'units/');

if (!defined ('PUBLIC_PATH'))
	define ('PUBLIC_PATH', BASE_PATH.'public/');

if (!defined ('PUBLIC_URL'))
	define ('PUBLIC_URL', BASE_URL.'public/');

// Paths
if (!defined ('TEMPLATE_DIR'))
	define ('TEMPLATE_DIR', BASE_PATH.'resources/templates/default/');

if (!defined ('LANGUAGE_DIR'))
	define ('LANGUAGE_DIR', BASE_PATH.'resources/language/');

if (!defined ('STATS_DIR'))
	define ('STATS_DIR', BASE_PATH.'resources/stats/');

if (!defined ('GAME_SPEED_RESOURCES'))
	define ('GAME_SPEED_RESOURCES', SPEED_FACTOR);

if (!defined ('GAME_SPEED_TRAINING'))
    define ('GAME_SPEED_TRAINING', SPEED_FACTOR * 2);

if (!defined ('GAME_SPEED_BUILDINGS'))
	define ('GAME_SPEED_BUILDINGS', SPEED_FACTOR);

if (!defined ('GAME_SPEED_MOVEMENT'))
	define ('GAME_SPEED_MOVEMENT', 1);

if (!defined ('GAME_SPEED_EFFECTS'))
	define ('GAME_SPEED_EFFECTS', SPEED_FACTOR);

// Game settings
if (!defined ('MAXMAPSTRAAL'))
	define ('MAXMAPSTRAAL', 15000);

if (!defined ('RANDMAPFACTOR'))
	define ('RANDMAPFACTOR', 'Funky Dolumar Game 2008');

if (!defined ('MAXBUILDINGRADIUS'))
	define ('MAXBUILDINGRADIUS', 25);			// Maximum distance between a building and it's towncenter

if (!defined ('MAXCLANDISTANCE'))
	define ('MAXCLANDISTANCE', MAXBUILDINGRADIUS * 2 * 5); 	// Maximum distance between 2 villages of the same clan.

if (!defined ('COOKIE_PREFIX'))
	define ('COOKIE_PREFIX', 'PJX');

if (!defined ('COOKIE_LIFE'))
	define ('COOKIE_LIFE', 60 * 60 * 24 * 31 * 12);

// Outside  URLS
if (!defined ('WIKI_GUIDE_URL'))
	define ('WIKI_GUIDE_URL', false);

if (!defined ('WIKI_EDIT_URL')) {
    if (defined('WIKI_EDIT_URL')) {
        define('WIKI_EDIT_URL', WIKI_GUIDE_URL . 'index.php?action=edit&title=');
    } else {
        define('WIKI_EDIT_URL', false);
    }
}


if (!defined ('WIKI_PREFIX'))
	define ('WIKI_PREFIX', false);

if (!defined ('FORUM_URL'))
	define ('FORUM_URL', 'http://forum.dolumar.com/');

if (!defined ('CONTACT_URL'))
	define ('CONTACT_URL', false);

if (!defined ('PREMIUM_URL'))
	define ('PREMIUM_URL', false);

if (!defined ('TRACKER_URL'))
	define ('TRACKER_URL', false);

if (!defined ('PREMIUM_API_KEY'))
	define ('PREMIUM_API_KEY', false);

if (!defined ('SERVERLIST_URL'))
	define ('SERVERLIST_URL', false);

if (!defined ('GAMENEWS_RSS_URL'))
	define ('GAMENEWS_RSS_URL', false);

if (!defined ('COOKIE_LIFETIME'))
	define ('COOKIE_LIFETIME', 60*60*24*365*2);

if (!defined ('PREMIUM_COST_CREDITS'))
	define ('PREMIUM_COST_CREDITS', 100);

define ('PREMIUM_GAME_TOKEN', 'Dolumar');

/*
* More premium features
*/
if (!defined ('PREMIUM_SPEEDUP_BUILDINGS'))
	define ('PREMIUM_SPEEDUP_BUILDINGS', true);

if (!defined ('PREMIUM_SPEEDUP_BUILDINGS_PRICE'))
	define ('PREMIUM_SPEEDUP_BUILDINGS_PRICE', 20);

if (!defined ('PREMIUM_SPEEDUP_BUILDINGS_UNIT'))
	define ('PREMIUM_SPEEDUP_BUILDINGS_UNIT', 1800);

if (!defined ('PREMIUM_SPEEDUP_TRAINING'))
	define ('PREMIUM_SPEEDUP_TRAINING', true);

if (!defined ('PREMIUM_SPEEDUP_TRAINING_PRICE'))
	define ('PREMIUM_SPEEDUP_TRAINING_PRICE', 20);

if (!defined ('PREMIUM_SPEEDUP_TRAINING_UNIT'))
	define ('PREMIUM_SPEEDUP_TRAINING_UNIT', 1800);

if (!defined ('PREMIUM_SPEEDUP_SCOUTING'))
	define ('PREMIUM_SPEEDUP_SCOUTING', true);

if (!defined ('PREMIUM_SPEEDUP_SCOUTING_PRICE'))
	define ('PREMIUM_SPEEDUP_SCOUTING_PRICE', 100);

if (!defined ('PREMIUM_SPEEDUP_SCOUTING_UNIT'))
	define ('PREMIUM_SPEEDUP_SCOUTING_UNIT', 1800);

// Default: 6 months
if (!defined ('GAME_RUNNING_DURATION'))
	define ('GAME_RUNNING_DURATION', 60 * 60 * 24 * 30 * 6);

// And allowing clan portals
if (!defined ('ALLOW_CLANPORTALS'))
	define ('ALLOW_CLANPORTALS', true);

if (!defined ('OPENID_SKIP_LOGIN'))
	define ('OPENID_SKIP_LOGIN', true);

if (!defined ('OPENID_SKIP_NICKNAME'))
	define ('OPENID_SKIP_NICKNAME', true);

$db = Neuron_Core_Database::__getInstance ();
$db->customQuery("set global sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';");
$db->customQuery("set global sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';");