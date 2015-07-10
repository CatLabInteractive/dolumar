<?php
interface Dolumar_Effects_iBoost extends Dolumar_Effects_Effect implements Dolumar_Players_iBoost
{
	public function procBuildingCost 	($resources, $objBuilding);
	public function procBuildCost 		($resources, $objBuilding);
	public function procUpgradeCost 	($resources, $objBuilding);
	public function procCapacity 		($resources, $objBuilding);
	public function procIncome 		($resources, $objBuilding);
	public function procUnitStats 		(&$stats, $unit);
	public function procEffectDifficulty 	($difficulty, $effect);
	public function procDefenseBonus 	($def);
	public function procBattleVisible 	($battle);
	public function procMoraleCheck 	($morale, $fight);
	public function procEquipmentDuration	($duration, $item);
	public function procEquipmentCost	($cost, $item);
	
	public function onBatteFought ($battle);
}
?>
