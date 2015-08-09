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

class Dolumar_Underworld_Models_Battle
{
	public static function fight 
	(
		Dolumar_Underworld_Models_Army $attacker,
		Dolumar_Underworld_Models_Army $target
	)
	{
		$dummy = new Dolumar_Players_DummyVillage ();
		
		$slots = $target->getSlots ();
		
		$attUnits = $attacker->getUnits ($slots);
		$defUnits = $target->getUnits ($slots);
		
		$logger = new Dolumar_Battle_Logger ();
	
		$fight = new Dolumar_Battle_Fight ($dummy, $dummy, $attUnits, $defUnits, $slots, array (), $logger);
		
		$fight->getResult ();
		
		$report = Dolumar_Underworld_Models_Report::getFromLogger ($logger);
		
		// No need to duplicate the report anymore
		//$report = Dolumar_Battle_Report::unserialize ($report->serialize ());
		
		// Create battle
		$battle = new Dolumar_Underworld_Models_Battle (null);
		
		$battle->setReport ($report);
		
		$battle->setAttacker ($attacker);
		$battle->setDefender ($target);
		
		$battle->setStartdate (NOW);
		$battle->setEnddate (NOW + $report->getDuration ());

		// Kill kill kill kill kill the units
		$fight->killUnits ($fight);
		
		return $battle;
	}
	
	private $id;
	
	private $attacker;
	private $defender;
	
	private $report;
	
	private $startdate;
	private $endtime;

	private $attackerside;
	private $defenderside;

	private $attackerlocation;
	private $defenderlocation;
	
	public function __construct ($id)
	{
		$this->setId ($id);
	}
	
	public function setId ($id)
	{
		$this->id = $id;
	}
	
	public function getId ()
	{
		return $this->id;
	}
	
	public function setAttacker (Dolumar_Underworld_Models_Army $attacker)
	{
		$this->attacker = $attacker;
	}
	
	public function getAttacker ()
	{
		return $this->attacker;
	}
	
	public function setDefender (Dolumar_Underworld_Models_Army $defender)
	{
		$this->defender = $defender;
	}
	
	public function getDefender ()
	{
		return $this->defender;
	}
	
	public function setStartdate ($startdate)
	{
		$this->startdate = $startdate;
	}

	public function getStartdate ()
	{
		return $this->startdate;
	}
	
	public function setEnddate ($endtime)
	{
		$this->endtime = $endtime;
	}

	public function getEnddate ()
	{
		return $this->endtime;
	}
	
	public function setReport (Dolumar_Battle_Report $report)
	{
		$this->report = $report;
	}
	
	public function getReport ()
	{
		return $this->report;
	}
	
	/**
	*	Check if someone has won
	*/
	public function isWinner (Dolumar_Underworld_Models_Army $army)
	{
		if ($this->getAttacker ()->equals ($army))
		{
			return $this->isAttackerWinner ();
		}
		else
		{
			return !$this->isAttackerWinner ();
		}
	}
	
	public function isAttackerWinner ()
	{
		return $this->getReport ()->getVictory () > 0;
	}

	public function isVictory (Dolumar_Underworld_Models_Side $side)
	{
		if ($this->getAttackerSide ()->equals ($side))
		{
			return $this->isAttackerWinner ();
		}	
		else
		{
			return !$this->isAttackerWinner ();
		}
	}

	public function isAttack (Dolumar_Underworld_Models_Side $side)
	{
		return $this->getAttackerSide ()->equals ($side);
	}

	public function isDefense (Dolumar_Underworld_Models_Side $side)
	{
		return $this->getDefenderSide ()->equals ($side);
	}

	public function setAttackerSide (Dolumar_Underworld_Models_Side $side)
	{
		$this->attackerside = $side;
	}

	public function getAttackerSide ()
	{
		return $this->attackerside;
	}

	public function setDefenderSide (Dolumar_Underworld_Models_Side $side)
	{
		$this->defenderside = $side;
	}

	public function getDefenderSide ()
	{
		return $this->defenderside;
	}

	public function getAttackerLocation ()
	{
		return $this->attackerlocation;	
	}

	public function getDefenderLocation ()
	{
		return $this->defenderlocation;
	}

	public function setAttackerLocation (Neuron_GameServer_Map_Location $location)
	{
		$this->attackerlocation = $location;
	}

	public function setDefenderLocation (Neuron_GameServer_Map_Location $location)
	{
		$this->defenderlocation = $location;
	}
	
	// Data (for serialization and... well, to put in database
	public function getData ()
	{
		return array
		(
			'uat_id' => $this->getId (),
			'uat_attacker' => $this->attacker->getId (),
			'uat_defender' => $this->defender->getId (),
			'startdate' => $this->startdate,
			'enddate' => $this->endtime,
			'uat_fightlog' => $this->getReport ()->serialize ()
		);
	}
	
	public function setData ($data)
	{
		$this->setId ($data['uat_id']);

		if (!empty ($data['uat_attackerr']))
		{
			$this->attacker = Dolumar_Underworld_Mappers_ArmyMapper::getFromId ($data['uat_attacker']);
		}

		if (!empty ($data['uat_defender']))
		{
			$this->defender = Dolumar_Underworld_Mappers_ArmyMapper::getFromId ($data['uat_defender']);
		}

		$report = Dolumar_Underworld_Models_Report::unserialize ($data['uat_fightlog']);
		$report->setId ($this->getId ());

		$this->startdate = $data['startdate'];
		$this->endtime = $data['enddate'];
		$this->setReport ($report);

		$this->setAttackerSide (new Dolumar_Underworld_Models_Side ($data['uat_attacker_side']));
		$this->setDefenderSide (new Dolumar_Underworld_Models_Side ($data['uat_defender_side']));

		$this->setAttackerLocation (new Neuron_GameServer_Map_Location ($data['uat_from_x'], $data['uat_from_y']));
		$this->setDefenderLocation (new Neuron_GameServer_Map_Location ($data['uat_to_x'], $data['uat_to_y']));
	}

	public function getHistoricalArmyData (Dolumar_Underworld_Models_Army $army)
	{
		$report = $this->getReport ();
		$units = $report->getUnits ();

		if ($this->getAttackerSide ()->equals ($army->getSide ()))
		{
			// Attacker
			return $units['attacking'];
		}

		else if ($this->getDefenderSide ()->equals ($army->getSide ()))
		{
			// Defender
			return $units['defending'];
		}

		return null;
	}
}
?>