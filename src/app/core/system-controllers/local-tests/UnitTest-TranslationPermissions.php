<?php

//Quién traduce con IA (P202, P208 b): por omisión los seis tipos y root siempre; la lista, en Configuración › IA.
//En proceso, sin red: el permiso con Roles::hasPermissions(), la misma comprobación que da el 403 en index.php.
//@areas src/app/classes/API/
//@areas src/app/classes/Publications/
//@areas src/app/classes/PiecesPHP/Settings/

use API\Controllers\APIController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Slim\Exception\HttpForbiddenException;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use SystemApprovals\Mappers\SystemApprovalsMapper;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/translation-permissions';
$cliTaskDescription = 'La traducción con IA: por omisión los seis tipos, root siempre, la lista y su validación en Configuración › IA; y el botón de publicaciones sigue a la ruta';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:TranslationPermissions] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;

    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };

    $rutas = ['api-admin-translations-actions', 'api-admin-translations-actions-full'];
    $nombreOpcion = APIController::TRANSLATION_AI_USER_TYPES_CONFIG;
    //r2, r3, b2 y b3 comparan lo que tiene Roles con la lista guardada; r0, la que queda sin opción.
    $configPrevia = get_config($nombreOpcion);
    $conPermiso = APIController::translationUserTypes($configPrevia);

    //──── 0. La regla, pura ────────────────────────────────────────────────────────────────────
    echoTerminal('[0/3] La regla: APIController::translationUserTypes() y el validador de Configuración › IA');
    $seis = array_keys(UsersModel::TYPES_USERS);
    $igual = function (array $a, array $b): bool {
        sort($a);
        sort($b);
        return $a === $b;
    };
    $check($igual(APIController::translationUserTypes(null), $seis) && $igual(APIController::translationUserTypes('basura'), $seis), 'p1 sin opción, o si no es una lista: los seis tipos');
    $check($igual(APIController::translationUserTypes([UsersModel::TYPE_USER_GENERAL]), [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_GENERAL]), 'p2 una lista con un tipo: ese y root');
    $check(APIController::translationUserTypes([]) === [UsersModel::TYPE_USER_ROOT], 'p3 la lista vacía: solo root (root siempre puede)', json_encode(APIController::translationUserTypes([])) ?: '');
    $check($igual(APIController::translationUserTypes(['x', 99, (string) UsersModel::TYPE_USER_ADMIN_GRAL, null]), [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL]), 'p4 lo que no es un tipo conocido se descarta');
    $check(SettingsController::translationUserTypesIsValid([(string) UsersModel::TYPE_USER_ADMIN_GRAL, (string) UsersModel::TYPE_USER_GENERAL]), 'v1 la pantalla acepta una lista de tipos conocidos');
    $check(SettingsController::translationUserTypesIsValid(['']) && SettingsController::translationUserTypesIsValid([]), 'v2 y la vacía (nada elegido llega como [\'\']: solo root)');
    $check(!SettingsController::translationUserTypesIsValid(['99']) && !SettingsController::translationUserTypesIsValid('2') && !SettingsController::translationUserTypesIsValid('') && !SettingsController::translationUserTypesIsValid(['x']), 'v3 DISCRIMINANTE: rechaza un tipo desconocido, un valor suelto y basura');
    $check(in_array(APIController::TRANSLATION_AI_USER_TYPES_CONFIG, SettingsController::AI_CONFIG_KEYS, true), 'v4 la opción la guarda Configuración › IA');
    echoTerminal(' ');

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'b0 hay conexión a la base')) {
        return ['success' => false, 'message' => 'Sin conexión a la base.'];
    }
    $prefijo = 'zz-prueba-traduccion-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $usuarios = [];
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');
    $fuenteVista = (string) file_get_contents(basepath('app/classes/Publications/Views/publications/forms/edit.php'));

    //La fila entera, en crudo: quien haya guardado Configuración › IA la tiene, y esta suite no depende de eso.
    $tablaOpciones = (new SettingsModel())->getModel()->getTable();
    $filaOpcion = function () use ($database, $tablaOpciones, $nombreOpcion): ?array {
        $consulta = $database->prepare("SELECT * FROM `{$tablaOpciones}` WHERE BINARY name = ?");
        $consulta->execute([$nombreOpcion]);
        $fila = $consulta->fetch(\PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    };
    $filaPrevia = $filaOpcion();

    try {
        $database->prepare("DELETE FROM `{$tablaOpciones}` WHERE BINARY name = ?")->execute([$nombreOpcion]);
        set_config($nombreOpcion, null);

        //──── 1. Las rutas, por tipo ───────────────────────────────────────────────────────────────
        echoTerminal('[1/3] Las dos rutas, para cada tipo: lo que comprueba index.php antes de llamar a translations()');
        $opcion = get_config($nombreOpcion);
        $check($filaOpcion() === null && !is_array($opcion) && $igual(APIController::translationUserTypes($opcion), $seis), 'r0 CANARIO: quitada la opción no queda fila ni valor, y sin ella se mide la de por omisión (los seis tipos)', json_encode($opcion) ?: '');

        foreach ($rutas as $ruta) {
            $check(is_array(get_route_info($ruta)) && (get_route_info($ruta)['require_login'] ?? false) === true, "r0 {$ruta} existe y pide sesión: sin ella, 403 antes de llegar al proveedor");
        }
        $roles = array_filter(Roles::getRoles(), fn ($rol): bool => is_array($rol) && array_key_exists((int) ($rol['code'] ?? -1), UsersModel::TYPES_USERS));
        $check(count($roles) === count(UsersModel::TYPES_USERS), 'r1 CANARIO: hay un rol por cada tipo de usuario', count($roles) . ' de ' . count(UsersModel::TYPES_USERS));
        foreach ($roles as $rol) {
            $tipo = (int) $rol['code'];
            $esperado = in_array($tipo, $conPermiso, true);
            foreach ($rutas as $ruta) {
                //index.php pregunta por el nombre del rol; routeName(), por el tipo. Los dos tienen que decir lo mismo.
                $porNombre = Roles::hasPermissions($ruta, $rol['name'], true);
                $porTipo = Roles::hasPermissions($ruta, $tipo, true);
                $check($porNombre === $esperado && $porTipo === $esperado, ($esperado ? 'r2' : 'r3') . " {$rol['name']} ({$tipo}) " . ($esperado ? 'sí' : 'NO') . " tiene {$ruta}",
                    'por nombre ' . var_export($porNombre, true) . ', por tipo ' . var_export($porTipo, true));
            }
        }
        echoTerminal(' ');

        //──── 2. El botón de publicaciones ─────────────────────────────────────────────────────────
        echoTerminal('[2/3] El botón «Traducir» y su URL: la condición de publications/forms/edit.php, con un usuario de cada lado');
        echoTerminal('   (la vista no se renderiza: pide una publicación real con su formulario entero; se evalúa su condición)');

        $crearUsuario = function (string $sufijo, int $tipo) use ($prefijo, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Traduccion';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            return (int) $u->id;
        };
        $comoUsuario = function (int $id): void {
            set_config('current_user', (object) ['id' => $id]);
            set_config('pcsphp_current_user_stored', null);
        };
        //La misma expresión que la vista.
        $urlDelBoton = fn (): string => APIController::allowedRoute('translations-actions') && APIController::currentUserCanTranslate() ? (string) APIController::routeName('translations-actions') : '';

        $check(str_contains($fuenteVista, "\$translationURL = APIController::allowedRoute('translations-actions') && APIController::currentUserCanTranslate() ? (string) APIController::routeName('translations-actions') : '';")
            && str_contains($fuenteVista, "&& \$translationURL !== ''):?>"), 'b1 CANARIO: la vista calcula $translationURL así y el botón lo exige: lo de abajo prueba su condición');

        $comoUsuario($crearUsuario('general', UsersModel::TYPE_USER_GENERAL));
        $check(($urlDelBoton() !== '') === in_array(UsersModel::TYPE_USER_GENERAL, $conPermiso, true), 'b2 un usuario general tiene la URL si la lista lo incluye, como la de por omisión: la vista pinta el botón', $urlDelBoton());
        $comoUsuario($crearUsuario('comunicaciones', UsersModel::TYPE_USER_COMUNICACIONES));
        $url = $urlDelBoton();
        $check(in_array(UsersModel::TYPE_USER_COMUNICACIONES, $conPermiso, true) ? ($url !== '' && str_contains($url, 'translations')) : $url === '', 'b3 para comunicaciones, si la lista lo incluye, la URL de la ruta corta: la vista pinta el botón y el JS la recibe', $url);

        //──── 3. La lista, al atender ──────────────────────────────────────────────────────────────
        echoTerminal('[3/3] La lista elegida: el rechazo de translations(), en proceso y sin proveedor');
        echoTerminal('   (una acción inexistente: quien pasa la lista cae en el 404, antes de llamar a nadie)');
        $atender = function (): string {
            $peticion = (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-prueba-traduccion'), new Headers(), [], [], (new StreamFactory())->createStream('')))
                ->withAttribute('actionType', 'zz-accion-inexistente')
                //Sin Slim delante no hay RouteContext: con 'route' presente, NotFoundException no lo pide.
                ->withAttribute('route', null);
            try {
                (new APIController())->translations($peticion, new ResponseRoute());
                return 'sin excepción';
            } catch (HttpForbiddenException $e) {
                return '403';
            } catch (NotFoundException $e) {
                return '404';
            }
        };
        $comunicaciones = $usuarios['comunicaciones'];
        $general = $usuarios['general'];
        set_config($nombreOpcion, [(string) UsersModel::TYPE_USER_GENERAL]);
        $comoUsuario($comunicaciones);
        $check(get_config($nombreOpcion) === [(string) UsersModel::TYPE_USER_GENERAL] && !APIController::currentUserCanTranslate(), 'l1 CANARIO: con una lista sin comunicaciones, la regla le dice que no');
        $resultado = $atender();
        $check($resultado === '403', 'l2 DISCRIMINANTE: comunicaciones, fuera de la lista, recibe 403 en la petición', $resultado);
        $check(Roles::hasPermissions($rutas[0], UsersModel::TYPE_USER_COMUNICACIONES, true), 'l3 y aun así conserva la ruta: quien la frena es la lista, no Roles');
        $url = $urlDelBoton();
        $check($url === '', 'l4 DISCRIMINANTE: fuera de la lista, la vista no recibe URL y no pinta el botón', $url);
        $comoUsuario($general);
        $resultado = $atender();
        $check($resultado === '404', 'l5 un general, en la lista, pasa la puerta y llega a la acción', $resultado);
        $check($urlDelBoton() !== '', 'l6 y su vista sí recibe la URL');
        set_config('current_user', null);
        set_config('pcsphp_current_user_stored', null);
        $check(!APIController::currentUserCanTranslate(), 'l7 sin sesión, la regla dice que no');
    } catch (\Throwable $e) {
        $check(false, 'e* el recorrido corre entero', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        set_config($nombreOpcion, $configPrevia === false ? null : $configPrevia);
        try {
            if ($filaPrevia !== null && $filaOpcion() === null) {
                $columnas = implode('`, `', array_keys($filaPrevia));
                $marcas = implode(', ', array_fill(0, count($filaPrevia), '?'));
                $database->prepare("INSERT INTO `{$tablaOpciones}` (`{$columnas}`) VALUES ({$marcas})")->execute(array_values($filaPrevia));
            }
        } catch (\Throwable $e) {
            $check(false, 'z2 la opción se repone', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $check($filaOpcion() === $filaPrevia, 'z2 la opción queda como estaba: con su fila y su valor, o sin ninguna');
        try {
            foreach ($usuarios as $id) {
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
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedan}");
    }
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:TranslationPermissions] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "La traducción con IA sigue a su lista de Configuración › IA ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_DATABASE])->register();
