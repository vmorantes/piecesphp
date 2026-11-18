-- Las series 2 y 3 guardadas con la fábrica nueva pasan a vacío: vacío es la del tema, que es la misma.

UPDATE `pcsphp_app_config` SET `value` = '' WHERE `name` = 'data_color_2' AND UPPER(TRIM(`value`)) = '#0B7A77';
UPDATE `pcsphp_app_config` SET `value` = '' WHERE `name` = 'data_color_3' AND UPPER(TRIM(`value`)) = '#E8730C';
