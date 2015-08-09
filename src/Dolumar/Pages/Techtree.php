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

class Dolumar_Pages_Techtree extends Neuron_GameServer_Pages_Page
{
	public function getOutput ()
	{
		//echo get_include_path ();

		require_once 'Image/GraphViz.php';

		$race = Dolumar_Races_Race::getFromId (Neuron_Core_Tools::getInput ('_GET', 'race', 'int', 1));

		$default_settings = array
		(
			'fontsize' => 8
		);

		$default_node_settings = $default_settings;
		$default_node_settings['shape'] = 'box';

		$tech_atts = $default_node_settings;
		$tech_atts['bgcolor'] = 'ff0000';
		$tech_atts['color'] = 'blue';

		$equip_atts = $default_node_settings;
		$equip_atts['bgcolor'] = 'ff0000';
		$equip_atts['color'] = 'red';
		$equip_atts['rankdir'] = 'tb';
		$equip_atts['constraint'] = false;

		$unit_atts = $default_node_settings;
		$unit_atts['bgcolor'] = 'ff0000';
		$unit_atts['color'] = 'green';

		$arrow_atts = $default_settings;
		$arrow_atts['arrowType'] = 'normal';
		$arrow_atts['fontsize'] = '6';

		$grayarrow_atts = $arrow_atts;
		$grayarrow_atts['color'] = 'gray';

		define ('UNIT_PREFIX', '[U] ');
		define ('TECHNOLOGY_PREFIX', '[T] ');
		define ('BUILDING_PREFIX', '[B] ');
		define ('EQUIPMENT_PREFIX', '[E] ');

		$show_equipment = Neuron_Core_Tools::getInput ('_GET', 'equipment', 'int', 0) == 1;
		$show_technology = Neuron_Core_Tools::getInput ('_GET', 'technology', 'int', 0) == 1;
		$show_units = Neuron_Core_Tools::getInput ('_GET', 'units', 'int', 0) == 1;


		$gv = new Image_GraphViz(true, array ('label' => $race->getName (), 'labelloc' => 't'));

		// All buildings
		$gv->addCluster ("BUILDINGS", "Buildings");

		if ($show_technology)
		{
			$gv->addCluster ("TECHNOLOGY", "Technology");
		}

		//$gv->addCluster ("EQUIPMENT", "Equipment");

		if ($show_equipment)
		{
			$gv->addCluster ("weapon", "Weapons", array ('rotate' => '90'));
			$gv->addCluster ("armour", "Armour", array ());
		}

		if ($show_units)
		{
			$gv->addCluster ("UNITS", "Units");
		}

		$buildings = Dolumar_Buildings_Building::getBuildingObjects ($race);
		foreach ($buildings as $building)
		{
			$building->setVillage (new Dolumar_Players_DummyVillage ($race));
	
			//$building->setRace ($race);
	
			// Add building
			$gv->addNode (BUILDING_PREFIX . $building->getName (), $default_node_settings, "BUILDINGS");
	
			// Add building requirements
			foreach ($building->getRequiredBuildings () as $req)
			{
				$label = $req['amount'] . '+';
				$gv->addEdge (array (BUILDING_PREFIX . $req['building']->getName () => BUILDING_PREFIX . $building->getName ()), array_merge ($arrow_atts, array ('label' => $label)));
			}
	
			// Technologies
			if ($show_technology)
			{
				foreach ($building->getTechnologies () as $tech)
				{
					$label = 'Level ' . $tech->getMinLevel ();
			
					$gv->addNode (TECHNOLOGY_PREFIX . $tech->getName (), $tech_atts, "TECHNOLOGY");
					$gv->addEdge (array (BUILDING_PREFIX . $building->getName () => TECHNOLOGY_PREFIX . $tech->getName ()), array_merge ($arrow_atts, array ('label' => $label)));
			
					// requirements for the technologies?
					foreach ($tech->getRequiredTechnologies () as $req)
					{
							$label = null;
							$gv->addEdge (array (TECHNOLOGY_PREFIX . $req->getName () => TECHNOLOGY_PREFIX . $tech->getName ()), array_merge ($arrow_atts, array ('label' => $label)));
					}
				}
			}
	
			// Equipment
			if ($building instanceof Dolumar_Buildings_Crafting && $show_equipment)
			{
				foreach ($building->getEquipment () as $equip)
				{
					//$gv->addNode (EQUIPMENT_PREFIX . $equip->getName (), $equip_atts, "EQUIPMENT");
					$gv->addNode (EQUIPMENT_PREFIX . $equip->getName (), $equip_atts, $equip->getItemType ());
			
					// Arrow to this building
					$label = $equip->getRequiredLevel () > 0 ? 'Level ' . $equip->getRequiredLevel () : null;
					//$gv->addEdge (array (BUILDING_PREFIX . $building->getName () => EQUIPMENT_PREFIX . $equip->getName ()), array_merge ($arrow_atts, array ('label' => $label)));
					$gv->addEdge (array (BUILDING_PREFIX . $building->getName () => EQUIPMENT_PREFIX . $equip->getName ()), array_merge ($grayarrow_atts, array ('label' => $label, 'ltail' => 'EQUIPMENT')));
			
					// Required technologies?
					foreach ($equip->getRequiredTechnologies () as $tech)
					{
						$label = null;
						$gv->addEdge (array (TECHNOLOGY_PREFIX . $tech->getName () => EQUIPMENT_PREFIX . $equip->getName ()), array_merge ($grayarrow_atts, array ('label' => $label)));
					}
				}
			}
	
			// Units
			if ($building instanceof Dolumar_Buildings_Training && $show_units)
			{
				foreach ($building->getUnits () as $unit)
				{
					// Units!
					$gv->addNode (UNIT_PREFIX . $unit->getName (), $unit_atts, "UNITS");
			
					// Arrow to this building
					$label = null;
					$gv->addEdge (array (BUILDING_PREFIX . $building->getName () => UNIT_PREFIX . $unit->getName ()), array_merge ($grayarrow_atts, array ('label' => $label)));
			
					// Required technologies?
					foreach ($unit->getRequiredTechnologies () as $tech)
					{
						$label = null;
						$gv->addEdge (array (TECHNOLOGY_PREFIX . $tech->getName () => UNIT_PREFIX . $unit->getName ()), array_merge ($grayarrow_atts, array ('label' => $label)));
					}
				}
			}
		}

		// All equipment
		/*
		$gv->addCluster ("EQUIPMENT", "Equipment");
		$eqs = Dolumar_Players_Equipment::getAllEquipment();
		foreach ($eqs as $building)
		{
			$gv->addNode ($building->getName (), $equip_atts, "EQUIPMENT");
	
			// Required technologies?
			foreach ($building->getRequiredTechnologies () as $tech)
			{
				$label = null;
				$gv->addEdge (array ($tech->getName () => $building->getName ()), array_merge ($arrow_atts, array ('label' => $label)));
			}
		}
		*/

		// All units
		$output = Neuron_Core_Tools::getInput ('_GET', 'engine', 'varchar');
		switch ($output)
		{
			case "dot":
			case "neato":
	
			break;
	
			default:
				$output = "dot";
			break;
		}

		if (!$gv->image("png", $output))
		{
			echo "Error... Is Graphviz installed?";
		}
	
	}
}
?>
