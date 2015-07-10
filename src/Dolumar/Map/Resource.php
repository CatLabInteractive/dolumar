<?php

class Dolumar_Map_Resource extends Dolumar_Map_Location
{

	private function getRes ()
	{
		$o = array
		(
			'',
			'gems',
			'iron',
			'stone'
		);

		return $o[$this->randomNumber];
	}

	public function getImage ()
	{
		return array
		(
			'image' 	=> $this->getRes (),
			'width'		=> 200,
			'height'	=> 100
		);
	}

	public function getMapColor ()
	{
		return array (255, 200, 0);
	}
	
	public function canBuildBuilding ()
	{
		return false;
	}

	public function getIncomeBonus ()
	{
		return array
		(
			$this->getRes () => 20
		);
	}
}

?>
