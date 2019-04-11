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

class Dolumar_Mappers_BuildingMapper
{
	public static function getInstance ()
	{
		static $in;
		if (!isset ($in))
		{
			$in = new self ();
		}
		return $in;
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $building
	 * @param int $radius
	 * @return Dolumar_Buildings_Building[]
	 */
	public function getBuildingsFromTypeWithinRadius ($x, $y, $building, $radius)
	{
        $buildingSQL = Dolumar_Map_Map::getBuildingsFromLocations ([ $x, $y ], $radius);

        $objects = array ();
        foreach ($buildingSQL as $buildingV)
        {
            $race = Dolumar_Races_Race::getRace ($buildingV['race']);

            $b = Dolumar_Buildings_Building::getBuilding
            (
                $buildingV['buildingType'],
                $race,
                $buildingV['xas'], $buildingV['yas']
            );

            if ($b->getBuildingId() != $building) {
                continue;
            }

            $village = Dolumar_Players_Village::getVillage ($buildingV['village']);

            $b->setVillage ($village);

            $b->setData ($buildingV['bid'], $buildingV);

            if ($buildingV['destroyDate'] > 0 && $buildingV['destroyDate'] < NOW)
            {
                $b->setDestroyed (true);
            }

            $objects[] = $b;
        }

        return $objects;
	}
}