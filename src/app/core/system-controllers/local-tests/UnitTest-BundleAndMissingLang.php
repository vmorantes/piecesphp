<?php

//bundle y scan-missing-lang por su efecto (D15, 11): no tienen inverso, así que se prueba la ida sobre carpetas de prueba
//en el temporal del sistema. La única escritura en src/ es la de lang(), y su carpeta se borra al terminar.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\BundleTask;
use Terminal\Tasks\ScanMissingLangTask;

CliActions::make('unit-tests:core/bundle-and-missing-lang', function ($args) {

    echoTerminal("\e[33m[TEST:BundleAndMissingLang] bundle y scan-missing-lang, por lo que dejan\e[39m");
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
    $escribir = function (string $ruta, string $contenido): void {
        if (!is_dir(dirname($ruta)) && !mkdir(dirname($ruta), 0755, true)) {
            throw new \RuntimeException("No se pudo crear la carpeta de {$ruta}.");
        }
        if (file_put_contents($ruta, $contenido) === false) {
            throw new \RuntimeException("No se pudo escribir {$ruta}.");
        }
    };
    $listar = function (string $carpeta): array {
        if (!is_dir($carpeta)) {
            return [];
        }
        $archivos = [];
        $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($carpeta, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterador as $archivo) {
            if ($archivo->isFile()) {
                $archivos[] = substr($archivo->getPathname(), strlen(rtrim($carpeta, '/')) + 1);
            }
        }
        sort($archivos);
        return $archivos;
    };
    $borrar = function (string $carpeta) use (&$borrar): void {
        if (!is_dir($carpeta)) {
            return;
        }
        foreach (scandir($carpeta) ?: [] as $nombre) {
            if ($nombre === '.' || $nombre === '..') {
                continue;
            }
            $ruta = "{$carpeta}/{$nombre}";
            is_dir($ruta) && !is_link($ruta) ? $borrar($ruta) : unlink($ruta);
        }
        if (!rmdir($carpeta)) {
            throw new \RuntimeException("No se pudo borrar {$carpeta}.");
        }
    };

    $aleatorio = bin2hex(random_bytes(4));
    $temporal = rtrim(sys_get_temp_dir(), '/') . "/zz-prueba-bundle-{$aleatorio}";
    $grupoReal = "zz-prueba-grupo-{$aleatorio}";
    $carpetaReal = app_basepath('lang/missing-lang-messages');
    $carpetaGrupoReal = "{$carpetaReal}/{$grupoReal}";

    try {
        echoTerminal('[1] bundleInto(): copia app y statics sin lo que no viaja');
        $app = "{$temporal}/src/app";
        $statics = "{$temporal}/src/statics";
        foreach (['a.php', 'sass/x.scss', 'cache/c.txt', 'logs/l.txt', 'sub/b.php'] as $relativa) {
            $escribir("{$app}/{$relativa}", "zz {$relativa}");
        }
        foreach (['s.js', 'plugins/p.js', 'uploads/u.png', 'filemanager/f.txt', 'sass/y.scss'] as $relativa) {
            $escribir("{$statics}/{$relativa}", "zz {$relativa}");
        }
        $destino = "{$temporal}/dest";
        $copio = BundleTask::bundleInto($destino, $app, $statics, true, true, false);
        $enApp = $listar("{$destino}/app");
        $enStatics = $listar("{$destino}/statics");
        $check($copio, '1a dice que copió');
        $check($enApp === ['a.php', 'sub/b.php'], '1b en app solo a.php y sub/b.php', implode(', ', $enApp));
        $check($enStatics === ['s.js'], '1c en statics solo s.js', implode(', ', $enStatics));
        $iguales = true;
        foreach (['app/a.php' => "{$app}/a.php", 'app/sub/b.php' => "{$app}/sub/b.php", 'statics/s.js' => "{$statics}/s.js"] as $copia => $original) {
            $iguales = $iguales && is_file("{$destino}/{$copia}") && sha1_file("{$destino}/{$copia}") === sha1_file($original);
        }
        $check($iguales, '1d lo copiado tiene el mismo sha1 que la fuente');
        $escribir("{$destino}/sobra.txt", 'zz sobra');
        BundleTask::bundleInto($destino, $app, $statics, true, true, false);
        $check(!is_file("{$destino}/sobra.txt") && $listar("{$destino}/app") === ['a.php', 'sub/b.php'], '1e una segunda llamada vacía el destino antes de copiar');
        echoTerminal(' ');

        echoTerminal('[2] bundleInto() con zip: lo que deja (informativo)');
        $destinoZip = "{$temporal}/dest-zip";
        BundleTask::bundleInto($destinoZip, $app, $statics, true, true, true);
        $check(true, '2 informativo — deja: ' . (implode(', ', $listar($destinoZip)) ?: '(nada)'));
        echoTerminal(' ');

        echoTerminal('[3] collectMissing(): idiomas y grupos');
        $falsa = "{$temporal}/missing";
        $escribir("{$falsa}/zzgrupo/en/h1.to-translate", 'zz cadena uno');
        $escribir("{$falsa}/zzgrupo/fr/h2.to-translate", 'zz dos');
        $escribir("{$falsa}/zzotro/en/h3.to-translate", 'zz tres');
        $escribir("{$falsa}/zzgrupo/en/h4.txt", 'zz otra extensión');
        $reunido = ScanMissingLangTask::collectMissing($falsa, ['es', 'en'], [], ['zzotro']);
        $check($reunido === ['en' => ['zzgrupo' => ['zz cadena uno' => 'zz cadena uno']]], '3a solo en > zzgrupo > «zz cadena uno»', (string) json_encode($reunido, \JSON_UNESCAPED_UNICODE));
        $reunido = ScanMissingLangTask::collectMissing($falsa, ['es', 'en'], ['en'], ['zzotro']);
        $check($reunido === [], '3b con en excluido, vacío', (string) json_encode($reunido, \JSON_UNESCAPED_UNICODE));
        echoTerminal(' ');

        echoTerminal('[4] La ida de verdad: lang() y collectMissing() sobre la carpeta real');
        lang($grupoReal, 'zz cadena real', 'en');
        $archivoReal = "{$carpetaGrupoReal}/en/" . sha1('zz cadena real') . '.to-translate';
        $check(is_file($archivoReal), '4a lang() deja su .to-translate', $archivoReal);
        $reunido = ScanMissingLangTask::collectMissing($carpetaReal, ['en'], [], []);
        $check(($reunido['en'][$grupoReal]['zz cadena real'] ?? null) === 'zz cadena real', '4b collectMissing() lo trae');
    } finally {
        $borrar($carpetaGrupoReal);
        $borrar($temporal);
        $check(!is_dir($carpetaGrupoReal) && !is_dir($temporal), 'z1 no queda ni la carpeta del grupo zz ni el temporal');
    }
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('bundle y scan-missing-lang por su efecto: la copia de app y statics sin lo que no viaja, el zip (informativo), la reunión por idioma y grupo, y la ida real de lang(). En el temporal del sistema; la carpeta zz de lang() se borra.')->setEffects([CliActions::EFFECT_FILES])->register();
