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
 * Admin page "NPC tribes": every tribe, its standing towards players and its decision log (npc_log).
 * Filters: ?vid= (tribe village id) and ?plid= (player id).
 */
class Dolumar_Pages_Admin_Npctribes extends Neuron_GameServer_Pages_Admin_Page
{
	const TRIBES_PER_PAGE = 50;
	const STANDINGS_LIMIT = 200;
	const LOGS_PER_PAGE = 100;

	/** @var array vid => name */
	private $villageNames = [];

	/** @var array plid => name */
	private $playerNames = [];

	public function getBody ()
	{
		$myself = Neuron_GameServer::getPlayer ();
		if (!$myself->isModerator ())
		{
			return '<p>You don\'t have the rights to access the NPC tribes.</p>';
		}

		$repository = Dolumar_Npc_StandingRepository::getInstance ();
		$now = time ();

		$vid = $this->intFilter ('vid');
		$plid = $this->intFilter ('plid');
		$filters = array_filter (['vid' => $vid, 'plid' => $plid], function ($v) { return $v !== null; });

		$page = new Neuron_Core_Template ();
		$page->set ('formAction', $this->getUrl ('npctribes'));
		$page->set ('vid', $vid === null ? '' : $vid);
		$page->set ('plid', $plid === null ? '' : $plid);
		$page->set ('aggression', Dolumar_Npc_Config::aggression ());
		$page->set ('protectionRunes', Dolumar_Npc_Config::protectionRunes ());

		if (count ($filters) > 0)
		{
			$page->set ('clearUrl', $this->getUrl ('npctribes'));
			$page->set ('filterText', $this->filterText ($vid, $plid));
		}

		// Tribes (only without a tribe filter).
		if ($vid === null)
		{
			$tpage = max (0, (int) Neuron_Core_Tools::getInput ('_GET', 'tpage', 'int', 1) - 1);
			$total = $repository->countTribes ();
			$page->set ('tribeCount', $total);

			foreach ($repository->tribesPage ($tpage * self::TRIBES_PER_PAGE, self::TRIBES_PER_PAGE) as $tribe)
			{
				$page->addListValue ('tribes', [
					'tribe' => $this->e ($tribe['tribe_name']),
					'village' => $this->villageName ($tribe['vid']),
					'url' => $this->getUrl ('npctribes', ['vid' => $tribe['vid']]),
					'multipliers' => sprintf ('%.2f / %.2f / %.2f', $tribe['mul_threshold'], $tribe['mul_decay'], $tribe['mul_hoard']),
					'request' => $tribe['request_amount'] > 0 ? $tribe['request_amount'] . ' ' . $this->e ($tribe['request_res']) : '-',
					'standings' => $tribe['standings'],
					'created' => $tribe['created'] > 0 ? date (DATETIME, $tribe['created']) : '-',
					'lastRun' => $tribe['last_run'] > 0 ? Dolumar_Npc_AdminView::duration ($now - $tribe['last_run']) . ' ago' : 'never',
				]);
			}

			$this->pager ($page, 'tribes', 'tpage', $tpage, ($tpage + 1) * self::TRIBES_PER_PAGE < $total, $filters);
		}
		else if ($tribe = $repository->tribe ($vid))
		{
			$page->set ('tribe', [
				'tribe' => $this->e ($tribe['tribe_name']),
				'village' => $this->villageName ($vid),
				'multipliers' => sprintf ('%.2f / %.2f / %.2f', $tribe['mul_threshold'], $tribe['mul_decay'], $tribe['mul_hoard']),
				'request' => $tribe['request_amount'] > 0 ? $tribe['request_amount'] . ' ' . $this->e ($tribe['request_res']) : '-',
				'lastRun' => $tribe['last_run'] > 0 ? date (DATETIME, $tribe['last_run']) : 'never',
			]);
		}

		// Standings.
		$tribes = [];
		foreach ($repository->standingsFiltered ($vid, $plid, self::STANDINGS_LIMIT) as $row)
		{
			if (!array_key_exists ($row['vid'], $tribes))
			{
				$tribes[$row['vid']] = $repository->tribe ($row['vid']) ?: [];
			}
			$view = Dolumar_Npc_AdminView::standing ($row, $tribes[$row['vid']], $now);

			$page->addListValue ('standings', [
				'tribe' => $this->villageName ($row['vid']),
				'tribeUrl' => $this->getUrl ('npctribes', ['vid' => $row['vid']]),
				'player' => $this->playerName ($row['plid']),
				'playerUrl' => $this->getUrl ('npctribes', ['plid' => $row['plid']]),
				'userUrl' => $this->getUrl ('user', ['id' => $row['plid']]),
				'value' => sprintf ('%.1f', $view['value']),
				'tier' => $view['tier'],
				'stage' => $this->e ($view['stage']),
				'truce' => $view['truce'],
				'pact' => $view['pact'] ? 'yes' : '',
				'support' => $view['support'],
				'lastAttack' => $view['last_attack'],
			]);
		}

		// Decision log.
		$lpage = max (0, (int) Neuron_Core_Tools::getInput ('_GET', 'page', 'int', 1) - 1);
		$logs = $repository->logPage ($vid, $plid, $lpage * self::LOGS_PER_PAGE, self::LOGS_PER_PAGE);
		foreach ($logs as $log)
		{
			$page->addListValue ('logs', [
				'date' => date (DATETIME, $log['date']),
				'tribe' => $this->villageName ($log['vid']),
				'tribeUrl' => $this->getUrl ('npctribes', ['vid' => $log['vid']]),
				'player' => $log['plid'] === null ? '' : $this->playerName ($log['plid']),
				'playerUrl' => $log['plid'] === null ? '' : $this->getUrl ('npctribes', ['plid' => $log['plid']]),
				'message' => $this->e ($log['message']),
			]);
		}
		$this->pager ($page, 'logs', 'page', $lpage, count ($logs) === self::LOGS_PER_PAGE, $filters);

		return $page->parse ('pages/admin/npctribes.phpt');
	}

