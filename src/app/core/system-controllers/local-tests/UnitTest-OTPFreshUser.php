<?php

//Única suite que escribe: crea un usuario real y lo borra siempre en el finally. Ver T35.

use App\Model\UsersModel;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\UserDataPackage;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/otp-fresh-user';
$cliTaskDescription = 'Un usuario sin filas OTP no rompe ninguna ruta de autenticación';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:OTPFreshUser] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;

    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        $condition ? $passed++ : $failed++;
        echoTerminal('   ' . ($condition ? "\e[32m[PASÓ]\e[39m" : "\e[31m[FALLÓ]\e[39m") . " {$name}");
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };

    $database = (new BaseModel())->getDatabase();
    $username = 'zz_otp_fresh_' . bin2hex(random_bytes(5));
    $userID = null;

    try {
        //──── Alta directa en la tabla, como haría una importación ──────────────────────
        $statement = $database->prepare(
            'INSERT INTO ' . UsersModel::TABLE . ' (username, password, firstname, first_lastname, email, type, status)'
            . ' VALUES (?, ?, ?, ?, ?, ?, 1)'
        );
        $statement->execute([
            $username,
            password_hash('zz-' . bin2hex(random_bytes(6)), \PASSWORD_DEFAULT),
            'ZZ', 'Prueba', $username . '@example.invalid',
            UsersModel::TYPE_USER_ROOT,
        ]);
        $userID = (int) $database->lastInsertId();
        echoTerminal("   usuario de prueba creado: id={$userID}");
        echoTerminal(' ');

        //──── 1. Los lectores devuelven null y nadie revienta ───────────────────────────
        echoTerminal('[1/5] Lectores sobre un usuario sin filas OTP', true, "\r\n", '36');

        $check(
            OTPSecretsUsersMapper::getTOTPData($userID) === null,
            'getTOTPData() devuelve null, no crea nada'
        );
        $check(
            OTPSecretsUsersMapper::getOTPData($userID, OTPSecretsUsersMapper::METHOD_ONE_USE_CODE) === null,
            'getOTPData() devuelve null, no crea nada'
        );

        $rows = function () use ($database, $userID): int {
            $statement = $database->prepare('SELECT COUNT(*) FROM ' . OTPSecretsUsersMapper::TABLE . ' WHERE user = ?');
            $statement->execute([$userID]);
            return (int) $statement->fetchColumn();
        };
        $check($rows() === 0, 'leer NO ha creado ninguna fila', 'filas = ' . $rows());
        echoTerminal(' ');

        $profileRows = function () use ($database, $userID): int {
            $statement = $database->prepare('SELECT COUNT(*) FROM user_system_profile WHERE belongsTo = ?');
            $statement->execute([$userID]);
            return (int) $statement->fetchColumn();
        };

        //──── 2. UserDataPackage, que se construye en casi cada petición ────────────────
        echoTerminal('[2/5] UserDataPackage con TOTPData nulo', true, "\r\n", '36');

        $package = null;
        try {
            $package = new UserDataPackage($userID);
            $check(true, 'el constructor NO lanza');
        } catch (\Throwable $exception) {
            $check(false, 'el constructor NO lanza', $exception->getMessage());
        }
        $check($package !== null && $package->TOTPData === null, 'TOTPData es null, y eso es legal ahora');
        $check($rows() === 0, 'construir el paquete NO escribe FILAS OTP', 'filas = ' . $rows());

        //La gemela de D2: construir un UserDataPackage no puede crear filas de OTP.
        $check(
            $profileRows() === 0,
            'construir el paquete NO escribe FILAS DE PERFIL',
            'filas en user_system_profile = ' . $profileRows()
                . '. GEMELA DE D2: UserProfileMapper::getProfile() es get-or-create y se alcanza sin autenticar.'
        );
        echoTerminal(' ');

        //──── 3. La superficie de OTPHandler ────────────────────────────────────────────
        echoTerminal('[3/5] OTPHandler sobre un usuario sin 2FA', true, "\r\n", '36');

        foreach ([
            'checkValidityOTP' => fn () => OTPHandler::checkValidityOTP('no-es-su-clave', $username) === false,
            'isEnabled2FA' => fn () => OTPHandler::isEnabled2FA($userID) === false,
            'wasViewedCurrentUserQRData' => fn () => OTPHandler::wasViewedCurrentUserQRData($userID) === false,
        ] as $name => $callback) {
            try {
                $check($callback(), "{$name}() devuelve false sin lanzar");
            } catch (\Throwable $exception) {
                $check(false, "{$name}() devuelve false sin lanzar", $exception->getMessage());
            }
        }
        $check($rows() === 0, 'nada de lo anterior ha escrito', 'filas = ' . $rows());
        echoTerminal(' ');

        //──── 4. El código de un solo uso: setOTP SÍ crea ───────────────────────────────
        echoTerminal('[4/5] Código de un solo uso — el camino de ESCRITURA sí crea', true, "\r\n", '36');

        $code = 'ZZ' . random_int(100000, 999999);
        $check(
            OTPSecretsUsersMapper::setOTP($userID, $code, OTPSecretsUsersMapper::METHOD_ONE_USE_CODE) === true,
            'setOTP() crea la fila que faltaba y devuelve true',
            'Es lo que impide que un usuario importado se quede sin poder pedir su código.'
        );
        $check($rows() === 1, 'ahora hay exactamente UNA fila', 'filas = ' . $rows());
        $check(
            OTPHandler::checkValidityOTP($code, $username) === true,
            'el código recién puesto SÍ valida',
            'El camino completo funciona para un usuario recién importado.'
        );
        $check(
            OTPSecretsUsersMapper::getTOTPData($userID) === null,
            'y el TOTP sigue sin existir: setOTP no lo inventa'
        );
        echoTerminal(' ');

        //──── 5. Activar y desactivar el 2FA ────────────────────────────────────────────
        echoTerminal('[5/5] toggle2FA — el otro camino de escritura', true, "\r\n", '36');

        $check(
            OTPSecretsUsersMapper::toggle2FA($userID, true, 'zz-codigo', 'ZZ Prueba') === true,
            'toggle2FA(true) crea el TOTP y devuelve true'
        );

        //Preparar no activa: si esto falla, la cuenta queda pidiendo un código que nadie tiene.
        $totpPrepared = OTPSecretsUsersMapper::getTOTPData($userID);
        $secretOnScan = $totpPrepared !== null ? (string) $totpPrepared->secret : '';

        $check(
            OTPSecretsUsersMapper::isEnabled2FA($userID) === false,
            'PREPARAR NO ACTIVA: tras ver el QR, la cuenta NO pide código',
            'Es la ventana de bloqueo que había: abandonar el flujo dejaba al usuario fuera.'
        );
        $check(
            $secretOnScan !== '' && $totpPrepared !== null && (string) $totpPrepared->twoAuthFactorAlias === 'ZZ Prueba',
            'pero el secreto y el alias SÍ quedan guardados',
            'La tabla tiene columnas separadas: preparar puede guardarlo todo sin armar la cuenta.'
        );

        $check(
            OTPSecretsUsersMapper::confirm2FA($userID) === true,
            'confirm2FA() es lo que activa'
        );
        $check(
            OTPSecretsUsersMapper::isEnabled2FA($userID) === true,
            'y AHORA sí pide código'
        );
        $totpConfirmed = OTPSecretsUsersMapper::getTOTPData($userID);
        $check(
            $totpConfirmed !== null && (string) $totpConfirmed->secret === $secretOnScan,
            'el secreto NO se regenera al confirmar: es el que se escaneó',
            'La reversión antigua lo cambiaba, y dejaba el autenticador apuntando a un secreto muerto.'
        );
        $totp = OTPSecretsUsersMapper::getTOTPData($userID);
        $check($totp !== null, 'ahora getTOTPData() sí devuelve el registro');
        $check(
            $totp !== null && is_string($totp->secret) && $totp->secret !== '',
            'con un secreto generado'
        );
        $check(
            OTPSecretsUsersMapper::toggle2FA($userID, false, 'zz-codigo') === true,
            'toggle2FA(false) también devuelve true'
        );

        //Y la ruta que daba 500: el emisor cae en el nombre del sitio si no hay alias.
        $totp = OTPSecretsUsersMapper::getTOTPData($userID);
        if ($totp !== null) {
            $totp->twoAuthFactorAlias = null;
            $totp->update();
        }
        try {
            OTPHandler::getCurrentUserQRData();
            $check(true, 'getCurrentUserQRData() no lanza con el alias vacío');
        } catch (\Throwable $exception) {
            $check(false, 'getCurrentUserQRData() no lanza con el alias vacío', $exception->getMessage());
        }

    } finally {
        //──── Limpieza: pase lo que pase, el usuario de prueba se va ────────────────────
        if ($userID !== null) {
            //El perfil primero: tiene una clave ajena contra el usuario.
            $database->prepare('DELETE FROM user_system_profile WHERE belongsTo = ?')->execute([$userID]);
            $database->prepare('DELETE FROM ' . OTPSecretsUsersMapper::TABLE . ' WHERE user = ?')->execute([$userID]);
            $database->prepare('DELETE FROM ' . UsersModel::TABLE . ' WHERE id = ?')->execute([$userID]);
            echoTerminal(' ');
            echoTerminal("   usuario de prueba borrado: id={$userID}");
        }
    }

    echoTerminal(' ');
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . ' PASADAS ');
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:OTPFreshUser] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Un usuario sin filas OTP no rompe nada ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->register();
