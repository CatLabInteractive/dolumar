<?php
class Dolumar_NewMap_BackgroundManager
	implements Neuron_GameServer_Map_Managers_BackgroundManager
{
	private $map;

	public function __construct (Dolumar_Map $map)
	{
		$this->map = $map;
	}

	public function getLocation (Neuron_GameServer_Map_Location $location, $objectcount = 0)
	{
		// Fetch the image from the old map object
		$l = $this->map->getLocation ($location->x (), $location->y (), $objectcount > 0);
		
		$color = $l->getMapColor ();
		$image = $l->getImage ();
		
		// Make a new sprite
		$out = new Neuron_GameServer_Map_Display_Sprite (STATIC_URL . 'images/tiles/' . $image['image'] . '.gif');
		
		$out->setColor (new Neuron_GameServer_Map_Color ($color[0], $color[1], $color[2]));
		
		return array ($out);
	}
	
	public function getTileSize ()
	{
		return array (200, 100);
	}
}
?>
