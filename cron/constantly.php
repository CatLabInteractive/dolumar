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

//define ('DISABLE_STATIC_FACTORY', true);

set_time_limit (600);

$game = new Dolumar_Game ();
$server = Neuron_GameServer::bootstrap();
$server->setGame ($game);

$game = new Dolumar_Game ();
$server = Neuron_GameServer::getInstance ();
$server->setGame ($game);

$lock = Neuron_Core_Lock::getInstance ();

// Unlock after 30 minutes.
$locktime = 60 * 30;

$maxmemory = return_bytes (ini_get ('memory_limit')) * 0.9;
$starttime = time ();

if ($lock->setLock ('cron_const', 0, $locktime))
{
	// Collect all queues of all villages
	$db = Neuron_DB_Database::__getInstance ();

	$profiler = Neuron_Profiler_Profiler::getInstance ();

	// Every village with something in its queue, the one that waited longest
	// first. Players that are online get their queue processed by the
	// statusbar as soon as a counter runs out; this catches everyone else.
	$villageIds = $db->query
	("
		SELECT
			pq_vid,
			MIN(COALESCE(pq_lastcheck, FROM_UNIXTIME(0))) AS lastcheck
		FROM
			premium_queue
		GROUP BY
			pq_vid
		ORDER BY
			lastcheck ASC
	");

	$count = 0;
	$total = count ($villageIds);

	foreach ($villageIds as $row)
	{
		$profiler->start ('Executing queue of village ' . $row['pq_vid']);

		$village = Dolumar_Players_Village::getFromId ($row['pq_vid']);

		if ($village)
		{
			$village->premium->processQueue ();
		}
		else
		{
			echo "Removing queues of missing village {$row['pq_vid']}\n";

			$db->query
			("
				DELETE FROM
					premium_queue
				WHERE
					pq_vid = " . intval ($row['pq_vid']) . "
			");
		}

		$memory = memory_get_usage ();

		$count ++;
		echo 'Executed queues of village ' . $count . ' / ' . $total . "\n";
		echo 'Memory usage: '.round($memory / (1024 * 1024)).'MB' . "\n";
		echo "---\n";

		if ($village)
		{
			$village->__destruct ();
		}

		$profiler->stop ();

		if
		(
			$memory > $maxmemory
			|| $starttime < (time () - $locktime)
		)
		{
			break;
		}
	}

	// Settle pending credit purchases (missed notifies). Only when credits are configured.
	try
	{
		$creditsClient = Neuron_GameServer_Credits::getPureCreditsObject ();
		if ($creditsClient)
		{
			$service = new Neuron_GameServer_Credits_PurchaseService ($creditsClient);
			echo 'Reconciled credit purchases: ' . $service->reconcile () . "\n";
		}
	}
	catch (\Throwable $e)
	{
		echo 'Credit reconcile failed: ' . get_class ($e) . "\n";
	}

	//customMail ('daedelson@gmail.com', 'debug cronjob', $profiler);
	$lock->releaseLock ('cron_const', 0);
}