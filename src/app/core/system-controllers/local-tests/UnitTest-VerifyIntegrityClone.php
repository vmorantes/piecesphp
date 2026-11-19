<?php

//H364 y H366: verify-integrity con módulos apagados y con la línea base del código propio de un clon.
//Simula el clon con PCSPHP_CLONE_BASELINE en app/cache y dos controladores de mentira allí; lo borra todo.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\VerifyIntegrityTask;

CliActions::make('unit-tests:core/verify-integrity-clone', function ($args) {

    echoTerminal("\e[33m[TEST:verify-integrity-clone] Módulos apagados y línea base del clon\e[39m");
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

    /** Llama a un método protegido de la tarea, que es donde viven las comprobaciones. */
    $call = function (string $method, array $arguments = []) {
        return (new \ReflectionMethod(VerifyIntegrityTask::class, $method))->invokeArgs(null, $arguments);
    };

    $suffix = bin2hex(random_bytes(4));
    $directory = basepath("app/cache/zz-vi-clone-{$suffix}");
    $baselineFile = "{$directory}/verify-integrity.json";
    $previousEnv = getenv('PCSPHP_CLONE_BASELINE');

    try {
        if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
            throw new \RuntimeException("no se pudo crear {$directory}");
        }

        //──── 1. Módulos apagados (H364), en el framework, con todos encendidos ─────────────
        echoTerminal('[1] Módulos apagados: con todos encendidos nada cambia, y el canario sigue viendo');
        //Los cuatro dan por hecho el framework con todo encendido: solo un clon (la distribución) puede tener módulos apagados.
        $offNow = $call('offModuleRoutePrefixes');
        if ($offNow !== [] && \Terminal\Distribution::isDistribution()) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m 1a-1d: esta instalación tiene módulos apagados (" . implode(', ', array_keys($offNow)) . '); las comprobaciones de abajo sí corren.');
        } else {
            $check($call('offModuleRoutePrefixes') === [], '1a en el framework ningún módulo está apagado');
            $check($call('offModuleOfRoute', ['publications-admin-list']) === null, '1b una ruta de un módulo encendido no es de un módulo apagado');
            $usersList = ['users-list' => ['requireLogin' => true, 'roles' => [0, 1]]];
            $few = $usersList + array_fill_keys(array_map(fn (int $i): string => "zz-ruta-{$i}", range(1, 203)), ['requireLogin' => true, 'roles' => [0]]);
            $canary = $call('validateDeclaredPermissions', [$few]);
            $check(count($canary) === 1 && str_contains($canary[0], 'más de 300'), '1c con 204 rutas y ningún módulo apagado, el canario cae', implode(' | ', $canary));
            $many = $usersList + array_fill_keys(array_map(fn (int $i): string => "zz-ruta-{$i}", range(1, 310)), ['requireLogin' => true, 'roles' => [0]]);
            $check($call('validateDeclaredPermissions', [$many]) === [], '1d con 311 rutas no cae');
        }
        echoTerminal(' ');

        //──── 1bis. Las tres formas de apagar (H400), sobre un árbol de mentira ───────────────
        echoTerminal('[1bis] Módulos apagados por `…ENABLE`, por `->active()` y por la lista declarada');
        $upper = strtoupper($suffix);
        $offConstant = "ZZ_VI_OFF_{$upper}";
        $onConstant = "ZZ_VI_ON_{$upper}";
        define($offConstant, false);
        define($onConstant, true);
        $namespace = "ZzViOff{$suffix}";
        $classesRoot = "{$directory}/clases";
        $fakeFiles = [
            'A/ZzSwitchRoutes.php' => "<?php\nnamespace {$namespace}\\A;\nclass ZzSwitchRoutes\n{\n    const ZZ_FEATURE_ENABLE = {$offConstant};\n}\n",
            'A/ZzAController.php' => "<?php\nnamespace {$namespace}\\A;\nclass ZzAController\n{\n    protected static \$baseRouteName = 'zz-vi-a';\n}\n",
            'B/ZzPartRoutes.php' => "<?php\nnamespace {$namespace}\\B;\nclass ZzPartRoutes\n{\n    const ENABLE = {$onConstant};\n    const ENABLE_ZZ_PART = {$offConstant};\n}\n",
            'B/ZzBController.php' => "<?php\nnamespace {$namespace}\\B;\nclass ZzBController\n{\n    protected static \$baseRouteName = 'zz-vi-b';\n}\n",
            'C/ZzGroup.php' => "<?php\nnamespace {$namespace}\\C;\nclass ZzGroup\n{\n    protected static \$baseRouteName = 'zz-vi-c';\n    public static function routes(\$group)\n    {\n        \$group->active({$offConstant});\n    }\n}\n",
            'D/ZzGroupOn.php' => "<?php\nnamespace {$namespace}\\D;\nclass ZzGroupOn\n{\n    protected static \$baseRouteName = 'zz-vi-d';\n    public static function routes(\$group)\n    {\n        \$group->active({$onConstant});\n    }\n}\n",
            'E/ZzCommented.php' => "<?php\nnamespace {$namespace}\\E;\nclass ZzCommented\n{\n    protected static \$baseRouteName = 'zz-vi-e';\n    /** No `->active({$offConstant})` aquí. */\n    public static function routes(\$group)\n    {\n        // \$group->active({$offConstant});\n    }\n}\n",
            'F/ZzQuoted.php' => "<?php\nnamespace {$namespace}\\F;\nclass ZzQuoted\n{\n    protected static \$baseRouteName = 'zz-vi-f';\n    const TEXT = '\$group->active({$offConstant})';\n}\n",
            'G/ZzOnRoutes.php' => "<?php\nnamespace {$namespace}\\G;\nclass ZzOnRoutes\n{\n    const ENABLE = {$onConstant};\n    const ZZ_PART_ENABLE = {$offConstant};\n}\n",
            'G/ZzGController.php' => "<?php\nnamespace {$namespace}\\G;\nclass ZzGController\n{\n    protected static \$baseRouteName = 'zz-vi-g';\n}\n",
        ];
        foreach ($fakeFiles as $relative => $content) {
            $fakePath = "{$classesRoot}/{$relative}";
            if ((!is_dir(dirname($fakePath)) && !mkdir(dirname($fakePath), 0775, true)) || file_put_contents($fakePath, $content) === false) {
                throw new \RuntimeException("no se pudo escribir {$fakePath}");
            }
        }
        require_once "{$classesRoot}/A/ZzSwitchRoutes.php";
        require_once "{$classesRoot}/B/ZzPartRoutes.php";
        require_once "{$classesRoot}/G/ZzOnRoutes.php";
        $check($call('offModuleOfRoute', ['zz-vi-a-list', $classesRoot]) === "{$namespace}\\A\\ZzSwitchRoutes", '1e `const …_ENABLE` en falso apaga la carpeta de su clase *Routes');
        $check($call('offModuleOfRoute', ['zz-vi-b-list', $classesRoot]) === null, '1f `const ENABLE_…` en falso apaga una parte, no la carpeta');
        $check($call('offModuleOfRoute', ['zz-vi-c-list', $classesRoot]) === "{$namespace}\\C\\ZzGroup", '1g `->active(CONSTANTE)` en falso apaga el $baseRouteName de su archivo');
        $check($call('offModuleOfRoute', ["{$namespace}\\C\\ZzGroup", $classesRoot]) === "{$namespace}\\C\\ZzGroup", '1h y el nombre de su clase, el de sus estáticos');
        $check($call('offModuleOfRoute', ['zz-vi-d-list', $classesRoot]) === null, '1i `->active(CONSTANTE)` en verdadero no apaga nada');
        $check($call('offModuleOfRoute', ['zz-vi-e-list', $classesRoot]) === null, '1i2 `->active(CONSTANTE)` en falso dentro de un comentario no apaga nada');
        $check($call('offModuleOfRoute', ['zz-vi-f-list', $classesRoot]) === null, '1i3 `->active(CONSTANTE)` en falso dentro de una cadena no apaga nada');
        $check($call('offModuleOfRoute', ['zz-vi-g-list', $classesRoot]) === null, '1i4 con `ENABLE` en verdadero, un `…_ENABLE` en falso no apaga la carpeta');
        $check($call('offModuleOfRoute', ['zz-vi-suelta', $classesRoot, ['zz-vi-suelta' => $offConstant]]) === $offConstant, '1j una ruta de la lista declarada con su constante en falso no aplica');
        $check($call('offModuleOfRoute', ['zz-vi-suelta', $classesRoot, ['zz-vi-suelta' => $onConstant]]) === null, '1k con su constante en verdadero, sí aplica');
        echoTerminal(' ');

        //──── 1ter. La lista declarada sigue lo que registra el código ────────────────────────
        echoTerminal('[1ter] OFF_ROUTES_BY_CONSTANT: la ruta se registra detrás de esa constante');
        /** Si `$file` registra `$route` con `CONSTANTE ? new Route(…, <sufijo>, …) : …`, por tokens. */
        $registeredBehind = static function (string $file, string $constantName, string $route): bool {
            $tokens = array_values(array_filter(
                \PhpToken::tokenize((string) file_get_contents($file)),
                fn (\PhpToken $t): bool => !$t->is([\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT])
            ));
            $base = null;
            foreach ($tokens as $i => $token) {
                if ($token->is(\T_VARIABLE) && $token->text === '$baseRouteName' && ($tokens[$i + 1]->text ?? '') === '=' && ($tokens[$i + 2] ?? null)?->is(\T_CONSTANT_ENCAPSED_STRING)) {
                    $base = substr($tokens[$i + 2]->text, 1, -1);
                    break;
                }
            }
            if ($base === null || !str_starts_with($route, $base)) {
                return false;
            }
            $tail = "'" . substr($route, strlen($base)) . "'";
            foreach ($tokens as $i => $token) {
                if (!$token->is(\T_STRING) || $token->text !== $constantName || ($tokens[$i + 1]->text ?? '') !== '?' || !($tokens[$i + 2] ?? null)?->is(\T_NEW)) {
                    continue;
                }
                $depth = 0;
                for ($j = $i + 3; $j < count($tokens); $j++) {
                    $depth += $tokens[$j]->text === '(' ? 1 : ($tokens[$j]->text === ')' ? -1 : 0);
                    if ($tokens[$j]->text === $tail) {
                        return true;
                    }
                    if ($depth === 0 && $tokens[$j]->text === ')') {
                        break;
                    }
                }
            }
            return false;
        };
        $declared = VerifyIntegrityTask::OFF_ROUTES_BY_CONSTANT;
        $homeImage = 'helpers-system-generic-content-admin-forms-home-image';
        $genericContent = basepath('app/classes/PiecesPHP/BuiltIn/Helpers/Controllers/GenericContentController.php');
        $check(array_keys($declared) === [$homeImage], '1l la lista declarada tiene solo las rutas que esta prueba vigila', implode(', ', array_keys($declared)));
        $check(defined((string) ($declared[$homeImage] ?? '')), '1m su constante existe', (string) ($declared[$homeImage] ?? ''));
        $check($registeredBehind($genericContent, (string) ($declared[$homeImage] ?? ''), $homeImage), '1n GenericContentController registra la ruta detrás de esa constante');
        $check($call('offModuleOfRoute', [$homeImage]) === (HOME_IMAGE_BANNER ? null : 'HOME_IMAGE_BANNER'), '1o offModuleOfRoute() la sigue según el valor de la constante');
        $copy = "{$directory}/GenericContentController.php";
        $written = file_put_contents($copy, str_replace('HOME_IMAGE_BANNER ?', 'OTRA_CONSTANTE ?', (string) file_get_contents($genericContent)));
        $check($written !== false && !$registeredBehind($copy, 'HOME_IMAGE_BANNER', $homeImage), '1p canario: con otra constante en la ternaria, la comprobación cae');
        echoTerminal(' ');

        //──── 1quater. Los cuatro módulos que el clon puede apagar (H404), por tokens ──────────
        echoTerminal('[1quater] Directorio, Geo, Notificaciones y MCP: su ENABLE lee su constante del clon y lo que pintan fuera, guardado');
        $tokensOf = static fn (string $relative): array => array_values(array_filter(
            \PhpToken::tokenize((string) file_get_contents(basepath($relative))),
            static fn (\PhpToken $t): bool => !$t->is([\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT])
        ));
        $cloneDefines = [];
        $section = 'framework';
        $raw = array_values(array_filter(
            \PhpToken::tokenize((string) file_get_contents(basepath('app/config/constants.php'))),
            static fn (\PhpToken $t): bool => !$t->is(\T_WHITESPACE) && !($t->is(\T_COMMENT) && !str_starts_with($t->text, '//── Del ')) && !$t->is(\T_DOC_COMMENT)
        ));
        foreach ($raw as $i => $token) {
            if ($token->is(\T_COMMENT)) {
                $section = str_contains($token->text, 'Del clon') ? 'clon' : 'framework';
            }
            if ($token->is(\T_STRING) && $token->text === 'define' && ($raw[$i + 1]->text ?? '') === '(' && ($raw[$i + 2] ?? null)?->is(\T_CONSTANT_ENCAPSED_STRING) && ($raw[$i + 3]->text ?? '') === ',') {
                $cloneDefines[trim($raw[$i + 2]->text, '\'"')] = $section === 'clon' ? strtolower((string) ($raw[$i + 4]->text ?? '')) : 'framework';
            }
        }
        foreach ([
            'app/classes/Directory/DirectoryRoutes.php' => 'DIRECTORY_MODULE',
            'app/classes/PiecesPHP/Geo/GeoRoutes.php' => 'GEO_MODULE',
            'app/classes/PiecesPHP/Notifications/NotificationsRoutes.php' => 'NOTIFICATIONS_MODULE',
            'app/classes/PiecesPHP/Mcp/McpRoutes.php' => 'MCP_MODULE',
        ] as $relative => $constant) {
            $tokens = $tokensOf($relative);
            $value = null;
            foreach ($tokens as $i => $token) {
                if ($token->is(\T_CONST) && ($tokens[$i + 1]->text ?? '') === 'ENABLE' && ($tokens[$i + 2]->text ?? '') === '=') {
                    $value = ($tokens[$i + 4]->text ?? '') === ';' ? $tokens[$i + 3]->text : 'una expresión';
                    break;
                }
            }
            $check($value === $constant, "1q1 " . basename($relative) . " · ENABLE = {$constant}", 'ENABLE = ' . ($value ?? 'no está'));
            //En un clon, un constants.php anterior a la 10.54.0 no la trae y vale (la red la da por true); el del framework, sí.
            $allowed = \Terminal\Distribution::isDistribution() ? ['true', 'false', 'sin definir'] : ['true', 'false'];
            $check(in_array($cloneDefines[$constant] ?? 'sin definir', $allowed, true) && defined($constant) && is_bool(constant($constant)), "1q2 {$constant}: en constants.php, true o false; si no está, la red la deja definida", (string) ($cloneDefines[$constant] ?? 'sin definir'));
        }
        //Lo que pinta un módulo fuera de su carpeta va dentro de un `if (…) :` con su guarda; el canario exige que haya algo que guardar.
        $guarded = static function (array $tokens, callable $isTarget, string $needle): array {
            $open = [];
            $targets = 0;
            $loose = 0;
            foreach ($tokens as $i => $token) {
                if ($token->is(\T_IF) && ($tokens[$i + 1]->text ?? '') === '(') {
                    $depth = 0;
                    $condition = '';
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        $depth += $tokens[$j]->text === '(' ? 1 : ($tokens[$j]->text === ')' ? -1 : 0);
                        $condition .= $tokens[$j]->text;
                        if ($depth === 0) {
                            break;
                        }
                    }
                    if (($tokens[$j + 1]->text ?? '') === ':') {
                        $open[] = $condition;
                    }
                }
                if ($token->is(\T_ENDIF)) {
                    array_pop($open);
                }
                if ($isTarget($tokens, $i)) {
                    $targets++;
                    //Sin el espacio de nombres; y ni negada ni como cola de otro nombre (OrganizationProfileController).
                    $loose += array_filter($open, static fn (string $c): bool => preg_match('/(?<![\\w!])' . preg_quote($needle, '/') . '/', (string) preg_replace('/\\\\?(?:\\w+\\\\)+(?=\\w+::)/', '', $c)) === 1) === [] ? 1 : 0;
                }
            }
            return [$targets, $loose];
        };
        $staticCall = static fn (string $class, string $method): callable => static fn (array $t, int $i): bool => ($t[$i]->text === $class || str_ends_with($t[$i]->text, '\\' . $class)) && ($t[$i + 1] ?? null)?->is(\T_DOUBLE_COLON) && ($t[$i + 2]->text ?? '') === $method;
        foreach ([
            ['app/classes/Organizations/Views/my-organization-profile/my-organization-profile.php', $staticCall('ProfileController', 'routeName'), "ProfileController::allowedRoute('profile'", 1],
            ['app/classes/Organizations/Views/profile-organization/profile.php', $staticCall('ProfileController', 'routeName'), "ProfileController::allowedRoute('profile'", 2],
            ['app/view/panel/layout/topbar-bar.php', static fn (array $t, int $i): bool => $t[$i]->is(\T_INLINE_HTML) && str_contains($t[$i]->text, 'notifications-toggle'), 'NotificationsRoutes::ENABLE', 1],
            ['app/view/panel/layout/topbar.php', $staticCall('NotificationsController', 'listMarkup'), 'NotificationsRoutes::ENABLE', 1],
        ] as [$relative, $isTarget, $needle, $expected]) {
            [$targets, $loose] = $guarded($tokensOf($relative), $isTarget, $needle);
            $check($targets === $expected && $loose === 0, '1q3 ' . basename($relative) . " · {$targets} sitio(s), todos dentro de «{$needle}»", "esperados {$expected}, sin guarda {$loose}");
        }
        //En topbar-data.php, las dos llamadas son ternarias: la guarda va delante en la misma sentencia.
        $statements = explode(';', implode('', array_map(static fn (\PhpToken $t): string => $t->text, $tokensOf('app/view/panel/layout/topbar-data.php'))));
        foreach (['Notifications::unread(' => 'NotificationsRoutes::ENABLE?', 'Assistant::panelWindow(' => 'McpRoutes::ENABLE?'] as $callText => $guard) {
            $with = array_values(array_filter($statements, static fn (string $s): bool => str_contains($s, $callText)));
            $check(count($with) === 1 && str_contains($with[0], $guard) && strpos($with[0], $guard) < strpos($with[0], $callText), "1q4 topbar-data.php · {$callText}) solo tras «{$guard}»", $with[0] ?? 'no está');
        }
        $notify = implode('', array_map(static fn (\PhpToken $t): string => $t->is([\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT]) ? '' : $t->text, $tokensOf('app/classes/PiecesPHP/Notifications/Notifications.php')));
        $start = strpos($notify, 'functionnotify(');
        $head = $start === false ? '' : (string) strstr(substr($notify, $start), 'try{', true);
        $check(str_contains($head, 'if(!NotificationsRoutes::ENABLE||self::$offForTesting){return0;}'), '1q5 Notifications::notify() sale con 0 antes de escribir si NOTIFICATIONS_MODULE está apagado', $head === '' ? 'no está' : $head);
        //Una capacidad servida por una ruta de un módulo apagado no aplica; sin el módulo apagado, la misma falta falla.
        if (!defined('ZZ_VI_CLONE_OFF_MODULE')) {
            define('ZZ_VI_CLONE_OFF_MODULE', false);
        }
        $capability = ['zz-apagada' => ['routes' => ['zz-vi-clone-ruta-apagada'], 'announced' => []]];
        $offMap = ['zz-vi-clone-ruta-apagada' => 'ZZ_VI_CLONE_OFF_MODULE'];
        $offFailures = $call('validateAnnouncedCapabilities', [$capability, [], basepath('..'), [], '', $offMap]);
        $check($offFailures === [] && $call('announcedRoutesOff', [$capability, [], $offMap]) === ['ZZ_VI_CLONE_OFF_MODULE' => 1], '1q9 capacidades: la ruta de un módulo apagado no aplica y se cuenta', implode(' | ', $offFailures));
        $onFailures = $call('validateAnnouncedCapabilities', [$capability, [], basepath('..'), [], '', []]);
        $check(count($onFailures) === 1 && str_contains($onFailures[0], 'zz-vi-clone-ruta-apagada') && $call('announcedRoutesOff', [$capability, [], []]) === [], '1q10 CANARIO: sin el módulo apagado, la misma ruta falta y falla');
        //La red de un constants.php viejo: se carga justo después de él, y en un PHP aparte sin nada definido da las cuatro en true.
        $boot = implode('', array_map(static fn (\PhpToken $t): string => $t->is([\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT]) ? '' : $t->text, $tokensOf('app/core/bootstrap.php')));
        $afterConstants = (string) strstr((string) strstr($boot, "require\$directories['custom_constants'];"), "require\$directories['custom_autoloads_config'];", true);
        $check(str_contains($afterConstants, "}require\$directories['module_constants_defaults'];"), '1q6 bootstrap.php carga module-constants-defaults.php justo tras constants.php, fuera de su if', $afterConstants === '' ? 'no está' : $afterConstants);
        $defaults = escapeshellarg(basepath('app/core/module-constants-defaults.php'));
        $probe = static fn (string $before): string => trim((string) shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($before . 'require ' . $defaults . '; echo json_encode([DIRECTORY_MODULE, GEO_MODULE, NOTIFICATIONS_MODULE, MCP_MODULE]);') . ' 2>&1'));
        $check($probe('') === '[true,true,true,true]', '1q7 sin ninguna definida, las cuatro valen true', $probe(''));
        $check($probe("define('GEO_MODULE', false);") === '[true,false,true,true]', '1q8 lo que el proyecto apagó sigue apagado', $probe("define('GEO_MODULE', false);"));
        echoTerminal(' ');

        //──── 2. Sin archivo del clon, todo sigue igual ───────────────────────────────────────
        echoTerminal('[2] Sin línea base del clon, los fallos de estilo caen como siempre');
        //RETORNO-IGNORADO: si putenv() fallara, 2a y 3a lo dirían: la tarea leería el archivo del clon de verdad, que no existe.
        putenv("PCSPHP_CLONE_BASELINE={$baselineFile}");
        $style = [
            'COMENTARIO' => ['src/app/zz/A.php — 2 bloque(s) narrativo(s), 5 líneas de prosa, y el archivo NO está en el registro. La guarda cabe en una línea (LEY 7); el relato va al CHANGELOG.'],
            'COLOR SUELTO' => ['src/app/zz/panel.scss:4 — #123456: color suelto; ponlo en una variable de SCSS con nombre de su papel'],
            'STYLE EN VISTA' => ['TRINQUETE ROTO: 1 vista(s) con más style=" que en la línea base:', 'src/app/zz/Views/a.php: 0 -> 3', 'El diseño va en las clases de Fomantic, no en la vista (fase (1), D16).'],
            'ORDEN' => ['piecesphp/src/app/zz/B.php:20 — protected $x se declara después del primer método. Las propiedades van arriba.'],
        ];
        [$kept, $errors] = $call('applyCloneBaseline', [$style]);
        $check($kept === $style && $errors === [], '2a sin el archivo, applyCloneBaseline() no toca nada');
        echoTerminal(' ');

        //──── 3. Con la línea base del clon ───────────────────────────────────────────────────
        echoTerminal('[3] Con la línea base: lo congelado no cae, lo nuevo sí');
        $written = file_put_contents($baselineFile, json_encode([
            'style' => [
                'COMENTARIO' => ['src/app/zz/A.php' => 5],
                'COLOR SUELTO' => ['src/app/zz/panel.scss' => 1],
                'STYLE EN VISTA' => ['src/app/zz/Views/a.php' => 3],
                'ORDEN' => ['piecesphp/src/app/zz/B.php' => 1],
            ],
            'route_overrides' => [
                "ZzCloneTest{$suffix}\\ZzDecides::_allowedRoute" => 'zz: solo el creador',
                "ZzCloneTest{$suffix}\\ZzInert::_allowedRoute" => 'zz: no decide nada',
            ],
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));
        if ($written === false) {
            throw new \RuntimeException("no se pudo escribir {$baselineFile}");
        }
        [$kept] = $call('applyCloneBaseline', [$style]);
        $check(array_sum(array_map('count', $kept)) === 0, '3a los cuatro fallos de estilo en la línea base no caen (ni el resumen del censo)', (string) json_encode($kept, \JSON_UNESCAPED_UNICODE));

        $grown = $style;
        $grown['ORDEN'][] = 'piecesphp/src/app/zz/B.php:30 — protected $y se declara después del primer método. Las propiedades van arriba.';
        $grown['COLOR SUELTO'][] = 'src/app/zz/otro.scss:2 — #abcdef: color suelto; ponlo en una variable de SCSS con nombre de su papel';
        $grown['STYLE EN VISTA'][1] = 'src/app/zz/Views/a.php: 0 -> 4';
        [$kept] = $call('applyCloneBaseline', [$grown]);
        $check(count($kept['ORDEN']) === 2, '3b un archivo con más fallos que en la línea base cae entero', (string) json_encode($kept['ORDEN'], \JSON_UNESCAPED_UNICODE));
        $check(count($kept['COLOR SUELTO']) === 1 && str_contains($kept['COLOR SUELTO'][0], 'otro.scss'), '3c un archivo que no está en la línea base cae');
        $check(count($kept['STYLE EN VISTA']) === 3, '3d una vista que sube de 3 a 4 style= cae, con el resumen del censo');
        $check(count($kept['COMENTARIO']) === 0, '3e el que no cambió sigue congelado');
        echoTerminal(' ');

        //──── 4. Las de funcionamiento no se congelan ─────────────────────────────────────────
        echoTerminal('[4] Funcionamiento y seguridad no entran en la línea base');
        $written = file_put_contents($baselineFile, json_encode(['style' => ['INSTANTÁNEA' => ['src/app/zz/A.php' => 1], 'RUTA' => ['src/app/zz/A.php' => 1]]], \JSON_UNESCAPED_UNICODE));
        if ($written === false) {
            throw new \RuntimeException("no se pudo escribir {$baselineFile}");
        }
        $baseline = $call('cloneBaseline');
        $check($baseline['style'] === [] && count($baseline['errors']) === 2, '4a INSTANTÁNEA y RUTA en la línea base del clon son un error del archivo, no se congelan', implode(' | ', $baseline['errors']));
        $check(VerifyIntegrityTask::CLONE_STYLE_RULES === ['COMENTARIO', 'COLOR SUELTO', 'STYLE EN VISTA', 'ORDEN', 'DOCBLOCK HUÉRFANO'], '4b solo las cinco reglas de estilo (NÚMERO DE REGISTRO es de funcionamiento)');
        $source = (string) file_get_contents(basepath('app/classes/Terminal/Tasks/VerifyIntegrityTask.php'));
        $check(str_contains($source, "\$styleFailures = ['COMENTARIO' => \$narrativeFailures, 'COLOR SUELTO' => \$looseColorFailures, 'STYLE EN VISTA' => \$inlineStyleFailures, 'ORDEN' => \$orderFailures, 'DOCBLOCK HUÉRFANO' => \$orphanFailures];"), '4c la tarea solo pasa a la línea base del clon los fallos de esas cinco');
        echoTerminal(' ');

        //──── 5. Sobreescrituras de ruta del clon ─────────────────────────────────────────────
        echoTerminal('[5] Sobreescrituras de ruta del clon, con el mismo clasificador');
        $written = file_put_contents($baselineFile, json_encode([
            'style' => [],
            'route_overrides' => [
                "ZzCloneTest{$suffix}\\ZzDecides::_allowedRoute" => 'zz: solo el creador',
                "ZzCloneTest{$suffix}\\ZzInert::_allowedRoute" => 'zz: no decide nada',
                'Zz\\SinMotivo::_allowedRoute' => '',
            ],
        ], \JSON_UNESCAPED_UNICODE));
        if ($written === false) {
            throw new \RuntimeException("no se pudo escribir {$baselineFile}");
        }
        $controller = function (string $class, string $body) use ($directory, $suffix): string {
            $code = "<?php\n\nnamespace ZzCloneTest{$suffix};\n\nclass {$class}\n{\n    public static function _allowedRoute(string \$name, string \$route, array \$params = []): bool\n    {\n{$body}    }\n}\n";
            if (file_put_contents("{$directory}/{$class}.php", $code) === false) {
                throw new \RuntimeException("no se pudo escribir {$class}.php");
            }
            return "app/cache/zz-vi-clone-{$suffix}/{$class}.php";
        };
        $files = [
            $controller('ZzDecides', "        \$allow = \$route !== '';\n        if (\$name === 'zz-propio-actions-delete') {\n            \$allow = false;\n        }\n        return \$allow;\n"),
            $controller('ZzInert', "        \$allow = strlen(\$route) > 0;\n        return \$allow;\n"),
            $controller('ZzUndeclared', "        \$allow = \$route !== '';\n        if (\$name === 'zz') {\n            \$allow = false;\n        }\n        return \$allow;\n"),
        ];
        $baseline = $call('cloneBaseline');
        $check(count($baseline['errors']) === 1 && str_contains($baseline['errors'][0], 'Zz\\SinMotivo'), '5a una sobreescritura sin motivo es un error del archivo', implode(' | ', $baseline['errors']));
        $mine = array_values(array_filter($call('checkRouteOverrides', [$files]), fn (string $line): bool => str_starts_with($line, "ZzCloneTest{$suffix}\\")));
        $about = fn (string $class): array => array_values(array_filter($mine, fn (string $line): bool => str_starts_with($line, "ZzCloneTest{$suffix}\\{$class}::")));
        $check($about('ZzDecides') === [], '5b una sobreescritura declarada que decide pasa', implode(' | ', $about('ZzDecides')));
        $check(count($about('ZzInert')) === 1 && str_contains($about('ZzInert')[0], 'SIGUE DECIDIENDO'), '5c una declarada que no decide cae', implode(' | ', $about('ZzInert')));
        $check(count($about('ZzUndeclared')) === 1 && str_contains($about('ZzUndeclared')[0], 'sin estar registrado'), '5d una que decide pero no está declarada cae', implode(' | ', $about('ZzUndeclared')));
        echoTerminal(' ');

        //──── 6. update-clone-baseline escribe y conserva las sobreescrituras ─────────────────
        echoTerminal('[6] Escribir la línea base del clon');
        $check($call('writeCloneBaseline', [$style]) === false, '6a con el archivo mal (la sobreescritura sin motivo) no se escribe');
        $written = file_put_contents($baselineFile, json_encode(['route_overrides' => ["ZzCloneTest{$suffix}\\ZzDecides::_allowedRoute" => 'zz: solo el creador']], \JSON_UNESCAPED_UNICODE));
        if ($written === false) {
            throw new \RuntimeException("no se pudo escribir {$baselineFile}");
        }
        $check($call('writeCloneBaseline', [$style]) === true, '6b se escribe');
        $document = json_decode((string) file_get_contents($baselineFile), true);
        $check(($document['style']['ORDEN']['piecesphp/src/app/zz/B.php'] ?? null) === 1 && ($document['style']['COMENTARIO']['src/app/zz/A.php'] ?? null) === 5 && ($document['style']['STYLE EN VISTA']['src/app/zz/Views/a.php'] ?? null) === 3, '6c con la cifra de cada archivo por regla', (string) json_encode($document['style'] ?? null, \JSON_UNESCAPED_UNICODE));
        $check(isset($document['route_overrides']["ZzCloneTest{$suffix}\\ZzDecides::_allowedRoute"]), '6d y conserva las sobreescrituras de ruta');
        echoTerminal(' ');

        //──── 8. DOCBLOCK HUÉRFANO, de estilo (H369) ───────────────────────────────────────────
        echoTerminal('[8] DOCBLOCK HUÉRFANO se congela como las demás de estilo');
        $orphan = 'src/app/zz/C.php:12 — un docblock con @param/@return va seguido de otro docblock: quedó separado de lo que documenta.';
        if (file_put_contents($baselineFile, json_encode(['style' => ['DOCBLOCK HUÉRFANO' => ['src/app/zz/C.php' => 1]]], \JSON_UNESCAPED_UNICODE)) === false) {
            throw new \RuntimeException("no se pudo escribir {$baselineFile}");
        }
        [$kept] = $call('applyCloneBaseline', [['DOCBLOCK HUÉRFANO' => [$orphan]]]);
        $check($kept['DOCBLOCK HUÉRFANO'] === [], '8a uno en la línea base no cae');
        [$kept] = $call('applyCloneBaseline', [['DOCBLOCK HUÉRFANO' => [$orphan, str_replace(':12', ':40', $orphan)]]]);
        $check(count($kept['DOCBLOCK HUÉRFANO']) === 2, '8b uno más en el mismo archivo cae');
        echoTerminal(' ');

        //──── 9. Los usos de ofuscación del proyecto (H369) ────────────────────────────────────
        echoTerminal('[9] La ofuscación del proyecto se declara como Clase::método con su motivo');
        $namespace = "ZzCloneObf{$suffix}";
        $obfuscatorCode = "<?php\n\nnamespace {$namespace};\n\nuse PiecesPHP\\Core\\BaseHashEncryption;\n\nclass ZzSlug\n{\n    public static function slug(int \$id): string\n    {\n        return BaseHashEncryption::obfuscate((string) \$id, 'zz');\n    }\n}\n";
        if (file_put_contents("{$directory}/ZzSlug.php", $obfuscatorCode) === false) {
            throw new \RuntimeException('no se pudo escribir ZzSlug.php');
        }
        $check($call('methodAtLine', [$obfuscatorCode, 11]) === "{$namespace}\\ZzSlug::slug", '9a methodAtLine() da la clase y el método de la llamada', (string) $call('methodAtLine', [$obfuscatorCode, 11]));
        $mineObf = fn (): array => array_values(array_filter($call('checkSealedPurposes'), fn (string $line): bool => str_contains($line, $namespace) || str_contains($line, "zz-vi-clone-{$suffix}")));
        if (file_put_contents($baselineFile, '{}') === false) {
            throw new \RuntimeException("no se pudo escribir {$baselineFile}");
        }
        $undeclared = $mineObf();
        $check(count($undeclared) === 1 && str_contains($undeclared[0], 'ofusca fuera de la lista'), '9b sin declararlo, el uso cae', implode(' | ', $undeclared));
        if (file_put_contents($baselineFile, json_encode(['obfuscation' => ["{$namespace}\\ZzSlug::slug" => 'zz: el id corto de la dirección', "{$namespace}\\ZzSlug::otro" => 'zz: ya no existe']], \JSON_UNESCAPED_UNICODE)) === false) {
            throw new \RuntimeException("no se pudo escribir {$baselineFile}");
        }
        $declared = $mineObf();
        $check(count($declared) === 1 && str_contains($declared[0], 'ZzSlug::otro') && str_contains($declared[0], 'ya no ofusca'), '9c declarado, el uso pasa; y uno declarado que ya no existe cae', implode(' | ', $declared));
        if (file_put_contents($baselineFile, json_encode(['obfuscation' => ["{$namespace}\\ZzSlug::slug" => '']], \JSON_UNESCAPED_UNICODE)) === false) {
            throw new \RuntimeException("no se pudo escribir {$baselineFile}");
        }
        $baseline = $call('cloneBaseline');
        $check($baseline['obfuscation'] === [] && count($baseline['errors']) === 1, '9d un uso sin motivo es un error del archivo y no se acepta', implode(' | ', $baseline['errors']));
        if (file_put_contents($baselineFile, json_encode(['obfuscation' => ["{$namespace}\\ZzSlug::slug" => 'zz: el id corto']], \JSON_UNESCAPED_UNICODE)) === false) {
            throw new \RuntimeException("no se pudo escribir {$baselineFile}");
        }
        $call('writeCloneBaseline', [[]]);
        $document = json_decode((string) file_get_contents($baselineFile), true);
        $check(isset($document['obfuscation']["{$namespace}\\ZzSlug::slug"]), '9e update-clone-baseline conserva «obfuscation»');
        echoTerminal(' ');

        //──── 7. En el repositorio del framework, el archivo del clon es un fallo ─────────────
        echoTerminal('[7] El framework no puede tener la línea base de un proyecto');
        $fakeRoot = "{$directory}/raiz";
        foreach (["{$fakeRoot}/bin", "{$fakeRoot}/files/clone"] as $folder) {
            if (!is_dir($folder) && !mkdir($folder, 0775, true)) {
                throw new \RuntimeException("no se pudo crear {$folder}");
            }
        }
        if (file_put_contents("{$fakeRoot}/files/clone/verify-integrity.json", '{}') === false) {
            throw new \RuntimeException('no se pudo escribir la línea base de la raíz de prueba');
        }
        $check($call('frameworkHasCloneBaseline', [$fakeRoot]) === null, '7a en un clon (sin bin/distribution-exclude.txt) el archivo es legítimo');
        if (file_put_contents("{$fakeRoot}/bin/distribution-exclude.txt", "bin/distribution-exclude.txt\n") === false) {
            throw new \RuntimeException('no se pudo escribir la marca de la raíz de prueba');
        }
        $message = $call('frameworkHasCloneBaseline', [$fakeRoot]);
        $check(is_string($message) && str_contains($message, 'es de un proyecto, no del framework'), '7b con bin/distribution-exclude.txt (el framework), el archivo del clon es un fallo', (string) $message);
        $check($call('frameworkHasCloneBaseline', [dirname(rtrim(str_replace('\\', '/', basepath('')), '/'))]) === null, '7c este repositorio no tiene el archivo del clon');
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        //RETORNO-IGNORADO: devolver el entorno como estaba; el proceso termina con la suite.
        putenv($previousEnv === false ? 'PCSPHP_CLONE_BASELINE' : "PCSPHP_CLONE_BASELINE={$previousEnv}");
        foreach (["{$directory}/raiz/files/clone/verify-integrity.json", "{$directory}/raiz/bin/distribution-exclude.txt"] as $path) {
            //RETORNO-IGNORADO: limpieza de lo propio en app/cache; lo que quede no afecta al resultado.
            @unlink($path);
        }
        foreach (["{$directory}/raiz/files/clone", "{$directory}/raiz/files", "{$directory}/raiz/bin", "{$directory}/raiz"] as $path) {
            //RETORNO-IGNORADO: carpetas propias vacías en app/cache.
            @rmdir($path);
        }
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $folder) {
            foreach ((array) glob("{$directory}/clases/{$folder}/*.php") as $path) {
                //RETORNO-IGNORADO: limpieza de lo propio en app/cache; lo que quede no afecta al resultado.
                @unlink((string) $path);
            }
            //RETORNO-IGNORADO: carpetas propias vacías en app/cache.
            @rmdir("{$directory}/clases/{$folder}");
        }
        //RETORNO-IGNORADO: carpeta propia vacía en app/cache.
        @rmdir("{$directory}/clases");
        foreach ((array) glob("{$directory}/*") as $path) {
            //RETORNO-IGNORADO: limpieza de lo propio en app/cache; lo que quede no afecta al resultado.
            @unlink((string) $path);
        }
        //RETORNO-IGNORADO: la carpeta propia, vacía; si queda, está en app/cache.
        @rmdir($directory);
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('H364 y H366: verify-integrity con módulos apagados y con la línea base del código propio de un clon.')->setEffects([CliActions::EFFECT_FILES])->register();
