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

class Dolumar_Buildings_Producing extends Dolumar_Buildings_Building
{
	protected $RESOURCE = 'grain';
	protected $INCOME = 60;
	
	public function getIncome ($since = NOW, $now = NOW) 
	{
		$profiler = Neuron_Profiler_Profiler::getInstance ();
	
		$profiler->start ('Calculating income for this ' . $this->getClassName ());
		
		$o = array ($this->RESOURCE => $this->calculateIncome ());
		
		$profiler->start ('Processing bonus effects');
		$o = Neuron_Core_Tools::floor_array 
		(
			$this->getVillage ()->procBonusses 
			(
				'procIncome', 
				array ($o, $this), 
				$now, $since
			)
		);
		
		$profiler->stop ();
		
		$profiler->stop ();
		
		return $o;
	}
	
	protected function calculateIncome ($level = null)
	{
		$steps = $this->calculateIncomeSteps ($level);
		return $steps['total'];
	}

	/*
		The income of this building before technologies and spells,
		step by step: base (level), honour, nearby bonus tiles.
		calculateIncome () returns 'total'.
	*/
	protected function calculateIncomeSteps ($level = null)
	{
		$profiler = Neuron_Profiler_Profiler::getInstance ();
	
		if (!isset ($level))
		{
			$level = $this->getLevel ();
		}
	
		$out = array ();
	
		// 40 for every level
		$income = $this->INCOME * GAME_SPEED_RESOURCES * 1.1 * $level;
		$out['base'] = $income;
		
		// Manipulated by honour!
		$profiler->start ('Fetching honour');
		$out['honourPercentage'] = $this->getVillage()->honour->getHonour();
		$income *= ($out['honourPercentage'] / 100);
		$out['honour'] = $income;
		$profiler->stop ();

		// Bonusses (in resources)
		$profiler->start ('Fetching locations');
		$out['tiles'] = $this->getNearbyBonusTiles ();
		$bonus = 0;
		foreach ($out['tiles'] as $v)
		{
			$bonus += $v['percentage'];
		}

		if ($bonus != 0)
		{
			$income += ( ($income / 100) * $bonus );
		}
		$out['tilesPercentage'] = $bonus;
		$out['tilesIncome'] = $income;
		$profiler->stop ();
		
		//$income = (floor ($income * 4 * GAME_SPEED_RESOURCES) / 4);
		$out['total'] = floor ($income);
		
		return $out;
	}

	/*
		The map tiles in the 3x3 square around this building (its own tile
		included) that raise this building's resource, grouped by kind:
		key => array ('key', 'amount', 'percentage', 'image').
	*/
	public function getNearbyBonusTiles ()
	{
		$out = array ();
		for ($i = -1; $i <= 1; $i ++)
		{
			for ($j = -1; $j <= 1; $j ++)
			{
				$land = Dolumar_Map_Location::getLocation ($this->tileLocationX + $i, $this->tileLocationY + $j);
				$benefit = $land->getIncomeBonus ();
				
				if (empty ($benefit[$this->RESOURCE]))
				{
					continue;
				}

				$key = $land->getBonusKey ();
				if (!isset ($out[$key]))
				{
					$image = $land->getImage ();
					$out[$key] = array
					(
						'key' => $key,
						'amount' => 0,
						'percentage' => 0,
						'image' => IMAGE_URL . 'tiles/' . $image['image'] . '.gif'
					);
				}

				$out[$key]['amount'] ++;
				$out[$key]['percentage'] += $benefit[$this->RESOURCE];
			}
		}
		
		return $out;
	}

	/*
		Where the income comes from, as rows for the building window:
		array ('label', 'amount' (signed), 'image' (optional)).
		The amounts add up to getIncome ().
	*/
	public function getIncomeBreakdown ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('buildings');
		$text->setSection ('producing');

		$steps = $this->calculateIncomeSteps ();

		$rows = array ();

		$rows[] = array
		(
			'label' => Neuron_Core_Tools::putIntoText ($text->get ('baseIncome'), array ('level' => $this->getLevel ())),
			'amount' => floor ($steps['base'])
		);

		if ($steps['honourPercentage'] != 100)
		{
			$rows[] = array
			(
				'label' => Neuron_Core_Tools::putIntoText ($text->get ('honourIncome'), array ('honour' => $steps['honourPercentage'])),
				'amount' => floor ($steps['honour']) - floor ($steps['base'])
			);
		}

		// Show every bonus tile kind with its share of the bonus.
		$previous = $steps['honour'];
		foreach ($steps['tiles'] as $v)
		{
			$after = $previous + ($steps['honour'] / 100) * $v['percentage'];
			$rows[] = array
			(
				'label' => Neuron_Core_Tools::putIntoText
				(
					$text->get ($v['amount'] > 1 ? 'tileBonusMultiple' : 'tileBonus'),
					array
					(
						'tile' => $text->get ($v['key'], 'tiles', 'buildings', $v['key']),
						'amount' => $v['amount'],
						'percentage' => $v['percentage']
					)
				),
				'amount' => floor ($after) - floor ($previous),
				'image' => $v['image']
			);
			$previous = $after;
		}

