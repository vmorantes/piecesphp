-- El consumo de la IA por capacidad, proveedor y periodo: la tabla del centro de mando de la IA (ADR 0074).
--
-- Para INSTALACIONES QUE YA EXISTEN: la tabla viene también en `piecesphp_structure.sql` desde el 2026-11-13. Lo de
-- abajo es lo que emite
--
--     bin/cli scheme-create module=PiecesPHP/AI
--
-- más lo que el generador no emite y se añade a mano: el índice único (capability, provider, periodStart), una fila por
-- capacidad, proveedor y periodo.
--
-- Es UN paso y no hay tarea que ejecutar después. El ALTER no se puede repetir: si la tabla ya tiene el índice, falla y
-- no pasa nada. Sin esta tabla el framework sigue funcionando: `AIGateway::recordUsage()` no lanza, anota el fallo en el
-- registro de errores y sigue sumando el acumulado de siempre.

CREATE TABLE IF NOT EXISTS `pcsphp_ai_usage`(
	`id` bigint NOT NULL AUTO_INCREMENT,
	`capability` varchar(64) COLLATE utf8mb4_bin NOT NULL,
	`provider` varchar(64) COLLATE utf8mb4_bin NOT NULL,
	`periodStart` datetime NOT NULL,
	`tokens` bigint NOT NULL,
	`requests` int NOT NULL,
	`createdAt` datetime NOT NULL,
	`updatedAt` datetime NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

ALTER TABLE `pcsphp_ai_usage` ADD UNIQUE KEY `capabilityProviderPeriod` (`capability`, `provider`, `periodStart`);
