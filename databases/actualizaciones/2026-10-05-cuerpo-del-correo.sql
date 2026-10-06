-- El registro de correos guarda el cuerpo, cifrado (ADR 0048).
--
-- Para TODA instalación cuya tabla `pcsphp_mail_log` salió de `2026-10-03-registro-de-correos.sql`. Una tabla
-- generada después con `bin/cli scheme-create module=PiecesPHP/SystemStatus` ya trae la columna: ahí este ALTER falla
-- con «Duplicate column name 'body'», que es inofensivo y quiere decir que no hacía falta.
--
-- Es UN paso y no hay tarea que ejecutar después. Se puede hacer con la aplicación en marcha: la columna nace `NULL`, y
-- `NULL` significa «esta fila no tiene cuerpo guardado», que es lo que tienen todas las anteriores.
--
-- `longtext` porque el cuerpo va CIFRADO (AES-256-CBC sobre el texto comprimido, en base64) y un cifrado recortado
-- no se puede descifrar: no se pone tope de longitud. Los adjuntos no se guardan nunca.

ALTER TABLE `pcsphp_mail_log`
  ADD COLUMN `body` longtext COLLATE utf8mb4_bin DEFAULT NULL AFTER `reason`;
