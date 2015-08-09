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

class Dolumar_Players_Server 
	extends Neuron_GameServer_Server
{
	const GAMESTATE_COUNTDOWN = 0;
	const GAMESTATE_PLAYING = 10;
	const GAMESTATE_ENDGAME_STARTED = 20;
	const GAMESTATE_ENDGAME_RUNNING = 30;
	const GAMESTATE_ENDGAME_FINISHED = 40;
	const GAMESTATE_WAITINGFORRESET = 50;

	public function canRegister ()
	{
		return true;

		$state = $this->getData ('gamestate');
		if (!$state)
		{
			return true;
		}

		if ($state == self::GAMESTATE_PLAYING || $state == self::GAMESTATE_ENDGAME_STARTED)
		{
			return true;
		}

		return false;
	}

	public function getEndgameStartDate ()
	{
		$duration = GAME_RUNNING_DURATION;
		$start = $this->getStartDate ();

		return $start + $duration;
	}

	/**
	* Determine if it's time to take a step forwards
	*/
	public function updateStatus ()
	{
		$state = $this->getData ('gamestate');

		if (!$state)
		{
			$this->setData ('gamestate', self::GAMESTATE_PLAYING);
			return;
		}

		switch ($state)
		{
			case self::GAMESTATE_PLAYING:

				// If the server has been running for xxx months, go to next step
				$duration = GAME_RUNNING_DURATION;
				$start = $this->getStartDate ();
				$timepassed = time () - $start;

				if ($timepassed > $duration)
				{
					// Initiate end game
					$this->setData ('gamestate', self::GAMESTATE_ENDGAME_STARTED);

					// And notify all players
					Neuron_GameServer_Player_Guide::addPublicMessage 
					(
						'end_scroll', 
						array (), 
						'guide', 
						'scared'
					);
				}

			break;

			case self::GAMESTATE_ENDGAME_FINISHED:

				$this->setData ('end_date', time ());
				$this->setData ('gamestate', self::GAMESTATE_WAITINGFORRESET);

				$server = Neuron_GameServer::getServer ();

				$out = array ();

				$out['winner'] = '';
				$out['members'] = '';
				$out['ranking'] = '';
				$out['members'] = '';
				$out['villages'] = '';
				$out['clans'] = '';
				$out['players'] = '';

				$winner = $server->getData ('winner');
				if ($winner)
				{
					$clan = Dolumar_Players_Clan::getFromId ($winner);
					if ($clan)
					{
						$out['winner'] = Neuron_Core_Tools::output_varchar ($clan->getName ());

						foreach ($clan->getMembers () as $v)
						{
							$out['members'] .= "- " . Neuron_Core_Tools::output_varchar ($v->getName ()) . "\n";
						}
					}
				}

				// Ranking
				$players = Dolumar_Players_Ranking::getPlayerRanking (0, 5);
				$i = 0;
				foreach ($players as $v)
				{
					$i ++;
					$out['players'] .= $i . ". " . $v->getName () . ' (' . $v->getScore () . ')' . "\n";
				}

				$villages = Dolumar_Players_Ranking::getRanking (0, 5);
				$i = 0;
				foreach ($villages as $v)
				{
					$i ++;
					$out['villages'] .= $i . ". " . $v->getName () . ' (' . $v->getNetworth () . ')' . "\n";
				}

				$ranking = Dolumar_Players_Ranking::getClanRanking (0, 5);
				$i = 0;
				foreach ($ranking as $v)
				{
					$i ++;
					$out['clans'] .= $i . ". " . $v->getName () . ' (' . $v->getNetworth () . ')'. "\n";
				}

				$out['servername'] = $this->getServerName ();
				$out['serverurl'] = ABSOLUTE_URL;

				// Send out the bloody newsletter
				if (!defined ('IS_TESTSERVER') || !IS_TESTSERVER)
				{
					$this->sendNewsletter ('serverreset', $out);
				}
			break;

			case self::GAMESTATE_WAITINGFORRESET:

				if ($this->getData ('end_date') <= (NOW - 60 * 60 * 23))
				{
					$this->reset ();
				}

			break;
		}
	}

	public function reset ()
	{
		// Truncate all tables
		$db = Neuron_DB_Database::getInstance ();

		$okay = false;
		$iterations = 10;

		$db->query ("SET foreign_key_checks = 0;");

		while (!$okay && $iterations > 0)
		{
			$okay = true;
			$iterations --;

			$tables = $db->query ('SHOW TABLES');

			foreach ($tables as $v)
			{
				$table = array_values ($v);
				$table = $table[0];

				try {
					$db->query ("TRUNCATE TABLE " . $table);	
				} catch (Exception $e) {
					echo $e;
					$okay = false;
				}

				// Check
				$chk = $db->query 
				("
					SELECT 
						*
					FROM
						$table
				");

				if (count ($chk) > 0)
				{
					$okay = false;
				}
			}
		}

		$db->query ("SET foreign_key_checks = 1;");

		// Set a launch date in the future
		$launchdate = mktime (20, 0, 0);
		$launchdate += 60 * 60 * 24 * 1;
		$this->setData ('launchdate', $launchdate);

		$this->clearCache ();
	}

	public function sendNewsletter ($newsletter, $data)
	{
		$text = Neuron_Core_Text::getInstance ();

		$credits = Neuron_GameServer_Credits::getPureCreditsObject ();

		$plaintext = $text->getTemplate ('newsletters/' . $newsletter, $data);
		$subject = Neuron_Core_Tools::putIntoText ($text->get ($newsletter, 'subjects', 'newsletters'), $data);

		// Now put it in html
		$page = new Neuron_Core_Template ();

		$page->set ('subject', $subject);
		$page->set ('content', Neuron_Core_Tools::output_text ($plaintext));

		$content = $page->parse ('mailtemplate.phpt');

		//echo $page->parse ('mailtemplate.phpt');
		//exit;

		$credits->sendNewsletter ($subject, $content, $plaintext, 'en');
	}

	public function getStartDate ()
	{
		$db = Neuron_DB_Database::getInstance ();

		$date = $db->query
		("
			SELECT UNIX_TIMESTAMP(l_date) AS datum FROM `game_log` ORDER BY l_date ASC LIMIT 1
		");

		if (count ($date) > 0)
		{
			return $date[0]['datum'];
		}
		else
		{
			return NOW;
		}
	}

	public static function searchVillage ($sName, $iStart = 0, $iLength = 10)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$l = $db->getDataFromQuery
		($db->customQuery(
		"
			SELECT
				*
			FROM
				villages
			WHERE
				vname LIKE '".$db->escape ($sName)."'
			LIMIT
				$iStart, $iLength
		"));

		$o = array ();
		$i = 0;
		foreach ($l as $v)
		{
			$o[$i] = Dolumar_Players_Village::getVillage ($v['vid'], false);
			$o[$i]->setData ($v);
		}
		return $o;
	}
	
	public static function searchVillageCount ($sName)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$l = $db->getDataFromQuery
		($db->customQuery(
		"
			SELECT
				COUNT(vid) as aantal
			FROM
				villages
			WHERE
				vname LIKE '".$db->escape ($sName)."'
		"));

		if (count ($l) == 1)
		{
			return $l['aantal'];
		}
		else
		{
			return false;
		}
	}
	
	public static function countVillagesFromRace ($raceId)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$l = $db->getDataFromQuery
		($db->customQuery(
		"
			SELECT
				COUNT(vid) as aantal
			FROM
				villages
			WHERE
				race = '".((int)$raceId)."'
		"));

		return $l[0]['aantal'];
	}
	
	public static function countVillages ($showInactive = true)
	{
		$db = Neuron_Core_Database::__getInstance ();

		if ($showInactive)
		{
			$l = $db->getDataFromQuery
			($db->customQuery(
			"
				SELECT
					COUNT(vid) as aantal
				FROM
					villages
			"));
		}
		else
		{
			$l = $db->getDataFromQuery
			($db->customQuery(
			"
				SELECT
					COUNT(vid) as aantal
				FROM
					villages
				LEFT JOIN
					n_players ON villages.plid = n_players.plid
				WHERE
					n_players.isKillVillages = '0'
			"));
		}

		return $l[0]['aantal'];
	}
}
?>
