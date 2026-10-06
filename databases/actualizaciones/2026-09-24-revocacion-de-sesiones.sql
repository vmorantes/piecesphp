-- Revocación de sesiones por usuario (ADR 0026, fase A · lote 11a §4bis).
--
-- Para INSTALACIONES QUE YA EXISTEN. Una instalación nueva no necesita este archivo: la columna ya viene en
-- `piecesphp_structure.sql`.
--
-- Es UN paso y no hay tarea que ejecutar después.
--
-- SE PUEDE HACER CON LA APLICACIÓN EN MARCHA, y no echa fuera a nadie: la columna nace `NULL`, y `NULL` significa
-- «este usuario no tiene ninguna revocación». Las sesiones abiertas siguen abiertas.
--
-- QUÉ HACE la columna: un token de ese usuario vale si nació DESPUÉS de esta fecha. Ponerla a «ahora» cierra todas
-- las sesiones de ese usuario a la vez, que es el «cerrar sesión en todos mis dispositivos».
--
-- La comprobación vive en `src/index.php`, donde el usuario ya está leído de la base, así que la columna NO cuesta
-- ninguna consulta nueva.

ALTER TABLE `pcsphp_users`
  ADD COLUMN `sessions_valid_from` datetime DEFAULT NULL AFTER `modified_at`;
