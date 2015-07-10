<?php
class Dolumar_Effects_Boost_TowerBonus extends Dolumar_Effects_Boost
{
	private $bonus;

	public function __construct ($bonus)
	{
		$this->bonus = $bonus;
		parent::__construct ();
	}

	public function procUnitStats (&$stats, $objUnit)
	{
		$stats['hp'] *= (1 + $this->bonus);
	}
}
?>
