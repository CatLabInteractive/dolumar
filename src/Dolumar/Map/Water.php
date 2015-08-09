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

class Dolumar_Map_Water extends Dolumar_Map_Location
{	
	public static function getMultipleImages ($fn, $random)
	{
		static $multiples;
		
		if (!isset ($multiples))
		{
			$multiples = array
			(
				'water_11111111' => array ('water_11111111', 'water_11111111', 'water_11111111', 'water_11111111', 'water_11111111', 'water_11111111', 'water_11111111b', 'water_11111111c', 'water_11111111d', 'water_11111111e'),
				'water_00110001' => array ('water_00110001', 'water_00110001b'),
				'water_01100010' => array ('water_01100010', 'water_01100010b'),
				'water_01110011' => array ('water_01110011', 'water_01110011', 'water_01110011', 'water_01110011b'),
				'water_10011000' => array ('water_10011000', 'water_10011000b'),
				'water_10111001' => array ('water_10111001', 'water_10111001b'),
				'water_11000100' => array ('water_11000100', 'water_11000100b'),
				'water_11011100' => array ('water_11011100', 'water_11011100b'),
				'water_11100110' => array ('water_11100110', 'water_11100110', 'water_11100110', 'water_11100110b'),
				'water_11110111' => array ('water_11110111', 'water_11110111b'),
				'water_11111011' => array ('water_11111011', 'water_11111011b'),
				'water_11111101' => array ('water_11111101', 'water_11111101b'),
				'water_11111110' => array ('water_11111110', 'water_11111110b')
			);
		}

		if (isset ($multiples[$fn]))
		{
			$key = $random % count ($multiples[$fn]);
			return $multiples[$fn][$key];
		}
		else
		{
			return $fn;
		}
	}

	public function getMapColor ()
	{
		return array (0, 0, 150);
	}
	
	public function isWaterLeft ()
	{
		return self::getLocation ($this->x - 1, $this->y)->isWater ();
	}
	
	public function isWaterUp ()
	{
		return self::getLocation ($this->x, $this->y + 1)->isWater ();
	}
	
	public function isWaterRight ()
	{
		return self::getLocation ($this->x + 1, $this->y)->isWater ();
	}
	
	public function isWaterBottom ()
	{
		return self::getLocation ($this->x, $this->y - 1)->isWater ();
	}

	public function getImage ()
	{
		/*
 			top left isConnected == (!isWater(x-1, y-1)) || !isWaterLeft(x,y) || !isWaterTop(x,y)). 
 			Or more neatly, !(isWater(x-1, y-1) && isWaterLeft(x, y) && isWaterTop(x, y))
		*/
		
		// Rename tiles that have multiple versions


		$fn = 'water_';

		// Zijden
		$l = $this->isWaterLeft ();
		$b = $this->isWaterUp ();
		$r = $this->isWaterRight ();
		$o = $this->isWaterBottom ();

		$zLinks = $l ? '1' : '0';
		$zBoven = $b ? '1' : '0';
		$zRechts = $r ? '1' : '0';
		$zOnder = $o ? '1' : '0';

		// Hoeken		
		$lb = self::getLocation ($this->x - 1, $this->y + 1);
		$rb = self::getLocation ($this->x + 1, $this->y + 1);
		$ro = self::getLocation ($this->x + 1, $this->y - 1);
		$lo = self::getLocation ($this->x - 1, $this->y - 1);
		
		$hLinksBoven = !($l && $b && $lb->isWater ()) ? '0' : '1';
		$hRechtsBoven = !($r && $b && $rb->isWater ()) ? '0' : '1';
		$hRechtsOnder = !($r && $o && $ro->isWater ()) ? '0' : '1';
		$hLinksOnder = !($l && $o && $lo->isWater ()) ? '0' : '1';
		
		// De hoeken
		$hoeken = $hLinksOnder . $hLinksBoven . $hRechtsBoven . $hRechtsOnder;

		// De zijden
		$zijden = $zLinks . $zBoven . $zRechts . $zOnder;
		
		$fn .= $zijden . $hoeken;
	
		return array
		(
			'image' => self::getMultipleImages ($fn, $this->randomNumber)
		);

	}

	public function canBuildBuilding ()
	{
		return false;
	}
	
	public function isWater ()
	{
		return true;
	}
}

?>
