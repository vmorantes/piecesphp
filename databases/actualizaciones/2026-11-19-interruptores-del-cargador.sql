-- Las imágenes del cargador guardadas vacías (lo que hacía su «Eliminar») pasan a su interruptor apagado, el texto 0, y la
-- opción vacía se borra: sin ella vuelve la imagen de fábrica, que el interruptor apagado ya no deja ver.
-- La pantalla de carga tiene un solo interruptor para las dos: se apaga solo si ninguna de las dos tiene imagen, para no
-- esconder la que sí la tiene.

INSERT INTO `pcsphp_app_config` (`name`, `value`)
SELECT CONCAT(`saved`.`name`, 'Enabled'), '0' FROM `pcsphp_app_config` AS `saved`
WHERE `saved`.`name` IN ('loaderAnimation', 'loaderLogo') AND `saved`.`value` IS NOT NULL AND TRIM(`saved`.`value`) IN ('', '""')
ON DUPLICATE KEY UPDATE `pcsphp_app_config`.`value` = '0';

INSERT INTO `pcsphp_app_config` (`name`, `value`)
SELECT 'loaderScreenEnabled', '0' FROM (
    SELECT
        SUM(`value` IS NOT NULL AND TRIM(`value`) IN ('', '""')) AS `empties`,
        SUM(`value` IS NOT NULL AND TRIM(`value`) NOT IN ('', '""')) AS `images`
    FROM `pcsphp_app_config` WHERE `name` IN ('loaderScreen', 'loaderScreenMobile')
) AS `screens`
WHERE `screens`.`empties` > 0 AND `screens`.`images` = 0
ON DUPLICATE KEY UPDATE `pcsphp_app_config`.`value` = '0';

DELETE FROM `pcsphp_app_config`
WHERE `name` IN ('loaderAnimation', 'loaderLogo', 'loaderScreen', 'loaderScreenMobile') AND `value` IS NOT NULL AND TRIM(`value`) IN ('', '""');
