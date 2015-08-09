<?php
/**
 *  Dolumar, browser based strategy game
 *  Copyright (C) 2009 Thijs Van der Schaeghe
 *  CatLab Interactive bvba, Gent, Belgium
 *  http://www.catlab.eu/
 *  http://www.dolumar.com/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

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
