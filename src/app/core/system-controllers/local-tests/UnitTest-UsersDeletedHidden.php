<?php

//Un usuario eliminado (status 6, la baja propia) no vuelve por la interfaz (P380): sus rutas por id responden como un id
//que no existe, el guardado no le escribe, nadie pone el 6 por POST y él no entra. Por HTTP, con usuarios zz-.
//@areas nucleo

use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Notifications\Mappers\NotificationMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/users-deleted-hidden', function ($args) {

    echoTerminal("\e[33m[TEST:UsersDeletedHidden] Un usuario eliminado responde como uno que no existe\e[39m");
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
    if (!$check($base !== '' && $database !== null, 'p1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::headerName();
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, ?string $jwt, ?array $post = null) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array_merge($jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [], ['X-Requested-With: XMLHttpRequest']),
        ];
        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    $json = fn (array $r): array => is_array($decodificado = json_decode($r['body'], true)) ? $decodificado : [];
    $mensaje = fn (array $r): string => (string) ($json($r)['message'] ?? '');
    $exito = fn (array $r): bool => $r['status'] === 200 && ($json($r)['success'] ?? false) === true;
    //Lo que dice una respuesta sin enseñar un token: código, éxito y mensaje.
    $resumen = fn (array $r): string => "HTTP {$r['status']}, success " . var_export($json($r)['success'] ?? null, true) . ", «{$mensaje($r)}»";

    $prefijo = 'zz-prueba-usuarios-baja-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $usuarios = [];
    $claves = [];

    try {
        $crearUsuario = function (string $sufijo, int $tipo) use ($prefijo, &$usuarios, &$claves): int {
            $clave = bin2hex(random_bytes(12));
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            $u->password = password_hash($clave, \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Baja';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            $claves[$sufijo] = $clave;
            return (int) $u->id;
        };
        $root = UserSessions::issueToken($crearUsuario('root', UsersModel::TYPE_USER_ROOT));
        $activo = $crearUsuario('activo', UsersModel::TYPE_USER_GENERAL);
        $baja = $crearUsuario('baja', UsersModel::TYPE_USER_GENERAL);
        $fila = fn (int $id): array => (array) $database->query("SELECT status, firstname, sessionsValidFrom FROM `{$tablaUsuarios}` WHERE id = {$id}")->fetch(\PDO::FETCH_ASSOC);
        $inexistente = (int) $database->query("SELECT COALESCE(MAX(id), 0) + 1000 FROM `{$tablaUsuarios}`")->fetchColumn();

        $formulario = fn (int $id): array => $pedir($camino(UsersController::routeName('form-edit', ['id' => $id], true)), $root);
        $rutaEdicion = $camino(UsersController::routeName('edit-request', [], true));
        $campos = fn (int $id, string $sufijo, array $extra = []): array => array_merge([
            'id' => (string) $id,
            'username' => "{$prefijo}-{$sufijo}",
            'email' => "{$prefijo}-{$sufijo}@example.com",
            'firstname' => 'Zz',
            'secondname' => '',
            'first_lastname' => 'Baja',
            'second_lastname' => '',
            'status' => (string) UsersModel::STATUS_USER_ACTIVE,
            'organization' => (string) OrganizationMapper::INITIAL_ID_GLOBAL,
        ], $extra);
        $rutaAvatar = $camino((string) get_route('push-avatars', [], true));
        //Sin imagen: con permiso responde MISSING_OR_UNEXPECTED_PARAMS y no escribe; sin él, 403 antes de mirar la imagen.
        $avatar = fn (int $id): array => $pedir($rutaAvatar, $root, ['user_id' => (string) $id]);
        $rutaRevocar = $camino(UsersController::routeName('revoke-sessions-request', [], true));
        $revocar = fn (int $id): array => $pedir($rutaRevocar, $root, ['id' => (string) $id]);
        $rutaLogin = $camino(UsersController::routeName('login-request', [], true));
        $perfil = $camino(UsersController::routeName('form-profile', [], true));

        echoTerminal('[a] Canarios: un usuario activo se abre, se guarda y se gestiona');
        $r = $formulario($activo);
        $check($r['status'] === 200 && str_contains($r['body'], "{$prefijo}-activo"), 'a1 CANARIO: su formulario de edición se abre', "HTTP {$r['status']}");
        $r = $pedir($rutaEdicion, $root, $campos($activo, 'activo', ['firstname' => 'Zz Canario']));
        $check($exito($r) && $fila($activo)['firstname'] === 'Zz Canario', 'a2 CANARIO: su POST de edición se guarda', $resumen($r) . ', nombre ' . $fila($activo)['firstname']);
        $r = $avatar($activo);
        $check($r['status'] === 200 && ($json($r)['error'] ?? '') === 'MISSING_OR_UNEXPECTED_PARAMS', 'a3 CANARIO: su avatar pasa el permiso (sin imagen, pide la imagen)', "HTTP {$r['status']}");
        $r = $revocar($activo);
        $check($r['status'] === 200 && ($json($r)['success'] ?? null) === true, 'a4 CANARIO: cerrar sus sesiones se acepta', $resumen($r));

        echoTerminal('');
        echoTerminal('[b] Nadie pone el estado Eliminado por POST: la baja es solo la propia');
        $r = $pedir($rutaEdicion, $root, $campos($activo, 'activo', ['status' => (string) UsersModel::STATUS_USER_DELETED, 'firstname' => 'Zz Seis']));
        echoTerminal("   medido: root manda status=6 para otro usuario → {$resumen($r)}");
        $quedo = $fila($activo);
        $check(!$exito($r), 'b1 root mandando status=6 para otro usuario se rechaza', $resumen($r));
        $check((int) $quedo['status'] === UsersModel::STATUS_USER_ACTIVE && $quedo['firstname'] === 'Zz Canario', 'b2 y el usuario sigue como estaba', "estado {$quedo['status']}, nombre {$quedo['firstname']}");

        echoTerminal('');
        echoTerminal('[c] Se da de baja a sí mismo y deja de entrar');
        $loginBaja = fn (): array => $pedir($rutaLogin, null, ['username' => "{$prefijo}-baja", 'password' => $claves['baja']]);
        $r = $loginBaja();
        $check($r['status'] === 200 && ($json($r)['auth'] ?? null) === true, 'c1 CANARIO: activo, entra con su contraseña', "HTTP {$r['status']}, auth " . var_export($json($r)['auth'] ?? null, true));
        $tokenBaja = UserSessions::issueToken($baja);
        $r = $pedir($perfil, $tokenBaja);
        $check($r['status'] === 200, 'c2 CANARIO: activo, su token abre su perfil', "HTTP {$r['status']}");
        $r = $pedir($camino(UsersController::routeName('delete-account-request', [], true)), $tokenBaja, []);
        $check(($json($r)['success'] ?? null) === true && (int) $fila($baja)['status'] === UsersModel::STATUS_USER_DELETED, 'c3 su baja propia lo deja Eliminado (6)', "HTTP {$r['status']}, estado " . $fila($baja)['status']);
        $r = $loginBaja();
        echoTerminal('   medido: login del eliminado con su contraseña → HTTP ' . $r['status'] . ', auth ' . var_export($json($r)['auth'] ?? null, true) . ", «{$mensaje($r)}»");
        $check(($json($r)['auth'] ?? null) !== true, 'c4 eliminado, no entra con su contraseña', "HTTP {$r['status']}");
        $r = $pedir($perfil, $tokenBaja);
        echoTerminal("   medido: el token emitido antes de la baja, en su perfil → HTTP {$r['status']}");
        $check($r['status'] !== 200, 'c5 eliminado, el token de antes de la baja ya no abre su perfil', "HTTP {$r['status']}");

        echoTerminal('');
        echoTerminal('[d] Con root, sus rutas por id responden como las de un id que no existe (P380)');
        $nada = $formulario($inexistente);
        $eliminado = $formulario($baja);
        echoTerminal("   medido: formulario de edición, inexistente HTTP {$nada['status']}, eliminado HTTP {$eliminado['status']}");
        $check($eliminado['status'] === $nada['status'] && $eliminado['status'] !== 200 && !str_contains($eliminado['body'], "{$prefijo}-baja"), 'd1 DISCRIMINANTE: su formulario de edición responde como el de un id que no existe', "eliminado HTTP {$eliminado['status']}, inexistente HTTP {$nada['status']}");

        $antes = $fila($baja);
        $nada = $pedir($rutaEdicion, $root, $campos($inexistente, 'baja', ['firstname' => 'Zz Resucitado']));
        $eliminado = $pedir($rutaEdicion, $root, $campos($baja, 'baja', ['firstname' => 'Zz Resucitado']));
        echoTerminal("   medido: POST de edición, inexistente {$resumen($nada)}; eliminado {$resumen($eliminado)}");
        $despues = $fila($baja);
        $check($eliminado['status'] === $nada['status'] && $mensaje($eliminado) === $mensaje($nada) && !$exito($eliminado), 'd2 DISCRIMINANTE: su POST de edición responde como el de un id que no existe', "eliminado {$resumen($eliminado)}; inexistente {$resumen($nada)}");
        $check((int) $despues['status'] === UsersModel::STATUS_USER_DELETED && $despues['firstname'] === $antes['firstname'], 'd3 y sigue Eliminado, sin el cambio enviado', "estado {$despues['status']}, nombre {$despues['firstname']}");

        //Si el guardado lo resucitó, vuelve a Eliminado: lo que sigue mide sus otras rutas, no esta.
        $database->exec("UPDATE `{$tablaUsuarios}` SET status = " . UsersModel::STATUS_USER_DELETED . " WHERE id = {$baja}");

        $nada = $avatar($inexistente);
        $eliminado = $avatar($baja);
        echoTerminal("   medido: avatar, inexistente HTTP {$nada['status']}, eliminado HTTP {$eliminado['status']}");
        $check($eliminado['status'] === $nada['status'] && $eliminado['status'] !== 200, 'd4 su avatar responde como el de un id que no existe', "eliminado HTTP {$eliminado['status']}, inexistente HTTP {$nada['status']}");

        //Una marca vieja y conocida: la que pondría la ruta lleva la hora de ahora, y en el mismo segundo no se distinguiría.
        $database->exec("UPDATE `{$tablaUsuarios}` SET sessionsValidFrom = '2000-01-01 00:00:00' WHERE id = {$baja}");
        $antes = $fila($baja);
        $nada = $revocar($inexistente);
        $eliminado = $revocar($baja);
        echoTerminal("   medido: cerrar sesiones, inexistente {$resumen($nada)}; eliminado {$resumen($eliminado)}");
        $despues = $fila($baja);
        $check($eliminado['status'] === $nada['status'] && $mensaje($eliminado) === $mensaje($nada) && !str_contains($eliminado['body'], "{$prefijo}-baja"), 'd5 cerrar sus sesiones responde como con un id que no existe, sin nombrarlo', "eliminado {$resumen($eliminado)}; inexistente {$resumen($nada)}");
        $check($despues['sessionsValidFrom'] === $antes['sessionsValidFrom'], 'd6 y no le escribe la marca de sesiones', "antes {$antes['sessionsValidFrom']}, después {$despues['sessionsValidFrom']}");
        $avisos = (int) $database->query('SELECT COUNT(*) FROM `' . NotificationMapper::TABLE . "` WHERE `user` = {$baja}")->fetchColumn();
        $check($avisos === 0, 'd7 ni le deja un aviso', "avisos {$avisos}");
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $database->exec('DELETE FROM `' . LoginAttemptsModel::TABLE . '` WHERE usernameAttempt LIKE ' . $database->quote("{$prefijo}%"));
            foreach ($usuarios as $id) {
                $database->prepare('DELETE FROM `' . UserSessions::TABLE . '` WHERE userID = ?')->execute([$id]);
                $database->prepare('DELETE FROM `' . LoginAttemptsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
                $database->prepare('DELETE FROM `' . NotificationMapper::TABLE . '` WHERE `user` = ?')->execute([$id]);
                $database->prepare('DELETE FROM `' . LogsMapper::TABLE . '` WHERE createdBy = ? OR (referenceSource = ? AND referenceValue = ?)')->execute([$id, $tablaUsuarios, (string) $id]);
                $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue = ?')->execute([$tablaUsuarios, (string) $id]);
                $database->prepare('DELETE FROM user_system_profile WHERE belongsTo = ?')->execute([$id]);
                $database->prepare("DELETE FROM `{$tablaUsuarios}` WHERE id = ?")->execute([$id]);
            }
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $ids = $usuarios !== [] ? implode(',', array_map('intval', $usuarios)) : '0';
        $quedan = [
            'usuarios' => (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn(),
            'perfiles' => (int) $database->query("SELECT COUNT(*) FROM user_system_profile WHERE belongsTo IN ({$ids})")->fetchColumn(),
            'sesiones' => (int) $database->query('SELECT COUNT(*) FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$ids})")->fetchColumn(),
            'intentos' => (int) $database->query('SELECT COUNT(*) FROM `' . LoginAttemptsModel::TABLE . "` WHERE userID IN ({$ids}) OR usernameAttempt LIKE " . $database->quote("{$prefijo}%"))->fetchColumn(),
            'avisos' => (int) $database->query('SELECT COUNT(*) FROM `' . NotificationMapper::TABLE . "` WHERE `user` IN ({$ids})")->fetchColumn(),
            'aprobaciones' => (int) $database->query('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ' . $database->quote($tablaUsuarios) . " AND referenceValue IN ('" . implode("','", array_map('intval', $usuarios ?: [0])) . "')")->fetchColumn(),
        ];
        $check(array_sum($quedan) === 0, 'z1 no queda ningún usuario, perfil, sesión, intento, aviso ni aprobación de la prueba', json_encode($quedan) ?: '');
    }

    return $balance();

})->setDescription('Un usuario eliminado (status 6, por su baja propia) responde como un id que no existe en su formulario de edición, su POST de edición (que no le escribe), su avatar y el cierre de sus sesiones; root no pone el estado Eliminado por POST a otro; y el eliminado no entra ni con su contraseña ni con un token de antes de la baja (P380). Por HTTP, con usuarios zz- que crea y retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
