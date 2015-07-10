<?php
class Dolumar_NewMap_Object
	extends Neuron_GameServer_Map_MapObject
{
	private $building;

	public function __construct (Dolumar_Buildings_Building $building)
	{
		$this->building = $building;
		
		$id = $this->building->getId ();
		$onclick = "openWindow ('building', {'bid':".$id."});";
		
		$this->observe ('click', $onclick);
	}
	
	public function getDisplayObject ()
	{
		return $this->building->getDisplayObject ();
	}
	
	public function getName ()
	{
		return $this->building->getName ();
	}
}
?>
