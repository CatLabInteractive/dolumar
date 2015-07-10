<?php
class Dolumar_Effects_Boost_Invisibility extends Dolumar_Effects_Boost
{
	protected $sType = 'magic';
	protected $iDuration = 43200;
	protected $hidden = 3600;
	
	public function procBattleVisible ($battle) 
	{
		return ($battle->getFightDate () - $this->hidden) > NOW ; 
	}
	
	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'hidden' => ($this->hidden / 60)
			)
		);
	}
	
	protected function getCostFromLevel ()
	{
		return 20;
	}
	
	public function getDifficulty ($iBaseAmount = 40)
	{
		return 80;
	}
}
?>
