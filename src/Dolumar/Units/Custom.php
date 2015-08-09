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

class Dolumar_Units_Custom extends Dolumar_Units_Unit
{
	private $stats = array
	(
		'atAt' => 20,
		'atDef' => 25,
		'hp' => 600,
		'defIn' => 35,
		'defAr' => 40,
		'defCav' => 50,
		'defMag' => 10
	);

	private $attackType = 'defAr';
	
	public function getConsumption ()
	{
		return array ();
	}

	public function setAttackType ($at)
	{
		if ($at == 'defAr')
		{
			$this->attackType = 'defAr';
		}

		elseif ($at == 'defCav')
		{
			$this->attackType = 'defCav';
		}

		elseif ($at == 'defMag')
		{
			$this->attackType = 'defMag';
		}

		else
		{
			$this->attackType = 'defIn';
		}
	}

	public function getAttackType ()
	{
		return $this->attackType;
	}

	public function setStats ($atAt, $atDef, $hp, $defIn, $defAr, $defCav, $defMag)
	{
		$this->stats = array
		(
			'atAt' => $atAt,
			'atDef' => $atDef,
			'hp' => $hp,
			'defIn' => $defIn,
			'defAr' => $defAr,
			'defCav' => $defCav,
			'defMag' => $defMag
		);
	}

	public function getStats ()
	{
		return $this->stats;
	}
}
?>
