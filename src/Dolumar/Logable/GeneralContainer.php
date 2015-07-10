<?php
class Dolumar_Logable_GeneralContainer implements Neuron_GameServer_Interfaces_Logable
{
	private $data = array ();

	public function __construct ()
	{
	
	}
	
	public function add (Neuron_GameServer_Interfaces_Logable $object)
	{
		$this->data[] = $object;
	}
	
	public static function getFromId ($id)
	{
		$data = Neuron_GameServer_LogSerializer::decode ($id);
		
		$out = new Dolumar_Logable_GeneralContainer ();
		
		foreach ($data as $v)
		{
			$out->add ($v);
		}
		
		return $out;
	}
	
	
	public function getName ()
	{
		return $this->getDisplayName ();
	}
	
	// Get the serialized object
	public function getId ()
	{
		return Neuron_GameServer_LogSerializer::encode ($this->data);
	}
	
	public function getLogArray ()
	{
		return $this->data;
	}
	
	public function getDisplayName ()
	{
		if (count ($this->data) == 0)
		{
			return "NOTHING";
		}
	
		$out = "";
		foreach ($this->data as $v)
		{
			$out .= $v->getDisplayName () . " & ";
		}
		return substr ($out, 0, -3);
	}
	
	public function __toString ()
	{
		return $this->getDisplayName ();
	}
}
?>
