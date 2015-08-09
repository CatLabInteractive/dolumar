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

class Dolumar_Underworld_Windows_Battle
	extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$this->setClassName ('underworld battle');
		$this->setSize ('350px', '360px');
		$this->setOnload ('initBattleWindow');
		$this->setTitle ('Battles');
	}

	public function getContent ()
	{
		$player = Neuron_GameServer::getPlayer ();

		if (!$player)
		{
			return '<p>Please login.</p>';
		}

		$id = $this->getInput ('report');

		if (isset ($id))
		{
			return $this->getReport ($id);
		}
		else
		{
			return $this->getOverview ();
		}
	}

	private function getReport ($id)
	{
		$player = Neuron_GameServer::getPlayer ();

		$report = Dolumar_Underworld_Mappers_BattleMapper::getFromId ($id);

		if (!isset ($report))
		{
			return '<p>Report not found.</p>';
		}

		return $report->getReport ()->getReport (null, $this->getInput ('log'), $this->getInput ('fightlog') == '1', true);
	}

	private function getOverview ()
	{
		$player = Neuron_GameServer::getPlayer ();

		$map = $this->getServer ()->getMap ();
		if (! ($map instanceof Dolumar_Underworld_Map_Map))
		{
			$this->reloadWindow ();
			return '<p>Mission is finished.</p>';
		}

		$mission = $map->getMission ();

		$side = $mission->getPlayerSide ($player);

		$total = Dolumar_Underworld_Mappers_BattleMapper::countFromSide ($mission, $side);
		$battles = Dolumar_Underworld_Mappers_BattleMapper::getFromSide ($mission, $side);

		$page = new Neuron_Core_Template ();

		$page->set ('side', $side);

		foreach ($battles as $v)
		{
			$page->addListValue ('battles', $v);
		}

		return $page->parse ('dolumar/underworld/windows/battle.phpt');
	}
}