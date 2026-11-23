<?php

//bin/artefactos (P193): con --check NO escribe nada, nombra la foto rancia y nunca escribe un trinquete.
//Altera COPIAS y las pasa con --sustituye: nunca escribe un archivo versionado (H473).
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/artefactos', function ($args) {

    echoTerminal("\e[33m[TEST:Artefactos] bin/artefactos --check no escribe, nombra lo rancio y no toca trinquetes\e[39m");
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
    $foto = "{$raiz}/files/dev/css-fingerprint.json";
    $vigiladas = [
        "{$raiz}/files/dev/permissions-declared.json",
        "{$raiz}/files/dev/integrity-signatures.json",
        "{$raiz}/files/dev/scss-census.json",
        "{$raiz}/files/dev/fatal-candidates.json",
    ];
    $trinquete = "{$raiz}/PHPStanResult.Summary.baseline.txt";
    $huellas = function (array $rutas): array {
        $resultado = [];
        foreach ($rutas as $ruta) {
            $resultado[$ruta] = is_file($ruta) ? (string) sha1_file($ruta) : '-';
        }
        return $resultado;
    };
    $escribir = fn(string $ruta, string $datos): bool => file_put_contents($ruta, $datos) === strlen($datos);
    $correr = function (string $argumentos) use ($raiz): array {
        $salida = [];
        $codigo = 0;
        //RETORNO-IGNORADO: la salida entera llega por $salida; la última línea que devuelve exec() no aporta.
        exec('cd ' . escapeshellarg($raiz) . ' && bin/artefactos ' . $argumentos . ' 2>&1', $salida, $codigo);
        return [$codigo, implode("\n", $salida)];
    };

    $fotoOriginal = (string) file_get_contents($foto);
    $trinqueteOriginal = (string) file_get_contents($trinquete);
    $versionados = $huellas([$foto, $trinquete]);
    $temporal = sprintf('%s/artefactos-prueba-%s', sys_get_temp_dir(), bin2hex(random_bytes(6)));
    $copiaFoto = "{$temporal}/css-fingerprint.json";
    $copiaTrinquete = "{$temporal}/PHPStanResult.Summary.baseline.txt";

    try {
        $check(mkdir($temporal, 0700), '00 el temporal propio se pudo crear', $temporal);

        //─── a · Una foto tocada: --check sale con 1, la nombra y NO la rehace ──────────────────
        echoTerminal('[a] Una foto rancia se nombra y no se reescribe');
        $tocada = $fotoOriginal . "\n";
        $check($escribir($copiaFoto, $tocada), 'a0 la copia de la foto se pudo tocar para la prueba');
        $antes = $huellas($vigiladas);
        [$codigo, $salida] = $correr('--check --sustituye=' . escapeshellarg("files/dev/css-fingerprint.json={$copiaFoto}"));
        $check($codigo === 1, 'a1 --check sale con 1', "rc {$codigo}");
        $check(preg_match('/huella del CSS\s+RANCIA/u', $salida) === 1, 'a2 y nombra la foto rancia');
        $check((string) file_get_contents($copiaFoto) === $tocada, 'a3 la copia sigue como se dejó: --check no escribe');
        $check($huellas($vigiladas) === $antes, 'a4 y las demás fotos no cambian');
        $check(str_contains($salida, 'artefactos ejecutado:'), 'a5 y emite su línea de «ejecutado» (LEY 18)');
        $check($huellas([$foto, $trinquete]) === $versionados, 'a6 y la foto y la línea base del repositorio no cambian (sha1)');

        //─── b · Un trinquete que se movería: se dice y el archivo NO cambia ────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Un trinquete que se movería se informa y no se escribe');
        $alterado = preg_replace_callback(
            '/(\[TOTAL DE ERRORES VISIBLES\]\s*\R\s*)(\d+)(?![\s\S]*\[TOTAL DE ERRORES VISIBLES\])/u',
            fn(array $m): string => $m[1] . ((int) $m[2] + 1),
            $trinqueteOriginal
        );
        $check(is_string($alterado) && $alterado !== $trinqueteOriginal, 'b0 la copia de la línea base se pudo alterar para la prueba');
        $check($escribir($copiaTrinquete, (string) $alterado), 'b0b y se pudo escribir');
        [$codigo, $salida] = $correr('--check --sustituye=' . escapeshellarg("PHPStanResult.Summary.baseline.txt={$copiaTrinquete}"));
        $check(preg_match('/PHPStan\s+se movería: \d+ → \d+/u', $salida) === 1, 'b1 dice que el trinquete de PHPStan se movería');
        $check((string) file_get_contents($copiaTrinquete) === $alterado, 'b2 y no escribe la copia');
        $check($codigo === 0, 'b3 y con las fotos al día --check sale con 0: un trinquete no es una foto rancia', "rc {$codigo}");

        //─── c · --sustituye sin --check, o sobre lo que el guion no lee: error y nada corre ────
        echoTerminal(' ');
        echoTerminal('[c] --sustituye fuera de --check o sobre un trinquete ajeno es un error');
        [$codigo, $salida] = $correr('--sustituye=' . escapeshellarg("files/dev/css-fingerprint.json={$copiaFoto}"));
        $check($codigo === 2 && str_contains($salida, 'solo vale junto a --check'), 'c1 sin --check, PARA con su motivo', "rc {$codigo}");
        $check(!str_contains($salida, '== fotos'), 'c2 y no regenera nada');
        [$codigo, $salida] = $correr('--check --sustituye=' . escapeshellarg("files/dev/sql-concat-baseline.json={$copiaTrinquete}"));
        $check($codigo === 2 && str_contains($salida, 'no es algo que este guion lea'), 'c3 un trinquete que lee su censo no se sustituye', "rc {$codigo}");
    } finally {
        $limpio = true;
        foreach ([$copiaFoto, $copiaTrinquete] as $copia) {
            if (is_file($copia)) {
                $limpio = unlink($copia) && $limpio;
            }
        }
        if (is_dir($temporal)) {
            $limpio = rmdir($temporal) && $limpio;
        }
    }
    $check($huellas([$foto, $trinquete]) === $versionados, 'z1 la foto y la línea base del repositorio no cambiaron (sha1 antes de [a] y después de [c])');
    $check($limpio && !is_dir($temporal), 'z2 el temporal propio se borró');

    return $balance();

})->setDescription('bin/artefactos --check no escribe, nombra la foto rancia y nunca escribe un trinquete')->setEffects([CliActions::EFFECT_FILES])->register();
