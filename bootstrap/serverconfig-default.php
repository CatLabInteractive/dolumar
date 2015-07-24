<?php

define ('DB_USERNAME', 'myuser');
define ('DB_PASSWORD', 'myuser');
define ('DB_SERVER', 'localhost');
define ('DB_DATABASE', 'dolumar');

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