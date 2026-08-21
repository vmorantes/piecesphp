<?php

/**
 * UnitTest-OTPWriteSeparation.php
 *
 * COMPROBAR NO DEBE ESCRIBIR, Y REGISTRAR RUTAS TAMPOCO.
 *
 * Esta suite fija dos reglas que el código violaba y que son la raíz del hallazgo D2:
 *
 * 1. Los buscadores de `OTPSecretsUsersMapper` son de LECTURA. `getOTPData()` y
 *    `getTOTPData()` eran get-or-create: si no encontraban registro, INSERTABAN uno con
 *    un secreto TOTP recién generado. Como `UserDataPackage::__construct()` llama al
 *    segundo sin condiciones, **construir un paquete de usuario escribía en base de
 *    datos** — y lo alcanzan sin autenticar `checkValidityOTP`, `checkValidityTOTP`,
 *    `toExpireOTP` y `generateOTP`, todos con `new UserDataPackage($id)` antes de
 *    verificar credencial alguna.
 *
 * 2. El registro de rutas es PURO. `UserSystemFeaturesRoutes::routes()` llamaba a
 *    `createOTPAlternativesRecords()`, una migración de datos que recorre la tabla de
 *    usuarios con dos `GROUP_CONCAT` + `LEFT JOIN`. En cada petición. Para siempre.
 *
 * POR QUÉ LAS COMPROBACIONES 1 Y 2 SON ESTRUCTURALES Y NO DE COMPORTAMIENTO
 * La versión de comportamiento —«un usuario sin filas provoca un INSERT»— exige crear un
 * usuario sin filas, y eso es escribir datos de prueba en una base que no es nuestra.
 * Peor: hoy no fallaría, porque el relleno masivo del punto 2 ya creó las filas de todos
 * los usuarios y tapa el defecto del punto 1. **Esa es exactamente la trampa de orden**:
 * si se quitara el relleno sin arreglar los buscadores, el get-or-create volvería a
 * escribir en la ruta no autenticada. Por eso las dos reglas se comprueban por separado
 * y ninguna depende de que la otra siga rota.
 *
 * La comprobación 3 sí es de comportamiento, y es de SOLO LECTURA.
 */

