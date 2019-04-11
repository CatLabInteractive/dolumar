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

/*
	This class handles AI 'strategy'
*/
class Dolumar_Players_Village_Ai
{
    const MAX_AVAILABLE_RUNES = 20; // how long do we wait before scouting for new runes?
    const SCOUT_RUNES_AMOUNT = 4; // how many runes do we scout for?
    const MAX_STRENGTH_FACTOR = 1.2; // how much bigger/smaller than the closeby players can this village become?

    /**
     * This parameter defines the building ratio we are aiming for.
     * @var array
     */
    protected $buildingRatios = [
        Dolumar_Buildings_Silo::class =>        0.8,
        Dolumar_Buildings_Farm::class =>        1.5,
        Dolumar_Buildings_Lumber::class =>      0.8,
        Dolumar_Buildings_StoneMine::class =>   0.5,
        Dolumar_Buildings_IronMine::class =>    0.8,
        Dolumar_Buildings_Barrack::class =>     1.0
    ];

	/**
	 * @var Dolumar_Players_Village
	 */
	private $objProfile;

    /**
     * @var callable
     */
	private $logger;

	/**
	 * Dolumar_Players_Village_Ai constructor.
	 * @param $profile
	 */
	public function __construct ($profile)
	{
		$this->objProfile = $profile;
	}

	/**
	 * @return Dolumar_Players_Village
	 */
	private function getVillage ()
	{
		return $this->objProfile;
	}

    /**
     * @param null $logger
     * @throws Neuron_Core_Error
     */
	public function run($logger = null)
	{
        $this->logger = $logger;

		// Is this an npc?
		if (!$this->getVillage()->isNpc()) {
			return;
		}

		$this->log('AI reporting for duty');

		$this->log('Runes: ' . print_r($this->getVillage()->resources->getRunes(), true));
        $this->log('Resources: ' . print_r($this->getVillage()->resources->getResources(), true));

		// should we scout for runes?
        $this->scoutForRunes();

        // should we build more buildings?
        $this->buildBuildings();

        // should we train more units?
        $this->trainUnits();

        // should we war?
        $this->doWar();
	}

    /**
     * Should we scout for runes?
     * @throws Neuron_Core_Error
     */
	private function scoutForRunes()
    {
        $village = $this->getVillage();

        $availableRunes = $village->resources->getAvailableRunes_amount();
        $this->log('Available runes: ' . $availableRunes);

        $maxStrength = $this->getMaxVillageStrength();
        $ourStrength = $this->getCurrentStrength();

        $this->log('Max strength based on nearby players: ' . $maxStrength);
        $this->log('Our current strength: ' . $ourStrength);

        // Are we already stronger than the neighbouring players?
        if ($ourStrength / self::MAX_STRENGTH_FACTOR > $maxStrength) {
            $this->log('Not scouting for runes: our village is getting too strong');
            return;
        }

        if ($availableRunes > self::MAX_AVAILABLE_RUNES) {
            $this->log('Not scouting for runes: ' . $availableRunes . ' available');
            return;
        }

        if ($village->isScouting()) {
            $this->log('Not scouting for runes: we are already scouting');
            return;
        }

        // Do we have enough resources?
        if ($village->scout(self::SCOUT_RUNES_AMOUNT)) {
            $this->log('Succesfully scouted for runes.');
        } else {
            $this->log('Failed scouting for runes (not enough resources?)');
        }
    }

