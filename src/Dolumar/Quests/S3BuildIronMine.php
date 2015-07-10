<?php
class Dolumar_Quests_S3BuildIronMine extends Neuron_GameServer_Quest
{
	public function onStart (Neuron_GameServer_Player $player)
	{
		$player->guide->setAllRead ();
		
		$player->guide->addMessage ('s2acomplete', array (), 'guide', 'happy');
		$player->guide->addMessage ('s3runes', array (), 'guide', 'neutral');
		$player->guide->addMessage ('s3buildiron', array (), 'guide', 'happy');
		
	}
	
	public function isFinished (Neuron_GameServer_Player $player)
	{
		$village = $player->getMainVillage ();
		
		// Check if we already have a farm
		return $village->buildings->hasBuilding 
		(
			Dolumar_Buildings_Building::getBuilding (13, $village->getRace ()), 
			true
		);
	}
	
	public function onComplete (Neuron_GameServer_Player $player)
	{
		$quest = new Dolumar_Quests_S4BuildBarrack ();
		$player->quests->addQuest ($quest);
	}
}
?>
