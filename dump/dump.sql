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
) ENGINE=InnoDB AUTO_INCREMENT=1848 DEFAULT CHARSET=utf8;
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
  `c_description` text,
  `c_password` varchar(32) DEFAULT NULL,
  `c_score` int(11) NOT NULL DEFAULT '0',
  `c_isFull` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`c_id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8;
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
  `order` tinyint(4) NOT NULL DEFAULT '0',
  `title` text NOT NULL,
  `desc` text,
  `private` tinyint(1) DEFAULT '0',
  `guestable` tinyint(1) NOT NULL DEFAULT '0',
  `last_post` mediumint(9) DEFAULT NULL,
  `last_topic_id` smallint(6) DEFAULT NULL,
  `last_topic_title` text,
  `last_post_id` mediumint(9) DEFAULT NULL,
  `last_poster` smallint(6) DEFAULT NULL,
  `post_count` smallint(6) NOT NULL DEFAULT '0',
  `topic_count` smallint(6) NOT NULL DEFAULT '0',
  KEY `ID` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8;
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
  `banned` text,
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
) ENGINE=InnoDB AUTO_INCREMENT=289086 DEFAULT CHARSET=utf8;
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
) ENGINE=InnoDB AUTO_INCREMENT=20698 DEFAULT CHARSET=utf8;
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
) ENGINE=InnoDB AUTO_INCREMENT=9641634 DEFAULT CHARSET=utf8;
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
) ENGINE=InnoDB AUTO_INCREMENT=69417 DEFAULT CHARSET=utf8;
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
) ENGINE=MyISAM AUTO_INCREMENT=45974 DEFAULT CHARSET=latin1;
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
  `isKillVillages` tinyint(1) N