use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\UserDataPackage;
use PiecesPHP\UserSystem\UserSystemFeaturesRoutes;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/otp-write-separation';
$cliTaskDescription = 'Comprobar credenciales y registrar rutas no deben escribir en base de datos';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:OTPWriteSeparation] Iniciando suite...', true, "\r\n", '33');
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

    /**
     * Devuelve el código fuente de un método. Se lee por rango de líneas de la reflexión
     * y con `file()`, que respeta la numeración de PHP tanto con LF como con CRLF —el
     * repositorio mezcla los dos y partir por `\r\n` a mano desplaza los índices.
     */
    $fuenteDelMetodo = function (string $clase, string $metodo): string {
        try {
            $r = new \ReflectionMethod($clase, $metodo);
        } catch (\ReflectionException $e) {
            return '';
        }
        $archivo = $r->getFileName();
        if (!is_string($archivo) || !is_file($archivo)) {
            return '';
        }
        $lineas = file($archivo);
        if ($lineas === false) {
            return '';
        }
        $desde = $r->getStartLine() - 1;
        $cuantas = $r->getEndLine() - $r->getStartLine() + 1;
        return implode('', array_slice($lineas, $desde, $cuantas));
    };

    /** Busca llamadas de escritura del ORM en un fragmento de código. */
    $escribe = function (string $fuente): array {
        $encontradas = [];
        foreach (['->save(', '->update(', '->delete('] as $llamada) {
            if (mb_strpos($fuente, $llamada) !== false) {
                $encontradas[] = rtrim($llamada, '(') . '()';
            }
        }
        return $encontradas;
    };

    //──── 1. Los buscadores del mapper son de lectura ───────────────────────────────────
    echoTerminal('[1/3] getOTPData() y getTOTPData() no deben escribir...');

    foreach (['getOTPData', 'getTOTPData'] as $metodo) {
        $fuente = $fuenteDelMetodo(OTPSecretsUsersMapper::class, $metodo);
        if ($fuente === '') {
            $comprobar(false, "{$metodo}() existe y su fuente es legible");
            continue;
        }
        $escrituras = $escribe($fuente);
        $comprobar(
            $escrituras === [],
            "{$metodo}() no contiene ninguna escritura del ORM",
            $escrituras === []
                ? 'Es un buscador puro: devuelve el registro o null.'
                : 'ESCRIBE con ' . implode(', ', $escrituras) . '. Lo alcanza una ruta NO autenticada a través de UserDataPackage.'
        );
    }
    echoTerminal(' ');

    //──── 2. El registro de rutas es puro ───────────────────────────────────────────────
    echoTerminal('[2/3] Registrar rutas no debe disparar una migración de datos...');

    $fuenteRutas = $fuenteDelMetodo(UserSystemFeaturesRoutes::class, 'routes');
    if ($fuenteRutas === '') {
        $comprobar(false, 'UserSystemFeaturesRoutes::routes() existe y su fuente es legible');
    } else {
        $comprobar(
            mb_strpos($fuenteRutas, 'createOTPAlternativesRecords') === false,
            'routes() no llama a createOTPAlternativesRecords()',
            'Ese método recorre la tabla de usuarios con dos GROUP_CONCAT + LEFT JOIN. En routes() eso corre EN CADA PETICIÓN. Su sitio es una tarea de terminal.'
        );
        $comprobar(
            $escribe($fuenteRutas) === [],
            'routes() no contiene escrituras del ORM',
            'El registro de rutas debe ser puro: describe el mapa, no lo modifica.'
        );
    }
    echoTerminal(' ');

    //──── 3. Un intento de login fallido no cambia el conteo de filas ───────────────────
    echoTerminal('[3/3] Un login fallido con usuario existente no debe insertar filas...');

    /**
     * Solo lectura: se descubre un usuario real en ejecución, se cuenta, se falla el
     * login a propósito y se vuelve a contar. No se crea ni se borra nada.
     */
    $contar = function (): ?int {
        try {
            $modelo = OTPSecretsUsersMapper::model();
                $modelo->select('COUNT(*) AS total');
            $modelo->execute();
            $filas = $modelo->result();
            return isset($filas[0]) ? (int) $filas[0]->total : null;
        } catch (\Throwable $e) {
            return null;
        }
    };

    $usuario = null;
    try {
        /** Mismo patrón de descubrimiento que UnitTest-MapperFinders: sin escribir nada. */
        $modeloUsuarios = \App\Model\UsersModel::model();
        $modeloUsuarios->select(['id', 'username']);
        $modeloUsuarios->execute();
        $encontrados = $modeloUsuarios->result();
        $usuario = is_array($encontrados) && count($encontrados) > 0 ? $encontrados[0] : null;
    } catch (\Throwable $e) {
        $usuario = null;
    }

    $antes = $contar();

    if ($usuario === null || $antes === null) {
        echoTerminal("   \e[33m[OMITIDA]\e[39m sin base de datos o sin usuarios: nada que contar.");
    } else {
        $comprobar(
            true,
            "usuario de prueba descubierto en ejecución (id {$usuario->id})",
            "Filas en " . OTPSecretsUsersMapper::TABLE . " antes: {$antes}"
        );

        /** Contraseña deliberadamente incorrecta: el camino no autenticado. */
        try {
            OTPHandler::checkValidityOTP('contraseña-que-no-es-la-suya-' . bin2hex(random_bytes(4)), (string) $usuario->username);
        } catch (\Throwable $e) {
            echoTerminal('      - checkValidityOTP lanzó: ' . mb_substr($e->getMessage(), 0, 60));
        }

        /** Y el constructor, que es donde estaba de verdad la escritura. */
        try {
            new UserDataPackage((int) $usuario->id);
        } catch (\Throwable $e) {
            echoTerminal('      - UserDataPackage lanzó: ' . mb_substr($e->getMessage(), 0, 60));
        }

        $despues = $contar();
        $comprobar(
            $despues === $antes,
            'el conteo de filas no cambia tras un intento fallido',
            "antes={$antes} despues=" . var_export($despues, true)
        );
    }
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$pasadas}/" . ($pasadas + $fallidas) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:OTPWriteSeparation] Suite finalizada.', true, "\r\n", $fallidas === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $fallidas === 0,
        'message' => $fallidas === 0
            ? "Comprobar no escribe y registrar rutas es puro ({$pasadas} comprobaciones)."
            : "{$fallidas} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->register();
