<?php

//Escribe en la base local: crea filas zz en login_attempts por el mapper y en el finally las borra.
//Ver la ruptura 30 del CHANGELOG y la 5.0.0 de piecesphp/database.

use App\Model\LoginAttemptsModel;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;

CliActions::make('unit-tests:core/text-round-trip', function ($args) {

    echoTerminal("\e[33m[TEST:TextRoundTrip] El texto se guarda y se lee tal cual por el ORM, y el límite del OTP cuenta los nombres con comilla\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    $marca = 'zz-texto-' . bin2hex(random_bytes(4));
    $valores = [
        1 => 'C:\ruta\archivo.txt',
        2 => "O'Brien",
        3 => 'comillas "dobles"',
        4 => 'barra final\\',
        5 => "barra y apóstrofo \\'",
        6 => 'barra doble \\\\',
    ];

    $filaCruda = function (string $patron) use ($database): ?array {
        $statement = $database->prepare('SELECT id, username_attempt, message FROM ' . LoginAttemptsModel::TABLE . ' WHERE username_attempt LIKE ?');
        $statement->execute([$patron]);
        $fila = $statement->fetch(\PDO::FETCH_ASSOC);
        return is_array($fila) ? $fila : null;
    };
    $ver = fn($valor): string => var_export($valor, true);

    try {
        //─── 1/2 · Ida y vuelta ────────────────────────────────────────────────────────────
        echoTerminal('[1/2] Seis valores se guardan, se leen y se vuelven a guardar sin cambiar un carácter');
        foreach ($valores as $i => $valor) {
            echoTerminal("   v{$i} = {$valor}  (strlen " . strlen($valor) . ')');
            try {
                LoginAttemptsModel::addLogin(null, $marca . '-' . $i . '-' . $valor, true, $valor);
                $fila = $filaCruda($marca . '-' . $i . '-%');
                if ($fila === null) {
                    foreach (['a', 'b', 'c', 'd'] as $letra) {
                        $check(false, "r{$i}.{$letra}", 'no se encontró la fila');
                    }
                    continue;
                }
                $id = (int) $fila['id'];
                $check($fila['username_attempt'] === $marca . '-' . $i . '-' . $valor, "r{$i}.a el username_attempt crudo es el enviado", $ver($fila['username_attempt']));
                $check($fila['message'] === $valor, "r{$i}.b el message crudo es el enviado", $ver($fila['message']));
                $leido = (new LoginAttemptsModel($id))->message;
                $check($leido === $valor, "r{$i}.c el mapper lee el message enviado", $ver($leido));
                $mapper = new LoginAttemptsModel($id);
                $mapper->update();
                $tras = $filaCruda($marca . '-' . $i . '-%');
                $check($tras !== null && $tras['message'] === $valor, "r{$i}.d tras update() el message crudo sigue siendo el enviado", $ver($tras['message'] ?? null));
            } catch (\Throwable $exception) {
                $check(false, "r{$i}", get_class($exception) . ': ' . $exception->getMessage());
            }
        }
        echoTerminal(' ');

        //─── 2/2 · 4e ──────────────────────────────────────────────────────────────────────
        echoTerminal("[2/2] El límite del OTP cuenta los fallos de un nombre con comilla");
        $nombre = $marca . "-o'brien";
        $database->prepare('DELETE FROM ' . LoginAttemptsModel::TABLE . ' WHERE username_attempt = ?')->execute([$nombre]);
        try {
            for ($intento = 0; $intento < 5; $intento++) {
                OTPRateLimiter::record(OTPRateLimiter::VIA_CHECK_TOTP, null, $nombre, false, 'zz');
            }
            $segundos = OTPRateLimiter::secondsToUnlock($nombre, '203.0.113.' . random_int(1, 254));
            $check($segundos > 0, 'e1. cinco fallos de un nombre con comilla bloquean', "segundos {$segundos}");
        } catch (\Throwable $exception) {
            $check(false, 'e1. cinco fallos de un nombre con comilla bloquean', get_class($exception) . ': ' . $exception->getMessage());
        }

    } finally {
        $database->prepare('DELETE FROM ' . LoginAttemptsModel::TABLE . ' WHERE username_attempt LIKE ?')->execute([$marca . '%']);
    }

    $statement = $database->prepare('SELECT COUNT(*) FROM ' . LoginAttemptsModel::TABLE . ' WHERE username_attempt LIKE ?');
    $statement->execute([$marca . '%']);
    echoTerminal(' ');
    echoTerminal('   restos tras la limpieza: ' . (int) $statement->fetchColumn());

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El texto se guarda y se lee tal cual por el ORM, y el límite del OTP cuenta los nombres con comilla.')->setEffects([CliActions::EFFECT_DATABASE])->register();
