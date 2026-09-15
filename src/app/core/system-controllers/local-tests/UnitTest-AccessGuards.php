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
    echoTerminal('[1/8]hashVerify() RECHAZA una firma que no es la suya');

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
    echoTerminal('[2/8]verify() rechaza, y su código de error SÍ es truthy');

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
    echoTerminal('[3/8]decode() y check() RECHAZAN un token manipulado');

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
    echoTerminal('[4/8]hasPermissions() niega lo que no está concedido');

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
    echoTerminal('[5/8]get_route_roles_allowed() con un `$type` que no contempla');

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
    echoTerminal('[6/8]Parameter::isValid() nace en `true`, y eso decide qué pasa sin validador');

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
    echoTerminal('[7/8]Las rutas públicas de listado solo devuelven lo publicado sin permiso');

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
    echoTerminal('[8/9]UsersModel::fieldsToSelect() no selecciona la contraseña');

    //Si esto cae, getBy(), all() y los informes de accesos vuelven a mandar el hash en la respuesta.
    $camposUsuarios = (new \ReflectionMethod(\App\Model\UsersModel::class, 'fieldsToSelect'))->invoke(null);
    $conPassword = array_values(array_filter($camposUsuarios, fn ($campo) => is_string($campo) && str_ends_with($campo, '.password')));
    $check(count($camposUsuarios) > 0 && count($conPassword) === 0, 'usuarios: ningún campo de fieldsToSelect() termina en .password',
        count($conPassword) === 0 ? count($camposUsuarios) . ' campos' : 'con password: ' . implode(', ', $conPassword));
    $check(in_array(\App\Model\UsersModel::TABLE . '.username', $camposUsuarios, true), 'DISCRIMINANTE: usuarios, fieldsToSelect() sigue trayendo las demás columnas');
    echoTerminal(' ');

    //──── 9. /users/all/ no devuelve la contraseña (#057) ─────────────────────────────
    echoTerminal('[9/9] UsersController::_all() no devuelve la contraseña');

    //Si esto cae, cualquier usuario con sesión vuelve a poder pedir el hash de todos.
    $filasTodos = \App\Controller\UsersController::_all(1, 5)->elements();
    $clavesDe = static fn ($fila): array => is_object($fila) ? array_keys(get_object_vars($fila)) : (is_array($fila) ? array_keys($fila) : []);
    $conClave = array_values(array_filter($filasTodos, fn ($fila) => in_array('password', $clavesDe($fila), true)));
    $check(count($filasTodos) > 0 && count($conClave) === 0, 'usuarios: ninguna fila de _all() trae la clave password',
        count($filasTodos) === 0 ? 'SIN FILAS: sin usuarios en local no hay veredicto' : count($filasTodos) . ' filas, ' . count($conClave) . ' con password');
    $check(count($filasTodos) > 0 && in_array('username', $clavesDe($filasTodos[0]), true), 'DISCRIMINANTE: usuarios, las filas de _all() siguen trayendo username');
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
