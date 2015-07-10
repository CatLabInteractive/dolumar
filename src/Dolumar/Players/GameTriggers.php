<?php
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