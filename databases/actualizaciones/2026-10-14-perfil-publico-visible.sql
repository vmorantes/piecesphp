-- P136: el dueño y root deciden si un perfil público se lista y se muestra (el Directorio, el mapa y su página).
-- Para INSTALACIONES QUE YA EXISTEN: una nueva lo trae en `piecesphp_structure.sql`. Por omisión, todo visible, como
-- hasta ahora. Se puede aplicar con la aplicación en marcha, y hay que aplicarlo ANTES de desplegar el código, que lee
-- la columna.

ALTER TABLE `user_system_profile` ADD COLUMN `publicVisible` tinyint(4) NOT NULL DEFAULT 1 AFTER `longitude`;
ALTER TABLE `organizations_elements` ADD COLUMN `publicVisible` tinyint(4) NOT NULL DEFAULT 1 AFTER `status`;
