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

    /**
     * Devuelve el código fuente de un método. Se lee por rango de líneas de la reflexión
     * y con `file()`, que respeta la numeración de PHP tanto con LF como con CRLF —el
     * repositorio mezcla los dos y partir por `\r\n` a mano desplaza los índices.
     */
    $methodSource = function (string $className, string $method): string {
        try {
            $r = new \ReflectionMethod($className, $method);
        } catch (\ReflectionException $e) {
            return '';
        }
        $file = $r->getFileName();
        if (!is_string($file) || !is_file($file)) {
            return '';
        }
        $lines = file($file);
        if ($lines === false) {
            return '';
        }
        $from = $r->getStartLine() - 1;
        $howMany = $r->getEndLine() - $r->getStartLine() + 1;
        $source = implode('', array_slice($lines, $from, $howMany));

        /**
         * SE QUITAN LOS COMENTARIOS. Buscar por texto plano no distingue una llamada de
         * una mención, y este arreglo deja precisamente comentarios que NOMBRAN lo que se
         * quitó, para que quien lea el módulo sepa por qué ya no está. Un test que
         * confunde documentar con hacer obliga a no documentar, que es peor que el test.
         * Es la misma lección que `verify-integrity`: se tokeniza, no se busca a ojo.
         */
        $tokens = @token_get_all('<?php ' . $source);
        $clean = '';
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                    continue;
                }
                $clean .= $token[1];
            } else {
                $clean .= $token;
            }
        }
        return $clean;
    };

    /** Busca llamadas de escritura del ORM en un fragmento de código. */
    $writeCalls = function (string $source): array {
        $found = [];
        foreach (['->save(', '->update(', '->delete('] as $call) {
            if (mb_strpos($source, $call) !== false) {
                $found[] = rtrim($call, '(') . '()';
            }
        }
        return $found;
    };

    //──── 1. Los buscadores del mapper son de lectura ───────────────────────────────────
    echoTerminal('[1/3] getOTPData() y getTOTPData() no deben escribir...');

    foreach (['getOTPData', 'getTOTPData'] as $method) {
        $source = $methodSource(OTPSecretsUsersMapper::class, $method);
        if ($source === '') {
            $check(false, "{$method}() existe y su fuente es legible");
            continue;
        }
        $writes = $writeCalls($source);
        $check(
            $writes === [],
            "{$method}() no contiene ninguna escritura del ORM",
            $writes === []
                ? 'Es un buscador puro: devuelve el registro o null.'
                : 'ESCRIBE con ' . implode(', ', $writes) . '. Lo alcanza una ruta NO autenticada a través de UserDataPackage.'
        );
    }
    echoTerminal(' ');

    //──── 2. El registro de rutas es puro ───────────────────────────────────────────────
    echoTerminal('[2/3] Registrar rutas no debe disparar una migración de datos...');

    $routesSource = $methodSource(UserSystemFeaturesRoutes::class, 'routes');
    if ($routesSource === '') {
        $check(false, 'UserSystemFeaturesRoutes::routes() existe y su fuente es legible');
    } else {
        $check(
            mb_strpos($routesSource, 'createOTPAlternativesRecords') === false,
            'routes() no llama a createOTPAlternativesRecords()',
            'Ese método recorre la tabla de usuarios con dos GROUP_CONCAT + LEFT JOIN. En routes() eso corre EN CADA PETICIÓN. Su sitio es una tarea de terminal.'
        );
        $check(
            $writeCalls($routesSource) === [],
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
    $countRows = function (): ?int {
        try {
            $model = OTPSecretsUsersMapper::model();
                $model->select('COUNT(*) AS total');
            $model->execute();
            $rows = $model->result();
            return isset($rows[0]) ? (int) $rows[0]->total : null;
        } catch (\Throwable $e) {
            return null;
        }
    };

    $user = null;
    try {
        /** Mismo patrón de descubrimiento que UnitTest-MapperFinders: sin escribir nada. */
        $usersModel = \App\Model\UsersModel::model();
        $usersModel->select(['id', 'username']);
        $usersModel->execute();
        $foundUsers = $usersModel->result();
        $user = is_array($foundUsers) && count($foundUsers) > 0 ? $foundUsers[0] : null;
    } catch (\Throwable $e) {
        $user = null;
    }

    $before = $countRows();

    if ($user === null || $before === null) {
        echoTerminal("   \e[33m[OMITIDA]\e[39m sin base de datos o sin usuarios: nada que contar.");
    } else {
        $check(
            true,
            "usuario de prueba descubierto en ejecución (id {$user->id})",
            "Filas en " . OTPSecretsUsersMapper::TABLE . " antes: {$before}"
        );

        /** Contraseña deliberadamente incorrecta: el camino no autenticado. */
        try {
            OTPHandler::checkValidityOTP('contraseña-que-no-es-la-suya-' . bin2hex(random_bytes(4)), (string) $user->username);
        } catch (\Throwable $e) {
            echoTerminal('      - checkValidityOTP lanzó: ' . mb_substr($e->getMessage(), 0, 60));
        }

        /** Y el constructor, que es donde estaba de verdad la escritura. */
        try {
            new UserDataPackage((int) $user->id);
        } catch (\Throwable $e) {
            echoTerminal('      - UserDataPackage lanzó: ' . mb_substr($e->getMessage(), 0, 60));
        }

        $after = $countRows();
        $check(
            $after === $before,
            'el conteo de filas no cambia tras un intento fallido',
            "antes={$before} despues=" . var_export($after, true)
        );
    }
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:OTPWriteSeparation] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Comprobar no escribe y registrar rutas es puro ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->register();
