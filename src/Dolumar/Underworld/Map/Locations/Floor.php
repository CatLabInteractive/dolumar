<?php
class Dolumar_Underworld_Map_Locations_Floor 
	extends Dolumar_Underworld_Map_Locations_Location
{
	public function getTile (Dolumar_Underworld_Map_BackgroundManager $map)
	{
		$random = $this->getRandomNumber (9);
		return new Neuron_GameServer_Map_Display_Sprite ($this->getTileDir () . '/grass' . $random . '.png');
	}
}
?>
