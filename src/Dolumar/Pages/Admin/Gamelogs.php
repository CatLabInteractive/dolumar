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

class  Dolumar_Pages_Admin_Gamelogs extends Neuron_GameServer_Pages_Admin_Page
{
	public function getBody ()
	{
		$player = Neuron_GameServer::getPlayer ();
		if (!$player->isModerator ())
		{
			return '<p>You don\'t have the rights to access the player logs.</p>';
		}
	
		$page = new Neuron_Core_Template ();
		
		// Let's find the players
		$input = Neuron_Core_Tools::getInput ('_GET', 'players', 'varchar');
		$playerids = explode ('|', $input);
		
		$players = array ();
		$villages = array ();
		
		$ids = array ();
		
		$i = 0;
		
		foreach ($playerids as $v)
		{
			$player = Neuron_GameServer::getPlayer ($v);
			if ($player)
			{
				$players[] = $player;
				$villages = array_merge ($villages, $player->getVillages ());
				$ids[$player->getId ()] = $i;
				
				$page->addListValue 
				(
					'players', 
					array
					(
						'key' => $i,
						'id' => $player->getId (),
						'name' => $player->getName (),
						'url' => $this->getUrl ('user', array ('id' => $player->getId ())),
					)
				);
				
				$i ++;
			}
		}
		
		$pageid = max (0, intval (Neuron_Core_Tools::getInput ('_GET', 'page', 'int', 1)) - 1);
		
		$objLogs = Dolumar_Players_Logs::getInstance ();
		$logs = $objLogs->getLogs ($villages, $pageid * 250, 250, 'DESC');
		
		foreach ($logs as $v)
		{
			$player = Dolumar_Players_Village::getFromId ($v['village'])->getOwner ();
			
			// Check if this is an important log.
			$bImportant = $this->isImportantLog ($players, $v);
		
			$page->addListValue
			(
				'logs',
				array
				(
					'action' => $objLogs->getLogText ($v, false),
					'date' => date (DATETIME, $v['timestamp']),
					'player' => $player->getName (),
					'url' => $this->getUrl ('user', array ('id' => $player->getId ())),
					'key' => $ids[$player->getId ()],
					'important' => $bImportant ? 'important' : null
				)
			);
		}
		
		$page->set ('page', $pageid + 1);
		if (count ($logs) == 250)
		{
			$page->set ('nextpage', $this->getUrl ('gamelogs', array ('players' => $input, 'page' => $pageid + 2)));
		}
		
		if ($pageid > 0)
		{
			$page->set ('previouspage', $this->getUrl ('gamelogs', array ('players' => $input, 'page' => $pageid)));
		}
	
		return $page->parse ('pages/admin/gamelogs.phpt');
	}
	
	private function isImportantLog ($players, $log)
	{
		switch ($log['action'])
		{
			case 'sendMsg':
			case 'receiveMsg':
				return false;
			break;
		}
	
		if (!isset ($log['targets']))
		{
			return false;
		}
	
		foreach ($log['targets'] as $v)
		{
			foreach ($players as $vv)
			{
				if ($v->getId () == $vv->getId ())
				{
					return true;
				}
			}
		}
		return false;
	}
}
?>
