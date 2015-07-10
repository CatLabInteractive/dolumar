<?php
/*
	This container groups a bunch of resources / runes.
*/
class Dolumar_Logable_ResourceContainer extends Dolumar_Logable_Container
{
	public static function getFromId ($id)
	{
		$res = self::getDataFromId ($id);
		return new self ($res);
	}
	
	public function getId ()
	{
		$data = '';
		foreach ($this->resources as $k => $v)
		{
			if ($v != 0)
			{
				$data .= $k.'='.$v.'&';
			}
		}
		return substr ($data, 0, -1);
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
