<?php

// Autoload.
$loader = require_once 'vendor/autoload.php';
require_once 'bootstrap/bootstrap.php';

$game = new Dolumar_Game ();

$server = Neuron_GameServer::bootstrap();
$server->setGame ($game);

$server->dispatch ();