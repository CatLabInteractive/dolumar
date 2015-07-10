<?php
class Dolumar_Buildings_Building 
	implements Neuron_GameServer_Interfaces_Logable
{
	private $id, $data;
	protected $tileLocationX, $tileLocationY;
	private $destroyed = false;
	
	private $bShowOptions = true;
	private $bShowTechnologyUpgrades = true;
	
	protected $objWindow;
	
	private $sError;
	
	private $oVillage;
	
	private $oRace;
	private $requirements = array ();

	public static function getAllBuildings ()
	{
		return array
		(
			// Town Center
			1 => 'TownCenter',	// +
			2 => 'Silo',		// +
			3 => 'Outpost',
			
			// Producing Buildings
			10 => 'Farm', 		// grain +
			11 => 'StoneMine',	// wood +
			12 => 'Lumber',		// stone +
			13 => 'IronMine',	// iron +
			14 => 'GemMine',	// gems +
			16 => 'Mill',		// grain bonus

			// Training
			20 => 'Barrack',	// +
			21 => 'Archery',	// +
			22 => 'Stable',		// +
			23 => 'SiegeWorkshop',

			// Trading
			30 => 'Market',
			31 => 'WeaponSmith',
			32 => 'ArmourSmith',
			33 => 'ThievesDen',

			// Magic
			40 => 'Temple',
			41 => 'WizardTower',

			// Bonus
			50 => 'Tower', 		// +
			
			// Portal
			60 => 'Portal',
			61 => 'Clanportal',
			
			100 => 'BonusBuilding',

			501 => 'TraderTent'
		);
	}
	
	public static function getBuildingObjects ($race)
	{
		$out = array ();
		foreach (self::getAllBuildings () as $v)
		{
			$out[] = self::getBuildingFromName ($v, $race);
		}
		return $out;
	}
	
	// Get building classes
	public static function getBuilding ($id, $race, $locationX = 0, $locationY = 0)
	{
		$o = self::getAllBuildings ();
		return self::getBuildingFromName ($o[$id], $race, $locationX, $locationY);
	}
	
	public static function getFromId ($id)
	{
		// Load building from ID (takes a mysql query)
		$db = Neuron_DB_Database::__getInstance ();
		
		$id = intval ($id);
		
		$data = $db->query
		("
			SELECT
				*
			FROM
				map_buildings
			WHERE
				bid = '{$id}'
		");
		
		if (count ($data) == 1)
		{
			$village = Dolumar_Players_Village::getFromId ($data[0]['village']);
			if ($village)
			{
				$building = self::getBuilding ($data[0]['buildingType'], $village->getRace (), $data[0]['xas'], $data[0]['yas']);
				$building->setData ($data[0]['bid'], $data[0]);
				return $building;
			}
			else
			{
				return false;
			}
		}
		return false;
	}
	
	public static function getBuildingFromName ($id, $race, $locationX = 0, $locationY = 0)
	{
		$rn = $race->getName ();
		
		if (class_exists ('Dolumar_Races_'.$rn.'_Dolumar_Buildings_'.$id))
		{
			$c = 'Dolumar_Races_'.$rn.'_Dolumar_Buildings_'.$id;
			return new $c ($locationX, $locationY);
			//eval ('$c = new  ($locationX, $locationY);');
		}
		
		elseif (class_exists ('Dolumar_Buildings_'.$id))
		{
			$c = 'Dolumar_Buildings_'.$id;
			$c = new $c ($locationX, $locationY);
			//eval ('$c = new Dolumar_Buildings_'.$id.' ($locationX, $locationY);');
		}
		else
		{
			$c = new self ($locationX, $locationY);
		}
		
		$c->setRace ($race);
		
		return $c;
	}
	
	protected $points = 25;
	protected $stats;
	protected $upgradeRune;
	
	public static function getStatistics ($sName)
	{
		$stats = Neuron_Core_Stats::__getInstance ();

		// Fetch these statistics
		$myStats = $stats->getSection ($sName, 'buildings');

		// Split up
		$bc = array ('wood' => 15, 'stone' => 15, 'runeId' => 'random', 'runeAmount' => 1);
		//$uc = array ();
		$points = 30;
		//$bDuration = 300;
		//$uDuration = 600;

		foreach ($myStats as $k => $v)
		{
			switch ($k)
			{
				case 'points':
					$$k = $v;
				break;

				default:
					switch (substr ($k, 0, 2))
					{
						case 'uc':
							$uc[strtolower ($k[2]) . (substr ($k, 3))] = $v;
						break;
						
						case 'bc':
							$bc[strtolower ($k[2]) . (substr ($k, 3))] = $v;
						break;
					}
				break;
			}
		}
		
		return array
		(
			'bc' => $bc,
			'points' => $points
		);
	}

	private function __construct ($locationX, $locationY)
	{
		$this->tileLocationX = $locationX;
		$this->tileLocationY = $locationY;
		
		// Load statistics
		$name = get_class ($this);
		$name = explode ('_', $name);
		$name = $name[count ($name) - 1];
		
		// Get stats
		$this->stats = Dolumar_Buildings_Building::getStatistics ($name);

		// Backwards comptability
		$this->bc = $this->stats['bc'];
		$this->points = $this->stats['points'];
		
		$this->initRequirements ();
	}

	/**
	* Depending on if this is an upgrade or a construction,
	* return the time left.
	*/
	public function getTimeLeft ()
	{
		$construction = $this->getReadyDate ();
		if ($construction > NOW)
		{
			$dif = $construction - NOW;
		}
		else
		{
			$dif = $this->getLastUpgradeDate () - NOW;
		}

		if ($dif < 0)
		{
			return 0;
		}
		return $dif;
	}
	
	public function getReadyDate ()
	{
		return $this->data['readyDate'];
	}

	public function getLastUpgradeDate ()
	{
		return $this->data['lastUpgradeDate'];
	}

	/*
		Initialise this buildings requiremnets
	*/
	protected function initRequirements ()
	{
		// Do nothing.
	}
	
	public function getStat ($name)
	{
		if (isset ($this->stats[$name]))
		{
			return $this->stats[$name];
		}
		else
		{
			return null;
		}
	}
	
	public function setRace (Dolumar_Races_Race $race)
	{
		$this->oRace = $race;
	}
	
	public function getRace ()
	{
		if (isset ($this->oRace))
		{
			return $this->oRace;
		}
		else
		{
			return $this->getVillage ()->getRace ();
		}
	}
	
	public function setWindow ($objWindow)
	{
		$this->objWindow = $objWindow;
	}

	public function getClassName ()
	{
		$name = get_class ($this);
		$name = explode ('_', $name);
		$name = $name[count ($name) - 1];

		return $name;
	}
	
	public function getLocation ()
	{
		return array ($this->tileLocationX, $this->tileLocationY);
	}
	
	// Used to move buildings arround.
	public function setLocation ($x, $y)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$x = intval ($x);
		$y = intval ($y);
		
		$db->query
		("
			UPDATE
				map_buildings
			SET
				xas = {$x},
				yas = {$y}
			WHERE
				bid = {$this->getId ()}
		");
		
		$this->data['xas'] = $x;
		$this->data['yas'] = $y;
	}
	
	public function getLevel ()
	{
		return $this->data['bLevel'];
		//return $this->getVillage()->buildings->getBuildingLevel ($this);
	}
	
	public function isFinished ($date = NOW)
	{
		return $this->isFinishedBuilding () && $this->data['lastUpgradeDate'] < $date;
	}

	public function isFinishedBuilding ()
	{
		return $this->data['readyDate'] < time();
	}

	public function setData ($id, $data)
	{
		$this->id = $id;
		$this->data = $data;
	}
	
	public function getBuildingId ()
	{
		$name = get_class ($this);
		$name = explode ('_', $name);
		$name = $name[count ($name) - 1];
		
		$o = $this->getAllBuildings ();
		
		return array_search ($name, $o);
	}

	public function getId ()
	{
		return (int)$this->id;
	}
	
	public function getOwner ()
	{
		return $this->getVillage ()->getOwner ();
	}
	
	public function setVillage ($oVillage)
	{
		$this->oVillage = $oVillage;
	}

	/**
	 * @return Dolumar_Players_Village
	 * @throws Neuron_Core_Error
	 */
	public function getVillage ()
	{
		if (!isset ($this->oVillage))
		{
			//$this->setVillage (Dolumar_Players_Village::getVillage ($this->data['village']));
			throw new Neuron_Core_Error ('Village not set in Building object.');
		}
		
		return $this->oVillage;
	}

	public function getVillageForced ()
	{
		try
		{
			$village = $this->getVillage ();
		}
		catch (Neuron_Core_Error $e)
		{
			$village = Dolumar_Players_Village::getVillage ($this->data['village']);
		}

		return $village;
	}

	/*
		Return the construction duration (in seconds)
	*/
	public function getConstructionTime ($village)
	{
		$runes = $village->resources->getUsedRunes_amount (false);
		
		if ($runes < 5)
		{
			return 30;
		}
		return ceil (($runes - 4) * 200 / GAME_SPEED_BUILDINGS);
	}
	
	public function getUpgradeTime ($village)
	{
		return $this->getConstructionTime ($village);
	}
	
	public function setChosenRune ($village, $runeId)
	{
		$b = $this->getBuildingCost ($village);
		if (isset ($b['runeId']) && $b['runeId'] == 'random')
		{
			$this->bc['runeId'] = $runeId;
		}
		
		$u = $this->getUpgradeCost ($village);
		if (isset ($u['runeId']) && $u['runeId'] == 'random')
		{
			$this->upgradeRune = $runeId;
		}
	}

	/*
		Buildinjg costs
	*/
	public function getBuildingCost ($village)
	{
		$bc = $this->bc;
		
		$runes = $village->resources->getUsedRunes_amount (false);
		$level = $this->getLevel ();
		
		if ($runes == 0)
		{
			$runes = 0.6;
		}
		
		foreach ($bc as $k => $v)
		{
			if ($k != 'runeId' && $k != 'runeAmount')
			{
				// Verhoging volgens aantal gebouwen (+ 10 %)
				//$bc[$k] = $v + ( ($v / 10) * $runes );

				// Verhoging volgens level ( * 1.2 ^ $level )
				//$bc[$k] = ceil ( $bc[$k] * pow (1.2, $level ) );
				
				$bc[$k] = ceil ($bc[$k] * $runes);
			}
		}
		return $bc;
	}

	/*
		Upgrade cost:
		The rune used for an update is always the rune used for building the buildingd.
	*/
	public function getUpgradeCost ($village, $level = null)
	{
		$uc = $this->getBuildingCost ($village);
		$out = array ();

		if ($level === null)
		{
			$level = $this->getLevel ();
		}
		
		if (isset ($uc['runeId']) && isset ($uc['runeAmount']))
		{
			$out['runeId'] = $uc['runeId'];
			$out['runeAmount'] = $uc['runeAmount'];
			
			$resources = $this->getUsedRunes ();
			
			if (count ($resources) > 0)
			{
				$f = array_keys ($resources);
				$out['runeId'] = $f[0];
			}
		}
		

		// Make everythign more expensive, but not the runeAmount ofcourse.
		// Formula: res+(res/5)*(level-1)
		
		// Cost to upgrade a building: build cost * ($level * 1.25)
		foreach ($uc as $k => $v)
		{
			if ($k != 'runeId' && $k != 'runeAmount')
			{
				$out[$k] = floor ($v + (($v / 5) * ($level - 1)));
			}
		}
		return $out;
	}
	
	public static function resourceToText ($res, $showRunes = true, $dot = true, $village = false, $runeId = 'rune')
	{
		return Neuron_Core_Tools::resourceToText ($res, $showRunes, $dot, $village, $runeId);
	}
	
	public function getBuildingCost_Text ($village)
	{
		$res = $this->getBuildingCost ($village);
		return $this->resourceToText ($res, true, true, $village, $this->getBuildingId());
	}
	
	public function getUpgradeCost_Text ($village, $showRandom = false)
	{
		$res = $this->getUpgradeCost ($village, null, $showRandom);
		return $this->resourceToText ($res, true, true, false, $this->getBuildingId());
	}
	
	/* GET GENERAL DATA */
	public function getDisplayName ()
	{
		return '<span class="building" title="'.$this->getDescription ().'">'.$this->getName ().'</span>';
	}
	
	public function getName ($multiple = false, $showLevel = false)
	{
		$text = Neuron_Core_Text::__getInstance ();

		$name = $this->getClassName ();
		
		if ($multiple)
		{
			$t = $text->get ($name, 'buildingsMultiple', 'buildings', $name);
		}
		
		else
		{
			$t = $text->get ($name, 'buildings', 'buildings', $name);
		}

		if ($showLevel)
		{
			$t .= ' ' . $text->get ('lvl', 'building', 'building') .
				' ' . $this->getLevel ();
		}

		if ($this->destroyed)
		{
			$t .= ' ('.$text->get ('destroyed', 'building', 'building').')';
		}

		return $t;
	}

	public function getDescription ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		$name = get_class ($this);
		$name = explode ('_', $name);
		$name = $name[count ($name) - 1];
		
		return $text->get ($name, 'desc', 'buildings', false);
	}
	
	public function getGeneralContent ($showAll = false)
	{
		$text = Neuron_Core_Text::__getInstance ();
		
		$page = new Neuron_Core_Template ();
		
		$page->set ('desc', $this->getDescription ());
		
		$page->set ('owner_value', Neuron_Core_Tools::output_varchar ($this->getOwner ()->getNickname ()));		
		$page->set ('village_value', Neuron_Core_Tools::output_varchar ($this->getVillage ()->getName ()));
		
		$page->set ('owner_id', $this->getOwner ()->getId ());
		$page->set ('village_id', $this->data['village']);
		
		if (!$this->getVillage ()->isActive ())
		{
			$page->set ('inactive', true);
		}
		
		$race = $this->getVillage ()->getRace ();
		$page->set ('race_value', $race->getRaceName ());
		
		$page->set ('owner', $text->get ('owner', 'building', 'building'));
		$page->set ('village', $text->get ('village', 'building', 'building'));
		$page->set ('race', $text->get ('race', 'building', 'building'));
		
		$page->set ('location', $text->get ('location', 'building', 'building'));
		
		$n = $this->calculateDirection ($this->tileLocationX, $this->tileLocationY);
		$page->set ('location_value', '['.floor ($this->tileLocationX).','.floor ($this->tileLocationY).'] ('
			.$text->get ($n, 'directions', 'main', $n).')');
		
		if ($showAll)
		{
			$page->set ('level', $text->get ('level', 'building', 'building'));
			$page->set ('level_value', $this->getLevel ());
			
			$page->set ('buildDate', $text->get ('buildDate', 'building', 'building'));
			$page->set ('buildDate_value', date (DATETIME, $this->data['readyDate']));

			$resources = array
			(
				'resources' => $this->getUsedResources (true),
				'runes' => $this->getUsedRunes (true)
			);
			
			if (count ($resources['runes']) > 0)
			{
				$iDifRunes = 0;
			
				$amount = 0;
				$rune = null;
				
				// Check what runes have been used.
				foreach ($resources['runes'] as $k => $v)
				{
					$iDifRunes ++;
				
					$rune = $k;
					$amount += $v;
				}
			
				// If more than one rune is selected, go for random
				if ($iDifRunes > 1)
				{
					$rune = 'randomrune';
				}
		
				$page->set ('usedRune', $text->get ('usedRune', 'building', 'building'));
				$page->set 
				(
					'usedRune_value',
					$amount . ' ' .
					ucfirst (
						$text->get 
						(
							$rune,
							$amount > 1 ? 'runeDouble' : 'runeSingle',
							'main'
						)
					)
				);
			}
			
			$page->set ('back', $text->getClickTo ($text->get ('back', 'building', 'building')));
		}
		
		return $page->parse ('buildings/general_info.tpl');
	}
	
	/*
		By default, thie function returns a description about the building.
	*/
	protected function getCustomContent ($input) 
	{
		return '<p class="maybe">'.$this->getDescription ().'</p>';
	}
	
	/*
		Hide the general options.
	*/
	protected function hideGeneralOptions ()
	{
		$this->bShowOptions = false;
	}

	protected function hideTechnologyUpgrades ()
	{
		$this->bShowTechnologyUpgrades = false;
	}

	public function getMyContent ($input)
	{
		$text = Neuron_Core_Text::__getInstance ();
		$lvl = $this->getLevel ();

		$page = new Neuron_Core_Template ();
		$page->set ('custom', $this->getCustomContent ($input));
		
		$page->set ('showOptions', $this->bShowOptions);
		
		$text->setFile ('building');
		$text->setSection ('building');
		
		$page->set ('general', $text->get ('general'));

		if ($this->isFinished ())
		{
			$page->set ('overview', $text->get ('overview'));

			if ($this->isUpgradeable ())
			{
				$page->set 
				(
					'upgrade', 
					Neuron_Core_Tools::putIntoText
					(
						$text->get ('upgrade'),
						array
						(
							$this->getName (false),
							($lvl + 1)
						)
					)
				);

				$page->set ('upgradeCost', $this->getUpgradeCost_Text ($this->getVillage (), true));
			}

			if ($this->isDestructable ())
			{
				$page->set ('destruct', $text->get ('destruct'));
				$page->set ('destruct_url', "{'action':'destruct','key':'".Neuron_Core_Tools::getConfirmLink ()."'}");
			}

			$page->set ('confirmTxt', addslashes ($text->get ('confirm', 'destruct')));

			// Now: lets add the knowledge
			if ($this->bShowTechnologyUpgrades)
			{
				$this->addTechnologyUpgrades ($page);
			}
			
			return $page->parse ('buildings/general_mine.tpl');
		}

		else
		{
			/* Upgrading */
			if ($this->data['lastUpgradeDate'] > $this->data['readyDate'])
			{
				$page->set ('timeLeft', Neuron_Core_Tools::getCountdown ($this->data['lastUpgradeDate']));

				$page->set ('txt', $text->get ('upgrading'));
				$page->set ('tl', $text->get ('timeLeft'));
			}

			/* Constructing */
			else
			{
				$page->set ('timeLeft', Neuron_Core_Tools::getCountdown ($this->data['readyDate']));
				$page->set ('txt', $text->get ('constructing'));
				$page->set ('tl', $text->get ('timeLeft'));
				
				$page->set ('cancel', $text->getClickTo ($text->get ('cancel')));
				$page->set ('cancel_url', "{'action':'destruct','key':'".Neuron_Core_Tools::getConfirmLink ()."'}");
				$page->set ('confirmTxt', addslashes ($text->get ('confirm', 'destruct')));
			}

			return $page->parse ('buildings/general_const.tpl');
		}
	}
	
	public function getTechnologies ()
	{
		$out = array ();
		$technologies = $this->getTechnologyUpgrades ();
		
		foreach ($technologies as $v)
		{
			$v = Dolumar_Technology_Technology::getTechnology ($v);
			if ($v->checkRace ($this))
			{
				$out[] = $v;
			}
		}
		
		return $out;
	}

	private function addTechnologyUpgrades ($page)
	{
		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('building');
		$text->setSection ('technology');

		$page->set ('technology', $text->get ('technology'));

		$technologies = $this->getTechnologyUpgrades ();
		
		$village = $this->getVillage ();
		foreach ($technologies as $v)
		{
			$technology = Dolumar_Technology_Technology::getTechnology ($v);
			
			if (
				!$village->hasTechnology ($v, true)
				&& $technology->canResearch ($this)
			)
			{
				$page->addListValue
				(
					'technology',
					array
					(
						$technology->getName (),
						$v
					)
				);
			}
		}
	}

	public function getUpgradeTechnology ($input)
	{
		$s_technology = $input['technology'];
		$technologies = $this->getTechnologyUpgrades ();

		$text = Neuron_Core_Text::__getInstance ();
		$text->setFile ('building');
		$text->setSection ('technology');

		$page = new Neuron_Core_Template ();

		$page->set ('title', $text->get ('technology'));

		$village = $this->getVillage ();
		if
		(
			in_array ($s_technology, $technologies)
			&& !$village->hasTechnology ($s_technology, true)
		)
		{
			$technology = Dolumar_Technology_Technology::getTechnology ($s_technology);
			$showForm = true;

			if ($technology->canResearch ($this))
			{
				if (isset ($input['confirm']) && Neuron_Core_Tools::checkConfirmLink ($input['confirm']))
				{
					$cost = $technology->getResearchCost ();

					if (isset ($cost['runeId']) && $cost['runeId'] == 'random' && isset ($input['runeSelection']))
					{
						$cost['runeId'] = $input['runeSelection'];
					}

					if ($village->resources->takeResourcesAndRunes ($cost))
					{
						if ($village->trainTechnology ($technology))
						{
							$showForm = false;
							$page->set ('done', $text->get ('done'));
							reloadStatusCounters ();
						}
						else
						{
							$page->set ('error', 'Error: couldn\'t start the research.');
						}
					}
					else
					{
						$page->set ('error', $text->get ('notEnoughResources'));
					}
				}

				if ($showForm)
				{
					$page->set
					(
						'youSure',
						Neuron_Core_Tools::putIntoText
						(
							$text->get ('youSure'),
							array
							(
								Neuron_Core_Tools::output_varchar ($technology->getName ())
							)
						)
					);

					$page->set ('description', Neuron_Core_Tools::output_varchar ($technology->getDescription ()));

					$page->set
					(
						'cost',
						Neuron_Core_Tools::putIntoText
						(
							$text->get ('costTime'),
							array
							(
								$this->resourceToText ($technology->getResearchCost (), true, true, false, $this->getBuildingId())
							)
						)
					);

					$page->set
					(
						'duration',
						Neuron_Core_Tools::putIntoText
						(
							$text->get ('duration'),
							array
							(
								Neuron_Core_Tools::getDuration ($technology->getDuration ())
							)
						)
					);

					$page->set
					(
						'confirm',
						$text->getClickto ($text->get ('toConfirm'))
					);

					$page->set ('technology', $s_technology);
					$page->set ('confirmation', Neuron_Core_Tools::getConfirmLink ());
				}
			}
			else
			{
				return '<p>Invalid input: technology not researchable.</p>';
			}
		}
		else
		{
			$page->set ('notFound', $text->get ('notFound'));
		}

		$page->set
		(
			'return',
			$text->getClickto ($text->get ('toReturn'))
		);
		
		return $page->parse ('buildings/general_tech.tpl');
	}

	/*
		This function will destruct the building (without the fancy html stuff)
	*/
	public function doDestructBuilding ($evenTowncenter = false, $date = NOW, $log = true)
	{
		if ($date == NOW)
		{
			$date --;
		}
	
		// Call the onDestroy trigger
		$this->onDestruct ();
	
		$db = Neuron_Core_Database::__getInstance ();
	
		// Get resources
		$timeLeft = $this->data['readyDate'] - time ();
		$duration = $this->data['readyDate'] - $this->data['startDate'];

		// Procent
		$procent = ($timeLeft / max (1, $duration)) - 0.2;

		// Give resources back!
		if ($procent > 0)
		{
			// Calculate the amount of resources
			$res = $this->getUsedResources (false, true);

			$new_array = array ();
			foreach ($res as $k => $v)
			{
				if (is_numeric ($v) && $k != 'runeAmount')
				{
					$new_array[$k] = floor ($v * $procent);
				}
				
				elseif ($k == 'runeAmount')
				{
					$new_array[$k] = $v * $this->getLevel ();
				}

				else
				{
					$new_array[$k] = $v;
				}
			}
			
			// Give resources back.
			$this->getVillage()->resources->giveResourcesAndRunes ($new_array);
		}

		else
		{
			// Only give rune back :)
			$runes = $this->getUsedRunes (true);
			
			if (!$this->getVillage ()->moduleExists ('resources'))
			{
				throw new Neuron_Core_Error ('Module could not be loaded: resources');
			}
			
			$this->getVillage ()->resources->giveRunes ($runes);
		}
		
		$this->getVillage()->onDestroy ($this, $log);
		
		$loc = $this->getLocation ();
		Neuron_GameServer::addMapUpdate ($loc[0], $loc[1], 'DESTROY');
		
		// Remove the item from the database
		$db->update
		(
			'map_buildings',
			array ('destroyDate' => $date),
			"bid = '".$this->id."'"
		);
	}
	
	/**
	*	Function that is called whenever the
	*	building gets destructed.
	*/
	protected function onDestruct ()
	{
	}
	
	/**
	*	Method that is called after this building was build
	*/
	protected function onBuild ()
	{
	
	}

	public function destructBuilding ($date = NOW, $log = true)
	{
		// Remove building
		$this->doDestructBuilding (false, $date, $log);
		
		$page = new Neuron_Core_Template ();
		$text = Neuron_Core_Text::__getInstance ();		
		$page->set ('done', $text->get ('done', 'destruct', 'building'));
		return $page->parse ('buildings/general_destr.tpl');
	}
	
	private function sumArray (&$array, $newarray)
	{
		foreach ($newarray as $k => $v)
		{
			if (!isset ($array[$k]))
			{
				$array[$k] = $v;
			}
			else
			{
				$array[$k] += $v;
			}
		}
	}
	
	public function getUsedResources ($includeUpgradeRunes = false, $oldSystem = false)
	{
		$res = $this->getUsedAssets ($includeUpgradeRunes);
		
		$out = $res['resources'];
		
		if ($oldSystem && count ($res['runes']) > 0)
		{
			foreach ($res['runes'] as $k => $v)
			{
				$out['runeId'] = $k;
				$out['runeAmount'] = $v;
			}
		}
		
		return $out;
	}
	
	protected function calcUsedAssets ($includeUpgradeRunes = false)
	{
		$data = $this->data['usedResources'];
	
		if (substr ($data, 0, 1) == '[')
		{
			$json = json_decode ($data, true);
		
			$resources = array ();
			$runes = array ();
		
			foreach ($json as $v)
			{
				if (isset ($v['runes']))
				{
					$this->sumArray ($runes, $v['runes']);
				}
			
				if (isset ($v['resources']))
				{
					$this->sumArray ($resources, $v['resources']);
				}
			
				if (!$includeUpgradeRunes)
				{
					break;
				}
			}
		
			return array
			(
				'resources' => $resources,
				'runes' => $runes
			);
		}
		else
		{
			// Old system.
			$res = explode ('|', $data);
			$r = array ();
			foreach ($res as $k => $v)
			{
				$d = explode ('=', $v);
				if (count ($d) == 2)
				{
					$r[$d[0]] = $d[1];
				}
			}
		
			if ($includeUpgradeRunes && isset ($r['runeAmount']))
			{
				$r['runeAmount'] *= $this->getLevel ();
			}
			
			$runes = array ();
			if (isset ($r['runeId']) && isset ($r['runeAmount']))
			{
				$runes = array
				(
					$r['runeId'] => $r['runeAmount']
				);
			}
			
			unset ($r['runeId']);
			unset ($r['runeAmount']);
		
			return array
			(
				'resources' => $r,
				'runes' => $runes
			);
		}
	}
	
	public function getUsedRunes ($includeUpgradeRunes = false)
	{
		$res = $this->getUsedAssets ($includeUpgradeRunes);
		return $res['runes'];
	}

	/*
		Return the amount of used resources 
		(only the amount used for actually building the building)
	*/
	public function getUsedAssets ($includeUpgradeRunes = false)
	{
		return $this->calcUsedAssets ($includeUpgradeRunes);
	}
	
	/* MAP OPTIONS */
	public function getTileOffset ()
	{
		return array
		(
			ceil ($this->tileLocationX) - $this->tileLocationX,
			ceil ($this->tileLocationY) - $this->tileLocationY
		);
	}
	
	public function getSize ()
	{
		return array (1, 1);
	}
	
	private function calculateDirection ($x, $y)
	{		
		return Dolumar_Map_Map::getDirection ($x, $y);
	}

	public function setDestroyed ($t = true)
	{
		$this->destroyed = $t;
	}

	public function getIsoImage ($race = false)
	{
		if ($this->destroyed)
		{
			return $this->getRubbleImage ($race);
		}

		else
		{
			return $this->getImage ($race);
		}
	}
	
	/*
		We are using this one because it's newer and cooler and stuff.
		This creates the actual image, rendering all other methods useless.
	*/
	public function getDisplayObject ($race = false)
	{
		list ($x, $y) = $this->getTileOffset ();
		
		$url = $this->getImageUrl ($race);
	
		$offset = new Neuron_GameServer_Map_Offset ($x, $y);
		$image = new Neuron_GameServer_Map_Display_Sprite ($url, $offset);
		
		return $image;
	}
	
	/*
		This function returns the full URL
		for the image
	*/
	public function getImageUrl ($race = false)
	{
		return IMAGE_URL.'sprites/'.$this->getIsoImage ($race).'.png';
	}
	
	public function getImagePath ()
	{
		return IMAGE_PATH.'sprites/'.$this->getIsoImage ($race).'.png';
	}

	public function getImage ($race = false)
	{
		$name = strtolower ($this->getClassName ());

		if (!$race && isset ($this->data['village']))
		{
			$race = $this->getVillage()->getRace ();
		}
		
		if ($race)
		{
			$race = strtolower ($race->getName ());
			
			if (file_exists (IMAGE_PATH.'sprites/'.$race.'_'.$name.'.png'))
			{
				$name = $race.'_'.$name;
			}
		}
		
		return $name;
	}
	
	public function getRubbleImage ($race = false)
	{
		$size = $this->getSize ();

		if ($size[0] == 0.5 && $size[1] == 0.5)
		{
			$name = 'rubblesmall';
		}

		else
		{
			$name = 'rubble';
		}
		
		if (!$race && isset ($this->data['village']))
		{
			$race = $this->getVillage()->getRace ();
		}
		
		if ($race)
		{
			$race = strtolower ($race->getName ());
			
			if (file_exists (IMAGE_PATH.'sprites/'.$race.'_'.$name.'.png'))
			{
				$name = $race.'_'.$name;
			}
		}
		
		return $name;
	}
	
	public function getSmallImage ($race = false)
	{
		$name = strtolower ($this->getClassName ());
	
		if ($race)
		{
			$race = strtolower ($race->getName ());
			
			if (file_exists (IMAGE_PATH.'buildings/'.$race.'_'.$name.'.png'))
			{
				$name = $race.'_'.$name;
			}
		}

		return IMAGE_URL.'buildings/'.$name.'.png';
	}

	// Return map color (R,G,B)
	public function getMapColor () { return array (128, 0, 0); }
	
	/* BUILD FUNCTION */
	
	/*
		This function just checks if this building
		can be build on this part of the MAP!
	*/
	public function checkMapLocation ($village, $x, $y)
	{
		$location = Dolumar_Map_Location::getLocation ($x, $y);
		return $location->canBuildBuilding ();
	}
	
	public function isMoveable ()
	{
		return true;
	}
	
	public function checkBuildLocation ($village, $x, $y)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$range = $village->getBuildingRadius ();
		
		/*
		
		$townCenter = $db->select
		(
			'map_buildings',
			array ('xas, yas'),
			"buildingType = '1' AND village = '".$village->getId ()."'"
		);
		
		*/
		
		$townCenter = $village->buildings->getTownCenter ();
		
		if ($townCenter)
		{
			$size = $this->getSize ();
			
			// Don't allow buildings spreading over multiple tiles.
			if ($size[0] > 0.5) { $x = floor ($x); }
			if ($size[1] > 0.5) { $y = floor ($y); }
			
			// Check for range
			$loc = $townCenter->getLocation ();
			
			$d = sqrt ( pow ($loc[0] - $x, 2) + pow ($loc[1] - $y, 2) );
			
			// Niet goed: te ver van het town center
			if ($d > $range)
			{
				return array (false, 'outOfRange', array ($range));
			}
			
			else
			{
				// Check for other buildings in the area
				$location = Dolumar_Map_Location::getLocation ($x, $y);
				
				$chk = $db->select
				(
					'map_buildings',
					array ('bid'),
					"xas > ('".($x)."' - sizeX) AND xas < '".($x+$size[0])."' ".
					"AND yas > ('".($y)."' - sizeY) AND yas < '".($y+$size[1])."' ".
					"AND destroyDate = '0'"
				);
				
				// Alles is okay
				if (count ($chk) == 0 && $location->canBuildBuilding ())
				{
					return array (true, array ($x, $y));
				}
				
				// buildingOverlap
				else
				{
					return array (false, 'buildingOverlap');
				}
			}
		}
		
		else
		{
			return array (false, 'townCenterNotFound');
		}
	}
	
	/*
		Build this building and return the new building object.
	*/
	public function build ($village, $x, $y, $useDuration = true)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		if ($useDuration) { $constructionTime = $this->getConstructionTime ($village); }
		else { $constructionTime = 0; }
		
		$size = $this->getSize ();
		$bcost = $this->getBuildingCost ($village);

		$resources = array ();
		$runes = array ();
		
		if (isset ($bcost['runeId']) && isset ($bcost['runeAmount']))
		{
			$runes[$bcost['runeId']] = $bcost['runeAmount'];
		}
		
		unset ($bcost['runeId']);
		unset ($bcost['runeAmount']);
		
		foreach ($bcost as $k => $v)
		{
			$resources[$k] = $v;
		}
		
		$t = json_encode
		(
			array
			(
				array
				(
					'resources' => $resources,
					'runes' => $runes
				)
			)
		);
		
		$data = array
		(
			'xas' => $x,
			'yas' => $y,
			'sizeX' => $size[0],
			'sizeY' => $size[1],
			'buildingType' => $this->getBuildingId (),
			'village' => $village->getId (),
			'startDate' => time (),
			'readyDate' => (time() + $constructionTime),
			'usedResources' => $t
		);
		
		$id = $db->insert
		(
			'map_buildings',
			$data
		);
		
		$village->buildings->reloadBuildings ();
		$village->resources->reloadRunes ();
		
		reloadEverything ();
		
		Neuron_GameServer::addMapUpdate ($x, $y, 'BUILD');
		
		$obj = self::getBuilding ($this->getBuildingId (), $village->getRace (), $x, $y);
		$obj->setVillage ($village);
		$obj->setData ($id, $data);
		
		// Triggers!
		$obj->onBuild ();
		
		return $obj;
	}

	public function speedupBuild ($amount)
	{
		$profiler = Neuron_Profiler_Profiler::__getInstance ();
		$profiler->start ('Speeding up building ' . $this->getId () . ' with ' . $amount . ' seconds.');

		// Now let's check if $amount is bigger than timeleft
		if ($amount > $this->getTimeLeft ())
		{
			$amount = $this->getTimeLeft ();
		}
		$result = $this->delayBuild (0 - $amount);

		$profiler->stop ();

		return $result;
	}
	
	public function delayBuild ($amount)
	{
		$db = Neuron_DB_Database::getInstance ();
	
		if (!$this->isFinishedBuilding ())
		{
			$db->query
			("
				UPDATE
					map_buildings
				SET
					readyDate = readyDate + {$amount}
				WHERE
					bid = {$this->getId ()}
			");
		}
	
		elseif (!$this->isFinished ())
		{
			$db->query
			("
				UPDATE
					map_buildings
				SET
					lastUpgradeDate = lastUpgradeDate + {$amount}
				WHERE
					bid = {$this->getId ()}
			");
		}
	}
	
	/*
		Add a new round of used resource
	*/
	private function addUsedResources ($resources, $runes)
	{	
		$db = Neuron_DB_Database::getInstance ();
		
		$data = $this->data['usedResources'];
	
		// Old system
		if (substr ($data, 0, 1) != '[')
		{
			$new = array
			(
				array
				(
					'runes' => $this->getUsedRunes (true),
					'resources' => $this->getUsedResources (true)
				)
			);
		}
		
		else
		{
			$new = json_decode ($this->data['usedResources'], true);
		}
		
		$new[] = array
		(
			'runes' => $runes,
			'resources' => $resources
		);
		
		$new = json_encode ($new);
		
		$db->query
		("
			UPDATE
				map_buildings
			SET
				usedResources = '{$db->escape ($new)}'
			WHERE
				bid = {$this->getId ()}
		");
	}
	
	public function getUpgradeContent ($confirm = false)
	{
		$text = Neuron_Core_Text::__getInstance ();
		
		if ($confirm)
		{
			$input = $this->objWindow->getInputData ();
			
			if (isset ($input['runeSelection']))
			{
				$this->setChosenRune ($this->getVillage (), $input['runeSelection']);
			}
		
			if ($this->doUpgradeBuilding ())
			{
				return $this->showUpgradeContent ('done');
			}
			
			elseif (isset ($input['queue']) && $input['queue'] == 'true')
			{
				return $this->getQueueContent ();
			}
			
			else
			{
				return $this->showUpgradeContent ($this->sError);
			}
		}
		
		else
		{
			return $this->showUpgradeContent ();
		}
	}
	
	/*
		Try to upgrade a building
	*/
	public function doUpgradeBuilding ()
	{
		$res = $this->getUpgradeCost ($this->getVillage ());
		
		$objLock = Neuron_Core_Lock::__getInstance ();
		
		if ($objLock->setLock ('upgradebuilding', $this->getId ()))
		{
			$return = false;
			
			$duration = $this->getUpgradeTime ($this->getVillage ());
		
			// Check some stuff
			if (!$this->isFinished ())
			{
				$this->sError = 'unfinished';
			}
			elseif (!$this->getVillage ()->readyToBuild ())
			{
				$this->sError = 'busy';
			}
			elseif (!$this->getVillage ()->resources->takeResourcesAndRunes ($res))
			{
				$this->sError = $this->getVillage ()->resources->getError ();
			}
			else
			{
				$db = Neuron_Core_Database::__getInstance ();
	
				// Reload buildings & runes
				$this->getVillage()->buildings->reloadBuildings ();
				$this->getVillage()->resources->reloadRunes ();
	
				$this->getVillage()->buildings->increaseBuildingLevel ($this, $this->getLevel () + 1);
	
				// Update duration
				$db->update
				(
					'map_buildings',
					array
					(
						'lastUpgradeDate' => time () + $duration,
						'bLevel' => '++'
					),
					"bid = '".$this->id."'"
				);
	
				// Reload windows
				if (isset ($this->objWindow))
				{
					reloadEverything ();
					reloadStatusCounters ();
	
					$loc = $this->getLocation ();
					$this->objWindow->reloadLocation ($loc[0], $loc[1]);
				}
				
				$runes = array ();
				if (isset ($res['runeId']) && isset ($res['runeAmount']))
				{
					$runes[$res['runeId']] = $res['runeAmount'];
				}
				
				unset ($res['runeId']);
				unset ($res['runeAmount']);
				
				$this->addUsedResources ($res, $runes);
				
				$this->getVillage ()->onUpgrade ($this);
		
				$return = true;
			}
			
			$objLock->releaseLock ('upgradebuilding', $this->getId ());

			return $return;
		}
		else
		{
			return false;
		}
	}
	
	/*
		Return queued content
	*/
	private function getQueueContent ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		
		$res = $this->getUpgradeCost ($this->getVillage ());
		
		$data = array
		(
			'building' => $this->getId (),
			'level' => ($this->getLevel () + 1),
			'rune' => $res['runeId']
		);
	
		if ($this->getVillage ()->premium->addQueueAction ('upgrade', $data))
		{
			$text = Neuron_Core_Text::__getInstance ();
			//$this->objWindow->alert ($text->get ('doneUpgrade', 'queue', 'building'));
			reloadStatusCounters ();
		}
		else
		{
			$this->objWindow->alert ($this->getVillage ()->premium->getError (true));
		}
	}
	
	/*
		This function will return a small amount of HTML to put in the upgrade window.
	*/
	protected function getUpgradeInformation ()
	{
		return null;
	}
	
	private function showUpgradeContent ($error = null, $errorA = array ())
	{
		$text = Neuron_Core_Text::__getInstance ();
		$page = new Neuron_Core_Template ();
		
		$input = $this->objWindow->getInputData ();
		
		$lvl = $this->getLevel ();

		if (!empty ($error)) 
		{
			if ($error == 'unfinished')
			{
				$errorA = array (Neuron_Core_Tools::getCountdown (max ($this->data['lastUpgradeDate'], $this->data['readyDate'])));
			}
		}
		elseif (!$this->getVillage()->readyToBuild())
		{
			$error = 'busy';
		}
		
		elseif (!$this->isFinished ())
		{
			$error = 'unfinished';
			$errorA = array (Neuron_Core_Tools::getCountdown (max ($this->data['lastUpgradeDate'], $this->data['readyDate'])));
		}

		if (!empty ($error))
		{
			$txterr = Neuron_Core_Tools::putIntoText ($text->get ($error, 'upgradeError', 'building'), $errorA);
			
			$jsondata = json_encode
			(
				array
				(
					'page' => 'upgrade',
					'queue' => 'true',
					'upgrade' => 'confirm',
					'runeSelection' => isset ($input['runeSelection']) ? $input['runeSelection'] : null
				)
			);
	
			$page->set ('error', $txterr);
			$page->set ('errorV', $error);
			
			if ($error != 'done' && isset ($input['upgrade']))
			{
				$this->objWindow->dialog 
				(
					$txterr, 
					$text->get ('queueUpgrade', 'queue', 'building'), 
					'windowAction (this, '.$jsondata.');', 
					$text->get ('okay', 'main', 'main'), 
					'void(0);'
				);
			}
		}
		
		$page->set ('about', $text->get ('about', 'upgrade', 'building'));
		$page->set ('upgrade', $text->get ('upgrade', 'upgrade', 'building'));
		$page->set ('cost', $text->get ('cost', 'upgrade', 'building'));
		$page->set ('duration', Neuron_Core_Tools::getDurationText ($this->getUpgradeTime ($this->getVillage ())));
		
		$page->set ('info', $this->getUpgradeInformation ());
		
		$page->set
		(
			'upgradeAll', 
			Neuron_Core_Tools::putIntoText 
			(
				$text->get ('upgradeAll', 'upgrade', 'building'), 
				array ($this->getName (true))
			)
		);
		
		$page->set 
		(
			'upgradeLink', 
			Neuron_Core_Tools::putIntoText
			(
				$text->get ('startUpgrade', 'upgrade', 'building'),
				array
				(
					$this->getName (true),
					($lvl + 1)
				)
			)
		);
		
		$upgrade = $this->getUpgradeCost_text ($this->getVillage (), true);
		$page->set ('upgradeCost', $upgrade);
		
		$page->set ('back', $text->getClickTo ($text->get ('back', 'building', 'building')));
		
		return $page->parse ('buildings/general_upgrade.tpl');
	}

	/*
		Loop trough ALL technologies
		and select the ones that have this building.
	*/
	public function getTechnologyUpgrades ()
	{ 
		$stats = Neuron_Core_Stats::__getInstance ();
		//$up = $stats->getSection ($this->getClassName (), 'research');
		$allTechnologies = $stats->getFile ('technology');
		
		$building = $this->getClassname ();
		
		$out = array ();
		foreach ($allTechnologies as $name => $tech)
		{
			if (isset ($tech['building']) && $tech['building'] == $building)
			{
				$out[] = $name;
			}
		}
		
		return $out;

		/*
		if ($up && is_array ($up))
		{
			return $up;
		}
		else
		{
			return array ();
		}
		*/
	}
	
	public function getScore ()
	{
		return ceil ($this->points * $this->getLevel ());
		//return ceil ( $this->points + ( ( $this->points / 5 ) * $this->getLevel () ) );
	}
	
	/*
		Building requirements
	*/
	protected function addRequiresBuilding ($buildingid, $minlevel = 1)
	{
		if (!isset ($this->requirements['buildings']))
		{
			$this->requirements['buildings'] = array ();
		}
		
		$this->requirements['buildings'][$buildingid] = $minlevel;
	}
	
	public function getRequiredBuildings ()
	{
		if (!isset ($this->requirements['buildings']))
		{
			$this->requirements['buildings'] = array ();
		}
		
		$out = array ();
		foreach ($this->requirements['buildings'] as $k => $v)
		{
			$out[] = array
			(
				'building' => self::getBuilding ($k, $this->getRace ()),
				'amount' => $v
			);
		}
		return $out;
	}
	
	public function canBuildBuilding (Dolumar_Players_Village $village)
	{
		return $this->buildRequirementCheck ($village);
	}
	
	protected function buildRequirementCheck (Dolumar_Players_Village $village)
	{
		foreach ($this->getRequiredBuildings () as $v)
		{
			if ($village->buildings->getBuildingAmount ($v['building']) < $v['amount'])
			{
				return false;
			}
		}
		
		return true;
	}
	
	/*
		This is an additional check in the building levels.
		According to the new rules, you cannot build a new building unless
		all your other buildings are at the same level. (which is the highest)
	*/
	public function checkBuildingLevels ($village)
	{
		$amount = $village->buildings->getBuildingAmount ($this, true);
		foreach ($village->buildings->getBuildings () as $building)
		{
			$class = get_class($this);
			if (get_class ($building) == $class && $building->getLevel () <= $amount)
			{
				return false;
			}
		}
		return true;
	}
	
	public function isUpgradeable ()
	{
		//return $this->getLevel () < 5;
		return true;
	}
	
	/*
		This returns an array that can be used
		by the map to draw a "status" symbol.
		Should always return an array
		(
			'status' => 'building'
			'start' => 'start date',
			'end' => 'end date'
		)
	*/
	public function getMapStatus ()
	{
		return array 
		(
			'status' => 'finished',
			'start' => 0,
			'end' => 0
		);
	}

	/* BUILDING SPECIFIC */
	public function getIncome ()
	{
		return false;
	}
	
	public function getCapacity ()
	{
		return false;
	}
	
	public function getUnitCapacity ()
	{
		return false;
	}
	
	public function getGuards ()
	{
		return false;
	}
	
	public function getLogArray ()
	{
		return array ();
	}
	
	public function getBuildingError ()
	{
		return $this->sError;
	}
	
	public function isDestructable ()
	{
		return true;
	}
	
	public function getDestroyDate ()
	{
		return $this->data['destroyDate'];
	}

	// Default: do nothing.
	public function onClanLeave ()
	{

	}
	
	public function __toString ()
	{
		return $this->getDisplayName ();
	}
	
	public function equals (Dolumar_Buildings_Building $building)
	{
		return $this->getId () == $building->getId ();
	}
	
	public function __destruct ()
	{
		unset ( $this->id );
		unset ( $this->data );
		unset ( $this->tileLocationX );
		unset ( $this->tileLocationY );
		unset ( $this->destroyed );
	
		unset ( $this->bShowOptions );
	
		unset ( $this->objWindow );
	
		unset ( $this->sError );
	
		unset ( $this->oVillage );
	}
}
?>
