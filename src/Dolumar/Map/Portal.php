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

class Dolumar_Map_Portal
{

/**
	Mappers (ugly, I know)
*/

	public static function getFromBuilding ($building)
	{
		$building = intval ($building->getId ());
	
		$db = Neuron_DB_Database::getInstance ();
		
		$chk = $db->query
		("
			SELECT
				*
			FROM
				map_portals
			WHERE
				p_caster_b_id = {$building} OR
				p_target_b_id = {$building}
		");
		
		// This can be multiple instances now
		
		$out = array ();
		
		foreach ($chk as $v)
		{
			$in = new self ($v['p_id']);
			$in->setData ($v);
			$out[] = $in;
		}
		
		return $out;
	}
	
	public static function getClanportals (Dolumar_Players_Clan $clan)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$villages = array ();
		foreach ($clan->getMembers () as $player)
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
				map_portals
			WHERE
				p_caster_v_id IN $list OR
				p_target_b_id IN $list
		");
		
		$out = array ();
		
		foreach ($chk as $v)
		{
			$in = new self ($v['p_id']);
			$in->setData ($v[0]);
			$out[] = $in;
		}
		
		return $out;
	}
	
	public static function removeFromBuilding (Dolumar_Buildings_Clanportal $building)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$db->query
		("
			DELETE FROM
				map_portals
			WHERE
				p_caster_b_id = {$building->getId ()} OR
				p_target_b_id = {$building->getId ()}
		");
	}
	
	/*
		Return all villages
	*/
	public static function getFromVillages ($villages)
	{
		// First, fetch *all* the villages
		$villies = array ();
		
		foreach ($villages as $village)
		{
			$villies[$village->getId ()] = $village;
			foreach ($village->getOwner ()->getClans () as $clan)
			{
				foreach ($clan->getMembers () as $member)
				{
					foreach ($member->getVillages () as $v)
					{
						$villies[$v->getId ()] = $v;
					}
				}
			}
		}
		
		$sql = "SELECT *,FROM_UNIXTIME(".NOW.") FROM map_portals WHERE (p_endDate > FROM_UNIXTIME(".NOW.") OR p_endDate IS NULL) ";
		
		$sql .= "AND (";
		foreach ($villies as $v)
		{
			$sql .= "p_caster_v_id = {$v->getId ()} OR p_target_v_id = {$v->getId ()} OR ";
		}
		$sql = substr ($sql, 0, -4);
		
		$sql .= ") GROUP BY p_id";
		
		$db = Neuron_DB_Database::getInstance ();
		$data = $db->query ($sql);
		
		$out = array ();
		foreach ($data as $v)
		{
			$tmp = new self ($v['p_id']);
			$tmp->setData ($v);
			$out[] = $tmp;
		}
		
		return $out;
	}
	
	public static function getBetweenVillages ($from, $to)
	{
		$portals = self::getFromVillages (array ($from, $to));
		
		$out = array ();
		
		foreach ($portals as $v)
		{
			if ($v->isBetween ($from, $to))
			{
				$out[] = $v;
			}
		}
		
		return $out;
	}
	
	public static function insert (Dolumar_Map_Portal $portal)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$b1 = $portal->getCasterBuilding ();
		$b2 = $portal->getTargetBuilding ();
		
		$v1 = $b1->getVillage ();
		$v2 = $b2->getVillage ();
		
		$l1 = $portal->getCasterLocation ();
		$l2 = $portal->getTargetLocation ();
		
		$db->query
		("
			INSERT INTO
				map_portals
			SET
				p_caster_v_id = {$v1->getId ()},
				p_target_v_id = {$v2->getId ()},
				p_caster_x = {$l1[0]},
				p_caster_y = {$l1[1]},
				p_target_x = {$l2[0]},
				p_target_y = {$l2[1]},
				p_caster_b_id = {$b1->getId ()},
				p_target_b_id = {$b2->getId ()},
				p_endDate = NULL
		");
	}
	
/**
	Actual class
*/

	private $data;
	private $id;
	
	private $vCaster;
	private $vTarget;
	
	private $bCaster;
	private $bTarget;
	
	public function __construct ($id)
	{
		$this->id = intval ($id);
	}
	
	public function setData ($data)
	{
		$this->data = $data;
		
		$registry = Dolumar_Registry_Village::getInstance ();
		
		// Fetch villages
		$this->vCaster = $registry->get ($data['p_caster_v_id']);
		$this->vTarget = $registry->get ($data['p_target_v_id']);
	}
	
	public function setId ($id)
	{
		$this->id = $id;
	}
	
	public function getId ()
	{
		return $this->id;
	}
	
	public function getOtherSide ($village)
	{
		if ($this->vCaster->equals ($village))
		{
			return $this->vTarget;
		}
		else
		{
			return $this->vCaster;
		}
	}
	
	public function getCasterBuilding ()
	{
		if (!isset ($this->bCaster))
		{
			$this->setCasterBuilding ($this->vCaster->buildings->getBuilding ($this->data['p_caster_b_id']));
		}
		return $this->bCaster;
	}
	
	public function setCasterBuilding (Dolumar_Buildings_Portal $building)
	{
		$this->bCaster = $building;
	}
	
	public function getTargetBuilding ()
	{
		if (!isset ($this->bTarget))
		{
			$this->setTargetBuilding ($this->vTarget->buildings->getBuilding ($this->data['p_target_b_id']));
		}
		return $this->bTarget;
	}
	
	public function setTargetBuilding (Dolumar_Buildings_Portal $building)
	{
		$this->bTarget = $building;
	}
	
	public function getCasterLocation ()
	{
		$b = $this->getCasterBuilding ();
		if (!$b) { return array (0, 0); }
		return $b->getLocation ();
	}
	
	public function getTargetLocation ()
	{
		$b = $this->getTargetBuilding ();
		if (!$b) { return array (0, 0); }
		return $b->getLocation ();
	}
	
	/*
		Returns TRUE if this portal is between village 1 and village 2.
		Mind that a portal is between two villages if they both are on
		one of the sided clans.
	*/
	public function isBetween ($village1, $village2)
	{
		if 
		(
			($this->vCaster->equals ($village1) && $this->vTarget->equals ($village2)) ||
			($this->vCaster->equals ($village2) && $this->vTarget->equals ($village1))
		)
		{
			return true;
		}
	
		$myclans = $village1->getOwner ()->getClans ();
		$hisclans = $village2->getOwner ()->getClans ();
		
		$casterclans = $this->vCaster->getOwner ()->getClans ();
		$targetclans = $this->vTarget->getOwner ()->getClans ();
		
		foreach ($myclans as $c11)
		{
			foreach ($hisclans as $c12)
			{
				foreach ($casterclans as $c21)
				{
					foreach ($targetclans as $c22)
					{
						if
						(
							($c11->equals ($c21) && $c12->equals ($c22)) ||
							($c11->equals ($c22) && $c12->equals ($c21))
						)
						{
							return true;
						}
					}
				}
			}
		}
		
		return false;
	}
	
	/*
		Return the distance penalty
	*/
	public function getDistancePenalty ()
	{
		return 500;
	}
}
?>
