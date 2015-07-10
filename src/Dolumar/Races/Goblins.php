<?php

class Dolumar_Races_Goblins
	extends Dolumar_Races_Race
{
	public function canPlayerSelect (Neuron_GameServer_Player $player)
	{
		return false;
	}
}