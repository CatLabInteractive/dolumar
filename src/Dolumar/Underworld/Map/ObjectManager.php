<?php
class Dolumar_Underworld_Map_ObjectManager 
	extends Neuron_GameServer_Map_Managers_MapObjectManager
{
	private $mission;
	private $fogofwar;

	public function __construct (Dolumar_Underworld_Models_Mission $mission)
	{
		$this->mission = $mission;
	}
	
	public function setFogOfWar (Dolumar_Underworld_Map_FogOfWar $fog)
	{
		$this->fogofwar = $fog;
	}
	
	private function getExploreStatus (Neuron_GameServer_Map_Location $location)
	{
		if (isset ($this->fogofwar))
		{
			return $this->fogofwar->getExploreStatus ($location);
		}
		
		return Dolumar_Underworld_Map_FogOfWar::VISIBLE;
	}

	/**
	*	Return all objects on one specific location
	*/
	public function getFromLocation (Neuron_GameServer_Map_Location $location, $useFogOfWar = true)
	{
		$area = new Neuron_GameServer_Map_Area ($location, 1);
		
		$objects = $this->getDisplayObjects ($area, $useFogOfWar);
		$out = array ();
		
		foreach ($objects as $v)
		{
			if ($v->getLocation ()->equals ($location))
			{
				$out[] = $v;
			}
		}
		
		return $out;
	}

	/**
	*	Return all display objects that are within $radius
	*	of $location (so basically loading a bubble)
	*/
	public function getDisplayObjects (Neuron_GameServer_Map_Area $area, $useFogOfWar = true)
	{		
		$objects = array ();
		
		$armies = Dolumar_Underworld_Mappers_ArmyMapper::getFromArea ($this->mission, $area);
		
		foreach ($armies as $v)
		{
			if (!$useFogOfWar || $this->getExploreStatus ($v->getLocation ()) == Dolumar_Underworld_Map_FogOfWar::VISIBLE)
			{
				$objects[] = $v;
			}
		}
		
		return $objects;
	}
	
	/**
	*	Move an object to another position
	*/	
	public function move 
	(
		Neuron_GameServer_Map_MapObject $object,
		Neuron_GameServer_Map_Location $location,
		Neuron_GameServer_Map_Date $start, 
		Neuron_GameServer_Map_Date $end
	)
	{
		// not implemented in this game.
		echo "yihaa";
	}
}
?>
