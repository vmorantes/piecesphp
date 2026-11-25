<?php

//verify-integrity deja fuera files/design/ (ADR 0086 §4) en el universo de PHPStan y en los 100755 sin «#!».
//Los dos filtros reciben la raíz y la lista de git ya leída: el banco vive en una raíz temporal propia, nunca en el repositorio.
//@areas nucleo

use PiecesPHP\PublicSite\SiteExporter;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\VerifyIntegrityTask;

CliActions::make('unit-tests:core/verify-integrity-design', function ($args) {

    echoTerminal("\e[33m[TEST:verify-integrity-design] files/design/ no cuenta en el universo de PHPStan ni en los bits de ejecución\e[39m");
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
    $call = function (string $method, array $arguments = []) {
        return (new \ReflectionMethod(VerifyIntegrityTask::class, $method))->invokeArgs(null, $arguments);
    };

    //Una raíz temporal propia de la corrida: ninguna suite en paralelo ve este files/design/ ni este app/cache/.
    $prefijo = 'zz-vi-design-' . getmypid() . '-' . bin2hex(random_bytes(3));
    $raiz = sys_get_temp_dir() . "/{$prefijo}";
    $relDiseno = SiteExporter::DESIGN_DIRECTORY . "/{$prefijo}/zz.php";
    //El de contraste, en app/cache/: fuera de files/design/ el mismo archivo sí cuenta.
    $relPropio = "src/app/cache/{$prefijo}.php";
    $sinPrograma = "<?php\necho 1;\n";
    $sha = str_repeat('0', 40);

    try {
        $banco = mkdir($raiz, 0700) && mkdir(dirname("{$raiz}/{$relDiseno}"), 0775, true) && mkdir(dirname("{$raiz}/{$relPropio}"), 0775, true)
            && file_put_contents("{$raiz}/{$relDiseno}", $sinPrograma) !== false && file_put_contents("{$raiz}/{$relPropio}", $sinPrograma) !== false;
        $check($banco, 'd0 banco: un .php sin «#!» bajo files/design/ y otro igual en app/cache/');

        echoTerminal('[p] checkPhpStanUniverse: el PHP de files/design/ no es del universo');
        $bloques = ['paths' => ['src'], 'excludePaths' => []];
        [$total, $analizados, $excluidos, $sinCubrir] = $call('phpStanUniverseTally', [$raiz, [$relDiseno, $relPropio], $bloques]);
        $check($total === 1 && $analizados === 1 && $excluidos === 0 && $sinCubrir === [], 'p1 solo cuenta el de app/cache/: total 1, analizado 1', json_encode([$total, $analizados, $excluidos, $sinCubrir]) ?: '');
        [$total, , , $sinCubrir] = $call('phpStanUniverseTally', [$raiz, [$relDiseno], ['paths' => ['src'], 'excludePaths' => []]]);
        $check($total === 0 && $sinCubrir === [], 'p2 DISCRIMINANTE: el de files/design/, fuera de paths, no sale como sin cubrir', json_encode([$total, $sinCubrir]) ?: '');

        echoTerminal('');
        echoTerminal('[x] checkExecutableBits: un 100755 de files/design/ no tiene que empezar por «#!»');
        $entradas = ["100755 {$sha} 0\t{$relDiseno}", "100755 {$sha} 0\t{$relPropio}", "100644 {$sha} 0\tsrc/index.php"];
        [$ejecutables, $sinShebang] = $call('executablesWithoutShebang', [$raiz, $entradas]);
        $check($ejecutables === 1 && $sinShebang === [$relPropio], 'x1 DISCRIMINANTE: solo el de app/cache/ es un 100755 sin «#!»; el de files/design/ no cuenta', json_encode([$ejecutables, $sinShebang]) ?: '');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        //Solo la raíz temporal de esta corrida: nada fuera de sys_get_temp_dir().
        $limpio = true;
        if (is_dir($raiz) && str_starts_with($raiz, sys_get_temp_dir() . '/zz-vi-design-')) {
            $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterador as $elemento) {
                $limpio = ($elemento->isDir() && !$elemento->isLink() ? rmdir($elemento->getPathname()) : unlink($elemento->getPathname())) && $limpio;
            }
            $limpio = rmdir($raiz) && $limpio;
        }
        clearstatcache();
        $check($limpio && !file_exists($raiz), 'z1 no queda la raíz temporal de la prueba', $raiz);
    }

    return $balance();

})->setDescription('verify-integrity: files/design/ queda fuera del universo de PHPStan y de los archivos en 100755 que deben empezar por «#!».')->setEffects([CliActions::EFFECT_FILES])->register();
