-- phpMyAdmin SQL Dump
-- version 4.6.6deb5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Gegenereerd op: 24 mei 2018 om 14:33
-- Serverversie: 5.7.22-0ubuntu18.04.1
-- PHP-versie: 7.2.5-0ubuntu0.18.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dolumar`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `battle`
--

CREATE TABLE `battle` (
  `battleId` int(11) NOT NULL,
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
  `iBattleSlots` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `battle_report`
--

CREATE TABLE `battle_report` (
  `reportId` int(11) NOT NULL,
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
  `specialUnits` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `battle_specialunits`
--

CREATE TABLE `battle_specialunits` (
  `bsu_id` int(11) NOT NULL,
  `bsu_bid` int(11) NOT NULL,
  `bsu_vsu_id` int(11) NOT NULL,
  `bsu_ba_id` varchar(10) NOT NULL,
  `bsu_vid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `battle_squads`
--

CREATE TABLE `battle_squads` (
  `bs_id` int(11) NOT NULL,
  `bs_bid` int(11) NOT NULL,
  `bs_squadId` int(11) NOT NULL,
  `bs_unitId` int(11) NOT NULL,
  `bs_vid` int(11) NOT NULL,
  `bs_slot` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `bonus_buildings`
--

CREATE TABLE `bonus_buildings` (
  `b_id` int(11) NOT NULL,
  `b_player_tile` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `boosts`
--

CREATE TABLE `boosts` (
  `b_id` int(11) NOT NULL,
  `b_targetId` int(11) NOT NULL,
  `b_fromId` int(11) NOT NULL,
  `b_type` enum('spell') NOT NULL,
  `b_ba_id` varchar(10) NOT NULL,
  `b_start` int(11) NOT NULL,
  `b_end` int(11) NOT NULL,
  `b_secret` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `clans`
--

CREATE TABLE `clans` (
  `c_id` int(11) NOT NULL,
  `c_name` varchar(20) NOT NULL,
  `c_description` text,
  `c_password` varchar(32) DEFAULT NULL,
  `c_score` int(11) NOT NULL DEFAULT '0',
  `c_isFull` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `clan_members`
--

CREATE TABLE `clan_members` (
  `cm_id` int(11) NOT NULL,
  `plid` int(11) NOT NULL,
  `c_id` int(11) NOT NULL,
  `c_status` enum('member','captain','leader') NOT NULL,
  `cm_active` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `effects`
--

CREATE TABLE `effects` (
  `e_id` int(11) NOT NULL,
  `e_name` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `effect_report`
--

CREATE TABLE `effect_report` (
  `er_id` int(11) NOT NULL,
  `er_vid` int(11) NOT NULL,
  `er_target_v_id` int(11) DEFAULT NULL,
  `er_type` varchar(20) NOT NULL,
  `er_date` datetime NOT NULL,
  `er_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `equipment`
--

CREATE TABLE `equipment` (
  `e_id` int(11) NOT NULL,
  `e_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `forum_bans`
--

CREATE TABLE `forum_bans` (
  `ID` mediumint(9) NOT NULL,
  `user` tinytext NOT NULL,
  `forumID` tinytext NOT NULL,
  `time` int(11) NOT NULL,
  `reason` tinytext NOT NULL,
  `by` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `forum_boards`
--

CREATE TABLE `forum_boards` (
  `ID` mediumint(9) NOT NULL,
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
  `topic_count` smallint(6) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `forum_forums`
--

CREATE TABLE `forum_forums` (
  `type` mediumint(9) NOT NULL,
  `ID` mediumint(9) NOT NULL,
  `banned` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `forum_modlog`
--

CREATE TABLE `forum_modlog` (
  `ID` mediumint(9) NOT NULL,
  `mod_user_id` smallint(6) NOT NULL,
  `timestamp` mediumint(9) NOT NULL,
  `desc` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `forum_posts`
--

CREATE TABLE `forum_posts` (
  `ID` mediumint(9) NOT NULL,
  `forum_id` tinytext NOT NULL,
  `topic_id` mediumint(9) NOT NULL,
  `board_id` mediumint(9) NOT NULL,
  `number` smallint(6) NOT NULL,
  `poster_id` mediumint(9) NOT NULL,
  `created` int(11) NOT NULL,
  `edited_time` int(11) NOT NULL,
  `edits` tinyint(4) NOT NULL,
  `edit_by` mediumint(9) NOT NULL,
  `post_content` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `forum_topics`
--

CREATE TABLE `forum_topics` (
  `ID` mediumint(9) NOT NULL,
  `forum_id` tinytext NOT NULL,
  `board_id` mediumint(9) NOT NULL,
  `creator` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  `lastpost` int(11) NOT NULL,
  `lastposter` mediumint(9) NOT NULL,
  `title` text NOT NULL,
  `postcount` smallint(6) NOT NULL,
  `type` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `game_log`
--

CREATE TABLE `game_log` (
  `l_id` int(11) NOT NULL,
  `l_vid` int(11) NOT NULL,
  `l_action` varchar(20) NOT NULL,
  `l_subId` int(11) NOT NULL,
  `l_date` datetime NOT NULL,
  `l_data` varchar(250) NOT NULL,
  `l_notification` tinyint(1) NOT NULL,
  `l_suspicious` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `game_log_scouts`
--

CREATE TABLE `game_log_scouts` (
  `ls_id` int(11) NOT NULL,
  `ls_runes` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `game_log_training`
--

CREATE TABLE `game_log_training` (
  `lt_id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `lt_amount` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `map_buildings`
--

CREATE TABLE `map_buildings` (
  `bid` int(11) NOT NULL,
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
  `bLevel` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `map_portals`
--

CREATE TABLE `map_portals` (
  `p_id` int(11) NOT NULL,
  `p_caster_v_id` int(11) NOT NULL,
  `p_target_v_id` int(11) NOT NULL,
  `p_caster_x` int(11) NOT NULL,
  `p_caster_y` int(11) NOT NULL,
  `p_target_x` int(11) NOT NULL,
  `p_target_y` int(11) NOT NULL,
  `p_caster_b_id` int(11) NOT NULL,
  `p_target_b_id` int(11) NOT NULL,
  `p_endDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_auth_openid`
--

CREATE TABLE `n_auth_openid` (
  `openid_url` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notify_url` text,
  `profilebox_url` text,
  `userstats_url` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_chat_channels`
--

CREATE TABLE `n_chat_channels` (
  `c_c_id` int(11) NOT NULL,
  `c_c_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_chat_messages`
--

CREATE TABLE `n_chat_messages` (
  `c_m_id` bigint(20) NOT NULL,
  `c_c_id` int(11) NOT NULL,
  `c_plid` int(11) NOT NULL,
  `c_date` datetime NOT NULL,
  `c_message` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_locks`
--

CREATE TABLE `n_locks` (
  `l_id` bigint(20) NOT NULL,
  `l_type` varchar(30) NOT NULL,
  `l_lid` int(11) NOT NULL,
  `l_date` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_logables`
--

CREATE TABLE `n_logables` (
  `l_id` int(11) NOT NULL,
  `l_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_login_failures`
--

CREATE TABLE `n_login_failures` (
  `l_id` int(11) NOT NULL,
  `l_plid` int(11) DEFAULT NULL,
  `l_ip` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `l_username` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `l_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_login_log`
--

CREATE TABLE `n_login_log` (
  `l_id` int(11) NOT NULL,
  `l_plid` int(11) DEFAULT NULL,
  `l_ip` varchar(20) NOT NULL,
  `l_datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_map_updates`
--

CREATE TABLE `n_map_updates` (
  `mu_id` int(11) NOT NULL,
  `mu_action` enum('BUILD','DESTROY') NOT NULL,
  `mu_x` int(11) NOT NULL,
  `mu_y` int(11) NOT NULL,
  `mu_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_mod_actions`
--

CREATE TABLE `n_mod_actions` (
  `ma_id` int(11) NOT NULL,
  `ma_action` varchar(20) NOT NULL,
  `ma_data` text NOT NULL,
  `ma_plid` int(11) NOT NULL,
  `ma_date` datetime NOT NULL,
  `ma_reason` text NOT NULL,
  `ma_processed` tinyint(1) NOT NULL DEFAULT '0',
  `ma_executed` tinyint(1) DEFAULT NULL,
  `ma_target` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players`
--

CREATE TABLE `n_players` (
  `plid` int(11) NOT NULL,
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
  `p_score` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_admin_cleared`
--

CREATE TABLE `n_players_admin_cleared` (
  `pac_id` int(11) NOT NULL,
  `pac_plid1` int(11) NOT NULL,
  `pac_plid2` int(11) NOT NULL,
  `pac_reason` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_banned`
--

CREATE TABLE `n_players_banned` (
  `pb_id` int(11) NOT NULL,
  `plid` int(11) NOT NULL,
  `bp_channel` varchar(20) NOT NULL,
  `bp_end` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_guide`
--

CREATE TABLE `n_players_guide` (
  `pg_id` int(11) NOT NULL,
  `plid` int(11) NOT NULL,
  `pg_template` varchar(50) NOT NULL,
  `pg_character` varchar(20) NOT NULL,
  `pg_mood` varchar(20) NOT NULL,
  `pg_data` text NOT NULL,
  `pg_read` enum('0','1') NOT NULL,
  `pg_highlight` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_preferences`
--

CREATE TABLE `n_players_preferences` (
  `p_plid` int(11) NOT NULL,
  `p_key` varchar(15) NOT NULL,
  `p_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_quests`
--

CREATE TABLE `n_players_quests` (
  `pq_id` int(11) NOT NULL,
  `plid` int(11) NOT NULL,
  `q_id` int(11) NOT NULL,
  `q_finished` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_social`
--

CREATE TABLE `n_players_social` (
  `ps_plid` int(11) NOT NULL,
  `ps_targetid` int(11) NOT NULL,
  `ps_status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_players_update`
--

CREATE TABLE `n_players_update` (
  `pu_id` int(11) NOT NULL,
  `pu_plid` int(11) NOT NULL,
  `pu_key` varchar(20) NOT NULL,
  `pu_value` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_privatechat_updates`
--

CREATE TABLE `n_privatechat_updates` (
  `pu_id` int(11) NOT NULL,
  `pu_from` int(11) NOT NULL,
  `pu_to` int(11) NOT NULL,
  `c_m_id` int(11) NOT NULL,
  `pu_date` datetime NOT NULL,
  `pu_read` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_quests`
--

CREATE TABLE `n_quests` (
  `q_id` int(11) NOT NULL,
  `q_class` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_server_data`
--

CREATE TABLE `n_server_data` (
  `s_name` varchar(10) NOT NULL,
  `s_value` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_server_text`
--

CREATE TABLE `n_server_text` (
  `s_id` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `s_lang` varchar(5) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `s_value` text CHARACTER SET utf8 COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `n_temp_passwords`
--

CREATE TABLE `n_temp_passwords` (
  `p_id` int(11) NOT NULL,
  `p_plid` int(11) NOT NULL,
  `p_pass` varchar(8) NOT NULL,
  `p_expire` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `oid_associations`
--

CREATE TABLE `oid_associations` (
  `server_url` varchar(2047) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `secret` blob NOT NULL,
  `issued` int(11) NOT NULL,
  `lifetime` int(11) NOT NULL,
  `assoc_type` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `oid_nonces`
--

CREATE TABLE `oid_nonces` (
  `server_url` varchar(2047) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `salt` char(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `players_tiles`
--

CREATE TABLE `players_tiles` (
  `t_id` int(11) NOT NULL,
  `t_userid` int(11) NOT NULL,
  `t_imagename` varchar(50) NOT NULL,
  `t_isPublic` tinyint(1) NOT NULL DEFAULT '0',
  `t_startDate` datetime DEFAULT NULL,
  `t_endDate` datetime DEFAULT NULL,
  `t_description` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `premium_queue`
--

CREATE TABLE `premium_queue` (
  `pq_id` int(11) NOT NULL,
  `pq_vid` int(11) NOT NULL,
  `pq_action` varchar(10) NOT NULL,
  `pq_data` text NOT NULL,
  `pq_date` datetime NOT NULL,
  `pq_lastcheck` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `specialunits`
--

CREATE TABLE `specialunits` (
  `s_id` int(11) NOT NULL,
  `s_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `specialunits_effects`
--

CREATE TABLE `specialunits_effects` (
  `s_id` int(11) NOT NULL,
  `b_id` int(11) NOT NULL,
  `e_id` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `squad_commands`
--

CREATE TABLE `squad_commands` (
  `sc_id` int(11) NOT NULL,
  `s_id` int(11) NOT NULL,
  `s_action` enum('move') NOT NULL,
  `s_start` datetime NOT NULL,
  `s_end` datetime NOT NULL,
  `s_from` int(11) DEFAULT NULL,
  `s_to` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `squad_equipment`
--

CREATE TABLE `squad_equipment` (
  `se_id` int(11) NOT NULL,
  `s_id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `e_id` varchar(10) NOT NULL,
  `v_id` int(11) NOT NULL,
  `i_itid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `squad_units`
--

CREATE TABLE `squad_units` (
  `su_id` int(11) NOT NULL,
  `s_id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `s_amount` int(11) NOT NULL,
  `v_id` int(11) NOT NULL,
  `s_slotId` tinyint(4) NOT NULL DEFAULT '0',
  `s_priority` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `technology`
--

CREATE TABLE `technology` (
  `techId` int(11) NOT NULL,
  `techName` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_armies`
--

CREATE TABLE `underworld_armies` (
  `ua_id` int(11) NOT NULL,
  `um_id` int(11) NOT NULL,
  `ua_x` int(11) NOT NULL,
  `ua_y` int(11) NOT NULL,
  `ua_side` int(11) NOT NULL,
  `ua_lastrefresh` datetime NOT NULL,
  `ua_movepoints` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_armies_leaders`
--

CREATE TABLE `underworld_armies_leaders` (
  `ual_id` int(11) NOT NULL,
  `ua_id` int(11) NOT NULL,
  `plid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_armies_squads`
--

CREATE TABLE `underworld_armies_squads` (
  `uas_id` int(11) NOT NULL,
  `ua_id` int(11) NOT NULL,
  `s_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_checkpoints`
--

CREATE TABLE `underworld_checkpoints` (
  `uc_id` int(11) NOT NULL,
  `uc_x` int(11) NOT NULL,
  `uc_y` int(11) NOT NULL,
  `uc_side` int(11) NOT NULL,
  `uc_date` datetime NOT NULL,
  `um_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_explored`
--

CREATE TABLE `underworld_explored` (
  `ue_id` int(11) NOT NULL,
  `um_id` int(11) NOT NULL,
  `ue_side` int(11) NOT NULL,
  `ue_x` int(11) NOT NULL,
  `ue_y` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_log_armies`
--

CREATE TABLE `underworld_log_armies` (
  `ul_a_vid` int(11) NOT NULL,
  `ul_a_id` int(11) NOT NULL,
  `ul_a_version` int(11) NOT NULL DEFAULT '0',
  `ua_id` int(11) NOT NULL,
  `ul_a_squads` text NOT NULL,
  `ul_a_side` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_log_armies_leaders`
--

CREATE TABLE `underworld_log_armies_leaders` (
  `ul_a_vid` int(11) NOT NULL,
  `plid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_log_battles`
--

CREATE TABLE `underworld_log_battles` (
  `uat_id` int(11) NOT NULL,
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
  `uat_defender_side` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_log_clans`
--

CREATE TABLE `underworld_log_clans` (
  `us_id` int(11) NOT NULL,
  `um_id` int(11) NOT NULL,
  `us_clan` int(11) NOT NULL,
  `us_side` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_log_event`
--

CREATE TABLE `underworld_log_event` (
  `ul_e_id` int(11) NOT NULL,
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
  `ul_side` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_log_mission`
--

CREATE TABLE `underworld_log_mission` (
  `ul_m_id` int(11) NOT NULL,
  `um_id` int(11) DEFAULT NULL,
  `ul_m_map` varchar(20) NOT NULL,
  `ul_m_mission` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_missions`
--

CREATE TABLE `underworld_missions` (
  `um_id` int(11) NOT NULL,
  `um_map` varchar(20) NOT NULL,
  `um_mission` varchar(20) NOT NULL,
  `um_global` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_missions_clans`
--

CREATE TABLE `underworld_missions_clans` (
  `umc_id` int(11) NOT NULL,
  `um_id` int(11) NOT NULL,
  `c_id` int(11) NOT NULL,
  `umc_side` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `underworld_score`
--

CREATE TABLE `underworld_score` (
  `us_id` int(11) NOT NULL,
  `um_id` int(11) NOT NULL,
  `us_side` int(11) NOT NULL,
  `us_score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `units`
--

CREATE TABLE `units` (
  `unitId` int(11) NOT NULL,
  `unitName` varchar(20) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages`
--

CREATE TABLE `villages` (
  `vid` int(11) NOT NULL,
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
  `removalDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_blevel`
--

CREATE TABLE `villages_blevel` (
  `vid` int(11) NOT NULL DEFAULT '0',
  `bid` int(11) NOT NULL DEFAULT '0',
  `lvl` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_counters`
--

CREATE TABLE `villages_counters` (
  `c_id` int(11) NOT NULL,
  `vid` int(11) NOT NULL,
  `c_start` int(11) NOT NULL,
  `c_end` int(11) NOT NULL,
  `c_text` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_itemlevels`
--

CREATE TABLE `villages_itemlevels` (
  `v_id` int(11) NOT NULL,
  `e_id` int(11) NOT NULL,
  `vi_level` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_items`
--

CREATE TABLE `villages_items` (
  `i_id` int(11) NOT NULL,
  `vid` int(11) NOT NULL,
  `i_itemId` varchar(10) NOT NULL,
  `i_amount` int(11) NOT NULL,
  `i_startCraft` int(11) NOT NULL,
  `i_endCraft` int(11) NOT NULL,
  `i_removed` int(11) NOT NULL,
  `i_buildingId` int(11) NOT NULL,
  `i_bid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_morale`
--

CREATE TABLE `villages_morale` (
  `m_id` int(11) NOT NULL,
  `m_vid` int(11) NOT NULL,
  `m_amount` tinyint(4) NOT NULL,
  `m_start` datetime NOT NULL,
  `m_end` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_runes`
--

CREATE TABLE `villages_runes` (
  `vid` int(11) NOT NULL DEFAULT '0',
  `runeId` varchar(10) NOT NULL DEFAULT '',
  `amount` int(11) NOT NULL DEFAULT '0',
  `usedRunes` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_scouting`
--

CREATE TABLE `villages_scouting` (
  `scoutId` int(11) NOT NULL,
  `vid` int(11) NOT NULL DEFAULT '0',
  `finishDate` int(11) NOT NULL DEFAULT '0',
  `runes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_slots`
--

CREATE TABLE `villages_slots` (
  `vs_vid` int(11) NOT NULL,
  `vs_slot` tinyint(4) NOT NULL,
  `vs_slotId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_specialunits`
--

CREATE TABLE `villages_specialunits` (
  `vsu_id` int(11) NOT NULL,
  `v_id` int(11) NOT NULL,
  `vsu_bid` int(11) NOT NULL,
  `vsu_tStartDate` int(11) NOT NULL,
  `vsu_tEndDate` int(11) NOT NULL,
  `vsu_location` int(11) DEFAULT NULL,
  `vsu_moveStart` datetime DEFAULT NULL,
  `vsu_moveEnd` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_squads`
--

CREATE TABLE `villages_squads` (
  `s_id` int(11) NOT NULL,
  `v_id` int(11) NOT NULL,
  `v_type` int(11) NOT NULL,
  `s_name` varchar(20) NOT NULL,
  `s_village` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_tech`
--

CREATE TABLE `villages_tech` (
  `id` int(11) NOT NULL,
  `vid` int(11) NOT NULL,
  `techId` tinyint(4) NOT NULL,
  `startDate` int(11) NOT NULL,
  `endDate` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_transfers`
--

CREATE TABLE `villages_transfers` (
  `t_id` int(11) NOT NULL,
  `from_vid` int(11) NOT NULL,
  `to_vid` int(11) NOT NULL,
  `t_date_sent` datetime NOT NULL,
  `t_date_received` datetime NOT NULL,
  `t_isReceived` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_transfers_items`
--

CREATE TABLE `villages_transfers_items` (
  `ti_id` int(11) NOT NULL,
  `t_id` int(11) NOT NULL,
  `ti_type` enum('RESOURCE','RUNE','EQUIPMENT') NOT NULL,
  `ti_key` varchar(20) NOT NULL,
  `ti_amount` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_units`
--

CREATE TABLE `villages_units` (
  `uid` int(11) NOT NULL,
  `vid` int(11) NOT NULL DEFAULT '0',
  `unitId` int(11) NOT NULL DEFAULT '0',
  `buildingId` int(11) NOT NULL DEFAULT '0',
  `village` int(11) NOT NULL DEFAULT '0',
  `amount` int(11) NOT NULL DEFAULT '0',
  `startTraining` int(11) NOT NULL DEFAULT '0',
  `endTraining` int(11) NOT NULL DEFAULT '0',
  `killedAmount` int(11) NOT NULL DEFAULT '0',
  `bid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `villages_visits`
--

CREATE TABLE `villages_visits` (
  `vi_id` int(11) NOT NULL,
  `v_id` int(11) NOT NULL,
  `vi_v_id` int(11) NOT NULL,
  `vi_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `battle`
--
ALTER TABLE `battle`
  ADD PRIMARY KEY (`battleId`),
  ADD KEY `vid` (`vid`),
  ADD KEY `targetId` (`targetId`);

--
-- Indexen voor tabel `battle_report`
--
ALTER TABLE `battle_report`
  ADD PRIMARY KEY (`reportId`),
  ADD KEY `battleId` (`battleId`),
  ADD KEY `fromId` (`fromId`),
  ADD KEY `targetId` (`targetId`);

--
-- Indexen voor tabel `battle_specialunits`
--
ALTER TABLE `battle_specialunits`
  ADD PRIMARY KEY (`bsu_id`),
  ADD KEY `bsu_bid` (`bsu_bid`);

--
-- Indexen voor tabel `battle_squads`
--
ALTER TABLE `battle_squads`
  ADD PRIMARY KEY (`bs_id`),
  ADD UNIQUE KEY `bs_bid` (`bs_bid`,`bs_squadId`,`bs_unitId`),
  ADD KEY `bs_bid_2` (`bs_bid`);

--
-- Indexen voor tabel `bonus_buildings`
--
ALTER TABLE `bonus_buildings`
  ADD PRIMARY KEY (`b_id`);

--
-- Indexen voor tabel `boosts`
--
ALTER TABLE `boosts`
  ADD PRIMARY KEY (`b_id`),
  ADD KEY `b_targetId` (`b_targetId`),
  ADD KEY `b_fromId` (`b_fromId`);

--
-- Indexen voor tabel `clans`
--
ALTER TABLE `clans`
  ADD PRIMARY KEY (`c_id`);

--
-- Indexen voor tabel `clan_members`
--
ALTER TABLE `clan_members`
  ADD PRIMARY KEY (`cm_id`),
  ADD KEY `plid` (`plid`);

--
-- Indexen voor tabel `effects`
--
ALTER TABLE `effects`
  ADD PRIMARY KEY (`e_id`);

--
-- Indexen voor tabel `effect_report`
--
ALTER TABLE `effect_report`
  ADD PRIMARY KEY (`er_id`);

--
-- Indexen voor tabel `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`e_id`);

--
-- Indexen voor tabel `forum_bans`
--
ALTER TABLE `forum_bans`
  ADD KEY `ID` (`ID`);

--
-- Indexen voor tabel `forum_boards`
--
ALTER TABLE `forum_boards`
  ADD KEY `ID` (`ID`);

--
-- Indexen voor tabel `forum_forums`
--
ALTER TABLE `forum_forums`
  ADD KEY `ID` (`ID`);

--
-- Indexen voor tabel `forum_modlog`
--
ALTER TABLE `forum_modlog`
  ADD KEY `ID` (`ID`);

--
-- Indexen voor tabel `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`ID`);

--
-- Indexen voor tabel `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD KEY `ID` (`ID`);

--
-- Indexen voor tabel `game_log`
--
ALTER TABLE `game_log`
  ADD PRIMARY KEY (`l_id`),
  ADD KEY `l_vid` (`l_vid`);

--
-- Indexen voor tabel `game_log_scouts`
--
ALTER TABLE `game_log_scouts`
  ADD PRIMARY KEY (`ls_id`);

--
-- Indexen voor tabel `game_log_training`
--
ALTER TABLE `game_log_training`
  ADD PRIMARY KEY (`lt_id`);

--
-- Indexen voor tabel `map_buildings`
--
ALTER TABLE `map_buildings`
  ADD PRIMARY KEY (`bid`),
  ADD KEY `xas` (`xas`,`yas`),
  ADD KEY `village` (`village`),
  ADD KEY `buildingType` (`buildingType`);

--
-- Indexen voor tabel `map_portals`
--
ALTER TABLE `map_portals`
  ADD PRIMARY KEY (`p_id`);

--
-- Indexen voor tabel `n_auth_openid`
--
ALTER TABLE `n_auth_openid`
  ADD PRIMARY KEY (`openid_url`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexen voor tabel `n_chat_channels`
--
ALTER TABLE `n_chat_channels`
  ADD PRIMARY KEY (`c_c_id`);

--
-- Indexen voor tabel `n_chat_messages`
--
ALTER TABLE `n_chat_messages`
  ADD PRIMARY KEY (`c_m_id`),
  ADD KEY `c_c_id` (`c_c_id`);

--
-- Indexen voor tabel `n_locks`
--
ALTER TABLE `n_locks`
  ADD PRIMARY KEY (`l_id`),
  ADD KEY `l_type` (`l_type`,`l_lid`);

--
-- Indexen voor tabel `n_logables`
--
ALTER TABLE `n_logables`
  ADD PRIMARY KEY (`l_id`),
  ADD UNIQUE KEY `l_name` (`l_name`);

--
-- Indexen voor tabel `n_login_failures`
--
ALTER TABLE `n_login_failures`
  ADD PRIMARY KEY (`l_id`);

--
-- Indexen voor tabel `n_login_log`
--
ALTER TABLE `n_login_log`
  ADD PRIMARY KEY (`l_id`),
  ADD KEY `l_plid` (`l_plid`);

--
-- Indexen voor tabel `n_map_updates`
--
ALTER TABLE `n_map_updates`
  ADD PRIMARY KEY (`mu_id`);

--
-- Indexen voor tabel `n_mod_actions`
--
ALTER TABLE `n_mod_actions`
  ADD PRIMARY KEY (`ma_id`);

--
-- Indexen voor tabel `n_players`
--
ALTER TABLE `n_players`
  ADD PRIMARY KEY (`plid`),
  ADD KEY `nickname` (`nickname`);

--
-- Indexen voor tabel `n_players_admin_cleared`
--
ALTER TABLE `n_players_admin_cleared`
  ADD PRIMARY KEY (`pac_id`),
  ADD KEY `pac_plid1` (`pac_plid1`,`pac_plid2`);

--
-- Indexen voor tabel `n_players_banned`
--
ALTER TABLE `n_players_banned`
  ADD PRIMARY KEY (`pb_id`);

--
-- Indexen voor tabel `n_players_guide`
--
ALTER TABLE `n_players_guide`
  ADD PRIMARY KEY (`pg_id`);

--
-- Indexen voor tabel `n_players_preferences`
--
ALTER TABLE `n_players_preferences`
  ADD PRIMARY KEY (`p_plid`,`p_key`);

--
-- Indexen voor tabel `n_players_quests`
--
ALTER TABLE `n_players_quests`
  ADD PRIMARY KEY (`pq_id`);

--
-- Indexen voor tabel `n_players_social`
--
ALTER TABLE `n_players_social`
  ADD PRIMARY KEY (`ps_plid`,`ps_targetid`);

--
-- Indexen voor tabel `n_players_update`
--
ALTER TABLE `n_players_update`
  ADD PRIMARY KEY (`pu_id`),
  ADD KEY `pu_plid` (`pu_plid`),
  ADD KEY `pu_key` (`pu_key`);

--
-- Indexen voor tabel `n_privatechat_updates`
--
ALTER TABLE `n_privatechat_updates`
  ADD PRIMARY KEY (`pu_id`);

--
-- Indexen voor tabel `n_quests`
--
ALTER TABLE `n_quests`
  ADD PRIMARY KEY (`q_id`),
  ADD UNIQUE KEY `q_class` (`q_class`);

--
-- Indexen voor tabel `n_server_data`
--
ALTER TABLE `n_server_data`
  ADD PRIMARY KEY (`s_name`);

--
-- Indexen voor tabel `n_server_text`
--
ALTER TABLE `n_server_text`
  ADD PRIMARY KEY (`s_id`,`s_lang`);

--
-- Indexen voor tabel `n_temp_passwords`
--
ALTER TABLE `n_temp_passwords`
  ADD PRIMARY KEY (`p_id`),
  ADD KEY `p_plid` (`p_plid`);

--
-- Indexen voor tabel `oid_associations`
--
ALTER TABLE `oid_associations`
  ADD PRIMARY KEY (`server_url`(255),`handle`);

--
-- Indexen voor tabel `oid_nonces`
--
ALTER TABLE `oid_nonces`
  ADD UNIQUE KEY `server_url` (`server_url`(255),`timestamp`,`salt`);

--
-- Indexen voor tabel `players_tiles`
--
ALTER TABLE `players_tiles`
  ADD PRIMARY KEY (`t_id`);

--
-- Indexen voor tabel `premium_queue`
--
ALTER TABLE `premium_queue`
  ADD PRIMARY KEY (`pq_id`),
  ADD KEY `pq_vid` (`pq_vid`);

--
-- Indexen voor tabel `specialunits`
--
ALTER TABLE `specialunits`
  ADD PRIMARY KEY (`s_id`);

--
-- Indexen voor tabel `specialunits_effects`
--
ALTER TABLE `specialunits_effects`
  ADD PRIMARY KEY (`s_id`),
  ADD KEY `b_id` (`b_id`);

--
-- Indexen voor tabel `squad_commands`
--
ALTER TABLE `squad_commands`
  ADD PRIMARY KEY (`sc_id`),
  ADD KEY `s_id` (`s_id`);

--
-- Indexen voor tabel `squad_equipment`
--
ALTER TABLE `squad_equipment`
  ADD PRIMARY KEY (`se_id`);

--
-- Indexen voor tabel `squad_units`
--
ALTER TABLE `squad_units`
  ADD PRIMARY KEY (`su_id`),
  ADD UNIQUE KEY `s_id` (`s_id`,`u_id`);

--
-- Indexen voor tabel `technology`
--
ALTER TABLE `technology`
  ADD PRIMARY KEY (`techId`),
  ADD UNIQUE KEY `techName` (`techName`);

--
-- Indexen voor tabel `underworld_armies`
--
ALTER TABLE `underworld_armies`
  ADD PRIMARY KEY (`ua_id`);

--
-- Indexen voor tabel `underworld_armies_leaders`
--
ALTER TABLE `underworld_armies_leaders`
  ADD PRIMARY KEY (`ual_id`),
  ADD KEY `ua_id` (`ua_id`),
  ADD KEY `plid` (`plid`);

--
-- Indexen voor tabel `underworld_armies_squads`
--
ALTER TABLE `underworld_armies_squads`
  ADD PRIMARY KEY (`uas_id`),
  ADD KEY `ua_id` (`ua_id`),
  ADD KEY `s_id` (`s_id`);

--
-- Indexen voor tabel `underworld_checkpoints`
--
ALTER TABLE `underworld_checkpoints`
  ADD PRIMARY KEY (`uc_id`),
  ADD UNIQUE KEY `uc_x` (`uc_x`,`uc_y`,`um_id`),
  ADD KEY `um_id` (`um_id`);

--
-- Indexen voor tabel `underworld_explored`
--
ALTER TABLE `underworld_explored`
  ADD PRIMARY KEY (`ue_id`);

--
-- Indexen voor tabel `underworld_log_armies`
--
ALTER TABLE `underworld_log_armies`
  ADD PRIMARY KEY (`ul_a_vid`),
  ADD UNIQUE KEY `ul_a_id_2` (`ul_a_id`,`ul_a_version`),
  ADD KEY `ua_id` (`ua_id`),
  ADD KEY `ul_a_version` (`ul_a_version`),
  ADD KEY `ul_a_id` (`ul_a_id`);

--
-- Indexen voor tabel `underworld_log_armies_leaders`
--
ALTER TABLE `underworld_log_armies_leaders`
  ADD KEY `plid` (`plid`),
  ADD KEY `ul_a_vid` (`ul_a_vid`);

--
-- Indexen voor tabel `underworld_log_battles`
--
ALTER TABLE `underworld_log_battles`
  ADD PRIMARY KEY (`uat_id`),
  ADD KEY `uat_defender` (`uat_defender`),
  ADD KEY `uat_attacker` (`uat_attacker`),
  ADD KEY `um_id` (`um_id`);

--
-- Indexen voor tabel `underworld_log_clans`
--
ALTER TABLE `underworld_log_clans`
  ADD PRIMARY KEY (`us_id`),
  ADD KEY `us_clan` (`us_clan`),
  ADD KEY `um_id` (`um_id`);

--
-- Indexen voor tabel `underworld_log_event`
--
ALTER TABLE `underworld_log_event`
  ADD PRIMARY KEY (`ul_e_id`),
  ADD UNIQUE KEY `uat_id` (`uat_id`),
  ADD KEY `ul_a_id` (`ul_a_vid`),
  ADD KEY `ul_m_id` (`ul_m_id`),
  ADD KEY `plid` (`plid`),
  ADD KEY `ul_a2_id` (`ul_a2_vid`);

--
-- Indexen voor tabel `underworld_log_mission`
--
ALTER TABLE `underworld_log_mission`
  ADD PRIMARY KEY (`ul_m_id`),
  ADD KEY `um_id` (`um_id`);

--
-- Indexen voor tabel `underworld_missions`
--
ALTER TABLE `underworld_missions`
  ADD PRIMARY KEY (`um_id`);

--
-- Indexen voor tabel `underworld_missions_clans`
--
ALTER TABLE `underworld_missions_clans`
  ADD PRIMARY KEY (`umc_id`),
  ADD KEY `um_id` (`um_id`),
  ADD KEY `c_id` (`c_id`);

--
-- Indexen voor tabel `underworld_score`
--
ALTER TABLE `underworld_score`
  ADD PRIMARY KEY (`us_id`),
  ADD UNIQUE KEY `um_id_2` (`um_id`,`us_side`),
  ADD KEY `um_id` (`um_id`);

--
-- Indexen voor tabel `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`unitId`),
  ADD UNIQUE KEY `unitName` (`unitName`);

--
-- Indexen voor tabel `villages`
--
ALTER TABLE `villages`
  ADD PRIMARY KEY (`vid`),
  ADD KEY `plid` (`plid`),
  ADD KEY `vname` (`vname`);

--
-- Indexen voor tabel `villages_blevel`
--
ALTER TABLE `villages_blevel`
  ADD PRIMARY KEY (`vid`,`bid`);

--
-- Indexen voor tabel `villages_counters`
--
ALTER TABLE `villages_counters`
  ADD PRIMARY KEY (`c_id`),
  ADD KEY `vid` (`vid`);

--
-- Indexen voor tabel `villages_itemlevels`
--
ALTER TABLE `villages_itemlevels`
  ADD PRIMARY KEY (`v_id`,`e_id`);

--
-- Indexen voor tabel `villages_items`
--
ALTER TABLE `villages_items`
  ADD PRIMARY KEY (`i_id`),
  ADD KEY `vid` (`vid`);

--
-- Indexen voor tabel `villages_morale`
--
ALTER TABLE `villages_morale`
  ADD PRIMARY KEY (`m_id`),
  ADD KEY `m_vid` (`m_vid`);

--
-- Indexen voor tabel `villages_runes`
--
ALTER TABLE `villages_runes`
  ADD PRIMARY KEY (`vid`,`runeId`);

--
-- Indexen voor tabel `villages_scouting`
--
ALTER TABLE `villages_scouting`
  ADD PRIMARY KEY (`scoutId`),
  ADD UNIQUE KEY `vid` (`vid`,`finishDate`);

--
-- Indexen voor tabel `villages_slots`
--
ALTER TABLE `villages_slots`
  ADD PRIMARY KEY (`vs_vid`,`vs_slot`);

--
-- Indexen voor tabel `villages_specialunits`
--
ALTER TABLE `villages_specialunits`
  ADD PRIMARY KEY (`vsu_id`),
  ADD KEY `v_id` (`v_id`);

--
-- Indexen voor tabel `villages_squads`
--
ALTER TABLE `villages_squads`
  ADD PRIMARY KEY (`s_id`),
  ADD KEY `v_id` (`v_id`);

--
-- Indexen voor tabel `villages_tech`
--
ALTER TABLE `villages_tech`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vid` (`vid`);

--
-- Indexen voor tabel `villages_transfers`
--
ALTER TABLE `villages_transfers`
  ADD PRIMARY KEY (`t_id`),
  ADD KEY `from_vid` (`from_vid`),
  ADD KEY `to_vid` (`to_vid`);

--
-- Indexen voor tabel `villages_transfers_items`
--
ALTER TABLE `villages_transfers_items`
  ADD PRIMARY KEY (`ti_id`),
  ADD KEY `t_id` (`t_id`);

--
-- Indexen voor tabel `villages_units`
--
ALTER TABLE `villages_units`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `vid` (`vid`,`village`),
  ADD KEY `village` (`village`);

--
-- Indexen voor tabel `villages_visits`
--
ALTER TABLE `villages_visits`
  ADD PRIMARY KEY (`vi_id`),
  ADD KEY `v_id` (`v_id`,`vi_v_id`);

--
-- Beperkingen voor geëxporteerde tabellen
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