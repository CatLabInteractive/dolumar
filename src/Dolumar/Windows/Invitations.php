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

class Dolumar_Windows_Invitations extends Neuron_GameServer_Windows_Window
{

	public function setSettings ()
	{
	
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('250px', '105px');
		$this->setTitle ($text->get ('invitations', 'menu', 'main'));
		
		$this->setAllowOnlyOnce ();
	
	}
	
	public function getContent ()
	{
		$login = Neuron_Core_Login::__getInstance ();
		$db = Neuron_Core_Database::__getInstance ();
		$text = Neuron_Core_Text::__getInstance ();
		
		if ($login->isLogin ())
		{
		
			// Check for invitation key
			$key = $db->select
			(
				'invitation_codes',
				array ('invCode', 'invLeft'),
				"plid = '".$login->getUserId ()."'"
			);
			
			if (count ($key) < 1)
			{
				$this->generateNewKey ($login->getUserId ());
			}
			
			else {
				$this->invKey = $key[0]['invCode'];
				$this->invLeft = $key[0]['invLeft'];
			}
			
			$page = new Neuron_Core_Template ();
			
			$page->setVariable ('invKey', $this->invKey);
			$page->setVariable ('invLeft', $this->invLeft);
			
			return $page->parse ('invitations.tpl');
		
		}
		
		else {
			return '<p class="false">'.$text->get ('login', 'login', 'account').'</p>';
		}
	}
	
	private function generateNewKey ($plid)
	{
	
		$db = Neuron_Core_Database::__getInstance ();
		
		$okay = false;
		while (!$okay)
		{
		
			// Let's go mad.
			$key = substr (md5 (rand (-999999999999999, 999999999999999)), rand (0, 15), 15);
			
			$check = $db->select
			(
				'invitation_codes',
				array ('invCode'),
				"invCode = '$key'"
			);
			
			if (count ($check) == 0)
			{
				$db->insert
				(
					'invitation_codes',
					array
					(
						'plid' => $plid,
						'invCode' => $key,
						'invLeft' => 15
					)
				);
				
				$okay = true;
			}
		
		}
	
		$this->invKey = $key;
		$this->invLeft = 15;
	}

	public function getRefresh ()
	{
	

	
	}

}

?>
