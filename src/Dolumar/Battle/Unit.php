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

/*
	This unit is used in the report class. it represents one 
	troop (well duh).
*/
class Dolumar_Battle_Unit
{
	private $name;
	private $unit;
	private $amount;
	private $stunned = false;
	private $whiped = false;
	private $fled = false;
	
	private $status = 'idle';
	
	private $frontage = 0;

	public function __construct ()
	{
		
	}
	
	/*
		Return the current slot of this unit.
	*/
	public function getCurrentSlot ()
	{
		return $this->oCurrentSlot;
	}
	
	public function setCurrentSlot ($objSlot)
	{
		$this->oCurrentSlot = $objSlot;
		$this->stunned = false;
	}
	
	public function setName ($name)
	{
		$this->name = $name;
	}
	
	public function getName ()
	{
		return $this->name;
	}
	
	public function setUnit ($unit)
	{
		$this->unit = $unit;
	}
	
	public function getUnit ()
	{
		return $this->unit;
	}
	
	public function setAmount ($amount)
	{
		$this->amount = $amount;
	}
	
	public function getAmount ()
	{
		return $this->amount;
	}
	
	public function setStunned ()
	{
		$this->stunned = true;
	}
	
	public function setWhiped ()
	{
		$this->whiped = true;
		//$this->setCurrentSlot (null);
	}
	
	public function setFled ()
	{
		$this->fled = true;
		//$this->setCurrentSlot (null);
	}
	
	public function setAttacking ()
	{
		$this->status = 'attacking';
	}
	
	public function setDying ($amount)
	{
		$this->status = 'dying ' . $amount;
	}
	
	public function setIdle ()
	{
		$this->status = 'idle';
	}
	
	public function getStatus ()
	{
		if ($this->whiped)
		{
			return 'whiped';
		}
		
		elseif ($this->fled)
		{
			return 'fled';
		}
		
		elseif ($this->stunned)
		{
			return 'stunned';
		}
		
		return $this->status;
	}
	
	public function setFrontage ($frontage)
	{
		$this->frontage = $frontage;
	}
	
	public function getFrontage ()
	{
		return $this->frontage;
	}

	public function getOutput ()
	{
		$sOut = $this->getAmount () . ' ' . $this->getUnit ()->getDisplayName ();
		
		if ($squad = $this->getName ())
		{
			$sOut .= ' (' . $squad . ')';
		}
		
		return $sOut;
	}
	
	public function __toString ()
	{
		return $this->getOutput ();
	}
	
	public function __destruct ()
	{
		unset ($this->unit);
		unset ($this->name);
		unset ($this->amount);
	}
}
?>
