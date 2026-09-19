-- Adminer 4.8.1 MySQL 5.5.5-10.5.18-MariaDB-1:10.5.18+maria~ubu2004 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `ODS_Usuarios`;
CREATE TABLE `ODS_Usuarios` (
  `ID` bigint DEFAULT NULL,
  `Email` text DEFAULT NULL,
  `Usuario` text DEFAULT NULL,
  `Contraseña` text DEFAULT NULL,
  `PrimerNombre` text DEFAULT NULL,
  `SegundoNombre` text DEFAULT NULL,
  `PrimerApellido` text DEFAULT NULL,
  `SegundoApellido` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


-- 2023-08-08 16:22:07
