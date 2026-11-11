<?php

//scan-missing-lang: --lang manda sobre no_scan_langs y --exclude-dir deja fuera carpetas (relativas a app/), sin
//llevarse la vecina que empieza igual. En el temporal del sistema; no corre la tarea ni toca su JSON.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\ScanMissingLangTask;

CliActions::make('unit-tests:core/scan-missing-lang-options', function ($args) {

    echoTerminal("\e[33m[TEST:ScanMissingLangOptions] scan-missing-lang: los idiomas forzados y las carpetas excluidas\e[39m");
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

    $permitidos = ['es', 'en'];
    $sinRevisar = ['es', 'fr'];
    $app = '/zz/app';

    echoTerminal('[l] Los idiomas');
    $o = ScanMissingLangTask::options([], $permitidos, $sinRevisar, $app);
    $check($o['langs'] === ['es', 'en'] && $o['excludeLang'] === ['es', 'fr'] && $o['noScanLangs'] === $sinRevisar, 'l1 sin argumentos, como siempre: los permitidos, y no_scan_langs fuera', (string) json_encode($o));
    $o = ScanMissingLangTask::options(['--lang' => 'fr'], $permitidos, $sinRevisar, $app);
    $check($o['langs'] === ['fr'] && !in_array('fr', $o['excludeLang'], true) && !in_array('fr', $o['noScanLangs'], true), 'l2 DISCRIMINANTE: --lang=fr revisa el francés aunque esté en no_scan_langs', (string) json_encode($o));
    $check(in_array('es', $o['excludeLang'], true) && in_array('es', $o['noScanLangs'], true), 'l3 el resto de no_scan_langs sigue fuera', (string) json_encode($o));
    $o = ScanMissingLangTask::options(['--lang' => ' fr, ,en ', '--exclude-lang' => 'en,de'], $permitidos, $sinRevisar, $app);
    $check($o['langs'] === ['fr', 'en'] && $o['excludeLang'] === ['de', 'es'], 'l4 la lista se limpia y lo forzado gana a --exclude-lang', (string) json_encode($o));

    echoTerminal(' ');
    echoTerminal('[c] Las carpetas');
    $o = ScanMissingLangTask::options([], $permitidos, $sinRevisar, $app . '/');
    $check($o['excludeDirs'] === ['/zz/app/vendor', '/zz/app/node_modules'], 'c1 sin argumentos, las de siempre', (string) json_encode($o['excludeDirs']));
    $o = ScanMissingLangTask::options(['--exclude-dir' => 'config/secure/,/otra/abs/,,lang'], $permitidos, $sinRevisar, $app);
    $check($o['excludeDirs'] === ['/zz/app/vendor', '/zz/app/node_modules', '/zz/app/config/secure', '/otra/abs', '/zz/app/lang'], 'c2 DISCRIMINANTE: relativas a app/, las absolutas tal cual y sin vacías', (string) json_encode($o['excludeDirs']));

    $raiz = rtrim(sys_get_temp_dir(), '/') . '/zz-scan-' . bin2hex(random_bytes(3));
    try {
        foreach (['a', 'ab'] as $carpeta) {
            if (!mkdir("{$raiz}/{$carpeta}", 0775, true) || file_put_contents("{$raiz}/{$carpeta}/zz.php", "<?php\n__('zzgrupo', 'zz {$carpeta}');\n") === false) {
                throw new \RuntimeException("No se pudo preparar {$raiz}/{$carpeta}");
            }
        }
        $encontrados = function (array $excluir) use ($raiz): array {
            $r = ScanMissingLangTask::searchFunctionUsesWithParser('__', $raiz, ['excludeDirs' => $excluir]);
            unset($r['_stats']);
            $vistos = array_map(fn (string $f): string => basename(dirname($f)), array_keys($r));
            sort($vistos);
            return $vistos;
        };
        $check($encontrados([]) === ['a', 'ab'], 'c3 sin excluir, las dos carpetas', (string) json_encode($encontrados([])));
        $vistos = $encontrados(ScanMissingLangTask::options(['--exclude-dir' => 'a'], [], [], $raiz)['excludeDirs']);
        $check($vistos === ['ab'], 'c4 DISCRIMINANTE: --exclude-dir=a deja fuera «a» y no «ab»', (string) json_encode($vistos));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach (['a', 'ab'] as $carpeta) {
            if (is_file("{$raiz}/{$carpeta}/zz.php")) {
                //RETORNO-IGNORADO: lo comprueba z1.
                unlink("{$raiz}/{$carpeta}/zz.php");
            }
            if (is_dir("{$raiz}/{$carpeta}")) {
                //RETORNO-IGNORADO: lo comprueba z1.
                rmdir("{$raiz}/{$carpeta}");
            }
        }
        if (is_dir($raiz)) {
            //RETORNO-IGNORADO: lo comprueba z1.
            rmdir($raiz);
        }
        clearstatcache();
        $check(!file_exists($raiz), 'z1 no queda el temporal');
    }

    return $balance();

})->setDescription('scan-missing-lang: --lang revisa idiomas aunque estén en no_scan_langs, --exclude-dir deja fuera carpetas relativas a app/ sin llevarse la vecina que empieza igual. En el temporal del sistema.')->setEffects([CliActions::EFFECT_FILES])->register();
