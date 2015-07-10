<?php
class Dolumar_NewMap_Map 
	extends Neuron_GameServer_Map_Map2D
{
	public function getInitialLocation ()
	{
		$player = Neuron_GameServer::getPlayer ();
		if ($player)
		{
			return $player->getHomeLocation ();
		}
		else
		{
			return null;
		}
	}
}
?>
