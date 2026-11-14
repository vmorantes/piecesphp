-- El grupo de la IP en los intentos de acceso.
--
-- Para INSTALACIONES QUE YA EXISTEN. Una instalación nueva no necesita este archivo: la columna y sus índices ya vienen
-- en `piecesphp_structure.sql`.
--
-- Es UN paso, y se puede hacer con la aplicación en marcha. La columna nace admitiendo NULL A PROPÓSITO y las filas
-- que ya están se quedan sin grupo: el límite de intentos solo mira una ventana corta, y para una fila sin grupo sigue
-- comparando la dirección exacta. Los intentos nuevos guardan las dos cosas, la dirección y su grupo.

-- PASO 1. La columna, junto a la dirección, y dos índices con la fecha: el del grupo y el del usuario. La consulta del
-- límite busca por usuario O por grupo dentro de la ventana, y sin los dos índices MySQL recorre la tabla entera.
ALTER TABLE `login_attempts`
  ADD COLUMN `ipGroup` varchar(64) DEFAULT NULL AFTER `ip`,
  ADD KEY `ipGroupDate` (`ipGroup`, `date`),
  ADD KEY `usernameAttemptDate` (`usernameAttempt`, `date`);
