<?php

//La sesión de una app (ADR 0092, R1): su vida propia, abrirla con la clave del dispositivo y renewExpired solo para la web.
//Las opciones, en memoria (set_config) y restauradas. Siembra zz-agente-jpg- y al empezar y al acabar lo borra.
//@areas nucleo

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Crypto\Sealed;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/app-sessions', function ($args) {

    echoTerminal('[TEST:AppSessions] Iniciando suite...', true, "\r\n", '33');
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

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'e0 hay conexión a la base')) {
        return ['success' => false, 'message' => 'Sin conexión a la base.'];
    }
    $tablaUsuarios = UsersModel::TABLE;
    $tablaSesiones = UserSessions::TABLE;
    $marca = 'zz-agente-jpg-';
    $prefijo = $marca . bin2hex(random_bytes(3));
    $contar = function (string $sql, array $valores = []) use ($database): int {
        $statement = $database->prepare($sql);
        $statement->execute($valores);
        return (int) $statement->fetchColumn();
    };
    $propio = fn (): array => [
        'usuarios' => $contar("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE ?", ["{$marca}%"]),
        'sesiones' => $contar("SELECT COUNT(*) FROM `{$tablaSesiones}` s JOIN `{$tablaUsuarios}` u ON u.id = s.userID WHERE u.username LIKE ?", ["{$marca}%"]),
    ];
    $limpiar = function () use ($database, $tablaUsuarios, $tablaSesiones, $marca): array {
        $ids = $database->prepare("SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE ?");
        $ids->execute(["{$marca}%"]);
        $borradas = ['sesiones' => 0, 'usuarios' => 0];
        foreach (array_map('intval', $ids->fetchAll(\PDO::FETCH_COLUMN)) as $id) {
            $s = $database->prepare("DELETE FROM `{$tablaSesiones}` WHERE userID = ?");
            $s->execute([$id]);
            $borradas['sesiones'] += $s->rowCount();
            $u = $database->prepare("DELETE FROM `{$tablaUsuarios}` WHERE id = ?");
            $u->execute([$id]);
            $borradas['usuarios'] += $u->rowCount();
        }
        return array_filter($borradas);
    };
    $restos = $limpiar();
    if ($restos !== []) {
        echoTerminal('[0] Restos de una pasada anterior, borrados al empezar: ' . json_encode($restos));
    }
    $inicial = $propio();
    $check(array_sum($inicial) === 0, 'e1 al empezar no hay nada de la suite en la base', json_encode($inicial) ?: '');

    $crearUsuario = function (string $sufijo) use ($database, $tablaUsuarios, $prefijo): int {
        $database->prepare("INSERT INTO `{$tablaUsuarios}` (username, password, firstname, firstLastname, email, type, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute(["{$prefijo}-{$sufijo}", password_hash('zz-' . bin2hex(random_bytes(6)), \PASSWORD_DEFAULT), 'Zz', 'Sesiones', "{$prefijo}-{$sufijo}@example.invalid", UsersModel::TYPE_USER_GENERAL, UsersModel::STATUS_USER_ACTIVE]);
        return (int) $database->lastInsertId();
    };
    $fila = function (string $identifier) use ($database, $tablaSesiones): array {
        $statement = $database->prepare("SELECT * FROM `{$tablaSesiones}` WHERE sessionHash = ?");
        $statement->execute([UserSessions::hash($identifier)]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : [];
    };
    $filaPorSelector = function (string $selector) use ($database, $tablaSesiones): array {
        $statement = $database->prepare("SELECT * FROM `{$tablaSesiones}` WHERE refreshSelector = ?");
        $statement->execute([$selector]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : [];
    };
    $nulo = fn (array $row, string $columna): bool => array_key_exists($columna, $row) && $row[$columna] === null;
    $carga = function (string $jwt): array {
        $partes = explode('.', $jwt);
        $datos = isset($partes[1]) ? json_decode((string) base64_decode(strtr($partes[1], '-_', '+/')), true) : null;
        return is_array($datos) ? $datos : [];
    };
    $claveEC = function (string $curva): string {
        $privada = openssl_pkey_new(['private_key_type' => \OPENSSL_KEYTYPE_EC, 'curve_name' => $curva]);
        $detalles = $privada !== false ? openssl_pkey_get_details($privada) : false;
        $pem = is_array($detalles) ? (string) ($detalles['key'] ?? '') : '';
        return (string) preg_replace('/-----(BEGIN|END) PUBLIC KEY-----|\s+/', '', $pem);
    };

    $opciones = [
        SessionToken::INACTIVITY_CONFIG, SessionToken::CEILING_CONFIG,
        SessionToken::APP_ACCESS_LIFETIME_CONFIG, SessionToken::APP_INACTIVITY_CONFIG, SessionToken::APP_CEILING_CONFIG,
    ];
    $previas = [];
    foreach ($opciones as $opcion) {
        $previas[$opcion] = get_config($opcion);
    }

    try {
        //──── 1. La vida ──────────────────────────────────────────────────────────────────────────
        echoTerminal('[1/3] La vida: la web como antes; la app, con sus tres opciones');
        $ahora = time();
        $esperadoWeb = function (int $actividad, int $creada): array {
            $inactividad = SessionToken::inactivity();
            $tope = SessionToken::ceiling();
            $vida = $tope > 0 ? $tope : UserSessions::SESSION_TOKEN_NO_CEILING;
            $limites = [];
            if ($inactividad > 0) {
                $limites[] = $actividad + $inactividad;
            }
            if ($tope > 0) {
                $limites[] = $creada + $tope;
            }
            return ['vida' => $vida, 'caduca' => $limites !== [] ? min($limites) : $creada + UserSessions::SESSION_TOKEN_NO_CEILING, 'techo' => $creada + $vida];
        };
        $todoIgual = true;
        $detalle = [];
        foreach ([[100, 1000], [0, 0], [3600, 0], [0, 500], [31 * 86400, 365 * 86400]] as [$inactividad, $tope]) {
            set_config(SessionToken::INACTIVITY_CONFIG, $inactividad);
            set_config(SessionToken::CEILING_CONFIG, $tope);
            //Las de app, con valores que no deben notarse en la web.
            set_config(SessionToken::APP_ACCESS_LIFETIME_CONFIG, 900);
            set_config(SessionToken::APP_INACTIVITY_CONFIG, 7);
            set_config(SessionToken::APP_CEILING_CONFIG, 11);
            $creada = $ahora - 950;
            $esperado = $esperadoWeb($ahora, $creada);
            $filaWeb = ['createdAt' => date('Y-m-d H:i:s', $creada), 'kind' => UserSessions::KIND_WEB];
            $filaSinTipo = ['createdAt' => date('Y-m-d H:i:s', $creada)];
            $obtenido = [
                UserSessions::tokenLifetime(), UserSessions::tokenLifetime(UserSessions::KIND_WEB),
                UserSessions::expiryAt($ahora, $creada), UserSessions::expiryAt($ahora, $creada, UserSessions::KIND_WEB),
                UserSessions::ceiling($filaWeb), UserSessions::ceiling($filaSinTipo),
            ];
            $igual = $obtenido === [$esperado['vida'], $esperado['vida'], $esperado['caduca'], $esperado['caduca'], $esperado['techo'], $esperado['techo']];
            $todoIgual = $todoIgual && $igual;
            if (!$igual) {
                $detalle[] = json_encode(['config' => [$inactividad, $tope], 'esperado' => $esperado, 'obtenido' => $obtenido]);
            }
        }
        $check($todoIgual, 'a1 DISCRIMINANTE: con cinco configuraciones, la vida, la caducidad y el techo de una fila web (y de una sin tipo) son los de la fórmula de siempre, y las opciones de app no se notan', implode(' | ', $detalle));

        set_config(SessionToken::INACTIVITY_CONFIG, 100);
        set_config(SessionToken::CEILING_CONFIG, 1000);
        set_config(SessionToken::APP_ACCESS_LIFETIME_CONFIG, 600);
        set_config(SessionToken::APP_INACTIVITY_CONFIG, 0);
        set_config(SessionToken::APP_CEILING_CONFIG, 0);
        $sinLimites = UserSessions::expiryAt($ahora, $ahora - 10, UserSessions::KIND_APP);
        $techoSinTope = UserSessions::ceiling(['createdAt' => date('Y-m-d H:i:s', $ahora - 10), 'kind' => UserSessions::KIND_APP]);
        set_config(SessionToken::APP_INACTIVITY_CONFIG, 7200);
        set_config(SessionToken::APP_CEILING_CONFIG, 9000);
        $conValores = UserSessions::expiryAt($ahora, $ahora - 8000, UserSessions::KIND_APP);
        $techoConTope = UserSessions::ceiling(['createdAt' => date('Y-m-d H:i:s', $ahora - 8000), 'kind' => UserSessions::KIND_APP]);
        $vidaApp = UserSessions::tokenLifetime(UserSessions::KIND_APP);
        set_config(SessionToken::APP_ACCESS_LIFETIME_CONFIG, 100);
        $vidaNoValida = UserSessions::tokenLifetime(UserSessions::KIND_APP);
        set_config(SessionToken::APP_ACCESS_LIFETIME_CONFIG, 600);
        $check($sinLimites === $ahora - 10 + UserSessions::SESSION_TOKEN_NO_CEILING && $techoSinTope === $ahora - 10 + UserSessions::SESSION_TOKEN_NO_CEILING
            && $conValores === min($ahora + 7200, $ahora - 8000 + 9000) && $techoConTope === $ahora - 8000 + 9000
            && $vidaApp === 600 && $vidaNoValida === SessionToken::APP_ACCESS_LIFETIME,
            'a2 una fila app usa las suyas: sin límites, inicio + SESSION_TOKEN_NO_CEILING; con valores, el mínimo; su token vive session_app_access_lifetime (un 100 no válido vale el de omisión)',
            json_encode([$sinLimites - $ahora, $techoSinTope - $ahora, $conValores - $ahora, $techoConTope - $ahora, $vidaApp, $vidaNoValida]) ?: '');
        echoTerminal(' ');

        //──── 2. La clave del dispositivo y abrir la sesión ─────────────────────────────────────
        echoTerminal('[2/3] La clave del dispositivo, openApp() e issueAppToken()');
        $p256 = $claveEC('prime256v1');
        $normalizada = UserSessions::normalizeDeviceKey($p256);
        $huella = $normalizada !== null ? UserSessions::deviceKeyFingerprint($normalizada) : '';
        $rsa = openssl_pkey_new(['private_key_type' => \OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 512]);
        $rsaDetalles = $rsa !== false ? openssl_pkey_get_details($rsa) : false;
        $rsaB64 = is_array($rsaDetalles) ? (string) preg_replace('/-----(BEGIN|END) PUBLIC KEY-----|\s+/', '', (string) ($rsaDetalles['key'] ?? '')) : '';
        $p384 = $claveEC('secp384r1');
        $rechazos = [];
        $sinExcepcion = true;
        foreach (['rsa' => $rsaB64, 'p384' => $p384, 'basura' => 'esto-no-es-base64!!', 'azar' => base64_encode(random_bytes(90)), 'larga' => str_repeat('A', 204), 'vacia' => ''] as $nombre => $entrada) {
            try {
                $rechazos[$nombre] = UserSessions::normalizeDeviceKey($entrada);
            } catch (\Throwable $e) {
                $sinExcepcion = false;
                $rechazos[$nombre] = get_class($e);
            }
        }
        $check($normalizada !== null && UserSessions::normalizeDeviceKey($p256) === $normalizada && preg_match('/^[0-9a-f]{16}$/', $huella) === 1
            && strlen($rsaB64) > 0 && strlen($rsaB64) <= 200 && strlen($p384) <= 200
            && $sinExcepcion && array_filter($rechazos, fn ($valor): bool => $valor !== null) === [],
            'a3 normalizeDeviceKey: una P-256 pasa (siempre igual) y su huella son 16 hex; RSA y secp384r1 de menos de 200 caracteres, basura, azar, una de 204 y la vacía dan null sin excepción',
            json_encode(['huella' => $huella, 'rsa' => strlen($rsaB64), 'p384' => strlen($p384), 'rechazos' => $rechazos]) ?: '');

        $usuario = $crearUsuario('app');
        $primera = UserSessions::openApp($usuario, (string) $normalizada);
        $filaPrimera = $fila($primera['identifier']);
        [$selector, $verificador] = array_pad(explode('.', $primera['refreshToken'], 2), 2, '');
        $pendiente = Sealed::open((string) ($filaPrimera['refreshPending'] ?? ''), Sealed::PURPOSE_SESSION_REFRESH_PENDING);
        $check(($filaPrimera['kind'] ?? null) === UserSessions::KIND_APP && preg_match('/^[A-Za-z0-9_-]{22}$/', $selector) === 1 && preg_match('/^[A-Za-z0-9_-]{43}$/', $verificador) === 1
            && ($filaPrimera['refreshSelector'] ?? null) === $selector && ($filaPrimera['refreshHash'] ?? null) === hash('sha256', $verificador)
            && $pendiente === $verificador && $nulo($filaPrimera, 'refreshPrevHash') && $nulo($filaPrimera, 'refreshLastTimestamp')
            && ($filaPrimera['deviceKey'] ?? null) === $normalizada,
            'a4 openApp: la fila nace app, con selector de 22, refreshHash = sha256 del verificador, refreshPending se abre al verificador y prevHash y lastTimestamp nulos',
            json_encode(['kind' => $filaPrimera['kind'] ?? null, 'selector' => strlen($selector), 'verificador' => strlen($verificador), 'pendiente' => $pendiente === $verificador]) ?: '');

        $segunda = UserSessions::openApp($usuario, (string) $normalizada);
        $primeraDespues = $fila($primera['identifier']);
        $segundaFila = $fila($segunda['identifier']);
        $otraClave = (string) UserSessions::normalizeDeviceKey($claveEC('prime256v1'));
        $tercera = UserSessions::openApp($usuario, $otraClave);
        $segundaTrasOtra = $fila($segunda['identifier']);
        $terceraFila = $fila($tercera['identifier']);
        $check(($primeraDespues['closedAt'] ?? null) !== null && ($primeraDespues['closeReason'] ?? null) === UserSessions::REASON_REPLACED
            && $nulo($segundaFila, 'closedAt') && $nulo($segundaTrasOtra, 'closedAt') && $nulo($terceraFila, 'closedAt')
            && $filaPorSelector($selector) !== [],
            'a4b dos openApp con la misma clave dejan la primera cerrada con «replaced» y la segunda abierta; con otra clave, las dos abiertas',
            json_encode(['primera' => [$primeraDespues['closedAt'] ?? null, $primeraDespues['closeReason'] ?? null], 'segunda' => $segundaTrasOtra['closedAt'] ?? null, 'tercera' => $terceraFila['closedAt'] ?? null]) ?: '');

        $emitido = UserSessions::issueAppToken($usuario, $otraClave);
        $datos = BaseToken::getData($emitido['token']);
        $sid = $datos instanceof \stdClass ? (string) ($datos->sid ?? '') : '';
        $claims = $carga($emitido['token']);
        $vida = isset($claims['iat'], $claims['exp']) ? (int) $claims['exp'] - (int) $claims['iat'] : -1;
        $check($sid !== '' && ($fila($sid)['kind'] ?? null) === UserSessions::KIND_APP && !array_key_exists('aud', $claims)
            && abs($vida - UserSessions::tokenLifetime(UserSessions::KIND_APP)) <= 2 && $emitido['accessTokenExpiresIn'] === UserSessions::tokenLifetime(UserSessions::KIND_APP)
            && abs($emitido['accessTokenExpiresAt'] - (time() + $emitido['accessTokenExpiresIn'])) <= 2 && preg_match('/^[A-Za-z0-9_-]{22}\.[A-Za-z0-9_-]{43}$/', $emitido['refreshToken']) === 1,
            'a5 issueAppToken: el JWT trae sid de una fila app, NO trae aud y vive tokenLifetime(KIND_APP); accessTokenExpiresIn es esa vida',
            json_encode(['sid' => $sid !== '', 'aud' => array_key_exists('aud', $claims), 'vida' => $vida, 'expiresIn' => $emitido['accessTokenExpiresIn']]) ?: '');
        echoTerminal(' ');

        //──── 3. renewExpired y la actividad ──────────────────────────────────────────────────────
        echoTerminal('[3/3] renewExpired solo renueva filas web; la actividad y extender de una app usan su techo');
        $web = UserSessions::open($usuario);
        $check(UserSessions::identifierIsRenewable($sid, $usuario) === false && UserSessions::identifierIsRenewable($web, $usuario) === true
            && UserSessions::identifierIsOpen($sid, $usuario) === true && UserSessions::identifierIsOpen($web, $usuario) === true,
            'a6 DISCRIMINANTE: identifierIsRenewable() da false para una fila app abierta y true para una web; identifierIsOpen() da true para las dos');

        //a6b: la rama de renewExpired de index.php pregunta identifierIsRenewable(), nunca identifierIsOpen().
        $llamadas = [];
        $tokens = token_get_all((string) file_get_contents(basepath('index.php')));
        foreach ($tokens as $i => $token) {
            if (is_array($token) && $token[0] === \T_STRING && in_array($token[1], ['identifierIsRenewable', 'identifierIsOpen'], true)) {
                $llamadas[] = $token[1];
            }
        }
        $check($llamadas === ['identifierIsRenewable'], 'a6b index.php llama a UserSessions::identifierIsRenewable() en renewExpired y a identifierIsOpen() en ningún sitio', json_encode($llamadas) ?: '');

        set_config(SessionToken::INACTIVITY_CONFIG, 100);
        set_config(SessionToken::CEILING_CONFIG, 365 * 86400);
        set_config(SessionToken::APP_INACTIVITY_CONFIG, 7200);
        set_config(SessionToken::APP_CEILING_CONFIG, 0);
        $filaApp = $fila($sid);
        $database->prepare("UPDATE `{$tablaSesiones}` SET lastActivityAt = ?, expiresAt = ? WHERE id = ?")
            ->execute([date('Y-m-d H:i:s', time() - 1000), date('Y-m-d H:i:s', time() + 60), (int) $filaApp['id']]);
        $entra = UserSessions::tokenIsValid((object) ['id' => $usuario, 'sid' => $sid], $usuario);
        $caduca = (int) strtotime((string) ($fila($sid)['expiresAt'] ?? ''));
        $extendidaApp = UserSessions::extend((int) $filaApp['id'], 400);
        $extendidaWeb = UserSessions::extend((int) ($fila($web)['id'] ?? 0), 400);
        $check($entra && abs($caduca - (time() + 7200)) <= 5 && $extendidaApp !== null && $extendidaWeb === null,
            'a7 la actividad de una fila app corre su caducidad con session_app_inactivity, y extender 400 días cabe en una app sin tope y no en una web con 365',
            json_encode(['entra' => $entra, 'caduca' => $caduca - time(), 'app' => $extendidaApp?->format('Y-m-d'), 'web' => $extendidaWeb?->format('Y-m-d')]) ?: '');
        echoTerminal(' ');
    } catch (\Throwable $e) {
        $check(false, 'e* el recorrido corre entero', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach ($previas as $opcion => $valor) {
            set_config($opcion, $valor);
        }
        try {
            $borradas = $limpiar();
            echoTerminal('   - limpieza: ' . ($borradas === [] ? 'nada' : json_encode($borradas)));
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
    }
    $final = $propio();
    $check($final === $inicial, 'z1 al acabar, lo de la suite en la base es lo del principio (nada): usuarios y sesiones', 'inicial ' . json_encode($inicial) . ', final ' . json_encode($final));

    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . ' PASADAS ');
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:AppSessions] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0 ? "La sesión de app se cumple ({$passed} comprobaciones)." : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription('La sesión de una app: su vida propia, abrirla con la clave del dispositivo y renewExpired solo para la web. Escribe usuarios y sesiones zz-agente-jpg- y los borra')->setEffects([CliActions::EFFECT_DATABASE])->register();
