<?php

//P37/P39: las altas por API que FALLAN. Un alta que falla NO envía correo: por eso esta mitad se queda en `gates`
//y las dos que salen bien viven en `core/api-register-mail`, con `email` declarado. 309.

use API\Controllers\APIController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/api-register', function ($args) {

    echoTerminal("\e[33m[TEST:ApiRegister] El alta pública por API que falla: ni usuario, ni organización, ni -10\e[39m");
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

    $marca = 'zz-prueba-api-' . bin2hex(random_bytes(3));
    $pdo = UsersModel::model()::getDb(Config::app_db('default')['db']);
    $creados = ['usuarios' => [], 'organizaciones' => []];

    //El alta por API: el cuerpo viaja como lo manda el cliente, con organizationID y userType cifrados.
    $peticion = function (array $cuerpo): RequestRoute {
        $peticion = new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-prueba-api'), new Headers(), [], [], (new StreamFactory())->createStream(''));
        return $peticion->withAttribute('actionType', 'register')->withParsedBody($cuerpo);
    };
    $alta = function (array $extra) use ($peticion, $marca): array {
        $sufijo = $extra['__sufijo'];
        unset($extra['__sufijo']);
        $cuerpo = array_merge([
            'email' => "{$marca}-{$sufijo}@example.com",
            'password' => 'zz-Clave-Api-1',
            'passwordConfirm' => 'zz-Clave-Api-1',
            'firstName' => 'Zz',
            'firstLastName' => 'Prueba',
            'phoneCode' => '+57',
            'phoneNumber' => '3000000000',
        ], $extra);
        $respuesta = (new APIController())->usersActions($peticion($cuerpo), new ResponseRoute());
        $texto = (string) $respuesta->getBody();
        return [json_decode($texto, true), $texto];
    };
    $usuario = function (string $sufijo) use ($marca): ?\stdClass {
        $modelo = UsersModel::model();
        $modelo->resetAll();
        $modelo->select()->where(['username' => "{$marca}-{$sufijo}@example.com"])->execute();
        $filas = (array) $modelo->result();
        return $filas[0] ?? null;
    };
    $organizacionPorNombre = function (string $nombre): ?\stdClass {
        $modelo = OrganizationMapper::model();
        $modelo->resetAll();
        $modelo->select()->where(new WhereSegment([WhereItem::isEqual('name', $nombre)]))->execute();
        $filas = (array) $modelo->result();
        return $filas[0] ?? null;
    };

    $pdo->beginTransaction();
    try {

        //─── c · LOS CANARIOS: sin ellos, «no queda usuario» y «no queda organización» pasan gratis ─────
        echoTerminal('[c] Los canarios: los buscadores de esta suite encuentran lo que sí existe');
        //No puede ser un alta que salga bien: eso ENVIARÍA. Se prueba el DETECTOR, no el alta: se crea con
        //`createOrganization()`, que no envía, y se exige que el buscador lo VEA.
        $nombreCanario = "{$marca}-org-canario";
        $organizacionCanario = OrganizationsController::createOrganization([
            'name' => $nombreCanario,
            'nit' => "{$marca}-nit-canario",
        ]);
        if ($organizacionCanario !== null) { $creados['organizaciones'][] = $nombreCanario; }
        $check($organizacionCanario !== null && $organizacionPorNombre($nombreCanario) !== null, 'c1 CANARIO: el buscador de organizaciones encuentra una que existe, así que un «NO queda» significa algo', $organizacionCanario === null ? 'createOrganization() devolvió null' : 'el buscador no la vio');
        $modeloPrincipal = UsersModel::model();
        $modeloPrincipal->resetAll();
        $modeloPrincipal->select()->where(new WhereSegment([WhereItem::isEqual('type', UsersModel::TYPE_USER_ROOT)]))->execute();
        $cuantosPrincipales = count((array) $modeloPrincipal->result());
        $check($cuantosPrincipales > 0, 'c2 CANARIO: el mismo mecanismo de consulta de usuarios devuelve filas de la base', "principales encontrados: {$cuantosPrincipales}");
        echoTerminal(' ');

        //─── a2 · La organización que no se puede crear ─────────────────────────────────────────────────
        echoTerminal('[a2] Si la organización no se puede crear, no queda usuario ni organización');
        //El nombre no cabe en la columna: la base lo rechaza, createOrganization() lo propaga y el alta se corta ahí.
        //Sin tocar código: es un dato que el cliente podría mandar.
        $nombreImposible = str_repeat('Z', 70000);
        [$cuerpoA2, $textoA2] = $alta([
            '__sufijo' => 'a2',
            'organizationID' => BaseHashEncryption::encryptBidirectionalHash('NONE'),
            'organizationName' => $nombreImposible,
        ]);
        $usuarioA2 = $usuario('a2');
        $organizacionA2 = $organizacionPorNombre($nombreImposible);
        if ($usuarioA2 !== null) { $creados['usuarios'][] = (string) $usuarioA2->username; }
        if ($organizacionA2 !== null) { $creados['organizaciones'][] = 'la del nombre imposible'; }
        $mensajeA2 = is_array($cuerpoA2) ? (string) ($cuerpoA2['message'] ?? '') : '';
        $check(($cuerpoA2['success'] ?? null) === false, 'a2 responde que no', mb_substr($textoA2, 0, 200));
        $check(preg_match('~ERR-\d{8}-[A-Z0-9]{6}~', $mensajeA2) === 1, 'a2 con el código de referencia del error (P56)', mb_substr($mensajeA2, 0, 200));
        $check($usuarioA2 === null, 'a2 NO queda usuario');
        $check($organizacionA2 === null, 'a2 NO queda organización');
        echoTerminal(' ');

        //─── a4 · P39 ───────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a4] P39: a un tipo que requiere organización no se le queda el -10');
        $check(!in_array(UsersModel::TYPE_USER_GENERAL, UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION, true), 'a4 el usuario general SÍ requiere organización');
        [$cuerpoA4, $textoA4] = $alta([
            '__sufijo' => 'a4',
            'userType' => BaseHashEncryption::encryptBidirectionalHash((string) UsersModel::TYPE_USER_GENERAL),
        ]);
        $usuarioA4 = $usuario('a4');
        if ($usuarioA4 !== null) { $creados['usuarios'][] = (string) $usuarioA4->username; }
        $check(($cuerpoA4['success'] ?? null) === false, 'a4 el alta sin organización falla', mb_substr($textoA4, 0, 200));
        $check($usuarioA4 === null, 'a4 y no queda usuario con el -10', $usuarioA4 !== null ? 'organización ' . var_export($usuarioA4->organization, true) : '');
        $conMenosDiez = UsersModel::model();
        $conMenosDiez->resetAll();
        $conMenosDiez->select()->where(new WhereSegment([
            WhereItem::like('username', "{$marca}%", WhereItem::AND_OPERATOR),
            WhereItem::isEqual('organization', OrganizationMapper::INITIAL_ID_GLOBAL),
        ]))->execute();
        $cuantosMenosDiez = count((array) $conMenosDiez->result());
        $check($cuantosMenosDiez === 0, 'a4 ningún usuario de esta prueba quedó en la organización global', "con -10: {$cuantosMenosDiez}");

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        echoTerminal(' ');
        echoTerminal('[z1] Los registros de la prueba');
        echoTerminal('   usuarios creados: ' . (count($creados['usuarios']) > 0 ? implode(', ', $creados['usuarios']) : 'ninguno'));
        echoTerminal('   organizaciones creadas: ' . (count($creados['organizaciones']) > 0 ? implode(', ', $creados['organizaciones']) : 'ninguna'));
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
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

})->setDescription('P37/P39: las dos altas por API que fallan —la organización que no se puede crear y el tipo que exige organización—, con sus canarios. NO envía correo: un alta que falla se corta antes del envío; las que salen bien están en core/api-register-mail.')->setEffects([CliActions::EFFECT_DATABASE])->register();
