<?php

//El administrador de organización solo ve y exporta los accesos de la suya; el principal y el general, las dos. Por HTTP.
//Siembra dos organizaciones, usuarios e intentos, todos zz-, y los retira.
//@areas src/app/classes/Organizations/
//@areas src/app/classes/PiecesPHP/Logs/
//@areas src/app/classes/SystemApprovals/

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Logs\Controllers\LogsController;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
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
    $cabeceraToken = SessionToken::headerName();
    $camino = function (string $sufijo) use ($prefijoBase): string {
        $url = LoginAttemptsController::routeName($sufijo, [], true);
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
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
        $root = UserSessions::issueToken($crearUsuario('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL));
        $general = UserSessions::issueToken($crearUsuario('admin-general', UsersModel::TYPE_USER_ADMIN_GRAL, OrganizationMapper::INITIAL_ID_GLOBAL));

        $ahora = date('Y-m-d H:i:s');
        foreach (['propia', 'ajena'] as $cual) {
            $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-{$cual}", 'zz-', "{$prefijo}-{$cual}", $ahora, $usuarios['root'], 1, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
            $organizaciones[$cual] = (int) $database->lastInsertId();
        }

        $adminOrg = UserSessions::issueToken($crearUsuario('admin-org', UsersModel::TYPE_USER_ADMIN_ORG, $organizaciones['propia']));
        foreach (['propia', 'ajena'] as $cual) {
            $conAcceso = $crearUsuario("{$cual}-entro", UsersModel::TYPE_USER_GENERAL, $organizaciones[$cual]);
            $crearUsuario("{$cual}-no-entro", UsersModel::TYPE_USER_GENERAL, $organizaciones[$cual]);
            $database->prepare("INSERT INTO `{$tablaIntentos}` (userID, usernameAttempt, success, ip, message, date) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$conAcceso, "{$prefijo}-{$cual}-entro", LoginAttemptsModel::SUCCESS_ATTEMPT, '0.0.0.0', 'zz-', $ahora]);
        }

        //Lo que el administrador de la propia debe contar: lo de su organización y los intentos sin usuario (P101), en el
        //periodo por omisión de la pestaña (JGB: las cifras y la exportación de intentos son del periodo).
        $desde = $database->quote(LoginAttemptsModel::periodRange(LoginAttemptsModel::DEFAULT_PERIOD, new \DateTimeImmutable())[0]->format('Y-m-d H:i:s'));
        $esperadoIntentos = (int) $database->query("SELECT COUNT(*) FROM `{$tablaIntentos}` la LEFT JOIN `{$tablaUsuarios}` u ON u.id = la.userID"
            . " WHERE la.date >= {$desde} AND ((la.userID IS NULL AND u.organization IS NULL) OR u.organization = {$organizaciones['propia']})")->fetchColumn();
        $esperadoUsuarios = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE organization = {$organizaciones['propia']}")->fetchColumn();
        //Con ingreso EN EL PERIODO por omisión (JGD): las exportaciones de con y sin ingreso son del periodo.
        $conIngreso = "SELECT la.userID FROM `{$tablaIntentos}` la WHERE la.userID IS NOT NULL AND la.date >= {$desde} AND la.success = " . LoginAttemptsModel::SUCCESS_ATTEMPT;
        $esperadoIngreso = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE organization = {$organizaciones['propia']} AND id IN ({$conIngreso})")->fetchColumn();
        $esperadoSinIngreso = $esperadoUsuarios - $esperadoIngreso;
        $todosIntentos = (int) $database->query("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE date >= {$desde}")->fetchColumn();
        $todosIngreso = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE id IN ({$conIngreso})")->fetchColumn();
        //Sin el usuario de sistema: ningún listado lo enseña (B14).
        $todosSinIngreso = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE " . \PiecesPHP\UserSystem\ORM\UsersModel::excludeSystemUserSQL())->fetchColumn() - $todosIngreso;

        echoTerminal('[a] El resumen (sin XHR), en la pestaña «Accesos» de «Registros»: las cifras son de la propia');
        //La pantalla vieja ya solo redirige a la pestaña: se pide la pestaña, que es donde están las cifras.
        $registros = LogsController::routeName('index', [], true);
        $registros = str_contains($registros, '://') ? (string) parse_url($registros, \PHP_URL_PATH) : $registros;
        $registros = $prefijoBase !== '' && str_starts_with($registros, $prefijoBase) ? substr($registros, strlen($prefijoBase)) : $registros;
        $resumenIntentos = $pedir($registros . '?tab=access&view=attempts', $adminOrg);
        //Las cifras van en las piezas de estadísticas, por su data-type.
        $cifra = fn(string $html, string $tipo): ?string => preg_match('/data-type="' . $tipo . '"[^>]*>(?:&nbsp;)?\s*(\d+)/', $html, $m) === 1 ? $m[1] : null;
        $total = [1 => $cifra($resumenIntentos['body'], 'totalAttempts')];
        $hayTotal = $total[1] !== null;
        $check($resumenIntentos['status'] === 200 && $hayTotal && $total[1] === (string) $esperadoIntentos, "a1 el total de intentos es el de la propia ({$esperadoIntentos})", "HTTP {$resumenIntentos['status']}, total " . ($total[1] ?? '¿?'));
        $resumenIngreso = $pedir($registros . '?tab=access&view=logged', $adminOrg);
        $conIngresoPintados = $cifra($resumenIngreso['body'], 'loggedUsers');
        $sinIngresoPintados = $cifra($resumenIngreso['body'], 'notLoggedUsers');
        $hayTotalUsuarios = $conIngresoPintados !== null && $sinIngresoPintados !== null;
        $totalUsuarios = [1 => $hayTotalUsuarios ? (string) ((int) $conIngresoPintados + (int) $sinIngresoPintados) : null];
        $check($resumenIngreso['status'] === 200 && $hayTotalUsuarios && $totalUsuarios[1] === (string) $esperadoUsuarios, "a2 el total de usuarios es el de la propia ({$esperadoUsuarios})", "HTTP {$resumenIngreso['status']}, total " . ($totalUsuarios[1] ?? '¿?'));
        $check($conIngresoPintados === (string) $esperadoIngreso, "a2b y los que entraron, los de la propia ({$esperadoIngreso})", 'pintados ' . ($conIngresoPintados ?? '¿?'));

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

        //─── e · Accesos › Intentos del prototipo (JGB): periodo, cifras del rango, serie, filtro y exportación ──────
        echoTerminal('');
        echoTerminal('[e] Intentos: el periodo, las cifras de un rango, la serie con ceros, el filtro por resultado y la exportación');
        $ruta = function (string $sufijo, array $parametros = []) use ($prefijoBase): string {
            $url = LoginAttemptsController::routeName($sufijo, $parametros, true);
            $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
            return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
        };
        $masViejo = LoginAttemptsModel::oldestDate();
        echoTerminal('   INFO: el registro guarda hasta ' . LoginAttemptsModel::maxRows() . ' intentos; hay ' . LoginAttemptsModel::countsBySuccess((object) ['id' => $usuarios['root'], 'type' => UsersModel::TYPE_USER_ROOT, 'organization' => null])['total'] . ' y el más viejo es del ' . ($masViejo?->format('d-m-Y H:i') ?? 'ninguno'));
        $periodoActivo = fn(string $html): ?string => preg_match('/<div class="ui mini buttons" role="radiogroup"[^>]*data-access-periods>\s*(?:<a [^>]*>[^<]*<\/a>\s*)*?<a class="ui active button" role="radio" aria-checked="true"[^>]*data-access-period="(\d+)"/', $html, $m) === 1 ? $m[1] : null;
        $check($periodoActivo($pedir($registros . '?tab=access&view=attempts', $root)['body']) === '14', 'e1 DISCRIMINANTE: sin days, el periodo es de 14 días');
        $check($periodoActivo($pedir($registros . '?tab=access&view=attempts&days=13', $root)['body']) === '14' && $periodoActivo($pedir($registros . '?tab=access&view=attempts&days=30', $root)['body']) === '30', 'e2 DISCRIMINANTE: un days que no es de la lista (13) vuelve a 14; uno de la lista (30) se respeta');

        //Los deltas de lo plantado: la base tiene sus propios intentos.
        $actorRoot = (object) ['id' => $usuarios['root'], 'type' => UsersModel::TYPE_USER_ROOT, 'organization' => OrganizationMapper::INITIAL_ID_GLOBAL];
        $actorOrg = (object) ['id' => $usuarios['admin-org'], 'type' => UsersModel::TYPE_USER_ADMIN_ORG, 'organization' => $organizaciones['propia']];
        [$desdeHoy, $hastaHoy] = LoginAttemptsModel::periodRange(14, new \DateTimeImmutable());
        $antesRoot = LoginAttemptsModel::periodStats($desdeHoy, $hastaHoy, $actorRoot);
        $antesOrg = LoginAttemptsModel::periodStats($desdeHoy, $hastaHoy, $actorOrg);
        $hoy = date('Y-m-d H:i:s');
        $viejo = date('Y-m-d H:i:s', strtotime('-20 days'));
        $plantar = function (int $usuario, string $escrito, bool $exito, string $fecha, string $mensaje = '') use ($database, $tablaIntentos): void {
            $database->prepare("INSERT INTO `{$tablaIntentos}` (userID, usernameAttempt, success, ip, message, date) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$usuario, $escrito, $exito ? LoginAttemptsModel::SUCCESS_ATTEMPT : 0, '0.0.0.0', $mensaje, $fecha]);
        };
        $propio = $usuarios['propia-no-entro'];
        $plantar($propio, strtoupper("{$prefijo}-unico"), true, $hoy);
        $plantar($propio, "{$prefijo}-unico", true, $hoy);
        $plantar($propio, "{$prefijo}-otro", false, $hoy, 'zz-fallo');
        $plantar($propio, "{$prefijo}-viejo", true, $viejo);
        $plantar($usuarios['ajena-no-entro'], "{$prefijo}-ajeno", false, $hoy, 'zz-fallo');
        $despuesRoot = LoginAttemptsModel::periodStats($desdeHoy, $hastaHoy, $actorRoot);
        $despuesOrg = LoginAttemptsModel::periodStats($desdeHoy, $hastaHoy, $actorOrg);
        $deltas = fn(array $a, array $d): array => [$d['total'] - $a['total'], $d['success'] - $a['success'], $d['error'] - $a['error'], $d['uniqueUsers'] - $a['uniqueUsers']];
        $check($deltas($antesRoot, $despuesRoot) === [4, 2, 2, 3], 'e3 DISCRIMINANTE: el rango cuenta lo de hoy (2 exitosos y 2 fallidos) y no lo de hace 20 días; usuarios únicos +3 («ZZ-…-UNICO» y «zz-…-unico» son uno)', json_encode($deltas($antesRoot, $despuesRoot)) ?: '');
        $check($deltas($antesOrg, $despuesOrg) === [3, 2, 1, 2], 'e4 DISCRIMINANTE: el administrador de organización no cuenta el de la otra organización', json_encode($deltas($antesOrg, $despuesOrg)) ?: '');
        $diaHoy = date('Y-m-d');
        $check(($despuesRoot['days'][$diaHoy]['success'] ?? -1) - ($antesRoot['days'][$diaHoy]['success'] ?? 0) === 2 && ($despuesRoot['days'][$diaHoy]['error'] ?? -1) - ($antesRoot['days'][$diaHoy]['error'] ?? 0) === 2, 'e5 la serie: los de hoy, en el día de hoy');
        $vacio = LoginAttemptsModel::periodStats(new \DateTimeImmutable('2000-01-01'), new \DateTimeImmutable('2000-01-08'), $actorRoot);
        $check(array_keys($vacio['days']) === ['2000-01-01', '2000-01-02', '2000-01-03', '2000-01-04', '2000-01-05', '2000-01-06', '2000-01-07'] && array_sum(array_map(fn($d) => $d['success'] + $d['error'], $vacio['days'])) === 0 && $vacio['total'] === 0, 'e6 DISCRIMINANTE: un punto por día del rango, también los días sin intentos (en 0)');
        $rangoViejo = LoginAttemptsModel::countsBySuccess($actorRoot, new \DateTimeImmutable(date('Y-m-d', strtotime('-21 days'))), new \DateTimeImmutable(date('Y-m-d', strtotime('-19 days'))));
        $sinRango = LoginAttemptsModel::countsBySuccess($actorRoot);
        $check($rangoViejo['total'] >= 1 && $rangoViejo['total'] < $sinRango['total'] && $sinRango === LoginAttemptsModel::countsBySuccess($actorRoot, null, null), 'e7 DISCRIMINANTE: countsBySuccess con rango cuenta el de hace 20 días y menos que sin rango; sin rango, igual que siempre', $rangoViejo['total'] . ' de ' . $sinRango['total']);

        //La tabla: el filtro por resultado, la fecha relativa, «Ingreso exitoso» y la marca de la fila.
        $tabla = function (string $resultado) use ($pedir, $ruta, $root, $prefijo): array {
            $consulta = ['xhr' => 'yes', 'days' => '14', 'draw' => 1, 'start' => 0, 'length' => 100, 'search' => ['value' => $prefijo, 'regex' => 'false']] + ($resultado !== '' ? ['result' => $resultado] : []);
            $columnas = [];
            for ($i = 0; $i < 6; $i++) {
                $columnas[] = ['data' => $i, 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']];
            }
            $r = $pedir($ruta('reports-ajax', ['type' => 'attempts']) . '?' . http_build_query($consulta + ['columns' => $columnas]), $root);
            $datos = json_decode($r['body'], true);
            return is_array($datos['data'] ?? null) ? $datos['data'] : [];
        };
        $todas = $tabla('');
        $soloError = $tabla('error');
        $soloExito = $tabla('success');
        $marcas = fn(array $filas): array => array_values(array_unique(array_map(fn($f) => preg_match('/data-attempt-result="(\w+)"/', (string) ($f[0] ?? ''), $m) === 1 ? $m[1] : '?', $filas)));
        //Con el prefijo también salen los dos exitosos de hoy que la suite planta arriba (…-propia-entro, …-ajena-entro).
        $check(count($todas) === 6 && count($soloError) === 2 && $marcas($soloError) === ['error'] && count($soloExito) === 4 && $marcas($soloExito) === ['success'], 'e8 DISCRIMINANTE: result=error y result=success filtran la tabla en el servidor (6, 2 y 4, sin el de hace 20 días)', count($todas) . '/' . count($soloError) . '/' . count($soloExito));
        $exito = $soloExito[0] ?? [];
        $check(str_contains((string) ($exito[2] ?? ''), __(LOGIN_REPORT_LANG_GROUP, 'Ingreso exitoso')) && preg_match('/^<time datetime="\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}" title="\d{2}-\d{2}-\d{4} \d{2}:\d{2}">[^<]+<\/time>$/', (string) ($exito[5] ?? '')) === 1, 'e9 DISCRIMINANTE: un exitoso sin mensaje dice «Ingreso exitoso», y la fecha es relativa en <time> con la exacta en title', (string) ($exito[5] ?? ''));
        $check(str_contains((string) (($soloError[0] ?? [])[2] ?? ''), 'zz-fallo'), 'e10 un fallido conserva su mensaje');

        //La exportación, con el periodo y el resultado.
        $exportar = function (array $consulta) use ($pedir, $camino, $root, $celdas): string {
            $r = $pedir($camino('export-attempts') . '?' . http_build_query($consulta), $root);
            return $r['status'] === 200 ? (string) $celdas($r['body']) : '';
        };
        $siete = $exportar(['days' => '7']);
        $treinta = $exportar(['days' => '30']);
        $treintaError = $exportar(['days' => '30', 'result' => 'error']);
        $check(str_contains($siete, "{$prefijo}-unico") && !str_contains($siete, "{$prefijo}-viejo") && str_contains($treinta, "{$prefijo}-viejo"), 'e11 DISCRIMINANTE: exportar respeta el periodo (el de hace 20 días, en 30 y no en 7)');
        $check(str_contains($treintaError, "{$prefijo}-otro") && !str_contains($treintaError, "{$prefijo}-unico"), 'e12 DISCRIMINANTE: y el resultado activo');

        //Las tarjetas, pintadas en proceso: la variación, «Sin datos…», usuarios únicos y la pista del recorte.
        $pintar = function (int $total, int $anterior, ?\DateTimeImmutable $viejoGuardado): string {
            [$desde, $hasta] = LoginAttemptsModel::periodRange(14, new \DateTimeImmutable());
            return (string) (new \PiecesPHP\UserSystem\Controllers\HelperController())->setInstanceViewDir(basepath('app/classes/PiecesPHP/UserSystem/Views/'))->render('panel/pages/login-reports/attempts', [
                'days' => 14, 'periods' => [], 'from' => $desde, 'until' => $hasta->modify('-1 day'),
                'stats' => ['total' => $total, 'success' => $total, 'error' => 0, 'uniqueUsers' => 7, 'days' => [$desde->format('Y-m-d') => ['success' => $total, 'error' => 0]]],
                'previousTotal' => $anterior, 'oldest' => $viejoGuardado, 'maxRows' => 1000, 'tableURL' => 'https://zz/', 'exportUrl' => 'https://zz/export?days=14',
                'pieces' => new \PiecesPHP\UserSystem\Controllers\HelperController(),
            ], false);
        };
        $con = $pintar(106, 100, null);
        $sin = $pintar(5, 0, new \DateTimeImmutable('today'));
        $check(str_contains($con, sprintf(__(LOGIN_REPORT_LANG_GROUP, '%s frente al periodo anterior'), '+6,0 %')) && str_contains($sin, escape_html(__(LOGIN_REPORT_LANG_GROUP, 'Sin datos del periodo anterior'))), 'e13 DISCRIMINANTE: la variación frente al anterior (+6,0 %) y, sin anterior, «Sin datos del periodo anterior»');
        $check(preg_match('/data-type="uniqueUsers"[^>]*>\s*7\s*</', $con) === 1 && str_contains($con, sprintf(__(LOGIN_REPORT_LANG_GROUP, 'entre %d intentos'), 106)), 'e14 la tarjeta «Usuarios únicos» con su cifra y su pie «entre N intentos»');
        $check(!str_contains($con, 'data-access-trimmed') && str_contains($sin, 'data-access-trimmed') && str_contains($sin, (new \DateTimeImmutable('today'))->format('d-m-Y')), 'e15 la vista pinta la pista solo si le llega la fecha');
        //La pista, solo con el registro LLENO (P305 H1): la regla, pura; y el periodo de 90 días de esta base, sin ella.
        $inicio = new \DateTimeImmutable('2026-01-10');
        $despues = new \DateTimeImmutable('2026-01-20');
        $check(LoginAttemptsModel::trimmedSince($despues, $inicio, 1000, 1000) == $despues && LoginAttemptsModel::trimmedSince($despues, $inicio, 999, 1000) === null && LoginAttemptsModel::trimmedSince(new \DateTimeImmutable('2026-01-01'), $inicio, 5000, 1000) === null && LoginAttemptsModel::trimmedSince(null, $inicio, 5000, 1000) === null, 'e16 DISCRIMINANTE: recortado solo si está lleno (≥ maxRows) Y el más viejo es posterior al principio del periodo');
        [$desde90] = LoginAttemptsModel::periodRange(90, new \DateTimeImmutable());
        $viejoGuardado = LoginAttemptsModel::oldestDate();
        if ($viejoGuardado !== null && $viejoGuardado > $desde90 && LoginAttemptsModel::storedCount() < LoginAttemptsModel::maxRows()) {
            $check(!str_contains($pedir($registros . '?tab=access&view=attempts&days=90', $root)['body'], 'data-access-trimmed'), 'e17 DISCRIMINANTE: a 90 días, con el más viejo dentro del periodo pero el registro sin llenar, no sale la pista', $viejoGuardado->format('d-m-Y'));
        } else {
            echoTerminal('   [OMITIDO] e17: en esta base el más viejo no cae dentro de los 90 días, o el registro está lleno: no discrimina');
        }

        //─── f · Registro de ingreso y Usuarios sin ingreso, por periodo (JGD) ─────────────────────────────
        echoTerminal('');
        echoTerminal('[f] Con y sin ingreso: el periodo, las cifras, el orden, la visibilidad, la exportación y el estado vacío');
        $check($periodoActivo($pedir($registros . '?tab=access&view=logged', $root)['body']) === '14' && $periodoActivo($pedir($registros . '?tab=access&view=not-logged&days=13', $root)['body']) === '14' && $periodoActivo($pedir($registros . '?tab=access&view=logged&days=90', $root)['body']) === '90', 'f1 DISCRIMINANTE: en las dos sub-vistas, 14 días por omisión y con un days inválido; uno de la lista se respeta');
        $enlaces = $pedir($registros . '?tab=access&view=not-logged&days=30', $root)['body'];
        $check(preg_match('/data-access-period="7"/', $enlaces) === 1 && str_contains($enlaces, 'view=not-logged&amp;days=7') && !str_contains($enlaces, 'view=attempts&amp;days='), 'f2 cada sub-vista enlaza su propio periodo (view=not-logged&days=N)');
        $hoyUsuario = $crearUsuario('f-hoy', UsersModel::TYPE_USER_GENERAL, $organizaciones['propia']);
        $viejoUsuario = $crearUsuario('f-viejo', UsersModel::TYPE_USER_GENERAL, $organizaciones['propia']);
        $plantar($hoyUsuario, "{$prefijo}-f-hoy", true, $hoy);
        $plantar($viejoUsuario, "{$prefijo}-f-viejo", true, $viejo);
        $listado = function (string $tipo, string $dias, string $quien, string $busca) use ($pedir, $ruta, $root, $adminOrg): array {
            $consulta = ['xhr' => 'yes', 'days' => $dias, 'draw' => 1, 'start' => 0, 'length' => 200, 'search' => ['value' => $busca, 'regex' => 'false']];
            $columnas = [];
            for ($i = 0; $i < ($tipo === 'logged' ? 5 : 3); $i++) {
                $columnas[] = ['data' => $i, 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']];
            }
            $r = $pedir($ruta('reports-ajax', ['type' => $tipo]) . '?' . http_build_query($consulta + ['columns' => $columnas]), $quien === 'org' ? $adminOrg : $root);
            $datos = json_decode($r['body'], true);
            return is_array($datos['data'] ?? null) ? $datos['data'] : [];
        };
        $usuariosDe = fn(array $filas): array => array_map(fn($f) => strip_tags((string) ($f[1] ?? '')), $filas);
        $con14 = $usuariosDe($listado('logged', '14', 'root', "{$prefijo}-f-"));
        $con30 = $usuariosDe($listado('logged', '30', 'root', "{$prefijo}-f-"));
        $sin14 = $usuariosDe($listado('not-logged', '14', 'root', "{$prefijo}-f-"));
        $check(in_array("{$prefijo}-f-hoy", $con14, true) && !in_array("{$prefijo}-f-viejo", $con14, true) && in_array("{$prefijo}-f-viejo", $con30, true) && in_array("{$prefijo}-f-viejo", $sin14, true) && !in_array("{$prefijo}-f-hoy", $sin14, true), 'f3 DISCRIMINANTE: con ingreso = un ingreso correcto DENTRO del periodo (el de hace 20 días: sin ingreso en 14, con ingreso en 30)', json_encode([$con14, $con30, $sin14]) ?: '');
        $todosCon = $listado('logged', '90', 'root', '');
        $accesos = array_map(fn($f) => ($d = \DateTime::createFromFormat('!d-m-Y H:i', (string) ($f[3] ?? ''))) !== false ? $d->getTimestamp() : null, $todosCon);
        $ordenados = $accesos;
        rsort($ordenados);
        $check($todosCon !== [] && !in_array(null, $accesos, true) && $accesos === $ordenados, 'f4 DISCRIMINANTE: Registro de ingreso, del último acceso más reciente al más viejo (d-m-Y H:i)', (string) count($todosCon));
        $todosSin = $usuariosDe($listado('not-logged', '90', 'root', ''));
        $porNombre = $todosSin;
        usort($porNombre, fn($a, $b) => strcasecmp($a, $b));
        $check(count($todosSin) > 1 && array_map('mb_strtolower', $todosSin) === array_map('mb_strtolower', $porNombre), 'f5 Usuarios sin ingreso, por usuario ascendente');
        $plantar($usuarios['ajena-no-entro'], "{$prefijo}-ajena-no-entro", true, $hoy);
        $deOrg = $usuariosDe($listado('logged', '14', 'org', $prefijo));
        $check(in_array("{$prefijo}-f-hoy", $deOrg, true) && !in_array("{$prefijo}-ajena-no-entro", $deOrg, true), 'f6 DISCRIMINANTE: el administrador de organización no ve los ingresos de la otra organización', json_encode($deOrg) ?: '');
        $exportarUsuarios = function (string $sufijo, string $dias) use ($pedir, $camino, $root, $celdas): string {
            $r = $pedir($camino($sufijo) . '?' . http_build_query(['days' => $dias]), $root);
            return $r['status'] === 200 ? (string) $celdas($r['body']) : '';
        };
        $hoyId = (string) $hoyUsuario;
        $viejoId = (string) $viejoUsuario;
        $exp7 = $exportarUsuarios('export-logged', '7');
        $exp30 = $exportarUsuarios('export-logged', '30');
        $expSin14 = $exportarUsuarios('export-not-logged', '14');
        $tieneId = fn(string $texto, string $id): bool => preg_match('/(^|\n)' . preg_quote($id, '/') . '\t/', $texto) === 1;
        $check($tieneId($exp7, $hoyId) && !$tieneId($exp7, $viejoId) && $tieneId($exp30, $viejoId) && $tieneId($expSin14, $viejoId) && !$tieneId($expSin14, $hoyId), 'f7 DISCRIMINANTE: las exportaciones de con y sin ingreso respetan el periodo');
        $horasPintadas = preg_match('/data-type="allTimeOnPlatform"[^>]*>\s*([^<]+?)\s*</', $pedir($registros . '?tab=access&view=logged', $root)['body'], $m) === 1 ? $m[1] : null;
        //Calculadas como el root de la suite: la cifra depende de quién mira (P133).
        $sesionAntes = [get_config('current_user'), get_config('pcsphp_current_user_stored')];
        set_config('current_user', (object) ['id' => $usuarios['root']]);
        set_config('pcsphp_current_user_stored', null);
        try {
            $horasEsperadas = LoginAttemptsController::formatDecimal((float) str_replace(',', '', (string) \PiecesPHP\UserSystem\ORM\TimeOnPlatformModel::getAllHoursOnPlatform()));
        } finally {
            set_config('current_user', $sesionAntes[0]);
            set_config('pcsphp_current_user_stored', $sesionAntes[1]);
        }
        $check($horasPintadas !== null && $horasPintadas === $horasEsperadas, 'f8 «Horas totales», las de siempre (TimeOnPlatformModel::getAllHoursOnPlatform())', "{$horasPintadas} / {$horasEsperadas}");
        $pintarVista = function (string $vista, array $variables): string {
            [$desde, $hasta] = LoginAttemptsModel::periodRange(14, new \DateTimeImmutable());
            return (string) (new \PiecesPHP\UserSystem\Controllers\HelperController())->setInstanceViewDir(basepath('app/classes/PiecesPHP/UserSystem/Views/'))->render('panel/pages/login-reports/' . $vista, $variables + [
                'days' => 14, 'periods' => [], 'from' => $desde, 'until' => $hasta->modify('-1 day'), 'exportUrl' => 'https://zz/export?days=14', 'tableURL' => 'https://zz/datos',
                'pieces' => new \PiecesPHP\UserSystem\Controllers\HelperController(),
            ], false);
        };
        $conVista = $pintarVista('logged', ['totalUsers' => 4, 'loggedUsers' => 1, 'notLoggedUsers' => 3, 'deletedLoggedUsers' => 0, 'allTimeOnPlatform' => 2]);
        $check(str_contains($conVista, sprintf(__(LOGIN_REPORT_LANG_GROUP, '%d %% del total de usuarios'), 25)) && str_contains($conVista, sprintf(__(LOGIN_REPORT_LANG_GROUP, '%d %% del total de usuarios'), 75)) && !str_contains($conVista, 'chart-data') && !str_contains($conVista, __(LOGIN_REPORT_LANG_GROUP, 'Exportar datos')), 'f9 DISCRIMINANTE: Registro de ingreso con sus % (25 y 75), sin la dona ni «Exportar datos»');
        $sinVacio = $pintarVista('not-logged', ['totalUsers' => 4, 'loggedUsers' => 4, 'notLoggedUsers' => 0]);
        $sinLleno = $pintarVista('not-logged', ['totalUsers' => 4, 'loggedUsers' => 1, 'notLoggedUsers' => 3]);
        $check(str_contains($sinVacio, 'ui placeholder segment') && str_contains($sinVacio, escape_html(__(LOGIN_REPORT_LANG_GROUP, 'Todos los usuarios han ingresado en este periodo.'))) && !str_contains($sinVacio, 'data-access-table') && str_contains($sinLleno, 'data-access-table') && !str_contains($sinLleno, 'ui placeholder segment'), 'f10 DISCRIMINANTE: Usuarios sin ingreso: con cero, el estado vacío y sin tabla; con alguno, la tabla');
        $check(preg_match('/class="ui (orange|yellow|warning)[^"]*label/', $sinLleno) === 1 || str_contains($sinLleno, \PiecesPHP\Core\Utilities\Helpers\StatusLabel::html(sprintf(__(LOGIN_REPORT_LANG_GROUP, '%d %% del total de usuarios'), 75), \PiecesPHP\Core\Utilities\Helpers\StatusLabel::WARNING)), 'f11 el % de «Sin ingreso», con la etiqueta de advertencia del tema');

        //─── g · Los Eliminados: en la tabla de ingreso (actividad), no en las cifras; y las horas con el formato ──
        echoTerminal('');
        echoTerminal('[g] Un Eliminado que entró en el periodo: en la tabla con su etiqueta, no en las cifras; las horas con el formato del idioma');
        $cifraDe = fn(string $html, string $tipo): ?string => preg_match('/data-type="' . $tipo . '"[^>]*>\s*([^<]+?)\s*</', $html, $m) === 1 ? $m[1] : null;
        $antesOrgHtml = $pedir($registros . '?tab=access&view=logged', $adminOrg)['body'];
        $eliminado = $crearUsuario('g-eliminado', UsersModel::TYPE_USER_GENERAL, $organizaciones['propia']);
        $database->prepare('UPDATE `' . UsersModel::TABLE . '` SET status = ? WHERE id = ?')->execute([UsersModel::STATUS_USER_DELETED, $eliminado]);
        $plantar($eliminado, "{$prefijo}-g-eliminado", true, $hoy);
        $despuesOrgHtml = $pedir($registros . '?tab=access&view=logged', $adminOrg)['body'];
        $check($cifraDe($antesOrgHtml, 'loggedUsers') !== null && $cifraDe($antesOrgHtml, 'loggedUsers') === $cifraDe($despuesOrgHtml, 'loggedUsers') && $cifraDe($antesOrgHtml, 'notLoggedUsers') === $cifraDe($despuesOrgHtml, 'notLoggedUsers'), 'g1 DISCRIMINANTE: el Eliminado que entró NO cuenta en «Con ingreso» ni en «Sin ingreso»', json_encode([$cifraDe($antesOrgHtml, 'loggedUsers'), $cifraDe($despuesOrgHtml, 'loggedUsers')]) ?: '');
        $check(!str_contains($antesOrgHtml, 'data-access-deleted') && str_contains($despuesOrgHtml, 'data-access-deleted') && str_contains($despuesOrgHtml, escape_html(sprintf(__(LOGIN_REPORT_LANG_GROUP, 'La tabla incluye %d cuentas eliminadas que entraron en este periodo; no cuentan en las cifras.'), 1))), 'g2 DISCRIMINANTE: la pista dice 1 (y antes no salía)');
        $filaEliminado = array_values(array_filter($listado('logged', '14', 'org', "{$prefijo}-g-"), fn($f) => strip_tags((string) ($f[1] ?? '')) === "{$prefijo}-g-eliminado"));
        $check(count($filaEliminado) === 1 && str_contains((string) ($filaEliminado[0][2] ?? ''), '>' . __(LOGIN_REPORT_LANG_GROUP, 'Eliminado') . '<'), 'g3 DISCRIMINANTE: está en la tabla de ingreso, con la etiqueta «Eliminado»');
        $check(!in_array("{$prefijo}-g-eliminado", $usuariosDe($listado('not-logged', '14', 'org', "{$prefijo}-g-")), true), 'g4 y no en la de sin ingreso');
        $expIngreso = $exportarUsuarios('export-logged', '14');
        $check(str_contains($expIngreso, ' (' . __(LOGIN_REPORT_LANG_GROUP, 'Eliminado') . ')'), 'g5 DISCRIMINANTE: la exportación de ingreso lo lleva con « (Eliminado)»');
        //En español, la función tal cual; en inglés, con los separadores de su diccionario (en el terminal, set_lang() no
        //recarga los diccionarios).
        $enEspanol = LoginAttemptsController::formatDecimal(1203.5);
        $enIngles = number_format(1203.5, 1, lang(LOGIN_REPORT_LANG_GROUP, ',', 'en'), lang(LOGIN_REPORT_LANG_GROUP, '.', 'en'));
        $check(\PiecesPHP\Core\Config::get_lang() === 'es' && $enEspanol === '1.203,5' && $enIngles === '1,203.5', 'g6 DISCRIMINANTE: las horas, con coma decimal en español y punto en inglés', "{$enEspanol} / {$enIngles}");
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

})->setDescription('El administrador de organización solo ve y exporta los accesos de la suya: el resumen y las tres exportaciones de intentos de acceso; el principal y el administrador general ven las dos organizaciones. Y Accesos › Intentos: el periodo (14 por omisión), las cifras y la serie de un rango, el filtro por resultado de la tabla y de la exportación, y las tarjetas. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
