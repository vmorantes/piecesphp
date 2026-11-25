<?php

//bin/cli public-site-export con from= y sin site=: el nombre sale del pcs.json de la carpeta, y sin él lo dice. Carpetas zz- en un temporal, retiradas.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\PublicSiteExportTask;

CliActions::make('unit-tests:core/export-task-site-name', function ($args) {

    echoTerminal("\e[33m[TEST:ExportTaskSiteName] public-site-export deduce el sitio del pcs.json de from=, y sin él pide site=\e[39m");
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
    $temporal = sprintf('%s/zz-export-task-site-name-%s', sys_get_temp_dir(), bin2hex(random_bytes(4)));
    $conNombre = "{$temporal}/con-nombre";
    $sinManifiesto = "{$temporal}/sin-manifiesto";
    $sinNombre = "{$temporal}/sin-nombre";
    $nombreVacio = "{$temporal}/nombre-vacio";
    $destino = "{$temporal}/destino";
    $archivos = ["{$conNombre}/pcs.json", "{$sinNombre}/pcs.json", "{$nombreVacio}/pcs.json"];

    try {
        $banco = mkdir($conNombre, 0700, true) && mkdir($sinManifiesto, 0700, true) && mkdir($sinNombre, 0700, true) && mkdir($nombreVacio, 0700, true)
            && file_put_contents("{$conNombre}/pcs.json", "{\"name\": \"zz-sitio\"}\n") !== false
            && file_put_contents("{$sinNombre}/pcs.json", "{\"version\": 1}\n") !== false
            && file_put_contents("{$nombreVacio}/pcs.json", "{\"name\": \"\"}\n") !== false;
        $check($banco, 's0 banco: carpetas con pcs.json con name, sin pcs.json, sin name y con name vacío');

        echoTerminal('[s] siteFromFolder(): el nombre del pcs.json de from=');
        $check(PublicSiteExportTask::siteFromFolder($conNombre, '/nada') === 'zz-sitio', 's1 ruta absoluta con pcs.json con name: ese nombre');
        $check(PublicSiteExportTask::siteFromFolder('con-nombre', $temporal) === 'zz-sitio', 's2 ruta relativa: se resuelve contra la raíz que se le pasa');
        $check(PublicSiteExportTask::siteFromFolder($sinManifiesto, '/nada') === null, 's3 sin pcs.json: null');
        $check(PublicSiteExportTask::siteFromFolder($sinNombre, '/nada') === null, 's4 pcs.json sin name: null');
        $check(PublicSiteExportTask::siteFromFolder($nombreVacio, '/nada') === null, 's5 pcs.json con name vacío: null');

        echoTerminal('');
        echoTerminal('[m] bin/cli public-site-export from=<sin pcs.json> sin site=: dice qué falta');
        $salida = [];
        $codigo = 0;
        //RETORNO-IGNORADO: la salida entera llega por $salida.
        exec('cd ' . escapeshellarg($raiz) . ' && bin/cli public-site-export from=' . escapeshellarg($sinManifiesto) . ' destination=' . escapeshellarg($destino) . ' 2>&1', $salida, $codigo);
        $texto = implode("\n", $salida);
        $esperado = "la carpeta {$sinManifiesto} no tiene pcs.json (o no dice su nombre): indica site=<nombre>";
        $check($codigo === 1 && str_contains($texto, $esperado) && !str_contains($texto, 'faltan parámetros'), 'm1 sale con 1 y nombra la carpeta sin pcs.json, no «faltan parámetros»', "rc {$codigo} · " . mb_substr($texto, -300));
        $check(!file_exists($destino), 'm2 y no crea el destino');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach ($archivos as $archivo) {
            if (is_file($archivo)) {
                //RETORNO-IGNORADO: lo comprueba z1.
                unlink($archivo);
            }
        }
        foreach ([$conNombre, $sinManifiesto, $sinNombre, $nombreVacio, $temporal] as $directorio) {
            if (is_dir($directorio)) {
                //RETORNO-IGNORADO: lo comprueba z1.
                rmdir($directorio);
            }
        }
        clearstatcache();
        $check(!file_exists($temporal), 'z1 no queda el temporal de la prueba');
    }

    return $balance();

})->setDescription('public-site-export: con from= y sin site=, el nombre sale del pcs.json de la carpeta (siteFromFolder), y sin pcs.json o sin name lo dice y pide site=.')->setEffects([CliActions::EFFECT_FILES])->register();
