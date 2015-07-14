<?php

// Autoload.
require_once 'bootstrap/bootstrap.php';

$game = new Dolumar_Game ();

$server = Neuron_GameServer::bootstrap();

$scripts = file_get_contents ('vendor/catlabinteractive/dolumar-engine/dumps/gameserver.sql');
$scripts .= file_get_contents ('dump/dump.sql');

$db = Neuron_Core_Database::__getInstance();

// Check.
echo '<pre>';
echo 'Checking setup.' . "\n";

try {
	$db->select('n_players', array ('*'));
}
catch (Exception $e)
{
	echo 'Installing database' . "\n";
	$db->multiQuery ($scripts);
}
