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

if (PHP_SAPI !== 'cli') {
	http_response_code (404);
	exit;
}

require_once __DIR__ . '/../bootstrap/bootstrap.php';

set_time_limit (600);

$game = new Dolumar_Game ();
$server = Neuron_GameServer::bootstrap();
$server->setGame ($game);

if (!defined ('SERVERLIST_VISIBLE')) {
	define ('SERVERLIST_VISIBLE', false);
}

function writeln($message)
{
    echo $message . "\n";
}

// Never let two runs overlap: a run can take a while and every decision must be made once.
$lock = Neuron_Core_Lock::__getInstance ();
if (!$lock->setLock ('npc_ai', 0, 600)) {
    writeln('Another AI run is still going; skipping.');
    exit;
}

try {
    Dolumar_Npc_Schema::ensure ();

    // Make sure every player has an NPC village within reach of each of their villages.
    $players = $game->getServer()->getActivePlayers();

    writeln('Generating AI villages');
    foreach ($players as $player) {

        writeln('Processing player "' . $player->getName() . '"');

        foreach ($player->getVillages(false) as $village) {
            writeln('- Checking village "' . $village->getName() . '"');
            $village->lookForNPCs();
        }
    }

    // Then run every NPC village once.
    $hasRun = [];
    $aiVillages = Dolumar_Players_Village::getAllNpc(false);

    foreach ($aiVillages as $aiVillage) {

        if (isset($hasRun[$aiVillage->getId()])) {
            continue;
        }
        $hasRun[$aiVillage->getId()] = true;

        // do we have a town center?
        $tc = $aiVillage->buildings->getTownCenter();
        if (!$tc) {
            $aiVillage->destroyVillage();
            continue;
        }

        writeln('Found AI village: ' . $aiVillage->getName());

        try {
            $aiVillage->ai->run('writeln');
        } catch (Exception $e) {
            writeln('[' . $aiVillage->getId() . '] failed: ' . $e->getMessage());
        }
    }

    writeln('All done!');
} finally {
    $lock->releaseLock ('npc_ai', 0);
}
