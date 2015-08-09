<?php
/**
 *  Dolumar, browser based strategy game
 *  Copyright (C) 2009 Thijs Van der Schaeghe
 *  CatLab Interactive bvba, Gent, Belgium
 *  http://www.catlab.eu/
 *  http://www.dolumar.com/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

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