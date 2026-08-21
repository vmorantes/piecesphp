<?php

/**
 * UnitTest-SessionUser.php
 *
 * Congela el contrato ACTUAL de getLoggedFrameworkUser() y de SessionToken.
 *
 * POR QUÉ EXISTE
 * La ventana siguiente va a cambiar ese contrato: `getLoggedFrameworkUser()` devuelve
 * `UserDataPackage|null` y sus llamadas encadenadas sin comprobar explican 123 de los
 * errores de nulabilidad. La propuesta sobre la mesa es añadir una variante que garantice
 * un usuario o falle. Antes de tocarlo hay que tener escrito qué hace hoy.
 *
 * ESTAS PRUEBAS SON DE CARACTERIZACIÓN, NO DE ASPIRACIÓN.
 * Describen el comportamiento actual, incluido el que consideramos defectuoso. Cuando la
 * ventana de nulabilidad cambie el contrato, varias fallarán: **ese fallo es la señal**,
 * no un problema. Cada una dice qué se espera que pase entonces.
 *
 * AISLAMIENTO
 * Se ejecutan desde el CLI, donde NO hay sesión HTTP. Eso no es una limitación: es
 * exactamente el escenario que hoy nadie prueba y en el que estas funciones devuelven
 * null. Un cronjob corre así.
 */

use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\UserDataPackage;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/session-user';
$cliTaskDescription = 'Contrato actual de getLoggedFrameworkUser() y SessionToken sin sesión';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:SessionUser] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;

    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };

    //──── 1. getLoggedFrameworkUser() sin sesión ────────────────────────────────────────
    echoTerminal('[1/6] getLoggedFrameworkUser() sin sesión...');
    $user = getLoggedFrameworkUser();
    $check(
        $user === null || $user instanceof UserDataPackage,
        'devuelve UserDataPackage o null, nunca otra cosa',
        'Obtenido: ' . (is_object($user) ? get_class($user) : gettype($user))
    );
    $check(
        $user === null,
        'sin sesión devuelve null — CONTRATO ACTUAL',
        'Si la ventana de nulabilidad añade una variante que garantice usuario, esta prueba debe seguir pasando: la función original no cambia.'
    );
    echoTerminal(' ');

    //──── 2. Es estable entre llamadas ──────────────────────────────────────────────────
    echoTerminal('[2/6] La función debe ser estable sin sesión...');
    $check(
        getLoggedFrameworkUser() === getLoggedFrameworkUser(),
        'dos llamadas seguidas devuelven lo mismo'
    );
    $check(
        getLoggedFrameworkUser(true) === null,
        'con $reload = true sigue devolviendo null',
        'El parámetro fuerza reconsulta a base de datos; sin sesión no hay a quién consultar.'
    );
    echoTerminal(' ');

    //──── 3. Ese null es la causa de 123 errores ────────────────────────────────────────
    echoTerminal('[3/6] Encadenar sobre el resultado sin comprobar debe fallar...');
    $threw = false;
    $message = '';
    try {
        /** Exactamente el patrón que aparece 123 veces en el código. */
        $x = getLoggedFrameworkUser()->type;
        unset($x);
    } catch (\Throwable $e) {
        $threw = true;
        $message = $e->getMessage();
    }
    $check(
        $threw,
        'getLoggedFrameworkUser()->type sin sesión FALLA — comportamiento actual congelado',
        'Es la forma exacta de los 123 errores de nulabilidad. Excepción: ' . substr($message, 0, 80)
    );
    echoTerminal(' ');

    //──── 3bis. getLoggedFrameworkUserOrFail() ──────────────────────────────────────────
    echoTerminal('[3bis] getLoggedFrameworkUserOrFail() sin sesión debe lanzar...');
    $threw = false;
    $className = '';
    $msg = '';
    try {
        getLoggedFrameworkUserOrFail();
    } catch (\Throwable $e) {
        $threw = true;
        $className = get_class($e);
        $msg = $e->getMessage();
    }
    $check($threw, 'sin sesión LANZA en vez de devolver null',
        'Excepción: ' . $className . ' — ' . substr($msg, 0, 60));

    /**
     * La razón por la que sustituir es seguro: el código de HOY ya aborta en esos
     * sitios. Se comprueba que los dos caminos fallan, para que quede escrito que el
     * cambio afecta al MENSAJE, no a si la petición sobrevive.
     */
    $failsOnChain = false;
    try {
        $x = getLoggedFrameworkUser()->type;
        unset($x);
    } catch (\Throwable $e) {
        $failsOnChain = true;
    }
    $check($failsOnChain && $threw,
        'los DOS caminos abortan sin sesión: el cambio mejora el diagnóstico, no el resultado',
        'Encadenar sobre null ya lanzaba ErrorException porque bootstrap.php promueve E_WARNING en ambos entornos.');
    echoTerminal(' ');

    //──── 4. SessionToken sin cabecera ni cookie ────────────────────────────────────────
    echoTerminal('[4/6] SessionToken::getJWTReceived() sin cabecera ni cookie...');
    $jwt = SessionToken::getJWTReceived();
    $check(
        is_string($jwt),
        'devuelve siempre una cadena, nunca null',
        'Obtenido: ' . gettype($jwt) . ' de longitud ' . (is_string($jwt) ? mb_strlen($jwt) : 0)
    );
    $check(
        $jwt === '',
        'sin cabecera JWTAuth ni cookie devuelve cadena vacía',
        'Importa: el consumidor debe comprobar la cadena vacía, no un null.'
    );
    echoTerminal(' ');

    //──── 5. isActiveSession con entradas inválidas ─────────────────────────────────────
    echoTerminal('[5/6] SessionToken::isActiveSession() con entradas inválidas...');
    foreach ([
        '' => 'cadena vacía',
        'no-es-un-jwt' => 'texto que no es un JWT',
        'aaa.bbb.ccc' => 'tres segmentos pero basura',
    ] as $input => $description) {
        try {
            $isActive = SessionToken::isActiveSession((string) $input);
            $check(
                $isActive === false,
                "isActiveSession({$description}) === false",
                'Obtenido: ' . var_export($isActive, true)
            );
        } catch (\Throwable $e) {
            //Que lance también es un contrato; se congela cuál de los dos es.
            $check(
                false,
                "isActiveSession({$description}) lanzó excepción en vez de devolver false",
                'Excepción: ' . substr($e->getMessage(), 0, 80)
            );
        }
    }
    echoTerminal(' ');

    //──── 6. Coherencia entre las dos ───────────────────────────────────────────────────
    echoTerminal('[6/6] Coherencia entre SessionToken y el usuario del framework...');
    $check(
        SessionToken::isActiveSession(SessionToken::getJWTReceived()) === false && getLoggedFrameworkUser() === null,
        'sin sesión activa, no hay usuario: las dos coinciden',
        'Si divergen, hay un camino que devuelve usuario sin sesión válida, o al revés.'
    );
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:SessionUser] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Contrato actual de sesión y usuario congelado ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->register();
