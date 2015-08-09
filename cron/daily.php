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

require_once __DIR__ . '/../bootstrap/bootstrap.php';

define ('DISABLE_STATIC_FACTORY', true);

set_time_limit (600);

$game = new Dolumar_Game ();
$server = Neuron_GameServer::bootstrap();
$server->setGame ($game);

if (!defined ('SERVERLIST_VISIBLE')) {
	define ('SERVERLIST_VISIBLE', false);
}

function postRequest($url, $referer, $_data) {
 
	// convert variables array to string:
	$data = array();    
	while(list($n,$v) = each($_data))
	{
		$data[] = "$n=$v";
	}    
	$data = implode('&', $data);
	// format --> test1=a&test2=b etc.

	// parse the given URL
	$url = parse_url($url);
	if ($url['scheme'] != 'http') 
	{ 
		die('Only HTTP request are supported !');
	}

	// extract host and path:
	$host = $url['host'];
	$path = $url['path'];

	// open a socket connection on port 80
	$fp = fsockopen($host, 80);

	if (!$fp)
	{
		die ("Could not open connection with " . $host);
	}

	// send the request headers:
	fputs($fp, "POST $path HTTP/1.1\r\n");
	fputs($fp, "Host: $host\r\n");
	fputs($fp, "Referer: $referer\r\n");
	fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
	fputs($fp, "Content-length: ". strlen($data) ."\r\n");
	fputs($fp, "Connection: close\r\n\r\n");
	fputs($fp, $data);

	$result = ''; 
	while(!feof($fp)) 
	{
		// receive the results of the request
		$result .= fgets($fp, 128);
	}

	// close the socket connection:
	fclose($fp);

	// split the result header from the content
	$result = explode("\r\n\r\n", $result, 2);

	$header = isset($result[0]) ? $result[0] : '';
	$content = isset($result[1]) ? $result[1] : '';
	
	//echo $content;

	// return as array:
	//return array ($header, $content);
	
	$data = json_decode ($content, true);
	return is_array ($data) ? $data : false;
}


$server = Neuron_GameServer::getServer ();

// If the server is not installed, install the server.
// The master server will assign an ID
if (!$server->isInstalled ())
{
	if (defined ('DISABLE_RESTART'))
	{
		echo 'This server is scheduled for shut down.';
		exit;
	}

	if (SERVERLIST_URL) {
		echo "Contacting " . SERVERLIST_URL . " to initiate server.\n";

		// Install the server
		$request = postRequest
		(
			SERVERLIST_URL,
			ABSOLUTE_URL,
			array
			(
				'action' => 'initialize',
				'server' => ABSOLUTE_URL,
				'visible' => SERVERLIST_VISIBLE ? 1 : 0
			)
		);

		if ($request && isset ($request['id']) && isset ($request['name'])) {
			$server->setServerName ($request['id'], $request['name']);
		}
	}
	else {
		$server->setServerName (1, 'Dolumar');
	}
}

else if (SERVERLIST_URL)
{

	echo "Contacting ".SERVERLIST_URL." to update serverlist.\n";
	
	echo "Generating request... ";
	$out = array
	(
		'action' => 'update',
		'server' => ABSOLUTE_URL,
		'id' => $server->getServerId (),
		'players' => $server->countTotalPlayers (),
		'last24' => $server->countOnlineUsers (60*60*24),
		'visible' => SERVERLIST_VISIBLE ? 1 : 0,
		'version' => APP_VERSION,
		'bigpoint' => defined ('BIGPOINT_URL') ? BIGPOINT_URL : null
	);
	echo "done!\n";
	
	echo "Sending request...";

	// Contact thze master server for an update
	$request = postRequest
	(
		SERVERLIST_URL,
		ABSOLUTE_URL,
		$out
	);
	
	echo "done!\n";
	
	if ($request && isset ($request['name']))
	{
		$server->updateServerName ($request['name']);
	}
}

// Update the daily
$server->setLastDaily ();
$server->updateStatus ();

if (!isset ($_SERVER['REMOTE_ADDR']))
{
	// Clean thze server
	$server->cleanServer ();

	// Run the preperation script
	/*
	$map = new Dolumar_Map_Preparer ();
	$map->prepare ();
	*/
}
