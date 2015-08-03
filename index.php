<?php

// Autoload.
require_once 'bootstrap/bootstrap.php';
require_once 'cronjobs.php';

$game = new Dolumar_Game ();

$server = Neuron_GameServer::bootstrap();
$server->setGame ($game);

$server->dispatch ();