<?php
class Dolumar_Underworld_Models_Logger
{
	private $mission;

	public function __construct (Dolumar_Underworld_Models_Mission $mission)
	{
		$this->mission = $mission;
	}

	public function getMission ()
	{
		return $this->mission;
	}

	public function spawn 
	(
		Neuron_GameServer_Player $player,
		Dolumar_Underworld_Models_Army $army,
		Neuron_GameServer_Map_Location $location
	)
	{
		Dolumar_Underworld_Mappers_LoggerMapper::addSpawnLog ($this->getMission (), $player, $army, $location, $player->getMainClan ());
	}

	public function move 
	(
		Neuron_GameServer_Player $player,
		Dolumar_Underworld_Models_Army $army,
		Neuron_GameServer_Map_Location $to,
		Neuron_GameServer_Map_Path $path
	)
	{
		Dolumar_Underworld_Mappers_LoggerMapper::addMoveLog ($this->getMission (), $player, $army, $to, $path);
	}

	public function attack 
	(
		Neuron_GameServer_Player $player,
		Dolumar_Underworld_Models_Army $attacker,
		Dolumar_Underworld_Models_Army $defender,
		Neuron_GameServer_Map_Location $location,
		Neuron_GameServer_Map_Path $path,
		Dolumar_Underworld_Models_Battle $battle
	)
	{
		Dolumar_Underworld_Mappers_LoggerMapper::addAttackLog ($this->getMission (), $player, $attacker, $defender, $location, $path, $battle);
	}

	public function split 
	(
		Neuron_GameServer_Player $player,
		Dolumar_Underworld_Models_Army $oldarmy,
		Dolumar_Underworld_Models_Army $newarmy,
		Neuron_GameServer_Map_Location $newlocation	
	)
	{
		Dolumar_Underworld_Mappers_LoggerMapper::split ($this->getMission (), $player, $oldarmy, $newarmy, $newlocation);
	}

	public function merge 
	(
		Neuron_GameServer_Player $player,
		Dolumar_Underworld_Models_Army $destroyedArmy,
		Dolumar_Underworld_Models_Army $mergedArmy,
		Neuron_GameServer_Map_Location $armylocation,
		Neuron_GameServer_Map_Path $path
	)
	{
		Dolumar_Underworld_Mappers_LoggerMapper::merge ($this->getMission (), $player, $destroyedArmy, $mergedArmy, $armylocation, $path);
	}

	public function withdraw
	(
		Neuron_GameServer_Player $player,
		Dolumar_Underworld_Models_Army $army,
		Neuron_GameServer_Map_Location $location
	)
	{
		Dolumar_Underworld_Mappers_LoggerMapper::withdraw ($this->getMission (), $player, $army, $location);
	}

	public function win (Dolumar_Underworld_Models_Side $side)
	{
		Dolumar_Underworld_Mappers_LoggerMapper::win ($this->getMission (), $side);
	}
}