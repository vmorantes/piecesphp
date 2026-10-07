<?php

//La revocación de sesiones (ADR 0026) medida por HTTP: la comprobación vive en `src/index.php` y solo corre al servir.
//Crea usuarios y organizaciones zz-prueba-rev-*, un borrador de publicación y mueve la marca global; lo retira todo y lo comprueba.

use EventsLog\Mappers\LogsMapper;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\SystemApprovalsMiddleware;

CliActions::make('unit-tests:core/session-revocation', function ($args) {

    echoTerminal("\e[33m[TEST:SessionRevocation] Un token revocado deja de valer, y nadie cierra lo que no le toca\e[39m");
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

    //`base_url` en el terminal es `http://localhost` (bootstrap.php fuerza HTTP_HOST): no sirve. Igual que access-without-session.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrixPath = $proyecto . '/files/dev/permissions-matrix.json';
        if (is_file($matrixPath)) {
            $matrix = json_decode((string) file_get_contents($matrixPath), true);
            $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
        }
    }
    $base = rtrim($base, '/');

    //La ruta de cada nombre la resuelve el enrutador, no un literal: si alguien la mueve, la prueba la sigue. Igual que
    //`route-inventory`; en el terminal sale sin host, y si saliera con él se queda solo la ruta.
    $ruta = function (string $nombre, array $parametros = []): ?string {
        $url = get_route($nombre, $parametros, true);
        if (!is_string($url) || $url === '') {
            return null;
        }
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return '/' . ltrim($path, '/');
    };

    $nombres = [
        'propia' => 'my-space-admin-revoke-my-sessions',
        'ajena' => 'users-revoke-sessions-request',
        'protegida' => 'my-space-admin-my-space',
        'acceso' => 'users-form-login',
        'publica' => 'publications-single',
    ];

    //─── Canario ────────────────────────────────────────────────────────────────────────────────
    echoTerminal('[canario] Lo que esta prueba da por hecho');
    $check($base !== '', 'c1 hay una base con la que pedir', 'ni PCSPHP_WALK_BASE ni files/dev/permissions-matrix.json');
    foreach ($nombres as $nombre) {
        $check(is_array(get_route_info($nombre)), "c2 existe la ruta {$nombre}");
    }
    $infoPublica = get_route_info($nombres['publica']);
    $infoProtegida = get_route_info($nombres['protegida']);
    $check(is_array($infoPublica) && ($infoPublica['require_login'] ?? true) === false, "c3 {$nombres['publica']} sigue SIN exigir sesión");
    $check(is_array($infoProtegida) && ($infoProtegida['require_login'] ?? false) === true, "c4 {$nombres['protegida']} sigue exigiendo sesión");
    $check(in_array(UsersModel::TYPE_USER_ADMIN_GRAL, PublicationMapper::CAN_VIEW_DRAFT), 'c5 el administrador general sigue viendo borradores');
    if ($failed > 0) {
        echoTerminal("\e[31m Lo que la prueba da por hecho no se cumple: lo que midiera no significaría nada. \e[39m");
        return $balance();
    }
    echoTerminal(' ');

    $cabeceraToken = SessionToken::tokenName();

    /**
     * @return array{status:int,body:string,location:string,cookies:list<string>,json:mixed}
     */
    $pedir = function (string $metodo, string $path, ?string $jwt = null, array $datos = [], bool $xhr = true) use ($base, $cabeceraToken): array {
        $cabeceras = [];
        if ($jwt !== null) {
            $cabeceras[] = "{$cabeceraToken}: {$jwt}";
        }
        if ($xhr) {
            $cabeceras[] = 'X-Requested-With: XMLHttpRequest';
        }
        $cookies = [];
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            //Sin esto una redirección al acceso se seguiría sola y un 302 se leería como 200.
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $cabeceras,
            CURLOPT_HEADERFUNCTION => function (\CurlHandle $h, string $linea) use (&$cookies): int {
                if (stripos($linea, 'Set-Cookie:') === 0) {
                    $cookies[] = trim(substr($linea, 11));
                }
                return strlen($linea);
            },
        ]);
        if ($metodo === 'POST') {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($datos));
        }
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $location = (string) curl_getinfo($handle, CURLINFO_REDIRECT_URL);
        //Sin curl_close(): deprecado desde PHP 8.5 y sin efecto desde la 8.0, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        return ['status' => $status, 'body' => $body, 'location' => $location, 'cookies' => $cookies, 'json' => json_decode($body, true)];
    };

    //Un token como los del producto: solo el id (`generateToken()` pone la hora de creación).
    $token = fn(int $id): string => SessionToken::generateToken(['id' => $id], null, null, false);
    //DENTRO es que la ruta protegida no lo manda al acceso: un general con el perfil sin completar recibe un 302 a su
    //perfil, y eso también es estar identificado. FUERA es el 302 al acceso.
    $ultimaEntrada = '';
    $acceso = (string) $ruta($nombres['acceso']);
    $entra = function (?string $jwt) use ($pedir, $ruta, $nombres, $acceso, &$ultimaEntrada): string {
        $r = $pedir('GET', (string) $ruta($nombres['protegida']), $jwt, [], false);
        $ultimaEntrada = 'HTTP ' . $r['status'] . ($r['location'] !== '' ? " → {$r['location']}" : '');
        if ($r['status'] === 302 && str_contains($r['location'], $acceso)) {
            return 'FUERA';
        }
        return in_array($r['status'], [200, 302], true) ? 'DENTRO' : "HTTP {$r['status']}";
    };
    $marcaDe = function (int $id): ?string {
        $fila = UsersModel::model();
        $fila->resetAll();
        $fila->select('sessionsValidFrom')->where(['id' => $id])->execute();
        $valor = ((array) $fila->result())[0]->sessionsValidFrom ?? null;
        return is_string($valor) ? $valor : null;
    };
    //Espera a que empiece un segundo nuevo: separa lo que debe nacer DESPUÉS de una marca, que tiene resolución de segundo.
    $siguienteSegundo = function (int $despuesDe): void {
        while (time() <= $despuesDe) {
            usleep(20000);
        }
    };
    $pareceToken = fn(string $texto): bool => preg_match('/eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/', $texto) === 1;

    $prefijo = 'zz-prueba-rev-' . bin2hex(random_bytes(3));
    $usuariosDelPrefijo = function () use ($prefijo): array {
        $m = UsersModel::model();
        $m->resetAll();
        $m->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        return array_values((array) $m->result());
    };
    $crear = function (string $sufijo, int $tipo, int $estado = UsersModel::STATUS_USER_ACTIVE, ?int $organizacion = null) use ($prefijo): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: los tokens los fabrica la prueba. Aleatoria y solo en memoria.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Prueba';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = $estado;
        $u->failedAttempts = 0;
        $u->organization = $organizacion ?? OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        return (int) $u->id;
    };
    //QUIÉN LA CREA IMPORTA: la que firma root o un administrador general queda aprobada sola, y el terminal firma como
    //root. Para una organización SIN aprobar se le presta un general como creador, como en el alta pública.
    $organizacion = function (string $sufijo, int $estado, ?int $creador = null) use ($prefijo): int {
        $usuarioPrevio = get_config('current_user');
        $guardadoPrevio = get_config('pcsphp_current_user_stored');
        try {
            if ($creador !== null) {
                set_config('current_user', (object) ['id' => $creador]);
                set_config('pcsphp_current_user_stored', null);
            }
            $nueva = OrganizationsController::createOrganization(['name' => "{$prefijo}-{$sufijo}", 'nit' => "{$prefijo}-{$sufijo}"]);
        } finally {
            set_config('current_user', $usuarioPrevio);
            set_config('pcsphp_current_user_stored', $guardadoPrevio);
        }
        if ($nueva === null || $nueva->id === null) {
            return 0;
        }
        $o = new OrganizationMapper((int) $nueva->id);
        $o->status = $estado;
        $o->update();
        return (int) $o->id;
    };

    $marcaGlobalExistia = SettingsModel::optionExists(SessionToken::MINIMUM_DATE_CONFIG);
    $marcaGlobalPrevia = SettingsModel::getConfigValue(SessionToken::MINIMUM_DATE_CONFIG);
    $ultimoLog = (function (): int {
        $m = LogsMapper::model();
        $m->resetAll();
        $m->select('MAX(id) AS ultimo')->execute();
        return (int) (((array) $m->result())[0]->ultimo ?? 0);
    })();
    $publicacionID = null;
    $publicacionKID = null;
    $ids = [];

    try {
        $ids['general'] = $crear('general', UsersModel::TYPE_USER_GENERAL);
        $ids['otro'] = $crear('otro', UsersModel::TYPE_USER_GENERAL);
        $ids['admin'] = $crear('admin', UsersModel::TYPE_USER_ADMIN_GRAL);
        $ids['lector'] = $crear('lector', UsersModel::TYPE_USER_ADMIN_GRAL);
        $ids['rootA'] = $crear('root-a', UsersModel::TYPE_USER_ROOT);
        $ids['rootB'] = $crear('root-b', UsersModel::TYPE_USER_ROOT);
        $check(count(array_filter($ids, fn($id) => $id > 0)) === 6, 'banco: los seis primeros usuarios de la prueba creados', (string) json_encode($ids));
        $check($entra($token($ids['general'])) === 'DENTRO', 'banco: un token recién fabricado entra (sin él, todo rechazo sería gratis)', $ultimaEntrada);
        echoTerminal(' ');

        //─── A · El usuario cierra sus sesiones ─────────────────────────────────────────────────
        echoTerminal('[A] Cerrar mis sesiones: el token con el que se pide y uno anterior dejan de valer');
        $anterior = $token($ids['general']);
        $siguienteSegundo(time());
        $actual = $token($ids['general']);
        $check($entra($anterior) === 'DENTRO' && $entra($actual) === 'DENTRO', 'a1 los dos tokens entran antes de cerrar');
        $r = $pedir('POST', (string) $ruta($nombres['propia']), $actual);
        $marca = $marcaDe($ids['general']);
        $check($r['status'] === 200 && ($r['json']['success'] ?? null) === true, 'a2 cerrar mis sesiones responde 200 con éxito', 'HTTP ' . $r['status'] . ' ' . mb_substr($r['body'], 0, 120));
        $check($marca !== null, 'a3 y la marca del usuario queda puesta', 'marca ' . var_export($marca, true));
        $check(!$pareceToken($r['body']) && !$pareceToken(implode("\n", $r['cookies'])), 'a4 la respuesta NO trae un token nuevo, ni en el cuerpo ni en una cookie');
        $check(str_contains((string) ($r['json']['values']['redirect_to'] ?? ''), (string) $ruta($nombres['acceso'])), 'a5 y manda al cliente al acceso', (string) ($r['json']['values']['redirect_to'] ?? 'sin redirect_to'));
        $borraCookie = count(array_filter($r['cookies'], fn($c) => str_starts_with($c, "{$cabeceraToken}=;") || str_starts_with($c, "{$cabeceraToken}=deleted"))) > 0;
        $check($borraCookie, 'a6 y caduca la cookie de sesión', implode(' | ', $r['cookies']));
        $check($entra($actual) === 'FUERA', 'a7 el MISMO token ya no entra: va al acceso', $ultimaEntrada);
        $check($entra($anterior) === 'FUERA', 'a8 el token anterior tampoco');
        $check($pedir('GET', (string) $ruta($nombres['protegida']), $actual)['status'] === 403, 'a9 y una petición de datos con él recibe 403');
        echoTerminal(' ');

        //─── B · Mismo segundo ──────────────────────────────────────────────────────────────────
        echoTerminal('[B] Un token del MISMO segundo que la marca queda revocado; uno del segundo siguiente, no');
        $siguienteSegundo(time());
        $mismo = $token($ids['general']);
        $r = $pedir('POST', (string) $ruta($nombres['propia']), $mismo);
        $marca = (string) $marcaDe($ids['general']);
        $creado = BaseToken::getCreated($mismo);
        $check($r['status'] === 200, 'b1 cerrar con un token recién fabricado responde 200', 'HTTP ' . $r['status']);
        $check(is_int($creado) && $creado === strtotime($marca), 'b2 el token nació en el mismo segundo que la marca', "token {$creado}, marca " . strtotime($marca));
        $check($entra($mismo) === 'FUERA', 'b3 y aun así queda revocado (la comparación es <=)');
        $siguienteSegundo((int) strtotime($marca));
        $despues = $token($ids['general']);
        $check($entra($despues) === 'DENTRO', 'b4 DISCRIMINANTE: un token nacido después de la marca sí entra');
        echoTerminal(' ');

        //─── C · El administrador ───────────────────────────────────────────────────────────────
        echoTerminal('[C] Cerrar las sesiones de otro: códigos de error y la guarda de root');
        $admin = $token($ids['admin']);
        $general = $token($ids['general']);
        $ajena = (string) $ruta($nombres['ajena']);
        $c = $pedir('POST', $ajena, $admin, []);
        $check($c['status'] === 400, 'c1 sin id → 400', 'HTTP ' . $c['status']);
        $c = $pedir('POST', $ajena, $admin, ['id' => 'abc']);
        $check($c['status'] === 400, 'c2 id que no es un entero → 400', 'HTTP ' . $c['status']);
        $c = $pedir('POST', $ajena, $admin, ['id' => '999999999']);
        $check($c['status'] === 404, 'c3 id que no existe → 404', 'HTTP ' . $c['status']);
        $c = $pedir('POST', $ajena, $general, ['id' => (string) $ids['otro']]);
        $check($c['status'] === 403, 'c4 un usuario general → 403, no 200 con success false', 'HTTP ' . $c['status']);
        $check($marcaDe($ids['otro']) === null, 'c5 y la víctima sigue sin marca');
        $c = $pedir('POST', $ajena, $admin, ['id' => (string) $ids['rootB']]);
        $check($c['status'] === 403, 'c6 el administrador general contra un root → 403', 'HTTP ' . $c['status']);
        $check($marcaDe($ids['rootB']) === null, 'c7 y el root sigue sin marca');
        $rootB = $token($ids['rootB']);
        $c = $pedir('POST', $ajena, $token($ids['rootA']), ['id' => (string) $ids['rootB']]);
        $check($c['status'] === 200 && ($c['json']['success'] ?? null) === true, 'c8 root contra root → 200', 'HTTP ' . $c['status'] . ' ' . mb_substr($c['body'], 0, 120));
        $check($marcaDe($ids['rootB']) !== null && $entra($rootB) === 'FUERA', 'c9 y el token del root B deja de valer');
        $otro = $token($ids['otro']);
        $c = $pedir('POST', $ajena, $admin, ['id' => (string) $ids['otro']]);
        $check($c['status'] === 200 && !$pareceToken($c['body']) && !$pareceToken(implode("\n", $c['cookies'])), 'c10 el administrador contra un general → 200, sin token en la respuesta', 'HTTP ' . $c['status']);
        $check($entra($otro) === 'FUERA' && $entra($admin) === 'DENTRO', 'c11 cae el token del general, y el del administrador sigue');
        echoTerminal(' ');

        //─── D · El cuerpo no elige al sujeto ───────────────────────────────────────────────────
        echoTerminal('[D] Cerrar MIS sesiones con el id de otro en el cuerpo solo cierra las mías');
        $siguienteSegundo((int) strtotime((string) $marcaDe($ids['otro'])));
        $otroNuevo = $token($ids['otro']);
        $marcaOtro = $marcaDe($ids['otro']);
        $siguienteSegundo((int) strtotime((string) $marcaDe($ids['general'])));
        $mio = $token($ids['general']);
        $d = $pedir('POST', (string) $ruta($nombres['propia']), $mio, ['id' => (string) $ids['otro']]);
        $check($d['status'] === 200, 'd1 la petición responde 200', 'HTTP ' . $d['status']);
        $check($marcaDe($ids['otro']) === $marcaOtro && $entra($otroNuevo) === 'DENTRO', 'd2 la marca del otro NO se movió y su token sigue entrando', var_export($marcaDe($ids['otro']), true) . ' / ' . var_export($marcaOtro, true));
        $check($entra($mio) === 'FUERA', 'd3 y el que pidió sí quedó fuera');
        echoTerminal(' ');

        //─── E · P78: una ruta sin require_login ────────────────────────────────────────────────
        echoTerminal("[E] P78: con un token revocado, {$nombres['publica']} trata al usuario como visitante");
        //La categoría sale de las categorías, no de una publicación: sin publicaciones en la base, eso daba 0 y la clave foránea fallaba.
        $molde = \Publications\Mappers\PublicationCategoryMapper::model();
        $molde->resetAll();
        $molde->select('id')->execute(false, 1);
        $categoria = (int) (((array) $molde->result())[0]->id ?? \Publications\Mappers\PublicationCategoryMapper::uncategorizedCategory()->id);
        $p = new PublicationMapper();
        $lang = get_config('default_lang');
        $p->baseLang = $lang;
        $p->setLangData($lang, 'title', "{$prefijo} borrador");
        $p->setLangData($lang, 'content', 'Borrador de la prueba de revocación.');
        $p->setLangData($lang, 'seoDescription', '');
        $p->setLangData($lang, 'publicDate', new \DateTime());
        $p->setLangData($lang, 'startDate', null);
        $p->setLangData($lang, 'endDate', null);
        $p->setLangData($lang, 'category', $categoria);
        $p->setLangData($lang, 'visits', 0);
        $p->setLangData($lang, 'author', $ids['lector']);
        $p->setLangData($lang, 'folder', str_replace('.', '', uniqid()));
        $p->setLangData($lang, 'featured', PublicationMapper::UNFEATURED);
        $p->setLangData($lang, 'mainImage', 'statics/images/zz-prueba.jpg');
        $p->setLangData($lang, 'thumbImage', 'statics/images/zz-prueba.jpg');
        $p->setLangData($lang, 'ogImage', '');
        $p->status = PublicationMapper::DRAFT;
        //`save()` exige un usuario en sesión, y el terminal no tiene: se le presta el autor y se devuelve el que había.
        $usuarioPrevio = get_config('current_user');
        $guardadoPrevio = get_config('pcsphp_current_user_stored');
        try {
            set_config('current_user', (object) ['id' => $ids['lector']]);
            set_config('pcsphp_current_user_stored', null);
            $p->save();
        } finally {
            set_config('current_user', $usuarioPrevio);
            set_config('pcsphp_current_user_stored', $guardadoPrevio);
        }
        $publicacionID = $p->id !== null ? (int) $p->id : null;
        $check($publicacionID !== null, 'e0 banco: un borrador de publicación creado', (string) $publicacionID);
        $rutaPublica = (string) $ruta($nombres['publica'], ['slug' => $p->getSlug()]);
        $lector = $token($ids['lector']);
        $e1 = $pedir('GET', $rutaPublica, $lector, [], false);
        $check($e1['status'] === 200, 'e1 DISCRIMINANTE: con sesión válida el administrador ve el borrador', "HTTP {$e1['status']} en {$rutaPublica}");
        $e2 = $pedir('GET', $rutaPublica, null, [], false);
        $check($e2['status'] === 404, 'e2 un visitante no lo ve', 'HTTP ' . $e2['status']);
        $check((new UsersModel())->revokeSessions($ids['lector']), 'e3 banco: se revocan las sesiones del administrador');
        $e4 = $pedir('GET', $rutaPublica, $lector, [], false);
        $check($e4['status'] === 404, 'e4 con su token REVOCADO tampoco: la ruta lo trata como visitante', 'HTTP ' . $e4['status']);
        echoTerminal(' ');

        //─── F · Cerrar sesión con un token revocado ────────────────────────────────────────────
        echoTerminal('[F] Con un token revocado se llega al formulario de acceso (cerrar sesión sigue funcionando)');
        $f1 = $pedir('GET', (string) $ruta($nombres['acceso']), $lector, [], false);
        $check($f1['status'] === 200, 'f1 el formulario de acceso responde 200 con el token revocado', 'HTTP ' . $f1['status'] . ($f1['location'] !== '' ? " → {$f1['location']}" : ''));
        $siguienteSegundo((int) strtotime((string) $marcaDe($ids['lector'])));
        $f2 = $pedir('GET', (string) $ruta($nombres['acceso']), $token($ids['lector']), [], false);
        $check($f2['status'] === 302, 'f2 DISCRIMINANTE: con uno válido, el acceso redirige al panel', 'HTTP ' . $f2['status']);
        echoTerminal(' ');

        //─── P · Los accesos parciales ──────────────────────────────────────────────────────────
        echoTerminal('[P] Accesos parciales: qué estados siguen entrando, cuáles no, y el recorte del no aprobado');
        //`index.php` fija el usuario actual SOLO con la sesión viva: aquí se congela qué estados NO la matan y cuáles sí.
        //Y el recorte del NO APROBADO (`SystemApprovalsMiddleware`), que depende de ese usuario actual.
        $orgPendiente = $organizacion('org-pendiente', OrganizationMapper::PENDING_APPROVAL);
        $orgInactiva = $organizacion('org-inactiva', OrganizationMapper::INACTIVE);
        $orgConJefe = $organizacion('org-jefe', OrganizationMapper::ACTIVE);
        $check($orgPendiente > 0 && $orgInactiva > 0 && $orgConJefe > 0, 'p0 banco: tres organizaciones de la prueba', "{$orgPendiente}, {$orgInactiva}, {$orgConJefe}");
        //Administradores generales, para que la ruta pública (el borrador) distinga identificado de visitante.
        $tipoP = UsersModel::TYPE_USER_ADMIN_GRAL;
        foreach (['pA' => 'p-activo', 'pB' => 'p-por-aprobar', 'pC' => 'p-rechazado', 'pD' => 'p-bloqueado', 'pG' => 'p-inactivo'] as $clave => $sufijo) {
            $ids[$clave] = $crear($sufijo, $tipoP);
        }
        $ids['pE'] = $crear('p-org-pendiente', $tipoP, UsersModel::STATUS_USER_ACTIVE, $orgPendiente);
        $ids['pF'] = $crear('p-org-inactiva', $tipoP, UsersModel::STATUS_USER_ACTIVE, $orgInactiva);
        //El administrador de una organización es un GENERAL: lo que gana por serlo son las rutas de usuarios.
        $ids['pI'] = $crear('p-jefe', UsersModel::TYPE_USER_GENERAL, UsersModel::STATUS_USER_ACTIVE, $orgConJefe);
        $ids['pJ'] = $crear('p-raso', UsersModel::TYPE_USER_GENERAL, UsersModel::STATUS_USER_ACTIVE, $orgConJefe);
        //El no aprobado: general, por aprobar, en una organización que tampoco lo está. Y su control, en la global, antes.
        $ids['pL'] = $crear('p-aprobado', UsersModel::TYPE_USER_GENERAL);
        $orgSinAprobar = $organizacion('org-sin-aprobar', OrganizationMapper::PENDING_APPROVAL, $ids['pL']);
        $ids['pK'] = $crear('p-no-aprobado', UsersModel::TYPE_USER_GENERAL, UsersModel::STATUS_USER_APPROVED_PENDING, $orgSinAprobar);
        $check($orgSinAprobar > 0, 'p0 banco: una organización creada por un general, que no se aprueba sola', (string) $orgSinAprobar);
        $conJefe = new OrganizationMapper($orgConJefe);
        $conJefe->administrator = $ids['pI'];
        $conJefe->update();

        //TRAMPA DEL BANCO: `SystemApprovalManager::init()` PONE ACTIVO, en su primera petición, al usuario nuevo de una
        //organización aprobada. Por eso: alta, una petición para que corra ese registro, y DESPUÉS el estado.
        $pedir('GET', $acceso, null, [], false);
        $estados = [
            'pB' => UsersModel::STATUS_USER_APPROVED_PENDING,
            'pC' => UsersModel::STATUS_USER_REJECTED,
            'pD' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK,
            'pG' => UsersModel::STATUS_USER_INACTIVE,
        ];
        foreach ($estados as $clave => $estado) {
            (new UsersModel())->changeStatus($estado, $ids[$clave]);
        }
        $estados['pK'] = UsersModel::STATUS_USER_APPROVED_PENDING;
        $estadoDe = function (int $id): ?int {
            $fila = UsersModel::model();
            $fila->resetAll();
            $fila->select('status')->where(['id' => $id])->execute();
            $valor = ((array) $fila->result())[0]->status ?? null;
            return $valor !== null ? (int) $valor : null;
        };
        $bancoEnPie = function () use ($estados, $ids, $estadoDe, $orgPendiente, $orgInactiva, $orgSinAprobar): string {
            $mal = [];
            foreach ($estados as $clave => $estado) {
                if ($estadoDe($ids[$clave]) !== $estado) {
                    $mal[] = "{$clave}: " . var_export($estadoDe($ids[$clave]), true) . " en vez de {$estado}";
                }
            }
            foreach ([$orgPendiente => OrganizationMapper::PENDING_APPROVAL, $orgInactiva => OrganizationMapper::INACTIVE, $orgSinAprobar => OrganizationMapper::PENDING_APPROVAL] as $id => $estado) {
                $guardado = (new OrganizationMapper($id))->status;
                if ((int) $guardado !== $estado) {
                    $mal[] = "organización {$id}: " . var_export($guardado, true) . " en vez de {$estado}";
                }
            }
            return implode('; ', $mal);
        };
        $check($bancoEnPie() === '', 'p0 banco: los estados de usuarios y organizaciones están como se pidieron ANTES de medir', $bancoEnPie());

        $listado = (string) $ruta('users-list');
        $medir = function (string $caso, string $jwt) use ($entra, $pedir, $rutaPublica, $listado, &$ultimaEntrada): array {
            $protegida = $entra($jwt);
            $detalle = $ultimaEntrada;
            $publica = $pedir('GET', $rutaPublica, $jwt, [], false)['status'];
            $datos = $pedir('GET', $listado, $jwt)['status'];
            echoTerminal("      MEDIDA {$caso}: protegida {$protegida} ({$detalle}) · pública HTTP {$publica} · listado de usuarios (XHR) HTTP {$datos}");
            return [$protegida, $publica, $datos];
        };
        $casos = [
            'A activo, organización activa' => [$token($ids['pA']), 'DENTRO', 200],
            'B usuario por aprobar' => [$token($ids['pB']), 'DENTRO', 200],
            'C usuario rechazado' => [$token($ids['pC']), 'DENTRO', 200],
            'D usuario bloqueado por intentos' => [$token($ids['pD']), 'DENTRO', 200],
            'E organización pendiente' => [$token($ids['pE']), 'DENTRO', 200],
            'F organización inactiva' => [$token($ids['pF']), 'FUERA', 404],
            'G usuario inactivo' => [$token($ids['pG']), 'FUERA', 404],
            'H token caducado de un activo' => [SessionToken::generateToken(['id' => $ids['pA']], null, -60, false), 'FUERA', 404],
        ];
        foreach ($casos as $caso => [$jwt, $protegidaEsperada, $publicaEsperada]) {
            [$protegida, $publica] = $medir($caso, $jwt);
            $letra = strtolower($caso[0]);
            $check($protegida === $protegidaEsperada, "p{$letra}1 {$caso}: la ruta protegida lo deja {$protegidaEsperada}", $protegida);
            $check($publica === $publicaEsperada, "p{$letra}2 {$caso}: la ruta pública " . ($publicaEsperada === 200 ? 'lo ve como identificado' : 'lo trata como visitante'), "HTTP {$publica}");
        }
        [$jefeProtegida, , $jefeListado] = $medir('I administrador de su organización', $token($ids['pI']));
        [, , $rasoListado] = $medir('J general de la misma organización (control de I)', $token($ids['pJ']));
        $check($jefeProtegida === 'DENTRO', 'pi1 I: el administrador de una organización entra', $jefeProtegida);
        $check($jefeListado === 200, 'pi2 I: y abre el listado de usuarios, que gana por serlo', "HTTP {$jefeListado}");
        $check($rasoListado === 403, 'pi3 DISCRIMINANTE: un general de la misma organización que no la administra, no', "HTTP {$rasoListado}");

        //K: la ruta recortada se ELIGE midiendo: una GET sin parámetros que el rol general tiene, que el recorte del
        //no aprobado no conserva, y que al general aprobado le responde 200.
        $noAprobado = $token($ids['pK']);
        $aprobado = $token($ids['pL']);
        $generales = (array) (get_config('roles')['baseInitialSegmentedPermissions']['generals'] ?? []);
        $candidatas = [];
        foreach (get_routes() as $nombreRuta => $info) {
            $nombreRuta = (string) $nombreRuta;
            if (($info['method'] ?? '') === 'GET' && ($info['require_login'] ?? false) === true && count((array) ($info['parameters'] ?? [])) === 0
                && !SystemApprovalsMiddleware::keepsWhenNotApproved($nombreRuta) && !in_array($nombreRuta, $generales, true)
                && !str_starts_with($nombreRuta, 'terminal-')) {
                $candidatas[] = $nombreRuta;
            }
        }
        sort($candidatas);
        $recortada = null;
        foreach ($candidatas as $candidata) {
            $url = $ruta($candidata);
            if ($url !== null && $pedir('GET', $url, $aprobado)['status'] === 200) {
                $recortada = $candidata;
                break;
            }
        }
        $check($recortada !== null, 'pk0 banco: hay una ruta del rol general fuera del recorte que el aprobado abre', count($candidatas) . ' candidatas');
        [$kProtegida, ] = $medir('K general NO aprobado, organización pendiente', $noAprobado);
        //Su señal pública: un borrador SUYO. El de arriba es de otro creador y, desde F3 (pendientes.md 380), un general
        //solo ve los borradores de su organización: lo que se mide es la sesión, no el permiso de borradores.
        $pk = new PublicationMapper();
        $pk->baseLang = $lang;
        foreach (['title' => "{$prefijo} borrador de K", 'content' => 'Borrador de K.', 'seoDescription' => '', 'publicDate' => new \DateTime(), 'startDate' => null, 'endDate' => null, 'category' => $categoria, 'visits' => 0, 'author' => $ids['pK'], 'folder' => str_replace('.', '', uniqid()), 'featured' => PublicationMapper::UNFEATURED, 'mainImage' => 'statics/images/zz-prueba.jpg', 'thumbImage' => 'statics/images/zz-prueba.jpg', 'ogImage' => ''] as $campo => $valor) {
            $pk->setLangData($lang, $campo, $valor);
        }
        $pk->status = PublicationMapper::DRAFT;
        $usuarioPrevio = get_config('current_user');
        $guardadoPrevio = get_config('pcsphp_current_user_stored');
        try {
            set_config('current_user', (object) ['id' => $ids['pK']]);
            set_config('pcsphp_current_user_stored', null);
            $pk->save();
        } finally {
            set_config('current_user', $usuarioPrevio);
            set_config('pcsphp_current_user_stored', $guardadoPrevio);
        }
        $publicacionKID = $pk->id !== null ? (int) $pk->id : null;
        $rutaPublicaK = (string) $ruta($nombres['publica'], ['slug' => $pk->getSlug()]);
        $kPublica = $pedir('GET', $rutaPublicaK, $noAprobado, [], false)['status'];
        $kPublicaSinSesion = $pedir('GET', $rutaPublicaK, null, [], false)['status'];
        echoTerminal("      MEDIDA K, su propio borrador: con sesión HTTP {$kPublica} · sin sesión HTTP {$kPublicaSinSesion}");
        $kRecortada = $recortada !== null ? $pedir('GET', (string) $ruta($recortada), $noAprobado)['status'] : 0;
        echoTerminal("      MEDIDA K, ruta recortada {$recortada} (XHR): no aprobado HTTP {$kRecortada} · aprobado HTTP 200");
        $check($kProtegida === 'DENTRO', 'pk1 K: el no aprobado ENTRA: conserva su espacio', $kProtegida);
        $check($kPublica === 200 && $kPublicaSinSesion === 404, 'pk2 K: y la ruta pública lo ve como identificado (su borrador: 200 con sesión, 404 sin ella)', "HTTP {$kPublica} y {$kPublicaSinSesion}");
        $check($kRecortada === 403, "pk3 K: pero {$recortada}, que su rol tiene, le responde 403: el recorte sigue aplicándose", "HTTP {$kRecortada}");
        //La lista de lo que conserva el no aprobado nombra rutas por su nombre: si una se renombra y la lista no, la pierde sin ruido.
        $kMapbox = $pedir('GET', (string) $ruta('configurations-integrations-mapbox-key'), $noAprobado)['status'];
        $check($kMapbox === 200, 'pk4 K: y conserva la clave de Mapbox, que el recorte le deja por nombre', "HTTP {$kMapbox}");
        $check($bancoEnPie() === '', 'p9 banco: los estados siguen como se pidieron DESPUÉS de medir (nada los movió por el camino)', $bancoEnPie());
        echoTerminal(' ');

        //─── X · La regla de las dos marcas, y quién la pregunta antes de renovar ────────────────
        echoTerminal('[X] Un token vale si nació DESPUÉS de las marcas; y un caducado no se renueva sin preguntarlo');
        //La marca global de este proceso, para fabricar los casos alrededor de ella.
        $global = SessionToken::minimumDateCreated()->getTimestamp();
        $marca = date('Y-m-d H:i:s', $global + 3600);
        $casos = [
            'limpio: posterior a la global y sin marca de usuario' => [$global + 10, null, true],
            'marca de usuario vacía' => [$global + 10, '   ', true],
            'posterior a las dos marcas' => [$global + 3601, $marca, true],
            'revocado: anterior a la marca del usuario' => [$global + 10, $marca, false],
            'el mismo segundo que la marca del usuario' => [$global + 3600, $marca, false],
            'anterior a la marca global' => [$global - 1, null, false],
            'el mismo segundo que la marca global' => [$global, null, false],
            'sin fecha entera' => [null, null, false],
            'marca de usuario que no es una fecha: falla cerrado' => [$global + 10, 'no-es-una-fecha', false],
            //El dato llega del token caducado: si ese camino se rompe, llega NULO o en cero, y las dos
            //formas tienen que NEGAR. Es lo que impide que un dato ausente se lea como permiso.
            'dato vacío: el cero de la época' => [0, null, false],
            'dato vacío con marca de usuario' => [null, $marca, false],
            'dato vacío y marca ilegible' => [null, 'no-es-una-fecha', false],
        ];
        foreach ($casos as $nombre => [$creado, $marcaUsuario, $esperado]) {
            $obtenido = SessionToken::isCreatedAfterMarks($creado, $marcaUsuario);
            $check($obtenido === $esperado, "x1 {$nombre} → " . ($esperado ? 'vale' : 'no vale'), var_export($obtenido, true));
        }
        //El bloque que renueva un token caducado tiene que preguntarlo ANTES de fabricar el nuevo: el nuevo nace
        //después de cualquier marca, y con él un revocado entraba. Por tokens, para que un comentario no cuente.
        $fuente = (string) file_get_contents(basepath('index.php'));
        $desde = mb_strpos($fuente, '$ignoreExpired = in_array(');
        $hasta = $desde !== false ? mb_strpos($fuente, '//Verifica la validez del usuario activo', $desde) : false;
        $check($desde !== false && $hasta !== false, 'x2 canario: el bloque que renueva un token caducado se encuentra en src/index.php');
        $llamadas = [];
        if ($desde !== false && $hasta !== false) {
            $tokens = array_values(array_filter(token_get_all('<?php ' . mb_substr($fuente, $desde, $hasta - $desde)), fn($t) => !is_array($t) || !in_array($t[0], [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT], true)));
            foreach ($tokens as $k => $t) {
                if (is_array($t) && $t[0] === \T_STRING && in_array($t[1], ['isCreatedAfterMarks', 'generateToken'], true) && ($tokens[$k + 1] ?? null) === '(') {
                    $llamadas[] = $t[1];
                }
            }
        }
        $check($llamadas === ['isCreatedAfterMarks', 'generateToken'], 'x3 y pregunta por las marcas ANTES de fabricar el token nuevo', (string) json_encode($llamadas));
        //LA PRUEBA DEL ACOPLAMIENTO (300.2): el dato de la revocación tiene que decodificarse FUERA del
        //`if` del registro, que está apagado de serie. Por tokens: un comentario no cuenta.
        $bloqueCaducada = mb_strpos($fuente, 'if (!$isActiveSession) {');
        $finCaducada = $bloqueCaducada !== false ? mb_strpos($fuente, '$ignoreExpired = in_array(', $bloqueCaducada) : false;
        $check($bloqueCaducada !== false && $finCaducada !== false, 'x3b canario: el bloque de la sesión caducada se encuentra en src/index.php');
        $decodificaFuera = false;
        $decodificaDentro = false;
        if ($bloqueCaducada !== false && $finCaducada !== false) {
            $trozo = mb_substr($fuente, $bloqueCaducada, $finCaducada - $bloqueCaducada);
            $profundidad = 0;
            $profundidadDeLaDecodificacion = null;
            foreach (token_get_all('<?php ' . $trozo) as $k => $t) {
                $texto = is_array($t) ? $t[1] : $t;
                if (is_array($t) && in_array($t[0], [\T_COMMENT, \T_DOC_COMMENT], true)) {
                    continue;
                }
                if ($texto === '{') {
                    $profundidad++;
                } elseif ($texto === '}') {
                    $profundidad--;
                } elseif (is_array($t) && $t[0] === \T_VARIABLE && $t[1] === '$expiredToken' && $profundidadDeLaDecodificacion === null) {
                    //La primera aparición es su asignación: ahí se mide la profundidad.
                    $profundidadDeLaDecodificacion = $profundidad;
                }
            }
            //Profundidad 1 es el cuerpo del `if (!$isActiveSession)`; 2 o más, dentro de otro `if`.
            $decodificaFuera = $profundidadDeLaDecodificacion === 1;
            $decodificaDentro = is_int($profundidadDeLaDecodificacion) && $profundidadDeLaDecodificacion > 1;
        }
        $check($decodificaFuera && !$decodificaDentro,
            'x3c el token caducado se decodifica FUERA del `if` del registro, así que la revocación nunca decide sin dato',
            'profundidad medida: ' . var_export($profundidadDeLaDecodificacion ?? null, true));
        //Y `isActiveSession()` dice lo mismo con la marca global: se le pone la marca en el segundo exacto del token.
        $marcaEstatica = new \ReflectionProperty(SessionToken::class, 'minimumDateCreated');
        $marcaEstaticaPrevia = $marcaEstatica->getValue();
        try {
            $tokenX = $token($ids['admin']);
            $creadoX = (int) BaseToken::getCreated($tokenX);
            SessionToken::setMinimumDateCreated(new \DateTime(date('Y-m-d H:i:s', $creadoX)));
            $check(SessionToken::isActiveSession($tokenX) === false, 'x4 isActiveSession(): un token del mismo segundo que la marca global NO vale');
            SessionToken::setMinimumDateCreated(new \DateTime(date('Y-m-d H:i:s', $creadoX - 1)));
            $check(SessionToken::isActiveSession($tokenX) === true, 'x5 DISCRIMINANTE: con la marca un segundo antes, sí vale');
        } finally {
            $marcaEstatica->setValue(null, $marcaEstaticaPrevia);
        }
        //INFORMATIVO, no es un fallo: un clon puede usar la lista, y con x3 ya no abre nada.
        $lista = mb_strpos($fuente, '$ignoreExpiredForRoutesName = [');
        $finLista = $lista !== false ? mb_strpos($fuente, '];', $lista) : false;
        $rutasEnLista = [];
        if ($lista !== false && $finLista !== false) {
            foreach (token_get_all('<?php ' . mb_substr($fuente, $lista, $finLista - $lista)) as $t) {
                if (is_array($t) && $t[0] === \T_CONSTANT_ENCAPSED_STRING) {
                    $rutasEnLista[] = mb_substr($t[1], 1, -1);
                }
            }
        }
        echoTerminal('      - la lista ignoreExpired nombra hoy: ' . json_encode($rutasEnLista));
        echoTerminal(' ');

        //─── G · La marca global desde el terminal ──────────────────────────────────────────────
        echoTerminal('[G] sessions-revoke-all: sin confirmación no hace nada; con ella echa a todos');
        $tarea = function (array $argumentos) use ($proyecto): array {
            $comando = escapeshellarg("{$proyecto}/bin/cli") . ' sessions-revoke-all';
            foreach ($argumentos as $argumento) {
                $comando .= ' ' . escapeshellarg($argumento);
            }
            $salida = [];
            $codigo = -1;
            //RETORNO-IGNORADO: el resultado va en $salida y $codigo; el retorno es solo la última línea.
            exec($comando . ' 2>&1', $salida, $codigo);
            return [$codigo, (string) preg_replace('/\e\[[0-9;]*m/', '', implode("\n", $salida))];
        };
        $siguienteSegundo((int) strtotime((string) $marcaDe($ids['admin']) ?: '1990-01-01'));
        $vivo = $token($ids['admin']);
        [$codigo, $salida] = $tarea([]);
        $check($codigo === 1 && SettingsModel::getConfigValue(SessionToken::MINIMUM_DATE_CONFIG) === $marcaGlobalPrevia, 'g1 sin confirm=yes → 1 y la marca no se mueve', $salida);
        $check($entra($vivo) === 'DENTRO', 'g2 y el token sigue entrando');
        //SIN separar un segundo: un token del mismo segundo que la marca global tampoco vale, como con la del usuario.
        [$codigo, $salida] = $tarea(['confirm=yes']);
        $nueva = SettingsModel::getConfigValue(SessionToken::MINIMUM_DATE_CONFIG);
        $check($codigo === 0 && is_string($nueva) && abs(strtotime($nueva) - time()) <= 5, 'g3 con confirm=yes → 0 y la marca queda en ahora', "código {$codigo}, marca " . var_export($nueva, true));
        $check(preg_match('/hasta \d+ usuario/', $salida) === 1, 'g4 y dice a cuántos usuarios alcanza', $salida);
        $check($entra($vivo) === 'FUERA', 'g5 el token de antes ya no entra', $ultimaEntrada);
        $siguienteSegundo(is_string($nueva) ? (int) strtotime($nueva) : time());
        $check($entra($token($ids['admin'])) === 'DENTRO', 'g6 DISCRIMINANTE: uno nacido después, sí');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[Z] Limpieza');
        $quedanOrganizaciones = -1;
        $quedanAprobaciones = -1;
        //Un fallo al limpiar tiene que verse como comprobación fallida, con su balance, y no tumbar la suite.
        try {
            $retirado = [];
            //La marca global vuelve a como estaba: si no existía, la fila se borra.
            if ($marcaGlobalExistia) {
                SettingsModel::setConfigValue(SessionToken::MINIMUM_DATE_CONFIG, $marcaGlobalPrevia);
            } else {
                $config = SettingsModel::model();
                $config->resetAll();
                $config->delete(['name' => SessionToken::MINIMUM_DATE_CONFIG])->execute();
            }
            $retirado[] = 'marca global ' . ($marcaGlobalExistia ? 'restaurada' : 'retirada');
            foreach ([$publicacionID, $publicacionKID] as $idPublicacion) {
                if ($idPublicacion !== null) {
                    $pub = PublicationMapper::model();
                    $pub->resetAll();
                    $pub->delete(['id' => $idPublicacion])->execute();
                    $retirado[] = "publicación {$idPublicacion}";
                }
            }
            $ahora = $usuariosDelPrefijo();
            //Los registros de acciones apuntan a los usuarios: se borran antes, solo los de esta prueba.
            $logs = LogsMapper::model();
            foreach (array_merge([1], array_map(fn($u) => (int) $u->id, $ahora)) as $autor) {
                $logs->resetAll();
                $logs->delete(new WhereSegment([
                    new WhereItem('id', WhereItem::GREATER_THAN_OPERATOR, $ultimoLog),
                    new WhereItem('createdBy', WhereItem::EQUAL_OPERATOR, $autor, WhereItem::AND_OPERATOR),
                    WhereItem::like('textMessageVariables', "%{$prefijo}%", WhereItem::AND_OPERATOR),
                ]))->execute();
            }
            $logs->resetAll();
            $logs->delete(new WhereSegment([
                new WhereItem('id', WhereItem::GREATER_THAN_OPERATOR, $ultimoLog),
                new WhereItem('textMessage', WhereItem::EQUAL_OPERATOR, LogsMapper::MESSAGES[LogsMapper::MSG_REVOKE_ALL_SESSIONS], WhereItem::AND_OPERATOR),
            ]))->execute();
            //La organización sin aprobar apunta a su creador, que es un usuario de la prueba: se le quita antes de borrarlo.
            $deLaPrueba = OrganizationMapper::model();
            $deLaPrueba->resetAll();
            $deLaPrueba->update(['createdBy' => 1])->where(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
            //El alta crea un perfil para algunos tipos, y el perfil apunta al usuario: va antes.
            $perfiles = UserProfileMapper::model();
            foreach ($ahora as $u) {
                $perfiles->resetAll();
                $perfiles->delete(['belongsTo' => (int) $u->id])->execute();
            }
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
            $retirado[] = count($ahora) . " usuarios {$prefijo}-*";
            //Las organizaciones de la prueba, después de sus usuarios, que apuntan a ellas.
            $organizaciones = OrganizationMapper::model();
            $organizaciones->resetAll();
            $organizaciones->select()->where(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
            $idsOrganizaciones = array_map(fn($o) => (int) $o->id, (array) $organizaciones->result());
            //`SystemApprovalManager::init()` anota cada usuario, organización y publicación nuevos en la tabla de
            //aprobaciones al servir una petición: esas filas no caen con su elemento y hay que retirarlas aparte.
            $aprobaciones = SystemApprovalsMapper::model();
            $referencias = [
                UsersModel::TABLE => array_map(fn($u) => (int) $u->id, $ahora),
                OrganizationMapper::TABLE => $idsOrganizaciones,
                PublicationMapper::TABLE => array_values(array_filter([$publicacionID, $publicacionKID], fn ($id) => $id !== null)),
            ];
            $quedanAprobaciones = 0;
            foreach ($referencias as $tabla => $idsDeTabla) {
                foreach ($idsDeTabla as $idReferido) {
                    $criterio = new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tabla),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $idReferido, WhereItem::AND_OPERATOR),
                    ]);
                    $aprobaciones->resetAll();
                    $aprobaciones->delete($criterio)->execute();
                    $aprobaciones->resetAll();
                    $aprobaciones->select()->where($criterio)->execute();
                    $quedanAprobaciones += count((array) $aprobaciones->result());
                }
            }
            $retirado[] = 'sus filas de aprobación';
            $organizaciones->resetAll();
            $organizaciones->delete(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
            $organizaciones->resetAll();
            $organizaciones->select()->where(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
            $quedanOrganizaciones = count((array) $organizaciones->result());
            $retirado[] = "organizaciones {$prefijo}-*";
            echoTerminal('      - ' . implode('; ', $retirado));

        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }

        $quedaPublicacion = ($publicacionID !== null && PublicationMapper::existsByID($publicacionID)) || ($publicacionKID !== null && PublicationMapper::existsByID($publicacionKID));
        $logsRestantes = LogsMapper::model();
        $logsRestantes->resetAll();
        $logsRestantes->select()->where(new WhereSegment([
            new WhereItem('id', WhereItem::GREATER_THAN_OPERATOR, $ultimoLog),
            WhereItem::like('textMessageVariables', "%{$prefijo}%", WhereItem::AND_OPERATOR),
        ]))->execute();
        $check(
            count($usuariosDelPrefijo()) === 0 && $quedanOrganizaciones === 0 && $quedanAprobaciones === 0 && !$quedaPublicacion && count((array) $logsRestantes->result()) === 0
            && SettingsModel::optionExists(SessionToken::MINIMUM_DATE_CONFIG) === $marcaGlobalExistia,
            'z1 limpieza: 0 usuarios, 0 organizaciones, 0 publicaciones, 0 filas de aprobación, 0 registros de la prueba y la marca global como estaba'
        );
    }

    return $balance();

})->setDescription('La revocación de sesiones por HTTP: el token revocado deja de valer, nadie cierra lo que no le toca y la marca global se mueve desde el terminal.')->setEffects([CliActions::EFFECT_NETWORK, CliActions::EFFECT_DATABASE])->register();
