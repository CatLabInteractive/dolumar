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

class Dolumar_Quests_S10JoinClan extends Dolumar_Quests_Quest
{
	public function onStart (Neuron_GameServer_Player $player)
	{
		// The guide looks proud of a won first battle and sad about a lost one.
		$this->say
		(
			$player,
			Dolumar_Players_Server::isNeverending () ? 's10clan_neverending' : 's10clan',
			$this->hasWonLastBattle ($player) ? 'proud' : 'sad'
		);
	}

	/**
	 * True when the main village won the last attack it fought (or the result is unknown).
	 */
	private function hasWonLastBattle (Neuron_GameServer_Player $player)
	{
		$village = $this->getVillage ($player);
		if (!$village)
		{
			return true;
		}

		$db = Neuron_DB_Database::getInstance ();
		$data = $db->query
		("
			SELECT
				r.victory
			FROM
				battle b
			INNER JOIN
				battle_report r ON r.reportId = b.bLogId
			WHERE
				b.vid = " . intval ($village->getId ()) . " AND
				b.isFought = 1
			ORDER BY
				b.fightDate DESC
			LIMIT 1
		");

		// Same rule as the battle report: the attacker won from 1% victory up.
		return count ($data) === 0 || floatval ($data[0]['victory']) >= 0.01;
	}

	public function getObjective (Neuron_GameServer_Player $player)
	{
		return 'clan';
	}

	public function getAction (Neuron_GameServer_Player $player)
	{
		return "openWindow('clan');";
	}

	public function isFinished (Neuron_GameServer_Player $player)
	{
		return count ($player->getClans ()) > 0;
	}

	public function onComplete (Neuron_GameServer_Player $player)
	{
		$this->say ($player, Dolumar_Players_Server::isNeverending () ? 's10finished_neverending' : 's10finished', 'proud');
	}
}
