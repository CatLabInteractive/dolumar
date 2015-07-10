<?php

class Dolumar_Map_Haystack extends Dolumar_Map_Location
{
	public function getImage ()
	{
		return array
		(
			'image' 	=> 'haystack',
			'width'		=> 200,
			'height'	=> 200
		);
	}

	public function getMapColor ()
	{
		return array (255, 255, 100);
	}
	
	public function canBuildBuilding ()
	{
		return false;
	}

	public function getIncomeBonus ()
	{
		//return array ();
		return array ('grain' => 20);
	}
}

?>
