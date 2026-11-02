<?php
// Loads the full game against the docker DB (production snapshot).
require_once __DIR__ . '/../bootstrap/bootstrap.php';

$game = new Dolumar_Game ();
$server = Neuron_GameServer::bootstrap ();
$server->setGame ($game);
