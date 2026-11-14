<?php

//El tope de envíos del código de un uso (G2-a), por HTTP: con el límite, generate-otp ni genera ni envía y responde como a
//un usuario que no existe. El correo de prueba es zz-prueba-…@mailinator.com (ADR 0011), por si algo llegara a enviar.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/otp-send-limit', function ($args) {

    echoTerminal("\e[33m[TEST:OTPSendLimit] El tope de envíos del código de un uso, por la ruta generate-otp\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'banco: hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $ruta = (string) parse_url(UserSystemFeaturesController::routeName('generate-otp', [], true), \PHP_URL_PATH);
    $ruta = $prefijoBase !== '' && str_starts_with($ruta, $prefijoBase) ? substr($ruta, strlen($prefijoBase)) : $ruta;
    $pedir = function (string $usuario) use ($base, $ruta): array {
        $h = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($h, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/') . '?' . http_build_query(['username' => $usuario]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'],
        ]);
        $cuerpo = curl_exec($h);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE), 'body' => is_string($cuerpo) ? $cuerpo : ''];
    };
    //El cuerpo sin el nombre escrito, que la respuesta repite en los dos casos: lo que queda tiene que ser idéntico.
    $sinNombre = function (string $cuerpo, string $usuario): ?array {
        $datos = json_decode($cuerpo, true);
        if (!is_array($datos)) {
            return null;
        }
        array_walk_recursive($datos, function (&$valor) use ($usuario): void {
            if ($valor === $usuario) {
                $valor = '<nombre>';
            }
        });
        return $datos;
    };
    $tablaIntentos = LoginAttemptsModel::TABLE;
    //La huella de las filas del código de un uso del usuario: si cambia, se generó un código. No se imprime nada de ellas.
    $huellaCodigos = function (int $id) use ($database): string {
        $consulta = $database->prepare('SELECT * FROM `' . OTPSecretsUsersMapper::TABLE . '` WHERE `user` = ? ORDER BY id');
        $consulta->execute([$id]);
        return hash('sha256', (string) json_encode($consulta->fetchAll(\PDO::FETCH_ASSOC)));
    };
    $correosA = function (string $correo) use ($database): int {
        $consulta = $database->prepare('SELECT COUNT(*) FROM `' . MailLogMapper::TABLE . '` WHERE recipients LIKE ?');
        $consulta->execute(['%' . $correo . '%']);
        return (int) $consulta->fetchColumn();
    };

    $sufijo = bin2hex(random_bytes(3));
    $usuario = "zz-agente-otpenvio-{$sufijo}";
    $correo = "zz-prueba-otpenvio-{$sufijo}@mailinator.com";
    $inexistente = "zz-agente-otpnoexiste-{$sufijo}";
    $marca = (int) $database->query("SELECT COALESCE(MAX(id), 0) FROM `{$tablaIntentos}`")->fetchColumn();
    $id = 0;

    try {
        $u = new UsersModel();
        $u->username = $usuario;
        $u->email = $correo;
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Envio';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_GENERAL;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $id = (int) $u->id;

        //──── [l] Con el límite alcanzado ────────────────────────────────────────────────────
        echoTerminal('[l] Con el límite alcanzado (un envío con éxito hace un momento, anotado por su correo)');
        //Por el CORREO: la petición va por el nombre, así que esto prueba también que los dos comparten contador.
        OTPRateLimiter::record(OTPRateLimiter::VIA_GENERATE_OTP, null, $correo, true, '');
        $check(OTPRateLimiter::sendLimitReached([$usuario, $correo]), 'l0 el límite está alcanzado antes de pedir');
        $huellaAntes = $huellaCodigos($id);
        $correosAntes = $correosA($correo);
        $r = $pedir($usuario);
        $datos = json_decode($r['body'], true);
        $check($r['status'] === 200 && is_array($datos) && ($datos['success'] ?? null) === true && ($datos['message'] ?? null) === OTPRateLimiter::uniformOTPMessage(),
            'u7a responde como un envío: 200, success y el mensaje uniforme', "HTTP {$r['status']}");
        $check($huellaCodigos($id) === $huellaAntes, 'u7b DISCRIMINANTE: no genera el código (las filas del código de un uso no cambian)');
        $check($correosA($correo) === $correosAntes, 'u7c no envía (ningún correo nuevo a esa dirección en el registro de correos)', "antes {$correosAntes}, después " . $correosA($correo));
        $consulta = $database->prepare("SELECT success, message, extraData FROM `{$tablaIntentos}` WHERE id > ? AND usernameAttempt = ? ORDER BY id DESC LIMIT 1");
        $consulta->execute([$marca, $usuario]);
        $fila = $consulta->fetch(\PDO::FETCH_ASSOC);
        $extra = is_array($fila) && is_string($fila['extraData'] ?? null) ? json_decode($fila['extraData'], true) : null;
        $check(is_array($fila) && (int) $fila['success'] === LoginAttemptsModel::FAIL_ATTEMPT && $fila['message'] === __(UserSystemFeaturesController::LANG_GROUP, 'Límite de envíos del código alcanzado.') && is_array($extra) && ($extra[OTPRateLimiter::EXTRA_DATA_VIA] ?? null) === OTPRateLimiter::VIA_GENERATE_OTP,
            'u7d deja una fila de fallo de generate-otp con ese mensaje', is_array($fila) ? 'success ' . $fila['success'] . ', «' . $fila['message'] . '»' : 'sin fila');
        echoTerminal(' ');

        //──── [n] Indistinguible de un usuario que no existe ───────────────────────────────
        echoTerminal('[n] La misma respuesta que la de un usuario que no existe');
        $n = $pedir($inexistente);
        $limitado = $sinNombre($r['body'], $usuario);
        $noExiste = $sinNombre($n['body'], $inexistente);
        $check($n['status'] === $r['status'] && $limitado !== null && $limitado === $noExiste,
            'u8 DISCRIMINANTE: con el límite, el estado y el cuerpo son los de un usuario que no existe (quitado el nombre escrito, que los dos repiten)',
            "HTTP {$r['status']} y {$n['status']}; " . json_encode($limitado) . ' · ' . json_encode($noExiste));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $borrar = $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE id > ? AND usernameAttempt IN (?, ?, ?)");
        $borrar->execute([$marca, $usuario, $correo, $inexistente]);
        if ($id > 0) {
            $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
            foreach ($referencias as [$tabla, $columna]) {
                $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare('DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?')->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote('zz-agente-otpenvio-%'))->fetchColumn();
        $consulta = $database->prepare("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE id > ? AND usernameAttempt IN (?, ?, ?)");
        $consulta->execute([$marca, $usuario, $correo, $inexistente]);
        $intentos = (int) $consulta->fetchColumn();
        $check($quedan === 0 && $intentos === 0, 'z1 no quedan el usuario ni sus intentos', "usuarios {$quedan}, intentos {$intentos}");
    }

    return $balance();

})->setDescription('El tope de envíos del código de un uso (G2-a): con el límite alcanzado (contado por nombre o correo), generate-otp no genera ni envía, responde como un envío y anota un fallo; y su respuesta es la de un usuario que no existe. Por HTTP; crea un usuario zz- de correo zz-prueba-…@mailinator.com y lo retira con sus intentos.')->setEffects([CliActions::EFFECT_DATABASE])->register();
