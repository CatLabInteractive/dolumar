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

class Dolumar_Windows_Clan extends Neuron_GameServer_Windows_Window
{
	private $errors = array ();

	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('250px', '300px');
		$this->setTitle ($text->get ('clan', 'menu', 'main'));
		
		$this->setClassname ('clan');
		
		$this->setAllowOnlyOnce ();
		
		$this->setAjaxPollSeconds (60);
	}
	
	public function getRefresh ()
	{
		if (isset ($_SESSION['clan_overview_lastrefresh']) 
			&& $_SESSION['clan_overview_lastrefresh'] < (time()-59))
		{
			$this->updateContent ();
		}
	}
	
	public function getContent ()
	{
		$_SESSION['clan_overview_lastrefresh'] = null;
	
		$data = $this->getRequestData ();
		if (isset ($data['id']) && intval ($data['id']) > 0)
		{
			$clan = new Dolumar_Players_Clan ($data['id']);
			if ($clan->isFound ())
			{
				return $this->getOverview ($clan);
			}
			else
			{
				return $this->throwError ($text->get ('notFound', 'overview', 'clan'));
			}
		}
		else
		{
			$login = Neuron_Core_Login::__getInstance ();
			$profile = Neuron_GameServer::getPlayer ();
		
			if ($login->isLogin () && $profile->isPlaying ())
			{
				$clans = $profile->getClans ();
			
				if (count ($clans) > 0)
				{
					$this->updateRequestData (array ('id' => $clans[0]->getId ()));
					return $this->getOverview ($clans[0]);
				}
				else
				{
					return $this->getNoClan ($profile);
				}
			}
			else
			{
				$text = Neuron_Core_Text::__getInstance ();
				return $this->throwError ($text->get ('noLogin', 'main', 'main'));
			}
		}
	}
	
	/*
		Add error
	*/
	private function addError ($sError)
	{
		$this->errors[] = $sError;
	}
	
	/*
		Get all errors
	*/
	public function getErrors ()
	{
		return $this->errors;
	}
	
	/*
		Get last error
	*/
	public function getError ()
	{
		if (count ($this->errors) > 0)
		{
			return $this->errors[count ($this->errors) - 1];
		}
		else
		{
			return null;
		}
	}
	
	private function getNoClan ($profile, $err = false)
	{
		$page = new Neuron_Core_Template ();
		$page->setTextSection ('noclan', 'clan');
		
		$page->set ('distance', Neuron_Core_Tools::output_distance (MAXCLANDISTANCE));
		
		if ($err)
		{
			$page->set ('error', $err);
		}
		
		$clans = $this->getClosebyClans ($profile);
		foreach ($clans as $v)
		{
			$page->addListValue
			(
				'clans',
				array
				(
					'id' => $v->getId (),
					'name' => Neuron_Core_Tools::output_varchar ($v->getName ())
				)
			);
		}
		
		return $page->parse ('clan/noclan.phpt');
	}
	
	public function processInput ()
	{
		$_SESSION['clan_overview_lastrefresh'] = null;
	
		$login = Neuron_Core_Login::__getInstance ();
		
		$data = $this->getRequestData ();
		$input = $this->getInputData ();
		
		if (isset ($data['id']) && $data['id'] > 0)
		{
			$clan = new Dolumar_Players_Clan ($data['id']);
			if ($clan)
			{
				if (!isset ($input['action']))
				{
					$input['action'] = 'overview';
				}
				
				switch ($input['action'])
				{
					case 'join':
						$this->updateContent ($this->getJoinClan ($clan));
					break;
					
					case 'government':
						$this->updateContent ($this->getGovernment ($clan));
					break;
					
					case 'leave':
						$this->updateContent ($this->getLeaveClan ($clan));
					break;
				
					case 'overview':
					default:
						$this->updateContent ($this->getOverview ($clan));
					break;
				}
			}
		}
		elseif ($login->isLogin ())
		{
			$profile = Neuron_GameServer::getPlayer ();
		
			$clans = $profile->getClans ();
			
			if (count ($clans) == 0 && isset ($input['clanname']))
			{
				if (Neuron_Core_Tools::checkInput ($input['clanname'], 'unitname'))
				{
					if ($this->makeClan ($input['clanname']))
					{
						$this->updateContent ();
					}
					else
					{
						$this->updateContent ($this->getNoClan ($profile, $this->getError ()));
					}
					
				}
				else
				{
					$this->updateContent ($this->getNoClan ($profile, 'err_clanname'));
				}
			}
		}
	}
	
	private function getJoinClan ($clan)
	{
		$myself = Neuron_GameServer::getPlayer ();
		$text = Neuron_Core_Text::__getInstance ();
		
		if ($myself)
		{
			$clans = $myself->getClans ();
			
			if (count ($clans) > 0)
			{
				return $this->throwError ($text->get ('inClan', 'join', 'clan'));
			}
		
			$input = $this->getInputData ();
			
			$page = new Neuron_Core_Template ();
			$page->setTextSection ('join', 'clan');
		
			// Check for a confirm
			if (isset ($input['confirm']) && $input['confirm'] == 'join')
			{
				// Check for password
				$password = isset ($input['password']) ? $input['password'] : null;
			
				if ($clan->joinClan ($myself, $password))
				{
					return $this->getOverview ($clan);
				}
				else
				{
					$page->set ('error', $clan->getError ());
				}
			}
		
			$page->set ('clan', Neuron_Core_Tools::output_varchar ($clan->getName ()));
		
			// Check if protected
			$page->set ('isProtected', $clan->isPasswordProtected ());
		
			return $page->parse ('clan/join.phpt');
		}
		else
		{
			return $this->throwError ($text->get ('noLogin', 'main', 'main'));
		}
	}
	
	private function getLeaveClan ($clan)
	{
		$myself = Neuron_GameServer::getPlayer ();
		$text = Neuron_Core_Text::__getInstance ();
		
		if ($myself)
		{
			$page = new Neuron_Core_Template ();
			
			$page->set ('clan', Neuron_Core_Tools::output_varchar ($clan->getName ()));
			
			if (!$clan->leaveClan ($myself))
			{
				$page->set ('error', $clan->getError ());
			}
		
			return $page->parse ('clan/leave.phpt');
		}
		else
		{
			return $this->throwError ($text->get ('noLogin', 'main', 'main'));
		}
	}
	
	private function makeClan ($name)
	{
		$profile = Neuron_GameServer::getPlayer ();
		if ($profile)
		{
			$db = Neuron_Core_Database::__getInstance ();
			
			// Check for a clan with the same name
			$chk = $db->select
			(
				'clans',
				array ('c_id'),
				"c_name = '".$db->escape ($name)."'"
			);
			
			if (count ($chk) > 0)
			{
				$this->addError ('name_duplicate');
				return false;
			}
			else
			{
				$id = $db->insert
				(
					'clans',
					array
					(
						'c_name' => $name
					)
				);
		
				// Add yourself
				/*
				$db->insert
				(
					'clan_members',
					array
					(
						'plid' => $profile->getId (),
						'c_id' => $id,
						'c_status' => 'leader'
					)
				);
				*/
				
				$clan = Dolumar_Players_Clan::getFromId ($id);
				$clan->doJoinClan ($profile, 'leader');
				
				return true;
			}
		}
	}
	
	private function getGovernment ($clan)
	{
		$myself = Neuron_GameServer::getPlayer ();
		
		// If this guy is not a leader, throw him out
		if (!$clan->isLeader ($myself))
		{
			return $this->getOverview ($clan);
		}
		
		// Check for input
		$input = $this->getInputData (); 
		if (isset ($input['password']))
		{
			$clan->setPassword ($input['password']);
		}
		
		// Define a local variable, this will become false if
		// the leadership is given away.
		$isLeader = true;
		
		// Check for description
		$form = isset ($input['form']) ? $input['form'] : null;
		switch ($form)
		{
			case 'description':
				$name = isset ($input['name']) ? $input['name'] : null;
				$description = isset ($input['description']) ? $input['description'] : null;
				
				if ($name)
				{
					$clan->setName ($name, $description);
				}
			break;
			
			case 'usermanagement':
				// Loop trough the members
				foreach ($clan->getMembers () as $v)
				{
					// Check for input
					$role = isset ($input['member_'.$v->getId ()]) ? $input['member_'.$v->getId ()] : null;
					
					switch ($role)
					{
						case 'leader':
							$isLeader = false;
							$clan->makeLeader ($v);
						break;
						
						case 'captain':
						case 'member':
							$clan->setRole ($v, $role);
						break;
						
						case 'kick':
							$clan->kickFromClan ($v);
						break;
					}
				}
				
				$clan->reloadMembers ();
			break;
		}
		
		if ($isLeader)
		{
			$page = new Neuron_Core_Template ();
			$page->setTextSection ('government', 'clan');
		
			$page->set ('isProtected', $clan->isPasswordProtected ());
		
			// Fetch members and list
			foreach ($clan->getMembers () as $v)
			{
				if ($v->getId () != $myself->getId ())
				{
					$page->addListValue
					(
						'members',
						array
						(
							'id' => $v->getId (),
							'name' => Neuron_Core_Tools::output_varchar ($v->getNickname ()),
							'role' => $clan->getMemberStatus ($v)
						)
					);
				}
			}
		
			// Values
			$page->set ('name_value', Neuron_Core_Tools::output_form ($clan->getName ()));
			$page->set ('description_value', Neuron_Core_Tools::output_form ($clan->getDescription (false)));
		
			// Get roles
			$page->set ('roles', array ('leader', 'captain', 'member', 'kick'));
		
			return $page->parse ('clan/government.phpt');
		}
		else
		{
			return $this->getOverview ($clan);
		}
	}
	
	private function getOverview ($clan)
	{
		$_SESSION['clan_overview_lastrefresh'] = time ();
	
		// Change the title
		$text = Neuron_Core_Text::__getInstance ();
		$this->setTitle ($text->get ('clan', 'menu', 'main') . ': '.Neuron_Core_Tools::output_varchar ($clan->getName ()));
		
		$page = new Neuron_Core_Template ();
		
		// Check for errors
		$error = $clan->getError ();
		if (isset ($error))
		{
			$page->set ('error', $text->get ($error, 'errors', 'clan'));
		}
	
		$myself = Neuron_GameServer::getPlayer ();
		
		$canJoin = $myself && !$clan->isMember ($myself);
		$canLeave = $myself && $clan->isMember ($myself);
	
		$page->setTextSection ('overview', 'clan');
		
		$page->set ('clanname', Neuron_Core_Tools::output_varchar ($clan->getName ()));
		$page->set ('clanid', $clan->getId ());
		
		foreach ($clan->getMembers () as $v)
		{
			$status = $clan->getMemberStatus ($v);
		
			$page->addListValue
			(
				'members',
				array
				(
					'id' => $v->getId (),
					'name' => Neuron_Core_Tools::output_varchar ($v->getNickname ()),
					'status' => $status,
					'status_t' => $text->get ($status, 'roles', 'clan'),
					'online' => $v->isOnline () ? 'online' : 'offline'
				)
			);
		}
		
		// Check if it's possible to join
		$page->set ('canJoin', $canJoin);
		$page->set ('canLeave', $canLeave);
		$page->set ('canGovern', $clan->isLeader ($myself));
		
		$desc = $clan->getDescription ();
		if (!empty ($desc))
		{
			$page->set ('description', Neuron_Core_Tools::output_text ($desc));
		}
		
		return $page->parse ('clan/overview.phpt');
	}
	
	private function getClosebyClans ()
	{
		// Fetch all players within the available radius
		$db = Neuron_Core_Database::__getInstance ();
		
		// sqrt (pow ($x1 - $x2, 2) + pow ($y1 - $y2, 2) )
		$player = Neuron_GameServer::getPlayer ();
		
		$selectors = "FALSE OR ";
		
		$villages = $player->getVillages ();
		
		// No villages = no nearby clans.
		if (count ($villages) == 0)
		{
			return array ();
		}
		
		foreach ($villages as $v)
		{
			$townCenter = $v->buildings->getTownCenter ();
			if ($townCenter)
			{
				list ($x, $y) = $townCenter->getLocation ();
				$selectors .= "SQRT(POW(xas-".$x.",2)+POW(yas-".$y.",2)) < ".MAXCLANDISTANCE." OR ";
			}
		}
		
		$selectors = substr ($selectors, 0, -4);
		
		$sSQL = "
			SELECT
				clans.*
			FROM
				map_buildings
			LEFT JOIN
				villages ON map_buildings.village = villages.vid
			LEFT JOIN
				n_players ON villages.plid = n_players.plid
			LEFT JOIN
				clan_members ON n_players.plid = clan_members.plid
			LEFT JOIN
				clans ON clan_members.c_id = clans.c_id
			WHERE
				(
					map_buildings.buildingType = 1
					AND 
					(
						$selectors
					)
					AND clans.c_name IS NOT NULL
				)
			GROUP BY
				clans.c_id
		";
		
		$sql = $db->getDataFromQuery ($db->customQuery ($sSQL));
		
		$out = array ();
		foreach ($sql as $v)
		{
			$out[] = new Dolumar_Players_Clan ($v['c_id'], $v);
		}
		
		return $out;
	}
}
?>
