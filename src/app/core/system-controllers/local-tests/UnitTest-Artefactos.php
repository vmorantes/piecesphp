<?php

//bin/artefactos (P193): con --check NO escribe nada, nombra la foto rancia y nunca escribe un trinquete.
//Toca dos archivos versionados y los repone por sha1 en el finally: si la suite muere a mitad, git los enseña.

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
    $correr = function () use ($raiz): array {
        $salida = [];
        $codigo = 0;
        //RETORNO-IGNORADO: la salida entera llega por $salida; la última línea que devuelve exec() no aporta.
        exec('cd ' . escapeshellarg($raiz) . ' && bin/artefactos --check 2>&1', $salida, $codigo);
        return [$codigo, implode("\n", $salida)];
    };

    $fotoOriginal = (string) file_get_contents($foto);
    $trinqueteOriginal = (string) file_get_contents($trinquete);

    try {
        //─── a · Una foto tocada: --check sale con 1, la nombra y NO la rehace ──────────────────
        echoTerminal('[a] Una foto rancia se nombra y no se reescribe');
        $tocada = $fotoOriginal . "\n";
        $check($escribir($foto, $tocada), 'a0 la foto se pudo tocar para la prueba');
        $antes = $huellas($vigiladas);
        [$codigo, $salida] = $correr();
        $check($codigo === 1, 'a1 --check sale con 1', "rc {$codigo}");
        $check(preg_match('/huella del CSS\s+RANCIA/u', $salida) === 1, 'a2 y nombra la foto rancia');
        $check((string) file_get_contents($foto) === $tocada, 'a3 la foto sigue como se dejó: --check no escribe');
        $check($huellas($vigiladas) === $antes, 'a4 y las demás fotos no cambian');
        $check(str_contains($salida, 'artefactos ejecutado:'), 'a5 y emite su línea de «ejecutado» (LEY 18)');
        $check($escribir($foto, $fotoOriginal), 'a6 y se repone');

        //─── b · Un trinquete que se movería: se dice y el archivo NO cambia ────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Un trinquete que se movería se informa y no se escribe');
        $alterado = preg_replace_callback(
            '/(\[TOTAL DE ERRORES VISIBLES\]\s*\R\s*)(\d+)(?![\s\S]*\[TOTAL DE ERRORES VISIBLES\])/u',
            fn(array $m): string => $m[1] . ((int) $m[2] + 1),
            $trinqueteOriginal
        );
        $check(is_string($alterado) && $alterado !== $trinqueteOriginal, 'b0 la línea base se pudo alterar para la prueba');
        $check($escribir($trinquete, (string) $alterado), 'b0b y se pudo escribir');
        [$codigo, $salida] = $correr();
        $check(preg_match('/PHPStan\s+se movería: \d+ → \d+/u', $salida) === 1, 'b1 dice que el trinquete de PHPStan se movería');
        $check((string) file_get_contents($trinquete) === $alterado, 'b2 y no lo escribe');
        $check($codigo === 0, 'b3 y con las fotos al día --check sale con 0: un trinquete no es una foto rancia', "rc {$codigo}");
    } finally {
        $escribir($foto, $fotoOriginal);
        $escribir($trinquete, $trinqueteOriginal);
    }
    $check(sha1_file($foto) === sha1($fotoOriginal) && sha1_file($trinquete) === sha1($trinqueteOriginal), 'z1 los dos archivos quedan como estaban (sha1)');

    return $balance();

})->setDescription('bin/artefactos --check no escribe, nombra la foto rancia y nunca escribe un trinquete')->setEffects([CliActions::EFFECT_FILES])->register();
