<?php
/*
	This container groups a bunch of resources / runes.
*/
class Dolumar_Logable_RuneContainer extends Dolumar_Logable_Container
{
	public static function getFromId ($id)
	{
		$res = self::getDataFromId ($id);
		return new self ($res);
	}
	
	public function getId ()
	{
		$data = '';
		foreach ($this->resources as $k => $v)
		{
			if ($v != 0)
			{
				$data .= $k.'='.$v.'&';
			}
		}
		return substr ($data, 0, -1);
	}
	
	public function getName ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		$data = $this->getLogArray ();
		
		$out = '';
		
		$last = count ($data);
		$i = 0;
		
		foreach ($data as $k => $v)
		{
			$out .= $v . ' ' . $text->get ($k, $v > 1 ? 'runeDouble' : 'runeSingle', 'main');
			
			if ($i < ($last - 2))
			{
				$out .= ', ';
			}
			elseif ($i == ($last - 2))
			{
				$out .= ' and ';
			}
			
			$i ++;
		}
		
		return $out;
	}
}
?>
