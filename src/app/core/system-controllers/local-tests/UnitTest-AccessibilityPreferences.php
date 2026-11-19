<?php

//La accesibilidad, fase 2 (H252): contraste, movimiento y foco del sitio y de cada persona, su ruta, el <html> y las dos
//secciones de «Preferencias» y de Configuración › Accesibilidad. Por HTTP, con usuarios zz-agente- propios que se retiran.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/accessibility-preferences', function ($args) {

    echoTerminal("\e[33m[TEST:AccessibilityPreferences] Contraste, movimiento y foco: del sitio y de cada persona\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'banco: hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $relativa = function (string $url) use ($prefijoBase): string {
        $ruta = (string) parse_url($url, \PHP_URL_PATH);
        return $prefijoBase !== '' && str_starts_with($ruta, $prefijoBase) ? substr($ruta, strlen($prefijoBase)) : $ruta;
    };
    $peticion = function (string $camino, ?string $jwt, ?array $campos) use ($base): array {
        $h = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($h, [
            CURLOPT_URL => $base . '/' . ltrim($camino, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => array_merge(['X-Requested-With: XMLHttpRequest'], $jwt !== null ? [SessionToken::headerName() . ": {$jwt}"] : []),
        ] + ($campos !== null ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($campos)] : []));
        $cuerpo = curl_exec($h);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE), 'body' => is_string($cuerpo) ? $cuerpo : ''];
    };
    $exito = fn (array $r): bool => $r['status'] === 200 && (json_decode($r['body'], true)['success'] ?? null) === true;
    //Solo la etiqueta de apertura de <html>: los atributos no pueden venir de otro sitio.
    $html = fn (string $cuerpo): string => preg_match('/<html\b[^>]*>/i', $cuerpo, $m) === 1 ? $m[0] : '';
    $dom = function (string $cuerpo): \DOMXPath {
        $documento = new \DOMDocument();
        //RETORNO-IGNORADO: el HTML del panel no es XML estricto; lo que cuenta son las consultas de abajo.
        @$documento->loadHTML('<?xml encoding="utf-8"?>' . $cuerpo);
        return new \DOMXPath($documento);
    };
    $cuenta = function (\DOMXPath $xpath, string $consulta): int {
        $nodos = $xpath->query($consulta);
        return $nodos === false ? 0 : $nodos->length;
    };

    $tablaOpciones = (new SettingsModel())->getModel()->getTable();
    $opciones = [SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG, SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG, SettingsController::ACCESSIBILITY_CONTRAST_CONFIG, SettingsController::ACCESSIBILITY_MOTION_CONFIG, SettingsController::ACCESSIBILITY_FOCUS_CONFIG, SettingsController::ACCESSIBILITY_TEXT_SPACING_CONFIG, SettingsController::ACCESSIBILITY_TRANSPARENCY_CONFIG];
    $crudoDe = function (string $nombre) use ($database, $tablaOpciones): ?string {
        $consulta = $database->prepare("SELECT value FROM `{$tablaOpciones}` WHERE BINARY name = ?");
        $consulta->execute([$nombre]);
        $valor = $consulta->fetchColumn();
        return $valor === false ? null : (string) $valor;
    };
    $foto = function () use ($opciones, $crudoDe): array {
        $filas = [];
        foreach ($opciones as $opcion) {
            $filas[$opcion] = $crudoDe($opcion);
        }
        return $filas;
    };
    $filasAntes = $foto();
    $huellaAntes = sha1((string) json_encode($filasAntes));

    $prefijo = 'zz-agente-accesibilidad-' . bin2hex(random_bytes(3));
    $ids = [];

    try {
        $crear = function (string $nombre, int $tipo) use ($prefijo, &$ids): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$nombre}";
            $u->email = "{$prefijo}-{$nombre}@example.invalid";
            $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Accesibilidad';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $ids[] = (int) $u->id;
            return (int) $u->id;
        };
        $idRoot = $crear('root', UsersModel::TYPE_USER_ROOT);
        $jwtRoot = UserSessions::issueToken($idRoot);
        $ruta = $relativa(AdminPanelController::routeName('accessibility-preference', [], true));
        $inicio = $relativa(AdminPanelController::routeName('', [], true));

        //──── [r] La ruta ────────────────────────────────────────────────────────────────────
        echoTerminal('[r] admin-accessibility-preference: una preferencia y un valor de su lista');
        $r = $peticion($ruta, $jwtRoot, ['preference' => 'zz-sabor', 'value' => 'more']);
        $check($r['status'] === 400 && UsersModel::metaValue($idRoot, AdminPanelController::CONTRAST_META) === null, 'r1 una preferencia desconocida: 400 y no guarda', "HTTP {$r['status']}");
        $malos = [];
        foreach (['contrast' => 'reduce', 'motion' => 'more', 'focus' => 'zz-grueso', 'text-spacing' => 'reduce', 'transparency' => 'wide'] as $preferencia => $valor) {
            $r = $peticion($ruta, $jwtRoot, ['preference' => $preferencia, 'value' => $valor]);
            if ($r['status'] !== 400) {
                $malos[] = "{$preferencia}={$valor}: HTTP {$r['status']}";
            }
        }
        $check($malos === [] && UsersModel::metaValue($idRoot, AdminPanelController::CONTRAST_META) === null && UsersModel::metaValue($idRoot, AdminPanelController::MOTION_META) === null && UsersModel::metaValue($idRoot, AdminPanelController::FOCUS_META) === null && UsersModel::metaValue($idRoot, AdminPanelController::TEXT_SPACING_META) === null && UsersModel::metaValue($idRoot, AdminPanelController::TRANSPARENCY_META) === null, 'r2 DISCRIMINANTE: un valor fuera de la lista de SU preferencia (el de otra no vale): 400 y no guarda', implode(' · ', $malos));
        $r = $peticion($ruta, null, ['preference' => 'contrast', 'value' => 'more']);
        $check($r['status'] !== 200, 'r3 sin sesión no entra', "HTTP {$r['status']}");
        $guardados = [];
        foreach (['contrast' => 'more', 'motion' => 'reduce', 'focus' => 'strong', 'text-spacing' => 'wide', 'transparency' => 'reduce'] as $preferencia => $valor) {
            $r = $peticion($ruta, $jwtRoot, ['preference' => $preferencia, 'value' => $valor]);
            $datos = json_decode($r['body'], true);
            $guardados[$preferencia] = $exito($r) && ($datos['value'] ?? null) === $valor;
        }
        $check($guardados === ['contrast' => true, 'motion' => true, 'focus' => true, 'text-spacing' => true, 'transparency' => true] && UsersModel::metaValue($idRoot, AdminPanelController::CONTRAST_META) === 'more' && UsersModel::metaValue($idRoot, AdminPanelController::MOTION_META) === 'reduce' && UsersModel::metaValue($idRoot, AdminPanelController::FOCUS_META) === 'strong' && UsersModel::metaValue($idRoot, AdminPanelController::TEXT_SPACING_META) === 'wide' && UsersModel::metaValue($idRoot, AdminPanelController::TRANSPARENCY_META) === 'reduce', 'r4 200: guarda las cinco metas y responde el valor guardado', (string) json_encode($guardados));
        $r = $peticion($inicio, $jwtRoot, null);
        $etiqueta = $html($r['body']);
        $check($r['status'] === 200 && str_contains($etiqueta, 'data-contrast="more"') && str_contains($etiqueta, 'data-motion="reduce"') && str_contains($etiqueta, 'data-focus="strong"') && str_contains($etiqueta, 'data-text-spacing="wide"') && str_contains($etiqueta, 'data-transparency="reduce"'), 'r5 la petición siguiente lleva los cinco en <html>', "HTTP {$r['status']}, {$etiqueta}");
        echoTerminal(' ');

        //──── [h] La herencia del sitio ──────────────────────────────────────────────────────
        echoTerminal('[h] Sin meta, el del sitio; con meta, el suyo; con una que no vale, el del sitio');
        $memoria = [];
        foreach ([SettingsController::ACCESSIBILITY_CONTRAST_CONFIG, SettingsController::ACCESSIBILITY_MOTION_CONFIG, SettingsController::ACCESSIBILITY_FOCUS_CONFIG, SettingsController::ACCESSIBILITY_TEXT_SPACING_CONFIG, SettingsController::ACCESSIBILITY_TRANSPARENCY_CONFIG] as $opcion) {
            $memoria[$opcion] = get_config($opcion);
        }
        try {
            set_config(SettingsController::ACCESSIBILITY_CONTRAST_CONFIG, 'more');
            set_config(SettingsController::ACCESSIBILITY_MOTION_CONFIG, 'reduce');
            set_config(SettingsController::ACCESSIBILITY_FOCUS_CONFIG, 'strong');
            set_config(SettingsController::ACCESSIBILITY_TEXT_SPACING_CONFIG, 'wide');
            set_config(SettingsController::ACCESSIBILITY_TRANSPARENCY_CONFIG, 'reduce');
            $sinMeta = $crear('sin-meta', UsersModel::TYPE_USER_GENERAL);
            $check(AdminPanelController::contrastOf($sinMeta) === 'more' && AdminPanelController::motionOf($sinMeta) === 'reduce' && AdminPanelController::focusOf($sinMeta) === 'strong' && AdminPanelController::textSpacingOf($sinMeta) === 'wide' && AdminPanelController::transparencyOf($sinMeta) === 'reduce', 'h1 sin meta: los cinco del sitio');
            UsersModel::setMetaValue($sinMeta, AdminPanelController::CONTRAST_META, 'auto');
            UsersModel::setMetaValue($sinMeta, AdminPanelController::FOCUS_META, 'zz-viejo');
            UsersModel::setMetaValue($sinMeta, AdminPanelController::TEXT_SPACING_META, 'normal');
            UsersModel::setMetaValue($sinMeta, AdminPanelController::TRANSPARENCY_META, 'zz-viejo');
            $check(AdminPanelController::contrastOf($sinMeta) === 'auto' && AdminPanelController::focusOf($sinMeta) === 'strong' && AdminPanelController::textSpacingOf($sinMeta) === 'normal' && AdminPanelController::transparencyOf($sinMeta) === 'reduce', 'h2 con meta, la suya (auto aunque el sitio diga more); con una que no vale, la del sitio');
            set_config(SettingsController::ACCESSIBILITY_CONTRAST_CONFIG, 'zz-raro');
            set_config(SettingsController::ACCESSIBILITY_MOTION_CONFIG, null);
            set_config(SettingsController::ACCESSIBILITY_FOCUS_CONFIG, 'zz-raro');
            set_config(SettingsController::ACCESSIBILITY_TEXT_SPACING_CONFIG, 'zz-raro');
            set_config(SettingsController::ACCESSIBILITY_TRANSPARENCY_CONFIG, null);
            $check(SettingsController::accessibilityContrast() === 'auto' && SettingsController::accessibilityMotion() === 'auto' && SettingsController::accessibilityFocus() === 'normal' && SettingsController::accessibilityTextSpacing() === 'normal' && SettingsController::accessibilityTransparency() === 'auto', 'h3 el sitio sin valor o con uno que no vale: auto, auto, normal, normal y auto');
        } finally {
            foreach ($memoria as $opcion => $valor) {
                set_config($opcion, $valor);
            }
        }
        echoTerminal(' ');

        //──── [g] Configuración › Accesibilidad ──────────────────────────────────────────────
        echoTerminal('[g] Configuración › Accesibilidad guarda y valida los cinco de contraste a transparencias');
        $guardar = $relativa(SettingsController::routeName('appearance-accessibility-save', [], true));
        $validos = [SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG => 'normal', SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG => 'auto', SettingsController::ACCESSIBILITY_CONTRAST_CONFIG => 'more', SettingsController::ACCESSIBILITY_MOTION_CONFIG => 'reduce', SettingsController::ACCESSIBILITY_FOCUS_CONFIG => 'strong', SettingsController::ACCESSIBILITY_TEXT_SPACING_CONFIG => 'wide', SettingsController::ACCESSIBILITY_TRANSPARENCY_CONFIG => 'reduce'];
        $r = $peticion($guardar, $jwtRoot, $validos);
        $check($exito($r) && $crudoDe(SettingsController::ACCESSIBILITY_CONTRAST_CONFIG) === 'more' && $crudoDe(SettingsController::ACCESSIBILITY_MOTION_CONFIG) === 'reduce' && $crudoDe(SettingsController::ACCESSIBILITY_FOCUS_CONFIG) === 'strong' && $crudoDe(SettingsController::ACCESSIBILITY_TEXT_SPACING_CONFIG) === 'wide' && $crudoDe(SettingsController::ACCESSIBILITY_TRANSPARENCY_CONFIG) === 'reduce', 'g1 guarda los cinco valores válidos', "HTTP {$r['status']}");
        $noGuardan = [];
        foreach ([SettingsController::ACCESSIBILITY_CONTRAST_CONFIG => 'reduce', SettingsController::ACCESSIBILITY_MOTION_CONFIG => 'zz', SettingsController::ACCESSIBILITY_FOCUS_CONFIG => 'auto', SettingsController::ACCESSIBILITY_TEXT_SPACING_CONFIG => 'auto', SettingsController::ACCESSIBILITY_TRANSPARENCY_CONFIG => 'wide'] as $opcion => $malo) {
            $antes = $foto();
            $r = $peticion($guardar, $jwtRoot, [$opcion => $malo] + $validos);
            if ($exito($r) || $foto() !== $antes) {
                $noGuardan[] = $opcion;
            }
            $antes = $foto();
            $sinCampo = $validos;
            unset($sinCampo[$opcion]);
            $r = $peticion($guardar, $jwtRoot, $sinCampo);
            if ($exito($r) || $foto() !== $antes) {
                $noGuardan[] = "sin {$opcion}";
            }
        }
        $check($noGuardan === [], 'g2 DISCRIMINANTE: un valor fuera de su lista, o el campo que falta, no guarda nada (ninguna de las siete)', implode(' · ', $noGuardan));
        $r = $peticion($guardar, UserSessions::issueToken($crear('general', UsersModel::TYPE_USER_GENERAL)), $validos);
        $check($r['status'] === 403, 'g3 un usuario general: 403', "HTTP {$r['status']}");
        //Quien no eligió hereda lo del sitio en <html> (el sitio quedó en more, reduce, strong, wide y reduce).
        $r = $peticion($inicio, UserSessions::issueToken($crear('hereda', UsersModel::TYPE_USER_ROOT)), null);
        $etiqueta = $html($r['body']);
        $check($r['status'] === 200 && str_contains($etiqueta, 'data-contrast="more"') && str_contains($etiqueta, 'data-motion="reduce"') && str_contains($etiqueta, 'data-focus="strong"') && str_contains($etiqueta, 'data-text-spacing="wide"') && str_contains($etiqueta, 'data-transparency="reduce"'), 'g4 quien no eligió: los del sitio en <html>', "HTTP {$r['status']}, {$etiqueta}");
        echoTerminal(' ');

        //──── [v] Las dos vistas, en dos secciones ───────────────────────────────────────────
        echoTerminal('[v] «Preferencias» y Configuración › Accesibilidad, con sus dos secciones y sus campos');
        $r = $peticion($relativa(UsersController::routeName('form-profile', [], true)) . '?' . UsersController::PROFILE_TAB_PARAM . '=' . UsersController::PROFILE_PREFERENCES_TAB, $jwtRoot, null);
        $xpath = $dom($r['body']);
        $preferencias = [
            'apariencia' => $cuenta($xpath, "//*[@id='user-preferences-appearance-title']"),
            'lectura y movimiento' => $cuenta($xpath, "//*[@id='user-preferences-reading-title']"),
            'modo' => $cuenta($xpath, "//*[@data-tab='preferences']//*[@theme-mode-selector]//*[@data-theme-mode]"),
            'tamaño' => $cuenta($xpath, "//*[@data-tab='preferences']//*[@text-size-selector]//*[@data-text-size]"),
            'contraste' => $cuenta($xpath, "//*[@accessibility-preference-selector][@data-preference='contrast'][@role='radiogroup']//*[@data-value]"),
            'movimiento' => $cuenta($xpath, "//*[@accessibility-preference-selector][@data-preference='motion'][@role='radiogroup']//*[@data-value]"),
            'foco' => $cuenta($xpath, "//*[@accessibility-preference-selector][@data-preference='focus'][@role='radiogroup']//*[@data-value]"),
            'espaciado' => $cuenta($xpath, "//*[@accessibility-preference-selector][@data-preference='text-spacing'][@role='radiogroup']//*[@data-value]"),
            'transparencias' => $cuenta($xpath, "//*[@accessibility-preference-selector][@data-preference='transparency'][@role='radiogroup']//*[@data-value]"),
            'espaciado activo wide' => $cuenta($xpath, "//*[@data-preference='text-spacing']//*[@data-value='wide'][contains(@class,'active')][@aria-checked='true']"),
            'contraste activo more' => $cuenta($xpath, "//*[@data-preference='contrast']//*[@data-value='more'][contains(@class,'active')][@aria-checked='true']"),
            //La elegida, con primary (a 3:1 de las demás, H326): una por grupo, y solo la elegida.
            'elegidas con primary' => $cuenta($xpath, "//*[@data-tab='preferences']//*[@role='radiogroup']//button[contains(concat(' ', normalize-space(@class), ' '), ' primary ')][@aria-checked='true']"),
            'primary sin elegir' => $cuenta($xpath, "//*[@data-tab='preferences']//*[@role='radiogroup']//button[contains(concat(' ', normalize-space(@class), ' '), ' primary ')][@aria-checked='false']"),
            'título repetido' => $cuenta($xpath, "//*[@id='user-preferences-title']"),
        ];
        $check($r['status'] === 200 && $preferencias === ['apariencia' => 1, 'lectura y movimiento' => 1, 'modo' => 4, 'tamaño' => 4, 'contraste' => 3, 'movimiento' => 3, 'foco' => 3, 'espaciado' => 3, 'transparencias' => 3, 'espaciado activo wide' => 1, 'contraste activo more' => 1, 'elegidas con primary' => 7, 'primary sin elegir' => 0, 'título repetido' => 0], 'v1 «Preferencias»: Apariencia (modo y tamaño, 4 con «Como el sitio»; espaciado del texto 3) y Lectura y movimiento (contraste, movimiento, transparencias y foco, 3 cada uno), con lo guardado marcado; la elegida de cada uno de los siete grupos con primary, y sin el título repetido', "HTTP {$r['status']}, " . json_encode($preferencias, \JSON_UNESCAPED_UNICODE));
        //Las dos tarjetas del tema (H354), cada una con sus grupos en este orden, y la vista previa debajo, inerte.
        $gruposDe = function (string $titulo) use ($xpath): array {
            $nodos = $xpath->query("//section[@aria-labelledby='{$titulo}']//*[@role='radiogroup']");
            $ids = [];
            foreach ($nodos === false ? [] : $nodos as $nodo) {
                if ($nodo instanceof \DOMElement) {
                    $ids[] = str_replace(['user-preferences-', '-label'], '', $nodo->getAttribute('aria-labelledby'));
                }
            }
            return $ids;
        };
        $tarjetas = [
            'apariencia' => $gruposDe('user-preferences-appearance-title'),
            'lectura y movimiento' => $gruposDe('user-preferences-reading-title'),
            'vista previa inerte' => $cuenta($xpath, "//section[@user-preferences-preview]//*[@inert]"),
            'grupos en la vista previa' => $cuenta($xpath, "//section[@user-preferences-preview]//*[@role='radiogroup']"),
        ];
        $check($tarjetas === ['apariencia' => ['theme-mode', 'text-size', 'text-spacing'], 'lectura y movimiento' => ['contrast', 'motion', 'transparency', 'focus'], 'vista previa inerte' => 1, 'grupos en la vista previa' => 0], 'v3 las dos tarjetas con sus grupos en orden (Apariencia: modo, tamaño, espaciado; Lectura y movimiento: contraste, movimiento, transparencias, foco) y la vista previa inerte, sin grupos', (string) json_encode($tarjetas));
        //«Como el sitio» primero en cada grupo, con su recordatorio debajo (P313).
        $grupos = $xpath->query("//*[@data-tab='preferences']//*[@role='radiogroup']");
        $sinSitio = [];
        foreach ($grupos === false ? [] : $grupos as $grupo) {
            if (!$grupo instanceof \DOMElement) {
                continue;
            }
            $id = str_replace('-label', '', $grupo->getAttribute('aria-labelledby'));
            $primera = $xpath->query('./button[1]', $grupo);
            $boton = $primera !== false ? $primera->item(0) : null;
            $esSitio = $boton instanceof \DOMElement && in_array('site', [$boton->getAttribute('data-value'), $boton->getAttribute('data-theme-mode'), $boton->getAttribute('data-text-size')], true)
                && trim($boton->textContent) === __(AdminPanelController::ADMIN_LANG_GROUP, 'Como el sitio');
            $recordatorio = $xpath->query("//*[@id='{$id}-site']");
            $nodoRecordatorio = $recordatorio !== false ? $recordatorio->item(0) : null;
            $tieneRecordatorio = $nodoRecordatorio instanceof \DOMElement && str_starts_with(trim($nodoRecordatorio->textContent), strReplaceTemplate(__(AdminPanelController::ADMIN_LANG_GROUP, 'Como el sitio: %s'), ['%s' => '']));
            if (!$esSitio || !$tieneRecordatorio) {
                $sinSitio[] = $id;
            }
        }
        $check(($grupos === false ? 0 : $grupos->length) === 7 && $sinSitio === [], 'v4 «Como el sitio» es la primera opción de los siete grupos y cada uno lleva debajo «Como el sitio: …»', 'sin él: ' . implode(', ', $sinSitio));
        $r = $peticion($relativa(SettingsController::routeName('appearance-colors', [], true)), $jwtRoot, null);
        $xpath = $dom($r['body']);
        $sitio = [
            'apariencia' => $cuenta($xpath, "//*[@id='accessibility-appearance-title']"),
            'lectura y movimiento' => $cuenta($xpath, "//*[@id='accessibility-reading-title']"),
        ];
        foreach ($opciones as $opcion) {
            $sitio[$opcion] = $cuenta($xpath, "//form[@accessibility-form]//select[@name='{$opcion}']");
        }
        $check($r['status'] === 200 && array_unique(array_values($sitio)) === [1] && $cuenta($xpath, "//select[@name='accessibility_contrast']/option[@value='more'][@selected]") === 1, 'v2 Configuración › Accesibilidad: sus dos tarjetas y los siete campos en un solo formulario, con lo guardado elegido', "HTTP {$r['status']}, " . json_encode($sitio));
        $camposDe = function (string $titulo) use ($xpath): array {
            $nodos = $xpath->query("//form[@accessibility-form]//section[@aria-labelledby='{$titulo}']//select");
            $nombres = [];
            foreach ($nodos === false ? [] : $nodos as $nodo) {
                if ($nodo instanceof \DOMElement) {
                    $nombres[] = str_replace('accessibility_', '', $nodo->getAttribute('name'));
                }
            }
            return $nombres;
        };
        $tarjetasSitio = ['apariencia' => $camposDe('accessibility-appearance-title'), 'lectura y movimiento' => $camposDe('accessibility-reading-title')];
        $check($tarjetasSitio === ['apariencia' => ['theme_mode', 'text_size', 'text_spacing'], 'lectura y movimiento' => ['contrast', 'motion', 'transparency', 'focus']], 'v5 Configuración › Accesibilidad con las mismas dos tarjetas y los campos en el mismo orden', (string) json_encode($tarjetasSitio));
        echoTerminal(' ');

        //──── [s] «Como el sitio» (H327) ─────────────────────────────────────────────────────
        echoTerminal('[s] «Como el sitio»: site borra la meta en las tres rutas y la persona vuelve a heredar');
        //El sitio quedó en g1 con normal, auto, more, reduce, strong, wide y reduce: root elige lo contrario de cada uno.
        $rutaModo = $relativa(AdminPanelController::routeName('theme-mode', [], true));
        $rutaTamano = $relativa(AdminPanelController::routeName('text-size', [], true));
        $propias = [
            $exito($peticion($rutaModo, $jwtRoot, ['mode' => 'dark'])),
            $exito($peticion($rutaTamano, $jwtRoot, ['size' => 'larger'])),
            $exito($peticion($ruta, $jwtRoot, ['preference' => 'contrast', 'value' => 'auto'])),
            $exito($peticion($ruta, $jwtRoot, ['preference' => 'motion', 'value' => 'auto'])),
            $exito($peticion($ruta, $jwtRoot, ['preference' => 'focus', 'value' => 'normal'])),
            $exito($peticion($ruta, $jwtRoot, ['preference' => 'text-spacing', 'value' => 'normal'])),
            $exito($peticion($ruta, $jwtRoot, ['preference' => 'transparency', 'value' => 'auto'])),
        ];
        $etiqueta = $html($peticion($inicio, $jwtRoot, null)['body']);
        $check($propias === [true, true, true, true, true, true, true] && str_contains($etiqueta, 'data-theme="dark"') && str_contains($etiqueta, 'data-text-size="larger"') && str_contains($etiqueta, 'data-contrast="auto"') && str_contains($etiqueta, 'data-text-spacing="normal"') && str_contains($etiqueta, 'data-transparency="auto"'), 's0 root elige lo contrario del sitio y su <html> lo lleva', $etiqueta);
        $rechazos = [
            $peticion($rutaModo, $jwtRoot, ['mode' => 'sitio'])['status'],
            $peticion($rutaTamano, $jwtRoot, ['size' => 'SITE'])['status'],
            $peticion($ruta, $jwtRoot, ['preference' => 'zz-sabor', 'value' => 'site'])['status'],
        ];
        $check($rechazos === [400, 400, 400] && UsersModel::metaValue($idRoot, AdminPanelController::THEME_MODE_META) === 'dark' && UsersModel::metaValue($idRoot, AdminPanelController::TEXT_SIZE_META) === 'larger', 's1 DISCRIMINANTE: otro valor (sitio, SITE, o site en una preferencia desconocida) sigue dando 400 y no borra', (string) json_encode($rechazos));
        $alSitio = [
            json_decode($peticion($rutaModo, $jwtRoot, ['mode' => 'site'])['body'], true),
            json_decode($peticion($rutaTamano, $jwtRoot, ['size' => 'site'])['body'], true),
            json_decode($peticion($ruta, $jwtRoot, ['preference' => 'contrast', 'value' => 'site'])['body'], true),
            json_decode($peticion($ruta, $jwtRoot, ['preference' => 'motion', 'value' => 'site'])['body'], true),
            json_decode($peticion($ruta, $jwtRoot, ['preference' => 'focus', 'value' => 'site'])['body'], true),
            json_decode($peticion($ruta, $jwtRoot, ['preference' => 'text-spacing', 'value' => 'site'])['body'], true),
            json_decode($peticion($ruta, $jwtRoot, ['preference' => 'transparency', 'value' => 'site'])['body'], true),
        ];
        $respuestas = array_map(fn($d): string => is_array($d) && ($d['success'] ?? null) === true && ($d['choice'] ?? null) === 'site' ? (string) ($d['mode'] ?? $d['size'] ?? $d['value'] ?? '') : 'MAL', $alSitio);
        $metas = array_map(fn(string $meta) => UsersModel::metaValue($idRoot, $meta), [AdminPanelController::THEME_MODE_META, AdminPanelController::TEXT_SIZE_META, AdminPanelController::CONTRAST_META, AdminPanelController::MOTION_META, AdminPanelController::FOCUS_META, AdminPanelController::TEXT_SPACING_META, AdminPanelController::TRANSPARENCY_META]);
        $check($respuestas === ['auto', 'normal', 'more', 'reduce', 'strong', 'wide', 'reduce'] && $metas === [null, null, null, null, null, null, null], 's2 site en las tres rutas: 200, borra las siete metas y responde el valor del sitio, que es el que se aplica', (string) json_encode(['respuestas' => $respuestas, 'metas' => $metas]));
        $etiqueta = $html($peticion($inicio, $jwtRoot, null)['body']);
        $check(count(array_filter(['data-theme="auto"', 'data-text-size="normal"', 'data-contrast="more"', 'data-motion="reduce"', 'data-focus="strong"', 'data-text-spacing="wide"', 'data-transparency="reduce"'], fn(string $atributo): bool => str_contains($etiqueta, $atributo))) === 7, 's3 la petición siguiente lleva en <html> los siete del sitio', $etiqueta);
        $xpath = $dom($peticion($relativa(UsersController::routeName('form-profile', [], true)) . '?' . UsersController::PROFILE_TAB_PARAM . '=' . UsersController::PROFILE_PREFERENCES_TAB, $jwtRoot, null)['body']);
        $marcadas = $cuenta($xpath, "//*[@data-tab='preferences']//*[@role='radiogroup']//button[@aria-checked='true'][@data-theme-mode='site' or @data-text-size='site' or @data-value='site'][contains(@class,'active')]");
        $nodosNota = $xpath->query("//*[@id='user-preferences-contrast-site']");
        $nodoNota = $nodosNota !== false ? $nodosNota->item(0) : null;
        $notaContraste = $nodoNota instanceof \DOMElement ? trim($nodoNota->textContent) : '';
        $check($marcadas === 7 && $notaContraste === strReplaceTemplate(__(AdminPanelController::ADMIN_LANG_GROUP, 'Como el sitio: %s'), ['%s' => __(AdminPanelController::ADMIN_LANG_GROUP, 'Más contraste')]), 's4 sin meta, «Como el sitio» marcada en los siete grupos, y debajo el valor del sitio («Como el sitio: Más contraste»)', (string) json_encode(['marcadas' => $marcadas, 'nota' => $notaContraste], \JSON_UNESCAPED_UNICODE));
        echoTerminal(' ');

        //──── [p] Las páginas del panel con <html> propio (H325) ─────────────────────────────
        echoTerminal('[p] El acceso y los problemas de acceso, sin sesión, con los siete del sitio');
        //El sitio quedó en g1 con normal, auto, more, reduce, strong, wide y reduce.
        $delSitio = ['data-theme="auto"', 'data-text-size="normal"', 'data-contrast="more"', 'data-motion="reduce"', 'data-focus="strong"', 'data-text-spacing="wide"', 'data-transparency="reduce"'];
        set_config('current_user', null);
        set_config('pcsphp_current_user_stored', null);
        $memoriaSitio = [];
        foreach ($validos as $opcion => $valor) {
            $memoriaSitio[$opcion] = get_config($opcion);
            set_config($opcion, $valor);
        }
        $atributos = AdminPanelController::htmlPreferenceAttributes();
        foreach ($memoriaSitio as $opcion => $valor) {
            set_config($opcion, $valor);
        }
        $check(count(array_filter($delSitio, fn(string $atributo): bool => str_contains($atributos, $atributo))) === 7 && substr_count((string) preg_replace('/ data-loader-[a-z-]+="[^"]*"/', '', $atributos), 'data-') === 7, 'p1 htmlPreferenceAttributes() sin sesión: los siete del sitio, escapados', $atributos);
        $check(preg_match_all('/ data-loader-(animation|logo)(-dark|-invert)?="[^"]*"/', $atributos) === 6, 'p1b y detrás, los seis del cargador (H345)', $atributos);
        $paginas = [
            'p2 la página de acceso' => $relativa(UsersController::routeName('form-login', [], true)),
            'p3 la lista de problemas de acceso' => $relativa(UsersController::routeName('problems-list', [], true)),
        ];
        foreach ($paginas as $caso => $camino) {
            $r = $peticion($camino, null, null);
            $etiqueta = $html($r['body']);
            $check($r['status'] === 200 && count(array_filter($delSitio, fn(string $atributo): bool => str_contains($etiqueta, $atributo))) === 7, "{$caso}, sin sesión: los siete del sitio en <html>", "HTTP {$r['status']}, {$etiqueta}");
        }
        echoTerminal(' ');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        foreach ($filasAntes as $nombre => $crudo) {
            if ($crudo === null) {
                //RETORNO-IGNORADO: lo comprueba z2 comparando la huella.
                $database->prepare("DELETE FROM `{$tablaOpciones}` WHERE BINARY name = ?")->execute([$nombre]);
            } else {
                //RETORNO-IGNORADO: lo comprueba z2 comparando la huella.
                $database->prepare("UPDATE `{$tablaOpciones}` SET value = ? WHERE BINARY name = ?")->execute([$crudo, $nombre]);
            }
        }
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            foreach ($referencias as [$tabla, $columna]) {
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario de la prueba (ni sus metas)', "quedan {$quedan}");
        $huellaDespues = sha1((string) json_encode($foto()));
        $check($huellaDespues === $huellaAntes, 'z2 las siete opciones de accesibilidad, como estaban (sha1)', "{$huellaAntes} → {$huellaDespues}");
    }

    return $balance();

})->setDescription('La accesibilidad, fase 2 (H252): la ruta admin-accessibility-preference (400 fuera de lista, 200 guarda la meta y el <html> la lleva), la herencia del sitio, Configuración › Accesibilidad con contraste, movimiento, foco, espaciado del texto y transparencias (todo o nada; 403 al general) y las dos secciones de «Preferencias» y de la pantalla del sitio. Por HTTP; crea usuarios zz-agente- y los retira, y repone las opciones en crudo.')->setEffects([CliActions::EFFECT_DATABASE])->register();
