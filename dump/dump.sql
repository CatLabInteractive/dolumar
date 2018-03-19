-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2
-- http://www.phpmyadmin.net
--
-- Machine: localhost
-- Genereertijd: 14 sep 2013 om 17:36
-- Serverversie: 5.5.31
-- PHP-Versie: 5.4.4-14+deb7u4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Databank: `k000171_1_int1`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `battle`
--

CREATE TABLE IF NOT EXISTS `battle` (
  `battleId` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL DEFAULT '0',
  `targetId` int(11) NOT NULL DEFAULT '0',
  `startDate` int(11) NOT NULL DEFAULT '0',
  `arriveDate` int(11) NOT NULL,
  `fightDate` int(11) NOT NULL DEFAULT '0',
  `endFightDate` int(11) NOT NULL,
  `endDate` int(11) NOT NULL DEFAULT '0',
  `goHomeDuration` int(11) NOT NULL,
  `attackType` enum('attack') NOT NULL DEFAULT 'attack',
  `isFought` tinyint(1) NOT NULL DEFAULT '0',
  `bLogId` int(11) NOT NULL,
  `iHonourLose` int(11) DEFAULT NULL,
  `iBattleSlots` int(11) NOT NULL,
  PRIMARY KEY (`battleId`),
  KEY `vid` (`vid`),
  KEY `targetId` (`targetId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=33 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `battle_report`
--

CREATE TABLE IF NOT EXISTS `battle_report` (
  `reportId` int(11) NOT NULL AUTO_INCREMENT,
  `battleId` int(11) NOT NULL DEFAULT '0',
  `fightDate` int(11) NOT NULL DEFAULT '0',
  `fightDuration` int(11) NOT NULL,
  `fromId` int(11) NOT NULL DEFAULT '0',
  `targetId` int(11) NOT NULL DEFAULT '0',
  `squads` text NOT NULL,
  `slots` text NOT NULL,
  `fightLog` text NOT NULL,
  `battleLog` text NOT NULL,
  `resultLog` text NOT NULL,
  `victory` float NOT NULL DEFAULT '0',
  `execDate` datetime NOT NULL,
  `specialUnits` text,
  PRIMARY KEY (`reportId`),
  KEY `battleId` (`battleId`),
  KEY `fromId` (`fromId`),
  KEY `targetId` (`targetId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=31 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `battle_specialunits`
--

CREATE TABLE IF NOT EXISTS `battle_specialunits` (
  `bsu_id` int(11) NOT NULL AUTO_INCREMENT,
  `bsu_bid` int(11) NOT NULL,
  `bsu_vsu_id` int(11) NOT NULL,
  `bsu_ba_id` varchar(10) NOT NULL,
  `bsu_vid` int(11) NOT NULL,
  PRIMARY KEY (`bsu_id`),
  KEY `bsu_bid` (`bsu_bid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `battle_squads`
--

CREATE TABLE IF NOT EXISTS `battle_squads` (
  `bs_id` int(11) NOT NULL AUTO_INCREMENT,
  `bs_bid` int(11) NOT NULL,
  `bs_squadId` int(11) NOT NULL,
  `bs_unitId` int(11) NOT NULL,
  `bs_vid` int(11) NOT NULL,
  `bs_slot` tinyint(4) NOT NULL,
  PRIMARY KEY (`bs_id`),
  UNIQUE KEY `bs_bid` (`bs_bid`,`bs_squadId`,`bs_unitId`),
  KEY `bs_bid_2` (`bs_bid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=83 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `bonus_buildings`
--

CREATE TABLE IF NOT EXISTS `bonus_buildings` (
  `b_id` int(11) NOT NULL,
  `b_player_tile` int(11) NOT NULL,
  PRIMARY KEY (`b_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `boosts`
--

CREATE TABLE IF NOT EXISTS `boosts` (
  `b_id` int(11) NOT NULL AUTO_INCREMENT,
  `b_targetId` int(11) NOT NULL,
  `b_fromId` int(11) NOT NULL,
  `b_type` enum('spell') NOT NULL,
  `b_ba_id` varchar(10) NOT NULL,
  `b_start` int(11) NOT NULL,
  `b_end` int(11) NOT NULL,
  `b_secret` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`b_id`),
  KEY `b_targetId` (`b_targetId`),
  KEY `b_fromId` (`b_fromId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=375 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `clans`
--

CREATE TABLE IF NOT EXISTS `clans` (
  `c_id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(20) NOT NULL,
  `c_description` text NOT NULL,
  `c_password` varchar(32) DEFAULT NULL,
  `c_score` int(11) NOT NULL,
  `c_isFull` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`c_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `clan_members`
--

CREATE TABLE IF NOT EXISTS `clan_members` (
  `cm_id` int(11) NOT NULL AUTO_INCREMENT,
  `plid` int(11) NOT NULL,
  `c_id` int(11) NOT NULL,
  `c_status` enum('member','captain','leader') NOT NULL,
  `cm_active` enum('1','0') NOT NULL,
  PRIMARY KEY (`cm_id`),
  KEY `plid` (`plid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=124 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `effects`
--

CREATE TABLE IF NOT EXISTS `effects` (
  `e_id` int(11) NOT NULL AUTO_INCREMENT,
  `e_name` varchar(40) NOT NULL,
  PRIMARY KEY (`e_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=34 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `effect_report`
--

CREATE TABLE IF NOT EXISTS `effect_report` (
  `er_id` int(11) NOT NULL AUTO_INCREMENT,
  `er_vid` int(11) NOT NULL,
  `er_target_v_id` int(11) DEFAULT NULL,
  `er_type` varchar(20) NOT NULL,
  `er_date` datetime NOT NULL,
  `er_data` text NOT NULL,
  PRIMARY KEY (`er_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `equipment`
--

CREATE TABLE IF NOT EXISTS `equipment` (
  `e_id` int(11) NOT NULL AUTO_INCREMENT,
  `e_name` varchar(20) NOT NULL,
  PRIMARY KEY (`e_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `forum_bans`
--

CREATE TABLE IF NOT EXISTS `forum_bans` (
  `ID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `user` tinytext NOT NULL,
  `forumID` tinytext NOT NULL,
  `time` int(11) NOT NULL,
  `reason` tinytext NOT NULL,
  `by` smallint(6) NOT NULL,
  KEY `ID` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `forum_boards`
--

CREATE TABLE IF NOT EXISTS `forum_boards` (
  `ID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `forum_id` tinytext NOT NULL,
  `order` tinyint(4) NOT NULL,
  `title` text NOT NULL,
  `desc` text NOT NULL,
  `private` tinyint(1) NOT NULL,
  `guestable` tinyint(1) NOT NULL DEFAULT '1',
  `last_post` mediumint(9) NOT NULL,
  `last_topic_id` smallint(6) NOT NULL,
  `last_topic_title` text NOT NULL,
  `last_post_id` mediumint(9) NOT NULL,
  `last_poster` smallint(6) NOT NULL,
  `post_count` smallint(6) NOT NULL,
  `topic_count` smallint(6) NOT NULL,
  KEY `ID` (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `forum_forums`
--

CREATE TABLE IF NOT EXISTS `forum_forums` (
  `type` mediumint(9) NOT NULL,
  `ID` mediumint(9) NOT NULL,
  `banned` text NOT NULL,
  KEY `ID` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `forum_modlog`
--

CREATE TABLE IF NOT EXISTS `forum_modlog` (
  `ID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `mod_user_id` smallint(6) NOT NULL,
  `timestamp` mediumint(9) NOT NULL,
  `desc` text NOT NULL,
  KEY `ID` (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `forum_posts`
--

CREATE TABLE IF NOT EXISTS `forum_posts` (
  `ID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `forum_id` tinytext NOT NULL,
  `topic_id` mediumint(9) NOT NULL,
  `board_id` mediumint(9) NOT NULL,
  `number` smallint(6) NOT NULL,
  `poster_id` mediumint(9) NOT NULL,
  `created` int(11) NOT NULL,
  `edited_time` int(11) NOT NULL,
  `edits` tinyint(4) NOT NULL,
  `edit_by` mediumint(9) NOT NULL,
  `post_content` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=43 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `forum_topics`
--

CREATE TABLE IF NOT EXISTS `forum_topics` (
  `ID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `forum_id` tinytext NOT NULL,
  `board_id` mediumint(9) NOT NULL,
  `creator` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  `lastpost` int(11) NOT NULL,
  `lastposter` mediumint(9) NOT NULL,
  `title` text NOT NULL,
  `postcount` smallint(6) NOT NULL,
  `type` tinyint(4) NOT NULL,
  KEY `ID` (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `game_log`
--

CREATE TABLE IF NOT EXISTS `game_log` (
  `l_id` int(11) NOT NULL AUTO_INCREMENT,
  `l_vid` int(11) NOT NULL,
  `l_action` varchar(20) NOT NULL,
  `l_subId` int(11) NOT NULL,
  `l_date` datetime NOT NULL,
  `l_data` varchar(250) NOT NULL,
  `l_notification` tinyint(1) NOT NULL,
  `l_suspicious` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`l_id`),
  KEY `l_vid` (`l_vid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=31598 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `game_log_scouts`
--

CREATE TABLE IF NOT EXISTS `game_log_scouts` (
  `ls_id` int(11) NOT NULL AUTO_INCREMENT,
  `ls_runes` varchar(50) NOT NULL,
  PRIMARY KEY (`ls_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=931 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `game_log_training`
--

CREATE TABLE IF NOT EXISTS `game_log_training` (
  `lt_id` int(11) NOT NULL AUTO_INCREMENT,
  `u_id` int(11) NOT NULL,
  `lt_amount` int(11) NOT NULL,
  PRIMARY KEY (`lt_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3345 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `map_buildings`
--

CREATE TABLE IF NOT EXISTS `map_buildings` (
  `bid` int(11) NOT NULL AUTO_INCREMENT,
  `xas` float NOT NULL DEFAULT '0',
  `yas` float NOT NULL DEFAULT '0',
  `sizeX` float NOT NULL DEFAULT '0',
  `sizeY` float NOT NULL DEFAULT '0',
  `buildingType` int(11) NOT NULL DEFAULT '0',
  `village` int(11) NOT NULL DEFAULT '0',
  `startDate` int(11) NOT NULL DEFAULT '0',
  `readyDate` int(11) NOT NULL DEFAULT '0',
  `lastUpgradeDate` int(11) NOT NULL DEFAULT '0',
  `usedResources` text NOT NULL,
  `destroyDate` int(11) NOT NULL DEFAULT '0',
  `bLevel` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`bid`),
  KEY `xas` (`xas`,`yas`),
  KEY `village` (`village`),
  KEY `buildingType` (`buildingType`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1253 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `map_portals`
--

CREATE TABLE IF NOT EXISTS `map_portals` (
  `p_id` int(11) NOT NULL AUTO_INCREMENT,
  `p_caster_v_id` int(11) NOT NULL,
  `p_target_v_id` int(11) NOT NULL,
  `p_caster_x` int(11) NOT NULL,
  `p_caster_y` int(11) NOT NULL,
  `p_target_x` int(11) NOT NULL,
  `p_target_y` int(11) NOT NULL,
  `p_caster_b_id` int(11) NOT NULL,
  `p_target_b_id` int(11) NOT NULL,
  `p_endDate` datetime DEFAULT NULL,
  PRIMARY KEY (`p_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=47 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_auth_openid`
--

CREATE TABLE IF NOT EXISTS `n_auth_openid` (
  `openid_url` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notify_url` text NOT NULL,
  `profilebox_url` text NOT NULL,
  `userstats_url` text NOT NULL,
  PRIMARY KEY (`openid_url`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_chat_channels`
--

CREATE TABLE IF NOT EXISTS `n_chat_channels` (
  `c_c_id` int(11) NOT NULL AUTO_INCREMENT,
  `c_c_name` varchar(20) NOT NULL,
  PRIMARY KEY (`c_c_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=37 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_chat_messages`
--

CREATE TABLE IF NOT EXISTS `n_chat_messages` (
  `c_m_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `c_c_id` int(11) NOT NULL,
  `c_plid` int(11) NOT NULL,
  `c_date` datetime NOT NULL,
  `c_message` varchar(1000) NOT NULL,
  PRIMARY KEY (`c_m_id`),
  KEY `c_c_id` (`c_c_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1249 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_locks`
--

CREATE TABLE IF NOT EXISTS `n_locks` (
  `l_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `l_type` varchar(30) NOT NULL,
  `l_lid` int(11) NOT NULL,
  `l_date` int(11) NOT NULL,
  PRIMARY KEY (`l_id`),
  KEY `l_type` (`l_type`,`l_lid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=845 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_logables`
--

CREATE TABLE IF NOT EXISTS `n_logables` (
  `l_id` int(11) NOT NULL AUTO_INCREMENT,
  `l_name` varchar(50) NOT NULL,
  PRIMARY KEY (`l_id`),
  UNIQUE KEY `l_name` (`l_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=40 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_login_failures`
--

CREATE TABLE IF NOT EXISTS `n_login_failures` (
  `l_id` int(11) NOT NULL AUTO_INCREMENT,
  `l_plid` int(11) DEFAULT NULL,
  `l_ip` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `l_username` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `l_date` datetime NOT NULL,
  PRIMARY KEY (`l_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_login_log`
--

CREATE TABLE IF NOT EXISTS `n_login_log` (
  `l_id` int(11) NOT NULL AUTO_INCREMENT,
  `l_plid` int(11) DEFAULT NULL,
  `l_ip` varchar(20) NOT NULL,
  `l_datetime` datetime NOT NULL,
  PRIMARY KEY (`l_id`),
  KEY `l_plid` (`l_plid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2703 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_map_updates`
--

CREATE TABLE IF NOT EXISTS `n_map_updates` (
  `mu_id` int(11) NOT NULL AUTO_INCREMENT,
  `mu_action` enum('BUILD','DESTROY') NOT NULL,
  `mu_x` int(11) NOT NULL,
  `mu_y` int(11) NOT NULL,
  `mu_date` datetime NOT NULL,
  PRIMARY KEY (`mu_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1634 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_mod_actions`
--

CREATE TABLE IF NOT EXISTS `n_mod_actions` (
  `ma_id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_action` varchar(20) NOT NULL,
  `ma_data` text NOT NULL,
  `ma_plid` int(11) NOT NULL,
  `ma_date` datetime NOT NULL,
  `ma_reason` text NOT NULL,
  `ma_processed` tinyint(1) NOT NULL DEFAULT '0',
  `ma_executed` tinyint(1) DEFAULT NULL,
  `ma_target` int(11) NOT NULL,
  PRIMARY KEY (`ma_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players`
--

CREATE TABLE IF NOT EXISTS `n_players` (
  `plid` int(11) NOT NULL AUTO_INCREMENT,
  `nickname` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_cert` tinyint(4) NOT NULL DEFAULT '0',
  `email_cert_key` varchar(32) DEFAULT NULL,
  `password1` varchar(32) DEFAULT NULL,
  `password2` varchar(32) DEFAULT NULL,
  `activated` tinyint(1) NOT NULL DEFAULT '1',
  `buildingClick` tinyint(4) NOT NULL DEFAULT '0',
  `minimapPosition` tinyint(4) NOT NULL DEFAULT '0',
  `creationDate` datetime DEFAULT NULL,
  `removalDate` datetime DEFAULT NULL,
  `lastRefresh` datetime DEFAULT NULL,
  `isRemoved` tinyint(1) NOT NULL DEFAULT '0',
  `isKillVillages` tinyint(1) NOT NULL DEFAULT '0',
  `isPlaying` tinyint(1) NOT NULL DEFAULT '0',
  `startX` int(11) DEFAULT NULL,
  `startY` int(11) DEFAULT NULL,
  `isPremium` tinyint(1) NOT NULL DEFAULT '0',
  `premiumEndDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `sponsorEndDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `showSponsor` tinyint(1) NOT NULL DEFAULT '0',
  `showAdvertisement` tinyint(4) NOT NULL DEFAULT '0',
  `killCounter` tinyint(4) NOT NULL DEFAULT '0',
  `tmp_key` varchar(32) DEFAULT NULL,
  `tmp_key_end` datetime DEFAULT NULL,
  `startVacation` datetime DEFAULT NULL,
  `referee` varchar(20) NOT NULL,
  `p_referer` int(11) DEFAULT NULL,
  `p_admin` tinyint(1) NOT NULL DEFAULT '0',
  `p_lang` varchar(5) DEFAULT NULL,
  `p_score` int(11) NOT NULL,
  PRIMARY KEY (`plid`),
  KEY `nickname` (`nickname`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=132 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_admin_cleared`
--

CREATE TABLE IF NOT EXISTS `n_players_admin_cleared` (
  `pac_id` int(11) NOT NULL AUTO_INCREMENT,
  `pac_plid1` int(11) NOT NULL,
  `pac_plid2` int(11) NOT NULL,
  `pac_reason` text NOT NULL,
  PRIMARY KEY (`pac_id`),
  KEY `pac_plid1` (`pac_plid1`,`pac_plid2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_banned`
--

CREATE TABLE IF NOT EXISTS `n_players_banned` (
  `pb_id` int(11) NOT NULL AUTO_INCREMENT,
  `plid` int(11) NOT NULL,
  `bp_channel` varchar(20) NOT NULL,
  `bp_end` datetime NOT NULL,
  PRIMARY KEY (`pb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_guide`
--

CREATE TABLE IF NOT EXISTS `n_players_guide` (
  `pg_id` int(11) NOT NULL AUTO_INCREMENT,
  `plid` int(11) NOT NULL,
  `pg_template` varchar(50) NOT NULL,
  `pg_character` varchar(20) NOT NULL,
  `pg_mood` varchar(20) NOT NULL,
  `pg_data` text NOT NULL,
  `pg_read` enum('0','1') NOT NULL,
  `pg_highlight` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`pg_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1780 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_preferences`
--

CREATE TABLE IF NOT EXISTS `n_players_preferences` (
  `p_plid` int(11) NOT NULL,
  `p_key` varchar(15) NOT NULL,
  `p_value` text NOT NULL,
  PRIMARY KEY (`p_plid`,`p_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_quests`
--

CREATE TABLE IF NOT EXISTS `n_players_quests` (
  `pq_id` int(11) NOT NULL AUTO_INCREMENT,
  `plid` int(11) NOT NULL,
  `q_id` int(11) NOT NULL,
  `q_finished` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`pq_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=514 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_social`
--

CREATE TABLE IF NOT EXISTS `n_players_social` (
  `ps_plid` int(11) NOT NULL,
  `ps_targetid` int(11) NOT NULL,
  `ps_status` int(11) NOT NULL,
  PRIMARY KEY (`ps_plid`,`ps_targetid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_update`
--

CREATE TABLE IF NOT EXISTS `n_players_update` (
  `pu_id` int(11) NOT NULL AUTO_INCREMENT,
  `pu_plid` int(11) NOT NULL,
  `pu_key` varchar(20) NOT NULL,
  `pu_value` varchar(20) NOT NULL,
  PRIMARY KEY (`pu_id`),
  KEY `pu_plid` (`pu_plid`),
  KEY `pu_key` (`pu_key`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_privatechat_updates`
--

CREATE TABLE IF NOT EXISTS `n_privatechat_updates` (
  `pu_id` int(11) NOT NULL AUTO_INCREMENT,
  `pu_from` int(11) NOT NULL,
  `pu_to` int(11) NOT NULL,
  `c_m_id` int(11) NOT NULL,
  `pu_date` datetime NOT NULL,
  `pu_read` tinyint(4) NOT NULL,
  PRIMARY KEY (`pu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=713 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_quests`
--

CREATE TABLE IF NOT EXISTS `n_quests` (
  `q_id` int(11) NOT NULL AUTO_INCREMENT,
  `q_class` varchar(50) NOT NULL,
  PRIMARY KEY (`q_id`),
  UNIQUE KEY `q_class` (`q_class`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_server_data`
--

CREATE TABLE IF NOT EXISTS `n_server_data` (
  `s_name` varchar(10) NOT NULL,
  `s_value` varchar(20) NOT NULL,
  PRIMARY KEY (`s_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_server_text`
--

CREATE TABLE IF NOT EXISTS `n_server_text` (
  `s_id` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `s_lang` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `s_value` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`s_id`,`s_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_temp_passwords`
--

CREATE TABLE IF NOT EXISTS `n_temp_passwords` (
  `p_id` int(11) NOT NULL AUTO_INCREMENT,
  `p_plid` int(11) NOT NULL,
  `p_pass` varchar(8) NOT NULL,
  `p_expire` datetime NOT NULL,
  PRIMARY KEY (`p_id`),
  KEY `p_plid` (`p_plid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `oid_associations`
--

CREATE TABLE IF NOT EXISTS `oid_associations` (
  `server_url` varchar(2047) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `secret` blob NOT NULL,
  `issued` int(11) NOT NULL,
  `lifetime` int(11) NOT NULL,
  `assoc_type` varchar(64) NOT NULL,
  PRIMARY KEY (`server_url`(255),`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `oid_nonces`
--

CREATE TABLE IF NOT EXISTS `oid_nonces` (
  `server_url` varchar(2047) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `salt` char(40) NOT NULL,
  UNIQUE KEY `server_url` (`server_url`(255),`timestamp`,`salt`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `players_tiles`
--

CREATE TABLE IF NOT EXISTS `players_tiles` (
  `t_id` int(11) NOT NULL AUTO_INCREMENT,
  `t_userid` int(11) NOT NULL,
  `t_imagename` varchar(50) NOT NULL,
  `t_isPublic` tinyint(1) NOT NULL DEFAULT '0',
  `t_startDate` datetime DEFAULT NULL,
  `t_endDate` datetime DEFAULT NULL,
  `t_description` text,
  PRIMARY KEY (`t_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `premium_queue`
--

CREATE TABLE IF NOT EXISTS `premium_queue` (
  `pq_id` int(11) NOT NULL AUTO_INCREMENT,
  `pq_vid` int(11) NOT NULL,
  `pq_action` varchar(10) NOT NULL,
  `pq_data` text NOT NULL,
  `pq_date` datetime NOT NULL,
  `pq_lastcheck` datetime NOT NULL,
  PRIMARY KEY (`pq_id`),
  KEY `pq_vid` (`pq_vid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=84 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `specialUnits`
--

CREATE TABLE IF NOT EXISTS `specialUnits` (
  `s_id` int(11) NOT NULL AUTO_INCREMENT,
  `s_name` varchar(20) NOT NULL,
  PRIMARY KEY (`s_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `specialUnits_effects`
--

CREATE TABLE IF NOT EXISTS `specialunits_effects` (
  `s_id` int(11) NOT NULL AUTO_INCREMENT,
  `b_id` int(11) NOT NULL,
  `e_id` varchar(10) NOT NULL,
  PRIMARY KEY (`s_id`),
  KEY `b_id` (`b_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=79 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `squad_commands`
--

CREATE TABLE IF NOT EXISTS `squad_commands` (
  `sc_id` int(11) NOT NULL AUTO_INCREMENT,
  `s_id` int(11) NOT NULL,
  `s_action` enum('move') NOT NULL,
  `s_start` datetime NOT NULL,
  `s_end` datetime NOT NULL,
  `s_from` int(11) DEFAULT NULL,
  `s_to` int(11) DEFAULT NULL,
  PRIMARY KEY (`sc_id`),
  KEY `s_id` (`s_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `squad_equipment`
--

CREATE TABLE IF NOT EXISTS `squad_equipment` (
  `se_id` int(11) NOT NULL AUTO_INCREMENT,
  `s_id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `e_id` varchar(10) NOT NULL,
  `v_id` int(11) NOT NULL,
  `i_itid` int(11) NOT NULL,
  PRIMARY KEY (`se_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1450 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `squad_units`
--

CREATE TABLE IF NOT EXISTS `squad_units` (
  `su_id` int(11) NOT NULL AUTO_INCREMENT,
  `s_id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `s_amount` int(11) NOT NULL,
  `v_id` int(11) NOT NULL,
  `s_slotId` tinyint(4) NOT NULL DEFAULT '0',
  `s_priority` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`su_id`),
  UNIQUE KEY `s_id` (`s_id`,`u_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=138 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `technology`
--

CREATE TABLE IF NOT EXISTS `technology` (
  `techId` int(11) NOT NULL AUTO_INCREMENT,
  `techName` varchar(25) NOT NULL,
  PRIMARY KEY (`techId`),
  UNIQUE KEY `techName` (`techName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_armies`
--

CREATE TABLE IF NOT EXISTS `underworld_armies` (
  `ua_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_id` int(11) NOT NULL,
  `ua_x` int(11) NOT NULL,
  `ua_y` int(11) NOT NULL,
  `ua_side` int(11) NOT NULL,
  `ua_lastrefresh` datetime NOT NULL,
  `ua_movepoints` double NOT NULL,
  PRIMARY KEY (`ua_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_armies_leaders`
--

CREATE TABLE IF NOT EXISTS `underworld_armies_leaders` (
  `ual_id` int(11) NOT NULL AUTO_INCREMENT,
  `ua_id` int(11) NOT NULL,
  `plid` int(11) NOT NULL,
  PRIMARY KEY (`ual_id`),
  KEY `ua_id` (`ua_id`),
  KEY `plid` (`plid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_armies_squads`
--

CREATE TABLE IF NOT EXISTS `underworld_armies_squads` (
  `uas_id` int(11) NOT NULL AUTO_INCREMENT,
  `ua_id` int(11) NOT NULL,
  `s_id` int(11) NOT NULL,
  PRIMARY KEY (`uas_id`),
  KEY `ua_id` (`ua_id`),
  KEY `s_id` (`s_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_checkpoints`
--

CREATE TABLE IF NOT EXISTS `underworld_checkpoints` (
  `uc_id` int(11) NOT NULL AUTO_INCREMENT,
  `uc_x` int(11) NOT NULL,
  `uc_y` int(11) NOT NULL,
  `uc_side` int(11) NOT NULL,
  `uc_date` datetime NOT NULL,
  `um_id` int(11) NOT NULL,
  PRIMARY KEY (`uc_id`),
  UNIQUE KEY `uc_x` (`uc_x`,`uc_y`,`um_id`),
  KEY `um_id` (`um_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_explored`
--

CREATE TABLE IF NOT EXISTS `underworld_explored` (
  `ue_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_id` int(11) NOT NULL,
  `ue_side` int(11) NOT NULL,
  `ue_x` int(11) NOT NULL,
  `ue_y` int(11) NOT NULL,
  PRIMARY KEY (`ue_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=38 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_log_armies`
--

CREATE TABLE IF NOT EXISTS `underworld_log_armies` (
  `ul_a_vid` int(11) NOT NULL AUTO_INCREMENT,
  `ul_a_id` int(11) NOT NULL,
  `ul_a_version` int(11) NOT NULL DEFAULT '0',
  `ua_id` int(11) NOT NULL,
  `ul_a_squads` text NOT NULL,
  `ul_a_side` int(11) NOT NULL,
  PRIMARY KEY (`ul_a_vid`),
  UNIQUE KEY `ul_a_id_2` (`ul_a_id`,`ul_a_version`),
  KEY `ua_id` (`ua_id`),
  KEY `ul_a_version` (`ul_a_version`),
  KEY `ul_a_id` (`ul_a_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_log_armies_leaders`
--

CREATE TABLE IF NOT EXISTS `underworld_log_armies_leaders` (
  `ul_a_vid` int(11) NOT NULL,
  `plid` int(11) NOT NULL,
  KEY `plid` (`plid`),
  KEY `ul_a_vid` (`ul_a_vid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_log_battles`
--

CREATE TABLE IF NOT EXISTS `underworld_log_battles` (
  `uat_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_id` int(11) DEFAULT NULL,
  `uat_attacker` int(11) DEFAULT NULL,
  `uat_defender` int(11) DEFAULT NULL,
  `uat_startdate` datetime DEFAULT NULL,
  `uat_enddate` datetime DEFAULT NULL,
  `uat_fightlog` text NOT NULL,
  `uat_from_x` int(11) NOT NULL,
  `uat_from_y` int(11) NOT NULL,
  `uat_to_x` int(11) NOT NULL,
  `uat_to_y` int(11) NOT NULL,
  `uat_attacker_side` int(11) NOT NULL,
  `uat_defender_side` int(11) NOT NULL,
  PRIMARY KEY (`uat_id`),
  KEY `uat_defender` (`uat_defender`),
  KEY `uat_attacker` (`uat_attacker`),
  KEY `um_id` (`um_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_log_clans`
--

CREATE TABLE IF NOT EXISTS `underworld_log_clans` (
  `us_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_id` int(11) NOT NULL,
  `us_clan` int(11) NOT NULL,
  `us_side` int(11) NOT NULL,
  PRIMARY KEY (`us_id`),
  KEY `us_clan` (`us_clan`),
  KEY `um_id` (`um_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_log_event`
--

CREATE TABLE IF NOT EXISTS `underworld_log_event` (
  `ul_e_id` int(11) NOT NULL AUTO_INCREMENT,
  `ul_m_id` int(11) NOT NULL,
  `plid` int(11) DEFAULT NULL,
  `ul_a_vid` int(11) DEFAULT NULL,
  `ul_e_action` enum('SPAWN','MOVE','ATTACK','SPLIT','MERGE','WITHDRAW','WIN') NOT NULL,
  `ul_a2_vid` int(11) DEFAULT NULL,
  `uat_id` int(11) DEFAULT NULL,
  `ul_e_x` int(11) DEFAULT NULL,
  `ul_e_y` int(11) DEFAULT NULL,
  `ul_e_date` datetime NOT NULL,
  `ul_e_extra` text,
  `ul_side` int(11) DEFAULT NULL,
  PRIMARY KEY (`ul_e_id`),
  UNIQUE KEY `uat_id` (`uat_id`),
  KEY `ul_a_id` (`ul_a_vid`),
  KEY `ul_m_id` (`ul_m_id`),
  KEY `plid` (`plid`),
  KEY `ul_a2_id` (`ul_a2_vid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=17 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_log_mission`
--

CREATE TABLE IF NOT EXISTS `underworld_log_mission` (
  `ul_m_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_id` int(11) DEFAULT NULL,
  `ul_m_map` varchar(20) NOT NULL,
  `ul_m_mission` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`ul_m_id`),
  KEY `um_id` (`um_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_missions`
--

CREATE TABLE IF NOT EXISTS `underworld_missions` (
  `um_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_map` varchar(20) NOT NULL,
  `um_mission` varchar(20) NOT NULL,
  `um_global` tinyint(4) NOT NULL,
  PRIMARY KEY (`um_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_missions_clans`
--

CREATE TABLE IF NOT EXISTS `underworld_missions_clans` (
  `umc_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_id` int(11) NOT NULL,
  `c_id` int(11) NOT NULL,
  `umc_side` tinyint(4) NOT NULL,
  PRIMARY KEY (`umc_id`),
  KEY `um_id` (`um_id`),
  KEY `c_id` (`c_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_score`
--

CREATE TABLE IF NOT EXISTS `underworld_score` (
  `us_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_id` int(11) NOT NULL,
  `us_side` int(11) NOT NULL,
  `us_score` int(11) NOT NULL,
  PRIMARY KEY (`us_id`),
  UNIQUE KEY `um_id_2` (`um_id`,`us_side`),
  KEY `um_id` (`um_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `units`
--

CREATE TABLE IF NOT EXISTS `units` (
  `unitId` int(11) NOT NULL AUTO_INCREMENT,
  `unitName` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`unitId`),
  UNIQUE KEY `unitName` (`unitName`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages`
--

CREATE TABLE IF NOT EXISTS `villages` (
  `vid` int(11) NOT NULL AUTO_INCREMENT,
  `isActive` enum('1','0') NOT NULL,
  `isDestroyed` tinyint(4) NOT NULL DEFAULT '0',
  `plid` int(11) NOT NULL DEFAULT '0',
  `race` tinyint(4) NOT NULL DEFAULT '0',
  `vname` varchar(30) NOT NULL DEFAULT '',
  `gold` double NOT NULL DEFAULT '250',
  `wood` double NOT NULL DEFAULT '750',
  `stone` double NOT NULL DEFAULT '750',
  `iron` double NOT NULL DEFAULT '750',
  `grain` double NOT NULL DEFAULT '750',
  `gems` double NOT NULL DEFAULT '10',
  `lastResRefresh` int(11) NOT NULL DEFAULT '0',
  `networth` int(11) NOT NULL DEFAULT '0',
  `networth_date` int(11) NOT NULL DEFAULT '0',
  `runeScoutsDone` int(11) NOT NULL DEFAULT '0',
  `removalDate` datetime DEFAULT NULL,
  PRIMARY KEY (`vid`),
  KEY `plid` (`plid`),
  KEY `vname` (`vname`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=288 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_blevel`
--

CREATE TABLE IF NOT EXISTS `villages_blevel` (
  `vid` int(11) NOT NULL DEFAULT '0',
  `bid` int(11) NOT NULL DEFAULT '0',
  `lvl` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vid`,`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_counters`
--

CREATE TABLE IF NOT EXISTS `villages_counters` (
  `c_id` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL,
  `c_start` int(11) NOT NULL,
  `c_end` int(11) NOT NULL,
  `c_text` varchar(100) NOT NULL,
  PRIMARY KEY (`c_id`),
  KEY `vid` (`vid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_itemlevels`
--

CREATE TABLE IF NOT EXISTS `villages_itemlevels` (
  `v_id` int(11) NOT NULL,
  `e_id` int(11) NOT NULL,
  `vi_level` tinyint(4) NOT NULL,
  PRIMARY KEY (`v_id`,`e_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_items`
--

CREATE TABLE IF NOT EXISTS `villages_items` (
  `i_id` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL,
  `i_itemId` varchar(10) NOT NULL,
  `i_amount` int(11) NOT NULL,
  `i_startCraft` int(11) NOT NULL,
  `i_endCraft` int(11) NOT NULL,
  `i_removed` int(11) NOT NULL,
  `i_buildingId` int(11) NOT NULL,
  `i_bid` int(11) NOT NULL,
  PRIMARY KEY (`i_id`),
  KEY `vid` (`vid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=586 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_morale`
--

CREATE TABLE IF NOT EXISTS `villages_morale` (
  `m_id` int(11) NOT NULL AUTO_INCREMENT,
  `m_vid` int(11) NOT NULL,
  `m_amount` tinyint(4) NOT NULL,
  `m_start` datetime NOT NULL,
  `m_end` datetime NOT NULL,
  PRIMARY KEY (`m_id`),
  KEY `m_vid` (`m_vid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_runes`
--

CREATE TABLE IF NOT EXISTS `villages_runes` (
  `vid` int(11) NOT NULL DEFAULT '0',
  `runeId` varchar(10) NOT NULL DEFAULT '',
  `amount` int(11) NOT NULL DEFAULT '0',
  `usedRunes` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vid`,`runeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_scouting`
--

CREATE TABLE IF NOT EXISTS `villages_scouting` (
  `scoutId` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL DEFAULT '0',
  `finishDate` int(11) NOT NULL DEFAULT '0',
  `runes` text NOT NULL,
  PRIMARY KEY (`scoutId`),
  UNIQUE KEY `vid` (`vid`,`finishDate`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=941 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_slots`
--

CREATE TABLE IF NOT EXISTS `villages_slots` (
  `vs_vid` int(11) NOT NULL,
  `vs_slot` tinyint(4) NOT NULL,
  `vs_slotId` int(11) NOT NULL,
  PRIMARY KEY (`vs_vid`,`vs_slot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_specialunits`
--

CREATE TABLE IF NOT EXISTS `villages_specialunits` (
  `vsu_id` int(11) NOT NULL AUTO_INCREMENT,
  `v_id` int(11) NOT NULL,
  `vsu_bid` int(11) NOT NULL,
  `vsu_tStartDate` int(11) NOT NULL,
  `vsu_tEndDate` int(11) NOT NULL,
  `vsu_location` int(11) DEFAULT NULL,
  `vsu_moveStart` datetime DEFAULT NULL,
  `vsu_moveEnd` datetime DEFAULT NULL,
  PRIMARY KEY (`vsu_id`),
  KEY `v_id` (`v_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_squads`
--

CREATE TABLE IF NOT EXISTS `villages_squads` (
  `s_id` int(11) NOT NULL AUTO_INCREMENT,
  `v_id` int(11) NOT NULL,
  `v_type` int(11) NOT NULL,
  `s_name` varchar(20) NOT NULL,
  `s_village` int(11) NOT NULL,
  PRIMARY KEY (`s_id`),
  KEY `v_id` (`v_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=140 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_tech`
--

CREATE TABLE IF NOT EXISTS `villages_tech` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL,
  `techId` tinyint(4) NOT NULL,
  `startDate` int(11) NOT NULL,
  `endDate` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `vid` (`vid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=86 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_transfers`
--

CREATE TABLE IF NOT EXISTS `villages_transfers` (
  `t_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_vid` int(11) NOT NULL,
  `to_vid` int(11) NOT NULL,
  `t_date_sent` datetime NOT NULL,
  `t_date_received` datetime NOT NULL,
  `t_isReceived` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`t_id`),
  KEY `from_vid` (`from_vid`),
  KEY `to_vid` (`to_vid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10302 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_transfers_items`
--

CREATE TABLE IF NOT EXISTS `villages_transfers_items` (
  `ti_id` int(11) NOT NULL AUTO_INCREMENT,
  `t_id` int(11) NOT NULL,
  `ti_type` enum('RESOURCE','RUNE','EQUIPMENT') NOT NULL,
  `ti_key` varchar(20) NOT NULL,
  `ti_amount` int(11) NOT NULL,
  PRIMARY KEY (`ti_id`),
  KEY `t_id` (`t_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10954 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_units`
--

CREATE TABLE IF NOT EXISTS `villages_units` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL DEFAULT '0',
  `unitId` int(11) NOT NULL DEFAULT '0',
  `buildingId` int(11) NOT NULL DEFAULT '0',
  `village` int(11) NOT NULL DEFAULT '0',
  `amount` int(11) NOT NULL DEFAULT '0',
  `startTraining` int(11) NOT NULL DEFAULT '0',
  `endTraining` int(11) NOT NULL DEFAULT '0',
  `killedAmount` int(11) NOT NULL DEFAULT '0',
  `bid` int(11) NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `vid` (`vid`,`village`),
  KEY `village` (`village`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3345 ;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_visits`
--

CREATE TABLE IF NOT EXISTS `villages_visits` (
  `vi_id` int(11) NOT NULL AUTO_INCREMENT,
  `v_id` int(11) NOT NULL,
  `vi_v_id` int(11) NOT NULL,
  `vi_date` datetime NOT NULL,
  PRIMARY KEY (`vi_id`),
  KEY `v_id` (`v_id`,`vi_v_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=442 ;

--
-- Beperkingen voor gedumpte tabellen
--

--
-- Beperkingen voor tabel `underworld_armies_leaders`
--
ALTER TABLE `underworld_armies_leaders`
  ADD CONSTRAINT `underworld_armies_leaders_ibfk_1` FOREIGN KEY (`ua_id`) REFERENCES `underworld_armies` (`ua_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `underworld_armies_leaders_ibfk_2` FOREIGN KEY (`plid`) REFERENCES `n_players` (`plid`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Beperkingen voor tabel `underworld_armies_squads`
--
ALTER TABLE `underworld_armies_squads`
  ADD CONSTRAINT `underworld_armies_squads_ibfk_3` FOREIGN KEY (`ua_id`) REFERENCES `underworld_armies` (`ua_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `underworld_armies_squads_ibfk_4` FOREIGN KEY (`s_id`) REFERENCES `villages_squads` (`s_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Beperkingen voor tabel `underworld_checkpoints`
--
ALTER TABLE `underworld_checkpoints`
  ADD CONSTRAINT `underworld_checkpoints_ibfk_1` FOREIGN KEY (`um_id`) REFERENCES `underworld_missions` (`um_id`);

--
-- Beperkingen voor tabel `underworld_log_armies_leaders`
--
ALTER TABLE `underworld_log_armies_leaders`
  ADD CONSTRAINT `underworld_log_armies_leaders_ibfk_2` FOREIGN KEY (`plid`) REFERENCES `n_players` (`plid`),
  ADD CONSTRAINT `underworld_log_armies_leaders_ibfk_3` FOREIGN KEY (`ul_a_vid`) REFERENCES `underworld_log_armies` (`ul_a_vid`);

--
-- Beperkingen voor tabel `underworld_log_battles`
--
ALTER TABLE `underworld_log_battles`
  ADD CONSTRAINT `underworld_log_battles_ibfk_1` FOREIGN KEY (`uat_attacker`) REFERENCES `underworld_armies` (`ua_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `underworld_log_battles_ibfk_2` FOREIGN KEY (`uat_defender`) REFERENCES `underworld_armies` (`ua_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `underworld_log_battles_ibfk_4` FOREIGN KEY (`um_id`) REFERENCES `underworld_missions` (`um_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Beperkingen voor tabel `underworld_log_clans`
--
ALTER TABLE `underworld_log_clans`
  ADD CONSTRAINT `underworld_log_clans_ibfk_1` FOREIGN KEY (`um_id`) REFERENCES `underworld_log_mission` (`ul_m_id`),
  ADD CONSTRAINT `underworld_log_clans_ibfk_2` FOREIGN KEY (`us_clan`) REFERENCES `clans` (`c_id`);

--
-- Beperkingen voor tabel `underworld_log_event`
--
ALTER TABLE `underworld_log_event`
  ADD CONSTRAINT `underworld_log_event_ibfk_1` FOREIGN KEY (`ul_m_id`) REFERENCES `underworld_log_mission` (`ul_m_id`),
  ADD CONSTRAINT `underworld_log_event_ibfk_4` FOREIGN KEY (`plid`) REFERENCES `n_players` (`plid`),
  ADD CONSTRAINT `underworld_log_event_ibfk_5` FOREIGN KEY (`ul_a_vid`) REFERENCES `underworld_log_armies` (`ul_a_vid`),
  ADD CONSTRAINT `underworld_log_event_ibfk_6` FOREIGN KEY (`ul_a2_vid`) REFERENCES `underworld_log_armies` (`ul_a_vid`),
  ADD CONSTRAINT `underworld_log_event_ibfk_7` FOREIGN KEY (`uat_id`) REFERENCES `underworld_log_battles` (`uat_id`);

--
-- Beperkingen voor tabel `underworld_log_mission`
--
ALTER TABLE `underworld_log_mission`
  ADD CONSTRAINT `underworld_log_mission_ibfk_1` FOREIGN KEY (`um_id`) REFERENCES `underworld_missions` (`um_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Beperkingen voor tabel `underworld_missions_clans`
--
ALTER TABLE `underworld_missions_clans`
  ADD CONSTRAINT `underworld_missions_clans_ibfk_1` FOREIGN KEY (`um_id`) REFERENCES `underworld_missions` (`um_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `underworld_missions_clans_ibfk_2` FOREIGN KEY (`c_id`) REFERENCES `clans` (`c_id`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Beperkingen voor tabel `underworld_score`
--
ALTER TABLE `underworld_score`
  ADD CONSTRAINT `underworld_score_ibfk_1` FOREIGN KEY (`um_id`) REFERENCES `underworld_missions` (`um_id`);

--
-- Beperkingen voor tabel `villages_transfers`
--
ALTER TABLE `villages_transfers`
  ADD CONSTRAINT `villages_transfers_ibfk_1` FOREIGN KEY (`from_vid`) REFERENCES `villages` (`vid`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `villages_transfers_ibfk_2` FOREIGN KEY (`to_vid`) REFERENCES `villages` (`vid`) ON DELETE NO ACTION ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;