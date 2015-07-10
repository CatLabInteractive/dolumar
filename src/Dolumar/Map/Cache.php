<?php
class Dolumar_Map_Cache extends Neuron_Core_Cache
{
	public static function __getInstance ($bla = null)
	{
		static $in;
		if (!isset ($in))
		{
			$in = new Dolumar_Map_Cache ('map/');
		}
		return $in;
	}
	
	public function hasLocationCache ($x, $y)
	{
		return parent::hasCache ('map'.$x.'x'.$y);
	}
	
	public function getLocationCache ($x, $y)
	{
		if ($this->hasCache ($x, $y))
		{
			return unserialize (parent::getCache ('map'.$x.'x'.$y));
		}
		return false;
	}
	
	public function setLocationCache ($x, $y, $data)
	{
		parent::setCache ('map'.$x.'x'.$y, serialize ($data));
	}
}
?>
