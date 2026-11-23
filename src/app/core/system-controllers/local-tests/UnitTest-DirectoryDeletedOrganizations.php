<?php

//El Directorio no lista una organización eliminada (P380), con ningún filtro ni para ningún rol que lo vea.
//Por HTTP. Crea usuarios y organizaciones zz-, y los retira.
//@areas src/app/classes/Directory/
//@requires-module Directory\DirectoryRoutes
//@requires-module Organizations\OrganizationsRoutes
//@requires-module SystemApprovals\SystemApprovalsRoutes

use Directory\Controllers\AllProfilesController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/directory-deleted-organizations', function ($args) {

    echoTerminal("\e[33m[TEST:DirectoryDeletedOrganizations] El Directorio no lista una organización eliminada\e[39m");
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
    $url = AllProfilesController::routeName('datatables', [], true);
    $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
    $path = $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    $pedir = function (string $jwt, array $consulta) use ($base, $path, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/') . '?' . http_build_query($consulta),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    //Las mismas formas que pide la pantalla: búsqueda libre y orden por las dos columnas ordenables.
    $consulta = function (string $busqueda, ?int $columna, string $sentido): array {
        $parametros = ['draw' => 1, 'start' => 0, 'length' => 100, 'search' => ['value' => $busqueda, 'regex' => 'false'], 'columns' => []];
        for ($c = 0; $c < 3; $c++) {
            $parametros['columns'][] = ['data' => $c, 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']];
        }
        if ($columna !== null) {
            $parametros['order'] = [['column' => $columna, 'dir' => $sentido]];
        }
        return $parametros;
    };

    $prefijo = 'zz-prueba-dir-org-' . bin2hex(random_bytes(3));
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $tablaAprobaciones = SystemApprovalsMapper::TABLE;
    $usuarios = [];
    $organizaciones = [];

    try {
        $crearUsuario = function (string $sufijo, int $tipo) use ($prefijo, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            //Nadie entra con contraseña: el token lo fabrica la prueba.
            $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Directorio';
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
        $rootID = $crearUsuario('root', UsersModel::TYPE_USER_ROOT);
        $generalID = $crearUsuario('general', UsersModel::TYPE_USER_GENERAL);
        $roles = [
            'root' => UserSessions::issueToken($rootID),
            'general' => UserSessions::issueToken($generalID),
        ];

        //Visible y aprobada: lo único que la distingue de la otra es el estado 0.
        $crearOrganizacion = function (string $sufijo) use ($prefijo, $database, $tablaOrganizaciones, $tablaAprobaciones, $rootID, &$organizaciones): int {
            $o = new OrganizationMapper();
            $o->name = "{$prefijo}-{$sufijo}";
            $o->nit = "{$prefijo}-nit-{$sufijo}";
            $o->folder = "{$prefijo}-{$sufijo}";
            $o->status = OrganizationMapper::ACTIVE;
            $o->activitySector = 'Sector de prueba';
            $o->informativeEmail = "{$prefijo}-{$sufijo}@example.com";
            $o->save();
            $id = (int) $o->id;
            $organizaciones[$sufijo] = $id;
            $database->prepare("UPDATE `{$tablaOrganizaciones}` SET publicVisible = 1 WHERE id = ?")->execute([$id]);
            $hay = $database->prepare("SELECT COUNT(*) FROM `{$tablaAprobaciones}` WHERE referenceTable = ? AND referenceValue = ?");
            $hay->execute([$tablaOrganizaciones, (string) $id]);
            if ((int) $hay->fetchColumn() > 0) {
                $database->prepare("UPDATE `{$tablaAprobaciones}` SET status = ? WHERE referenceTable = ? AND referenceValue = ?")
                    ->execute([SystemApprovalsMapper::STATUS_APPROVED, $tablaOrganizaciones, (string) $id]);
            } else {
                $database->prepare("INSERT INTO `{$tablaAprobaciones}` (referenceAlias, referenceValue, referenceTable, referenceDate, createdAt, createdBy, status) VALUES (?, ?, ?, ?, NOW(), ?, ?)")
                    ->execute(['zz', (string) $id, $tablaOrganizaciones, date('Y-m-d H:i:s'), $rootID, SystemApprovalsMapper::STATUS_APPROVED]);
            }
            return $id;
        };
        $visible = $crearOrganizacion('visible');
        $eliminada = $crearOrganizacion('eliminada');
        $database->prepare("UPDATE `{$tablaOrganizaciones}` SET status = ? WHERE id = ?")->execute([OrganizationMapper::DELETED, $eliminada]);
        $estado = $database->prepare("SELECT status FROM `{$tablaOrganizaciones}` WHERE id = ?");
        $estado->execute([$eliminada]);
        $check((int) $estado->fetchColumn() === OrganizationMapper::DELETED, 'p2 la segunda organización sembrada queda en DELETED (0)');

        $nombreVisible = "{$prefijo}-visible";
        $nombreEliminada = "{$prefijo}-eliminada";
        $filtros = [
            'sin búsqueda ni orden' => $consulta('', null, 'asc'),
            'búsqueda por prefijo' => $consulta($prefijo, null, 'asc'),
            'búsqueda por el nombre exacto de la eliminada' => $consulta($nombreEliminada, null, 'asc'),
            'búsqueda por prefijo, orden nombre asc' => $consulta($prefijo, 0, 'asc'),
            'búsqueda por prefijo, orden nombre desc' => $consulta($prefijo, 0, 'desc'),
            'búsqueda por prefijo, orden ubicación asc' => $consulta($prefijo, 1, 'asc'),
        ];

        foreach ($roles as $rol => $jwt) {
            echoTerminal('');
            echoTerminal("[{$rol}] all-profiles-admin-datatables");
            foreach ($filtros as $nombreFiltro => $parametros) {
                $r = $pedir($jwt, $parametros);
                $datos = json_decode($r['body'], true);
                $valido = $r['status'] === 200 && is_array($datos) && array_key_exists('data', $datos);
                $esperaVisible = $nombreFiltro !== 'búsqueda por el nombre exacto de la eliminada';
                echoTerminal("   · medida: {$nombreFiltro}: HTTP {$r['status']}, visible " . (str_contains($r['body'], $nombreVisible) ? 'sí' : 'no') . ', eliminada ' . (str_contains($r['body'], $nombreEliminada) ? 'sí' : 'no'));
                if ($esperaVisible) {
                    $check($valido && str_contains($r['body'], $nombreVisible), "{$rol} CANARIO: con {$nombreFiltro}, la organización visible sale", "HTTP {$r['status']}");
                }
                $check($valido && !str_contains($r['body'], $nombreEliminada), "{$rol} DISCRIMINANTE: con {$nombreFiltro}, la eliminada no sale", "HTTP {$r['status']}");
            }
        }
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            foreach ($usuarios as $id) {
                $aprobaciones = SystemApprovalsMapper::model();
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
            //Las aprobaciones de las organizaciones tienen `createdBy` con clave foránea al usuario: se retiran antes.
            foreach ($organizaciones as $id) {
                $database->prepare("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = ? AND referenceValue = ?")->execute([$tablaOrganizaciones, (string) $id]);
            }
            $soltar = OrganizationMapper::model();
            $soltar->resetAll();
            //RETORNO-IGNORADO: si no soltara, el borrado de usuarios lanzaría y lo diría z0.
            $soltar->update(['modifiedBy' => null])->where(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
            $modeloOrganizaciones = OrganizationMapper::model();
            $modeloOrganizaciones->resetAll();
            //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
            $modeloOrganizaciones->delete(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedanUsuarios = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedanUsuarios === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedanUsuarios}");
        $quedanOrganizaciones = (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijo}%") . ' OR nit LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanAprobaciones = 0;
        foreach ($organizaciones as $id) {
            $cuenta = $database->prepare("SELECT COUNT(*) FROM `{$tablaAprobaciones}` WHERE referenceTable = ? AND referenceValue = ?");
            $cuenta->execute([$tablaOrganizaciones, (string) $id]);
            $quedanAprobaciones += (int) $cuenta->fetchColumn();
        }
        $check($quedanOrganizaciones === 0 && $quedanAprobaciones === 0, 'z2 no queda ninguna organización ni aprobación de la prueba', "organizaciones {$quedanOrganizaciones}, aprobaciones {$quedanAprobaciones}");
    }

    return $balance();

})->setDescription('El Directorio (all-profiles-admin-datatables) no lista una organización eliminada (status 0), con root ni con un usuario general, sin búsqueda, buscando y ordenando; la misma organización sin eliminar sí sale (P380). Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
