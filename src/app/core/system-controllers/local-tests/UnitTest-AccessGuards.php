<?php

//Guardas de acceso: si se rompen EN SILENCIO nadie lo nota. Ver T147 y la LEY 24.

use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/access-guards';
$cliTaskDescription = 'Las guardas de acceso RECHAZAN, no solo aceptan lo bueno';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:AccessGuards] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;

    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };

    $llave = 'clave-de-prueba-de-esta-suite-no-es-la-de-la-aplicacion';
    $mensaje = 'mensaje-de-control';

    //──── 1. BaseHashEncryption::hashVerify ─────────────────────────────────────────────
    echoTerminal('[1/18]hashVerify() RECHAZA una firma que no es la suya');

    $firmaBuena = hash_hmac('SHA256', $mensaje, $llave, true);

    $check(
        BaseHashEncryption::hashVerify($mensaje, 'firma-inventada', $llave) === false,
        'una firma inventada da false, ESTRICTO',
        var_export(BaseHashEncryption::hashVerify($mensaje, 'firma-inventada', $llave), true)
    );
    $check(
        BaseHashEncryption::hashVerify('otro-mensaje', $firmaBuena, $llave) === false,
        'la firma correcta de OTRO mensaje también da false'
    );
    $check(
        BaseHashEncryption::hashVerify($mensaje, $firmaBuena, 'otra-llave') === false,
        'la firma correcta con OTRA llave también da false'
    );

    //EL DISCRIMINANTE. Sin esto, una guarda que rechazara SIEMPRE pasaría las tres de arriba.
    $check(
        BaseHashEncryption::hashVerify($mensaje, $firmaBuena, $llave) === true,
        'DISCRIMINANTE: la firma correcta da true, ESTRICTO'
    );

    //La rama sin `else` de la cadena de algoritmos: qué devuelve un algoritmo que no existe.
    $noSoportado = BaseHashEncryption::hashVerify($mensaje, $firmaBuena, $llave, 'ALGORITMO-QUE-NO-EXISTE');
    $check(
        $noSoportado === BaseHashEncryption::NOT_SUPPORTED_ALGORITHM,
        'un algoritmo no soportado devuelve NOT_SUPPORTED_ALGORITHM'
    );
    $check(
        !$noSoportado,
        'y ese valor es FALSY: un `if (!hashVerify(...))` lo trata como rechazo — CIERRA',
        'Vale ' . var_export($noSoportado, true) . '. Si alguien lo cambiara a una cadena, este `if` pasaría a ACEPTAR.'
    );
    echoTerminal(' ');

    //──── 2. BaseToken::verify, y el valor que NO es falsy ──────────────────────────────
    echoTerminal('[2/18]verify() rechaza, y su código de error SÍ es truthy');

    $firmaToken = hash_hmac('SHA256', $mensaje, $llave, true);

    $check(
        BaseToken::verify($mensaje, 'firma-inventada', $llave) === false,
        'una firma inventada da false, ESTRICTO'
    );
    $check(
        BaseToken::verify($mensaje, $firmaToken, $llave) === true,
        'DISCRIMINANTE: la firma correcta da true, ESTRICTO'
    );

    //AQUÍ ESTÁ LA TRAMPA, y se congela para que nadie la destape:
    $verifyNoSoportado = BaseToken::verify($mensaje, $firmaToken, $llave, 'ALGORITMO-QUE-NO-EXISTE');
    $check(
        $verifyNoSoportado === BaseToken::NOT_SUPPORTED_ALGORITHM,
        'un algoritmo no soportado devuelve la CADENA NOT_SUPPORTED_ALGORITHM'
    );
    $check(
        (bool) $verifyNoSoportado === true,
        'y esa cadena es TRUTHY: `if (!verify(...))` la leería como FIRMA VÁLIDA',
        'Por eso `decode()` corta ANTES con `empty(self::$supported_algs[$header->alg])`. Quitar esa comprobación deja pasar cualquier firma.'
    );
    echoTerminal(' ');

    //──── 3. decode() no entrega el contenido de un token con firma alterada ────────────
    echoTerminal('[3/18]decode() y check() RECHAZAN un token manipulado');

    $tokenBueno = BaseToken::encode(['dato' => 'valor-original'], $llave, 'HS256');
    $partes = explode('.', $tokenBueno);

    //Mismo cuerpo, firma cambiada.
    $tokenFirmaRota = $partes[0] . '.' . $partes[1] . '.' . strrev($partes[2]);

    //Y el que ataca la rama del algoritmo: cabecera con un `alg` que no existe.
    $cabeceraFalsa = BaseToken::urlSafeB64Encode((string) BaseToken::jsonEncode(['typ' => 'JWT', 'alg' => 'ALGORITMO-QUE-NO-EXISTE']));
    $tokenAlgFalso = $cabeceraFalsa . '.' . $partes[1] . '.' . $partes[2];

    $algs = ['HS256', 'HS512', 'HS384'];

    $decodificadoRoto = BaseToken::decode($tokenFirmaRota, $llave, $algs);
    $check(
        !is_object($decodificadoRoto),
        'un token con la firma alterada NO devuelve el contenido',
        'Devolvió: ' . (is_object($decodificadoRoto) ? 'un objeto — LA FIRMA NO SE ESTÁ COMPROBANDO' : var_export($decodificadoRoto, true))
    );

    $decodificadoAlg = BaseToken::decode($tokenAlgFalso, $llave, $algs);
    $check(
        !is_object($decodificadoAlg),
        'un token con un `alg` inexistente TAMPOCO devuelve el contenido',
        'Devolvió: ' . (is_object($decodificadoAlg) ? 'un objeto — SE ACEPTÓ UNA FIRMA SIN VERIFICAR' : var_export($decodificadoAlg, true))
    );

    //La de arriba NO prueba la guarda que parece: detrás hay otra. Para aislar
    //`empty(self::$supported_algs[...])` hay que meter el alg inventado en `$allowed_algs`. T147.
    $decodificadoAlgPermitido = BaseToken::decode($tokenAlgFalso, $llave, array_merge($algs, ['ALGORITMO-QUE-NO-EXISTE']));
    $check(
        !is_object($decodificadoAlgPermitido),
        'y aunque el `alg` inventado esté en $allowed_algs, SIGUE sin devolver el contenido',
        'Devolvió: ' . (is_object($decodificadoAlgPermitido)
            ? 'un objeto — `verify()` habría devuelto la CADENA NOT_SUPPORTED_ALGORITHM y el `!` la leería como firma válida'
            : var_export($decodificadoAlgPermitido, true))
    );

    //EL DISCRIMINANTE.
    $decodificadoBueno = BaseToken::decode($tokenBueno, $llave, $algs);
    $check(
        is_object($decodificadoBueno) && ($decodificadoBueno->dato ?? null) === 'valor-original',
        'DISCRIMINANTE: el token intacto sí devuelve su contenido'
    );

    //`check()` devuelve true, una CADENA de error, o —medido— el objeto del payload: el
    //consumidor tiene que comparar con `!== true`. Ver T147.
    $check(
        BaseToken::check('', $llave) === BaseToken::INVALID_TOKEN_SUPPLIED,
        'check() con token vacío devuelve INVALID_TOKEN_SUPPLIED'
    );
    $check(
        BaseToken::check($tokenFirmaRota, $llave, $algs) !== true,
        'check() con la firma alterada NO devuelve true'
    );

    //El token de verdad lo hace `setToken()`, que le pone `exp`. El DISCRIMINANTE va con ese.
    $tokenSesion = (string) BaseToken::setToken(['dato' => 'valor-original'], $llave);
    $check(
        BaseToken::check($tokenSesion, $llave, $algs) === true,
        'DISCRIMINANTE: check() sobre un token de `setToken()` devuelve true, ESTRICTO'
    );

    //Medido y congelado: un JWT SIN `exp` no da true ni da una cadena.
    $sinExp = BaseToken::check($tokenBueno, $llave, $algs);
    $check(
        $sinExp !== true && is_object($sinExp),
        'un JWT SIN `exp` hace que check() devuelva el OBJETO del payload, no true',
        'Sale de `isExpire()`, que sin `exp` devuelve el payload; `check()` lo reenvía tal cual. '
        . 'Es TRUTHY y NO es true: con `!== true` cierra, con `if (!$x)` abriría.'
    );
    $check(
        (bool) BaseToken::INVALID_TOKEN_SUPPLIED === true
        && (bool) BaseToken::EXPIRED_TOKEN === true
        && (bool) BaseToken::INVALID_USER_LOGGIN === true,
        'los TRES códigos de error de check() son TRUTHY',
        'Por eso `SessionToken::isActiveSession()` compara con `!== true`. Cambiarlo a `if (!$logged)` haría pasar cualquier error como sesión válida.'
    );
    echoTerminal(' ');

    //──── 4. Roles::hasPermissions ──────────────────────────────────────────────────────
    echoTerminal('[4/18]hasPermissions() niega lo que no está concedido');

    $roles = Roles::getRoles();
    $rutas = get_routes();

    //Del árbol, no escritas a mano: una ruta concedida a un rol y NO a otro.
    $rutaElegida = null;
    $rolConcedido = null;
    $rolSinConceder = null;

    foreach ($roles as $rolA) {
        foreach ($roles as $rolB) {
            if ($rolA['code'] === $rolB['code'] || $rolB['all'] === true) {
                continue;
            }
            foreach ($rolA['allowed_routes'] as $nombreRuta) {
                if (!array_key_exists($nombreRuta, $rutas)) {
                    continue;
                }
                if (in_array($nombreRuta, $rolB['allowed_routes'], true)) {
                    continue;
                }
                $rutaElegida = $nombreRuta;
                $rolConcedido = $rolA['code'];
                $rolSinConceder = $rolB['code'];
                break 3;
            }
        }
    }

    $check(
        $rutaElegida !== null,
        'el árbol da una ruta concedida a un rol y NO a otro',
        $rutaElegida === null
            ? 'NO SE ENCONTRÓ NINGUNA: sin este par, las dos comprobaciones de abajo no significan nada.'
            : "ruta «{$rutaElegida}», concedida a {$rolConcedido} y no a {$rolSinConceder}"
    );

    if ($rutaElegida !== null) {
        $check(
            Roles::hasPermissions($rutaElegida, $rolSinConceder) === false,
            'un rol SIN la ruta concedida recibe false'
        );
        $check(
            Roles::hasPermissions($rutaElegida, $rolConcedido) === true,
            'DISCRIMINANTE: el rol que SÍ la tiene recibe true'
        );
    }

    $check(
        Roles::hasPermissions('ruta-que-no-existe-en-ninguna-parte-ag3', $rolConcedido ?? 0) === false,
        'una ruta que no existe da false — no se concede lo que no se conoce'
    );
    //CONTRATO MEDIDO: un rol inexistente NO devuelve false, LANZA. Solo calla con
    //`$silent_mode`. Ver T147.
    $lanzo = false;
    try {
        Roles::hasPermissions($rutaElegida ?? 'admin', 'rol-que-no-existe-ag3');
    } catch (\Throwable $e) {
        $lanzo = $e instanceof \PiecesPHP\Core\Exceptions\RoleNotExistsException;
    }
    $check(
        $lanzo,
        'un rol que no existe LANZA RoleNotExistsException, no devuelve false'
    );
    $check(
        Roles::hasPermissions($rutaElegida ?? 'admin', 'rol-que-no-existe-ag3', true) === false,
        'y con $silent_mode devuelve false — nunca true'
    );
    echoTerminal(' ');

    //──── 5. get_route_roles_allowed y su cadena sin `else` ─────────────────────────────
    echoTerminal('[5/18]get_route_roles_allowed() con un `$type` que no contempla');

    //Hace falta una ruta que DECLARE roles: con la lista vacía, la rama sin `else` no se
    //distingue de la buena y la comprobación no significaría nada.
    $rutaConRoles = null;
    foreach (array_keys($rutas) as $clave) {
        $nombreRuta = (string) $clave;
        $info = get_route_info($nombreRuta);
        if (is_array($info['roles_allowed'] ?? null) && count($info['roles_allowed']) > 0) {
            $rutaConRoles = $nombreRuta;
            break;
        }
    }

    $check(
        $rutaConRoles !== null,
        'el árbol da una ruta que declara roles',
        $rutaConRoles === null
            ? 'NO HAY NINGUNA: sin ella, lo de abajo no distingue la rama buena de la mala.'
            : "ruta «{$rutaConRoles}»"
    );

    if ($rutaConRoles !== null) {

        $porCodigo = get_route_roles_allowed($rutaConRoles, 'code');
        $porNombre = get_route_roles_allowed($rutaConRoles, 'name');
        $porNada = get_route_roles_allowed($rutaConRoles, 'tipo-que-no-existe');

        $check(
            count($porCodigo) > 0 && !in_array(null, $porCodigo, true),
            'DISCRIMINANTE: con `code` devuelve roles y ninguno es null'
        );
        $check(
            count($porNombre) > 0 && !in_array(null, $porNombre, true),
            'DISCRIMINANTE: con `name` devuelve roles y ninguno es null'
        );
        $check(
            in_array(null, $porNada, true),
            'CONTRATO ACTUAL: un `$type` no contemplado mete NULOS en la lista, y no avisa',
            'La cadena `if ($type == "name") … elseif ($type == "code")` NO tiene `else`, así que el '
            . '`array_map` devuelve null por cada rol declarado. Obtenido: ' . json_encode($porNada)
        );
        $check(
            $porNada !== $porCodigo,
            'y desde luego no devuelve lo mismo que `code`: se pierde la lista'
        );
    }
    echoTerminal(' ');

    //──── 6. Parameter: el acumulador que NACE en `true` ────────────────────────────────
    echoTerminal('[6/18]Parameter::isValid() nace en `true`, y eso decide qué pasa sin validador');

    //RECHAZO: con validador y NO opcional, un valor que no pasa tiene que LANZAR.
    $soloEnteros = new Parameter('edad', null, static fn ($v): bool => is_int($v), false);
    $lanzoParametro = false;
    try {
        $soloEnteros->validate('no-soy-un-entero');
    } catch (\Throwable $e) {
        $lanzoParametro = $e instanceof InvalidParameterValueException;
    }
    $check(
        $lanzoParametro,
        'un valor que el validador rechaza lanza InvalidParameterValueException'
    );

    //EL DISCRIMINANTE.
    $bueno = new Parameter('edad', null, static fn ($v): bool => is_int($v), false);
    $check(
        $bueno->validate(42) === true && $bueno->getValue() === 42,
        'DISCRIMINANTE: un valor que el validador acepta pasa y se guarda'
    );

    //Sin validador TODO es válido: `$valid` nace en `true`. Forma de `FileUpload` (T135),
    //aquí deliberada. Se congela para que un cambio en la inicialización se note.
    $sinValidador = new Parameter('lo-que-sea', null, null, false);
    $check(
        $sinValidador->validate('cualquier-cosa') === true,
        'CONTRATO ACTUAL: un Parameter SIN validador acepta cualquier valor',
        'El acumulador nace en `true` y solo baja si hay un `validate` invocable. '
        . 'Quien declare un parámetro sin validador NO está validando nada.'
    );
    echoTerminal(' ');

    //──── 7. Las rutas públicas de listado no devuelven borradores sin permiso ─────────
    echoTerminal('[7/18]Las rutas públicas de listado solo devuelven lo publicado sin permiso');

    //Sin sesión, pedir un estado no cuenta; con permiso, sí. Si esto cae, un anónimo lista borradores.
    $filtroPub = new \ReflectionMethod(\Publications\Controllers\PublicationsController::class, 'publicStatusFilter');
    $check($filtroPub->invokeArgs(null, [null, true, null]) === [null, false], 'publications: sin sesión, status=ANY se reduce a lo publicado');
    $check($filtroPub->invokeArgs(null, [\Publications\Mappers\PublicationMapper::DRAFT, false, null]) === [null, false], 'publications: sin sesión, pedir borradores se reduce a lo publicado');
    $check($filtroPub->invokeArgs(null, [null, true, \App\Model\UsersModel::TYPE_USER_GOOGLE_PLAY]) === [null, false], 'publications: un tipo sin permiso de borradores tampoco elige estado');
    $check($filtroPub->invokeArgs(null, [null, true, \App\Model\UsersModel::TYPE_USER_ROOT]) === [null, true], 'DISCRIMINANTE: publications, con permiso de borradores, status=ANY se respeta');
    $permisoBanner = new \ReflectionMethod(\PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController::class, 'canListAnyStatus');
    $check($permisoBanner->invokeArgs(null, [null]) === false, 'banner: sin sesión no se puede pedir cualquier estado', 'routeName() concede sin usuario: la guarda exige la sesión.');
    $check($permisoBanner->invokeArgs(null, [\App\Model\UsersModel::TYPE_USER_ROOT]) === true, 'DISCRIMINANTE: banner, con sesión y permiso de listado, se respeta');

    //LA CACHÉ DEL LISTADO: todo lo que cambia la respuesta cambia la clave.
    $claveCache = new \ReflectionMethod(\Publications\Controllers\PublicationsController::class, 'listCacheChecksum');
    $claveBase = ['es', 1, 10, null, null, false, null, null, [], false, 'sello'];
    $claveCon = static function (int $posicion, $valor) use ($claveBase): array {
        $partes = $claveBase;
        $partes[$posicion] = $valor;
        return $partes;
    };
    $check($claveCache->invokeArgs(null, $claveBase) === $claveCache->invokeArgs(null, $claveBase), 'DISCRIMINANTE: caché, el mismo listado da la misma clave');
    $check($claveCache->invokeArgs(null, $claveBase) !== $claveCache->invokeArgs(null, $claveCon(8, ['un-slug'])), 'caché: dos listados que solo difieren en ignoreSlugs tienen clave distinta');
    $check($claveCache->invokeArgs(null, $claveBase) !== $claveCache->invokeArgs(null, $claveCon(9, true)), 'caché: el orden aleatorio tiene su propia clave');
    $check($claveCache->invokeArgs(null, $claveBase) !== $claveCache->invokeArgs(null, $claveCon(5, true)), 'caché: status=ANY con permiso no comparte clave con el listado por defecto');
    echoTerminal(' ');

    //──── 8. El SELECT de listado de usuarios no trae la contraseña (#055) ──────────────
    echoTerminal('[8/18]UsersModel::fieldsToSelect() no selecciona la contraseña');

    //Si esto cae, getBy(), all() y los informes de accesos vuelven a mandar el hash en la respuesta.
    $camposUsuarios = (new \ReflectionMethod(\App\Model\UsersModel::class, 'fieldsToSelect'))->invoke(null);
    $conPassword = array_values(array_filter($camposUsuarios, fn ($campo) => is_string($campo) && str_ends_with($campo, '.password')));
    $check(count($camposUsuarios) > 0 && count($conPassword) === 0, 'usuarios: ningún campo de fieldsToSelect() termina en .password',
        count($conPassword) === 0 ? count($camposUsuarios) . ' campos' : 'con password: ' . implode(', ', $conPassword));
    $check(in_array(\App\Model\UsersModel::TABLE . '.username', $camposUsuarios, true), 'DISCRIMINANTE: usuarios, fieldsToSelect() sigue trayendo las demás columnas');
    echoTerminal(' ');

    //──── 9. /users/all/ no devuelve la contraseña (#057) ─────────────────────────────
    echoTerminal('[9/18] UsersController::_all() no devuelve la contraseña');

    //Si esto cae, cualquier usuario con sesión vuelve a poder pedir el hash de todos.
    $filasTodos = \App\Controller\UsersController::_all(1, 5)->elements();
    $clavesDe = static fn ($fila): array => is_object($fila) ? array_keys(get_object_vars($fila)) : (is_array($fila) ? array_keys($fila) : []);
    $conClave = array_values(array_filter($filasTodos, fn ($fila) => in_array('password', $clavesDe($fila), true)));
    $check(count($filasTodos) > 0 && count($conClave) === 0, 'usuarios: ninguna fila de _all() trae la clave password',
        count($filasTodos) === 0 ? 'SIN FILAS: sin usuarios en local no hay veredicto' : count($filasTodos) . ' filas, ' . count($conClave) . ' con password');
    $check(count($filasTodos) > 0 && in_array('username', $clavesDe($filasTodos[0]), true), 'DISCRIMINANTE: usuarios, las filas de _all() siguen trayendo username');
    echoTerminal(' ');

    //──── 10. canManage(): el alcance de las aprobaciones, en el servidor (#071) ─────────
    echoTerminal('[10/18] SystemApprovalsController::canManage() aplica C3 y C5; al limitado por C5, además pendiente, C1 y C4');

    //Si esto cae, un administrador de organización aprueba lo de otra, o lo suyo, con un POST directo.
    $usuario = static function (int $id, int $type, ?int $organization): \PiecesPHP\UserSystem\UserDataPackage {
        //SIN BASE: el constructor carga el usuario; aquí solo cuentan id, tipo y organización.
        $paquete = (new \ReflectionClass(\PiecesPHP\UserSystem\UserDataPackage::class))->newInstanceWithoutConstructor();
        foreach (['id' => $id, 'type' => $type, 'organization' => $organization] as $propiedad => $valor) {
            (new \ReflectionProperty(\PiecesPHP\UserSystem\UserDataPackage::class, $propiedad))->setValue($paquete, $valor);
        }
        return $paquete;
    };
    $elemento = static function (int $createdBy, int $organization, ?int $administrator, string $status = \SystemApprovals\Mappers\SystemApprovalsMapper::STATUS_PENDING,
        string $referenceTable = \Publications\Mappers\PublicationMapper::TABLE, string $isActive = '1', ?string $organizationApproval = null): \SystemApprovals\Mappers\SystemApprovalsMapper {
        //SIN BASE: sin id el constructor no consulta; los alias de fieldsToSelect() van en el registro extendido, como texto.
        $mapper = new \SystemApprovals\Mappers\SystemApprovalsMapper();
        (new \ReflectionProperty(\SystemApprovals\Mappers\SystemApprovalsMapper::class, 'extendedRecord'))->setValue($mapper, (object) [
            'referenceCreatedBy' => (string) $createdBy,
            'referenceOrganization' => (string) $organization,
            'referenceOrganizationAdministrator' => $administrator !== null ? (string) $administrator : null,
            'status' => $status,
            'referenceTable' => $referenceTable,
            'referenceIsActive' => $isActive,
            'referenceOrtanizationApprovalValue' => $organizationApproval,
        ]);
        return $mapper;
    };
    $puede = [\SystemApprovals\Controllers\SystemApprovalsController::class, 'canManage'];
    $adminA = $usuario(90001, \App\Model\UsersModel::TYPE_USER_ADMIN_ORG, 1);
    $check($puede($elemento(90010, 1, 90001), $adminA) === true, 'DISCRIMINANTE: el administrador de A resuelve lo de un miembro de A');
    $check($puede($elemento(90020, 2, 90002), $adminA) === false, 'el administrador de A NO resuelve lo de B (C5)');
    $check($puede($elemento(90011, 1, 90099), $adminA) === false, 'ni lo de A si en meta el administrador de A es otro (C5)');
    $check($puede($elemento(90001, 1, 90001), $adminA) === false, 'ni lo suyo propio, aunque sea de A y él la administre (C3)');
    $check($puede(new \SystemApprovals\Mappers\SystemApprovalsMapper(), $adminA) === false, 'sin registro extendido, como un id inexistente → false');
    $root = $usuario(90000, \App\Model\UsersModel::TYPE_USER_ROOT, -10);
    $check($puede($elemento(90020, 2, 90002), $root) === true && $puede($elemento(90000, -10, 3), $root) === true, 'DISCRIMINANTE: root resuelve lo de cualquier organización y lo suyo');
    //Estados (#073): el limitado por C5 solo resuelve lo pendiente; los que lo aprueban todo pueden volver a resolver.
    $pendiente = \SystemApprovals\Mappers\SystemApprovalsMapper::STATUS_PENDING;
    $aprobado = \SystemApprovals\Mappers\SystemApprovalsMapper::STATUS_APPROVED;
    $check($puede($elemento(90010, 1, 90001, $aprobado), $adminA) === false, 'el administrador de A NO vuelve a resolver lo de A ya APPROVED');
    $check($puede($elemento(90010, 1, 90001, \SystemApprovals\Mappers\SystemApprovalsMapper::STATUS_REJECTED), $adminA) === false, 'ni lo de A ya REJECTED');
    $check($puede($elemento(90010, 1, 90001, \SystemApprovals\Mappers\SystemApprovalsMapper::STATUS_DELETED), $adminA) === false, 'ni lo de A ya DELETED');
    $check($puede($elemento(90010, 1, 90001, $pendiente, \Publications\Mappers\PublicationMapper::TABLE, '0'), $adminA) === false, 'ni lo de A con la referencia inactiva (C1)');
    $check($puede($elemento(90010, 1, 90001, $pendiente, \App\Model\UsersModel::TABLE, '1', $aprobado), $adminA) === false, 'ni el perfil de un miembro de A si A ya está aprobada (C4)');
    $check($puede($elemento(90010, 1, 90001, $pendiente, \App\Model\UsersModel::TABLE, '1', $pendiente), $adminA) === true, 'DISCRIMINANTE: el perfil de un miembro de A con A pendiente, sí (C4)');
    $check($puede($elemento(90020, 2, 90002, $aprobado), $root) === true, 'DISCRIMINANTE: root sí vuelve a resolver lo ya APPROVED, como hoy');
    echoTerminal(' ');

    //──── 11. Roles: registrar, conceder y fijar el rol actual (#089) ───────────────────
    echoTerminal('[11/18] Roles RECHAZA lo duplicado, lo que no existe y el código que no está');

    //EL BANCO ES EL ESTADO ESTÁTICO: se fotografía y se repone. gates corre cada suite en su proceso, pero aquí no se confía en eso.
    $propiedadRoles = new \ReflectionProperty(Roles::class, 'roles');
    $propiedadActual = new \ReflectionProperty(Roles::class, 'currentRole');
    $rolesAntes = $propiedadRoles->getValue();
    $actualAntes = $propiedadActual->getValue();

    try {

        $codigos = array_map(fn (array $rol) => $rol['code'], Roles::getRoles());
        $check(Roles::roleExists(987654) === false, 'roleExists(): un código que no está registrado da false, ESTRICTO');
        $check(count($codigos) > 0 && Roles::roleExists((int) $codigos[0]) === true, 'DISCRIMINANTE: roleExists() con un código del árbol da true, ESTRICTO',
            json_encode($codigos, JSON_THROW_ON_ERROR));

        Roles::registerRole('zz-prueba-rol-bp', 770001, ['zz-prueba-ruta-bp']);
        $check(Roles::roleExists(770001) === true, 'DISCRIMINANTE: registerRole() deja el rol nuevo registrado');
        foreach ([['zz-prueba-rol-bp', 770002, 'el nombre'], ['zz-prueba-rol-bp-otro', 770001, 'el código']] as [$nombre, $codigo, $que]) {
            $lanzo = false;
            try {
                Roles::registerRole($nombre, $codigo);
            } catch (\Throwable $e) {
                $lanzo = $e instanceof \PiecesPHP\Core\Exceptions\RoleDuplicateException;
            }
            $check($lanzo, "registerRole(): repetir {$que} lanza RoleDuplicateException");
        }

        //EL TIPO CODE CON UN NOMBRE: castea a 0, que es el código de root, y le añadía la ruta. Arreglado en #091: ahora lanza.
        $rolCero = Roles::getRole(0);
        $rutasDeRootAntes = is_array($rolCero) ? count($rolCero['allowed_routes']) : -1;
        $lanzoCode = false;
        try {
            Roles::addPermission('zz-prueba-ruta-bp-mal-tipada', 'zz-prueba-rol-bp', Roles::IDENTIFIER_TYPE_CODE);
        } catch (\Throwable $e) {
            $lanzoCode = $e instanceof \PiecesPHP\Core\Exceptions\RoleNotExistsException;
        }
        $rolCeroDespues = Roles::getRole(0);
        $rutasDeRootDespues = is_array($rolCeroDespues) ? count($rolCeroDespues['allowed_routes']) : -1;
        $check($lanzoCode, 'RECHAZO: con el tipo CODE, un identificador que no es número lanza RoleNotExistsException');
        $check($rutasDeRootAntes === $rutasDeRootDespues && $rutasDeRootAntes > 0,
            'DISCRIMINANTE: y el rol de código 0 (root) NO gana ninguna ruta por una llamada mal tipada',
            "root: {$rutasDeRootAntes} rutas antes, {$rutasDeRootDespues} después");
        //Un código numérico sigue funcionando, como entero y como cadena.
        Roles::addPermission('zz-prueba-ruta-bp-codigo', 770001, Roles::IDENTIFIER_TYPE_CODE);
        Roles::addPermission('zz-prueba-ruta-bp-cadena', '770001', Roles::IDENTIFIER_TYPE_CODE);
        $rolPorCodigo = Roles::getRole(770001);
        $check(is_array($rolPorCodigo) && in_array('zz-prueba-ruta-bp-codigo', $rolPorCodigo['allowed_routes'], true)
            && in_array('zz-prueba-ruta-bp-cadena', $rolPorCodigo['allowed_routes'], true),
            'DISCRIMINANTE: un código válido sigue valiendo, como entero (770001) y como cadena («770001»)');

        $lanzo = false;
        try {
            Roles::addPermission('zz-prueba-ruta-bp2', 'rol-que-no-existe-bp', Roles::IDENTIFIER_TYPE_NAME);
        } catch (\Throwable $e) {
            $lanzo = $e instanceof \PiecesPHP\Core\Exceptions\RoleNotExistsException;
        }
        $check($lanzo, 'addPermission(): por nombre, un rol que no existe lanza RoleNotExistsException');

        //La ruta tiene que EXISTIR en el árbol: hasPermissions() no concede lo que no conoce (sección 4). Se reusa la de allí.
        $rutaReal = $rutaElegida ?? array_key_first($rutas);
        Roles::addPermission((string) $rutaReal, 'zz-prueba-rol-bp', Roles::IDENTIFIER_TYPE_NAME);
        $rolNuevo = Roles::getRole('zz-prueba-rol-bp');
        $check(is_array($rolNuevo) && in_array((string) $rutaReal, $rolNuevo['allowed_routes'], true), 'DISCRIMINANTE: addPermission() con el rol bueno añade la ruta', (string) $rutaReal);
        $check(Roles::hasPermissions((string) $rutaReal, 770001) === true, 'y hasPermissions() se la concede a ese rol, ESTRICTO');
        $check(Roles::hasPermissions('zz-prueba-ruta-bp-jamas-concedida', 770001) === false, 'y NO concede una ruta que nadie le añadió');

        $lanzo = false;
        try {
            Roles::setCurrentRole('rol-que-no-existe-bp');
        } catch (\Throwable $e) {
            $lanzo = $e instanceof \PiecesPHP\Core\Exceptions\RoleNotExistsException;
        }
        $check($lanzo, 'setCurrentRole(): un rol que no existe lanza RoleNotExistsException, y el actual no cambia');
        Roles::setCurrentRole('zz-prueba-rol-bp');
        $actual = Roles::getCurrentRole();
        $check(is_array($actual) && $actual['code'] === 770001, 'DISCRIMINANTE: setCurrentRole() fija el rol que sí existe');
        //La comparación es laxa (`==`), y el 0 es el caso que cambió en PHP 8: un nombre no se confunde con el código 0.
        Roles::setCurrentRole(0);
        $actualCero = Roles::getCurrentRole();
        $check(is_array($actualCero) && $actualCero['code'] === 0, 'setCurrentRole(0) fija el rol de código 0, no el primer rol con nombre que se cruce');

    } finally {
        $propiedadRoles->setValue(null, $rolesAntes);
        $propiedadActual->setValue(null, $actualAntes);
    }

    $check(count(Roles::getRoles()) === count($rolesAntes) && Roles::roleExists(770001) === false, 'el estado de Roles queda como estaba al acabar');
    echoTerminal(' ');

    //──── 12. RequestRoute::getAttribute (#089) ─────────────────────────────────────────
    echoTerminal('[12/18] getAttribute() no se inventa la ruta, y devuelve el valor por defecto de lo que no está');

    $peticion = new \PiecesPHP\Core\Routing\RequestRoute(
        'GET',
        (new \Slim\Psr7\Factory\UriFactory())->createUri('http://localhost/zz-prueba-bp'),
        new \Slim\Psr7\Headers(),
        [],
        [],
        (new \Slim\Psr7\Factory\StreamFactory())->createStream('')
    );

    $claseLanzada = null;
    $devuelto = 'no-lanzó';
    try {
        $devuelto = $peticion->getAttribute('route');
    } catch (\Throwable $e) {
        $claseLanzada = get_class($e);
    }
    $check($claseLanzada !== null, "getAttribute('route') sin enrutado hecho LANZA: no devuelve una ruta ni null",
        $claseLanzada ?? 'devolvió ' . var_export($devuelto, true) . ' — SI DEVUELVE null, el llamador cree que no hay ruta y sigue');
    $peticion->silenceOnUnexistingRoute = true;
    $silenciada = 'lanzó';
    try {
        $silenciada = $peticion->getAttribute('route');
    } catch (\Throwable $e) {
        $silenciada = 'lanzó ' . get_class($e);
    }
    $check($silenciada === null || is_string($silenciada), 'con silenceOnUnexistingRoute no devuelve una Route inventada', var_export($silenciada, true));
    $check($peticion->getAttribute('zz-no-esta-bp', 'porDefecto') === 'porDefecto', 'DISCRIMINANTE: un atributo que no está devuelve el valor por defecto');
    $conAtributo = $peticion->withAttribute('zz-bp', 'valor');
    $check($conAtributo->getAttribute('zz-bp') === 'valor', 'DISCRIMINANTE: un atributo puesto se devuelve tal cual');
    //La comparación es laxa (`$name == 'route'`), pero la FIRMA pide string: un int no llega nunca a compararse, y '0' == 'route' es false.
    $check($peticion->getAttribute('0', 'porDefecto') === 'porDefecto', 'pedir el atributo «0» no entra en la rama de «route»: la laxa no tiene por dónde morder');
    echoTerminal(' ');

    //──── 13. Parameter: lo obligatorio rechaza; lo opcional se queda en su default (#089) ──
    echoTerminal('[13/18] Parameter RECHAZA cuando es obligatorio, y lo opcional NUNCA falla: cae al valor por defecto');

    $obligatorio = new Parameter('zz-bp', 0, static fn ($v): bool => is_int($v), false);
    $lanzoObligatorio = false;
    try {
        $obligatorio->validate('abc');
    } catch (\Throwable $e) {
        $lanzoObligatorio = $e instanceof InvalidParameterValueException;
    }
    $check($lanzoObligatorio, 'obligatorio: un valor que el validador rechaza lanza InvalidParameterValueException');

    $valido = new Parameter('zz-bp', 0, static fn ($v): bool => is_int($v), false);
    $check($valido->validate(7) === true && $valido->getValue() === 7, 'DISCRIMINANTE: el valor válido pasa y se guarda tal cual, ESTRICTO');

    //CONTRATO MEDIDO: con `optional`, validate() SIEMPRE devuelve true y el valor inválido se sustituye por el default, sin avisar.
    $opcional = new Parameter('zz-bp', 0, static fn ($v): bool => is_int($v), true);
    $check($opcional->validate('abc') === true && $opcional->getValue() === 0,
        'CONTRATO: opcional con un valor inválido devuelve TRUE y deja el valor por defecto',
        'validate() solo falla si NO es opcional. Quien declare un parámetro opcional no recibe error: recibe el default.');
    $vacia = new Parameter('zz-bp', 0, static fn ($v): bool => is_int($v), true);
    $check($vacia->validate('') === true && $vacia->getValue() === null,
        'CONTRATO: la cadena vacía se convierte en null antes de validar (nullable()), y el opcional la acepta');
    //La comparación laxa de isValid solo interviene en el camino opcional: acepta un valor que el validador rechazó si == al default.
    $laxa = new Parameter('zz-bp', '0', static fn ($v): bool => is_string($v), true);
    $check($laxa->validate(0) === true && $laxa->getValue() === 0,
        'CONTRATO: opcional con default «0» acepta el ENTERO 0 por la comparación laxa, y lo guarda como entero',
        'Es el único efecto medible de la laxa: `$value == $this->getDefaultValue()`. Con === ese 0 caería al default «0».');
    echoTerminal(' ');

    //──── 14. has_global_asset: el índice 0 es FALSY (#091, tanda B) ────────────────────
    echoTerminal('[14/18] has_global_asset() no encuentra lo que no está, y su índice 0 es FALSY');

    //EL BANCO ES LA CONFIGURACIÓN: se fotografía y se repone.
    $assetsAntes = get_config('global_assets');

    try {

        $check(has_global_asset('zz-prueba-bp-no-esta.js', 'js') === false, 'un asset que no está da false, ESTRICTO');
        $check(has_global_asset('zz-prueba-bp-no-esta.js', 'tipo-que-no-existe') === false, 'un tipo que no existe da false: la cadena sin else no inventa una lista');
        $check(add_global_asset('zz-prueba-bp.js', 'js') === true, 'DISCRIMINANTE: add_global_asset() añade uno nuevo y devuelve true');
        $indice = has_global_asset('zz-prueba-bp.js', 'js');
        $check(is_int($indice) && $indice > 0, 'DISCRIMINANTE: y has_global_asset() lo encuentra devolviendo su índice', 'índice ' . var_export($indice, true));
        $check(add_global_asset('zz-prueba-bp.js', 'js') === true, 'añadir el que ya está también devuelve true: es idempotente');
        $check(add_global_asset('', 'js') === false, 'RECHAZO: un asset vacío no se añade');
        $check(add_global_asset('zz-prueba-bp2.js', 'tipo-que-no-existe') === false, 'RECHAZO: un tipo que no existe no se añade');

        //CONTRATO: el PRIMER asset de un tipo tiene índice 0, que es FALSY. Un `if (!has_global_asset(...))` lo leería como ausente.
        $assets = get_config('global_assets');
        $primero = is_array($assets) && isset($assets['js'][0]) && is_string($assets['js'][0]) ? $assets['js'][0] : null;
        if ($primero !== null) {
            $indicePrimero = has_global_asset($primero, 'js');
            $check($indicePrimero === 0 && $indicePrimero !== false,
                'CONTRATO: el primer asset devuelve 0, que NO es false pero SÍ es falsy',
                'Quien escriba `if (!has_global_asset($a, $t))` tratará el primer asset como ausente y lo volverá a añadir.');
        } else {
            $check(false, 'el árbol da un primer asset js con el que medir el índice 0', 'NO HAY NINGUNO: sin él, lo del índice 0 no se puede comprobar');
        }

    } finally {
        set_config('global_assets', $assetsAntes);
    }

    $check(has_global_asset('zz-prueba-bp.js', 'js') === false, 'la configuración de assets queda como estaba al acabar');
    echoTerminal(' ');

    //──── 15. RouteAdapter: el controlador y el nombre (#091, tanda B) ───────────────────
    echoTerminal('[15/18] RouteAdapter RECHAZA un controlador que no es string ni callable');

    /** @var array<int, array{0: string, 1: mixed}> $controladoresMalos */
    $controladoresMalos = [['un entero', 42], ['un array', []]];
    foreach ($controladoresMalos as [$que, $controlador]) {
        $lanzo = false;
        try {
            //La firma pide string o callable: se le pasa lo que prohíbe, y lo que se comprueba es el TypeError en ejecución.
            new \PiecesPHP\Core\Routing\RouteAdapter('/zz-prueba-bp[/]', $controlador, 'zz-prueba-bp-ra');
        } catch (\Throwable $e) {
            $lanzo = $e instanceof \TypeError;
        }
        $check($lanzo, "RECHAZO: {$que} como controlador lanza TypeError");
    }
    $conNombre = new \PiecesPHP\Core\Routing\RouteAdapter('/zz-prueba-bp[/]', 'Clase:metodo', 'zz-prueba-bp-ra');
    $check($conNombre->name() === 'zz-prueba-bp-ra' && $conNombre->controller() === 'Clase:metodo', 'DISCRIMINANTE: con un controlador string y su nombre, los conserva, ESTRICTO');
    $callable = new \PiecesPHP\Core\Routing\RouteAdapter('/zz-prueba-bp[/]', 'strlen', 'zz-prueba-bp-ra2');
    $check(is_callable($callable->controller()), 'DISCRIMINANTE: un callable también vale');

    //CONTRATO: `$name == null` es TRUE para la cadena vacía en PHP 8, así que un nombre vacío se vuelve un uniqid().
    $sinNombre = new \PiecesPHP\Core\Routing\RouteAdapter('/zz-prueba-bp[/]', 'Clase:metodo', '');
    $nombreGenerado = $sinNombre->name();
    $check(is_string($nombreGenerado) && $nombreGenerado !== '' && preg_match('/^[0-9a-f]{13}$/', $nombreGenerado) === 1,
        'CONTRATO: un nombre VACÍO se sustituye en silencio por un uniqid()',
        'El nombre de la ruta ES el identificador de permiso: con un uniqid, ningún rol lo tiene concedido. Cierra, pero sin avisar.');
    echoTerminal(' ');

    //──── 16. MenuGroup::isCurrent (#091, tanda B) ──────────────────────────────────────
    echoTerminal('[16/18] isCurrent() no marca como actual una página que no lo es');

    $serverAntes = $_SERVER;

    try {

        $_SERVER['HTTP_HOST'] = 'zz-prueba.local';
        $_SERVER['REQUEST_URI'] = '/zona/pagina/';
        unset($_SERVER['HTTPS']);

        $distinto = new \PiecesPHP\Core\Menu\MenuGroup(['name' => 'zz-distinto', 'href' => 'http://zz-prueba.local/otra/cosa']);
        $check($distinto->isCurrent() === false, 'RECHAZO: un href que no es la URL actual da false, ESTRICTO');
        $igual = new \PiecesPHP\Core\Menu\MenuGroup(['name' => 'zz-igual', 'href' => 'http://zz-prueba.local/zona/pagina']);
        $check($igual->isCurrent() === true, 'DISCRIMINANTE: el href de la URL actual da true, ESTRICTO (la barra final no cuenta)');
        $conAncla = new \PiecesPHP\Core\Menu\MenuGroup(['name' => 'zz-ancla', 'href' => 'http://zz-prueba.local/zona/pagina/#seccion']);
        $check($conAncla->isCurrent() === true, 'DISCRIMINANTE: el ancla se recorta antes de comparar');
        $padre = new \PiecesPHP\Core\Menu\MenuGroup(['name' => 'zz-padre', 'href' => 'http://zz-prueba.local/otra/cosa', 'groups' => [
            new \PiecesPHP\Core\Menu\MenuGroup(['name' => 'zz-hijo', 'href' => 'http://zz-prueba.local/zona/pagina']),
        ]]);
        $check($padre->isCurrent() === true, 'DISCRIMINANTE: un padre cuyo hijo es el actual también es actual');
        //CONTRATO: la opción `current` manda sobre la URL, sin comparar nada.
        $forzado = new \PiecesPHP\Core\Menu\MenuGroup(['name' => 'zz-forzado', 'href' => 'http://zz-prueba.local/otra/cosa', 'current' => true]);
        $check($forzado->isCurrent() === true, 'CONTRATO: con la opción current en true, se marca actual aunque el href sea otro');

    } finally {
        $_SERVER = $serverAntes;
    }

    echoTerminal(' ');

    //──── 17. register_route: nombres repetidos y roles inexistentes (#091, tanda B) ─────
    echoTerminal('[17/18] register_route() RECHAZA un nombre repetido y no concede a un rol que no existe');

    //Un router de pega: register_route solo le pide map(), y a lo devuelto setName() y add(). Un RouteCollectorProxy de
    //verdad pediría la app de Slim, así que el objeto viaja como mixed y lo mapeado se anota fuera.
    $mapeadas = [];
    $anotaMapeada = static function (string $patron) use (&$mapeadas): void {
        $mapeadas[] = $patron;
    };
    $routerObjeto = new class ($anotaMapeada) {
        /** @var callable(string): void */
        private $anota;
        /** @param callable(string): void $anota */
        public function __construct(callable $anota)
        {
            $this->anota = $anota;
        }
        /** @param string[] $metodos */
        public function map(array $metodos, string $patron, $controlador): object
        {
            ($this->anota)($patron);
            return new class () {
                public function setName(string $nombre): object
                {
                    return $this;
                }
                public function add($middleware): object
                {
                    return $this;
                }
            };
        }
    };
    /** @var mixed $routerFalso */
    $routerFalso = $routerObjeto;
    $rutasAntes = get_config('_routes_');

    try {

        $definicion = [
            'route' => '/zz-prueba-bp-registro[/]',
            'controller' => 'Clase:metodo',
            'name' => 'zz-prueba-bp-registro',
            'method' => 'GET',
            'require_login' => false,
            'roles_allowed' => [],
            'parameters' => [],
            'middlewares' => [],
        ];
        //@phpstan-ignore-next-line El tipo declarado de $route no contempla los bools y arrays de una definición real.
        register_route($definicion, $routerFalso);
        $rutasTras = get_config('_routes_');
        $check(is_array($rutasTras) && array_key_exists('zz-prueba-bp-registro', $rutasTras), 'DISCRIMINANTE: una ruta nueva queda registrada en el inventario');
        $check(in_array('/zz-prueba-bp-registro[/]', $mapeadas, true), 'DISCRIMINANTE: y se mapea en el router');

        $lanzoDuplicada = false;
        try {
            //@phpstan-ignore-next-line Igual que arriba: el tipo declarado de $route no contempla la definición real.
            register_route($definicion, $routerFalso);
        } catch (\Throwable $e) {
            $lanzoDuplicada = $e instanceof \PiecesPHP\Core\Exceptions\RouteDuplicateNameException;
        }
        $check($lanzoDuplicada, 'RECHAZO: repetir el nombre lanza RouteDuplicateNameException, no sobrescribe la ruta anterior');

        //Los roles se filtran por roleExists(): uno que no existe no recibe el permiso.
        $definicionRoles = $definicion;
        $definicionRoles['name'] = 'zz-prueba-bp-registro2';
        $definicionRoles['route'] = '/zz-prueba-bp-registro2[/]';
        $definicionRoles['roles_allowed'] = [987654];
        //@phpstan-ignore-next-line Igual que arriba.
        register_route($definicionRoles, $routerFalso);
        $rutasConRoles = get_config('_routes_');
        $rolesGuardados = $rutasConRoles['zz-prueba-bp-registro2']['roles_allowed'] ?? null;
        $check(is_array($rolesGuardados) && count($rolesGuardados) === 0, 'RECHAZO: un rol que no existe se filtra y la ruta queda sin roles', json_encode($rolesGuardados, JSON_THROW_ON_ERROR));

    } finally {
        set_config('_routes_', $rutasAntes);
    }

    $rutasRepuestas = get_config('_routes_');
    $check(is_array($rutasRepuestas) && !array_key_exists('zz-prueba-bp-registro', $rutasRepuestas), 'el inventario de rutas queda como estaba al acabar');
    echoTerminal(' ');

    //──── 18. processFromQuery NO es una guarda (#091, tanda B) ──────────────────────────
    echoTerminal('[18/18] processFromQuery(): sus comparaciones laxas deciden el ORDEN, no el acceso');

    //Sus laxas (1089, 1092 y 1125) deciden la dirección del orden, no el acceso: no hay rechazo que probar.
    //Lo que sí decide acceso ahí —el buscador y el HAVING por marcador— lo prueba UnitTest-SqlPlaceholders.
    $codigo = (string) file_get_contents(basepath('app/core/psr4/PiecesPHP/Core/Utilities/Helpers/DataTablesHelper.php'));
    $check(str_contains($codigo, "trim(mb_strtoupper(\$direction_ordering)) == 'ASC' ? 'ASC' : 'DESC'"),
        'CONTRATO: la dirección se normaliza a ASC o DESC, así que un valor raro NO entra en el SQL',
        'Es lo único que decide esa laxa. Si alguien la cambiara por interpolar la dirección, esto se cae.');
    $check(str_contains($codigo, 'const INGNORE'), 'y la columna marcada INGNORE se ordena en PHP, no en SQL');
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:AccessGuards] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Las guardas de acceso rechazan lo que deben ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NONE])->register();
