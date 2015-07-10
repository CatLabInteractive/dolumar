<?php
class Dolumar_Effects_Instant_OpenPortal extends Dolumar_Effects_Instant
{
	public function prepare ()
	{
		// Fetch a random target
		$networth = $this->getVillage ()->getScore ();
		
		$minscore = floor ($networth * 0.75);
		$maxscore = ceil ($networth * 1.25);
		
		$db = Neuron_DB_Database::getInstance ();
		
		$myclanlist = "";
		foreach ($this->getVillage ()->getOwner ()->getClans () as $v)
		{
			$myclanlist .= $v->getId ().",";
		}
		$myclanlist = substr ($myclanlist, 0, -1);
		
		$chk = $db->query
		("
			SELECT
				*
			FROM
				villages v
			LEFT JOIN
				n_players p USING(plid)
			LEFT JOIN
				clan_members cm ON cm.plid = p.plid AND cm.c_id IN ({$myclanlist})
			WHERE
				v.networth > {$minscore} AND
				v.networth < {$maxscore} AND
				v.plid != {$this->getVillage ()->getOwner ()->getId ()} AND
				v.isActive = 1 AND
				p.startVacation IS NULL AND
				cm.c_id IS NULL
			ORDER BY
				RAND()
			LIMIT
				1
		");
		
		//die ($db->getLastQuery ());
		
		if (count ($chk) > 0)
		{	
			$village = Dolumar_Players_Village::getVillage ($chk[0]['vid']);
			$this->setTarget ($village);
		}
		else
		{
			$this->setCastable (false);
			$this->setError (Dolumar_Effects_Effect::ERROR_NO_TARGET_FOUND);
		}
	}

	public function execute ($a = null, $b = null, $c = null)
	{
		$target = $this->getTarget ();
		if (isset ($target))
		{
			$this->getVillage ()->portals->openPortal ($target);
		}
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 4;
	}
}
?>
