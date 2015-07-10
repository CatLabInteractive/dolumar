<?php
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
		$profiler = Neuron_Profiler_Profiler::getInstance ();
	
		if (!isset ($level))
		{
			$level = $this->getLevel ();
		}
	
		// 40 for every level
		$income = $this->INCOME * GAME_SPEED_RESOURCES * 1.1 * $level;
		
		// Manipulated by honour!
		$profiler->start ('Fetching honour');
		$income *= ($this->getVillage()->honour->getHonour() / 100);
		$profiler->stop ();

		// Bonusses (in resources)
		$profiler->start ('Fetching locations');
		$bonus = $this->loadNearebyLocation ();
		if (isset ($bonus[$this->RESOURCE]))
		{
			$income += ( ($income / 100) * $bonus[ $this->RESOURCE ] );
		}
		$profiler->stop ();
		
		//$income = (floor ($income * 4 * GAME_SPEED_RESOURCES) / 4);
		$income = floor ($income);
		
		return $income;
	}

	private function loadNearebyLocation ()
	{
		$resources = array ();
		for ($i = -1; $i <= 1; $i ++)
		{
			for ($j = -1; $j <= 1; $j ++)
			{
				$land = Dolumar_Map_Location::getLocation ($this->tileLocationX + $i, $this->tileLocationY + $j);
				$benefit = $land->getIncomeBonus ();
				
				foreach ($benefit as $k => $v)
				{
					if (!isset ($resources[$k]))
					{
						$resources[$k] = $v;
					}
					else
					{
						$resources[$k] += $v;
					}
				}
			}
		}
		
		return $resources;
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
		
		$page->set ('income', $this->resourceToText($this->getIncome(), true, false));
		
		return $page->parse ('buildings/producing.tpl');
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
		$page->set ('income_increase', round ((($later[$this->RESOURCE] - $later[$this->RESOURCE]) / max (1, $later[$this->RESOURCE])) * 100));
		
		return $page->parse ('buildings/producing_up.phpt');
	}
}
?>
