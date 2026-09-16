<?php

//La configuración se cambia SOLO en memoria (set_config) y se restaura en un finally: la guardada no se toca.
//Los destinatarios fallan cerrado: una sola entrada inválida vacía la lista entera.

use App\Controller\ContactFormsController;
use App\Controller\UserProblemsController;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/form-recipients', function ($args) {

    echoTerminal("\e[33m[TEST:FormRecipients] Los destinatarios de los formularios públicos salen de la configuración\e[39m");
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

    $casos = [
        'c1' => [null, []],
        'c2' => [[], []],
        'c3' => ['zz-prueba-a@localhost.test', []],
        'c4' => [['zz-prueba-a@localhost.test', 'no-es-correo'], []],
        'c5' => [['zz-prueba-a@localhost.test', 5], []],
        'c6' => [[' zz-prueba-a@localhost.test ', 'zz-prueba-b@localhost.test'], ['zz-prueba-a@localhost.test', 'zz-prueba-b@localhost.test']],
        'c7' => [['x' => 'zz-prueba-a@localhost.test'], ['zz-prueba-a@localhost.test']],
    ];

    $pares = [
        ['contact_form_recipients', [ContactFormsController::class, 'recipients']],
        ['other_problems_recipients', [UserProblemsController::class, 'otherProblemsRecipients']],
    ];

    $bloque = 0;
    foreach ($pares as [$clave, $resolutor]) {

        $bloque++;
        echoTerminal("[{$bloque}/3] {$clave}");
        $original = get_config($clave);

        try {
            foreach ($casos as $caso => [$valor, $esperado]) {
                set_config($clave, $valor);
                $detalle = '';
                $obtenido = null;
                try {
                    $obtenido = call_user_func($resolutor);
                } catch (\Throwable $e) {
                    $detalle = get_class($e) . ': ' . $e->getMessage();
                }
                if ($detalle === '' && $obtenido !== $esperado) {
                    $detalle = 'devolvió ' . json_encode($obtenido, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                }
                $check($detalle === '', "{$caso} de {$clave}: " . json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . ' → ' . json_encode($esperado, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $detalle);
            }
        } finally {
            set_config($clave, $original === false ? null : $original);
        }

        echoTerminal(' ');
    }

    echoTerminal('[3/3] Ninguna dirección queda escrita en los controladores');
    $check(!defined(ContactFormsController::class . '::RECIPIENTS_MESSAGES'), 'e1: ContactFormsController ya no tiene RECIPIENTS_MESSAGES');
    $check(!property_exists(ContactFormsController::class, 'recipientsMessages'), 'e2: ContactFormsController ya no tiene $recipientsMessages');
    $check(!defined(UserProblemsController::class . '::EMAIL_ON_FAILED_OS_TICKET'), 'e3: UserProblemsController ya no tiene EMAIL_ON_FAILED_OS_TICKET');

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Los destinatarios de los formularios públicos salen de la configuración y fallan cerrado.')->setEffects([CliActions::EFFECT_NONE])->register();
