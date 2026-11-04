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

class Dolumar_Windows_Guide extends Neuron_GameServer_Windows_Guide
{
	// Quests are checked on this poll, so steps that are not a building (training,
	// scouting, a battle) advance the guide without a page load.
	const POLL_SECONDS = 10;

	public function setSettings ()
	{
		parent::setSettings ();
		$this->setAjaxPollSeconds (self::POLL_SECONDS);
	}

	public function getRefresh ()
	{
		$player = Neuron_GameServer::getPlayer ();
		if (!$player || !$player->isPlaying ())
		{
			return;
		}

		$before = $this->countUnread ($player);
		$player->quests->evaluate ();

		if ($this->countUnread ($player) > $before)
		{
			$this->updateContent ();
		}
	}

	/*
		Adds the current objective: a "Show me" link in the speech bubble and, when the
		guide is folded away, a one-line task that opens it again. The mobile layout
		keeps that line on screen (Game.dolumar.mobile.syncGuide); the desktop hides it.
	*/
	public function getContent ()
	{
		$html = parent::getContent () . $this->getRegistrationTracker ();

		$player = Neuron_GameServer::getPlayer ();
		$quest = $player && $player->isPlaying () ? $this->getCurrentQuest ($player) : null;

		if (!$quest)
		{
			return $html;
		}

		$text = Neuron_Core_Text::getInstance ();
		$action = $quest->getAction ($player);

		$showMe = '';
		if ($action)
		{
			$showMe = '<a href="javascript:void(0);" class="guide-showme" onclick="' .
				htmlspecialchars ($action, ENT_QUOTES) . '">' .
				Neuron_Core_Tools::output_varchar ($text->get ('showme', 'navigation', 'guide')) . '</a>';
		}

		if (strpos ($html, 'blockquote') !== false)
		{
			if ($showMe)
			{
				$html = str_replace ('<ul class="navigation">', '<ul class="navigation"><li>' . $showMe . '</li>', $html);
			}
			return $html;
		}

		$objective = $quest->getObjective ($player);
		if (!$objective)
		{
			return $html;
		}

		$open = Neuron_URLBuilder::getInstance ()->getUpdateUrl
		(
			'Guide',
			Neuron_Core_Tools::output_varchar ($text->get ($objective, 'objectives', 'guide')),
			array ('action' => 'show'),
			$text->get ('callguide', 'navigation', 'guide')
		);

		return '<div class="objective"><span class="objective-label">' .
			Neuron_Core_Tools::output_varchar ($text->get ('objective', 'navigation', 'guide')) .
			'</span> ' . $open . ' ' . $showMe . '</div>' . $html;
	}

	/*
		The account page used to load the registration tracker right after race selection;
		Dolumar_Windows_MyAccount now closes that page, so the guide loads it once instead.
	*/
	private function getRegistrationTracker ()
	{
		if (empty ($_SESSION['dolumar_registration_tracker']))
		{
			return '';
		}

		$player = Neuron_GameServer::getPlayer ();
		if (!$player)
		{
			return '';
		}

		unset ($_SESSION['dolumar_registration_tracker']);

		return '<iframe src="' . htmlentities ($player->getTrackerUrl ('registration')) . '" width="1" ' .
			'height="1" border="0" class="hidden-iframe"></iframe>';
	}

	/**
	 * @return Dolumar_Quests_Quest|null
	 */
	private function getCurrentQuest (Neuron_GameServer_Player $player)
	{
		try
		{
			foreach ($player->quests->getPendingQuests () as $quest)
			{
				if ($quest instanceof Dolumar_Quests_Quest)
				{
					return $quest;
				}
			}
		}
		catch (Neuron_Core_Error $e)
		{
			// A quest row whose class no longer exists: no objective.
		}

		return null;
	}

	private function countUnread (Neuron_GameServer_Player $player)
	{
		$db = Neuron_DB_Database::getInstance ();

		$data = $db->query
		("
			SELECT
				COUNT(pg_id) AS aantal
			FROM
				n_players_guide
			WHERE
				plid = " . intval ($player->getId ()) . " AND
				pg_read = '0'
		");

		return count ($data) > 0 ? intval ($data[0]['aantal']) : 0;
	}

	protected function getClassname ($character, $mood)
	{
		$race = null;
		
		if ($player = Neuron_GameServer::getPlayer ())
		{
			if ($village = $player->getMainVillage ())
			{
				$race = $village->getRace ()->getName ();
			}
		}

		return $race . ' ' . $character . ' ' . $mood;
	}
}
?>
