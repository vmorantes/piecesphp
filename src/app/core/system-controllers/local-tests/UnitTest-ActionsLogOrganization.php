<?php

//El registro de acciones: el de organización ve solo las de la suya, ni por id; las de root, solo root (P133).
//Crea una organización y tres usuarios zz-agente-acciones-*, y dos acciones; borra todo.
//@areas src/app/classes/EventsLog/
//@areas src/app/classes/Organizations/
//@areas src/app/classes/SystemApprovals/

use EventsLog\Mappers\LogsMapper;
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

CliActions::make('unit-tests:core/actions-log-organization', function ($args) {

    echoTerminal("\e[33m[TEST:ActionsLogOrganization] El registro de acciones, recortado a la organización de quien mira\e[39m");
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
    if (!$check($base !== '' && $database !== null, 'c1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $ruta = function (string $nombre, array $parametros = []): string {
        $url = get_route($nombre, $parametros, true);
        if (!is_string($url) || $url === '') {
            return '';
        }
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $pantalla = $ruta('actions-logs-admin-list');
    $datos = $ruta('actions-logs-admin-datatables');
    $check($pantalla !== '' && $datos !== '' && $ruta('actions-logs-admin-detail', ['id' => 1]) !== '', 'c2 existen la pantalla, los datos y el detalle', "{$pantalla} · {$datos}");
    if ($failed > 0) {
        return $balance();
    }

    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
    };
    $token = fn(int $id): string => UserSessions::issueToken($id);

    $prefijo = 'zz-agente-acciones-' . bin2hex(random_bytes(3));
    $marca = 'zz-acciones-' . bin2hex(random_bytes(4));
    $tablaUsuarios = UsersModel::TABLE;
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $tablaAcciones = LogsMapper::TABLE;
    $usuarios = [];
    $organizacion = 0;
    $crear = function (string $sufijo, int $tipo, int $org) use ($prefijo, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: los tokens los fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Agente';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = $org;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };
    $plantar = function (int $createdBy, string $texto) use ($database, $tablaAcciones): int {
        $database->prepare("INSERT INTO `{$tablaAcciones}` (textMessage, textMessageVariables, createdBy, createdAt, meta) VALUES (?, ?, ?, ?, ?)")
            ->execute([LogsMapper::MESSAGES[LogsMapper::MSG_GENERIC], json_encode(['%message%' => $texto]), $createdBy, date('Y-m-d H:i:s'), '{}']);
        return (int) $database->lastInsertId();
    };

    try {

        $idRoot = $crear('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL);
        $idGeneral = $crear('admin-general', UsersModel::TYPE_USER_ADMIN_GRAL, OrganizationMapper::INITIAL_ID_GLOBAL);
        $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-propia", 'zz-', "{$prefijo}-propia", date('Y-m-d H:i:s'), $idRoot, 1, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
        $organizacion = (int) $database->lastInsertId();
        $idOrg = $crear('admin-org', UsersModel::TYPE_USER_ADMIN_ORG, $organizacion);
        $root = $token($idRoot);
        $general = $token($idGeneral);
        $org = $token($idOrg);
        $propia = $plantar($idOrg, "{$marca}-propia");
        $ajena = $plantar($idRoot, "{$marca}-ajena");
        $check($idRoot > 0 && $idOrg > 0 && $organizacion > 0 && $propia > 0 && $ajena > 0, 'banco: una organización, sus usuarios y una acción de dentro y otra de fuera');

        $listado = fn(string $jwt, string $filtros = ''): array => $pedir("{$datos}?draw=1&start=0&length=200{$filtros}", $jwt);

        //─── a · El listado ───────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[a] El listado: cada uno, lo suyo');
        $deRoot = $listado($root);
        $check($deRoot['status'] === 200 && str_contains($deRoot['body'], "{$marca}-propia") && str_contains($deRoot['body'], "{$marca}-ajena"), 'a1 CANARIO: el principal ve las dos', "HTTP {$deRoot['status']}");
        $deGeneral = $listado($general);
        //P133: el general ve la del de organización (rango menor) y NO la de root.
        $check($deGeneral['status'] === 200 && str_contains($deGeneral['body'], "{$marca}-propia") && !str_contains($deGeneral['body'], "{$marca}-ajena"), 'a2 el administrador general ve la del de organización y no la de root', "HTTP {$deGeneral['status']}");
        $deOrg = $listado($org);
        $check($deOrg['status'] === 200 && str_contains($deOrg['body'], "{$marca}-propia"), 'a3 el administrador de organización ve la de su organización', "HTTP {$deOrg['status']}");
        $check(!str_contains($deOrg['body'], "{$marca}-ajena"), 'a4 y NO la de otra organización');

        //─── b · Por id ───────────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] El detalle, pidiéndolo por id');
        $detalle = fn(int $id, string $jwt): array => $pedir($ruta('actions-logs-admin-detail', ['id' => $id]), $jwt);
        $propiaOrg = $detalle($propia, $org);
        $check($propiaOrg['status'] === 200 && ($propiaOrg['json']['message'] ?? '') === "{$marca}-propia", 'b1 CANARIO: el de organización abre el detalle de la suya', "HTTP {$propiaOrg['status']}");
        $ajenaOrg = $detalle($ajena, $org);
        $check($ajenaOrg['status'] === 404 && !str_contains($ajenaOrg['body'], $marca), 'b2 la de otra organización da 404 y no trae nada', "HTTP {$ajenaOrg['status']}");
        $inexistente = $detalle(999999999, $org);
        $check($inexistente['status'] === 404 && ($inexistente['json']['error'] ?? null) === ($ajenaOrg['json']['error'] ?? false), 'b3 y responde igual que una que no existe: no se delata cuáles hay');
        $ajenaRoot = $detalle($ajena, $root);
        $check($ajenaRoot['status'] === 200 && ($ajenaRoot['json']['message'] ?? '') === "{$marca}-ajena", 'b4 CANARIO: el principal la abre');

        //─── c · Filtros ──────────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] Los filtros, por marcador');
        $porUsuario = $listado($root, '&' . http_build_query(['user' => "{$prefijo}-admin-org"]));
        $check(str_contains($porUsuario['body'], "{$marca}-propia") && !str_contains($porUsuario['body'], "{$marca}-ajena"), 'c3 por usuario: solo la suya');
        $manana = (new \DateTime('+1 day'))->format('d-m-Y');
        $desdeManana = $listado($root, '&' . http_build_query(['from' => $manana]));
        $check($desdeManana['status'] === 200 && !str_contains($desdeManana['body'], $marca), 'c4 desde mañana: ninguna');
        $hoy = date('d-m-Y');
        $deHoy = $listado($root, '&' . http_build_query(['from' => $hoy, 'to' => $hoy, 'type' => LogsMapper::MSG_GENERIC]));
        $check(str_contains($deHoy['body'], "{$marca}-propia") && str_contains($deHoy['body'], "{$marca}-ajena"), 'c5 de hoy y de su tipo: las dos');
        $otroTipo = $listado($root, '&' . http_build_query(['type' => LogsMapper::MSG_UPDATE_PROFILE]));
        $check($otroTipo['status'] === 200 && !str_contains($otroTipo['body'], $marca), 'c6 de otro tipo: ninguna');
        $inyectado = $listado($root, '&' . http_build_query(['user' => "' OR 1=1 -- ", 'type' => "x' OR '1'='1"]));
        $check($inyectado['status'] === 200 && !str_contains($inyectado['body'], $marca), 'c7 un filtro con comillas no abre nada: va por marcador');

        //─── d · La pantalla ──────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] La pantalla dice el alcance');
        $pantallaOrg = $pedir($pantalla, $org);
        $check($pantallaOrg['status'] === 200 && str_contains($pantallaOrg['body'], 'data-actions-log-scope'), 'd1 al administrador de organización le dice que ve solo lo de su organización', "HTTP {$pantallaOrg['status']}");
        $pantallaRoot = $pedir($pantalla, $root);
        $check($pantallaRoot['status'] === 200 && !str_contains($pantallaRoot['body'], 'data-actions-log-scope'), 'd2 al principal, nada');

    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $database->prepare("DELETE FROM `{$tablaAcciones}` WHERE textMessageVariables LIKE ?")->execute(["%{$marca}%"]);
            $placeholders = $usuarios !== [] ? implode(',', array_fill(0, count($usuarios), '?')) : null;
            if ($placeholders !== null) {
                //Lo que la aplicación anotara a nombre de los usuarios de la prueba.
                $database->prepare("DELETE FROM `{$tablaAcciones}` WHERE createdBy IN ({$placeholders})")->execute(array_values($usuarios));
            }
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ([[$tablaOrganizaciones, $organizacion > 0 ? [$organizacion] : []], [$tablaUsuarios, array_values($usuarios)]] as [$tablaReferencia, $ids]) {
                foreach ($ids as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaReferencia),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                }
            }
            foreach ($usuarios as $id) {
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            //El usuario apunta a su organización y la organización a su creador: los usuarios, a la global; luego cae la
            //organización y al final los usuarios.
            $database->prepare("UPDATE `{$tablaUsuarios}` SET organization = ? WHERE username LIKE ?")->execute([OrganizationMapper::INITIAL_ID_GLOBAL, "{$prefijo}%"]);
            if ($organizacion > 0) {
                $database->prepare("DELETE FROM `{$tablaOrganizaciones}` WHERE id = ?")->execute([$organizacion]);
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $cuenta = function (string $sql, array $valores) use ($database): int {
            $consulta = $database->prepare($sql);
            $consulta->execute($valores);
            return (int) $consulta->fetchColumn();
        };
        $quedan = $cuenta("SELECT COUNT(*) FROM `{$tablaAcciones}` WHERE textMessageVariables LIKE ?", ["%{$marca}%"])
             + $cuenta("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE ?", ["{$prefijo}%"])
             + $cuenta("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE ?", ["{$prefijo}%"]);
        $check($quedan === 0, 'z1 no queda ninguna acción, organización ni usuario de la prueba', (string) $quedan);
    }

    return $balance();

})->setDescription('El registro de acciones por HTTP: el principal ve todo; el administrador general, todo menos lo de root (P133); el administrador de organización, solo las acciones de los usuarios de la suya, también al pedir el detalle por id (404 igual que una inexistente); los filtros de fecha, tipo y usuario van por marcador. Crea y borra una organización, tres usuarios y dos acciones.')->setEffects([CliActions::EFFECT_DATABASE])->register();