    /**
     * Scheudle
     */
    private function buildBuildings()
    {
        $village = $this->getVillage();

        if (!$village->readyToBuild()) {
            $this->log('Cannot build any more buildings.');
        }

        // Are we already building something?
        $busy = $village->buildings->getBuildingBuildings();
        if (count($busy) >= 2) {
            $this->log('We are already building ' . count($busy) . ' buildings; not going to schedule another one.');
            return;
        }

        // Sort the buildings we want to build based on the difference between the planned ratio and the current ratio
        $toBuild = $this->getBuildOrder();

        // Show what we are planning to build
        $this->log('Build order:');
        $i = 0;
        foreach ($toBuild as $v) {
            $this->log((++ $i) . ': ' . $v);
        }

        // Now actually try to build something
        foreach ($toBuild as $building) {

            $this->log('Trying to build/upgrade ' . $building);

            // Check if there are requirements that don't work yet
            $buildingObj = Dolumar_Buildings_Building::getBuildingFromName ($building, $this->getVillage()->getRace ());

            if (!$buildingObj->canBuildBuilding ($this->getVillage())) {
                $this->log('canBuildBuilding returns false. Try next building.');
                continue;
            }

            if (!$this->buildOrUpgrade($building)) {
                $this->log('Failed to build a ' . $building);
                return;
            } else {
                return;
            }
        }
    }

    /**
     * @param $buildingClassName
     * @return bool
     */
    private function buildOrUpgrade($buildingClassName)
    {
        // Count the amount of buildings we have of this type
        $targetLevel = $this->getVillage()->buildings->getBuildingAmount($buildingClassName) + 1;

        $this->log('Target level for ' . $buildingClassName . ' = ' . $targetLevel);

        // Look for buildings that can be upgraded
        foreach ($this->getVillage()->buildings->getBuildings() as $building) {

            /** @var Dolumar_Buildings_Building $building */

            if (!$building instanceof $buildingClassName) {
                continue;
            }

            // did we reach target level yet?
            if ($building->getLevel() < $targetLevel) {
                // upgrade!
                if ($this->upgradeBuilding($building)) {
                    $this->log('Succesfully upgraded a ' . $buildingClassName);
                    return true;
                }
            }
        }

        // upgrade didn't work? Try to build.
        $buildingObj = Dolumar_Buildings_Building::getBuildingFromName ($buildingClassName, $this->getVillage()->getRace ());

        if ($this->buildBuilding($buildingObj)) {
            return true;
        }

        return false;
    }

    /**
     * Try to upgrade a specific building
     * @param Dolumar_Buildings_Building $building
     * @return bool
     */
    private function upgradeBuilding(Dolumar_Buildings_Building $building)
    {
        $status = $building->upgrade();
        if ($status === true) {
            return true;
        }

        $this->log('Failed upgrading building ' . $building->getId() . ': ' . $status);
        return false;
    }

    /**
     * @param Dolumar_Buildings_Building $building
     * @return bool
     */
    private function buildBuilding(Dolumar_Buildings_Building $building)
    {
        // find a suitable location
        $tcLocation = $this->getVillage()->buildings->getTowncenterLocation();
        $location = $this->getVillage()->buildings->getEmptyLocationNearPosition($tcLocation);

        $this->log('Trying to build ' . $building->getClassName() . ' at [' . $location[0] . ',' . $location[1] . ']');

        if ($building->canSelectRune($this->getVillage())) {
            $building->setChosenRune($this->getVillage(), $this->getRandomAvailableRune());
        }

        $b = $this->getVillage()->buildings->build($building, $location[0], $location[1]);

        if ($b) {
            $this->log('Succesfully built ' . $building->getName());
            return true;
        } else {

            $error = $this->getVillage()->buildings->getError();
            switch ($error) {

                case 'resources':
                    $this->log('Insufficient resources: ' . print_r($building->getBuildingCost($this->getVillage()), true));
                    break;

                default:
                    $this->log('Build error: ' . $error);;
                    break;
            }


            return false;
        }
    }

    /**
     * @return string
     */
    private function getRandomAvailableRune()
    {
        $runes = $this->getVillage()->resources->getRunes();

        $available = [];
        foreach ($runes as $k => $v) {
            if ($v > 0) {
                $available[] = $k;
            }
        }

        $rune = $available[array_rand($available)];

        $this->log('Picked random rune: ' . $rune);
        return $rune;
    }

