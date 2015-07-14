<?php

$url = getenv("CLEARDB_DATABASE_URL");

if (!$url)
	return array ();

$url = parse_url($url);

define ('DB_USERNAME', $url["user"]);
define ('DB_PASSWORD', $url["pass"]);
define ('DB_SERVER', $url["host"]);
define ('DB_DATABASE', substr($url["path"], 1));

/*
 * Cache
 */
define ('CACHE_DIR', '/tmp/');
define ('CACHE_URL', 'cache.php?d=');

/**
 * Memcahce
 */
define ('MEMCACHE_SERVERS', getenv("MEMCACHIER_SERVERS"));
define ('MEMCACHE_USERNAME', getenv("MEMCACHIER_USERNAME"));
define ('MEMCACHE_PASSWORD', getenv("MEMCACHIER_PASSWORD"));

define ('USE_PROFILE', true);