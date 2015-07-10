<?php
/*
	This class handles everything that has to do with honour.
*/
class Dolumar_Players_Village_Units
{
	private $objProfile;
	private $honour = null;

	public function __construct ($profile)
	{
		$this->objProfile = $profile;
	}
	
	/**
	* Return all data from one given order ID
	*/
	public function getTrainingStatus ($id)
	{
		$db = Neuron_DB_Database::getInstance ();

		$id = intval ($id);

		$data = $db->query
		("
			SELECT
				*
			FROM
				villages_units
			WHERE
				uid = {$id}
		");

		if (count ($data) > 0)
		{
			$v = $data[0];

			return array
			(
				'id' => $v['uid'],
				'village' => $v['vid'],
				'unit' => $v['unitId'],
				'amount' => $v['amount'],
				'startTraining' => $v['startTraining'],
				'endTraining' => $v['endTraining'],
				'timeLeft' => $v['endTraining'] - NOW
			);
		}
	}

	/**
	* Return all data from one given order ID
	*/
	public function speedupBuild ($id, $amount)
	{
		$db = Neuron_DB_Database::getInstance ();

		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		
		$profiler->start ('Speeding up training of ' . $id . ' with ' . $amount . ' seconds.');

		$data = $this->getTrainingStatus ($id);

		if ($data)
		{
			$timeLeft = $data['timeLeft'];
			if ($amount > $timeLeft)
			{
				$amount = $timeLeft;
			}
		}

		$db->query
		("
			UPDATE
				villages_units
			SET
				endTraining = endTraining - {$amount}
			WHERE
				uid = {$id}
		");

		$profiler->stop ();

		return;
	}
	
	public function __destruct ()
	{
		unset ($this->objProfile);
		unset ($this->honour);
	}
}