<?php

//La accesibilidad (H252): el tamaño de letra y el modo de color del sitio (Apariencia › Accesibilidad) y los de cada
//persona. Por HTTP, con usuarios zz-agente- propios que se retiran al final; las opciones se reponen en crudo.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/text-size', function ($args) {

    echoTerminal("\e[33m[TEST:TextSize] La accesibilidad: el tamaño de letra y el modo de color, del sitio y de cada persona\e[39m");
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

    //──── [s] El nivel del sitio, en proceso: set_config() es memoria de este proceso ────────
    echoTerminal('[s] El nivel del sitio');
    $memoria = [
        SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG => get_config(SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG),
        SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG => get_config(SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG),
    ];
    try {
        set_config(SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG, 'large');
        set_config(SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG, 'dark');
        $check(SettingsController::accessibilityTextSize() === 'large' && SettingsController::accessibilityThemeMode() === 'dark', 's1 lo guardado se lee');
        set_config(SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG, 'zz-enorme');
        set_config(SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG, ['dark']);
        $check(SettingsController::accessibilityTextSize() === AdminPanelController::TEXT_SIZE_NORMAL && SettingsController::accessibilityThemeMode() === AdminPanelController::THEME_MODE_AUTO, 's2 lo que no vale cae a normal y a auto');
    } finally {
        foreach ($memoria as $opcion => $valor) {
            set_config($opcion, $valor);
        }
    }
    echoTerminal(' ');

    //──── [x] Los píxeles de cada nivel, en proceso ──────────────────────────────────────
    echoTerminal('[x] Los píxeles de cada nivel');
    $baseGuardada = get_config('theme_font_size');
    try {
        set_config('theme_font_size', 14);
        $catorce = [AdminPanelController::textSizePixels('normal'), AdminPanelController::textSizePixels('large'), AdminPanelController::textSizePixels('larger')];
        set_config('theme_font_size', 16);
        $dieciseis = [AdminPanelController::textSizePixels('normal'), AdminPanelController::textSizePixels('large'), AdminPanelController::textSizePixels('larger')];
        $check($catorce === [14, 16, 18], 't5a con la base en 14: 14, 16 y 18', json_encode($catorce) ?: '');
        $check($dieciseis === [16, 18, 20], 't5b con la base en 16: 16, 18 y 20', json_encode($dieciseis) ?: '');
        $check(AdminPanelController::textSizePixels('zz-enorme') === 16, 't5c un nivel que no vale cuenta como normal');
    } finally {
        set_config('theme_font_size', $baseGuardada);
    }
    echoTerminal(' ');

    //──── Por HTTP ───────────────────────────────────────────────────────────────────────
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

    $tablaOpciones = (new SettingsModel())->getModel()->getTable();
    $opciones = [SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG, SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG];
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
    //Las opciones, en crudo: se reponen tal cual. null = no existía la fila.
    $filasAntes = $foto();

    $prefijo = 'zz-agente-letra-' . bin2hex(random_bytes(3));
    $ids = [];

    try {
        $crear = function (string $nombre, int $tipo) use ($prefijo, &$ids): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$nombre}";
            $u->email = "{$prefijo}-{$nombre}@example.invalid";
            $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Letra';
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

        //──── [g] Guardar la pantalla Accesibilidad ──────────────────────────────────────────
        echoTerminal('[g] Guardar la pantalla Accesibilidad');
        $guardar = $relativa(SettingsController::routeName('appearance-accessibility-save', [], true));
        $r = $peticion($guardar, $jwtRoot, [SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG => 'larger', SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG => 'dark']);
        $check($exito($r) && $crudoDe(SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG) === 'larger' && $crudoDe(SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG) === 'dark', 'g1 guarda los dos valores válidos', "HTTP {$r['status']}");
        foreach ([
            't7a un tamaño fuera de la lista' => [SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG => 'zz-enorme', SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG => 'light'],
            't7b un modo fuera de la lista' => [SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG => 'normal', SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG => 'zz-sepia'],
            't7c sin el tamaño' => [SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG => 'light'],
        ] as $caso => $cuerpo) {
            $antes = $foto();
            $r = $peticion($guardar, $jwtRoot, $cuerpo);
            $check(!$exito($r) && $foto() === $antes, "{$caso}: no guarda nada", "HTTP {$r['status']}, " . json_encode($foto()));
        }
        $idGeneral = $crear('general', UsersModel::TYPE_USER_GENERAL);
        $antes = $foto();
        $r = $peticion($guardar, UserSessions::issueToken($idGeneral), [SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG => 'normal', SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG => 'light']);
        $check($r['status'] === 403 && $foto() === $antes, 'g2 un usuario general: 403 y nada cambia', "HTTP {$r['status']}");
        $r = $peticion($relativa(SettingsController::routeName('appearance-accessibility', [], true)), $jwtRoot, null);
        $check($r['status'] === 200 && str_contains($r['body'], 'name="accessibility_text_size"') && str_contains($r['body'], '<option value="larger" selected>'), 'g3 la pantalla pinta lo guardado', "HTTP {$r['status']}");
        echoTerminal(' ');

        //──── [p] El nivel de la persona ─────────────────────────────────────────────────────
        echoTerminal('[p] El nivel de la persona');
        //Solo la etiqueta de apertura de <html>: los atributos no pueden venir de otro sitio.
        $html = fn (string $cuerpo): string => preg_match('/<html\b[^>]*>/i', $cuerpo, $m) === 1 ? $m[0] : '';
        $inicio = $relativa(AdminPanelController::routeName('', [], true));
        $tamano = $relativa(AdminPanelController::routeName('text-size', [], true));
        $baseLetra = SettingsController::themeFontSize();

        $memoriaSitio = [
            SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG => get_config(SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG),
            SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG => get_config(SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG),
        ];
        try {
            set_config(SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG, 'large');
            set_config(SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG, 'dark');
            $check(UsersModel::metaValue($idRoot, AdminPanelController::TEXT_SIZE_META) === null && AdminPanelController::textSizeOf($idRoot) === 'large', 't3 sin elegir, el tamaño del sitio');
            $check(UsersModel::metaValue($idRoot, AdminPanelController::THEME_MODE_META) === null && AdminPanelController::themeModeOf($idRoot) === 'dark', 't4 sin elegir, el modo del sitio');
            //Un valor guardado que ya no vale (de otra versión, o escrito a mano) también sigue al sitio. Se retira al salir.
            $otroID = $crear('viejo', UsersModel::TYPE_USER_GENERAL);
            UsersModel::setMetaValue($otroID, AdminPanelController::TEXT_SIZE_META, 'zz-viejo');
            UsersModel::setMetaValue($otroID, AdminPanelController::THEME_MODE_META, 'zz-viejo');
            $check(AdminPanelController::textSizeOf($otroID) === 'large', 't3b un tamaño guardado que no vale: el del sitio');
            $check(AdminPanelController::themeModeOf($otroID) === 'dark', 't4b un modo guardado que no vale: el del sitio');
        } finally {
            foreach ($memoriaSitio as $opcion => $valor) {
                set_config($opcion, $valor);
            }
        }

        $r = $peticion($tamano, $jwtRoot, ['size' => 'zz-enorme']);
        $check($r['status'] === 400 && UsersModel::metaValue($idRoot, AdminPanelController::TEXT_SIZE_META) === null, 't1 un valor fuera de lista: 400 y no guarda', "HTTP {$r['status']}");
        $r = $peticion($tamano, null, ['size' => 'larger']);
        $check($r['status'] !== 200 && UsersModel::metaValue($idRoot, AdminPanelController::TEXT_SIZE_META) === null, 't1b sin sesión no entra ni guarda', "HTTP {$r['status']}");
        $r = $peticion($tamano, $jwtRoot, ['size' => 'larger']);
        $datos = json_decode($r['body'], true);
        $check($r['status'] === 200 && ($datos['success'] ?? null) === true && ($datos['size'] ?? null) === 'larger' && ($datos['pixels'] ?? null) === $baseLetra + 4 && UsersModel::metaValue($idRoot, AdminPanelController::TEXT_SIZE_META) === 'larger', 't2 guarda uno válido y responde sus píxeles', "HTTP {$r['status']}, {$r['body']}");

        //El sitio (g1 lo dejó en larger y dark) manda para quien no eligió: otro usuario sin metas.
        $peticion($guardar, $jwtRoot, [SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG => 'large', SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG => 'dark']);
        $jwtOtro = UserSessions::issueToken($crear('otro', UsersModel::TYPE_USER_ROOT));
        $r = $peticion($inicio, $jwtOtro, null);
        $etiqueta = $html($r['body']);
        $check($r['status'] === 200 && str_contains($etiqueta, 'data-text-size="large"') && !str_contains($etiqueta, 'style=') && str_contains($etiqueta, 'data-theme="dark"'), 't6a quien no eligió: el tamaño y el modo del sitio en <html>, sin style (lo traduce el tema)', "HTTP {$r['status']}, {$etiqueta}");
        $r = $peticion($inicio, $jwtRoot, null);
        $etiqueta = $html($r['body']);
        $check($r['status'] === 200 && str_contains($etiqueta, 'data-text-size="larger"') && !str_contains($etiqueta, 'style='), 't6b quien eligió: lo suyo, sin style', "HTTP {$r['status']}, {$etiqueta}");
        $peticion($tamano, $jwtRoot, ['size' => 'normal']);
        $r = $peticion($inicio, $jwtRoot, null);
        $etiqueta = $html($r['body']);
        $check($r['status'] === 200 && str_contains($etiqueta, 'data-text-size="normal"') && !str_contains($etiqueta, 'style='), 't6c normal: data-text-size y sin style', "HTTP {$r['status']}, {$etiqueta}");
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        foreach ($filasAntes as $nombre => $crudo) {
            if ($crudo === null) {
                $database->prepare("DELETE FROM `{$tablaOpciones}` WHERE BINARY name = ?")->execute([$nombre]);
            } else {
                $database->prepare("UPDATE `{$tablaOpciones}` SET value = ? WHERE BINARY name = ?")->execute([$crudo, $nombre]);
            }
        }
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            foreach ($referencias as [$tabla, $columna]) {
                $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedan}");
        $check($foto() === $filasAntes, 'z2 las opciones quedan con su valor crudo de antes');
    }

    return $balance();

})->setDescription('La accesibilidad (H252): el tamaño de letra y el modo de color del sitio se leen y caen a normal y a auto si no valen; su pantalla guarda los dos o ninguno, rechaza lo que está fuera de lista y solo la guardan el principal y el administrador general. Por HTTP; crea usuarios zz-agente- y los retira, y repone las opciones en crudo.')->setEffects([CliActions::EFFECT_DATABASE])->register();
