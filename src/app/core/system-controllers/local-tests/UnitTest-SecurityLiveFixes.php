<?php

//Tres arreglos de seguridad: límite de códigos por usuario, encargado de la misma organización y regiones por marcador.
//Escribe en la base local filas zz-seg-; al empezar y en el finally borra las de todas las pasadas, también una caída.
//@areas src/app/classes/EventsLog/
//@areas src/app/classes/Organizations/
//@areas src/app/classes/PiecesPHP/SystemStatus/

use EventsLog\Mappers\LogsMapper;
use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\App\Locations\Controllers\Region;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Controllers\RecoveryPasswordController;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/security-live-fixes', function ($args) {

    echoTerminal("\e[33m[TEST:SecurityLiveFixes] Códigos con límite por usuario, encargado de la misma organización y regiones por marcador\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    $prefijo = 'zz-seg-' . bin2hex(random_bytes(3));
    $correo = "{$prefijo}-codigos@example.invalid";
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');
    $usuarios = [];
    $organizaciones = [];

    $peticion = function (string $metodo, array $cuerpo = [], array $consulta = []): RequestRoute {
        $request = new RequestRoute(
            $metodo,
            (new UriFactory())->createUri('http://localhost/prueba'),
            new Headers(),
            [],
            [],
            (new StreamFactory())->createStream('')
        );
        $request = $request->withParsedBody($cuerpo)->withQueryParams($consulta);
        return $request instanceof RequestRoute ? $request : new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/prueba'), new Headers(), [], [], (new StreamFactory())->createStream(''));
    };
    //Devuelve [estado, cuerpo decodificado]; una excepción deja el detalle en el cuerpo y estado 0.
    $llamar = function (object $controlador, string $metodo, RequestRoute $request, array $argumentos = []): array {
        try {
            $respuesta = $argumentos === []
                ? $controlador->$metodo($request, new ResponseRoute())
                : $controlador->$metodo($request, new ResponseRoute(), $argumentos);
            $cuerpo = json_decode((string) $respuesta->getBody(), true);
            return [$respuesta->getStatusCode(), is_array($cuerpo) ? $cuerpo : []];
        } catch (\Throwable $exception) {
            return [0, ['excepcion' => get_class($exception) . ': ' . $exception->getMessage()]];
        }
    };
    $describir = fn(array $valor): string => json_encode($valor, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '';
    $contar = function (string $tabla) use ($database, $correo): int {
        $statement = $database->prepare("SELECT COUNT(*) FROM `{$tabla}` WHERE email = ?");
        $statement->execute([$correo]);
        return (int) $statement->fetchColumn();
    };
    //Filas de código ya pasadas el minuto: prueban el tope por hora sin el del minuto.
    $filasViejas = function (string $tabla, int $cuantas, ?string $tipo = null) use ($database, $correo): void {
        $database->prepare("DELETE FROM `{$tabla}` WHERE email = ?")->execute([$correo]);
        for ($i = 1; $i <= $cuantas; $i++) {
            $creada = (new \DateTime())->modify('-' . (5 * $i) . ' minutes');
            $columnas = 'email, code, created, expired' . ($tipo !== null ? ', type' : '');
            $marcas = '?, ?, ?, ?' . ($tipo !== null ? ', ?' : '');
            $valores = [$correo, 900000 + $i, $creada->format('Y-m-d H:i:s'), (clone $creada)->modify('+24 hour')->format('Y-m-d H:i:s')];
            if ($tipo !== null) {
                $valores[] = $tipo;
            }
            $database->prepare("INSERT INTO `{$tabla}` ({$columnas}) VALUES ({$marcas})")->execute($valores);
        }
    };
    $crearUsuario = function (string $sufijo, string $email, int $tipo, ?int $organizacion) use ($database, $prefijo, &$usuarios): int {
        $statement = $database->prepare(
            'INSERT INTO ' . UsersModel::TABLE . ' (username, password, firstname, firstLastname, email, type, status, organization)'
                . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            "{$prefijo}-{$sufijo}",
            password_hash('zz-' . bin2hex(random_bytes(6)), \PASSWORD_DEFAULT),
            'ZZ', 'Seguridad', $email, $tipo, UsersModel::STATUS_USER_ACTIVE, $organizacion,
        ]);
        $id = (int) $database->lastInsertId();
        $usuarios[] = $id;
        return $id;
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

    //El encargado es una metapropiedad: un mapper, un id o nada.
    $idDelEncargado = function (int $organizacion): ?int {
        $administrador = (new OrganizationMapper($organizacion))->administrator;
        $id = is_object($administrador) ? ($administrador->id ?? null) : $administrador;
        return is_numeric($id) && (int) $id > 0 ? (int) $id : null;
    };

    //La limpieza, por el patrón de TODAS las pasadas (zz-seg- y 6 hex), no solo la de esta: una pasada que cayó a mitad
    //deja sus filas con otro prefijo. En el orden que piden las claves foráneas: primero lo que apunta a los usuarios.
    $patron = '^zz-seg-[0-9a-f]{6}-';
    //Devuelve lo borrado por tabla, para enumerarlo cuando al empezar encuentra restos.
    $limpiar = function () use ($database, $patron): array {
        $borradas = [];
        $ejecutar = function (string $tabla, string $sql, array $valores) use ($database, &$borradas): void {
            $statement = $database->prepare($sql);
            $statement->execute($valores);
            $borradas[$tabla] = ($borradas[$tabla] ?? 0) + $statement->rowCount();
        };
        $ids = function (string $sql) use ($database, $patron): array {
            $statement = $database->prepare($sql);
            $statement->execute([$patron]);
            return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
        };
        $usuarios = $ids('SELECT id FROM ' . UsersModel::TABLE . ' WHERE username REGEXP ?');
        $organizaciones = $ids('SELECT id FROM `' . OrganizationMapper::TABLE . '` WHERE name REGEXP ?');
        $ejecutar('locations_countries', 'DELETE FROM locations_countries WHERE name REGEXP ?', [$patron]);
        foreach (['pcsphp_recovery_password', 'pcsphp_user_problems', 'pcsphp_tickets_log'] as $tabla) {
            $ejecutar($tabla, "DELETE FROM `{$tabla}` WHERE email REGEXP ?", [$patron]);
        }
        $borradas[MailLogMapper::TABLE] = MailLogMapper::deleteByRecipientMark('zz-seg-');
        foreach ($usuarios as $id) {
            $ejecutar(LogsMapper::TABLE, 'DELETE FROM ' . LogsMapper::TABLE . ' WHERE (referenceSource = ? AND referenceValue = ?) OR createdBy = ?', [UsersModel::TABLE, (string) $id, $id]);
            $ejecutar('system_approvals_elements', 'DELETE FROM `system_approvals_elements` WHERE (referenceTable = ? AND referenceValue = ?) OR createdBy = ? OR approvalBy = ?', [UsersModel::TABLE, (string) $id, $id, $id]);
            foreach (['login_attempts' => 'userID', 'time_on_platform' => 'userID', 'pcsphp_users_otp_secrets' => 'user', 'news_readed_relationship' => 'readerUser', 'user_system_profile' => 'belongsTo'] as $tabla => $columna) {
                $ejecutar($tabla, "DELETE FROM `{$tabla}` WHERE `{$columna}` = ?", [$id]);
            }
            //La organización guarda quién la creó y quién la editó, con clave foránea al usuario (el encargado va en `meta`).
            $ejecutar(OrganizationMapper::TABLE . ' (createdBy a NULL)', 'UPDATE `' . OrganizationMapper::TABLE . '` SET createdBy = NULL WHERE createdBy = ?', [$id]);
            $ejecutar(OrganizationMapper::TABLE . ' (modifiedBy a NULL)', 'UPDATE `' . OrganizationMapper::TABLE . '` SET modifiedBy = NULL WHERE modifiedBy = ?', [$id]);
        }
        foreach ($usuarios as $id) {
            $ejecutar(UsersModel::TABLE, 'DELETE FROM ' . UsersModel::TABLE . ' WHERE id = ?', [$id]);
        }
        foreach ($organizaciones as $id) {
            $ejecutar('system_approvals_elements', 'DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?', [OrganizationMapper::TABLE, (string) $id]);
            $ejecutar(OrganizationMapper::TABLE, 'DELETE FROM `' . OrganizationMapper::TABLE . '` WHERE id = ?', [$id]);
        }
        return array_filter($borradas);
    };
    $restosPrevios = $limpiar();
    if ($restosPrevios !== []) {
        echoTerminal('[0] Restos de una pasada anterior, borrados al empezar:');
        foreach ($restosPrevios as $tabla => $filas) {
            echoTerminal("   · {$tabla}: {$filas}");
        }
        echoTerminal(' ');
    }

    try {
        //──── 1. Los códigos de recuperación tienen un límite por usuario ──────────────────────
        echoTerminal('[1/3] Los códigos de recuperación tienen un límite por usuario');
        if ($check(MailDelivery::goesToSink(), 'p1 PRE: el correo va al sumidero local: la prueba no envía nada fuera')) {
            $crearUsuario('codigos', $correo, UsersModel::TYPE_USER_GENERAL, null);
            $recuperacion = new RecoveryPasswordController();
            $problemas = new UserProblemsController();

            $r = [];
            for ($i = 0; $i < 3; $i++) {
                $r[] = $llamar($recuperacion, 'recoveryPasswordRequestCode', $peticion('POST', ['username' => $correo]));
            }
            $check($contar('pcsphp_recovery_password') === 1, 'g1 tres peticiones de recuperación seguidas crean UN código', 'hay ' . $contar('pcsphp_recovery_password'));
            $check($r[1] === $r[0] && $r[2] === $r[0] && $r[0][0] === 200, 'g2 la 2.ª y la 3.ª responden lo mismo que la 1.ª', $describir($r));

            $filasViejas('pcsphp_recovery_password', 4);
            $llamar($recuperacion, 'recoveryPasswordRequestCode', $peticion('POST', ['username' => $correo]));
            $check($contar('pcsphp_recovery_password') === 5, 'g3 CANARIO: con 4 en la hora, ninguno del último minuto, la 5.ª crea', 'hay ' . $contar('pcsphp_recovery_password'));
            $filasViejas('pcsphp_recovery_password', 5);
            $llamar($recuperacion, 'recoveryPasswordRequestCode', $peticion('POST', ['username' => $correo]));
            $check($contar('pcsphp_recovery_password') === 5, 'g4 con 5 en la hora, la 6.ª no crea', 'hay ' . $contar('pcsphp_recovery_password'));

            $p = [];
            for ($i = 0; $i < 3; $i++) {
                $p[] = $llamar($problemas, 'generateCode', $peticion('POST', ['username' => $correo, 'type' => UserProblemsController::TYPE_USER_FORGET]));
            }
            $check($contar('pcsphp_user_problems') === 1, 'g5 tres peticiones de «olvidé mi usuario» seguidas crean UN código', 'hay ' . $contar('pcsphp_user_problems'));
            $check($p[1] === $p[0] && $p[2] === $p[0] && ($p[0][1]['send_mail'] ?? null) === true, 'g6 la 2.ª y la 3.ª responden lo mismo que la 1.ª, que envió', $describir($p));

            $filasViejas('pcsphp_user_problems', 5, UserProblemsController::TYPE_USER_FORGET);
            $llamar($problemas, 'generateCode', $peticion('POST', ['username' => $correo, 'type' => UserProblemsController::TYPE_USER_FORGET]));
            $check($contar('pcsphp_user_problems') === 5, 'g7 con 5 en la hora, la 6.ª no crea', 'hay ' . $contar('pcsphp_user_problems'));
        }
        echoTerminal(' ');

        //──── 2. El encargado tiene que ser de la misma organización ────────────────────────
        echoTerminal('[2/3] El encargado tiene que ser de la misma organización');
        $propia = $crearOrganizacion('propia');
        $ajena = $crearOrganizacion('ajena');
        $deLaAjena = $crearUsuario('de-la-ajena', "{$prefijo}-de-la-ajena@example.invalid", UsersModel::TYPE_USER_ADMIN_ORG, $ajena);
        $deLaPropia = $crearUsuario('de-la-propia', "{$prefijo}-de-la-propia@example.invalid", UsersModel::TYPE_USER_ADMIN_ORG, $propia);
        set_config('current_user', (object) ['id' => 1]);
        set_config('pcsphp_current_user_stored', null);
        $root = getLoggedFrameworkUser();
        if ($check($root !== null && (int) $root->type === UsersModel::TYPE_USER_ROOT, 'p2 PRE: la petición va como root (el id 1)')) {
            $controlador = new MyOrganizationProfileController();
            [$estado, $cuerpo] = $llamar($controlador, 'changeOrganizationAdminAction', $peticion('POST', ['newUserAdminID' => (string) $deLaAjena]), ['organizationID' => (string) $propia]);
            $administradorID = $idDelEncargado($propia);
            $check(($cuerpo['success'] ?? null) === false && $administradorID === null, 'o1 un usuario de otra organización como encargado se rechaza, y no se guarda', $describir($cuerpo) . ', administrador ' . var_export($administradorID, true));
            [$estado, $cuerpo] = $llamar($controlador, 'changeOrganizationAdminAction', $peticion('POST', ['newUserAdminID' => (string) $deLaPropia]), ['organizationID' => (string) $propia]);
            $administradorID = $idDelEncargado($propia);
            $check(($cuerpo['success'] ?? null) === true && $administradorID === $deLaPropia, 'o2 CANARIO: uno de la misma organización se acepta', $describir($cuerpo) . ', administrador ' . var_export($administradorID, true));
        }
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        echoTerminal(' ');

        //──── 3. La búsqueda de regiones va por marcador ───────────────────────────────────
        echoTerminal('[3/3] La búsqueda de regiones va por marcador');
        //Dos países propios con su región: la base limpia no trae ninguna, y sin filas la inyección no se vería.
        $inicio = 'ZzSeg' . bin2hex(random_bytes(3));
        $pais = $database->prepare('INSERT INTO locations_countries (name, region, active) VALUES (?, ?, 1)');
        $pais->execute(["{$prefijo}-pais-norte", "{$inicio}Norte"]);
        $pais->execute(["{$prefijo}-pais-sur", "{$inicio}Sur"]);
        if ($check(true, 'p3 PRE: dos países zz-seg- con región propia')) {
            $esperada = $database->prepare('SELECT region FROM locations_countries WHERE UPPER(region) LIKE UPPER(?) GROUP BY region ORDER BY region ASC');
            $esperada->execute([$inicio . '%']);
            $esperadas = array_map('strval', $esperada->fetchAll(\PDO::FETCH_COLUMN));
            [$estado, $cuerpo] = $llamar(new Region(), 'search', $peticion('GET', [], ['query' => $inicio]));
            $ids = array_map(fn($fila) => (string) ($fila['id'] ?? ''), $cuerpo);
            $check($estado === 200 && $ids === $esperadas && count($ids) === 2, "r1 una búsqueda normal («{$inicio}») devuelve las mismas regiones que el LIKE por prefijo", $describir($ids) . ' frente a ' . $describir($esperadas));
            [$estado, $cuerpo] = $llamar(new Region(), 'search', $peticion('GET', [], ['query' => "a'b"]));
            $check($estado === 200 && !isset($cuerpo['excepcion']) && $cuerpo === [], 'r2 una búsqueda con comilla («a\'b») no rompe ni devuelve nada', $describir($cuerpo));
            [$estado, $cuerpo] = $llamar(new Region(), 'search', $peticion('GET', [], ['query' => "%') OR 1=1 OR ('"]));
            $check($estado === 200 && $cuerpo === [], 'r3 una que intenta cerrar la cadena e inyectar no devuelve nada (sin el marcador, devolvería las regiones)', $describir($cuerpo));
        }
        echoTerminal(' ');

    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
        $limpiar();
    }

    $quedan = function (string $sql, array $valores) use ($database): int {
        $statement = $database->prepare($sql);
        $statement->execute($valores);
        return (int) $statement->fetchColumn();
    };
    $restos = $quedan('SELECT COUNT(*) FROM ' . UsersModel::TABLE . ' WHERE username LIKE ?', ["{$prefijo}%"])
         + $quedan('SELECT COUNT(*) FROM `' . OrganizationMapper::TABLE . '` WHERE name LIKE ?', ["{$prefijo}%"])
         + $quedan('SELECT COUNT(*) FROM locations_countries WHERE name LIKE ?', ["{$prefijo}%"])
         + $quedan('SELECT COUNT(*) FROM pcsphp_recovery_password WHERE email = ?', [$correo])
         + $quedan('SELECT COUNT(*) FROM pcsphp_user_problems WHERE email = ?', [$correo])
         + $quedan('SELECT COUNT(*) FROM `' . MailLogMapper::TABLE . '` WHERE recipients LIKE ?', ["%{$prefijo}%"]);
    $check($restos === 0, 'z1 la limpieza no deja restos zz-seg-', "quedan {$restos}");

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Tres arreglos de seguridad: los códigos de recuperación tienen un límite por usuario (uno por minuto, cinco por hora) y responden igual, el encargado de una organización tiene que ser de ella, y la búsqueda de regiones va por marcador. Escribe usuarios, organizaciones y códigos zz-seg- y los borra.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
