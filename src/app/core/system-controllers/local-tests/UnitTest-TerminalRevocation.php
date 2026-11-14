<?php

//H289: la sesión que la terminal fabrica para root no cae en una revocación lanzada en ese mismo segundo. Con valores
//fijos sobre la marca global de este proceso (se repone al final): no toca las sesiones ni la marca de nadie.
//@areas nucleo

use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;

CliActions::make('unit-tests:core/terminal-revocation', function ($args) {

    echoTerminal("\e[33m[TEST:TerminalRevocation] la terminal y las marcas de revocación\e[39m");
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

    //La marca global vive en una estática del proceso: se cambia aquí y se repone en el finally.
    $marcaEstatica = new \ReflectionProperty(SessionToken::class, 'minimumDateCreated');
    $marcaPrevia = $marcaEstatica->getValue();
    try {
        //─── a · La marca global, en el mismo segundo ───────────────────────────────────────────────────
        echoTerminal('[a] isActiveSession() con la marca global en el segundo del token');
        $token = UserSessions::terminalToken(1);
        $creado = BaseToken::getCreated($token);
        $check(is_int($creado), 'a0 el token de la terminal lleva su fecha', var_export($creado, true));
        $creado = is_int($creado) ? $creado : time();
        SessionToken::setMinimumDateCreated(new \DateTime(date('Y-m-d H:i:s', $creado)));
        $check(SessionToken::isActiveSession($token) === false, 'a1 lo que vio ifcol: con la marca en su mismo segundo, no vale');
        $check(SessionToken::isActiveSession($token, null, true) === true, 'a2 sin las marcas (la terminal), vale');
        $check(SessionToken::isActiveSession('zz-no-es-un-token', null, true) === false, 'a3 sin las marcas no se salta la firma: un token roto sigue sin valer');
        echoTerminal(' ');

        //─── b · Las marcas del usuario ─────────────────────────────────────────────────────────────────
        echoTerminal('[b] revocationAllows(): la marca del usuario en el mismo segundo');
        SessionToken::setMinimumDateCreated(new \DateTime(date('Y-m-d H:i:s', $creado - 3600)));
        $mismoSegundo = date('Y-m-d H:i:s', $creado);
        $check(SessionToken::revocationAllows($creado, $mismoSegundo, false) === false, 'b1 una sesión de navegador del mismo segundo que su marca: no vale');
        $check(SessionToken::revocationAllows($creado, $mismoSegundo, true) === true, 'b2 la de la terminal: vale');
        $check(SessionToken::revocationAllows($creado, date('Y-m-d H:i:s', $creado - 1), false) === true, 'b3 DISCRIMINANTE: con la marca un segundo antes, la del navegador vale');
        echoTerminal(' ');

        //─── c · index.php la usa así ───────────────────────────────────────────────────────────────────
        echoTerminal('[c] index.php: la bandera solo en la rama de la terminal, y las dos marcas del usuario por revocationAllows()');
        $fuente = (string) file_get_contents(basepath('index.php'));
        $check(substr_count($fuente, '$terminalSession = true;') === 1 && str_contains($fuente, 'SessionToken::isActiveSession($JWT, null, true)'), 'c1 la rama de la terminal marca su sesión y la valida sin las marcas');
        $check(substr_count($fuente, ', $rootValidFrom, $terminalSession)') === 1 && substr_count($fuente, ', $sessionsValidFrom, $terminalSession)') === 1, 'c2 la marca de root y la del usuario pasan por revocationAllows() con la bandera');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        $marcaEstatica->setValue(null, $marcaPrevia);
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La terminal y la revocación (H289): con la marca global o la del usuario en el mismo segundo que el token, una sesión de navegador no vale y la que la terminal fabrica para root sí; e index.php lo cablea así.')->setEffects([CliActions::EFFECT_NONE])->register();
