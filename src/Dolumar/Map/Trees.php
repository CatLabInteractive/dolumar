<?php

class Dolumar_Map_Trees extends Dolumar_Map_Location
{
	public function getImage ()
	{	
		return array
		(
			'image' 	=> 'trees'.$this->randomNumber,
			'width'		=> 200,
			'height'	=> 200
		);
	}
	
	public function getMapColor ()
	{
		return array (61, 132, 26);
	}

	public function getIncomeBonus ()
	{
		return array ('wood' => 20);
	}
}

?>
