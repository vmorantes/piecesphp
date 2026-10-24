-- El usuario de sistema (B14): el autor de lo que se hace sin sesión. Antes se guardaba como del usuario 1, que es un
-- root real, y el registro decía que root había hecho lo que hizo el sistema.
--
-- Para INSTALACIONES QUE YA EXISTEN. Una instalación nueva no necesita este archivo: la fila ya viene en
-- `piecesphp_data.sql`. Es del núcleo: no tiene generador.
--
-- Se puede aplicar con la aplicación en marcha, y hay que aplicarlo ANTES de desplegar el código: el código escribe
-- `createdBy = -10` en tablas con clave foránea a `pcsphp_users`, y sin la fila esas escrituras fallan.
--
-- LA FILA: id -10 (ni 0, que es el tipo de root y lo que `empty()` toma por vacío, ni -1, que el código usa como
-- centinela), tipo 99 (sin rol), estado 0 (inactivo) y contraseña `!`, que ningún `password_verify()` acepta. El código
-- lo deja fuera de los listados y recuentos, y no lo deja entrar, recuperar contraseña, suplantar, editar ni borrar.
--
-- LAS FILAS VIEJAS: se corrigen solo las que se pueden DEMOSTRAR del sistema. Son las del registro de acciones que
-- llevan `meta.actor.kind = 'system'`, que el registro escribe desde que distingue quién actuaba. Las demás tablas no
-- guardan esa marca: lo que tengan como del usuario 1 se queda como está, porque no se puede saber de quién era.

INSERT IGNORE INTO `pcsphp_users` (`id`, `organization`, `username`, `password`, `firstname`, `secondname`, `firstLastname`, `secondLastname`, `email`, `meta`, `type`, `status`, `failedAttempts`, `createdAt`, `modifiedAt`) VALUES
(-10, NULL, 'system', '!', 'Sistema', '', '', '', 'system@localhost.invalid', '{"system":true}', 99, 0, 0, NOW(), NOW());

-- CASE y no AND: MySQL no promete evaluar AND de izquierda a derecha, y JSON_EXTRACT sobre un texto que no es JSON
-- válido es un error en MySQL (en MariaDB, NULL con aviso).
UPDATE `actions_log` SET `createdBy` = -10
WHERE `createdBy` = 1
  AND (CASE WHEN JSON_VALID(`meta`) THEN JSON_UNQUOTE(JSON_EXTRACT(`meta`, '$.actor.kind')) END) = 'system';
