<?php

require_once 'bootstrap/bootstrap.php';

$lock = Neuron_Core_Lock::getInstance();

if ($lock->setLock('cronjobs', 60, 60)) {
	exec ('php cron/constantly.php');
}

if ($lock->setLock('cronjobs', 60 * 60 * 24, 60 * 60 * 24)) {
	exec ('php cron/daily.php');
}