<?php
class Dolumar_Players_NPCPlayer extends Dolumar_Players_Player
{
	public function getNickname ()
	{
		return 'Gaia';
	}

	public function loadData ()
	{
		// Do nothing.
	}

	public function isPlaying ()
	{
		return true;
	}

	public function isDeveloper ()
	{
		return false;
	}

	public function isAdmin ()
	{
		return false;
	}

	public function isModerator ()
	{
		return false;
	}

	public function isChatModerator ()
	{
		return false;
	}
}