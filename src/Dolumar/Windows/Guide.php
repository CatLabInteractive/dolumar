<?php
class Dolumar_Windows_Guide extends Neuron_GameServer_Windows_Guide
{
	protected function getClassname ($character, $mood)
	{
		$race = null;
		
		if ($player = Neuron_GameServer::getPlayer ())
		{
			if ($village = $player->getMainVillage ())
			{
				$race = $village->getRace ()->getName ();
			}
		}

		return $race . ' ' . $character . ' ' . $mood;
	}
}
?>
