-- Los colores guardados con la fábrica vieja pasan a la del logo (tema 0.17.1); un color propio no se toca.

UPDATE `pcsphp_app_config` SET `value` = '#00008B' WHERE `name` IN ('main_brand_color', 'meta_theme_color') AND UPPER(TRIM(`value`)) = '#356DCA';
UPDATE `pcsphp_app_config` SET `value` = '#0B7A77' WHERE `name` IN ('second_brand_color', 'data_color_2') AND UPPER(TRIM(`value`)) = '#7A5AF8';
UPDATE `pcsphp_app_config` SET `value` = '#E8730C' WHERE `name` IN ('third_brand_color', 'data_color_3') AND UPPER(TRIM(`value`)) = '#C11574';
