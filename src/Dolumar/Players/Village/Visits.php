<?php
/*
	This class handles everything that has to do with battles.
*/
class Dolumar_Players_Village_Visits
{
	private $objProfile;

	public function __construct ($profile)
	{
		$this->objProfile = $profile;
	}
	
	public function getLastVisits ($amount = 10)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$l = $db->query
		("
			SELECT
				*
			FROM
				villages_visits
			WHERE
				v_id = {$this->objProfile->getId ()}
			ORDER BY
				vi_date DESC
			LIMIT
				{$amount}
		");
	
		$out = array ();
		
		foreach ($l as $v)
		{
			$out[] = Dolumar_Players_Village::getVillage ($v['vi_v_id']);
		}
		
		return $out;
	}
	
	public function registerVisit ($village)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		// Check if exists
		$chk = $db->query
		("
			SELECT
				vi_id
			FROM
				villages_visits
			WHERE
				v_id = {$this->objProfile->getId ()} AND 
				vi_v_id = {$village->getId ()} 
		");
		
		if (count ($chk) > 0)
		{
			$db->query
			("
				UPDATE
					villages_visits
				SET
					vi_date = NOW()
				WHERE
					v_id = {$this->objProfile->getId ()} AND 
					vi_v_id = {$village->getId ()} 
			");
		}
		
		else
		{
			$db->query
			("
				INSERT INTO
					villages_visits
				SET
					vi_date = NOW(),
					v_id = {$this->objProfile->getId ()},
					vi_v_id = {$village->getId ()}
			");
		}
	}
	
	public function __destruct ()
	{
		unset ($this->objProfile);
	}
}
?>
