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


/*
	Browser notifications: queue the timed events (finished buildings, battles,
	armies home) and deliver everything in the push queue. Run every minute from
	cron/run.php. Does nothing until VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY are set.
*/

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

require_once __DIR__ . '/../bootstrap/bootstrap.php';

if (!Neuron_GameServer_Push_Config::isEnabled ())
{
	echo "Browser notifications are off (VAPID keys not set).\n";
	return;
}

$server = Neuron_GameServer::bootstrap ();
$server->setGame (new Dolumar_Game ());

$lock = Neuron_Core_Lock::getInstance ();

if (!$lock->setLock ('cron_push', 0, 60 * 10))
{
	echo "Push run still busy, skipping.\n";
	return;
}

try
{
	$queued = Dolumar_Push_Events::scan ();
	echo "Queued {$queued} timed-event notifications.\n";

	$sender = new Neuron_GameServer_Push_Sender ();
	$stats = $sender->flushQueue ();
	echo 'Push queue: ' . json_encode ($stats) . "\n";
}
catch (Throwable $e)
{
	Neuron_ErrorHandler_Handler::getInstance ()->notify ($e);
	echo 'Push run failed: ' . $e->getMessage () . "\n";
}

$lock->releaseLock ('cron_push', 0);
