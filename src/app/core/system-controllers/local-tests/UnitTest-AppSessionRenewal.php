<?php

//La sesión de una app (ADR 0092, R2): el acceso con la clave del dispositivo y la renovación con la llave de un solo uso.
//El acceso, la ruta y index.php por HTTP; la renovación también en proceso. Siembra zz-agente-jph- y al acabar lo borra.
//@areas nucleo

use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use Organizations\OrganizationsRoutes;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Crypto\AppKeyring;
use PiecesPHP\Core\Crypto\EncryptedDataRegistry;
use PiecesPHP\Core\Crypto\KeyRotation;
use PiecesPHP\Core\MaintenanceMode;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\AppSessionRenewal;
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\HelperController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\Controllers\UserSessionsController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use Terminal\Tasks\AppKeyCommands;

CliActions::make('unit-tests:core/app-session-renewal', function ($args) {

    echoTerminal('[TEST:AppSessionRenewal] Iniciando suite...', true, "\r\n", '33');
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
        echoTerminal(str_repeat('=', 80));
        echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . ' PASADAS ');
        echoTerminal(str_repeat('=', 80));
        echoTerminal('[TEST:AppSessionRenewal] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
        return ['success' => $failed === 0, 'message' => $failed === 0 ? "La sesión de app se renueva bien ({$passed} comprobaciones)." : "{$failed} comprobaciones fallaron."];
    };

    //La base HTTP, como core/user-sessions: el terminal no conoce la dirección servida.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'e0 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $ruta = function (string $nombre, array $parametros = []): string {
        $url = get_route($nombre, $parametros, true);
        return is_string($url) && $url !== '' ? '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/') : '';
    };
    $rutaAcceso = $ruta('users-login-request');
    $rutaVerificar = $ruta('users-verify-login-request');
    $rutaRenovar = $ruta('api-admin-users-actions', ['actionType' => 'renew-session']);
    if (!$check($rutaAcceso !== '' && $rutaVerificar !== '' && $rutaRenovar !== '', 'e1 existen el acceso, la verificación y la acción renew-session', $rutaRenovar)) {
        return $balance();
    }

    $cabeceraToken = SessionToken::headerName();
    //Devuelve [estado, cabeceras en minúscula, cuerpo decodificado]; con un token, en la cabecera de la sesión.
    $pedir = function (string $metodo, string $path, ?array $cuerpo, array $cabeceras = []) use ($base): array {
        $handle = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_HTTPHEADER => $cabeceras,
        ];
        if ($cuerpo !== null) {
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($cuerpo);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $respuesta = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $tamano = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        $respuesta = is_string($respuesta) ? $respuesta : '';
        $lineas = [];
        foreach (preg_split('/\r?\n/', substr($respuesta, 0, $tamano)) ?: [] as $linea) {
            if (str_contains($linea, ':')) {
                [$nombre, $valor] = explode(':', $linea, 2);
                $lineas[mb_strtolower(trim($nombre))][] = trim($valor);
            }
        }
        $json = json_decode(substr($respuesta, $tamano), true);
        return [$status, $lineas, is_array($json) ? $json : []];
    };
    $pedirJson = function (string $path, array $cuerpo) use ($base): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => (string) json_encode($cuerpo),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Requested-With: XMLHttpRequest'],
        ]);
        $respuesta = curl_exec($handle);
        $json = json_decode(is_string($respuesta) ? $respuesta : '', true);
        return [(int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), is_array($json) ? $json : []];
    };
    $verifica = fn (string $token): bool => ($pedir('POST', $rutaVerificar, [], ["{$cabeceraToken}: {$token}", 'X-Requested-With: XMLHttpRequest'])[2]['isAuth'] ?? null) === true;
    $acceder = function (string $usuario, string $clave, ?string $clave64) use ($pedir, $rutaAcceso): array {
        $cuerpo = ['username' => $usuario, 'password' => $clave];
        if ($clave64 !== null) {
            $cuerpo['deviceKey'] = $clave64;
        }
        return $pedir('POST', $rutaAcceso, $cuerpo, ['isExternalLogin: yes', 'X-Requested-With: XMLHttpRequest']);
    };
    $renovarEnProceso = function (array $cuerpo): array {
        $request = new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-renovar'), new Headers(), [], [], (new StreamFactory())->createStream(''));
        $request = $request->withParsedBody($cuerpo);
        $respuesta = AppSessionRenewal::handle($request instanceof RequestRoute ? $request : throw new \LogicException('sin RequestRoute'), new ResponseRoute());
        $json = json_decode((string) $respuesta->getBody(), true);
        return [$respuesta->getStatusCode(), $respuesta->getHeaderLine('Cache-Control'), is_array($json) ? $json : []];
    };

    //Claves P-256 de la prueba, su SPKI en base64 (sin comprimir y comprimida) y la firma r||s de un mensaje.
    $nuevaClave = function (): array {
        $privada = openssl_pkey_new(['private_key_type' => \OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $detalles = $privada !== false ? openssl_pkey_get_details($privada) : false;
        $x = is_array($detalles) ? str_pad((string) $detalles['ec']['x'], 32, "\0", \STR_PAD_LEFT) : '';
        $y = is_array($detalles) ? str_pad((string) $detalles['ec']['y'], 32, "\0", \STR_PAD_LEFT) : '';
        $sinComprimir = base64_encode(hex2bin(UserSessions::P256_SPKI_PREFIX) . "\x04" . $x . $y);
        $comprimida = base64_encode(hex2bin('3039301306072a8648ce3d020106082a8648ce3d030107032200') . ((ord($y[31]) & 1) === 1 ? "\x03" : "\x02") . $x);
        return ['privada' => $privada, 'b64' => $sinComprimir, 'comprimida' => $comprimida, 'x963' => base64_encode("\x04" . $x . $y)];
    };
    $firmar = function ($privada, string $mensaje): string {
        $der = '';
        openssl_sign($mensaje, $der, $privada, \OPENSSL_ALGO_SHA256);
        //DER → r||s: dos INTEGER, cada uno a 32 bytes.
        $posicion = 2;
        $partes = [];
        for ($i = 0; $i < 2; $i++) {
            $largo = ord($der[$posicion + 1]);
            $partes[] = str_pad(ltrim(substr($der, $posicion + 2, $largo), "\0"), 32, "\0", \STR_PAD_LEFT);
            $posicion += 2 + $largo;
        }
        return base64_encode($partes[0] . $partes[1]);
    };
    $cuerpoRenovar = fn ($privada, string $llave, int $momento): array => [
        'refreshToken' => $llave,
        'timestamp' => (string) $momento,
        'signature' => $firmar($privada, AppSessionRenewal::SIGNED_PREFIX . $llave . '|' . $momento),
    ];

    $tablaUsuarios = UsersModel::TABLE;
    $tablaSesiones = UserSessions::TABLE;
    $tablaIntentos = LoginAttemptsModel::TABLE;
    $tablaRegistro = LogsMapper::TABLE;
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $marca = 'zz-agente-jph-';
    $prefijo = $marca . bin2hex(random_bytes(3));
    $contar = function (string $sql, array $valores = []) use ($database): int {
        $statement = $database->prepare($sql);
        $statement->execute($valores);
        return (int) $statement->fetchColumn();
    };
    $fila = function (int $id) use ($database, $tablaSesiones): array {
        $statement = $database->prepare("SELECT * FROM `{$tablaSesiones}` WHERE id = ?");
        $statement->execute([$id]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : [];
    };
    $filaDeToken = function (string $jwt) use ($database, $tablaSesiones): array {
        $datos = \PiecesPHP\Core\BaseToken::getData($jwt);
        $sid = $datos instanceof \stdClass ? (string) ($datos->sid ?? '') : '';
        $statement = $database->prepare("SELECT * FROM `{$tablaSesiones}` WHERE sessionHash = ?");
        $statement->execute([UserSessions::hash($sid)]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : [];
    };
    $carga = function (string $jwt): array {
        $partes = explode('.', $jwt);
        $datos = isset($partes[1]) ? json_decode((string) base64_decode(strtr($partes[1], '-_', '+/')), true) : null;
        return is_array($datos) ? $datos : [];
    };
    $propio = fn (): array => [
        'usuarios' => $contar("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE ?", ["{$marca}%"]),
        'sesiones' => $contar("SELECT COUNT(*) FROM `{$tablaSesiones}` s JOIN `{$tablaUsuarios}` u ON u.id = s.userID WHERE u.username LIKE ?", ["{$marca}%"]),
        'intentos' => $contar("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE usernameAttempt LIKE ?", ["{$marca}%"]),
        'organizaciones' => $contar("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE ?", ["{$marca}%"]),
    ];
    $limpiar = function () use ($database, $tablaUsuarios, $tablaSesiones, $tablaIntentos, $tablaRegistro, $tablaOrganizaciones, $marca): array {
        $borradas = [];
        $ejecutar = function (string $clave, string $sql, array $valores) use ($database, &$borradas): void {
            $statement = $database->prepare($sql);
            $statement->execute($valores);
            $borradas[$clave] = ($borradas[$clave] ?? 0) + $statement->rowCount();
        };
        $ids = $database->prepare("SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE ?");
        $ids->execute(["{$marca}%"]);
        $ids = array_map('intval', $ids->fetchAll(\PDO::FETCH_COLUMN));
        $ejecutar('intentos', "DELETE FROM `{$tablaIntentos}` WHERE usernameAttempt LIKE ?", ["{$marca}%"]);
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '{$tablaUsuarios}' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            $ejecutar('sesiones', "DELETE FROM `{$tablaSesiones}` WHERE userID = ?", [$id]);
            $ejecutar('intentos', "DELETE FROM `{$tablaIntentos}` WHERE userID = ?", [$id]);
            $ejecutar('registro', "DELETE FROM `{$tablaRegistro}` WHERE createdBy = ? OR (referenceSource = ? AND referenceValue = ?)", [$id, $tablaUsuarios, (string) $id]);
            $ejecutar('aprobaciones', 'DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?', [$tablaUsuarios, (string) $id]);
            $ejecutar('perfiles', 'DELETE FROM `user_system_profile` WHERE belongsTo = ?', [$id]);
            foreach ($referencias as [$tabla, $columna]) {
                $ejecutar('referencias', "DELETE FROM `{$tabla}` WHERE `{$columna}` = ?", [$id]);
            }
            $ejecutar('usuarios', "DELETE FROM `{$tablaUsuarios}` WHERE id = ?", [$id]);
        }
        $ejecutar('organizaciones', "DELETE FROM `{$tablaOrganizaciones}` WHERE name LIKE ?", ["{$marca}%"]);
        return array_filter($borradas);
    };
    $restos = $limpiar();
    if ($restos !== []) {
        echoTerminal('[0] Restos de una pasada anterior, borrados al empezar: ' . json_encode($restos));
    }
    $inicial = $propio();
    $check(array_sum($inicial) === 0, 'e2 al empezar no hay nada de la suite en la base', json_encode($inicial) ?: '');

    $clave = 'zz-Clave-' . bin2hex(random_bytes(8));
    $crearUsuario = function (string $sufijo, int $tipo = UsersModel::TYPE_USER_GENERAL) use ($prefijo, $clave): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.invalid";
        $u->password = password_hash($clave, \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'App';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        return (int) $u->id;
    };
    //Una sesión de app abierta en proceso, con su clave: para los casos que no prueban el acceso.
    $sesionApp = function (int $userID) use ($nuevaClave): array {
        $clave = $nuevaClave();
        $emitida = UserSessions::issueAppToken($userID, (string) UserSessions::normalizeDeviceKey($clave['b64']));
        return $emitida + ['privada' => $clave['privada']];
    };

    $configPrevia = [MaintenanceMode::ENABLED_CONFIG => get_config(MaintenanceMode::ENABLED_CONFIG), MaintenanceMode::ALLOWED_ROLES_CONFIG => get_config(MaintenanceMode::ALLOWED_ROLES_CONFIG)];
    $marcaGlobal = new \ReflectionProperty(SessionToken::class, 'minimumDateCreated');
    $marcaGlobalPrevia = $marcaGlobal->getValue();
    $llaveroIlegible = new \ReflectionProperty(Config::class, 'keyringUnreadable');
    $llaveroPrevio = $llaveroIlegible->getValue();
    $ahora = time();

    try {
        //──── 1. El acceso ────────────────────────────────────────────────────────────────────────
        echoTerminal('[1/6] El acceso de una app con su clave de dispositivo (por HTTP)');
        $usuarioA = $crearUsuario('a');
        $nombreA = "{$prefijo}-a";
        $claveA = $nuevaClave();
        [$estado, $cabeceras, $cuerpo] = $acceder($nombreA, $clave, $claveA['b64']);
        $tokenA0 = (string) ($cuerpo['token'] ?? '');
        $llaveA0 = (string) ($cuerpo['refreshToken'] ?? '');
        $filaA = $filaDeToken($tokenA0);
        $claims = $carga($tokenA0);
        $check($estado === 200 && ($cuerpo['auth'] ?? null) === true && preg_match('/^[A-Za-z0-9_-]{22}\.[A-Za-z0-9_-]{43}$/', $llaveA0) === 1
            && is_int($cuerpo['accessTokenExpiresAt'] ?? null) && ($cuerpo['accessTokenExpiresIn'] ?? null) === UserSessions::tokenLifetime(UserSessions::KIND_APP)
            && ($filaA['kind'] ?? null) === UserSessions::KIND_APP && ($filaA['deviceKey'] ?? null) === UserSessions::normalizeDeviceKey($claveA['b64'])
            && !array_key_exists('aud', $claims) && (int) ($claims['exp'] ?? 0) - (int) ($claims['iat'] ?? 0) === UserSessions::tokenLifetime(UserSessions::KIND_APP)
            && !isset($cabeceras['set-cookie']) && in_array('no-store', $cabeceras['cache-control'] ?? [], true),
            'l1 DISCRIMINANTE: con deviceKey, fila app con la clave canónica, los cuatro campos, el JWT sin aud y con la vida de app, SIN Set-Cookie aunque llegue X-Requested-With, y Cache-Control: no-store',
            json_encode(['estado' => $estado, 'error' => $cuerpo['error'] ?? null, 'kind' => $filaA['kind'] ?? null, 'aud' => array_key_exists('aud', $claims), 'cookie' => isset($cabeceras['set-cookie']), 'cache' => $cabeceras['cache-control'] ?? null]) ?: '');

        $usuarioW = $crearUsuario('web');
        [$estado, $cabeceras, $cuerpo] = $acceder("{$prefijo}-web", $clave, null);
        $tokenW = (string) ($cuerpo['token'] ?? '');
        $filaW = $filaDeToken($tokenW);
        $conAud = (bool) get_config('check_aud_on_auth');
        $check($estado === 200 && ($cuerpo['auth'] ?? null) === true && !isset($cuerpo['refreshToken']) && ($filaW['kind'] ?? null) === UserSessions::KIND_WEB
            && array_key_exists('aud', $carga($tokenW)) === $conAud && isset($cabeceras['set-cookie']),
            'l2 sin deviceKey, idéntico a hoy: fila web, aud según check_aud_on_auth, sin refreshToken y con la cookie (X-Requested-With)',
            json_encode(['estado' => $estado, 'kind' => $filaW['kind'] ?? null, 'aud' => array_key_exists('aud', $carga($tokenW)), 'esperaAud' => $conAud, 'cookie' => isset($cabeceras['set-cookie'])]) ?: '');

        $rsa = openssl_pkey_new(['private_key_type' => \OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 512]);
        $rsaDetalles = $rsa !== false ? openssl_pkey_get_details($rsa) : false;
        $p384 = openssl_pkey_new(['private_key_type' => \OPENSSL_KEYTYPE_EC, 'curve_name' => 'secp384r1']);
        $p384Detalles = $p384 !== false ? openssl_pkey_get_details($p384) : false;
        $pemABase64 = fn ($detalles): string => is_array($detalles) ? (string) preg_replace('/-----(BEGIN|END) PUBLIC KEY-----|\s+/', '', (string) ($detalles['key'] ?? '')) : '';
        $filasAntes = $contar("SELECT COUNT(*) FROM `{$tablaSesiones}` WHERE userID = ?", [$usuarioA]);
        $errores = [];
        foreach (['rsa' => $pemABase64($rsaDetalles), 'p384' => $pemABase64($p384Detalles), 'basura' => 'esto-no-es-una-clave', 'x963' => $claveA['x963'], 'cola' => base64_encode((string) base64_decode($claveA['b64']) . "\x00")] as $nombre => $entrada) {
            [, , $cuerpo] = $acceder($nombreA, $clave, $entrada);
            $errores[$nombre] = [$cuerpo['error'] ?? null, $cuerpo['auth'] ?? null, isset($cuerpo['token']) && $cuerpo['token'] !== ''];
        }
        $filasDespues = $contar("SELECT COUNT(*) FROM `{$tablaSesiones}` WHERE userID = ?", [$usuarioA]);
        $check(array_filter($errores, fn (array $e): bool => $e !== [UsersController::INVALID_DEVICE_KEY, false, false]) === [] && $filasDespues === $filasAntes,
            'l3 deviceKey inválida (RSA, P-384, basura, X9.63 pelado y una SPKI con un byte de más) da INVALID_DEVICE_KEY, sin token y sin fila nueva',
            json_encode(['errores' => $errores, 'filas' => [$filasAntes, $filasDespues]]) ?: '');

        $usuarioC = $crearUsuario('comprimida');
        $claveC = $nuevaClave();
        [, , $primera] = $acceder("{$prefijo}-comprimida", $clave, $claveC['comprimida']);
        [, , $segunda] = $acceder("{$prefijo}-comprimida", $clave, $claveC['b64']);
        $filaC1 = $filaDeToken((string) ($primera['token'] ?? ''));
        $filaC2 = $filaDeToken((string) ($segunda['token'] ?? ''));
        $check(($filaC1['deviceKey'] ?? 'a') === ($filaC2['deviceKey'] ?? 'b') && UserSessions::deviceKeyFingerprint((string) ($filaC1['deviceKey'] ?? '')) === UserSessions::deviceKeyFingerprint((string) UserSessions::normalizeDeviceKey($claveC['b64']))
            && ($fila((int) ($filaC1['id'] ?? 0))['closeReason'] ?? null) === UserSessions::REASON_REPLACED && array_key_exists('closedAt', $fila((int) ($filaC2['id'] ?? 0))) && $fila((int) ($filaC2['id'] ?? 0))['closedAt'] === null,
            'l4 la misma clave comprimida y sin comprimir da la misma clave guardada y la misma huella, y el segundo acceso cierra el primero con «replaced»',
            json_encode(['primera' => $primera['error'] ?? null, 'segunda' => $segunda['error'] ?? null, 'cierre' => $fila((int) ($filaC1['id'] ?? 0))['closeReason'] ?? null]) ?: '');
        echoTerminal(' ');

        //──── 2. Renovar ──────────────────────────────────────────────────────────────────────────
        echoTerminal('[2/6] Renovar: la actual rota, la anterior reenvía, otra cierra; firma, reloj y forma');
        $idA = (int) ($filaA['id'] ?? 0);
        $creadaA = (string) ($filaA['createdAt'] ?? '');
        [$estado, $cabeceras, $r1] = $pedir('POST', $rutaRenovar, $cuerpoRenovar($claveA['privada'], $llaveA0, $ahora), ['X-Requested-With: XMLHttpRequest']);
        $llaveA1 = (string) ($r1['refreshToken'] ?? '');
        $tokenA1 = (string) ($r1['token'] ?? '');
        $filaA1 = $fila($idA);
        [$verificadorA0] = array_slice(explode('.', $llaveA0), 1);
        $check($estado === 200 && ($r1['success'] ?? null) === true && $llaveA1 !== $llaveA0 && substr($llaveA1, 0, 22) === substr($llaveA0, 0, 22)
            && ($filaA1['refreshPrevHash'] ?? null) === hash('sha256', $verificadorA0) && in_array('no-store', $cabeceras['cache-control'] ?? [], true)
            && $verifica($tokenA1) && !$verifica($tokenA0),
            'r1 DISCRIMINANTE: por HTTP, renovar con la actual da 200, la llave cambia (mismo selector), prevHash es la huella vieja; el token nuevo entra y el ANTERIOR ya no (el sid rota)',
            json_encode(['estado' => $estado, 'error' => $r1['error'] ?? null, 'cambia' => $llaveA1 !== $llaveA0, 'nuevo' => $verifica($tokenA1), 'viejo' => $verifica($tokenA0)]) ?: '');
        $check((int) ($filaA1['id'] ?? 0) === $idA && ($filaA1['createdAt'] ?? null) === $creadaA,
            'r8 tras renovar, la fila conserva su id y su createdAt (la revocación por marca sigue comparando su nacimiento)');

        [$estado, , $r2] = $renovarEnProceso($cuerpoRenovar($claveA['privada'], $llaveA0, $ahora + 1));
        $tokenA2 = (string) ($r2['token'] ?? '');
        $check($estado === 200 && ($r2['refreshToken'] ?? null) === $llaveA1 && ($fila($idA)['refreshHash'] ?? null) === ($filaA1['refreshHash'] ?? 'x') && $verifica($tokenA2) && !$verifica($tokenA1),
            'r2 DISCRIMINANTE: la ANTERIOR tras r1 da 200 con la llave actual (la de r1), sin rotar; su token entra y el de r1 ya no',
            json_encode(['estado' => $estado, 'error' => $r2['error'] ?? null, 'misma' => ($r2['refreshToken'] ?? null) === $llaveA1]) ?: '');

        $antes = $fila($idA);
        $firmaMala = $cuerpoRenovar($claveA['privada'], $llaveA1, $ahora + 2);
        $bytes = (string) base64_decode($firmaMala['signature']);
        $bytes[10] = chr(ord($bytes[10]) ^ 0x01);
        $firmaMala['signature'] = base64_encode($bytes);
        $otraClave = $nuevaClave();
        [$e1, , $c1] = $renovarEnProceso($firmaMala);
        [$e2, , $c2] = $renovarEnProceso($cuerpoRenovar($otraClave['privada'], $llaveA1, $ahora + 2));
        $check($e1 === 401 && ($c1['error'] ?? null) === AppSessionRenewal::ERROR_REAUTH_REQUIRED && $e2 === 401 && $fila($idA) === $antes,
            'r4 DISCRIMINANTE: una firma con un byte cambiado y una de otra clave dan 401 REAUTH_REQUIRED y la fila queda INTACTA (sin la clave no se cierra nada)',
            json_encode([$e1, $c1['error'] ?? null, $e2, 'intacta' => $fila($idA) === $antes]) ?: '');

        [$e1, , $c1] = $renovarEnProceso($cuerpoRenovar($claveA['privada'], $llaveA1, time() + 301));
        [$e2, , $c2] = $renovarEnProceso($cuerpoRenovar($claveA['privada'], $llaveA1, time() - 301));
        $check($e1 === 409 && $e2 === 409 && ($c1['error'] ?? null) === AppSessionRenewal::ERROR_CLOCK_SKEW && is_int($c1['serverTime'] ?? null) && is_int($c2['serverTime'] ?? null) && $fila($idA) === $antes,
            'r5 el reloj a +301 y a −301 da 409 CLOCK_SKEW con serverTime, y la fila no cambia', json_encode([$e1, $c1, $e2]) ?: '');

        [$e1, , $c1] = $renovarEnProceso($cuerpoRenovar($claveA['privada'], $llaveA1, $ahora + 3));
        $llaveA2 = (string) ($c1['refreshToken'] ?? '');
        [$e2, , $c2] = $renovarEnProceso($cuerpoRenovar($claveA['privada'], $llaveA2, $ahora + 3));
        $check($e1 === 200 && $e2 === 409 && ($c2['error'] ?? null) === AppSessionRenewal::ERROR_RETRY,
            'r6 DISCRIMINANTE: el mismo timestamp dos veces: la primera renueva y la segunda da 409 RETRY', json_encode([$e1, $e2, $c2['error'] ?? null]) ?: '');

        $formas = [
            'llave corta' => ['refreshToken' => 'abc.def', 'timestamp' => (string) time(), 'signature' => base64_encode(str_repeat('a', 64))],
            'selector de 21' => ['refreshToken' => substr($llaveA2, 1), 'timestamp' => (string) time(), 'signature' => base64_encode(str_repeat('a', 64))],
            'firma de 63' => ['refreshToken' => $llaveA2, 'timestamp' => (string) time(), 'signature' => base64_encode(str_repeat('a', 63))],
            'timestamp con letras' => ['refreshToken' => $llaveA2, 'timestamp' => '17642000a0', 'signature' => base64_encode(str_repeat('a', 64))],
            'sin firma' => ['refreshToken' => $llaveA2, 'timestamp' => (string) time()],
        ];
        $resultados = [];
        foreach ($formas as $nombre => $cuerpo) {
            $resultados[$nombre] = $renovarEnProceso($cuerpo)[0];
        }
        $porGet = $pedir('GET', $rutaRenovar, null, ['X-Requested-With: XMLHttpRequest'])[0];
        $check(array_unique(array_values($resultados)) === [400] && $porGet === 404,
            'r7 la forma: llave corta, selector de 21, firma de 63 bytes, timestamp con letras y sin firma dan 400; por GET, 404', json_encode($resultados + ['GET' => $porGet]) ?: '');

        //r6b: repetir una petición vieja ya usada (la de r1, su llave y su timestamp) no mata la sesión: 409 y la fila intacta.
        $antesRepeticion = $fila($idA);
        [$e1, , $c1] = $renovarEnProceso($cuerpoRenovar($claveA['privada'], $llaveA0, $ahora));
        $check($e1 === 409 && ($c1['error'] ?? null) === AppSessionRenewal::ERROR_RETRY && $fila($idA) === $antesRepeticion,
            'r6b DISCRIMINANTE: repetir la petición de r1 (llave vieja y timestamp ya usado) da 409 RETRY y la fila queda intacta: no la cierra como reutilización',
            json_encode([$e1, $c1['error'] ?? null, 'cierre' => $fila($idA)['closeReason'] ?? null]) ?: '');

        $logsAntes = $contar("SELECT COUNT(*) FROM `{$tablaRegistro}` WHERE referenceSource = ? AND referenceValue = ? AND textMessage = ?", [$tablaUsuarios, (string) $usuarioA, LogsMapper::messages()[LogsMapper::MSG_SESSION_REFRESH_REUSE]]);
        [$e1, , $c1] = $renovarEnProceso($cuerpoRenovar($claveA['privada'], $llaveA0, $ahora + 4));
        $filaCerrada = $fila($idA);
        $logsDespues = $contar("SELECT COUNT(*) FROM `{$tablaRegistro}` WHERE referenceSource = ? AND referenceValue = ? AND textMessage = ?", [$tablaUsuarios, (string) $usuarioA, LogsMapper::messages()[LogsMapper::MSG_SESSION_REFRESH_REUSE]]);
        $check($e1 === 401 && ($filaCerrada['closeReason'] ?? null) === UserSessions::REASON_REFRESH_REUSE && $logsDespues === $logsAntes + 1,
            'r3 DISCRIMINANTE: una llave más vieja que la anterior da 401, cierra la fila con «refresh-reuse» y deja su entrada en el registro', json_encode([$e1, $filaCerrada['closeReason'] ?? null, $logsAntes, $logsDespues]) ?: '');
        $check(array_key_exists('refreshPending', $filaCerrada) && $filaCerrada['refreshPending'] === null, 'h1a cerrar una fila deja refreshPending a NULL (H5 de R1)');
        echoTerminal(' ');

        //──── 3. Coherencia con index.php ─────────────────────────────────────────────────────────
        echoTerminal('[3/6] Coherencia: lo que index.php rechaza, la renovación también lo rechaza (y al revés)');
        $usuarioK = $crearUsuario('coherencia');
        $momento = $ahora + 10;
        $base0 = $sesionApp($usuarioK);
        [$eBase, , $cBase] = $renovarEnProceso($cuerpoRenovar($base0['privada'], $base0['refreshToken'], $momento++));
        $check($eBase === 200 && $verifica((string) ($cBase['token'] ?? '')), 'c7 CANARIO: sin revocación, renovar da 200 e index.php acepta el token nuevo', json_encode([$eBase, $cBase['error'] ?? null]) ?: '');
        $revocaciones = [
            'fila cerrada' => [fn (array $s) => UserSessions::close((int) $filaDeToken($s['token'])['id'], null, UserSessions::REASON_CLOSED_BY_ADMIN), fn () => null],
            'marca del usuario' => [fn () => $database->prepare("UPDATE `{$tablaUsuarios}` SET sessionsValidFrom = ? WHERE id = ?")->execute([date('Y-m-d H:i:s'), $usuarioK]), fn () => $database->prepare("UPDATE `{$tablaUsuarios}` SET sessionsValidFrom = NULL WHERE id = ?")->execute([$usuarioK])],
            'contraseña cambiada desde otra sesión' => [fn () => UserSessions::closeAfterPasswordChange($usuarioK), fn () => null],
            'usuario inactivo' => [fn () => $database->prepare("UPDATE `{$tablaUsuarios}` SET status = ? WHERE id = ?")->execute([UsersModel::STATUS_USER_INACTIVE, $usuarioK]), fn () => $database->prepare("UPDATE `{$tablaUsuarios}` SET status = ? WHERE id = ?")->execute([UsersModel::STATUS_USER_ACTIVE, $usuarioK])],
            'tipo que deja de ser rol' => [fn () => $database->prepare("UPDATE `{$tablaUsuarios}` SET type = 999 WHERE id = ?")->execute([$usuarioK]), fn () => $database->prepare("UPDATE `{$tablaUsuarios}` SET type = ? WHERE id = ?")->execute([UsersModel::TYPE_USER_GENERAL, $usuarioK])],
            'fila caducada' => [fn (array $s) => $database->prepare("UPDATE `{$tablaSesiones}` SET expiresAt = ? WHERE id = ?")->execute([date('Y-m-d H:i:s', time() - 5), (int) $filaDeToken($s['token'])['id']]), fn () => null],
        ];
        if (OrganizationsRoutes::enabled()) {
            $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-org", 'zz-', "{$prefijo}-org", date('Y-m-d H:i:s'), $usuarioK, OrganizationMapper::ACTIVE, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
            $organizacion = (int) $database->lastInsertId();
            $revocaciones['organización sin sesión'] = [
                function () use ($database, $tablaUsuarios, $tablaOrganizaciones, $organizacion, $usuarioK): void {
                    $database->prepare("UPDATE `{$tablaUsuarios}` SET organization = ? WHERE id = ?")->execute([$organizacion, $usuarioK]);
                    $database->prepare("UPDATE `{$tablaOrganizaciones}` SET status = ? WHERE id = ?")->execute([OrganizationMapper::INACTIVE, $organizacion]);
                },
                fn () => $database->prepare("UPDATE `{$tablaUsuarios}` SET organization = ? WHERE id = ?")->execute([OrganizationMapper::INITIAL_ID_GLOBAL, $usuarioK]),
            ];
        } else {
            echoTerminal('      - NO APLICA: «organización sin sesión», con el módulo de organizaciones apagado');
        }
        $coherentes = [];
        foreach ($revocaciones as $nombre => [$revocar, $restaurar]) {
            $s = $sesionApp($usuarioK);
            $revocar($s);
            [$estado, , $c] = $renovarEnProceso($cuerpoRenovar($s['privada'], $s['refreshToken'], $momento++));
            $entra = $verifica($s['token']);
            $restaurar();
            $coherentes[$nombre] = [$estado, $c['error'] ?? null, $entra];
        }
        $check(array_filter($coherentes, fn (array $r): bool => $r !== [401, AppSessionRenewal::ERROR_REAUTH_REQUIRED, false]) === [],
            'c7 DISCRIMINANTE: con cada revocación por fila o por usuario (' . implode(', ', array_keys($coherentes)) . ') la renovación da 401 e index.php rechaza el token de esa fila',
            json_encode($coherentes, JSON_UNESCAPED_UNICODE) ?: '');

        //La marca global, en proceso: moverla en la base echaría a toda la instalación.
        $sg = $sesionApp($usuarioK);
        $iatGlobal = (int) ($carga($sg['token'])['iat'] ?? 0);
        SessionToken::setMinimumDateCreated(new \DateTime(date('Y-m-d H:i:s', time())));
        [$estado, , $c] = $renovarEnProceso($cuerpoRenovar($sg['privada'], $sg['refreshToken'], $momento++));
        $indexAceptaria = SessionToken::isCreatedAfterMarks($iatGlobal, null);
        $marcaGlobal->setValue(null, $marcaGlobalPrevia);
        $check($estado === 401 && $indexAceptaria === false, 'c7b la marca global (en proceso): la renovación da 401 y la regla de index.php (isCreatedAfterMarks con el iat del token) también lo rechaza', json_encode([$estado, $c['error'] ?? null, $indexAceptaria]) ?: '');

        //DIVERGENCIA DECLARADA (#3364): index.php no mira hasExternalLogin; la ventana es como mucho la vida del acceso de app.
        $sx = $sesionApp($usuarioK);
        $database->prepare("UPDATE `{$tablaUsuarios}` SET type = ? WHERE id = ?")->execute([UsersModel::TYPE_USER_ADMIN_GRAL, $usuarioK]);
        [$estado, , $c] = $renovarEnProceso($cuerpoRenovar($sx['privada'], $sx['refreshToken'], $momento++));
        $entraSinExterno = $verifica($sx['token']);
        $database->prepare("UPDATE `{$tablaUsuarios}` SET type = ? WHERE id = ?")->execute([UsersModel::TYPE_USER_GENERAL, $usuarioK]);
        $check($estado === 401 && ($c['error'] ?? null) === AppSessionRenewal::ERROR_REAUTH_REQUIRED, 'c7c un tipo sin hasExternalLogin: la renovación da 401', json_encode([$estado, $c['error'] ?? null]) ?: '');
        echoTerminal('      - DIVERGENCIA DECLARADA: index.php ' . ($entraSinExterno ? 'ACEPTA' : 'rechaza') . ' ese token (no mira hasExternalLogin); la ventana es como mucho la vida del acceso de app (' . UserSessions::tokenLifetime(UserSessions::KIND_APP) . ' s)');

        //Mantenimiento, en memoria.
        $sm = $sesionApp($usuarioK);
        $antesM = $filaDeToken($sm['token']);
        set_config(MaintenanceMode::ENABLED_CONFIG, true);
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, [UsersModel::TYPE_USER_ROOT]);
        [$estado, , $c] = $renovarEnProceso($cuerpoRenovar($sm['privada'], $sm['refreshToken'], $momento++));
        foreach ($configPrevia as $opcion => $valor) {
            set_config($opcion, $valor);
        }
        $check($estado === 503 && ($c['error'] ?? null) === AppSessionRenewal::ERROR_MAINTENANCE && $fila((int) ($antesM['id'] ?? 0)) === $antesM,
            'm1 el mantenimiento que para su tipo da 503 MAINTENANCE y la fila queda intacta', json_encode([$estado, $c['error'] ?? null]) ?: '');

        //El llavero ilegible, en memoria (la marca de Config; secure-keys/ no se toca).
        $su = $sesionApp($usuarioK);
        $antesU = $filaDeToken($su['token']);
        $llaveroIlegible->setValue(null, true);
        [$estado, , $c] = $renovarEnProceso($cuerpoRenovar($su['privada'], $su['refreshToken'], $momento++));
        $llaveroIlegible->setValue(null, $llaveroPrevio);
        $check($estado === 503 && ($c['error'] ?? null) === AppSessionRenewal::ERROR_UNAVAILABLE && $fila((int) ($antesU['id'] ?? 0)) === $antesU,
            'u1 con el llavero ilegible, 503 UNAVAILABLE y la fila intacta', json_encode([$estado, $c['error'] ?? null]) ?: '');
        echoTerminal(' ');

        //──── 4. WebCrypto ────────────────────────────────────────────────────────────────────────
        echoTerminal('[4/6] Un vector real de WebCrypto (Chromium sin ventana, privada no extraíble)');
        $vectorSPKI = 'MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEUr8P/Tq4frc00Nv/HKeOdrjnDIyvXoRgMY2/Xwl98DfuwLzkpY77eUd97vtgNPs5A9NtUDvm4PkBtcO3xGgcfA==';
        $vectorMensaje = 'pcsphp-renew-session-v1|AAAAAAAAAAAAAAAAAAAAAA.BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB|1764200000';
        $vectorFirma = 'kf079wmByvmylby+CFUUWarmU1fq8PfLf3by5MLsJ7fH/LVGigNavniYMueNwGB0hPje2QTSSbb2ca6PYDyRgQ==';
        $pem = (string) UserSessions::normalizeDeviceKey($vectorSPKI);
        $cruda = (string) base64_decode($vectorFirma);
        $alterada = $cruda;
        $alterada[40] = chr(ord($alterada[40]) ^ 0x01);
        $check($pem !== '' && AppSessionRenewal::signatureIsValid($vectorMensaje, $cruda, $pem) && !AppSessionRenewal::signatureIsValid($vectorMensaje, $alterada, $pem) && !AppSessionRenewal::signatureIsValid($vectorMensaje . 'x', $cruda, $pem),
            'w1 la firma r||s de WebCrypto verifica; con un bit cambiado o con otro mensaje, no');
        echoTerminal(' ');

        //──── 5. Lo de R1 ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[5/6] Los arreglos de R1: transacción, firmar antes de escribir y el tipo desconocido');
        $usuarioT = $crearUsuario('transaccion');
        $claveT = (string) UserSessions::normalizeDeviceKey($nuevaClave()['b64']);
        $primeraT = UserSessions::openApp($usuarioT, $claveT);
        $selectorUsado = substr($primeraT['refreshToken'], 0, 22);
        $material = UserSessions::appMaterial();
        $material['selector'] = $selectorUsado;
        $lanzo = false;
        try {
            UserSessions::openApp($usuarioT, $claveT, $material);
        } catch (\Throwable $e) {
            $lanzo = true;
        }
        $filaPrimeraT = $filaDeToken(SessionToken::generateToken(['id' => $usuarioT, 'sid' => $primeraT['identifier']], null, 60, false));
        $check($lanzo && array_key_exists('closedAt', $filaPrimeraT) && $filaPrimeraT['closedAt'] === null,
            'h1b si el alta falla (selector repetido), la transacción se deshace y la sesión anterior con la misma clave sigue abierta', json_encode(['lanzo' => $lanzo, 'cerrada' => $filaPrimeraT['closedAt'] ?? 'sin fila']) ?: '');

        //h1c: el orden de issueAppToken(), por tokens: firma (generateToken) antes de escribir (openApp).
        $codigo = (string) file_get_contents(basepath('app/classes/PiecesPHP/UserSystem/Authentication/UserSessions.php'));
        $cuerpoEmitir = (string) substr($codigo, (int) strpos($codigo, 'function issueAppToken('), 1500);
        $posFirma = strpos($cuerpoEmitir, 'SessionToken::generateToken(');
        $posEscritura = strpos($cuerpoEmitir, 'self::openApp(');
        $check($posFirma !== false && $posEscritura !== false && $posFirma < $posEscritura, 'h1c issueAppToken() firma el token ANTES de escribir la fila: un fallo al firmar no deja fila');

        $web = UserSessions::open($usuarioT);
        $database->prepare("UPDATE `{$tablaSesiones}` SET kind = 'xyz' WHERE sessionHash = ?")->execute([UserSessions::hash($web)]);
        $check(UserSessions::kindOf(['kind' => 'xyz']) === UserSessions::KIND_UNKNOWN && UserSessions::identifierIsRenewable($web, $usuarioT) === false && UserSessions::kindOf([]) === UserSessions::KIND_WEB,
            'h1d un tipo desconocido no es web: identifierIsRenewable() da false (falla cerrado); una fila sin la columna sigue siendo web');
        echoTerminal(' ');

        //──── 6. Ronda 3: la rotación de la clave, las listas, la llave anterior y el código de un uso ──────
        echoTerminal('[6/6] R3: rotar la clave cierra las sesiones de app; las listas; la anterior; el código de un uso; firmas y formas');
        $usuarioR = $crearUsuario('rotacion');
        $appR = $sesionApp($usuarioR);
        $filaAppR = $filaDeToken($appR['token']);
        $webR = UserSessions::open($usuarioR);
        $filaWebR = $filaDeToken(SessionToken::generateToken(['id' => $usuarioR, 'sid' => $webR], null, 60, false));
        $otrasApp = $contar("SELECT COUNT(*) FROM `{$tablaSesiones}` s JOIN `{$tablaUsuarios}` u ON u.id = s.userID WHERE s.kind = 'app' AND s.closedAt IS NULL AND s.expiresAt > NOW() AND u.username NOT LIKE ?", ["{$marca}%"]);
        $sitioSesion = ['session-refresh-pending' => EncryptedDataRegistry::all()['session-refresh-pending']];
        $actual = AppKeyring::get();
        $temporal = sys_get_temp_dir() . '/zz-agente-jpi-' . bin2hex(random_bytes(4));
        $pdo = $database instanceof \PDO ? $database : throw new \LogicException('sin base');
        if (!mkdir($temporal, 0700)) {
            throw new \RuntimeException("No se pudo crear {$temporal}.");
        }
        try {
            //k1a: la rotación que falla (el llavero no se puede escribir) no cierra nada y no deja transacción abierta.
            $pendienteAntes = $fila((int) $filaAppR['id'])['refreshPending'] ?? null;
            $lanzo = false;
            try {
                (new KeyRotation($pdo, $sitioSesion))->rotate($actual, $actual->rotatedTo(AppKeyring::generateKey()), $temporal . '/no-existe/' . AppKeyring::FILE_NAME);
            } catch (\Throwable $e) {
                $lanzo = true;
            }
            $tras = $fila((int) $filaAppR['id']);
            $check($lanzo && array_key_exists('closedAt', $tras) && $tras['closedAt'] === null && ($tras['refreshPending'] ?? null) === $pendienteAntes && !$database->inTransaction(),
                'k1a si la rotación falla antes del commit, la sesión de app sigue ABIERTA y con su llave pendiente (rollback)');
            if ($otrasApp > 0) {
                echoTerminal("      - NO APLICA k1 y k1b: hay {$otrasApp} sesión(es) de app abiertas que no son de la suite; rotar las cerraría");
            } else {
                //k1: la rotación con un llavero temporal (secure-keys/ no se toca): cierra la de app y deja la web.
                $informe = (new KeyRotation($pdo, $sitioSesion))->rotate($actual, $actual->rotatedTo(AppKeyring::generateKey()), $temporal . '/' . AppKeyring::FILE_NAME);
                $appTras = $fila((int) $filaAppR['id']);
                $webTras = $fila((int) $filaWebR['id']);
                $check(($appTras['closeReason'] ?? null) === UserSessions::REASON_APP_KEY_ROTATED && array_key_exists('refreshPending', $appTras) && $appTras['refreshPending'] === null
                    && array_key_exists('closedAt', $webTras) && $webTras['closedAt'] === null && isset($informe['app-sessions']),
                    'k1 DISCRIMINANTE: rotar la clave (llavero temporal) cierra la sesión de app con «app-key-rotated» y refreshPending NULL, y deja abierta la web',
                    json_encode(['app' => $appTras['closeReason'] ?? null, 'web' => $webTras['closedAt'] ?? 'sin fila', 'informe' => $informe['app-sessions'] ?? null], JSON_UNESCAPED_UNICODE) ?: '');
                //k1b: generate-app-key, con un llavero temporal; el llavero de este proceso se repone.
                $appG = $sesionApp($usuarioR);
                try {
                    $lineas = AppKeyCommands::generate($temporal . '/generado-' . AppKeyring::FILE_NAME, $actual);
                } finally {
                    AppKeyring::set($actual);
                }
                $check(($filaDeToken($appG['token'])['closeReason'] ?? null) === UserSessions::REASON_APP_KEY_ROTATED && str_contains(implode(' ', $lineas), 'Sesiones de app cerradas'),
                    'k1b generate-app-key también cierra las sesiones de app', json_encode($lineas, JSON_UNESCAPED_UNICODE) ?: '');
            }
        } finally {
            foreach (glob($temporal . '/*') ?: [] as $archivo) {
                //RETORNO-IGNORADO: un llavero de prueba que no se borre queda en el temporal del sistema, nunca en secure-keys/.
                @unlink($archivo);
            }
            //RETORNO-IGNORADO: la carpeta temporal vacía no importa si no se borra.
            @rmdir($temporal);
        }

        //s1: las listas distinguen la de app y escapan.
        $filaAppS = ['kind' => UserSessions::KIND_APP, 'deviceKey' => (string) ($filaAppR['deviceKey'] ?? ''), 'userAgent' => 'Zz<script>zzMalo()</script> Chrome/120'];
        $filaWebS = ['kind' => UserSessions::KIND_WEB, 'deviceKey' => null, 'userAgent' => '<b>Firefox/1</b>'];
        $huella = UserSessions::deviceKeyFingerprint((string) ($filaAppR['deviceKey'] ?? ''));
        $celdaApp = UserSessionsController::browserCellHTML($filaAppS);
        $celdaWeb = UserSessionsController::browserCellHTML($filaWebS);
        $vista = (string) (new HelperController())->setInstanceViewDir(basepath('app/classes/PiecesPHP/UserSystem/Views/'))->render('panel/pages/sessions/user-list', [
            'langGroup' => UserSessionsController::LANG_GROUP,
            'rows' => array_map(fn (array $r): array => ['browser' => UserSessionsController::browserLabel($r), 'deviceKey' => UserSessionsController::deviceKeyLabel($r), 'ip' => '198.51.100.1', 'createdAt' => '', 'lastActivityAt' => '', 'expiresAt' => '', 'current' => false, 'actions' => ''], [$filaAppS, $filaWebS]),
            'mine' => false,
            'closeOthersURL' => '',
        ], false);
        $check(str_contains($celdaApp, 'App · ') && str_contains($celdaApp, $huella) && !str_contains($celdaApp, '<script') && !str_contains($celdaWeb, 'App · ') && !str_contains($celdaWeb, '<small')
            && str_contains($vista, 'App · ') && str_contains($vista, $huella) && substr_count($vista, '<small') === 1 && !str_contains($vista, '<script'),
            's1 la lista de administración y «mis sesiones» pintan «App ·» y la huella de su clave para la fila app, nada nuevo para la web, y escapan', $celdaApp . ' | ' . $celdaWeb);

        //b1 y b2: la llave anterior sobre una fila caducada, y con lo pendiente manipulado.
        $usuarioB = $crearUsuario('anterior');
        $sb = $sesionApp($usuarioB);
        [, , $rb] = $renovarEnProceso($cuerpoRenovar($sb['privada'], $sb['refreshToken'], $momento++));
        $idB = (int) ($filaDeToken((string) ($rb['token'] ?? ''))['id'] ?? 0);
        $database->prepare("UPDATE `{$tablaSesiones}` SET expiresAt = ? WHERE id = ?")->execute([date('Y-m-d H:i:s', time() - 5), $idB]);
        [$e1, , $c1] = $renovarEnProceso($cuerpoRenovar($sb['privada'], $sb['refreshToken'], $momento++));
        $check($e1 === 401 && ($c1['error'] ?? null) === AppSessionRenewal::ERROR_REAUTH_REQUIRED, 'b1 la llave anterior sobre una fila caducada da 401 REAUTH_REQUIRED', json_encode([$e1, $c1['error'] ?? null]) ?: '');
        $sb2 = $sesionApp($usuarioB);
        [, , $rb2] = $renovarEnProceso($cuerpoRenovar($sb2['privada'], $sb2['refreshToken'], $momento++));
        $idB2 = (int) ($filaDeToken((string) ($rb2['token'] ?? ''))['id'] ?? 0);
        $database->prepare("UPDATE `{$tablaSesiones}` SET refreshPending = ? WHERE id = ?")->execute(['v2:zz-manipulado', $idB2]);
        [$e2, , $c2] = $renovarEnProceso($cuerpoRenovar($sb2['privada'], $sb2['refreshToken'], $momento++));
        $check($e2 === 401 && ($c2['error'] ?? null) === AppSessionRenewal::ERROR_REAUTH_REQUIRED, 'b2 DISCRIMINANTE: la llave anterior con lo pendiente manipulado da 401 REAUTH_REQUIRED, no 409 (no se podrá reenviar nunca)', json_encode([$e2, $c2['error'] ?? null]) ?: '');

        //b3: la entrada de reutilización de r3 trae el id de la fila.
        $variables = $database->prepare("SELECT textMessageVariables FROM `{$tablaRegistro}` WHERE referenceSource = ? AND referenceValue = ? AND textMessage = ? ORDER BY id DESC LIMIT 1");
        $variables->execute([$tablaUsuarios, (string) $usuarioA, LogsMapper::messages()[LogsMapper::MSG_SESSION_REFRESH_REUSE]]);
        $variablesR3 = json_decode((string) $variables->fetchColumn(), true);
        $check(is_array($variablesR3) && (string) ($variablesR3['%sessionID%'] ?? '') === (string) $idA, 'b3 la entrada de reutilización trae el id de la fila', json_encode($variablesR3) ?: '');

        //i8: el código de un uso se sigue gastando; y se gasta DESPUÉS de abrir la sesión.
        $usuarioO = $crearUsuario('codigo');
        $codigo = 'zz' . random_int(100000, 999999);
        OTPSecretsUsersMapper::setOTP($usuarioO, $codigo, OTPSecretsUsersMapper::METHOD_ONE_USE_CODE, 10);
        [, , $conCodigo] = $acceder("{$prefijo}-codigo", $codigo, null);
        $check(($conCodigo['auth'] ?? null) === true && OTPHandler::checkValidityOTP($codigo, "{$prefijo}-codigo") === false,
            'i8 un acceso con código de un uso entra y lo deja gastado', json_encode([$conCodigo['auth'] ?? null, $conCodigo['error'] ?? null]) ?: '');
        $fuente = (string) file_get_contents(basepath('app/classes/PiecesPHP/UserSystem/Controllers/UsersController.php'));
        $posApp = strpos($fuente, 'UserSessions::issueAppToken(');
        $posWeb = strpos($fuente, 'UserSessions::issueToken(');
        $posGasto = strpos($fuente, 'OTPHandler::toExpireOTP($username);');
        $check($posApp !== false && $posWeb !== false && $posGasto !== false && $posGasto > $posApp && $posGasto > $posWeb,
            'i8b el acceso gasta el código DESPUÉS de abrir la sesión (de app o web): si abrirla lanza, el código no se pierde (por posición: no se puede hacer fallar la apertura en proceso)');

        //El acceso de app con 2FA, y deviceKey sin isExternalLogin.
        $usuarioF = $crearUsuario('dosfactor');
        OTPSecretsUsersMapper::toggle2FA($usuarioF, true, 'zz-codigo-jpi', 'ZZ JPI');
        OTPSecretsUsersMapper::confirm2FA($usuarioF);
        $claveF = $nuevaClave();
        $filasF = fn (): int => $contar("SELECT COUNT(*) FROM `{$tablaSesiones}` WHERE userID = ? AND kind = 'app'", [$usuarioF]);
        [, , $sinFactor] = $pedir('POST', $rutaAcceso, ['username' => "{$prefijo}-dosfactor", 'password' => $clave, 'deviceKey' => $claveF['b64']], ['isExternalLogin: yes', 'X-Requested-With: XMLHttpRequest']);
        $filasSinFactor = $filasF();
        [, , $conFactor] = $pedir('POST', $rutaAcceso, ['username' => "{$prefijo}-dosfactor", 'password' => $clave, 'deviceKey' => $claveF['b64'], 'twoFactor' => 'zz-codigo-jpi'], ['isExternalLogin: yes', 'X-Requested-With: XMLHttpRequest']);
        $check(($sinFactor['error'] ?? null) === UsersController::INVALID_TWO_FACTOR_CODE && $filasSinFactor === 0 && ($conFactor['auth'] ?? null) === true && isset($conFactor['refreshToken']) && $filasF() === 1,
            '2fa el acceso de app con doble factor: sin el código, INVALID_TWO_FACTOR_CODE y ninguna fila; con él, la sesión de app', json_encode([$sinFactor['error'] ?? null, $filasSinFactor, $conFactor['error'] ?? null, $filasF()]) ?: '');
        $usuarioX = $crearUsuario('sin-externo');
        [, , $sinExterno] = $pedir('POST', $rutaAcceso, ['username' => "{$prefijo}-sin-externo", 'password' => $clave, 'deviceKey' => $nuevaClave()['b64']], ['X-Requested-With: XMLHttpRequest']);
        $check(($sinExterno['auth'] ?? null) === true && !isset($sinExterno['refreshToken']) && ($filaDeToken((string) ($sinExterno['token'] ?? ''))['kind'] ?? null) === UserSessions::KIND_WEB,
            'dk deviceKey sin isExternalLogin: sesión web, sin refreshToken (la clave se ignora)');

        //Firmas: DER mínimo con ceros a la izquierda y bit alto; firmas reales con esas formas; la maleada.
        $vectorDer = AppSessionRenewal::derFromRaw(str_repeat("\0", 31) . "\x05" . "\x80" . str_repeat("\x01", 31));
        $esperadoDer = "\x30" . chr(3 + 35) . "\x02\x01\x05" . "\x02\x21\x00\x80" . str_repeat("\x01", 31);
        $ceroDer = AppSessionRenewal::derFromRaw(str_repeat("\x7f", 32) . str_repeat("\0", 32));
        $claveV = $nuevaClave();
        $pemV = (string) UserSessions::normalizeDeviceKey($claveV['b64']);
        $formas = ['cero' => false, 'alto' => false];
        for ($i = 0; $i < 4000 && in_array(false, $formas, true); $i++) {
            $mensaje = 'zz-jpi-' . $i;
            $crudo = (string) base64_decode($firmar($claveV['privada'], $mensaje));
            if (!$formas['cero'] && $crudo[0] === "\0") {
                $formas['cero'] = AppSessionRenewal::signatureIsValid($mensaje, $crudo, $pemV);
            }
            if (!$formas['alto'] && (ord($crudo[0]) & 0x80) !== 0) {
                $formas['alto'] = AppSessionRenewal::signatureIsValid($mensaje, $crudo, $pemV);
            }
        }
        $check($vectorDer === $esperadoDer && $ceroDer === "\x30\x25\x02\x20" . str_repeat("\x7f", 32) . "\x02\x01\x00" && $formas === ['cero' => true, 'alto' => true],
            'v1 el DER es mínimo (r con ceros a la izquierda, s con el bit alto, s cero) y verifican firmas reales con r que empieza por 0x00 y con el bit alto', json_encode($formas) ?: '');
        //La maleada (r, n−s): se acepta como firma, pero repetir la petición la frena el timestamp.
        $n = hex2bin('ffffffff00000000ffffffffffffffffbce6faada7179e84f3b9cac2fc632551');
        $sm = $sesionApp($usuarioB);
        $idM = (int) ($filaDeToken($sm['token'])['id'] ?? 0);
        $cuerpoM = $cuerpoRenovar($sm['privada'], $sm['refreshToken'], $momento++);
        $crudoM = (string) base64_decode($cuerpoM['signature']);
        $sValor = substr($crudoM, 32);
        $resta = '';
        $prestamo = 0;
        for ($i = 31; $i >= 0; $i--) {
            $d = ord($n[$i]) - ord($sValor[$i]) - $prestamo;
            $prestamo = $d < 0 ? 1 : 0;
            $resta = chr(($d + 256) % 256) . $resta;
        }
        $maleada = $cuerpoM;
        $maleada['signature'] = base64_encode(substr($crudoM, 0, 32) . $resta);
        $aceptaMaleada = AppSessionRenewal::signatureIsValid(AppSessionRenewal::SIGNED_PREFIX . $cuerpoM['refreshToken'] . '|' . $cuerpoM['timestamp'], substr($crudoM, 0, 32) . $resta, (string) UserSessions::normalizeDeviceKey('' . $pemABase64($sm['privada'] !== false ? openssl_pkey_get_details($sm['privada']) : false)));
        [$eM1] = $renovarEnProceso($cuerpoM);
        [$eM2, , $cM2] = $renovarEnProceso($maleada);
        $check($eM1 === 200 && $eM2 === 409 && ($cM2['error'] ?? null) === AppSessionRenewal::ERROR_RETRY && array_key_exists('closedAt', $fila($idM)) && $fila($idM)['closedAt'] === null,
            'v2 la firma maleada (r, n−s) ' . ($aceptaMaleada ? 'se acepta como firma' : 'no se acepta') . ', pero repetir la petición la frena el timestamp: 409 RETRY', json_encode([$eM1, $eM2, $cM2['error'] ?? null]) ?: '');

        //El cuerpo en JSON, como lo mandará la app.
        $sj = $sesionApp($usuarioB);
        [$eJ, $cJ] = $pedirJson($rutaRenovar, $cuerpoRenovar($sj['privada'], $sj['refreshToken'], time()));
        $check($eJ === 200 && ($cJ['success'] ?? null) === true && preg_match('/^[A-Za-z0-9_-]{22}\.[A-Za-z0-9_-]{43}$/', (string) ($cJ['refreshToken'] ?? '')) === 1,
            'j1 renovar con el cuerpo en JSON (por HTTP) funciona igual', json_encode([$eJ, $cJ['error'] ?? null]) ?: '');

        //m15: sin la migración (simulado por reflexión: la marca de UserSessions a false), cerrar las de app no hace nada y no
        //falla, y una fila sin la columna se pinta como web. La columna real no se quita.
        $columnas = new \ReflectionProperty(UserSessions::class, 'appColumns');
        $columnasPrevias = $columnas->getValue();
        $s15 = $sesionApp($usuarioB);
        $id15 = (int) ($filaDeToken($s15['token'])['id'] ?? 0);
        try {
            $columnas->setValue(null, false);
            $cerradas15 = UserSessions::closeAllApp(UserSessions::REASON_APP_KEY_ROTATED);
            $etiqueta15 = UserSessionsController::browserLabel(['userAgent' => 'Chrome/120 Android']);
        } finally {
            $columnas->setValue(null, $columnasPrevias);
        }
        $fila15 = $fila($id15);
        $check($cerradas15 === 0 && array_key_exists('closedAt', $fila15) && $fila15['closedAt'] === null && !str_contains($etiqueta15, 'App'),
            'm15 sin las columnas de app (simulado), closeAllApp() no cierra nada ni falla y una fila sin kind se pinta como web', json_encode([$cerradas15, $etiqueta15], JSON_UNESCAPED_UNICODE) ?: '');

        //b1x: resend() con la fila leída ANTES de que caduque: la sentencia ya no casa (expiresAt > ?) y no anota nada.
        $reenviar = new \ReflectionMethod(AppSessionRenewal::class, 'resend');
        $sx1 = $sesionApp($usuarioB);
        [, , $rx1] = $renovarEnProceso($cuerpoRenovar($sx1['privada'], $sx1['refreshToken'], $momento++));
        $idX1 = (int) ($filaDeToken((string) ($rx1['token'] ?? ''))['id'] ?? 0);
        $leida = $fila($idX1);
        $database->prepare("UPDATE `{$tablaSesiones}` SET expiresAt = ? WHERE id = ?")->execute([date('Y-m-d H:i:s', time() - 5), $idX1]);
        $resultadoX1 = $reenviar->invoke(null, $leida, substr($sx1['refreshToken'], 0, 22), $momento++);
        $check($resultadoX1 === null && ($fila($idX1)['refreshLastTimestamp'] ?? 'x') === ($leida['refreshLastTimestamp'] ?? 'y'),
            'b1x DISCRIMINANTE: resend() con la fila leída antes de caducar no reenvía (null) y no anota el timestamp', json_encode(['resultado' => $resultadoX1 === null ? null : 'reenvió']) ?: '');

        //b1bis: la relectura del paso 8 vuelve a pasar el 6: una petición vieja (su timestamp ya superado) da RETRY, nunca cierra.
        $relectura = new \ReflectionMethod(AppSessionRenewal::class, 'rereadRefusal');
        $sx2 = $sesionApp($usuarioB);
        $filaX2 = $filaDeToken($sx2['token']);
        $filaX2['refreshLastTimestamp'] = $momento + 50;
        $vieja = $relectura->invoke(null, $filaX2, $momento);
        $nueva = $relectura->invoke(null, $filaX2, $momento + 51);
        $cerrada = $relectura->invoke(null, ['closedAt' => date('Y-m-d H:i:s')] + $filaX2, $momento + 51);
        $check($vieja === [409, AppSessionRenewal::ERROR_RETRY] && $nueva === null && $cerrada === [401, AppSessionRenewal::ERROR_REAUTH_REQUIRED]
            && array_key_exists('closedAt', $fila((int) $filaX2['id'])) && $fila((int) $filaX2['id'])['closedAt'] === null,
            'b1bis DISCRIMINANTE: releída, una petición con el timestamp ya superado da 409 RETRY (no la rama que cierra); una nueva sigue; una fila cerrada da 401', json_encode([$vieja, $nueva, $cerrada]) ?: '');

        //m15b-m15d: sin las columnas de app (la marca a false), cerrar sigue funcionando, renovar responde 401 sin consultar y
        //el acceso con deviceKey es el de siempre. closeAll() va dentro de una transacción que se deshace: no cierra nada real.
        $usuarioM = $crearUsuario('sin-migracion');
        $webM1 = UserSessions::open($usuarioM);
        $webM2 = UserSessions::open($usuarioM);
        $idDe = fn (string $sid): int => (int) ($filaDeToken(SessionToken::generateToken(['id' => $usuarioM, 'sid' => $sid], null, 60, false))['id'] ?? 0);
        $idM1 = $idDe($webM1);
        $idM2 = $idDe($webM2);
        $errores15 = [];
        $sa15 = $sesionApp($usuarioB);
        $filaSa15 = $filaDeToken($sa15['token']);
        try {
            $columnas->setValue(null, false);
            try {
                $cerro1 = UserSessions::close($idM1, null, UserSessions::REASON_CLOSED_BY_ADMIN);
                $cerroTodas = UserSessions::closeAllOfUser($usuarioM, null, UserSessions::REASON_REVOKE_ALL_USER);
                $database->beginTransaction();
                try {
                    $cerroGlobal = UserSessions::closeAll(UserSessions::REASON_REVOKE_ALL_GLOBAL);
                } finally {
                    $database->rollBack();
                }
            } catch (\Throwable $e) {
                $errores15[] = get_class($e) . ': ' . $e->getMessage();
            }
            [$eSin, , $cSin] = $renovarEnProceso($cuerpoRenovar($sa15['privada'], $sa15['refreshToken'], $momento++));
            $peticionAcceso = (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-acceso'), new Headers(['isExternalLogin' => 'yes']), [], [], (new StreamFactory())->createStream('')))
                ->withParsedBody(['username' => "{$prefijo}-sin-migracion", 'password' => $clave, 'deviceKey' => $nuevaClave()['b64']]);
            $respuestaAcceso = (new UsersController())->login($peticionAcceso instanceof RequestRoute ? $peticionAcceso : throw new \LogicException('sin RequestRoute'), new ResponseRoute());
            $cuerpoAcceso = json_decode((string) $respuestaAcceso->getBody(), true);
            $peticionMala = (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-acceso'), new Headers(['isExternalLogin' => 'yes']), [], [], (new StreamFactory())->createStream('')))
                ->withParsedBody(['username' => "{$prefijo}-sin-migracion", 'password' => $clave, 'deviceKey' => 'zz-no-es-una-clave']);
            $respuestaMala = (new UsersController())->login($peticionMala instanceof RequestRoute ? $peticionMala : throw new \LogicException('sin RequestRoute'), new ResponseRoute());
            $cuerpoMala = json_decode((string) $respuestaMala->getBody(), true);
            try {
                UserSessions::openApp($usuarioB, $nuevaClave()['b64']);
                $abrioSinColumnas = 'abrió';
            } catch (\RuntimeException $e) {
                $abrioSinColumnas = $e->getMessage();
            }
        } finally {
            $columnas->setValue(null, $columnasPrevias);
        }
        $cuerpoAcceso = is_array($cuerpoAcceso ?? null) ? $cuerpoAcceso : [];
        $cuerpoMala = is_array($cuerpoMala ?? null) ? $cuerpoMala : [];
        $filaSa15Despues = $filaDeToken($sa15['token']);
        $intacta15 = array_intersect_key($filaSa15Despues, array_flip(['closedAt', 'refreshHash', 'refreshPrevHash', 'refreshLastTimestamp', 'sessionHash'])) === array_intersect_key($filaSa15, array_flip(['closedAt', 'refreshHash', 'refreshPrevHash', 'refreshLastTimestamp', 'sessionHash']));
        $check($errores15 === [] && ($cerro1 ?? false) === true && ($fila($idM1)['closedAt'] ?? null) !== null && ($cerroTodas ?? 0) >= 1 && ($fila($idM2)['closedAt'] ?? null) !== null && ($cerroGlobal ?? 0) >= 1,
            'm15b sin las columnas de app, close(), closeAllOfUser() y closeAll() no lanzan y cierran (closeAll, deshecho)', json_encode(['errores' => $errores15, 'close' => $cerro1 ?? null, 'deUsuario' => $cerroTodas ?? null, 'global' => $cerroGlobal ?? null], JSON_UNESCAPED_UNICODE) ?: '');
        $check($eSin === 401 && ($cSin['error'] ?? null) === AppSessionRenewal::ERROR_REAUTH_REQUIRED && isset($filaSa15['refreshHash']) && $intacta15,
            'm15c DISCRIMINANTE: sin las columnas, renew-session responde 401 REAUTH_REQUIRED sin consultar la fila: con una llave y una firma buenas, la fila sigue intacta', json_encode([$eSin, $cSin['error'] ?? null, $intacta15]) ?: '');
        $check(($cuerpoMala['auth'] ?? null) === true && !isset($cuerpoMala['refreshToken']) && ($cuerpoMala['error'] ?? null) !== UsersController::INVALID_DEVICE_KEY && ($filaDeToken((string) ($cuerpoMala['token'] ?? ''))['kind'] ?? null) === UserSessions::KIND_WEB,
            'm15c2 sin las columnas, una deviceKey inválida tampoco cuenta: acceso web normal, no INVALID_DEVICE_KEY', json_encode([$cuerpoMala['auth'] ?? null, $cuerpoMala['error'] ?? null]) ?: '');
        $check(($abrioSinColumnas ?? '') === 'Falta aplicar databases/actualizaciones/2026-11-26-sesiones-de-app.sql: la sesión de app necesita sus columnas.',
            'm15f DISCRIMINANTE: openApp() sin las columnas lanza y dice qué migración falta (un clon que la llame directo)', (string) ($abrioSinColumnas ?? ''));
        $check(($cuerpoAcceso['auth'] ?? null) === true && !isset($cuerpoAcceso['refreshToken']) && ($filaDeToken((string) ($cuerpoAcceso['token'] ?? ''))['kind'] ?? null) === UserSessions::KIND_WEB,
            'm15d sin las columnas, el acceso con deviceKey es el de siempre: sesión web, sin refreshToken', json_encode([$cuerpoAcceso['auth'] ?? null, $cuerpoAcceso['error'] ?? null, isset($cuerpoAcceso['refreshToken'])]) ?: '');

        //m15e: la base real tiene la columna, así que m15b no ve si un cierre escribiría refreshPending sin ella: se mira la pieza
        //(pendingReset() vacío sin las columnas) y que close(), closeAllOfUser() y closeAll() la usan en vez del literal.
        $trozo = new \ReflectionMethod(UserSessions::class, 'pendingReset');
        try {
            $columnas->setValue(null, false);
            $sinColumnas = $trozo->invoke(null);
            $columnas->setValue(null, true);
            $conColumnas = $trozo->invoke(null);
        } finally {
            $columnas->setValue(null, $columnasPrevias);
        }
        $fuenteSesiones = (string) file_get_contents(basepath('app/classes/PiecesPHP/UserSystem/Authentication/UserSessions.php'));
        $cuerpoDe = function (string $metodo) use ($fuenteSesiones): string {
            $inicio = strpos($fuenteSesiones, "public static function {$metodo}(");
            return $inicio === false ? '' : (string) substr($fuenteSesiones, $inicio, (int) strpos($fuenteSesiones, "\n    }\n", $inicio) - $inicio);
        };
        $usanLaPieza = array_map(fn (string $m): bool => str_contains($cuerpoDe($m), 'self::pendingReset()') && !str_contains($cuerpoDe($m), 'refreshPending = NULL'), ['close' => 'close', 'closeAllOfUser' => 'closeAllOfUser', 'closeAll' => 'closeAll']);
        $check($sinColumnas === '' && $conColumnas === ', refreshPending = NULL' && array_filter($usanLaPieza, fn (bool $b): bool => !$b) === [],
            'm15e DISCRIMINANTE: sin las columnas, la pieza que vacía refreshPending es vacía, y close(), closeAllOfUser() y closeAll() la usan (por texto: la base real no puede quedarse sin la columna)', json_encode([$sinColumnas, $usanLaPieza]) ?: '');

        //t1: sin las columnas DE VERDAD. Una tabla TEMPORARY con el mismo nombre y sin las de app esconde la real solo en esta
        //conexión; information_schema no ve las temporales, así que la marca se pone a false (lo que diría sin la migración).
        $usuarioT = $crearUsuario('temporal');
        $idsT = [];
        $erroresT1 = [];
        $vistaT1 = false;
        $previoUsuario = get_config('current_user');
        $raiz = (int) $contar("SELECT id FROM `{$tablaUsuarios}` WHERE username = ?", ['zz-agente-root']);
        try {
            $database->exec("CREATE TEMPORARY TABLE `zz_sesiones_sin_app` LIKE `{$tablaSesiones}`");
            $database->exec('ALTER TABLE `zz_sesiones_sin_app` DROP COLUMN kind, DROP COLUMN deviceKey, DROP COLUMN refreshSelector, DROP COLUMN refreshHash, DROP COLUMN refreshPrevHash, DROP COLUMN refreshPending, DROP COLUMN refreshLastTimestamp');
            $database->exec("CREATE TEMPORARY TABLE `{$tablaSesiones}` LIKE `zz_sesiones_sin_app`");
            $columnasVistas = $database->query("SHOW COLUMNS FROM `{$tablaSesiones}`")->fetchAll(\PDO::FETCH_COLUMN);
            //Sin ver la temporal no se sigue: closeAll() cerraría las filas reales.
            $vistaT1 = is_array($columnasVistas) && in_array('sessionHash', $columnasVistas, true) && !in_array('refreshPending', $columnasVistas, true) && !in_array('kind', $columnasVistas, true);
            if (!$vistaT1) {
                throw new \LogicException('la tabla temporal no esconde a la real en esta conexión');
            }
            $alta = $database->prepare("INSERT INTO `{$tablaSesiones}` (userID, sessionHash, createdAt, lastActivityAt, expiresAt) VALUES (?, ?, ?, ?, ?)");
            foreach ([1, 2, 3, 4] as $n) {
                $alta->execute([$usuarioT, hash('sha256', random_bytes(16)), date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), date('Y-m-d H:i:s', time() + 3600)]);
                $idsT[$n] = (int) $database->lastInsertId();
            }
            $hashT3 = (string) $database->query("SELECT sessionHash FROM `{$tablaSesiones}` WHERE id = " . $idsT[3])->fetchColumn();
            $columnas->setValue(null, false);
            try {
                set_config('current_user', (object) ['id' => $raiz]);
                getLoggedFrameworkUser(true);
                $peticionT = (new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz-sesiones'), new Headers(['X-Requested-With' => 'XMLHttpRequest']), [], [], (new StreamFactory())->createStream('')))
                    ->withQueryParams(['draw' => 1, 'start' => 0, 'length' => 10, 'userID' => (string) $usuarioT, 'columns' => array_fill(0, 7, ['searchable' => 'true']), 'search' => ['value' => '', 'regex' => 'false']]);
                $listadoT = json_decode((string) (new UserSessionsController())->dataTables($peticionT instanceof RequestRoute ? $peticionT : throw new \LogicException('sin RequestRoute'), new ResponseRoute())->getBody(), true);
                $mineT = UserSessions::openOfUser($usuarioT);
                $cerroT1 = UserSessions::close($idsT[1], null, UserSessions::REASON_CLOSED_BY_ADMIN);
                $cerroT2 = UserSessions::closeAllOfUser($usuarioT, null, UserSessions::REASON_REVOKE_ALL_USER, $hashT3);
                $cerroT3 = UserSessions::closeAll(UserSessions::REASON_REVOKE_ALL_GLOBAL);
            } catch (\Throwable $e) {
                $erroresT1[] = get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200);
            } finally {
                $columnas->setValue(null, $columnasPrevias);
                set_config('current_user', $previoUsuario);
                getLoggedFrameworkUser(true);
            }
            $abiertasT = (int) $database->query("SELECT COUNT(*) FROM `{$tablaSesiones}` WHERE closedAt IS NULL")->fetchColumn();
        } catch (\Throwable $e) {
            $erroresT1[] = get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200);
        } finally {
            //TEMPORARY: nunca borra la real, aunque la temporal no exista.
            $database->exec("DROP TEMPORARY TABLE IF EXISTS `{$tablaSesiones}`");
            $database->exec('DROP TEMPORARY TABLE IF EXISTS `zz_sesiones_sin_app`');
        }
        $realVuelve = $database->query("SHOW COLUMNS FROM `{$tablaSesiones}` LIKE 'refreshPending'")->fetchAll();
        $listadosT = is_array($listadoT ?? null) ? (int) ($listadoT['recordsFiltered'] ?? -1) : -1;
        $check($vistaT1 && $erroresT1 === [] && ($cerroT1 ?? false) === true && ($cerroT2 ?? 0) === 2 && ($cerroT3 ?? 0) === 1 && ($abiertasT ?? -1) === 0,
            't1 DISCRIMINANTE: en una tabla SIN las columnas de app (temporal), close(), closeAllOfUser() y closeAll() no lanzan y cierran', json_encode(['vista' => $vistaT1, 'errores' => $erroresT1, 'close' => $cerroT1 ?? null, 'deUsuario' => $cerroT2 ?? null, 'global' => $cerroT3 ?? null, 'abiertas' => $abiertasT ?? null], JSON_UNESCAPED_UNICODE) ?: '');
        $check($vistaT1 && $listadosT === 4 && count($mineT ?? []) === 4,
            't1b en esa tabla, Registros › Sesiones (dataTables) y Mis sesiones (openOfUser) listan sin lanzar', json_encode(['registros' => $listadosT, 'mias' => count($mineT ?? [])]) ?: '');
        $check(is_array($realVuelve) && count($realVuelve) === 1, 't1c al acabar, la tabla real vuelve a verse con su columna refreshPending', json_encode($realVuelve) ?: '');

        //nl: un salto de línea final en la llave o en el timestamp es una forma mala (400), no una reutilización.
        $sNl = $sesionApp($usuarioB);
        $tsNl = $momento++;
        $conSalto = ['refreshToken' => $sNl['refreshToken'] . "\n", 'timestamp' => (string) $tsNl, 'signature' => $firmar($sNl['privada'], AppSessionRenewal::SIGNED_PREFIX . $sNl['refreshToken'] . "\n|" . $tsNl)];
        $tsConSalto = $cuerpoRenovar($sNl['privada'], $sNl['refreshToken'], $tsNl);
        $tsConSalto['timestamp'] .= "\n";
        [$eNl1] = $renovarEnProceso($conSalto);
        [$eNl2] = $renovarEnProceso($tsConSalto);
        $check($eNl1 === 400 && $eNl2 === 400 && array_key_exists('closedAt', $filaDeToken($sNl['token'])) && $filaDeToken($sNl['token'])['closedAt'] === null,
            'nl DISCRIMINANTE: un salto de línea final en refreshToken o en timestamp da 400, y la sesión sigue abierta', json_encode([$eNl1, $eNl2]) ?: '');
        echoTerminal(' ');
    } catch (\Throwable $e) {
        $check(false, 'e* el recorrido corre entero', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach ($configPrevia as $opcion => $valor) {
            set_config($opcion, $valor);
        }
        $marcaGlobal->setValue(null, $marcaGlobalPrevia);
        $llaveroIlegible->setValue(null, $llaveroPrevio);
        try {
            $borradas = $limpiar();
            echoTerminal('   - limpieza: ' . ($borradas === [] ? 'nada' : json_encode($borradas)));
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
    }
    $final = $propio();
    $check($final === $inicial, 'z1 al acabar, lo de la suite en la base es lo del principio (nada): usuarios, sesiones, intentos y organizaciones', 'inicial ' . json_encode($inicial) . ', final ' . json_encode($final));

    return $balance();

})->setDescription('La sesión de una app: el acceso con la clave de su dispositivo y la renovación con su llave de un solo uso. Escribe usuarios, sesiones y una organización zz-agente-jph- y los borra')->setEffects([CliActions::EFFECT_DATABASE])->register();
