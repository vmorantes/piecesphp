<?php

//Un usuario Eliminado deja de existir en la práctica (PO, 2026-10-16): no se lista, no se busca, no se elige y su perfil
//no existe; en el historial de ingresos sigue. Por HTTP y en proceso; siembra zz- y lo retira entero.

use DataImportExportUtility\Definitions\UsersExportDefinition;
use DataImportExportUtility\Examples\ExampleUsersReportExportDefinition;
use Directory\Controllers\AllProfilesController;
use Directory\Controllers\ProfileController;
use Directory\Geo\PersonsGeoSource;
use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\DataTransfer\Export\ExportContext;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Geo\GeoQuery;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\LoginAttemptsController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Util\SystemApprovalManager;

CliActions::make('unit-tests:core/deleted-user-gone', function ($args) {

    echoTerminal("\e[33m[TEST:DeletedUserGone] Un usuario Eliminado no se lista, no se busca, no se elige y su perfil no existe; en el historial de ingresos sigue\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que profile-visibility.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'banco: hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $path, string $jwt, ?array $post = null, array $query = []) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/') . (!empty($query) ? '?' . http_build_query($query) : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest', "{$cabeceraToken}: {$jwt}"],
        ];
        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => $body, 'json' => json_decode($body, true)];
    };

    $prefijo = 'zz-agente-borrado-' . bin2hex(random_bytes(3));
    $nombre = 'Zzborrado' . bin2hex(random_bytes(3));
    $ids = [];
    $organizacionID = null;
    $crear = function (string $sufijo, int $tipo, int $organizacion) use ($prefijo, $nombre, &$ids): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = $nombre;
        $u->secondname = '';
        $u->firstLastname = $sufijo;
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = $organizacion;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $ids[] = (int) $u->id;
        return (int) $u->id;
    };
    $encargadoDe = function (int $organizacion): ?int {
        $administrador = (new OrganizationMapper($organizacion))->administrator;
        return $administrador instanceof UsersModel ? ($administrador->id !== null ? (int) $administrador->id : null) : (is_int($administrador) ? $administrador : null);
    };
    $ponerEncargado = function (int $organizacion, int $usuario): void {
        $o = new OrganizationMapper($organizacion);
        $o->administrator = $usuario;
        $o->update();
    };
    $tabla = ['draw' => 1, 'start' => 0, 'length' => 5000, 'xhr' => 'yes'];
    $enDirectorio = function (string $jwt) use ($pedir, $camino, $nombre): bool {
        $parametros = ['draw' => 1, 'start' => 0, 'length' => 50, 'search' => ['value' => $nombre, 'regex' => 'false'], 'order' => [['column' => 0, 'dir' => 'asc']], 'columns' => []];
        for ($c = 0; $c < 6; $c++) {
            $parametros['columns'][] = ['data' => $c, 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']];
        }
        $r = $pedir($camino(AllProfilesController::routeName('datatables', [], true)), $jwt, null, $parametros);
        //Por el nombre completo: el arranque le crea un perfil vacío al encargado, que comparte el nombre de pila.
        return $r['status'] === 200 && str_contains($r['body'], "{$nombre} borrado");
    };
    //El mapa es el geovisor: los puntos de su fuente de personas, en todo el mundo y buscando el nombre.
    $enMapa = function () use ($nombre): bool {
        $puntos = (new PersonsGeoSource())->points(new GeoQuery(null, 0, [PersonsGeoSource::FILTER_SEARCH => $nombre]));
        return str_contains((string) json_encode($puntos->points(), JSON_UNESCAPED_UNICODE), $nombre);
    };
    $enFilas = function (iterable $filas, string $buscado): bool {
        foreach ($filas as $fila) {
            if (str_contains((string) json_encode($fila, JSON_UNESCAPED_UNICODE), $buscado)) {
                return true;
            }
        }
        return false;
    };

    try {
        $organizacion = new OrganizationMapper();
        $organizacion->name = "{$prefijo}-org";
        $organizacion->nit = "{$prefijo}-nit";
        $organizacion->folder = "{$prefijo}-org";
        $organizacion->status = OrganizationMapper::ACTIVE;
        $organizacion->save();
        $organizacionID = (int) $organizacion->id;
        $borrado = $crear('borrado', UsersModel::TYPE_USER_ADMIN_ORG, $organizacionID);
        $encargado = $crear('encargado', UsersModel::TYPE_USER_ADMIN_ORG, $organizacionID);
        $otro = $crear('otro', UsersModel::TYPE_USER_GENERAL, OrganizationMapper::INITIAL_ID_GLOBAL);
        $root = $crear('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL);
        $ponerEncargado($organizacionID, $encargado);
        $perfil = UserProfileMapper::createProfile($borrado);
        $m = UserProfileMapper::model();
        $m->resetAll();
        $m->update(['latitude' => 4.6, 'longitude' => -74.1, 'publicVisible' => 1])->where(['belongsTo' => $borrado])->execute();
        //El mapa solo enseña lo aprobado: las filas de aprobación se crean al arrancar, y la prueba ya arrancó.
        SystemApprovalManager::init();
        $jwt = ['otro' => UserSessions::issueToken($otro), 'root' => UserSessions::issueToken($root)];
        $check($perfil !== null && $organizacionID > 0, 'banco: la organización y el perfil del usuario existen', (string) json_encode($ids));
        $pagina = $camino(ProfileController::routeName('profile', ['userID' => $borrado], true));
        $usuarioBorrado = "{$prefijo}-borrado";
        $sinIngreso = $camino(LoginAttemptsController::routeName('reports-ajax', ['type' => 'not-logged'], true));
        $conIngreso = $camino(LoginAttemptsController::routeName('reports-ajax', ['type' => 'logged'], true));
        $cambiarEncargado = $camino(MyOrganizationProfileController::routeName('actions-change-administrator', ['organizationID' => $organizacionID], true));
        $editarOrganizacion = $camino(OrganizationsController::routeName('actions-edit', [], true));
        $edicion = fn (int $administrador): array => ['id' => $organizacionID, 'lang' => Config::get_default_lang(), 'name' => "{$prefijo}-org", 'administrator' => $administrador];
        $exportados = fn (): iterable => (new UsersExportDefinition(200))->rows(new ExportContext([], null));
        $informe = fn (): iterable => (new ExampleUsersReportExportDefinition(200))->rows(new ExportContext(['created' => null, 'types' => [], 'active' => null, 'search' => null], null));

        //─── a · Activo: todo lo ve (canarios) ────────────────────────────────────────────────────
        echoTerminal('[a] Activo: sale en cada sitio (CANARIOS)');
        $check($enDirectorio($jwt['root']), 'a1 sale en el Directorio');
        $check($enMapa(), 'a2 sale en el mapa');
        $r = $pedir($pagina, $jwt['otro']);
        $check($r['status'] === 200, 'a3 otro usuario ve su perfil', "HTTP {$r['status']}");
        $check(array_key_exists((string) $borrado, UsersModel::allOrganizationUsersCanBeAdminForSelect($organizacionID)), 'a4 se puede elegir como encargado (selector)');
        $check($enFilas(UsersController::_all(1, 5000, UsersModel::TYPE_USER_ADMIN_ORG)->elements(), $usuarioBorrado), 'a5 sale en users-ajax-all');
        $check(array_key_exists($borrado, getAllUsersForSelect()), 'a6 sale en getAllUsersForSelect()');
        $r = $pedir($sinIngreso, $jwt['root'], null, $tabla);
        $check($r['status'] === 200 && str_contains($r['body'], $usuarioBorrado), 'a7 sale en «Usuarios sin ingreso»', "HTTP {$r['status']}");
        $check($enFilas($exportados(), $usuarioBorrado), 'a8 sale en la exportación de usuarios');
        $check($enFilas($informe(), $usuarioBorrado), 'a9 sale en el informe de ejemplo');
        $r = $pedir($cambiarEncargado, $jwt['root'], ['newUserAdminID' => $borrado]);
        $check(($r['json']['success'] ?? false) === true && $encargadoDe($organizacionID) === $borrado, 'a10 se puede poner de encargado (Mi organización)', mb_substr($r['body'], 0, 200));
        $ponerEncargado($organizacionID, $encargado);
        $r = $pedir($editarOrganizacion, $jwt['root'], $edicion($borrado));
        $check(($r['json']['success'] ?? false) === true && $encargadoDe($organizacionID) === $borrado, 'a11 se puede poner de encargado (edición de la organización)', mb_substr($r['body'], 0, 200));
        $ponerEncargado($organizacionID, $encargado);

        //─── d · Eliminado: deja de existir en la práctica ────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[d] Eliminado');
        $u = UsersModel::model();
        $u->resetAll();
        $u->update(['status' => UsersModel::STATUS_USER_DELETED])->where(['id' => $borrado])->execute();
        $check(!$enDirectorio($jwt['root']), 'd1 no sale en el Directorio');
        $check(!$enMapa(), 'd2 no sale en el mapa');
        $r = $pedir($pagina, $jwt['otro']);
        $check($r['status'] === 404, 'd3 su perfil da 404 a otro usuario', "HTTP {$r['status']}");
        $r = $pedir($pagina, $jwt['root']);
        $check($r['status'] === 404, 'd4 y a root', "HTTP {$r['status']}");
        $check(!array_key_exists((string) $borrado, UsersModel::allOrganizationUsersCanBeAdminForSelect($organizacionID)), 'd5 no se ofrece como encargado (selector)');
        $check(!$enFilas(UsersController::_all(1, 5000, UsersModel::TYPE_USER_ADMIN_ORG)->elements(), $usuarioBorrado), 'd6 no sale en users-ajax-all');
        $check(!array_key_exists($borrado, getAllUsersForSelect()), 'd7 no sale en getAllUsersForSelect()');
        $r = $pedir($sinIngreso, $jwt['root'], null, $tabla);
        $check($r['status'] === 200 && !str_contains($r['body'], $usuarioBorrado), 'd8 no sale en «Usuarios sin ingreso»', "HTTP {$r['status']}");
        $check(!$enFilas($exportados(), $usuarioBorrado), 'd9 no sale en la exportación de usuarios');
        $check(!$enFilas($informe(), $usuarioBorrado), 'd10 no sale en el informe de ejemplo');
        $r = $pedir($cambiarEncargado, $jwt['root'], ['newUserAdminID' => $borrado]);
        $check(($r['json']['success'] ?? null) === false && $encargadoDe($organizacionID) === $encargado, 'd11 no se acepta como encargado (Mi organización), como un id que no existe', mb_substr($r['body'], 0, 200));
        $r = $pedir($editarOrganizacion, $jwt['root'], $edicion($borrado));
        $check(($r['json']['success'] ?? null) === false && $encargadoDe($organizacionID) === $encargado, 'd12 no se acepta como encargado (edición de la organización)', mb_substr($r['body'], 0, 200));

        //─── r · Por relación sigue: el historial de ingresos ─────────────────────────────────────
        echoTerminal('');
        echoTerminal('[r] Por relación: el historial de ingresos lo conserva');
        $database->prepare('INSERT INTO `' . LoginAttemptsModel::TABLE . '` (userID, usernameAttempt, success, ip, message, date) VALUES (?, ?, 1, ?, ?, NOW())')
            ->execute([$borrado, $usuarioBorrado, '127.0.0.1', 'zz']);
        $r = $pedir($conIngreso, $jwt['root'], null, $tabla);
        $check($r['status'] === 200 && str_contains($r['body'], $usuarioBorrado), 'r1 el eliminado sigue en «Usuarios con ingreso» (historial de actividad)', "HTTP {$r['status']}");
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        //Los usuarios dejan la organización de la prueba antes de borrar lo que apunta a ellos (la organización también).
        foreach ($ids as $id) {
            $database->prepare('UPDATE `' . UsersModel::TABLE . '` SET organization = ? WHERE id = ?')->execute([OrganizationMapper::INITIAL_ID_GLOBAL, $id]);
            $database->prepare('DELETE FROM `' . LoginAttemptsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
        }
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            foreach ($referencias as [$tablaReferencia, $columna]) {
                $database->prepare("DELETE FROM `{$tablaReferencia}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        if ($organizacionID !== null) {
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([OrganizationMapper::TABLE, (string) $organizacionID]);
            $database->prepare('DELETE FROM `' . OrganizationMapper::TABLE . '` WHERE id = ?')->execute([$organizacionID]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query('SELECT COUNT(*) FROM `' . OrganizationMapper::TABLE . '` WHERE name LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query('SELECT COUNT(*) FROM `' . LoginAttemptsModel::TABLE . '` WHERE usernameAttempt LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario, organización, perfil, ingreso ni aprobación de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Un usuario Eliminado no sale en el Directorio, el mapa, el selector de encargado, users-ajax-all, getAllUsersForSelect(), «Usuarios sin ingreso», la exportación ni el informe de ejemplo; no se acepta como encargado; su perfil da 404 a todos; y sigue en el historial de ingresos. Por HTTP y en proceso; siembra zz- y lo retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
