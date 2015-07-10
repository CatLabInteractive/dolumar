<?php
class Dolumar_Map_Object implements Neuron_GameServer_Interfaces_Map_Object
{
	private $building;
	
	public function __construct ($building)
	{
		$this->building = $building;
	}
	
	public function getLocation ()
	{
		return $this->building->getLocation ();
	}
	
	public function getName ()
	{
		return $this->building->getName ();
	}
	
	public function getTileOffset ()
	{
		return $this->building->getTileOffset ();
	}
	
	public function getOnClick ()
	{
		$id = $this->building->getId ();
		return "openWindow ('building', {'bid':".$id."});";
	}
	
	public function getImageURL ()
	{
		return $this->building->getImageUrl ();
	}
	
	public function getMapStatus ()
	{
		return $this->building->getMapStatus ();
	}
	
	public function getImageName ()
	{
		$race = strtolower ($this->building->getVillage()->getRace()->getName());
		$sName = $this->building->getIsoImage ();
			
		// Check if file exists		
		if (!empty ($race) && file_exists (IMAGE_PATH.'sprites/png/'.$race.'_'.$sName.'.png'))
		{
			$id = $race.'_'.$sName;
		}
		else
		{
			$id = $sName;
		}
	
		return $id;
	}
}
?>
