<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

// Autoload.
require_once 'bootstrap/bootstrap.php';
require_once 'cronjobs.php';

$game = new Dolumar_Game ();

$server = Neuron_GameServer::bootstrap();
$server->setGame ($game);

$server->dispatch ();