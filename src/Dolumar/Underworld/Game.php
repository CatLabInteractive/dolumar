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

class Dolumar_Underworld_Game extends Dolumar_Game
{
	private $mission;

	public function __construct (Dolumar_Underworld_Models_Mission $mission = null)
	{
		$this->mission = $mission;
	}
	
	public function getMap ()
	{
		if (isset ($this->mission))
		{
			return $this->mission->getMap ();	
		}
		else
		{
			return new Neuron_GameServer_Map_Map2D ();
		}
	}
	
	public function getSide (Dolumar_Players_Player $player)
	{
		return $this->mission->getPlayerSide ($player);
	}
	
	public function getServer ()
	{
		return new Dolumar_Players_Server ();
	}
	
	public function getInitialWindows ($objServer)
	{
		$out = array ();

		if (isset ($this->mission))
		{
			$out[] = $objServer->getWindow ('Status');
		}

		else
		{
			$out[] = $objServer->getWindow ('Finished');
		}

		return $out;
	}
	
	/*
		Return a page
	*/
	public function getWindow ($sPage)
	{
		$sClassname = 'Dolumar_Underworld_Windows_'.ucfirst (strtolower ($sPage));
		if (class_exists ($sClassname))
		{
			return new $sClassname ();
		}
		else
		{
			$sClassname = 'Dolumar_Windows_' . ucfirst ($sPage);
			if (class_exists ($sClassname))
			{
				return new $sClassname;
			}
			else
			{
				return false;
			}
		}
	}

	public function getCustomOutput ()
	{
		if (!isset ($this->mission))
		{
			return '<p>The mission is finished.</p>';
		}
	}
}
?>
