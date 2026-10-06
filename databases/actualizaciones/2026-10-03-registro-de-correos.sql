-- Registro de todos los correos, con su resultado (ADR 0043 §5).
--
-- Para CUALQUIER instalación, nueva o existente: la tabla es de un módulo de `src/app/classes`, así que NO vive en
-- `piecesphp_structure.sql` —ese archivo guarda las tablas del núcleo, que no tienen generador—. El SQL de abajo es
-- exactamente lo que emite:
--
--     bin/cli scheme-create module=PiecesPHP/SystemStatus
--
-- Es UN paso y no hay tarea que ejecutar después. Sin esta tabla el framework sigue enviando correo: el registro
-- falla en silencio a propósito (`MailLogMapper::record()` no lanza), porque un registro que rompe el envío es peor
-- que no tener registro.
--
-- Por qué existe: hasta el 2026-10-03 un envío que fallaba solo dejaba una línea en un registro que nadie lee, y
-- desde que el correo retenido se guarda en el buzón, una entrega mal declarada en producción **guarda en silencio**
-- en vez de fallar. Esta tabla es lo que hace visible el correo que no llegó.

CREATE TABLE IF NOT EXISTS `pcsphp_mail_log`(
	`id` int NOT NULL AUTO_INCREMENT,
	`sentAt` datetime NOT NULL,
	`recipients` text COLLATE utf8mb4_bin NOT NULL,
	`subject` text COLLATE utf8mb4_bin,
	`origin` text COLLATE utf8mb4_bin,
	`delivery` text COLLATE utf8mb4_bin NOT NULL,
	`result` text COLLATE utf8mb4_bin NOT NULL,
	`reason` text COLLATE utf8mb4_bin,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
