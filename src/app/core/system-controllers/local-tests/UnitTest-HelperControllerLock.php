<?php

//P324 (H276): construir un HelperController deja lock_assets como estaba, sea true o false. Se descubren por el nombre
//del archivo bajo src/app/classes, no por una lista escrita; el total esperado va abajo y la suite lo comprueba.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/helper-controller-lock', function ($args) {

    echoTerminal("\e[33m[TEST:HelperControllerLock] Los HelperController dejan el candado de los estáticos como lo encontraron\e[39m");
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

    //Si cambia, se cambia a sabiendas: un módulo nuevo con su HelperController, o uno retirado.
    $expected = 25;
    $previous = get_config('lock_assets');

    try {
        $classes = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(basepath('app/classes'), \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || $file->getFilename() !== 'HelperController.php') {
                continue;
            }
            $source = (string) file_get_contents($file->getPathname());
            if (preg_match('/^namespace\s+([^;]+);/m', $source, $namespace) === 1) {
                $classes[str_replace('\\', '/', substr($file->getPathname(), strlen(basepath(''))))] = trim($namespace[1]) . '\\HelperController';
            }
        }
        ksort($classes);
        $check(count($classes) === $expected, "a0 se descubren {$expected} HelperController", (string) count($classes));

        foreach ($classes as $path => $class) {
            $results = [];
            foreach ([true, false] as $state) {
                set_config('lock_assets', $state);
                new $class();
                $results[] = get_config('lock_assets') === $state;
            }
            $check($results === [true, true], "{$path}: true sigue true y false sigue false", json_encode($results) ?: '');
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        set_config('lock_assets', $previous);
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P324 (H276): construir cada HelperController de src/app/classes deja lock_assets como estaba (true y false); descubiertos por nombre de archivo, con el total esperado comprobado.')->setEffects([CliActions::EFFECT_NONE])->register();
