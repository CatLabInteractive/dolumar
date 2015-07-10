<?php
/*
	This container groups a bunch of resources / runes.
*/
class Dolumar_Logable_Building extends Dolumar_Logable_Container
{
	public function __construct ($data)
	{
		if ($data instanceof Dolumar_Buildings_Building)
		{
			list ($x, $y) = $data->getLocation ();
		
			$data = array
			(
				'building' => $data->getBuildingId (),
				'level' => $data->getLevel (),
				'race' => $data->getVillage ()->getRace ()->getId (),
				'x' => $x,
				'y' => $y
			);
		}
		
		parent::__construct ($data);
	}

	public static function getFromId ($id)
	{
		$res = self::getDataFromId ($id);
		return new self ($res);
	}
	
	public function getName ()
	{
		$data = $this->getLogArray ();
		
		//print_r ($data);
		
		$id = $data['building'];
		$race = Dolumar_Races_Race::getFromId ($data['race']);
		
		list ($locationX, $locationY) = array ($data['x'], $data['y']);
		$level = $data['level'];
	
		$building = Dolumar_Buildings_Building::getBuilding ($id, $race, $locationX, $locationY);
		
		if ($building)
		{
			$text = Neuron_Core_Text::getInstance ();
			return $building->getName () . ' '.$text->get ('lvl', 'building', 'building').' '. $level;
		}
		else
		{
			return 'Building not found: '.print_r ($data);
		}
	}
}
?>
