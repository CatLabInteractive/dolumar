<?php
class Dolumar_Effects_Boost_Demoralize extends Dolumar_Effects_Boost
{
	protected $sType = 'thievery';
	protected $iDuration = 21600;

	protected function getBonusFromLevel ()
	{
		//return 10 + $this->getLevel () * 15;
		
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 5;
			break;
			
			case 2:
				return 6;
			break;
			
			case 3:
				return 7;
			break;
			
			case 4:
				return 8;
			break;
			
			case 5:
				return 9;
			break;
		}
	}

	public function procMoraleCheck ($morale, $fight) 
	{
		$morale -= ($morale / 100) * $this->getBonusFromLevel ();
		
		return $morale;
	}
	
	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'penalty' => $this->getBonusFromLevel ()
			)
		);
	}
}
?>
