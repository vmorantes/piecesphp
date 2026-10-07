<?php

//Sesión, desbloqueo, aprobaciones, canManage y su listado solo mueven los estados que les tocan. Por HTTP donde hay ruta; los
//manejadores de aprobación, en proceso. Siembra zz- y lo retira.

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\TicketsLogModel;
use PiecesPHP\UserSystem\ORM\UserProblemsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use PiecesPHP\UserSystem\UserDataPackage;
use Publications\Controllers\PublicationsController;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Controllers\SystemApprovalsController;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\Util\Packages\OrganizationApprovalHandler;
use SystemApprovals\Util\Packages\UsersApprovalHandler;

CliActions::make('unit-tests:core/status-transitions', function ($args) {

    echoTerminal("\e[33m[TEST:StatusTransitions] Sesión, desbloqueo y aprobaciones solo mueven los estados que les tocan\e[39m");
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

    $prefijo = 'zz-prueba-estados-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $tablaAprobaciones = SystemApprovalsMapper::TABLE;
    $tablaProblemas = (new UserProblemsModel())->getModel()->getTable();
    $tablaSolicitudes = (new TicketsLogModel())->getModel()->getTable();
    $tablaCorreo = MailLogMapper::TABLE;
    //Los envíos reales de esta prueba (solo si una guarda de generateCode falta) van a estos buzones y quedan en el registro.
    $buzonesPrueba = '%zz-prueba-estados-%@mailinator.com%';
    $usuarios = [];
    $claves = [];
    $organizaciones = [];
    $publicaciones = [];

    try {
        $ahora = date('Y-m-d H:i:s');
        $crearUsuario = function (string $sufijo, int $estado, int $tipo, ?int $organizacion, string $correo = '') use ($prefijo, &$usuarios, &$claves): int {
            $clave = bin2hex(random_bytes(12));
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = $correo !== '' ? $correo : "{$prefijo}-{$sufijo}@example.com";
            $u->password = password_hash($clave, \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Estados';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = $estado;
            $u->failedAttempts = 0;
            $u->organization = $organizacion ?? OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            $claves[$sufijo] = $clave;
            return (int) $u->id;
        };
        $filaAprobacion = function (string $tabla, int $referencia, int $creador, string $estado, string $alias) use ($database, $tablaAprobaciones, $ahora): int {
            $database->prepare("INSERT INTO `{$tablaAprobaciones}` (referenceAlias, referenceValue, referenceTable, referenceDate, createdAt, createdBy, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$alias, (string) $referencia, $tabla, $ahora, $ahora, $creador, $estado]);
            return (int) $database->lastInsertId();
        };
        $estadoAprobacion = fn (int $usuario): ?string => ($v = $database->query("SELECT status FROM `{$tablaAprobaciones}` WHERE referenceTable = '{$tablaUsuarios}' AND referenceValue = '{$usuario}'")->fetchColumn()) === false ? null : (string) $v;
        //Organización con su fila de aprobación PENDIENTE desde antes de la primera petición: la autoaprobación solo reactiva
        //en una organización aprobada (pendientes.md 387.1), y así nada reescribe los estados sembrados.
        $crearOrganizacion = function (string $sufijo, int $creador) use ($database, $prefijo, $tablaOrganizaciones, $ahora, $filaAprobacion, &$organizaciones): int {
            $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-{$sufijo}", 'zz-' . bin2hex(random_bytes(4)), "{$prefijo}-{$sufijo}", $ahora, $creador, OrganizationMapper::ACTIVE, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
            $id = (int) $database->lastInsertId();
            $organizaciones[] = $id;
            $filaAprobacion($tablaOrganizaciones, $id, $creador, SystemApprovalsMapper::STATUS_PENDING, "{$prefijo}-{$sufijo}");
            return $id;
        };
        $estadoDe = fn (int $id): int => (int) $database->query("SELECT status FROM `{$tablaUsuarios}` WHERE id = {$id}")->fetchColumn();
        //Con $guardado, el estado que tenía al bloquearse, como lo deja changeStatus().
        $ponerEstado = function (int $id, int $estado, int $intentos = 0, ?int $guardado = null) use ($database, $tablaUsuarios): void {
            $meta = $guardado !== null ? $database->quote((string) json_encode([UsersModel::META_STATUS_BEFORE_BLOCK => $guardado])) : 'NULL';
            $database->exec("UPDATE `{$tablaUsuarios}` SET status = {$estado}, failedAttempts = {$intentos}, meta = {$meta} WHERE id = {$id}");
        };
        //La fila de aprobación del perfil, con el estado que la resolución de una persona le dejaría.
        $ponerFila = function (int $id, string $estado) use ($database, $tablaAprobaciones, $tablaUsuarios, $filaAprobacion, $prefijo): void {
            $existe = (int) $database->query("SELECT COUNT(*) FROM `{$tablaAprobaciones}` WHERE referenceTable = '{$tablaUsuarios}' AND referenceValue = '{$id}'")->fetchColumn();
            if ($existe > 0) {
                $database->exec("UPDATE `{$tablaAprobaciones}` SET status = " . $database->quote($estado) . " WHERE referenceTable = '{$tablaUsuarios}' AND referenceValue = '{$id}'");
            } else {
                $filaAprobacion($tablaUsuarios, $id, $id, $estado, "{$prefijo}-fila-{$id}");
            }
        };

        //El creador de las organizaciones: un activo de la global, que ninguna reactivación cambia.
        $creador = $crearUsuario('creador', UsersModel::STATUS_USER_ACTIVE, UsersModel::TYPE_USER_ADMIN_GRAL, null);
        $orgSesion = $crearOrganizacion('org-sesion', $creador);
        $orgMiembros = $crearOrganizacion('org-miembros', $creador);

        $perfil = $camino(UsersController::routeName('form-profile', [], true));
        $login = $camino(UsersController::routeName('login-request', [], true));
        $desbloquear = $camino(UserProblemsController::routeName('blocked-resolve', [], true));
        $pedirCodigo = $camino(UserProblemsController::routeName('blocked-request-code', [], true));

        //──── [a] Una sesión abierta y el estado que cambia ─────────────────────────────────────
        echoTerminal('[a] Una sesión abierta cuando el estado cambia: inactivo y borrado la cortan; el bloqueo protege la contraseña, no echa');
        $sesion = $crearUsuario('sesion', UsersModel::STATUS_USER_ACTIVE, UsersModel::TYPE_USER_COMUNICACIONES, $orgSesion);
        $jwtSesion = SessionToken::generateToken(['id' => $sesion], null, null, false);
        $r = $pedir($perfil, $jwtSesion);
        $check($r['status'] === 200, 'a1 CANARIO: activo, con su token, abre su perfil', "HTTP {$r['status']}");
        $casosSesion = [
            'bloqueado' => [UsersModel::STATUS_USER_ATTEMPTS_BLOCK, true],
            'inactivo' => [UsersModel::STATUS_USER_INACTIVE, false],
            'borrado' => [UsersModel::STATUS_USER_DELETED, false],
            'pendiente' => [UsersModel::STATUS_USER_APPROVED_PENDING, true],
            'rechazado' => [UsersModel::STATUS_USER_REJECTED, true],
        ];
        $n = 2;
        foreach ($casosSesion as $quien => [$estado, $sigue]) {
            $ponerEstado($sesion, $estado);
            $r = $pedir($perfil, $jwtSesion);
            echoTerminal("   medido: pasa a {$quien} ({$estado}) y repite con el mismo token → HTTP {$r['status']}");
            $check(($r['status'] === 200) === $sigue, "a{$n} {$quien}: " . ($sigue ? 'sigue en sesión' : 'la sesión se corta'), "HTTP {$r['status']}");
            $n++;
        }
        //El diseño (pendientes.md 390.1): cuatro contraseñas erradas de cualquiera no echan a nadie, pero el bloqueado no
        //abre una sesión nueva ni con la contraseña buena.
        $ponerEstado($sesion, UsersModel::STATUS_USER_ATTEMPTS_BLOCK);
        $r = $pedir($login, null, ['username' => "{$prefijo}-sesion", 'password' => $claves['sesion']]);
        $datos = json_decode($r['body'], true);
        $check(is_array($datos) && ($datos['auth'] ?? false) !== true, "a{$n} bloqueado: no inicia una sesión nueva con su contraseña", mb_substr($r['body'], 0, 160));
        $n++;
        $ponerEstado($sesion, UsersModel::STATUS_USER_ACTIVE);
        $r = $pedir($perfil, $jwtSesion);
        $check($r['status'] === 200, "a{$n} CANARIO: vuelto a activo, el mismo token vuelve a entrar", "HTTP {$r['status']}");

        //──── [b] El desbloqueo ──────────────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[b] El código de desbloqueo solo desbloquea al bloqueado, y le devuelve el estado que tenía');
        //El código se siembra en la base: pedirlo por la ruta envía un correo.
        $sembrarCodigo = function (int $id) use ($database, $tablaUsuarios, $tablaProblemas): string {
            $correo = (string) $database->query("SELECT email FROM `{$tablaUsuarios}` WHERE id = {$id}")->fetchColumn();
            //La columna es entera: el código no puede llevar el prefijo zz-; se borra por correo.
            $codigo = (string) random_int(100000000, 999999999);
            $database->prepare("INSERT INTO `{$tablaProblemas}` (email, code, created, expired, type) VALUES (?, ?, ?, ?, ?)")
                ->execute([$correo, $codigo, date('Y-m-d H:i:s'), date('Y-m-d H:i:s', time() + 3600), UserProblemsController::TYPE_USER_BLOCKED]);
            return $codigo;
        };
        $usarCodigo = function (int $id) use ($pedir, $desbloquear, $sembrarCodigo): array {
            $r = $pedir($desbloquear, null, ['code' => $sembrarCodigo($id), 'type' => UserProblemsController::TYPE_USER_BLOCKED]);
            $datos = json_decode($r['body'], true);
            return is_array($datos) ? $datos : ['http' => $r['status']];
        };
        $intentosDe = fn (int $id): int => (int) $database->query("SELECT failedAttempts FROM `{$tablaUsuarios}` WHERE id = {$id}")->fetchColumn();

        //b1-b4: no bloqueados con los intentos al máximo. Antes, el código los dejaba activos a todos.
        $casosNoBloqueados = [
            'nb-pendiente' => UsersModel::STATUS_USER_APPROVED_PENDING,
            'nb-rechazado' => UsersModel::STATUS_USER_REJECTED,
            'nb-inactivo' => UsersModel::STATUS_USER_INACTIVE,
            'nb-borrado' => UsersModel::STATUS_USER_DELETED,
        ];
        $n = 1;
        foreach ($casosNoBloqueados as $sufijo => $estado) {
            $id = $crearUsuario($sufijo, $estado, UsersModel::TYPE_USER_GENERAL, $orgMiembros);
            $ponerEstado($id, $estado, UsersController::MAX_ATTEMPTS);
            $datos = $usarCodigo($id);
            $queda = $estadoDe($id);
            echoTerminal("   medido: {$sufijo} ({$estado}), intentos " . UsersController::MAX_ATTEMPTS . ' → estado ' . $queda . ', success ' . var_export($datos['success'] ?? null, true));
            $check($queda === $estado && ($datos['success'] ?? null) !== true, "b{$n} {$sufijo} ({$estado}): el código no lo mueve", "queda en {$queda}");
            $n++;
        }

        //b5-b7: bloqueados de verdad, por el login, desde activo, pendiente y rechazado (los tres que entran).
        $bloquearPorLogin = function (string $sufijo) use ($pedir, $login, $prefijo): void {
            for ($i = 0; $i < UsersController::MAX_ATTEMPTS; $i++) {
                //RETORNO-IGNORADO: lo que se mide es el estado en la base.
                $pedir($login, null, ['username' => "{$prefijo}-{$sufijo}", 'password' => 'zz-incorrecta']);
            }
        };
        $casosBloqueados = [
            'bl-activo' => UsersModel::STATUS_USER_ACTIVE,
            'bl-pendiente' => UsersModel::STATUS_USER_APPROVED_PENDING,
            'bl-rechazado' => UsersModel::STATUS_USER_REJECTED,
        ];
        foreach ($casosBloqueados as $sufijo => $estado) {
            $id = $crearUsuario($sufijo, $estado, UsersModel::TYPE_USER_GENERAL, $orgMiembros);
            $bloquearPorLogin($sufijo);
            $bloqueado = $estadoDe($id);
            $check($bloqueado === UsersModel::STATUS_USER_ATTEMPTS_BLOCK, "b{$n} banco: {$sufijo} queda bloqueado tras " . UsersController::MAX_ATTEMPTS . ' contraseñas erradas', "queda en {$bloqueado}");
            $n++;
            $datos = $usarCodigo($id);
            $queda = $estadoDe($id);
            echoTerminal("   medido: {$sufijo} bloqueado y desbloqueado → estado {$queda}, intentos " . $intentosDe($id) . ', success ' . var_export($datos['success'] ?? null, true));
            $check($queda === $estado && $intentosDe($id) === 0 && ($datos['success'] ?? null) === true, "b{$n} {$sufijo}: el código lo desbloquea y vuelve a {$estado}", "queda en {$queda}");
            $n++;
        }
        //Las demás claves de `meta` (los filtros guardados) sobreviven al bloqueo y al desbloqueo: los dos escriben solo su clave.
        $id = $crearUsuario('mt-presets', UsersModel::STATUS_USER_ACTIVE, UsersModel::TYPE_USER_GENERAL, $orgMiembros);
        $database->exec("UPDATE `{$tablaUsuarios}` SET meta = " . $database->quote('{"dataTransferPresets":{"zz":{"a":"b"}}}') . " WHERE id = {$id}");
        $bloquearPorLogin('mt-presets');
        $metaDe = fn (int $id): array => (array) json_decode((string) $database->query("SELECT meta FROM `{$tablaUsuarios}` WHERE id = {$id}")->fetchColumn(), true);
        $meta = $metaDe($id);
        $check(($meta['dataTransferPresets']['zz']['a'] ?? null) === 'b' && ($meta[UsersModel::META_STATUS_BEFORE_BLOCK] ?? null) === UsersModel::STATUS_USER_ACTIVE, "b{$n} al bloquear, meta conserva los filtros y guarda el estado", (string) json_encode($meta));
        $n++;
        $usarCodigo($id);
        $meta = $metaDe($id);
        $check(($meta['dataTransferPresets']['zz']['a'] ?? null) === 'b' && !array_key_exists(UsersModel::META_STATUS_BEFORE_BLOCK, $meta) && $estadoDe($id) === UsersModel::STATUS_USER_ACTIVE, "b{$n} al desbloquear, conserva los filtros y quita solo el estado guardado", (string) json_encode($meta));
        $n++;
        $r = $pedir($login, null, ['username' => "{$prefijo}-bl-activo", 'password' => $claves['bl-activo']]);
        $datos = json_decode($r['body'], true);
        $check(is_array($datos) && ($datos['auth'] ?? false) === true, "b{$n} CANARIO: el activo desbloqueado vuelve a iniciar sesión", "HTTP {$r['status']}");
        $n++;

        //Sin estado guardado (bloqueos de antes de esta versión): activo si su perfil está aprobado o su tipo se aprueba solo.
        $casosSinGuardar = [
            'sg-comunicaciones' => [UsersModel::TYPE_USER_COMUNICACIONES, null, UsersModel::STATUS_USER_ACTIVE],
            'sg-general-aprobado' => [UsersModel::TYPE_USER_GENERAL, SystemApprovalsMapper::STATUS_APPROVED, UsersModel::STATUS_USER_ACTIVE],
            'sg-general-pendiente' => [UsersModel::TYPE_USER_GENERAL, SystemApprovalsMapper::STATUS_PENDING, UsersModel::STATUS_USER_APPROVED_PENDING],
        ];
        foreach ($casosSinGuardar as $sufijo => [$tipo, $aprobacion, $esperado]) {
            $id = $crearUsuario($sufijo, UsersModel::STATUS_USER_ATTEMPTS_BLOCK, $tipo, $orgMiembros);
            if ($aprobacion !== null) {
                $filaAprobacion($tablaUsuarios, $id, $id, $aprobacion, "{$prefijo}-{$sufijo}");
            }
            $ponerEstado($id, UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersController::MAX_ATTEMPTS);
            $datos = $usarCodigo($id);
            $queda = $estadoDe($id);
            echoTerminal("   medido: {$sufijo}, bloqueado sin estado guardado → estado {$queda}");
            $check($queda === $esperado && ($datos['success'] ?? null) === true, "b{$n} {$sufijo}: sin estado guardado queda en {$esperado}", "queda en {$queda}");
            $n++;
        }

        //La fila de aprobación manda (pendientes.md 390.2). En la global, que está aprobada: el caso en que la autoaprobación
        //reactivaba al navegar.
        $global = OrganizationMapper::INITIAL_ID_GLOBAL;
        $check(\SystemApprovals\Util\SystemApprovalManager::getInstance()->isApproved(OrganizationMapper::class, $global), "b{$n} banco: la organización global está aprobada");
        $n++;
        $navegar = function (int $id) use ($pedir, $perfil): int {
            return $pedir($perfil, SessionToken::generateToken(['id' => $id], null, null, false))['status'];
        };
        $id = $crearUsuario('sg-general-rechazado', UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersModel::TYPE_USER_GENERAL, $global);
        $ponerFila($id, SystemApprovalsMapper::STATUS_REJECTED);
        $ponerEstado($id, UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersController::MAX_ATTEMPTS);
        $datos = $usarCodigo($id);
        $queda = $estadoDe($id);
        $check($queda === UsersModel::STATUS_USER_REJECTED && ($datos['success'] ?? null) === true, "b{$n} rechazado y bloqueado sin estado guardado, en organización aprobada: el código lo deja rechazado", "queda en {$queda}");
        $n++;
        $http = $navegar($id);
        $check($estadoDe($id) === UsersModel::STATUS_USER_REJECTED && $estadoAprobacion($id) === SystemApprovalsMapper::STATUS_REJECTED, "b{$n} y navegar (HTTP {$http}) no lo reactiva: sigue rechazado, con la fila rechazada", 'estado ' . $estadoDe($id) . ', fila ' . var_export($estadoAprobacion($id), true));
        $n++;

        $id = $crearUsuario('ov-rechazado', UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersModel::TYPE_USER_GENERAL, $orgMiembros);
        $ponerFila($id, SystemApprovalsMapper::STATUS_REJECTED);
        $ponerEstado($id, UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersController::MAX_ATTEMPTS, UsersModel::STATUS_USER_ACTIVE);
        $usarCodigo($id);
        $queda = $estadoDe($id);
        $check($queda === UsersModel::STATUS_USER_REJECTED, "b{$n} guardado activo pero fila rechazada: la fila manda y vuelve rechazado", "queda en {$queda}");
        $n++;

        //Resuelto DURANTE el bloqueo: sigue bloqueado, se mueve lo guardado, y al desbloquear vuelve con la resolución.
        $casosDurante = [
            'tr-aprobado' => [UsersModel::STATUS_USER_APPROVED_PENDING, 'aprobar', SystemApprovalsMapper::STATUS_APPROVED, UsersModel::STATUS_USER_ACTIVE],
            'tr-rechazado' => [UsersModel::STATUS_USER_ACTIVE, 'rechazar', SystemApprovalsMapper::STATUS_REJECTED, UsersModel::STATUS_USER_REJECTED],
        ];
        foreach ($casosDurante as $sufijo => [$antes, $accion, $fila, $esperado]) {
            $id = $crearUsuario($sufijo, $antes, UsersModel::TYPE_USER_GENERAL, $orgMiembros);
            $bloquearPorLogin($sufijo);
            $accion === 'aprobar' ? UsersApprovalHandler::onApproved(new UsersModel($id)) : UsersApprovalHandler::onRejected(new UsersModel($id));
            $guardado = UsersModel::statusBeforeBlock($id);
            $check($estadoDe($id) === UsersModel::STATUS_USER_ATTEMPTS_BLOCK && $guardado === $esperado, "b{$n} {$sufijo}: al {$accion} durante el bloqueo sigue bloqueado y lo guardado pasa a {$esperado}", 'estado ' . $estadoDe($id) . ', guardado ' . var_export($guardado, true));
            $n++;
            $ponerFila($id, $fila);
            $usarCodigo($id);
            $queda = $estadoDe($id);
            $check($queda === $esperado, "b{$n} {$sufijo}: al desbloquear vuelve en {$esperado}", "queda en {$queda}");
            $n++;
        }

        //La autoaprobación no deshace un rechazo: un pendiente con la fila rechazada, en organización aprobada.
        $id = $crearUsuario('ar-pendiente-rechazado', UsersModel::STATUS_USER_APPROVED_PENDING, UsersModel::TYPE_USER_GENERAL, $global);
        $ponerFila($id, SystemApprovalsMapper::STATUS_REJECTED);
        $http = $navegar($id);
        $check($estadoDe($id) === UsersModel::STATUS_USER_APPROVED_PENDING && $estadoAprobacion($id) === SystemApprovalsMapper::STATUS_REJECTED, "b{$n} pendiente con fila rechazada: navegar (HTTP {$http}) no reescribe la fila ni lo activa", 'estado ' . $estadoDe($id) . ', fila ' . var_export($estadoAprobacion($id), true));
        $n++;
        $id = $crearUsuario('ar-pendiente-canario', UsersModel::STATUS_USER_APPROVED_PENDING, UsersModel::TYPE_USER_GENERAL, $global);
        $ponerFila($id, SystemApprovalsMapper::STATUS_PENDING);
        $http = $navegar($id);
        $check($estadoDe($id) === UsersModel::STATUS_USER_ACTIVE && $estadoAprobacion($id) === SystemApprovalsMapper::STATUS_APPROVED, "b{$n} CANARIO: con la fila pendiente, navegar sí lo aprueba", 'estado ' . $estadoDe($id) . ', fila ' . var_export($estadoAprobacion($id), true));
        $n++;

        //Pedir el código: a quien no está bloqueado no se le crea ni se le envía, y la respuesta es la misma.
        //El correo es de mailinator (ADR 0011): si la guarda se quita, el envío va a un buzón permitido.
        $correoNoBloqueado = 'zz-prueba-estados-' . bin2hex(random_bytes(3)) . '@mailinator.com';
        $crearUsuario('pc-activo', UsersModel::STATUS_USER_ACTIVE, UsersModel::TYPE_USER_GENERAL, $orgMiembros, $correoNoBloqueado);
        $cuentaCodigos = fn (string $correo): int => (int) $database->query("SELECT COUNT(*) FROM `{$tablaProblemas}` WHERE email = " . $database->quote($correo))->fetchColumn();
        echoTerminal("   correo del activo que pide el código: {$correoNoBloqueado}");
        $r = $pedir($pedirCodigo, null, ['username' => "{$prefijo}-pc-activo", 'type' => UserProblemsController::TYPE_USER_BLOCKED]);
        $datos = json_decode($r['body'], true);
        $check($cuentaCodigos($correoNoBloqueado) === 0, "b{$n} pedir el código para un activo no crea código (ni correo)", 'códigos ' . $cuentaCodigos($correoNoBloqueado));
        $n++;
        $check(is_array($datos) && ($datos['send_mail'] ?? null) === true && ($datos['error'] ?? null) === UserProblemsController::NO_ERROR, "b{$n} y la respuesta es la de un envío: no dice si está bloqueado", mb_substr($r['body'], 0, 200));
        $n++;
        $r = $pedir($pedirCodigo, null, ['username' => "{$prefijo}-no-existe", 'type' => UserProblemsController::TYPE_USER_BLOCKED]);
        $datos = json_decode($r['body'], true);
        $check(is_array($datos) && ($datos['error'] ?? null) === 'USER_NO_EXISTS', "b{$n} CANARIO: la ruta responde y valida (un usuario que no existe)", mb_substr($r['body'], 0, 200));

        //──── [c] Aprobar y rechazar una organización ────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[c] Aprobar una organización solo activa a sus pendientes; rechazarla solo devuelve a pendiente a sus activos');
        $estadosMiembros = [
            'm-inactivo' => UsersModel::STATUS_USER_INACTIVE,
            'm-activo' => UsersModel::STATUS_USER_ACTIVE,
            'm-bloqueado' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK,
            'm-pendiente' => UsersModel::STATUS_USER_APPROVED_PENDING,
            'm-rechazado' => UsersModel::STATUS_USER_REJECTED,
            'm-borrado' => UsersModel::STATUS_USER_DELETED,
            'm-bloq-desde-3' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK,
            'm-bloq-desde-1' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK,
        ];
        //Los bloqueados con estado guardado: la transición se aplica a lo guardado y siguen en 2.
        $guardadosMiembros = ['m-bloq-desde-3' => UsersModel::STATUS_USER_APPROVED_PENDING, 'm-bloq-desde-1' => UsersModel::STATUS_USER_ACTIVE];
        $orgAprobar = $crearOrganizacion('org-aprobar', $creador);
        $miembros = [];
        foreach ($estadosMiembros as $sufijo => $estado) {
            $miembros[$sufijo] = $crearUsuario($sufijo, $estado, UsersModel::TYPE_USER_GENERAL, $orgAprobar);
            $filaAprobacion($tablaUsuarios, $miembros[$sufijo], $miembros[$sufijo], SystemApprovalsMapper::STATUS_PENDING, "{$prefijo}-{$sufijo}");
        }
        $sembrar = function () use ($miembros, $estadosMiembros, $guardadosMiembros, $ponerEstado, $database, $tablaAprobaciones, $tablaUsuarios): void {
            foreach ($miembros as $sufijo => $id) {
                $ponerEstado($id, $estadosMiembros[$sufijo], 0, $guardadosMiembros[$sufijo] ?? null);
                $database->exec("UPDATE `{$tablaAprobaciones}` SET status = " . $database->quote(SystemApprovalsMapper::STATUS_PENDING) . " WHERE referenceTable = '{$tablaUsuarios}' AND referenceValue = '{$id}'");
            }
        };
        $tras = [
            'aprobar' => [
                'm-inactivo' => UsersModel::STATUS_USER_INACTIVE, 'm-activo' => UsersModel::STATUS_USER_ACTIVE, 'm-bloqueado' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK,
                'm-pendiente' => UsersModel::STATUS_USER_ACTIVE, 'm-rechazado' => UsersModel::STATUS_USER_REJECTED, 'm-borrado' => UsersModel::STATUS_USER_DELETED,
                'm-bloq-desde-3' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK, 'm-bloq-desde-1' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK,
            ],
            'rechazar' => [
                'm-inactivo' => UsersModel::STATUS_USER_INACTIVE, 'm-activo' => UsersModel::STATUS_USER_APPROVED_PENDING, 'm-bloqueado' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK,
                'm-pendiente' => UsersModel::STATUS_USER_APPROVED_PENDING, 'm-rechazado' => UsersModel::STATUS_USER_REJECTED, 'm-borrado' => UsersModel::STATUS_USER_DELETED,
                'm-bloq-desde-3' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK, 'm-bloq-desde-1' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK,
            ],
        ];
        //Lo guardado de los bloqueados tras cada acción, sobre la organización y sobre el perfil.
        $guardadosTras = [
            'organización aprobar' => ['m-bloq-desde-3' => UsersModel::STATUS_USER_ACTIVE, 'm-bloq-desde-1' => UsersModel::STATUS_USER_ACTIVE],
            'organización rechazar' => ['m-bloq-desde-3' => UsersModel::STATUS_USER_APPROVED_PENDING, 'm-bloq-desde-1' => UsersModel::STATUS_USER_APPROVED_PENDING],
            'perfil aprobar' => ['m-bloq-desde-3' => UsersModel::STATUS_USER_ACTIVE, 'm-bloq-desde-1' => UsersModel::STATUS_USER_ACTIVE],
            'perfil rechazar' => ['m-bloq-desde-3' => UsersModel::STATUS_USER_REJECTED, 'm-bloq-desde-1' => UsersModel::STATUS_USER_REJECTED],
        ];
        $comprobarGuardados = function (string $letra, int &$n, string $clave) use ($guardadosTras, $miembros, $check): void {
            foreach ($guardadosTras[$clave] as $sufijo => $esperado) {
                $guardado = UsersModel::statusBeforeBlock($miembros[$sufijo]);
                $check($guardado === $esperado, "{$letra}{$n} {$clave}: lo guardado de {$sufijo} pasa a {$esperado}", 'guardado ' . var_export($guardado, true));
                $n++;
            }
        };
        //Las filas de aprobación que la aprobación de la organización pasa a aprobadas: solo las de quien queda activo.
        $filasAprobadas = ['m-activo', 'm-pendiente', 'm-bloq-desde-3', 'm-bloq-desde-1'];
        $n = 1;
        foreach ($tras as $accion => $esperados) {
            $sembrar();
            $accion === 'aprobar'
                ? OrganizationApprovalHandler::onApproved(new OrganizationMapper($orgAprobar))
                : OrganizationApprovalHandler::onRejected(new OrganizationMapper($orgAprobar));
            $medido = [];
            foreach ($miembros as $sufijo => $id) {
                $medido[] = "{$sufijo} {$estadosMiembros[$sufijo]}→" . $estadoDe($id) . ($accion === 'aprobar' ? ' (fila ' . var_export($estadoAprobacion($id), true) . ')' : '');
            }
            echoTerminal("   medido, al {$accion}: " . implode(', ', $medido));
            foreach ($esperados as $sufijo => $esperado) {
                $queda = $estadoDe($miembros[$sufijo]);
                $check($queda === $esperado, "c{$n} al {$accion} la organización, {$sufijo} ({$estadosMiembros[$sufijo]}) queda en {$esperado}", "queda en {$queda}");
                $n++;
            }
            $comprobarGuardados('c', $n, "organización {$accion}");
            if ($accion === 'aprobar') {
                foreach ($miembros as $sufijo => $id) {
                    $esperada = in_array($sufijo, $filasAprobadas, true) ? SystemApprovalsMapper::STATUS_APPROVED : SystemApprovalsMapper::STATUS_PENDING;
                    $fila = $estadoAprobacion($id);
                    $check($fila === $esperada, "c{$n} al aprobar, la fila de aprobación de {$sufijo} queda en {$esperada}", 'queda en ' . var_export($fila, true));
                    $n++;
                }
            }
        }

        //──── [d] Aprobar y rechazar un perfil ───────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[d] Aprobar un perfil activa al pendiente y al rechazado; rechazarlo, al activo y al pendiente');
        $trasPerfil = [
            'aprobar' => [
                'm-inactivo' => UsersModel::STATUS_USER_INACTIVE, 'm-activo' => UsersModel::STATUS_USER_ACTIVE, 'm-bloqueado' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK,
                'm-pendiente' => UsersModel::STATUS_USER_ACTIVE, 'm-rechazado' => UsersModel::STATUS_USER_ACTIVE, 'm-borrado' => UsersModel::STATUS_USER_DELETED,
                'm-bloq-desde-3' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK, 'm-bloq-desde-1' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK,
            ],
            'rechazar' => [
                'm-inactivo' => UsersModel::STATUS_USER_INACTIVE, 'm-activo' => UsersModel::STATUS_USER_REJECTED, 'm-bloqueado' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK,
                'm-pendiente' => UsersModel::STATUS_USER_REJECTED, 'm-rechazado' => UsersModel::STATUS_USER_REJECTED, 'm-borrado' => UsersModel::STATUS_USER_DELETED,
                'm-bloq-desde-3' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK, 'm-bloq-desde-1' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK,
            ],
        ];
        $n = 1;
        foreach ($trasPerfil as $accion => $esperados) {
            $sembrar();
            $medido = [];
            foreach ($miembros as $sufijo => $id) {
                $accion === 'aprobar' ? UsersApprovalHandler::onApproved(new UsersModel($id)) : UsersApprovalHandler::onRejected(new UsersModel($id));
                $medido[] = "{$sufijo} {$estadosMiembros[$sufijo]}→" . $estadoDe($id);
            }
            echoTerminal("   medido, al {$accion}: " . implode(', ', $medido));
            foreach ($esperados as $sufijo => $esperado) {
                $queda = $estadoDe($miembros[$sufijo]);
                $check($queda === $esperado, "d{$n} al {$accion} su perfil, {$sufijo} ({$estadosMiembros[$sufijo]}) queda en {$esperado}", "queda en {$queda}");
                $n++;
            }
            $comprobarGuardados('d', $n, "perfil {$accion}");
        }

        //──── [e] canManage y la autoridad sobre el tipo ─────────────────────────────────────
        echoTerminal('');
        echoTerminal('[e] El institucional no resuelve la fila de un perfil de tipo superior');
        $institucional = $crearUsuario('institucional', UsersModel::STATUS_USER_ACTIVE, UsersModel::TYPE_USER_INSTITUCIONAL, null);
        $jwtInstitucional = SessionToken::generateToken(['id' => $institucional], null, null, false);
        $actor = new UserDataPackage($institucional);
        $perfiles = [
            'e-root' => [UsersModel::TYPE_USER_ROOT, false],
            'e-admin' => [UsersModel::TYPE_USER_ADMIN_GRAL, false],
            'e-general' => [UsersModel::TYPE_USER_GENERAL, true],
        ];
        $n = 1;
        $filasPerfiles = [];
        foreach ($perfiles as $sufijo => [$tipo, $puede]) {
            $id = $crearUsuario($sufijo, UsersModel::STATUS_USER_APPROVED_PENDING, $tipo, $orgMiembros);
            $fila = $filaAprobacion($tablaUsuarios, $id, $id, SystemApprovalsMapper::STATUS_PENDING, "{$prefijo}-{$sufijo}");
            $resultado = SystemApprovalsController::canManage(new SystemApprovalsMapper($fila), $actor);
            $formulario = $pedir($camino(SystemApprovalsController::routeName('forms-approval', ['id' => $fila], true)), $jwtInstitucional);
            echoTerminal("   medido: institucional sobre la fila de {$sufijo} → canManage " . var_export($resultado, true) . ", formulario HTTP {$formulario['status']}");
            $check($resultado === $puede, "e{$n} canManage del institucional sobre {$sufijo}: " . var_export($puede, true), 'da ' . var_export($resultado, true));
            $n++;
            $check(($formulario['status'] === 200) === $puede, "e{$n} y su formulario de aprobación, por HTTP: " . ($puede ? '200' : 'no 200'), "HTTP {$formulario['status']}");
            $n++;
            $filasPerfiles[$sufijo] = [$fila, $puede];
        }
        //El listado, con la misma autoridad y en LISTA BLANCA: un tipo fuera de TYPES_USERS no se lista a nadie.
        $id = $crearUsuario('e-tipo-50', UsersModel::STATUS_USER_APPROVED_PENDING, UsersModel::TYPE_USER_GOOGLE_PLAY, $orgMiembros);
        $check(!array_key_exists(UsersModel::TYPE_USER_GOOGLE_PLAY, UsersModel::TYPES_USERS), "e{$n} banco: el tipo " . UsersModel::TYPE_USER_GOOGLE_PLAY . ' está fuera de TYPES_USERS');
        $n++;
        $filasPerfiles['e-tipo-50'] = [$filaAprobacion($tablaUsuarios, $id, $id, SystemApprovalsMapper::STATUS_PENDING, "{$prefijo}-e-tipo-50"), false];
        $rootActor = $crearUsuario('e-root-actor', UsersModel::STATUS_USER_ACTIVE, UsersModel::TYPE_USER_ROOT, null);
        $celdasDe = function (string $jwt, array $extra = []) use ($pedir, $camino): array {
            $listado = $pedir($camino(SystemApprovalsController::routeName('datatables', [], true)) . '?' . http_build_query(array_merge(['draw' => 1, 'start' => 0, 'length' => 5000], $extra)), $jwt);
            $datos = json_decode($listado['body'], true);
            $celdas = '';
            foreach ((is_array($datos) && is_array($datos['data'] ?? null) ? $datos['data'] : []) as $filaListado) {
                $celdas .= implode(' ', array_map(fn ($c) => is_scalar($c) ? (string) $c : '', is_array($filaListado) ? $filaListado : []));
            }
            return [$listado['status'], $celdas];
        };
        $listadoDe = [
            'institucional' => [$jwtInstitucional, ['e-root' => false, 'e-admin' => false, 'e-general' => true, 'e-tipo-50' => false]],
            'principal' => [SessionToken::generateToken(['id' => $rootActor], null, null, false), ['e-root' => true, 'e-admin' => true, 'e-general' => true, 'e-tipo-50' => false]],
        ];
        foreach ($listadoDe as $quien => [$jwt, $esperados]) {
            [$http, $celdas] = $celdasDe($jwt);
            foreach ($esperados as $sufijo => $visible) {
                $listada = preg_match('#forms/approval/' . ($filasPerfiles[$sufijo][0] ?? 0) . '(/|\'|")#', $celdas) === 1;
                $check($listada === $visible, "e{$n} el listado del {$quien} " . ($visible ? 'muestra' : 'no muestra') . " la fila de {$sufijo}" . ($visible ? ' (CANARIO)' : ''), "HTTP {$http}, listada " . var_export($listada, true));
                $n++;
            }
        }
        //Por referencia: las filas de más abajo se añaden después.
        $listadaEn = function (string $celdas, string $sufijo) use (&$filasPerfiles): bool {
            return preg_match('#forms/approval/' . ($filasPerfiles[$sufijo][0] ?? 0) . '(/|\'|")#', $celdas) === 1;
        };
        //Con lo que se añade DETRÁS del grupo de autoridad (una búsqueda, elapsedDays): el grupo cierra en AND y no lo anula.
        //La búsqueda solo actúa con las columnas buscables que manda DataTables (DataTablesHelper::searchableFieldsForHaving).
        $columnasBuscables = ['columns' => array_fill(0, 4, ['searchable' => 'true'])];
        //Una búsqueda que CASA con e-root: con el grupo abierto en OR, (lo permitido) OR (la búsqueda) lo dejaría salir. Con
        //elapsedDays no se consiguió un valor que casara con e-root (sin averiguar); su anulación la prueba el canario de 1000.
        $filtrosDetras = [
            'una búsqueda «Zz», que casa con todas' => $columnasBuscables + ['search' => ['value' => 'Zz']],
        ];
        foreach ($filtrosDetras as $como => $extra) {
            [$http, $celdas] = $celdasDe($jwtInstitucional, $extra);
            foreach (['e-root', 'e-admin', 'e-tipo-50'] as $sufijo) {
                $check(!$listadaEn($celdas, $sufijo), "e{$n} con {$como}, el institucional sigue sin ver la fila de {$sufijo}", "HTTP {$http}");
                $n++;
            }
        }
        //CANARIOS de que cada filtro se aplicó: lo que no casa vacía la fila de e-general; lo que casa la deja.
        [$http, $celdas] = $celdasDe($jwtInstitucional, $columnasBuscables + ['search' => ['value' => 'Zz']]);
        $check($listadaEn($celdas, 'e-general'), "e{$n} CANARIO: con una búsqueda que casa, el institucional ve la de e-general", "HTTP {$http}");
        $n++;
        [$http, $celdas] = $celdasDe($jwtInstitucional, $columnasBuscables + ['search' => ['value' => 'zz-no-casa-' . bin2hex(random_bytes(4))]]);
        $check(!$listadaEn($celdas, 'e-general'), "e{$n} CANARIO: con una búsqueda que no casa, la búsqueda se aplica y e-general no sale", "HTTP {$http}");
        $n++;
        [$http, $celdas] = $celdasDe($jwtInstitucional, ['elapsedDays' => '1000']);
        $check(!$listadaEn($celdas, 'e-general'), "e{$n} CANARIO: con elapsedDays=1000 el filtro se aplica y e-general no sale", "HTTP {$http}");
        $n++;
        //El administrador de organización va seguido de C5: ve lo de su organización y nada de otra.
        $jefe = $crearUsuario('e-jefe', UsersModel::STATUS_USER_ACTIVE, UsersModel::TYPE_USER_ADMIN_ORG, null);
        $orgJefe = $crearOrganizacion('org-jefe', $creador);
        $database->exec("UPDATE `{$tablaUsuarios}` SET organization = {$orgJefe} WHERE id = {$jefe}");
        $conJefe = new OrganizationMapper($orgJefe);
        $conJefe->administrator = $jefe;
        $conJefe->update();
        $id = $crearUsuario('e-del-jefe', UsersModel::STATUS_USER_APPROVED_PENDING, UsersModel::TYPE_USER_GENERAL, $orgJefe);
        $filasPerfiles['e-del-jefe'] = [$filaAprobacion($tablaUsuarios, $id, $id, SystemApprovalsMapper::STATUS_PENDING, "{$prefijo}-e-del-jefe"), true];
        [$http, $celdas] = $celdasDe(SessionToken::generateToken(['id' => $jefe], null, null, false));
        $check(!$listadaEn($celdas, 'e-general'), "e{$n} el administrador de organización no ve el perfil de un general de otra organización", "HTTP {$http}");
        $n++;
        $check($listadaEn($celdas, 'e-del-jefe'), "e{$n} CANARIO: sí ve el de un general de la suya", "HTTP {$http}");
        $n++;
        //BAJA-4: por id directo, canManage tampoco resuelve el tipo fuera de TYPES_USERS.
        $resultado = SystemApprovalsController::canManage(new SystemApprovalsMapper($filasPerfiles['e-tipo-50'][0]), $actor);
        $formulario = $pedir($camino(SystemApprovalsController::routeName('forms-approval', ['id' => $filasPerfiles['e-tipo-50'][0]], true)), $jwtInstitucional);
        $check($resultado === false && $formulario['status'] !== 200, "e{$n} canManage y el formulario, por id, no resuelven la fila del tipo " . UsersModel::TYPE_USER_GOOGLE_PLAY, 'canManage ' . var_export($resultado, true) . ", HTTP {$formulario['status']}");
        $n++;
        //El nombre lo edita el propio usuario y solo pasa por ucwords(): en el listado va escapado.
        $database->prepare("UPDATE `{$tablaUsuarios}` SET firstname = ? WHERE id = ?")->execute(['Zz<i>escapado</i>', (int) $database->query("SELECT referenceValue FROM `{$tablaAprobaciones}` WHERE id = " . (int) ($filasPerfiles['e-general'][0] ?? 0))->fetchColumn()]);
        [$http, $celdas] = $celdasDe($jwtInstitucional);
        $check(!str_contains($celdas, '<i>escapado</i>') && str_contains($celdas, '&lt;i&gt;escapado&lt;/i&gt;'), "e{$n} un nombre con < y > llega escapado al listado", "HTTP {$http}, crudo " . var_export(str_contains($celdas, '<i>escapado</i>'), true));
        $n++;

        //──── [f] El bloqueado que era pendiente o rechazado sigue recortado ──────────────────
        echoTerminal('');
        echoTerminal('[f] Bloquear no levanta el recorte: el 2 se recorta según lo guardado; con 1 guardado conserva su rol');
        $listadoPublicaciones = $camino(PublicationsController::routeName('list', [], true));
        $casosRecorte = [
            'f-guardado-4' => [UsersModel::TYPE_USER_COMUNICACIONES, UsersModel::STATUS_USER_REJECTED, 403],
            'f-guardado-3' => [UsersModel::TYPE_USER_COMUNICACIONES, UsersModel::STATUS_USER_APPROVED_PENDING, 403],
            'f-sin-guardado' => [UsersModel::TYPE_USER_COMUNICACIONES, null, 403],
            'f-guardado-1' => [UsersModel::TYPE_USER_COMUNICACIONES, UsersModel::STATUS_USER_ACTIVE, 200],
            'f-root-guardado-4' => [UsersModel::TYPE_USER_ROOT, UsersModel::STATUS_USER_REJECTED, 200],
        ];
        $n = 1;
        foreach ($casosRecorte as $sufijo => [$tipo, $guardado, $esperado]) {
            $id = $crearUsuario($sufijo, UsersModel::STATUS_USER_ACTIVE, $tipo, $tipo === UsersModel::TYPE_USER_ROOT ? null : $orgSesion);
            $jwt = SessionToken::generateToken(['id' => $id], null, null, false);
            $ponerEstado($id, UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersController::MAX_ATTEMPTS, $guardado);
            $r = $pedir($listadoPublicaciones, $jwt);
            $p = $pedir($perfil, $jwt);
            echoTerminal("   medido: {$sufijo}, bloqueado con su sesión → listado HTTP {$r['status']}, perfil HTTP {$p['status']}");
            $check($r['status'] === $esperado && $p['status'] === 200, "f{$n} {$sufijo}: listado de publicaciones {$esperado}" . ($esperado === 200 ? ' (CANARIO)' : '') . ', y su perfil 200', "listado {$r['status']}, perfil {$p['status']}");
            $n++;
        }

        //──── [g] Nadie aprueba su propia fila por la acción ─────────────────────────────────
        echoTerminal('');
        echoTerminal('[g] La acción de aprobar sigue la regla de su formulario: la fila propia, no (salvo el principal)');
        //Con correo de mailinator (ADR 0011): si la guarda se quita, la aprobación avisa a un buzón permitido.
        $accion = fn (int $fila): string => $camino(SystemApprovalsController::routeName('actions-approval', ['id' => $fila], true));
        //En una organización sin aprobar: en la global, la autoaprobación aprobaría su fila en la primera petición.
        $correoInstActivo = 'zz-prueba-estados-' . bin2hex(random_bytes(3)) . '@mailinator.com';
        echoTerminal("   correo de g-inst-activo, al que avisaría una aprobación: {$correoInstActivo}");
        $id = $crearUsuario('g-inst-activo', UsersModel::STATUS_USER_ACTIVE, UsersModel::TYPE_USER_INSTITUCIONAL, $orgMiembros, $correoInstActivo);
        $fila = $filaAprobacion($tablaUsuarios, $id, $id, SystemApprovalsMapper::STATUS_PENDING, "{$prefijo}-g-inst-activo");
        $r = $pedir($accion($fila), SessionToken::generateToken(['id' => $id], null, null, false), ['approvalStatus' => SystemApprovalsMapper::STATUS_APPROVED, 'reason' => '']);
        $filaQueda = (string) $database->query("SELECT status FROM `{$tablaAprobaciones}` WHERE id = {$fila}")->fetchColumn();
        $check($r['status'] === 404 && $filaQueda === SystemApprovalsMapper::STATUS_PENDING, 'g1 un institucional activo no aprueba su propia fila por la acción: 404 y la fila sigue pendiente', "HTTP {$r['status']}, fila {$filaQueda}");
        //El escenario del auditor: rechazado, bloqueado por terceros con su sesión abierta, intenta aprobarse.
        $correoInstRechazado = 'zz-prueba-estados-' . bin2hex(random_bytes(3)) . '@mailinator.com';
        echoTerminal("   correo de g-inst-rechazado, al que avisaría una aprobación: {$correoInstRechazado}");
        $id = $crearUsuario('g-inst-rechazado', UsersModel::STATUS_USER_REJECTED, UsersModel::TYPE_USER_INSTITUCIONAL, null, $correoInstRechazado);
        $fila = $filaAprobacion($tablaUsuarios, $id, $id, SystemApprovalsMapper::STATUS_REJECTED, "{$prefijo}-g-inst-rechazado");
        $jwt = SessionToken::generateToken(['id' => $id], null, null, false);
        $bloquearPorLogin('g-inst-rechazado');
        $r = $pedir($accion($fila), $jwt, ['approvalStatus' => SystemApprovalsMapper::STATUS_APPROVED, 'reason' => '']);
        $filaQueda = (string) $database->query("SELECT status FROM `{$tablaAprobaciones}` WHERE id = {$fila}")->fetchColumn();
        $check($r['status'] !== 200 && $filaQueda === SystemApprovalsMapper::STATUS_REJECTED && UsersModel::statusBeforeBlock($id) === UsersModel::STATUS_USER_REJECTED, 'g2 el rechazado y bloqueado no se aprueba a sí mismo: la fila sigue rechazada y lo guardado, 4', "HTTP {$r['status']}, fila {$filaQueda}, guardado " . var_export(UsersModel::statusBeforeBlock($id), true));
        $usarCodigo($id);
        $check($estadoDe($id) === UsersModel::STATUS_USER_REJECTED, 'g3 y al desbloquearse vuelve rechazado', 'queda en ' . $estadoDe($id));
        //CANARIO, sin escribir ni enviar: con un estado inválido la acción pasa la guarda y la frena la validación.
        $idGeneral = $crearUsuario('g-general', UsersModel::STATUS_USER_APPROVED_PENDING, UsersModel::TYPE_USER_GENERAL, $orgMiembros);
        $fila = $filaAprobacion($tablaUsuarios, $idGeneral, $idGeneral, SystemApprovalsMapper::STATUS_PENDING, "{$prefijo}-g-general");
        $r = $pedir($accion($fila), $jwtInstitucional, ['approvalStatus' => 'zz-invalido', 'reason' => '']);
        $filaQueda = (string) $database->query("SELECT status FROM `{$tablaAprobaciones}` WHERE id = {$fila}")->fetchColumn();
        $check($r['status'] === 200 && $filaQueda === SystemApprovalsMapper::STATUS_PENDING, 'g4 CANARIO: sobre la fila de un general, la acción del institucional pasa la guarda (responde la validación, no 404)', "HTTP {$r['status']}, fila {$filaQueda}");

        //──── [h] La carrera, simulada ───────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[h] Si lo guardado cambia entre la lectura y la escritura, se relee una vez; si no casa, falla cerrado');
        $n = 5;
        //Una lectura que devuelve lo que había ANTES de que una resolución cambiara lo guardado: las primeras $rancias.
        $lecturaRancia = new class () extends UsersModel {
            /** @var array{status:int,meta:array<string,mixed>}|null */
            public static ?array $fija = null;
            public static int $rancias = 0;
            protected static function freshStatusAndMeta(int $id): ?array
            {
                if (static::$rancias > 0 && static::$fija !== null) {
                    static::$rancias--;
                    return static::$fija;
                }
                return parent::freshStatusAndMeta($id);
            }
        };
        $claseRancia = get_class($lecturaRancia);
        $rancioDe3 = ['status' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK, 'meta' => [UsersModel::META_STATUS_BEFORE_BLOCK => UsersModel::STATUS_USER_APPROVED_PENDING]];
        $id = $crearUsuario('h-reintento', UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersModel::TYPE_USER_GENERAL, $orgMiembros);
        $ponerEstado($id, UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersController::MAX_ATTEMPTS, UsersModel::STATUS_USER_ACTIVE);
        $claseRancia::$fija = $rancioDe3;
        $claseRancia::$rancias = 1;
        $hecho = $lecturaRancia->unblockFromAttempts($id, UsersModel::STATUS_USER_APPROVED_PENDING);
        $check($hecho === true && $estadoDe($id) === UsersModel::STATUS_USER_ACTIVE, 'h1 desbloqueo: la primera lectura está rancia (3), la escritura no casa, relee y desbloquea con lo de verdad (1)', 'devuelve ' . var_export($hecho, true) . ', estado ' . $estadoDe($id));
        $id = $crearUsuario('h-cerrado', UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersModel::TYPE_USER_GENERAL, $orgMiembros);
        $ponerEstado($id, UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersController::MAX_ATTEMPTS, UsersModel::STATUS_USER_ACTIVE);
        $claseRancia::$rancias = 2;
        $hecho = $lecturaRancia->unblockFromAttempts($id, UsersModel::STATUS_USER_APPROVED_PENDING);
        $check($hecho === false && $estadoDe($id) === UsersModel::STATUS_USER_ATTEMPTS_BLOCK && UsersModel::statusBeforeBlock($id) === UsersModel::STATUS_USER_ACTIVE, 'h2 desbloqueo: rancia dos veces, falla cerrado y no toca nada', 'devuelve ' . var_export($hecho, true) . ', estado ' . $estadoDe($id) . ', guardado ' . var_export(UsersModel::statusBeforeBlock($id), true));
        $claseRancia::$rancias = 1;
        $hecho = $claseRancia::transitionStatusBeforeBlock($id, [UsersModel::STATUS_USER_ACTIVE, UsersModel::STATUS_USER_APPROVED_PENDING], UsersModel::STATUS_USER_REJECTED);
        $check($hecho === true && UsersModel::statusBeforeBlock($id) === UsersModel::STATUS_USER_REJECTED, 'h3 transición: rancia una vez, relee y transiciona lo de verdad', 'devuelve ' . var_export($hecho, true) . ', guardado ' . var_export(UsersModel::statusBeforeBlock($id), true));
        $ponerEstado($id, UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersController::MAX_ATTEMPTS, UsersModel::STATUS_USER_ACTIVE);
        $claseRancia::$rancias = 2;
        $hecho = $claseRancia::transitionStatusBeforeBlock($id, [UsersModel::STATUS_USER_ACTIVE, UsersModel::STATUS_USER_APPROVED_PENDING], UsersModel::STATUS_USER_REJECTED);
        $check($hecho === false && UsersModel::statusBeforeBlock($id) === UsersModel::STATUS_USER_ACTIVE, 'h4 transición: rancia dos veces, falla cerrado y lo guardado sigue siendo 1', 'devuelve ' . var_export($hecho, true) . ', guardado ' . var_export(UsersModel::statusBeforeBlock($id), true));
        $claseRancia::$rancias = 0;
        //BAJA-3: un valor guardado raro cuenta como «sin guardado» (regla de respaldo), no como un fallo para siempre.
        foreach (['h-raro-true' => 'true', 'h-raro-decimal' => '1.5', 'h-raro-cadena' => '"1"', 'h-raro-exponente' => '3e0'] as $sufijo => $raro) {
            $id = $crearUsuario($sufijo, UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersModel::TYPE_USER_GENERAL, $orgMiembros);
            $database->exec("UPDATE `{$tablaUsuarios}` SET failedAttempts = " . UsersController::MAX_ATTEMPTS . ', meta = ' . $database->quote('{"' . UsersModel::META_STATUS_BEFORE_BLOCK . '":' . $raro . '}') . " WHERE id = {$id}");
            $datos = $usarCodigo($id);
            $check(($datos['success'] ?? null) === true && $estadoDe($id) === UsersModel::STATUS_USER_APPROVED_PENDING, "h{$n} lo guardado vale {$raro}: se desbloquea con el respaldo (3)", 'success ' . var_export($datos['success'] ?? null, true) . ', estado ' . $estadoDe($id));
            $n++;
        }
        //MEDIA-2: el rechazo pierde la carrera con el desbloqueo (el manejador recibe el usuario leído en 2) y no se pierde.
        $id = $crearUsuario('h-carrera-rechazo', UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersModel::TYPE_USER_GENERAL, $orgMiembros);
        $ponerEstado($id, UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersController::MAX_ATTEMPTS, UsersModel::STATUS_USER_ACTIVE);
        $viejo = new UsersModel($id);
        (new UsersModel())->unblockFromAttempts($id, UsersModel::STATUS_USER_APPROVED_PENDING);
        $ponerFila($id, SystemApprovalsMapper::STATUS_REJECTED);
        UsersApprovalHandler::onRejected($viejo);
        $check((int) $viejo->status === UsersModel::STATUS_USER_ATTEMPTS_BLOCK && $estadoDe($id) === UsersModel::STATUS_USER_REJECTED, "h{$n} el manejador lo leyó en 2, el desbloqueo lo dejó en 1, y el rechazo lo deja en 4", 'estado ' . $estadoDe($id));
        $n++;

        //──── [i] La organización aprobada no aprueba un perfil rechazado ────────────────────
        echoTerminal('');
        echoTerminal('[i] Aprobar una organización no toca la fila rechazada de un miembro ni su estado');
        $orgH3 = $crearOrganizacion('org-h3', $creador);
        $idRechazado = $crearUsuario('i-pendiente-rechazado', UsersModel::STATUS_USER_APPROVED_PENDING, UsersModel::TYPE_USER_GENERAL, $orgH3);
        $ponerFila($idRechazado, SystemApprovalsMapper::STATUS_REJECTED);
        $idCanario = $crearUsuario('i-pendiente', UsersModel::STATUS_USER_APPROVED_PENDING, UsersModel::TYPE_USER_GENERAL, $orgH3);
        $ponerFila($idCanario, SystemApprovalsMapper::STATUS_PENDING);
        OrganizationApprovalHandler::onApproved(new OrganizationMapper($orgH3));
        $check($estadoDe($idRechazado) === UsersModel::STATUS_USER_APPROVED_PENDING && $estadoAprobacion($idRechazado) === SystemApprovalsMapper::STATUS_REJECTED, 'i1 el pendiente con fila rechazada sigue pendiente y con la fila rechazada', 'estado ' . $estadoDe($idRechazado) . ', fila ' . var_export($estadoAprobacion($idRechazado), true));
        $check($estadoDe($idCanario) === UsersModel::STATUS_USER_ACTIVE && $estadoAprobacion($idCanario) === SystemApprovalsMapper::STATUS_APPROVED, 'i2 CANARIO: el pendiente con fila pendiente queda activo y aprobado', 'estado ' . $estadoDe($idCanario) . ', fila ' . var_export($estadoAprobacion($idCanario), true));

        //──── [j] Los formularios de aprobación escapan lo que escribe un usuario ─────────────
        echoTerminal('');
        echoTerminal('[j] Los formularios de aprobación pintan escapado lo que escribe un usuario; y modifiedAt guarda la hora de 24 h');
        $jwtRoot = SessionToken::generateToken(['id' => $rootActor], null, null, false);
        $marca = '<b>"zz\'esc</b>';
        $marcaEscapada = htmlspecialchars($marca, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $idEsc = $crearUsuario('j-escape', UsersModel::STATUS_USER_APPROVED_PENDING, UsersModel::TYPE_USER_GENERAL, $orgMiembros);
        $database->prepare("UPDATE `{$tablaUsuarios}` SET firstname = ?, firstLastname = ?, username = ?, email = ? WHERE id = ?")->execute(["Zz{$marca}", "Ap{$marca}", "{$prefijo}-j-escape{$marca}", "{$prefijo}-j{$marca}@example.com", $idEsc]);
        $filaEsc = $filaAprobacion($tablaUsuarios, $idEsc, $idEsc, SystemApprovalsMapper::STATUS_PENDING, "{$prefijo}-j-escape");
        $r = $pedir($camino(SystemApprovalsController::routeName('forms-approval', ['id' => $filaEsc], true)), $jwtRoot);
        $check($r['status'] === 200 && !str_contains($r['body'], $marca) && substr_count($r['body'], $marcaEscapada) >= 4, 'j1 el formulario del perfil pinta nombres, apellidos, usuario y correo escapados', "HTTP {$r['status']}, crudo " . var_export(str_contains($r['body'], $marca), true) . ', escapados ' . substr_count($r['body'], $marcaEscapada));
        $orgEsc = $crearOrganizacion('org-j-escape', $creador);
        $database->prepare("UPDATE `{$tablaOrganizaciones}` SET name = ? WHERE id = ?")->execute(["{$prefijo}-org-j-escape{$marca}", $orgEsc]);
        $filaOrg = (int) $database->query("SELECT id FROM `{$tablaAprobaciones}` WHERE referenceTable = '{$tablaOrganizaciones}' AND referenceValue = '{$orgEsc}'")->fetchColumn();
        $r = $pedir($camino(SystemApprovalsController::routeName('forms-approval', ['id' => $filaOrg], true)), $jwtRoot);
        $check($r['status'] === 200 && !str_contains($r['body'], $marca) && str_contains($r['body'], $marcaEscapada), 'j2 el formulario de la organización pinta su nombre escapado', "HTTP {$r['status']}, crudo " . var_export(str_contains($r['body'], $marca), true));
        //Una publicación pendiente: su título y el nombre de su autor (el usuario de arriba). `content` es HTML del editor y no
        //se escapa: queda fuera (pendientes.md 394).
        $categoria = (int) (PublicationCategoryMapper::uncategorizedCategory()->id ?? 0);
        $publicacion = new PublicationMapper();
        $lang = (string) get_config('default_lang');
        $publicacion->baseLang = $lang;
        foreach (['title' => "{$prefijo} publicación{$marca}", 'content' => 'Contenido de la prueba.', 'seoDescription' => '', 'publicDate' => new \DateTime(), 'startDate' => null, 'endDate' => null, 'category' => $categoria, 'visits' => 0, 'author' => $idEsc, 'folder' => str_replace('.', '', uniqid()), 'featured' => PublicationMapper::UNFEATURED, 'mainImage' => 'statics/images/zz-prueba.jpg', 'thumbImage' => 'statics/images/zz-prueba.jpg', 'ogImage' => ''] as $campo => $valor) {
            $publicacion->setLangData($lang, $campo, $valor);
        }
        $publicacion->status = PublicationMapper::DRAFT;
        //`save()` exige un usuario en sesión, y el terminal no tiene: se le presta el autor y se devuelve el que había.
        $usuarioPrevio = get_config('current_user');
        $guardadoPrevio = get_config('pcsphp_current_user_stored');
        try {
            set_config('current_user', (object) ['id' => $idEsc]);
            set_config('pcsphp_current_user_stored', null);
            $publicacion->save();
        } finally {
            set_config('current_user', $usuarioPrevio);
            set_config('pcsphp_current_user_stored', $guardadoPrevio);
        }
        $idPublicacion = (int) $publicacion->id;
        $publicaciones[] = $idPublicacion;
        $filaPublicacion = $filaAprobacion(PublicationMapper::TABLE, $idPublicacion, $idEsc, SystemApprovalsMapper::STATUS_PENDING, "{$prefijo}-publicacion");
        $r = $pedir($camino(SystemApprovalsController::routeName('forms-approval', ['id' => $filaPublicacion], true)), $jwtRoot);
        $check($r['status'] === 200 && !str_contains($r['body'], $marca) && substr_count($r['body'], $marcaEscapada) >= 2, 'j3 el formulario de la publicación pinta su título y el nombre del autor escapados', "HTTP {$r['status']}, crudo " . var_export(str_contains($r['body'], $marca), true) . ', escapados ' . substr_count($r['body'], $marcaEscapada));
        //Solo distingue cuando `h` y `H` dan horas distintas: de 13:00 a 23:59 y de 00:00 a 00:59.
        (new UsersModel())->updateModifiedAt($idEsc);
        $guardada = (string) $database->query("SELECT modifiedAt FROM `{$tablaUsuarios}` WHERE id = {$idEsc}")->fetchColumn();
        $diferencia = abs(strtotime($guardada) - time());
        $distingue = date('h') !== date('H');
        $check($diferencia <= 60, 'j4 modifiedAt guarda la hora actual en 24 h (' . ($distingue ? 'a esta hora distingue' : 'a esta hora no distingue') . ')', "guardada {$guardada}, ahora " . date('Y-m-d H:i:s'));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $database->exec('DELETE FROM `' . LoginAttemptsModel::TABLE . '` WHERE usernameAttempt LIKE ' . $database->quote("{$prefijo}%"));
            $database->exec("DELETE FROM `{$tablaProblemas}` WHERE email LIKE " . $database->quote("{$prefijo}%") . ' OR email LIKE ' . $database->quote('zz-prueba-estados-%@mailinator.com'));
            $database->exec("DELETE FROM `{$tablaSolicitudes}` WHERE email LIKE " . $database->quote("{$prefijo}%") . ' OR email LIKE ' . $database->quote('zz-prueba-estados-%@mailinator.com'));
            $database->prepare("DELETE FROM `{$tablaCorreo}` WHERE recipients LIKE ?")->execute([$buzonesPrueba]);
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
            //Las claves van en los dos sentidos: los usuarios pasan a la global, caen las organizaciones y después los usuarios.
            $database->exec("UPDATE `{$tablaUsuarios}` SET organization = " . OrganizationMapper::INITIAL_ID_GLOBAL . ' WHERE username LIKE ' . $database->quote("{$prefijo}%"));
            foreach ($publicaciones as $idPublicacion) {
                $database->exec("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = '" . PublicationMapper::TABLE . "' AND referenceValue = '{$idPublicacion}'");
                $database->exec('DELETE FROM `' . PublicationMapper::TABLE . "` WHERE id = {$idPublicacion}");
            }
            foreach ($organizaciones as $organizacion) {
                $database->exec("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = '{$tablaOrganizaciones}' AND referenceValue = '{$organizacion}'");
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
            + (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query("SELECT COUNT(*) FROM `{$tablaProblemas}` WHERE email LIKE " . $database->quote("{$prefijo}%") . ' OR email LIKE ' . $database->quote('zz-prueba-estados-%@mailinator.com'))->fetchColumn()
            + (int) $database->query("SELECT COUNT(*) FROM `{$tablaSolicitudes}` WHERE email LIKE " . $database->quote("{$prefijo}%") . ' OR email LIKE ' . $database->quote('zz-prueba-estados-%@mailinator.com'))->fetchColumn()
            + (int) $database->query("SELECT COUNT(*) FROM `{$tablaCorreo}` WHERE recipients LIKE " . $database->quote($buzonesPrueba))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario, organización, intento de acceso código, solicitud ni registro de correo de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Las transiciones de estado de un usuario: inactivo y borrado cortan la sesión abierta y el bloqueado la conserva sin poder abrir otra; el código de desbloqueo solo desbloquea al bloqueado y le devuelve su estado, con la fila de aprobación rechazada mandando; aprobar o rechazar una organización o un perfil solo mueve los estados que le tocan, también lo guardado de un bloqueado; y el institucional no resuelve ni ve listada la fila de un tipo superior. Por HTTP donde hay ruta.')->setEffects([CliActions::EFFECT_DATABASE])->register();
