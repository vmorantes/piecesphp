<?php

/**
 * @pcsphp-config framework
 * Qué conviene editar aquí: nada: prepara el logo de los correos.
 */

//La ruta fija de siempre, para quien lea get_config('mailing_logo') (las plantillas de un clon). Las del framework usan
//mailing_logo(), que ve la subida; aquí no se genera nada: statics/images es de fábrica.
set_config('mailing_logo', 'statics/images/mailing-logo.png');

if (!function_exists('mailing_logo')) {
    /**
     * La ruta relativa del logo de los correos, resuelta AL LLAMARSE: al cargar este archivo las opciones de la base aún
     * no están (index.php las aplica después), y sin eso un logo subido (mailingLogo) nunca llegaba al correo.
     * Orden: la subida si su archivo existe; si no, la de fábrica; si falta, se genera desde el logo en las subidas de
     * Configuración (nunca en statics/images); si nada vale, el logo.
     *
     * @return string
     */
    function mailing_logo(): string
    {
        $uploaded = get_config('mailingLogo');
        if (is_string($uploaded) && $uploaded !== '' && is_file(basepath($uploaded))) {
            return $uploaded;
        }
        $factory = 'statics/images/mailing-logo.png';
        if (is_file(basepath($factory))) {
            return $factory;
        }
        $logo = get_config('logo');
        try {
            $folder = basepath(\PiecesPHP\Settings\Controllers\SettingsController::SETTINGS_UPLOADS_FOLDER . '/brand');
            if (is_string($logo) && $logo !== '' && is_file(basepath($logo)) && (is_dir($folder) || mkdir($folder, 0755, true))) {
                $temporary = "{$folder}/mailing-logo-upload-" . bin2hex(random_bytes(6)) . '.' . mb_strtolower(pathinfo($logo, \PATHINFO_EXTENSION));
                resizeAndCenterImage(basepath($logo), $temporary, 500, 172);
                $generated = \PiecesPHP\Settings\Controllers\SettingsController::settleSettingsImage($temporary, 'brand', 'mailing-logo');
                if ($generated !== null) {
                    return $generated;
                }
            }
        } catch (\Exception $e) {
            log_exception($e);
        }
        return is_string($logo) ? $logo : $factory;
    }
}
