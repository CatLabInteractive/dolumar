<?php
class Dolumar_Battle_Report
{

	private $unitCache = array ();
	
	private $squads = array ();
	
	private $oBattlefield;

	public static function getAllReports ($start = 0, $length = 50, $fullReport = false)
	{
		$db = Neuron_Core_Database::__getInstance ();

		$list = $db->select
		(
			'battle_report',
			array ('*'),
			false,
			'fightDate DESC',
			$start . ", ".$length
		);

		$o = array ();
		foreach ($list as $v)
		{
			$o[] = new self ($v['reportId'], $v, $fullReport);
		}
		return $o;
	}

	public static function countAllReports ()
	{
		$db = Neuron_Core_Database::__getInstance ();

		$count = $db->select
		(
			'battle_report',
			array ('COUNT(reportId)')
		);

		return $count[0]['COUNT(reportId)'];
	}

	public static function getPlayerReports ($villages = array (), $start = 0, $length = 50, $fullReport = false)
	{
		$db = Neuron_Core_Database::__getInstance ();

		if (!is_array ($villages))
		{
			$villages = array ($villages);
		}

		$where = "";
		foreach ($villages as $v)
		{
			if (is_object ($v))
			{
				$id = $v->getId ();
			}
			else
			{
				$id = $v;
			}

			$where .= "(fromId = $id OR targetId = $id) AND ";
		}
		$where = substr ($where, 0, -5);

		$list = $db->select
		(
			'battle_report',
			array ('*'),
			$where,
			'fightDate DESC',
			$start . ", ".$length
		);

		$o = array ();
		foreach ($list as $v)
		{
			$o[] = new self ($v['reportId'], $v, $fullReport);
		}
		return $o;
	}

	public static function countPlayerReports ($villages)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		if (!is_array ($villages))
		{
			$villages = array ($villages);
		}

		$where = "";
		foreach ($villages as $v)
		{
			if (is_object ($v))
			{
				$id = $v->getId ();
			}
			else
			{
				$id = $v;
			}

			$where .= "(fromId = $id OR targetId = $id) AND ";
		}
		$where = substr ($where, 0, -5);

		$count = $db->select
		(
			'battle_report',
			array ('COUNT(reportId)'),
			$where
		);