	/**
	 * Sets <prefix>Previous / <prefix>Next links that keep the current filters.
	 */
	private function pager (Neuron_Core_Template $page, $prefix, $param, $current, $hasNext, array $filters)
	{
		if ($current > 0)
		{
			$page->set ($prefix . 'Previous', $this->getUrl ('npctribes', $filters + [$param => $current]));
		}
		if ($hasNext)
		{
			$page->set ($prefix . 'Next', $this->getUrl ('npctribes', $filters + [$param => $current + 2]));
		}
	}

	/**
	 * @param string $key
	 * @return int|null a positive id from the query string
	 */
	private function intFilter ($key)
	{
		$value = isset ($_GET[$key]) ? (int) $_GET[$key] : 0;
		return $value > 0 ? $value : null;
	}

	private function filterText ($vid, $plid)
	{
		$parts = [];
		if ($vid !== null)
		{
			$parts[] = 'tribe ' . $this->villageName ($vid);
		}
		if ($plid !== null)
		{
			$parts[] = 'player ' . $this->playerName ($plid);
		}
		return implode (' and ', $parts);
	}

	/**
	 * @param int $vid
	 * @return string escaped village name with its id
	 */
	private function villageName ($vid)
	{
		if (!array_key_exists ($vid, $this->villageNames))
		{
			$village = Dolumar_Players_Village::getFromId ($vid);
			$name = ($village && $village->isFound ()) ? $village->getName () : 'deleted village';
			$this->villageNames[$vid] = $this->e ($name) . ' (#' . (int) $vid . ')';
		}
		return $this->villageNames[$vid];
	}

	/**
	 * @param int $plid
	 * @return string escaped player name with its id
	 */
	private function playerName ($plid)
	{
		if (!array_key_exists ($plid, $this->playerNames))
		{
			$player = Neuron_GameServer::getPlayer ($plid);
			$name = ($player && $player->isFound ()) ? $player->getName () : 'unknown player';
			$this->playerNames[$plid] = $this->e ($name) . ' (#' . (int) $plid . ')';
		}
		return $this->playerNames[$plid];
	}

	private function e ($text)
	{
		return htmlspecialchars ((string) $text, ENT_QUOTES, 'UTF-8');
	}
}
