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

class Dolumar_Players_Clan implements Neuron_GameServer_Interfaces_Logable
{
	private $id;
	private $data;
	
	private $error = null;
	private $memberStatus = array ();
	
	const MIN_DAYS_BETWEEN_CLANHOP = 2;
	const MIN_DAYS_BETWEEN_OWN_CLANHOP = 10;
	
	const MIN_DAYS_BETWEEN_JOINS = 5;
	const MIN_DAYS_BETWEEN_KICKJOIN = 20;

	public function __construct ($id, $data = null)
	{
		$this->id = intval ($id);
		if (!empty ($data))
		{
			$this->setData ($data);
		}
	}
	
	public static function getFromId ($id)
	{
		return new self ($id);
	}
	
	public function getLogArray ()
	{
		return array
		(
			'name' => $this->getName ()
		);
	}
	
	public function getId ()
	{
		return $this->id;
	}
	
	public function getDisplayName ()
	{
		return '<a href="javascript:void(0);" '.
			'onclick="openWindow(\'clan\',{\'id\':'.$this->getId ().'});" class="clan">'.
			$this->getName ().'</a>';
	}
	
	public function getName ()
	{
		$this->loadData ();
		return $this->data['c_name'];
	}
	
	/*
		The description can use some "text replace" values.
	*/
	public function getDescription ($tags = true)
	{
		$this->loadData ();
		if ($tags)
		{
			return Neuron_Core_Tools::putIntoText
			(
				$this->data['c_description'],
				array
				(
					'clan' => $this->getName (),
					'members' => count ($this->getMembers ())
				)
			);
		}
		else
		{
			return $this->data['c_description'];
		}
	}
	
	private function setData ($data)
	{
		$this->data = $data;
	}
	
	public function reloadData ()
	{
		$this->data = null;
	}
	
	private function loadData ()
	{
		if ($this->data === null)
		{
			$db = Neuron_Core_Database::__getInstance ();
			
			$data = $db->select
			(
				'clans',
				array ('*'),
				"c_id = '".$this->getId ()."'"
			);
			
			if (count ($data) == 1)
			{
				$this->setData ($data[0]);
			}
			else
			{
				$this->data = false;
			}
		}
	}
	
	public function isFound ()
	{
		$this->loadData ();
		return is_array ($this->data);
	}
	
	/*
		Reset members & forces object to reload on next call
	*/
	public function reloadMembers ()
	{
		// Well, actually, there is nothing to do...
	}
	
	public function getMembers ()
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$membs = $db->getDataFromQuery
		(
			$db->customQuery
			("
				SELECT
					*
				FROM
					clan_members
				LEFT JOIN
					n_players ON n_players.plid = clan_members.plid
				WHERE
					clan_members.c_id = ".$this->getId ()." AND
					clan_members.cm_active = 1
				ORDER BY
					c_status DESC,
					n_players.nickname ASC
			")
		);
		
		$out = array ();
		foreach ($membs as $v)
		{
			if (!empty ($v['plid']))
			{
				$out[] = Neuron_GameServer::getPlayer ($v['plid'], $v);
				$this->memberStatus[$v['plid']] = $v['c_status'];
			}
		}
		
		return $out;
	}
	
	public function isMember ($objMember)
	{
		$id = $objMember->getId ();
		foreach ($this->getMembers () as $v)
		{
			if ($v->getId () == $id)
			{
				return true;
			}
		}
		return false;
	}
	
	public function getMemberStatus ($member)
	{
		$members = $this->getMembers ();
		if ($member && isset ($this->memberStatus[$member->getId ()]))
		{
			return $this->memberStatus[$member->getId ()];
		}
		else
		{
			return false;
		}
	}
	
	/*
		 Returns leader (or appoint a new leader if no leader is found).
	*/
	public function getLeader ()
	{
		foreach ($this->getMembers () as $member)
		{
			if ($this->getMemberStatus ($member) == 'leader')
			{
				// Check if this player is active enough.
				if ($member->getLastRefresh () > time () - 60*60*24*7)
				{
					return $member;
				}
			}
		}
		
		// No leader is found, appoint a new one.
		$leader = $this->getNewLeaderCandidate ();
		if ($leader)
		{
			$this->makeLeader ($leader);
		}
	}
	
	/*
		Return the player object that is the
		best new leader
	*/
	private function getNewLeaderCandidate ()
	{
		// In short: the first player that joined the clan and has been online in the last 24 hours.
		$members = $this->getMembers ();
		
		foreach ($members as $v)
		{
			if ($v->getLastRefresh () > (time () - 60*60*48))
			{
				return $v;
			}
		}
		
		if (count ($members) > 0)
		{
			return $members[0];
		}
		
		else
		{
			return false;
		}
	}
	
	public function isModerator ($member)
	{
		$status = $this->getMemberStatus ($member);
		return $status == 'leader' || $status == 'captain';
	}
	