		return $count[0]['COUNT(reportId)'];
	}

	private $id, $village = null, $data = null, $fullReport = false;

	private $stolenResources = array (), $stolenRunes = array ();

	public function setId ($id)
	{
		$this->id = $id;
	}

	public function getId ()
	{
		return $this->id;
	}
	
	public static function getFromLogger (Dolumar_Battle_Logger $logger)
	{	
		$class = get_called_class ();
		$report = new $class (0, null, true);
		
		$data = array ();
		$data['resources'] = null;
		$data['runes'] = null;
		$data['fightDate'] = time () - 1;
		$data['fightDuration'] = $logger->getDuration ();
		$data['fromId'] = 0;
		$data['targetId'] = 0;
		
		//var_dump ($logger->getSquads ());
		
		$data['squads'] = $logger->getSquads ();
		$data['slots'] = $logger->getInitialSlots ();
		$data['fightLog'] = $logger->getFightSummary ();
		$data['battleLog'] = $logger->getFightLog ();
		$data['resultLog'] = null;
		$data['victory'] = $logger->getVictory ();
		$data['execDate'] = time ();
		$data['specialUnits'] = $logger->getSpecialUnits ();
		
		$report->setData ($data);
		
		return $report;
	}
	
	public function __construct ($id, $data = null, $fullReport = false)
	{
		$this->id = $id;

		$this->fullReport = $fullReport;

		if (!empty ($data))
		{
			$this->setData ($data);
		}
	}

	public function setVillageFilter ($objVillage)
	{
		$this->village = $objVillage;
	}

	private function setData ($data)
	{
		$this->data = $data;

		$this->stolenResources = array ();
		$this->stolenRunes = array ();
		
		$result = json_decode ($data['resultLog'], true);
		
		if (isset ($result['resources']) && is_array ($result['resources']))
		{
			$this->stolenResources = $result['resources'];
		}
		
		if (isset ($result['runes']) && is_array ($result['runes']))
		{
			foreach ($result['runes'] as $k => $v)
			{
				$this->stolenRunes[$k] = $v;
			}
		}

		/*
		// Resources
		$result = explode ('|', $data['resultLog']);
		foreach ($result as $v)
		{
			$data = explode (':', $v);
			if (count ($data) == 2)
			{
				if ($data[0] == 'res')
				{
					$this->stolenResources = Dolumar_Battle_Battle::logToRes ($data[1]);
				}

				elseif ($data[0] == 'runes')
				{
					$this->stolenRunes = Dolumar_Battle_Battle::logToRunes ($data[1]);
				}
			}
		}
		*/
		
		// Squads
		$this->squads = explode (';', $this->data['squads']);
	}

	private function loadData ()
	{
		if ($this->data === null)
		{
			$db = Neuron_Core_Database::__getInstance ();
			if (is_object ($this->village))
			{
				$v = $db->select
				(
					'battle_report',
					array ('*'),
					"reportId = '".$this->id."' AND (targetId = '".$this->village->getId ()."' OR fromId = '".$this->village->getId ()."')"
				);
			}
			else
			{
				$v = $db->select
				(
					'battle_report',
					array ('*'),
					"reportId = '".$this->id."'"
				);
			}

			if (count ($v) == 1)
			{
				$this->setData ($v[0]);
			}
			else
			{
				$this->data = false;
			}
		}
	}

	public function getVictory ()
	{
		$this->loadData ();
		return $this->data['victory'];
	}

	public function getAttacker ()
	{
		$this->loadData ();
		return Dolumar_Players_Village::getVillage ($this->data['fromId']);
	}

	public function getDefender ()
	{
		$this->loadData ();
		return Dolumar_Players_Village::getVillage ($this->data['targetId']);
	}

	public function getEnvironmentBonus ()
	{
		$this->loadData ();
		return array ($this->data['bBonusAtt'], $this->data['bBonusDef']);
	}
	
	public function __toString ()
	{
		return $this->getReport ();
	}
	
	public function isFinished ()
	{
		return $this->isDone ();
	}

	public function getReport ($objVillage = null, $logid = null, $bShowFight = false, $forceView = false)
	{
		$id = $this->id;
		
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('battle');
		$text->setSection ('report');

		$this->loadData ();

		if ($this->data)
		{
			$v = $this->data;
			
			$page = new Neuron_Core_Template ();

			$attacker = $this->getAttacker ();
			$defender = $this->getDefender ();

			$page->set ('reportid', $this->id);
			$page->set ('title', $text->get ('title'));
			$page->set ('attacker', $text->get ('attacker'));
			$page->set ('defender', $text->get ('defender'));
			$page->set ('date', $text->get ('date'));
			$page->set ('victory', $text->get ('victory'));

			$page->set ('unit', $text->get ('unit'));
			$page->set ('amount', $text->get ('amount'));
			$page->set ('died', $text->get ('died'));
			$page->set ('noDefending', $text->get ('noDefending'));
			$page->set ('secretUnits', $text->get ('secretUnits'));

			if (! ($attacker instanceof Dolumar_Players_DummyVillage))
			{
				$page->set ('attacker_value', Neuron_Core_Tools::output_varchar ($attacker->getName ()));
			}

			if (! ($defender instanceof Dolumar_Players_DummyVillage))
			{
				$page->set ('defender_value', Neuron_Core_Tools::output_varchar ($defender->getName ()));
			}
			
			// URL
			$page->set ('attacker_id', $attacker->getId ());
			$page->set ('defender_id', $defender->getId ());
			
			$page->set ('date_value', date (DATETIME, $this->getBattleTime ()));

			$page->set ('toReturn', $text->getClickTo ($text->get ('toReturn')));
			
			$showreport = true;

			if ($this->isDone () || $forceView)
			{
				$page->set ('show_summary', true);
			
				$showreport = $bShowFight;
			
				if ($this->getVictory () < 0.01)
				{
					$page->set ('victory_value', $text->get ('defeated'));
				}
				else
				{
					$page->set ('victory_value', Neuron_Core_Tools::putIntoText
						(
							$text->get ('victory_value'),
							array
							(
								floor ($this->getVictory () * 100)
							)
						)
					);
				}
				
				if (!$showreport)
				{
					$page->set ('cur_tab', 'summary');
				
					$page->set ('attacking', $text->get ('attacking'));
					$page->set ('defending', $text->get ('defending'));

					$page->set ('stolen', $text->get ('stolen'));
					$page->set ('runes', $text->get ('runes'));
			
					// Loop trough attackers
					$units = $this->getUnits ($objVillage);
			
					self::printUnits ($page, 'attacking', $units['attacking']);
					self::printUnits ($page, 'defending', $units['defending']);

					// Resources
					if ($this->getStolenResources ())
					{
						$page->set ('stolen_value', Dolumar_Battle_Battle::resourceToText ($this->getStolenResources ()));
					}

					foreach ($this->getStolenRunes () as $k => $v)
					{
						$page->addListValue
						(
							'runes',
							array
							(
								ucfirst ($text->get ($k, $v > 1 ? 'runeDouble' : 'runeSingle', 'main')),
								$v
							)
						);
					}
				
					// Add special units
					$specialunits = $this->getSpecialUnits ();
				
					foreach ($specialunits as $k => $units)
					{
						foreach ($units as $v)
						{
							$page->addListValue
							(
								$k.'_su',
								array
								(
									'name' => $v->getDisplayName (),
									'died' => !$v->isAlive ()
								)
							);
						}
					}
				}
			}
			
			if ($showreport)
			{
				$page->set ('cur_tab', 'fightlog');
			
				$fighttxt = $this->getFightlog ($logid, $forceView);
				$page->set ('report', $fighttxt);
			}
			
			return $page->parse ('battle/report.tpl');
		}

		else
		{
			return '<p class="false">'.$text->get ('notFound').'</p>';
		}
	}
	
	public function getFightlog ($logid, $showAll = false)
	{
		if (!isset ($logid) && $this->isFinished ())
		{
			$logid = 0;
		}
		elseif (!isset ($logid))
		{
			$logid = null;
		}
		
		$fight = $this->getFightReport (NOW, $logid, $showAll);
		
		if (!isset ($logid))
		{
			$logid = count ($fight) - 1;
		}
		
		$alternate = true; 
		
		$fighttxt = '<ul class="fightlog">';
		foreach ($fight as $k => $v)
		{
			if (empty ($v))
			{
				continue;
			}
		
			if ($alternate)
			{
				$alternate = false;
				$rowclass = "odd";
			}
			else
			{
				$alternate = true;
				$rowclass = "even";
			}
		
			if ($k === $logid)
			{
				$fighttxt .= '<li class="current focus ' . $rowclass . '">';
			}
			else
			{
				$fighttxt .= '<li class="' . $rowclass . '">';
			}
			
			$fighttxt .= '<a href="javascript:void(0);" onclick="windowAction(this,{\'report\':'.$this->id.',\'log\':'.$k.',\'fightlog\':1});">';
			$fighttxt .= $v;
			$fighttxt .= '</a></li>';
		}
		
		$fighttxt .= '</ul>';
		
		return $this->oBattlefield . $fighttxt;
	}
	
	private function getSpecialUnits ()
	{
		$this->loadData ();
		$specialunits = json_decode ($this->data['specialUnits'], true);

		$out = array
		(
			'attacking' => array (),
			'defending' => array ()
		);
		
		if (isset ($specialunits['attacking']))
		{
			foreach ($specialunits['attacking'] as $v)
			{
				$out['attacking'][] = $this->parseSpecialUnit ($v);
			}
		}
		
		return $out;
	}
	
	/*
		return array
		(
			$unit->getUnitId (),
			$unit->getLevel (),
			$unit->getCustomName (),
			$unit->isAlive ()
		);
	*/
	private function parseSpecialUnit ($data)
	{
		$unit = Dolumar_SpecialUnits_SpecialUnits::getFromId ($data[0]);
		$unit->setLevel ($data[1]);
		$unit->setCustomName ($data[2]);
		$unit->setAlive ($data[3]);
		
		if (isset ($data[4]))
		{
			$unit->setRace (Dolumar_Races_Race::getRace ($data[4]));
		}
		
		return $unit;
	}
	
	public function isDone ()
	{
		//return true;
		$this->loadData ();
		return $this->data['fightDate'] + $this->data['fightDuration'] < time ();
	}
	
	public function getDuration ()
	{
		return $this->data['fightDuration'];
	}
	
	public function getFightReport ($now = NOW, $logid = null, $showAll = false)
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		$this->loadData ();
		$data = $this->data['battleLog'];
		
		// Check how much "report" can be seen
		$fightStart = $this->data['fightDate'];
		
		$out = array ();
		
		$data = explode (';', $data);
		array_pop ($data);
		
		$lastAction = null;
		
		$out[] = Neuron_Core_Tools::putIntoText
		(
			$text->getRandomLine ('introtext', 'battlereport', $this->id),
			array
			(
				'attacker' => $this->getAttacker ()->getName (),
				'defender' => $this->getDefender ()->getName ()
			)
		);
		
		$isDone = $showAll || $this->isDone ();
		
		$slots = json_decode ($this->data['slots'], true);
		
		if (isset ($slots['attacking']) && isset ($slots['defending']))
		{
			// Create thze battle report
			$attacking = $this->parseUnitSlots ($slots['attacking']);
			$defending = $this->parseUnitSlots ($slots['defending']);
			
			$this->oBattlefield = new Dolumar_Battle_Battlefield ($attacking, $defending);
		}
		else
		{
			$this->oBattlefield = new Dolumar_Battle_Battlefield (null, null);
		}
		
		$i = 0;
		
		foreach ($data as $v)
		{
			$facts = explode ('|', $v);
			$orgfacts = $facts;
			
			if (count ($facts) > 3)
			{
				// Fetch the date from the data
				$iDate = array_shift ($facts);
				
				// Jump out of the loop if date is in the future
				if (!$isDone && $iDate + $fightStart > time ())
				{
					break;
				}
				
				$iAction = intval (array_shift ($facts));
				$iRandom = array_shift ($facts);
				
				// $iTeam: attacker = 1, defender = 2.
				$iTeam = array_shift ($facts);
				
				$isAttacker = $iTeam == 1;

				$date = Neuron_Core_Tools::getDuration ($iDate);
				
				// Check for "empty" status report
				if ($lastAction === 0 && $iAction === 0)
				{
					array_pop ($out);
				}
				
				$lastAction = $iAction;
				
				// Check if we should push this log to the battlefield
				if ($logid != null && $logid == $i)
				{
					$selected = true;
					$usefield = true;
				}
				else
				{
					$selected = false;
					$usefield = $logid === null || $logid > $i;
				}
				
				switch ($iAction)
				{
					// Status
					case 0:
						//$out[] = null;						
						$out[] = $text->getRandomLine ('status_'.$facts[0], 'battlereport', $iRandom);
						
						if ($usefield)
							$this->oBattlefield->nextround ();
					break;
				
					// Move
					case 1:
						$unit = $this->getUnitData ($facts[0]);
						
						$out[] = Neuron_Core_Tools::putIntoText
						(
							$text->getRandomLine ('move', 'battlereport', $iRandom, $unit),
							array
							(
								'unit' => (string)$unit,
								'from' => $facts[1],
								'to' => $facts[2]
							)
						);
						
						if ($usefield)
						$this->oBattlefield->move ($isAttacker, $facts[1], $facts[2], $unit);
					break;
					
					// Stunned
					case 2:
						$unit = $this->getUnitData ($facts[0]);
						$slot = $this->getUnitSlot ($facts[0]);
						
						$out[] = 
						Neuron_Core_Tools::putIntoText
						(
							$text->getRandomLine ('stunned', 'battlereport', $iRandom),
							array
							(
								'unit' => (string)$unit
							)
						);
						
						if ($usefield)
							$this->oBattlefield->stunned (!$isAttacker, $slot, $unit);
					break;
					
					// Magic
					case 5:	
						/* 112|5|68|1|1:1|3|4:3:7::0:50|8 */
						
						// unit | effect | probability
						
						$unitData = explode (':', array_shift ($facts));
					
						$unit = Dolumar_SpecialUnits_SpecialUnits::getFromId ($unitData[0]);
						
						if ($unit)
						{
							$unit->setLevel ($unitData[1]);
							$unit->setVillage ($this->getAttacker ());
						
							$effectId = array_shift ($facts);
							$effect = Dolumar_Effects_Effect::getFromId ($effectId);
						
							$probability = array_shift ($facts);
						
							if ($effect && $effect instanceof Dolumar_Effects_Battle)
							{
								$out[] = $effect->getBattleLog ($this, $unit, $probability, $facts);
							
								if ($usefield)
								$this->oBattlefield->specialunit_action ($isAttacker, $unit, $effect, true);
							}
							else
							{
								$out[] = 'Unidentified battle effect: '.$effectId;
							}
						}
						else
						{
							$out[] = 'Unidentified battle effect user: '.print_r ($orgfacts, true);
						}
					break;
					
					case 6:
						/* 158|6|59|1|1:1|3 */
						$unitData = explode (':', array_shift ($facts));
					
						$unit = Dolumar_SpecialUnits_SpecialUnits::getFromId ($unitData[0]);
						$unit->setLevel ($unitData[1]);
						$unit->setVillage ($this->getAttacker ());
					
						$effectId = array_shift ($facts);
						$effect = Dolumar_Effects_Effect::getFromId ($effectId);
						
						$probability = array_shift ($facts);
						
						if ($effect && $effect instanceof Dolumar_Effects_Battle)
						{
							$out[] = $effect->getFailedLog ($this, $unit, $probability, $facts);
							
							if ($usefield)
							$this->oBattlefield->specialunit_action ($isAttacker, $unit, $effect, false);
						}
					break;
					
					// Dead special units
					case 7:
						/* 158|6|59|1|1:1|3 */
						$unitData = explode (':', array_shift ($facts));
					
						$unit = Dolumar_SpecialUnits_SpecialUnits::getFromId ($unitData[0]);
						$unit->setLevel ($unitData[1]);
						$unit->setVillage ($this->getAttacker ());
						
						$out[] = Neuron_Core_Tools::putIntoText
						(
							$text->getRandomLine ('specialunitdied', 'battlereport', $iRandom),
							array
							(
								'unit' => $unit->getName (),
								'level' => $unit->getLevel ()
							)
						);
						
						if ($usefield)
						$this->oBattlefield->specialunit_dead ($isAttacker, $effect, false);
					break;
					
					// whiped units
					case 8:
						$unit = $this->getUnitData ($facts[0]);
					
						$out[] = Neuron_Core_Tools::putIntoText
						(
							$text->getRandomLine ('whipe', 'battlereport', $iRandom),
							array
							(
								'unit' => (string)$unit,
								'date' => $date
							)
						);
						
						if ($usefield)
						$this->oBattlefield->whipe ($isAttacker, $this->getUnitSlot ($facts[0]), $unit);
					break;
					
					case 9:
						$unit = $this->getUnitData ($facts[0]);
					
						$out[] = Neuron_Core_Tools::putIntoText
						(
							$text->getRandomLine ('flee', 'battlereport', $iRandom),
							array
							(
								'unit' => (string)$unit,
								'date' => $date
							)
						);
						
						if ($usefield)
						$this->oBattlefield->flee ($isAttacker, $this->getUnitSlot ($facts[0]), $unit);
					break;
				
					// Damage
					case 10:
					case 11:
					case 12:
					
						switch ($iAction)
						{
							case 11:
								$damage = 'melee';
							break;
							
							case 12:
								$damage = 'shooting';
							break;
							
							default:
								$damage = 'error';
							break;
						}
						
						$attacker = $this->getUnitData ($facts[0]);
						$defender = $this->getUnitData ($facts[1]);

						$out[] = Neuron_Core_Tools::putIntoText
						(
							$text->getRandomLine ('casualties_'.$damage, 'battlereport', $iRandom),
							array
							(
								'attacker' => (string)$attacker,
								'defender' => (string)$defender,
								'amount' => $facts[2],
								'date' => $date,
								'frontage' => $this->getUnitFrontage ($facts[0])
							)
						);
						
						if ($usefield)
						$this->oBattlefield->damage 
						(
							$isAttacker, 
							$this->getUnitSlot ($facts[0]), 
							$this->getUnitSlot ($facts[1]), 
							$facts[2],
							$attacker,
							$defender
						);
					break;
					
					// Default
					default:
						$out[] = $date.' Unknown log: '.$iAction;
					break;
				}
				
				$i = count ($out);
			}
		}
		
		if (!$isDone)
		{
			$out[] = '...';
		}
		
		return $out;
	}
	
	private function parseUnitSlots ($slots)
	{
		foreach ($slots as $k => $v)
		{
			if (isset ($v[1]))
			{
				$slots[$k][1] = $this->getUnit ($v[1]);
			}
		}	
		
		return $slots;
	}
	
	public function getUnitFrontage ($data)
	{
		$data = json_decode ($data, true);
		$data = array_values ($data);
		return $data[7];
	}
	
	public function getUnitData ($data)
	{
		$unit = $this->getUnit ($data);
		return $unit;
	}
	
	/*
		Return a Dolumar_Battle_Unit object
		TEMPORARY: use the array_values
		SOON: replace with assoc array values
	*/
	private function getUnit ($data)
	{
		$data = json_decode ($data, true);
		
		$data = array_values ($data);
		
		if (!$data)
		{
			$vil = Dolumar_Players_Village::getVillage ($data[1]);
			return new Dolumar_Units_Unit ($vil);
		}
		
		$sKey = $data[0].'_'.$data[1].'_'.$data[2].'_'.$data[6]; // 6: race
		
		if (!isset ($this->unitCache[$sKey]))
		{
			$vil = Dolumar_Players_Village::getVillage ($data[1]);
			$race = Dolumar_Races_Race::getFromId ($data[6]);
		
			$this->unitCache[$sKey] = Dolumar_Units_Unit::getUnitFromId ($data[0], $race, $vil);
		}
		
		$unit = new Dolumar_Battle_Unit ();
		$unit->setUnit ($this->unitCache[$sKey]);
		
		$unit->setAmount ($data[5]);
		
		// Squad (4 or 'squad')
		if ($data[4] > 0 && isset ($this->squads[$data[4] - 1]))
			$unit->setName ($this->squads[$data[4] - 1]);
			
		// Frontage
		$frontage = isset ($data[7]) ? $data[7] : 0;
		$unit->setFrontage ($frontage);
		
		return $unit;
	}
	
	private function getUnitSlot ($data)
	{
		//$data = explode (':', $data);
		$data = json_decode ($data, true);
		if (!$data)
		{
			return 1;
		}
		
		$data = array_values ($data);
		
		return $data[2];
	}

	private function printUnits ($page, $name = 'attacking', $ats)
	{
		if ($ats)
		{
			foreach ($ats as $v)
			{
				$page->addListValue
				(
					$name,
					array
					(
						$v['unit']->getName (),
						$v['amount'],
						$v['died']
					)
				);
			}
		}
		else
		{
			$page->set ('list_'.$name, false);
		}
	}

	public function getBattleTime ()
	{
		$this->loadData ();
		return $this->data['fightDate'];
	}

	public function getUnits ($objVillage = null)
	{
		$this->loadData ();
	
		// Fetch attackers & defenders
		$log = explode ('&', $this->data['fightLog']);
		$units = array ();
		
		$units['attacking'] = explode (';', $log[0]);
		if (isset ($log[1]))
		{
			$units['defending'] = explode (';', $log[1]);
		}

		else
		{
			$units['defending'] = array ();
		}

		// Loop trough units
		$out = array ();

		// Filter for defender (info)
		$disabled = array ();

		/*

		if
		(
			!(
				$this->fullReport ||
				(
					is_object ($objVillage) && 
					$objVillage->getId () == $this->getDefender ()->getId ()
				) ||
				$this->getVictory () > 0.1
			)
		)
		{
			$disabled['defending'] = true;
		}
		
		*/
		
		foreach ($units as $teamKey => $team)
		{
			$out[$teamKey] = array ();
			$distinct = array ();

			if (isset ($disabled[$teamKey]))
			{
				$out[$teamKey] = false;
			}
			else
			{
				foreach ($team as $v)
				{
					$l = explode (':', $v);
					if (count ($l) >= 4)
					{
						$vil = Dolumar_Players_Village::getVillage ($l[1]);
						//$race = $vil->getRace ();
						$race = Dolumar_Races_Race::getFromId ($l[2]);

						$key = $l[0] . '_' . $race->getName ();

						$log = $key . ": ";

						if (!isset ($distinct[$key]))
						{
							$distinct[$key] = count ($out[$teamKey]);
							
							$unit = Dolumar_Units_Unit::getUnitFromId ($l[0], $race, $vil);
							
							$out[$teamKey][$distinct[$key]] = array
							(
								'unit' 	=> $unit,
								'amount' => $l[3],
								'died' => $l[4]
							);

							$log .= "init";
						}
						else
						{
							$out[$teamKey][$distinct[$key]]['amount'] += $l[3];
							$out[$teamKey][$distinct[$key]]['died'] += $l[4];

							$log .= "update";
						}
					}
				}
			}
		}

		return $out;
	}

	public function getStolenResources ()
	{
		$this->loadData ();
		return $this->stolenResources;
	}

	public function getStolenRunes ()
	{
		$this->loadData ();
		return $this->stolenRunes;
	}
	
	public function countStolenRunes ()
	{
		$sum = 0;
		foreach ($this->getStolenRunes () as $v)
		{
			$sum += $v;
		}
		return $sum;
	}
	
	public function serialize ()
	{
		return json_encode ($this->data);
	}
	
	public static function unserialize ($data)
	{
		$class = get_called_class ();
		$report = new $class (0, null, true);
		
		$data = json_decode ($data, true);
		
		$report->setData ($data);
		return $report;
	}
	
	public function __destruct ()
	{
		if (isset ($this->oBattlefield))
		{
			//$this->oBattlefield->__destruct ();
		}

		unset ($this->oBattlefield);
	}
}