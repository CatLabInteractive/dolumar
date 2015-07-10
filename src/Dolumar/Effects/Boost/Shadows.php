<?php
class Dolumar_Effects_Boost_Shadows extends Dolumar_Effects_Boost
{
	protected $sType = 'magic';

	protected function getBonusFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
			default:
				return 40;
			break;
			
			case 2:
				return 45;
			break;
			
			case 3:
				return 50;
			break;
			
			case 4:
				return 55;
			break;
			
			case 5:
				return 60;
			break;
		}
	}
	
	protected function getCostFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
				return 5.0;
			break;
			
			case 2:
				return 5.5;			
			break;
			
			case 3:
				return 6.2;
			break;
			
			case 4:
				return 7.0;
			break;
			
			case 5:
				return 8;
			break;
		}
	}

	public function procEffectDifficulty ($difficulty, $effect) 
	{
		if ($effect->getEffectType () == 'thievery')
		{
			$difficulty -= ($difficulty / 100) * $this->getBonusFromLevel ();
		}
		
		return $difficulty;
	}
	
	public function getDescription ($data = array ())
	{
		return parent::getDescription
		(
			array
			(
				'bonus' => $this->getBonusFromLevel ()
			)
		);
	}
}
?>
