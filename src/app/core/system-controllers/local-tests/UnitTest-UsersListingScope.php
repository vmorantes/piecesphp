<?php

//users-ajax-all y el selector de usuarios no enseñan usuarios de otra organización a quien no puede con todas (zz-, retirados).
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use Organizations\OrganizationsRoutes;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/users-listing-scope', function ($args) {

    echoTerminal("\e[33m[TEST:UsersListingScope] users-ajax-all y users-search-dropdown, acotados como el listado\e[39m");
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
    $pedir = function (string $path, string $jwt, array $query) use ($base, $prefijoBase, $cabeceraToken): array {
        $path = $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/') . '?' . http_build_query($query),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        $json = json_decode($body, true);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => $body, 'json' => is_array($json) ? $json : []];
    };

    //Los ids que devuelve cada ruta, ordenados: se comparan fila a fila.
    $idsListado = function (string $jwt) use ($pedir, $camino): array {
        $r = $pedir($camino((string) UsersController::routeName('datatables', [], true)), $jwt, ['draw' => 1, 'start' => 0, 'length' => 5000]);
        $ids = array_map(fn ($fila): int => (int) ($fila[0] ?? 0), is_array($r['json']['data'] ?? null) ? $r['json']['data'] : []);
        sort($ids);
        return [$r['status'], $ids];
    };
    $idsTodos = function (string $jwt) use ($pedir, $camino): array {
        $r = $pedir($camino((string) UsersController::routeName('ajax-all', [], true)), $jwt, ['page' => 1, 'per_page' => 5000]);
        $ids = array_map(fn ($fila): int => (int) ($fila['id'] ?? 0), is_array($r['json']['elements'] ?? null) ? $r['json']['elements'] : []);
        sort($ids);
        return [$r['status'], $ids, mb_substr($r['body'], 0, 300)];
    };
    $idsSelector = function (string $jwt, ?string $buscar) use ($pedir, $camino): array {
        $r = $pedir($camino((string) UsersController::routeName('search-dropdown', [], true)), $jwt, $buscar !== null ? ['search' => $buscar] : []);
        $ids = array_map(fn ($fila): int => (int) ($fila['value'] ?? 0), is_array($r['json']['results'] ?? null) ? $r['json']['results'] : []);
        sort($ids);
        return [$r['status'], $ids];
    };

    //La limpieza barre la familia entera: también lo que dejara una corrida interrumpida.
    $familia = 'zz-prueba-users-scope-';
    $prefijo = $familia . bin2hex(random_bytes(3));
    $crearUsuario = function (string $sufijo, int $tipo, ?int $organizacion) use ($prefijo): UsersModel {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Alcance de usuarios';
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
        return new UsersModel((int) $u->id);
    };
    $crearOrganizacion = function (string $sufijo) use ($prefijo): int {
        $o = new OrganizationMapper();
        $o->name = "{$prefijo}-org-{$sufijo}";
        $o->nit = "{$prefijo}-nit-{$sufijo}";
        $o->folder = "{$prefijo}-{$sufijo}";
        $o->status = OrganizationMapper::ACTIVE;
        $o->activitySector = 'Sector de prueba';
        $o->informativeEmail = "{$prefijo}-org-{$sufijo}@example.com";
        $o->save();
        return (int) $o->id;
    };
    //Los criterios del listado de quien mira, sin petición: el controlador sin constructor y con su usuario puesto.
    $visible = new \ReflectionMethod(UsersController::class, 'visibleUsersWhereItems');
    $propia = new \ReflectionMethod(UsersController::class, 'ownOrganizationWhereItem');
    $criteriosDe = function (UsersModel $quien) use ($visible): array {
        $controlador = (new \ReflectionClass(UsersController::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(AdminPanelController::class, 'user'))->setValue($controlador, (object) ['id' => $quien->id]);
        return $visible->invoke($controlador);
    };
    $idsDe = function (array $filas): array {
        $ids = array_map(fn ($fila): int => (int) $fila->id, $filas);
        sort($ids);
        return $ids;
    };

    $interruptor = OrganizationsRoutes::swapEnabledForTesting(null);

    try {
        //──── Datos ───────────────────────────────────────────────────────────────────────────
        $orgA = $crearOrganizacion('a');
        $orgB = $crearOrganizacion('b');
        $editor3A = $crearUsuario('editor3-a', UsersModel::TYPE_USER_INSTITUCIONAL, $orgA);
        $editor4A = $crearUsuario('editor4-a', UsersModel::TYPE_USER_COMUNICACIONES, $orgA);
        $encargadoA = $crearUsuario('encargado-a', UsersModel::TYPE_USER_ADMIN_ORG, $orgA);
        $generalA = $crearUsuario('general-a', UsersModel::TYPE_USER_GENERAL, $orgA);
        $rootZ = $crearUsuario('root-a', UsersModel::TYPE_USER_ROOT, $orgA);
        $adminGralZ = $crearUsuario('admin-gral-a', UsersModel::TYPE_USER_ADMIN_GRAL, $orgA);
        $generalB = $crearUsuario('general-b', UsersModel::TYPE_USER_GENERAL, $orgB);
        $editor3B = $crearUsuario('editor3-b', UsersModel::TYPE_USER_INSTITUCIONAL, $orgB);
        $global = $crearUsuario('global', UsersModel::TYPE_USER_GENERAL, OrganizationMapper::INITIAL_ID_GLOBAL);
        $mapperA = new OrganizationMapper($orgA);
        $mapperA->administrator = (int) $encargadoA->id;
        $mapperA->update();
        $deA = array_map(fn (UsersModel $u): int => (int) $u->id, [$editor3A, $editor4A, $encargadoA, $generalA, $rootZ, $adminGralZ]);
        $deB = [(int) $generalB->id, (int) $editor3B->id];
        $deGlobal = [(int) $global->id];
        $masRango = [(int) $rootZ->id, (int) $adminGralZ->id];
        $check($editor3A->id !== null && $global->id !== null && (new OrganizationMapper($orgA))->administrator !== null, '0 CANARIO: usuarios y organizaciones de la prueba existen');

        //El universo de toda la base, para saber de qué organización es cada id que devuelva una ruta.
        $database = (new BaseModel())->getDatabase();
        $organizacionDe = [];
        foreach ($database->query('SELECT id, organization FROM `' . UsersModel::TABLE . '`')->fetchAll(\PDO::FETCH_ASSOC) as $fila) {
            $organizacionDe[(int) $fila['id']] = $fila['organization'] !== null ? (int) $fila['organization'] : null;
        }
        $reparto = function (array $ids) use ($deA, $deB, $deGlobal, $masRango, $organizacionDe, $orgA): string {
            $deOtras = count(array_filter($ids, fn (int $id): bool => ($organizacionDe[$id] ?? null) !== $orgA));
            return sprintf('total %d · zz de A %d · zz de B %d · zz de -10 %d · zz de más rango %d · de otra organización (toda la base) %d', count($ids), count(array_intersect($ids, $deA)), count(array_intersect($ids, $deB)), count(array_intersect($ids, $deGlobal)), count(array_intersect($ids, $masRango)), $deOtras);
        };

        //──── 1. Por HTTP, con el interruptor real ────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[1] Por HTTP (Organizaciones ' . (OrganizationsRoutes::ENABLE ? 'encendido' : 'apagado') . ' en la configuración)');
        if (!$check($base !== '' && (bool) OrganizationsRoutes::ENABLE, '1 CANARIO: hay base HTTP y el módulo está encendido en la configuración', $base)) {
            throw new \RuntimeException('Sin base HTTP o con el módulo apagado: lo de HTTP no mide nada.');
        }

        $restringidos = ['editor 3' => $editor3A, 'editor 4' => $editor4A, 'encargado 12' => $encargadoA];
        foreach ($restringidos as $nombre => $quien) {
            $jwt = UserSessions::issueToken((int) $quien->id);
            [$estadoListado, $listado] = $idsListado($jwt);
            [$estadoTodos, $todos] = $idsTodos($jwt);
            [$estadoSelector, $selector] = $idsSelector($jwt, $prefijo);
            [$estadoSinBuscar, $sinBuscar] = $idsSelector($jwt, null);
            echoTerminal("   · {$nombre} de A, listado: " . $reparto($listado));
            echoTerminal("   · {$nombre} de A, users-ajax-all: " . $reparto($todos));
            echoTerminal("   · {$nombre} de A, selector (buscando el prefijo): " . $reparto($selector));
            echoTerminal("   · {$nombre} de A, selector (sin buscar): " . $reparto($sinBuscar));
            $check($estadoListado === 200 && $estadoTodos === 200 && $todos === $listado && count($listado) > 0, "1a {$nombre} de A: users-ajax-all devuelve fila a fila lo mismo que su listado", "{$estadoTodos}/{$estadoListado}; " . count($todos) . ' contra ' . count($listado));
            $check(array_intersect($todos, array_merge($deB, $deGlobal, $masRango)) === [], "1b {$nombre} de A: users-ajax-all sin nadie de B, de -10 ni de más rango");
            $ajenosSelector = array_filter(array_merge($selector, $sinBuscar), fn (int $id): bool => ($organizacionDe[$id] ?? null) !== $orgA);
            $check($estadoSelector === 200 && $estadoSinBuscar === 200 && count($selector) > 0 && count($sinBuscar) > 0 && $ajenosSelector === [], "1c {$nombre} de A: el selector, buscando y sin buscar, solo trae usuarios de A", count($ajenosSelector) . ' ajenos');
        }

        foreach (['root' => $rootZ, 'administrador general' => $adminGralZ] as $nombre => $quien) {
            $jwt = UserSessions::issueToken((int) $quien->id);
            [$estadoTodos, $todos, $cuerpoTodos] = $idsTodos($jwt);
            [, $selector] = $idsSelector($jwt, $prefijo);
            echoTerminal("   · {$nombre}, users-ajax-all: " . $reparto($todos));
            echoTerminal("   · {$nombre}, selector (buscando el prefijo): " . $reparto($selector));
            $esperados = $idsDe(UsersController::_all(1, 5000)->elements());
            $check($estadoTodos === 200 && $todos === $esperados && array_diff(array_merge($deA, $deB, $deGlobal), $todos) === [], "1d {$nombre}: users-ajax-all sigue trayendo todos, de todas las organizaciones", count($todos) . ' contra ' . count($esperados) . "; {$cuerpoTodos}");
            $check(array_diff(array_merge($deA, $deB, $deGlobal), $selector) === [], "1e {$nombre}: el selector sigue trayendo a los de todas las organizaciones", count($selector) . ' filas');
        }

        //──── 2. En proceso, con el interruptor forzado ───────────────────────────────────────
        echoTerminal('');
        echoTerminal('[2] En proceso: encendido acota por organización; apagado, solo el rango (P384 a)');
        OrganizationsRoutes::swapEnabledForTesting(true);
        $encendido = $idsDe(UsersController::_all(1, 5000, null, [], $criteriosDe($editor3A))->elements());
        $check(array_intersect($encendido, array_merge($deB, $deGlobal, $masRango)) === [] && in_array((int) $editor4A->id, $encendido, true), '2a ENCENDIDO: _all() con los criterios del editor 3 trae a los de A de menor rango y a nadie de fuera');
        $check($propia->invoke(null, $editor3A) instanceof WhereItem, '2b ENCENDIDO: el selector recibe el criterio de su organización');
        OrganizationsRoutes::swapEnabledForTesting(false);
        $apagado = $idsDe(UsersController::_all(1, 5000, null, [], $criteriosDe($editor3A))->elements());
        $check(array_diff(array_merge($deB, $deGlobal), $apagado) === [] && array_intersect($apagado, $masRango) === [] && !in_array((int) $editor3A->id, $apagado, true), '2c APAGADO: la organización no recorta (salen B y -10), el rango sí (ni root ni el administrador general, ni él mismo)');
        $check($propia->invoke(null, $editor3A) === null, '2d APAGADO: el selector no recibe criterio de organización');
        OrganizationsRoutes::swapEnabledForTesting(null);
    } catch (\Throwable $e) {
        $check(false, 'sin excepciones', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        OrganizationsRoutes::swapEnabledForTesting($interruptor);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $quedanSesiones = null;
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
            $database = (new BaseModel())->getDatabase();
            $aprobaciones = SystemApprovalsMapper::model();
            $quedanSesiones = 0;
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
                $database->prepare('DELETE FROM `' . UserSessions::TABLE . '` WHERE userID = ?')->execute([$id]);
                $database->prepare('DELETE FROM `' . LoginAttemptsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
                $cuenta = $database->prepare('SELECT COUNT(*) FROM `' . UserSessions::TABLE . '` WHERE userID = ?');
                $cuenta->execute([$id]);
                $quedanSesiones += (int) $cuenta->fetchColumn();
            }
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
        $check(count((array) $usuarios->result()) === 0 && $quedanSesiones === 0, 'z1 no queda ningún usuario de la prueba ni sesión suya');
        $restos = OrganizationMapper::model();
        $restos->resetAll();
        $restos->select()->where(new WhereSegment([WhereItem::like('name', "{$familia}%")]))->execute();
        $check(count((array) $restos->result()) === 0, 'z2 no queda ninguna organización de la prueba');
        $check(OrganizationsRoutes::enabled() === (bool) OrganizationsRoutes::ENABLE, 'z3 el interruptor vuelve a ENABLE');
    }

    $total = $passed + $failed;
    echoTerminal(' ');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('users-ajax-all y users-search-dropdown con Organizaciones encendido: un editor 3 o 4 o un encargado 12 recibe en users-ajax-all fila a fila lo de su listado y en el selector solo usuarios de su organización; root y el administrador general, todos. En proceso, apagado, la organización no recorta y el rango sí. Por HTTP con el estado real; crea zz- y lo retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
