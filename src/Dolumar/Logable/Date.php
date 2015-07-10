<?php
/*
	This container groups a bunch of resources / runes.
*/
class Dolumar_Logable_Date extends Dolumar_Logable_Container
{
	public static function getFromId ($id)
	{
		$res = self::getDataFromId ($id);
		return new self ($res);
	}
	
	public function getName ()
	{
		$data = $this->getLogArray ();
		return isset ($data[0]) ? date (DATETIME, $data[0]) : null;;
	}
}
?>
