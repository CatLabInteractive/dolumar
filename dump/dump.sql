-- MySQL dump 10.13  Distrib 5.7.22, for Linux (x86_64)
--
-- Host: localhost    Database: dolumar
-- ------------------------------------------------------
-- Server version	5.7.22-0ubuntu0.17.10.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `battle`
--

DROP TABLE IF EXISTS `battle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `battle` (
  `battleId` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL DEFAULT '0',
  `targetId` int(11) NOT NULL DEFAULT '0',
  `startDate` int(11) NOT NULL DEFAULT '0',
  `arriveDate` int(11) NOT NULL,
  `fightDate` int(11) NOT NULL DEFAULT '0',
  `endFightDate` int(11) DEFAULT NULL,
  `endDate` int(11) DEFAULT '0',
  `goHomeDuration` int(11) DEFAULT NULL,
  `attackType` enum('attack') NOT NULL DEFAULT 'attack',
  `isFought` tinyint(1) NOT NULL DEFAULT '0',
  `bLogId` int(11) DEFAULT NULL,
  `iHonourLose` int(11) DEFAULT NULL,
  `iBattleSlots` int(11) NOT NULL,
  PRIMARY KEY (`battleId`),
  KEY `vid` (`vid`),
  KEY `targetId` (`targetId`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `battle_report`
--

DROP TABLE IF EXISTS `battle_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `battle_report` (
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
) ENGINE=InnoDB AUTO_INCREMENT=7298 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `battle_specialunits`
--

DROP TABLE IF EXISTS `battle_specialunits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `battle_specialunits` (
  `bsu_id` int(11) NOT NULL AUTO_INCREMENT,
  `bsu_bid` int(11) NOT NULL,
  `bsu_vsu_id` int(11) NOT NULL,
  `bsu_ba_id` varchar(10) NOT NULL,
  `bsu_vid` int(11) NOT NULL,
  PRIMARY KEY (`bsu_id`),
  KEY `bsu_bid` (`bsu_bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `battle_squads`
--

DROP TABLE IF EXISTS `battle_squads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `battle_squads` (
  `bs_id` int(11) NOT NULL AUTO_INCREMENT,
  `bs_bid` int(11) NOT NULL,
  `bs_squadId` int(11) NOT NULL,
  `bs_unitId` int(11) NOT NULL,
  `bs_vid` int(11) NOT NULL,
  `bs_slot` tinyint(4) NOT NULL,
  PRIMARY KEY (`bs_id`),
  UNIQUE KEY `bs_bid` (`bs_bid`,`bs_squadId`,`bs_unitId`),
  KEY `bs_bid_2` (`bs_bid`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bonus_buildings`
--

DROP TABLE IF EXISTS `bonus_buildings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bonus_buildings` (
  `b_id` int(11) NOT NULL,
  `b_player_tile` int(11) NOT NULL,
  PRIMARY KEY (`b_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boosts`
--

DROP TABLE IF EXISTS `boosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boosts` (
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
) ENGINE=InnoDB AUTO_INCREMENT=27381 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clan_members`
--

DROP TABLE IF EXISTS `clan_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clan_members` (
  `cm_id` int(11) NOT NULL AUTO_INCREMENT,
  `plid` int(11) NOT NULL,
  `c_id` int(11) NOT NULL,
  `c_status` enum('member','captain','leader') NOT NULL,
  `cm_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`cm_id`),
  KEY `plid` (`plid`)
) ENGINE=InnoDB AUTO_INCREMENT=1847 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clans`
--

DROP TABLE IF EXISTS `clans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clans` (
  `c_id` int(11) NOT NULL AUTO_INCREMENT,
  `c_name` varchar(20) NOT NULL,
  `c_description` text NOT NULL,
  `c_password` varchar(32) DEFAULT NULL,
  `c_score` int(11) NOT NULL,
  `c_isFull` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`c_id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `effect_report`
--

