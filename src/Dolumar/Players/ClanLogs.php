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

class Dolumar_Players_ClanLogs extends Dolumar_Players_Logs
{
	public static function __getInstance ()
	{
		static $in;
		if (!isset ($in) || empty ($in))
		{
			$in = new self ();
			$in->clearFilters ();
			$in->applyFilters ();
		}
		return $in;
	}
	
	public static function getInstance ()
	{
		return self::__getInstance ();
	}
	
	public function applyFilters ()
	{
		$this->addShowOnly ('defend');
		$this->addShowOnly ('attack');
		$this->addShowOnly ('portal_open');
	}
	
	public function getClanLogs ($objClan, $startPoint = 0, $length = 50, $order = 'DESC')
	{		
		$villages = $this->getVillages ($objClan);
		return $this->getLogs ($villages, $startPoint, $length, $order);
	}
	
	public function countClanLogs ($objClan)
	{
		$villages = $this->getVillages ($objClan);	
		return $this->countLogs ($villages);
	}
	
	private function getVillages ($objClan)
	{
		if (!is_array ($objClan))
		{
			$objClan = array ($objClan);
		}
	
		$villages = array ();
		foreach ($objClan as $clan)
		{
			foreach ($clan->getMembers () as $members)
			{
				foreach ($members->getVillages () as $v)
				{
					$villages[] = $v;
				}
			}
		}
		
		return $villages;
	}
}
?>
