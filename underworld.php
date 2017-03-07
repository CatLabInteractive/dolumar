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

require_once 'bootstrap/bootstrap.php';

echo 'underworld down';
exit;

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