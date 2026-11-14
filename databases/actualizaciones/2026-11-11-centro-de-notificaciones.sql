-- Las notificaciones por usuario: el canal del centro de notificaciones (ADR 0073).
--
-- Para INSTALACIONES QUE YA EXISTEN: la tabla viene también en `piecesphp_structure.sql` desde el 2026-11-11. Lo de
-- abajo es lo que emite
--
--     bin/cli scheme-create module=PiecesPHP/Notifications
--
-- más lo que el generador no emite y se añade a mano: los índices (user, readAt) y (user, createdAt) y el borrado en
-- cascada de la clave foránea (si se borra un usuario, sus notificaciones se van con él).
--
-- Es UN paso y no hay tarea que ejecutar después. Sin esta tabla el framework sigue funcionando: `Notifications::notify()`
-- no lanza, anota el fallo en el registro de errores y devuelve 0.

CREATE TABLE IF NOT EXISTS `pcsphp_notifications`(
	`id` bigint NOT NULL AUTO_INCREMENT,
	`user` bigint NOT NULL,
	`kind` varchar(64) COLLATE utf8mb4_bin NOT NULL,
	`level` varchar(16) COLLATE utf8mb4_bin NOT NULL,
	`langGroup` varchar(64) COLLATE utf8mb4_bin NOT NULL,
	`titleKey` text COLLATE utf8mb4_bin NOT NULL,
	`bodyKey` text COLLATE utf8mb4_bin,
	`params` longtext COLLATE utf8mb4_bin,
	`routeName` varchar(191) COLLATE utf8mb4_bin,
	`routeParams` longtext COLLATE utf8mb4_bin,
	`createdAt` datetime NOT NULL,
	`readAt` datetime,
	`meta` longtext COLLATE utf8mb4_bin,
	PRIMARY KEY (`id`),
	KEY `userReadAt` (`user`, `readAt`),
	KEY `userCreatedAt` (`user`, `createdAt`),
	CONSTRAINT `pcsphp_notifications_ibfk_1` FOREIGN KEY (`user`) REFERENCES `pcsphp_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
