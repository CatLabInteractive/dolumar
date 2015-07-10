<?php
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
