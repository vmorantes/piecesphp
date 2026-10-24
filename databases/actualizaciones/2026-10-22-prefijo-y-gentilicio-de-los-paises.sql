-- P196 b (B9): cada país lleva su prefijo telefónico y su gentilicio.
--
-- Para INSTALACIONES QUE YA EXISTEN. Una instalación nueva ya nace con las dos columnas en `piecesphp_structure.sql`.
--
-- SE PUEDE HACER CON LA APLICACIÓN EN MARCHA, y hay que hacerlo ANTES de desplegar el código, que lee las columnas.
-- Son dos pasos: las columnas, y los datos de Colombia y Francia (los de la instalación de ejemplo), solo si existen con
-- ese nombre y no tienen ya el dato. Sin ellos, los desplegables de prefijo y nacionalidad saldrían vacíos al quitar
-- las listas fijas. Los demás países quedan sin el dato hasta que se les ponga en Ubicaciones › Países.

ALTER TABLE `locations_countries`
  ADD COLUMN `phoneCode` varchar(10) DEFAULT NULL AFTER `code`,
  ADD COLUMN `demonym` varchar(255) DEFAULT NULL AFTER `phoneCode`;

UPDATE `locations_countries` SET `phoneCode` = '+57' WHERE `name` = 'Colombia' AND `phoneCode` IS NULL;
UPDATE `locations_countries` SET `demonym` = 'Colombiana' WHERE `name` = 'Colombia' AND `demonym` IS NULL;
UPDATE `locations_countries` SET `phoneCode` = '+33' WHERE `name` = 'Francia' AND `phoneCode` IS NULL;
UPDATE `locations_countries` SET `demonym` = 'Francesa' WHERE `name` = 'Francia' AND `demonym` IS NULL;
