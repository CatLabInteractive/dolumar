<?php
class Dolumar_Quests_S1BuildFarm extends Neuron_GameServer_Quest
{
	public function onStart (Neuron_GameServer_Player $player)
	{
		$player->guide->setAllRead ();
		
		$player->guide->addMessage ('welcome', array (), 'guide', 'proud');
		$player->guide->addMessage ('interface', array (), 'guide', 'neutral');
		$player->guide->addMessage ('resources', array (), 'guide', 'neutral', 'economics');
		$player->guide->addMessage ('s1buildfarm', array (), 'guide', 'happy', 'build');
	}
	
	public function isFinished (Neuron_GameServer_Player $player)
	{
		$village = $player->getMainVillage ();
		
		// Check if we already have a farm
		return $village->buildings->hasBuilding 
		(
			Dolumar_Buildings_Building::getBuilding (10, $village->getRace ()), 
			true
		);
	}
	
	public function onComplete (Neuron_GameServer_Player $player)
	{
		$quest = new Dolumar_Quests_S2BuildLumber ();
		$player->quests->addQuest ($quest);
	}
}
?>
