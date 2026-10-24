<?php

//El paquete de sesión no admite escrituras desde fuera (D15, 6): un nombre nuevo o uno declarado lanzan LogicException
//y leer sigue igual. No escribe en la base.

use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\UserDataPackage;

CliActions::make('unit-tests:core/user-data-package-read-only', function ($args) {

    echoTerminal("\e[33m[TEST:UserDataPackageReadOnly] El paquete de sesión no admite escrituras desde fuera\e[39m");
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
    $escribir = function (UserDataPackage $paquete, string $nombre, mixed $valor): ?\Throwable {
        try {
            $paquete->$nombre = $valor;
        } catch (\Throwable $e) {
            return $e;
        }
        return null;
    };

    $paquete = new UserDataPackage(1);
    $usernameAntes = $paquete->username;

    echoTerminal('[1] Un nombre no declarado');
    $e = $escribir($paquete, 'zzInventado', 'x');
    $check($e !== null && get_class($e) === \LogicException::class, '1a lanza exactamente \LogicException', $e === null ? 'no lanzó' : get_class($e));
    $check($e !== null && str_contains($e->getMessage(), 'zzInventado'), '1b el mensaje nombra la propiedad', $e === null ? '' : $e->getMessage());
    echoTerminal(' ');

    echoTerminal('[2] Una propiedad declarada');
    $e = $escribir($paquete, 'username', 'zz-otro');
    $check($e !== null && get_class($e) === \LogicException::class, '2a lanza exactamente \LogicException', $e === null ? 'no lanzó' : get_class($e));
    $check($paquete->username === $usernameAntes, '2b el valor leído no cambia', var_export($paquete->username, true));
    echoTerminal(' ');

    echoTerminal('[3] Leer sigue funcionando');
    $check($paquete->id === 1, '3a id', var_export($paquete->id, true));
    $check(is_string($paquete->username) && $paquete->username !== '', '3b username', var_export($paquete->username, true));
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El paquete de sesión no admite escrituras desde fuera: un nombre nuevo o uno declarado lanzan LogicException, y leer sigue igual. No escribe en la base.')->setEffects([CliActions::EFFECT_NONE])->register();
