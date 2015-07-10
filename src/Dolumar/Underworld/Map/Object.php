<?php
abstract class Dolumar_Underworld_Map_Object
	extends Neuron_GameServer_Map_MapObject
{
	public function __construct ()
	{
		$id = $this->getId ();
	
		$onclick = "openWindow ('Army', {'id':".$id."});";
		$this->observe ('click', $onclick);
	}
	
	public function getDisplayObject ()
	{
		$url = STATIC_URL . 'images/underworld/objects/team' . (($this->getSide ()->getId () % 4) + 1) . '.png';
	
		$offset = new Neuron_GameServer_Map_Offset (0, 0);
		$image = new Neuron_GameServer_Map_Display_Sprite ($url, $offset);
		
		return $image;
	}
	
	public function getName ()
	{
		return 'Army';
	}
}
?>
