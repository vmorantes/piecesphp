<?php

//bin/censo-scss --trinquete (H163): falla si una cifra del censo SUBE sobre la línea base y no escribe nada.
//Compara contra COPIAS de la línea base en un temporal propio (--base=): nunca altera la versionada.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/scss-census-ratchet', function ($args) {

    echoTerminal("\e[33m[TEST:ScssCensusRatchet] El censo de SCSS no puede crecer sobre su línea base\e[39m");
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
    $base = "{$raiz}/files/dev/scss-census-baseline.json";
    $censo = "{$raiz}/files/dev/scss-census.json";
    //El sha1 no basta: el censo reescrito sale idéntico byte a byte. La fecha de modificación delata la escritura.
    $huellas = function () use ($base, $censo): array {
        clearstatcache();
        return array_map(fn (string $f): string => is_file($f) ? sha1_file($f) . '@' . filemtime($f) : '-', [$base, $censo]);
    };
    $antes = $huellas();
    $original = json_decode((string) @file_get_contents($base), true);
    if (!$check(is_array($original) && is_array($original['cifras'] ?? null) && is_int($original['cifras']['SOBRESCRITURA'] ?? null), '00 la línea base existe y trae sus cifras', $base)) {
        return $balance();
    }
    $correr = function (string $argumentos) use ($raiz): array {
        $salida = [];
        $codigo = 0;
        //RETORNO-IGNORADO: la salida entera llega por $salida; la última línea que devuelve exec() no aporta.
        exec('cd ' . escapeshellarg($raiz) . ' && bin/censo-scss ' . $argumentos . ' 2>&1', $salida, $codigo);
        $veredicto = '';
        foreach ($salida as $linea) {
            if (str_starts_with($linea, 'TRINQUETE')) {
                $veredicto = $linea;
            }
        }
        return [$codigo, $veredicto];
    };
    $temporal = sprintf('%s/censo-scss-prueba-%s', sys_get_temp_dir(), bin2hex(random_bytes(6)));
    $copia = "{$temporal}/scss-census-baseline.json";
    $escribirCopia = function (array $cifras) use ($original, $copia): bool {
        $datos = $original;
        $datos['cifras'] = $cifras;
        $json = (string) json_encode($datos, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
        return file_put_contents($copia, $json) === strlen($json);
    };

    try {
        $check(mkdir($temporal, 0700), '00 el temporal propio se pudo crear', $temporal);
        //La primera compila (gulp sass-all): las demás miden el mismo CSS.
        [$codigo, $veredicto] = $correr('--trinquete');
        //Igual o por debajo: un censo que baja no rompe (la línea base se rehace en el commit que baja).
        $noSube = fn (int $codigo, string $veredicto): bool => $codigo === 0 && (str_contains($veredicto, 'igual que la línea base') || str_contains($veredicto, 'baja'));
        $check($noSube($codigo, $veredicto), 'a1 con la línea base de verdad, no sube', "rc {$codigo} · {$veredicto}");

        $check($escribirCopia($original['cifras']), 'b0 una copia idéntica de la línea base');
        [$codigo, $veredicto] = $correr('--sin-compilar --trinquete --base=' . escapeshellarg($copia));
        $check($noSube($codigo, $veredicto), 'b1 contra la copia idéntica, no sube', "rc {$codigo} · {$veredicto}");

        //DISCRIMINANTE: una cifra de la línea base por debajo de lo medido es «el censo crece».
        $menor = $original['cifras'];
        $menor['SOBRESCRITURA']--;
        $check($escribirCopia($menor), 'c0 una copia con SOBRESCRITURA una por debajo');
        [$codigo, $veredicto] = $correr('--sin-compilar --trinquete --base=' . escapeshellarg($copia));
        $check($codigo === 1 && str_contains($veredicto, 'TRINQUETE ROTO') && str_contains($veredicto, 'SOBRESCRITURA'), 'c1 DISCRIMINANTE: el censo crece sobre la copia, sale con 1 y nombra la cifra', "rc {$codigo} · {$veredicto}");

        $mayor = $original['cifras'];
        $mayor['lines_total']++;
        $check($escribirCopia($mayor), 'd0 una copia con lines_total una por encima');
        [$codigo, $veredicto] = $correr('--sin-compilar --trinquete --base=' . escapeshellarg($copia));
        $check($codigo === 0 && str_contains($veredicto, 'baja'), 'd1 si el censo baja, avisa y no falla', "rc {$codigo} · {$veredicto}");

        $incompleta = $original['cifras'];
        unset($incompleta['dead_rules']);
        $check($escribirCopia($incompleta), 'e0 una copia sin dead_rules');
        [$codigo, $veredicto] = $correr('--sin-compilar --trinquete --base=' . escapeshellarg($copia));
        $check($codigo === 1 && str_contains($veredicto, 'SIN LÍNEA BASE NO HAY TRINQUETE'), 'e1 sin una cifra en la línea base no hay trinquete: sale con 1', "rc {$codigo} · {$veredicto}");

        //Lo marcado @en-desuso: la marca, los comentarios que la siguen y el bloque de debajo, con lo anidado; nada más.
        $prueba = "{$temporal}/prueba.scss";
        $scssPrueba = ".viva {\n    color: red;\n}\n// @en-desuso 11.0.0: prueba\n.vieja {\n    .hija {\n        color: blue;\n    }\n}\n.otra {\n    color: green;\n}\n";
        $check(file_put_contents($prueba, $scssPrueba) === strlen($scssPrueba), 'g0 un .scss con una regla marcada entre dos sin marcar');
        $salida = [];
        //RETORNO-IGNORADO: la salida llega por $salida.
        exec('cd ' . escapeshellarg($raiz) . ' && bin/censo-scss --en-desuso=' . escapeshellarg($prueba) . ' 2>&1', $salida);
        $marcadas = json_decode((string) end($salida), true);
        $check($marcadas === [4, 5, 6, 7, 8, 9], 'g1 marca la línea de la marca y su bloque entero (4-9), y no .viva ni .otra', json_encode($marcadas) ?: '');
        //Las formas que cortaban el bloque o se tragaban lo de después (revisión de JNW): cada una, su .scss y lo esperado.
        $casos = [
            'g2 una interpolación #{} en el selector no corta el bloque' => ["// @en-desuso\n.#{\$x}.button,\na.#{\$x}.button {\n    color: red;\n    .h { x: 1; }\n}\n.viva { c: 1; }\n", [1, 2, 3, 4, 5, 6]],
            'g3 un url(//…) no se come el cierre ni lo de después' => ["// @en-desuso\n.a { background: url(//cdn/x.png); }\n.viva { c: 1; }\n.otra { c: 2; }\n", [1, 2]],
            'g4 una llave dentro de una cadena no cuenta' => ["// @en-desuso\n.a {\n    content: '}';\n    b: 1;\n}\n.viva { c: 1; }\n", [1, 2, 3, 4, 5]],
            'g5 una marca dentro de un docblock no marca la regla de debajo' => ["/*\n * @en-desuso\n */\n.viva { c: 1; }\n", []],
            'g6 una marca justo antes del cierre de su padre no se come lo de fuera' => [".p {\n    // @en-desuso\n}\n.siguiente { c: 1; }\n", [2]],
            'g7 un selector * tras la marca es código, no comentario' => ["// @en-desuso\n* {\n    box-sizing: border-box;\n}\n.viva { c: 1; }\n", [1, 2, 3, 4]],
            'g8 una marca al final del archivo no inventa líneas' => [".x { c: 1; }\n// @en-desuso\n\n", [2, 3]],
        ];
        foreach ($casos as $nombre => [$contenido, $esperado]) {
            $escrito = file_put_contents($prueba, $contenido) === strlen($contenido);
            $salida = [];
            //RETORNO-IGNORADO: la salida llega por $salida.
            exec('cd ' . escapeshellarg($raiz) . ' && bin/censo-scss --en-desuso=' . escapeshellarg($prueba) . ' 2>&1', $salida);
            $obtenido = json_decode((string) end($salida), true);
            $check($escrito && $obtenido === $esperado, $nombre, json_encode($obtenido) ?: '');
        }

        //De punta a punta: las líneas del trinquete más las en desuso son las de todo el universo de .scss.
        $todas = [];
        //RETORNO-IGNORADO: la lista llega por $todas.
        exec('cd ' . escapeshellarg($raiz) . " && git --no-optional-locks ls-files --cached --others --exclude-standard -- '*.scss'", $todas);
        $lineasUniverso = 0;
        foreach ($todas as $archivo) {
            //Como Python al iterar el archivo: cada salto, más la última línea si no acaba en salto.
            $contenido = (string) file_get_contents("{$raiz}/{$archivo}");
            $lineasUniverso += substr_count($contenido, "\n") + ($contenido !== '' && !str_ends_with($contenido, "\n") ? 1 : 0);
        }
        $salida = [];
        //RETORNO-IGNORADO: la salida llega por $salida.
        exec('cd ' . escapeshellarg($raiz) . ' && bin/censo-scss --sin-compilar --trinquete 2>&1', $salida);
        $texto = implode("\n", $salida);
        $enDesuso = preg_match('/en desuso \(fuera del trinquete\): (\d+) reglas \((\d+) que no pueden casar\), (\d+) líneas/u', $texto, $d) === 1 ? [(int) $d[1], (int) $d[2], (int) $d[3]] : null;
        $lineasTrinquete = preg_match('/lines_total \d+ → (\d+)/u', $texto, $l) === 1 ? (int) $l[1] : null;
        $check($enDesuso !== null && $enDesuso[2] > 0 && $enDesuso[0] > 0, 'h0 imprime lo que queda en desuso, aparte', mb_substr($texto, -300));
        $check($enDesuso !== null && $lineasTrinquete !== null && $lineasTrinquete + $enDesuso[2] === $lineasUniverso, 'h1 DISCRIMINANTE: lines_total del trinquete + las en desuso = las de todo el universo (lo marcado no cuenta)', "trinquete {$lineasTrinquete} + en desuso " . ($enDesuso[2] ?? '?') . " · universo {$lineasUniverso}");
    } finally {
        $limpio = true;
        foreach ([$copia, "{$temporal}/prueba.scss"] as $archivo) {
            if (is_file($archivo)) {
                $limpio = unlink($archivo) && $limpio;
            }
        }
        if (is_dir($temporal)) {
            $limpio = rmdir($temporal) && $limpio;
        }
    }
    $check($huellas() === $antes, 'z1 ni la línea base ni el censo versionados cambiaron (sha1 y fecha): --trinquete no escribe');
    [$codigo, $veredicto] = $correr('--sin-compilar --trinquete --guardar-base');
    $check($codigo === 2 && $huellas() === $antes, 'z3 --guardar-base con --trinquete se rechaza y no escribe nada', "rc {$codigo}");
    $check($limpio && !is_dir($temporal), 'z2 el temporal propio se borró');

    return $balance();

})->setDescription('bin/censo-scss --trinquete: falla si una cifra del censo de SCSS sube sobre la línea base, avisa si baja y no escribe nada')->setEffects([CliActions::EFFECT_FILES])->register();
