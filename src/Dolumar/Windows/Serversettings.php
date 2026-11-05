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

class Dolumar_Windows_Serversettings extends Neuron_GameServer_Windows_Window
{
	const ENDGAME_NEVER = 'never';
	const ENDGAME_DAYS = 'days';
	const ENDGAME_COUNTDOWN = 'countdown';
	const ENDGAME_STARTED = 'started';
	const ENDGAME_FINISHED = 'finished';

	public function setSettings ()
	{
		$text = Neuron_Core_Text::getInstance ();

		// Window settings
		$this->setSize ('300px', '310px');
		$this->setTitle ($text->get ('title', 'serversettings', 'serversettings'));

		$this->setAllowOnlyOnce ();

		$this->setCentered ();
	}

	public function getContent ()
	{
		$text = Neuron_Core_Text::getInstance ();
		$server = Neuron_GameServer::getServer ();

		$page = new Neuron_Core_Template ();
		$page->setTextSection ('serversettings', 'serversettings');

		$page->set ('speed', self::formatSpeed (SPEED_FACTOR));
		$page->set ('speeds', array (
			array ($text->get ('resources', 'serversettings', 'serversettings'), self::formatSpeed (GAME_SPEED_RESOURCES)),
			array ($text->get ('buildings', 'serversettings', 'serversettings'), self::formatSpeed (GAME_SPEED_BUILDINGS)),
			array ($text->get ('training', 'serversettings', 'serversettings'), self::formatSpeed (GAME_SPEED_TRAINING)),
			array ($text->get ('movement', 'serversettings', 'serversettings'), self::formatSpeed (GAME_SPEED_MOVEMENT)),
			array ($text->get ('effects', 'serversettings', 'serversettings'), self::formatSpeed (GAME_SPEED_EFFECTS)),
		));

		$endgameDate = $server->getEndgameStartDate ();
		$state = self::getEndgameState (
			Dolumar_Players_Server::isNeverending (),
			$server->getData ('gamestate'),
			$endgameDate,
			time ()
		);

		$page->set ('endgame_state', $state);

		switch ($state)
		{
			case self::ENDGAME_DAYS:
				$days = self::getDaysLeft ($endgameDate, time ());
				$page->set ('endgame_in', Neuron_Core_Tools::putIntoText (
					$text->get ('startsOn', 'serversettings', 'serversettings'),
					array (
						'time' => '<strong>' . Neuron_Core_Tools::putIntoText (
							$text->get ($days == 1 ? 'day' : 'days', 'serversettings', 'serversettings'),
							array ('days' => $days)
						) . '</strong>',
						'date' => date (DATE, $endgameDate)
					)
				));
			break;

			case self::ENDGAME_COUNTDOWN:
				$page->set ('endgame_in', Neuron_Core_Tools::putIntoText (
					$text->get ('startsIn', 'serversettings', 'serversettings'),
					array ('time' => '<strong>' . Neuron_Core_Tools::getCountdown ($endgameDate) . '</strong>')
				));
			break;
		}

		return $page->parse ('dolumar/serversettings.phpt');
	}

	/**
	 * What the window says about the endgame.
	 * @param bool $neverending NEVERENDING world: there is no endgame at all.
	 * @param int|null $gamestate Dolumar_Players_Server::GAMESTATE_* stored on the server.
	 * @param int $endgameDate Timestamp at which the endgame starts.
	 * @param int $now
	 * @return string One of the ENDGAME_* constants.
	 */
	public static function getEndgameState ($neverending, $gamestate, $endgameDate, $now)
	{
		if ($neverending)
		{
			return self::ENDGAME_NEVER;
		}

		if ($gamestate == Dolumar_Players_Server::GAMESTATE_ENDGAME_FINISHED
			|| $gamestate == Dolumar_Players_Server::GAMESTATE_WAITINGFORRESET)
		{
			return self::ENDGAME_FINISHED;
		}

		if ($gamestate == Dolumar_Players_Server::GAMESTATE_ENDGAME_STARTED
			|| $gamestate == Dolumar_Players_Server::GAMESTATE_ENDGAME_RUNNING
			|| $endgameDate <= $now)
		{
			return self::ENDGAME_STARTED;
		}

		// More than a day away: a day count reads better than a 4317-hour clock.
		if ($endgameDate - $now > 60 * 60 * 24)
		{
			return self::ENDGAME_DAYS;
		}

		return self::ENDGAME_COUNTDOWN;
	}

	/**
	 * Whole days left, rounded down, so "1 day" never shows while more than 48 hours remain.
	 * @param int $endgameDate
	 * @param int $now
	 * @return int
	 */
	public static function getDaysLeft ($endgameDate, $now)
	{
		return (int) floor (max (0, $endgameDate - $now) / (60 * 60 * 24));
	}

	/**
	 * x3, x1.5: speed factors from the environment can be fractions.
	 * @param int|float $factor
	 * @return string
	 */
	public static function formatSpeed ($factor)
	{
		$rounded = round ((float) $factor, 2);
		if ($rounded == floor ($rounded))
		{
			return 'x' . (int) $rounded;
		}
		return 'x' . rtrim (rtrim (number_format ($rounded, 2, '.', ''), '0'), '.');
	}
}
