<?php

//Las imágenes de terceros de fábrica (P377): los patrocinadores con su oscura de fábrica y el logo del desarrollador con
//la suya. En el proceso: cambia opciones con set_config() y las repone en el finally; no escribe en la base.
//@areas src/app/classes/PiecesPHP/Settings/

use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/third-party-images', function ($args) {

    echoTerminal("\e[33m[TEST:ThirdPartyImages] Patrocinadores y logo del desarrollador, con su imagen oscura de fábrica\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    $opciones = ['partners', 'partnersVertical', 'partnersInvertOnDark', 'partnersVerticalInvertOnDark', 'partnersDarkMode', 'partnersVerticalDarkMode', 'partnersDark', 'partnersVerticalDark', 'developer_logo', 'developer_logo_invert_on_dark'];
    $antes = array_combine($opciones, array_map(fn (string $opcion): mixed => get_config($opcion), $opciones));
    $viejos = [];

    try {
        //─── p · Los patrocinadores de fábrica ────────────────────────────────────────────────────────
        echoTerminal('[p] Los patrocinadores de fábrica, sin modo guardado, enseñan en oscuro su imagen oscura de fábrica');
        $fabrica = ['partners' => 'statics/images/partners.png', 'partnersVertical' => 'statics/images/partners-vertical.png'];
        $oscuras = ['partners' => 'statics/images/partners-dark.png', 'partnersVertical' => 'statics/images/partners-vertical-dark.png'];
        $limpiar = function (string $imagen): void {
            foreach (['InvertOnDark', 'DarkMode', 'Dark'] as $sufijo) {
                set_config($imagen . $sufijo, false);
            }
        };
        foreach ($fabrica as $imagen => $ruta) {
            $limpiar($imagen);
            set_config($imagen, $ruta);
            SettingsController::clearBrandImageCache();
            $etiqueta = SettingsController::brandImageTag($imagen, 'zz');
            $check(SettingsController::brandImageDarkMode($imagen) === 'image' && SettingsController::brandImageDarkImage($imagen) === $oscuras[$imagen]
                && substr_count($etiqueta, '<img') === 2 && str_contains($etiqueta, 'class="zz brand-image-dark" src="' . $oscuras[$imagen] . '"') && !str_contains($etiqueta, 'invert-on-dark'), "p1 DISCRIMINANTE: {$imagen} de fábrica y sin modo, «Otra imagen» con {$oscuras[$imagen]}, sin invertir", $etiqueta);

            //Como el logo: la oscura de fábrica gana a la opción vieja guardada, a 1 y a 0.
            foreach ([1, 0] as $vieja) {
                set_config($imagen . 'InvertOnDark', $vieja);
                $etiqueta = SettingsController::brandImageTag($imagen, 'zz');
                $check(SettingsController::brandImageDarkMode($imagen) === 'image' && substr_count($etiqueta, '<img') === 2 && str_contains($etiqueta, 'src="' . $oscuras[$imagen] . '"') && !str_contains($etiqueta, 'invert-on-dark'), 'p' . (2 + (1 - $vieja)) . " DISCRIMINANTE: {$imagen} de fábrica con la opción vieja a {$vieja} guardada, la oscura de fábrica gana (como el logo)", $etiqueta);
            }
            set_config($imagen . 'DarkMode', 'same');
            $etiqueta = SettingsController::brandImageTag($imagen, 'zz');
            $check(SettingsController::brandImageDarkMode($imagen) === 'same' && substr_count($etiqueta, '<img') === 1 && !str_contains($etiqueta, 'brand-image-'), "p4 DISCRIMINANTE: {$imagen}: el modo nuevo guardado manda sobre la oscura de fábrica", $etiqueta);

            $limpiar($imagen);
            set_config($imagen . 'Dark', 'statics/images/zz-no-existe.png');
            $etiqueta = SettingsController::brandImageTag($imagen, 'zz');
            $check(SettingsController::brandImageEffectiveDarkMode($imagen) === 'invert' && str_contains($etiqueta, 'class="zz invert-on-dark"'), "p5 DISCRIMINANTE: {$imagen} de fábrica sin oscura que exista, vuelve a invertirse (FACTORY_BRAND_IMAGES_DARK_IS_INVERTED)", $etiqueta);
            $limpiar($imagen);

            //El sha1 anterior también es de fábrica: una instalación que no ha copiado aún las nuevas.
            $viejo = 'app/cache/zz-' . $imagen . '-viejo-' . bin2hex(random_bytes(3)) . '.png';
            $viejos[] = $viejo;
            $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
            $git = proc_open(['git', '--no-optional-locks', '-C', $proyecto, 'show', 'v10.53.0:src/' . $ruta], [1 => ['file', basepath($viejo), 'w'], 2 => ['file', '/dev/null', 'w']], $tuberias);
            $sacado = is_resource($git) && proc_close($git) === 0 && in_array(sha1_file(basepath($viejo)), (array) SettingsController::FACTORY_BRAND_IMAGES[$imagen], true) && sha1_file(basepath($viejo)) !== sha1_file(basepath($ruta));
            set_config($imagen, $viejo);
            $check($sacado && SettingsController::isFactoryBrandImage($imagen) && SettingsController::brandImageDarkMode($imagen) === 'image', "p6 {$imagen}: el de fábrica anterior (v10.53.0) también se reconoce", $sacado ? '' : 'no se pudo sacar de git');
            set_config($imagen, 'statics/images/logo.png');
            $check(SettingsController::brandImageDarkMode($imagen) === 'same', "p7 DISCRIMINANTE: {$imagen} ajena y sin opciones, «Igual»");
            set_config($imagen, $ruta);
        }

        //─── d · El logo del desarrollador ────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] El logo del desarrollador: de fábrica y sin la clave, su clara y su oscura; con la clave a 1 o 0, como antes');
        set_config('developer_logo', SettingsController::FACTORY_DEVELOPER_LOGO);
        set_config('developer_logo_invert_on_dark', null);
        $etiqueta = SettingsController::developerLogoTag('zz', 'Hecho por "x"');
        $check(substr_count($etiqueta, '<img') === 2 && str_contains($etiqueta, 'class="zz brand-image-light" src="' . SettingsController::FACTORY_DEVELOPER_LOGO . '"')
            && str_contains($etiqueta, 'class="zz brand-image-dark" src="' . SettingsController::FACTORY_DEVELOPER_LOGO_DARK . '"') && !str_contains($etiqueta, 'invert-on-dark')
            && substr_count($etiqueta, 'title="Hecho por &quot;x&quot;"') === 2, 'd1 DISCRIMINANTE: de fábrica y sin la clave, la clara y developer-logo-dark.png, sin invertir y con el title escapado', $etiqueta);
        $check(SettingsController::developerLogoInvertsOnDark(), 'd2 developerLogoInvertsOnDark() sigue siendo la regla de una imagen (la que usa quien aún no pinta con developerLogoTag())');
        set_config('developer_logo_invert_on_dark', false);
        $check(substr_count(SettingsController::developerLogoTag('zz'), '<img') === 2, 'd3 false cuenta como sin poner: las dos');
        set_config('developer_logo_invert_on_dark', 1);
        $etiqueta = SettingsController::developerLogoTag('zz');
        $check(substr_count($etiqueta, '<img') === 1 && str_contains($etiqueta, 'class="zz invert-on-dark"') && !str_contains($etiqueta, 'title='), 'd4 DISCRIMINANTE: con la clave a 1, vuelve a invertir la clara', $etiqueta);
        set_config('developer_logo_invert_on_dark', '0');
        $etiqueta = SettingsController::developerLogoTag('zz');
        $check(substr_count($etiqueta, '<img') === 1 && $etiqueta === '<img class="zz" src="' . SettingsController::FACTORY_DEVELOPER_LOGO . '" alt="">', 'd5 DISCRIMINANTE: con la clave a 0, la clara sola, sin invertir', $etiqueta);
        set_config('developer_logo', 'statics/images/zz-prueba-otro-logo.png');
        set_config('developer_logo_invert_on_dark', null);
        $etiqueta = SettingsController::developerLogoTag('zz');
        $check($etiqueta === '<img class="zz" src="statics/images/zz-prueba-otro-logo.png" alt="">', 'd6 DISCRIMINANTE: un logo ajeno sin la clave, uno y sin invertir', $etiqueta);

        //De fábrica por sha1 y no por ruta: el clon que puso su logo en developer-logo.png no ve la oscura del framework.
        $ajeno = 'app/cache/zz-developer-logo-ajeno-' . bin2hex(random_bytes(3)) . '.png';
        $viejos[] = $ajeno;
        $copiado = copy(basepath('statics/images/logo.png'), basepath($ajeno));
        set_config('developer_logo', $ajeno);
        $etiqueta = SettingsController::developerLogoTag('zz');
        $check($copiado && !SettingsController::isFactoryDeveloperLogo() && SettingsController::developerLogoDarkImage() === '' && $etiqueta === '<img class="zz" src="' . $ajeno . '" alt="">', 'd8 un logo ajeno que existe en disco, sin la clave: una sola imagen, sin la oscura de fábrica', $etiqueta);
        $copia = 'app/cache/zz-developer-logo-copia-' . bin2hex(random_bytes(3)) . '.png';
        $viejos[] = $copia;
        $copiado = copy(basepath(SettingsController::FACTORY_DEVELOPER_LOGO), basepath($copia));
        set_config('developer_logo', $copia);
        $etiqueta = SettingsController::developerLogoTag('zz');
        $check($copiado && SettingsController::isFactoryDeveloperLogo() && substr_count($etiqueta, '<img') === 2 && str_contains($etiqueta, 'src="' . SettingsController::FACTORY_DEVELOPER_LOGO_DARK . '"'), 'd9 DISCRIMINANTE: el contenido de fábrica en otra ruta es de fábrica (sha1, no ruta): la clara y la oscura', $etiqueta);
        $viejo = 'app/cache/zz-developer-logo-viejo-' . bin2hex(random_bytes(3)) . '.png';
        $viejos[] = $viejo;
        $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $git = proc_open(['git', '--no-optional-locks', '-C', $proyecto, 'show', 'v10.53.0:src/' . SettingsController::FACTORY_DEVELOPER_LOGO], [1 => ['file', basepath($viejo), 'w'], 2 => ['file', '/dev/null', 'w']], $tuberias);
        $sacado = is_resource($git) && proc_close($git) === 0 && sha1_file(basepath($viejo)) !== sha1_file(basepath(SettingsController::FACTORY_DEVELOPER_LOGO));
        set_config('developer_logo', $viejo);
        $check($sacado && SettingsController::isFactoryDeveloperLogo() && SettingsController::developerLogoInvertsOnDark() && substr_count(SettingsController::developerLogoTag('zz'), '<img') === 2, 'd10 DISCRIMINANTE: el logo de fábrica anterior (v10.53.0, 67 × 32) también se reconoce', $sacado ? '' : 'no se pudo sacar de git');
        set_config('developer_logo', '');
        $check(SettingsController::developerLogoTag('zz') === '', 'd7 sin developer_logo, nada');
    } finally {
        foreach ($antes as $opcion => $valor) {
            set_config($opcion, $valor);
        }
        SettingsController::clearBrandImageCache();
        foreach ($viejos as $viejo) {
            if (is_file(basepath($viejo))) {
                //RETORNO-IGNORADO: un temporal en app/cache/, que git ignora; z1 lo comprueba.
                unlink(basepath($viejo));
            }
        }
    }
    $check(array_filter($viejos, fn (string $viejo): bool => is_file(basepath($viejo))) === [], 'z1 limpieza de los temporales de app/cache/');

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Los patrocinadores y el logo del desarrollador de fábrica, con su imagen oscura de fábrica (P377).')->setEffects([CliActions::EFFECT_FILES])->register();
