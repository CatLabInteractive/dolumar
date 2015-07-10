<?php
class Dolumar_Underworld_Map_Locations_Unexplored 
	extends Dolumar_Underworld_Map_Locations_Location
{
	public function isPassable ()
	{
		return false;
	}
	
	public function getTile (Dolumar_Underworld_Map_BackgroundManager $map)
	{
		return new Neuron_GameServer_Map_Display_Sprite ($this->getTileDir () . '/unexplored.png');
	}
}
?>
