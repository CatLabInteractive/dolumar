<?php
class Dolumar_Guide
{
	public function __construct (Neuron_GameServer_Player $player)
	{
		$player->events->observe ('register', array (__CLASS__, 'onRegister'));
	}
	
	public static function onRegister (Neuron_GameServer_Player $player)
	{
		// Remove all messages
		$player->guide->removeMessages ();
	
		// Remove all pending quests
		$player->quests->removeQuests ();
		
		// Add the first quest
		$quest = new Dolumar_Quests_S1BuildFarm ();
		$player->quests->addQuest ($quest);
	}
}
?>
