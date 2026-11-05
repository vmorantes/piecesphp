<?php

//Dos conversiones de src/app/core/Utilities.php que se quedan (P221) y tenían defectos (H64): seconds_to_duration() daba
//la vuelta a las 24 horas, y decimalCoordinatesToDMS() con coma decimal devolvía null. En proceso, sin base ni archivos.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/utilities-conversions', function ($args) {

    echoTerminal("\e[33m[TEST:UtilitiesConversions] seconds_to_duration() y decimalCoordinatesToDMS()\e[39m");
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

    echoTerminal('[1] seconds_to_duration()');
    $duracion = seconds_to_duration(90000);
    $check($duracion === '25hrs 00mns 00segs', '1a 90000 s son 25 horas, sin vuelta a las 24', var_export($duracion, true));
    $duracion = seconds_to_duration(3661);
    $check($duracion === '01h 01mn 01seg', '1b 3661 s, como antes: una hora, un minuto y un segundo', var_export($duracion, true));
    echoTerminal(' ');

    echoTerminal('[2] decimalCoordinatesToDMS()');
    $conPunto = decimalCoordinatesToDMS('-74.0721', 'longitude', '.');
    $conComa = decimalCoordinatesToDMS('-74,0721', 'longitude', ',');
    $check($conComa !== null && $conComa === $conPunto, '2a con coma decimal da lo mismo que con punto', var_export($conComa, true));
    //Lo que daba con punto antes del arreglo (51cf99ca): no puede cambiar.
    $check(
        is_array($conPunto) && $conPunto['hemisphere'] === 'W' && $conPunto['degrees'] === -74 && $conPunto['minutes'] == 4 && abs($conPunto['seconds'] - 19.56) < 0.000001,
        '2b con punto, igual que antes: 74° 4\' 19,56" W',
        var_export($conPunto, true)
    );
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Dos conversiones de Utilities.php: seconds_to_duration() pasa de 24 horas y decimalCoordinatesToDMS() acepta la coma decimal con el mismo resultado que el punto.')->setEffects([CliActions::EFFECT_NONE])->register();
