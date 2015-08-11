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
	//$iIdleTime = 60*15;
	$iIdleTime = 0;

	// Collect all queues of all villages
	$db = Neuron_DB_Database::__getInstance ();

	$profiler = Neuron_Profiler_Profiler::getInstance ();

	// Only check once every 15 minutes!
	$queues = $db->query
	("
		SELECT
			*
		FROM
			premium_queue
		WHERE
			pq_lastcheck < FROM_UNIXTIME(".(time()-$iIdleTime).")
		ORDER BY
			pq_lastcheck ASC
	");
	
	$count = 0;
	$total = count ($queues);

	foreach ($queues as $queue)
	{	
		echo "Loaded queue {$queue['pq_id']}\n";
	
		$profiler->start ('Executing queue ' . $queue['pq_id'] . ' (' . $queue['pq_action'] . ')');

		$bRemove = true;

		$profiler->start ('Loading village');
		$village = Dolumar_Players_Village::getFromId ($queue['pq_vid']);
		$profiler->stop ();
	
		if ($village && $village->isActive () && !$village->getOwner ()->inVacationMode ())
		{
			$bRemove = $village->premium->executeQueuedAction ($queue['pq_action'], json_decode ($queue['pq_data'], true));
		}

		if ($bRemove)
		{
			echo "Removing queue {$queue['pq_id']}\n";

			// Remove the queue
			$db->query
			("
				DELETE FROM
					premium_queue
				WHERE
					pq_id = {$queue['pq_id']}
			");	
		}
		else
		{
			echo "Updating queue {$queue['pq_id']}\n";

			// Update the queue
			$db->query
			("
				UPDATE
					premium_queue
				SET
					pq_lastcheck = FROM_UNIXTIME(".time().")
				WHERE
					pq_id = {$queue['pq_id']}
			");	
		}
		
		$memory = memory_get_usage ();
		
		$count ++;
		echo 'Executed queue ' . $count . ' / ' . $total . "\n";
		echo 'Memory usage: '.round($memory / (1024 * 1024)).'kB' . "\n";
		echo "---\n";

		$profiler->start ('Destroying village');
		$village->__destruct ();
		$profiler->stop ();
	
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
	
	//customMail ('daedelson@gmail.com', 'debug cronjob', $profiler);
	$lock->releaseLock ('cron_const', 0);
}
?>
