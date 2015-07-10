<?php
/*
	This class handles everything that has to do with honour.
*/
class Dolumar_Players_Village_Honour
{
	private $objProfile;
	private $honour = null;

	public function __construct ($profile)
	{
		$this->objProfile = $profile;
	}
	
	/*
		Return the honour: a number between 10 and 125
	*/
	public function getHonour ()
	{
		if ($this->objProfile instanceof Dolumar_Players_DummyVillage)
		{
			return 100;
		}
	
		if (!isset ($this->honour))
		{
			$db = Neuron_DB_Database::__getInstance ();
		
			// Fetch all honour modifiers
			$honour = $db->query
			("
				SELECT
					m_amount,
					UNIX_TIMESTAMP(m_start) AS start,
					UNIX_TIMESTAMP(m_end) AS end
				FROM
					villages_morale
				WHERE
					m_end > FROM_UNIXTIME(".NOW.") AND
					m_vid = ".$this->objProfile->getId ()."
			");
		
			$iHonour = 100;
		
			foreach ($honour as $v)
			{
				$duration = $v['end'] - $v['start'];
				$passed = NOW - $v['start'];
			
				$iHonour += (1 - ($passed / $duration)) * $v['m_amount'];
			}
		
			$this->honour = max (min (floor ($iHonour), 125), 10);
		}
		
		return $this->honour;
	}
	
	public function withdrawHonour ($amount)
	{
		$db = Neuron_DB_Database::__getInstance ();
		
		$amount *= -1;
		
		// A village gets 1 honour every hour:
		$duration = (abs ($amount) * 60 * 60);
		
		// Duration should take longer depending on the current honour
		$duration += (min (0, 100 - $this->getHonour ()) * 60 * 60);
		
		$db->query
		("
			INSERT INTO
				villages_morale
			SET
				m_vid = ".$this->objProfile->getId ().",
				m_amount = ".$amount.",
				m_start = FROM_UNIXTIME(".NOW."),
				m_end = FROM_UNIXTIME(".(NOW + ceil ($duration)).")
		");
		
		// Clean the mess
		$db->query
		("
			DELETE FROM
				villages_morale
			WHERE
				m_end < FROM_UNIXTIME(".NOW.")
		");
		
		// Reset the honour
		$this->honour = null;
	}
	
	public function __destruct ()
	{
		unset ($this->objProfile);
		unset ($this->honour);
	}
}
?>
