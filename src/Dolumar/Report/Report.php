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
class Dolumar_Report_Report implements Neuron_GameServer_Interfaces_Logable
{
	private $data = array ();
	private $id;
	private $village;
	private $target;
	private $date = NOW;
	
	private $type = 'Report';

	public function __construct ($village)
	{
		$this->village = $village;
		
		$this->type = substr (get_class ($this), strlen ('Dolumar_Report_'));
	}
	
	public function setTarget ($village)
	{
		$this->target = $village;
	}
	
	public function getId ()
	{
		return $this->id;
	}
	
	public function setId ($id)
	{
		$this->id = $id;
	}
	
	public static function getFromId ($id)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$id = intval ($id);
		
		$d = $db->query
		("
			SELECT
				*,
				UNIX_TIMESTAMP(er_date) AS datum
			FROM
				effect_report
			WHERE
				er_id = {$id}
		");
		
		if (count ($d) > 0)
		{
			$village = Dolumar_Registry_Village::getInstance ()->get ($d[0]['er_vid']);
			
			$classname = 'Dolumar_Report_' . $d[0]['er_type'];
			if (class_exists ($classname))
			{
				$report = new $classname ($village);
			}
			else
			{
				$report = new self ($village);
			}
			
			$report->setId ($id);
			
			foreach (self::getObjectsFromLog ($d[0]['er_data']) as $v)
			{
				$report->addItem ($v);
			}
			
			$report->setDate ($d[0]['datum']);
			
			if (isset ($d[0]['er_target_v_id']))
			{
				$report->setTarget (Dolumar_Registry_Village::getInstance ()->get ($d[0]['er_target_v_id']));
			}
			
			return $report;
		}
		
		return false;
	}
	
	private function setDate ($date)
	{
		$this->date = $date;
	}
	
	protected function addItem ($obj)
	{
		if (! ($obj instanceof Neuron_GameServer_Interfaces_Logable))
		{
			throw new Neuron_Core_Error 
				('Items in reports must implement Neuron_GameServer_Interfaces_Logable');
		}
		
		$this->data[] = $obj;
	}
	
	private function getLogFromObjects ()
	{
		return Dolumar_Players_Logs::getLogFromObjects ($this->data);
	}
	
	private static function getObjectsFromLog ($data)
	{
		return Dolumar_Players_Logs::getObjectsFromLog ($data);
	}
	
	public function store ()
	{
		if ($this->getId () > 0)
		{
			throw new Neuron_Core_Error ('Cannot call store method on loaded report.');
		}
	
		$db = Neuron_DB_Database::getInstance ();
		
		$data = $this->getLogFromObjects ();
		
		$target = isset ($this->target) ? $this->target->getId () : null;
		
		$this->id = $db->query
		("
			INSERT INTO
				effect_report
			SET
				er_vid = {$this->village->getId ()},
				er_target_v_id = {$target},
				er_type = '{$db->escape ($this->type)}',
				er_data = '{$db->escape ($data)}',
				er_date = FROM_UNIXTIME(".NOW.")
		");
	}
	
	public function getName ()
	{
		$text = Neuron_Core_Text::getInstance ();
		return $text->get ('report', 'general', 'effects');
	}
	
	public function getDisplayName ()
	{
		return '<a href="javascript:void(0);" onclick="openWindow(\'EffectReport\',{\'id\':'.$this->getId ().'});">'.
			$this->getName ().'</a>';
	}
	
	public function getLogArray ()
	{
		return array ();
	}
	
	public function getOutput ()
	{
		$page = new Neuron_Core_Template ();
		
		$page->set ('type', $this->type);
		$page->set ('date', date (DATETIME, $this->date));

		if (isset ($this->target))
		{
			$page->set ('target', $this->target->getDisplayName ());
		}
		
		foreach ($this->data as $v)
		{
			$page->addListValue
			(
				'records',
				$this->getRecordOutput ($v)
			);
		}
		
		$page->sortList ('records');
		
		return $page->parse ('dolumar/report/general.phpt');
	}
	
	protected function getRecordOutput ($v)
	{
		return (string)$v;
	}
	
	public function __toString ()
	{
		return $this->getOutput ();
	}
}
?>
