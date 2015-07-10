<?php
class Dolumar_Buildings_WizardTower extends Dolumar_Buildings_SpecialUnits
{
	public function getSpecialUnit ()
	{
		return new Dolumar_SpecialUnits_Mages ($this);
	}
	
	public function getSpecialUnitCapacity ()
	{
		return 1;
	}

	protected function initRequirements ()
	{
		$this->addRequiresBuilding (14);
	}	
	
	protected function getAvailableEffects ()
	{
		$runes = $this->getUsedRunes ();
		$runes = array_keys ($runes);
		
		$out = array ();
		foreach ($runes as $v)
		{
			switch ($v)
			{
				case 'earth':
					$out = array_merge ($out, $this->getEarthSpells ());
				break;
				
				case 'fire':
					$out = array_merge ($out, $this->getFireSpells ());
				break;
				
				case 'wind':
					$out = array_merge ($out, $this->getWindSpells ());
				break;
				
				case 'water':
					$out = array_merge ($out, $this->getWaterSpells ());
				break;
			}
		}
		
		return $out;
	
		/*
		return array 
		(				
			new Dolumar_Effects_Boost_Shadows (1),
			new Dolumar_Effects_Boost_Shadows (2),
			new Dolumar_Effects_Boost_Shadows (3),
			new Dolumar_Effects_Boost_Shadows (4),
			new Dolumar_Effects_Boost_Shadows (5),
			
			new Dolumar_Effects_Boost_Invisibility (),
		);
		*/
	}
	
	private function getWindSpells ()
	{
		return array
		(
			// 5 levels wood bonus			
			new Dolumar_Effects_Boost_FavorOfTheWoods (1),
			new Dolumar_Effects_Boost_FavorOfTheWoods (2),
			new Dolumar_Effects_Boost_FavorOfTheWoods (3),
			new Dolumar_Effects_Boost_FavorOfTheWoods (4),
			new Dolumar_Effects_Boost_FavorOfTheWoods (5),
			
			// Dispel
			new Dolumar_Effects_Instant_Dispel (),
			
			new Dolumar_Effects_Boost_Haste (1),
			new Dolumar_Effects_Boost_Haste (2),
			new Dolumar_Effects_Boost_Haste (3),
			new Dolumar_Effects_Boost_Haste (4),
			new Dolumar_Effects_Boost_Haste (5),
			
			new Dolumar_Effects_Boost_SummonGhosts (1),
			new Dolumar_Effects_Boost_SummonGhosts (2),
			new Dolumar_Effects_Boost_SummonGhosts (3),
			new Dolumar_Effects_Boost_SummonGhosts (4),
			new Dolumar_Effects_Boost_SummonGhosts (5),
			
			new Dolumar_Effects_Instant_Tornado (1),
			new Dolumar_Effects_Instant_Tornado (2),
			new Dolumar_Effects_Instant_Tornado (3),
			new Dolumar_Effects_Instant_Tornado (4),
			new Dolumar_Effects_Instant_Tornado (5),
			
			/*
				Battle spells
			*/
			new Dolumar_Effects_Battle_Fear (1),
			new Dolumar_Effects_Battle_Fear (2),
			new Dolumar_Effects_Battle_Fear (3),
			new Dolumar_Effects_Battle_Fear (4),
			new Dolumar_Effects_Battle_Fear (5),
			
			new Dolumar_Effects_Battle_Cold (1),
			new Dolumar_Effects_Battle_Cold (2),
			new Dolumar_Effects_Battle_Cold (3),
			new Dolumar_Effects_Battle_Cold (4),
			new Dolumar_Effects_Battle_Cold (5)
		);
	}
	
	private function getFireSpells ()
	{
		return array
		(
			// 5 levels gold bonus			
			new Dolumar_Effects_Boost_GoldenMines (1),
			new Dolumar_Effects_Boost_GoldenMines (2),
			new Dolumar_Effects_Boost_GoldenMines (3),
			new Dolumar_Effects_Boost_GoldenMines (4),
			new Dolumar_Effects_Boost_GoldenMines (5),
			
			// 5 levels iron bonus			
			new Dolumar_Effects_Boost_ShiningOre (1),
			new Dolumar_Effects_Boost_ShiningOre (2),
			new Dolumar_Effects_Boost_ShiningOre (3),
			new Dolumar_Effects_Boost_ShiningOre (4),
			new Dolumar_Effects_Boost_ShiningOre (5),
			
			new Dolumar_Effects_Boost_CityOfLight (1),
			new Dolumar_Effects_Boost_CityOfLight (2),
			new Dolumar_Effects_Boost_CityOfLight (3),
			new Dolumar_Effects_Boost_CityOfLight (4),
			new Dolumar_Effects_Boost_CityOfLight (5),
			
			new Dolumar_Effects_Boost_FireWeapons (1),
			new Dolumar_Effects_Boost_FireWeapons (2),
			new Dolumar_Effects_Boost_FireWeapons (3),
			new Dolumar_Effects_Boost_FireWeapons (4),
			new Dolumar_Effects_Boost_FireWeapons (5),
			
			new Dolumar_Effects_Boost_HotSmithyFires (1),
			new Dolumar_Effects_Boost_HotSmithyFires (2),
			new Dolumar_Effects_Boost_HotSmithyFires (3),
			new Dolumar_Effects_Boost_HotSmithyFires (4),
			new Dolumar_Effects_Boost_HotSmithyFires (5),
			
			/*
				Battle spells
			*/
			// 5 levels fireball
			new Dolumar_Effects_Battle_Fireball (1),
			new Dolumar_Effects_Battle_Fireball (2),
			new Dolumar_Effects_Battle_Fireball (3),
			new Dolumar_Effects_Battle_Fireball (4),
			new Dolumar_Effects_Battle_Fireball (5),
			
			new Dolumar_Effects_Battle_MeteorRain (1),
			new Dolumar_Effects_Battle_MeteorRain (2),
			new Dolumar_Effects_Battle_MeteorRain (3),
			new Dolumar_Effects_Battle_MeteorRain (4),
			new Dolumar_Effects_Battle_MeteorRain (5),
			
		);
	}
	
