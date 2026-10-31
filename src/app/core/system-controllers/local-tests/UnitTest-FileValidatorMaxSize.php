<?php

//El tamaño máximo por defecto de FileValidator se calcula en bytes (G7-15): «8M» frente a «128M», «1G», «512K» y «0»
//(sin límite, como en php.ini). En proceso; escribe un temporal propio y lo borra.
//@areas nucleo

use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/file-validator-max-size', function ($args) {

    echoTerminal("\e[33m[TEST:FileValidatorMaxSize] El tamaño máximo por defecto, en bytes\e[39m");
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
    $mb = 1024 * 1024;

    echoTerminal('[a] De php.ini a bytes');
    $check(FileValidator::iniSizeToBytes('1G') === 1024 * $mb, 'a1 «1G» es un gigabyte, no 1 MB', var_export(FileValidator::iniSizeToBytes('1G'), true));
    $check(FileValidator::iniSizeToBytes('512K') === 512 * 1024, 'a2 «512K» son 512 KB, no 512 MB', var_export(FileValidator::iniSizeToBytes('512K'), true));
    $check(FileValidator::iniSizeToBytes('8M') === 8 * $mb && FileValidator::iniSizeToBytes('2048') === 2048, 'a3 «8M» y un número sin unidad');
    $check(FileValidator::iniSizeToBytes('0') === null, 'a4 «0» es sin límite');
    echoTerminal(' ');

    echoTerminal('[b] El menor de los dos, comparando números');
    $check(FileValidator::defaultMaxBytes('8M', '128M') === 8 * $mb, 'b1 «8M» frente a «128M» da 8M (como texto, «128M» < «8M»)', var_export(FileValidator::defaultMaxBytes('8M', '128M'), true));
    $check(FileValidator::defaultMaxBytes('1G', '512K') === 512 * 1024, 'b2 «1G» frente a «512K» da 512K');
    $check(FileValidator::defaultMaxBytes('0', '2M') === 2 * $mb, 'b3 uno sin límite no cuenta: manda el otro');
    $check(FileValidator::defaultMaxBytes('0', '0') === null, 'b4 los dos sin límite: sin límite');
    echoTerminal(' ');

    echoTerminal('[c] validate() con el límite');
    $temporal = tempnam(sys_get_temp_dir(), 'zz-fv-');
    try {
        $check($temporal !== false && file_put_contents($temporal, str_repeat('z', 600 * 1000)) !== false, 'c0 banco: un archivo de 600 KB');
        $limite = new \ReflectionProperty(FileValidator::class, 'maxFileSizeMB');
        $sinLimite = new FileValidator([FileValidator::TYPE_ANY]);
        $limite->setValue($sinLimite, null);
        $check($sinLimite->validate((string) $temporal, 'zz.txt'), 'c1 sin límite, pasa', $sinLimite->getMessage());
        $corto = new FileValidator([FileValidator::TYPE_ANY]);
        $limite->setValue($corto, FileValidator::iniSizeToBytes('512K') / 1000 / 1000);
        $check(!$corto->validate((string) $temporal, 'zz.txt'), 'c2 CANARIO: con 512K, no pasa');
        $check((new FileValidator([FileValidator::TYPE_ANY], 1))->validate((string) $temporal, 'zz.txt'), 'c3 un máximo explícito de 1 MB, como antes, pasa');
    } finally {
        if (is_string($temporal) && is_file($temporal)) {
            //RETORNO-IGNORADO: el temporal es propio; si no se borrara, lo diría z1.
            unlink($temporal);
        }
        $check(!is_string($temporal) || !is_file($temporal), 'z1 el temporal no queda');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El tamaño máximo por defecto de FileValidator se calcula en bytes: 8M frente a 128M, 1G, 512K y 0 (sin límite).')->setEffects([CliActions::EFFECT_FILES])->register();
