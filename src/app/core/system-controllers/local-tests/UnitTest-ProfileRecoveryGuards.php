<?php

//El perfil, la recuperación y el acceso rechazan lo que no vale (H522). Por HTTP, con usuarios zz-agente- que crea y retira.
//@areas nucleo

use API\Controllers\APIController;
use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper as Aprobaciones;

CliActions::make('unit-tests:core/profile-recovery-guards', function ($args) {

    echoTerminal("\e[33m[TEST:ProfileRecoveryGuards] El perfil, la recuperación y el acceso rechazan lo que no vale\e[39m");
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

    if (!$check(API_MODULE, 'p0 el módulo de la API está encendido (API_MODULE)')) {
        return $balance();
    }
    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la suite del avatar.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'p1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = trim((string) parse_url($base, \PHP_URL_PATH), '/');
    $relativa = function (string $url) use ($prefijoBase): string {
        $url = trim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
        return $prefijoBase !== '' && str_starts_with($url, $prefijoBase) ? trim(substr($url, strlen($prefijoBase)), '/') : $url;
    };
    $cabeceraToken = SessionToken::headerName();
    //Un cuerpo en cadena va crudo como JSON; un arreglo, como formulario.
    $pedir = function (string $ruta, array|string $cuerpo, ?string $jwt = null) use ($base, $cabeceraToken): array {
        $cabeceras = ['X-Requested-With: XMLHttpRequest', 'Accept: application/json'];
        if ($jwt !== null) {
            $cabeceras[] = "{$cabeceraToken}: {$jwt}";
        }
        if (is_string($cuerpo)) {
            $cabeceras[] = 'Content-Type: application/json';
        }
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => "{$base}/{$ruta}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => is_string($cuerpo) ? $cuerpo : http_build_query($cuerpo),
            CURLOPT_HTTPHEADER => $cabeceras,
        ]);
        $body = curl_exec($handle);
        $datos = is_string($body) ? json_decode($body, true) : null;
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'json' => is_array($datos) ? $datos : null, 'body' => is_string($body) ? mb_substr($body, 0, 300) : ''];
    };
    $resumen = fn (array $r): string => "HTTP {$r['status']} · " . ($r['json'] !== null ? json_encode($r['json'], JSON_UNESCAPED_UNICODE) : $r['body']);
    $registro = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\') . '/error.log.jsonl';
    $lineasRegistro = function () use ($registro): int {
        clearstatcache();
        return is_file($registro) ? count(file($registro) ?: []) : 0;
    };

    $prefijo = 'zz-agente-joz-' . bin2hex(random_bytes(3));
    $usuarios = [];
    $organizaciones = [];
    $claveDe = fn (string $sufijo): string => "zz-Clave-{$sufijo}-" . substr(sha1($prefijo), 0, 8);
    $crear = function (string $sufijo, int $estado) use ($prefijo, $claveDe, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.invalid";
        $u->password = password_hash($claveDe($sufijo), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Agente';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_GENERAL;
        $u->status = $estado;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };
    $hashDe = fn (int $id): string => (string) (new UsersModel($id))->password;

    try {
        $rutaAPI = fn (string $accion): string => $relativa((string) APIController::routeName('users-actions', ['actionType' => $accion], true));
        $rutaLogin = $relativa((string) UsersController::routeName('login-request', [], true));
        $rutaEdit = $relativa((string) UsersController::routeName('edit-request', [], true));

        //──── a. Un cuerpo que no es un objeto: 400, nunca un TypeError (3) ───────────────────────
        echoTerminal('[a] Un cuerpo que no es un objeto');
        foreach (['recovery-password', 'change-password-code'] as $i => $accion) {
            $antes = $lineasRegistro();
            $r = $pedir($rutaAPI($accion), '"zz-cadena"');
            $check($r['status'] === 400 && ($r['json']['error'] ?? null) === UsersController::MISSING_OR_UNEXPECTED_PARAMS && $lineasRegistro() === $antes, 'a' . ($i + 1) . " DISCRIMINANTE: {$accion} con un JSON que es una cadena responde 400 con su error de parámetros, sin anotar un fallo", $resumen($r) . " · registro {$antes} → {$lineasRegistro()}");
        }

        $antes = $lineasRegistro();
        $r = $pedir($relativa((string) UsersController::routeName('new-password-verify-code', [], true)), '"zz-cadena"');
        $check($r['status'] === 400 && ($r['json']['error'] ?? null) === UsersController::MISSING_OR_UNEXPECTED_PARAMS && $lineasRegistro() === $antes, 'a3 DISCRIMINANTE: verify-create-password-code con un JSON que es una cadena responde 400, sin anotar un fallo', $resumen($r) . " · registro {$antes} → {$lineasRegistro()}");

        //(j) Un campo que no es texto (una lista) en las cuatro entradas de la recuperación: 400, sin aviso de PHP.
        $listas = [
            'recovery-password (API)' => [$rutaAPI('recovery-password'), '{"username":["zz"]}'],
            'recovery (formulario)' => [$relativa((string) UsersController::routeName('recovery-password-request', [], true)), '{"username":["zz"]}'],
            'change-password-code' => [$rutaAPI('change-password-code'), '{"username":["zz"],"code":"1","password":"zz-a","repassword":"zz-a"}'],
            'verify-create-password-code' => [$relativa((string) UsersController::routeName('new-password-verify-code', [], true)), '{"username":"zz","code":["1"]}'],
        ];
        $n = 4;
        foreach ($listas as $entrada => [$ruta, $cuerpo]) {
            $antes = $lineasRegistro();
            $r = $pedir($ruta, $cuerpo);
            $check($r['status'] === 400 && ($r['json']['error'] ?? null) === UsersController::MISSING_OR_UNEXPECTED_PARAMS && $lineasRegistro() === $antes, 'a' . $n++ . " DISCRIMINANTE: {$entrada} con un campo que es una lista responde 400, sin anotar nada", $resumen($r) . " · registro {$antes} → {$lineasRegistro()}");
        }

        //(o) Las rutas públicas de los problemas de acceso: un campo que no es texto responde como uno que falta, sin aviso.
        $problemas = [
            'user-forget-code' => ['forget-request-code', '{"username":["zz"],"type":"TYPE_USER_FORGET"}'],
            'user-blocked-code' => ['blocked-request-code', '{"username":"zz","type":["user-blocked"]}'],
            'get-username' => ['forget-get', '{"code":["1"],"type":"TYPE_USER_FORGET"}'],
            'unblock-user' => ['blocked-resolve', '{"code":"1","type":{"a":"b"}}'],
            'other-problems' => ['other-problems-send', '{"name":["zz"],"email":"zz-agente-joz@example.invalid","message":"zz"}'],
            'other-problems (extra)' => ['other-problems-send', '{"name":"zz","email":"zz-agente-joz@example.invalid","message":"zz","extra":"zz"}'],
            'other-problems (lastname)' => ['other-problems-send', '{"name":"zz","lastname":["zz"],"email":"zz-agente-joz@example.invalid","message":"zz"}'],
        ];
        $n = 8;
        foreach ($problemas as $entrada => [$sufijoRuta, $cuerpo]) {
            $antes = $lineasRegistro();
            $r = $pedir($relativa((string) UsersController::routeName($sufijoRuta, [], true)), $cuerpo);
            $check($r['status'] === 200 && ($r['json']['error'] ?? null) === UsersController::MISSING_OR_UNEXPECTED_PARAMS && $lineasRegistro() === $antes, 'a' . $n++ . " DISCRIMINANTE: {$entrada} con un campo que no es texto responde como uno que falta, sin anotar nada", $resumen($r) . " · registro {$antes} → {$lineasRegistro()}");
        }

        //──── b. change-password-code: vacía, sin conversiones y sin recortar (2, 5) ──────────────
        echoTerminal('[b] Cambiar la contraseña con el código');
        $idRecupera = $crear('recupera', UsersModel::STATUS_USER_ACTIVE);
        $codigo = (string) random_int(100000, 999999);
        $ponerCodigo = function () use ($database, $prefijo, $codigo): void {
            $correo = "{$prefijo}-recupera@example.invalid";
            $database->prepare('DELETE FROM `pcsphp_recovery_password` WHERE email = ?')->execute([$correo]);
            $database->prepare('INSERT INTO `pcsphp_recovery_password` (email, code, created, expired) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR))')->execute([$correo, $codigo]);
        };
        $cambiar = fn (string $clave, string $repetida): array => $pedir($rutaAPI('change-password-code'), ['username' => "{$prefijo}-recupera", 'code' => $codigo, 'password' => $clave, 'repassword' => $repetida]);
        $ponerCodigo();
        $hashAntes = $hashDe($idRecupera);
        $r = $cambiar('', '');
        $check(($r['json']['success'] ?? null) !== true && $hashDe($idRecupera) === $hashAntes, 'b1 DISCRIMINANTE: una contraseña vacía se rechaza y no cambia la guardada', $resumen($r));
        $check(is_string($r['json']['message'] ?? null) && ($r['json']['message'] ?? '') !== '' && ($r['json']['error'] ?? '') !== UsersController::NO_ERROR, 'b2 y lo dice con un mensaje de validación', $resumen($r));
        $ponerCodigo();
        $r = $cambiar('   ', '   ');
        $check(($r['json']['success'] ?? null) !== true && $hashDe($idRecupera) === $hashAntes, 'b3 solo espacios cuenta como vacía', $resumen($r));
        $ponerCodigo();
        $r = $cambiar('1e1', '10');
        $check(($r['json']['success'] ?? null) !== true && ($r['json']['error'] ?? null) === UsersController::NOT_MATCH_PASSWORDS && $hashDe($idRecupera) === $hashAntes, 'b4 DISCRIMINANTE: «1e1» y «10» no coinciden (con == eran el mismo número)', $resumen($r));
        $ponerCodigo();
        $conEspacios = ' zz-Clave-Espacios ';
        $r = $cambiar($conEspacios, $conEspacios);
        $guardada = $hashDe($idRecupera);
        $check(($r['json']['success'] ?? null) === true && password_verify($conEspacios, $guardada) && !password_verify(trim($conEspacios), $guardada), 'b5 DISCRIMINANTE: la contraseña se guarda sin recortar, como la compara el acceso', $resumen($r));
        $login = fn (string $usuario, string $clave, ?string $jwt = null): array => $pedir($rutaLogin, ['username' => $usuario, 'password' => $clave], $jwt);
        $conClave = $login("{$prefijo}-recupera", $conEspacios);
        $sinEspacios = $login("{$prefijo}-recupera", trim($conEspacios));
        $check(($conClave['json']['auth'] ?? null) === true && ($sinEspacios['json']['auth'] ?? null) !== true, 'b6 CANARIO: el acceso no recorta: entra con los espacios y no sin ellos', $resumen($conClave) . ' · ' . $resumen($sinEspacios));
        $ponerCodigo();
        $hashAntes = $hashDe($idRecupera);
        $r = $cambiar("zz-Clave\0nula", "zz-Clave\0nula");
        $check($r['status'] === 200 && ($r['json']['success'] ?? null) !== true && ($r['json']['error'] ?? '') !== UsersController::NO_ERROR && is_string($r['json']['message'] ?? null) && $hashDe($idRecupera) === $hashAntes, 'b7 DISCRIMINANTE: una contraseña con un byte nulo se rechaza con su mensaje, sin un 500, y no cambia la guardada', $resumen($r));
        $larga = str_repeat('ñ', 37);
        $r = $cambiar($larga, $larga);
        $check(($r['json']['success'] ?? null) !== true && ($r['json']['message'] ?? '') === __('revoveryPasswordModule', 'La contraseña es demasiado larga: como mucho 72 caracteres, menos si lleva tildes o emojis.') && $hashDe($idRecupera) === $hashAntes, 'b8 DISCRIMINANTE: una contraseña de 74 bytes (37 «ñ»: bcrypt solo mira 72) se rechaza y no cambia la guardada', $resumen($r));

        //──── c. edit: sin conversiones, el largo de la columna y las iniciales (5, 6, 7) ─────────
        echoTerminal('[c] Editar el perfil propio');
        $idPerfil = $crear('perfil', UsersModel::STATUS_USER_ACTIVE);
        $jwtPerfil = UserSessions::issueToken($idPerfil);
        $editar = fn (array $campos): array => $pedir($rutaEdit, ['id' => $idPerfil, 'is_profile' => 'yes'] + $campos, $jwtPerfil);
        $hashAntes = $hashDe($idPerfil);
        $r = $editar(['current-password' => $claveDe('perfil'), 'password' => '1e1', 'password2' => '10']);
        $check(($r['json']['success'] ?? null) !== true && ($r['json']['message'] ?? '') === __(UsersController::LANG_GROUP, 'Las contraseñas no coinciden.') && $hashDe($idPerfil) === $hashAntes, 'c1 DISCRIMINANTE: en el perfil, «1e1» y «10» no coinciden', $resumen($r));
        $largo = str_repeat('ñ', 256);
        $r = $editar(['firstname' => $largo, 'first_lastname' => 'Agente']);
        $check(($r['json']['success'] ?? null) !== true && (new UsersModel($idPerfil))->firstname === 'Zz', 'c2 DISCRIMINANTE: un nombre de 256 caracteres (más que su columna, varchar(255)) se rechaza y no se guarda', $resumen($r));
        $check(is_string($r['json']['message'] ?? null) && str_contains((string) ($r['json']['message'] ?? ''), '255'), 'c3 y el mensaje dice el máximo', $resumen($r));
        $r = $editar(['firstname' => str_repeat('ñ', 255), 'first_lastname' => 'Agente']);
        $check(($r['json']['success'] ?? null) === true && mb_strlen((string) (new UsersModel($idPerfil))->firstname) === 255, 'c4 CANARIO: 255 caracteres sí caben', $resumen($r));
        $r = $editar(['firstname' => 'josé ñúñez', 'first_lastname' => 'MCDONALD', 'secondname' => 'ana', 'second_lastname' => 'de la peña']);
        $guardado = new UsersModel($idPerfil);
        $check(($r['json']['success'] ?? null) === true && $guardado->firstname === 'José Ñúñez', 'c5 DISCRIMINANTE: «josé ñúñez» queda «José Ñúñez»: la inicial con tilde o ñ sube', $resumen($r) . ' · ' . $guardado->firstname);
        $check($guardado->firstLastname === 'MCDONALD' && $guardado->secondname === 'Ana' && $guardado->secondLastname === 'De La Peña', 'c6 CANARIO: lo ASCII, igual que ucwords: «MCDONALD» se queda, «ana» → «Ana», «de la peña» → «De La Peña»', "{$guardado->firstLastname} · {$guardado->secondname} · {$guardado->secondLastname}");
        $hashAntes = $hashDe($idPerfil);
        $r = $editar(['current-password' => $claveDe('perfil'), 'password' => "zz\0nula", 'password2' => "zz\0nula"]);
        $check($r['status'] === 200 && ($r['json']['success'] ?? null) !== true && $hashDe($idPerfil) === $hashAntes && !str_contains((string) ($r['json']['message'] ?? ''), 'error interno'), 'c7 DISCRIMINANTE: en el perfil, una contraseña con un byte nulo se rechaza con su mensaje y no cambia', $resumen($r));
        $r = $editar(['current-password' => $claveDe('perfil'), 'password' => '   ', 'password2' => '   ']);
        $check(($r['json']['success'] ?? null) !== true && ($r['json']['message'] ?? '') === __(UsersController::LANG_GROUP, 'La contraseña no puede estar vacía.') && $hashDe($idPerfil) === $hashAntes, 'c8 DISCRIMINANTE: en el perfil, una contraseña nueva de solo espacios se rechaza, como en la recuperación', $resumen($r));
        $r = $editar(['current-password' => $claveDe('perfil'), 'password' => str_repeat('a', 73), 'password2' => str_repeat('a', 73)]);
        $check(($r['json']['success'] ?? null) !== true && ($r['json']['message'] ?? '') === __(UsersController::LANG_GROUP, 'La contraseña es demasiado larga: como mucho 72 caracteres, menos si lleva tildes o emojis.') && $hashDe($idPerfil) === $hashAntes, 'c9 DISCRIMINANTE: en el perfil, una contraseña de 73 bytes se rechaza', $resumen($r));

        //──── d. El acceso: un token de un inactivo y el registro de errores (9, 10) ──────────────
        echoTerminal('[d] El acceso');
        $idInactivo = $crear('inactivo', UsersModel::STATUS_USER_ACTIVE);
        $jwtInactivo = UserSessions::issueToken($idInactivo);
        $database->prepare('UPDATE `' . UsersModel::TABLE . '` SET status = ? WHERE id = ?')->execute([UsersModel::STATUS_USER_INACTIVE, $idInactivo]);
        $r = $login("{$prefijo}-inactivo", $claveDe('inactivo'), $jwtInactivo);
        $check(($r['json']['error'] ?? null) !== UsersController::ACTIVE_SESSION && ($r['json']['auth'] ?? null) !== true && ($r['json']['error'] ?? null) === UsersController::INACTIVE_USER, 'd1 DISCRIMINANTE: con el token vivo de un usuario ya inactivo, el acceso no dice ACTIVE_SESSION: sigue el normal y lo rechaza por inactivo', $resumen($r));
        $r = $login("{$prefijo}-perfil", 'zz-mala', $jwtPerfil);
        $check(($r['json']['error'] ?? null) === UsersController::ACTIVE_SESSION, 'd2 CANARIO: con el token de un usuario activo, sigue diciendo ACTIVE_SESSION', $resumen($r));
        //Una organización que ya no admite sesión (Inactiva), como en index.php: su usuario no tiene sesión abierta.
        $org = new OrganizationMapper();
        $org->name = "{$prefijo}-org";
        $org->nit = "{$prefijo}-nit";
        $org->folder = "{$prefijo}-org";
        $org->status = OrganizationMapper::ACTIVE;
        $org->activitySector = 'Sector de prueba';
        $org->informativeEmail = "{$prefijo}-org@example.invalid";
        $org->save();
        $organizaciones[] = (int) $org->id;
        $idOrg = $crear('org', UsersModel::STATUS_USER_ACTIVE);
        $database->prepare('UPDATE `' . UsersModel::TABLE . '` SET organization = ? WHERE id = ?')->execute([(int) $org->id, $idOrg]);
        $jwtOrg = UserSessions::issueToken($idOrg);
        $database->prepare('UPDATE `' . OrganizationMapper::TABLE . '` SET status = ? WHERE id = ?')->execute([OrganizationMapper::INACTIVE, (int) $org->id]);
        $r = $login("{$prefijo}-org", 'zz-mala', $jwtOrg);
        $check(($r['json']['error'] ?? null) !== UsersController::ACTIVE_SESSION && ($r['json']['auth'] ?? null) !== true, 'd5 DISCRIMINANTE: con el token vivo de un usuario cuya organización ya no admite sesión, el acceso no dice ACTIVE_SESSION', $resumen($r));

        //──── f. El alta, como el perfil (b, c) ─────────────────────────────────────────────────
        echoTerminal('[f] El alta');
        $idRaiz = $crear('raiz', UsersModel::STATUS_USER_ACTIVE);
        //Entra por token: una contraseña al azar, para que un root que sobreviva a una corrida cortada no tenga una conocida.
        $database->prepare('UPDATE `' . UsersModel::TABLE . '` SET type = ?, password = ? WHERE id = ?')->execute([UsersModel::TYPE_USER_ROOT, password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT), $idRaiz]);
        $jwtRaiz = UserSessions::issueToken($idRaiz);
        $rutaAlta = $relativa((string) UsersController::routeName('register-request', [], true));
        $alta = fn (string $sufijo, array $campos): array => $pedir($rutaAlta, $campos + [
            'username' => "{$prefijo}-alta-{$sufijo}",
            'email' => "{$prefijo}-alta-{$sufijo}@example.invalid",
            'password' => 'zz-Clave-Alta',
            'password2' => 'zz-Clave-Alta',
            'firstname' => 'Zz',
            'first_lastname' => 'Alta',
            'type' => UsersModel::TYPE_USER_GENERAL,
            'status' => UsersModel::STATUS_USER_ACTIVE,
            'organization' => OrganizationMapper::INITIAL_ID_GLOBAL,
        ], $jwtRaiz);
        $existe = fn (string $sufijo): ?UsersModel => ($fila = UsersModel::getBy("{$prefijo}-alta-{$sufijo}", 'username')) !== null ? new UsersModel((int) $fila->id) : null;
        $r = $alta('numeros', ['password' => '1e1', 'password2' => '10']);
        $check(($r['json']['success'] ?? null) !== true && ($r['json']['message'] ?? '') === __(UsersController::LANG_GROUP, 'Las contraseñas no coinciden.') && $existe('numeros') === null, 'f1 DISCRIMINANTE: en el alta, «1e1» y «10» no coinciden', $resumen($r));
        $r = $alta('nula', ['password' => "zz\0nula", 'password2' => "zz\0nula"]);
        $check($r['status'] === 200 && ($r['json']['success'] ?? null) !== true && $existe('nula') === null && !str_contains((string) ($r['json']['message'] ?? ''), 'error interno'), 'f2 DISCRIMINANTE: en el alta, una contraseña con un byte nulo se rechaza con su mensaje', $resumen($r));
        $r = $alta('larga', ['password' => str_repeat('a', 73), 'password2' => str_repeat('a', 73)]);
        $check(($r['json']['success'] ?? null) !== true && ($r['json']['message'] ?? '') === __(UsersController::LANG_GROUP, 'La contraseña es demasiado larga: como mucho 72 caracteres, menos si lleva tildes o emojis.') && $existe('larga') === null, 'f4 DISCRIMINANTE: en el alta, una contraseña de 73 bytes se rechaza', $resumen($r));
        $r = $alta('espacios', ['password' => '   ', 'password2' => '   ']);
        $check(($r['json']['success'] ?? null) !== true && ($r['json']['message'] ?? '') === __(UsersController::LANG_GROUP, 'La contraseña no puede estar vacía.') && $existe('espacios') === null, 'f5 DISCRIMINANTE: en el alta, una contraseña de solo espacios se rechaza, como en el perfil y la recuperación', $resumen($r));
        $r = $alta('tildes', ['firstname' => 'josé ñúñez', 'first_lastname' => 'MCDONALD']);
        $nuevo = $existe('tildes');
        $check(($r['json']['success'] ?? null) === true && $nuevo !== null && $nuevo->firstname === 'José Ñúñez' && $nuevo->firstLastname === 'MCDONALD', 'f3 DISCRIMINANTE: en el alta, «josé ñúñez» queda «José Ñúñez» y «MCDONALD» se queda', $resumen($r) . ' · ' . ($nuevo !== null ? "{$nuevo->firstname} · {$nuevo->firstLastname}" : 'sin usuario'));
        $antes = $lineasRegistro();
        $falta = $pedir($rutaLogin, ['username' => "{$prefijo}-perfil"]);
        $tipo = $pedir($rutaLogin, ['username' => "{$prefijo}-perfil", 'password' => ['zz']]);
        $check(($falta['json']['error'] ?? null) === UsersController::MISSING_OR_UNEXPECTED_PARAMS && ($tipo['json']['error'] ?? null) === UsersController::GENERIC_ERROR, 'd3 CANARIO: sin contraseña y con la contraseña como lista responde lo de siempre', $resumen($falta) . ' · ' . $resumen($tipo));
        $check($lineasRegistro() === $antes, 'd4 DISCRIMINANTE: y no anota nada en el registro de errores', "{$antes} → {$lineasRegistro()}");

        //──── e. La errata del acceso externo (8) ─────────────────────────────────────────────────
        echoTerminal('[e] La errata');
        $fuentes = [basepath('app/classes/PiecesPHP/UserSystem/Controllers/UsersController.php')];
        foreach (glob(basepath('app/lang/*/*.php')) ?: [] as $diccionario) {
            $fuentes[] = $diccionario;
        }
        $conErrata = array_values(array_filter($fuentes, fn (string $f): bool => str_contains((string) file_get_contents($f), 'para usar la este')));
        $check($conErrata === [], 'e1 ni el código ni los diccionarios dicen «para usar la este método»', implode(', ', $conErrata));
        $check(str_contains((string) file_get_contents($fuentes[0]), 'para usar este método de inicio de sesión'), 'e2 CANARIO: el mensaje sigue, corregido');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $tabla = UsersModel::TABLE;
            //Por la familia del prefijo, no solo los de esta corrida: recoge también lo que dejó una corrida que falló.
            $familia = $database->prepare("SELECT id FROM `{$tabla}` WHERE username LIKE ?");
            $familia->execute(['zz-agente-joz-%']);
            $ids = array_unique(array_merge(array_values($usuarios), array_map('intval', $familia->fetchAll(\PDO::FETCH_COLUMN))));
            //Las organizaciones de la familia: sus usuarios vuelven a la global y se van sus aprobaciones antes que ellas.
            $orgs = $database->prepare('SELECT id FROM `' . OrganizationMapper::TABLE . '` WHERE name LIKE ?');
            $orgs->execute(['zz-agente-joz-%']);
            foreach (array_unique(array_merge($organizaciones, array_map('intval', $orgs->fetchAll(\PDO::FETCH_COLUMN)))) as $idOrgLimpia) {
                $database->prepare("UPDATE `{$tabla}` SET organization = ? WHERE organization = ?")->execute([OrganizationMapper::INITIAL_ID_GLOBAL, $idOrgLimpia]);
                $database->prepare('DELETE FROM `' . Aprobaciones::TABLE . '` WHERE referenceTable = ? AND referenceValue = ?')->execute([OrganizationMapper::TABLE, (string) $idOrgLimpia]);
                $database->prepare('UPDATE `' . OrganizationMapper::TABLE . '` SET modifiedBy = NULL WHERE id = ?')->execute([$idOrgLimpia]);
                $database->prepare('DELETE FROM `' . OrganizationMapper::TABLE . '` WHERE id = ?')->execute([$idOrgLimpia]);
            }
            //Primero lo que cuelga de todos (el raíz de la prueba es autor de los perfiles y aprobaciones del alta); luego ellos.
            foreach ($ids as $id) {
                $database->prepare('DELETE FROM `pcsphp_user_sessions` WHERE userID = ?')->execute([$id]);
                $database->prepare('DELETE FROM `login_attempts` WHERE userID = ?')->execute([$id]);
                $database->prepare('DELETE FROM `' . LogsMapper::TABLE . '` WHERE createdBy = ? OR (referenceSource = ? AND referenceValue = ?)')->execute([$id, $tabla, (string) $id]);
                $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE (referenceTable = ? AND referenceValue = ?) OR createdBy = ? OR approvalBy = ?')->execute([$tabla, (string) $id, $id, $id]);
                $database->prepare('DELETE FROM user_system_profile WHERE belongsTo = ? OR createdBy = ? OR modifiedBy = ?')->execute([$id, $id, $id]);
            }
            foreach ($ids as $id) {
                $database->prepare("DELETE FROM `{$tabla}` WHERE id = ?")->execute([$id]);
            }
            $database->prepare('DELETE FROM `pcsphp_recovery_password` WHERE email LIKE ?')->execute(['zz-agente-joz-%']);
            $database->prepare('DELETE FROM `login_attempts` WHERE usernameAttempt LIKE ?')->execute(['zz-agente-joz-%']);
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `" . UsersModel::TABLE . "` WHERE username LIKE " . $database->quote('zz-agente-joz-%'))->fetchColumn()
         + (int) $database->query("SELECT COUNT(*) FROM `pcsphp_recovery_password` WHERE email LIKE " . $database->quote('zz-agente-joz-%'))->fetchColumn()
         + (int) $database->query("SELECT COUNT(*) FROM `login_attempts` WHERE usernameAttempt LIKE " . $database->quote('zz-agente-joz-%'))->fetchColumn()
         + (int) $database->query('SELECT COUNT(*) FROM `' . OrganizationMapper::TABLE . '` WHERE name LIKE ' . $database->quote('zz-agente-joz-%'))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario, organización, código ni intento de acceso de la prueba', (string) $quedan);
    }

    return $balance();

})->setDescription('El perfil, la recuperación de contraseña y el acceso rechazan lo que no vale (H522): cuerpo que no es objeto, contraseña vacía, comparaciones con conversiones, campos más largos que su columna, un token de un inactivo como sesión y anotaciones del uso normal en el registro de errores. Por HTTP, con usuarios zz-agente- que crea y retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
