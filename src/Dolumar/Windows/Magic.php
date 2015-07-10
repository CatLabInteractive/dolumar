<?php
class Dolumar_Windows_Magic extends Neuron_GameServer_Windows_Window
{
	protected $village = false;
	
	protected $buildingType = 'Dolumar_Buildings_WizardTower';
	protected $sTextFile = 'magic';

	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$login = Neuron_Core_Login::__getInstance ();
	
		// Window settings
		$this->setSize ('315px', '350px');
		
		$data = $this->getRequestData ();
		
		// Construct village
		if (isset ($data['village']) && $login->isLogin ())
		{
			$this->village = Dolumar_Players_Village::getMyVillage ($data['village']);
			
			$this->setTitle ($text->get ($this->sTextFile, 'menu', 'main') . ' (' .
				Neuron_Core_Tools::output_varchar ($this->village->getName ()).')');
		}
		
		else 
		{
			$this->village = false;
			$this->setTitle ($text->get ($this->sTextFile, 'menu', 'main'));
		}
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		if (!$this->village)
		{
			$text = Neuron_Core_Text::__getInstance ();
			return '<p class="false">'.$text->get ('login', 'login', 'account').'</p>';
		}
		
		$data = $this->getRequestData ();
		$building = isset ($data['building']) ? $data['building'] : 0;
		
