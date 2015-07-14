<?php

class Dolumar_Buildings_TraderTent extends Dolumar_Buildings_Building
{
	public function getMyContent ($input)
	{
		return '<p>Nothing to do here yet.</p>';
	}

	public function getImage ($race = false)
	{
		return 'trader_tent' . mt_rand (1, 6);
	}

	public function canBuildBuilding (Dolumar_Players_Village $village)
	{
		return false;
	}
}