	private function getEarthSpells ()
	{
		return array
		(
			// 5 levels stone bonus			
			new Dolumar_Effects_Boost_RollingRocks (1),
			new Dolumar_Effects_Boost_RollingRocks (2),
			new Dolumar_Effects_Boost_RollingRocks (3),
			new Dolumar_Effects_Boost_RollingRocks (4),
			new Dolumar_Effects_Boost_RollingRocks (5),
			
			// 3 levels resources
			new Dolumar_Effects_Boost_DryLands (1),
			new Dolumar_Effects_Boost_DryLands (2),
			new Dolumar_Effects_Boost_DryLands (3),
			new Dolumar_Effects_Boost_DryLands (4),
			new Dolumar_Effects_Boost_DryLands (5),
			
			new Dolumar_Effects_Boost_MagicalShield (1),
			new Dolumar_Effects_Boost_MagicalShield (2),
			new Dolumar_Effects_Boost_MagicalShield (3),
			new Dolumar_Effects_Boost_MagicalShield (4),
			new Dolumar_Effects_Boost_MagicalShield (5),
			
			new Dolumar_Effects_Boost_Earthquake (1),
			new Dolumar_Effects_Boost_Earthquake (2),
			new Dolumar_Effects_Boost_Earthquake (3),
			new Dolumar_Effects_Boost_Earthquake (4),
			new Dolumar_Effects_Boost_Earthquake (5),
			
			new Dolumar_Effects_Boost_SandStorm (1),
			new Dolumar_Effects_Boost_SandStorm (2),
			new Dolumar_Effects_Boost_SandStorm (3),
			new Dolumar_Effects_Boost_SandStorm (4),
			new Dolumar_Effects_Boost_SandStorm (5),
			
			/*
				Battle spells
			*/
			new Dolumar_Effects_Battle_CrackedEarth (1),
			new Dolumar_Effects_Battle_CrackedEarth (2),
			new Dolumar_Effects_Battle_CrackedEarth (3),
			new Dolumar_Effects_Battle_CrackedEarth (4),
			new Dolumar_Effects_Battle_CrackedEarth (5),
			
			new Dolumar_Effects_Battle_Quicksand (1),
			new Dolumar_Effects_Battle_Quicksand (2),
			new Dolumar_Effects_Battle_Quicksand (3),
			new Dolumar_Effects_Battle_Quicksand (4),
			new Dolumar_Effects_Battle_Quicksand (5),
		);
	}
	
	private function getWaterSpells ()
	{
		return array
		(
			// 5 levels rainseason			
			new Dolumar_Effects_Boost_RainSeason (1),
			new Dolumar_Effects_Boost_RainSeason (2),
			new Dolumar_Effects_Boost_RainSeason (3),
			new Dolumar_Effects_Boost_RainSeason (4),
			new Dolumar_Effects_Boost_RainSeason (5),
			
			new Dolumar_Effects_Instant_OpenPortal (),
			
			new Dolumar_Effects_Instant_MagicMirror (),
			
			new Dolumar_Effects_Instant_Floods (1),
			new Dolumar_Effects_Instant_Floods (2),
			new Dolumar_Effects_Instant_Floods (3),
			new Dolumar_Effects_Instant_Floods (4),
			new Dolumar_Effects_Instant_Floods (5),
			
			new Dolumar_Effects_Boost_LightningStorm (1),
			new Dolumar_Effects_Boost_LightningStorm (2),
			new Dolumar_Effects_Boost_LightningStorm (3),
			new Dolumar_Effects_Boost_LightningStorm (4),
			new Dolumar_Effects_Boost_LightningStorm (5),
			
			/*
				Battle spells
			*/
			new Dolumar_Effects_Battle_SoakingRains (1),
			new Dolumar_Effects_Battle_SoakingRains (2),
			new Dolumar_Effects_Battle_SoakingRains (3),
			new Dolumar_Effects_Battle_SoakingRains (4),
			new Dolumar_Effects_Battle_SoakingRains (5),
			
			new Dolumar_Effects_Battle_LightningBolt (1),
			new Dolumar_Effects_Battle_LightningBolt (2),
			new Dolumar_Effects_Battle_LightningBolt (3),
			new Dolumar_Effects_Battle_LightningBolt (4),
			new Dolumar_Effects_Battle_LightningBolt (5),
			
			new Dolumar_Effects_Battle_Frost (1),
			new Dolumar_Effects_Battle_Frost (2),
			new Dolumar_Effects_Battle_Frost (3),
			new Dolumar_Effects_Battle_Frost (4),
			new Dolumar_Effects_Battle_Frost (5),
		);
	}
	
	protected function getFreeEffects ()
	{
		$out = array
		(
			new Dolumar_Effects_Boost_RainSeason (),
			new Dolumar_Effects_Battle_Fireball ()
		);

		// End game? End effect!
		$server = Neuron_GameServer::getServer ();
		if ($server->getData ('gamestate') == Dolumar_Players_Server::GAMESTATE_ENDGAME_STARTED)
		{
			$out[] = new Dolumar_Effects_Instant_DorDaedeloth ();
		}

		return $out;
	}
}
?>
