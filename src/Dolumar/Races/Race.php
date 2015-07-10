<?php

class Dolumar_Races_Race
{
	private $race;

	public static function getRaces ()
	{
		return array
		(
			1 => 'Humans',
			2 => 'DarkElves',
			3 => 'Goblins'
		);
	}

	public static function getRaceObjects ()
	{
		$o = array ();
		foreach (self::getRaces () as $k => $v)
		{
			$o[] = self::getRace ($k);
		}
		return $o;
	}
	
	public static function getFromId ($id)
	{
		return self::getRace ($id);
	}
	
	public static function getRaceFromId ($id)
	{	
		$o = self::getRaces ();
		
		if (isset ($o[$id]))
		{
			return $o[$id];
		}
		
		else 
		{
			return $o[1];
		}
	}
	
	public static function getRace ($id)
	{
		if (is_numeric ($id))
		{
			$id = self::getRaceFromId ($id);
		}
		
		if (class_exists ('Dolumar_Races_'.$id))
		{
			eval ('$a = new Dolumar_Races_'.$id.' (\''.$id.'\');');
		}
		
		else {
			$a = new Dolumar_Races_Race ($id);
		}
		
		return $a;
	}
	
	public function __construct ($id)
	{
		$this->race = $id;
	}
	
	public function getId ()
	{
		$races = Dolumar_Races_Race::getRaces ();
		
		$o = 1;
		foreach ($races as $k => $v)
		{
			if ($v == $this->getName ())
			{
				$o = $k;
			}
		}
		
		return $o;
	}
	
	public function getName ()
	{
		return $this->race;
	}
	
	public function getRaceName ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		return $text->get ($this->race, 'races', 'races', $this->race);
	}
	
	public function getDisplayName ()
	{
		return $this->getRaceName ();
	}
	
	public function readyToBuild ($village)
	{
		$buildings = $village->buildings->getBuildingBuildings ();

		// Get town center level
		/*
		$level = $village->buildings->getBuildingLevel (1);

		// Calculate max actions
		return count ($buildings) < min (1 + ($level), 6);
		*/
		
		return count ($buildings) < 2;
	}
	
	public function equals ($race)
	{
		if ($this->getId () == $race->getId ())
		{
			return true;
		}
		
		return false;
	}

	public function canPlayerSelect (Neuron_GameServer_Player $player)
	{
		return true;
	}
}
