<?php

//El administrador de organización solo ve y exporta los accesos de la suya; el principal y el general, todo. Por HTTP.
//Siembra dos organizaciones, usuarios e intentos, todos zz-, y los retira.

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Controllers\LoginAttemptsController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/login-attempts-organization', function ($args) {

    echoTerminal("\e[33m[TEST:LoginAttemptsOrganization] El administrador de organización solo ve y exporta los accesos de la suya\e[39m");
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
    $camino = function (string $sufijo) use ($prefijoBase): string {
        $url = LoginAttemptsController::routeName($sufijo, [], true);
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    //El texto de todas las celdas de una exportación; null si no es un .xlsx legible.
    $celdas = function (string $xlsx): ?string {
        $archivo = tempnam(sys_get_temp_dir(), 'zz-xlsx-');
        if ($archivo === false) {
            return null;
        }
        try {
            if (file_put_contents($archivo, $xlsx) === false) {
                return null;
            }
            $hoja = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx')->load($archivo)->getActiveSheet()->toArray();
            return implode("\n", array_map(fn ($fila) => implode("\t", array_map('strval', $fila)), $hoja));
        } catch (\Throwable $e) {
            return null;
        } finally {
            //RETORNO-IGNORADO: un temporal propio de sys_get_temp_dir(); si quedara, no altera la prueba.
            @unlink($archivo);
        }
    };

    $prefijo = 'zz-prueba-accesos-' . bin2hex(random_bytes(3));
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $tablaIntentos = LoginAttemptsModel::TABLE;
    $tablaUsuarios = UsersModel::TABLE;
    $organizaciones = [];
    $usuarios = [];

    try {
        $crearUsuario = function (string $sufijo, int $tipo, int $organizacion) use ($prefijo, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            //Nadie entra con contraseña: el token lo fabrica la prueba.
            $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
            $u->firstname = "{$prefijo}-{$sufijo}";
            $u->secondname = '';
            $u->firstLastname = 'Zz';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = $organizacion;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            return (int) $u->id;
        };
        $root = SessionToken::generateToken(['id' => $crearUsuario('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL)], null, null, false);
        $general = SessionToken::generateToken(['id' => $crearUsuario('admin-general', UsersModel::TYPE_USER_ADMIN_GRAL, OrganizationMapper::INITIAL_ID_GLOBAL)], null, null, false);

        $ahora = date('Y-m-d H:i:s');
        foreach (['propia', 'ajena'] as $cual) {
            $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-{$cual}", 'zz-', "{$prefijo}-{$cual}", $ahora, $usuarios['root'], 1, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
            $organizaciones[$cual] = (int) $database->lastInsertId();
        }

        $adminOrg = SessionToken::generateToken(['id' => $crearUsuario('admin-org', UsersModel::TYPE_USER_ADMIN_ORG, $organizaciones['propia'])], null, null, false);
        foreach (['propia', 'ajena'] as $cual) {
            $conAcceso = $crearUsuario("{$cual}-entro", UsersModel::TYPE_USER_GENERAL, $organizaciones[$cual]);
            $crearUsuario("{$cual}-no-entro", UsersModel::TYPE_USER_GENERAL, $organizaciones[$cual]);
            $database->prepare("INSERT INTO `{$tablaIntentos}` (userID, usernameAttempt, success, ip, message, date) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$conAcceso, "{$prefijo}-{$cual}-entro", LoginAttemptsModel::SUCCESS_ATTEMPT, '0.0.0.0', 'zz-', $ahora]);
        }

        //Lo que el administrador de la propia debe contar: lo de su organización y los intentos sin usuario (P101).
        $esperadoIntentos = (int) $database->query("SELECT COUNT(*) FROM `{$tablaIntentos}` la LEFT JOIN `{$tablaUsuarios}` u ON u.id = la.userID"
            . " WHERE (la.userID IS NULL AND u.organization IS NULL) OR u.organization = {$organizaciones['propia']}")->fetchColumn();
        $esperadoUsuarios = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE organization = {$organizaciones['propia']}")->fetchColumn();
        $conIngreso = "SELECT la.userID FROM `{$tablaIntentos}` la WHERE la.userID IS NOT NULL AND la.success = " . LoginAttemptsModel::SUCCESS_ATTEMPT;
        $esperadoIngreso = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE organization = {$organizaciones['propia']} AND id IN ({$conIngreso})")->fetchColumn();
        $esperadoSinIngreso = $esperadoUsuarios - $esperadoIngreso;
        $todosIntentos = (int) $database->query("SELECT COUNT(*) FROM `{$tablaIntentos}`")->fetchColumn();
        $todosIngreso = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE id IN ({$conIngreso})")->fetchColumn();
        $todosSinIngreso = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}`")->fetchColumn() - $todosIngreso;

        echoTerminal('[a] El resumen (sin XHR): las cifras son de la propia');
        $resumenIntentos = $pedir($camino('reports') . '?attempts=yes', $adminOrg);
        $hayTotal = preg_match('/<span class="value">\s*(\d+)\s*<\/span>/', $resumenIntentos['body'], $total) === 1;
        $check($resumenIntentos['status'] === 200 && $hayTotal && $total[1] === (string) $esperadoIntentos, "a1 el total de intentos es el de la propia ({$esperadoIntentos})", "HTTP {$resumenIntentos['status']}, total " . ($total[1] ?? '¿?'));
        $resumenIngreso = $pedir($camino('reports') . '?logged=yes', $adminOrg);
        $hayTotalUsuarios = preg_match('/const totalUsers = `(\d+)`/', $resumenIngreso['body'], $totalUsuarios) === 1;
        $check($resumenIngreso['status'] === 200 && $hayTotalUsuarios && $totalUsuarios[1] === (string) $esperadoUsuarios, "a2 el total de usuarios es el de la propia ({$esperadoUsuarios})", "HTTP {$resumenIngreso['status']}, total " . ($totalUsuarios[1] ?? '¿?'));

        $exportaciones = [
            'export-attempts' => ['propia' => "{$prefijo}-propia-entro", 'ajena' => "{$prefijo}-ajena-entro", 'filas' => $esperadoIntentos, 'todas' => $todosIntentos],
            'export-logged' => ['propia' => "{$prefijo}-propia-entro", 'ajena' => "{$prefijo}-ajena-entro", 'filas' => $esperadoIngreso, 'todas' => $todosIngreso],
            'export-not-logged' => ['propia' => "{$prefijo}-propia-no-entro", 'ajena' => "{$prefijo}-ajena-no-entro", 'filas' => $esperadoSinIngreso, 'todas' => $todosSinIngreso],
        ];
        foreach (array_combine(['b', 'c', 'd'], array_keys($exportaciones)) as $letra => $sufijo) {
            $buscar = $exportaciones[$sufijo];
            echoTerminal('');
            echoTerminal("[{$letra}] La exportación {$sufijo}");
            $deAdmin = $pedir($camino($sufijo), $adminOrg);
            $textoAdmin = $deAdmin['status'] === 200 ? $celdas($deAdmin['body']) : null;
            $check($textoAdmin !== null, "{$letra}1 el administrador de organización recibe un .xlsx legible", "HTTP {$deAdmin['status']}");
            $check($textoAdmin !== null && str_contains($textoAdmin, $buscar['propia']), "{$letra}2 CANARIO: trae lo de su organización");
            $check($textoAdmin !== null && !str_contains($textoAdmin, $buscar['ajena']), "{$letra}3 y NO trae lo de la otra organización");
            //Menos la cabecera. Cuenta también lo que no sembró la prueba: lo ajeno que ya hubiera en la base.
            $filas = $textoAdmin !== null ? substr_count($textoAdmin, "\n") : -1;
            $check($filas === $buscar['filas'], "{$letra}4 trae exactamente las filas de la propia ({$buscar['filas']})", "filas {$filas}, ajenas " . ($filas - $buscar['filas']));
            $dePrincipal = $pedir($camino($sufijo), $root);
            $textoPrincipal = $dePrincipal['status'] === 200 ? $celdas($dePrincipal['body']) : null;
            $check($textoPrincipal !== null && str_contains($textoPrincipal, $buscar['propia']) && str_contains($textoPrincipal, $buscar['ajena']), "{$letra}5 CANARIO: el principal ve las dos organizaciones", "HTTP {$dePrincipal['status']}");
            $filasPrincipal = $textoPrincipal !== null ? substr_count($textoPrincipal, "\n") : -1;
            $check($filasPrincipal === $buscar['todas'], "{$letra}6 CANARIO: y todas las filas de la base ({$buscar['todas']})", "filas {$filasPrincipal}");
            $deGeneral = $pedir($camino($sufijo), $general);
            $textoGeneral = $deGeneral['status'] === 200 ? $celdas($deGeneral['body']) : null;
            $check($textoGeneral !== null && str_contains($textoGeneral, $buscar['propia']) && str_contains($textoGeneral, $buscar['ajena']), "{$letra}7 CANARIO: el administrador general también ve las dos", "HTTP {$deGeneral['status']}");
        }
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $database->exec("DELETE FROM `{$tablaIntentos}` WHERE usernameAttempt LIKE " . $database->quote("{$prefijo}%"));
            foreach ($usuarios as $id) {
                $database->exec("DELETE FROM `{$tablaIntentos}` WHERE userID = " . (int) $id);
            }
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ([[$tablaOrganizaciones, $organizaciones], [$tablaUsuarios, $usuarios]] as [$tablaReferencia, $ids]) {
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
            //Las claves van en los dos sentidos (el usuario a su organización, la organización a su creador): los usuarios
            //pasan a la global, caen las organizaciones y después los usuarios.
            $database->exec("UPDATE `{$tablaUsuarios}` SET organization = " . OrganizationMapper::INITIAL_ID_GLOBAL . ' WHERE username LIKE ' . $database->quote("{$prefijo}%"));
            foreach ($organizaciones as $id) {
                $database->exec("DELETE FROM `{$tablaOrganizaciones}` WHERE id = " . (int) $id);
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedanIntentos = (int) $database->query("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE usernameAttempt LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanOrganizaciones = (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanUsuarios = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanAprobaciones = 0;
        foreach ([[$tablaOrganizaciones, $organizaciones], [$tablaUsuarios, $usuarios]] as [$tablaReferencia, $ids]) {
            foreach ($ids as $id) {
                $quedanAprobaciones += (int) $database->query('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ' . $database->quote($tablaReferencia) . ' AND referenceValue = ' . $database->quote((string) $id))->fetchColumn();
            }
        }
        $check($quedanIntentos + $quedanOrganizaciones + $quedanUsuarios + $quedanAprobaciones === 0, 'z1 no queda ningún intento, organización, usuario ni aprobación de la prueba', "intentos {$quedanIntentos}, organizaciones {$quedanOrganizaciones}, usuarios {$quedanUsuarios}, aprobaciones {$quedanAprobaciones}");
    }

    return $balance();

})->setDescription('El administrador de organización solo ve y exporta los accesos de la suya: el resumen y las tres exportaciones de intentos de acceso; el principal y el administrador general los ven todos. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
