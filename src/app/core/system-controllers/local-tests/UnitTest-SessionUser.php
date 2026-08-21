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

    $pasadas = 0;
    $fallidas = 0;

    $comprobar = function (bool $condicion, string $nombre, ?string $detalle = null) use (&$pasadas, &$fallidas) {
        if ($condicion) {
            $pasadas++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$nombre}");
        } else {
            $fallidas++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$nombre}");
        }
        if ($detalle !== null) {
            echoTerminal("      - {$detalle}");
        }
        return $condicion;
    };

    //──── 1. getLoggedFrameworkUser() sin sesión ────────────────────────────────────────
    echoTerminal('[1/6] getLoggedFrameworkUser() sin sesión...');
    $usuario = getLoggedFrameworkUser();
    $comprobar(
        $usuario === null || $usuario instanceof UserDataPackage,
        'devuelve UserDataPackage o null, nunca otra cosa',
        'Obtenido: ' . (is_object($usuario) ? get_class($usuario) : gettype($usuario))
    );
    $comprobar(
        $usuario === null,
        'sin sesión devuelve null — CONTRATO ACTUAL',
        'Si la ventana de nulabilidad añade una variante que garantice usuario, esta prueba debe seguir pasando: la función original no cambia.'
    );
    echoTerminal(' ');

    //──── 2. Es estable entre llamadas ──────────────────────────────────────────────────
    echoTerminal('[2/6] La función debe ser estable sin sesión...');
    $comprobar(
        getLoggedFrameworkUser() === getLoggedFrameworkUser(),
        'dos llamadas seguidas devuelven lo mismo'
    );
    $comprobar(
        getLoggedFrameworkUser(true) === null,
        'con $reload = true sigue devolviendo null',
        'El parámetro fuerza reconsulta a base de datos; sin sesión no hay a quién consultar.'
    );
    echoTerminal(' ');

    //──── 3. Ese null es la causa de 123 errores ────────────────────────────────────────
    echoTerminal('[3/6] Encadenar sobre el resultado sin comprobar debe fallar...');
    $revento = false;
    $mensaje = '';
    try {
        /** Exactamente el patrón que aparece 123 veces en el código. */
        $x = getLoggedFrameworkUser()->type;
        unset($x);
    } catch (\Throwable $e) {
        $revento = true;
        $mensaje = $e->getMessage();
    }
    $comprobar(
        $revento,
        'getLoggedFrameworkUser()->type sin sesión FALLA — comportamiento actual congelado',
        'Es la forma exacta de los 123 errores de nulabilidad. Excepción: ' . substr($mensaje, 0, 80)
    );
    echoTerminal(' ');

    //──── 3bis. getLoggedFrameworkUserOrFail() ──────────────────────────────────────────
    echoTerminal('[3bis] getLoggedFrameworkUserOrFail() sin sesión debe lanzar...');
    $lanzo = false;
    $clase = '';
    $msg = '';
    try {
        getLoggedFrameworkUserOrFail();
    } catch (\Throwable $e) {
        $lanzo = true;
        $clase = get_class($e);
        $msg = $e->getMessage();
    }
    $comprobar($lanzo, 'sin sesión LANZA en vez de devolver null',
        'Excepción: ' . $clase . ' — ' . substr($msg, 0, 60));

    /**
     * La razón por la que sustituir es seguro: el código de HOY ya aborta en esos
     * sitios. Se comprueba que los dos caminos fallan, para que quede escrito que el
     * cambio afecta al MENSAJE, no a si la petición sobrevive.
     */
    $fallaEncadenando = false;
    try {
        $x = getLoggedFrameworkUser()->type;
        unset($x);
    } catch (\Throwable $e) {
        $fallaEncadenando = true;
    }
    $comprobar($fallaEncadenando && $lanzo,
        'los DOS caminos abortan sin sesión: el cambio mejora el diagnóstico, no el resultado',
        'Encadenar sobre null ya lanzaba ErrorException porque bootstrap.php promueve E_WARNING en ambos entornos.');
    echoTerminal(' ');

    //──── 4. SessionToken sin cabecera ni cookie ────────────────────────────────────────
    echoTerminal('[4/6] SessionToken::getJWTReceived() sin cabecera ni cookie...');
    $jwt = SessionToken::getJWTReceived();
    $comprobar(
        is_string($jwt),
        'devuelve siempre una cadena, nunca null',
        'Obtenido: ' . gettype($jwt) . ' de longitud ' . (is_string($jwt) ? mb_strlen($jwt) : 0)
    );
    $comprobar(
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
    ] as $entrada => $descripcion) {
        try {
            $activa = SessionToken::isActiveSession((string) $entrada);
            $comprobar(
                $activa === false,
                "isActiveSession({$descripcion}) === false",
                'Obtenido: ' . var_export($activa, true)
            );
        } catch (\Throwable $e) {
            //Que lance también es un contrato; se congela cuál de los dos es.
            $comprobar(
                false,
                "isActiveSession({$descripcion}) lanzó excepción en vez de devolver false",
                'Excepción: ' . substr($e->getMessage(), 0, 80)
            );
        }
    }
    echoTerminal(' ');

    //──── 6. Coherencia entre las dos ───────────────────────────────────────────────────
    echoTerminal('[6/6] Coherencia entre SessionToken y el usuario del framework...');
    $comprobar(
        SessionToken::isActiveSession(SessionToken::getJWTReceived()) === false && getLoggedFrameworkUser() === null,
        'sin sesión activa, no hay usuario: las dos coinciden',
        'Si divergen, hay un camino que devuelve usuario sin sesión válida, o al revés.'
    );
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$pasadas}/" . ($pasadas + $fallidas) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:SessionUser] Suite finalizada.', true, "\r\n", $fallidas === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $fallidas === 0,
        'message' => $fallidas === 0
            ? "Contrato actual de sesión y usuario congelado ({$pasadas} comprobaciones)."
            : "{$fallidas} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->register();
