<?php
class Dolumar_Underworld_Windows_Finished 
	extends Neuron_GameServer_Windows_Window
{
	private $map;

	private $army;
	private $me;

	public function setSettings ()
	{
		$this->setSize ('250px', '250px');		
		$this->setAllowOnlyOnce ();
		$this->setCentered ();
	}
	
	public function getContent ()
	{
		return '<p>The mission is finished.</p>';
	}

}
