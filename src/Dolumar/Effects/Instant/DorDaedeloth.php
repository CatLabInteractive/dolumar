<?php
class Dolumar_Effects_Instant_DorDaedeloth extends Dolumar_Effects_Instant
{
	public function execute ($a = null, $b = null, $c = null)
	{
		$village = $this->getVillage ();

		// Add mission
		$mission = new Dolumar_Underworld_Models_Mission (null);
		$mission->setMapName ('dordaedeloth.map');
		$mission->setObjectiveName ('DorDaedeloth');

		Dolumar_Underworld_Mappers_MissionMapper::create ($mission, true);

		// Update server status
		$server = Neuron_GameServer::getServer ();
		$server->setData ('gamestate', Dolumar_Players_Server::GAMESTATE_ENDGAME_RUNNING);

		// And notify all players
		Neuron_GameServer_Player_Guide::addPublicMessage 
		(
			'end_casted', 
			array (
				$village->getOwner (),
				$village
			), 
			'guide', 
			'neutral'
		);
	}

	public function getCost ($objUnit, $objTarget, $cost = null)
	{
		return array (
			'gems' => 25000,
			'woods' => 100000,
			'stone' => 100000,
			'iron' => 100000,
			'grain' => 100000,
			'gold' => 100000
		);
	}

	public function getDifficulty ($iBaseAmount = 40)
	{
		return 150;
	}
}
?>
