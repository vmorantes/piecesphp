-- Las opciones que salieron con la pantalla Colores del tema (JCQ). Sin esto, cada fila daría un aviso grave de opción
-- sin declarar (ConfigOptions::warnUndeclared()). Sus valores no se migran: el tema ya no los lee.

DELETE FROM `pcsphp_app_config`
WHERE `name` IN (
    'font_color_one',
    'font_color_two',
    'menu_color_background',
    'menu_color_mark',
    'menu_color_font',
    'bg_tools_buttons',
    'font_family_global'
);
