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

class Dolumar_Effects_Battle_MeteorRain extends Dolumar_Effects_Battle
{
	protected $iLevel;

	public function __construct ($iLevel = 1)
	{
		$this->iLevel = $iLevel;
	}
	
	private function getDamageFromLevel ()
	{
		return 100 + ($this->getLevel () * 100);
	}
	
	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'damage' => $this->getDamageFromLevel ()
			)
		);
	}
	
	/*
		Meteor rain
	*/
	public function doAction ($fight, &$attackers, &$defenders)
	{
		$damage = $this->getDamageFromLevel ();
		
		// Deel de damage over alle defenders
		$deffies = count ($defenders);
		
		//$perunit = ceil ($damage / $deffies);
		$perunit = $damage;
		
		$i = 0;
		
		$casualties = 0;
		foreach ($defenders as $v)
		{
			$data = $this->doDamage ($v, $perunit);
			
			$casualties += $data[1];
			
			$i ++;
			
			if ($i >= 3)
			{
				break;
			}
		}
		
		return array ($deffies, $casualties);
		
		//return $this->doRandomDamage ($defenders, $damage, 'defMag', $this->iLevel);
	}
	
	public function getBattleLog ($report, $unit, $probability, $data, $html = true)
	{
		$text = Neuron_Core_Text::__getInstance ();
		return Neuron_Core_Tools::putIntoText
		(
			$text->get ('onSuccess', $this->getClassName (), 'effects'),
			array
			(
				'squads' => $data[0],
				'casualties' => $data[1],
				'unit' => $unit->getName (),
				'probability' => $probability,
				'level' => $this->getLevel (),
				'spell' => $html ? $this->getDisplayName () : $this->getName ()
			)
		);
	}
	
	public function getCost ($objUnit, $objTarget, $cost = null)
	{
		switch ($this->getLevel ())
		{
			case 1:
			default:
				$gems = 30;
			break;
			
			case 2:
				$gems = 50;
			break;
			
			case 3:
				$gems = 70;
			break;
			
			case 4:
				$gems = 90;
			break;
			
			case 5:
				$gems = 110;
			break;
		}
	
		if (!isset ($cost))
		{
			$cost = array
			(
				'gems' => $gems
			);
		}
		
		return $cost;
	}
	
	public function getDifficulty ($iBaseAmount = 40)
	{
		return 50;
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 3;
	}
}
?>
