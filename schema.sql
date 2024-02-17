SET NAMES utf8;
SET time_zone = '+00:00';
SET NAMES utf8mb4;

CREATE TABLE `alv` (
  `alkuaika` int(11) NOT NULL,
  `loppuaika` int(11) DEFAULT NULL,
  `alv` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `alv` (`alkuaika`, `loppuaika`, `alv`) VALUES
(1640995200,	1669852799,	24),
(1669852800,	1682899199,	10),
(1682899200,	NULL,	24);

CREATE TABLE `tuntihinta` (
  `aikaleima` int(11) NOT NULL,
  `tuntihinta` double NOT NULL,
  PRIMARY KEY (`aikaleima`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE VIEW `vw_kooste` AS select `th`.`aikaleima` AS `aikaleima`,`th`.`tuntihinta` AS `tuntihinta`,`a`.`alv` AS `alv` from (`tuntihinta` `th` left join `alv` `a` on(`th`.`aikaleima` between ifnull(`a`.`alkuaika`,0) and ifnull(`a`.`loppuaika`,4294967295)));

CREATE VIEW `vw_selko` AS select from_unixtime(`tuntihinta`.`aikaleima`) AS `aikaleima`,`tuntihinta`.`tuntihinta` AS `tuntihinta` from `tuntihinta`;
