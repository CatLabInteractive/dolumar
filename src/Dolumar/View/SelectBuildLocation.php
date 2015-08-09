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
