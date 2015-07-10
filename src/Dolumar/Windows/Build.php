<?php

class Dolumar_Windows_Build extends Neuron_GameServer_Windows_Window
{

	private $village;
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$login = Neuron_Core_Login::__getInstance ();
		
		// Window settings
		$this->setAllowOnlyOnce ();
		
		$data = $this->getRequestData ();
		$this->village = Dolumar_Players_Village::getMyVillage ($data['vid']);
		
		if ($login->isLogin () && $this->village && $this->village->isActive ())
		{
			$this->setTitle ($text->get ('build', 'menu', 'main').
				' ('.Neuron_Core_Tools::output_varchar ($this->village->getName ()).')');
			$this->setSize ('290px', '300px');
		}
		
		else 
		{
			$this->setTitle ($text->get ('build', 'menu', 'main'));
			$this->setSize ('250px', '150px');
		}
	}
	
	public function getContent ($errorInput = null, $putIntoText = array ())
	{
		// Keep original value in seperate variable!
		$error = $errorInput;
	
		$text = Neuron_Core_Text::__getInstance ();
		$login = Neuron_Core_Login::__getInstance ();
		$me = Neuron_GameServer::getPlayer ();
		$input = $this->getInputData ();
		
		if 
		(
			$login->isLogin () && 
			$this->village &&
			$this->village->isActive () && 
			$this->village->getOwner()->getId() == $me->getId ())
		{
			// Make sure you are not in vacation mode.
			if ($this->village->getOwner()->inVacationMode ())
			{
				return '<p class="false">'.$text->get ('vacationMode', 'main', 'main').'</p>';
			}
		
			$page = new Neuron_Core_Template ();
			
			$page->set ('intro', $text->get ('intro', 'build', 'building'));
			$page->set ('construct', $text->get ('construct', 'build', 'building'));
			$page->set ('click', $text->get ('click', 'build', 'building'));
			
			$selectrunewarning = addslashes ($text->get ('selectRune', 'build', 'building'));
			
			$page->set ('selectRune', $selectrunewarning);
			
			$page->set ('village', $this->village->getId ());
			
			// Workers are working.
			if (!isset ($error) && !$this->village->readyToBuild ())
			{
				$error = 'stillConstructing';
			}
			
			// Show error.
			if (isset ($error))
			{
				$txterr = Neuron_Core_Tools::putIntoText 
				(
					$text->get ($error, 'buildError', 'building'), 
					$putIntoText
				);

				$page->set ('error', $txterr);				
				$page->set ('errorV', $error);
				
				if (isset ($errorInput) && !empty ($errorInput) && $errorInput != 'done')
				{
					if ($error != 'done')
					{
						switch ($error)
						{
							case 'noRunes':
							case 'noResources':
							case 'stillConstructing':
					
								$data = $this->getInputData ();
					
								$jsondata = json_encode
								(
									array
									(
										'action' => 'queue',
										'building' => $data['building'],
										'x' => $data['x'],
										'y' => $data['y'],
										'rune' => $data['rune']
									)
								);
					
								$this->dialog 
								(
									$txterr, 
									$text->get ('queueBuild', 'queue', 'building'), 
									'windowAction (this, '.$jsondata.');', 
									$text->get ('okay', 'main', 'main'), 
									'void(0);'
								);
							break;
						
							default:
								$this->alert ($txterr);
							break;
						}
					}
				}
			}
			
			// Get all buildings (even those who can't be build)
			$buildings = Dolumar_Buildings_Building::getAllBuildings ();
			
			$buildings_out = array ();
			
			$race = $this->village->getRace ();

			foreach ($buildings as $buildingV)
			{
				$building = Dolumar_Buildings_Building::getBuildingFromName ($buildingV, $this->village->getRace ());
				
				if ($building->canBuildBuilding ($this->village))
				{
					$duration = $building->getConstructionTime ($this->village);
					
					$dur = Neuron_Core_Tools::getDuration ($duration);
					$size = $building->getSize ();
					
					$buildings_out[] = array 
					(
						$building->getName (), 
						$building->getBuildingCost_Text ($this->village), 
						$building->getDescription (),
						$building->getSmallImage ($race),
						$building->getBuildingId (),
						$dur,
						$building->getImage ($this->village->getRace ()),
						$size[0],
						$size[1],
						
						// Building level
						'canBuild' => $building->checkBuildingLevels ($this->village),
						'upgrade' => Neuron_Core_Tools::putIntoText 
						(
							$text->get ('upgradeFirst', 'build', 'building'), 
							array ('name' => $building->getName (true))
						),
						
						'myBuildingsName' => $building->getName ($this->village->buildings->getBuildingAmount ($building) > 1),
						
						'action' => new Dolumar_View_SelectBuildLocation 
						(
							$building->getBuildingId (), 
							'build',
							'building_' . $building->getBuildingId (),
							$building->getDisplayObject ($race),
							null,
							$selectrunewarning
						)
					);
				}
			}
			
			// Order the list
			//$page->sortList ('buildings');
			
			usort ($buildings_out, array ($this, 'order_buildings'));
			
			$page->set ('list_buildings', $buildings_out);
			
			return $page->parse ('build.tpl');
		}
		
		else 
		{
			return '<p class="false">'.$text->get ('login', 'login', 'account').'</p>';
		}
	
	}
	
	public function order_buildings ($a, $b)
	{
		if ($a['canBuild'] && !$b['canBuild'])
		{
			return -1;
		}
		elseif ($b['canBuild'] && !$a['canBuild'])
		{
			return 1;
		}
		else
		{
			return $a[0] > $b[0] ? 1 : -1;
		}
	}

	public function getRefresh () {}
	
	public function processInput ()
	{
		//$this->dialog ('Dit is een kleine test.', 'Add to que', "windowAction (this, {'debug':'test'});", 'Cancel', "alert('nope');");
	
		$login = Neuron_Core_Login::__getInstance ();
		$db = Neuron_Core_Database::__getInstance ();
		
		if ($login->isLogin () && $this->village && $this->village->isActive ())
		{	
			$data = $this->getInputData ();
			
			$action = isset ($data['action']) ? $data['action'] : 'build';
			
			switch ($action)
			{
				case 'queue':
					$this->processQueue ();
				break;
			
				case 'build':
				default:
					$this->processBuildInput ();
				break;
			}
		}
	}

	/*
		Process queue input
	*/	
	private function processQueue ()
	{
		$data = $this->getInputData ();
		
		// Check if this player has premium account
		$owner = $this->village->getOwner ();
		
		// Everything is alright.
		if (isset ($data['building']) && isset ($data['rune']) && isset ($data['x']) && isset ($data['y']))
		{
			// Get building
			$building = Dolumar_Buildings_Building::getBuilding ($data['building'], $this->village->getRace ());
		
			if (isset ($data['rune']))
			{
				$building->setChosenRune ($this->village, $data['rune']);
			}
			
			/*
				Check if the game allows building this building now
			*/
			if (!$building->canBuildBuilding ($this->village))
			{			
				$this->updateContent ($this->getContent ('techlevel'));
			}
		
			/*
				Limit the amount of buildings
			*/
			elseif (!$building->checkBuildingLevels ($this->village))
			{
				$this->updateContent 
				(
					$this->getContent 
					(
						'buildinglevel',
						array
						(
							'building' => $building->getName (false),
							'buildings' => $building->getName (true),
							'level' => ($this->village->buildings->getBuildingAmount ($building) + 1)
						)
					)
				);
			}
			
			else
			{
				// Everything seems to work out fine.
				// Add to the queue
				$data = array
				(
					'building' => $building->getBuildingId (),
					'rune' => $data['rune'],
					'x' => $data['x'],
					'y' => $data['y']
				);
				
				if ($this->village->premium->addQueueAction ('build', $data))
				{
					$text = Neuron_Core_Text::__getInstance ();
					//$this->alert ($text->get ('doneBuild', 'queue', 'building'));
					reloadStatusCounters ();
				}
				else
				{
					$this->alert ($this->village->premium->getError (true));
				}
			}
		}
	}
	
	/*
		Process build input
	*/
	private function processBuildInput ()
	{
		$data = $this->getInputData ();
	
		// Get building
		$building = Dolumar_Buildings_Building::getBuilding ($data['building'], $this->village->getRace ());
		
		if (isset ($data['rune']))
		{
			$building->setChosenRune ($this->village, $data['rune']);
		}
		
		$x = floor ($data['x'] * 2) / 2;
		$y = floor ($data['y'] * 2) / 2;
		
		/*
			Check if the game allows building this building now
		*/
		if (!$building->canBuildBuilding ($this->village))
		{			
			$this->updateContent ($this->getContent ('techlevel'));
		}
		
		/*
			Limit the amount of buildings
		*/
		elseif (!$building->checkBuildingLevels ($this->village))
		{
			$this->updateContent 
			(
				$this->getContent 
				(
					'buildinglevel',
					array
					(
						'building' => $building->getName (false),
						'buildings' => $building->getName (true),
						'level' => ($this->village->buildings->getBuildingAmount ($building) + 1)
					)
				)
			);
		}
		
		elseif ($this->village->readyToBuild ())
		{
			$this->buildBuilding ($building, $x, $y);
		}

		else
		{
			$this->updateContent ($this->getContent ('stillConstructing'));
		}
	}
	
	private function buildBuilding ($building, $x, $y)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$text = Neuron_Core_Text::__getInstance ();
		
		// Check range, overlap etc.
		$chk = $building->checkBuildLocation ($this->village, $x, $y);
		
		if ($chk[0])
		{
			$x = $chk[1][0];
			$y = $chk[1][1];
			
			$res = $building->getBuildingCost ($this->village);
			
			// Take resources & runes
			if 
			(
				$this->village->resources->takeResourcesAndRunes ($res)
			)
			{	
				$building = $building->build ($this->village, $x, $y);
				
				//$this->reloadLocation ($x, $y);
				
				// Reload buildings & runes
				$this->village->buildings->reloadBuildings ();
				$this->village->buildings->reloadBuildingLevels ();
				$this->village->onBuild ($building);
				
				// Reload windows
				reloadEverything ();
				
				$this->updateContent ($this->getContent ('done'));
			
			}
			
			else 
			{
				$this->updateContent ($this->getContent ($this->village->resources->getError ()));
			}
		}
		
		else 
		{
			// Game error: town center not found
			$this->updateContent ($this->getContent ($chk[1], isset ($chk[2]) ? $chk[2] : array ()));
		}
	}
}

?>
