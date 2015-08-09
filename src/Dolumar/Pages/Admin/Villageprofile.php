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

class  Dolumar_Pages_Admin_Villageprofile extends Neuron_GameServer_Pages_Admin_Page
{
	public function getBody ()
	{
		$village = Neuron_Core_Tools::getInput ('_GET', 'village', 'int');
		$village = Dolumar_Players_Village::getVillage ($village);
		
		$body = '<h2>'.$village->getName ().'</h2>';
		$location = $village->buildings->getTownCenterLocation ();
		
		//$url = ABSOLUTE_URL . '#' . $location[0] . ',' . $location[1];
		$url = $this->getUrl ('Playerprofile', array ('plid' => $village->getOwner ()->getId ()));
		
		header ('Location: '.$url);
		
		return $body;
	}
}
?>
