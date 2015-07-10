<?php
$server = trim (@file_get_contents ('/etc/hostname'));

define ('FREE_PREMIUM', true);

define ('ALLOW_VACATION_MODE', false);

switch ($server)
{
	case 'daedelserv':
	case 'daedeltop':
	case 'daedel-home':
	case 'daedelhom':
	case 'daedelhome':
	case 'home.catlab.be':
	case 'thijs-i7-linux':

		define ('IS_TESTSERVER', true);
	
		if (!isset ($_SERVER['SERVER_NAME']))
		{
			$_SERVER['SERVER_NAME'] = 'daedeloth.dyndns.org';
		}
	
		define ('DB_USERNAME', 'myuser');
		define ('DB_PASSWORD', 'myuser');
		define ('DB_SERVER', 'localhost');
		define ('DB_DATABASE', 'dolumar');
	
		define ('ABSOLUTE_URL', 'http://'.$_SERVER['SERVER_NAME'] . '/');
		//define ('STATIC_ABSOLUTE_URL', 'http://daedeloth.no-ip.org.nyud.net/dolumar/');
	
		define ('CACHE_DIR', '/home/daedeloth/cache/');
		define ('CACHE_URL', 'cache.php?d=');
		
		define ('SPEED_FACTOR', 100);
		
		define ('SERVERLIST_VISIBLE', false);
		
		define ('OUTPUT_DEBUG_DATA', true);
		
		/*
			Memcache settings
		*/
		define ('MEMCACHE_IP', '127.0.0.1');
		define ('MEMCACHE_PORT', '11211');
		
		define ('USE_PROFILE', true);
		
		define ('PREMIUM_URL', 'http://daedeloth.dyndns.org/bbgs/credits/');
		define ('TRACKER_URL', 'http://daedeloth.dyndns.org/payment/tracker/');

		define ('MAX_MEMORY_USAGE', 10971520);
		
		//define ('LAUNCH_DATE', time () + 10);
		
		define ('GOOGLE_ANALYTICS', 'UA-459768-16');
		
		define ('ISLANDS', false);
		define ('ISLAND_SIZE', 500);

		define ('PREMIUM_SPEEDUP_BUILDINGS', true);
		define ('PREMIUM_SPEEDUP_TRAINING', true);
		define ('PREMIUM_SPEEDUP_SCOUTING', true);

		define ('OPENID_SKIP_LOGIN', false);
		define ('OPENID_SKIP_NICKNAME', false);

		define ('UNDERWORLD_MAX_MOVEPOINTS', 60);
		
	break;
}

//define ('NOLOGIN_REDIRECT', 'http://www.dolumar.fr/');
?>
