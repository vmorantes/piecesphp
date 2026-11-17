<?php

//La IP del visitante detrás de un proxy (H28): la cabecera, solo desde un proxy de confianza, de derecha a izquierda y parada en lo que no es IP;
//IPv4 mapeadas, /0 rechazado y escapado, solo root guarda, el aviso sin lista, el botón y la audiencia. Siembra zz- y repone la opción.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\VisitorIP;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/visitor-ip', function ($args) {

    echoTerminal("\e[33m[TEST:VisitorIP] La IP del visitante detrás de un proxy\e[39m");
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

    //─── r · La función, en el proceso ─────────────────────────────────────────────────────────────
    echoTerminal('[r] resolve(): la cabecera solo cuenta si la conexión viene de un proxy de confianza');
    $proxy = '198.51.100.7';
    $visitante = '203.0.113.20';
    $inventada = '192.0.2.99';
    $check(VisitorIP::resolve(['REMOTE_ADDR' => $proxy, 'HTTP_X_FORWARDED_FOR' => $inventada], []) === $proxy, 'r1 DISCRIMINANTE: sin lista, una cabecera inventada no cambia la IP (REMOTE_ADDR)');
    $check(VisitorIP::resolve(['REMOTE_ADDR' => $visitante, 'HTTP_X_FORWARDED_FOR' => $inventada], [$proxy]) === $visitante, 'r2 DISCRIMINANTE: con lista, si la conexión NO viene del proxy, la cabecera tampoco cuenta');
    $check(VisitorIP::resolve(['REMOTE_ADDR' => $proxy, 'HTTP_X_FORWARDED_FOR' => "{$inventada}, {$visitante}"], [$proxy]) === $visitante, 'r3 DISCRIMINANTE: con el proxy de confianza, de derecha a izquierda: la inventada del principio no se alcanza');
    $check(VisitorIP::resolve(['REMOTE_ADDR' => $proxy, 'HTTP_X_FORWARDED_FOR' => "{$inventada}, {$visitante}, 10.1.2.3"], [$proxy, '10.0.0.0/8']) === $visitante, 'r4 un CIDR de confianza en medio de la cadena se salta');
    $check(VisitorIP::resolve(['REMOTE_ADDR' => '2001:db8::1', 'HTTP_X_FORWARDED_FOR' => '2001:db8:ffff::5'], ['2001:db8::/48']) === '2001:db8:ffff::5', 'r5 IPv6 con un CIDR /48');
    $check(VisitorIP::resolve(['REMOTE_ADDR' => $proxy, 'HTTP_X_FORWARDED_FOR' => 'no-es-una-ip'], [$proxy]) === $proxy, 'r6 una cabecera que no es válida deja REMOTE_ADDR');
    $check(VisitorIP::resolve(['REMOTE_ADDR' => $proxy], [$proxy]) === $proxy, 'r7 sin cabecera, REMOTE_ADDR');
    $check(VisitorIP::resolve([], []) === VisitorIP::NONE, 'r8 sin REMOTE_ADDR (el terminal), 0.0.0.0');
    $check(VisitorIP::resolve(['REMOTE_ADDR' => $proxy, 'HTTP_X_FORWARDED_FOR' => "basura, {$visitante}"], [$proxy]) === $visitante, 'r9 DISCRIMINANTE: una entrada que no es IP en la parte del cliente no devuelve la del proxy');
    $check(VisitorIP::resolve(['REMOTE_ADDR' => $proxy, 'HTTP_X_FORWARDED_FOR' => "{$visitante}, basura"], [$proxy]) === $proxy, 'r10 una entrada que no es IP junto al proxy deja el último salto de confianza (REMOTE_ADDR)');
    $check(VisitorIP::resolve(['REMOTE_ADDR' => $proxy, 'HTTP_X_FORWARDED_FOR' => "{$inventada}, basura, 10.1.2.3"], [$proxy, '10.0.0.0/8']) === '10.1.2.3', 'r11 tras un salto de confianza en la cabecera, lo que no es IP deja ese salto');
    $check(VisitorIP::resolve(['REMOTE_ADDR' => '::ffff:198.51.100.7', 'HTTP_X_FORWARDED_FOR' => $visitante], [$proxy]) === $visitante, 'r12 DISCRIMINANTE: la conexión como IPv4 mapeada casa con su IPv4 de la lista');
    $check(VisitorIP::resolve(['REMOTE_ADDR' => $proxy, 'HTTP_X_FORWARDED_FOR' => $visitante], ['::ffff:198.51.100.7']) === $visitante, 'r13 una entrada mapeada de la lista casa con la IPv4 de la conexión');
    $check(VisitorIP::resolve(['REMOTE_ADDR' => $proxy, 'HTTP_X_FORWARDED_FOR' => $visitante], ['::ffff:198.51.100.0/120']) === $visitante, 'r14 una entrada mapeada con prefijo (/120 = /24 de IPv4) casa');
    echoTerminal(' ');

    //─── p · Privadas o locales, y el proxy sin lista ───────────────────────────────────────────────
    echoTerminal('[p] isPrivateOrLocal() y proxyWithoutList()');
    $check(VisitorIP::isPrivateOrLocal('127.0.0.1') && VisitorIP::isPrivateOrLocal('::1') && VisitorIP::isPrivateOrLocal('10.1.2.3') && VisitorIP::isPrivateOrLocal('192.168.1.1') && VisitorIP::isPrivateOrLocal('::ffff:127.0.0.1'), 'p1 locales, privadas y la IPv4 mapeada local: true');
    $check(!VisitorIP::isPrivateOrLocal('8.8.8.8') && !VisitorIP::isPrivateOrLocal('2001:4860:4860::8888') && !VisitorIP::isPrivateOrLocal('no-es-una-ip'), 'p2 públicas y lo que no es IP: false');
    $check(VisitorIP::proxyWithoutList(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_FOR' => $visitante], []) === true, 'pw1 DISCRIMINANTE: conexión local con X-Forwarded-For y lista vacía');
    $check(VisitorIP::proxyWithoutList(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_FOR' => $visitante], [$proxy]) === false, 'pw2 con lista, no');
    $check(VisitorIP::proxyWithoutList(['REMOTE_ADDR' => '127.0.0.1'], []) === false, 'pw3 sin cabecera, no');
    $check(VisitorIP::proxyWithoutList(['REMOTE_ADDR' => '8.8.8.8', 'HTTP_X_FORWARDED_FOR' => $visitante], []) === false, 'pw4 conexión pública, no');
    echoTerminal(' ');

    //─── v · La validación de la lista ──────────────────────────────────────────────────────────────
    echoTerminal('[v] Las entradas: IP o CIDR, IPv4 o IPv6; nunca /0');
    $check(VisitorIP::entryError('0.0.0.0/0') === VisitorIP::ERROR_ALL && VisitorIP::entryError('::/0') === VisitorIP::ERROR_ALL && VisitorIP::entryError('10.0.0.0/0') === VisitorIP::ERROR_ALL, 'v1 DISCRIMINANTE: 0.0.0.0/0, ::/0 y cualquier /0, rechazados');
    $check(VisitorIP::entryError('10.0.0.0/8') === null && VisitorIP::entryError('2001:db8::/32') === null && VisitorIP::entryError($proxy) === null, 'v2 una IP y los CIDR válidos pasan');
    $check(VisitorIP::entryError('10.0.0.0/33') === VisitorIP::ERROR_INVALID && VisitorIP::entryError('no-es-una-ip') === VisitorIP::ERROR_INVALID, 'v3 un prefijo fuera de rango o un texto, no válidos');
    $check(SettingsController::trustedProxiesFromInput("{$proxy}\n\n{$proxy}\n10.0.0.0/8") === [$proxy, '10.0.0.0/8'], 'v4 el área de texto: una por línea, sin vacías ni duplicados');
    $rechazo = null;
    try {
        SettingsController::trustedProxiesFromInput("{$proxy}\n0.0.0.0/0");
    } catch (\Throwable $e) {
        $rechazo = $e->getMessage();
    }
    $check($rechazo !== null && str_contains($rechazo, '0.0.0.0/0'), 'v5 guardar /0 lanza con su motivo y nombra la entrada', (string) $rechazo);
    $rechazo = null;
    try {
        SettingsController::trustedProxiesFromInput('<b>zz</b>');
    } catch (\Throwable $e) {
        $rechazo = $e->getMessage();
    }
    $check($rechazo !== null && str_contains($rechazo, '&lt;b&gt;zz&lt;/b&gt;') && !str_contains($rechazo, '<b>'), 'v6 DISCRIMINANTE: el motivo nombra la línea escapada', (string) $rechazo);
    $check(VisitorIP::entryError('::ffff:1.2.3.4/96') === VisitorIP::ERROR_ALL && VisitorIP::entryError('::ffff:0.0.0.0/96') === VisitorIP::ERROR_ALL && VisitorIP::entryError('::ffff:198.51.100.0/120') === null, 'v7 DISCRIMINANTE: una IPv4 mapeada con /96 abarca todas las IPv4, como /0; con /120, válida');
    $rechazo = null;
    try {
        SettingsController::trustedProxiesFromInput('::ffff:1.2.3.4/96');
    } catch (\Throwable $e) {
        $rechazo = $e->getMessage();
    }
    $check($rechazo !== null && str_contains($rechazo, '::ffff:1.2.3.4/96'), 'v8 guardar una IPv4 mapeada con /96 lanza y nombra la entrada', (string) $rechazo);
    echoTerminal(' ');

    //─── t · La audiencia del token (Usar IP del usuario para encriptar el token de sesión) ────────────
    echoTerminal('[t] La audiencia del token sale de la IP del visitante');
    $servidorAntes = $_SERVER;
    $listaAntes = get_config(VisitorIP::CONFIG_NAME);
    try {
        $_SERVER['REMOTE_ADDR'] = $proxy;
        $_SERVER['HTTP_X_FORWARDED_FOR'] = $visitante;
        unset($_SERVER['HTTP_CLIENT_IP']);
        set_config(VisitorIP::CONFIG_NAME, []);
        $token = BaseToken::setToken(['zz' => 1], null, time(), time() + 600, true);
        $check(BaseToken::check($token) === true, 't1 sin lista, el token emitido vale en la misma conexión');
        $_SERVER['HTTP_X_FORWARDED_FOR'] = $inventada;
        $check(BaseToken::check($token) === true, 't2 DISCRIMINANTE: sin lista, cambiar X-Forwarded-For no cambia la audiencia (antes la tomaba de la cabecera)');
        $_SERVER['HTTP_X_FORWARDED_FOR'] = $visitante;
        set_config(VisitorIP::CONFIG_NAME, [$proxy]);
        $check(BaseToken::check($token) === BaseToken::INVALID_USER_LOGGIN, 't3 al añadir el proxy a la lista, el token de quien entra por él deja de valer (su IP pasa de la del proxy a la suya): la sesión se cierra una vez');
        $tokenConLista = BaseToken::setToken(['zz' => 1], null, time(), time() + 600, true);
        $_SERVER['REMOTE_ADDR'] = '198.51.100.8';
        $check(BaseToken::check($tokenConLista) === BaseToken::INVALID_USER_LOGGIN, 't4 con la lista, otra conexión con la misma cabecera pero sin pasar por el proxy no hereda la audiencia');
    } finally {
        $_SERVER = $servidorAntes;
        set_config(VisitorIP::CONFIG_NAME, $listaAntes);
    }
    echoTerminal(' ');

    //─── h · La pantalla, por HTTP: solo root guarda la lista; /0 rechazado ─────────────────────────────
    echoTerminal('[h] Configuración › Seguridad: solo root guarda la lista, y no /0');
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
    $ruta = (string) parse_url((string) SettingsController::routeName('system-security', [], true), \PHP_URL_PATH);
    $ruta = $prefijoBase !== '' && str_starts_with($ruta, $prefijoBase) ? substr($ruta, strlen($prefijoBase)) : $ruta;
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $jwt, ?array $post, array $cabeceras = []) use ($base, $ruta, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => array_merge(["{$cabeceraToken}: {$jwt}"], $cabeceras),
        ] + ($post !== null ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)] : []));
        $cuerpo = (string) curl_exec($handle);
        $datos = json_decode($cuerpo, true);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => $cuerpo, 'data' => is_array($datos) ? $datos : []];
    };
    $tablaOpciones = (new SettingsModel())->getModel()->getTable();
    $crudo = function () use ($database, $tablaOpciones): ?string {
        $consulta = $database->prepare('SELECT value FROM `' . $tablaOpciones . '` WHERE BINARY name = ?');
        $consulta->execute([VisitorIP::CONFIG_NAME]);
        $valor = $consulta->fetchColumn();
        return $valor === false ? null : (string) $valor;
    };
    $opcionAntes = $crudo();
    $prefijo = 'zz-prueba-proxies-' . bin2hex(random_bytes(3));
    $usuarios = [];
    try {
        $usuario = function (string $sufijo, int $tipo) use ($prefijo, &$usuarios): string {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.invalid";
            //Nadie entra con contraseña: el token lo fabrica la prueba.
            $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Proxies';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[] = (int) $u->id;
            return UserSessions::issueToken((int) $u->id);
        };
        $jwtRoot = $usuario('root', UsersModel::TYPE_USER_ROOT);
        $jwtAdmin = $usuario('admin', UsersModel::TYPE_USER_ADMIN_GRAL);
        $campos = ['check_aud_on_auth' => get_config('check_aud_on_auth') ? 1 : 0, 'hide_app_key_warning' => get_config('hide_app_key_warning') === true ? 1 : 0, SessionToken::INACTIVITY_CONFIG => (int) ceil(SessionToken::inactivity() / 86400), SessionToken::CEILING_CONFIG => (int) ceil(SessionToken::ceiling() / 86400)];

        $pantallaAdmin = $pedir($jwtAdmin, null);
        $pantallaRoot = $pedir($jwtRoot, null);
        $check($pantallaRoot['status'] === 200 && str_contains($pantallaRoot['body'], 'data-trusted-proxies') && str_contains($pantallaRoot['body'], 'data-proxies-diagnostic'), 'h1 root ve la sección y el diagnóstico', "HTTP {$pantallaRoot['status']}");
        $check($pantallaAdmin['status'] === 200 && !str_contains($pantallaAdmin['body'], 'data-trusted-proxies'), 'h2 administración general ve Seguridad, pero no la lista', "HTTP {$pantallaAdmin['status']}");
        $r = $pedir($jwtAdmin, $campos + [VisitorIP::CONFIG_NAME => $proxy]);
        $check($r['status'] === 403 && $crudo() === $opcionAntes, 'h3 DISCRIMINANTE: un no-root que envía la lista recibe 403 y la opción no cambia', "HTTP {$r['status']}");
        $r = $pedir($jwtRoot, $campos + [VisitorIP::CONFIG_NAME => "{$proxy}\n::/0"]);
        $check(($r['data']['success'] ?? null) === false && $crudo() === $opcionAntes, 'h4 DISCRIMINANTE: root con ::/0, rechazado y sin guardar', mb_substr((string) ($r['data']['message'] ?? $r['body']), 0, 160));
        $r = $pedir($jwtRoot, $campos + [VisitorIP::CONFIG_NAME => "{$proxy}\n2001:db8::/32"]);
        $guardado = json_decode((string) $crudo(), true);
        $check(($r['data']['success'] ?? null) === true && $guardado === [$proxy, '2001:db8::/32'], 'h5 root guarda una lista válida, como lista', mb_substr((string) $crudo(), 0, 120) . ' · HTTP ' . $r['status'] . ' · ' . mb_substr((string) ($r['data']['message'] ?? $r['body']), 0, 200));

        //El aviso sin lista y el botón: la conexión de la prueba llega de local, como la de un proxy en la misma máquina.
        $antesSinCampo = $crudo();
        $r = $pedir($jwtRoot, $campos);
        $check(($r['data']['success'] ?? null) === true && $crudo() === $antesSinCampo, 'h6.1 un POST de root sin el campo no toca la lista guardada', (string) $crudo());
        $r = $pedir($jwtRoot, $campos + [VisitorIP::CONFIG_NAME => '']);
        $check(($r['data']['success'] ?? null) === true && $crudo() === '[]', 'h6.0 DISCRIMINANTE: root vacía la lista enviando el área vacía', (string) $crudo() . ' · ' . mb_substr((string) ($r['data']['message'] ?? $r['body']), 0, 200));
        $conCabecera = $pedir($jwtRoot, null, ["X-Forwarded-For: {$visitante}"]);
        $check(str_contains($conCabecera['body'], 'data-proxies-without-list') && !str_contains($conCabecera['body'], 'data-add-remote-addr-disabled'), 'h6 DISCRIMINANTE: con X-Forwarded-For y sin lista, el aviso, y el botón activo', "HTTP {$conCabecera['status']}");
        $sinCabecera = $pedir($jwtRoot, null);
        $check(!str_contains($sinCabecera['body'], 'data-proxies-without-list') && str_contains($sinCabecera['body'], 'data-add-remote-addr-disabled'), 'h7 DISCRIMINANTE: sin la cabecera, ni aviso ni botón activo', "HTTP {$sinCabecera['status']}");
        $r = $pedir($jwtRoot, $campos + [VisitorIP::CONFIG_NAME => '192.0.2.1']);
        $conLista = $pedir($jwtRoot, null, ["X-Forwarded-For: {$visitante}"]);
        $check(($r['data']['success'] ?? null) === true && $conLista['status'] === 200 && !str_contains($conLista['body'], 'data-proxies-without-list'), 'h8 con lista, sin aviso', "HTTP {$conLista['status']}");
        $dos = $pedir($jwtRoot, null, ['X-Forwarded-For: 192.0.2.1', "X-Forwarded-For: {$visitante}"]);
        $check(str_contains($dos['body'], "<code>192.0.2.1, {$visitante}</code>"), 'h9 dos cabeceras X-Forwarded-For llegan unidas, en orden', "HTTP {$dos['status']}");
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        if ($opcionAntes === null) {
            $database->prepare('DELETE FROM `' . $tablaOpciones . '` WHERE BINARY name = ?')->execute([VisitorIP::CONFIG_NAME]);
        } else {
            $database->prepare('UPDATE `' . $tablaOpciones . '` SET value = ? WHERE BINARY name = ?')->execute([$opcionAntes, VisitorIP::CONFIG_NAME]);
        }
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($usuarios as $id) {
            $database->prepare('DELETE FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
            foreach ($referencias as [$tablaReferencia, $columna]) {
                $database->prepare("DELETE FROM `{$tablaReferencia}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0 && $crudo() === $opcionAntes, 'z1 no quedan los usuarios de la prueba y la opción vuelve a su valor en crudo', "quedan {$quedan}");
    }

    //La lista vacía deja todo como hoy: la IP de los frenos es REMOTE_ADDR (las suites de acceso lo cubren de punta a punta).
    $check(OTPRateLimiter::clientIP() === VisitorIP::resolve($_SERVER, []), 'e1 con la lista vacía, la IP de los frenos es la de siempre');

    return $balance();

})->setDescription('La IP del visitante detrás de un proxy: sin proxy de confianza una cabecera inventada no cambia nada; con él, X-Forwarded-For de derecha a izquierda, parado en una entrada que no es IP; IPv6, IPv4 mapeadas, CIDR, /0 rechazado y el motivo escapado, solo root guarda la lista, el aviso de proxy sin lista, el botón solo detrás de un intermediario y la audiencia del token. Siembra zz- y repone la opción.')->setEffects([CliActions::EFFECT_DATABASE])->register();
