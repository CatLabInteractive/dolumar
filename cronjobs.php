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

$lock = Neuron_Core_Lock::getInstance();

if (!defined ('CRONJOB_OUTPUT')) {
	define ('CRONJOB_OUTPUT', true);
}

if (CRONJOB_OUTPUT) {
	header ('Content-type: text/text');
}

function runCronjobFile($file) {

	if (!CRONJOB_OUTPUT) {
		ob_start();
		include $file;
		ob_end_clean();
	}

	else {
		echo 'Running ' . $file . "\n";
		echo '----------------------' . "\n";
		include $file;
		echo "\n\n";
	}
}

if ($lock->setLock('cronjobs', 60, 60)) {
	runCronjobFile('cron/constantly.php');
} elseif (CRONJOB_OUTPUT) {
	echo 'Not running cron/constantly.php: too soon.' . "\n";
}

if ($lock->setLock('cronjobs', 60 * 60 * 24, 60 * 60 * 24)) {
	runCronjobFile('cron/daily.php');
} elseif (CRONJOB_OUTPUT) {
	echo 'Not running cron/daily.php: too soon.' . "\n";
}