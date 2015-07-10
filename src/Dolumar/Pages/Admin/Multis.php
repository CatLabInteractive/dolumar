<?php
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
