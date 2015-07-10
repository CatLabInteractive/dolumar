<?php
class Dolumar_Underworld_Map_Spawngroup
{
	private $requirements = array ();
	private $name;
	private $id;

	public function __construct ($id)
	{
		$this->id = $id;
	}

	public function getId ()
	{
		return $this->id;
	}

	public function setName ($name)
	{
		$this->name = $name;
	}

	public function getName ()
	{
		return $this->name;
	}

	public function getRequirements ()
	{
		return $this->requirements;
	}

	public function addRequirement ($req)
	{
		$this->requirements[] = $req;
	}
}
?>
