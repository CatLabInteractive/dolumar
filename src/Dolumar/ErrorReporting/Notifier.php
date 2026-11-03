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

/**
 * phpbrake notifier for our self-hosted, Airbrake-compatible tracker (Errbit).
 *
 * - phpbrake drops a failed delivery silently (wrong key, wrong host, tracker down). We write the reason to the error
 *   log, so `dokku logs` shows why nothing arrives.
 * - A network failure must not throw out of an error or exception handler.
 * - With a custom host, phpbrake still asks Airbrake's cloud (notifier-configs.airbrake.io) whether notifications are
 *   enabled for "project <id>". That id means nothing there, so we skip the lookup.
 */
class Dolumar_ErrorReporting_Notifier extends \Airbrake\Notifier
{
	public function sendNotice ($notice)
	{
		try
		{
			$result = parent::sendNotice ($notice);
		}
		catch (\Throwable $e)
		{
			error_log ('Error tracker: could not send notice: ' . $e->getMessage ());
			$notice['error'] = $e->getMessage ();
			return $notice;
		}

		if (isset ($result['error']))
		{
			$error = is_string ($result['error']) ? $result['error'] : json_encode ($result['error']);
			error_log ('Error tracker: notice was not accepted: ' . substr ($error, 0, 300));
		}

		return $result;
	}

	protected function remoteErrorConfig ()
	{
		if (!empty ($this->opt['host']))
		{
			return array ('host' => $this->opt['host'], 'enabled' => true);
		}

		return parent::remoteErrorConfig ();
	}
}
