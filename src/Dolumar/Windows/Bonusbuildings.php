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

class Dolumar_Windows_Bonusbuildings 
	extends Neuron_GameServer_Windows_Window
{
	private $village;
	
	const CREDIT_COST = 250;

	public function setSettings ()
	{
		// Window settings
		$this->setSize ('350px', '315px');
		
		$data = $this->getRequestData ();
		
		$village = isset ($data['village']) ? $data['village'] : null;
		
		//$this->village = Dolumar_Players_Village::getFromId ($village);
		$this->village = Neuron_GameServer::getPlayer ()->getCurrentVillage ();
		
		$this->setAllowOnlyOnce ();
		
		$this->setTitle (Neuron_Core_Text::getInstance ()->get ('title', 'bonusbuilding', 'premium'));
	}
	
	public function getContent ($error = null)
	{
		$sUploadUrl = ABSOLUTE_URL.'page/customsign/?sessionId=' . session_id ();
		
		if (!file_exists (PUBLIC_PATH) || !is_writable (PUBLIC_PATH))
		{
			return '<p>Error: '.PUBLIC_PATH.' does not exist or is not writeable.</p>';
		}
		
		if (!isset ($this->village))
		{
			return '<p class="false">No village defined.</p>';
		}
		
		$text = Neuron_Core_Text::getInstance ();
		
		$text->setFile ('premium');
		$text->setSection ('bonusbuilding');
		
		$page = new Neuron_Core_Template ();
		
		$page->set ('upload_url', $sUploadUrl);
		
		$buildings = $this->getPublicBuildings ();
		foreach ($this->getPublicBuildings () as $v)
		{
			$page->addListValue ('buildings', $v);
		}
		
		$db = Neuron_DB_Database::getInstance ();
		$login = Neuron_Core_Login::getInstance ();

		$r = $db->query
		("
			SELECT
				*
			FROM
				players_tiles
			WHERE
				t_userid = {$login->getUserId ()}
			ORDER BY
				t_id DESC
			LIMIT
				8
		");
		
		$data = array ();
		
		foreach ($r as $v)
		{
			if (file_exists (PUBLIC_PATH . $v['t_imagename']))
			{
				$imagename = PUBLIC_URL . $v['t_imagename'];
			
				$action = new Dolumar_View_SelectBuildLocation
				(
					100,
					'build bonus building',
					null,
					new Neuron_GameServer_Map_Display_Sprite
					(
						$imagename
					),
					$v['t_id']
				);
			
				// selectBuildLocation (this, 100, null, 'placeholder', null, <?=$v['id']
				$page->addListValue
				(
					'signs',
					array
					(
						'id' => $v['t_id'],
						'image_url' => PUBLIC_URL.$v['t_imagename'],
						'credits' => self::CREDIT_COST,
						'action' => $action->getAction ()
					)
				);
			}
		}
		
		if (isset ($error))
		{
			$page->set ($error);
		}
		
		return $page->parse ('dolumar/bonusbuilding/bonusbuilding.phpt');
	}
	
	private function getPublicBuildings ()
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$data = $db->query
		("
			SELECT
				*,
				UNIX_TIMESTAMP(t_startDate) AS startdatum,
				UNIX_TIMESTAMP(t_endDate) AS einddatum
			FROM
				players_tiles
			WHERE
				t_isPublic = 1 AND
				(t_startDate IS NULL OR t_startDate < FROM_UNIXTIME(".NOW.")) AND
				(t_endDate IS NULL OR t_endDate > FROM_UNIXTIME(".NOW."))
		");
		
		$out = array ();
		foreach ($data as $v)
		{
			if (file_exists (PUBLIC_PATH . $v['t_imagename']))
			{
				$building = Dolumar_Buildings_Building::getBuilding (100, $this->village->getRace (), 0, 0);
				$building->setBonusBuildingId ($v['t_id']);
		
				$out[] = array
				(
					'id' => $v['t_id'],
					'image_url' => PUBLIC_URL.$v['t_imagename'],
					'start_date' => $v['startdatum'],
					'end_date' => $v['einddatum'],
					'name' => $building->getName (),
					'description' => $building->getCustomContent (),
					'credits' => self::CREDIT_COST
				);
			}
		}
		
		return $out;
	}
	
	public function processInput ()
	{
		$data = $this->getInputData ();
		
		if (isset ($data['action']))
		{
			switch ($data['action'])
			{
				case 'overview':
					$this->updateContent ();
					return;
				break;
			}
		}
		
		$building = Dolumar_Buildings_Building::getBuilding (100, $this->village->getRace ());

		$x = floor ($data['x']);
		$y = floor ($data['y']);
	
		$extra = intval ($data['extraData']);
		$building->setBonusBuildingId ($extra);
		
		$chk = $building->checkBuildLocation ($this->village, $x, $y);
		
		if ($chk[0])
		{
			$x = $chk[1][0];
			$y = $chk[1][1];
		
			/*
			$this->village->premium->buildBonusBuilding ($building, $x, $y, $extra);
			$this->reloadLocation ($x, $y);
			*/
		
			//$this->updateContent ('<p>Your bonus building has been built.</p>');
			
			$data = array
			(
				'action' => 'bonusbuilding',
				'building' => 100,
				'tile' => $extra,
				'village' => $this->village->getId (),
				'x' => $x,
				'y' => $y
			);
			
			$player = Neuron_GameServer::getPlayer ();
			
			$url = $player->getCreditUseUrl (self::CREDIT_COST, $data, $building->getName ());
			
			//$url = '<a href="'.$url.'" target="_BLANK" onclick="windowAction(this,{\'action\':\'overview\'}); return !Game.gui.openWindow(this, 450, 190);">here</a>';
			
			//$this->updateContent ('<p>Click '.$url.' to buy a building.</p>');
			$page = new Neuron_Core_Template ();
			
			$page->set ('x', $x);
			$page->set ('y', $y);
			$page->set ('url', $url);
			$page->set ('building', $building->getDisplayName ());
			
			$this->updateContent ($page->parse ('dolumar/bonusbuilding/confirm.phpt'));
		}
		else
		{
			$this->updateContent ($this->getContent ('invalidlocation'));
		}
	}
	
	private function convertCredits ($amount = 100)
	{
		return $amount;
		$player = Neuron_GameServer::getPlayer ();
		
		if ($player)
		{
			$amount = $player->convertCredits ($amount);
		}
		
		return $amount;
	}
}
?>
