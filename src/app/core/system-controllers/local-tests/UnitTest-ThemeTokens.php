<?php

//La pantalla Colores: themeTokens() publica solo lo que se aparta de fábrica, y su acción guarda todo o nada, por HTTP.
//Crea dos usuarios zz-agente-colores-* y devuelve las opciones de color en crudo, como estaban.
//@areas src/app/classes/PiecesPHP/Settings/

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\CSSVariables;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/theme-tokens', function ($args) {

    echoTerminal("\e[33m[TEST:ThemeTokens] Los tokens del tema y la acción de Colores, que guarda todo o nada\e[39m");
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

    //Lo que escribe la pantalla, y lo que hay que devolver.
    $opciones = array_merge(SettingsController::COLOR_OPTIONS, ['theme_font', 'theme_font_size', 'theme_radius']);
    $fabrica = SettingsController::FACTORY_COLORS + [
        'meta_theme_color' => SettingsController::FACTORY_COLORS['main_brand_color'],
        'theme_font' => SettingsController::THEME_FONT_FACTORY,
        'theme_font_size' => SettingsController::THEME_FONT_SIZE_FACTORY,
        'theme_radius' => SettingsController::THEME_RADIUS_FACTORY,
    ];
    foreach (SettingsController::DATA_TOKENS as $opcion) {
        $fabrica[$opcion] = '';
    }

    //──── [a] Los tokens, en proceso: set_config() es memoria de este proceso ────────────────
    echoTerminal('[a] Con los valores de fábrica se publican solo los tres acentos');
    $enProceso = [];
    foreach ($opciones as $opcion) {
        $enProceso[$opcion] = get_config($opcion);
    }
    $fijar = function (array $valores) use ($fabrica): void {
        foreach ($valores + $fabrica as $opcion => $valor) {
            set_config($opcion, $valor);
        }
    };

    try {
        $fijar([]);
        $tokens = SettingsController::themeTokens();
        $check($tokens === ['accent' => '#356DCA', 'accent-2' => '#7A5AF8', 'accent-3' => '#C11574'], 'a1 solo accent, accent-2 y accent-3', (string) json_encode($tokens));
        $check(SettingsController::themeFontStylesheet() === null, 'a2 y la fuente del tema no lleva hoja');

        echoTerminal(' ');
        echoTerminal('[b] Lo que se aparta de fábrica se publica');
        $fijar(['data_color_2' => '#112233', 'data_color_5' => '#445566', 'theme_font' => 'poppins', 'theme_font_size' => 16, 'theme_radius' => 12]);
        $tokens = SettingsController::themeTokens();
        $check(($tokens['data-2'] ?? null) === '#112233' && ($tokens['data-5'] ?? null) === '#445566', 'b1 las series con valor');
        $check(!array_key_exists('data-3', $tokens) && !array_key_exists('data-4', $tokens), 'b2 CANARIO: las vacías no');
        $pila = SettingsController::THEME_FONTS['poppins']['stack'];
        $check(($tokens['font-body'] ?? null) === $pila && ($tokens['font-heading'] ?? null) === $pila, 'b3 la fuente, en el cuerpo y en los títulos');
        $check(SettingsController::themeFontStylesheet() === 'statics/fonts/poppins/poppins.css', 'b4 y con su hoja');
        $check(($tokens['font-size-base'] ?? null) === '16px', 'b5 el tamaño base');
        $check(($tokens['r-md'] ?? null) === '12px' && ($tokens['r-xs'] ?? null) === '6px' && ($tokens['r-sm'] ?? null) === '9px' && ($tokens['r-lg'] ?? null) === '18px', 'b6 el radio y sus tres proporciones', (string) json_encode($tokens));
        $fijar(['theme_font_size' => '15', 'theme_radius' => '4']);
        $tokens = SettingsController::themeTokens();
        $check(($tokens['font-size-base'] ?? null) === '15px' && ($tokens['r-sm'] ?? null) === '3px', 'b7 un número guardado como texto vale igual');
        $fijar(['theme_radius' => 0]);
        $check((SettingsController::themeTokens()['r-lg'] ?? null) === '0px', 'b8 un radio de 0 se publica');

        echoTerminal(' ');
        echoTerminal('[c] Lo que no vale no se publica crudo');
        $fijar(['main_brand_color' => '#123456</style><b>zz', 'second_brand_color' => 'rgba(1, 2, 3, 0.5)', 'third_brand_color' => '', 'data_color_2' => 'red;}zz', 'data_color_3' => '#FFF', 'theme_font' => 'zz-comic', 'theme_font_size' => 13, 'theme_radius' => 7]);
        $tokens = SettingsController::themeTokens();
        $check($tokens === ['accent' => '#356DCA', 'accent-2' => '#7A5AF8', 'accent-3' => '#C11574'], 'c1 los acentos caen a fábrica y lo demás no sale', (string) json_encode($tokens));
        $check(SettingsController::themeFontStylesheet() === null, 'c2 y una fuente fuera de la lista no pide hoja');
        $fijar(['theme_font_size' => [16], 'theme_radius' => 'zz']);
        $tokens = SettingsController::themeTokens();
        $check(!array_key_exists('font-size-base', $tokens) && !array_key_exists('r-md', $tokens), 'c3 ni un tamaño o un radio que no son números');
    } finally {
        foreach ($enProceso as $opcion => $valor) {
            set_config($opcion, $valor);
        }
    }

    //──── Por HTTP ───────────────────────────────────────────────────────────────────────
    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la pantalla de respaldos.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    echoTerminal(' ');
    if (!$check($base !== '' && $database !== null, 'p1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::tokenName();
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, ?string $jwt, ?array $post = null) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $cabeceras = ['X-Requested-With: XMLHttpRequest'];
        if ($jwt !== null) {
            $cabeceras[] = "{$cabeceraToken}: {$jwt}";
        }
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $cabeceras,
            CURLOPT_POST => $post !== null,
        ];
        if ($post !== null) {
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };

    $tablaOpciones = (new SettingsModel())->getModel()->getTable();
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

    $prefijo = 'zz-agente-colores-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    //Las opciones, en crudo: se reponen tal cual. null = no existía la fila.
    $filasAntes = $foto();

    try {
        $crear = function (string $nombre, int $tipo) use ($prefijo): int {
            $usuario = new UsersModel();
            $usuario->username = "{$prefijo}-{$nombre}";
            $usuario->email = "{$prefijo}-{$nombre}@example.com";
            $usuario->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
            $usuario->firstname = 'Zz';
            $usuario->secondname = '';
            $usuario->firstLastname = 'Colores';
            $usuario->secondLastname = '';
            $usuario->type = $tipo;
            $usuario->status = UsersModel::STATUS_USER_ACTIVE;
            $usuario->failedAttempts = 0;
            $usuario->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
            $usuario->createdAt = new \DateTime();
            $usuario->modifiedAt = $usuario->createdAt;
            $usuario->save();
            return (int) $usuario->id;
        };
        $jwtRoot = UserSessions::issueToken($crear('root', UsersModel::TYPE_USER_ROOT));
        $jwtAdmin = UserSessions::issueToken($crear('admin', UsersModel::TYPE_USER_ADMIN_GRAL));
        $jwtGeneral = UserSessions::issueToken($crear('general', UsersModel::TYPE_USER_GENERAL));

        $ruta = $camino(SettingsController::routeName('appearance-colors-save', [], true));
        $cuerpo = [
            'main_brand_color' => '#0e7a5a',
            'second_brand_color' => '#AA5500',
            'third_brand_color' => '#225588',
            'data_color_2' => '#112233',
            'data_color_3' => '#334455',
            'data_color_4' => '#556677',
            'data_color_5' => '#778899',
            'browser_bar' => SettingsController::BROWSER_BAR_DARK,
            'theme_font' => 'poppins',
            'theme_font_size' => '16',
            'theme_radius' => '12',
        ];
        $exito = fn (array $respuesta): bool => $respuesta['status'] === 200 && (json_decode($respuesta['body'], true)['success'] ?? null) === true;

        echoTerminal(' ');
        echoTerminal('[d] La acción guarda todo');
        $r = $pedir($ruta, $jwtRoot, $cuerpo);
        $check($exito($r), 'd1 CANARIO: el cuerpo válido se guarda', "HTTP {$r['status']}");
        $check(SettingsModel::getConfigValue('main_brand_color') === '#0E7A5A', 'd2 el acento, en mayúsculas');
        $check(SettingsModel::getConfigValue('second_brand_color') === '#AA5500' && SettingsModel::getConfigValue('third_brand_color') === '#225588', 'd3 los dos realces');
        $check(SettingsModel::getConfigValue('data_color_2') === '#112233' && SettingsModel::getConfigValue('data_color_5') === '#778899', 'd4 las series');
        $check(SettingsModel::getConfigValue('theme_font') === 'poppins' && SettingsModel::getConfigValue('theme_font_size') === 16 && SettingsModel::getConfigValue('theme_radius') === 12, 'd5 la fuente, el tamaño y el radio, estos dos como enteros');

        echoTerminal(' ');
        echoTerminal('[e] Un campo malo no escribe NINGUNO');
        $casos = [
            'un acento roto' => ['main_brand_color' => '#FFF'],
            'un realce con un cierre de estilo' => ['third_brand_color' => '#112233</style>'],
            'una serie rota' => ['data_color_4' => 'rgba(1, 2, 3, 0.5)'],
            'una barra que no existe' => ['browser_bar' => 'zz'],
            'una fuente fuera de la lista' => ['theme_font' => 'zz-comic'],
            'un tamaño fuera de la lista' => ['theme_font_size' => '13'],
            'un radio fuera de la lista' => ['theme_radius' => '7'],
            'un radio que es una lista' => ['theme_radius' => ['8']],
        ];
        $valido = ['main_brand_color' => '#123123', 'second_brand_color' => '#234234', 'third_brand_color' => '#345345', 'data_color_2' => '#456456', 'browser_bar' => SettingsController::BROWSER_BAR_ACCENT, 'theme_font' => 'public-sans', 'theme_font_size' => '15', 'theme_radius' => '4'];
        $n = 1;
        foreach ($casos as $como => $malo) {
            $antes = $foto();
            $r = $pedir($ruta, $jwtRoot, $malo + $valido + $cuerpo);
            $despues = $foto();
            $check(!$exito($r) && $despues === $antes, "e{$n} con {$como}, ninguna opción cambia", "HTTP {$r['status']}");
            $n++;
        }
        $falta = $valido + $cuerpo;
        unset($falta['third_brand_color']);
        $antes = $foto();
        $r = $pedir($ruta, $jwtRoot, $falta);
        $check(!$exito($r) && $foto() === $antes, "e{$n} sin un campo obligatorio, tampoco", "HTTP {$r['status']}");

        echoTerminal(' ');
        echoTerminal('[f] Una serie con el valor de fábrica se guarda vacía');
        $conFabrica = ['data_color_2' => '#7a5af8', 'data_color_3' => '#C11574', 'data_color_4' => '#6E4A2E', 'data_color_5' => '#1D2939'] + $cuerpo;
        $r = $pedir($ruta, $jwtRoot, $conFabrica);
        $check($exito($r) && array_filter(array_map(fn (string $opcion) => SettingsModel::getConfigValue($opcion), SettingsController::DATA_TOKENS)) === [], 'f1 las cuatro, también en minúsculas', "HTTP {$r['status']}");
        $sinSeries = $cuerpo;
        foreach (SettingsController::DATA_TOKENS as $opcion) {
            unset($sinSeries[$opcion]);
        }
        $r = $pedir($ruta, $jwtRoot, $sinSeries + ['data_color_2' => '']);
        $check($exito($r) && SettingsModel::getConfigValue('data_color_2') === '', 'f2 el vacío se admite, y una serie que no llega también', "HTTP {$r['status']}");

        echoTerminal(' ');
        echoTerminal('[g] La barra del navegador se guarda como hexadecimal');
        $r = $pedir($ruta, $jwtRoot, ['browser_bar' => SettingsController::BROWSER_BAR_ACCENT] + $cuerpo);
        $check($exito($r) && SettingsModel::getConfigValue('meta_theme_color') === '#0E7A5A', 'g1 accent: el acento', (string) json_encode(SettingsModel::getConfigValue('meta_theme_color')));
        $r = $pedir($ruta, $jwtRoot, ['browser_bar' => SettingsController::BROWSER_BAR_DARK] + $cuerpo);
        $check($exito($r) && SettingsModel::getConfigValue('meta_theme_color') === SettingsController::META_THEME_DARK, 'g2 dark: el neutro oscuro', (string) json_encode(SettingsModel::getConfigValue('meta_theme_color')));

        echoTerminal(' ');
        echoTerminal('[h] La hoja de variables publica lo guardado');
        $hoja = $pedir($camino(CSSVariables::globalVersionedURL()), $jwtRoot);
        $check($hoja['status'] === 200 && str_contains($hoja['body'], '--accent: #0E7A5A;') && str_contains($hoja['body'], '--font-size-base: 16px;') && str_contains($hoja['body'], '--r-md: 12px;') && str_contains($hoja['body'], "'Poppins'"), 'h1 acento, tamaño, radio y fuente', "HTTP {$hoja['status']}");
        $pantalla = $pedir($camino(SettingsController::routeName('appearance-colors', [], true)), $jwtRoot);
        $check($pantalla['status'] === 200 && str_contains($pantalla['body'], 'value="#0e7a5a"') && str_contains($pantalla['body'], 'value="poppins" data-font-stack="') && substr_count($pantalla['body'], ' selected') >= 3, 'h2 la pantalla pinta lo guardado', "HTTP {$pantalla['status']}");

        echoTerminal(' ');
        echoTerminal('[i] Quién puede');
        $antes = $foto();
        $r = $pedir($ruta, $jwtGeneral, ['main_brand_color' => '#010203'] + $cuerpo);
        $check($r['status'] === 403 && $foto() === $antes, 'i1 un usuario general: 403 y ninguna opción cambia', "HTTP {$r['status']}");
        $r = $pedir($ruta, null, ['main_brand_color' => '#010203'] + $cuerpo);
        $check($r['status'] !== 200 && $foto() === $antes, 'i2 sin sesión tampoco', "HTTP {$r['status']}");
        $r = $pedir($ruta, $jwtAdmin, ['main_brand_color' => '#010203'] + $cuerpo);
        $check($exito($r) && SettingsModel::getConfigValue('main_brand_color') === '#010203', 'i3 CANARIO: el administrador general sí', "HTTP {$r['status']}");
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
        //`SystemApprovalManager::init()` anota cada usuario nuevo al servir, y el perfil nace con él: esas filas no caen solas.
        $tablaAprobaciones = \SystemApprovals\Mappers\SystemApprovalsMapper::TABLE;
        $consultaUsuarios = $database->query("SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"));
        foreach ($consultaUsuarios->fetchAll(\PDO::FETCH_COLUMN) as $id) {
            $database->prepare("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = ? AND referenceValue = ?")->execute([$tablaUsuarios, (string) $id]);
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $perfiles->delete(['belongsTo' => (int) $id])->execute();
        }
        $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"));
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedan}");
        $check($foto() === $filasAntes, 'z2 las opciones quedan con su valor crudo de antes');
    }

    return $balance();

})->setDescription('La pantalla Colores: themeTokens() publica los tres acentos y lo que se aparta de fábrica, nunca un valor inválido; su acción guarda todo o nada y solo la abren el principal y el administrador general.')->setEffects([CliActions::EFFECT_DATABASE])->register();
