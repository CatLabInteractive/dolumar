<?php
class Dolumar_Underworld_Models_Objectives_DorDaedeloth
	extends Dolumar_Underworld_Models_Objectives_TakeAndHold
{
	/**
	* Check the requirements of the checkpoint
	*/
	public function isValidSpawnPoint (Dolumar_Underworld_Models_Side $side, Dolumar_Underworld_Map_Locations_Location $location)
	{
		return $this->checkSpawnpointRequirements ($side, $location);
	}

	public function getName ()
	{
		return 'Dor Daedeloth';
	}

	protected function winnerBenefits (Dolumar_Underworld_Models_Side $side)
	{
		$server = Neuron_GameServer::getServer ();

		// Load clans
		$side = $this->getMission ()->getSideFromSide ($side);

		foreach ($side->getClans () as $v)
		{
			$winner = $v->getId ();
		}

		$server->setData ('winner', $winner);
		$server->setData ('gamestate', Dolumar_Players_Server::GAMESTATE_ENDGAME_FINISHED);
	}

	public function getHoldDuration ()
	{
		//return 60 * 1;
		return 60 * 60 * 24 * 7;
	}
}