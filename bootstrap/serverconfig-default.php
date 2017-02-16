<?php

$url = getenv("CLEARDB_DATABASE_URL");

if (!$url)
	return array ();

$url = parse_url($url);

if (!function_exists('defEnvOrDefault')) {
	function defEnvOrDefault($name, $default = null) {
		$val = getenv($name);
		if ($val) {
			define($name, $val);
		}
		elseif($default) {
			define($name, $default);
		}
		else {
			// Don't define.
		}
	}
}

define ('DB_USERNAME', $url["user"]);
define ('DB_PASSWORD', $url["pass"]);
define ('DB_SERVER', $url["host"]);
define ('DB_DATABASE', substr($url["path"], 1));

/*
 * Cache
 */
define ('CACHE_DIR', '/tmp/dolumar');
define ('CACHE_URL', 'cache.php?d=');

/**
 * Memcahce
 */
defEnvOrDefault('MEMCACHE_SERVERS');
defEnvOrDefault('MEMCACHE_USERNAME');
defEnvOrDefault('MEMCACHE_PASSWORD');

define ('USE_PROFILE', false);

defEnvOrDefault('EMAIL_FROM');
defEnvOrDefault('EMAIL_FROM_NAME');

defEnvOrDefault('EMAIL_SMTP_SERVER');
defEnvOrDefault('EMAIL_SMTP_PORT');
defEnvOrDefault('EMAIL_SMTP_USERNAME');
defEnvOrDefault('EMAIL_SMTP_PASSWORD');
defEnvOrDefault('EMAIL_SMTP_SECURE');

defEnvOrDefault('EMAIL_DEBUG_LEVEL');

/**
 * Credits
 */
defEnvOrDefault ('CREDITS_URL');
defEnvOrDefault ('CREDITS_PRIVATE_KEY');
defEnvOrDefault ('CREDITS_GAME_TOKEN');

/**
 * Master server
 */
defEnvOrDefault ('SERVERLIST_URL');
defEnvOrDefault ('SERVERLIST_PRIVATE_KEY');
defEnvOrDefault ('SERVERLIST_PUBLIC_KEY');
defEnvOrDefault ('SERVERLIST_VISIBLE', true);

defEnvOrDefault('AIRBRAKE_TOKEN');
defEnvOrDefault('AIRBRAKE_HOST');

defEnvOrDefault('NOLOGIN_REDIRECT');
defEnvOrDefault('WIKI_GUIDE_URL', 'http://wiki.dolumar.com/');