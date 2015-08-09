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
