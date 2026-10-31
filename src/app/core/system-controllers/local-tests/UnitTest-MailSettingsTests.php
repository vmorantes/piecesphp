<?php

//Probar la conexión y el correo de prueba de Configuración › Correo: solo root, y dicen a dónde fue de verdad (ADR 0046).
//Nunca sale a la red: si el correo no va retenido a 127.0.0.1, se para antes de pedir nada.
//@areas src/app/classes/Organizations/
//@areas src/app/classes/PiecesPHP/Settings/
//@areas src/app/classes/PiecesPHP/SystemStatus/

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\Mail\MailTester;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/mail-settings-tests', function ($args) {

    echoTerminal("\e[33m[TEST:MailSettingsTests] Probar la conexión y el correo de prueba: solo root, y dicen a dónde fue\e[39m");
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

    //La guarda de la red va PRIMERO: lo que se pide por HTTP conecta con el destino del Mailer de la instalación.
    $usado = new Mailer(false);
    $destinoLocal = in_array(gethostbyname((string) $usado->Host), ['127.0.0.1', '::1'], true);
    if (!$check(MailDelivery::goesToSink() && $destinoLocal, 'banco: el correo está retenido y el Mailer va a 127.0.0.1 (ninguna prueba sale a la red)', $usado->Host . ':' . $usado->Port)) {
        return $balance();
    }

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que view-escapes.
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
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $path, ?string $jwt, array $post) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $cabeceras = ['X-Requested-With: XMLHttpRequest'];
        if ($jwt !== null) {
            $cabeceras[] = "{$cabeceraToken}: {$jwt}";
        }
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $cabeceras,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post),
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $datos = is_string($body) ? json_decode($body, true) : null;
        return ['status' => $status, 'data' => is_array($datos) ? $datos : []];
    };

    $prefijo = 'zz-prueba-correo-' . bin2hex(random_bytes(3));
    //El ÚNICO destinatario de la prueba (ADR 0011), y el patrón de la limpieza del registro de correos.
    $destinatario = "zz-prueba-fw-{$prefijo}@mailinator.com";
    $usuarios = [];
    $blobPrevio = get_config('mail');
    $crear = function (int $type, string $sufijo) use ($prefijo, &$usuarios): int {
        $user = new UsersModel();
        $user->username = "{$prefijo}-{$sufijo}";
        $user->email = "{$prefijo}-{$sufijo}@example.com";
        $user->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $user->firstname = 'Zz';
        $user->secondname = '';
        $user->firstLastname = 'Correo';
        $user->secondLastname = '';
        $user->type = $type;
        $user->status = UsersModel::STATUS_USER_ACTIVE;
        $user->failedAttempts = 0;
        $user->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $user->createdAt = new \DateTime();
        $user->modifiedAt = $user->createdAt;
        $user->save();
        $usuarios[] = (int) $user->id;
        return (int) $user->id;
    };
    $enRegistro = function () use ($database, $destinatario): int {
        $consulta = $database->prepare('SELECT COUNT(*) FROM `' . MailLogMapper::TABLE . '` WHERE recipients LIKE ?');
        $consulta->execute(["%{$destinatario}%"]);
        return (int) $consulta->fetchColumn();
    };

    try {
        $jwtRoot = UserSessions::issueToken($crear(UsersModel::TYPE_USER_ROOT, 'root'));
        $jwtAdmin = UserSessions::issueToken($crear(UsersModel::TYPE_USER_ADMIN_GRAL, 'admin'));
        $rutaProbar = $camino(SettingsController::routeName('integrations-mail-probe', [], true));
        $rutaEnviar = $camino(SettingsController::routeName('integrations-mail-test-send', [], true));
        $check($rutaProbar !== '' && $rutaEnviar !== '', 'banco: las dos rutas existen', "{$rutaProbar} · {$rutaEnviar}");

        //─── a · Quién las pide ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Solo root: sin sesión o con otro tipo de usuario, ni conecta ni envía');
        foreach (['sin sesión' => null, 'administrador general' => $jwtAdmin] as $quien => $jwt) {
            $r = $pedir($rutaProbar, $jwt, []);
            $check(in_array($r['status'], [401, 403], true) && ($r['data']['success'] ?? null) !== true, "a1 {$quien}: «Probar conexión» se rechaza", "HTTP {$r['status']}");
            $r = $pedir($rutaEnviar, $jwt, ['to' => $destinatario]);
            $check(in_array($r['status'], [401, 403], true) && ($r['data']['success'] ?? null) !== true, "a2 {$quien}: «Enviar correo de prueba» se rechaza", "HTTP {$r['status']}");
        }
        $check($enRegistro() === 0, 'a3 y ningún rechazo dejó un correo en el registro');
        echoTerminal(' ');

        //─── b · Root, contra el sumidero local ─────────────────────────────────────────────────────────
        echoTerminal('[b] Root: conecta y envía, y dice el configurado y el usado');
        //Como MailTester::route(): los getters devuelven el valor o el objeto, y se estrecha por tipo.
        $hostConfigurado = (new MailConfig())->host();
        $puertoConfigurado = (new MailConfig())->port();
        $configurado = (is_string($hostConfigurado) ? $hostConfigurado : '') . ':' . (is_int($puertoConfigurado) ? $puertoConfigurado : '');
        $usadoTexto = $usado->Host . ':' . $usado->Port;
        $r = $pedir($rutaProbar, $jwtRoot, []);
        $check($r['status'] === 200 && ($r['data']['success'] ?? null) === true, 'b1 CANARIO: root prueba la conexión con el sumidero y funciona', "HTTP {$r['status']}, " . mb_substr((string) ($r['data']['message'] ?? ''), 0, 120));
        $check(($r['data']['used'] ?? null) === $usadoTexto && ($r['data']['configured'] ?? null) === $configurado, 'b2 y dice el usado (el sumidero) y el configurado, cada uno el suyo', (string) json_encode([$r['data']['used'] ?? null, $usadoTexto]));
        $r = $pedir($rutaEnviar, $jwtRoot, ['to' => $destinatario]);
        $check($r['status'] === 200 && ($r['data']['success'] ?? null) === true && ($r['data']['used'] ?? null) === $usadoTexto, 'b3 root envía el correo de prueba por el Mailer, al sumidero', "HTTP {$r['status']}, " . mb_substr((string) ($r['data']['message'] ?? ''), 0, 120));
        $check($enRegistro() === 1, 'b4 y queda UNA línea en el registro de correos', 'líneas ' . $enRegistro());
        $r = $pedir($rutaEnviar, $jwtRoot, ['to' => 'zz-no-es-un-correo']);
        $check($r['status'] === 400 && ($r['data']['success'] ?? null) === false && $enRegistro() === 1, 'b5 una dirección que no es un correo: 400 y no envía');
        //Mal formada pero con un dominio que SÍ tiene MX: mirar solo el MX la daba por buena.
        $r = $pedir($rutaEnviar, $jwtRoot, ['to' => 'x y@mailinator.com']);
        $check($r['status'] === 400 && ($r['data']['success'] ?? null) === false && $enRegistro() === 1, 'b6 una dirección mal formada con un dominio con MX: 400 y no envía', "HTTP {$r['status']}");
        echoTerminal(' ');

        //─── c · El camino de fallo, en proceso y solo en memoria ───────────────────────────────────────
        echoTerminal('[c] Con el servidor de pruebas en un puerto cerrado: falla, lo dice y nombra el puerto de verdad');
        $config = new MailConfig();
        $config->testHost('127.0.0.1');
        $config->testPort(9);
        set_config('mail', $config->toSave());
        $resultado = MailTester::probe();
        $check($resultado['success'] === false && str_starts_with($resultado['message'], sprintf(__(SettingsController::LANG_GROUP, 'Falló al %s: %s'), __(SettingsController::LANG_GROUP, 'conectar'), '')), 'c1 la conexión falla y el mensaje dice en qué paso', $resultado['message']);
        $check($resultado['used'] === '127.0.0.1:9' && $resultado['configured'] === $configurado, 'c2 «usado» es 127.0.0.1:9, el que se midió, y no el host configurado (el defecto del ADR 0046)', $resultado['route']);
    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        set_config('mail', $blobPrevio);
        //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
        MailLogMapper::deleteByRecipientMark($destinatario);
        foreach ($usuarios as $id) {
            $database->prepare('DELETE FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0 && $enRegistro() === 0 && get_config('mail') === $blobPrevio, 'z1 no quedan usuarios, sesiones ni líneas del registro de la prueba, y la configuración del correo es la de antes', "usuarios {$quedan}, líneas " . $enRegistro());
    }

    return $balance();

})->setDescription('Probar la conexión y el correo de prueba de Configuración › Correo: solo root, contra el sumidero local, y dicen el host configurado y el usado (ADR 0046). Por HTTP; nunca sale a la red.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
