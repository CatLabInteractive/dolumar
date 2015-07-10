<?php
class Dolumar_Players_ClanLogs extends Dolumar_Players_Logs
{
	public static function __getInstance ()
	{
		static $in;
		if (!isset ($in) || empty ($in))
		{
			$in = new self ();
			$in->clearFilters ();
			$in->applyFilters ();
		}
		return $in;
	}
	
	public static function getInstance ()
	{
		return self::__getInstance ();
	}
	
	public function applyFilters ()
	{
		$this->addShowOnly ('defend');
		$this->addShowOnly ('attack');
		$this->addShowOnly ('portal_open');
	}
	
	public function getClanLogs ($objClan, $startPoint = 0, $length = 50, $order = 'DESC')
	{		
		$villages = $this->getVillages ($objClan);
		return $this->getLogs ($villages, $startPoint, $length, $order);
	}
	
	public function countClanLogs ($objClan)
	{
		$villages = $this->getVillages ($objClan);	
		return $this->countLogs ($villages);
	}
	
	private function getVillages ($objClan)
	{
		if (!is_array ($objClan))
		{
			$objClan = array ($objClan);
		}
	
		$villages = array ();
		foreach ($objClan as $clan)
		{
			foreach ($clan->getMembers () as $members)
			{
				foreach ($members->getVillages () as $v)
				{
					$villages[] = $v;
				}
			}
		}
		
		return $villages;
	}
}
?>
