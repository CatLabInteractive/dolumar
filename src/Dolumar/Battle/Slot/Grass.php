<?php
class Dolumar_Battle_Slot_Grass 
{
	private $id;
	private $objVillage;

	public static function getAllSlots ()
	{
		return array
		(
			1 => 'Dolumar_Battle_Slot_Grass',
			2 => 'Dolumar_Battle_Slot_Forest',
			3 => 'Dolumar_Battle_Slot_Swamp',
			4 => 'Dolumar_Battle_Slot_Elevation',
			5 => 'Dolumar_Battle_Slot_Ruins'
		);
	}
	
	public static function getRandomSlot ($id, $village)
	{
		$out = self::getAllSlots ();
		$sName = $out[mt_rand (1, 5)];
		return new $sName ($id, $village);
	}

	public static function getFromId ($id, $oid, $village = null)
	{
		$ids = self::getAllSlots ();
		return isset ($ids[$id]) ? new $ids[$id] ($oid, $village) : false;
	}

	public function __construct ($id, $objVillage = null)
	{
		$this->id = $id;
		$this->objVillage = $objVillage;
	}
	
	public function getSlotId ()
	{
		$ids = self::getAllSlots ();
		$name = get_class ($this);
		
		foreach ($ids as $k => $v)
		{
			if ($name == $v)
			{
				return $k;
			}
		}
		
		return false;
	}
	
	public function getId ()
	{
		return $this->id;
	}
	
	public function getName ()
	{
		return strtolower (substr (get_class ($this), 20));
	}
	
	public function getImageUrl ()
	{
		return IMAGE_URL . 'slots/' . $this->getName () . '.png';
	}
	
	/**
		Return the effects that are affecting this area
	*/
	public function getEffects ()
	{
		return array ();
	}
	
	public function __destruct ()
	{
		
	}
}
?>