		$this->addEffectRows ($rows, $previous, $steps['total']);

		return $rows;
	}

	/*
		Add one row per technology or spell that changes the income.
		$previous is the income before effects (unrounded), $total the
		same value rounded the way calculateIncome () does.
	*/
	protected function addEffectRows (&$rows, $previous, $total)
	{
		$text = Neuron_Core_Text::__getInstance ();

		$effects = array ();
		$this->getVillage ()->procBonusses
		(
			'procIncome',
			array (array ($this->RESOURCE => $total), $this),
			NOW, NOW,
			$effects
		);

		// The rounding of the steps before goes into the first row after them.
		$last = floor ($previous);
		$lastRow = count ($rows) - 1;
		if ($lastRow >= 0 && $last != $total)
		{
			$rows[$lastRow]['amount'] += $total - $last;
		}
		$last = $total;

		foreach ($effects as $effect)
		{
			list ($object, $resources) = $effect;
			$value = isset ($resources[$this->RESOURCE]) ? floor ($resources[$this->RESOURCE]) : 0;

			if ($value != $last)
			{
				$rows[] = array
				(
					'label' => $object->getName (),
					'amount' => $value - $last
				);
				$last = $value;
			}
		}
	}
	
	protected function getCustomContent ($input)
	{
		$text = Neuron_Core_Text::__getInstance ();
		
		$text->setFile ('buildings');
		$text->setSection ('producing');
		
		$page = new Neuron_Core_Template ();
		
		$page->set ('hourlyIncome', $text->get ('hourlyIncome'));
		$page->set ('producing1', $text->get ('producing1'));
		$page->set ('producing2', $text->get ('producing2'));
		
		$income = $this->getIncome ();
		$page->set ('income', $this->resourceToText($income, true, false));
		$page->set ('resource', $this->RESOURCE);
		$page->set ('total', isset ($income[$this->RESOURCE]) ? $income[$this->RESOURCE] : 0);

		foreach ($this->getIncomeBreakdown () as $row)
		{
			$page->addListValue ('breakdown', $row);
		}

		$page->set ('explanation', $this->getBonusExplanation ());
		$page->set ('advice', $this->getIncomeAdvice ());
		
		return $page->parse ('buildings/producing.tpl');
	}

	/*
		What every spot is worth while this building is placed: the tiles
		near the village that raise its resource (for the engine's
		Game.map.placementPreview). Null when nothing can raise it.
	*/
	public function getPlacementPreview (Dolumar_Players_Village $village)
	{
		$townCenter = $village->buildings->getTownCenter ();
		if (!$townCenter)
		{
			return null;
		}

		$text = Neuron_Core_Text::__getInstance ();

		list ($cx, $cy) = $townCenter->getLocation ();

		// One tile further than the building radius: a tile just outside still counts.
		$radius = $village->getBuildingRadius () + 1;

		$tiles = array ();
		foreach (Dolumar_Map_Map::getBonusTilesAround ($cx, $cy, $radius) as $v)
		{
			list ($x, $y, $location) = $v;

			$bonus = $location->getIncomeBonus ();
			if (empty ($bonus[$this->RESOURCE]))
			{
				continue;
			}

			$key = $location->getBonusKey ();
			$tiles[] = array ((int)$x, (int)$y, $bonus[$this->RESOURCE], $text->get ($key, 'tiles', 'buildings', $key));
		}

		$resource = $text->get ($this->RESOURCE, 'resources', 'main', $this->RESOURCE);

		return array
		(
			'area' => 1,
			'tiles' => $tiles,

			// The engine fills in @@value; putIntoText would remove it.
			'text' => str_replace ('@@resource', $resource, $text->get ('bonustext', 'placement', 'buildings')),
			'none' => $text->get ('nobonus', 'placement', 'buildings')
		);
	}

	/*
		Extra advice under the breakdown (the mill uses it); null for none.
	*/
	protected function getIncomeAdvice ()
	{
		return null;
	}

	/*
		One line that tells the player how to raise this building's income.
	*/
	protected function getBonusExplanation ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		return $text->get ($this->getClassName (), 'bonusExplanation', 'buildings', '');
	}
	
	/*
		Returns a small upgrade message
	*/
	protected function getUpgradeInformation ()
	{
		$page = new Neuron_Core_Template ();
		
		$now = array ($this->RESOURCE => $this->calculateIncome ($this->getLevel () + 0));
		$later = array ($this->RESOURCE => $this->calculateIncome ($this->getLevel () + 1));
		
		$page->set ('income_next_level', $this->resourceToText ($later, true, false));
		$page->set ('income_this_level', $this->resourceToText ($now, true, false));
		
		return $page->parse ('buildings/producing_up.phpt');
	}
}
?>