DROP TABLE IF EXISTS `effect_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `effect_report` (
  `er_id` int(11) NOT NULL AUTO_INCREMENT,
  `er_vid` int(11) NOT NULL,
  `er_target_v_id` int(11) DEFAULT NULL,
  `er_type` varchar(20) NOT NULL,
  `er_date` datetime NOT NULL,
  `er_data` text NOT NULL,
  PRIMARY KEY (`er_id`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `effects`
--

DROP TABLE IF EXISTS `effects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `effects` (
  `e_id` int(11) NOT NULL AUTO_INCREMENT,
  `e_name` varchar(40) NOT NULL,
  PRIMARY KEY (`e_id`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment` (
  `e_id` int(11) NOT NULL AUTO_INCREMENT,
  `e_name` varchar(20) NOT NULL,
  PRIMARY KEY (`e_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_bans`
--

DROP TABLE IF EXISTS `forum_bans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_bans` (
  `ID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `user` tinytext NOT NULL,
  `forumID` tinytext NOT NULL,
  `time` int(11) NOT NULL,
  `reason` tinytext NOT NULL,
  `by` smallint(6) NOT NULL,
  KEY `ID` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_boards`
--

DROP TABLE IF EXISTS `forum_boards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_boards` (
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
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_forums`
--

DROP TABLE IF EXISTS `forum_forums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_forums` (
  `type` mediumint(9) NOT NULL,
  `ID` mediumint(9) NOT NULL,
  `banned` text NOT NULL,
  KEY `ID` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_modlog`
--

DROP TABLE IF EXISTS `forum_modlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_modlog` (
  `ID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `mod_user_id` smallint(6) NOT NULL,
  `timestamp` mediumint(9) NOT NULL,
  `desc` text NOT NULL,
  KEY `ID` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_posts`
--

DROP TABLE IF EXISTS `forum_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_posts` (
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_topics`
--

DROP TABLE IF EXISTS `forum_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_topics` (
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `game_log`
--

DROP TABLE IF EXISTS `game_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_log` (
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
) ENGINE=InnoDB AUTO_INCREMENT=289080 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `game_log_scouts`
--

DROP TABLE IF EXISTS `game_log_scouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_log_scouts` (
  `ls_id` int(11) NOT NULL AUTO_INCREMENT,
  `ls_runes` varchar(50) NOT NULL,
  PRIMARY KEY (`ls_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10494 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `game_log_training`
--

DROP TABLE IF EXISTS `game_log_training`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_log_training` (
  `lt_id` int(11) NOT NULL AUTO_INCREMENT,
  `u_id` int(11) NOT NULL,
  `lt_amount` int(11) NOT NULL,
  PRIMARY KEY (`lt_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52022 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_buildings`
--

DROP TABLE IF EXISTS `map_buildings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_buildings` (
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
) ENGINE=InnoDB AUTO_INCREMENT=20692 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_portals`
--

DROP TABLE IF EXISTS `map_portals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_portals` (
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
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_auth_openid`
--

DROP TABLE IF EXISTS `n_auth_openid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_auth_openid` (
  `openid_url` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notify_url` text,
  `profilebox_url` text,
  `userstats_url` text,
  PRIMARY KEY (`openid_url`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_chat_channels`
--

DROP TABLE IF EXISTS `n_chat_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_chat_channels` (
  `c_c_id` int(11) NOT NULL AUTO_INCREMENT,
  `c_c_name` varchar(20) NOT NULL,
  PRIMARY KEY (`c_c_id`)
) ENGINE=MyISAM AUTO_INCREMENT=355 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_chat_messages`
--

DROP TABLE IF EXISTS `n_chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_chat_messages` (
  `c_m_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `c_c_id` int(11) NOT NULL,
  `c_plid` int(11) NOT NULL,
  `c_date` datetime NOT NULL,
  `c_message` varchar(1000) NOT NULL,
  PRIMARY KEY (`c_m_id`),
  KEY `c_c_id` (`c_c_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3868 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_locks`
--

DROP TABLE IF EXISTS `n_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_locks` (
  `l_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `l_type` varchar(30) NOT NULL,
  `l_lid` int(11) NOT NULL,
  `l_date` int(11) NOT NULL,
  PRIMARY KEY (`l_id`),
  KEY `l_type` (`l_type`,`l_lid`)
) ENGINE=InnoDB AUTO_INCREMENT=9641484 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_logables`
--

DROP TABLE IF EXISTS `n_logables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_logables` (
  `l_id` int(11) NOT NULL AUTO_INCREMENT,
  `l_name` varchar(50) NOT NULL,
  PRIMARY KEY (`l_id`),
  UNIQUE KEY `l_name` (`l_name`)
) ENGINE=InnoDB AUTO_INCREMENT=233 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_login_failures`
--

DROP TABLE IF EXISTS `n_login_failures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_login_failures` (
  `l_id` int(11) NOT NULL AUTO_INCREMENT,
  `l_plid` int(11) DEFAULT NULL,
  `l_ip` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `l_username` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `l_date` datetime NOT NULL,
  PRIMARY KEY (`l_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_login_log`
--

DROP TABLE IF EXISTS `n_login_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_login_log` (
  `l_id` int(11) NOT NULL AUTO_INCREMENT,
  `l_plid` int(11) DEFAULT NULL,
  `l_ip` varchar(20) NOT NULL,
  `l_datetime` datetime NOT NULL,
  PRIMARY KEY (`l_id`),
  KEY `l_plid` (`l_plid`)
) ENGINE=InnoDB AUTO_INCREMENT=69414 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_map_updates`
--

DROP TABLE IF EXISTS `n_map_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_map_updates` (
  `mu_id` int(11) NOT NULL AUTO_INCREMENT,
  `mu_action` enum('BUILD','DESTROY') NOT NULL,
  `mu_x` int(11) NOT NULL,
  `mu_y` int(11) NOT NULL,
  `mu_date` datetime NOT NULL,
  PRIMARY KEY (`mu_id`)
) ENGINE=MyISAM AUTO_INCREMENT=45968 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_mod_actions`
--

DROP TABLE IF EXISTS `n_mod_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_mod_actions` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_players`
--

DROP TABLE IF EXISTS `n_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_players` (
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
  `premiumEndDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sponsorEndDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `showSponsor` tinyint(1) NOT NULL DEFAULT '0',
  `showAdvertisement` tinyint(4) NOT NULL DEFAULT '0',
  `killCounter` tinyint(4) NOT NULL DEFAULT '0',
  `tmp_key` varchar(32) DEFAULT NULL,
  `tmp_key_end` datetime DEFAULT NULL,
  `startVacation` datetime DEFAULT NULL,
  `referee` varchar(20) DEFAULT NULL,
  `p_referer` int(11) NOT NULL DEFAULT '0',
  `p_admin` tinyint(1) NOT NULL DEFAULT '0',
  `p_lang` varchar(5) DEFAULT NULL,
  `p_score` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`plid`),
  KEY `nickname` (`nickname`)
) ENGINE=InnoDB AUTO_INCREMENT=2439 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_players_admin_cleared`
--

DROP TABLE IF EXISTS `n_players_admin_cleared`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_players_admin_cleared` (
  `pac_id` int(11) NOT NULL AUTO_INCREMENT,
  `pac_plid1` int(11) NOT NULL,
  `pac_plid2` int(11) NOT NULL,
  `pac_reason` text NOT NULL,
  PRIMARY KEY (`pac_id`),
  KEY `pac_plid1` (`pac_plid1`,`pac_plid2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_players_banned`
--

DROP TABLE IF EXISTS `n_players_banned`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_players_banned` (
  `pb_id` int(11) NOT NULL AUTO_INCREMENT,
  `plid` int(11) NOT NULL,
  `bp_channel` varchar(20) NOT NULL,
  `bp_end` datetime NOT NULL,
  PRIMARY KEY (`pb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_players_guide`
--

DROP TABLE IF EXISTS `n_players_guide`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_players_guide` (
  `pg_id` int(11) NOT NULL AUTO_INCREMENT,
  `plid` int(11) NOT NULL,
  `pg_template` varchar(50) NOT NULL,
  `pg_character` varchar(20) NOT NULL,
  `pg_mood` varchar(20) NOT NULL,
  `pg_data` text NOT NULL,
  `pg_read` enum('0','1') NOT NULL,
  `pg_highlight` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`pg_id`)
) ENGINE=MyISAM AUTO_INCREMENT=26139 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_players_preferences`
--

DROP TABLE IF EXISTS `n_players_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_players_preferences` (
  `p_plid` int(11) NOT NULL,
  `p_key` varchar(15) NOT NULL,
  `p_value` text NOT NULL,
  PRIMARY KEY (`p_plid`,`p_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_players_quests`
--

DROP TABLE IF EXISTS `n_players_quests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_players_quests` (
  `pq_id` int(11) NOT NULL AUTO_INCREMENT,
  `plid` int(11) NOT NULL,
  `q_id` int(11) NOT NULL,
  `q_finished` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`pq_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7225 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_players_social`
--

DROP TABLE IF EXISTS `n_players_social`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_players_social` (
  `ps_plid` int(11) NOT NULL,
  `ps_targetid` int(11) NOT NULL,
  `ps_status` int(11) NOT NULL,
  PRIMARY KEY (`ps_plid`,`ps_targetid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_players_update`
--

DROP TABLE IF EXISTS `n_players_update`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_players_update` (
  `pu_id` int(11) NOT NULL AUTO_INCREMENT,
  `pu_plid` int(11) NOT NULL,
  `pu_key` varchar(20) NOT NULL,
  `pu_value` varchar(20) NOT NULL,
  PRIMARY KEY (`pu_id`),
  KEY `pu_plid` (`pu_plid`),
  KEY `pu_key` (`pu_key`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_privatechat_updates`
--

DROP TABLE IF EXISTS `n_privatechat_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_privatechat_updates` (
  `pu_id` int(11) NOT NULL AUTO_INCREMENT,
  `pu_from` int(11) NOT NULL,
  `pu_to` int(11) NOT NULL,
  `c_m_id` int(11) NOT NULL,
  `pu_date` datetime NOT NULL,
  `pu_read` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1618 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_quests`
--

DROP TABLE IF EXISTS `n_quests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_quests` (
  `q_id` int(11) NOT NULL AUTO_INCREMENT,
  `q_class` varchar(50) NOT NULL,
  PRIMARY KEY (`q_id`),
  UNIQUE KEY `q_class` (`q_class`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_server_data`
--

DROP TABLE IF EXISTS `n_server_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_server_data` (
  `s_name` varchar(10) NOT NULL,
  `s_value` varchar(20) NOT NULL,
  PRIMARY KEY (`s_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_server_text`
--

DROP TABLE IF EXISTS `n_server_text`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_server_text` (
  `s_id` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `s_lang` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `s_value` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`s_id`,`s_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `n_temp_passwords`
--

DROP TABLE IF EXISTS `n_temp_passwords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `n_temp_passwords` (
  `p_id` int(11) NOT NULL AUTO_INCREMENT,
  `p_plid` int(11) NOT NULL,
  `p_pass` varchar(8) NOT NULL,
  `p_expire` datetime NOT NULL,
  PRIMARY KEY (`p_id`),
  KEY `p_plid` (`p_plid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `oid_associations`
--

DROP TABLE IF EXISTS `oid_associations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oid_associations` (
  `server_url` varchar(2047) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `secret` blob NOT NULL,
  `issued` int(11) NOT NULL,
  `lifetime` int(11) NOT NULL,
  `assoc_type` varchar(64) NOT NULL,
  PRIMARY KEY (`server_url`(255),`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `oid_nonces`
--

DROP TABLE IF EXISTS `oid_nonces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oid_nonces` (
  `server_url` varchar(2047) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `salt` char(40) NOT NULL,
  UNIQUE KEY `server_url` (`server_url`(255),`timestamp`,`salt`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `players_tiles`
--

DROP TABLE IF EXISTS `players_tiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `players_tiles` (
  `t_id` int(11) NOT NULL AUTO_INCREMENT,
  `t_userid` int(11) NOT NULL,
  `t_imagename` varchar(50) NOT NULL,
  `t_isPublic` tinyint(1) NOT NULL DEFAULT '0',
  `t_startDate` datetime DEFAULT NULL,
  `t_endDate` datetime DEFAULT NULL,
  `t_description` text,
  PRIMARY KEY (`t_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `premium_queue`
--

DROP TABLE IF EXISTS `premium_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `premium_queue` (
  `pq_id` int(11) NOT NULL AUTO_INCREMENT,
  `pq_vid` int(11) NOT NULL,
  `pq_action` varchar(10) NOT NULL,
  `pq_data` text NOT NULL,
  `pq_date` datetime NOT NULL,
  `pq_lastcheck` datetime DEFAULT NULL,
  PRIMARY KEY (`pq_id`),
  KEY `pq_vid` (`pq_vid`)
) ENGINE=InnoDB AUTO_INCREMENT=1687 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specialunits`
--

DROP TABLE IF EXISTS `specialunits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specialunits` (
  `s_id` int(11) NOT NULL AUTO_INCREMENT,
  `s_name` varchar(20) NOT NULL,
  PRIMARY KEY (`s_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specialunits_effects`
--

DROP TABLE IF EXISTS `specialunits_effects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specialunits_effects` (
  `s_id` int(11) NOT NULL AUTO_INCREMENT,
  `b_id` int(11) NOT NULL,
  `e_id` varchar(10) NOT NULL,
  PRIMARY KEY (`s_id`),
  KEY `b_id` (`b_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2271 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `squad_commands`
--

DROP TABLE IF EXISTS `squad_commands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `squad_commands` (
  `sc_id` int(11) NOT NULL AUTO_INCREMENT,
  `s_id` int(11) NOT NULL,
  `s_action` enum('move') NOT NULL,
  `s_start` datetime NOT NULL,
  `s_end` datetime NOT NULL,
  `s_from` int(11) DEFAULT NULL,
  `s_to` int(11) DEFAULT NULL,
  PRIMARY KEY (`sc_id`),
  KEY `s_id` (`s_id`)
) ENGINE=InnoDB AUTO_INCREMENT=293 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `squad_equipment`
--

DROP TABLE IF EXISTS `squad_equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `squad_equipment` (
  `se_id` int(11) NOT NULL AUTO_INCREMENT,
  `s_id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `e_id` varchar(10) NOT NULL,
  `v_id` int(11) NOT NULL,
  `i_itid` int(11) NOT NULL,
  PRIMARY KEY (`se_id`)
) ENGINE=InnoDB AUTO_INCREMENT=57322 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `squad_units`
--

DROP TABLE IF EXISTS `squad_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `squad_units` (
  `su_id` int(11) NOT NULL AUTO_INCREMENT,
  `s_id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `s_amount` int(11) NOT NULL,
  `v_id` int(11) NOT NULL,
  `s_slotId` tinyint(4) NOT NULL DEFAULT '0',
  `s_priority` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`su_id`),
  UNIQUE KEY `s_id` (`s_id`,`u_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4851 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `technology`
--

DROP TABLE IF EXISTS `technology`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `technology` (
  `techId` int(11) NOT NULL AUTO_INCREMENT,
  `techName` varchar(25) NOT NULL,
  PRIMARY KEY (`techId`),
  UNIQUE KEY `techName` (`techName`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_armies`
--

DROP TABLE IF EXISTS `underworld_armies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_armies` (
  `ua_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_id` int(11) NOT NULL,
  `ua_x` int(11) NOT NULL,
  `ua_y` int(11) NOT NULL,
  `ua_side` int(11) NOT NULL,
  `ua_lastrefresh` datetime NOT NULL,
  `ua_movepoints` double NOT NULL,
  PRIMARY KEY (`ua_id`)
) ENGINE=InnoDB AUTO_INCREMENT=224 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_armies_leaders`
--

DROP TABLE IF EXISTS `underworld_armies_leaders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_armies_leaders` (
  `ual_id` int(11) NOT NULL AUTO_INCREMENT,
  `ua_id` int(11) NOT NULL,
  `plid` int(11) NOT NULL,
  PRIMARY KEY (`ual_id`),
  KEY `ua_id` (`ua_id`),
  KEY `plid` (`plid`),
  CONSTRAINT `underworld_armies_leaders_ibfk_1` FOREIGN KEY (`ua_id`) REFERENCES `underworld_armies` (`ua_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `underworld_armies_leaders_ibfk_2` FOREIGN KEY (`plid`) REFERENCES `n_players` (`plid`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=224 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_armies_squads`
--

DROP TABLE IF EXISTS `underworld_armies_squads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_armies_squads` (
  `uas_id` int(11) NOT NULL AUTO_INCREMENT,
  `ua_id` int(11) NOT NULL,
  `s_id` int(11) NOT NULL,
  PRIMARY KEY (`uas_id`),
  KEY `ua_id` (`ua_id`),
  KEY `s_id` (`s_id`),
  CONSTRAINT `underworld_armies_squads_ibfk_3` FOREIGN KEY (`ua_id`) REFERENCES `underworld_armies` (`ua_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `underworld_armies_squads_ibfk_4` FOREIGN KEY (`s_id`) REFERENCES `villages_squads` (`s_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=434 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_checkpoints`
--

DROP TABLE IF EXISTS `underworld_checkpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_checkpoints` (
  `uc_id` int(11) NOT NULL AUTO_INCREMENT,
  `uc_x` int(11) NOT NULL,
  `uc_y` int(11) NOT NULL,
  `uc_side` int(11) NOT NULL,
  `uc_date` datetime NOT NULL,
  `um_id` int(11) NOT NULL,
  PRIMARY KEY (`uc_id`),
  UNIQUE KEY `uc_x` (`uc_x`,`uc_y`,`um_id`),
  KEY `um_id` (`um_id`),
  CONSTRAINT `underworld_checkpoints_ibfk_1` FOREIGN KEY (`um_id`) REFERENCES `underworld_missions` (`um_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_explored`
--

DROP TABLE IF EXISTS `underworld_explored`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_explored` (
  `ue_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_id` int(11) NOT NULL,
  `ue_side` int(11) NOT NULL,
  `ue_x` int(11) NOT NULL,
  `ue_y` int(11) NOT NULL,
  PRIMARY KEY (`ue_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5807 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_log_armies`
--

DROP TABLE IF EXISTS `underworld_log_armies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_log_armies` (
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
) ENGINE=InnoDB AUTO_INCREMENT=284 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_log_armies_leaders`
--

DROP TABLE IF EXISTS `underworld_log_armies_leaders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_log_armies_leaders` (
  `ul_a_vid` int(11) NOT NULL,
  `plid` int(11) NOT NULL,
  KEY `plid` (`plid`),
  KEY `ul_a_vid` (`ul_a_vid`),
  CONSTRAINT `underworld_log_armies_leaders_ibfk_2` FOREIGN KEY (`plid`) REFERENCES `n_players` (`plid`),
  CONSTRAINT `underworld_log_armies_leaders_ibfk_3` FOREIGN KEY (`ul_a_vid`) REFERENCES `underworld_log_armies` (`ul_a_vid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_log_battles`
--

DROP TABLE IF EXISTS `underworld_log_battles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_log_battles` (
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
  KEY `um_id` (`um_id`),
  CONSTRAINT `underworld_log_battles_ibfk_1` FOREIGN KEY (`uat_attacker`) REFERENCES `underworld_armies` (`ua_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `underworld_log_battles_ibfk_2` FOREIGN KEY (`uat_defender`) REFERENCES `underworld_armies` (`ua_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `underworld_log_battles_ibfk_4` FOREIGN KEY (`um_id`) REFERENCES `underworld_missions` (`um_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_log_clans`
--

DROP TABLE IF EXISTS `underworld_log_clans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_log_clans` (
  `us_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_id` int(11) NOT NULL,
  `us_clan` int(11) NOT NULL,
  `us_side` int(11) NOT NULL,
  PRIMARY KEY (`us_id`),
  KEY `us_clan` (`us_clan`),
  KEY `um_id` (`um_id`),
  CONSTRAINT `underworld_log_clans_ibfk_1` FOREIGN KEY (`um_id`) REFERENCES `underworld_log_mission` (`ul_m_id`),
  CONSTRAINT `underworld_log_clans_ibfk_2` FOREIGN KEY (`us_clan`) REFERENCES `clans` (`c_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_log_event`
--

DROP TABLE IF EXISTS `underworld_log_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_log_event` (
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
  KEY `ul_a2_id` (`ul_a2_vid`),
  CONSTRAINT `underworld_log_event_ibfk_1` FOREIGN KEY (`ul_m_id`) REFERENCES `underworld_log_mission` (`ul_m_id`),
  CONSTRAINT `underworld_log_event_ibfk_4` FOREIGN KEY (`plid`) REFERENCES `n_players` (`plid`),
  CONSTRAINT `underworld_log_event_ibfk_5` FOREIGN KEY (`ul_a_vid`) REFERENCES `underworld_log_armies` (`ul_a_vid`),
  CONSTRAINT `underworld_log_event_ibfk_6` FOREIGN KEY (`ul_a2_vid`) REFERENCES `underworld_log_armies` (`ul_a_vid`),
  CONSTRAINT `underworld_log_event_ibfk_7` FOREIGN KEY (`uat_id`) REFERENCES `underworld_log_battles` (`uat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2366 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_log_mission`
--

DROP TABLE IF EXISTS `underworld_log_mission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_log_mission` (
  `ul_m_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_id` int(11) DEFAULT NULL,
  `ul_m_map` varchar(20) NOT NULL,
  `ul_m_mission` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`ul_m_id`),
  KEY `um_id` (`um_id`),
  CONSTRAINT `underworld_log_mission_ibfk_1` FOREIGN KEY (`um_id`) REFERENCES `underworld_missions` (`um_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_missions`
--

DROP TABLE IF EXISTS `underworld_missions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_missions` (
  `um_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_map` varchar(20) NOT NULL,
  `um_mission` varchar(20) NOT NULL,
  `um_global` tinyint(4) NOT NULL,
  PRIMARY KEY (`um_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_missions_clans`
--

DROP TABLE IF EXISTS `underworld_missions_clans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_missions_clans` (
  `umc_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_id` int(11) NOT NULL,
  `c_id` int(11) NOT NULL,
  `umc_side` tinyint(4) NOT NULL,
  PRIMARY KEY (`umc_id`),
  KEY `um_id` (`um_id`),
  KEY `c_id` (`c_id`),
  CONSTRAINT `underworld_missions_clans_ibfk_1` FOREIGN KEY (`um_id`) REFERENCES `underworld_missions` (`um_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `underworld_missions_clans_ibfk_2` FOREIGN KEY (`c_id`) REFERENCES `clans` (`c_id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `underworld_score`
--

DROP TABLE IF EXISTS `underworld_score`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `underworld_score` (
  `us_id` int(11) NOT NULL AUTO_INCREMENT,
  `um_id` int(11) NOT NULL,
  `us_side` int(11) NOT NULL,
  `us_score` int(11) NOT NULL,
  PRIMARY KEY (`us_id`),
  UNIQUE KEY `um_id_2` (`um_id`,`us_side`),
  KEY `um_id` (`um_id`),
  CONSTRAINT `underworld_score_ibfk_1` FOREIGN KEY (`um_id`) REFERENCES `underworld_missions` (`um_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
  `unitId` int(11) NOT NULL AUTO_INCREMENT,
  `unitName` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`unitId`),
  UNIQUE KEY `unitName` (`unitName`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages`
--

DROP TABLE IF EXISTS `villages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages` (
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
) ENGINE=InnoDB AUTO_INCREMENT=3426 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_blevel`
--

DROP TABLE IF EXISTS `villages_blevel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_blevel` (
  `vid` int(11) NOT NULL DEFAULT '0',
  `bid` int(11) NOT NULL DEFAULT '0',
  `lvl` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vid`,`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_counters`
--

DROP TABLE IF EXISTS `villages_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_counters` (
  `c_id` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL,
  `c_start` int(11) NOT NULL,
  `c_end` int(11) NOT NULL,
  `c_text` varchar(100) NOT NULL,
  PRIMARY KEY (`c_id`),
  KEY `vid` (`vid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_itemlevels`
--

DROP TABLE IF EXISTS `villages_itemlevels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_itemlevels` (
  `v_id` int(11) NOT NULL,
  `e_id` int(11) NOT NULL,
  `vi_level` tinyint(4) NOT NULL,
  PRIMARY KEY (`v_id`,`e_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_items`
--

DROP TABLE IF EXISTS `villages_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_items` (
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
) ENGINE=InnoDB AUTO_INCREMENT=26746 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_morale`
--

DROP TABLE IF EXISTS `villages_morale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_morale` (
  `m_id` int(11) NOT NULL AUTO_INCREMENT,
  `m_vid` int(11) NOT NULL,
  `m_amount` tinyint(4) NOT NULL,
  `m_start` datetime NOT NULL,
  `m_end` datetime NOT NULL,
  PRIMARY KEY (`m_id`),
  KEY `m_vid` (`m_vid`)
) ENGINE=InnoDB AUTO_INCREMENT=704 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_runes`
--

DROP TABLE IF EXISTS `villages_runes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_runes` (
  `vid` int(11) NOT NULL DEFAULT '0',
  `runeId` varchar(10) NOT NULL DEFAULT '',
  `amount` int(11) NOT NULL DEFAULT '0',
  `usedRunes` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vid`,`runeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_scouting`
--

DROP TABLE IF EXISTS `villages_scouting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_scouting` (
  `scoutId` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL DEFAULT '0',
  `finishDate` int(11) NOT NULL DEFAULT '0',
  `runes` text NOT NULL,
  PRIMARY KEY (`scoutId`),
  UNIQUE KEY `vid` (`vid`,`finishDate`)
) ENGINE=InnoDB AUTO_INCREMENT=9253 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_slots`
--

DROP TABLE IF EXISTS `villages_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_slots` (
  `vs_vid` int(11) NOT NULL,
  `vs_slot` tinyint(4) NOT NULL,
  `vs_slotId` int(11) NOT NULL,
  PRIMARY KEY (`vs_vid`,`vs_slot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_specialunits`
--

DROP TABLE IF EXISTS `villages_specialunits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_specialunits` (
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
) ENGINE=InnoDB AUTO_INCREMENT=1638 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_squads`
--

DROP TABLE IF EXISTS `villages_squads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_squads` (
  `s_id` int(11) NOT NULL AUTO_INCREMENT,
  `v_id` int(11) NOT NULL,
  `v_type` int(11) NOT NULL,
  `s_name` varchar(20) NOT NULL,
  `s_village` int(11) DEFAULT NULL,
  PRIMARY KEY (`s_id`),
  KEY `v_id` (`v_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4831 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_tech`
--

DROP TABLE IF EXISTS `villages_tech`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_tech` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL,
  `techId` tinyint(4) NOT NULL,
  `startDate` int(11) NOT NULL,
  `endDate` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `vid` (`vid`)
) ENGINE=InnoDB AUTO_INCREMENT=1195 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_transfers`
--

DROP TABLE IF EXISTS `villages_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_transfers` (
  `t_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_vid` int(11) NOT NULL,
  `to_vid` int(11) NOT NULL,
  `t_date_sent` datetime NOT NULL,
  `t_date_received` datetime NOT NULL,
  `t_isReceived` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`t_id`),
  KEY `from_vid` (`from_vid`),
  KEY `to_vid` (`to_vid`),
  CONSTRAINT `villages_transfers_ibfk_1` FOREIGN KEY (`from_vid`) REFERENCES `villages` (`vid`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `villages_transfers_ibfk_2` FOREIGN KEY (`to_vid`) REFERENCES `villages` (`vid`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35513 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_transfers_items`
--

DROP TABLE IF EXISTS `villages_transfers_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_transfers_items` (
  `ti_id` int(11) NOT NULL AUTO_INCREMENT,
  `t_id` int(11) NOT NULL,
  `ti_type` enum('RESOURCE','RUNE','EQUIPMENT') NOT NULL,
  `ti_key` varchar(20) NOT NULL,
  `ti_amount` int(11) NOT NULL,
  PRIMARY KEY (`ti_id`),
  KEY `t_id` (`t_id`)
) ENGINE=MyISAM AUTO_INCREMENT=41633 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_units`
--

DROP TABLE IF EXISTS `villages_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_units` (
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
) ENGINE=InnoDB AUTO_INCREMENT=52022 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `villages_visits`
--

DROP TABLE IF EXISTS `villages_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `villages_visits` (
  `vi_id` int(11) NOT NULL AUTO_INCREMENT,
  `v_id` int(11) NOT NULL,
  `vi_v_id` int(11) NOT NULL,
  `vi_date` datetime NOT NULL,
  PRIMARY KEY (`vi_id`),
  KEY `v_id` (`v_id`,`vi_v_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6021 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-05-24 11:02:44