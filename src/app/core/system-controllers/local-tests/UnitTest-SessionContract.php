<?php

//P46 tramo 0: el contrato de sesión por el lado que SÍ funciona. core/session-user fija qué pasa sin sesión; esta fija
//el viaje del token, la caducidad, el aud y la expulsión. Solo lee: no escribe en la base ni cambia configuración.

use PiecesPHP\UserSystem\ORM\UsersModel;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\TerminalData;

CliActions::make('unit-tests:core/session-contract', function ($args) {

    echoTerminal("\e[33m[TEST:SessionContract] El viaje del token, su firma, su caducidad y su aud\e[39m");
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

    $cabecera = 'HTTP_' . mb_strtoupper(SessionToken::TOKEN_NAME);
    $cabeceraPrevia = $_SERVER[$cabecera] ?? null;
    $cookiePrevia = $_COOKIE[SessionToken::TOKEN_NAME] ?? null;
    $remotaPrevia = $_SERVER['REMOTE_ADDR'] ?? null;
    $nombrePrevio = get_config(SessionToken::TOKEN_NAME_CONFIG);

    try {

        //─── a · El terreno de la terminal ──────────────────────────────────────────────────────────────
        echoTerminal('[a] Qué hay en la terminal cuando corre una suite');
        $enTerminal = TerminalData::getInstance()->isTerminal() === true;
        $tokenRecibido = SessionToken::getJWTReceived();
        $check($enTerminal, 'a1 la suite corre en modo terminal');
        echoTerminal('   INFO: cabecera ' . $cabecera . ' ' . ($cabeceraPrevia === null ? 'AUSENTE' : 'presente (' . mb_strlen($cabeceraPrevia) . ' bytes)'));
        echoTerminal('   INFO: cookie ' . SessionToken::TOKEN_NAME . ' ' . ($cookiePrevia === null ? 'AUSENTE' : 'presente'));
        echoTerminal('   INFO: getJWTReceived() devuelve ' . ($tokenRecibido === '' ? 'CADENA VACÍA' : mb_strlen($tokenRecibido) . ' bytes'));
        echoTerminal('   INFO: get_config(current_user) es ' . gettype(get_config('current_user')) . ', getLoggedFrameworkUser() ' . (getLoggedFrameworkUser() === null ? 'NULL' : 'paquete'));
        echoTerminal('   INFO: $_SERVER[REMOTE_ADDR] ' . ($remotaPrevia === null ? 'AUSENTE' : $remotaPrevia));
        echoTerminal(' ');

        //─── b · El viaje del token ─────────────────────────────────────────────────────────────────────
        echoTerminal('[b] Emitir, recibir por cabecera y por cookie');
        $token = SessionToken::generateToken(['id' => 1], null, null, false);
        $check(is_string($token) && mb_strlen($token) > 0 && substr_count($token, '.') === 2, 'b1 generateToken() devuelve un JWT de tres partes', mb_substr($token, 0, 24) . '…');
        $check(SessionToken::isActiveSession($token) === true, 'b2 y su propia validación lo acepta');
        $datos = BaseToken::getData($token);
        $check(is_object($datos) && (int) ($datos->id ?? 0) === 1, 'b3 y dentro viaja el id que se le puso', (string) json_encode($datos));

        unset($_SERVER[$cabecera], $_COOKIE[SessionToken::TOKEN_NAME]);
        $check(SessionToken::getJWTReceived() === '', 'b4 sin cabecera ni cookie no hay token que recibir');

        $_SERVER[$cabecera] = $token;
        $check(SessionToken::getJWTReceived() === $token, 'b5 por la CABECERA ' . $cabecera . ' se recibe');

        unset($_SERVER[$cabecera]);
        $_COOKIE[SessionToken::TOKEN_NAME] = $token;
        $check(SessionToken::getJWTReceived() === $token, 'b6 por la COOKIE ' . SessionToken::TOKEN_NAME . ' también');

        //Los dos a la vez, con valores distintos: se mide cuál manda, no se supone.
        $otro = SessionToken::generateToken(['id' => 2], null, null, false);
        $_SERVER[$cabecera] = $otro;
        $recibidoConAmbos = SessionToken::getJWTReceived();
        $check($recibidoConAmbos === $otro, 'b7 con cabecera Y cookie, manda la cabecera', $recibidoConAmbos === $token ? 'mandó la cookie' : 'mandó la cabecera');
        unset($_SERVER[$cabecera], $_COOKIE[SessionToken::TOKEN_NAME]);
        echoTerminal(' ');

        //─── c · La firma ───────────────────────────────────────────────────────────────────────────────
        echoTerminal('[c] Un token tocado no vale');
        $partes = explode('.', $token);
        $firmaTocada = $partes[0] . '.' . $partes[1] . '.' . strrev($partes[2]);
        $check(SessionToken::isActiveSession($firmaTocada) === false, 'c1 con la firma del revés, no vale');
        $cargaTocada = $partes[0] . '.' . strtr($partes[1], ['a' => 'b', 'A' => 'B']) . '.' . $partes[2];
        $check($cargaTocada === $token || SessionToken::isActiveSession($cargaTocada) === false, 'c2 con la carga cambiada, tampoco');
        $check(SessionToken::isActiveSession($token . 'x') === false, 'c3 ni con un carácter de más');
        $check(BaseToken::check($firmaTocada) !== true, 'c4 y BaseToken::check() no devuelve true, que es lo que se compara', var_export(BaseToken::check($firmaTocada), true));
        echoTerminal(' ');

        //─── d · La caducidad ───────────────────────────────────────────────────────────────────────────
        echoTerminal('[d] La caducidad');
        $caducado = SessionToken::generateToken(['id' => 1], null, -60, false);
        $check(SessionToken::isActiveSession($caducado) === false, 'd1 un token que expiró hace un minuto no vale');
        $check(BaseToken::check($caducado) === BaseToken::EXPIRED_TOKEN, 'd2 y el motivo que da es EXPIRED_TOKEN, no otro', var_export(BaseToken::check($caducado), true));
        $vivo = SessionToken::generateToken(['id' => 1], null, 3600, false);
        $check(SessionToken::isActiveSession($vivo) === true, 'd3 uno que expira en una hora sí vale');
        echoTerminal(' ');

        //─── e · El aud ─────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[e] El aud: el token atado al cliente que lo pidió');
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $conAud = null;
        $errorAud = null;
        try {
            $conAud = SessionToken::generateToken(['id' => 1], null, null, true);
        } catch (\Throwable $e) {
            $errorAud = $e;
        }
        $check($errorAud === null && is_string($conAud), 'e1 con REMOTE_ADDR puesta, se puede emitir un token con aud', $errorAud !== null ? get_class($errorAud) . ': ' . mb_substr($errorAud->getMessage(), 0, 120) : '');
        $check(is_string($conAud) && SessionToken::isActiveSession($conAud) === true, 'e2 y vale desde el mismo cliente');
        $_SERVER['REMOTE_ADDR'] = '10.0.0.99';
        $check(is_string($conAud) && SessionToken::isActiveSession($conAud) === false, 'e3 desde otra IP, NO vale');
        $check(is_string($conAud) && BaseToken::check($conAud) === BaseToken::INVALID_USER_LOGGIN, 'e4 y el motivo es INVALID_USER_LOGGIN', is_string($conAud) ? var_export(BaseToken::check($conAud), true) : '');
        $sinAud = SessionToken::generateToken(['id' => 1], null, null, false);
        $check(SessionToken::isActiveSession($sinAud) === true, 'e5 uno emitido SIN aud sobrevive al cambio de IP');
        echoTerminal(' ');

        //─── f · La expulsión ───────────────────────────────────────────────────────────────────────────
        echoTerminal('[f] La expulsión: lo que index.php usa para echar al usuario inactivo');
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $antesDeExpulsar = SessionToken::generateToken(['id' => 1], null, null, false);
        $check(SessionToken::isActiveSession($antesDeExpulsar) === true, 'f1 el token vale antes de expulsar');
        SessionToken::setMinimumDateCreated(new \DateTime('+1 second'));
        $check(SessionToken::isActiveSession($antesDeExpulsar) === false, 'f2 tras setMinimumDateCreated, el MISMO token deja de valer aunque su firma siga bien');
        $check(BaseToken::check($antesDeExpulsar) === true, 'f3 y la firma sigue siendo válida: lo que lo tumba es la fecha mínima, no el token');
        SessionToken::setMinimumDateCreated(new \DateTime(SessionToken::DEFAULT_MINIMUM_DATE_CREATED));
        $check(SessionToken::isActiveSession($antesDeExpulsar) === true, 'f4 repuesta la fecha mínima por defecto, vuelve a valer');
        $check(UsersModel::STATUSES_INACTIVE_EQUIVALENT === [UsersModel::STATUS_USER_INACTIVE, UsersModel::STATUS_USER_DELETED], 'f5 los estados que provocan la expulsión son inactivo y borrado', (string) json_encode(UsersModel::STATUSES_INACTIVE_EQUIVALENT));

        echoTerminal(' ');

        //─── g · Sin dirección del cliente, y sin fecha utilizable ──────────────────────────────────────
        echoTerminal('[g] Los dos bordes: sin REMOTE_ADDR y sin fecha de creación');
        //En terminal NO hay REMOTE_ADDR. Antes, calcular el aud reventaba con un aviso; el token de root de
        //index.php se emite con aud, así que de esa línea dependían TODAS las tareas del sistema de rutas.
        unset($_SERVER['REMOTE_ADDR']);
        $sinIP = null;
        $errorSinIP = null;
        try {
            $sinIP = SessionToken::generateToken(['id' => 1], null, null, true);
        } catch (\Throwable $e) {
            $errorSinIP = $e;
        }
        $check($errorSinIP === null && is_string($sinIP), 'g1 sin REMOTE_ADDR, emitir con aud no lanza', $errorSinIP !== null ? get_class($errorSinIP) . ': ' . mb_substr($errorSinIP->getMessage(), 0, 120) : '');
        $check(is_string($sinIP) && SessionToken::isActiveSession($sinIP) === true, 'g1 y el token vale: el aud se calcula igual al emitir y al validar');

        //Con la IP puesta sigue valiendo lo que valía, y un token emitido sin ella no vale desde una con ella:
        //el defecto por defecto no es un comodín.
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $conIP = SessionToken::generateToken(['id' => 1], null, null, true);
        $check(SessionToken::isActiveSession($conIP) === true, 'g2 con REMOTE_ADDR puesta, sigue valiendo como antes');
        $check(is_string($sinIP) && SessionToken::isActiveSession($sinIP) === false, 'g2 y el emitido sin ella NO vale desde una con ella: 0.0.0.0 no es un comodín');
        unset($_SERVER['REMOTE_ADDR']);

        //La fecha de creación: `getCreated()` puede devolver un código de error o null. Con un código, `date()`
        //lanzaba TypeError; con null tomaba la hora actual y el token pasaba por recién creado.
        $bueno = SessionToken::generateToken(['id' => 1], null, null, false);
        $check(is_int(BaseToken::getCreated($bueno)), 'g3 de un token bueno, la fecha de creación llega como entero', gettype(BaseToken::getCreated($bueno)));
        //De un CADUCADO devuelve un código: es la forma REAL en que `getCreated()` no da una fecha. Un token firmado
        //y sin `iat` no se fabrica por la API pública, así que ese otro camino solo lo abre la provocación.
        $caducadoG = SessionToken::generateToken(['id' => 1], null, -60, false);
        $fechaCaducado = BaseToken::getCreated($caducadoG);
        $check(!is_int($fechaCaducado), 'g3 de un token caducado, la fecha de creación NO es un entero: es un código', gettype($fechaCaducado) . ' ' . var_export($fechaCaducado, true));
        $errorFecha = null;
        $resultadoCaducado = null;
        try {
            $resultadoCaducado = SessionToken::isActiveSession($caducadoG);
        } catch (\Throwable $e) {
            $errorFecha = $e;
        }
        $check($errorFecha === null, 'g3 y isActiveSession() con él NO lanza', $errorFecha !== null ? get_class($errorFecha) . ': ' . mb_substr($errorFecha->getMessage(), 0, 140) : '');
        $check($resultadoCaducado === false, 'g3 y NO lo da por válido', var_export($resultadoCaducado, true));
        $check(SessionToken::isActiveSession($bueno) === true, 'g4 el contrato no cambió: con un token bueno sigue devolviendo true');
        $check(SessionToken::isActiveSession('') === false, 'g4 y con basura sigue devolviendo false');

        echoTerminal(' ');

        //─── h · El nombre de la sesión, configurable ───────────────────────────────────────────────────
        echoTerminal('[h] El nombre de la sesión sale de la configuración, con JWTAuth por defecto');
        set_config(SessionToken::TOKEN_NAME_CONFIG, null);
        $check(SessionToken::tokenName() === 'JWTAuth', 'h1 sin configurar nada, el nombre es JWTAuth', SessionToken::tokenName());
        $check(SessionToken::tokenName() === SessionToken::TOKEN_NAME, 'h1 y es el de la constante, que queda como el de por defecto');
        $tokenH = SessionToken::generateToken(['id' => 1], null, null, false);
        unset($_SERVER['HTTP_JWTAUTH'], $_COOKIE['JWTAuth']);
        $_COOKIE['JWTAuth'] = $tokenH;
        $check(SessionToken::getJWTReceived() === $tokenH, 'h1 y todo sigue funcionando como antes');
        unset($_COOKIE['JWTAuth']);

        set_config(SessionToken::TOKEN_NAME_CONFIG, 'zzSesionPrueba');
        $check(SessionToken::tokenName() === 'zzSesionPrueba', 'h2 configurado, el nombre es el configurado', SessionToken::tokenName());
        $_SERVER['HTTP_ZZSESIONPRUEBA'] = $tokenH;
        $check(SessionToken::getJWTReceived() === $tokenH, 'h2 y el token se recibe por la cabecera con ESE nombre');
        unset($_SERVER['HTTP_ZZSESIONPRUEBA']);
        $_COOKIE['zzSesionPrueba'] = $tokenH;
        $check(SessionToken::getJWTReceived() === $tokenH, 'h3 y por la cookie con ESE nombre');
        unset($_COOKIE['zzSesionPrueba']);

        $_SERVER['HTTP_JWTAUTH'] = $tokenH;
        $_COOKIE['JWTAuth'] = $tokenH;
        $check(SessionToken::getJWTReceived() === '', 'h4 con otro nombre configurado, el viejo JWTAuth ya NO se acepta', SessionToken::getJWTReceived() === '' ? '' : 'se aceptó');
        unset($_SERVER['HTTP_JWTAUTH'], $_COOKIE['JWTAuth']);

        //El guion se rechaza A PROPÓSITO: PHP lo convierte en guion bajo al pasar una cabecera a $_SERVER, y el
        //nombre se recibiría por la cookie y no por la cabecera, en silencio.
        foreach (['con-guion', 'con espacio', 'con;punto', '', str_repeat('z', 65)] as $malo) {
            set_config(SessionToken::TOKEN_NAME_CONFIG, $malo);
            $check(SessionToken::tokenName() === SessionToken::TOKEN_NAME, 'h5 «' . mb_substr($malo, 0, 16) . '» no vale y cae al de por defecto', SessionToken::tokenName());
        }
        $check(SessionToken::tokenNameIsValid('JWTAuth') && SessionToken::tokenNameIsValid('zz_Sesion_1') && !SessionToken::tokenNameIsValid('con-guion'), 'h5 el criterio: letras, dígitos y guion bajo, hasta 64');
        set_config(SessionToken::TOKEN_NAME_CONFIG, $nombrePrevio);

        echoTerminal(' ');

        //─── i · El token dice QUIÉN, no QUÉ ES ─────────────────────────────────────────────────────────
        echoTerminal('[i] El token identifica; el tipo se lee de la base');
        $emitido = SessionToken::generateToken(['id' => 1], null, null, false);
        $dentro = BaseToken::getData($emitido);
        $check(is_object($dentro) && (int) ($dentro->id ?? 0) === 1, 'i1 un token nuevo lleva el id', (string) json_encode($dentro));
        $check(is_object($dentro) && !isset($dentro->type), 'i1 y NO lleva el tipo', (string) json_encode($dentro));

        //Un token VIEJO: los emitidos antes de este cambio llevan el tipo dentro. Se fabrica uno igual.
        $viejo = SessionToken::generateToken(['id' => 1, 'type' => UsersModel::TYPE_USER_ROOT], null, null, false);
        $dentroViejo = BaseToken::getData($viejo);
        $check(is_object($dentroViejo) && isset($dentroViejo->type), 'i2 el token viejo sí lleva el tipo dentro', (string) json_encode($dentroViejo));
        $check(SessionToken::isActiveSession($viejo) === true, 'i2 y SE SIGUE ACEPTANDO: nadie se queda fuera por actualizar');
        //Y uno viejo con un tipo que ni existe también vale: el tipo del token ya no decide nada.
        $viejoAbsurdo = SessionToken::generateToken(['id' => 1, 'type' => 9999], null, null, false);
        $check(SessionToken::isActiveSession($viejoAbsurdo) === true, 'i2 incluso con un tipo que no existe, el token vale: ese campo ya no manda');

        //i3: la verdad es la FILA. Se le cambia el tipo a un usuario zz en transacción y se comprueba quién manda.
        $pdo = UsersModel::model()::getDb(Config::app_db('default')['db']);
        //RETORNO-IGNORADO: la conexión va en ERRMODE_EXCEPTION; si no pudiera abrir la transacción, lanzaría.
        $pdo->beginTransaction();
        $marca = 'zz-prueba-tipo-' . bin2hex(random_bytes(3));
        $usuarioPrevio = get_config('current_user');
        $guardadoPrevio = get_config('pcsphp_current_user_stored');
        try {
            $zz = new UsersModel();
            $zz->username = $marca;
            $zz->email = $marca . '@example.com';
            $zz->password = password_hash('zz-Clave-T2-1', \PASSWORD_DEFAULT);
            $zz->firstname = 'Zz';
            $zz->secondname = '';
            $zz->firstLastname = 'Prueba';
            $zz->secondLastname = '';
            $zz->type = UsersModel::TYPE_USER_GENERAL;
            $zz->status = UsersModel::STATUS_USER_ACTIVE;
            $zz->failedAttempts = 0;
            $zz->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $zz->createdAt = new \DateTime();
            $zz->modifiedAt = $zz->createdAt;
            $zz->save();
            $idZz = (int) $zz->id;

            //Un token viejo que MIENTE: dice que es root.
            $miente = SessionToken::generateToken(['id' => $idZz, 'type' => UsersModel::TYPE_USER_ROOT], null, null, false);
            $check((int) (BaseToken::getData($miente)->type ?? -1) === UsersModel::TYPE_USER_ROOT, 'i3 el token de la prueba dice que el usuario es root');
            set_config('current_user', (object) ['id' => $idZz]);
            set_config('pcsphp_current_user_stored', null);
            $deLaFila = getLoggedFrameworkUser(true);
            $check($deLaFila !== null && (int) $deLaFila->type === UsersModel::TYPE_USER_GENERAL, 'i3 y el framework lo trata como GENERAL: manda la fila, no el token', $deLaFila !== null ? 'tipo ' . $deLaFila->type : 'sin usuario');

            //Se le cambia el tipo en la base: el framework lo ve al instante, sin tocar el token.
            $zz->type = UsersModel::TYPE_USER_ADMIN_GRAL;
            $zz->update();
            $trasCambio = getLoggedFrameworkUser(true);
            $check($trasCambio !== null && (int) $trasCambio->type === UsersModel::TYPE_USER_ADMIN_GRAL, 'i3 y al cambiarle el tipo en la fila, cambia lo que el framework ve, con el mismo token', $trasCambio !== null ? 'tipo ' . $trasCambio->type : 'sin usuario');
        } finally {
            set_config('current_user', $usuarioPrevio);
            set_config('pcsphp_current_user_stored', $guardadoPrevio);
            if ($pdo->inTransaction()) {
                //RETORNO-IGNORADO: ERRMODE_EXCEPTION; y la comprobación de abajo mira de verdad si quedó algo.
                $pdo->rollBack();
            }
        }
        $restos = UsersModel::model();
        $restos->resetAll();
        $restos->select()->where(new WhereSegment([WhereItem::like('username', 'zz-prueba-tipo-%')]))->execute();
        $check(count((array) $restos->result()) === 0, 'i3 la transacción se revirtió: 0 usuarios de la prueba');

        $check(SessionToken::isActiveSession($emitido) === true && SessionToken::isActiveSession('') === false, 'i4 el contrato de isActiveSession() no cambió');

        //i5: lo que emite EL FRAMEWORK, que son tres llamadas; el token de i1 lo fabrica esta suite.
        //MÉTODO: de cada llamada a generateToken( en esos dos archivos, se mira su primer array hasta el «]».
        $emisores = ['index.php' => basepath('index.php'), 'UsersController.php' => basepath('app/classes/PiecesPHP/UserSystem/Controllers/UsersController.php')];
        $conTipo = [];
        $llamadas = 0;
        foreach ($emisores as $nombre => $ruta) {
            $codigo = is_file($ruta) ? (string) file_get_contents($ruta) : '';
            $desde = 0;
            while (($pos = mb_strpos($codigo, 'SessionToken::generateToken(', $desde)) !== false) {
                $llamadas++;
                $cierre = mb_strpos($codigo, ']', $pos);
                $carga = $cierre !== false ? mb_substr($codigo, $pos, $cierre - $pos) : '';
                if (str_contains($carga, "'type'")) {
                    $conTipo[] = $nombre;
                }
                $desde = $pos + 1;
            }
        }
        $check($llamadas === 3, 'i5 se encontraron las TRES llamadas que emiten token', "llamadas: {$llamadas}");
        $check(count($conTipo) === 0, 'i5 y ninguna mete el tipo en la carga del token', count($conTipo) > 0 ? implode(', ', $conTipo) : '');

        //─── j · La sesión aislada, la gemela ───────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[j] SessionTokenIsolated: el mecanismo para sesiones propias de quien clona');
        $aislada = new \PiecesPHP\Core\SessionTokenIsolated('zzSesionAislada');
        $tokenAislado = $aislada->generateToken(['dato' => 'zz'], 3600, false);
        $_SERVER['HTTP_ZZSESIONAISLADA'] = $tokenAislado;
        $check($aislada->isActiveSession() === true, 'j1 su token vale, y lo recibe por el nombre que se le pasó al construirla');
        $caducadoAislado = $aislada->generateToken(['dato' => 'zz'], -60, false);
        $_SERVER['HTTP_ZZSESIONAISLADA'] = $caducadoAislado;
        $errorAislado = null;
        $resultadoAislado = null;
        try {
            $resultadoAislado = $aislada->isActiveSession();
        } catch (\Throwable $e) {
            $errorAislado = $e;
        }
        $check($errorAislado === null, 'j2 con un token caducado NO lanza: la fecha inutilizable ya no llega a date()', $errorAislado !== null ? get_class($errorAislado) . ': ' . mb_substr($errorAislado->getMessage(), 0, 120) : '');
        $check($resultadoAislado === false, 'j2 y no lo da por válido', var_export($resultadoAislado, true));
        unset($_SERVER['HTTP_ZZSESIONAISLADA']);

        echoTerminal(' ');

        //─── k · Lo que el login manda al navegador ─────────────────────────────────────────────────────
        echoTerminal('[k] Al navegador solo viajan los campos declarados');
        //Se llama al login de verdad, con un usuario zz en transacción, y se miran las claves que devuelve.
        $pdoK = UsersModel::model()::getDb(Config::app_db('default')['db']);
        //RETORNO-IGNORADO: la conexión va en ERRMODE_EXCEPTION; si no pudiera abrir la transacción, lanzaría.
        $pdoK->beginTransaction();
        $marcaK = 'zz-prueba-login-' . bin2hex(random_bytes(3));
        $claveK = 'zz-Clave-K-1';
        try {
            $zzK = new UsersModel();
            $zzK->username = $marcaK;
            $zzK->email = $marcaK . '@example.com';
            $zzK->password = password_hash($claveK, \PASSWORD_DEFAULT);
            $zzK->firstname = 'Zz';
            $zzK->secondname = '';
            $zzK->firstLastname = 'Prueba';
            $zzK->secondLastname = '';
            $zzK->type = UsersModel::TYPE_USER_GENERAL;
            $zzK->status = UsersModel::STATUS_USER_ACTIVE;
            $zzK->failedAttempts = 0;
            $zzK->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $zzK->createdAt = new \DateTime();
            $zzK->modifiedAt = $zzK->createdAt;
            $zzK->save();

            $peticionK = (new \PiecesPHP\Core\Routing\RequestRoute('POST', (new \Slim\Psr7\Factory\UriFactory())->createUri('http://localhost/zz-login'), new \Slim\Psr7\Headers(), [], [], (new \Slim\Psr7\Factory\StreamFactory())->createStream('')))
                ->withParsedBody(['username' => $marcaK, 'password' => $claveK, 'overwriteSession' => true]);
            $respuestaK = (new \PiecesPHP\UserSystem\Controllers\UsersController())->login($peticionK, new \PiecesPHP\Core\Routing\ResponseRoute());
            $cuerpoK = json_decode((string) $respuestaK->getBody(), true);
            $paquete = is_array($cuerpoK) && isset($cuerpoK['userData']) && is_array($cuerpoK['userData']) ? $cuerpoK['userData'] : [];
            $claves = array_keys($paquete);
            sort($claves);
            $esperadas = array_merge(\PiecesPHP\UserSystem\Controllers\UsersController::LOGIN_USER_DATA_FIELDS, ['misc']);
            sort($esperadas);

            $check(($cuerpoK['auth'] ?? null) === true, 'k1 el login del usuario de prueba responde que sí', mb_substr((string) $respuestaK->getBody(), 0, 120));
            $check($claves === $esperadas, 'k1 y el paquete trae EXACTAMENTE los campos declarados más «misc», ni uno más', (string) json_encode($claves));
            foreach (['failedAttempts', 'status', 'createdAt', 'modifiedAt'] as $retirado) {
                $check(!array_key_exists($retirado, $paquete), "k2 «{$retirado}» NO viaja");
            }
            $check(!array_key_exists('password', $paquete) && !array_key_exists('meta', $paquete), 'k3 password y meta siguen sin viajar');
            //k4: el filtro es por LISTA, no por exclusión. Se comprueba con la fila entera: tiene más campos que el
            //paquete, y los que sobran son exactamente los no declarados. No hace falta tocar la tabla para verlo.
            $deLaFila = array_keys((new UsersModel((int) $zzK->id))->humanReadable());
            $noDeclarados = array_values(array_diff($deLaFila, \PiecesPHP\UserSystem\Controllers\UsersController::LOGIN_USER_DATA_FIELDS));
            $viajanSinDeclarar = array_values(array_intersect($noDeclarados, $claves));
            $check(count($deLaFila) > count(\PiecesPHP\UserSystem\Controllers\UsersController::LOGIN_USER_DATA_FIELDS), 'k4 la fila tiene más campos que la lista', count($deLaFila) . ' contra ' . count(\PiecesPHP\UserSystem\Controllers\UsersController::LOGIN_USER_DATA_FIELDS));
            $check(count($viajanSinDeclarar) === 0, 'k4 y NINGUNO de los no declarados viaja: el filtro es por lista', (string) json_encode($viajanSinDeclarar));
        } finally {
            if ($pdoK->inTransaction()) {
                //RETORNO-IGNORADO: ERRMODE_EXCEPTION; y la comprobación de abajo mira de verdad si quedó algo.
                $pdoK->rollBack();
            }
        }
        $restosK = UsersModel::model();
        $restosK->resetAll();
        $restosK->select()->where(new WhereSegment([WhereItem::like('username', 'zz-prueba-login-%')]))->execute();
        $check(count((array) $restosK->result()) === 0, 'k5 la transacción se revirtió: 0 usuarios de la prueba');
        echoTerminal(' ');

        //─── l · El canal de datos extra, retirado ───────────────────────────────────────────────────────
        echoTerminal('[l] El login no manda datos extra, y el navegador no escribe nada por ese canal');
        //Se mira sobre las claves de PRIMER NIVEL: buscar por subcadena en el cuerpo casaría con cualquier otro sitio.
        //El canal se retiró del login, y sin datos el navegador no escribe: `setItem(clave, null)` guarda "null".
        $clavesCuerpo = array_keys((array) $cuerpoK);
        $check(in_array('auth', $clavesCuerpo, true) && in_array('userData', $clavesCuerpo, true),
            'l1 el paquete del login sigue trayendo «auth» y «userData»: sin esto, lo de abajo pasaría por respuesta vacía',
            (string) json_encode($clavesCuerpo));
        $check(!in_array('extras', $clavesCuerpo, true), 'l1 y NO trae «extras»: el canal se retiró del login');
        $check(!in_array('extraData', $clavesCuerpo, true), 'l1 ni «extraData», que es la que lee el navegador');
        $rutaJs = 'statics/core/js/user-system/PiecesPHPSystemUserHelper.js';
        $paqueteJs = @file_get_contents(basepath($rutaJs));
        $check(is_string($paqueteJs) && $paqueteJs !== '', 'l2 se puede leer ' . $rutaJs, is_string($paqueteJs) ? strlen($paqueteJs) . ' bytes' : 'NO LEÍDO: lo que sigue no probaría nada');
        if (is_string($paqueteJs) && $paqueteJs !== '') {
            //El cuerpo de setJWT() y NO el archivo entero: ese mismo `removeItem` está también en el cierre de sesión,
            //así que buscarlo en todo el archivo daba verde con el arreglo deshecho. Medido al provocar.
            $inicioSetJWT = strpos($paqueteJs, 'setJWT(JWT) {');
            $cuerpoSetJWT = $inicioSetJWT === false ? '' : substr($paqueteJs, $inicioSetJWT, (strpos($paqueteJs, 'getJWT()', $inicioSetJWT) ?: strlen($paqueteJs)) - $inicioSetJWT);
            $check($cuerpoSetJWT !== '', 'l2 se localiza el cuerpo de setJWT()', strlen($cuerpoSetJWT) . ' bytes');
            $check(str_contains($cuerpoSetJWT, 'localStorage.removeItem(PiecesPHPSystemUserHelper.localJWTAuthExtraDataName)'),
                'l2 y sin datos extra RETIRA la clave del almacén en vez de escribir la cadena «null»');
            $check(preg_match('/setItem\(\s*PiecesPHPSystemUserHelper\.localJWTAuthExtraDataName\s*,\s*this\.extraData\s*!==\s*null\s*\?/', $paqueteJs) !== 1,
                'l3 y ya no queda el ternario que pasaba null a setItem()');
        }

        //─── m · La duración de la sesión sale de la configuración ───────────────────────────────
        echoTerminal(' ');
        echoTerminal('[m] La duración sale de la configuración, y una inválida cae al valor por defecto');

        $duracionPrevia = get_config(SessionToken::DURATION_CONFIG);
        try {
            set_config(SessionToken::DURATION_CONFIG, null);
            $check(SessionToken::duration() === SessionToken::DURATION,
                'm1 sin configurar, la de siempre: 31 días', SessionToken::duration() . ' s');

            set_config(SessionToken::DURATION_CONFIG, 3600);
            $check(SessionToken::duration() === 3600, 'm2 configurada, la configurada', SessionToken::duration() . ' s');

            //Y el canario: una duración configurada sigue emitiendo un token que ABRE sesión.
            $configurado = SessionToken::generateToken(['id' => 1], null, null, false);
            $check(SessionToken::isActiveSession($configurado) === true,
                'm3 y el token que emite sigue abriendo sesión');

            //LO QUE DE VERDAD SE MIDE: que el token EMITIDO dure lo configurado. Sin esto, la prueba pasa aunque
            //`generateToken()` siga clavando el literal, porque `duration()` acierta sola. Cazado provocándola.
            $duracionDelToken = function (string $token): ?int {
                $partes = explode('.', $token);
                $carga = isset($partes[1]) ? json_decode((string) base64_decode(strtr($partes[1], '-_', '+/')), true) : null;
                return is_array($carga) && isset($carga['iat'], $carga['exp']) ? (int) $carga['exp'] - (int) $carga['iat'] : null;
            };
            $check($duracionDelToken($configurado) === 3600,
                'm3bis y el token emitido dura lo configurado, no el literal',
                var_export($duracionDelToken($configurado), true) . ' s');

            set_config(SessionToken::DURATION_CONFIG, null);
            $check($duracionDelToken(SessionToken::generateToken(['id' => 1], null, null, false)) === SessionToken::DURATION,
                'm3ter y sin configurar, el token emitido dura 31 días');

            foreach ([0, -1, 'texto', SessionToken::DURATION_MAX + 1, 1.5, true, []] as $malo) {
                set_config(SessionToken::DURATION_CONFIG, $malo);
                $check(SessionToken::duration() === SessionToken::DURATION,
                    'm4 una duración inválida (' . (is_array($malo) ? 'array' : var_export($malo, true)) . ') cae al valor por defecto',
                    SessionToken::duration() . ' s');
            }

            set_config(SessionToken::DURATION_CONFIG, (string) 7200);
            $check(SessionToken::duration() === 7200, 'm5 y un entero escrito como texto vale', SessionToken::duration() . ' s');
        } finally {
            set_config(SessionToken::DURATION_CONFIG, $duracionPrevia);
        }

        //─── n · La marca global sale de la configuración (ADR 0026) ─────────────────────────────
        echoTerminal(' ');
        echoTerminal('[n] La marca global sale de la configuración, y una inválida NO deja el sistema sin marca');

        $marcaPrevia = get_config(SessionToken::MINIMUM_DATE_CONFIG);
        try {
            set_config(SessionToken::MINIMUM_DATE_CONFIG, null);
            $check(SessionToken::minimumDateCreated()->format('Y-m-d H:i:s') === SessionToken::MINIMUM_DATE_DEFAULT,
                'n1 sin configurar, la que estaba escrita en roles.php',
                SessionToken::minimumDateCreated()->format('Y-m-d H:i:s'));

            set_config(SessionToken::MINIMUM_DATE_CONFIG, '2027-05-04 03:02:01');
            $check(SessionToken::minimumDateCreated()->format('Y-m-d H:i:s') === '2027-05-04 03:02:01',
                'n2 configurada, la configurada');

            //Una marca inválida no puede dejar el sistema SIN marca: eso valdría cualquier token viejo.
            foreach (['', '   ', 'texto', '0', '-1', '99999999999999999999', 'ayer por la tarde'] as $mala) {
                set_config(SessionToken::MINIMUM_DATE_CONFIG, $mala);
                $check(SessionToken::minimumDateCreated()->format('Y-m-d H:i:s') === SessionToken::MINIMUM_DATE_DEFAULT,
                    'n3 una marca inválida (' . var_export($mala, true) . ') cae a la de por defecto',
                    SessionToken::minimumDateCreated()->format('Y-m-d H:i:s'));
            }

            //Y el centinela histórico NO es la marca: la sesión aislada congela su valor aparte.
            $check(SessionToken::DEFAULT_MINIMUM_DATE_CREATED !== SessionToken::MINIMUM_DATE_DEFAULT,
                'n4 el centinela de «sin marca» y la marca de por defecto son cosas distintas');
        } finally {
            set_config(SessionToken::MINIMUM_DATE_CONFIG, $marcaPrevia);
        }

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        //TODO lo que se toca es de proceso: se repone, o las suites que corran después heredarían la sesión de esta.
        SessionToken::setMinimumDateCreated(new \DateTime(SessionToken::DEFAULT_MINIMUM_DATE_CREATED));
        set_config(SessionToken::TOKEN_NAME_CONFIG, $nombrePrevio);
        if ($cabeceraPrevia === null) { unset($_SERVER[$cabecera]); } else { $_SERVER[$cabecera] = $cabeceraPrevia; }
        if ($cookiePrevia === null) { unset($_COOKIE[SessionToken::TOKEN_NAME]); } else { $_COOKIE[SessionToken::TOKEN_NAME] = $cookiePrevia; }
        if ($remotaPrevia === null) { unset($_SERVER['REMOTE_ADDR']); } else { $_SERVER['REMOTE_ADDR'] = $remotaPrevia; }
        $check(
            ($_SERVER[$cabecera] ?? null) === $cabeceraPrevia && ($_COOKIE[SessionToken::TOKEN_NAME] ?? null) === $cookiePrevia && ($_SERVER['REMOTE_ADDR'] ?? null) === $remotaPrevia,
            'z1 la cabecera, la cookie y la IP quedan como estaban'
        );
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P46: el contrato de sesión con token válido: viaje por cabecera y cookie, firma, caducidad, aud y expulsión.')->setEffects([CliActions::EFFECT_NONE])->register();
