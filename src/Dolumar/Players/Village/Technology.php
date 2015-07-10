<?php
class Dolumar_Players_Village_Technology
{
	private $technologies = null;
	private $objTechnologies = null;

	private $objProfile;

	public function __construct ($profile)
	{
		$this->objProfile = $profile;
	}

	private function loadTechnology ()
	{
		$db = Neuron_Core_Database::__getInstance ();

		if ($this->technologies === null)
		{
			$techs = $db->getDataFromQuery
			(
				$db->customQuery
				("
					SELECT
						*
					FROM
						villages_tech
					LEFT JOIN
						technology ON villages_tech.techId = technology.techId
					WHERE
						villages_tech.vid = '".$this->objProfile->getId ()."'
				")
			);

			$this->technologies = array ();
			foreach ($techs as $v)
			{
				$this->technologies[$v['techName']] = $v['endDate'] < time ();
			}
		}
	}

	/*
		Return an array of technology objects
	*/
	public function getTechnologies ()
	{
		if (!isset ($this->objTechnologies))
		{
			$this->loadTechnology ();
			
			$this->objTechnologies = array ();
			foreach ($this->technologies as $k => $v)
			{
				if ($v)
	   			{
	   				$tech = Dolumar_Technology_Technology::getTechnology ($k);
	   				
	   				if ($tech instanceof Dolumar_Technology_Technology)
	   				{
						$this->objTechnologies[] = $tech;
					}
				}
			}
		}
		return $this->objTechnologies;
	}
	
	/*
		Check a list of technologies
	*/
	public function hasTechnologies ($aTechnologies, $includeTraining = false)
	{
		foreach ($aTechnologies as $v)
		{
			if (! ($this->hasTechnology ($v)))
			{
				return false;
			}
		}
		
		return true;
	}

	/* 
		Technology settings
	*/
	public function hasTechnology ($s_technology, $includeTraining = false)
	{
		$this->loadTechnology ();
		
		if ($s_technology instanceof Dolumar_Technology_Technology)
		{
			$s_technology = $s_technology->getString ();
		}
		
		return isset ($this->technologies[$s_technology])
			&& ($includeTraining || $this->technologies[$s_technology]);
	}

	/*
		Train a technology
	*/
	public function trainTechnology ($technology)
	{
		$db = Neuron_Core_Database::__getInstance ();

		// Fetch technology id
		$techIds = $db->select
		(
			'technology',
			array ('techId'),
			"techName = '".$db->escape ($technology->getString ())."'"
		);

		if (count ($techIds) == 0)
		{
			$techId = $db->insert
			(
				'technology',
				array
				(
					'techName' => $technology->getString ()
				)
			);
		}
		else
		{
			$techId = $techIds[0]['techId'];
		}

		$db->insert
		(
			'villages_tech',
			array
			(
				'vid' => $this->objProfile->getId (),
				'techId' => $techId,
				'startDate' => time (),
				'endDate' => (time () + $technology->getDuration ())
			)
		);

		$objLogs = Dolumar_Players_Logs::__getInstance ();
		$objLogs->addResearchDone ($this->objProfile, $technology);

		reloadStatusCounters ();

		return true;
	}
	
	public function __destruct ()
	{
		unset ($this->village);
		unset ($this->technologies);
		
		/*
		if (isset ($this->objTechnologies) && is_array ($this->objTechnologies))
		{
			foreach ($this->objTechnologies as $v)
			{
				$v->__destruct ();
				unset ($v);
			}
		}
		*/
		
		unset ($this->objTechnologies);
	}
}
?>
