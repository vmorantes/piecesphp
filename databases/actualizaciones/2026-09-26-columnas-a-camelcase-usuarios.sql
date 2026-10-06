-- Las seis columnas de `pcsphp_users` pasan a camelCase, y los dos índices de la tanda anterior con ellas
-- (paso 3 del mapa, ronda 2 de 2). Con esto no queda ninguna columna con guion bajo en el esquema.
--
-- Para INSTALACIONES QUE YA EXISTEN. Una instalación nueva no necesita este archivo: los nombres nuevos ya vienen en
-- `piecesphp_structure.sql`.
--
-- Son TRES pasos —las seis columnas de usuarios y los dos índices— y no hay tarea que ejecutar después.
--
-- NO SE PUEDE HACER CON LA APLICACIÓN EN MARCHA, y echa fuera a TODO EL MUNDO mientras dura: `pcsphp_users` es la
-- tabla que lee cada petición con sesión, así que entre el `ALTER` y el despliegue del código no entra nadie, ni
-- siquiera por la terminal. El `ALTER` y el código van JUNTOS, y conviene hacerlo con el sitio en mantenimiento.
--
-- Y EL ORDEN IMPORTA: primero el código, después el `ALTER`. La razón no es que el desajuste desaparezca —existe en
-- los dos órdenes— sino cuál de los dos pasos se deshace: revertir archivos es `git`, revertir un `ALTER` a medias es
-- restaurar un respaldo.
--
-- QUÉ HACE: solo cambia nombres. El tipo, la nulabilidad y el valor por omisión de cada columna se repiten tal cual
-- para que `CHANGE` no los altere de paso. El índice `user_id` de las dos tablas de la tanda anterior se quedó con su
-- nombre viejo apuntando a `userID`, que hacía pensar que el renombrado estaba a medias; se renombra aquí. Los
-- `*_ibfk_1` los genera MySQL y no se tocan.

ALTER TABLE `pcsphp_users`
  CHANGE `first_lastname` `firstLastname` varchar(255) NOT NULL,
  CHANGE `second_lastname` `secondLastname` varchar(255) DEFAULT NULL,
  CHANGE `failed_attempts` `failedAttempts` int(1) NOT NULL DEFAULT 0,
  CHANGE `created_at` `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  CHANGE `modified_at` `modifiedAt` datetime NOT NULL DEFAULT current_timestamp(),
  CHANGE `sessions_valid_from` `sessionsValidFrom` datetime DEFAULT NULL;

ALTER TABLE `login_attempts`
  RENAME INDEX `user_id` TO `userID`;

ALTER TABLE `time_on_platform`
  RENAME INDEX `user_id` TO `userID`;
