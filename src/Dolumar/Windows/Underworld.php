<?php
class Dolumar_Windows_Underworld extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('600px', '500px');
		$this->setTitle ('Underworld');

		$this->setClass ('small-border no-overflow');
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		$data = $this->getRequestData ();
		
		$id = isset ($data['id']) ? $data['id'] : 0;

		$mission = Dolumar_Underworld_Mappers_MissionMapper::getFromId ($id);

		if ($mission)
		{
			return '<iframe src="'.$mission->getUrl ().'" style="width: 100%; height: 100%; border: 0px none black;" border="0"></iframe>';
		}
		else
		{
			return '<p>Mission not found.</p>';
		}
	}
	
	public function reloadContent ()
	{
	
	}
}
?>
