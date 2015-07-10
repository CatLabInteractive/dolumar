<?php
class Dolumar_Windows_EffectReport extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('250px', '300px');
		$this->setTitle ($text->get ('effectreports', 'menu', 'main'));
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		//return false;
		$req = $this->getRequestData ();
		
		$reportid = isset ($req['id']) ? $req['id'] : false;
		
		$report = Dolumar_Report_Report::getFromId ($reportid);
		return $report->getOutput ();
	}
}
?>
