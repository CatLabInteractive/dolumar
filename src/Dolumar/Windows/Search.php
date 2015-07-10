<?php
class Dolumar_Windows_Search extends Neuron_GameServer_Windows_Window
{
	private $perpage = 15;

	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('400px', '400px');
		$this->setTitle ($text->get ('search', 'menu', 'main'));
		
		$this->setAllowOnlyOnce ();
	}
	
	public function setPerPages ($pages = 15)
	{
		$this->perpage = $pages;
	}
	
	public function getContent ()
	{
		if ($result = $this->getResults ())
		{
			return $result;
		}
	
		$page = new Neuron_Core_Template ();
		
		// Fetch me villages
		$premium = false;
		
		$player = Neuron_GameServer::getPlayer ();
		if ($player)
		{
			foreach ($player->getVillages () as $v)
			{
				$loc = $v->buildings->getTownCenterLocation ();
			
				$page->addListValue
				(
					'villages',
					array
					(
						'location' => $loc[0].','.$loc[1],
						'name' => Neuron_Core_Tools::output_varchar ($v->getName ())
					)
				);
				
				$page->set ('premium', $player->isPremium ());
			}
		}
		
		$page->set ('premium', $premium);
		
		return $page->parse ('search/search.phpt');
	}
	
	// Check if there is a query
	private function getResults ()
	{
		$input = $this->getInputData ();
		
		if (isset ($input['search']) && $input['search'] == 'result')
		{
			$db = Neuron_DB_Database::getInstance ();
		
			$count = $this->getCountQuery ();
			
			if (empty ($count))
			{
				$text = Neuron_Core_Text::getInstance ();
				$this->alert ($text->get ('notEnoughFields', 'form', 'search'));
				return false;
			}
			
			$amount = $db->query ($count);
			$total = $amount[0]['amount'];
			
			$page = new Neuron_Core_Template ();
			
			$limit = Neuron_Core_Tools::splitInPages 
			(
				$page, 
				$total, 
				isset ($input['page']) ? $input['page'] : 0, 
				$this->perpage, 
				10,
				$input,
				'searchplayers'
			);
			
			$query = $this->getSearchQuery ($limit);
			
			$this->printResults ($page, $db->query ($query));
			
			return $page->parse ('search/results.phpt');
		}
		
		return false;
	}
	
	private function printResults ($page, $query)
	{
		foreach ($query as $v)
		{
			$displayname = Neuron_URLBuilder::getInstance ()->getOpenUrl 
			(
				'PlayerProfile', 
				Neuron_Core_Tools::output_varchar ($v['nickname']),
				array
				(
					'plid' => $v['plid']
				)
			);
			
			$villagename = Neuron_URLBuilder::getInstance ()->getOpenUrl 
			(
				'VillageProfile', 
				Neuron_Core_Tools::output_varchar ($v['vname']),
				array
				(
					'village' => $v['vid']
				)
			);
		
			$page->addListValue
			(
				'results',
				array
				(
					'id' => $v['plid'],
					'nickname' => $v['nickname'],
					'displayname' => $displayname,
					'village' => $v['vname'],
					'displayvillage' => $villagename,
					'distance' => Neuron_Core_Tools::output_distance ($v['distance']),
					'networth' => $v['networth']
				)
			);
		}
	}
	
	private function getSearchQuery ($limit)
	{
		return $this->buildQuery
		(
			"SELECT *",
			true,
			$limit
		);
	}
	
	private function getCountQuery ()
	{
		return $this->buildQuery
		(
			"SELECT COUNT(*) AS amount",
			false
		);
	}
	
	private function buildQuery ($select, $distance = false, $limit = null)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$hasFilter = false;
	
		$input = $this->getInputData ();
		
		$p1 = $select;
		
		$p2 = "
			FROM
				n_players p
			LEFT JOIN
				villages v ON p.plid = v.plid 
		";
		
		$sql = "WHERE p.isPlaying = 1 AND p.isRemoved = 0 AND v.isActive = '1' AND ";
		$order = "";
		
		$hasLocation = false;

		// Distance		
		if (!empty ($input['search_ankerpoint']))
		{
			$location = explode (',', $input['search_ankerpoint']);
			if (count ($location) == 2)
			{
				$x = intval ($location[0]);
				$y = intval ($location[1]);
			
				if ($distance)
				{
					$p1 .= ", SQRT(POWER(b.xas - $x, 2)+POWER(b.yas - $y, 2)) AS distance ";
				}
			
				$p2 .= "LEFT JOIN map_buildings b ON v.vid = b.village AND (b.buildingType = 1 OR b.buildingType = 3) ";
			
				$hasLocation = true;
			}
		}
		else
		{
			$p1 .= ", 0 AS distance ";
		}
		
		if (!empty ($input['search_name']))
		{
			$sql .= "p.nickname LIKE '%{$db->escape ($input['search_name'])}%' AND ";
			$hasFilter = true;
		}
		
		if (!empty ($input['search_village']))
		{
			$sql .= "v.vname LIKE '%{$db->escape ($input['search_village'])}%' AND ";
			$hasFilter = true;
		}
		
		if (!empty ($input['search_race']))
		{
			$sql .= "v.race = '{$db->escape ($input['search_race'])}' AND ";
			$hasFilter = true;
		}
		
		if (!empty ($input['search_online']))
		{
			$sql .= "p.lastRefresh > FROM_UNIXTIME(".( time() - intval ($input['search_online']) ).") AND ";
			$hasFilter = true;
		}
		
		// Distances.. pain in the ass
		if (
			(!empty ($input['search_distance_min']) || 
				!empty ($input['search_distance_max'])
			) && !empty ($input['search_ankerpoint'])
			&& $hasLocation
		)
		{						
			if (!empty ($input['search_distance_min']))
			{
				$d = floatval ($input['search_distance_min']);
				$d = Dolumar_Map_Map::league2tile ($d);
				
				$sql .= "SQRT(POWER(b.xas - $x, 2)+POWER(b.yas - $y, 2)) >= ".$d." AND ";
			}
				
			if (!empty ($input['search_distance_max']))
			{
				$d = floatval ($input['search_distance_max']);
				$d = Dolumar_Map_Map::league2tile ($d);
				
				$sql .= "SQRT(POWER(b.xas - $x, 2)+POWER(b.yas - $y, 2)) <= ".$d." AND ";
			}
			
			$hasFilter = true;
		}
		
		// Networth
		if (!empty ($input['search_networth_min']))
		{
			$sql .= "v.networth >= ".( intval($input['search_networth_min']) )." AND ";
			$hasFilter = true;
		}
		
		if (!empty ($input['search_networth_max']))
		{
			$sql .= "v.networth <= ".( intval($input['search_networth_max']) )." AND ";
			$hasFilter = true;
		}
		
		// Order
		$order = "p.nickname";
		
		if (!empty ($input['search_order']))
		{
			switch ($input['search_order'])
			{				
				case 'villagename':
					$order = "v.vname";
				break;
				
				case 'lastonline':
					$order = "p.lastRefresh";
				break;
				
				case 'distance':
					if ($hasLocation && $distance)
					{
						$order = 'distance';
					}
				break;
				
				case 'nickname':
				default:
					$order = "p.nickname";
				break;
			}
		}
		
		if (!empty ($input['search_order_dir']))
		{
			switch (strtolower ($input['search_order_dir']))
			{
				case 'asc':
					$order .= ' ASC';
				break;
				
				case 'desc':
					$order .= ' DESC';
				break;
			}
		}
		
		$sql = substr ($sql, 0, -4);
		
		$sLimit = "";
		if (isset ($limit) && is_array ($limit))
		{
			$sLimit = 'LIMIT '.$limit['limit'];
		}
		
		//customMail ('daedelson@gmail.com', 'bla', $p1 . $p2 .  $sql . 'ORDER BY ' . $order . ' ' . $sLimit);
		
		if ($hasFilter)
		{
			return $p1 . $p2 .  $sql . 'ORDER BY ' . $order . ' ' . $sLimit;
		}
		else
		{
			return false;
		}
	}
}
?>
