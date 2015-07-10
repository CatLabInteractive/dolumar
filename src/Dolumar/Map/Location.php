<?php

class Dolumar_Map_Location implements Neuron_GameServer_Interfaces_Map_Location
{		
	public static function getLocation ($x, $y, $hasBuilding = false)
	{
		return Dolumar_Map_Map::getLocation ($x, $y, $hasBuilding);
	}
	
	protected $x, $y, $height, $randomNumber;
	
	public function __construct ($randomNumber, $x, $y, $iHeight)
	{
		$this->randomNumber = $randomNumber;
		$this->x = $x;
		$this->y = $y;
		$this->height = $iHeight;
	}
	
	public function getImage ()
	{
		return array
		(
			'image' 	=> 'grass'.(($this->randomNumber % 15) + 1)
		);
	}
	
	public function getImageName ()
	{
		$img = $this->getImage ();
		return $img['image'];
	}
	
	public function getHeight ()
	{
		return $this->height;
	}
	
	public function getMapColor ()
	{
		return array (105, 178, 0);
	}

	public function canBuildBuilding ()
	{
		return true;
	}

	public function getIncomeBonus ()
	{
		return array ();
	}

	public function isWater ()
	{
		return false;
	}

	public function getHeightIntencity ()
	{
		return min (((floor ($this->getHeight ())) / 12) + 0.4, 1);
	}
}

?>
