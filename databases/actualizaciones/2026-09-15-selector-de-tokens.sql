-- La columna `selector` de los tokens y su índice único, que entró en el esquema el 2026-09-15 (fa911716).

ALTER TABLE `pcsphp_tokens`
  ADD COLUMN `selector` varchar(32) DEFAULT NULL,
  ADD UNIQUE KEY `selector` (`selector`);
