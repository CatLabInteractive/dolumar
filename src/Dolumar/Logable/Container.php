<?php
/*
	This container groups a bunch of resources / runes.
*/
class Dolumar_Logable_Container implements Neuron_GameServer_Interfaces_Logable
{
	public static function getFromId ($id)
	{
		$res = self::getDataFromId ($id);
		return new self ($res);
	}
	
	protected static function getDataFromId ($id)
	{
		$data = explode ('&', $id);
		$res = array ();
		foreach ($data as $v)
		{
			$d = explode ('=', $v);
			if (count ($d) == 2)
			{
				$res[$d[0]] = $d[1];
			}
		}
		return $res;
	}
	
	protected $resources;
	
	public function __construct ($resources = array ())
	{
		if (!is_array ($resources))
		{
			$resources = array ($resources);
		}
		
		$this->resources = $resources;
	}
	
	public function getDisplayName ()
	{
		return $this->getName ();
	}
	
	public function getName ()
	{
		$out = '';
		foreach ($this->resources as $k => $v)
		{
			$out .= $v . ' '.$k.', ';
		}
		return substr ($out, 0, -2);
	}
	
	public function getId ()
	{
		$data = '';
		foreach ($this->resources as $k => $v)
		{
			$data .= $k.'='.$v.'&';
		}
		return substr ($data, 0, -1);
	}
	
	public function getLogArray ()
	{
		return $this->resources;
	}
	
	public function getSum ()
	{
		$sum = 0;
		foreach ($this->resources as $v)
		{
			$sum += $v;
		}
		return $sum;
	}
	
	public function __toString ()
	{
		return $this->getDisplayName ();
	}
}
?>
