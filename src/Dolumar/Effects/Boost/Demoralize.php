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

class Dolumar_Effects_Boost_Demoralize extends Dolumar_Effects_Boost
{
	protected $sType = 'thievery';
	protected $iDuration = 21600;

	protected function getBonusFromLevel ()
	{
		//return 10 + $this->getLevel () * 15;
		
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 5;
			break;
			
			case 2:
				return 6;
			break;
			
			case 3:
				return 7;
			break;
			
			case 4:
				return 8;
			break;
			
			case 5:
				return 9;
			break;
		}
	}

	public function procMoraleCheck ($morale, $fight) 
	{
		$morale -= ($morale / 100) * $this->getBonusFromLevel ();
		
		return $morale;
	}
	
	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'penalty' => $this->getBonusFromLevel ()
			)
		);
	}
}
?>
