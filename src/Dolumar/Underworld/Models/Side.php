<?php
class Dolumar_Underworld_Models_Side
{
	private $side;
	private $clans = array ();

	public function __construct ($side)
	{
		$this->side = $side;
	}
	
	public function getId ()
	{
		return $this->side;
	}

	public function addClan (Dolumar_Players_Clan $clan)
	{
		$this->clans[] = $clan;
	}

	public function hasClan (Dolumar_Players_Clan $clan)
	{
		foreach ($this->clans as $v)
		{
			if ($clan->equals ($v))
			{
				return true;
			}
		}

		return false;
	}

	public function getClans ()
	{
		return $this->clans;
	}

	public function getDisplayName ()
	{
		return '<span>Side ' . $this->getId () . '</span>';
	}
	
	public function equals (Dolumar_Underworld_Models_Side $side)
	{
		return $this->side == $side->side;
	}
}
