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

class Dolumar_Effects_Instant_OpenPortal extends Dolumar_Effects_Instant
{
	public function prepare ()
	{
		// Fetch a random target
		$networth = $this->getVillage ()->getScore ();
		
		$minscore = floor ($networth * 0.75);
		$maxscore = ceil ($networth * 1.25);
		
		$db = Neuron_DB_Database::getInstance ();
		
		$myclanlist = "";
		foreach ($this->getVillage ()->getOwner ()->getClans () as $v)
		{
			$myclanlist .= $v->getId ().",";
		}
		$myclanlist = substr ($myclanlist, 0, -1);
		
		$chk = $db->query
		("
			SELECT
				*
			FROM
				villages v
			LEFT JOIN
				n_players p USING(plid)
			LEFT JOIN
				clan_members cm ON cm.plid = p.plid AND cm.c_id IN ({$myclanlist})
			WHERE
				v.networth > {$minscore} AND
				v.networth < {$maxscore} AND
				v.plid != {$this->getVillage ()->getOwner ()->getId ()} AND
				v.isActive = 1 AND
				p.startVacation IS NULL AND
				cm.c_id IS NULL
			ORDER BY
				RAND()
			LIMIT
				1
		");
		
		//die ($db->getLastQuery ());
		
		if (count ($chk) > 0)
		{	
			$village = Dolumar_Players_Village::getVillage ($chk[0]['vid']);
			$this->setTarget ($village);
		}
		else
		{
			$this->setCastable (false);
			$this->setError (Dolumar_Effects_Effect::ERROR_NO_TARGET_FOUND);
		}
	}

	public function execute ($a = null, $b = null, $c = null)
	{
		$target = $this->getTarget ();
		if (isset ($target))
		{
			$this->getVillage ()->portals->openPortal ($target);
		}
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 4;
	}
}
?>
