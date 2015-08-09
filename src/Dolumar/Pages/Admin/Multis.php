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

class Dolumar_Pages_Admin_Multis extends Neuron_GameServer_Pages_Admin_Multis
{
	const SUSPICIOUS_TRANSACTIONS_PERPAGE = 5;

	public function getBody ()
	{
		$timeframe = Neuron_Core_Tools::getInput ('_GET', 'timeframe', 'int', 60*60*48);
	
		$page = new Neuron_Core_Template ();
		
		$page->set ('timeframe', $timeframe);
		
		$objLogs = Dolumar_Players_Logs::getInstance ();
		$objLogs->setTimeInterval (NOW - $timeframe, NOW);
		
		$pageid = Neuron_Core_Tools::getInput ('_GET', 'page', 'int', 1);
		
		$limit = Neuron_Core_Tools::splitInPages 
		(
			$page, 
			$objLogs->getSuspiciousLogsCounter (), 
			$pageid, 
			self::SUSPICIOUS_TRANSACTIONS_PERPAGE, 
			7, 
			array 
			(
				'timeframe' => $timeframe
			),
			'multis'
		);
		
		//print_r ($limit);
		
		//$logs = $objLogs->getSuspiciousLogs ();
		$logs = $objLogs->getSuspiciousLogs ($limit['start'], $limit['perpage'], 'DESC');
		
		
		foreach ($logs as $v)
		{
			$player = Dolumar_Players_Village::getFromId ($v['village'])->getOwner ();
		
			$page->addListValue
			(
				'logs',
				array
				(
					'action' => $objLogs->getLogText ($v, false),
					'date' => date (DATETIME, $v['timestamp']),
					'player' => $player->getDisplayName ()
				)
			);
		}
	
		// Output the shizzle
		$html = $page->parse ('dolumar/pages/admin/multis/dangeroustransactions.phpt');
		$html .= parent::getBody ();
		return $html;
	}
}
?>
