<?php
class Dolumar_Underworld_Map_Locations_Explored 
	extends Dolumar_Underworld_Map_Locations_Location
{
	public function isPassable ()
	{
		return false;
	}

	private function isExplored (Dolumar_Underworld_Map_BackgroundManager $map, Neuron_GameServer_Map_Location $location)
	{
		$status = $map->getExploreStatus ($location);
		return $status !== Dolumar_Underworld_Map_FogOfWar::VISIBLE;
	}
	
	public function getTile (Dolumar_Underworld_Map_BackgroundManager $map)
	{	
		$lb = intval ($this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () - 1, $this->y () + 0)));
		$rb = intval ($this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () + 0, $this->y () + 1)));
		$ro = intval ($this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () + 1, $this->y () + 0)));
		$lo = intval ($this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () + 0, $this->y () - 1)));

		$l = intval ($lb && $rb && $this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () - 1, $this->y () + 1)));
		$d = intval ($rb && $ro && $this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () + 1, $this->y () + 1)));
		$r = intval ($ro && $lo && $this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () + 1, $this->y () - 1)));
		$u = intval ($lb && $lo && $this->isExplored ($map, new Neuron_GameServer_Map_Location ($this->x () - 1, $this->y () - 1)));

		/*
		$hLinksBoven = !($l && $b && $lb->isWater ()) ? '0' : '1';
		$hRechtsBoven = !($r && $b && $rb->isWater ()) ? '0' : '1';
		$hRechtsOnder = !($r && $o && $ro->isWater ()) ? '0' : '1';
		$hLinksOnder = !($l && $o && $lo->isWater ()) ? '0' : '1';
		*/

		$bits  = $l . $rb . $d;
		$bits .= $lb . $ro;
		$bits .= $u . $lo . $r;

		return new Neuron_GameServer_Map_Display_Sprite (STATIC_URL . 'images/underworld/myst/' . 'myst_' . $bits . '.png');
	}
}
?>
