<?php
/*
	This class is a prototype for the boosts ingame.
	It is used for skills, spells, etc.
*/
interface Dolumar_Players_iBoost
{
	/*
		Building bonusses
	*/
	public function procBuildingCost ($resources, $objBuilding); // Not working (I think)
	public function procBuildCost ($resources, $objBuilding); // Not working (I think)
	public function procUpgradeCost ($resources, $objBuilding); // Not working (I think)
	
	public function procCapacity ($resources, $objBuilding); // WORKING
	public function procIncome ($resources, $objBuilding); // WORKING!
	
	/*
		Unit bonus
	*/
	public function procUnitStats (&$stats, $objUnit);
}
?>