	public function isLeader ($member)
	{
		$this->getLeader ();
		$status = $this->getMemberStatus ($member);
		return $status == 'leader';
	}
	
	public function setPassword ($password)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$db->update
		(
			'clans',
			array
			(
				'c_password' => empty ($password) ? null : md5 ($password)
			),
			"c_id = ".$this->getId ()
		);
		
		$this->reloadData ();
	}
	
	public function isPasswordProtected ()
	{
		$this->loadData ();
		return !empty ($this->data['c_password']);
	}
	
	public function checkPassword ($password)
	{
		return !$this->isPasswordProtected () || md5 ($password) == $this->data['c_password'];
	}
	
	public function canJoin (Neuron_GameServer_Player $player, $checkpassword = false, $password = false)
	{
		$logs = Dolumar_Players_Logs::getInstance ();
				
		if
		(
			!$this->chkLeftClanRecently ($player) ||
			!$this->chkKickedFromClanRecently ($player) || 
			!$this->chkJoinClanRecently ($player)
		)
		{
			return false;
		}
		
		elseif ($this->isMember ($player))
		{
			$this->error = 'err_inclan';
			return false;
		}
		elseif ($this->isPasswordProtected () && !$this->checkPassword ($password))
		{
			$this->error = 'err_password';
			return false;
		}
		
		else
		{		
			return true;
		}
	}
	
	private function chkLeftClanRecently (Neuron_GameServer_Player $player)
	{
		$logs = Dolumar_Players_Logs::getInstance ();
	
		$own_clanhop = self::MIN_DAYS_BETWEEN_OWN_CLANHOP;
		$clanhop = self::MIN_DAYS_BETWEEN_CLANHOP;
		
		if ($clanhop > $own_clanhop)
		{
			$own_clanhop = $clanhop;
		}
	
		$logs->clearFilters ();
		$logs->addShowOnly ('clan_leave');
		$logs->setTimeInterval (NOW - 60 * 60 * 24 * $own_clanhop);
		
		foreach ($logs->getLogs ($player->getVillages ()) as $v)
		{
			$objects = $v['data'];
			
			if ($objects[0]->equals ($this) && $v['unixtime'] > (NOW - 60 * 60 * 24 * $own_clanhop))
			{
				$this->error = 'err_clanhop_ownclan';
				return false;
			}
			
			elseif ($v['unixtime'] > (NOW - 60 * 60 * 24 * $clanhop))
			{
				$this->error = 'err_clanhop';
				return false;
			}
		}
		
		return true;
	}
	
	private function chkJoinClanRecently (Neuron_GameServer_Player $player)
	{
		$logs = Dolumar_Players_Logs::getInstance ();
	
		$logs->clearFilters ();
		$logs->addShowOnly ('clan_join');
		$logs->setTimeInterval (NOW - 60 * 60 * 24 * self::MIN_DAYS_BETWEEN_JOINS);
		
		if (count ($logs->getLogs ($player->getVillages ())) == 0)
		{
			return true;
		}
		else
		{
			$this->error = 'err_joinhop';
			return false;
		}
	}
	
	private function chkKickedFromClanRecently (Neuron_GameServer_Player $player)
	{
		$logs = Dolumar_Players_Logs::getInstance ();
	
		$logs->clearFilters ();
		$logs->addShowOnly ('clan_kicked');
		$logs->setTimeInterval (NOW - 60 * 60 * 24 * self::MIN_DAYS_BETWEEN_KICKJOIN);
		
		foreach ($logs->getLogs ($player->getVillages ()) as $v)
		{
			$objects = $v['data'];
			
			if ($objects[0]->equals ($this))
			{
				$this->error = 'err_kicked';
				return false;
			}
		}
		
		return true;
	}
	
	public function joinClan (Neuron_GameServer_Player $member, $password = '')
	{
		$canJoin = $this->canJoin ($member, true, $password);
		
		if ($canJoin)
		{
			$this->doJoinClan ($member);
		}
		
		return $canJoin;
	}
	
	public function doJoinClan ($member, $state = 'member')
	{
		if ($this->isMember ($member))
		{
			$this->error = 'err_inclan';
			return false;
		}
		
		// Check clan limit
		elseif ($this->isFull ())
		{
			$this->error = 'err_full';
		}
	
		else
		{
			// Everything is okay! join the clan.
			$db = Neuron_Core_Database::__getInstance ();
		
			$db->insert
			(
				'clan_members',
				array
				(
					'plid' => $member->getId (),
					'c_id' => $this->getId (),
					'c_status' => $db->escape ($state),
					'cm_active' => 1
				)
			);
			
			$logs = Dolumar_Players_Logs::getInstance ();
			$logs->addJoinClanLog ($member, $this);
			
			$this->recalculateFullness ();
		}
	}
	
	/*
		Returns TRUE if this clan is full
	*/
	public function isFull ()
	{
		$this->loadData ();
		
		if ($this->data['c_isFull'] == -1)
		{
			$this->recalculateFullness ();
		}
		
		return intval ($this->data['c_isFull']) == 1;
		//return count ($this->getMembers ()) >= 20;
	}
	
	private function recalculateFullness ()
	{
		$isFull = count ($this->getMembers ()) >= 20;
		
		$isFull = $isFull ? 1 : 0;
		
		$db = Neuron_DB_Database::getInstance ();
		$db->query
		("
			UPDATE
				clans
			SET
				c_isFull = {$isFull}
			WHERE
				c_id = {$this->getId ()}
		");
		
		$this->data['c_isFull'] = $isFull;
	}
	
	/*
		Make this user leader (& remove the current user)
	*/
	public function makeLeader ($user)
	{
		$db = Neuron_DB_Database::__getInstance ();
		
		// Make the leader go away!
		$db->query
		("
			UPDATE
				clan_members
			SET
				c_status = 'captain'
			WHERE
				c_id = ".$this->getId ()." AND
				c_status = 'leader' AND
				cm_active = '1'
		");
		
		// Make $user a leader!
		$db->query
		("
			UPDATE
				clan_members
			SET
				c_status = 'leader'
			WHERE
				c_id = ".$this->getId ()." AND
				plid = ".$user->getId ()." AND
				cm_active = '1'
		");
	}
	
	/*
		Set a role
	*/
	public function setRole ($user, $role)
	{
		switch ($role)
		{
			case 'member':
			case 'captain':
			break;
			
			default:
				$role = 'member';
			break;
		}
		
		$db = Neuron_DB_Database::__getInstance ();
		
		$db->query
		("
			UPDATE
				clan_members
			SET
				c_status = '{$role}'
			WHERE
				c_id = {$this->getId()} AND
				plid = {$user->getId()} AND
				cm_active = 1
		");
	}
	
	public function kickFromClan ($user)
	{
		$this->leaveClan ($user, 'kicked');
	}
	
	public function leaveClan ($user, $action = 'leave')
	{
		$db = Neuron_DB_Database::__getInstance ();
		
		$db->query
		("
			UPDATE
				clan_members
			SET
				cm_active = 0
			WHERE
				c_id = ".$this->getId ()." AND 
				plid = ".$user->getId ()."
		");
		
		$logs = Dolumar_Players_Logs::getInstance ();
		$logs->addLeaveClanLog ($user, $this, $action);
		
		$this->recalculateFullness ();

		// Also destroy all clan buildings
		foreach ($user->getVillages () as $v)
		{
			foreach ($v->buildings->getBuildings () as $vv)
			{
				$vv->onClanLeave ();
			}
		}
	}
	
	public function setName ($name, $desc)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$db->update
		(
			'clans',
			array
			(
				'c_name' => $name,
				'c_description' => $desc
			),
			"c_id = ".$this->getId ()
		);
		
		$this->reloadData ();
	}
	
	public function getError ()
	{
		return $this->error;
	}
	
	/*
		Recalcualte this clans score.
	*/
	public function recalculateScore ()
	{
		$score = 0;
		foreach ($this->getMembers () as $v)
		{
			foreach ($v->getVillages () as $u)
			{
				$score += $u->getNetworth ();
			}
		}
		
		$db = Neuron_DB_Database::getInstance ();
		
		$db->query
		("
			UPDATE
				clans
			SET
				c_score = {$score}
			WHERE
				c_id = {$this->getId ()}
		");
	}
	
	/*
		Return networth
	*/
	public function getNetworth ()
	{
		$this->loadData ();
		return $this->data['c_score'];
	}
	
	public function equals ($clan)
	{
		if ($this->getId () == $clan->getId ())
		{
			return true;
		}
		return false;
	}
	
	/**
	*	Get all clan portal buildings
	*/
	public function getClanportals ()
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$villages = array ();
		foreach ($this->getMembers () as $player)
		{
			foreach ($player->getVillages () as $v)
			{
				$villages[] = $v->getId ();
			}
		}
		
		$list = "(" . implode ($villages, ",") . ")";
		
		$data = $db->query
		("
			SELECT
				*
			FROM
				map_buildings
			WHERE
				buildingType = 61 AND
				village IN $list AND
				(destroyDate = 0 OR destroyDate > ".NOW.")
		");
		
		$out = array ();
		
		foreach ($data as $v)
		{
			$village = Dolumar_Players_Village::getFromId ($v['village']);
		
			$building = Dolumar_Buildings_Building::getFromId ($v['bid'], $village->getRace (), $v['xas'], $v['yas']);
			$building->setData ($v['bid'], $v);
			$building->setVillage ($village);
			$out[] = $building;
		}
		
		return $out;
	}
	
	public function __toString ()
	{
		return $this->getDisplayName ();
	}
}
?>
