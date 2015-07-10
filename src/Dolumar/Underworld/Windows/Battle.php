<?php
class Dolumar_Underworld_Windows_Battle
	extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$this->setClassName ('underworld battle');
		$this->setSize ('350px', '360px');
		$this->setOnload ('initBattleWindow');
		$this->setTitle ('Battles');
	}

	public function getContent ()
	{
		$player = Neuron_GameServer::getPlayer ();

		if (!$player)
		{
			return '<p>Please login.</p>';
		}

		$id = $this->getInput ('report');

		if (isset ($id))
		{
			return $this->getReport ($id);
		}
		else
		{
			return $this->getOverview ();
		}
	}

	private function getReport ($id)
	{
		$player = Neuron_GameServer::getPlayer ();

		$report = Dolumar_Underworld_Mappers_BattleMapper::getFromId ($id);

		if (!isset ($report))
		{
			return '<p>Report not found.</p>';
		}

		return $report->getReport ()->getReport (null, $this->getInput ('log'), $this->getInput ('fightlog') == '1', true);
	}

	private function getOverview ()
	{
		$player = Neuron_GameServer::getPlayer ();

		$map = $this->getServer ()->getMap ();
		if (! ($map instanceof Dolumar_Underworld_Map_Map))
		{
			$this->reloadWindow ();
			return '<p>Mission is finished.</p>';
		}

		$mission = $map->getMission ();

		$side = $mission->getPlayerSide ($player);

		$total = Dolumar_Underworld_Mappers_BattleMapper::countFromSide ($mission, $side);
		$battles = Dolumar_Underworld_Mappers_BattleMapper::getFromSide ($mission, $side);

		$page = new Neuron_Core_Template ();

		$page->set ('side', $side);

		foreach ($battles as $v)
		{
			$page->addListValue ('battles', $v);
		}

		return $page->parse ('dolumar/underworld/windows/battle.phpt');
	}
}