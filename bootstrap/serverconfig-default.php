<?php

$url = getenv("DATABASE_URL");
if (!$url) {
    $url = getenv("CLEARDB_DATABASE_URL");
}

if (!$url) {
    echo 'No DB url provided.';
    exit;
}

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
defEnvOrDefault('MEMCACHE_SERVERS', getenv('MEMCACHIER_SERVERS'));
defEnvOrDefault('MEMCACHE_USERNAME', getenv('MEMCACHIER_USERNAME'));
defEnvOrDefault('MEMCACHE_PASSWORD', getenv('MEMCACHIER_PASSWORD'));

define ('USE_PROFILE', false);

defEnvOrDefault('EMAIL_FROM', 'dolumar@' . getenv('MAILGUN_DOMAIN'));
defEnvOrDefault('EMAIL_FROM_NAME');

defEnvOrDefault('EMAIL_SMTP_SERVER', getenv('MAILGUN_SMTP_SERVER'));
defEnvOrDefault('EMAIL_SMTP_PORT', getenv('MAILGUN_SMTP_PORT'));
defEnvOrDefault('EMAIL_SMTP_USERNAME', getenv('MAILGUN_SMTP_LOGIN'));
defEnvOrDefault('EMAIL_SMTP_PASSWORD', getenv('MAILGUN_SMTP_PASSWORD'));
defEnvOrDefault('EMAIL_SMTP_SECURE');

defEnvOrDefault('EMAIL_DEBUG_LEVEL');

/**
 * Credits
 */
defEnvOrDefault ('CREDITS_URL', 'https://credits.catlab.eu/');

if (getenv('CREDITS_PRIVATE_KEY')) {
    // is it a file?
    $privateKey = getenv('CREDITS_PRIVATE_KEY');
    if (file_exists($privateKey)) {
        define('CREDITS_PRIVATE_KEY', file_get_contents($privateKey));
    } else {
        define('CREDITS_PRIVATE_KEY', $privateKey);
    }
} else {
    define('CREDITS_PRIVATE_KEY', null);
}

defEnvOrDefault ('CREDITS_GAME_TOKEN');

/**
 * Master server
 */
defEnvOrDefault ('SERVERLIST_URL');

if (getenv('SERVERLIST_PRIVATE_KEY')) {
    // is it a file?
    $privateKey = getenv('SERVERLIST_PRIVATE_KEY');
    if (file_exists($privateKey)) {
        define('SERVERLIST_PRIVATE_KEY', file_get_contents($privateKey));
    } else {
        define('SERVERLIST_PRIVATE_KEY', $privateKey);
    }
} else {
    define('SERVERLIST_PRIVATE_KEY', null);
}

if (getenv('SERVERLIST_PUBLIC_KEY')) {
    // is it a file?
    $privateKey = getenv('SERVERLIST_PUBLIC_KEY');
    if (file_exists($privateKey)) {
        define('SERVERLIST_PUBLIC_KEY', file_get_contents($privateKey));
    } else {
        define('SERVERLIST_PUBLIC_KEY', $privateKey);
    }
} else {
    define('SERVERLIST_PUBLIC_KEY', null);
}
defEnvOrDefault ('SERVERLIST_VISIBLE', true);

defEnvOrDefault('AIRBRAKE_TOKEN');
defEnvOrDefault('AIRBRAKE_HOST');

defEnvOrDefault('NOLOGIN_REDIRECT');
defEnvOrDefault('WIKI_GUIDE_URL', 'http://wiki.dolumar.com/');