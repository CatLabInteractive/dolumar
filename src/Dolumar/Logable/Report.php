<?php
/*
	This container groups a bunch of resources / runes.
*/
abstract class Dolumar_Logable_Report extends Dolumar_Logable_Container
{
	public static function getFromId ($id)
	{
		$res = self::getDataFromId ($id);
		return new self ($res);
	}
	
	public function getName ()
	{
		/*
			$res, 
			$showRunes = true, 
			$dot = true, 
			$village = false, 
			$runeId = 'rune', 
			$html = true, 
			$income = array (),
			$capacity = array ()
		*/
		
		return Neuron_Core_Tools::resourceToText
		(
			$this->getLogArray (),
			false,
			false,
			false,
			false,
			false
		);
	}
}
?>
