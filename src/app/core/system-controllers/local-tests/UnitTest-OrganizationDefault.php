<?php

//P61: navegar NO escribe. El middleware del panel ya no asigna la organización global (-10) a quien no tiene; si el
//tipo la requiere, anota el defecto una vez por sesión. Corre el foundHandler REAL, en transacción que se revierte.

use PiecesPHP\UserSystem\ORM\UsersModel;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/organization-default', function ($args) {

    echoTerminal("\e[33m[TEST:OrganizationDefault] Nadie recibe la organización global por navegar\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    $marca = 'zz-prueba-p61-' . bin2hex(random_bytes(3));
    $pdo = UsersModel::model()::getDb(Config::app_db('default')['db']);
    $usuarioPrevio = get_config('current_user');
    $guardadoPrevio = get_config('pcsphp_current_user_stored');
    $rutaLog = basepath('app/logs/error.plain.log');
    $creados = ['usuarios' => [], 'organizaciones' => []];

    //El middleware REAL del panel, tal como lo registra el contenedor.
    $foundHandler = get_router()->getDI()->get('foundHandler');
    $manejador = new class implements \Psr\Http\Server\RequestHandlerInterface {
        public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
        {
            return new ResponseRoute();
        }
    };
    $navegar = function (int $idUsuario) use ($foundHandler, $manejador): void {
        set_config('current_user', (object) ['id' => $idUsuario]);
        set_config('pcsphp_current_user_stored', null);
        $peticion = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/admin/'), new Headers(), [], [], (new StreamFactory())->createStream(''));
        $foundHandler($peticion, $manejador);
    };
    $crearUsuario = function (int $tipo, ?int $organizacion, string $sufijo) use ($marca, &$creados): UsersModel {
        //Con el mapper y no por el alta: desde P39 el alta RECHAZA crear un usuario sin organización si su tipo la
        //requiere, que es justo el defecto de datos que esta prueba necesita reproducir.
        $usuario = new UsersModel();
        $usuario->username = "{$marca}-{$sufijo}";
        $usuario->email = "{$marca}-{$sufijo}@example.com";
        $usuario->password = password_hash('zz-Clave-P61-1', \PASSWORD_DEFAULT);
        $usuario->firstname = 'Zz';
        $usuario->secondname = '';
        $usuario->firstLastname = 'Prueba';
        $usuario->secondLastname = '';
        $usuario->type = $tipo;
        $usuario->status = UsersModel::STATUS_USER_ACTIVE;
        $usuario->failedAttempts = 0;
        if ($organizacion !== null) {
            $usuario->organization = $organizacion;
        }
        $usuario->createdAt = new \DateTime();
        $usuario->modifiedAt = $usuario->createdAt;
        $usuario->save();
        $creados['usuarios'][] = $usuario->username;
        return $usuario;
    };
    $organizacionDe = function (int $id): array {
        $modelo = UsersModel::model();
        $modelo->resetAll();
        $modelo->select()->where(new WhereSegment([WhereItem::isEqual('id', $id)]))->execute();
        $filas = (array) $modelo->result();
        $fila = $filas[0] ?? null;
        return [$fila !== null, $fila !== null ? $fila->organization : 'sin fila'];
    };
    $logDesde = function (int $desde) use ($rutaLog): string {
        return is_file($rutaLog) ? (string) file_get_contents($rutaLog, false, null, $desde) : '';
    };

    //RETORNO-IGNORADO: la conexión va en ERRMODE_EXCEPTION; si no pudiera abrir la transacción, lanzaría.
    $pdo->beginTransaction();
    try {

        //─── a1 · El tipo que NO requiere organización ──────────────────────────────────────────────────
        echoTerminal('[a1] Un tipo que no requiere organización, con null, sigue con null');
        $tamanoLog = is_file($rutaLog) ? (int) filesize($rutaLog) : 0;
        $sinRequerir = $crearUsuario(UsersModel::TYPE_USER_ROOT, null, 'a1');
        $idA1 = (int) $sinRequerir->id;
        $navegar($idA1);
        [$existeA1, $orgA1] = $organizacionDe($idA1);
        $nuevoLogA1 = $logDesde($tamanoLog);
        $check(in_array(UsersModel::TYPE_USER_ROOT, UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION, true), 'a1 el tipo elegido NO requiere organización');
        $check($existeA1 && $orgA1 === null, 'a1 tras navegar, su organización sigue siendo null', 'organización: ' . var_export($orgA1, true));
        $check(!str_contains($nuevoLogA1, (string) $idA1), 'a1 y no se anota nada: no hay defecto que anotar', mb_substr($nuevoLogA1, 0, 160));
        echoTerminal(' ');

        //─── a2 · El tipo que SÍ la requiere ────────────────────────────────────────────────────────────
        echoTerminal('[a2] Un tipo que la requiere, con null, tampoco la recibe, y queda el registro');
        $tamanoLog = is_file($rutaLog) ? (int) filesize($rutaLog) : 0;
        $requiriendo = $crearUsuario(UsersModel::TYPE_USER_GENERAL, null, 'a2');
        $idA2 = (int) $requiriendo->id;
        $navegar($idA2);
        [$existeA2, $orgA2] = $organizacionDe($idA2);
        $nuevoLogA2 = $logDesde($tamanoLog);
        $check(!in_array(UsersModel::TYPE_USER_GENERAL, UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION, true), 'a2 el tipo elegido SÍ requiere organización');
        $check($existeA2 && $orgA2 === null, 'a2 tras navegar, su organización sigue siendo null: nadie le pone el -10', 'organización: ' . var_export($orgA2, true));
        $check(
            str_contains($nuevoLogA2, (string) $idA2) && str_contains($nuevoLogA2, 'defecto de datos (P61)'),
            'a2 el defecto queda anotado en error.plain.log, con el id del usuario',
            mb_substr($nuevoLogA2, 0, 200)
        );
        echoTerminal(' ');

        //─── a2b · Una vez por sesión ───────────────────────────────────────────────────────────────────
        echoTerminal('[a2b] Con sesión activa, se anota UNA vez, no una por petición');
        $rutaSesiones = sys_get_temp_dir() . '/pcsphp-zz-p61-sesiones';
        $rutaSesionesPrevia = (string) ini_get('session.save_path');
        //RETORNO-IGNORADO: si la carpeta ya existe da false y da igual; lo que decide es si session_start() abre.
        @mkdir($rutaSesiones, 0700, true);
        //RETORNO-IGNORADO: devuelve el valor anterior, que ya se guardó arriba en $rutaSesionesPrevia.
        ini_set('session.save_path', $rutaSesiones);
        $sesionAbierta = session_status() === PHP_SESSION_ACTIVE ? true : @session_start();
        $tamanoLog = is_file($rutaLog) ? (int) filesize($rutaLog) : 0;
        $navegar($idA2);
        $navegar($idA2);
        $navegar($idA2);
        $nuevoLogSesion = $logDesde($tamanoLog);
        $vecesAnotado = mb_substr_count($nuevoLogSesion, 'defecto de datos (P61)');
        $check($sesionAbierta && session_status() === PHP_SESSION_ACTIVE, 'a2b hay sesión activa para la prueba', 'estado ' . session_status());
        $check($vecesAnotado === 1, 'a2b tres visitas, UNA sola anotación', "anotaciones: {$vecesAnotado}");
        [, $orgA2Sesion] = $organizacionDe($idA2);
        $check($orgA2Sesion === null, 'a2b y sigue sin organización tras las tres visitas', 'organización: ' . var_export($orgA2Sesion, true));
        echoTerminal(' ');

        //─── a3 · El que sí la tiene ────────────────────────────────────────────────────────────────────
        echoTerminal('[a3] El usuario con organización de verdad no cambia');
        $organizacion = OrganizationsController::createOrganization([
            'name' => "{$marca}-org",
            'nit' => "{$marca}-nit",
        ]);
        if ($organizacion !== null) { $creados['organizaciones'][] = "{$marca}-org"; }
        $idOrganizacion = $organizacion !== null && $organizacion->id !== null ? (int) $organizacion->id : 0;
        $conOrganizacion = $crearUsuario(UsersModel::TYPE_USER_GENERAL, $idOrganizacion, 'a3');
        $idA3 = (int) $conOrganizacion->id;
        $navegar($idA3);
        [$existeA3, $orgA3] = $organizacionDe($idA3);
        $check($idOrganizacion > 0, 'a3 la organización de la prueba se creó', 'id ' . $idOrganizacion);
        $check($existeA3 && (int) $orgA3 === $idOrganizacion, 'a3 tras navegar, sigue en la suya', 'organización: ' . var_export($orgA3, true));
        $check((int) $orgA3 !== OrganizationMapper::INITIAL_ID_GLOBAL, 'a3 y no se la cambiaron por la global');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        echoTerminal(' ');
        echoTerminal('[z1] Los registros de la prueba');
        echoTerminal('   usuarios creados: ' . (count($creados['usuarios']) > 0 ? implode(', ', $creados['usuarios']) : 'ninguno'));
        echoTerminal('   organizaciones creadas: ' . (count($creados['organizaciones']) > 0 ? implode(', ', $creados['organizaciones']) : 'ninguna'));
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        if (isset($rutaSesionesPrevia)) {
            //RETORNO-IGNORADO: se repone el valor de antes; el anterior que devuelve no sirve para nada aquí.
            ini_set('session.save_path', $rutaSesionesPrevia);
        }
        if (isset($rutaSesiones) && is_dir($rutaSesiones)) {
            foreach ((array) glob($rutaSesiones . '/*') as $resto) {
                //RETORNO-IGNORADO: limpieza de la carpeta temporal propia de la prueba.
                if (is_string($resto) && is_file($resto)) { unlink($resto); }
            }
            //RETORNO-IGNORADO: limpieza de la carpeta temporal propia de la prueba.
            rmdir($rutaSesiones);
        }
        if ($pdo->inTransaction()) {
            //RETORNO-IGNORADO: ERRMODE_EXCEPTION; y z1 comprueba de verdad que no quedó ningún registro.
            $pdo->rollBack();
        }
        set_config('current_user', $usuarioPrevio);
        set_config('pcsphp_current_user_stored', $guardadoPrevio);
        $restosUsuarios = UsersModel::model();
        $restosUsuarios->resetAll();
        $restosUsuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$marca}%")]))->execute();
        $restosOrganizaciones = OrganizationMapper::model();
        $restosOrganizaciones->resetAll();
        $restosOrganizaciones->select()->where(new WhereSegment([WhereItem::like('name', "{$marca}%")]))->execute();
        $cuantosUsuarios = count((array) $restosUsuarios->result());
        $cuantasOrganizaciones = count((array) $restosOrganizaciones->result());
        $check($cuantosUsuarios === 0 && $cuantasOrganizaciones === 0, 'z1 la transacción se revirtió: 0 usuarios y 0 organizaciones de la prueba', "usuarios {$cuantosUsuarios}, organizaciones {$cuantasOrganizaciones}");
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P61: navegar no asigna la organización global; el defecto se anota una vez por sesión.')->setEffects([CliActions::EFFECT_DATABASE])->register();
