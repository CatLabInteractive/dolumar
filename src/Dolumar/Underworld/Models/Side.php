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

class Dolumar_Underworld_Models_Side
{
	private $side;
	private $clans = array ();

	public function __construct ($side)
	{
		$this->side = $side;
	}
	
	public function getId ()
	{
		return $this->side;
	}

	public function addClan (Dolumar_Players_Clan $clan)
	{
		$this->clans[] = $clan;
	}

	public function hasClan (Dolumar_Players_Clan $clan)
	{
		foreach ($this->clans as $v)
		{
			if ($clan->equals ($v))
			{
				return true;
			}
		}

		return false;
	}

	public function getClans ()
	{
		return $this->clans;
	}

	public function getDisplayName ()
	{
		return '<span>Side ' . $this->getId () . '</span>';
	}
	
	public function equals (Dolumar_Underworld_Models_Side $side)
	{
		return $this->side == $side->side;
	}
}
