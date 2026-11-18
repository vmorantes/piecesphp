-- Inter salió: la fuente guardada como «inter» pasa a la de fábrica, Atkinson Hyperlegible Next.

UPDATE `pcsphp_app_config` SET `value` = 'atkinson-hyperlegible-next' WHERE `name` = 'theme_font' AND LOWER(TRIM(`value`)) = 'inter';
