-- Browser (Web Push) notifications: subscribed browsers and the outbox the cron job sends.
-- Runs on every deploy (tools/setup.php), so it must stay safe to run more than once.

CREATE TABLE IF NOT EXISTS `n_push_subscriptions` (
  `ps_id` int(11) NOT NULL AUTO_INCREMENT,
  `ps_plid` int(11) NOT NULL,
  `ps_endpoint` varchar(700) CHARACTER SET ascii NOT NULL,
  `ps_p256dh` varchar(128) CHARACTER SET ascii NOT NULL,
  `ps_auth` varchar(64) CHARACTER SET ascii NOT NULL,
  `ps_useragent` varchar(255) NOT NULL DEFAULT '',
  `ps_created` datetime NOT NULL,
  `ps_lastsuccess` datetime DEFAULT NULL,
  PRIMARY KEY (`ps_id`),
  UNIQUE KEY `ps_endpoint` (`ps_endpoint`),
  KEY `ps_plid` (`ps_plid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `n_push_queue` (
  `pn_id` int(11) NOT NULL AUTO_INCREMENT,
  `pn_plid` int(11) NOT NULL,
  `pn_payload` text NOT NULL,
  `pn_created` datetime NOT NULL,
  PRIMARY KEY (`pn_id`),
  KEY `pn_plid` (`pn_plid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
