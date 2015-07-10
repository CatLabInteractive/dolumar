<?php
class Dolumar_Buildings_BonusBuilding extends Dolumar_Buildings_Building
{
	private $data;
	private $bonusid;

	private function getData ()
	{
		if (!isset ($this->data))
		{
			$db = Neuron_DB_Database::getInstance ();
		
			if (isset ($this->bonusid))
			{
				$data = $db->query
				("
					SELECT
						*
					FROM
						players_tiles
					WHERE
						t_id = {$this->bonusid}
				");
			}
			else
			{
				$data = $db->query
				("
					SELECT
						p.*
					FROM
						bonus_buildings b
					LEFT JOIN
						players_tiles p ON b.b_player_tile = p.t_id
					WHERE
						b.b_id = {$this->getId ()}
				");
			}
			
			if (count ($data) > 0)
			{
				$this->data = $data[0];
			}
			
		}
		
		return $this->data;
	}
	
	public function setBonusBuildingId ($id)
	{
		$this->bonusid = intval ($id);
	}

	public function getImage ($race = false)
	{
		return md5 ($this->getImageUrl ($race));
	}

	public function getImageUrl ($race = false)
	{
		$data = $this->getData ();
		
		if ($data)
		{
			return PUBLIC_URL.$data['t_imagename'];
		}
		else
		{
			return PUBLIC_URL.'tsprites/sign1.png';
		}
	}
	
	public function getImagePath ()
	{
		$data = $this->getData ();
		
		if ($data)
		{
			return PUBLIC_PATH.$data['t_imagename'];
		}
		else
		{
			return PUBLIC_PATH.'tsprites/sign1.png';
		}
	}
	
	public function getCustomContent ($input)
	{
		$data = $this->getData ();
		
		if (!$data)
		{
			return null;
		}
		
		$content = json_decode ($data['t_description'], true);
		
		return Neuron_Core_Tools::output_text ($this->getTranslatedContent ($content, 'description'));
	}
	
	public function getName ()
	{
		$data = $this->getData ();
		
		if (!$data)
		{
			return "Bonus building";
		}
		
		$content = json_decode ($data['t_description'], true);
		$name = $this->getTranslatedContent ($content, 'title');
		
		if (!empty ($name))
		{
			return $name;
		}
		
		else
		{
			return parent::getName ();
		}
	}
	
	private function getTranslatedContent ($content, $tkey)
	{
		$text = Neuron_Core_Text::getInstance ();
		$key = $text->getCurrentLanguage ();
		$dkey = 'en';
		
		if (isset ($content[$key]) && !empty ($content[$key][$tkey]))
		{
			return $content[$key][$tkey];
		}
		
		elseif (isset ($content[$dkey]) && !empty ($content[$dkey][$tkey]))
		{
			return $content[$dkey][$tkey];
		}
		
		return null;
	}
	
	public function build ($village, $x, $y, $tile = true)
	{
		$building = parent::build ($village, $x, $y, false);
		
		$db = Neuron_DB_Database::getInstance ();
		
		$tile = intval ($tile);
		
		$db->query
		("
			INSERT INTO
				bonus_buildings
			SET
				b_id = {$building->getId ()},
				b_player_tile = {$tile}
		");
		
		return $building;
	}
	
	public function isUpgradeable ()
	{
		return false;
	}
	
	public function getUsedRunes ($includeUpgradeRunes = false, $oldSystem = false)
	{
		return array ();
	}
	
	public function getUsedResources ($includeUpgradeRunes = false, $oldSystem = false)
	{
		return array ();
	}
	
	public function canBuildBuilding (Dolumar_Players_Village $village)
	{
		return false;
	}
}