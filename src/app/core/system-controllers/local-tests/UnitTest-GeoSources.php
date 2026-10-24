<?php

//El geovisor (C11): registro, consulta, puntos [lng,lat] sin (0,0) ni ocultos, cota, encuadre, permisos, búsqueda e imagen.
//En proceso y por HTTP; siembra zz- en un rincón del mapa y lo retira entero.

use Directory\Geo\PersonsGeoSource;
use Organizations\Geo\OrganizationsGeoSource;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Statics\ProtectedUploads;
use PiecesPHP\Geo\Controllers\GeoController;
use PiecesPHP\Geo\GeoFilter;
use PiecesPHP\Geo\GeoPointsResult;
use PiecesPHP\Geo\GeoQuery;
use PiecesPHP\Geo\GeoQueryException;
use PiecesPHP\Geo\GeoSource;
use PiecesPHP\Geo\GeoSourceRegistry;
use PiecesPHP\Geo\GeoThumbnail;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\AvatarModel;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\Util\SystemApprovalManager;

CliActions::make('unit-tests:core/geo-sources', function ($args) {

    echoTerminal("\e[33m[TEST:GeoSources] El geovisor: registro, consulta, puntos de personas y organizaciones, permisos y búsqueda\e[39m");
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
    $lanza = function (callable $accion): bool {
        try {
            $accion();
        } catch (GeoQueryException $e) {
            return true;
        }
        return false;
    };

    //Una fuente de prueba que solo ve root (admin-cropper-testing): para el registro y el 403.
    $soloRoot = new class implements GeoSource {
        /** @var string */
        public $titulo = 'zz-solo-root';
        public function key(): string
        {
            return 'zz-solo-root';
        }
        public function title(): string
        {
            return $this->titulo;
        }
        public function permissionRoute(): string
        {
            return 'admin-cropper-testing';
        }
        public function marker(): string
        {
            return GeoSource::MARKER_DOT;
        }
        public function colorVariable(): string
        {
            return '--zz-color';
        }
        public function filters(): array
        {
            return [new GeoFilter('zz', 'zz', GeoFilter::TYPE_TEXT)];
        }
        public function points(GeoQuery $query): GeoPointsResult
        {
            return new GeoPointsResult([], 0);
        }
        public function pointDetail(int $id): ?array
        {
            return null;
        }
        public function thumbnailSource(int $id): ?string
        {
            return basepath('statics/images/default-avatar.png');
        }
        public function cardView(): string
        {
            return __DIR__ . '/../../../classes/Directory/Views/geo/card.php';
        }
    };

    $database = (new BaseModel())->getDatabase();
    $base = rtrim((string) (getenv('PCSPHP_WALK_BASE') ?: ''), '/');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = rtrim(is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '', '/');
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, string $jwt) use ($base): array {
        $h = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($h, [CURLOPT_URL => $base . '/' . ltrim($path, '/'), CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => [SessionToken::tokenName() . ": {$jwt}"]]);
        $body = curl_exec($h);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };

    $prefijo = 'zz-prueba-geo-' . bin2hex(random_bytes(3));
    $nombre = 'Zzgeo' . bin2hex(random_bytes(3));
    //Un rincón del Pacífico sur donde no siembra nadie: el encuadre de la prueba solo ve lo suyo.
    $rincon = new GeoQuery([150.0, -41.0, 151.0, -40.0]);
    $usuarios = [];
    $organizaciones = [];
    $crearUsuario = function (string $sufijo, int $tipo, int $estado = UsersModel::STATUS_USER_ACTIVE) use ($prefijo, $nombre, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = $nombre;
        $u->secondname = '';
        $u->firstLastname = $sufijo;
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = $estado;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };
    $persona = function (string $sufijo, float $lng, float $lat, int $visible = 1, int $estado = UsersModel::STATUS_USER_ACTIVE) use ($crearUsuario): int {
        $id = $crearUsuario($sufijo, UsersModel::TYPE_USER_GENERAL, $estado);
        UserProfileMapper::createProfile($id);
        $m = UserProfileMapper::model();
        $m->resetAll();
        //RETORNO-IGNORADO: si no escribiera, los casos de puntos lo dirían.
        $m->update(['latitude' => $lat, 'longitude' => $lng, 'publicVisible' => $visible])->where(['belongsTo' => $id])->execute();
        return $id;
    };
    $organizacion = function (string $sufijo, float $lng, float $lat, int $visible = 1, ?string $logo = 'statics/images/default-avatar-org.jpg') use ($prefijo, $nombre, &$organizaciones): int {
        $o = new OrganizationMapper();
        $o->name = "{$nombre} {$prefijo}-org-{$sufijo}";
        $o->nit = "{$prefijo}-nit-{$sufijo}";
        $o->folder = "{$prefijo}-{$sufijo}";
        $o->status = OrganizationMapper::ACTIVE;
        $o->activitySector = 'Sector de prueba';
        $o->informativeEmail = "{$prefijo}-org-{$sufijo}@example.com";
        //Un archivo versionado como logo: nadie lo sustituye, y la limpieza borra la fila sin pasar por el controlador.
        $o->logo = $logo;
        $o->latitude = $lat;
        $o->longitude = $lng;
        $o->publicVisible = $visible;
        $o->save();
        $organizaciones[$sufijo] = (int) $o->id;
        return (int) $o->id;
    };
    $ids = fn(GeoPointsResult $r): array => array_map(fn($p): int => $p->id, $r->points());
    $imagen = function (GeoPointsResult $r, int $id): ?string {
        foreach ($r->points() as $p) {
            if ($p->id === $id) {
                return $p->thumbnail;
            }
        }
        return 'zz-sin-punto';
    };
    //La misma regla que GeoViewer.isSafeURL(): http(s):// o una sola barra; lo demás, el cliente no lo pinta.
    $esSegura = fn($url): bool => is_string($url) && (preg_match('#^https?://#i', $url) === 1 || preg_match('#^/(?![/\\\\])#', $url) === 1);
    $avatares = [];
    //Patrones, no archivos: la miniatura lleva la huella del original en el nombre ({id}-{ancho}-{huella}.png).
    $generadas = [];
    $enDisco = function (array $patrones): array {
        $archivos = [];
        foreach ($patrones as $patron) {
            $archivos = array_merge($archivos, glob($patron) ?: []);
        }
        return $archivos;
    };
    $unaEnDisco = function (string $patron): ?string {
        $archivos = glob($patron) ?: [];
        return count($archivos) === 1 ? $archivos[0] : null;
    };

    try {
        //──── [r] El registro, en memoria y con lo de siempre repuesto al final ──────────────
        echoTerminal('[r] El registro');
        $originales = GeoSourceRegistry::swapForTesting();
        try {
            GeoSourceRegistry::register($soloRoot);
            $check(array_keys(GeoSourceRegistry::all()) === ['zz-solo-root'], 'r1 registra por clave');
            $check((function () use ($soloRoot): bool {
                try {
                    GeoSourceRegistry::register($soloRoot);
                } catch (\InvalidArgumentException $e) {
                    return true;
                }
                return false;
            })(), 'r2 una clave repetida lanza');
            GeoSourceRegistry::remove('zz-solo-root');
            $check((function () use ($soloRoot): bool {
                try {
                    GeoSourceRegistry::replace($soloRoot);
                } catch (\InvalidArgumentException $e) {
                    return true;
                }
                return false;
            })(), 'r3 replace() de una clave que no está lanza');
            GeoSourceRegistry::register($soloRoot);
            $otra = clone $soloRoot;
            $otra->titulo = 'zz-sustituida';
            GeoSourceRegistry::replace($otra);
            $check(GeoSourceRegistry::get('zz-solo-root')?->title() === 'zz-sustituida', 'r4 replace() sustituye la de esa clave');
            $check(array_key_exists('zz-solo-root', GeoSourceRegistry::visibleFor(UsersModel::TYPE_USER_ROOT)) && !array_key_exists('zz-solo-root', GeoSourceRegistry::visibleFor(UsersModel::TYPE_USER_GENERAL)), 'r5 visibleFor() mira el permiso de su ruta: admin-cropper-testing solo para root');

            //──── [p] Qué fuentes atiende cada petición ──────────────────────────────────────
            echoTerminal('[p] Las fuentes de una petición, por rol');
            $check(GeoController::resolveSources(['zz-solo-root'], UsersModel::TYPE_USER_GENERAL) === 403, 'p1 una fuente que existe y el rol no ve: 403');
            $check(GeoController::resolveSources(['zz-no-existe'], UsersModel::TYPE_USER_ROOT) === 404, 'p2 una fuente que no existe: 404');
            $check(GeoController::resolveSources('zz-solo-root', UsersModel::TYPE_USER_ROOT) === 400, 'p3 sources que no es una lista: 400');
            $check(GeoController::resolveSources(null, UsersModel::TYPE_USER_GENERAL) === [], 'p4 sin sources, las visibles: ninguna para quien no ve la única');
            $check(is_array(GeoController::resolveSources(['zz-solo-root'], UsersModel::TYPE_USER_ROOT)), 'p5 CANARIO: root sí la pide');
            $check(GeoController::resolveSource('zz-solo-root', UsersModel::TYPE_USER_GENERAL) === 403 && GeoController::resolveSource('zz-no-existe', UsersModel::TYPE_USER_ROOT) === 404, 'p6 el detalle: 403 si no la ve y 404 si no existe');
            $generadas[] = basepath(GeoThumbnail::DIRECTORY . '/zz-solo-root/1-64-*.png');
            $check(GeoController::resolveThumbnail('zz-solo-root', UsersModel::TYPE_USER_GENERAL, '1', '64') === 403, 'p7 la miniatura de una fuente que el rol no ve: 403, como points');
            $check(GeoController::resolveThumbnail('zz-no-existe', UsersModel::TYPE_USER_ROOT, '1', '64') === 404 && GeoController::resolveThumbnail('zz-solo-root', UsersModel::TYPE_USER_ROOT, '1', '65') === 404 && GeoController::resolveThumbnail('zz-solo-root', UsersModel::TYPE_USER_ROOT, 'x', '64') === 404, 'p8 sin fuente, con un tamaño que no es 64 ni 128 o con un id que no es número: 404');
            $check(is_string(GeoController::resolveThumbnail('zz-solo-root', UsersModel::TYPE_USER_ROOT, '1', '64')), 'p9 CANARIO: root sí la tiene');
        } finally {
            GeoSourceRegistry::swapForTesting($originales);
        }
        echoTerminal(' ');

        //──── [q] La consulta ───────────────────────────────────────────────────────────────
        echoTerminal('[q] GeoQuery valida, no recorta');
        $check($lanza(fn() => new GeoQuery([-181.0, 0.0, 10.0, 10.0])) && $lanza(fn() => new GeoQuery([0.0, -91.0, 10.0, 10.0])), 'q1 un encuadre fuera del mapa lanza');
        $check($lanza(fn() => new GeoQuery([10.0, 0.0, 0.0, 10.0])) && $lanza(fn() => new GeoQuery([0.0, 10.0, 10.0, 0.0])), 'q2 un encuadre invertido lanza');
        $check($lanza(fn() => new GeoQuery(null, 25)) && $lanza(fn() => new GeoQuery(null, -1)), 'q3 un acercamiento fuera de 0-24 lanza');
        $check($lanza(fn() => GeoQuery::fromParams(['bbox' => '1,2,3'])) && $lanza(fn() => GeoQuery::fromParams(['bbox' => '1,2,3,x'])), 'q4 un encuadre sin cuatro números lanza');
        $check(GeoQuery::fromParams(['limit' => '1'])->limit() === GeoQuery::DEFAULT_LIMIT, 'q5 un limit de la petición se ignora: la cota es la constante');
        $check($lanza(fn() => GeoQuery::fromParams(['filters' => ['search' => ['a' => ['b']]]])), 'q6 un filtro que no es escalar ni lista de escalares lanza');
        $check(GeoQuery::fromParams([])->bbox() === GeoQuery::WORLD, 'q7 CANARIO: sin encuadre, el mundo');
        echoTerminal(' ');

        //──── [c] Los puntos, con datos propios en el rincón ───────────────────────────────
        echoTerminal('[c] Los puntos de personas y organizaciones');
        $p1 = $persona('visible', 150.1234, -40.5678);
        $p2 = $persona('origen', 0.0, 0.0);
        $p3 = $persona('oculta', 150.2, -40.2, 0);
        $p4 = $persona('inactiva', 150.3, -40.3, 1, UsersModel::STATUS_USER_INACTIVE);
        $p5 = $persona('eliminada', 150.4, -40.4, 1, UsersModel::STATUS_USER_DELETED);
        $p6 = $persona('otra', 150.6, -40.6);
        $p7 = $persona('fuera', 151.5, -41.5);
        $o1 = $organizacion('visible', 150.7, -40.7);
        $o2 = $organizacion('oculta', 150.8, -40.8, 0);
        $o3 = $organizacion('origen', 0.0, 0.0);
        //Fuera del rincón, para que c9 siga viendo solo a la visible.
        $o4 = $organizacion('sinlogo', 151.6, -41.6, 1, null);
        //Un logo privado, como los que sube el formulario: en disco lleva el sufijo de ProtectedUploads.
        $carpetaLogo = basepath("statics/uploads/organizations/{$prefijo}-logo");
        $avatares[] = $carpetaLogo;
        //RETORNO-IGNORADO: si no la crea, la copia de abajo falla e i5 lo dice.
        mkdir($carpetaLogo, 0775, true);
        $avatares[] = "{$carpetaLogo}/logo.jpg" . ProtectedUploads::suffix();
        //RETORNO-IGNORADO: si no copia, i5 lo dice.
        copy(basepath('statics/images/default-avatar-org.jpg'), "{$carpetaLogo}/logo.jpg" . ProtectedUploads::suffix());
        $o5 = $organizacion('logoprivado', 151.7, -41.7, 1, "statics/uploads/organizations/{$prefijo}-logo/logo.jpg");
        //El mapa solo enseña lo aprobado: las filas de aprobación se crean al arrancar, y la prueba ya arrancó.
        SystemApprovalManager::init();
        $aprobaciones = SystemApprovalsMapper::model();
        foreach ([[UsersModel::TABLE, $usuarios], [OrganizationMapper::TABLE, $organizaciones]] as [$tabla, $lista]) {
            foreach ($lista as $id) {
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: si no aprobara, c1 lo diría.
                $aprobaciones->update(['status' => SystemApprovalsMapper::STATUS_APPROVED])->where(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tabla),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
            }
        }
        $personas = new PersonsGeoSource();
        $empresas = new OrganizationsGeoSource();
        $enRincon = $personas->points($rincon);
        $check($ids($enRincon) === [$p1, $p6], 'c1 en el rincón salen las dos visibles y nada más', (string) json_encode($ids($enRincon)) . " (esperaba [{$p1},{$p6}])");
        $primero = $enRincon->points()[0] ?? null;
        $check($primero !== null && $primero->lng === 150.1234 && $primero->lat === -40.5678, 'c2 el punto va [lng, lat]: 150.1234, -40.5678', $primero !== null ? "[{$primero->lng}, {$primero->lat}]" : 'sin punto');
        $mundo = $personas->points(new GeoQuery(null, 0, ['search' => $nombre]));
        $check(!in_array($p2, $ids($mundo), true) && in_array($p1, $ids($mundo), true), 'c3 el (0,0) no sale en ningún encuadre', (string) json_encode($ids($mundo)));
        //El (0,0) se descarta en el SQL y otra vez al leer la fila: el total solo cuadra si lo descarta el SQL.
        $check($mundo->total() === count($mundo->points()), 'c3b el total tampoco cuenta el (0,0): lo descarta el SQL, no solo PHP', "total {$mundo->total()}, devueltos " . count($mundo->points()));
        $check(!in_array($p3, $ids($mundo), true), 'c4 un perfil oculto no sale');
        $check(!in_array($p4, $ids($mundo), true) && !in_array($p5, $ids($mundo), true), 'c5 un usuario inactivo o eliminado no sale');
        $cota = $personas->points(new GeoQuery([150.0, -41.0, 151.0, -40.0], 0, [], 1));
        $check($cota->total() === 2 && count($cota->points()) === 1 && $cota->truncated(), 'c6 con cota 1: total 2, devuelve 1 y dice que recortó', "total {$cota->total()}, devueltos " . count($cota->points()));
        $check(!$enRincon->truncated() && $enRincon->total() === 2, 'c7 CANARIO: sin cota no recorta');
        $check(!in_array($p7, $ids($enRincon), true) && in_array($p7, $ids($mundo), true), 'c8 el encuadre deja fuera un punto que el mundo sí ve');
        $orgRincon = $empresas->points($rincon);
        $check($ids($orgRincon) === [$o1], 'c9 organizaciones: en el rincón solo la visible', (string) json_encode($ids($orgRincon)) . " (esperaba [{$o1}])");
        $orgPunto = $orgRincon->points()[0] ?? null;
        $check($orgPunto !== null && $orgPunto->lng === 150.7 && $orgPunto->lat === -40.7, 'c10 organizaciones: [lng, lat] leído de meta', $orgPunto !== null ? "[{$orgPunto->lng}, {$orgPunto->lat}]" : 'sin punto');
        $orgMundo = $empresas->points(new GeoQuery(null, 0, ['search' => $nombre]));
        $check(!in_array($o3, $ids($orgMundo), true) && !in_array($o2, $ids($orgMundo), true) && in_array($o1, $ids($orgMundo), true), 'c11 organizaciones: ni el (0,0) ni la oculta', (string) json_encode($ids($orgMundo)));
        $check($orgMundo->total() === count($orgMundo->points()), 'c11b organizaciones: el total no cuenta el (0,0)', "total {$orgMundo->total()}, devueltos " . count($orgMundo->points()));
        $check($personas->pointDetail($p3) === null && $personas->pointDetail($p2) === null && $empresas->pointDetail($o2) === null, 'c12 el detalle de una oculta o en el (0,0) no existe');
        $detalle = $personas->pointDetail($p1);
        $check(is_array($detalle) && str_contains((string) $detalle['name'], $nombre) && is_string($detalle['url']) && $detalle['url'] !== '', 'c13 CANARIO: el detalle de una visible trae su nombre y su URL');
        echoTerminal(' ');

        //──── [i] La imagen de cada punto (P189): la original, o null para que el cliente pinte las iniciales ──
        echoTerminal('[i] La imagen de cada punto');
        $carpetaAvatar = AvatarModel::getFolderUser($p1);
        if (!is_dir($carpetaAvatar)) {
            $avatares[] = $carpetaAvatar;
            //RETORNO-IGNORADO: si no la crea, la copia de abajo falla e i0 lo dice.
            mkdir($carpetaAvatar, 0777, true);
        }
        $avatares[] = AvatarModel::getUserAvatarName($p1);
        $check(copy(basepath('statics/images/default-avatar.png'), AvatarModel::getUserAvatarName($p1)), 'i0 CANARIO: el avatar de la prueba se escribe');
        $conAvatar = $personas->points($rincon);
        $urlAvatar = $imagen($conAvatar, $p1);
        $check($esSegura($urlAvatar) && str_contains((string) $urlAvatar, "/geo/thumbnail/persons/{$p1}/64/?v="), 'i1 una persona con avatar lleva su miniatura a 64, con sello y una URL que el cliente pinta', (string) json_encode($urlAvatar));
        $check($imagen($conAvatar, $p6) === null, 'i2 una persona sin avatar lleva null', (string) json_encode($imagen($conAvatar, $p6)));
        $urlLogo = $imagen($orgRincon, $o1);
        $check($esSegura($urlLogo) && str_contains((string) $urlLogo, "/geo/thumbnail/organizations/{$o1}/64/?v="), 'i3 una organización con logo (relativo, de la aplicación) lleva su miniatura a 64', (string) json_encode($urlLogo));
        $check(str_contains((string) $imagen($empresas->points(new GeoQuery(null, 0, ['search' => $nombre])), $o5), "/geo/thumbnail/organizations/{$o5}/64/?v="), 'i5 una organización con el logo privado (el del formulario) también lleva su miniatura', (string) json_encode($imagen($empresas->points(new GeoQuery(null, 0, ['search' => $nombre])), $o5)));
        $check($imagen($empresas->points(new GeoQuery(null, 0, ['search' => $nombre])), $o4) === null, 'i4 una organización sin logo lleva null', (string) json_encode($imagen($empresas->points(new GeoQuery(null, 0, ['search' => $nombre])), $o4)));
        echoTerminal(' ');

        //──── [e] La búsqueda va por marcador ─────────────────────────────────────────────
        echoTerminal('[e] La búsqueda no es SQL ni patrón');
        $check($personas->points(new GeoQuery([150.0, -41.0, 151.0, -40.0], 0, ['search' => '%']))->total() === 0, 'e1 «%» no es un comodín: no amplía');
        $check($personas->points(new GeoQuery([150.0, -41.0, 151.0, -40.0], 0, ['search' => '_']))->total() === 0, 'e2 «_» no es un comodín: no amplía');
        $check($personas->points(new GeoQuery([150.0, -41.0, 151.0, -40.0], 0, ['search' => "' OR '1'='1"]))->total() === 0, 'e3 una comilla no rompe la consulta ni la amplía');
        $check($personas->points(new GeoQuery([150.0, -41.0, 151.0, -40.0], 0, ['search' => $nombre]))->total() === 2, 'e4 CANARIO: el nombre sí encuentra a las dos');
        $check($lanza(fn() => $personas->points(new GeoQuery(null, 0, ['organizations' => ['1 OR 1=1']]))), 'e5 un id de organización que no es entero lanza (400)');
        echoTerminal(' ');

        //──── [h] Las dos rutas, por HTTP ─────────────────────────────────────────────────
        echoTerminal('[h] Las rutas, por HTTP como root');
        $jwt = UserSessions::issueToken($crearUsuario('root', UsersModel::TYPE_USER_ROOT));
        $puntos = $camino(GeoController::routeName('points', [], true));
        $r = $pedir($puntos . '?' . http_build_query(['bbox' => '150,-41,151,-40', 'limit' => 1, 'sources' => ['persons', 'organizations']]), $jwt);
        $json = json_decode($r['body'], true);
        $features = is_array($json) ? ($json['features'] ?? []) : [];
        $coordenadas = [];
        foreach ($features as $f) {
            $coordenadas[$f['properties']['source'] . ':' . $f['properties']['id']] = $f['geometry']['coordinates'];
        }
        $check($r['status'] === 200 && ($json['type'] ?? null) === 'FeatureCollection' && ($json['sources']['persons']['returned'] ?? null) === 2, 'h1 200, GeoJSON, y el limit de la petición no recorta', "HTTP {$r['status']}");
        $check(($coordenadas["persons:{$p1}"] ?? null) === [150.1234, -40.5678], 'h2 las coordenadas viajan [lng, lat]', (string) json_encode($coordenadas["persons:{$p1}"] ?? null));
        $r = $pedir($puntos . '?' . http_build_query(['sources' => ['zz-no-existe']]), $jwt);
        $check($r['status'] === 404, 'h3 una fuente que no existe: 404', "HTTP {$r['status']}");
        $r = $pedir($puntos . '?' . http_build_query(['bbox' => '151,-41,150,-40']), $jwt);
        $check($r['status'] === 400, 'h4 un encuadre invertido: 400, no un recorte', "HTTP {$r['status']}");
        $r = $pedir($camino(GeoController::routeName('point', ['source' => 'persons', 'id' => $p3], true)), $jwt);
        $check($r['status'] === 404, 'h5 el detalle de un perfil oculto: 404', "HTTP {$r['status']}");
        $r = $pedir($camino(GeoController::routeName('point', ['source' => 'persons', 'id' => $p1], true)), $jwt);
        $check($r['status'] === 200 && str_contains($r['body'], $nombre), 'h6 CANARIO: el de una visible, 200 con su nombre', "HTTP {$r['status']}");
        $r = $pedir($puntos . '?' . http_build_query(['bbox' => '150,-41,151,-40', 'sources' => ['persons']]), $jwt);
        $json = json_decode($r['body'], true);
        $miniaturas = [];
        foreach (is_array($json) ? ($json['features'] ?? []) : [] as $f) {
            //Sin ??: un null que viaja es justo lo que se mide.
            $miniaturas[$f['properties']['id']] = array_key_exists('thumbnail', $f['properties']) ? $f['properties']['thumbnail'] : 'zz-sin-clave';
        }
        $check($esSegura($miniaturas[$p1] ?? null) && array_key_exists($p6, $miniaturas) && $miniaturas[$p6] === null, 'h7 la imagen viaja en el JSON: la URL del avatar, y null sin él', (string) json_encode($miniaturas));
        echoTerminal(' ');

        //──── [t] Las miniaturas: tamaños, permiso, caché en disco y regeneración ──────────────────
        echoTerminal('[t] Las miniaturas, por HTTP');
        $miniatura = fn(string $fuente, int $id, int $ancho): string => $camino(GeoController::routeName('thumbnail', ['source' => $fuente, 'id' => $id, 'size' => $ancho], true));
        $anchoDe = function (string $cuerpo): int {
            $info = @getimagesizefromstring($cuerpo);
            return is_array($info) ? (int) $info[0] : 0;
        };
        $patronP1 = basepath(GeoThumbnail::DIRECTORY . "/persons/{$p1}-64-*.png");
        array_push($generadas, $patronP1, basepath(GeoThumbnail::DIRECTORY . "/persons/{$p1}-128-*.png"), basepath(GeoThumbnail::DIRECTORY . "/organizations/{$o1}-64-*.png"));
        $r = $pedir($miniatura('persons', $p1, 64), $jwt);
        $check($r['status'] === 200 && $anchoDe($r['body']) === 64, 't1 la de 64 es una imagen de 64 de ancho', "HTTP {$r['status']}, ancho {$anchoDe($r['body'])}");
        $r = $pedir($miniatura('persons', $p1, 128), $jwt);
        $check($r['status'] === 200 && $anchoDe($r['body']) === 128, 't2 la de 128, de 128', "HTTP {$r['status']}, ancho {$anchoDe($r['body'])}");
        $r = $pedir($miniatura('persons', $p1, 65), $jwt);
        $check($r['status'] === 404, 't3 un tamaño que no es 64 ni 128: 404', "HTTP {$r['status']}");
        $r = $pedir($miniatura('persons', $p6, 64), $jwt);
        $check($r['status'] === 404, 't4 un punto sin imagen: 404', "HTTP {$r['status']}");
        clearstatcache();
        $cacheP1 = $unaEnDisco($patronP1);
        $nodo = $cacheP1 !== null ? fileinode($cacheP1) : false;
        $pedir($miniatura('persons', $p1, 64), $jwt);
        clearstatcache();
        $check($nodo !== false && $cacheP1 !== null && $unaEnDisco($patronP1) === $cacheP1 && fileinode($cacheP1) === $nodo, 't5 la segunda petición la sirve del disco: el archivo generado es el mismo', (string) json_encode([$cacheP1, $nodo]));
        //RETORNO-IGNORADO: si no cambiara la fecha, t6 lo diría.
        touch(AvatarModel::getUserAvatarName($p1), time() + 5);
        $r = $pedir($miniatura('persons', $p1, 64), $jwt);
        clearstatcache();
        $cacheT6 = $unaEnDisco($patronP1);
        $check($r['status'] === 200 && $cacheT6 !== null && $cacheT6 !== $cacheP1 && !is_file((string) $cacheP1), 't6 un original más nuevo da otra huella, y la vieja se borra', (string) json_encode([$cacheP1, glob($patronP1)]));
        //Con la caché por fecha, un original SUSTITUIDO por otro de fecha más vieja seguía sirviendo la miniatura vieja.
        $antes = sha1($r['body']);
        $otro = imagecreatetruecolor(200, 200);
        $check($otro !== false && imagefill($otro, 0, 0, (int) imagecolorallocate($otro, 200, 30, 30)) && imagepng($otro, AvatarModel::getUserAvatarName($p1)), 't6a el original se sustituye por otra imagen');
        //RETORNO-IGNORADO: si no cambiara la fecha, la prueba no mediría nada y t6b lo diría por la huella.
        touch(AvatarModel::getUserAvatarName($p1), time() - 3600);
        $r = $pedir($miniatura('persons', $p1, 64), $jwt);
        clearstatcache();
        $check($r['status'] === 200 && sha1($r['body']) !== $antes && $unaEnDisco($patronP1) !== null && !is_file((string) $cacheT6), 't6b otro original con una fecha MÁS VIEJA da otra miniatura, y la del primero ya no está', (string) json_encode([$r['status'], $antes, sha1($r['body']), $cacheT6, glob($patronP1)]));
        $r = $pedir($miniatura('organizations', $o1, 64), $jwt);
        $check($r['status'] === 200 && $anchoDe($r['body']) === 64, 't7 la de una organización sale de su logo', "HTTP {$r['status']}");
        $generadas[] = basepath(GeoThumbnail::DIRECTORY . "/organizations/{$o5}-64-*.png");
        $r = $pedir($miniatura('organizations', $o5, 64), $jwt);
        $check($r['status'] === 200 && $anchoDe($r['body']) === 64, 't7b y la de un logo privado, también', "HTTP {$r['status']}");
        $check($pedir($miniatura('persons', $p1, 64), '')['status'] === $pedir($puntos, '')['status'], 't8 sin sesión, lo mismo que points');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            foreach ($enDisco($generadas) as $ruta) {
                if (is_file($ruta)) {
                    //RETORNO-IGNORADO: lo comprueba z4 contando lo que queda.
                    unlink($ruta);
                }
            }
            if (is_dir(basepath(GeoThumbnail::DIRECTORY . '/zz-solo-root'))) {
                //RETORNO-IGNORADO: la carpeta de la fuente de prueba; si no se vacía, la deja la siguiente corrida igual.
                @rmdir(basepath(GeoThumbnail::DIRECTORY . '/zz-solo-root'));
            }
            foreach (array_reverse($avatares) as $ruta) {
                if (is_file($ruta)) {
                    //RETORNO-IGNORADO: lo comprueba z3 contando lo que queda.
                    unlink($ruta);
                } elseif (is_dir($ruta)) {
                    //RETORNO-IGNORADO: lo comprueba z3 contando lo que queda.
                    rmdir($ruta);
                }
            }
            $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
            foreach ($organizaciones as $id) {
                $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([OrganizationMapper::TABLE, (string) $id]);
                $database->prepare('DELETE FROM `' . OrganizationMapper::TABLE . '` WHERE id = ?')->execute([$id]);
            }
            foreach ($usuarios as $id) {
                foreach ($referencias as [$tabla, $columna]) {
                    $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
                }
                $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
                $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
            }
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedan}");
        $quedanOrg = (int) $database->query('SELECT COUNT(*) FROM `' . OrganizationMapper::TABLE . '` WHERE nit LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedanOrg === 0, 'z2 no queda ninguna organización de la prueba', "quedan {$quedanOrg}");
        $check(count(array_filter($avatares, 'file_exists')) === 0, 'z3 no queda el avatar de la prueba');
        $check(count($enDisco($generadas)) === 0, 'z4 no queda ninguna miniatura de la prueba');
    }

    return $balance();

})->setDescription('El geovisor (C11, ronda 1): el registro de fuentes (clave repetida, sustituir, visibles por permiso), GeoQuery valida y no recorta, los puntos [lng,lat] de personas y organizaciones sin el (0,0) ni lo oculto, inactivo o eliminado, la cota y el encuadre, 403/404/400 de las rutas, la búsqueda por marcador, la imagen de cada punto (P189) y sus miniaturas (tamaños, permiso, caché y regeneración). En proceso y por HTTP; siembra zz- y lo retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
