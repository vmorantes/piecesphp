<?php

//P384 a: Organizaciones apagado, nadie gana ni pierde acceso por su organización; encendido, nada cambia (zz-, retirados).
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use Organizations\OrganizationsRoutes;
use Organizations\Util\OrganizationAdministration;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\Impersonation;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use PiecesPHP\UserSystem\UserDataPackage;
use Publications\Controllers\PublicationsController;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\Util\Packages\OrganizationApprovalHandler;
use SystemApprovals\Util\SystemApprovalManager;

CliActions::make('unit-tests:core/organizations-off', function ($args) {

    echoTerminal("\e[33m[TEST:OrganizationsOff] Con Organizaciones apagado, la organización ni concede ni recorta\e[39m");
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

    /** Código de un archivo sin comentarios: una mención en un comentario no es una guarda. */
    $code = function (string $relative): string {
        $clean = '';
        foreach (token_get_all((string) file_get_contents(basepath($relative))) as $token) {
            if (is_array($token)) {
                if ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT) {
                    $clean .= $token[1];
                }
            } else {
                $clean .= $token;
            }
        }
        return (string) preg_replace('/\s+/', ' ', $clean);
    };

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la pantalla de respaldos.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::headerName();
    $camino = function (string $url): string {
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $pedir = function (string $path, ?string $jwt, ?array $post = null) use ($base, $prefijoBase, $cabeceraToken): array {
        $path = $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
        $handle = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [],
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

    //La limpieza barre la familia entera: también lo que dejara una corrida interrumpida.
    $familia = 'zz-prueba-org-off-';
    $prefijo = $familia . bin2hex(random_bytes(3));
    $ids = [];
    $crearUsuario = function (string $sufijo, int $tipo, ?int $organizacion, ?string $clave = null) use ($prefijo, &$ids): UsersModel {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Sin clave dada, nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash($clave ?? bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Organizaciones apagado';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        //Sin organización se deja el campo sin asignar: la columna admite null.
        if ($organizacion !== null) {
            $u->organization = $organizacion;
        }
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $ids[$sufijo] = (int) $u->id;
        return new UsersModel((int) $u->id);
    };
    $organizaciones = [];
    $publicaciones = [];
    $crearOrganizacion = function (string $sufijo, int $estado = OrganizationMapper::ACTIVE) use ($prefijo, &$organizaciones): int {
        $o = new OrganizationMapper();
        $o->name = "{$prefijo}-org-{$sufijo}";
        $o->nit = "{$prefijo}-nit-{$sufijo}";
        $o->folder = "{$prefijo}-{$sufijo}";
        $o->status = $estado;
        $o->activitySector = 'Sector de prueba';
        $o->informativeEmail = "{$prefijo}-org-{$sufijo}@example.com";
        $o->save();
        $organizaciones[$sufijo] = (int) $o->id;
        return (int) $o->id;
    };
    $ponerEncargado = function (int $organizacion, int $usuario): OrganizationMapper {
        $o = new OrganizationMapper($organizacion);
        $o->administrator = $usuario;
        $o->update();
        return new OrganizationMapper($organizacion);
    };

    $canEdit = new \ReflectionMethod(UsersController::class, 'canEditOtherUser');
    $canCreate = new \ReflectionMethod(UsersController::class, 'canCreateUser');
    $visible = new \ReflectionMethod(UsersController::class, 'visibleUsersWhereItems');
    //Los criterios del listado de quien mira, sin petición: el controlador sin constructor y con su usuario puesto.
    $filtraPorOrganizacion = function (UsersModel $quien) use ($visible): bool {
        $controlador = (new \ReflectionClass(UsersController::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(AdminPanelController::class, 'user'))->setValue($controlador, (object) ['id' => $quien->id]);
        foreach ($visible->invoke($controlador) as $criterio) {
            if ($criterio instanceof WhereItem && $criterio->getLeftMember() === UsersModel::TABLE . '.organization') {
                return true;
            }
        }
        return false;
    };

    $interruptor = OrganizationsRoutes::swapEnabledForTesting(null);
    $rolesAntes = Roles::getRoles();

    try {
        //──── 1. El interruptor ───────────────────────────────────────────────────────────────
        echoTerminal('[1] El interruptor se lee en un solo sitio y se puede forzar');
        $check(OrganizationsRoutes::enabled() === (bool) OrganizationsRoutes::ENABLE, '1a sin forzar, enabled() es ENABLE', var_export(OrganizationsRoutes::ENABLE, true));
        OrganizationsRoutes::swapEnabledForTesting(false);
        $check(OrganizationsRoutes::enabled() === false, '1b forzado a apagado, enabled() da false');
        OrganizationsRoutes::swapEnabledForTesting(null);
        $check(OrganizationsRoutes::enabled() === (bool) OrganizationsRoutes::ENABLE, '1c y devuelto, vuelve a ENABLE');

        //──── Datos ───────────────────────────────────────────────────────────────────────────
        $orgA = $crearOrganizacion('a');
        $orgB = $crearOrganizacion('b');
        $orgC = $crearOrganizacion('c');
        $encargadoGeneral = $crearUsuario('encargado-general', UsersModel::TYPE_USER_GENERAL, $orgB);
        $miembroB = $crearUsuario('miembro-b', UsersModel::TYPE_USER_GENERAL, $orgB);
        $miembroC = $crearUsuario('miembro-c', UsersModel::TYPE_USER_GENERAL, $orgC);
        $sinOrganizacion = $crearUsuario('sin-org', UsersModel::TYPE_USER_GENERAL, null);
        $deLaGlobal = $crearUsuario('global', UsersModel::TYPE_USER_GENERAL, OrganizationMapper::INITIAL_ID_GLOBAL);
        $actorGlobal = $crearUsuario('actor-global', UsersModel::TYPE_USER_GENERAL, OrganizationMapper::INITIAL_ID_GLOBAL);
        $mapperB = $ponerEncargado($orgB, (int) $encargadoGeneral->id);
        $check($encargadoGeneral->id !== null && $miembroC->id !== null && $mapperB->administrator !== null, '0 CANARIO: los usuarios y las organizaciones de la prueba existen');

        //──── 2. El encargado (index.php) ─────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[2] Ser encargado: grantsAdministratorPermissions(), que es lo que mira index.php');
        OrganizationsRoutes::swapEnabledForTesting(true);
        $check(OrganizationMapper::grantsAdministratorPermissions((int) $encargadoGeneral->id, $mapperB), '2a ENCENDIDO: el encargado recibe los permisos de encargado');
        $check(!OrganizationMapper::grantsAdministratorPermissions((int) $miembroB->id, $mapperB), '2b ENCENDIDO: un miembro que no es el encargado, no');
        $check(!OrganizationMapper::grantsAdministratorPermissions((int) $encargadoGeneral->id, null), '2c ENCENDIDO: sin organización, no');
        OrganizationsRoutes::swapEnabledForTesting(false);
        $check(!OrganizationMapper::grantsAdministratorPermissions((int) $encargadoGeneral->id, $mapperB), '2d APAGADO: el encargado NO recibe los permisos de encargado');
        OrganizationsRoutes::swapEnabledForTesting(null);
        $index = $code('index.php');
        $check(substr_count($index, '$userIsOrganizationAdministrator =') === 2 && str_contains($index, '$userIsOrganizationAdministrator = OrganizationMapper::grantsAdministratorPermissions('), '2e index.php decide el encargado solo con grantsAdministratorPermissions()');

        //──── 3. La gestión de usuarios (UsersController) ─────────────────────────────────────
        echoTerminal('');
        echoTerminal('[3] La gestión de usuarios: con el módulo apagado decide el rol, no la organización');
        //Un rol con la gestión de usuarios sin poder sobre todas las organizaciones (un tipo propio, o el encargado de antes).
        Roles::addPermissions(OrganizationMapper::PERMISSIONS_ON_ADMINISTRATOR, UsersModel::TYPE_USER_GENERAL);
        $check(Roles::hasPermissions('users-form-edit', UsersModel::TYPE_USER_GENERAL, true) && !OrganizationMapper::canModifyAnyOrganization(UsersModel::TYPE_USER_GENERAL), '3.0 CANARIO: el actor tiene la gestión por su rol y no puede con todas las organizaciones');
        $estado = array_values(array_diff(array_keys(UsersModel::STATUSES), UsersModel::STATUSES_HIDDEN_ON_EDIT))[0];

        OrganizationsRoutes::swapEnabledForTesting(true);
        $check($canEdit->invoke(null, $encargadoGeneral, $miembroB, null, null) === true, '3a ENCENDIDO: edita a uno de su organización');
        $check($canEdit->invoke(null, $encargadoGeneral, $miembroC, null, null) === false, '3b ENCENDIDO: no edita a uno de otra');
        $check($canEdit->invoke(null, $encargadoGeneral, $miembroB, $orgC, $estado) === false, '3c ENCENDIDO: no mueve a uno de la suya a otra');
        $check($canEdit->invoke(null, $actorGlobal, $sinOrganizacion, null, null) === false && $canEdit->invoke(null, $actorGlobal, $deLaGlobal, null, null) === true, '3d ENCENDIDO: desde la global, edita a uno de la global y no a uno sin organización (como antes)');
        $check($canCreate->invoke(null, $encargadoGeneral, UsersModel::TYPE_USER_GENERAL, $orgB, $estado) === true && $canCreate->invoke(null, $encargadoGeneral, UsersModel::TYPE_USER_GENERAL, $orgC, $estado) === false, '3e ENCENDIDO: da de alta en su organización y no en otra');
        $check($filtraPorOrganizacion($encargadoGeneral) === true, '3f ENCENDIDO: su listado se filtra por su organización');

        OrganizationsRoutes::swapEnabledForTesting(false);
        $check($canEdit->invoke(null, $encargadoGeneral, $miembroC, null, null) === true, '3g APAGADO: su organización no le recorta: edita a uno de otra, por su rol');
        $check($canEdit->invoke(null, $actorGlobal, $sinOrganizacion, null, null) === true && $canEdit->invoke(null, $actorGlobal, $deLaGlobal, null, null) === true, '3h APAGADO: sin organización y la global cuentan igual');
        $check($canCreate->invoke(null, $encargadoGeneral, UsersModel::TYPE_USER_GENERAL, $orgC, $estado) === true, '3i APAGADO: el alta no depende de su organización');
        $check($filtraPorOrganizacion($encargadoGeneral) === false, '3j APAGADO: su listado no se filtra por organización');
        OrganizationsRoutes::swapEnabledForTesting(null);
        Roles::registerRoles($rolesAntes, true);
        $check($canEdit->invoke(null, $encargadoGeneral, $miembroB, null, null) === false, '3k RECHAZO: sin la gestión por su rol no edita a nadie, ni a uno de su organización');

        //──── 4. «Mi organización» y el perfil público ────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[4] Las rutas de los perfiles de organización y su entrada en el menú');
        OrganizationsRoutes::swapEnabledForTesting(false);
        $grupo = new RouteGroup('/zz-prueba-organizations-off');
        OrganizationsRoutes::profileRoutes($grupo);
        $registradas = (new \ReflectionProperty($grupo, 'routes'))->getValue($grupo);
        $check(is_array($registradas) && count($registradas) === 0, '4a APAGADO: profileRoutes() no registra ninguna ruta', is_array($registradas) ? count($registradas) . ' rutas' : '');
        OrganizationsRoutes::swapEnabledForTesting(null);
        $rutas = (array) get_routes();
        $perfiles = ['my-organization-profile-admin-my-organization-profile', 'profile-organization-admin-profile'];
        $presentes = array_values(array_filter($perfiles, fn (string $nombre): bool => array_key_exists($nombre, $rutas)));
        $check($presentes === (OrganizationsRoutes::ENABLE ? $perfiles : []), '4b en esta instalación, las rutas de los perfiles existen si y solo si el módulo está encendido', implode(', ', $presentes));
        $guarda = "OrganizationsRoutes::enabled() && MyOrganizationProfileController::allowedRoute('my-organization-profile')";
        $check(str_contains($code('app/config/menu.php'), $guarda) && str_contains($code('app/view/panel/layout/topbar-bar.php'), $guarda), '4c «Mi organización» en el menú y en la barra mira el interruptor');

        //──── 5. Por HTTP, con el estado real ─────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[5] Por HTTP, con el módulo ' . (OrganizationsRoutes::ENABLE ? 'ENCENDIDO' : 'APAGADO') . ' en esta instalación');
        if ($base === '') {
            echoTerminal("   \e[33m[OMITIDA]\e[39m no hay base HTTP (PCSPHP_WALK_BASE ni la matriz de permisos).");
        } else {
            $tokenEncargado = UserSessions::issueToken((int) $encargadoGeneral->id);
            $listado = $pedir($camino(UsersController::routeName('list', [], true) ?: (string) get_route('users-list')), $tokenEncargado);
            $editar = $pedir($camino((string) get_route('users-form-edit', ['id' => $miembroB->id])), $tokenEncargado);
            $editarOtra = $pedir($camino((string) get_route('users-form-edit', ['id' => $miembroC->id])), $tokenEncargado);
            $inicio = $pedir($camino((string) get_route('users-form-profile')), $tokenEncargado);
            $enlaceMiOrganizacion = str_contains($inicio['body'], '/my-organization-profile');
            if (OrganizationsRoutes::ENABLE) {
                $check($listado['status'] === 200, '5a ENCENDIDO: el encargado (de tipo general) abre el listado de usuarios', "HTTP {$listado['status']}");
                $check($editar['status'] === 200 && $editarOtra['status'] === 403, '5b ENCENDIDO: edita a uno de su organización (200) y no a uno de otra (403)', "HTTP {$editar['status']} / {$editarOtra['status']}");
                $check($inicio['status'] === 200 && $enlaceMiOrganizacion, '5c ENCENDIDO: su panel enlaza «Mi organización»', "HTTP {$inicio['status']}");
            } else {
                $check($listado['status'] === 403, '5a APAGADO: el encargado (de tipo general) NO abre el listado de usuarios', "HTTP {$listado['status']}");
                $check($editar['status'] === 403 && $editarOtra['status'] === 403, '5b APAGADO: no edita a nadie, ni a uno de su organización', "HTTP {$editar['status']} / {$editarOtra['status']}");
                $check($inicio['status'] === 200 && !$enlaceMiOrganizacion, '5c APAGADO: su panel no enlaza «Mi organización»', "HTTP {$inicio['status']}");
            }
        }

        //──── 6. El estado de la organización y la sesión ─────────────────────────────────────
        echoTerminal('');
        echoTerminal('[6] Una organización inactiva: con el módulo apagado no cierra la sesión de sus usuarios');
        $clave = 'Zz-' . bin2hex(random_bytes(12));
        $orgInactiva = $crearOrganizacion('inactiva', OrganizationMapper::INACTIVE);
        $deInactiva = $crearUsuario('de-inactiva', UsersModel::TYPE_USER_GENERAL, $orgInactiva, $clave);
        $filaInactiva = OrganizationMapper::getBy($orgInactiva, 'id');
        $check(is_object($filaInactiva) && !in_array($filaInactiva->status, Impersonation::ORGANIZATION_STATUSES_WITH_SESSION), '6.0 CANARIO: su estado es de los que cierran la sesión', is_object($filaInactiva) ? (string) $filaInactiva->status : '');
        $tokenInactiva = UserSessions::issueToken((int) $deInactiva->id);
        OrganizationsRoutes::swapEnabledForTesting(true);
        $check(!OrganizationMapper::statusAllowsSession(is_object($filaInactiva) ? $filaInactiva : null) && OrganizationMapper::statusAllowsSession(null) && OrganizationMapper::statusAllowsSession(new OrganizationMapper($orgA)), '6a ENCENDIDO: la inactiva no deja sesión; sin organización y una activa, sí');
        $check(!UserSessions::jwtOpensProtectedFiles($tokenInactiva), '6b ENCENDIDO: no abre archivos protegidos');
        $check(Impersonation::refusalReason((int) $deInactiva->id) !== null, '6c ENCENDIDO: no se le puede suplantar');
        OrganizationsRoutes::swapEnabledForTesting(false);
        $check(OrganizationMapper::statusAllowsSession(is_object($filaInactiva) ? $filaInactiva : null), '6d APAGADO: la inactiva deja sesión');
        $check(UserSessions::jwtOpensProtectedFiles($tokenInactiva), '6e APAGADO: abre archivos protegidos');
        $check(Impersonation::refusalReason((int) $deInactiva->id) === null, '6f APAGADO: se le puede suplantar', (string) Impersonation::refusalReason((int) $deInactiva->id));
        OrganizationsRoutes::swapEnabledForTesting(null);
        $sueltas = 0;
        foreach (['index.php', 'app/classes/PiecesPHP/UserSystem/Controllers/UsersController.php', 'app/classes/PiecesPHP/UserSystem/Authentication/Impersonation.php', 'app/classes/PiecesPHP/UserSystem/Authentication/UserSessions.php'] as $archivo) {
            $sueltas += preg_match_all('/in_array\([^;]*(ORGANIZATION_STATUSES_WITH_SESSION|STATUSES_FOR_LOGIN)/', $code($archivo));
        }
        $index = $code('index.php');
        $usuariosCodigo = $code('app/classes/PiecesPHP/UserSystem/Controllers/UsersController.php');
        $check($sueltas === 0 && str_contains($index, 'if (!OrganizationMapper::statusAllowsSession($organizationMapper))') && substr_count($usuariosCodigo, 'OrganizationMapper::statusAllowsSession($organizationMapper)') === 2 && str_contains($code('app/classes/PiecesPHP/UserSystem/Authentication/Impersonation.php'), 'return OrganizationMapper::statusAllowsSession('), '6g arranque, login, isAuth y suplantación deciden con statusAllowsSession(), sin listas sueltas', "sueltas {$sueltas}");

        if ($base === '') {
            echoTerminal("   \e[33m[OMITIDA]\e[39m por HTTP: no hay base.");
        } else {
            $login = $pedir($camino((string) get_route('users-login-request')), null, ['username' => (string) $deInactiva->username, 'password' => $clave]);
            $entra = json_decode($login['body'], true);
            $verifica = json_decode($pedir($camino((string) get_route('users-verify-login-request')), $tokenInactiva, [])['body'], true);
            $perfil = $pedir($camino((string) get_route('users-form-profile')), $tokenInactiva);
            if (OrganizationsRoutes::ENABLE) {
                $check(is_array($entra) && ($entra['auth'] ?? null) === false && ($entra['error'] ?? '') === 'ORGANIZATION_IS_NOT_ACTIVE', '6h ENCENDIDO: con su contraseña no entra: la organización no está activa', mb_substr($login['body'], 0, 160));
                $check(is_array($verifica) && ($verifica['isAuth'] ?? null) === false, '6i ENCENDIDO: la verificación de sesión dice que no la tiene');
                $check($perfil['status'] !== 200, '6j ENCENDIDO: cada petición le niega la sesión', "HTTP {$perfil['status']}");
            } else {
                $check(is_array($entra) && ($entra['auth'] ?? null) === true, '6h APAGADO: con su contraseña entra', mb_substr($login['body'], 0, 160));
                $check(is_array($verifica) && ($verifica['isAuth'] ?? null) === true, '6i APAGADO: la verificación de sesión dice que la tiene');
                $check($perfil['status'] === 200, '6j APAGADO: mantiene la sesión en cada petición', "HTTP {$perfil['status']}");
            }
        }

        //──── 7. Las aprobaciones de organizaciones ───────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[7] Aprobar o rechazar una organización: con el módulo apagado no arrastra a sus usuarios');
        OrganizationsRoutes::swapEnabledForTesting(true);
        $check(OrganizationApprovalHandler::isEnabled(), '7a ENCENDIDO: el manejador de aprobaciones de organizaciones se registra');
        OrganizationsRoutes::swapEnabledForTesting(false);
        $check(!OrganizationApprovalHandler::isEnabled(), '7b APAGADO: no se registra');
        OrganizationsRoutes::swapEnabledForTesting(null);
        $manager = SystemApprovalManager::getInstance();
        $check(($manager->getHandler(OrganizationMapper::TABLE) !== null) === (bool) OrganizationsRoutes::ENABLE, '7c en esta instalación, el gestor lo tiene si y solo si el módulo está encendido');
        $orgD = $crearOrganizacion('d');
        $miembroD = $crearUsuario('miembro-d', UsersModel::TYPE_USER_GENERAL, $orgD);
        $resolver = function (string $estado) use ($manager, $orgD): void {
            $fila = new SystemApprovalsMapper();
            $fila->referenceTable = OrganizationMapper::TABLE;
            $fila->referenceValue = (string) $orgD;
            $fila->status = $estado;
            $manager->updateStatus($fila);
        };
        $estadoDe = fn (int $id): int => (int) (new UsersModel($id))->status;
        $resolver(SystemApprovalsMapper::STATUS_REJECTED);
        $trasRechazo = $estadoDe((int) $miembroD->id);
        $resolver(SystemApprovalsMapper::STATUS_APPROVED);
        $trasAprobar = $estadoDe((int) $miembroD->id);
        if (OrganizationsRoutes::ENABLE) {
            $check($trasRechazo === UsersModel::STATUS_USER_APPROVED_PENDING && $trasAprobar === UsersModel::STATUS_USER_ACTIVE, '7d ENCENDIDO: rechazarla deja pendiente a su miembro activo y aprobarla lo devuelve a activo', "{$trasRechazo} / {$trasAprobar}");
        } else {
            $check($trasRechazo === UsersModel::STATUS_USER_ACTIVE && $trasAprobar === UsersModel::STATUS_USER_ACTIVE, '7d APAGADO: rechazarla o aprobarla no cambia el estado de su miembro', "{$trasRechazo} / {$trasAprobar}");
        }

        //──── 8. Publicaciones ────────────────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[8] Publicaciones: con el módulo apagado la organización no acota listados, edición ni vista previa');
        $database = (new BaseModel())->getDatabase();
        $categoria = $database->query('SELECT id FROM `' . PublicationCategoryMapper::TABLE . '` ORDER BY id LIMIT 1')->fetchColumn();
        $orgE = $crearOrganizacion('e');
        $orgF = $crearOrganizacion('f');
        $rootSinOrganizacion = $crearUsuario('root-pub', UsersModel::TYPE_USER_ROOT, null);
        $editorE = $crearUsuario('editor-e', UsersModel::TYPE_USER_COMUNICACIONES, $orgE);
        $encargadoE = $crearUsuario('encargado-e', UsersModel::TYPE_USER_COMUNICACIONES, $orgE);
        $autorE = $crearUsuario('autor-e', UsersModel::TYPE_USER_INSTITUCIONAL, $orgE);
        $editorF = $crearUsuario('editor-f', UsersModel::TYPE_USER_INSTITUCIONAL, $orgF);
        $ponerEncargado($orgE, (int) $encargadoE->id);
        $sembrar = function (string $sufijo, UsersModel $creador, int $estado) use ($database, $categoria, $prefijo, &$publicaciones): int {
            $ahora = date('Y-m-d H:i:s');
            $database->prepare('INSERT INTO `' . PublicationMapper::TABLE . '` (title, content, seoDescription, author, category, mainImage, thumbImage, ogImage, folder, visits, publicDate, createdAt, createdBy, status, featured, meta)'
                . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 0, ?)')
                ->execute(["{$prefijo}-pub-{$sufijo}", 'zz-', 'zz-', (int) $creador->id, (int) $categoria, '', '', '', "{$prefijo}-pub-{$sufijo}", $ahora, $ahora, (int) $creador->id, $estado, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
            $publicaciones[$sufijo] = (int) $database->lastInsertId();
            return $publicaciones[$sufijo];
        };
        $pubRoot = $sembrar('root', $rootSinOrganizacion, PublicationMapper::ACTIVE);
        $pubRootBorrador = $sembrar('root-borrador', $rootSinOrganizacion, PublicationMapper::DRAFT);
        $pubE = $sembrar('e', $autorE, PublicationMapper::ACTIVE);
        $pubEBorrador = $sembrar('e-borrador', $autorE, PublicationMapper::DRAFT);
        $pubF = $sembrar('f', $editorF, PublicationMapper::ACTIVE);
        $pubFBorrador = $sembrar('f-borrador', $editorF, PublicationMapper::DRAFT);
        //El listado público solo enseña lo aprobado: init() les crea su fila y se aprueban aquí.
        SystemApprovalManager::init();
        foreach ($publicaciones as $idPublicacion) {
            $database->prepare('UPDATE `' . SystemApprovalsMapper::TABLE . '` SET status = ? WHERE referenceTable = ? AND referenceValue = ?')->execute([SystemApprovalsMapper::STATUS_APPROVED, PublicationMapper::TABLE, (string) $idPublicacion]);
        }
        $check($categoria !== false && $pubFBorrador > 0, '8.0 CANARIO: hay categoría y las seis publicaciones existen');

        //La vista previa, en memoria: quien pide, con sus tres datos.
        $paquete = function (UsersModel $u): UserDataPackage {
            $clase = new \ReflectionClass(UserDataPackage::class);
            $p = $clase->newInstanceWithoutConstructor();
            foreach (['id' => (int) $u->id, 'type' => (int) $u->type, 'organization' => $u->organization !== null ? (int) $u->organization : null] as $propiedad => $valor) {
                $clase->getProperty($propiedad)->setValue($p, $valor);
            }
            return $p;
        };
        $previa = fn (int $pub, UsersModel $u): bool => PublicationMapper::canBePreviewedBy(new PublicationMapper($pub), $paquete($u));
        $sinBorradores = $crearUsuario('general-pub', UsersModel::TYPE_USER_GENERAL, $orgE);
        OrganizationsRoutes::swapEnabledForTesting(true);
        $check($previa($pubEBorrador, $editorE) && !$previa($pubRootBorrador, $editorE) && !$previa($pubFBorrador, $editorE), '8a ENCENDIDO: vista previa del borrador de su organización sí; del de root (sin organización) y de otra, no');
        OrganizationsRoutes::swapEnabledForTesting(false);
        $check($previa($pubRootBorrador, $editorE) && $previa($pubFBorrador, $editorE), '8b APAGADO: con permiso de borradores ve el de root y el de otra organización');
        $check(!$previa($pubFBorrador, $sinBorradores) && !$previa($pubEBorrador, $sinBorradores), '8c APAGADO: RECHAZO: sin permiso de borradores, ninguno, ni el de su organización');
        OrganizationsRoutes::swapEnabledForTesting(null);

        //La edición, en memoria: allowedRoute() mira al usuario actual, que se pone y se devuelve.
        $usuarioAntes = get_config('current_user');
        $guardadoAntes = get_config('pcsphp_current_user_stored');
        $puedeEditar = function (UsersModel $u, int $pub): bool {
            set_config('current_user', (object) ['id' => (int) $u->id]);
            set_config('pcsphp_current_user_stored', null);
            getLoggedFrameworkUser(true);
            return PublicationsController::allowedRoute('forms-edit', ['id' => $pub]);
        };
        try {
            OrganizationsRoutes::swapEnabledForTesting(true);
            $check($puedeEditar($encargadoE, $pubE) === true && $puedeEditar($editorE, $pubE) === false, '8d ENCENDIDO: el encargado edita la de su organización que no creó; otro editor de ella, no');
            OrganizationsRoutes::swapEnabledForTesting(false);
            $check($puedeEditar($encargadoE, $pubE) === false, '8e APAGADO: ser encargado no le deja editar una ajena que no creó ni firma');
            $check($puedeEditar($autorE, $pubE) === true, '8f APAGADO: su creador sí la edita');
            OrganizationsRoutes::swapEnabledForTesting(true);
            //Documentos y Formularios siguen exigiendo el tipo; Publicaciones no (su encargado puede ser de cualquier tipo).
            set_config('current_user', (object) ['id' => (int) $encargadoE->id]);
            set_config('pcsphp_current_user_stored', null);
            $paqueteEncargadoE = getLoggedFrameworkUser(true);
            $check($paqueteEncargadoE !== null && OrganizationAdministration::administersCreatorOf($autorE->id, $paqueteEncargadoE) === false && OrganizationAdministration::administersCreatorOf($autorE->id, $paqueteEncargadoE, false) === true,
                '8k ENCENDIDO: con el tipo exigido (Documentos, Formularios) un encargado de comunicaciones no administra; sin exigirlo (Publicaciones), sí');
            $puedeBorrarE = (function () use ($encargadoE, $pubE): bool {
                set_config('current_user', (object) ['id' => (int) $encargadoE->id]);
                set_config('pcsphp_current_user_stored', null);
                getLoggedFrameworkUser(true);
                return PublicationsController::allowedRoute('actions-delete', ['id' => $pubE]);
            })();
            $check($puedeBorrarE === true, '8k.1 CANARIO: ENCENDIDO, el encargado de su organización SÍ borra la de ella que no creó');
            //La organización general no cuenta: su administrador no edita ni borra lo de sus usuarios. Se repone la fila entera.
            $tablaOrganizaciones = OrganizationMapper::TABLE;
            $filaGeneral = $database->query("SELECT * FROM `{$tablaOrganizaciones}` WHERE id = " . OrganizationMapper::INITIAL_ID_GLOBAL)->fetch(\PDO::FETCH_ASSOC);
            if ($check(is_array($filaGeneral), '8l.0 CANARIO: existe la organización general') && is_array($filaGeneral)) {
                $encargadoGeneralOrg = $crearUsuario('encargado-org-general', UsersModel::TYPE_USER_COMUNICACIONES, OrganizationMapper::INITIAL_ID_GLOBAL);
                $autorGeneralOrg = $crearUsuario('autor-org-general', UsersModel::TYPE_USER_INSTITUCIONAL, OrganizationMapper::INITIAL_ID_GLOBAL);
                $pubG = $sembrar('g', $autorGeneralOrg, PublicationMapper::ACTIVE);
                try {
                    $ponerEncargado(OrganizationMapper::INITIAL_ID_GLOBAL, (int) $encargadoGeneralOrg->id);
                    $puedeBorrarG = (function () use ($encargadoGeneralOrg, $pubG): bool {
                        set_config('current_user', (object) ['id' => (int) $encargadoGeneralOrg->id]);
                        set_config('pcsphp_current_user_stored', null);
                        getLoggedFrameworkUser(true);
                        return PublicationsController::allowedRoute('actions-delete', ['id' => $pubG]);
                    })();
                    $check($puedeEditar($encargadoGeneralOrg, $pubG) === false && $puedeBorrarG === false, '8l ENCENDIDO: el administrador de la organización general no edita ni borra la publicación de un usuario de ella');
                } finally {
                    $columnas = array_keys($filaGeneral);
                    $database->prepare("UPDATE `{$tablaOrganizaciones}` SET " . implode(', ', array_map(fn (string $c): string => "`{$c}` = ?", $columnas)) . ' WHERE id = ?')
                        ->execute([...array_values($filaGeneral), OrganizationMapper::INITIAL_ID_GLOBAL]);
                    $filaGeneralDespues = $database->query("SELECT * FROM `{$tablaOrganizaciones}` WHERE id = " . OrganizationMapper::INITIAL_ID_GLOBAL)->fetch(\PDO::FETCH_ASSOC);
                    $check($filaGeneralDespues === $filaGeneral, '8l.1 la fila de la organización general queda como estaba');
                }
            }
        } finally {
            OrganizationsRoutes::swapEnabledForTesting(null);
            set_config('current_user', $usuarioAntes);
            set_config('pcsphp_current_user_stored', $guardadoAntes);
        }
        $codigoPublicaciones = $code('app/classes/Publications/Controllers/PublicationsController.php');
        $check(str_contains($codigoPublicaciones, 'if (!in_array($currentUserType, PublicationMapper::CAN_VIEW_ALL)) { if (!OrganizationsRoutes::enabled()) {')
            && str_contains($codigoPublicaciones, '$organizationScope = $currentUser !== null && OrganizationsRoutes::enabled() && !in_array(')
            && substr_count($codigoPublicaciones, '$allowByOrg = OrganizationAdministration::administersCreatorOf($publication->createdBy, $currentUser, false);') === 2, '8g el listado del panel, el público con no activas y la edición miran el interruptor (la edición, por OrganizationAdministration)');

        if ($base === '') {
            echoTerminal("   \e[33m[OMITIDA]\e[39m por HTTP: no hay base.");
        } else {
            $tokenEditor = UserSessions::issueToken((int) $editorE->id);
            $tokenEncargadoE = UserSessions::issueToken((int) $encargadoE->id);
            $panel = $pedir($camino(PublicationsController::routeName('datatables', [], true)) . '?draw=1&start=0&length=100&search[value]=' . rawurlencode($prefijo), $tokenEditor);
            $publico = $pedir($camino(PublicationsController::routeName('ajax-all', [], true)) . '?page=1&per_page=100&status=ANY&title=' . rawurlencode($prefijo), $tokenEditor);
            $editar = $pedir($camino(PublicationsController::routeName('forms-edit', ['id' => $pubE], true)), $tokenEncargadoE);
            $ve = fn (array $r, string $sufijo): bool => preg_match('/' . preg_quote("{$prefijo}-pub-{$sufijo}", '/') . '(?![a-z-])/', $r['body']) === 1;
            $vistas = fn (array $r): string => implode(',', array_filter(['root', 'root-borrador', 'e', 'e-borrador', 'f', 'f-borrador'], fn (string $sufijo): bool => $ve($r, $sufijo)));
            if (OrganizationsRoutes::ENABLE) {
                $check($panel['status'] === 200 && $ve($panel, 'e') && !$ve($panel, 'root') && !$ve($panel, 'f'), '8h ENCENDIDO: en el panel ve la de su organización y no la de root ni la de otra', "HTTP {$panel['status']}: " . $vistas($panel));
                $check($publico['status'] === 200 && $ve($publico, 'e-borrador') && !$ve($publico, 'root-borrador') && !$ve($publico, 'f-borrador'), '8i ENCENDIDO: con no activas, el borrador de su organización sí y los otros no', "HTTP {$publico['status']}: " . $vistas($publico) . ' ' . mb_substr($publico['body'], 0, 200));
                $check($editar['status'] === 200, '8j ENCENDIDO: el encargado abre la edición de la de su organización', "HTTP {$editar['status']}");
            } else {
                $check($panel['status'] === 200 && $ve($panel, 'e') && $ve($panel, 'root') && $ve($panel, 'f'), '8h APAGADO: en el panel ve también la de root y la de otra organización', "HTTP {$panel['status']}: " . $vistas($panel));
                $check($publico['status'] === 200 && $ve($publico, 'e-borrador') && $ve($publico, 'root-borrador') && $ve($publico, 'f-borrador'), '8i APAGADO: con no activas, sin acotar por organización', "HTTP {$publico['status']}: " . $vistas($publico));
                $check($editar['status'] === 403, '8j APAGADO: el encargado no abre la edición de una ajena', "HTTP {$editar['status']}");
            }
        }

        //──── 9. Guardar un usuario no mueve su organización ──────────────────────────────────
        echoTerminal('');
        echoTerminal('[9] Guardar un usuario: con el módulo apagado el servidor manda (editar conserva, crear pone la global)');
        $orgG = $crearOrganizacion('g');
        $orgH = $crearOrganizacion('h');
        $adminGeneral = $crearUsuario('admin-general', UsersModel::TYPE_USER_ADMIN_GRAL, null);
        $editado = $crearUsuario('editado', UsersModel::TYPE_USER_GENERAL, $orgG);
        $organizacionDe = fn (int $id): ?int => ($o = (new UsersModel($id))->organization) !== null ? (int) $o : null;
        $porNombre = function (string $sufijo) use ($prefijo): ?UsersModel {
            $fila = UsersModel::getBy("{$prefijo}-{$sufijo}", 'username');
            return $fila !== null ? new UsersModel((int) $fila->id) : null;
        };
        if ($base === '') {
            echoTerminal("   \e[33m[OMITIDA]\e[39m por HTTP: no hay base.");
        } else {
            $tokenAdmin = UserSessions::issueToken((int) $adminGeneral->id);
            $rutaEditar = $camino(UsersController::routeName('edit-request', [], true));
            $rutaAlta = $camino(UsersController::routeName('register-request', [], true));
            $cuerpo = fn (array $cambios): array => array_merge(['id' => (int) $editado->id, 'username' => (string) $editado->username, 'email' => (string) $editado->email, 'firstname' => 'Zz', 'first_lastname' => 'Organizaciones apagado', 'status' => UsersModel::STATUS_USER_ACTIVE], $cambios);
            $conOtra = json_decode($pedir($rutaEditar, $tokenAdmin, $cuerpo(['firstname' => 'ZzOtra', 'organization' => $orgH]))['body'], true);
            $trasOtra = $organizacionDe((int) $editado->id);
            $sinCampo = json_decode($pedir($rutaEditar, $tokenAdmin, $cuerpo(['firstname' => 'ZzSinCampo']))['body'], true);
            $trasSinCampo = $organizacionDe((int) $editado->id);
            $nombreTrasSinCampo = (string) (new UsersModel((int) $editado->id))->firstname;
            $clave = 'Zz-' . bin2hex(random_bytes(12));
            $alta = fn (string $sufijo, array $extra): array => $pedir($rutaAlta, $tokenAdmin, array_merge(['username' => "{$prefijo}-{$sufijo}", 'email' => "{$prefijo}-{$sufijo}@example.com", 'password' => $clave, 'password2' => $clave, 'firstname' => 'Zz', 'first_lastname' => 'Alta', 'type' => UsersModel::TYPE_USER_GENERAL, 'status' => UsersModel::STATUS_USER_ACTIVE], $extra));
            $alta('alta-con-h', ['organization' => $orgH]);
            $alta('alta-sin-campo', []);
            $altaConH = $porNombre('alta-con-h');
            $altaSinCampo = $porNombre('alta-sin-campo');
            if (OrganizationsRoutes::ENABLE) {
                $check(is_array($conOtra) && $trasOtra === $orgH, '9a ENCENDIDO: editar con otra organización la cambia (como antes)', var_export($trasOtra, true));
                $check($trasSinCampo === $orgH && $nombreTrasSinCampo !== 'ZzSinCampo', '9b ENCENDIDO: editar sin el campo no guarda: la organización es obligatoria', var_export($trasSinCampo, true) . " · {$nombreTrasSinCampo}");
                $check($altaConH !== null && $altaConH->organization !== null && (int) $altaConH->organization === $orgH, '9c ENCENDIDO: el alta queda en la organización pedida');
                $check($altaSinCampo === null, '9d ENCENDIDO: el alta sin organización no se crea');
            } else {
                $check(is_array($conOtra) && ($conOtra['success'] ?? false) === true && $trasOtra === $orgG, '9a APAGADO: editar con otra organización guarda y la conserva', var_export($trasOtra, true));
                $check($trasSinCampo === $orgG && $nombreTrasSinCampo === 'ZzSinCampo', '9b APAGADO: editar sin el campo guarda y la conserva', var_export($trasSinCampo, true) . " · {$nombreTrasSinCampo}");
                $check($altaConH !== null && $altaConH->organization !== null && (int) $altaConH->organization === OrganizationMapper::INITIAL_ID_GLOBAL, '9c APAGADO: el alta va a la global aunque pida otra', $altaConH !== null ? var_export($altaConH->organization, true) : 'no se creó');
                $check($altaSinCampo !== null && $altaSinCampo->organization !== null && (int) $altaSinCampo->organization === OrganizationMapper::INITIAL_ID_GLOBAL, '9d APAGADO: el alta sin el campo va a la global', $altaSinCampo !== null ? var_export($altaSinCampo->organization, true) : 'no se creó');
            }
        }
        $usuariosCodigo = $code('app/classes/PiecesPHP/UserSystem/Controllers/UsersController.php');
        $check(str_contains($usuariosCodigo, "if (!OrganizationsRoutes::enabled()) { \$body['organization'] = OrganizationMapper::INITIAL_ID_GLOBAL;")
            && str_contains($usuariosCodigo, "\$organization = \$organizationsEnabled ? \$parametersExcepted->getValue('organization') : null;")
            && str_contains($usuariosCodigo, 'if ($organizationsEnabled && UserTypes::requiresOrganization((int) $userMapper->type)) {'), '9e el alta y la edición del panel miran el interruptor');

        //──── 10. El administrador de organización sin el módulo ──────────────────────────────
        echoTerminal('');
        echoTerminal('[10] El tipo 12 con el módulo apagado no tiene rol ni entra');
        $check(Roles::roleExists(UsersModel::TYPE_USER_ADMIN_ORG) === (bool) OrganizationsRoutes::ENABLE, '10a en esta instalación, el tipo 12 tiene rol si y solo si el módulo está encendido');
        if ($base !== '') {
            $claveDoce = 'Zz-' . bin2hex(random_bytes(12));
            $doce = $crearUsuario('tipo-doce', UsersModel::TYPE_USER_ADMIN_ORG, $orgG, $claveDoce);
            $tokenDoce = UserSessions::issueToken((int) $doce->id);
            $estados = [];
            foreach (['admin', 'users-form-profile'] as $rutaDoce) {
                $estados[$rutaDoce] = $pedir($camino((string) get_route($rutaDoce)), $tokenDoce)['status'];
            }
            $loginDoce = json_decode($pedir($camino((string) get_route('users-login-request')), null, ['username' => (string) $doce->username, 'password' => $claveDoce])['body'], true);
            $detalleDoce = json_encode($estados) . ' · login ' . (is_array($loginDoce) ? (string) ($loginDoce['error'] ?? 'auth=' . var_export($loginDoce['auth'] ?? null, true)) : '');
            if (OrganizationsRoutes::ENABLE) {
                $check($estados['users-form-profile'] === 200 && is_array($loginDoce) && ($loginDoce['auth'] ?? null) === true, '10b ENCENDIDO: entra y abre su perfil', $detalleDoce);
            } else {
                $check(!in_array(200, $estados, true) && is_array($loginDoce) && ($loginDoce['auth'] ?? null) === false && ($loginDoce['error'] ?? '') === 'INVALID_USER_TYPE', '10b APAGADO: no entra con su contraseña y su token no abre ninguna ruta del panel', $detalleDoce);
            }
        }

        //──── 11. La aprobación automática por la organización ────────────────────────────────
        echoTerminal('');
        echoTerminal('[11] Un alta pendiente en la global: con el módulo apagado no se aprueba por su organización');
        $pendienteEn = function (string $sufijo) use ($crearUsuario): int {
            $u = $crearUsuario($sufijo, UsersModel::TYPE_USER_GENERAL, OrganizationMapper::INITIAL_ID_GLOBAL);
            $p = new UsersModel((int) $u->id);
            $p->status = UsersModel::STATUS_USER_APPROVED_PENDING;
            $p->update();
            return (int) $u->id;
        };
        $check(SystemApprovalManager::getInstance()->isApproved(OrganizationMapper::class, OrganizationMapper::INITIAL_ID_GLOBAL) === (bool) OrganizationsRoutes::ENABLE, '11.0 CANARIO: la global cuenta como aprobada si y solo si el manejador está registrado');
        $apagadoID = $pendienteEn('pendiente-apagado');
        OrganizationsRoutes::swapEnabledForTesting(false);
        $aprobadoApagado = \SystemApprovals\Util\Packages\UsersApprovalHandler::approveIfOrganizationApproved(new UsersModel($apagadoID));
        OrganizationsRoutes::swapEnabledForTesting(null);
        $check($aprobadoApagado === false && (int) (new UsersModel($apagadoID))->status === UsersModel::STATUS_USER_APPROVED_PENDING, '11a APAGADO: sigue pendiente', var_export($aprobadoApagado, true));
        if (OrganizationsRoutes::ENABLE) {
            $encendidoID = $pendienteEn('pendiente-encendido');
            OrganizationsRoutes::swapEnabledForTesting(true);
            $aprobadoEncendido = \SystemApprovals\Util\Packages\UsersApprovalHandler::approveIfOrganizationApproved(new UsersModel($encendidoID));
            OrganizationsRoutes::swapEnabledForTesting(null);
            $check($aprobadoEncendido === true && (int) (new UsersModel($encendidoID))->status === UsersModel::STATUS_USER_ACTIVE, '11b ENCENDIDO: se aprueba al crearlo, como antes', var_export($aprobadoEncendido, true));
        }

        //──── 12. Sin rastro de la organización en etiquetas, búsqueda y manual ───────────────
        echoTerminal('');
        echoTerminal('[12] Con el módulo apagado no queda rastro de la organización en la etiqueta, la búsqueda ni el manual');
        $independiente = $crearUsuario('independiente', UsersModel::TYPE_USER_GENERAL, OrganizationMapper::INITIAL_ID_GLOBAL);
        OrganizationsRoutes::swapEnabledForTesting(true);
        $etiquetaEncendido = \SystemApprovals\Util\Packages\UsersApprovalHandler::getContentTypeSpecificMapper((int) $independiente->id);
        OrganizationsRoutes::swapEnabledForTesting(false);
        $etiquetaApagado = \SystemApprovals\Util\Packages\UsersApprovalHandler::getContentTypeSpecificMapper((int) $independiente->id);
        OrganizationsRoutes::swapEnabledForTesting(null);
        $check($etiquetaEncendido === \SystemApprovals\Util\Packages\UsersApprovalHandler::TEXT_INDEPENDENT_USER, '12a ENCENDIDO: un usuario general de la global es «Usuario independiente»', $etiquetaEncendido);
        $check($etiquetaApagado !== \SystemApprovals\Util\Packages\UsersApprovalHandler::TEXT_INDEPENDENT_USER, '12b APAGADO: no lleva esa etiqueta', $etiquetaApagado);

        $usuarioAntes = get_config('current_user');
        $guardadoAntes = get_config('pcsphp_current_user_stored');
        try {
            set_config('current_user', (object) ['id' => (int) $rootSinOrganizacion->id]);
            set_config('pcsphp_current_user_stored', null);
            getLoggedFrameworkUser(true);
            $capitulos = array_column(\MySpace\Controllers\MySpaceController::manualChapters(), 'slug');
        } finally {
            set_config('current_user', $usuarioAntes);
            set_config('pcsphp_current_user_stored', $guardadoAntes);
        }
        $check(in_array('00-tu-cuenta', $capitulos, true) && in_array('58-tu-organizacion', $capitulos, true) === (bool) OrganizationsRoutes::ENABLE, '12c en esta instalación, «Tu organización» sale en el manual de root si y solo si el módulo está encendido', implode(',', $capitulos));

        if ($base !== '') {
            $orgK = $crearOrganizacion('k');
            $crearUsuario('miembro-k', UsersModel::TYPE_USER_GENERAL, $orgK);
            $tokenBusqueda = UserSessions::issueToken((int) $adminGeneral->id);
            $buscar = function (string $texto) use ($pedir, $camino, $tokenBusqueda): array {
                $columnas = array_map(fn (int $i): array => ['data' => $i, 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']], range(0, 7));
                return $pedir($camino(UsersController::routeName('datatables', [], true)) . '?' . http_build_query(['draw' => 1, 'start' => 0, 'length' => 100, 'columns' => $columnas, 'order' => [['column' => 0, 'dir' => 'desc']], 'search' => ['value' => $texto, 'regex' => 'false']]), $tokenBusqueda);
            };
            $busqueda = $buscar('-org-k');
            $encontrado = str_contains($busqueda['body'], "{$prefijo}-miembro-k");
            $check(!str_contains($buscar('zz-nada-' . bin2hex(random_bytes(4)))['body'], "{$prefijo}-miembro-k") && str_contains($buscar("{$prefijo}-miembro-k")['body'], "{$prefijo}-miembro-k"), '12.0 CANARIO: la búsqueda filtra: un texto que no casa no lo trae y su usuario sí');
            if (OrganizationsRoutes::ENABLE) {
                $check($busqueda['status'] === 200 && $encontrado, '12d ENCENDIDO: el listado de usuarios encuentra por el nombre de la organización', "HTTP {$busqueda['status']}");
            } else {
                $check($busqueda['status'] === 200 && !$encontrado, '12d APAGADO: el listado de usuarios no busca por la organización', "HTTP {$busqueda['status']}");
            }
        }
    } catch (\Throwable $e) {
        $check(false, 'sin excepciones', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        OrganizationsRoutes::swapEnabledForTesting($interruptor);
        Roles::registerRoles($rolesAntes, true);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            //Quien guarda queda en `modifiedBy` de la organización, con clave foránea al usuario: se suelta antes.
            $soltar = OrganizationMapper::model();
            $soltar->resetAll();
            //RETORNO-IGNORADO: si no soltara, el borrado de usuarios lanzaría y lo diría z0.
            $soltar->update(['modifiedBy' => null])->where(new WhereSegment([WhereItem::like('name', "{$familia}%")]))->execute();
            $buscar = UsersModel::model();
            $buscar->resetAll();
            $buscar->select([UsersModel::TABLE . '.id'])->where(new WhereSegment([WhereItem::like('username', "{$familia}%")]))->execute();
            $ids = array_map(fn ($fila): int => (int) $fila->id, (array) $buscar->result());
            $buscarOrganizaciones = OrganizationMapper::model();
            $buscarOrganizaciones->resetAll();
            $buscarOrganizaciones->select([OrganizationMapper::TABLE . '.id'])->where(new WhereSegment([WhereItem::like('name', "{$familia}%")]))->execute();
            $organizaciones = array_map(fn ($fila): int => (int) $fila->id, (array) $buscarOrganizaciones->result());
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ($ids as $id) {
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $database = (new BaseModel())->getDatabase();
            $buscarPublicaciones = $database->prepare('SELECT id FROM `' . PublicationMapper::TABLE . '` WHERE title LIKE ?');
            $buscarPublicaciones->execute(["{$familia}%"]);
            foreach ($buscarPublicaciones->fetchAll(\PDO::FETCH_COLUMN) as $idPublicacion) {
                $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue = ?')->execute([PublicationMapper::TABLE, (string) $idPublicacion]);
            }
            $database->prepare('DELETE FROM `' . PublicationMapper::TABLE . '` WHERE title LIKE ?')->execute(["{$familia}%"]);
            $quedanPublicaciones = (int) $database->query('SELECT COUNT(*) FROM `' . PublicationMapper::TABLE . '` WHERE title LIKE ' . $database->quote("{$familia}%"))->fetchColumn();
            $marcas = implode(',', array_map('intval', $ids ?: [0]));
            $database->exec('DELETE FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$marcas})");
            $database->exec('DELETE FROM `' . LoginAttemptsModel::TABLE . "` WHERE userID IN ({$marcas})");
            $database->prepare('DELETE FROM `' . LoginAttemptsModel::TABLE . '` WHERE usernameAttempt LIKE ?')->execute(["{$familia}%"]);
            $quedanSesiones = (int) $database->query('SELECT COUNT(*) FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$marcas})")->fetchColumn();
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$familia}%")]))->execute();
            foreach ($organizaciones as $id) {
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, OrganizationMapper::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
            }
            $organizacionesModelo = OrganizationMapper::model();
            $organizacionesModelo->resetAll();
            //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
            $organizacionesModelo->delete(new WhereSegment([WhereItem::like('name', "{$familia}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$familia}%")]))->execute();
        $check(count((array) $usuarios->result()) === 0 && ($quedanSesiones ?? 1) === 0, 'z1 no queda ningún usuario de la prueba ni sesión suya');
        $restos = OrganizationMapper::model();
        $restos->resetAll();
        $restos->select()->where(new WhereSegment([WhereItem::like('name', "{$familia}%")]))->execute();
        $check(count((array) $restos->result()) === 0 && ($quedanPublicaciones ?? 1) === 0, 'z2 no queda ninguna organización ni publicación de la prueba');
        $check(OrganizationsRoutes::enabled() === (bool) OrganizationsRoutes::ENABLE, 'z3 el interruptor vuelve a ENABLE');
    }

    $total = $passed + $failed;
    echoTerminal(' ');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P384 a: con el módulo de organizaciones apagado, ser encargado no concede permisos, la organización no recorta la gestión de usuarios (decide el rol; sin organización y la global cuentan igual) y los perfiles de organización no se registran ni salen en el menú. Encendido, todo igual. En memoria con el interruptor forzado y por HTTP con el estado real; crea zz- y lo retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
