<?php

//Crea un usuario zz-agente-respfirst-… y lo retira con lo suyo y sus correos de Mailpit. La entrada de error de [c]
//lleva la marca de la corrida y la poda quien la abrió (ADR 0063).
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\Authentication\RespondFirst;
use PiecesPHP\UserSystem\Controllers\RecoveryPasswordController;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/respond-first', function ($args) {

    echoTerminal("\e[33m[TEST:RespondFirst] Las pantallas que envían un código responden antes de enviarlo\e[39m");
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

    //──── [a] La decisión ────────────────────────────────────────────────────────────────────
    echoTerminal('[a] ¿Se puede cerrar la conexión antes del trabajo?');
    $check(RespondFirst::canFinishEarly(true, 'fpm-fcgi'), 'a1 con fastcgi_finish_request y FPM → sí');
    $check(!RespondFirst::canFinishEarly(false, 'fpm-fcgi'), 'a2 sin la función (mod_php y otros) → no');
    $check(!RespondFirst::canFinishEarly(true, 'cli'), 'a3 en la terminal → no, aunque la función exista');
    echoTerminal(' ');

    //──── [b] Sin poder cerrar: el trabajo antes y la respuesta de withJson ──────────────────
    echoTerminal('[b] Sin poder cerrar (esta terminal): el trabajo corre ANTES de devolver, y la respuesta es la de withJson()');
    $cuerpo = ['send_mail' => true, 'error' => 'NO_ERROR', 'message' => 'zz-respond-first b'];
    $corrio = false;
    $respuesta = RespondFirst::json(new ResponseRoute(), $cuerpo, function () use (&$corrio): void {
        $corrio = true;
    });
    $check($corrio, 'b1 al volver de json() el trabajo ya corrió');
    $esperada = (new ResponseRoute())->withJson($cuerpo);
    $check(
        $respuesta->getStatusCode() === 200 && (string) $respuesta->getBody() === (string) $esperada->getBody() && $respuesta->getHeaderLine('Content-Type') === 'application/json',
        'b2 estado 200, el cuerpo de withJson($body) y Content-Type application/json',
        $respuesta->getStatusCode() . ' ' . (string) $respuesta->getBody()
    );
    echoTerminal(' ');

    //──── [c] Una excepción en el trabajo no escapa y queda anotada ──────────────────────────
    echoTerminal('[c] Una excepción en el trabajo no escapa y queda en el registro de errores');
    $mensajeC = 'zz-respond-first c ' . bin2hex(random_bytes(4));
    $escapo = null;
    try {
        $terminoBien = RespondFirst::runSafely(function () use ($mensajeC): void {
            throw new \RuntimeException($mensajeC);
        });
    } catch (\Throwable $e) {
        $terminoBien = null;
        $escapo = get_class($e);
    }
    $check($escapo === null && $terminoBien === false, 'c1 runSafely() no deja escapar la excepción y dice que no terminó bien', (string) $escapo);
    $logDir = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\');
    $entradas = is_file("{$logDir}/" . GenericHandler::FILE_ENTRIES) ? (string) file_get_contents("{$logDir}/" . GenericHandler::FILE_ENTRIES) : '';
    $check(str_contains($entradas, json_encode($mensajeC, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES) ?: $mensajeC), 'c2 la excepción está en ' . GenericHandler::FILE_ENTRIES);
    $corrioDespues = false;
    $check(RespondFirst::runSafely(function () use (&$corrioDespues): void {
        $corrioDespues = true;
    }) && $corrioDespues, 'c3 sin excepción corre el trabajo y dice que terminó bien');
    echoTerminal(' ');

    //──── [d] Por HTTP, las tres pantallas ───────────────────────────────────────────────────
    echoTerminal('[d] Por HTTP, con un usuario que existe: la respuesta de siempre y, después, el código guardado y su correo');
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
    $ruta = function (string $url) use ($prefijoBase): string {
        $ruta = (string) parse_url($url, \PHP_URL_PATH);
        return ltrim($prefijoBase !== '' && str_starts_with($ruta, $prefijoBase) ? substr($ruta, strlen($prefijoBase)) : $ruta, '/');
    };
    $pedir = function (string $metodo, string $rutaRelativa, array $datos) use ($base): array {
        $h = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . $rutaRelativa . ($metodo === 'GET' ? '?' . http_build_query($datos) : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
        ];
        if ($metodo === 'POST') {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($datos);
        }
        \Terminal\TestRun::curlSetoptArray($h, $opciones);
        $cuerpo = curl_exec($h);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE), 'body' => is_string($cuerpo) ? $cuerpo : ''];
    };
    //La bandeja de Mailpit es DE LA MÁQUINA: se cuenta y se retira solo lo dirigido a la dirección de esta pasada.
    $api = 'http://127.0.0.1:8025/api/v1';
    $pedirMailpit = function (string $metodo, string $rutaApi, ?array $cuerpoApi = null) use ($api): ?array {
        $opciones = ['method' => $metodo, 'timeout' => 5, 'ignore_errors' => true];
        if ($cuerpoApi !== null) {
            $opciones['header'] = 'Content-Type: application/json';
            $opciones['content'] = json_encode($cuerpoApi, JSON_THROW_ON_ERROR);
        }
        $respuestaApi = @file_get_contents($api . $rutaApi, false, stream_context_create(['http' => $opciones]));
        $datos = is_string($respuestaApi) ? json_decode($respuestaApi, true) : null;
        return is_array($datos) ? $datos : null;
    };
    $idsA = function (string $direccion) use ($pedirMailpit): array {
        $ids = [];
        foreach ((array) (($pedirMailpit('GET', '/messages?limit=200') ?? [])['messages'] ?? []) as $mensaje) {
            foreach ((array) (is_array($mensaje) ? ($mensaje['To'] ?? []) : []) as $destino) {
                if (is_array($destino) && strcasecmp((string) ($destino['Address'] ?? ''), $direccion) === 0) {
                    $ids[] = (string) ($mensaje['ID'] ?? '');
                    continue 2;
                }
            }
        }
        return array_values(array_filter($ids));
    };
    //Espera, con tope, a que se cumpla la condición: lo de después de la respuesta llega unos milisegundos más tarde.
    $esperar = function (callable $condicion, float $topeSegundos = 10.0): bool {
        $limite = microtime(true) + $topeSegundos;
        do {
            if ($condicion()) {
                return true;
            }
            usleep(200000);
        } while (microtime(true) < $limite);
        return (bool) $condicion();
    };
    $contar = function (string $sql, array $valores) use ($database): int {
        $consulta = $database->prepare($sql);
        $consulta->execute($valores);
        return (int) $consulta->fetchColumn();
    };

    $sufijo = bin2hex(random_bytes(3));
    $usuario = "zz-agente-respfirst-{$sufijo}";
    $correo = "zz-agente-respfirst-{$sufijo}@example.invalid";
    $marca = (int) $database->query('SELECT COALESCE(MAX(id), 0) FROM `' . LoginAttemptsModel::TABLE . '`')->fetchColumn();
    $id = 0;

    try {
        $check(OTPRateLimiter::config()['uniformResponse'], 'd0 banco: generate-otp está en modo uniforme (el que responde primero)');
        $u = new UsersModel();
        $u->username = $usuario;
        $u->email = $correo;
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Responde';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_GENERAL;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $id = (int) $u->id;

        $pantallas = [
            'A' => [
                'nombre' => 'generate-otp',
                'metodo' => 'GET',
                'ruta' => $ruta(UserSystemFeaturesController::routeName('generate-otp', [], true)),
                'datos' => ['username' => $usuario],
                'esperado' => fn(array $d): bool => ($d['success'] ?? null) === true && ($d['message'] ?? null) === OTPRateLimiter::uniformOTPMessage() && ($d['values']['user'] ?? null) === $usuario,
                'guardado' => fn(): int => $contar('SELECT COUNT(*) FROM `' . OTPSecretsUsersMapper::TABLE . '` WHERE `user` = ? AND oneUseCode IS NOT NULL AND oneUseCode <> ?', [$id, '']),
            ],
            'B' => [
                'nombre' => 'recovery-code',
                'metodo' => 'POST',
                'ruta' => $ruta(RecoveryPasswordController::routeName('recovery-password-request-code', [], true)),
                'datos' => ['username' => $usuario],
                'esperado' => fn(array $d): bool => $d === ['send_mail' => true, 'error' => 'NO_ERROR', 'message' => OTPRateLimiter::uniformOTPMessage()],
                'guardado' => fn(): int => $contar('SELECT COUNT(*) FROM `pcsphp_recovery_password` WHERE email = ?', [$correo]),
            ],
            'C' => [
                'nombre' => 'user-forget-code',
                'metodo' => 'POST',
                'ruta' => $ruta(UserProblemsController::routeName('forget-request-code', [], true)),
                'datos' => ['username' => $usuario, 'type' => UserProblemsController::TYPE_USER_FORGET],
                'esperado' => fn(array $d): bool => $d === ['send_mail' => true, 'error' => 'NO_ERROR', 'message' => __(UserProblemsController::LANG_GROUP, 'Se ha enviado un mensaje al correo proporcionado.')],
                'guardado' => fn(): int => $contar('SELECT COUNT(*) FROM `pcsphp_user_problems` WHERE email = ?', [$correo]),
            ],
        ];

        foreach ($pantallas as $letra => $p) {
            $correosAntes = count($idsA($correo));
            $guardadoAntes = ($p['guardado'])();
            $r = $pedir($p['metodo'], $p['ruta'], $p['datos']);
            //En cuanto llega la respuesta, antes de nada más: síncrono, el correo ya estaría en la bandeja.
            $correosAlResponder = count($idsA($correo));
            $datos = json_decode($r['body'], true);
            $check($correosAlResponder === $correosAntes, "d{$letra}0 DISCRIMINANTE {$p['nombre']}: al llegar la respuesta su correo aún no está (se envía después de responder)", "antes {$correosAntes}, al responder {$correosAlResponder}");
            $check($r['status'] === 200 && is_array($datos) && ($p['esperado'])($datos), "d{$letra}1 {$p['nombre']}: 200 y la respuesta de siempre", "HTTP {$r['status']} " . mb_substr($r['body'], 0, 200));
            $guardo = $esperar(fn(): bool => ($p['guardado'])() > $guardadoAntes);
            $check($guardo, "d{$letra}2 {$p['nombre']}: después de la respuesta se guarda el código (la base sirve en el cierre)", "antes {$guardadoAntes}, ahora " . ($p['guardado'])());
            $llego = $esperar(fn(): bool => count($idsA($correo)) > $correosAntes);
            $check($llego, "d{$letra}3 {$p['nombre']}: el correo LLEGA al sumidero local", "antes {$correosAntes}, ahora " . count($idsA($correo)));
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        //Lo de después de la respuesta puede seguir escribiendo un instante: se espera a que el correo de la última se vea.
        usleep(500000);
        $propios = $idsA($correo);
        if ($propios !== []) {
            $pedirMailpit('DELETE', '/messages', ['IDs' => $propios]);
        }
        $database->prepare('DELETE FROM `' . LoginAttemptsModel::TABLE . '` WHERE id > ? AND usernameAttempt IN (?, ?)')->execute([$marca, $usuario, $correo]);
        foreach (['pcsphp_recovery_password', 'pcsphp_user_problems', 'pcsphp_tickets_log'] as $tabla) {
            $database->prepare("DELETE FROM `{$tabla}` WHERE email = ?")->execute([$correo]);
        }
        $database->prepare('DELETE FROM `' . MailLogMapper::TABLE . '` WHERE recipients LIKE ?')->execute(['%' . $correo . '%']);
        if ($id > 0) {
            $database->prepare('DELETE FROM `' . \EventsLog\Mappers\LogsMapper::TABLE . '` WHERE (referenceSource = ? AND referenceValue = ?) OR createdBy = ?')->execute([UsersModel::TABLE, (string) $id, $id]);
            $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
            foreach ($referencias as [$tabla, $columna]) {
                $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare('DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?')->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = $contar('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username = ?', [$usuario]);
        $intentos = $contar('SELECT COUNT(*) FROM `' . LoginAttemptsModel::TABLE . '` WHERE id > ? AND usernameAttempt IN (?, ?)', [$marca, $usuario, $correo]);
        $codigos = $contar('SELECT (SELECT COUNT(*) FROM pcsphp_recovery_password WHERE email = ?) + (SELECT COUNT(*) FROM pcsphp_user_problems WHERE email = ?) + (SELECT COUNT(*) FROM pcsphp_tickets_log WHERE email = ?)', [$correo, $correo, $correo]);
        $correosQuedan = count($idsA($correo));
        $registroCorreos = $contar('SELECT COUNT(*) FROM `' . MailLogMapper::TABLE . '` WHERE recipients LIKE ?', ['%' . $correo . '%']);
        $check($quedan === 0 && $intentos === 0 && $codigos === 0 && $correosQuedan === 0 && $registroCorreos === 0, 'z1 no quedan el usuario, sus intentos, sus códigos, sus correos ni su registro de correos', "usuarios {$quedan}, intentos {$intentos}, códigos {$codigos}, correos {$correosQuedan}, registro {$registroCorreos}");
    }

    return $balance();

})->setDescription('Responder primero (P314 a): RespondFirst decide si cierra la conexión antes, sin FPM hace el trabajo antes de responder, no deja escapar una excepción del trabajo; y por HTTP generate-otp, la petición de recuperación y la de usuario olvidado responden lo de siempre y después guardan el código y envían el correo. Crea un usuario zz- y lo retira con sus correos.')->setEffects([CliActions::EFFECT_DATABASE])->register();
