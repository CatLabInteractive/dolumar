<?php
class Dolumar_Underworld_Map_Locations_Location
	extends Neuron_GameServer_Map_Location
{	
	public function getTile (Dolumar_Underworld_Map_BackgroundManager $map)
	{
		return new Neuron_GameServer_Map_Display_Sprite (STATIC_URL . 'images/underworld/lava-tiles/black.png');
	}

	public function isPassable ()
	{
		return true;
	}

	protected function getRandom ($base)
	{
		return Dolumar_Map_Map::getRandom ($this->x (), $this->y (), $base);
	}

	protected function getTileDir ()
	{
		$prefix = STATIC_URL . 'images/underworld/lava-tiles/';
		return $prefix;
	}
}
?>
