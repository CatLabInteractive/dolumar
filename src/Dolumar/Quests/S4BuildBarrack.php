<?php
class Dolumar_Quests_S4BuildBarrack extends Neuron_GameServer_Quest
{
	public function onStart (Neuron_GameServer_Player $player)
	{
		$player->guide->setAllRead ();
		
		$player->guide->addMessage ('s3complete', array (), 'guide', 'happy');
		$player->guide->addMessage ('s4units', array (), 'guide', 'neutral');
		$player->guide->addMessage ('s4buildbarrack', array (), 'guide', 'neutral');
		
	}
	
	public function isFinished (Neuron_GameServer_Player $player)
	{
		$village = $player->getMainVillage ();
		
		// Check if we already have a farm
		return $village->buildings->hasBuilding 
		(
			Dolumar_Buildings_Building::getBuilding (20, $village->getRace ()), 
			true
		);
	}
	
	public function onComplete (Neuron_GameServer_Player $player)
	{
		$player->guide->addMessage ('s4consumption', array (), 'guide', 'neutral');
		$player->guide->addMessage ('s4finished', array (), 'guide', 'neutral');
	}
}
?>
