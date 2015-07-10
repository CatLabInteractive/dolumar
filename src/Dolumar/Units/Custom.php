<?php
class Dolumar_Units_Custom extends Dolumar_Units_Unit
{
	private $stats = array
	(
		'atAt' => 20,
		'atDef' => 25,
		'hp' => 600,
		'defIn' => 35,
		'defAr' => 40,
		'defCav' => 50,
		'defMag' => 10
	);

	private $attackType = 'defAr';
	
	public function getConsumption ()
	{
		return array ();
	}

	public function setAttackType ($at)
	{
		if ($at == 'defAr')
		{
			$this->attackType = 'defAr';
		}

		elseif ($at == 'defCav')
		{
			$this->attackType = 'defCav';
		}

		elseif ($at == 'defMag')
		{
			$this->attackType = 'defMag';
		}

		else
		{
			$this->attackType = 'defIn';
		}
	}

	public function getAttackType ()
	{
		return $this->attackType;
	}

	public function setStats ($atAt, $atDef, $hp, $defIn, $defAr, $defCav, $defMag)
	{
		$this->stats = array
		(
			'atAt' => $atAt,
			'atDef' => $atDef,
			'hp' => $hp,
			'defIn' => $defIn,
			'defAr' => $defAr,
			'defCav' => $defCav,
			'defMag' => $defMag
		);
	}

	public function getStats ()
	{
		return $this->stats;
	}
}
?>
