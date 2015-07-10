<?php
class Dolumar_Underworld_Game extends Dolumar_Game
{
	private $mission;

	public function __construct (Dolumar_Underworld_Models_Mission $mission = null)
	{
		$this->mission = $mission;
	}
	
	public function getMap ()
	{
		if (isset ($this->mission))
		{
			return $this->mission->getMap ();	
		}
		else
		{
			return new Neuron_GameServer_Map_Map2D ();
		}
	}
	
	public function getSide (Dolumar_Players_Player $player)
	{
		return $this->mission->getPlayerSide ($player);
	}
	
	public function getServer ()
	{
		return new Dolumar_Players_Server ();
	}
	
	public function getInitialWindows ($objServer)
	{
		$out = array ();

		if (isset ($this->mission))
		{
			$out[] = $objServer->getWindow ('Status');
		}

		else
		{
			$out[] = $objServer->getWindow ('Finished');
		}

		return $out;
	}
	
	/*
		Return a page
	*/
	public function getWindow ($sPage)
	{
		$sClassname = 'Dolumar_Underworld_Windows_'.ucfirst (strtolower ($sPage));
		if (class_exists ($sClassname))
		{
			return new $sClassname ();
		}
		else
		{
			$sClassname = 'Dolumar_Windows_' . ucfirst ($sPage);
			if (class_exists ($sClassname))
			{
				return new $sClassname;
			}
			else
			{
				return false;
			}
		}
	}

	public function getCustomOutput ()
	{
		if (!isset ($this->mission))
		{
			return '<p>The mission is finished.</p>';
		}
	}
}
?>
