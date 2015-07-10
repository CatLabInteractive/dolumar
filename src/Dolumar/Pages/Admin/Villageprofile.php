<?php
class  Dolumar_Pages_Admin_Villageprofile extends Neuron_GameServer_Pages_Admin_Page
{
	public function getBody ()
	{
		$village = Neuron_Core_Tools::getInput ('_GET', 'village', 'int');
		$village = Dolumar_Players_Village::getVillage ($village);
		
		$body = '<h2>'.$village->getName ().'</h2>';
		$location = $village->buildings->getTownCenterLocation ();
		
		//$url = ABSOLUTE_URL . '#' . $location[0] . ',' . $location[1];
		$url = $this->getUrl ('Playerprofile', array ('plid' => $village->getOwner ()->getId ()));
		
		header ('Location: '.$url);
		
		return $body;
	}
}
?>
