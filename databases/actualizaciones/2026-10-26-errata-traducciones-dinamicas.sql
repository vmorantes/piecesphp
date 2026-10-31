-- G21: la fila de las traducciones dinámicas pendientes se llamaba con una errata, `…|DYNAMIC_TRANSCALTIONS`, y la
-- misma palabra era la clave de su JSON. El código pasa a `…|DYNAMIC_TRANSLATIONS`.
--
-- Para INSTALACIONES QUE YA EXISTEN. Una instalación nueva no trae la fila: la crea el primer guardado, ya con el nombre
-- bueno.
--
-- SE PUEDE HACER CON LA APLICACIÓN EN MARCHA, y hay que hacerlo ANTES de desplegar el código. Si se despliega antes, el
-- código lee la fila vieja mientras la nueva no exista, así que no se pierde nada; pero el primer guardado crea la
-- nueva y deja la vieja sin uso. Por eso son dos pasos: primero se borra la fila nueva SOLO si ya existe y no guarda
-- ninguna traducción pendiente, y después se renombra la vieja. Si la nueva existe y tiene traducciones, el DELETE no la
-- toca y el UPDATE choca con su nombre único: se revisan las dos a mano.

DELETE FROM `pcsphp_app_config`
WHERE SUBSTRING_INDEX(`name`, '|', -1) = 'DYNAMIC_TRANSLATIONS'
  AND JSON_UNQUOTE(JSON_EXTRACT(`value`, '$.DYNAMIC_TRANSLATIONS')) IN ('[]', '{}', 'null', '')
  AND EXISTS (SELECT 1 FROM (SELECT `name` FROM `pcsphp_app_config`) AS `old` WHERE SUBSTRING_INDEX(`old`.`name`, '|', -1) = 'DYNAMIC_TRANSCALTIONS');

UPDATE `pcsphp_app_config`
SET `name`  = CONCAT(SUBSTRING_INDEX(`name`, '|', 1), '|DYNAMIC_TRANSLATIONS'),
    `value` = REPLACE(`value`, '"DYNAMIC_TRANSCALTIONS"', '"DYNAMIC_TRANSLATIONS"')
WHERE `name` LIKE '%|DYNAMIC_TRANSCALTIONS';