    /**
     * @return string[]
     */
    private function getBuildOrder()
    {
        $toBuild = $this->buildingRatios;

        $totalToBuild = 0;
        foreach ($toBuild as $v) {
            $totalToBuild += $v;
        }

        $runes = max(1, $this->getVillage()->resources->getUsedRunes_amount ());

        uksort($toBuild, function($key1, $key2) use ($runes, $totalToBuild) {

            $desired1 = $this->buildingRatios[$key1] / $totalToBuild;
            $desired2 = $this->buildingRatios[$key2] / $totalToBuild;

            $current1 = $this->getVillage()->buildings->getBuildingsRuneAmount ($key1) / $runes;
            $current2 = $this->getVillage()->buildings->getBuildingsRuneAmount ($key2) / $runes;

            $diff1 = $desired1 - $current1;
            $diff2 = $desired2 - $current2;

            if ($diff1 > $diff2) {
                return -1;
            } else {
                return 1;
            }
        });

        return array_keys($toBuild);
    }

    /**
     * Try to train as much units as possible
     * @throws Neuron_Core_Error
     */
    private function trainUnits()
    {
        $this->log('Train units');
        $buildings = $this->getVillage()->buildings->getBuildings();

        foreach ($buildings as $building) {

            if ($building instanceof Dolumar_Buildings_Training) {

                $this->trainUnitsInBuilding($building);

            }

        }
    }

    /**
     * @param Dolumar_Buildings_Training $building
     * @return bool|void
     * @throws Neuron_Core_Error
     */
    private function trainUnitsInBuilding(Dolumar_Buildings_Training $building)
    {
        $this->log('Training units in ' . $building->getName() . ' ' . $building->getId());



        if (!$building->isFinished()) {
            $this->log('Building is not finished yet... ' . ($building->getReadyTimeLeft()) . ' seconds remaining');
            return;
        }

        if ($building->isTrainingUnits()) {
            $this->log('Building is busy, not training now.');
            return;
        }

        $availableUnits = $building->getCheckedAvailableUnits();
        if (count($availableUnits) === 0) {
            $this->log('No available units found.');
            return;
        }

        $unit = $availableUnits[0];

        $amount = $building->calculateOptimalTrainable($unit);
        if ($amount < 1) {
            $this->log('Cannot build anymore units.');
            return;
        }

        $check = $building->doTrainUnits($unit, $amount);
        if ($check) {
            $this->log('Succesfully trained ' . $amount . ' ' . $unit->getName(true));
            return true;
        } else {
            $this->log('Failed training ' . $amount . ' ' . $unit->getName(true) . ': ' . $building->getTrainError());
            return false;
        }
    }

    /**
     * @param string $message
     */
	private function log($message)
    {
        if ($this->logger) {
            call_user_func($this->logger, '[' . $this->getVillage()->getId() . '] ' . $message);
        }
    }

    /**
     * Calculate the average strenght of the surrounding villages.
     * = the max amount of runes this village may contain.
     * @return int
     * @throws Neuron_Core_Error
     */
    private function getMaxVillageStrength()
    {
        $mapper = Dolumar_Mappers_BuildingMapper::getInstance ();
        $center = $this->getVillage()->buildings->getTownCenterLocation ();

        $buildings = $mapper->getBuildingsFromTypeWithinRadius ($center[0], $center[1], 1, 100);

        $maxStrength = null;

        foreach ($buildings as $building) {

            $village = $building->getVillage();

            // npc villages don't count
            if ($village->isNpc()) {
                continue;
            }

            $runes = $village->resources->getTotalRunes_amount();
            if ($maxStrength < $runes) {
                $maxStrength = $runes;
            }
        }

        if ($maxStrength < 10) {
            $maxStrength = 10;
        }

        return $maxStrength;
    }

    /**
     *
     */
    private function doWar()
    {
        $units = $this->getVillage()->getAllUnits();

        $this->log('Current army:');
        foreach ($units as $v) {
            $this->log($v->getAmount() . ' ' . $v->getName(true));
        }
    }

    /**
     * @return int
     */
    private function getCurrentStrength()
    {
        return $this->getVillage()->resources->getTotalRunes_amount();
    }

    /**
     *
     */
    public function __destruct ()
    {
        unset ($this->objProfile);
    }
}
