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

abstract class Dolumar_Effects_Battle extends Dolumar_Effects_Effect
{
	protected $COST_BASE = 0;
	protected $COST_PERLEVEL = 20;

	final public function getType ()
	{
		return 'battle';
	}
	
	public final function execute ($fight = null, $attackers = null, $defenders = null)
	{
		$out = $this->doAction ($fight, $attackers, $defenders);
		
		if (!is_array ($out))
		{
			$out = array ();
		}
		
		return $out;
	}
	
	abstract public function doAction ($fight, &$attackers, &$defenders);
	
	public function getBattleLog ($report, $unit, $probability, $data, $html = true)
	{
		return 'Suddenly, nothing happens. ('.str_replace ("\n", '', print_r ($data, true)).')';
	}
	
	public function getFailedLog ($objReport, $unit, $probability, $data = array ())
	{
		$text = Neuron_Core_Text::__getInstance ();
		return Neuron_Core_Tools::putIntoText 
		(
			$text->get ('onFailed', 'battle', 'effects'),
			array 
			(
				'unit' => $unit->getName (),
				'spell' => $this->getName (),
				'probability' => $probability
			)
		);
	}
	
	protected function getRandomUnit ($troops)
	{
		if (count ($troops) > 0)
		{
			// Fetch a random troop
			$temp = array_values ($troops);
			shuffle ($temp);

			return $temp[0];
		}
		else
		{
			return false;
		}
	}
	
	protected function doRandomDamage ($troops, $amount, $type = 'defMag', $skipTurn = 0)
	{
		if ($troop = $this->getRandomUnit ($troops))
		{
			return $this->doDamage ($troop, $amount, $type, $skipTurn);
		}
		else
		{
			return array (0);
		}
	}
	
	protected function doDamage ($unit, $damage, $type = 'defMag', $skipTurn = 0)
	{		
		// Get the amount of defending units
		$defending = $unit->getAmount () - $unit->getKillUnitsQue ();
	
		// Don't forget the defense!
		$damage -= $damage * $unit->getStat ($type) / 100;
	
		// Now how many units is that?
		$casualties = min ($defending, $damage / $unit->getStat ('hp'));
	
		$casualties = $unit->queKillUnits ($casualties);
		
		// Skip a turn!
		if ($skipTurn > 0)
		{
			$unit->addSkipTurns ($skipTurn);
		}
		
		return array ($unit, $casualties);
	}
	
	public function getCost ($objUnit, $objTarget, $cost = null)
	{
		if (!isset ($cost))
		{
			$cost = array
			(
				'gems' => $this->COST_BASE + ($this->COST_PERLEVEL * $this->getLevel ())
			);
		}
		
		return $cost;
	}
}