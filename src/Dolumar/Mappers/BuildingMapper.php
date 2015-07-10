<?php
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
		return array ();
	}
}