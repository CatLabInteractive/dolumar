-- NPC tribes: per-village tribe data, hidden standing per player and a decision log.
-- Runs on every deploy (tools/setup.php), so it must stay safe to run more than once.

CREATE TABLE IF NOT EXISTS `npc_villages` (
  `vid` int(11) NOT NULL,
  `tribe_name` varchar(60) NOT NULL DEFAULT '',
  `mul_threshold` float NOT NULL DEFAULT '1',
  `mul_decay` float NOT NULL DEFAULT '1',
  `mul_hoard` float NOT NULL DEFAULT '1',
  `last_report_id` int(11) NOT NULL DEFAULT '0',
  `last_transfer_id` int(11) NOT NULL DEFAULT '0',
  `request_res` varchar(10) NOT NULL DEFAULT '',
  `request_amount` int(11) NOT NULL DEFAULT '0',
  `request_created` int(11) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL DEFAULT '0',
  `last_run` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `npc_standings` (
  `vid` int(11) NOT NULL,
  `plid` int(11) NOT NULL,
  `standing` float NOT NULL DEFAULT '0',
  `changed_at` int(11) NOT NULL DEFAULT '0',
  `attacks_window_start` int(11) NOT NULL DEFAULT '0',
  `attacks_in_window` int(11) NOT NULL DEFAULT '0',
  `last_attack_at` int(11) NOT NULL DEFAULT '0',
  `last_attack_vid` int(11) NOT NULL DEFAULT '0',
  `last_penalty` float NOT NULL DEFAULT '0',
  `scouts_at` int(11) NOT NULL DEFAULT '0',
  `envoy_at` int(11) NOT NULL DEFAULT '0',
  `envoy_price` int(11) NOT NULL DEFAULT '0',
  `war_parties_sent` int(11) NOT NULL DEFAULT '0',
  `npc_attacks_day` int(11) NOT NULL DEFAULT '0',
  `npc_attacks_today` int(11) NOT NULL DEFAULT '0',
  `last_npc_attack_at` int(11) NOT NULL DEFAULT '0',
  `last_npc_battle_id` int(11) NOT NULL DEFAULT '0',
  `truce_until` int(11) NOT NULL DEFAULT '0',
  `request_delivered_at` int(11) NOT NULL DEFAULT '0',
  `gift_window_start` int(11) NOT NULL DEFAULT '0',
  `gift_best` int(11) NOT NULL DEFAULT '0',
  `support_squad` int(11) NOT NULL DEFAULT '0',
  `support_until` int(11) NOT NULL DEFAULT '0',
  `pact` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vid`,`plid`),
  KEY `plid` (`plid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `npc_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL,
  `plid` int(11) DEFAULT NULL,
  `date` int(11) NOT NULL DEFAULT '0',
  `message` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `vid` (`vid`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
