<?php

require_once (dirname (dirname (__FILE__)) . '/bootstrap.php');

define ('DOLUMAR_BASE_PATH', dirname (dirname (__FILE__)) . '/dolumar/php');
set_include_path (DOLUMAR_BASE_PATH);

require_once (dirname (dirname (__FILE__)) . '/gameserver/php/connect.php');

define ('DISABLE_STATIC_FACTORY', true);

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
