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

class Dolumar_Buildings_Mill extends Dolumar_Buildings_Farm
{
	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		$this->addRequiresBuilding (10);
	}	
	
	public function calculateIncome ($level = null)
	{
		$numbers = $this->getMillNumbers ($level);
		return $numbers['income'];
	}

	/*
		A mill does not use map tiles or honour: all mills share the
		grain of all farms. Every farm level adds 1.7 x 120 grain to share,
		divided over the farm and mill levels together.
		$level is this mill's level; another level than the current one
		also changes the total of mill levels (for the upgrade preview).
	*/
	private function getMillNumbers ($level = null, $extraFarms = 0, $extraMills = 0)
	{
		$current = $this->getLevel ();
		if (!isset ($level))
		{
			$level = $current;
		}

		$farms = $this->getVillage()->buildings->getBuildingLevelSum (10) + $extraFarms;
		$mills = $this->getVillage()->buildings->getBuildingLevelSum (16) + ($level - $current) + $extraMills;

		$income = $this->INCOME * GAME_SPEED_RESOURCES;

		return array
		(
			'farms' => $farms,
			'mills' => $mills,
			'income' => floor ((1.7 * $income * $farms * $level) / max (1, $farms + $mills)),

			// What all mills of this village produce together.
			'allMills' => floor ((1.7 * $income * $farms * $mills) / max (1, $farms + $mills))
		);
	}

	/*
		Mills do not use map tiles.
	*/
	public function getPlacementPreview (Dolumar_Players_Village $village)
	{
		return null;
	}

	public function getIncomeBreakdown ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('buildings');
		$text->setSection ('producing');

		$numbers = $this->getMillNumbers ();

		$rows = array ();
		$rows[] = array
		(
			'label' => Neuron_Core_Tools::putIntoText
			(
				$text->get ('millShare'),
				array
				(
					'level' => $this->getLevel (),
					'farms' => $numbers['farms'],
					'mills' => $numbers['mills']
				)
			),
			'amount' => $numbers['income']
		);

		$this->addEffectRows ($rows, $numbers['income'], $numbers['income']);

		return $rows;
	}

	/*
		What another mill level or another farm level would add through
		the mills (before technologies and spells).
	*/
	protected function getIncomeAdvice ()
	{
		$text = Neuron_Core_Text::__getInstance ();

		$now = $this->getMillNumbers ();
		$moreMills = $this->getMillNumbers (null, 0, 1);
		$moreFarms = $this->getMillNumbers (null, 1, 0);

		return Neuron_Core_Tools::putIntoText
		(
			$text->get ('millAdvice', 'producing', 'buildings'),
			array
			(
				'mill' => $this->grainToText ($moreMills['allMills'] - $now['allMills']),
				'farm' => $this->grainToText ($moreFarms['allMills'] - $now['allMills'])
			)
		);
	}

	private function grainToText ($amount)
	{
		// resourceToText leaves out amounts of 0 or less.
		if ($amount <= 0)
		{
			return '0';
		}

		return $this->resourceToText (array ('grain' => $amount), false, false);
	}
}
?>
