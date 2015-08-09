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

class Dolumar_Windows_IngameForum extends Neuron_GameServer_Windows_Window
{
	private $objForum;

	public function setSettings ()
	{		
		$this->objForum = $this->getForum ();
	
		// Window settings
		$this->setSize ('600px', '400px');
		$this->setPosition ('20px', '70px');
		$this->setTitle ($this->objForum->getTitle ());
		$this->setClass ('forumwin');
	}
	
	protected function getForum ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$login = Neuron_Core_Login::__getInstance ();

		if ($login->isLogin ())
		{
			$me = Neuron_GameServer::getPlayer ();
			$forum = new Neuron_Forum_Forum (0, 0, $me, $me->isChatModerator (), $me->isChatModerator ());
		}
		else
		{
			$forum = new Neuron_Forum_Forum (0, 0, false, false, false);
		}
		
		$forum->setTitle ($text->get ('ingameForum', 'menu', 'main'));
		
		return $forum;
	}

	public function getContent ()
	{
		if ($this->objForum)
		{
			return @$this->objForum->getHTML ($this->getInputData ());
		}
		else
		{
			return '<p class="false">Invalid Input: clan not found.</p>';
		}
	}

	public function processInput ()
	{
		$this->updateContent ();
	}
}
?>
