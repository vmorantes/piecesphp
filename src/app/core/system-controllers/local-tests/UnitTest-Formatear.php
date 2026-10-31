<?php

//bin/formatear (P233, P235, P236): --check cae con un archivo mal formateado de cada lenguaje y pasa formateado; no toca
//nada fuera del universo; TypeScript es el fijado; y el guion y .vscode/settings.json dicen lo mismo. Archivos zz-, retirados.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/formatear', function ($args) {

    echoTerminal("\e[33m[TEST:Formatear] bin/formatear formatea el universo, y solo el universo, como el editor\e[39m");
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

    $raiz = realpath(basepath('..'));
    if ($raiz === false) {
        $check(false, '00 la raíz del repositorio existe');
        return $balance();
    }
    $correr = function (string $argumentos) use ($raiz): array {
        $salida = [];
        $codigo = 0;
        //RETORNO-IGNORADO: la salida entera llega por $salida; la última línea que devuelve exec() no aporta.
        exec('cd ' . escapeshellarg($raiz) . " && bin/formatear {$argumentos} 2>&1", $salida, $codigo);
        return [$codigo, implode("\n", $salida)];
    };
    $prefijo = 'zz-prueba-formatear-' . bin2hex(random_bytes(3));
    //Dentro del universo: nuevo, sin versionar y no ignorado. Fuera: bin/tools/ (de terceros) y un .min.js.
    $carpeta = "{$raiz}/src/app/{$prefijo}";
    $fuera = "{$raiz}/bin/tools/{$prefijo}.php";
    $mal = [
        'php' => "<?php\nfunction zz(  \$a ){\nreturn array( \$a );}\n",
        'js' => "function zz( a ){\n  return a;\n}\n",
        'scss' => ".zz{color:red;.b{margin:0}}\n",
        'css' => ".zz{color:red}\n",
    ];
    $rutas = [];
    foreach (array_keys($mal) as $extension) {
        $rutas[$extension] = "{$carpeta}/zz.{$extension}";
    }
    $minificado = "{$carpeta}/zz.min.js";
    //Los siete defectos de phpfmt y js-beautify que corrige bin/formatear (JBP), cada uno en su archivo.
    $defectos = [
        'B1' => ['zz-b1.php', "<?php\nfunction zzB1(?\\DateTime \$a, ?string \$b = null): ?\\DateTime\n{\n    return \$a;\n}\n"],
        'B1b' => ['zz-b1b.php', "<?php\nfunction zzB1b(\n    ?int \$a = null,\n    ?\\DateTime \$b = null,\n    bool \$c = false\n): void\n{\n}\n"],
        'B2' => ['zz-b2.php', "<?php\nclass ZzB2\n{\n    public static function isSet(): bool\n    {\n        return true;\n    }\n}\n"],
        'B3' => ['zz-b3.php', "<?php\nzzB3(function () {\n    \$x = new class {\n        public function n(): string { return 'x'; }\n    };\n    return \$x;\n});\n"],
        'B3b' => ['zz-b3b.php', "<?php\nclass ZzB3b\n{\n    public function __construct()\n    {}\n}\n"],
        'B4' => ['zz-b4.php', "<?php\ndefined('BASEPATH') or die('x');\nforeach ([1] as \$a) {\n    \$b = \$a;\n}\n?>\n<div>\n    <?= \$b; ?>\n</div>\n"],
        'E1' => ['zz-e1.php', "<?php\n\$a = json_encode([], \\JSON_THROW_ON_ERROR | \\JSON_UNESCAPED_UNICODE);\n\$b = 1; // comentario\n"],
        //El de approval-publications.php:65: phpfmt añadía un espacio tras «!=» en cada pasada.
        'E2' => ['zz-e2.php', "<?php\n\$m = null;\n?>\n<div>\n    <div class=\"base-text\"><?= \$m != null ? strReplaceTemplate(localeDateFormat('%e', \$m), ['%1' => 'de']) : 'N/A'; ?></div>\n</div>\n"],
        //D-LÍNEAS (JBR): ni se juntan ni se parten líneas fuera de las excepciones decididas.
        'F1' => ['zz-f1.php', "<?php\n\$x = new class () extends \\stdClass {\n    public int \$a = 1;\n};\n"],
        'F2' => ['zz-f2.php', "<?php\nenum ZzF2: string\n{\n    case A = 'a';\n}\n"],
        'F3' => ['zz-f3.php', "<?php\nclass ZzF3\n{\n    public function __construct(private array \$d) {}\n}\n"],
        'F4' => ['zz-f4.php', "<?php\nfunction zzF4(?array \$a): array\n{\n    return \$a\n        ?? throw new \\RuntimeException('x');\n}\n"],
        'Fa' => ['zz-fa.php', "<div>\n    <?php foreach ([1] as \$x): ?>\n    <?php if (\$x) continue; ?>\n    <?php endforeach; ?>\n</div>\n"],
        'R12' => ['zz-r12.php', "<?php\nfunction zzR(int | string \$a) : string | int\n{\n    return \$a;\n}\n"],
        'R3' => ['zz-r3.php', "<?php\n\$a = true;\n\$b = \$a\n? 1\n: 2;\n"],
        'P' => ['zz-p.php', "<?php\nzzP( 1 , 2 );\n[\$c, , \$d] = [1, 2, 3];\n"],
        //7.1 (JBS): el espacio del original se queda (i), salvo las normalizaciones (ii); una alineación vertical, también (iii).
        'S1' => ['zz-s1.php', "<?php\n\$f = fn (\$x) => \$x;\n\$g = fn (int \$y): array => [...\$a, \$y];\n\$l = __LINE__ + 2;\nif (\$l) { \$f = 1; }\n"],
        'S2' => ['zz-s2.php', "<?php\nfunction zzS2( ? \\DateTime \$a , int | string \$b, ) : string | int\n{\n    return 1;\n}\n"],
        'S3' => ['zz-s3.php', "<?php\n\$x = [\n    'a'   => 1,\n    'bbb' => 2,\n];\n\$t = [\n    'Descripción' => 1,\n    'Imagen'      => 2,\n    'Tamaño'      => 3,\n];\n\$y = \$z instanceof \\stdClass  ? 1 : 2;\n"],
        'B5' => ['zz-b5.scss', "@use 'sass:math' as *;\n@use 'zz' with (\$a: 1);\n\n.zz {\n    color: red;\n}\n"],
        'B6' => ['zz-b6.scss', ".zz > .b + .c ~ .d {\n    color: red;\n}\n"],
    ];

    try {
        $creado = mkdir($carpeta, 0775, true);
        foreach ($mal as $extension => $contenido) {
            $creado = $creado && file_put_contents($rutas[$extension], $contenido) !== false;
        }
        $creado = $creado && file_put_contents($minificado, $mal['js']) !== false && file_put_contents($fuera, $mal['php']) !== false;
        foreach ($defectos as [$nombre, $contenido]) {
            $creado = $creado && file_put_contents("{$carpeta}/{$nombre}", $contenido) !== false;
        }
        $check($creado, 'f0 banco: un archivo mal formateado de cada lenguaje, un .min.js y uno en bin/tools/');

        echoTerminal('[a] --check cae con lo mal formateado y pasa con lo formateado');
        foreach ($rutas as $extension => $ruta) {
            [$codigo, $salida] = $correr('--check ' . escapeshellarg($ruta));
            $check($codigo === 1 && str_contains($salida, 'CAMBIARÍA') && (string) file_get_contents($ruta) === $mal[$extension], "a1 .{$extension}: --check sale con 1, lo nombra y no lo toca", "rc {$codigo} · " . mb_substr($salida, -200));
        }
        [$codigo, $salida] = $correr(implode(' ', array_map('escapeshellarg', $rutas)));
        $check($codigo === 0 && substr_count($salida, 'FORMATEADO') === 4, 'a2 sin --check los formatea, los cuatro', "rc {$codigo} · " . mb_substr($salida, -200));
        $check(str_contains((string) file_get_contents($rutas['js']), "function zz(a) {\n\treturn a\n}"), 'a3 el JS queda con tabulador y sin punto y coma (las del proyecto)', json_encode((string) file_get_contents($rutas['js']), JSON_THROW_ON_ERROR));
        [$codigo, $salida] = $correr('--check ' . implode(' ', array_map('escapeshellarg', $rutas)));
        $check($codigo === 0 && str_contains($salida, '0 cambiaría(n)'), 'a4 y --check, formateados, sale con 0', "rc {$codigo} · " . mb_substr($salida, -200));

        echoTerminal('');
        echoTerminal('[d] Los siete defectos de JBP no vuelven');
        [$codigo, $salida] = $correr(implode(' ', array_map(fn (array $d): string => escapeshellarg("{$carpeta}/{$d[0]}"), $defectos)));
        $tras = array_map(fn (array $d): string => (string) file_get_contents("{$carpeta}/{$d[0]}"), $defectos);
        $check($codigo === 0 && !str_contains($salida, 'ERROR'), 'd0 los veintiuno se formatean sin error de las puertas', "rc {$codigo} · " . mb_substr($salida, -200));
        $check(str_contains($tras['B1'], 'zzB1(?\\DateTime $a, ?string $b = null): ?\\DateTime'), 'd1 B1: el ? de un tipo anulable sigue pegado a su tipo', $tras['B1']);
        $check(str_contains($tras['B1b'], "zzB1b(\n    ?int \$a = null,\n    ?\\DateTime \$b = null,\n    bool \$c = false\n)"), 'd1b B1: una firma con un parámetro por línea conserva sus saltos, anulables y no anulables (#2088)', $tras['B1b']);
        $check(str_contains($tras['B2'], 'function isSet()'), 'd2 B2: el método isSet() no pasa a isset()', $tras['B2']);
        $check(!str_contains($tras['B3'], '{return') && !str_contains($tras['B3'], '};;') && preg_match("/\\n\\s+return 'x';\\n/", $tras['B3']) === 1, 'd3 B3: el cuerpo de una línea vuelve a una sentencia por línea, y sin «};;»', $tras['B3']);
        $check(str_contains($tras['B3b'], "__construct()\n    {}\n"), 'd3b B3: un cuerpo vacío, «{}», se queda como venía', $tras['B3b']);
        $check(str_starts_with($tras['B4'], "<?php\ndefined('BASEPATH')") && str_contains($tras['B4'], "\n    \$b = \$a;\n"), 'd4 B4: la cabecera de la vista sin sangrar, y lo anidado con su sangría', $tras['B4']);
        $check(str_contains($tras['E1'], '\\JSON_THROW_ON_ERROR | \\JSON_UNESCAPED_UNICODE') && str_contains($tras['E1'], '$b = 1; // comentario'), 'd9 E1: sin el doble espacio que ResizeSpaces deja tras un nombre con \\', $tras['E1']);
        $check(str_contains($tras['E2'], '$m != null ?'), 'd10 E2: «!= null» con un espacio', $tras['E2']);
        [$codigo, $salida] = $correr('--check ' . escapeshellarg("{$carpeta}/zz-e2.php"));
        $check($codigo === 0 && str_contains($salida, '0 cambiaría(n)'), 'd11 E2: y formatearlo otra vez no lo cambia (idempotente)', "rc {$codigo} · " . mb_substr($salida, -200));
        $check(str_contains($tras['F1'], 'extends \\stdClass {'), 'd12 F1: la llave de una clase anónima se queda en su línea', $tras['F1']);
        $check(str_contains($tras['F2'], "enum ZzF2: string\n{"), 'd13 F2: la de un enum, también', $tras['F2']);
        $check(str_contains($tras['F3'], '__construct(private array $d) {}'), 'd14 F3: el {} de un constructor con propiedades promovidas, en su línea', $tras['F3']);
        $check(str_contains($tras['F4'], "return \$a\n        ?? throw"), 'd15 F4: una continuación con ?? no se junta', $tras['F4']);
        $check(str_contains($tras['Fa'], '<?php if ($x) continue; ?>'), 'd16 a): un if dentro de un <?php … ?> de una línea, sin llaves ni saltos', $tras['Fa']);
        $check(str_contains($tras['R12'], 'zzR(int|string $a): string|int'), 'd17 R1 y R2: «): tipo» y «int|string», sin espacios', $tras['R12']);
        $check(str_contains($tras['R3'], "\$b = \$a\n    ? 1\n    : 2;"), 'd18 R3: la continuación de una ternaria, un nivel por dentro', $tras['R3']);
        $check(str_contains($tras['P'], 'zzP(1, 2);') && str_contains($tras['P'], '[$c, , $d]'), 'd19 sin espacio tras «(» ni antes de «,» o «)»; el hueco de una lista se queda', $tras['P']);
        $check(str_contains($tras['S1'], '$f = fn ($x) => $x;') && str_contains($tras['S1'], '$g = fn (int $y): array => [...$a, $y];') && str_contains($tras['S1'], '__LINE__ + 2') && str_contains($tras['S1'], 'if ($l) { $f = 1; }'), 'd20 7.1 (i): «fn (», «array =>», «[...$a», «__LINE__ + 2» y «{ $f = 1; }» se quedan como en el original', $tras['S1']);
        $check(str_contains($tras['S2'], 'function zzS2(?\\DateTime $a, int|string $b,): string|int'), 'd21 7.1 (ii): «?Tipo», «int|string», «): tipo» y nada junto a los paréntesis ni antes de la coma', $tras['S2']);
        $check(str_contains($tras['S3'], "    'a'   => 1,\n    'bbb' => 2,") && str_contains($tras['S3'], "    'Imagen'      => 2,\n    'Tamaño'      => 3,") && str_contains($tras['S3'], 'instanceof \\stdClass ? 1 : 2;'), 'd22 7.1 (iii): la alineación de «=>» se queda, también con tildes (columnas en caracteres); el doble espacio tras \\stdClass, no', $tras['S3']);
        $check(str_contains($tras['B5'], "'sass:math' as *;") && str_contains($tras['B5'], "'zz' with ("), 'd5 B5: @use conserva el espacio antes de as y de with', $tras['B5']);
        $check(str_contains($tras['B6'], '.zz > .b + .c ~ .d'), 'd6 B6: los combinadores conservan sus espacios', $tras['B6']);
        //La puerta, directa: un «formateado» que cambia un identificador es ERROR; uno con lo que se queda a propósito, no. Un
        //cambio de caja no sirve aquí: el arreglo de B2 lo devuelve antes de comparar.
        $original = "{$carpeta}/zz-puerta.php";
        $cambiado = "{$carpeta}/zz-puerta-cambiado.php";
        $permitido = "{$carpeta}/zz-puerta-permitido.php";
        $bancoPuerta = file_put_contents($original, "<?php\nuse Zz\\Usada;\nuse Zz\\NoUsada;\nif (TRUE) zz(array(1, 2,), Usada::X);\nclass ZzP { public function isSet() {} }\n") !== false
            && file_put_contents($cambiado, "<?php\nuse Zz\\Usada;\nuse Zz\\NoUsada;\nif (TRUE) zz(array(1, 2,), Usada::Y);\nclass ZzP { public function isSet() {} }\n") !== false
            && file_put_contents($permitido, "<?php\nuse Zz\\Usada;\n\nif (true) {\n    zz([1, 2], Usada::X);\n}\nclass ZzP\n{\n    public function isSet()\n    {\n    }\n}\n") !== false;
        $puerta = function (string $formateado) use ($raiz, $original): array {
            $salida = [];
            //RETORNO-IGNORADO: la salida es una línea JSON por par; si php falla, queda vacía y el caso falla.
            exec('cd ' . escapeshellarg($raiz) . ' && ' . escapeshellarg(PHP_BINARY) . ' bin/tools/formato/php.php ' . escapeshellarg($original) . ' ' . escapeshellarg($formateado) . ' - 2>&1', $salida);
            $dato = json_decode(implode('', $salida), true);
            return is_array($dato) ? $dato : ['error' => 'sin respuesta: ' . implode(' ', $salida)];
        };
        $r = $puerta($cambiado);
        $check($bancoPuerta && is_string($r['error'] ?? null) && str_contains($r['error'], 'X') && str_contains($r['error'], 'Y'), 'd7 la puerta caza un identificador cambiado (Usada::X → Usada::Y)', json_encode($r, JSON_UNESCAPED_UNICODE) ?: '');
        $r = $puerta($permitido);
        $check(array_key_exists('error', $r) && $r['error'] === null, 'd8 y deja pasar lo que se queda: use ordenados y sin los no usados, comas finales, llaves del if, TRUE → true y array() → []', json_encode($r, JSON_UNESCAPED_UNICODE) ?: '');

        echoTerminal('');
        echoTerminal('[e] El editor da lo mismo que bin/formatear (P242): la orden exacta de la extensión, con su php_bin');
        $ajustesEditor = json_decode((string) file_get_contents("{$raiz}/.vscode/settings.json"), true);
        $phpBin = (string) ($ajustesEditor['phpfmt.php_bin'] ?? '');
        $argumentosEditor = '--psr2 --indent_with_space=4 --passes=' . implode(',', (array) ($ajustesEditor['phpfmt.passes'] ?? []));
        $muestrasEditor = [
            'vista' => 'src/app/classes/Publications/Views/publications/forms/edit.php',
            'anulables' => 'src/app/core/Utilities.php',
            'anonima' => 'src/app/core/system-controllers/local-tests/UnitTest-EscapeHtml.php',
            'firma' => 'src/app/classes/News/Controllers/NewsController.php',
            'approval' => 'src/app/classes/SystemApprovals/Views/forms/approval-publications.php',
        ];
        $temporalEditor = sys_get_temp_dir() . "/{$prefijo}-editor";
        $creadoEditor = is_dir($temporalEditor) || mkdir($temporalEditor, 0700, true);
        $distintos = [];
        foreach ($muestrasEditor as $clave => $real) {
            $contenido = (string) file_get_contents("{$raiz}/{$real}");
            $enUniverso = "{$carpeta}/zz-e5-{$clave}.php";
            $deEditor = "{$temporalEditor}/temp-zzprueba-" . basename($real);
            $creadoEditor = $creadoEditor && file_put_contents($enUniverso, $contenido) !== false && file_put_contents($deEditor, $contenido) !== false;
            $correr(escapeshellarg($enUniverso));
            $salidaEditor = [];
            $codigoEditor = 0;
            //RETORNO-IGNORADO: el resultado es el temporal que deja; el código, en $codigoEditor.
            exec('cd ' . escapeshellarg(dirname("{$raiz}/{$real}")) . ' && ' . $phpBin . ' ' . escapeshellarg("{$raiz}/bin/tools/phpfmt/fmt.stub.php") . " {$argumentosEditor} " . escapeshellarg($deEditor) . ' 2>&1', $salidaEditor, $codigoEditor);
            if ($codigoEditor !== 0 || (string) file_get_contents($enUniverso) !== (string) file_get_contents($deEditor)) {
                $distintos[] = "{$clave} (rc {$codigoEditor})";
            }
        }
        $check($creadoEditor && $phpBin !== '' && $distintos === [], 'e1 la orden del editor sobre una vista, anulables, una clase anónima, una firma multilínea y approval-publications da lo mismo, byte a byte, que bin/formatear', implode(', ', $distintos));
        foreach (glob("{$temporalEditor}/*") ?: [] as $archivo) {
            //RETORNO-IGNORADO: lo comprueba z1.
            unlink($archivo);
        }
        if (is_dir($temporalEditor)) {
            //RETORNO-IGNORADO: lo comprueba z1.
            rmdir($temporalEditor);
        }

        echoTerminal('');
        echoTerminal('[b] Nada fuera del universo');
        $antes = [sha1_file($fuera), sha1_file($minificado)];
        [$codigo, $salida] = $correr(escapeshellarg($fuera) . ' ' . escapeshellarg($minificado));
        $check($codigo === 1 && substr_count($salida, 'FUERA') === 2 && [sha1_file($fuera), sha1_file($minificado)] === $antes, 'b1 bin/tools/ y un .min.js se rechazan como FUERA y no se tocan', "rc {$codigo} · " . mb_substr($salida, -200));
        [$codigo, $salida] = $correr('--universo');
        $lista = explode("\n", $salida);
        //La foto es lo versionado: lo nuevo se formatea (a2), pero no entra en la lista hasta su commit.
        $check(in_array('src/index.php', $lista, true) && !in_array("src/app/{$prefijo}/zz.php", $lista, true), 'b2 CANARIO: --universo lista lo versionado (src/index.php), no lo nuevo sin versionar');
        $ajenos = array_filter($lista, fn (string $r): bool => preg_match('#^(src/vendor/|src/statics/plugins/|node_modules/|bin/tools/)|\.min\.(js|css)$#', $r) === 1);
        $check(count($lista) > 1000 && $ajenos === [] && !in_array('src/statics/core/js/helpers-lib/UtilPieces.js', $lista, true), 'b3 el universo no tiene terceros, minificados ni lo compilado (UtilPieces.js)', implode(', ', array_slice($ajenos, 0, 5)));

        echoTerminal('');
        echoTerminal('[c] Lo fijado: TypeScript, y el guion y .vscode/settings.json diciendo lo mismo');
        $paquete = json_decode((string) file_get_contents("{$raiz}/package.json"), true);
        $instalado = json_decode((string) @file_get_contents("{$raiz}/node_modules/typescript/package.json"), true);
        $fijada = (string) ($paquete['devDependencies']['typescript'] ?? '');
        $check(preg_match('/^\d+\.\d+\.\d+$/', $fijada) === 1 && ($instalado['version'] ?? null) === $fijada, "c1 TypeScript fijado a una versión exacta ({$fijada}) y es el instalado", 'instalado ' . var_export($instalado['version'] ?? null, true));
        $ajustes = json_decode((string) file_get_contents("{$raiz}/.vscode/settings.json"), true);
        $guion = (string) file_get_contents("{$raiz}/bin/formatear");
        $pases = preg_match("/^PHP_PASES = \\[(.*)\\]$/m", $guion, $coincidencia) === 1 ? array_map(fn ($p) => trim($p, " '"), explode(',', $coincidencia[1])) : [];
        $check($pases !== [] && $pases === ($ajustes['phpfmt.passes'] ?? null), 'c2 PHP: las passes del guion son las de phpfmt.passes', implode(',', $pases));
        $exportado = function (string $herramienta, string $variable) use ($raiz): ?array {
            $salida = [];
            //RETORNO-IGNORADO: la salida es el JSON; si node falla, queda vacía y el caso falla.
            exec('cd ' . escapeshellarg($raiz) . ' && node -e ' . escapeshellarg("process.stdout.write(JSON.stringify(require('./bin/tools/formato/{$herramienta}').{$variable}))") . ' 2>&1', $salida);
            $datos = json_decode(implode('', $salida), true);
            return is_array($datos) ? $datos : null;
        };
        $js = $exportado('js.js', 'AJUSTES');
        $editorJs = $ajustes['[javascript]'] ?? [];
        $distintas = [];
        foreach ($editorJs as $clave => $valor) {
            if (str_starts_with($clave, 'js/ts.format.') && ($js[substr($clave, strlen('js/ts.format.'))] ?? null) !== $valor) {
                $distintas[] = $clave;
            }
        }
        $tabulador = ($editorJs['editor.tabSize'] ?? null) === ($js['tabSize'] ?? false) && ($editorJs['editor.insertSpaces'] ?? null) === ($js['convertTabsToSpaces'] ?? true);
        $check(is_array($js) && count(array_filter(array_keys($editorJs), fn ($c): bool => is_string($c) && str_starts_with($c, 'js/ts.format.'))) === 18 && $distintas === [] && $tabulador, 'c3 JS: las 18 js/ts.format y el tabulador del editor son las del guion', implode(', ', $distintas));
        $css = $exportado('css.js', 'OPCIONES');
        $check(is_array($css) && $css === ($ajustes['beautify.options'] ?? null), 'c4 SCSS/CSS: beautify.options son las del guion');
        $check(($ajustes['js/ts.tsdk.path'] ?? null) === 'node_modules/typescript/lib' && ($ajustes['js/ts.tsdk.promptToUseWorkspaceVersion'] ?? null) === true, 'c5 el editor usa el TypeScript del proyecto');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        foreach (array_merge(array_values($rutas), [$minificado, $fuera], glob("{$carpeta}/zz-*") ?: []) as $archivo) {
            if (is_file($archivo)) {
                //RETORNO-IGNORADO: lo comprueba z1.
                unlink($archivo);
            }
        }
        if (is_dir($carpeta)) {
            //RETORNO-IGNORADO: lo comprueba z1.
            rmdir($carpeta);
        }
        clearstatcache();
        $check(!file_exists($carpeta) && !file_exists($fuera), 'z1 no quedan los archivos de la prueba');
    }

    return $balance();

})->setDescription('bin/formatear: --check cae con un archivo mal formateado de PHP, JS, SCSS y CSS y pasa formateado; no toca nada fuera del universo; TypeScript es la versión fijada; y el guion y .vscode/settings.json dicen lo mismo.')->setEffects([CliActions::EFFECT_FILES])->register();
