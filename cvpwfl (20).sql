-- phpMyAdmin SQL Dump
-- version 4.9.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 13, 2025 at 08:08 PM
-- Server version: 10.4.10-MariaDB
-- PHP Version: 7.3.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cvpwfl`
--

-- --------------------------------------------------------

--
-- Table structure for table `awards`
--

DROP TABLE IF EXISTS `awards`;
CREATE TABLE IF NOT EXISTS `awards` (
  `awardID` int(11) NOT NULL AUTO_INCREMENT,
  `seasonID` int(11) DEFAULT NULL,
  `award_name` varchar(50) DEFAULT NULL,
  `playerID` int(11) DEFAULT NULL,
  `teamID` int(11) DEFAULT NULL,
  PRIMARY KEY (`awardID`),
  KEY `seasonID` (`seasonID`),
  KEY `playerID` (`playerID`),
  KEY `teamID` (`teamID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `draft_picks`
--

DROP TABLE IF EXISTS `draft_picks`;
CREATE TABLE IF NOT EXISTS `draft_picks` (
  `autoID` int(11) NOT NULL AUTO_INCREMENT,
  `dpteamID` int(5) NOT NULL DEFAULT 0,
  `dpseasonID` int(4) NOT NULL DEFAULT 0,
  `dpround` tinyint(3) NOT NULL DEFAULT 0,
  `dpslot` tinyint(2) NOT NULL DEFAULT 0,
  `dpmade` tinyint(1) NOT NULL DEFAULT 0,
  `playerID` int(11) DEFAULT NULL,
  PRIMARY KEY (`autoID`),
  KEY `dpteamID` (`dpteamID`),
  KEY `dpseasonID` (`dpseasonID`),
  KEY `playerID` (`playerID`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `draft_picks`
--

INSERT INTO `draft_picks` (`autoID`, `dpteamID`, `dpseasonID`, `dpround`, `dpslot`, `dpmade`, `playerID`) VALUES
(1, 2, 2025, 1, 1, 1, 68),
(2, 3, 2025, 1, 2, 1, 97),
(3, 1, 2025, 1, 3, 1, 102),
(4, 4, 2025, 1, 4, 1, 114),
(5, 2, 2025, 2, 1, 1, 99),
(6, 3, 2025, 2, 2, 1, 109),
(7, 1, 2025, 2, 3, 1, 101),
(8, 4, 2025, 2, 4, 1, 70),
(9, 3, 2025, 3, 1, 1, 103),
(10, 1, 2025, 3, 2, 1, 104),
(11, 4, 2025, 3, 3, 1, 69),
(12, 3, 2025, 4, 1, 1, 106),
(13, 1, 2025, 4, 2, 1, 72),
(14, 3, 2025, 5, 1, 1, 107),
(15, 1, 2025, 5, 2, 1, 115),
(16, 3, 2025, 6, 1, 1, 98),
(17, 1, 2025, 6, 2, 1, 105),
(18, 1, 2025, 7, 1, 1, 100);

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

DROP TABLE IF EXISTS `games`;
CREATE TABLE IF NOT EXISTS `games` (
  `gameID` int(11) NOT NULL,
  `seasonID` int(11) DEFAULT NULL,
  `week` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `teamID` int(11) NOT NULL,
  `home` tinyint(1) DEFAULT 0,
  `winner` tinyint(1) DEFAULT 0,
  `score_final` int(11) DEFAULT 0,
  `score_qtr1` int(11) DEFAULT 0,
  `score_qtr2` int(11) DEFAULT 0,
  `score_qtr3` int(11) DEFAULT 0,
  `score_qtr4` int(11) DEFAULT 0,
  `score_qtr5` int(11) DEFAULT 0,
  `penalties` int(11) DEFAULT 0,
  `penalty_yards` int(11) DEFAULT 0,
  `d1_clock` time DEFAULT NULL,
  `d1_qtr` tinyint(1) DEFAULT NULL,
  `d1_start` tinyint(3) DEFAULT NULL,
  `d2_clock` time DEFAULT NULL,
  `d2_qtr` tinyint(1) DEFAULT NULL,
  `d2_start` tinyint(3) DEFAULT NULL,
  `d3_clock` time DEFAULT NULL,
  `d3_qtr` tinyint(1) DEFAULT NULL,
  `d3_start` tinyint(3) DEFAULT NULL,
  `d4_clock` time DEFAULT NULL,
  `d4_qtr` tinyint(1) DEFAULT NULL,
  `d4_start` tinyint(3) DEFAULT NULL,
  `d5_clock` time DEFAULT NULL,
  `d5_qtr` tinyint(1) DEFAULT NULL,
  `d5_start` tinyint(3) DEFAULT NULL,
  `d6_clock` time DEFAULT NULL,
  `d6_qtr` tinyint(1) DEFAULT NULL,
  `d6_start` tinyint(3) DEFAULT NULL,
  `d7_clock` time DEFAULT NULL,
  `d7_qtr` tinyint(1) DEFAULT NULL,
  `d7_start` tinyint(3) DEFAULT NULL,
  `d8_clock` time DEFAULT NULL,
  `d8_qtr` tinyint(1) DEFAULT NULL,
  `d8_start` tinyint(3) DEFAULT NULL,
  `d9_clock` time DEFAULT NULL,
  `d9_qtr` tinyint(1) DEFAULT NULL,
  `d9_start` tinyint(3) DEFAULT NULL,
  `d10_clock` time DEFAULT NULL,
  `d10_qtr` tinyint(1) DEFAULT NULL,
  `d10_start` tinyint(3) DEFAULT NULL,
  `d11_clock` time DEFAULT NULL,
  `d11_qtr` tinyint(1) DEFAULT NULL,
  `d11_start` tinyint(3) DEFAULT NULL,
  `d12_clock` time DEFAULT NULL,
  `d12_qtr` tinyint(1) DEFAULT NULL,
  `d12_start` tinyint(3) DEFAULT NULL,
  `current_drive` tinyint(4) DEFAULT NULL,
  `current_position` tinyint(4) DEFAULT NULL,
  `current_qtr` tinyint(4) DEFAULT NULL,
  `d1_clock_end` time DEFAULT NULL,
  `d1_qtr_end` tinyint(4) DEFAULT NULL,
  `d2_clock_end` time DEFAULT NULL,
  `d2_qtr_end` tinyint(4) DEFAULT NULL,
  `d3_clock_end` time DEFAULT NULL,
  `d3_qtr_end` tinyint(4) DEFAULT NULL,
  `d4_clock_end` time DEFAULT NULL,
  `d4_qtr_end` tinyint(4) DEFAULT NULL,
  `d5_clock_end` time DEFAULT NULL,
  `d5_qtr_end` tinyint(4) DEFAULT NULL,
  `d6_clock_end` time DEFAULT NULL,
  `d6_qtr_end` tinyint(4) DEFAULT NULL,
  `d7_clock_end` time DEFAULT NULL,
  `d7_qtr_end` tinyint(4) DEFAULT NULL,
  `d8_clock_end` time DEFAULT NULL,
  `d8_qtr_end` tinyint(4) DEFAULT NULL,
  `d9_clock_end` time DEFAULT NULL,
  `d9_qtr_end` tinyint(4) DEFAULT NULL,
  `d10_clock_end` time DEFAULT NULL,
  `d10_qtr_end` tinyint(4) DEFAULT NULL,
  `d11_clock_end` time DEFAULT NULL,
  `d11_qtr_end` tinyint(4) DEFAULT NULL,
  `d12_clock_end` time DEFAULT NULL,
  `d12_qtr_end` tinyint(4) DEFAULT NULL,
  `d1_fp_end` tinyint(3) DEFAULT NULL,
  `d2_fp_end` tinyint(3) DEFAULT NULL,
  `d3_fp_end` tinyint(3) DEFAULT NULL,
  `d4_fp_end` tinyint(3) DEFAULT NULL,
  `d5_fp_end` tinyint(3) DEFAULT NULL,
  `d6_fp_end` tinyint(3) DEFAULT NULL,
  `d7_fp_end` tinyint(3) DEFAULT NULL,
  `d8_fp_end` tinyint(3) DEFAULT NULL,
  `d9_fp_end` tinyint(3) DEFAULT NULL,
  `d10_fp_end` tinyint(3) DEFAULT NULL,
  `d11_fp_end` tinyint(3) DEFAULT NULL,
  `d12_fp_end` tinyint(3) DEFAULT NULL,
  PRIMARY KEY (`gameID`,`teamID`),
  KEY `seasonID` (`seasonID`),
  KEY `teamID` (`teamID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`gameID`, `seasonID`, `week`, `date`, `teamID`, `home`, `winner`, `score_final`, `score_qtr1`, `score_qtr2`, `score_qtr3`, `score_qtr4`, `score_qtr5`, `penalties`, `penalty_yards`, `d1_clock`, `d1_qtr`, `d1_start`, `d2_clock`, `d2_qtr`, `d2_start`, `d3_clock`, `d3_qtr`, `d3_start`, `d4_clock`, `d4_qtr`, `d4_start`, `d5_clock`, `d5_qtr`, `d5_start`, `d6_clock`, `d6_qtr`, `d6_start`, `d7_clock`, `d7_qtr`, `d7_start`, `d8_clock`, `d8_qtr`, `d8_start`, `d9_clock`, `d9_qtr`, `d9_start`, `d10_clock`, `d10_qtr`, `d10_start`, `d11_clock`, `d11_qtr`, `d11_start`, `d12_clock`, `d12_qtr`, `d12_start`, `current_drive`, `current_position`, `current_qtr`, `d1_clock_end`, `d1_qtr_end`, `d2_clock_end`, `d2_qtr_end`, `d3_clock_end`, `d3_qtr_end`, `d4_clock_end`, `d4_qtr_end`, `d5_clock_end`, `d5_qtr_end`, `d6_clock_end`, `d6_qtr_end`, `d7_clock_end`, `d7_qtr_end`, `d8_clock_end`, `d8_qtr_end`, `d9_clock_end`, `d9_qtr_end`, `d10_clock_end`, `d10_qtr_end`, `d11_clock_end`, `d11_qtr_end`, `d12_clock_end`, `d12_qtr_end`, `d1_fp_end`, `d2_fp_end`, `d3_fp_end`, `d4_fp_end`, `d5_fp_end`, `d6_fp_end`, `d7_fp_end`, `d8_fp_end`, `d9_fp_end`, `d10_fp_end`, `d11_fp_end`, `d12_fp_end`) VALUES
(1, 2025, 1, '2025-09-05 11:00:00', 2, 1, 0, 7, 7, 0, 0, 0, 0, 1, 5, '00:06:00', 1, -35, '00:04:19', 1, -45, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 100, 1, '00:05:41', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(1, 2025, 1, '2025-09-05 11:00:00', 3, 0, 0, 7, 7, 0, 0, 0, 0, 1, 10, '00:05:41', 1, -25, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '00:04:19', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2025, 1, '2025-09-05 12:00:00', 1, 0, 0, 6, 6, 0, 0, 0, 0, 0, 0, '00:06:00', 1, -35, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '00:03:33', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2025, 1, '2025-09-05 12:00:00', 4, 1, 0, 7, 7, 0, 0, 0, 0, 0, 0, '00:03:33', 1, -35, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '00:01:11', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `league`
--

DROP TABLE IF EXISTS `league`;
CREATE TABLE IF NOT EXISTS `league` (
  `lid` int(11) NOT NULL AUTO_INCREMENT,
  `luCommish` int(4) UNSIGNED DEFAULT NULL,
  `lCurrentSeason` int(4) UNSIGNED DEFAULT 0,
  `lCurrentWeek` int(3) UNSIGNED DEFAULT 0,
  `lPassword` varchar(20) DEFAULT NULL,
  `curRd` tinyint(1) NOT NULL DEFAULT 0,
  `curSlot` tinyint(2) NOT NULL DEFAULT 0,
  `draft_time` datetime DEFAULT NULL,
  `draft_team` int(6) NOT NULL DEFAULT 0,
  `seasonMode` enum('season','offseason') DEFAULT 'offseason',
  `dCamp` datetime DEFAULT NULL,
  `dWeighin1` datetime DEFAULT NULL,
  `dWeighin2` datetime DEFAULT NULL,
  `dEquipment` datetime DEFAULT NULL,
  `dPractice` datetime DEFAULT NULL,
  `dWeek1` date DEFAULT NULL,
  `dWeek2` date DEFAULT NULL,
  `dWeek3` date DEFAULT NULL,
  `dWeek4` date DEFAULT NULL,
  `dWeek5` date DEFAULT NULL,
  `dWeek6` date DEFAULT NULL,
  `dSatNightGame` tinyint(1) DEFAULT NULL,
  `dBanquet` datetime DEFAULT NULL,
  `dWeek7` date DEFAULT NULL,
  `w1openingceremony` time DEFAULT NULL,
  `w1flag` time DEFAULT NULL,
  `w1third` time DEFAULT NULL,
  `w1game1` time DEFAULT NULL,
  `w1game2` time DEFAULT NULL,
  `w2flag` time DEFAULT NULL,
  `w2third` time DEFAULT NULL,
  `w2game1` time DEFAULT NULL,
  `w2game2` time DEFAULT NULL,
  `w3flag` time DEFAULT NULL,
  `w3third` time DEFAULT NULL,
  `w3game1` time DEFAULT NULL,
  `w3game2` time DEFAULT NULL,
  `w4flag` time DEFAULT NULL,
  `w4third` time DEFAULT NULL,
  `w4game1` time DEFAULT NULL,
  `w4game2` time DEFAULT NULL,
  `w5flag` time DEFAULT NULL,
  `w5third` time DEFAULT NULL,
  `w5game1` time DEFAULT NULL,
  `w5game2` time DEFAULT NULL,
  `w6flag` time DEFAULT NULL,
  `w6third` time DEFAULT NULL,
  `w6game1` time DEFAULT NULL,
  `w6game2` time DEFAULT NULL,
  `w7playoff` time DEFAULT NULL,
  PRIMARY KEY (`lid`),
  KEY `luCommish` (`luCommish`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `league`
--

INSERT INTO `league` (`lid`, `luCommish`, `lCurrentSeason`, `lCurrentWeek`, `lPassword`, `curRd`, `curSlot`, `draft_time`, `draft_team`, `seasonMode`, `dCamp`, `dWeighin1`, `dWeighin2`, `dEquipment`, `dPractice`, `dWeek1`, `dWeek2`, `dWeek3`, `dWeek4`, `dWeek5`, `dWeek6`, `dSatNightGame`, `dBanquet`, `dWeek7`, `w1openingceremony`, `w1flag`, `w1third`, `w1game1`, `w1game2`, `w2flag`, `w2third`, `w2game1`, `w2game2`, `w3flag`, `w3third`, `w3game1`, `w3game2`, `w4flag`, `w4third`, `w4game1`, `w4game2`, `w5flag`, `w5third`, `w5game1`, `w5game2`, `w6flag`, `w6third`, `w6game1`, `w6game2`, `w7playoff`) VALUES
(1, NULL, 2025, 1, NULL, 13, 0, '2025-08-12 19:19:41', 0, 'season', '2025-08-04 17:30:00', '2025-08-07 17:30:00', '2025-08-12 17:30:00', '2025-08-14 17:00:00', '2025-08-19 17:30:00', '2025-09-07', '2025-09-14', '2025-09-21', '2025-09-27', '2025-10-05', '2025-10-12', 4, NULL, NULL, '11:00:00', '09:00:00', '10:00:00', '11:45:00', '12:45:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00', '16:30:00', '17:30:00', '18:30:00', '19:30:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00', '08:00:00', '09:00:00', '10:00:00', '11:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `league_player_count`
--

DROP TABLE IF EXISTS `league_player_count`;
CREATE TABLE IF NOT EXISTS `league_player_count` (
  `seasonID` int(11) NOT NULL,
  `total_returning` int(11) DEFAULT 0,
  `incoming_rookies` int(11) DEFAULT 0,
  `total_league_players` int(11) DEFAULT 0,
  `players_per_team` int(11) DEFAULT 0,
  `draft_order` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`seasonID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `league_player_count`
--

INSERT INTO `league_player_count` (`seasonID`, `total_returning`, `incoming_rookies`, `total_league_players`, `players_per_team`, `draft_order`) VALUES
(2025, 37, 18, 55, 13, '2,3,1,4');

-- --------------------------------------------------------

--
-- Table structure for table `playergamestats`
--

DROP TABLE IF EXISTS `playergamestats`;
CREATE TABLE IF NOT EXISTS `playergamestats` (
  `gID` int(11) NOT NULL,
  `pID` int(11) NOT NULL,
  `rushes` int(11) DEFAULT 0,
  `rush_yards` int(11) DEFAULT 0,
  `rush_tds` int(11) DEFAULT 0,
  `pass_attempts` int(11) DEFAULT 0,
  `pass_completions` int(11) DEFAULT 0,
  `pass_yards` int(11) DEFAULT 0,
  `pass_tds` int(11) DEFAULT 0,
  `pass_ints` int(11) DEFAULT 0,
  `receptions` int(11) DEFAULT 0,
  `receiving_yards` int(11) DEFAULT 0,
  `receiving_tds` int(11) DEFAULT 0,
  `fumbles_lost` int(11) DEFAULT 0,
  `tackles_assisted` int(11) DEFAULT 0,
  `tackles_unassisted` int(11) DEFAULT 0,
  `tackles_for_loss` int(11) DEFAULT 0,
  `sacks` float DEFAULT 0,
  `fumbles_forced` int(11) DEFAULT 0,
  `fumbles_recovered` int(11) DEFAULT 0,
  `fumbles_td` int(11) DEFAULT 0,
  `interceptions` int(11) DEFAULT 0,
  `interception_yards` int(11) DEFAULT 0,
  `interception_tds` int(11) DEFAULT 0,
  `xp_rushes` int(11) DEFAULT 0,
  `xp_rush_yards` int(11) DEFAULT 0,
  `xp_rush_tds` int(11) DEFAULT 0,
  `xp_pass_atts` int(11) DEFAULT 0,
  `xp_pass_comps` int(11) DEFAULT 0,
  `xp_pass_tds` int(11) DEFAULT 0,
  `xp_recepts` int(11) DEFAULT 0,
  `xp_recepts_yards` int(11) DEFAULT 0,
  `xp_recepts_tds` int(11) DEFAULT 0,
  `xp_kick_att` int(11) DEFAULT 0,
  `xp_kick_result` int(11) DEFAULT 0,
  `fg_att` int(11) DEFAULT 0,
  `fg_att_result` int(11) DEFAULT 0,
  `xp_pass_yards` smallint(6) DEFAULT 0,
  PRIMARY KEY (`gID`,`pID`),
  KEY `pID` (`pID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `playergamestats`
--

INSERT INTO `playergamestats` (`gID`, `pID`, `rushes`, `rush_yards`, `rush_tds`, `pass_attempts`, `pass_completions`, `pass_yards`, `pass_tds`, `pass_ints`, `receptions`, `receiving_yards`, `receiving_tds`, `fumbles_lost`, `tackles_assisted`, `tackles_unassisted`, `tackles_for_loss`, `sacks`, `fumbles_forced`, `fumbles_recovered`, `fumbles_td`, `interceptions`, `interception_yards`, `interception_tds`, `xp_rushes`, `xp_rush_yards`, `xp_rush_tds`, `xp_pass_atts`, `xp_pass_comps`, `xp_pass_tds`, `xp_recepts`, `xp_recepts_yards`, `xp_recepts_tds`, `xp_kick_att`, `xp_kick_result`, `fg_att`, `fg_att_result`, `xp_pass_yards`) VALUES
(1, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 6, 1, 65, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 7, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 10, 0, 0, 0, 2, 1, 14, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 11, 1, 0, 0, 0, 0, 0, 0, 0, 1, 14, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 12, 1, -3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 13, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 15, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 18, 1, 18, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 3),
(1, 23, 1, 57, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 24, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 25, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 3, 1, 0, 0, 0, 0, 0),
(1, 27, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 29, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 30, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 32, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 66, 2, 34, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 68, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 97, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 98, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 99, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 103, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 106, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 107, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 109, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 36, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 37, 0, 0, 0, 2, 1, 7, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 38, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 39, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 40, 0, 0, 0, 0, 0, 0, 0, 0, 1, 7, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 47, 1, 13, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 48, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 52, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 53, 1, 13, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 54, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 56, 0, 0, 0, 1, 1, 24, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 57, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 58, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 3, 1, 0, 0, 0, 0, 0),
(2, 61, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 62, 1, 5, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 3),
(2, 64, 0, 0, 0, 0, 0, 0, 0, 0, 1, 24, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 65, 2, 23, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 69, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 70, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 72, 1, 42, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 100, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 101, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 102, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 104, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 105, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 114, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 115, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

DROP TABLE IF EXISTS `players`;
CREATE TABLE IF NOT EXISTS `players` (
  `playerID` int(11) NOT NULL AUTO_INCREMENT,
  `firstName` varchar(50) DEFAULT NULL,
  `lastName` varchar(50) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `jerseyNumber` int(11) DEFAULT NULL,
  `seasonID` int(11) NOT NULL,
  `teamID` int(11) DEFAULT 0,
  `grade` int(11) DEFAULT 0,
  `height` float DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `fortySpeed` float DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `web` tinyint(1) DEFAULT 0,
  `draft_season` int(11) DEFAULT NULL,
  `draft_round` int(11) DEFAULT NULL,
  `draft_slot` int(11) DEFAULT NULL,
  `draft_time` datetime DEFAULT NULL,
  PRIMARY KEY (`playerID`,`seasonID`),
  KEY `seasonID` (`seasonID`),
  KEY `teamID` (`teamID`)
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`playerID`, `firstName`, `lastName`, `birthday`, `email`, `phone`, `jerseyNumber`, `seasonID`, `teamID`, `grade`, `height`, `weight`, `fortySpeed`, `active`, `web`, `draft_season`, `draft_round`, `draft_slot`, `draft_time`) VALUES
(1, 'Luke', 'Lisai', NULL, NULL, NULL, 12, 2024, 2, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(1, 'Luke', 'Lisai', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(2, 'Kyson', 'Smith', NULL, NULL, NULL, 31, 2024, 2, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(2, 'Kyson', 'Smith', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(3, 'Jaikub', 'Wheeler', NULL, NULL, NULL, 33, 2024, 2, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(3, 'Jaikub', 'Wheeler', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(4, 'Jaxon', 'Latham', NULL, NULL, NULL, 22, 2024, 2, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(4, 'Jaxon', 'Latham', NULL, NULL, NULL, 22, 2025, 2, 6, NULL, NULL, NULL, 1, 0, 0, 0, 0, NULL),
(5, 'Owen', 'Wilson', NULL, NULL, NULL, 83, 2024, 2, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(5, 'Owen', 'Wilson', NULL, NULL, NULL, 83, 2025, 2, 6, NULL, 97, NULL, 1, 0, 0, 0, 0, NULL),
(6, 'Anthony', 'Lisai', NULL, NULL, NULL, 4, 2024, 2, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(6, 'Anthony', 'Lisai', NULL, NULL, NULL, 4, 2025, 2, 5, NULL, 68, NULL, 1, 0, 0, 0, 0, NULL),
(7, 'Achillies', 'Beltran', NULL, NULL, NULL, 21, 2024, 2, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(7, 'Achillies', 'Beltran', NULL, NULL, NULL, 21, 2025, 2, 5, NULL, 91, NULL, 1, 0, 0, 0, 0, NULL),
(8, 'Landen', 'Saunders', NULL, NULL, NULL, 28, 2024, 2, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(8, 'Landen', 'Saunders', NULL, NULL, NULL, 28, 2025, 2, 5, NULL, 88, NULL, 1, 0, 0, 0, 0, NULL),
(9, 'Jack', 'Olbrych', NULL, NULL, NULL, 32, 2024, 2, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(9, 'Jack', 'Olbrych', NULL, NULL, NULL, 32, 2025, 2, 5, NULL, 76, NULL, 1, 0, 0, 0, 0, NULL),
(10, 'Palmer', 'Wyman', NULL, NULL, NULL, 42, 2024, 2, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(10, 'Palmer', 'Wyman', NULL, NULL, NULL, 42, 2025, 2, 5, NULL, 94, NULL, 1, 0, 0, 0, 0, NULL),
(11, 'Teddy', 'Johnson', NULL, NULL, NULL, 44, 2024, 2, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(11, 'Teddy', 'Johnson', NULL, NULL, NULL, 44, 2025, 2, 5, NULL, 74, NULL, 1, 0, 0, 0, 0, NULL),
(12, 'Kelvin', 'Pelt', NULL, NULL, NULL, 52, 2024, 2, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(12, 'Kelvin', 'Pelt', NULL, NULL, NULL, 52, 2025, 2, 5, NULL, 124, NULL, 1, 0, 0, 0, 0, NULL),
(13, 'Jameson', 'Stocker', NULL, NULL, NULL, 54, 2024, 2, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(13, 'Jameson', 'Stocker', NULL, NULL, NULL, 54, 2025, 2, 5, NULL, 103, NULL, 1, 0, 0, 0, 0, NULL),
(14, 'Elliot', 'Aboul', NULL, NULL, NULL, 81, 2024, 2, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(14, 'Elliot', 'Aboul', NULL, NULL, NULL, 81, 2025, 0, 5, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(15, 'Jordan', 'Pollock', NULL, NULL, NULL, 88, 2024, 2, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(15, 'Jordan', 'Pollock', NULL, NULL, NULL, 88, 2025, 2, 5, NULL, 74, NULL, 1, 0, 0, 0, 0, NULL),
(16, 'Moses', 'Lupiani', NULL, NULL, NULL, 4, 2024, 3, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(16, 'Moses', 'Lupiani', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(17, 'Jahmil', 'Marsh', NULL, NULL, NULL, 11, 2024, 3, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(17, 'Jahmil', 'Marsh', NULL, NULL, NULL, 11, 2025, 0, 6, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(18, 'Dylan', 'Brown', NULL, NULL, NULL, 16, 2024, 3, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(18, 'Dylan', 'Brown', NULL, NULL, NULL, 16, 2025, 3, 5, NULL, 88, NULL, 1, 0, 0, 0, 0, NULL),
(19, 'Chase', 'Paquette', NULL, NULL, NULL, 21, 2024, 3, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(19, 'Chase', 'Paquette', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(20, 'Jonathon', 'Holt', NULL, NULL, NULL, 22, 2024, 3, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(20, 'Jonathon', 'Holt', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(21, 'Creedence', 'Matyas', NULL, NULL, NULL, 28, 2024, 3, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(21, 'Creedence', 'Matyas', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(22, 'Nazear', 'Koritz', NULL, NULL, NULL, 31, 2024, 3, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(22, 'Nazear', 'Koritz', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(23, 'Jackson', 'Barnett', NULL, NULL, NULL, 33, 2024, 3, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(23, 'Jackson', 'Barnett', NULL, NULL, NULL, 33, 2025, 3, 5, NULL, 90, NULL, 1, 0, 0, 0, 0, NULL),
(24, 'Wyatt', 'Wade', NULL, NULL, NULL, 42, 2024, 3, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(24, 'Wyatt', 'Wade', NULL, NULL, NULL, 42, 2025, 3, 6, NULL, 67, NULL, 1, 0, 0, 0, 0, NULL),
(25, 'Jackson', 'Stillwagon', NULL, NULL, NULL, 44, 2024, 3, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(25, 'Jackson', 'Stillwagon', NULL, NULL, NULL, 44, 2025, 3, 5, NULL, NULL, NULL, 1, 0, 0, 0, 0, NULL),
(26, 'Pasquale', 'Bazzano', NULL, NULL, NULL, 52, 2024, 3, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(26, 'Pasquale', 'Bazzano', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(27, 'Hayden', 'Moore', NULL, NULL, NULL, 66, 2024, 3, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(27, 'Hayden', 'Moore', NULL, NULL, NULL, 66, 2025, 3, 5, NULL, 122, NULL, 1, 0, 0, 0, 0, NULL),
(28, 'Asher', 'Hill', NULL, NULL, NULL, 68, 2024, 3, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(28, 'Asher', 'Hill', NULL, NULL, NULL, 68, 2025, 0, 5, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(29, 'Benjamin', 'Bill', NULL, NULL, NULL, 72, 2024, 3, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(29, 'Benjamin', 'Bill', NULL, NULL, NULL, 72, 2025, 3, 6, NULL, 116, NULL, 1, 0, 0, 0, 0, NULL),
(30, 'Benson', 'Elsesser', NULL, NULL, NULL, 81, 2024, 3, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(30, 'Benson', 'Elsesser', NULL, NULL, NULL, 81, 2025, 3, 6, NULL, 89, NULL, 1, 0, 0, 0, 0, NULL),
(31, 'Parker', 'Webster', NULL, NULL, NULL, 85, 2024, 3, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(31, 'Parker', 'Webster', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(32, 'Braden', 'Williams', NULL, NULL, NULL, 88, 2024, 3, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(32, 'Braden', 'Williams', NULL, NULL, NULL, 88, 2025, 3, 5, NULL, 87, NULL, 1, 0, 0, 0, 0, NULL),
(33, 'Kyle', 'Putnam', NULL, NULL, NULL, 85, 2024, 1, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(33, 'Kyle', 'Putnam', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(34, 'Quinn', 'Bisbee', NULL, NULL, NULL, 11, 2024, 1, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(34, 'Quinn', 'Bisbee', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(35, 'Trace', 'Tuttle', NULL, NULL, NULL, 31, 2024, 1, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(35, 'Trace', 'Tuttle', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(36, 'Joshua', 'Dupuis', NULL, NULL, NULL, 52, 2024, 1, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(36, 'Joshua', 'Dupuis', NULL, NULL, NULL, 52, 2025, 1, 6, NULL, 114, NULL, 1, 0, 0, 0, 0, NULL),
(37, 'Jack', 'Olney', NULL, NULL, NULL, 28, 2024, 1, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(37, 'Jack', 'Olney', NULL, NULL, NULL, 28, 2025, 1, 6, NULL, 89, NULL, 1, 0, 0, 0, 0, NULL),
(38, 'Carter', 'Chapin', NULL, NULL, NULL, 42, 2024, 1, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(38, 'Carter', 'Chapin', NULL, NULL, NULL, 42, 2025, 1, 6, NULL, 124, NULL, 1, 0, 0, 0, 0, NULL),
(39, 'Solomon', 'Lockerby', NULL, NULL, NULL, 32, 2024, 1, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(39, 'Solomon', 'Lockerby', NULL, NULL, NULL, 32, 2025, 1, 6, NULL, 93, NULL, 1, 0, 0, 0, 0, NULL),
(40, 'Garrett', 'Putnam', NULL, NULL, NULL, 22, 2024, 1, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(40, 'Garrett', 'Putnam', NULL, NULL, NULL, 22, 2025, 1, 5, NULL, 117, NULL, 1, 0, 0, 0, 0, NULL),
(41, 'Jayden', 'Wheeler', NULL, NULL, NULL, 72, 2024, 1, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(41, 'Jayden', 'Wheeler', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(42, 'Jase', 'Hill', NULL, NULL, NULL, 61, 2024, 1, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(42, 'Jase', 'Hill', NULL, NULL, NULL, 61, 2025, 0, 5, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(43, 'Kayden', 'Curtis', NULL, NULL, NULL, 4, 2024, 1, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(43, 'Kayden', 'Curtis', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(44, 'Adam', 'Dole', NULL, NULL, NULL, 21, 2024, 1, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(44, 'Adam', 'Dole', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(45, 'Lucas', 'Nowers', NULL, NULL, NULL, 33, 2024, 1, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(45, 'Lucas', 'Nowers', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(46, 'Nathaniel', 'Cooper', NULL, NULL, NULL, 51, 2024, 1, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(46, 'Nathaniel', 'Cooper', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(47, 'Scout', 'Burnor', NULL, NULL, NULL, 16, 2024, 1, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(47, 'Scout', 'Burnor', NULL, NULL, NULL, 16, 2025, 1, 5, NULL, 83, NULL, 1, 0, 0, 0, 0, NULL),
(48, 'Reese', 'Toussaint', NULL, NULL, NULL, 23, 2024, 1, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(48, 'Reese', 'Toussaint', NULL, NULL, NULL, 23, 2025, 1, 5, NULL, 82, NULL, 1, 0, 0, 0, 0, NULL),
(49, 'Cyrus', 'Hayes', NULL, NULL, NULL, 4, 2024, 4, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(49, 'Cyrus', 'Hayes', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(50, 'Sage', 'Wandzy', NULL, NULL, NULL, 51, 2024, 4, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(50, 'Sage', 'Wandzy', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(51, 'Ben', 'Swain', NULL, NULL, NULL, 66, 2024, 4, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(51, 'Ben', 'Swain', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(52, 'Mario', 'Checchi Jr', NULL, NULL, NULL, 52, 2024, 4, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(52, 'Mario', 'Checchi Jr', NULL, NULL, NULL, 52, 2025, 4, 6, NULL, 125, NULL, 1, 0, 0, 0, 0, NULL),
(53, 'Troy', 'Schultz', NULL, NULL, NULL, 22, 2024, 4, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(53, 'Troy', 'Schultz', NULL, NULL, NULL, 22, 2025, 4, 6, NULL, 85, NULL, 1, 0, 0, 0, 0, NULL),
(54, 'Eli', 'Stevens', NULL, NULL, NULL, 28, 2024, 4, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(54, 'Eli', 'Stevens', NULL, NULL, NULL, 28, 2025, 4, 6, NULL, 115, NULL, 1, 0, 0, 0, 0, NULL),
(55, 'Gavin', 'Wilson', NULL, NULL, NULL, 44, 2024, 4, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(55, 'Gavin', 'Wilson', NULL, NULL, NULL, 44, 2025, 0, 6, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(56, 'Winnie', 'Fortier', NULL, NULL, NULL, 77, 2024, 4, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(56, 'Winnie', 'Fortier', NULL, NULL, NULL, 77, 2025, 4, 6, NULL, 150, NULL, 1, 0, 0, 0, 0, NULL),
(57, 'Issac', 'Grove', NULL, NULL, NULL, 88, 2024, 4, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(57, 'Issac', 'Grove', NULL, NULL, NULL, 88, 2025, 4, 6, NULL, 89, NULL, 1, 0, 0, 0, 0, NULL),
(58, 'Liam', 'Morse', NULL, NULL, NULL, 33, 2024, 4, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(58, 'Liam', 'Morse', NULL, NULL, NULL, 33, 2025, 4, 5, NULL, 80, NULL, 1, 0, 0, 0, 0, NULL),
(59, 'Abram', 'Hooke', NULL, NULL, NULL, 1, 2024, 4, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(59, 'Abram', 'Hooke', NULL, NULL, NULL, 1, 2025, 0, 5, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(60, 'Carter', 'Fisher', NULL, NULL, NULL, 32, 2024, 4, 6, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(60, 'Carter', 'Fisher', NULL, NULL, NULL, NULL, 2025, 0, 7, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(61, 'Ezra', 'Ashworth', NULL, NULL, NULL, 54, 2024, 4, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(61, 'Ezra', 'Ashworth', NULL, NULL, NULL, 54, 2025, 4, 5, NULL, 110, NULL, 1, 0, 0, 0, 0, NULL),
(62, 'Ripken', 'Bernard', NULL, NULL, NULL, 12, 2024, 4, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(62, 'Ripken', 'Bernard', NULL, NULL, NULL, 12, 2025, 4, 5, NULL, 90, NULL, 1, 0, 0, 0, 0, NULL),
(63, 'Damian', 'Breed', NULL, NULL, NULL, 72, 2024, 4, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(63, 'Damian', 'Breed', NULL, NULL, NULL, 73, 2025, 0, 6, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL),
(64, 'Jonathan', 'Sprague', NULL, NULL, NULL, 21, 2024, 4, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(64, 'Jonathan', 'Sprague', NULL, NULL, NULL, 21, 2025, 4, 5, NULL, 67, NULL, 1, 0, 0, 0, 0, NULL),
(65, 'Quinn', 'May', NULL, NULL, NULL, 42, 2024, 4, 4, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(65, 'Quinn', 'May', NULL, NULL, NULL, 42, 2025, 4, 5, NULL, 65, NULL, 1, 0, 0, 0, 0, NULL),
(66, 'Ethan', 'Wheeler', NULL, NULL, NULL, NULL, 2024, 2, 5, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(66, 'Ethan', 'Wheeler', NULL, NULL, NULL, NULL, 2025, 2, 5, NULL, NULL, NULL, 1, 0, 0, 0, 0, NULL),
(68, 'Silas', 'Ladd', '2015-02-17', NULL, '603-499-2899', NULL, 2025, 2, 5, NULL, 85, NULL, 1, 0, 2025, 1, 1, '2025-08-12 19:00:49'),
(69, 'Noah', 'McKane', '2015-02-15', NULL, '276-266-0531', NULL, 2025, 4, 5, NULL, 115, NULL, 1, 0, 2025, 3, 3, '2025-08-12 19:12:11'),
(70, 'Everett', 'Grove', '2015-11-02', NULL, '401-864-3673', NULL, 2025, 4, 4, NULL, 75, NULL, 1, 0, 2025, 2, 4, '2025-08-12 19:09:24'),
(71, 'Nikko', 'Pollock', '2017-09-30', NULL, '617-784-3926', NULL, 2025, 0, 3, NULL, 56, NULL, 1, 0, NULL, NULL, NULL, NULL),
(72, 'Kamren', 'Pierce', '2016-04-14', NULL, '802-289-1849', NULL, 2025, 1, 4, NULL, 70, NULL, 1, 0, 2025, 4, 2, '2025-08-12 19:14:01'),
(73, 'Dean', 'Olney', '2017-08-10', NULL, '6035582173', NULL, 2025, 0, 3, NULL, 54, NULL, 1, 0, NULL, NULL, NULL, NULL),
(74, 'Sawyer', 'Scott', '2017-02-20', NULL, '802-557-8042', NULL, 2025, 0, 3, NULL, 98, NULL, 1, 0, NULL, NULL, NULL, NULL),
(97, 'Adler', 'Bennett', '2016-07-06', NULL, NULL, NULL, 2025, 3, 4, NULL, 141, NULL, 1, 0, 2025, 1, 2, '2025-08-12 19:02:17'),
(98, 'Corbin', 'Curran', '2016-03-14', NULL, NULL, NULL, 2025, 3, 4, NULL, 104, NULL, 1, 0, 2025, 6, 1, '2025-08-12 19:19:08'),
(99, 'Grason', 'Macintyre', '2016-05-09', NULL, NULL, NULL, 2025, 2, 4, NULL, 135, NULL, 1, 0, 2025, 2, 1, '2025-08-12 19:06:53'),
(100, 'Grayson', 'Fernett', '2015-07-09', NULL, NULL, NULL, 2025, 1, 5, NULL, 71, NULL, 1, 0, 2025, 7, 1, '2025-08-12 19:19:41'),
(101, 'Henry', 'Lovell', '2016-06-29', NULL, NULL, NULL, 2025, 1, 4, NULL, 86, NULL, 1, 0, 2025, 2, 3, '2025-08-12 19:08:36'),
(102, 'Jax', 'Tuttle', '2015-11-27', NULL, NULL, NULL, 2025, 1, 4, NULL, 100, NULL, 1, 0, 2025, 1, 3, '2025-08-12 19:03:14'),
(103, 'Jaxson', 'Ruggiero', '2016-01-02', NULL, NULL, NULL, 2025, 3, 4, NULL, 88, NULL, 1, 0, 2025, 3, 1, '2025-08-12 19:11:01'),
(104, 'Luke', 'Bisbee', '2015-08-03', NULL, NULL, NULL, 2025, 1, 4, NULL, 83, NULL, 1, 0, 2025, 3, 2, '2025-08-12 19:11:12'),
(105, 'Owen', 'Wade', '2016-03-10', NULL, NULL, NULL, 2025, 1, 4, NULL, 134, NULL, 1, 0, 2025, 6, 2, '2025-08-12 19:19:31'),
(106, 'Sawyer', 'Roina', '2015-12-09', NULL, NULL, NULL, 2025, 3, 4, NULL, 93, NULL, 1, 0, 2025, 4, 1, '2025-08-12 19:13:46'),
(107, 'Carson', 'Benjamin', '2016-08-23', NULL, NULL, NULL, 2025, 3, 4, NULL, 91, NULL, 1, 0, 2025, 5, 1, '2025-08-12 19:16:21'),
(108, 'Carter', 'Taylor', '2016-12-01', NULL, NULL, NULL, 2025, 0, 3, NULL, 59, NULL, 1, 0, NULL, NULL, NULL, NULL),
(109, 'Ayden', 'Fisk', '2012-11-19', NULL, NULL, NULL, 2025, 3, 6, NULL, 86, NULL, 1, 0, 2025, 2, 2, '2025-08-12 19:08:22'),
(110, 'Jonah', 'Houghton', '2017-01-18', NULL, NULL, NULL, 2025, 0, 3, NULL, 112, NULL, 0, 0, NULL, NULL, NULL, NULL),
(112, 'Jeremiah', 'Houghton', '2017-01-18', NULL, NULL, NULL, 2025, 0, 3, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL),
(113, 'Brady', 'White', '2017-02-18', NULL, NULL, NULL, 2025, 0, 3, NULL, 81, NULL, 1, 0, NULL, NULL, NULL, NULL),
(114, 'Luke', 'Druschel', '2014-05-22', NULL, NULL, NULL, 2025, 4, 6, NULL, 99, NULL, 1, 0, 2025, 1, 4, '2025-08-12 19:06:10'),
(115, 'Xander', 'Aldrich', '2015-07-09', NULL, NULL, NULL, 2025, 1, 5, NULL, 80, NULL, 1, 0, 2025, 5, 2, '2025-08-12 19:17:24'),
(116, 'Alton', 'Maxfield', '2016-08-21', NULL, NULL, NULL, 2025, 0, 3, NULL, 81, NULL, 1, 0, NULL, NULL, NULL, NULL),
(117, 'Elijah', 'Lique', '2017-02-05', NULL, NULL, NULL, 2025, 0, 3, NULL, 111, NULL, 1, 0, NULL, NULL, NULL, NULL),
(118, 'Rory', 'Hanatow', '2017-03-25', NULL, NULL, NULL, 2025, 0, 3, NULL, 69, NULL, 1, 0, NULL, NULL, NULL, NULL),
(119, 'Waylon', 'Whiton', '2017-07-22', NULL, NULL, NULL, 2025, 0, 3, NULL, 57, NULL, 1, 0, NULL, NULL, NULL, NULL),
(120, 'Maverick', 'Webster', '2016-12-30', NULL, NULL, NULL, 2025, 0, 3, NULL, 62, NULL, 1, 0, NULL, NULL, NULL, NULL),
(121, 'Sawyer', 'Wilson', '2017-03-01', NULL, NULL, NULL, 2025, 0, 3, NULL, 89, NULL, 1, 0, NULL, NULL, NULL, NULL),
(122, 'Benson', 'Moore', '2017-06-17', NULL, NULL, NULL, 2025, 0, 3, NULL, 63, NULL, 1, 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `standings`
--

DROP TABLE IF EXISTS `standings`;
CREATE TABLE IF NOT EXISTS `standings` (
  `steamID` smallint(2) NOT NULL,
  `ssesaonID` int(4) NOT NULL,
  `steam` varchar(20) DEFAULT NULL,
  `sheadcoach` int(4) UNSIGNED NOT NULL DEFAULT 0,
  `sWins` int(2) UNSIGNED DEFAULT 0,
  `sLosses` int(2) UNSIGNED DEFAULT 0,
  `sTies` int(2) UNSIGNED DEFAULT 0,
  `sPointsFor` int(3) DEFAULT 0,
  `sPointsAgainst` int(3) DEFAULT 0,
  `schamps` smallint(1) NOT NULL DEFAULT 0,
  `soff` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`steamID`,`ssesaonID`),
  KEY `sheadcoach` (`sheadcoach`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `standings`
--

INSERT INTO `standings` (`steamID`, `ssesaonID`, `steam`, `sheadcoach`, `sWins`, `sLosses`, `sTies`, `sPointsFor`, `sPointsAgainst`, `schamps`, `soff`) VALUES
(1, 2024, 'Dolphins', 1, 0, 0, 0, 0, 0, 0, 0),
(1, 2025, 'Dolphins', 1, 0, 0, 0, 0, 0, 0, 0),
(2, 2024, 'Jets', 2, 0, 0, 0, 0, 0, 0, 0),
(2, 2025, 'Jets', 2, 0, 0, 0, 0, 0, 0, 0),
(3, 2024, 'Packers', 3, 0, 0, 0, 0, 0, 0, 0),
(3, 2025, 'Packers', 3, 0, 0, 0, 0, 0, 0, 0),
(4, 2024, 'Patriots', 4, 0, 0, 0, 0, 0, 1, 0),
(4, 2025, 'Patriots', 4, 0, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
CREATE TABLE IF NOT EXISTS `teams` (
  `teamID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `color1` varchar(20) DEFAULT NULL,
  `color2` varchar(20) DEFAULT NULL,
  `logo` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`teamID`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`teamID`, `name`, `color1`, `color2`, `logo`) VALUES
(1, 'Dolphins', '#FC4C02', '#008E97', 'dolphin.png'),
(2, 'Jets', '#125740', '#FFFFFF', 'jet.png'),
(3, 'Packers', '#FFB612', '#203731', 'packer.png'),
(4, 'Patriots', '#C60C30', '#002244', 'patriot.png');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `active` tinyint(4) DEFAULT 0,
  `firstName` varchar(50) DEFAULT NULL,
  `lastName` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `teamID` int(11) NOT NULL DEFAULT 0,
  `admin` tinyint(4) NOT NULL DEFAULT 0,
  `headCoach` tinyint(4) NOT NULL DEFAULT 0,
  `asstCoach` tinyint(4) NOT NULL DEFAULT 0,
  `stats` tinyint(4) NOT NULL DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
