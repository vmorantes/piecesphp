<?php

//P37: las dos altas por API que SALEN BIEN. Un alta exitosa ENVÍA CORREO dentro del flujo (`APIController`,
//§«Envío de correo»), así que esta mitad declara `email` y sale de `gates`. 309.

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

CliActions::make('unit-tests:core/api-register-mail', function ($args) {

    echoTerminal("\e[33m[TEST:ApiRegisterMail] El alta pública por API que sale bien: organización nueva y previa\e[39m");
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
    //La bandeja de Mailpit es DE LA MÁQUINA y tiene correo de otros trabajos: se retira SOLO lo propio, por
    //sus identificadores, como hace core/mail-log. Hasta el 2026-10-05 esta suite dejaba sus dos correos.
    $api = 'http://127.0.0.1:8025/api/v1';
    $pedirMailpit = function (string $metodo, string $ruta, ?array $cuerpo = null) use ($api): ?array {
        $opciones = ['method' => $metodo, 'timeout' => 5, 'ignore_errors' => true];
        if ($cuerpo !== null) {
            $opciones['header'] = 'Content-Type: application/json';
            $opciones['content'] = json_encode($cuerpo, JSON_THROW_ON_ERROR);
        }
        $respuesta = @file_get_contents($api . $ruta, false, stream_context_create(['http' => $opciones]));
        $datos = is_string($respuesta) ? json_decode($respuesta, true) : null;
        return is_array($datos) ? $datos : null;
    };
    $idsCon = function (string $aguja) use ($pedirMailpit): array {
        $ids = [];
        foreach ((array) (($pedirMailpit('GET', '/messages?limit=200') ?? [])['messages'] ?? []) as $mensaje) {
            foreach ((array) (is_array($mensaje) ? ($mensaje['To'] ?? []) : []) as $destino) {
                if (is_array($destino) && str_contains((string) ($destino['Address'] ?? ''), $aguja)) {
                    $ids[] = (string) ($mensaje['ID'] ?? '');
                    continue 2;
                }
            }
        }
        return array_values(array_filter($ids));
    };
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

        //─── a1 · La organización nueva ─────────────────────────────────────────────────────────────────
        echoTerminal('[a1] El alta con organización nueva la crea y deja al usuario como su administrador');
        $nombreOrg = "{$marca}-org-nueva";
        [$cuerpoA1, $textoA1] = $alta([
            '__sufijo' => 'a1',
            'organizationID' => BaseHashEncryption::encryptBidirectionalHash('NONE'),
            'organizationName' => $nombreOrg,
        ]);
        $usuarioA1 = $usuario('a1');
        $organizacionA1 = $organizacionPorNombre($nombreOrg);
        if ($usuarioA1 !== null) { $creados['usuarios'][] = (string) $usuarioA1->username; }
        if ($organizacionA1 !== null) { $creados['organizaciones'][] = $nombreOrg; }
        $check(($cuerpoA1['success'] ?? null) === true && $usuarioA1 !== null, 'a1 responde success y el usuario existe', mb_substr($textoA1, 0, 200));
        $check($organizacionA1 !== null && (int) $organizacionA1->status === OrganizationMapper::PENDING_APPROVAL, 'a1 la organización se creó, pendiente de aprobación', $organizacionA1 !== null ? 'status ' . $organizacionA1->status : 'no existe');
        $check(
            $usuarioA1 !== null && $organizacionA1 !== null && (int) $usuarioA1->organization === (int) $organizacionA1->id,
            'a1 la organización del usuario es la nueva, no el -10',
            $usuarioA1 !== null ? 'usuario en ' . var_export($usuarioA1->organization, true) : 'sin usuario'
        );
        //«administrator» es meta-propiedad: vive en el JSON de «meta» y no sale en la fila. Se lee por el mapper.
        $mapperA1 = $organizacionA1 !== null ? new OrganizationMapper((int) $organizacionA1->id) : null;
        $administradorA1 = $mapperA1 !== null ? $mapperA1->administrator : null;
        $administradorA1 = $administradorA1 instanceof UsersModel ? $administradorA1->id : $administradorA1;
        $check($administradorA1 !== null && $usuarioA1 !== null && (int) $administradorA1 === (int) $usuarioA1->id, 'a1 el usuario nuevo es el administrador de la organización', 'administrador ' . var_export($administradorA1, true));
        $check($usuarioA1 !== null && (int) $usuarioA1->type === UsersModel::TYPE_USER_ADMIN_ORG, 'a1 y su tipo es administrador de organización', $usuarioA1 !== null ? 'tipo ' . $usuarioA1->type : 'sin usuario');
        $check($organizacionA1 !== null && str_starts_with((string) $organizacionA1->nit, OrganizationMapper::NIT_WITHOUT_INFORMATION_PREFIX), 'a1 el nit es el marcador de «sin información», por constante', $organizacionA1 !== null ? (string) $organizacionA1->nit : 'sin organización');
        echoTerminal(' ');

        //─── a3 · El alta normal ────────────────────────────────────────────────────────────────────────
        echoTerminal('[a3] El alta con una organización que ya existe sigue igual');
        $organizacionPrevia = OrganizationsController::createOrganization([
            'name' => "{$marca}-org-previa",
            'nit' => "{$marca}-nit",
        ]);
        if ($organizacionPrevia !== null) { $creados['organizaciones'][] = "{$marca}-org-previa"; }
        [$cuerpoA3, $textoA3] = $alta([
            '__sufijo' => 'a3',
            'organizationID' => BaseHashEncryption::encryptBidirectionalHash((string) ($organizacionPrevia !== null ? $organizacionPrevia->id : '')),
            'userType' => BaseHashEncryption::encryptBidirectionalHash((string) UsersModel::TYPE_USER_GENERAL),
        ]);
        $usuarioA3 = $usuario('a3');
        if ($usuarioA3 !== null) { $creados['usuarios'][] = (string) $usuarioA3->username; }
        $check($organizacionPrevia !== null && $organizacionPrevia->id !== null, 'a3 la organización de partida se creó con createOrganization()', $organizacionPrevia !== null ? 'id ' . var_export($organizacionPrevia->id, true) : 'null');
        $check(($cuerpoA3['success'] ?? null) === true && $usuarioA3 !== null, 'a3 responde success y el usuario existe', mb_substr($textoA3, 0, 200));
        $check(
            $usuarioA3 !== null && $organizacionPrevia !== null && (int) $usuarioA3->organization === (int) $organizacionPrevia->id && (int) $usuarioA3->type === UsersModel::TYPE_USER_GENERAL,
            'a3 queda en esa organización y como usuario general',
            $usuarioA3 !== null ? 'organización ' . var_export($usuarioA3->organization, true) . ', tipo ' . $usuarioA3->type : 'sin usuario'
        );

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
    echoTerminal('[z2] Esta mitad ENVÍA: dos correos, uno por cada alta que sale bien, al sumidero local; y los retira');
    //CANARIO: un mensaje AJENO, sembrado por la API de Mailpit, tiene que seguir ahí después de retirar lo propio.
    $ajeno = 'demo-ajeno-' . bin2hex(random_bytes(3)) . '@localhost.test';
    $sembrado = $pedirMailpit('POST', '/send', ['From' => ['Email' => 'demo-remitente@localhost.test'], 'To' => [['Email' => $ajeno]], 'Subject' => 'Ajeno a core/api-register-mail', 'Text' => 'canario']);
    $propios = $idsCon($marca);
    $check(count($propios) === 2, 'z2a los dos correos de las altas llegaron al sumidero', count($propios) . ' de 2');
    if ($propios !== []) {
        //RETORNO-IGNORADO: quien dice si se borraron es z2b.
        $pedirMailpit('DELETE', '/messages', ['IDs' => $propios]);
    }
    $check($idsCon($marca) === [], 'z2b y la prueba los retira: no queda ninguno suyo en la bandeja');
    $idsAjeno = $idsCon($ajeno);
    $check(is_array($sembrado) && count($idsAjeno) === 1, 'z2c CANARIO: el mensaje ajeno sigue ahí: solo se retira lo propio', count($idsAjeno) . ' ajeno(s)');
    if ($idsAjeno !== []) {
        //RETORNO-IGNORADO: el canario es de la prueba, y se retira; si quedara, se vería en la bandeja.
        $pedirMailpit('DELETE', '/messages', ['IDs' => $idsAjeno]);
    }
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P37: las dos altas por API que salen bien, con organización nueva y con organización existente. ENVÍA DOS CORREOS, uno por alta: el aviso de «a la espera de aprobación» sale dentro del flujo del alta y no hay forma de ejercitarla sin que salga.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_EMAIL])->register();
