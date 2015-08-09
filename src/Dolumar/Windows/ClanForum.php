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

class Dolumar_Windows_ClanForum extends Dolumar_Windows_IngameForum
{
	protected function getForum ()
	{
		$requestData = $this->getRequestData ();
		
		if (isset ($requestData['clan']))
		{
			$clan = new Dolumar_Players_Clan ($requestData['clan']);
			if ($clan)
			{
				$login = Neuron_Core_Login::__getInstance ();

				if ($login->isLogin ())
				{
					$me = Neuron_GameServer::getPlayer ();
					
					$isMember = $clan->isMember ($me);
					$isModerator = $clan->isModerator ($me);
					
					//__construct ($iForumType, $iForumId, $objUser = false, $bCanSeeAll = false, $bIsModerator = false)
					$forum = new Neuron_Forum_Forum (1, $clan->getId (), $me, $isMember, $isModerator);
				}
				else
				{
					$forum = new Neuron_Forum_Forum (1, $clan->getId (), false, false, false);
				}
				
				// Fetch thze title
				$text = Neuron_Core_Text::__getInstance ();
				
				$forum->setTitle 
				(
					Neuron_Core_Tools::putIntoText 
					(
						$text->get ('title', 'forum', 'clan'),
						array
						(
							'clan' => Neuron_Core_Tools::output_varchar ($clan->getName ())
						)
					)
				);
		
				return $forum;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
}
?>
