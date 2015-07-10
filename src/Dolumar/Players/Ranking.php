<?php
class Dolumar_Players_Ranking
{
	
	public static function getRanking ($startPoint = 0, $length = 10)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$l = $db->select
		(
			'villages',
			array ('*'),
			"isActive = 1",
			"networth desc, vid desc",
			"$startPoint, $length"
		);
		
		$o = array ();
		foreach ($l as $v)
		{
			$village = Dolumar_Players_Village::getVillage ($v['vid'], false, false, true);
			$village->setData ($v);
			$o[] = $village;
		}
		
		return $o;
	}
	
	public static function getPlayerRanking ($startPoint = 0, $length = 10)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$data = $db->query
		("
			SELECT
				n_players.*
			FROM
				n_players
			WHERE
				isPlaying = 1
			ORDER BY
				p_score DESC,
				plid ASC
			LIMIT 
				$startPoint, $length
		");
		
		$o = array ();
		foreach ($data as $v)
		{
			$player = Neuron_GameServer::getPlayer ($v['plid']);
			$player->setData ($v);
			$o[] = $player;
		}
		
		return $o;
	}
	
	public static function countPlayerRanking ($startPoint = 0, $length = 10)
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$data = $db->query
		("
			SELECT
				COUNT(plid) AS aantal
			FROM
				n_players
			WHERE
				isPlaying = 1
			ORDER BY
				p_score DESC,
				plid ASC
		");
		
		return $data[0]['aantal'];
	}
	
	public static function getClanRanking ($startPoint = 0, $length = 10)
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$l = $db->select
		(
			'clans',
			array ('*'),
			false,
			"c_score DESC",
			"$startPoint, $length"
		);
		
		$o = array ();
		foreach ($l as $v)
		{
			$o[] = new Dolumar_Players_Clan ($v['c_id'], $v);
		}
		
		return $o;
	}

	public static function countClanRanking ()
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$count = $db->query
		("
			SELECT
				COUNT(*) as aantal
			FROM
				clans
		");
		
		return $count[0]['aantal'];
	}

	public static function countRanking ()
	{		
		$db = Neuron_DB_Database::getInstance ();
		
		$count = $db->query
		("
			SELECT
				COUNT(*) as aantal
			FROM
				villages
			WHERE
				isActive = 1
		");
		
		return $count[0]['aantal'];
	}

	public static function getRankingFromOpenAuth ($authType, $authUIDs = array (), $startPoint = 0, $length = 9)
	{
		$db = Neuron_Core_Database::__getInstance ();
		if (!is_array ($authUIDs))
		{
			return array ();
		}
		else
		{

			$where = "p.authType = '".$db->escape ($authType)."' AND (FALSE";

			// Actual where values:
			foreach ($authUIDs as $v)
			{
				$where .= " OR authUID = '".$db->escape ($v)."'";
			}

			$where .= ')';

			$query = 
			("
				SELECT
					v.*
				FROM
					n_players p
				RIGHT JOIN
					villages v ON p.plid = v.plid
				WHERE
					$where
				ORDER BY
					v.networth DESC, v.vid DESC
				LIMIT $startPoint, $length
			");
			
			$l = $db->getDataFromQuery ($db->customQuery ($query));

			
			
			$o = array ();
			foreach ($l as $v)
			{
				$village = Dolumar_Players_Village::getVillage ($v['vid'], false);
				$village->setData ($v);
				$o[] = $village;
			}

			//customMail ('daedelson@gmail.com', 'test', print_r ($o, true));
			
			return $o;
		}
	}

	public static function countRankingFromOpenAuth ($authType, $authUIDs = array ())
	{
		$db = Neuron_Core_Database::__getInstance ();
		if (!is_array ($authUIDs))
		{
			return 0;
		}
		else
		{
			$where = "p.authType = '".$db->escape ($authType)."' AND (FALSE";

			// Actual where values:
			foreach ($authUIDs as $v)
			{
				$where .= " OR authUID = '".$db->escape ($v)."'";
			}

			$where .= ')';

			$query = 
			("
				SELECT
					count(v.vid) AS aantal
				FROM
					n_players p
				RIGHT JOIN
					villages v ON p.plid = v.plid
				WHERE
					$where
			");

			$l = $db->getDataFromQuery ($db->customQuery ($query));

			if (count ($l) == 1)
			{
				return $l[0]['aantal'];
			}
			else
			{
				return 0;
			}
		}
	}
}
?>
