<?php

//P426: el encargado (tipo 12) edita y borra en Publicaciones lo de SU organización; lo de otra o la general, 403; lista solo lo suyo.
//Por HTTP y en memoria. Crea usuarios, organizaciones y publicaciones zz- y los retira.
//@areas src/app/classes/Publications/
//@areas src/app/classes/Organizations/
//@requires-module Organizations\OrganizationsRoutes
//@requires-module SystemApprovals\SystemApprovalsRoutes

use Organizations\Mappers\OrganizationMapper;
use Organizations\OrganizationsRoutes;
use Organizations\Util\OrganizationAdministration;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Controllers\PublicationsController;
use Publications\Mappers\AttachmentPublicationMapper;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\Util\SystemApprovalManager;

CliActions::make('unit-tests:core/org-admin-publications', function ($args) {

    echoTerminal("\e[33m[TEST:OrgAdminPublications] El administrador de organización edita y borra en Publicaciones lo de SU organización\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la recomprobación de editar.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    if (!$check($base !== '', 'c1 hay una base HTTP para pedir', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $camino = function (string $sufijo, array $parametros = []) use ($prefijoBase): string {
        $url = PublicationsController::routeName($sufijo, $parametros, true);
        $url = '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
        return $prefijoBase !== '' && str_starts_with($url, $prefijoBase) ? substr($url, strlen($prefijoBase)) : $url;
    };
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $ruta, string $jwt, ?array $campos) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . $ruta,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ];
        if ($campos !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($campos);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        $datos = is_string($body) ? json_decode($body, true) : null;
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'success' => is_array($datos) ? ($datos['success'] ?? null) : null, 'datos' => $datos, 'body' => is_string($body) ? $body : ''];
    };
    $resumen = fn (array $r): string => "HTTP {$r['status']} · " . mb_substr(preg_replace('/\s+/', ' ', $r['body']) ?? '', 0, 160);

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'c2 hay conexión a la base')) {
        return $balance();
    }
    $sello = bin2hex(random_bytes(3));
    $prefijoUsuario = "zz-agente-jpv-{$sello}";
    $prefijo = "zz-jpv-{$sello}";
    $lang = Config::get_default_lang();
    $huella = function (int $id) use ($database): string {
        $fila = $database->query('SELECT * FROM `' . PublicationMapper::TABLE . '` WHERE id = ' . $id)->fetch(\PDO::FETCH_ASSOC);
        return is_array($fila) ? sha1((string) json_encode($fila)) : 'sin-fila';
    };
    $usuarios = [];
    $organizaciones = [];
    $publicaciones = [];
    $crearUsuario = function (string $sufijo, int $tipo, ?int $organizacion) use ($prefijoUsuario, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijoUsuario}-{$sufijo}";
        $u->email = "{$prefijoUsuario}-{$sufijo}@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Encargado';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        if ($organizacion !== null) {
            $u->organization = $organizacion;
        }
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[] = (int) $u->id;
        return (int) $u->id;
    };
    $crearOrganizacion = function (string $sufijo) use ($prefijo, &$organizaciones): int {
        $o = new OrganizationMapper();
        $o->name = "{$prefijo}-org-{$sufijo}";
        $o->nit = "{$prefijo}-nit-{$sufijo}";
        $o->folder = "{$prefijo}-{$sufijo}";
        $o->status = OrganizationMapper::ACTIVE;
        $o->save();
        $organizaciones[] = (int) $o->id;
        return (int) $o->id;
    };
    $categoria = (int) ($database->query('SELECT id FROM `' . PublicationCategoryMapper::TABLE . '` ORDER BY id LIMIT 1')->fetchColumn() ?: PublicationCategoryMapper::UNCATEGORIZED_ID);
    $sembrar = function (string $sufijo, int $creador, int $estado = PublicationMapper::ACTIVE, ?int $autor = null) use ($database, $categoria, $prefijo, $lang, &$publicaciones): int {
        $ahora = date('Y-m-d H:i:s');
        $database->prepare('INSERT INTO `' . PublicationMapper::TABLE . '` (title, content, seoDescription, author, category, mainImage, thumbImage, ogImage, folder, visits, publicDate, createdAt, createdBy, status, featured, meta)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 0, ?)')
            ->execute(["{$prefijo}-pub-{$sufijo}", 'zz-', 'zz-', $autor ?? $creador, $categoria, "{$prefijo}/m.jpg", "{$prefijo}/t.jpg", '', "{$prefijo}-pub-{$sufijo}", $ahora, $ahora, $creador, $estado, json_encode(['baseLang' => $lang, 'langData' => new \stdClass])]);
        $publicaciones[$sufijo] = (int) $database->lastInsertId();
        return $publicaciones[$sufijo];
    };
    $camposEdicion = fn (int $id, int $autor, string $titulo): array => ['id' => $id, 'lang' => $lang, 'baseLang' => $lang, 'author' => $autor, 'title' => $titulo, 'content' => 'zz-', 'category' => $categoria, 'publicDate' => date('d-m-Y'), 'draft' => 'yes'];

    $usuarioAntes = get_config('current_user');
    $guardadoAntes = get_config('pcsphp_current_user_stored');

    try {
        $orgX = $crearOrganizacion('x');
        $orgY = $crearOrganizacion('y');
        $ids = [
            'encargado' => $crearUsuario('encargado-x', UsersModel::TYPE_USER_ADMIN_ORG, $orgX),
            'otroAdmin' => $crearUsuario('admin-x-no-encargado', UsersModel::TYPE_USER_ADMIN_ORG, $orgX),
            'adminGeneral' => $crearUsuario('admin-org-general', UsersModel::TYPE_USER_ADMIN_ORG, OrganizationMapper::INITIAL_ID_GLOBAL),
            'autorX' => $crearUsuario('autor-x', UsersModel::TYPE_USER_COMUNICACIONES, $orgX),
            'autorY' => $crearUsuario('autor-y', UsersModel::TYPE_USER_COMUNICACIONES, $orgY),
            'autorGeneral' => $crearUsuario('autor-general', UsersModel::TYPE_USER_COMUNICACIONES, OrganizationMapper::INITIAL_ID_GLOBAL),
            'generalX' => $crearUsuario('general-x', UsersModel::TYPE_USER_GENERAL, $orgX),
            'root' => $crearUsuario('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL),
            'adminGral' => $crearUsuario('admin-general', UsersModel::TYPE_USER_ADMIN_GRAL, OrganizationMapper::INITIAL_ID_GLOBAL),
            'autorXInactivo' => $crearUsuario('autor-x-inactivo', UsersModel::TYPE_USER_COMUNICACIONES, $orgX),
            'autorX2' => $crearUsuario('autor-x-2', UsersModel::TYPE_USER_INSTITUCIONAL, $orgX),
            'autorCambia' => $crearUsuario('autor-que-cambia', UsersModel::TYPE_USER_COMUNICACIONES, $orgX),
            'creadorCambia' => $crearUsuario('creador-que-cambia', UsersModel::TYPE_USER_COMUNICACIONES, $orgX),
        ];
        $database->prepare('UPDATE `' . UsersModel::TABLE . '` SET status = ? WHERE id = ?')->execute([UsersModel::STATUS_USER_INACTIVE, $ids['autorXInactivo']]);
        $modeloX = new OrganizationMapper($orgX);
        $modeloX->administrator = $ids['encargado'];
        $modeloX->update();
        $jwt = array_map(fn (int $id): string => UserSessions::issueToken($id), $ids);

        $pubX = $sembrar('x', $ids['autorX']);
        $pubXBorrar = $sembrar('x-borrar', $ids['autorX']);
        $pubY = $sembrar('y', $ids['autorY']);
        $pubG = $sembrar('general', $ids['autorGeneral']);
        $sembrar('x-borrador', $ids['autorX'], PublicationMapper::DRAFT);
        $sembrar('general-borrador', $ids['autorGeneral'], PublicationMapper::DRAFT);
        //El listado de `all` solo enseña lo aprobado: init() les crea su fila y se aprueban aquí.
        SystemApprovalManager::init();
        foreach ($publicaciones as $idPublicacion) {
            $database->prepare('UPDATE `' . SystemApprovalsMapper::TABLE . '` SET status = ? WHERE referenceTable = ? AND referenceValue = ?')->execute([SystemApprovalsMapper::STATUS_APPROVED, PublicationMapper::TABLE, (string) $idPublicacion]);
        }
        $editar = $camino('actions-edit');

        echoTerminal('[1] El listado del panel, antes de editar ni borrar');
        $lista = function (string $quien) use ($pedir, $camino, $jwt, $prefijo): array {
            $r = $pedir($camino('datatables') . '?draw=1&start=0&length=100&search[value]=' . rawurlencode($prefijo), $jwt[$quien], null);
            $vistas = [];
            foreach (['x', 'y', 'general'] as $sufijo) {
                if (preg_match('/' . preg_quote("{$prefijo}-pub-{$sufijo}", '/') . '(?![a-z-])/', $r['body']) === 1) {
                    $vistas[] = $sufijo;
                }
            }
            return ['status' => $r['status'], 'vistas' => $vistas];
        };
        $l = $lista('encargado');
        $check($l['status'] === 200 && $l['vistas'] === ['x'], '1a el encargado de X ve las de X y ni las de Y ni las de la general', "HTTP {$l['status']}: " . implode(',', $l['vistas']));
        $l = $lista('otroAdmin');
        $check($l['status'] === 200 && $l['vistas'] === [], '1b un administrador de X que no es su encargado no ve las de X (solo lo suyo, que no tiene)', "HTTP {$l['status']}: " . implode(',', $l['vistas']));
        $l = $lista('adminGeneral');
        $check($l['status'] === 200 && $l['vistas'] === [], '1c un administrador de organización de la general no ve las de la general', "HTTP {$l['status']}: " . implode(',', $l['vistas']));
        $todas = function (string $quien) use ($pedir, $camino, $jwt, $prefijo): array {
            $r = $pedir($camino('ajax-all') . '?page=1&per_page=100&status=ANY&title=' . rawurlencode($prefijo), $jwt[$quien], null);
            $vistas = [];
            foreach (['x-borrador', 'general-borrador'] as $sufijo) {
                if (preg_match('/' . preg_quote("{$prefijo}-pub-{$sufijo}", '/') . '(?![a-z-])/', $r['body']) === 1) {
                    $vistas[] = $sufijo;
                }
            }
            return ['status' => $r['status'], 'vistas' => $vistas];
        };
        $t = $todas('encargado');
        $check($t['status'] === 200 && $t['vistas'] === [], '1d en `all` con no activas, el encargado de X no ve borradores: no tiene permiso de borradores (CAN_VIEW_DRAFT)', "HTTP {$t['status']}: " . implode(',', $t['vistas']));
        $t = $todas('otroAdmin');
        $check($t['status'] === 200 && $t['vistas'] === [], '1e y un administrador de X que no es su encargado, ninguno', "HTTP {$t['status']}: " . implode(',', $t['vistas']));
        $t = $todas('adminGeneral');
        $check($t['status'] === 200 && $t['vistas'] === [], '1f y un administrador de organización de la general, tampoco el de la general', "HTTP {$t['status']}: " . implode(',', $t['vistas']));

        echoTerminal('');
        echoTerminal('[2] Edición y borrado por HTTP');
        $antes = $huella($pubY);
        $r = $pedir($editar, $jwt['encargado'], $camposEdicion($pubY, $ids['autorY'], "{$prefijo}-sonda"));
        $check($r['status'] === 403 && $huella($pubY) === $antes, '2a el encargado de X edita una de un creador de Y → 403 y queda intacta', $resumen($r));
        $r = $pedir($camino('actions-delete', ['id' => $pubY]), $jwt['encargado'], ['id' => $pubY]);
        $check($r['status'] === 403 && $huella($pubY) === $antes, '2b y la borra → 403 y queda intacta', $resumen($r));
        $antes = $huella($pubG);
        $r = $pedir($editar, $jwt['encargado'], $camposEdicion($pubG, $ids['autorGeneral'], "{$prefijo}-sonda"));
        $check($r['status'] === 403 && $huella($pubG) === $antes, '2c el encargado de X edita una de un creador de la organización general → 403', $resumen($r));
        $r = $pedir($camino('actions-delete', ['id' => $pubG]), $jwt['encargado'], ['id' => $pubG]);
        $check($r['status'] === 403 && $huella($pubG) === $antes, '2d y la borra → 403', $resumen($r));
        $antes = $huella($pubX);
        $r = $pedir($editar, $jwt['otroAdmin'], $camposEdicion($pubX, $ids['autorX'], "{$prefijo}-sonda"));
        $check($r['status'] === 403 && $huella($pubX) === $antes, '2e un administrador de X que no es su encargado edita una de X → 403', $resumen($r));
        $r = $pedir($camino('actions-delete', ['id' => $pubX]), $jwt['otroAdmin'], ['id' => $pubX]);
        $check($r['status'] === 403 && $huella($pubX) === $antes, '2f y la borra → 403', $resumen($r));
        $r = $pedir($editar, $jwt['encargado'], $camposEdicion($pubX, $ids['autorX'], "{$prefijo}-pub-x-por-encargado"));
        $check($r['status'] === 200 && $r['success'] === true && $huella($pubX) !== $antes, '2g el encargado de X edita una de un creador de X', $resumen($r));
        $r = $pedir($camino('actions-delete', ['id' => $pubXBorrar]), $jwt['encargado'], ['id' => $pubXBorrar]);
        $estado = (int) $database->query('SELECT status FROM `' . PublicationMapper::TABLE . '` WHERE id = ' . $pubXBorrar)->fetchColumn();
        $check($r['status'] === 200 && $r['success'] === true && $estado === PublicationMapper::INACTIVE, '2h y borra otra de X', $resumen($r) . " · status {$estado}");
        $r = $pedir($camino('forms-add'), $jwt['encargado'], null);
        $check($r['status'] === 403, '2i RECHAZO: el administrador de organización no abre el alta (crear no es suyo)', "HTTP {$r['status']}");

        echoTerminal('');
        echoTerminal('[3] Con Organizaciones apagado, en memoria');
        $puede = function (string $quien, string $ruta, int $pub) use ($ids): bool {
            set_config('current_user', (object) ['id' => $ids[$quien]]);
            set_config('pcsphp_current_user_stored', null);
            getLoggedFrameworkUser(true);
            return PublicationsController::allowedRoute($ruta, ['id' => $pub]);
        };
        try {
            OrganizationsRoutes::swapEnabledForTesting(true);
            $check($puede('encargado', 'forms-edit', $pubX) && $puede('encargado', 'actions-delete', $pubX), '3a CANARIO: encendido, el encargado de X edita y borra la de X');
            OrganizationsRoutes::swapEnabledForTesting(false);
            $check(!$puede('encargado', 'forms-edit', $pubX) && !$puede('encargado', 'actions-delete', $pubX), '3b apagado, ser encargado no concede: ni edita ni borra la de X');
            OrganizationsRoutes::swapEnabledForTesting(true);
            //La organización general no cuenta aunque se le ponga encargado: se repone su fila entera.
            $tablaOrganizaciones = OrganizationMapper::TABLE;
            $filaGeneral = $database->query("SELECT * FROM `{$tablaOrganizaciones}` WHERE id = " . OrganizationMapper::INITIAL_ID_GLOBAL)->fetch(\PDO::FETCH_ASSOC);
            if ($check(is_array($filaGeneral), '3c.0 CANARIO: existe la organización general') && is_array($filaGeneral)) {
                try {
                    $general = new OrganizationMapper(OrganizationMapper::INITIAL_ID_GLOBAL);
                    $general->administrator = $ids['adminGeneral'];
                    $general->update();
                    set_config('current_user', (object) ['id' => $ids['adminGeneral']]);
                    set_config('pcsphp_current_user_stored', null);
                    $paqueteGeneral = getLoggedFrameworkUser(true);
                    set_config('current_user', (object) ['id' => $ids['encargado']]);
                    set_config('pcsphp_current_user_stored', null);
                    $paqueteEncargado = getLoggedFrameworkUser(true);
                    $check($paqueteGeneral !== null && $paqueteEncargado !== null && !OrganizationAdministration::administersOwnOrganization($paqueteGeneral) && OrganizationAdministration::administersOwnOrganization($paqueteEncargado),
                        '3c el encargado PUESTO de la organización general no cuenta como encargado de la suya; el de X sí');
                } finally {
                    $columnas = array_keys($filaGeneral);
                    $database->prepare("UPDATE `{$tablaOrganizaciones}` SET " . implode(', ', array_map(fn (string $c): string => "`{$c}` = ?", $columnas)) . ' WHERE id = ?')
                        ->execute([...array_values($filaGeneral), OrganizationMapper::INITIAL_ID_GLOBAL]);
                    $despues = $database->query("SELECT * FROM `{$tablaOrganizaciones}` WHERE id = " . OrganizationMapper::INITIAL_ID_GLOBAL)->fetch(\PDO::FETCH_ASSOC);
                    $check($despues === $filaGeneral, '3c.1 la fila de la organización general queda como estaba');
                }
            }
        } finally {
            OrganizationsRoutes::swapEnabledForTesting(null);
            set_config('current_user', $usuarioAntes);
            set_config('pcsphp_current_user_stored', $guardadoAntes);
            getLoggedFrameworkUser(true);
        }

        echoTerminal('');
        echoTerminal('[4] El autor se valida en el servidor (P431 2 a)');
        $mensajeAutor = 'El autor elegido no es válido para esta publicación.';
        $rechazado = fn (array $r): bool => $r['status'] === 200 && $r['success'] === false && is_array($r['datos']) && ($r['datos']['message'] ?? null) === $mensajeAutor;
        $antes = $huella($pubX);
        $r = $pedir($editar, $jwt['encargado'], $camposEdicion($pubX, $ids['autorY'], "{$prefijo}-sonda-autor"));
        $check($rechazado($r) && $huella($pubX) === $antes, '4a el encargado de X cambia el autor de la de X a uno de Y → 200, success false, el mensaje, y nada cambia', $resumen($r));
        $r = $pedir($editar, $jwt['encargado'], $camposEdicion($pubX, $ids['generalX'], "{$prefijo}-sonda-autor"));
        $check($rechazado($r) && $huella($pubX) === $antes, '4b a uno de X de un tipo que no es de autor → rechazo, y nada cambia', $resumen($r));
        $r = $pedir($editar, $jwt['encargado'], $camposEdicion($pubX, 2147483000, "{$prefijo}-sonda-autor"));
        $check($rechazado($r) && $huella($pubX) === $antes, '4c a uno que no existe → rechazo, y nada cambia', $resumen($r));
        $r = $pedir($editar, $jwt['encargado'], $camposEdicion($pubX, $ids['autorXInactivo'], "{$prefijo}-sonda-autor"));
        $check($rechazado($r) && $huella($pubX) === $antes, '4c.1 a uno de X inactivo → rechazo, y nada cambia', $resumen($r));
        $r = $pedir($editar, $jwt['encargado'], $camposEdicion($pubX, $ids['otroAdmin'], "{$prefijo}-sonda-autor"));
        $check($rechazado($r) && $huella($pubX) === $antes, '4c.2 a un administrador de organización (12) de X, por POST → rechazo', $resumen($r));
        $r = $pedir($editar, $jwt['encargado'], $camposEdicion($pubX, $ids['adminGral'], "{$prefijo}-sonda-autor"));
        $check($rechazado($r) && $huella($pubX) === $antes, '4c.3 a un administrador general (1), por POST → rechazo', $resumen($r));
        $r = $pedir($editar, $jwt['encargado'], $camposEdicion($pubX, $ids['autorX'], "{$prefijo}-pub-x-autor-x"));
        $check($r['status'] === 200 && $r['success'] === true && $huella($pubX) !== $antes, '4d con el autor de X que ya tenía → pasa', $resumen($r));
        $antes = $huella($pubX);
        $r = $pedir($editar, $jwt['root'], $camposEdicion($pubX, $ids['autorY'], "{$prefijo}-pub-x-autor-y-por-root"));
        $check($r['status'] === 200 && $r['success'] === true && $huella($pubX) !== $antes, '4e root la cambia a un autor de Y → pasa (edita todas)', $resumen($r));
        //El caso del ALTO de #3422: root crea con autor de X; ese autor guarda sin cambiar el autor.
        $pubRoot = $sembrar('root-autor-x', $ids['root'], PublicationMapper::ACTIVE, $ids['autorX']);
        $antes = $huella($pubRoot);
        $r = $pedir($editar, $jwt['autorX'], $camposEdicion($pubRoot, $ids['autorX'], "{$prefijo}-pub-root-autor-x-editada"));
        $check($r['status'] === 200 && $r['success'] === true && $huella($pubRoot) !== $antes, '4j DISCRIMINANTE: root la creó con autor de X; ese autor la guarda sin cambiar el autor → pasa', $resumen($r));
        $otroIdioma = current(array_filter(\PiecesPHP\Core\Config::get_allowed_langs(), fn ($l): bool => $l !== $lang));
        if (!is_string($otroIdioma)) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m 4k: la instalación no tiene otro idioma permitido.");
        } else {
            $antes = $huella($pubRoot);
            $r = $pedir($editar, $jwt['autorX'], ['lang' => $otroIdioma] + $camposEdicion($pubRoot, $ids['autorX'], "{$prefijo}-pub-root-autor-x-{$otroIdioma}"));
            $check($r['status'] === 200 && $r['success'] === true && $huella($pubRoot) !== $antes, "4k y en otro idioma ({$otroIdioma}) también", $resumen($r));
        }
        $antes = $huella($pubRoot);
        $r = $pedir($editar, $jwt['autorX'], $camposEdicion($pubRoot, $ids['autorY'], "{$prefijo}-sonda-autor"));
        $check($rechazado($r) && $huella($pubRoot) === $antes, '4l ese autor la cambia a uno de Y → rechazo, y nada cambia', $resumen($r));
        $r = $pedir($editar, $jwt['autorX'], $camposEdicion($pubRoot, $ids['autorX2'], "{$prefijo}-pub-root-autor-x2"));
        $check($r['status'] === 200 && $r['success'] === true && $huella($pubRoot) !== $antes, '4l.1 DISCRIMINANTE: y a otro de X, de su organización (la que acota su selector) → pasa, aunque la creó root', $resumen($r));
        //El alta por HTTP: el autor se valida antes de las subidas, así que el rechazo no necesita imágenes ni deja nada.
        $antesAlta = (int) $database->query('SELECT COUNT(*) FROM `' . PublicationMapper::TABLE . '` WHERE title LIKE ' . $database->quote("{$prefijo}-alta%"))->fetchColumn();
        $r = $pedir($camino('actions-add'), $jwt['autorX'], ['lang' => $lang, 'baseLang' => $lang, 'author' => $ids['autorY'], 'title' => "{$prefijo}-alta-autor-y", 'content' => 'zz-', 'category' => $categoria, 'publicDate' => date('d-m-Y'), 'draft' => 'yes']);
        $despuesAlta = (int) $database->query('SELECT COUNT(*) FROM `' . PublicationMapper::TABLE . '` WHERE title LIKE ' . $database->quote("{$prefijo}-alta%"))->fetchColumn();
        $check($rechazado($r) && $despuesAlta === $antesAlta, '4m el ALTA por HTTP: quien crea en X con un autor de Y → rechazo, y no se crea nada', $resumen($r));
        //El selector de autor ofrece lo mismo que acepta el guardado (salvo la organización, que acota solo).
        $selector = \PiecesPHP\UserSystem\Controllers\UsersController::routeName('search-dropdown', [], true);
        $ignorados = implode(',', array_filter(\PiecesPHP\UserSystem\UserTypes::codes(), fn ($t): bool => !in_array($t, PublicationsController::AUTHOR_TYPES, true)));
        $rutaSelector = '/' . ltrim(str_contains($selector, '://') ? (string) parse_url($selector, \PHP_URL_PATH) : $selector, '/');
        $rutaSelector = $prefijoBase !== '' && str_starts_with($rutaSelector, $prefijoBase) ? substr($rutaSelector, strlen($prefijoBase)) : $rutaSelector;
        //Los filtros, los que monta de verdad el formulario (urlForSearchUsers), no los de esta prueba.
        $urlReal = (string) (new \ReflectionProperty(PublicationsController::class, 'urlForSearchUsers'))->getValue(new PublicationsController());
        parse_str((string) parse_url($urlReal, \PHP_URL_QUERY), $filtros);
        $check(($filtros['ignoreTypes'] ?? null) === $ignorados && ($filtros['ignoreStatuses'] ?? null) === implode(',', PublicationsController::AUTHOR_EXCLUDED_STATUSES), '4n.0 el selector del formulario pide los tipos y los estados de autor', $urlReal);
        $r = $pedir($rutaSelector . '?search=' . rawurlencode('zz') . '&ignoreTypes=' . rawurlencode(is_string($filtros['ignoreTypes'] ?? null) ? $filtros['ignoreTypes'] : '') . '&ignoreStatuses=' . rawurlencode(is_string($filtros['ignoreStatuses'] ?? null) ? $filtros['ignoreStatuses'] : ''), $jwt['encargado'], null);
        $ofrecidos = array_map(fn ($e): int => (int) ($e['value'] ?? 0), (array) (($r['datos']['results'] ?? []) ?: []));
        $check($r['status'] === 200 && in_array($ids['autorX'], $ofrecidos, true) && !in_array($ids['autorXInactivo'], $ofrecidos, true) && !in_array($ids['otroAdmin'], $ofrecidos, true) && !in_array($ids['autorY'], $ofrecidos, true),
            '4n el selector de autor ofrece al de X activo, y no al inactivo, ni al 12, ni al de Y', "HTTP {$r['status']}: " . json_encode($ofrecidos));

        //En memoria: el alta (quien crea es quien guarda), Organizaciones apagado y un autor que cambia de tipo.
        $autorValido = function (string $quien, int $autor) use ($ids): bool {
            set_config('current_user', (object) ['id' => $ids[$quien]]);
            set_config('pcsphp_current_user_stored', null);
            getLoggedFrameworkUser(true);
            try {
                (new \ReflectionMethod(PublicationsController::class, 'assertValidAuthor'))->invoke(null, $autor);
                return true;
            } catch (\Publications\Exceptions\SafeException $e) {
                return false;
            }
        };
        try {
            OrganizationsRoutes::swapEnabledForTesting(true);
            $check(!$autorValido('autorX', $ids['autorY']) && $autorValido('autorX', $ids['autorX']), '4f en el alta, quien crea en X no pone un autor de Y; uno de X, sí');
            $check(!$autorValido('encargado', $ids['autorY']), '4g CANARIO: encendido, el encargado de X con un autor de Y → rechazo');
            OrganizationsRoutes::swapEnabledForTesting(false);
            $check($autorValido('encargado', $ids['autorY']), '4h con Organizaciones apagado, el encargado con un autor de Y → pasa (no hay frontera)');
            $check(!$autorValido('encargado', $ids['generalX']), '4i y apagado, un autor de un tipo que no es de autor sigue sin valer');
            //Figurar como autor concede mientras se sea de un tipo de autor: si pasa a 12 (no encargado), lo pierde.
            OrganizationsRoutes::swapEnabledForTesting(null);
            $pubCambia = $sembrar('autor-que-cambia', $ids['root'], PublicationMapper::ACTIVE, $ids['autorCambia']);
            $check($puede('autorCambia', 'forms-edit', $pubCambia) && $puede('autorCambia', 'actions-delete', $pubCambia), '4o CANARIO: su autor, de comunicaciones, la edita y la borra');
            $database->prepare('UPDATE `' . UsersModel::TABLE . '` SET type = ? WHERE id = ?')->execute([UsersModel::TYPE_USER_ADMIN_ORG, $ids['autorCambia']]);
            $check(!$puede('autorCambia', 'forms-edit', $pubCambia) && !$puede('autorCambia', 'actions-delete', $pubCambia), '4p DISCRIMINANTE: el mismo autor, ya tipo 12 no encargado, ni la edita ni la borra');
            //Lo que se mira es figurar como autor, no haberla creado: el creador que cambia de tipo la conserva.
            $pubCreador = $sembrar('creador-que-cambia', $ids['creadorCambia']);
            $database->prepare('UPDATE `' . UsersModel::TABLE . '` SET type = ? WHERE id = ?')->execute([UsersModel::TYPE_USER_ADMIN_ORG, $ids['creadorCambia']]);
            $check($puede('creadorCambia', 'forms-edit', $pubCreador) && $puede('creadorCambia', 'actions-delete', $pubCreador), '4q su creador, ya tipo 12, la sigue editando y borrando (por haberla creado)');
        } finally {
            OrganizationsRoutes::swapEnabledForTesting(null);
            set_config('current_user', $usuarioAntes);
            set_config('pcsphp_current_user_stored', $guardadoAntes);
            getLoggedFrameworkUser(true);
        }

    } catch (\Throwable $e) {
        $check(false, 'c0 la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        echoTerminal('');
        echoTerminal('[z] Limpieza');
        try {
            //Por familia de prefijo: barre también lo que dejara una corrida anterior cortada.
            foreach ($database->query('SELECT id FROM `' . PublicationMapper::TABLE . '` WHERE title LIKE ' . $database->quote('zz-jpv-%'))->fetchAll(\PDO::FETCH_COLUMN) as $id) {
                $publicaciones[] = (int) $id;
            }
            foreach ($database->query('SELECT id FROM `' . OrganizationMapper::TABLE . '` WHERE name LIKE ' . $database->quote('zz-jpv-%'))->fetchAll(\PDO::FETCH_COLUMN) as $id) {
                $organizaciones[] = (int) $id;
            }
            foreach ($database->query('SELECT id FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote('zz-agente-jpv-%'))->fetchAll(\PDO::FETCH_COLUMN) as $id) {
                $usuarios[] = (int) $id;
            }
            $publicaciones = array_values(array_unique($publicaciones));
            $organizaciones = array_values(array_unique($organizaciones));
            $usuarios = array_values(array_unique($usuarios));
            foreach ($publicaciones as $id) {
                $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue = ?')->execute([PublicationMapper::TABLE, (string) $id]);
                $database->prepare('DELETE FROM `' . AttachmentPublicationMapper::TABLE . '` WHERE publication = ?')->execute([$id]);
                $database->prepare('DELETE FROM `' . PublicationMapper::TABLE . '` WHERE id = ?')->execute([$id]);
            }
            foreach ($organizaciones as $id) {
                $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue = ?')->execute([OrganizationMapper::TABLE, (string) $id]);
                //Quien guarda queda en `modifiedBy` de la organización, con clave foránea al usuario: se suelta antes.
                $database->prepare('UPDATE `' . OrganizationMapper::TABLE . '` SET modifiedBy = NULL WHERE id = ?')->execute([$id]);
            }
            foreach ($usuarios as $id) {
                $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue = ?')->execute([UsersModel::TABLE, (string) $id]);
                //issueToken() abre una sesión por usuario, y la tabla no tiene clave foránea que la arrastre.
                $database->prepare('DELETE FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
                $database->prepare('DELETE FROM `' . UserProfileMapper::TABLE . '` WHERE belongsTo = ?')->execute([$id]);
            }
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE username LIKE ?')->execute(['zz-agente-jpv-%']);
            foreach ($organizaciones as $id) {
                $database->prepare('DELETE FROM `' . OrganizationMapper::TABLE . '` WHERE id = ?')->execute([$id]);
            }
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $restos = 0;
        foreach ([PublicationMapper::TABLE => $publicaciones, OrganizationMapper::TABLE => $organizaciones] as $tabla => $listaIds) {
            foreach ($listaIds as $id) {
                $restos += (int) $database->query("SELECT COUNT(*) FROM `{$tabla}` WHERE id = " . (int) $id)->fetchColumn();
            }
        }
        $restos += (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote('zz-agente-jpv-%'))->fetchColumn();
        foreach ($usuarios as $id) {
            $restos += (int) $database->query('SELECT COUNT(*) FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ' . (int) $id)->fetchColumn();
        }
        $check($restos === 0, 'z1 no queda ninguna fila de la prueba (sesiones incluidas)', "quedan {$restos}");
    }

    return $balance();

})->setDescription('P426: el administrador de organización encargado de la suya edita y borra en Publicaciones lo de ella; lo de otra, lo de la general, un administrador que no es el encargado y Organizaciones apagado, 403; no crea; el listado del panel, solo lo de su organización. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