		if ($building > 0)
		{
			return $this->getCastSpellHTML ($building);
		}
		else
		{
			return $this->getOverview ();
		}
	}
	
	protected function getOverview ()
	{
		$page = new Neuron_Core_Template ();
		$page->setTextSection ('overview', $this->sTextFile);
		
		
				
		return $page->parse ('magic/overview.phpt');
	}
	
	protected function getCastSpellHTML ($building)
	{
		$data = $this->getRequestData ();
		$input = $this->getInputData ();
	
		$building = $this->village->buildings->getBuilding ($building);
		if (!$building instanceof $this->buildingType)
		{
			$building = false;
		}
		
		// Let's go for the various stages
		if ($building && isset ($data['spell']) && $building->getUnitCount () > 0)
		{
			$unit = $building->getSpecialUnit ();
			$spell = $building->getSpecialUnit ()->getEffect ($data['spell']);
			if ($spell && ($spell instanceof Dolumar_Effects_Boost || $spell instanceof Dolumar_Effects_Instant))
			{
				// That's alright, let's go for the actual thing
				if (isset ($data['target']))
				{
					$objTarget = Dolumar_Players_Village::getVillage ($data['target']);
					if ($objTarget)
					{
						if ($objTarget->getOwner ()->inVacationMode ())
						{
							return $this->getChooseTarget ($spell, 'vacationmode');
						}
					
						// Check for confirmation
						elseif (isset ($input['confirm']))
						{
							return $this->doCastSpell ($unit, $spell, $objTarget);
						}
						else
						{
							return $this->getConfirmCast ($unit, $spell, $objTarget);
						}
					}
					else
					{
						return $this->getChooseTarget ($spell);
					}
				}

				elseif (!$spell->requiresTarget ())
				{
						// Check for confirmation
						if (isset ($input['confirm']))
						{
							return $this->doCastSpell ($unit, $spell, null);
						}
						else
						{
							return $this->getConfirmCast ($unit, $spell, null);
						}
				}

				else
				{
					return $this->getChooseTarget ($spell);
				}
			}
		}
		elseif ($building && $building->getUnitCount () > 0)
		{
			return $this->getCastSpell ($building->getSpecialUnit ());
		}
		
		// Building found, but no wizards available
		elseif ($building)
		{
			return $this->getOverview ();
		}
		else
		{
			return '<p>Invalid input: building not found.</p>';
		}
	}
	
	public function processInput ()
	{
		if ($this->village)
		{
			$data = $this->getRequestData ();
			$input = $this->getInputData ();
			
			if (isset ($input['page']) && $input['page'] == 'select')
			{
				$this->updateRequestData 
				(
					array
					(
						'village' => $this->village->getId (),
						'building' => $data['building']
					)
				);
			}
		
			elseif (isset ($input['target']) && isset ($data['building']) && isset ($data['spell']))
			{
				// Update requestdata to contain spell
				$this->updateRequestData 
				(
					array
					(
						'village' => $this->village->getId (),
						'building' => $data['building'],
						'spell' => $data['spell'],
						'target' => $input['target']
					)
				);
			}
		
			elseif (isset ($input['spell']) && isset ($data['building']))
			{
				// Update requestdata to contain spell
				$this->updateRequestData 
				(
					array
					(
						'village' => $this->village->getId (),
						'building' => $data['building'],
						'spell' => $input['spell'],
						'target' => isset ($data['target']) ? $data['target'] : null
					)
				);
			}
			
			// Update the page content
			$this->updateContent ();
		}
	}
	
	protected function getCastSpell ($objUnit, $addInputData = array (), $aReturnData = null)
	{
		$data = $this->getRequestData ();
	
		$page = new Neuron_Core_Template ();
		$page->setTextSection ('cast', $this->sTextFile);
		
		$page->set ('input', $addInputData);
		
		$spells = $objUnit->getEffects ();
		
		foreach ($spells as $spell)
		{
			if ($spell instanceof Dolumar_Effects_Boost || $spell instanceof Dolumar_Effects_Instant)
			{
				$page->addListValue
				(
					'spells',
					$spell->getOutputData ($objUnit, $this->village)
				);
			}
		}
		
		$page->sortList ('spells');
		
		$page->set ('returnData', $aReturnData);
		
		return $page->parse ('magic/cast.phpt');
	}
	
	protected function doCastSpell ($objUnit, $objSpell, $objTarget, $visible = true)
	{
		// Just feed the random number generator, just to be sure.
		mt_srand ();
	
		$page = new Neuron_Core_Template ();
		
		$text = Neuron_Core_Text::getInstance ();
		$page->setTextSection ('result', $this->sTextFile);
	
		// Now let's start the checking
		$cost = $objSpell->getCost ($objUnit, $objTarget);
		
		if ($this->village->resources->takeResourcesAndRunes ($cost))
		{
			$dif = $this->getProbability ($objUnit, $objSpell, $objTarget);
			$rand = mt_rand (0, 100);
			
			$objSpell->setVillage ($this->village);
			$objSpell->setTarget ($objTarget);
			
			$objSpell->prepare ();
			
			// Some spells are not castable for some reason.
			if (!$objSpell->isCastable ())
			{
				$page->set ('success', false);
				$page->set ('message', $text->get ($objSpell->getError (), 'errors', $this->sTextFile));
			}
			else
			{
				if ($rand < $dif)
				{
					$objSpell->execute ($visible);
					reloadStatusCounters ();
			
					$page->set ('success', true);
					$page->set ('message', $objSpell->getSuccessMessage ());
				
					// Call the trigger!
					$objUnit->onSuccess ();
				
					// Update request data (to return to overview)
					//$this->updateRequestData (array ('building' => $'village' => $this->village->getId ()));
					
					$page->set ('extra', $objSpell->getExtraContent ());
				
					$success = true;
				}
				else
				{
					$page->set ('success', false);				
					$page->set ('message', $objSpell->getFailedMessage ());
				
					// Call the trigger!
					$objUnit->onFail ();
				
					if ($objUnit->isAlive ())
					{
						$page->set ('retry', true);
						$page->set ('inputData', $this->getInputData ());
					}
				
					$success = false;
				}
			
				$toTarget = $success || !$visible;
			
				if (!$success)
				{
					$visible = true;
				}
			
				$objTarget = $objSpell->getTarget ();
			
				$log = Dolumar_Players_Logs::getInstance ();
				$log->addEffectLog 
				(
					$objTarget, 
					$objSpell, 
					$this->village, 
					$success, 
					$visible, 
					$toTarget, // only show if it has been a success or hidden.
					$this->sTextFile
				);
			
				reloadEverything ();
			}
		}
		else
		{
			$page->set ('success', false);
			$page->set ('error', 'err_resources');
		}
		
		return $page->parse ('magic/result.phpt');;
	}
	
	/*
		Returns the % of probability that this spell will succeed.
	*/
	protected function getProbability ($objUnit, $objSpell, $objTarget)
	{
		return $objSpell->getProbability ($objUnit, $objTarget, false);
	}
	
	protected function getConfirmCast ($objUnit, $objSpell, $objTarget, $inputData = null)
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		$page = new Neuron_Core_Template ();
		
		$page->setTextSection ('confirm', $this->sTextFile);
		
		if (isset ($objTarget))
		{
			$page->set ('target', Neuron_Core_Tools::output_varchar ($objTarget->getName ()));
		}

		$page->set ('spell', Neuron_Core_Tools::output_varchar ($objSpell->getName ()));
		$page->set ('cost', Neuron_Core_Tools::resourceToText ($objSpell->getCost ($objUnit, $objTarget)));
		$page->set ('about', Neuron_Core_Tools::output_varchar ($objSpell->getDescription ()));
		
		$page->set ('duration', $objSpell->getType_text ());
		$page->set ('difficulty', $objSpell->getDifficulty ());
		$page->set ('probability', $this->getProbability ($objUnit, $objSpell, $objTarget));
		
		$page->set ('toCast', 
			Neuron_Core_Tools::putIntoText
			(
				$text->get ('toCast', 'confirm', $this->sTextFile),
				array ('spell' => Neuron_Core_Tools::output_varchar ($objSpell->getName ()))
			)
		);
		
		// Set hidden values
		$page->set ('inputData', $inputData);
		
		return $page->parse ('magic/confirm.phpt');
	}
	
	protected function getChooseTarget ($spell, $error = null)
	{
		$structure = new Neuron_Structure_ChooseTarget ($this->getInputData (), $this->village, true, true);
		return $structure->getHTML ($error);
	}
}
?>
