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

class Dolumar_Windows_Clanlogs extends Dolumar_Windows_Logbook
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('550px', '280px');
		$this->setTitle ($text->get ('clanlogs', 'menu', 'main'));
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		$player = Neuron_GameServer::getPlayer ();
		$input = $this->getInputData ();
		
		if (!$player)
		{
			return false;
		}
		
		$page = new Neuron_Core_Template ();
		
		$clans = $player->getClans ();
		
		if (count ($clans) > 0)
		{
			$objlogs = Dolumar_Players_ClanLogs::getInstance ();
			
			$iPage = isset ($input['page']) ? $input['page'] : 0;
		
			// Split in pages
			$limit = Neuron_Core_Tools::splitInPages 
			(
				$page, 
				$objlogs->countClanLogs ($clans), 
				$iPage, 
				10
			);
			
			$objlogs->clearMyVillages ();
			foreach ($player->getVillages () as $village)
			{
				$objlogs->addMyVillage ($village);
			}
			
			$logs = $objlogs->getClanLogs ($clans, $limit['start'], $limit['perpage'], 'DESC');
			
			return $this->getLogHTML ($page, $objlogs, $logs);
		}
		
		return false;
	}
	
	protected function getLogHTML ($page, $objLogs, $logs)
	{
		foreach ($logs as $v)
		{
			$page->addListValue
			(
				'logs',
				array
				(
					'date' => date (DATETIME, $v['timestamp']),
					'text' => $objLogs->getLogText ($v, true)
				)
			);
		}
		
		return $page->parse ('clan/clanlogs.phpt');	
	}
}
?>
