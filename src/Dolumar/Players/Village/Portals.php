<?php
class Dolumar_Players_Village_Portals
{
	private $village;
	
	private $lx = false;
	private $ly = false;
	
	const PORTAL_LIFESPAN_HOURES = 96;

	public function __construct ($objMember)
	{
		$this->village = $objMember;
	}
	
	public function openPortal ($village)
	{
		if ($village->equals ($this->village))
		{
			throw new Neuron_Core_Error ('Can\'t open portal to own village.');
		}
	
		$race = $village->getRace ();
		$building = Dolumar_Buildings_Portal::getBuilding (60, $race);
		
		$date = NOW + ((self::PORTAL_LIFESPAN_HOURES * 60 * 60) / GAME_SPEED_EFFECTS);
		
		$loc1 = $this->openPortalNearVillage ($this->village, $building, $date);
		$loc2 = $this->openPortalNearVillage (      $village, $building, $date);
		
		$db = Neuron_DB_Database::getInstance ();
		
		$db->query
		("
			INSERT INTO
				map_portals
			SET
				p_caster_v_id = {$this->village->getId ()},
				p_target_v_id = {$village->getId ()},
				p_caster_x = {$loc1[0]},
				p_caster_y = {$loc1[1]},
				p_target_x = {$loc2[0]},
				p_target_y = {$loc2[1]},
				p_caster_b_id = {$loc1[2]},
				p_target_b_id = {$loc2[2]},
				p_endDate = FROM_UNIXTIME({$date})
		");
		
		$logs = Dolumar_Players_Logs::getInstance ();
		$logs->addOpenPortalLog ($this->village, $village, $date);
	}
	
	private function openPortalNearVillage ($village, $building, $date)
	{
		$location = $this->getPortalLocation ($village);
		$b = $building->build ($village, $location[0], $location[1], false);
		
		$b->doDestructBuilding (false, $date, false);
		
		$village->buildings->reloadBuildings ();
		
		return array
		(
			$location[0],
			$location[1],
			$b->getId ()
		);
	}
	
	private function getPortalLocation ($village)
	{
		$tcloc = $village->buildings->getTownCenterLocation ();
		
		$this->lx = false;
		$this->ly = false;
		
		// Fetch location
		list ($tx, $ty) = $tcloc;
		
		$startRad = 0;
		$endRad = 2;
		
		$c = mt_rand ($startRad * 10000, $endRad * 10000) / 10000;
		$r = 10;
		$x = $tx;
		$y = $ty;
		
		while (!$this->isValidLocation ($village, $x, $y))
		{
			$x = $tx + floor (sin ($c) * $r);
			$y = $ty + floor (cos ($c) * $r);
			
			$c += 0.05;
			
			if ($c > $endRad)
			{
				$c = $startRad;
				$r ++;
			}
		}
		
		return array ($x, $y);
	}
	
	private function isValidLocation ($village, $x, $y)
	{
		if ($x == $this->lx && $y == $this->ly)
		{
			return false;
		}
	
		// Fetch location
		$location = Dolumar_Map_Map::getLocation ($x, $y);
		
		if (!$location->canBuildBuilding ())
		{
			return false;
		}
		
		return !$village->buildings->isBuildingOnLocation ($x, $y);
	}
	
	public function __destruct ()
	{
		unset ($this->village);
	}
}
?>
