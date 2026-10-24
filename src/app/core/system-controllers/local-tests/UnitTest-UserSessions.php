<?php

//Las sesiones con fila (ADR 0026, fases B y C), por HTTP: lo que no entra, lo que cierra, la jerarquía de quién toca qué.
//Crea dos organizaciones y ocho usuarios zz-agente-sesiones-*, con sus sesiones; borra todo.
//@areas src/app/classes/EventsLog/
//@areas src/app/classes/Organizations/
//@areas src/app/classes/SystemApprovals/

use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Statics\ProtectFileMiddleware;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/user-sessions', function ($args) {

    echoTerminal("\e[33m[TEST:UserSessions] Cada sesión con su fila: rechazos, cierres, extensión y jerarquía\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la pantalla de respaldos.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'c1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $ruta = function (string $nombre, array $parametros = []): string {
        $url = get_route($nombre, $parametros, true);
        if (!is_string($url) || $url === '') {
            return '';
        }
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $panel = $ruta('admin');
    $check($panel !== '' && $ruta('user-sessions-logout') !== '' && $ruta('user-sessions-datatables') !== '', 'c2 existen el panel, el logout y el listado');

    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $path, string $jwt, ?array $post = null) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ];
        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
    };
    //Por XHR, una sesión rechazada es 401; una aceptada, 200, o 302 a completar el perfil (un usuario general recién
    //creado), que no es el acceso.
    $entra = fn(string $jwt): bool => in_array($pedir($panel, $jwt)['status'], [200, 302], true);

    $prefijo = 'zz-agente-sesiones-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $tablaSesiones = UserSessions::TABLE;
    $usuarios = [];
    $organizaciones = [];
    $maxLogPrevio = (int) $database->query('SELECT COALESCE(MAX(id), 0) FROM `' . LogsMapper::TABLE . '`')->fetchColumn();

    $crearOrganizacion = function (string $sufijo, int $creador) use ($database, $tablaOrganizaciones, $prefijo, &$organizaciones): int {
        $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-{$sufijo}", 'zz-', "{$prefijo}-{$sufijo}", date('Y-m-d H:i:s'), $creador, 1, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
        $id = (int) $database->lastInsertId();
        $organizaciones[] = $id;
        return $id;
    };
    $crear = function (string $sufijo, int $tipo, int $org) use ($prefijo, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: las sesiones las abre la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Agente';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = $org;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };
    //Una sesión a mano: su fila y su token, y el id de la fila para tocarla.
    $sesion = function (int $userID) use ($database, $tablaSesiones): array {
        $sid = UserSessions::open($userID);
        $jwt = SessionToken::generateToken(['id' => $userID, 'sid' => $sid], null, SessionToken::DURATION_MAX, false);
        $consulta = $database->prepare("SELECT id FROM `{$tablaSesiones}` WHERE sessionHash = ?");
        $consulta->execute([UserSessions::hash($sid)]);
        return ['sid' => $sid, 'jwt' => $jwt, 'id' => (int) $consulta->fetchColumn()];
    };
    $fila = function (int $id) use ($database, $tablaSesiones): array {
        $consulta = $database->prepare("SELECT * FROM `{$tablaSesiones}` WHERE id = ?");
        $consulta->execute([$id]);
        $r = $consulta->fetch(\PDO::FETCH_ASSOC);
        return is_array($r) ? $r : [];
    };

    try {

        $root = $crear('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL);
        $root2 = $crear('root2', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL);
        $gral = $crear('gral', UsersModel::TYPE_USER_ADMIN_GRAL, OrganizationMapper::INITIAL_ID_GLOBAL);
        $gral2 = $crear('gral2', UsersModel::TYPE_USER_ADMIN_GRAL, OrganizationMapper::INITIAL_ID_GLOBAL);
        $orgA = $crearOrganizacion('a', $root);
        $orgB = $crearOrganizacion('b', $root);
        $adminA = $crear('admin-a', UsersModel::TYPE_USER_ADMIN_ORG, $orgA);
        $adminA2 = $crear('admin-a2', UsersModel::TYPE_USER_ADMIN_ORG, $orgA);
        $genA = $crear('general-a', UsersModel::TYPE_USER_GENERAL, $orgA);
        $genB = $crear('general-b', UsersModel::TYPE_USER_GENERAL, $orgB);
        $check(count($usuarios) === 8 && $orgA > 0 && $orgB > 0, 'banco: dos organizaciones y ocho usuarios de la prueba');

        //─── a · Lo que no entra ──────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[a] Sin fila viva, no se entra');
        $viva = $sesion($genA);
        $check($entra($viva['jwt']), 'a1 CANARIO: una sesión con su fila abierta entra');
        $check(!$entra(SessionToken::generateToken(['id' => $genA], null, null, false)), 'a2 un token SIN identificador no entra (P130 a)');
        $cerrada = $sesion($genA);
        UserSessions::close($cerrada['id'], null, UserSessions::REASON_CLOSED_BY_ADMIN);
        $check(!$entra($cerrada['jwt']), 'a3 una fila cerrada no entra');
        $caducada = $sesion($genA);
        $database->prepare("UPDATE `{$tablaSesiones}` SET expiresAt = ? WHERE id = ?")->execute([date('Y-m-d H:i:s', time() - 60), $caducada['id']]);
        $check(!$entra($caducada['jwt']), 'a4 una fila caducada no entra');
        $deOtro = $sesion($genB);
        $check(!$entra(SessionToken::generateToken(['id' => $genA, 'sid' => $deOtro['sid']], null, SessionToken::DURATION_MAX, false)), 'a5 el identificador de la sesión de otro usuario no entra');
        //Sin `aud`: el del terminal lo lleva y la firma ya lo rechazaría fuera de su origen; así se mira la marca.
        $check(!$entra(SessionToken::generateToken(['id' => $genA, UserSessions::TERMINAL_FLAG => true], null, null, false)), 'a6 un token con la marca de terminal, por HTTP, no entra');
        $check(!$entra(SessionToken::generateToken(['id' => $genA, 'sid' => str_repeat('0', 64)], null, SessionToken::DURATION_MAX, false)), 'a7 un identificador bien formado pero sin fila no entra');

        //«¿Estoy dentro?» del acceso: con solo la firma, un token viejo sin fila hacía un bucle entre el acceso y el panel.
        $verificar = fn(string $jwt): ?bool => $pedir($ruta('users-verify-login-request'), $jwt, [])['json']['isAuth'] ?? null;
        $check($verificar($viva['jwt']) === true, 'a8 CANARIO: verify dice que una sesión viva está dentro');
        $check($verificar(SessionToken::generateToken(['id' => $genA], null, null, false)) === false, 'a9 y que un token sin identificador NO (sin bucle tras la ruptura)');
        $check($verificar($cerrada['jwt']) === false, 'a10 ni uno de una fila cerrada');

        //─── b · Última actividad: una escritura como mucho cada 5 minutos ─────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] La última actividad');
        $hace10 = date('Y-m-d H:i:s', time() - 600);
        $database->prepare("UPDATE `{$tablaSesiones}` SET lastActivityAt = ? WHERE id = ?")->execute([$hace10, $viva['id']]);
        $entra($viva['jwt']);
        $primera = (string) ($fila($viva['id'])['lastActivityAt'] ?? '');
        $check($primera !== $hace10 && strtotime($primera) >= time() - 60, 'b1 CANARIO: con más de 5 minutos, la petición la escribe', $primera);
        sleep(1);
        $entra($viva['jwt']);
        $check((string) ($fila($viva['id'])['lastActivityAt'] ?? '') === $primera, 'b2 y la siguiente, dentro de los 5 minutos, NO la vuelve a escribir');

        //─── c · Cerrar sesión cierra en el servidor ──────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] Logout y «cerrar las demás»');
        $paraSalir = $sesion($genA);
        $salida = $pedir($ruta('user-sessions-logout'), $paraSalir['jwt'], []);
        $check($salida['status'] === 200 && ($fila($paraSalir['id'])['closeReason'] ?? null) === UserSessions::REASON_LOGOUT, 'c1 el logout cierra su fila (logout)', "HTTP {$salida['status']}");
        $check(!$entra($paraSalir['jwt']), 'c2 y el mismo token ya no entra');
        $s1 = $sesion($genB);
        $s2 = $sesion($genB);
        $s3 = $sesion($genB);
        $demas = $pedir($ruta('user-sessions-close-others'), $s1['jwt'], []);
        $check($demas['status'] === 200, 'c3 «cerrar las demás» responde', "HTTP {$demas['status']}");
        $check($entra($s1['jwt']) && !$entra($s2['jwt']) && !$entra($s3['jwt']) && !$entra($deOtro['jwt']), 'c4 queda viva solo la actual');

        //─── d · La jerarquía (P133) ──────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] Quién cierra a quién');
        $cerrar = fn(array $s, string $jwt): int => $pedir($ruta('user-sessions-close', ['id' => $s['id']]), $jwt, [])['status'];
        $sAdminA = $sesion($adminA);
        $sAdminA2 = $sesion($adminA2);
        $sGral = $sesion($gral);
        $sGral2 = $sesion($gral2);
        $sRoot = $sesion($root);
        $sRoot2 = $sesion($root2);
        $vGenA = $sesion($genA);
        $vGenB = $sesion($genB);
        $check($cerrar($vGenB, $sAdminA['jwt']) === 404 && $entra($vGenB['jwt']), 'd1 el de organización no cierra la de otra organización (404, y sigue viva)');
        $check($cerrar($sAdminA2, $sAdminA['jwt']) === 404 && $entra($sAdminA2['jwt']), 'd2 ni la de otro administrador de su organización');
        $check($cerrar($vGenA, $sAdminA['jwt']) === 200 && !$entra($vGenA['jwt']), 'd3 CANARIO: sí la de un usuario de su organización');
        $check($cerrar($sGral2, $sGral['jwt']) === 404 && $entra($sGral2['jwt']), 'd4 el general no cierra la de otro general');
        $check($cerrar($sRoot, $sGral['jwt']) === 404 && $entra($sRoot['jwt']), 'd5 ni la de root');
        $check($cerrar($sAdminA2, $sGral['jwt']) === 200 && !$entra($sAdminA2['jwt']), 'd6 CANARIO: sí la de un administrador de organización');
        $check($cerrar($sRoot2, $sRoot['jwt']) === 200 && !$entra($sRoot2['jwt']), 'd7 root cierra la de otro root');

        //─── e · Extender ─────────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[e] Extender, hasta el techo');
        $extender = fn(array $s, string $jwt, int $dias): array => $pedir($ruta('user-sessions-extend', ['id' => $s['id']]), $jwt, ['days' => $dias]);
        $paraExtender = $sesion($genA);
        $check($extender($paraExtender, $sAdminA['jwt'], 10)['status'] === 403, 'e1 el de organización no extiende (403 por la ruta)');
        $check($extender($sGral2, $sGral['jwt'], 10)['status'] === 404, 'e2 el general no extiende la de otro general');
        $bien = $extender($paraExtender, $sGral['jwt'], 10);
        $nueva = strtotime((string) ($fila($paraExtender['id'])['expiresAt'] ?? ''));
        $check($bien['status'] === 200 && $nueva !== false && abs($nueva - (time() + 10 * 86400)) < 120, 'e3 CANARIO: el general extiende 10 días la de un usuario', "HTTP {$bien['status']}");
        $database->prepare("UPDATE `{$tablaSesiones}` SET createdAt = ? WHERE id = ?")->execute([date('Y-m-d H:i:s', time() - (SessionToken::DURATION_MAX - 5 * 86400)), $paraExtender['id']]);
        $techo = $extender($paraExtender, $sGral['jwt'], 30);
        $check($techo['status'] === 400 && abs((int) strtotime((string) ($fila($paraExtender['id'])['expiresAt'] ?? '')) - $nueva) < 2, 'e4 pasarse del techo (la caducidad del token) se rechaza y no mueve nada', "HTTP {$techo['status']}");

        //─── f · Lo que lista cada uno (P133 y #1315) ─────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[f] El listado de Registros › Sesiones abiertas');
        $listado = fn(string $jwt): string => $pedir($ruta('user-sessions-datatables') . '?draw=1&start=0&length=500', $jwt)['body'];
        $deGral = $listado($sGral['jwt']);
        $check(str_contains($deGral, "{$prefijo}-gral<") || str_contains($deGral, "{$prefijo}-gral "), 'f1 el general ve su propia sesión (#1315)');
        $check(!str_contains($deGral, "{$prefijo}-gral2") && !str_contains($deGral, "{$prefijo}-root"), 'f2 y no la de otro general ni la de root');
        $check(str_contains($deGral, "{$prefijo}-general-b"), 'f3 CANARIO: sí la de un usuario general');
        $deAdminA = $listado($sAdminA['jwt']);
        $check(str_contains($deAdminA, "{$prefijo}-admin-a<") || str_contains($deAdminA, "{$prefijo}-admin-a "), 'f4 el de organización ve la suya');
        $check(!str_contains($deAdminA, "{$prefijo}-general-b") && !str_contains($deAdminA, "{$prefijo}-gral"), 'f5 y no la de otra organización ni la de un general');
        $deRoot = $listado($sRoot['jwt']);
        $check(str_contains($deRoot, "{$prefijo}-gral2") && str_contains($deRoot, "{$prefijo}-general-b"), 'f6 CANARIO: root ve las de todos');

        //─── g · Inactividad y tope, configurables; 0 = sin ese límite (P142) ─────────────────────
        //Por llamada directa y con set_config: la configuración de Apache no la cambia este proceso.
        echoTerminal(' ');
        echoTerminal('[g] La sesión caduca por inactividad, con un tope; los dos configurables y 0 = sin ese límite');
        $previas = [SessionToken::INACTIVITY_CONFIG => get_config(SessionToken::INACTIVITY_CONFIG), SessionToken::CEILING_CONFIG => get_config(SessionToken::CEILING_CONFIG)];
        $poner = function (int $inactividad, int $tope): void {
            set_config(SessionToken::INACTIVITY_CONFIG, $inactividad);
            set_config(SessionToken::CEILING_CONFIG, $tope);
        };
        $marcar = function (array $s, int $creada, int $actividad, int $caduca) use ($database, $tablaSesiones): void {
            $database->prepare("UPDATE `{$tablaSesiones}` SET createdAt = ?, lastActivityAt = ?, expiresAt = ? WHERE id = ?")
                ->execute([date('Y-m-d H:i:s', $creada), date('Y-m-d H:i:s', $actividad), date('Y-m-d H:i:s', $caduca), $s['id']]);
        };
        $vale = fn(array $s, int $userID): bool => UserSessions::tokenIsValid((object) ['id' => $userID, 'sid' => $s['sid']], $userID);
        $caduca = fn(array $s): int => (int) strtotime((string) ($fila($s['id'])['expiresAt'] ?? ''));
        $vida = function (string $jwt): ?int {
            $partes = explode('.', $jwt);
            $carga = isset($partes[1]) ? json_decode((string) base64_decode(strtr($partes[1], '-_', '+/')), true) : null;
            return is_array($carga) && isset($carga['iat'], $carga['exp']) ? (int) $carga['exp'] - (int) $carga['iat'] : null;
        };
        $temporal = null;
        try {
            $ahora = time();
            $poner(100, 1000);
            $check(UserSessions::expiryAt($ahora, $ahora) === $ahora + 100 && UserSessions::expiryAt($ahora, $ahora - 950) === $ahora + 50,
                'g1 la caducidad: la inactividad desde la actividad, sin pasar del tope desde el inicio');

            $poner(3600, 0);
            $sg = $sesion($genB);
            $marcar($sg, $ahora - 1000, $ahora - 600, $ahora + 60);
            $entro = $vale($sg, $genB);
            $check($entro && abs($caduca($sg) - ($ahora + 3600)) < 5, 'g2 DISCRIMINANTE: la actividad corre la caducidad hasta ahora más la inactividad', date('Y-m-d H:i:s', $caduca($sg)));
            $database->prepare("UPDATE `{$tablaSesiones}` SET expiresAt = ? WHERE id = ?")->execute([date('Y-m-d H:i:s', $ahora + 120), $sg['id']]);
            $vale($sg, $genB);
            $check($caduca($sg) === $ahora + 120, 'g3 DISCRIMINANTE: el freno; dentro de los 5 minutos no se vuelve a escribir', date('Y-m-d H:i:s', $caduca($sg)));

            $poner(60, 0);
            $marcar($sg, $ahora - 1000, $ahora - 600, $ahora - 540);
            $check(!$vale($sg, $genB) && $caduca($sg) === $ahora - 540, 'g4 sin actividad durante la inactividad (60 s de prueba), la fila ha caducado: no entra y la petición no la revive');

            $poner(3600, 3600);
            $s5 = $sesion($genB);
            $marcar($s5, $ahora - 3500, $ahora - 600, $ahora + 30);
            $vale($s5, $genB);
            $check(abs($caduca($s5) - ($ahora + 100)) < 5, 'g5 DISCRIMINANTE: el tope acota la caducidad aunque haya actividad (inicio + tope, no ahora + inactividad)', date('Y-m-d H:i:s', $caduca($s5)));
            $marcar($s5, $ahora - 3700, $ahora - 200, $ahora - 100);
            $check(!$vale($s5, $genB), 'g6 pasado el tope, la sesión está cerrada aunque se use');

            $poner(0, 0);
            $s7 = $sesion($genB);
            $marcar($s7, $ahora - 1000, $ahora - 600, $ahora + 30);
            $entro = $vale($s7, $genB);
            $check($entro && $caduca($s7) === $ahora - 1000 + UserSessions::SESSION_TOKEN_NO_CEILING, 'g7 con 0 y 0, la sesión sigue abierta: caduca a los 10 años de su inicio', date('Y-m-d H:i:s', $caduca($s7)));

            $jwtSinTope = UserSessions::issueToken($genB);
            $check($vida($jwtSinTope) === UserSessions::SESSION_TOKEN_NO_CEILING, 'g8 con tope 0, el token se emite con 10 años de vida', var_export($vida($jwtSinTope), true) . ' s');
            $poner(3600, 7200);
            $check($vida(UserSessions::issueToken($genB)) === 7200, 'g8bis con tope, el token vive el tope', '');

            //LA FILA MANDA aunque el token viva 10 años: cerrada, ese token ya no entra por ningún camino.
            $poner(0, 0);
            $datos = BaseToken::getData($jwtSinTope);
            $sid = $datos instanceof \stdClass ? (string) ($datos->sid ?? '') : '';
            $consulta = $database->prepare("SELECT id FROM `{$tablaSesiones}` WHERE sessionHash = ?");
            $consulta->execute([UserSessions::hash($sid)]);
            $idSinTope = (int) $consulta->fetchColumn();
            $temporal = sys_get_temp_dir() . '/zz-agente-sesiones-' . bin2hex(random_bytes(4));
            //RETORNO-IGNORADO: un temporal propio; si falla, la comprobación que sigue lo dice.
            mkdir($temporal, 0775, true);
            file_put_contents($temporal . '/zz.txt', 'zz');
            ProtectFileMiddleware::protectWithSession($temporal);
            $_SERVER['HTTP_' . strtoupper(SessionToken::tokenName())] = $jwtSinTope;
            $peticion = new \PiecesPHP\Core\Routing\RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz'), new Headers(), [], [], (new StreamFactory())->createStream(''));
            $antes = [UserSessions::tokenIsValid($datos, $genB), UserSessions::identifierIsOpen($sid, $genB), ProtectFileMiddleware::validateAccess($temporal . '/zz.txt', $peticion)];
            $check($antes === [true, true, true], 'g9 CANARIO: abierta, el token de 10 años entra por los tres caminos', var_export($antes, true));
            UserSessions::close($idSinTope, null, UserSessions::REASON_CLOSED_BY_USER);
            $despues = [UserSessions::tokenIsValid($datos, $genB), UserSessions::identifierIsOpen($sid, $genB), ProtectFileMiddleware::validateAccess($temporal . '/zz.txt', $peticion)];
            $check($despues === [false, false, false], 'g10 DISCRIMINANTE: cerrada la fila, ese token ya no entra: ni la sesión (tokenIsValid), ni la renovación de index.php (identifierIsOpen), ni un archivo protegido', var_export($despues, true));
        } finally {
            foreach ($previas as $clave => $valor) {
                set_config($clave, $valor);
            }
            unset($_SERVER['HTTP_' . strtoupper(SessionToken::tokenName())]);
            if (is_string($temporal) && is_dir($temporal)) {
                foreach (array_merge(glob($temporal . '/*') ?: [], glob($temporal . '/.htaccess') ?: []) as $archivo) {
                    //RETORNO-IGNORADO: limpieza de un temporal propio.
                    unlink($archivo);
                }
                //RETORNO-IGNORADO: limpieza de un temporal propio.
                rmdir($temporal);
            }
        }

    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            if ($usuarios !== []) {
                $marcas = implode(',', array_fill(0, count($usuarios), '?'));
                $database->prepare("DELETE FROM `{$tablaSesiones}` WHERE userID IN ({$marcas})")->execute(array_values($usuarios));
                $database->prepare('DELETE FROM `' . LogsMapper::TABLE . "` WHERE id > ? AND createdBy IN ({$marcas})")->execute(array_merge([$maxLogPrevio], array_values($usuarios)));
            }
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ([[$tablaOrganizaciones, $organizaciones], [$tablaUsuarios, array_values($usuarios)]] as [$tablaReferencia, $ids]) {
                foreach ($ids as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaReferencia),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                }
            }
            foreach ($usuarios as $id) {
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            //El usuario apunta a su organización y la organización a su creador: los usuarios, a la global; luego caen
            //las organizaciones y al final los usuarios.
            $database->prepare("UPDATE `{$tablaUsuarios}` SET organization = ? WHERE username LIKE ?")->execute([OrganizationMapper::INITIAL_ID_GLOBAL, "{$prefijo}%"]);
            foreach ($organizaciones as $id) {
                $database->prepare("DELETE FROM `{$tablaOrganizaciones}` WHERE id = ?")->execute([$id]);
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $cuenta = function (string $sql, array $valores) use ($database): int {
            $consulta = $database->prepare($sql);
            $consulta->execute($valores);
            return (int) $consulta->fetchColumn();
        };
        $quedan = $cuenta("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE ?", ["{$prefijo}%"])
            + $cuenta("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE ?", ["{$prefijo}%"]);
        if ($usuarios !== []) {
            $marcas = implode(',', array_fill(0, count($usuarios), '?'));
            $quedan += $cuenta("SELECT COUNT(*) FROM `{$tablaSesiones}` WHERE userID IN ({$marcas})", array_values($usuarios));
        }
        $check($quedan === 0, 'z1 no queda ninguna sesión, organización ni usuario de la prueba', (string) $quedan);
    }

    return $balance();

})->setDescription('Las sesiones con fila, por HTTP: sin identificador, cerrada, caducada, ajena, del terminal o sin fila no entran; la última actividad se escribe como mucho cada 5 minutos; el logout y «cerrar las demás» cierran en el servidor; la jerarquía de P133 al cerrar, extender y listar; extender no pasa del techo. Crea y borra dos organizaciones, ocho usuarios y sus sesiones.')->setEffects([CliActions::EFFECT_DATABASE])->register();
