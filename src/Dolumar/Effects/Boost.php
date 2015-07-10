<?php
class Dolumar_Effects_Boost extends Dolumar_Effects_Effect implements Dolumar_Players_iBoost
{
	private $iStartDate = NOW;
	private $iEndDate = NOW;
	
	private $nPersistence = 1;
	
	private $boostId;
	
	protected $iDuration = 43200;

	public function procBuildingCost 	($resources, $objBuilding) { return $resources; }
	public function procBuildCost 		($resources, $objBuilding) { return $resources; }
	public function procUpgradeCost 	($resources, $objBuilding) { return $resources;  }
	public function procCapacity 		($resources, $objBuilding) { return $resources; }
	public function procIncome 		($resources, $objBuilding) { return $resources; }
	public function procUnitStats 		(&$stats, $unit) {}
	public function procEffectDifficulty 	($difficulty, $effect) { return $difficulty; }
	public function procDefenseBonus 	($def)	{ return $def; }
	public function procBattleVisible 	($battle) { return true; }
	public function procMoraleCheck 	($morale, $fight) { return $morale; }
	public function procEquipmentDuration	($duration, $item) { return $duration; }
	public function procEquipmentCost	($cost, $item) { return $cost; }
	
	public function onBattleFought ($battle) {}
	
	final public function getType ()
	{
		return 'boost';
	}
	
	public function setBoostId ($id)
	{
		$this->boostId = intval ($id);
	}
	
	public function getBoostId ()
	{
		return $this->boostId;
	}
	
	/*
		If a spell takes a certain amount of time, the persistence
		will take into account the average benefit of the spell.
	*/
	public function setPersistence ($p)
	{
		$this->nPersistence = $p;
	}
	
	public function getPersistence ()
	{
		return $this->nPersistence;
	}
	
	public function setDates ($start, $end)
	{
		$this->iStartDate = $start;
		$this->iEndDate = $end;
	}
	
	public function getStartDate ()
	{
		return $this->iStartDate;
	}
	
	public function getEndDate ()
	{
		return $this->iEndDate;
	}
	
	public function getDuration ()
	{
		return $this->iDuration / GAME_SPEED_EFFECTS;
	}
	
	public function execute ($visible = true, $b = null, $c = null)
	{
		$target = $this->getTarget ();
		if (!isset ($target))
		{
			throw new Neuron_Core_Error ('Target not set.');
		}
		
		$this->doCastSpell ($this->getVillage (), $target, $visible);
	}

	public function requiresTarget ()
	{
		return true;
	}
	
	public function doCastSpell ($objVillage, $objTarget, $visible = true)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		// First: get the id
		$id = $this->getId ();
		
		$db->insert
		(
			'boosts',
			array
			(
				'b_targetId' => $objTarget->getId (),
				'b_fromId' => $objVillage->getId (),
				'b_type' => 'spell',
				'b_ba_id' => $id,
				'b_start' => NOW,
				'b_end' => NOW + $this->getDuration (),
				'b_secret' => $visible ? '0' : '1'
			)
		);
		
		$objTarget->reloadActiveBoosts ();
	}
	
	public function cancel ()
	{
		$db = Neuron_DB_Database::getInstance ();
		$db->query
		("
			UPDATE
				boosts
			SET
				b_end = ".NOW."
			WHERE
				b_id = {$this->getBoostId ()}
		");
		
		$this->getVillage ()->reloadEffects ();
	}
}
?>
