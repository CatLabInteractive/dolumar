<?php
class Dolumar_Windows_Formation extends Neuron_GameServer_Windows_Window
{
	private $village;
	
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$login = Neuron_Core_Login::__getInstance ();

		$data = $this->getRequestData ();

		if (!isset ($data['vid']))
		{
			$data['vid'] = 0;
		}

		$this->village = Dolumar_Players_Village::getVillage ($data['vid']);
	
		// Window settings
		if ($login->isLogin () && $this->village->isFound ())
		{
			$this->setTitle ($text->get ('formation', 'menu', 'main').
				' ('.Neuron_Core_Tools::output_varchar ($this->village->getName ()).')');
		}

		else
		{
			$this->setTitle ($text->get ('formation', 'menu', 'main'));
		}
		
		$this->setSize (300, 300);
		$this->setClassName ('formation');
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ($msg = null, $failed = false)
	{
		$myself = Neuron_GameServer::getPlayer ();
		
		if (!$this->village->isActive () || $this->village->getOwner ()->getId () != $myself->getId ())
		{
			return false;
		}
	
		$page = new Neuron_Core_Template ();
		
		$page->setTextSection ('formation', 'battle');
		
		// Load battle slots
		$slots = $this->village->getDefenseSlots ();
		
		// Load your units
		$squads = $this->village->getSquads (false, false, false);
		
		// See how much rows that is
		$rows = $this->countRows ($squads, $slots);
		
		$page->set ('rows', $rows);
		$page->set ('slots', $slots);
		
		if (!empty ($msg))
		{
			$page->set ('message', $msg);
			$page->set ('error', $failed);
		}
		
		foreach ($squads as $v)
		{
			if ($v->getUnitsAmount () > 0)
			{
				$page->addListValue
				(
					'squads',
					array
					(
						'sName' => Neuron_Core_Tools::output_varchar ($v->getName ()),
						'oUnits' => $v->getUnits (),
						'id' => $v->getId ()
					)
				);
			}
		}
		
		return $page->parse ('battle/formation.phpt');
	}
	
	/*
		Only ONE backup troop allowed.
	*/
	private function countRows ($squads, $slots)
	{
		return min (ceil (count ($squads) / count ($slots)), 2);
	}
	
	public function processInput ()
	{
		$db = Neuron_Core_Database::__getInstance ();
	
		$input = $this->getInputData ();
	
		// Loop and collect data
		$squads = $this->village->getSquads (false, false, false);
		$slots = $this->village->getDefenseSlots ();
		$rows = $this->countRows ($squads, $slots);
		
		$units = array ();
		
		// First: make a list of all units
		foreach ($squads as $squad)
		{
			foreach ($squad->getUnits () as $unit)
			{
				$units[$squad->getId ().'_'.$unit->getUnitId ()] = $unit;
			}
		}
		
		// Next: loop trough the slots
		foreach ($slots as $slotId => $slot)
		{
			for ($i = 0; $i < $rows; $i ++)
			{
				$value = isset ($input['slot_'.$slotId.'_'.$i]) ? $input['slot_'.$slotId.'_'.$i] : null;
				
				if (isset ($units[$value]))
				{
					$db->update
					(
						'squad_units',
						array
						(
							's_slotId' => $slotId,
							's_priority' => $i
						),
						"s_id = '".($units[$value]->getSquad ()->getId ())."'
							AND u_id = '".($units[$value]->getUnitId ())."'"
					);
					
					unset ($units[$value]);
				}
				else
				{
					// You may not set a second one without a first one!
					break;
				}
			}
		}
		
		// Next: remove all non-assigned troop
		foreach ($units as $v)
		{
			$db->update
			(
				'squad_units',
				array
				(
					's_slotId' => 0,
					's_priority' => 0
				),
				"s_id = '".($v->getSquad ()->getId ())."'
					AND u_id = '".($v->getUnitId ())."'"
			);
		}
	
		$this->updateContent ($this->getContent ('saved', false));
	}
}
?>
