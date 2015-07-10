<?php
class Dolumar_Registry_Village extends Neuron_Core_Registry
{
	public static function getInstance ($class = null)
	{
		return parent::getInstance (__CLASS__);
	}

	protected function getNewObject ($id)
	{
		return Dolumar_Players_Village::getVillage ($id);
	}
}
?>
