<?php

class Dolumar_Map_Bush extends Dolumar_Map_Location
{
	public function getImage ()
	{
		return array
		(
			'image' 	=> 'bush'.(($this->randomNumber % 4) + 1),
			'width'		=> 200,
			'height'	=> 102
		);
	}
	
	public function getMapColor ()
	{
		return array (159, 188, 0);
	}
}

?>
