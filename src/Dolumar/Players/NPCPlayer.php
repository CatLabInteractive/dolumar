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

    /*
        This function is called whenever the score gets updated.
    */
    public function updateScore ()
    {
        // do nothing.
    }

    /**
     * Get a name for a new village
     * @return string
     */
	public function getNewVillageName()
    {
        $firstNames = [
            'Qen',
            'Kird',
            'Gomirt',
            'Roxeqi',
            'Sanni',
            'Kibo',
            'Grals',
            'Glus'
        ];

        $lastNames = [
            'Slickbottoms',
            'Grimheads',
            'Pickscrews',
            'Mantales',
            'Grimflares',
            'Shortsnipes',
            'Fiercebuckets',
            'Shadowsnipes'
        ];

        $towns = [
            'Keep',
            'Dump',
            'Suburp',
            'Borough',
            'Nook',
            'Spot'
        ];

        $name = '';

        $name .= $firstNames[array_rand($firstNames)] . ' ';
        $name .= $lastNames[array_rand($lastNames)] . ' ';
        $name .= $towns[array_rand($towns)];

        return $name;

    }
}