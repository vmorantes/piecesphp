<?php

//La pantalla Colores: themeTokens(), su guardado por HTTP y las actualizaciones de datos del 2026-11-18 (en transacciones
//que se deshacen). Crea tres usuarios zz-agente-colores-* y devuelve las opciones de color en crudo, como estaban.
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
    //main_brand_color_dark llega vacía de FACTORY_COLORS: vacía es «automático».
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
    echoTerminal('[a] Con los valores de fábrica se publica solo el acento');
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
        $check($tokens === ['accent' => '#00008B'], 'a1 solo accent: el oscuro lo calcula el tema', (string) json_encode($tokens));
        $check(SettingsController::themeFont() === 'atkinson-hyperlegible-next', 'a2 la fuente de fábrica es Atkinson Hyperlegible Next', SettingsController::themeFont());

        echoTerminal(' ');
        echoTerminal('[b] Lo que se aparta de fábrica se publica');
        $fijar(['data_color_2' => '#112233', 'data_color_5' => '#445566', 'theme_font' => 'onest', 'theme_font_size' => 16, 'theme_radius' => 12]);
        $tokens = SettingsController::themeTokens();
        $check(($tokens['data-2'] ?? null) === '#112233' && ($tokens['data-5'] ?? null) === '#445566', 'b1 las series con valor');
        $check(!array_key_exists('data-3', $tokens) && !array_key_exists('data-4', $tokens), 'b2 CANARIO: las vacías no');
        $pila = SettingsController::THEME_FONTS['onest']['stack'];
        $check(($tokens['font-body'] ?? null) === $pila && ($tokens['font-heading'] ?? null) === $pila, 'b3 la fuente, en el cuerpo y en los títulos');
        $check(str_starts_with($pila, '"Onest"') && !array_key_exists('stylesheet', SettingsController::THEME_FONTS['onest']), 'b4 con la pila del tema y sin hoja propia: la fuente la trae el tema');
        $check(($tokens['font-size-site'] ?? null) === '16px', 'b5 el tamaño base (--font-size-site, del que salen los tres niveles)');
        $check(($tokens['r-md'] ?? null) === '12px' && ($tokens['r-xs'] ?? null) === '6px' && ($tokens['r-sm'] ?? null) === '9px' && ($tokens['r-lg'] ?? null) === '18px', 'b6 el radio y sus tres proporciones', (string) json_encode($tokens));
        $fijar(['theme_font_size' => '15', 'theme_radius' => '4']);
        $tokens = SettingsController::themeTokens();
        $check(($tokens['font-size-site'] ?? null) === '15px' && ($tokens['r-sm'] ?? null) === '3px', 'b7 un número guardado como texto vale igual');
        $fijar(['theme_radius' => 0]);
        $check((SettingsController::themeTokens()['r-lg'] ?? null) === '0px', 'b8 un radio de 0 se publica');
        $fijar(['theme_font' => 'inter']);
        $check(!array_key_exists('inter', SettingsController::THEME_FONTS) && SettingsController::themeFont() === SettingsController::THEME_FONT_FACTORY && !array_key_exists('font-body', SettingsController::themeTokens()), 'b9 inter, retirada, no está en la lista: guardada, da la fábrica y no publica font-body');
        //Por call_user_func(): la llamada directa a lo que está en desuso la cuenta PHPStan.
        $check(call_user_func([SettingsController::class, 'themeFontStylesheet']) === null, 'b10 themeFontStylesheet(), en desuso, devuelve null');

        echoTerminal(' ');
        echoTerminal('[c] Lo que no vale no se publica crudo');
        $fijar(['main_brand_color' => '#123456</style><b>zz', 'second_brand_color' => 'rgba(1, 2, 3, 0.5)', 'third_brand_color' => '', 'data_color_2' => 'red;}zz', 'data_color_3' => '#FFF', 'theme_font' => 'zz-comic', 'theme_font_size' => 13, 'theme_radius' => 7]);
        $tokens = SettingsController::themeTokens();
        $check($tokens === ['accent' => '#00008B'], 'c1 los acentos caen a fábrica y lo demás no sale', (string) json_encode($tokens));
        $check(SettingsController::themeFont() === SettingsController::THEME_FONT_FACTORY, 'c2 una fuente fuera de la lista cae a fábrica');
        $fijar(['theme_font_size' => [16], 'theme_radius' => 'zz']);
        $tokens = SettingsController::themeTokens();
        $check(!array_key_exists('font-size-site', $tokens) && !array_key_exists('r-md', $tokens), 'c3 ni un tamaño o un radio que no son números');
        foreach (['poppins', 'public-sans'] as $retirada) {
            $fijar(['theme_font' => $retirada]);
            $check(SettingsController::themeFont() === SettingsController::THEME_FONT_FACTORY && !array_key_exists('font-body', SettingsController::themeTokens()), "c4 {$retirada}, retirada, cae a fábrica y no se publica");
        }

        echoTerminal(' ');
        echoTerminal('[j] El acento oscuro y los realces de fábrica');
        $fijar([]);
        $tokens = SettingsController::themeTokens();
        $check($tokens === ['accent' => '#00008B'], 'j1 acento de fábrica y oscuro vacío: solo --accent, sin --accent-dark', (string) json_encode($tokens));
        $fijar(['main_brand_color' => '#00008b']);
        $tokens = SettingsController::themeTokens();
        $check(!array_key_exists('accent-dark', $tokens), 'j1b tampoco con el acento de fábrica en minúsculas', (string) json_encode($tokens));
        $fijar(['main_brand_color' => '#123456']);
        $tokens = SettingsController::themeTokens();
        $check(($tokens['accent'] ?? null) === '#123456' && !array_key_exists('accent-dark', $tokens), 'j2 acento propio y oscuro vacío: sin --accent-dark, lo deriva el tema', (string) json_encode($tokens));
        $fijar(['main_brand_color' => '#123456', 'main_brand_color_dark' => '#33CCFF']);
        $conPropio = SettingsController::themeTokens()['accent-dark'] ?? null;
        $fijar(['main_brand_color_dark' => '#33CCFF']);
        $conFabrica = SettingsController::themeTokens()['accent-dark'] ?? null;
        $check($conPropio === '#33CCFF' && $conFabrica === '#33CCFF', 'j3 con valor, el suyo, sea el acento propio o el de fábrica', "{$conPropio} · {$conFabrica}");
        $fijar(['main_brand_color' => '#123456', 'main_brand_color_dark' => '#33CCFF</style>']);
        $check(!array_key_exists('accent-dark', SettingsController::themeTokens()) && SettingsController::accentDarkColor() === '', 'j3b un oscuro que no vale cuenta como vacío y no se publica crudo');
        $fijar(['second_brand_color' => '#0b7a77', 'third_brand_color' => '#E8730C']);
        $tokens = SettingsController::themeTokens();
        $check(!array_key_exists('accent-2', $tokens) && !array_key_exists('accent-3', $tokens), 'j4 accent-2 y accent-3 de fábrica no se publican, también en minúsculas', (string) json_encode($tokens));
        $fijar(['second_brand_color' => '#AA5500', 'third_brand_color' => '#225588']);
        $tokens = SettingsController::themeTokens();
        $check(($tokens['accent-2'] ?? null) === '#AA5500' && ($tokens['accent-3'] ?? null) === '#225588', 'j4b CANARIO: los propios sí');
        $check(SettingsController::isBrandColorValue('main_brand_color_dark', '') && !SettingsController::isBrandColorValue('main_brand_color', ''), 'j4c el vacío vale para el oscuro y no para el acento');
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
    $cabeceraToken = SessionToken::headerName();
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

    //──── [j5]–[j7] Las actualizaciones del 2026-11-18, cada prueba en una transacción que se deshace ─────
    $pdoAct = SettingsModel::model()->getDatabase();
    //Pone las filas, ejecuta el archivo, lee y deshace. Devuelve [ajenas, error, leídos, intacto tras el ROLLBACK].
    $probarActualizacion = function (string $archivo, array $filas) use ($proyecto, $tablaOpciones, $pdoAct, $crudoDe): array {
        $lineas = array_filter(preg_split('/\R/', (string) @file_get_contents("{$proyecto}/databases/actualizaciones/{$archivo}")) ?: [], fn (string $linea): bool => !str_starts_with(ltrim($linea), '--'));
        $sentencias = array_values(array_filter(array_map('trim', explode(';', implode("\n", $lineas))), fn (string $sentencia): bool => $sentencia !== ''));
        //Una sentencia que no sea UPDATE podría confirmar sola (DDL) y la transacción ya no desharía nada: no se ejecuta.
        $ajenas = array_values(array_filter($sentencias, fn (string $sentencia): bool => preg_match('/^UPDATE\s+`' . preg_quote($tablaOpciones, '/') . '`\s/i', $sentencia) !== 1));
        if ($sentencias === [] || $ajenas !== [] || $pdoAct === null) {
            return [$sentencias === [] ? ['(vacío)'] : $ajenas, 'sin ejecutar', [], false];
        }
        $nombres = array_keys($filas);
        $fotoFilas = fn (): array => array_combine($nombres, array_map($crudoDe, $nombres));
        $antes = $fotoFilas();
        $leidos = [];
        $error = '';
        try {
            $pdoAct->beginTransaction();
            foreach ($filas as $nombre => $valor) {
                $existe = $pdoAct->prepare("SELECT COUNT(*) FROM `{$tablaOpciones}` WHERE BINARY name = ?");
                $existe->execute([$nombre]);
                $pdoAct->prepare((int) $existe->fetchColumn() > 0
                    ? "UPDATE `{$tablaOpciones}` SET value = ? WHERE BINARY name = ?"
                    : "INSERT INTO `{$tablaOpciones}` (value, name) VALUES (?, ?)")->execute([$valor, $nombre]);
            }
            foreach ($sentencias as $sentencia) {
                $pdoAct->exec($sentencia);
            }
            foreach ($nombres as $nombre) {
                $lectura = $pdoAct->prepare("SELECT value FROM `{$tablaOpciones}` WHERE BINARY name = ?");
                $lectura->execute([$nombre]);
                $leidos[$nombre] = (string) $lectura->fetchColumn();
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        } finally {
            if ($pdoAct->inTransaction()) {
                $pdoAct->rollBack();
            }
        }
        return [[], $error, $leidos, $fotoFilas() === $antes];
    };

    echoTerminal(' ');
    echoTerminal('[j5] La actualización de los colores de fábrica cambia la fábrica vieja y no toca un color propio');
    [$ajenasD4, $errorD4, $leidosD4, $intactoD4] = $probarActualizacion('2026-11-18-colores-de-fabrica-del-logo.sql', [
        'main_brand_color' => '#356dca',
        'meta_theme_color' => ' #356DCA ',
        'second_brand_color' => '#7A5AF8',
        'data_color_2' => '#7a5af8',
        'third_brand_color' => '#c11574',
        'data_color_3' => '#123456',
        'data_color_4' => '#356DCA',
    ]);
    if ($check($ajenasD4 === [] && $errorD4 !== 'sin ejecutar', 'j5a el archivo son solo UPDATE sobre la tabla de opciones', implode(' | ', $ajenasD4))) {
        $esperadosD4 = ['main_brand_color' => '#00008B', 'meta_theme_color' => '#00008B', 'second_brand_color' => '#0B7A77', 'data_color_2' => '#0B7A77', 'third_brand_color' => '#E8730C'];
        $check($errorD4 === '' && array_intersect_key($leidosD4, $esperadosD4) === $esperadosD4, 'j5b la fábrica vieja pasa a la del logo, en mayúsculas o minúsculas y con espacios', $errorD4 !== '' ? $errorD4 : (string) json_encode($leidosD4));
        $check($errorD4 === '' && ($leidosD4['data_color_3'] ?? null) === '#123456', 'j5c un color propio no se toca', (string) json_encode($leidosD4['data_color_3'] ?? null));
        $check($errorD4 === '' && ($leidosD4['data_color_4'] ?? null) === '#356DCA', 'j5d el color viejo en una opción que la actualización no nombra tampoco', (string) json_encode($leidosD4['data_color_4'] ?? null));
        $check($intactoD4, 'j5e tras el ROLLBACK, las siete opciones siguen como estaban');
    }

    echoTerminal(' ');
    echoTerminal('[j6] La actualización de las series deja vacías la 2 y la 3 con la fábrica nueva, y nada más');
    $intactoJ6 = true;
    $j6 = [
        'mayúsculas' => ['data_color_2' => '#0B7A77', 'data_color_3' => '#E8730C', 'data_color_4' => '#0B7A77', 'second_brand_color' => '#0B7A77'],
        'minúsculas y espacios' => ['data_color_2' => ' #0b7a77 ', 'data_color_3' => '#e8730c ', 'data_color_4' => '#E8730C', 'third_brand_color' => '#E8730C'],
        'propios' => ['data_color_2' => '#112233', 'data_color_3' => '#0B7A77'],
    ];
    foreach ($j6 as $como => $filas) {
        [$ajenas, $error, $leidos, $intacto] = $probarActualizacion('2026-11-18-series-de-fabrica-vacias.sql', $filas);
        $intactoJ6 = $intactoJ6 && $intacto;
        $detalle = $ajenas !== [] ? implode(' | ', $ajenas) : ($error !== '' ? $error : (string) json_encode($leidos));
        if ($como === 'propios') {
            $check($ajenas === [] && $error === '' && $leidos === $filas, 'j6c un color propio, y la fábrica de la 3 en la 2, no se tocan', $detalle);
            continue;
        }
        $otras = array_diff_key($filas, ['data_color_2' => 1, 'data_color_3' => 1]);
        $check($ajenas === [] && $error === '' && ($leidos['data_color_2'] ?? null) === '' && ($leidos['data_color_3'] ?? null) === '' && array_intersect_key($leidos, $otras) === $otras, "j6" . ($como === 'mayúsculas' ? 'a' : 'b') . " con {$como}: las series 2 y 3 quedan vacías; la fábrica en otra opción no se toca", $detalle);
    }
    $check($intactoJ6, 'j6d tras cada ROLLBACK, las opciones siguen como estaban');

    echoTerminal(' ');
    echoTerminal('[j7] La actualización de la fuente pasa inter a la de fábrica, y nada más');
    $intactoJ7 = true;
    foreach (['inter' => 'atkinson-hyperlegible-next', 'Inter' => 'atkinson-hyperlegible-next', ' inter ' => 'atkinson-hyperlegible-next', 'onest' => 'onest', 'interfaz' => 'interfaz'] as $guardada => $esperada) {
        [$ajenas, $error, $leidos, $intacto] = $probarActualizacion('2026-11-18-fuente-de-fabrica.sql', ['theme_font' => $guardada]);
        $intactoJ7 = $intactoJ7 && $intacto;
        $check($ajenas === [] && $error === '' && ($leidos['theme_font'] ?? null) === $esperada, "j7 «{$guardada}» → «{$esperada}»", $ajenas !== [] ? implode(' | ', $ajenas) : ($error !== '' ? $error : (string) json_encode($leidos)));
    }
    $check($intactoJ7, 'j7b tras cada ROLLBACK, theme_font sigue como estaba');

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
            'main_brand_color_dark' => '#33ccff',
            'data_color_2' => '#112233',
            'data_color_3' => '#334455',
            'data_color_4' => '#556677',
            'data_color_5' => '#778899',
            'browser_bar' => SettingsController::BROWSER_BAR_DARK,
            'theme_font' => 'onest',
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
        $check(SettingsModel::getConfigValue('theme_font') === 'onest' && SettingsModel::getConfigValue('theme_font_size') === 16 && SettingsModel::getConfigValue('theme_radius') === 12, 'd5 la fuente, el tamaño y el radio, estos dos como enteros');
        $check(SettingsModel::getConfigValue('main_brand_color_dark') === '#33CCFF', 'd6 el acento oscuro, en mayúsculas');
        $sinOscuro = $cuerpo;
        unset($sinOscuro['main_brand_color_dark']);
        $r = $pedir($ruta, $jwtRoot, $sinOscuro);
        $check($exito($r) && SettingsModel::getConfigValue('main_brand_color_dark') === '', 'd7 sin el oscuro se guarda vacío: automático', "HTTP {$r['status']}");
        $r = $pedir($ruta, $jwtRoot, $cuerpo);
        $check($exito($r) && SettingsModel::getConfigValue('main_brand_color_dark') === '#33CCFF', 'd8 CANARIO: el cuerpo entero vuelve a guardar el oscuro', "HTTP {$r['status']}");

        echoTerminal(' ');
        echoTerminal('[e] Un campo malo no escribe NINGUNO');
        $casos = [
            'un acento roto' => ['main_brand_color' => '#FFF'],
            'un acento vacío' => ['main_brand_color' => ''],
            'un acento oscuro roto' => ['main_brand_color_dark' => '#33CCF'],
            'un realce con un cierre de estilo' => ['third_brand_color' => '#112233</style>'],
            'una serie rota' => ['data_color_4' => 'rgba(1, 2, 3, 0.5)'],
            'una barra que no existe' => ['browser_bar' => 'zz'],
            'una fuente fuera de la lista' => ['theme_font' => 'zz-comic'],
            'una fuente retirada' => ['theme_font' => 'poppins'],
            'un tamaño fuera de la lista' => ['theme_font_size' => '13'],
            'un radio fuera de la lista' => ['theme_radius' => '7'],
            'un radio que es una lista' => ['theme_radius' => ['8']],
        ];
        $valido = ['main_brand_color' => '#123123', 'second_brand_color' => '#234234', 'third_brand_color' => '#345345', 'data_color_2' => '#456456', 'browser_bar' => SettingsController::BROWSER_BAR_ACCENT, 'theme_font' => 'red-hat-text', 'theme_font_size' => '15', 'theme_radius' => '4'];
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
        $conFabrica = ['data_color_2' => '#0b7a77', 'data_color_3' => '#E8730C', 'data_color_4' => '#6e4a2e', 'data_color_5' => '#1D2939'] + $cuerpo;
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
        $check($hoja['status'] === 200 && str_contains($hoja['body'], '--accent: #0E7A5A;') && str_contains($hoja['body'], '--font-size-site: 16px;') && str_contains($hoja['body'], '--r-md: 12px;') && str_contains($hoja['body'], '"Onest"') && str_contains($hoja['body'], '--accent-dark: #33CCFF;'), 'h1 acento, su oscuro, tamaño, radio y fuente', "HTTP {$hoja['status']}");
        $pantalla = $pedir($camino(SettingsController::routeName('appearance-colors', [], true)), $jwtRoot);
        $check($pantalla['status'] === 200 && str_contains($pantalla['body'], 'value="#0e7a5a"') && str_contains($pantalla['body'], 'value="onest" data-font-stack="') && substr_count($pantalla['body'], ' selected') >= 3, 'h2 la pantalla pinta lo guardado', "HTTP {$pantalla['status']}");
        $tonos = [
            '--accent-100' => '14 % sobre el fondo; en claro, con un mínimo de luminosidad',
            '--accent-300' => '45 % sobre el fondo; en claro, con un mínimo de luminosidad',
            '--accent-600' => 'El acento con la luminosidad acotada: como mucho 0,5 en claro; como poco 0,72 en oscuro, con el croma limitado',
            '--accent-50' => 'Fondos activos · 8 % sobre el fondo; en claro, con un mínimo de luminosidad',
        ];
        $sinTitulo = array_keys(array_filter($tonos, fn (string $titulo, string $tono): bool => !str_contains($pantalla['body'], 'title="' . escape_html(__(SettingsController::LANG_GROUP, $titulo)) . '" data-colors-tone="' . $tono . '"'), \ARRAY_FILTER_USE_BOTH));
        $check($sinTitulo === [], 'h3 cada tono derivado dice cómo sale (title)', implode(', ', $sinTitulo));
        $check(str_contains($pantalla['body'], 'id="colors-accent-dark" value="#33ccff" name="main_brand_color_dark"') && $pantalla['status'] === 200 && !str_contains($pantalla['body'], 'data-accent-factory') && !str_contains($pantalla['body'], 'data-accent-dark-factory'), 'h4 la pantalla pinta el oscuro guardado, sin acento ni oscuro de fábrica en el formulario', "HTTP {$pantalla['status']}");
        $check($pantalla['status'] === 200 && str_contains($pantalla['body'], 'value="onest" data-font-stack="') && !str_contains($pantalla['body'], 'value="inter"'), 'h5 el selector de fuente no ofrece inter', "HTTP {$pantalla['status']}");
        //El acento es un relleno: se juzga a 3:1; el texto sobre él lo garantiza el tema (0.18.0). El umbral de texto ya no sale.
        $coloresJs = (string) file_get_contents(basepath('app/classes/PiecesPHP/Settings/Statics/js/colors.js'));
        $check(!str_contains($coloresJs, 'mínimo 4,5:1') && !str_contains($pantalla['body'], 'mínimo 4,5:1') && str_contains($coloresJs, 'mínimo 3:1') && str_contains($coloresJs, "measured('light')"), 'h6 Colores juzga el acento como relleno (3:1) con lo medido en cada modo, y no a 4,5:1');

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

})->setDescription('La pantalla Colores: themeTokens() publica siempre el acento, el oscuro solo si se guardó y lo que se aparta de fábrica, nunca un valor inválido; su acción guarda todo o nada y solo la abren el principal y el administrador general; las actualizaciones del 2026-11-18 cambian solo la fábrica vieja, las series de fábrica y la fuente inter.')->setEffects([CliActions::EFFECT_DATABASE])->register();
