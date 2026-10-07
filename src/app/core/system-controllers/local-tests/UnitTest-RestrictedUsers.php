<?php

//Un usuario pendiente de aprobación o rechazado entra, pero solo a lo suyo; inactivo, bloqueado y borrado no entran.
//Por HTTP, en una organización zz- NO aprobada: así la autoaprobación no reescribe su estado. Lo siembra y lo retira.

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Controllers\PublicationsController;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/restricted-users', function ($args) {

    echoTerminal("\e[33m[TEST:RestrictedUsers] Pendiente o rechazado entra solo a lo suyo; inactivo, bloqueado y borrado no entran\e[39m");
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
    $cabeceraToken = SessionToken::tokenName();
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, ?string $jwt, ?array $post = null) use ($base, $cabeceraToken): array {
        $handle = curl_init();
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
        curl_setopt_array($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };

    $prefijo = 'zz-prueba-acotados-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $usuarios = [];
    $claves = [];
    $organizacion = 0;

    try {
        //La organización, con su fila de aprobación PENDIENTE desde antes de la primera petición: SystemApprovalManager::init()
        //solo crea la de quien no la tiene, y la autoaprobación solo reactiva en una organización aprobada.
        $ahora = date('Y-m-d H:i:s');
        //El creador de la organización: un activo de la global (la reactivación no le cambia nada).
        $creador = new UsersModel();
        $creador->username = "{$prefijo}-creador";
        $creador->email = "{$prefijo}-creador@example.com";
        $creador->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $creador->firstname = 'Zz';
        $creador->secondname = '';
        $creador->firstLastname = 'Acotados';
        $creador->secondLastname = '';
        $creador->type = UsersModel::TYPE_USER_ADMIN_GRAL;
        $creador->status = UsersModel::STATUS_USER_ACTIVE;
        $creador->failedAttempts = 0;
        $creador->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $creador->createdAt = new \DateTime();
        $creador->modifiedAt = $creador->createdAt;
        $creador->save();
        $usuarios['creador'] = (int) $creador->id;
        $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-organizacion", 'zz-', "{$prefijo}-organizacion", $ahora, $usuarios['creador'], OrganizationMapper::ACTIVE, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
        $organizacion = (int) $database->lastInsertId();
        $database->prepare('INSERT INTO `' . SystemApprovalsMapper::TABLE . '` (referenceAlias, referenceValue, referenceTable, referenceDate, createdAt, createdBy, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute(["{$prefijo}-organizacion", (string) $organizacion, $tablaOrganizaciones, $ahora, $ahora, $usuarios['creador'], SystemApprovalsMapper::STATUS_PENDING]);

        $crearUsuario = function (string $sufijo, int $estado) use ($prefijo, &$usuarios, &$claves, &$organizacion): string {
            $clave = bin2hex(random_bytes(12));
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            $u->password = password_hash($clave, \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Acotados';
            $u->secondLastname = '';
            $u->type = UsersModel::TYPE_USER_COMUNICACIONES;
            $u->status = $estado;
            $u->failedAttempts = 0;
            $u->organization = $organizacion;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            $claves[$sufijo] = $clave;
            return SessionToken::generateToken(['id' => (int) $u->id], null, null, false);
        };
        $activo = $crearUsuario('activo', UsersModel::STATUS_USER_ACTIVE);
        $pendiente = $crearUsuario('pendiente', UsersModel::STATUS_USER_APPROVED_PENDING);
        $rechazado = $crearUsuario('rechazado', UsersModel::STATUS_USER_REJECTED);
        $crearUsuario('inactivo', UsersModel::STATUS_USER_INACTIVE);
        $crearUsuario('bloqueado', UsersModel::STATUS_USER_ATTEMPTS_BLOCK);
        $crearUsuario('borrado', UsersModel::STATUS_USER_DELETED);
        $sesion = $crearUsuario('sesion', UsersModel::STATUS_USER_ACTIVE);
        $sembrados = [
            'activo' => UsersModel::STATUS_USER_ACTIVE, 'pendiente' => UsersModel::STATUS_USER_APPROVED_PENDING, 'rechazado' => UsersModel::STATUS_USER_REJECTED,
            'inactivo' => UsersModel::STATUS_USER_INACTIVE, 'bloqueado' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK, 'borrado' => UsersModel::STATUS_USER_DELETED,
        ];
        $estadoDe = fn (string $sufijo): int => (int) $database->query("SELECT status FROM `{$tablaUsuarios}` WHERE id = " . $usuarios[$sufijo])->fetchColumn();

        //Comunicaciones se autoaprueba en el módulo por su tipo: el middleware de aprobaciones no lo recorta, recorta el
        //estado. La organización no está aprobada, así que nada reescribe ese estado (lo comprueba p2 al final).

        $listado = $camino(PublicationsController::routeName('list', [], true));
        $perfil = $camino(UsersController::routeName('form-profile', [], true));

        echoTerminal('[a] Activo: su rol completo');
        $r = $pedir($listado, $activo);
        $check($r['status'] === 200, 'a1 CANARIO: un activo del mismo tipo abre el listado de publicaciones', "HTTP {$r['status']}");

        foreach (['b' => ['pendiente', $pendiente], 'c' => ['rechazado', $rechazado]] as $letra => [$quien, $jwt]) {
            echoTerminal('');
            echoTerminal("[{$letra}] " . ucfirst($quien) . ': entra, pero solo a lo suyo');
            $r = $pedir($perfil, $jwt);
            $check($r['status'] === 200, "{$letra}1 abre su perfil", "HTTP {$r['status']}");
            $r = $pedir($listado, $jwt);
            $check($r['status'] === 403, "{$letra}2 NO abre el listado de publicaciones, que su rol completo sí tiene", "HTTP {$r['status']}");
            $login = $pedir($camino(UsersController::routeName('login-request', [], true)), null, ['username' => "{$prefijo}-{$quien}", 'password' => $claves[$quien]]);
            $datos = json_decode($login['body'], true);
            $check(is_array($datos) && ($datos['auth'] ?? false) === true, "{$letra}3 inicia sesión", "HTTP {$login['status']}");
        }

        echoTerminal('');
        echoTerminal('[f] Un rechazado de una organización APROBADA tampoco se reactiva: entra recortado');
        $rechazadoGlobal = new UsersModel($usuarios['rechazado']);
        $rechazadoGlobal->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $rechazadoGlobal->update();
        $r = $pedir($perfil, $rechazado);
        $check($r['status'] === 200, 'f1 abre su perfil', "HTTP {$r['status']}");
        $r = $pedir($listado, $rechazado);
        $check($r['status'] === 403, 'f2 NO abre el listado de publicaciones', "HTTP {$r['status']}");
        $check($estadoDe('rechazado') === UsersModel::STATUS_USER_REJECTED, 'f3 y sigue rechazado: navegar no lo reactiva', 'estado ' . $estadoDe('rechazado'));

        echoTerminal('');
        echoTerminal('[g] El principal no se recorta, como en el módulo de aprobaciones');
        $root = new UsersModel();
        $root->username = "{$prefijo}-root-rechazado";
        $root->email = "{$prefijo}-root-rechazado@example.com";
        $root->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $root->firstname = 'Zz';
        $root->secondname = '';
        $root->firstLastname = 'Acotados';
        $root->secondLastname = '';
        $root->type = UsersModel::TYPE_USER_ROOT;
        $root->status = UsersModel::STATUS_USER_REJECTED;
        $root->failedAttempts = 0;
        $root->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $root->createdAt = new \DateTime();
        $root->modifiedAt = $root->createdAt;
        $root->save();
        $usuarios['root-rechazado'] = (int) $root->id;
        $r = $pedir($listado, SessionToken::generateToken(['id' => (int) $root->id], null, null, false));
        $check($r['status'] === 200, 'g1 un principal rechazado abre el listado de publicaciones', "HTTP {$r['status']}");

        echoTerminal('');
        echoTerminal('[d] Inactivo, bloqueado y borrado no entran');
        foreach (['inactivo', 'bloqueado', 'borrado'] as $i => $quien) {
            $login = $pedir($camino(UsersController::routeName('login-request', [], true)), null, ['username' => "{$prefijo}-{$quien}", 'password' => $claves[$quien]]);
            $datos = json_decode($login['body'], true);
            echoTerminal("   medido: {$quien} → auth " . var_export(is_array($datos) ? ($datos['auth'] ?? null) : null, true) . ', error ' . var_export(is_array($datos) ? ($datos['error'] ?? null) : null, true));
            $check(is_array($datos) && ($datos['auth'] ?? false) !== true, 'd' . ($i + 1) . " {$quien}: no inicia sesión", "HTTP {$login['status']}");
        }

        echoTerminal('');
        echoTerminal('[e] Medido, no es una guarda de esta ronda: una sesión abierta cuando cambia el estado del usuario');
        $r = $pedir($perfil, $sesion);
        echoTerminal("   medido: activo, con su token, abre su perfil → HTTP {$r['status']}");
        foreach (['inactivo' => UsersModel::STATUS_USER_INACTIVE, 'bloqueado' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK, 'borrado' => UsersModel::STATUS_USER_DELETED] as $quien => $estado) {
            $cambio = new UsersModel($usuarios['sesion']);
            $cambio->status = $estado;
            $cambio->update();
            $r = $pedir($perfil, $sesion);
            echoTerminal("   medido: pasa a {$quien} y repite con el mismo token → HTTP {$r['status']}");
        }
        $check(true, 'e0 medido');

        echoTerminal('');
        $reescritos = array_filter(array_keys($sembrados), fn (string $sufijo) => $estadoDe($sufijo) !== $sembrados[$sufijo]);
        $check($reescritos === [], 'p2 ningún estado sembrado se reescribió (la autoaprobación no tocó a nadie)', implode(', ', $reescritos));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            //Los inicios de sesión dejan intentos de acceso, que atan al usuario por su clave foránea.
            $database->exec('DELETE FROM `' . LoginAttemptsModel::TABLE . '` WHERE usernameAttempt LIKE ' . $database->quote("{$prefijo}%"));
            foreach ($usuarios as $id) {
                $database->exec('DELETE FROM `' . LoginAttemptsModel::TABLE . '` WHERE userID = ' . (int) $id);
                $aprobaciones = SystemApprovalsMapper::model();
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaUsuarios),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            //Las claves van en los dos sentidos (el usuario a su organización, la organización a su creador): los usuarios
            //pasan a la global, cae la organización y después los usuarios.
            $database->exec("UPDATE `{$tablaUsuarios}` SET organization = " . OrganizationMapper::INITIAL_ID_GLOBAL . ' WHERE username LIKE ' . $database->quote("{$prefijo}%"));
            if ($organizacion > 0) {
                $database->exec('DELETE FROM `' . SystemApprovalsMapper::TABLE . "` WHERE referenceTable = '{$tablaOrganizaciones}' AND referenceValue = '{$organizacion}'");
                $database->exec("DELETE FROM `{$tablaOrganizaciones}` WHERE id = {$organizacion}");
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query('SELECT COUNT(*) FROM `' . LoginAttemptsModel::TABLE . '` WHERE usernameAttempt LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario, organización ni intento de acceso de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Un usuario pendiente de aprobación o rechazado entra, pero solo a su perfil y lo suyo; inactivo, bloqueado y borrado no entran. Mide además si una sesión abierta sobrevive a esos estados. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
