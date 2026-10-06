-- Las columnas de `login_attempts` y de `time_on_platform` pasan a camelCase (paso 3 del mapa).
--
-- Para INSTALACIONES QUE YA EXISTEN. Una instalación nueva no necesita este archivo: los nombres nuevos ya vienen en
-- `piecesphp_structure.sql`.
--
-- Son CUATRO pasos, uno por columna, y no hay tarea que ejecutar después.
--
-- NO SE PUEDE HACER CON LA APLICACIÓN EN MARCHA, y aquí está la diferencia con los anteriores: renombrar una columna
-- rompe al instante todo el código que la nombra por el nombre viejo. El `ALTER` y el despliegue del código van
-- JUNTOS. Entre los dos, cualquier petición que escriba un intento de acceso o mueva el temporizador falla.
--
-- QUÉ HACE: solo cambia el nombre. El tipo, la nulabilidad y el valor por omisión de cada columna se repiten tal
-- cual para que `CHANGE` no los altere de paso. Los índices y la clave ajena siguen el nombre nuevo solos; el
-- nombre del ÍNDICE (`user_id`) no es una columna y no se toca.
--
-- POR QUÉ `userID` y no `user`: la convención ambiental para una clave ajena en este esquema es la entidad a secas
-- —`publication`, `organization`, `createdBy`—, pero eso cambiaría el vocabulario y no solo la forma. Se cambia la
-- forma. La palabra es del Product Owner.

ALTER TABLE `login_attempts`
  CHANGE `user_id` `userID` bigint(20) DEFAULT NULL,
  CHANGE `username_attempt` `usernameAttempt` varchar(255) NOT NULL,
  CHANGE `extra_data` `extraData` longtext DEFAULT NULL;

ALTER TABLE `time_on_platform`
  CHANGE `user_id` `userID` bigint(20) NOT NULL;
