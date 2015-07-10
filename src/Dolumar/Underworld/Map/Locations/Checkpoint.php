<?php
class Dolumar_Underworld_Map_Locations_Checkpoint
	extends Dolumar_Underworld_Map_Locations_Floor
{
	public function getTile (Dolumar_Underworld_Map_BackgroundManager $map)
	{
		return new Neuron_GameServer_Map_Display_Sprite ($this->getTileDir () . 'castle.png');
	}
}
?>
