<?php

//bin/traer-tema --check (H258): decide por lo que registra ORIGEN.json, no por el árbol vivo del tema, que solo avisa.
//Sobre una copia de la carpeta del tema en un temporal propio, que se retira al terminar.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/traer-tema-check', function ($args) {

    echoTerminal("\e[33m[TEST:TraerTemaCheck] traer-tema --check contra ORIGEN.json\e[39m");
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

    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    $herramienta = "{$proyecto}/bin/traer-tema";
    $real = "{$proyecto}/src/statics/plugins/fomantic-piecesphp";
    if (!$check(is_file($herramienta) && is_file("{$real}/ORIGEN.json"), 'banco: la herramienta y la carpeta del tema con su ORIGEN.json')) {
        return $balance();
    }

    $temporal = rtrim(sys_get_temp_dir(), '/') . '/zz-traer-tema-' . bin2hex(random_bytes(4));
    //Copia la carpeta entera: la suite nunca toca la real.
    $copiar = function (string $desde, string $hasta) use (&$copiar): void {
        if (!mkdir($hasta, 0775, true)) {
            throw new \RuntimeException("No se pudo crear {$hasta}.");
        }
        foreach (scandir($desde) ?: [] as $nombre) {
            if ($nombre === '.' || $nombre === '..') {
                continue;
            }
            if (is_dir("{$desde}/{$nombre}")) {
                $copiar("{$desde}/{$nombre}", "{$hasta}/{$nombre}");
            } elseif (!copy("{$desde}/{$nombre}", "{$hasta}/{$nombre}")) {
                throw new \RuntimeException("No se pudo copiar {$nombre}.");
            }
        }
    };
    $borrar = function (string $ruta) use (&$borrar): void {
        if (is_dir($ruta) && !is_link($ruta)) {
            foreach (scandir($ruta) ?: [] as $nombre) {
                if ($nombre !== '.' && $nombre !== '..') {
                    $borrar("{$ruta}/{$nombre}");
                }
            }
            //RETORNO-IGNORADO: lo comprueba z1, que mira que no quede nada.
            rmdir($ruta);
        } elseif (file_exists($ruta)) {
            //RETORNO-IGNORADO: lo comprueba z1, que mira que no quede nada.
            unlink($ruta);
        }
    };
    $correr = function (array $entorno) use ($herramienta): array {
        $prefijo = '';
        foreach ($entorno as $variable => $valor) {
            $prefijo .= $variable . '=' . escapeshellarg($valor) . ' ';
        }
        $salida = [];
        $codigo = 0;
        //RETORNO-IGNORADO: lo que se mira es $salida entera y $codigo.
        exec($prefijo . escapeshellarg($herramienta) . ' --check 2>&1', $salida, $codigo);
        return ['codigo' => $codigo, 'texto' => implode("\n", $salida)];
    };

    try {
        $copiar($real, $temporal);
        $registro = json_decode((string) file_get_contents("{$temporal}/ORIGEN.json"), true);
        $copiado = is_array($registro) ? (string) ($registro['commit'] ?? '') : '';

        $r = $correr(['PCSPHP_TEMA_DESTINO' => $temporal, 'PCSPHP_TEMA_REPO' => "{$temporal}/zz-sin-origen"]);
        $check($r['codigo'] === 0 && str_contains($r['texto'], 'la carpeta es la que registra ORIGEN.json') && !str_contains($r['texto'], 'el origen está en'),
            't1 CANARIO: la copia intacta pasa, también sin el repositorio del tema', $r['texto']);

        $vivo = trim((string) shell_exec('git -C /var/www/html/vicsen/Fomantic-UI --no-optional-locks rev-parse --short HEAD 2>/dev/null'));
        $r = $correr(['PCSPHP_TEMA_DESTINO' => $temporal]);
        if ($vivo !== '' && $copiado !== '' && !str_starts_with($vivo, $copiado) && !str_starts_with($copiado, $vivo)) {
            $check($r['codigo'] === 0 && str_contains($r['texto'], "el origen está en {$vivo}") && str_contains($r['texto'], "lo copiado es {$copiado}"),
                't2 DISCRIMINANTE: con el origen en otro commit, solo avisa y pasa', $r['texto']);
        } else {
            $check($r['codigo'] === 0, 't2 con el origen en el commit copiado (o sin él), pasa (el aviso no aplica hoy)', $r['texto']);
        }

        $tocado = "{$temporal}/semantic.min.css";
        if (file_put_contents($tocado, "\n/* zz-cambio */", FILE_APPEND) === false) {
            throw new \RuntimeException('No se pudo cambiar la copia.');
        }
        $r = $correr(['PCSPHP_TEMA_DESTINO' => $temporal, 'PCSPHP_TEMA_REPO' => "{$temporal}/zz-sin-origen"]);
        $check($r['codigo'] === 1 && str_contains($r['texto'], 'NO coincide con ORIGEN.json'), 't3 DISCRIMINANTE: un archivo cambiado hace FALLAR la comprobación', $r['texto']);

        if (!unlink("{$temporal}/ORIGEN.json")) {
            throw new \RuntimeException('No se pudo quitar ORIGEN.json de la copia.');
        }
        $r = $correr(['PCSPHP_TEMA_DESTINO' => $temporal, 'PCSPHP_TEMA_REPO' => "{$temporal}/zz-sin-origen"]);
        $check($r['codigo'] === 1 && str_contains($r['texto'], 'NO se puede comprobar'), 't4 sin ORIGEN.json, falla en vez de dar por buena la carpeta', $r['texto']);
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        $borrar($temporal);
        $check(!file_exists($temporal), 'z1 no queda la copia temporal');
    }

    return $balance();

})->setDescription('bin/traer-tema --check (H258): pasa con la carpeta que registra ORIGEN.json aunque el tema haya avanzado (solo avisa), falla con un archivo cambiado o sin ORIGEN.json. Sobre una copia en un temporal propio, que retira.')->setEffects([CliActions::EFFECT_FILES])->register();
