<?php
class Dolumar_View_SelectBuildLocation
	extends Neuron_GameServer_View_SelectLocationAction
{
	private $data;
	private $pointer;
	private $sendformdata = false;
	private $value;
	private $runeclassname;
	private $extradata = "''";
	private $error;

	public function __construct 
	(
		$data, 
		$value, 
		$runeclassname, 
		Neuron_GameServer_Map_Display_Sprite $pointer, 
		$extradata = null,
		$errormessage = 'Please do not do that.')
	{
		$this->data = $data;
		$this->pointer = $pointer;
	
		parent::__construct ($data, $value, $pointer);
		$this->runeclassname = "'" . $runeclassname . "'";
		
		if (isset ($extradata))
			$this->extradata = $extradata;
		
		$this->error = $errormessage;
	}
	
	public function setSendFormData ()
	{
		$this->sendformdata = true;
	}
	
	public function getAction ()
	{	
		$img = $this->pointer;
	
		$data = htmlentities (json_encode ($this->data), ENT_COMPAT);

		$image = '{}';
		if ($this->pointer)
			$image = htmlentities (json_encode ($this->pointer->getDisplayData ()), ENT_COMPAT);
		
		$sendformdata = $this->sendformdata ? 'true' : 'false';
	
		return 'selectBuildLocation (this, '.$data.', '.$this->runeclassname.', '.$image.', '.$this->extradata.', \'' . $this->error . '\');';
	}
}
?>
