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

class Dolumar_Windows_Disclaimer extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('250px', '300px');
		$this->setTitle ('Village Overview');
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		//return false;
		return '<h1>The future of Dolumar</h1>'.
			'<p>Hello everyone! I have great news! I\'m working on the next version of Dolumar, which I will launch on '.
			'newyears eve. I\'m taking the old version offline until then, so that is why your village has been remove.</p>'.
			'<p>You will not be able to register / start a new village until '.date (DATETIME, GAME_LAUNCHDATE).'<br />(time zone: '.TIME_ZONE.').</p>'.
			'<p>I hope you can understand my decision. A reset was required anyway, so let\'s hope the next version of the '.
			'game will be better then the last one!</p>'.
			'<p>See you soon!<br />The Dolumar Development team.</p>';
	}
}
?>
