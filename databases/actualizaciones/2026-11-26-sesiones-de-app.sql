-- La sesión de una app móvil (ADR 0092 §1): su tipo, la clave pública del dispositivo y la llave de renovación de un
-- solo uso (selector fijo, huella del verificador actual y del anterior, el actual sellado y el último timestamp).

ALTER TABLE `pcsphp_user_sessions`
  ADD COLUMN `kind` varchar(8) NOT NULL DEFAULT 'web' AFTER `closeReason`,
  ADD COLUMN `deviceKey` text NULL AFTER `kind`,
  ADD COLUMN `refreshSelector` char(22) COLLATE utf8mb4_bin NULL AFTER `deviceKey`,
  ADD COLUMN `refreshHash` char(64) NULL AFTER `refreshSelector`,
  ADD COLUMN `refreshPrevHash` char(64) NULL AFTER `refreshHash`,
  ADD COLUMN `refreshPending` text NULL AFTER `refreshPrevHash`,
  ADD COLUMN `refreshLastTimestamp` bigint(20) NULL AFTER `refreshPending`,
  ADD UNIQUE KEY `refreshSelector` (`refreshSelector`);
