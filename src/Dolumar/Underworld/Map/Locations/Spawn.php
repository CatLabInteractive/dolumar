<?php
class Dolumar_Underworld_Map_Locations_Spawn
	extends Dolumar_Underworld_Map_Locations_Floor
{
	private $group;

	public function __construct ($x, $y, Dolumar_Underworld_Map_Spawngroup $group)
	{
		parent::__construct ($x, $y);
		$this->setGroup ($group);
	}

	public function isSide (Dolumar_Underworld_Models_Side $side)
	{
		if ($side->getId () == $this->group->getId ())
		{
			return true;
		}
		return false;
	}

	public function getTile (Dolumar_Underworld_Map_BackgroundManager $map)
	{
		return new Neuron_GameServer_Map_Display_Sprite ($this->getTileDir () . 'spawn.png');
	}

	public function setGroup (Dolumar_Underworld_Map_Spawngroup $group)
	{
		$this->group = $group;
	}

	public function getGroup ()
	{
		return $this->group;
	}
}