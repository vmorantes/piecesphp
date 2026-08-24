<?php

//Fija que ningún buscador de OTP escriba: era el defecto D2. Ver T33.

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
     * Código fuente de un método, sin comentarios. Se lee con `file()`: el repositorio
     * mezcla LF y CRLF, y partir por `\r\n` a mano desplaza los índices.
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

        //Se quitan los comentarios: buscar por texto plano confunde una mención con una llamada.
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

    //El grep de arriba no ve una escritura delegada una llamada más abajo, que era la forma de D2.
    echoTerminal('[1bis] Y llamándolos de verdad, que es lo que el texto no puede ver...');

    $db = (new \PiecesPHP\Core\BaseModel())->getDatabase();
    if ($db === null) {
        $check(false, 'hay conexión para la comprobación de comportamiento');
    } else {
        $usuario = 'zz_sep_' . bin2hex(random_bytes(4));
        $insert = $db->prepare('INSERT INTO pcsphp_users (username, password, firstname, first_lastname, email, type, status) VALUES (?,?,?,?,?,1,1)');
        $insert->execute([$usuario, password_hash('x', \PASSWORD_DEFAULT), 'ZZ', 'Sep', $usuario . '@example.invalid']);
        $usuarioID = (int) $db->lastInsertId();

        $contar = static function () use ($db, $usuarioID): int {
            $statement = $db->prepare('SELECT COUNT(*) FROM pcsphp_users_otp_secrets WHERE user = ?');
            $statement->execute([$usuarioID]);
            return (int) $statement->fetchColumn();
        };

        try {
            $antes = $contar();
            OTPSecretsUsersMapper::getTOTPData($usuarioID);
            OTPSecretsUsersMapper::getOTPData($usuarioID, OTPSecretsUsersMapper::METHOD_ONE_USE_CODE);
            $despues = $contar();
            $check(
                $antes === 0 && $despues === 0,
                'llamar a los dos buscadores sobre un usuario SIN filas no crea ninguna',
                "antes {$antes}, después {$despues} — un buscador que crea es el defecto D2"
            );
        } finally {
            $db->prepare('DELETE FROM pcsphp_users_otp_secrets WHERE user = ?')->execute([$usuarioID]);
            $db->prepare('DELETE FROM user_system_profile WHERE belongsTo = ?')->execute([$usuarioID]);
            $db->prepare('DELETE FROM pcsphp_users WHERE id = ?')->execute([$usuarioID]);
        }
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

    /** Solo lectura: descubre un usuario real, cuenta, falla el login y vuelve a contar. */
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

        try {
            OTPHandler::checkValidityOTP('contraseña-que-no-es-la-suya-' . bin2hex(random_bytes(4)), (string) $user->username);
        } catch (\Throwable $e) {
            echoTerminal('      - checkValidityOTP lanzó: ' . mb_substr($e->getMessage(), 0, 60));
        }

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
