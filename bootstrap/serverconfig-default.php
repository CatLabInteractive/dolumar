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
if (!empty($url["port"])) {
	define ('DB_PORT', $url["port"]);
}
define ('DB_DATABASE', substr($url["path"], 1));
defEnvOrDefault('DB_CHARSET', 'utf8mb4');

/*
 * Cache
 */
defEnvOrDefault('CACHE_DIR', '/tmp/dolumar');

/**
 * Memcahce
 */
// dokku-memcached sets MEMCACHED_URL (memcached://host:port), MemCachier sets MEMCACHIER_*.
defEnvOrDefault('MEMCACHE_SERVERS', getenv('MEMCACHED_URL') ?: getenv('MEMCACHIER_SERVERS'));
defEnvOrDefault('MEMCACHE_USERNAME', getenv('MEMCACHIER_USERNAME'));
defEnvOrDefault('MEMCACHE_PASSWORD', getenv('MEMCACHIER_PASSWORD'));

define ('USE_PROFILE', false);

defEnvOrDefault('EMAIL_FROM');
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
// The credits service is dead: CREDITS_* are only defined when configured,
// and all call sites skip when they are not.
if (getenv('CREDITS_PRIVATE_KEY')) {
    // is it a file?
    $privateKey = getenv('CREDITS_PRIVATE_KEY');
    if (file_exists($privateKey)) {
        define('CREDITS_PRIVATE_KEY', file_get_contents($privateKey));
    } else {
        define('CREDITS_PRIVATE_KEY', $privateKey);
    }
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

defEnvOrDefault('AIRBRAKE_TOKEN', getenv('AIRBRAKE_API_KEY'));
defEnvOrDefault('AIRBRAKE_HOST');

defEnvOrDefault('NOLOGIN_REDIRECT');
defEnvOrDefault('WIKI_GUIDE_URL');

defEnvOrDefault('OPENID_CONNECT_AUTHORIZE_URL');
defEnvOrDefault('OPENID_CONNECT_TOKEN_URL');
defEnvOrDefault('OPENID_CONNECT_PROFILE_URL');
defEnvOrDefault('OPENID_CONNECT_CLIENT_ID');
defEnvOrDefault('OPENID_CONNECT_SECRET');
defEnvOrDefault('OPENID_CONNECT_PROFILE_URL_PREFIX');
