<?php

define ('DOLUMAR_BASE_PATH', dirname (__FILE__) . '/dolumar/php');
set_include_path (DOLUMAR_BASE_PATH);

require_once ('bootstrap.php');
require_once ('gameserver/php/connect.php');

if (isset ($_GET['debug']))
{
	$_SESSION['debug'] = $_GET['debug'];
}

if (isset ($_SESSION['debug']))
{
	define ('DEBUG', $_SESSION['debug']);
}

$mission_id = Neuron_Core_Tools::getInput ('_GET', 'id', 'int');

$mission = Dolumar_Underworld_Mappers_MissionMapper::getFromId ($mission_id);
$game = new Dolumar_Underworld_Game ($mission);

$server = Neuron_GameServer::getInstance ();
$server->setGame ($game);

$server->setDispatchURL (ABSOLUTE_URL . 'underworld.php?id='.$mission_id.'&module=');

$server->dispatch ();