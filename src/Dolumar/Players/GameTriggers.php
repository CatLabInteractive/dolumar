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

class Dolumar_Players_GameTriggers
{

	protected $player;
	
	public function __construct ($player)
	{
		$this->player = $player;
	}

	/*
		This function is triggered when the "home location" of a player changes.
		This happens when a player chooses a race (or when he sets it manually).
	*/
	public function setPublicHomeLocation ($x, $y) { }

	/*
		Sent a notification to a player

		For example
		"You are defending an attack.", "battle", "defending"
	*/
	public function sendNotification ($msg, $action, $subaction, $rawdata = array ())
	{

	}

	/*
		WRONG WRONG WRONG!
	*/
	public function sentNotification ($msg, $action, $subaction, $rawdata = array ())
	{
		throwAlertError ('Please replace this notification with an english one (sentNotification).');
		$this->sendNotification ($msg, $action, $subaction, $rawdata);
	}
	
	////////////////////////////////////////////////////////////////////
	// Various trigger /////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////
	/*
		Is called whenever something in the village changes.
		(Build building, destroy building, etc.)
		
		Is not called if "small things".
		
		(We might want to display the ranking / some information about the user).
	*/
	public function onVillageChange () { }
}
?>