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
 * A step of the guide's quest chain.
 *
 * Every step adds one guide message when it starts (so a message the player misses
 * cannot hide the task) and names its objective, which the Guide window keeps on
 * screen until the step is finished. A step may also name a "Show me" action: the
 * JavaScript that opens the window where the player does it.
 */
abstract class Dolumar_Quests_Quest extends Neuron_GameServer_Quest
{
	/**
	 * Key in guide.lng, section [objectives], of the one-line task; null for none.
	 * @return string|null
	 */
	public function getObjective (Neuron_GameServer_Player $player)
	{
		return null;
	}

	/**
	 * JavaScript that opens the place where the player does this step; null for none.
	 * @return string|null
	 */
	public function getAction (Neuron_GameServer_Player $player)
	{
		return null;
	}

	/**
	 * Replace the guide's current advice by this step's message.
	 */
	protected function say (Neuron_GameServer_Player $player, $template, $mood = 'neutral', $data = array ())
	{
		$player->guide->setAllRead ();
		$player->guide->addMessage ($template, $data, 'guide', $mood);
	}

	/**
	 * @return Dolumar_Players_Village|null
	 */
	protected function getVillage (Neuron_GameServer_Player $player)
	{
		$village = $player->getMainVillage ();
		return $village ? $village : null;
	}

	protected function hasBuilding (Neuron_GameServer_Player $player, $buildingId)
	{
		$village = $this->getVillage ($player);

		return $village && $village->buildings->hasBuilding
		(
			Dolumar_Buildings_Building::getBuilding ($buildingId, $village->getRace ()),
			true
		);
	}

	/**
	 * Number of rows in $table where $column is the main village's id.
	 */
	protected function countVillageRows (Neuron_GameServer_Player $player, $table, $column, $extra = '')
	{
		$village = $this->getVillage ($player);
		if (!$village)
		{
			return 0;
		}

		$db = Neuron_DB_Database::getInstance ();
		$data = $db->query
		("
			SELECT
				COUNT(*) AS aantal
			FROM
				{$table}
			WHERE
				{$column} = " . intval ($village->getId ()) . "
				{$extra}
		");

		return count ($data) > 0 ? intval ($data[0]['aantal']) : 0;
	}

	protected function openBuildWindow (Neuron_GameServer_Player $player)
	{
		$village = $this->getVillage ($player);
		return $village ? "openWindow('build', {'vid':" . intval ($village->getId ()) . "});" : null;
	}

	/**
	 * Opens the window of the first building of this type in the main village.
	 */
	protected function openBuildingOfType (Neuron_GameServer_Player $player, $buildingId)
	{
		$village = $this->getVillage ($player);
		if (!$village)
		{
			return null;
		}

		$db = Neuron_DB_Database::getInstance ();
		$data = $db->query
		("
			SELECT
				bid
			FROM
				map_buildings
			WHERE
				village = " . intval ($village->getId ()) . " AND
				buildingType = " . intval ($buildingId) . " AND
				destroyDate = 0
			ORDER BY
				bid ASC
			LIMIT 1
		");

		return count ($data) > 0 ? "openWindow('building', {'bid':" . intval ($data[0]['bid']) . "});" : null;
	}
}
