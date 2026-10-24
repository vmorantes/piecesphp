-- Una fila por sesión (ADR 0026, fase B; P130 a): la tabla del núcleo con la que se cierra una sesión concreta, se
-- extiende, y cerrar sesión cierra también en el servidor.
--
-- Para INSTALACIONES QUE YA EXISTEN. Una instalación nueva no necesita este archivo: la tabla ya viene en
-- `piecesphp_structure.sql`. Es del núcleo: no tiene generador (`scheme-create` no la cubre).
--
-- ATENCIÓN, RUPTURA: desde la versión que trae esta tabla, un token sin identificador de sesión no entra. Al actualizar,
-- TODOS los usuarios vuelven a iniciar sesión una vez. Es a propósito (P130 a): sin la fila, cerrar una sesión sería
-- una ficción.
--
-- Es UN paso y no hay tarea que ejecutar después. Aplícalo ANTES de desplegar el código: sin la tabla, ninguna sesión
-- nueva se puede abrir.
--
-- QUÉ GUARDA: el hash sha256 del identificador (el identificador en claro solo viaja dentro del token), quién, cuándo
-- empezó, su última actividad (como mucho una escritura cada 5 minutos), cuándo caduca, la IP y el navegador para que
-- el dueño la reconozca, y si se cerró: cuándo, quién y por qué (logout, closed-by-user, closed-by-admin,
-- revoke-all-user, revoke-all-global). Las cerradas o caducadas hace más de 30 días se borran solas.

CREATE TABLE IF NOT EXISTS `pcsphp_user_sessions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `sessionHash` char(64) NOT NULL,
  `createdAt` datetime NOT NULL,
  `lastActivityAt` datetime NOT NULL,
  `expiresAt` datetime NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `userAgent` varchar(255) DEFAULT NULL,
  `closedAt` datetime DEFAULT NULL,
  `closedBy` int(11) DEFAULT NULL,
  `closeReason` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sessionHash` (`sessionHash`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
