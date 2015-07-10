<?php
class Dolumar_Windows_Speedup
	extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::getInstance ();
	
		// Window settings
		$this->setCentered ();

		$this->setSize ('300px', '200px');
		
		$this->setAllowOnlyOnce ();

		$this->setTitle ($text->get ('title', 'speedup', 'statusbar'));
	}

	public function getContent ()
	{
		$requestData = $this->getRequestData ();
		$inputData = $this->getInputData ();

		$type = isset ($requestData['type']) ? $requestData['type'] : false;

		if (!Dolumar_Windows_Statusbar::canSpeedUp ($type))
		{
			return '<p>Speedup not supported on this server: ' . $type . '</p>';
		}

		switch ($type)
		{
			case 'building':
				return $this->getSpeedUpBuilding ($requestData);
			break;

			case 'training':
				return $this->getSpeedUpTraining ($requestData);
			break;

			case 'scouting':
				return $this->getSpeedUpScouting ($requestData);
			break;
		}

		return '<p>Invalid input: type not found: ' . $type . '</p>';
	}

	private function getConfirm ($price, $unit, $duration, $description)
	{
		$data = $this->getRequestData ();
		$data['action'] = 'speedup';

		$credits = $price * $duration;
		$data['duration'] = $duration * $unit;

		//$description .= ' with ' . $data['duration'] . ' seconds.';

		$player = Neuron_GameServer::getPlayer ();

		$url = $player->getCreditUseUrl ($credits, $data, $description);

		// Nicer effect
		$this->closeWindow ();
		$this->popupWindow ($url, 450, 190);
		
		$page = new Neuron_Core_Template ();
		$page->set ('confirm_url', htmlentities ($url));
		return $page->parse ('dolumar/premium/speedupconfirm.phpt');
	}

	private function getSpeedUpScouting ($data)
	{
		$text = Neuron_Core_Text::getInstance ();

		$inputData = $this->getInputData ();

		$village = isset ($data['village']) ? $data['village'] : null;
		$village = Dolumar_Players_Village::getFromId ($village);
		$scoutId = isset ($data['scoutid']) ? $data['scoutid'] : null;

		if (!$village)
		{
			return '<p>Invalid input: village not found.</p>';
		}

		$scoutData = $village->getScoutData ($scoutId);

		if (!$scoutData)
		{
			return '<p>Invalid input: scout data not found.</p>';
		}

		$price = PREMIUM_SPEEDUP_SCOUTING_PRICE;
		$unit = PREMIUM_SPEEDUP_SCOUTING_UNIT;

		if (isset ($inputData['duration']))
		{
			$selected = abs (intval ($inputData['duration']));

			$desc = Neuron_Core_Tools::putIntoText
			(
				$text->get ('confdesc_scouting', 'speedup', 'statusbar'),
				array
				(
					'village' => $village->getName (),
					'amount' => Neuron_Core_Tools::getDurationText ($selected * $unit)
				)
			);

			return $this->getConfirm ($price, $unit, $selected, $desc);
		}

		$time = $scoutData['finishDate'] - NOW;

		return $this->getSpeedUpHTML ('scouting', $time, $price, $unit);		
	}

	private function getSpeedUpTraining ($data)
	{
		$text = Neuron_Core_Text::getInstance ();

		$inputData = $this->getInputData ();

		$village = isset ($data['village']) ? $data['village'] : null;
		$unit = isset ($data['unit']) ? $data['unit'] : null;
		$id = isset ($data['order']) ? $data['order'] : null;

		$village = Dolumar_Players_Village::getFromId ($village);
		$troop = Dolumar_Units_Unit::getFromId ($unit);
		$order = $village->units->getTrainingStatus ($id);

		if (!$troop || !$id || !$village || !$order)
		{
			return '<p>Order not found.</p>';
		}

		$price = PREMIUM_SPEEDUP_TRAINING_PRICE;
		$unit = PREMIUM_SPEEDUP_TRAINING_UNIT;

		if (isset ($inputData['duration']))
		{
			$selected = abs (intval ($inputData['duration']));

			$desc = Neuron_Core_Tools::putIntoText
			(
				$text->get ('confdesc_training', 'speedup', 'statusbar'),
				array
				(
					'unit' => $troop->getName (),
					'amount' => Neuron_Core_Tools::getDurationText ($selected * $unit)
				)
			);

			return $this->getConfirm ($price, $unit, $selected, $desc);
		}

		$time = $order['timeLeft'];

		return $this->getSpeedUpHTML ('training', $time, $price, $unit);		
	}

	private function getSpeedUpBuilding ($data)
	{
		$text = Neuron_Core_Text::getInstance ();

		$inputData = $this->getInputData ();

		$id = isset ($data['building']) ? $data['building'] : null;

		$building = Dolumar_Buildings_Building::getFromId ($id);

		if (!$building)
		{
			return '<p>Invalid input: building not found: ' . $id . '</p>';
		}

		$price = PREMIUM_SPEEDUP_BUILDINGS_PRICE;
		$unit = PREMIUM_SPEEDUP_BUILDINGS_UNIT;

		if (isset ($inputData['duration']))
		{
			$selected = abs (intval ($inputData['duration']));
			
			$desc = Neuron_Core_Tools::putIntoText
			(
				$text->get ('confdesc_building', 'speedup', 'statusbar'),
				array
				(
					'building' => $building->getName (),
					'amount' => Neuron_Core_Tools::getDurationText ($selected * $unit)
				)
			);

			return $this->getConfirm ($price, $unit, $selected, $desc);
		}

		$time = $building->getTimeLeft ();

		return $this->getSpeedUpHTML ('building', $time, $price, $unit);
	}

	private function convert ($price)
	{
		$player = Neuron_GameServer::getPlayer ();
		return $player->convertCredits ($price);
	}

	private function getSpeedUpHTML ($type, $timeleft, $price, $unit)
	{
		$page = new Neuron_Core_Template ();

		$page->set ('timeleft', $timeleft);
		$page->set ('price', $this->convert ($price));
		$page->set ('unit', $unit);
		$page->set ('type', $type);

		return $page->parse ('dolumar/premium/speedup.phpt');
	}
}