<?php
/*
	This container groups a bunch of resources / runes.
*/
class Dolumar_Logable_Location extends Dolumar_Logable_Container
{
	public static function getFromId ($id)
	{
		$res = self::getDataFromId ($id);
		return new self ($res);
	}
	
	public function getName ()
	{
		$data = $this->getLogArray ();
		if (isset ($data[0]) && isset ($data[1]))
		{
			return '['.$data[0] . ',' . $data[1].']';
		}
		else
		{
			return 'unknown';
		}
	}
}
?>
