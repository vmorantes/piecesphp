-- El registro de las actualizaciones de la base que db-update aplicó o marcó (H298).

CREATE TABLE IF NOT EXISTS `pcsphp_db_updates` (
  `name` varchar(191) NOT NULL,
  `sha1` char(40) NOT NULL,
  `statements` int(11) NOT NULL,
  `appliedAt` datetime NOT NULL,
  `mode` varchar(16) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
