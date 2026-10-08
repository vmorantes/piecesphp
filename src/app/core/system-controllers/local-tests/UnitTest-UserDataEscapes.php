<?php

//Un marcador distinto por campo: que otra salida del mismo texto no tape un «no sale crudo».
//El contenido enriquecido es el canario: tiene que salir EXACTAMENTE como se guardó.

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\BuiltIn\Banner\Mappers\BuiltInBannerMapper;
use PiecesPHP\Core\BaseModel;
use Documents\Mappers\DocumentsMapper;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/user-data-escapes', function ($args) {

    echoTerminal("\e[33m[TEST:UserDataEscapes] Lo que escribe cualquier usuario con sesión se pinta escapado, y el contenido enriquecido no cambia\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la suite view-escapes.
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
    $cabeceraToken = SessionToken::tokenName();
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, ?string $jwt, ?array $query = null) use ($base, $cabeceraToken): array {
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/') . ($query !== null ? '?' . http_build_query($query) : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [],
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    //Lo que pide DataTables: sin columnas ni orden, el helper da 500.
    $tabla = ['draw' => 1, 'start' => 0, 'length' => 5000, 'columns' => [['data' => 0, 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']]], 'order' => [['column' => 0, 'dir' => 'desc']], 'search' => ['value' => '', 'regex' => 'false']];
    //Las tarjetas que pinta una vista llegan en `rawData`; `data` son las columnas que monta el controlador.
    $celdasDe = function (string $body, string $clave = 'data'): string {
        $datos = json_decode($body, true);
        $html = '';
        foreach ((is_array($datos) && is_array($datos[$clave] ?? null) ? $datos[$clave] : []) as $fila) {
            $html .= is_array($fila) ? implode(' ', array_map(fn ($c) => is_scalar($c) ? (string) $c : '', $fila)) : (is_scalar($fila) ? (string) $fila : '');
        }
        return $html;
    };

    $sufijo = bin2hex(random_bytes(3));
    $prefijo = 'zz-prueba-datos-' . $sufijo;
    //Cada campo, su marcador: comilla simple, comilla doble y una etiqueta que solo existe si sale cruda.
    $m = fn (string $clave): string => "zz'{$clave}\"<{$clave}>";
    $crudo = fn (string $clave): string => "<{$clave}>";
    $esc = fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    //Escapado en su sitio y en ningún sitio crudo.
    $escapado = function (string $html, string $clave, string $nombre, int $status) use ($check, $m, $crudo, $esc): bool {
        return $check($status === 200 && str_contains($html, $esc($m($clave))) && !str_contains($html, $crudo($clave)), $nombre, "HTTP {$status}, escapado " . var_export(str_contains($html, $esc($m($clave))), true) . ', crudo ' . var_export(str_contains($html, $crudo($clave)), true));
    };

    $tablaUsuarios = UsersModel::TABLE;
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $tablaAprobaciones = SystemApprovalsMapper::TABLE;
    $tablaPerfiles = UserProfileMapper::TABLE;
    $tablaOpciones = (new SettingsModel())->getModel()->getTable();
    $usuarios = [];
    $organizaciones = [];
    $publicaciones = [];
    $banners = [];
    $documentos = [];
    $tiposDocumento = [];
    $categoriasFormularios = [];
    $carpetasSubidas = [];
    $noticias = [];
    $categoriasNoticias = [];
    $categoriasPublicaciones = [];
    $fotoOpciones = $database->query("SELECT name, value FROM `{$tablaOpciones}`")->fetchAll(\PDO::FETCH_KEY_PAIR);
    //La tabla de opciones vuelve a la foto: el SEO materializa filas por idioma al pintarse, y el propietario se planta.
    //Solo se revierte lo que la suite plantó: un cambio ajeno de la misma tabla durante la corrida se respeta.
    $plantadas = [];
    $reponerOpciones = function () use ($database, $tablaOpciones, $fotoOpciones, &$plantadas): void {
        $ahora = $database->query("SELECT name, value FROM `{$tablaOpciones}`")->fetchAll(\PDO::FETCH_KEY_PAIR);
        foreach ($ahora as $nombre => $valor) {
            if (!array_key_exists($nombre, $fotoOpciones)) {
                $database->prepare("DELETE FROM `{$tablaOpciones}` WHERE BINARY name = ?")->execute([$nombre]);
            } elseif (in_array($nombre, $plantadas, true) && $fotoOpciones[$nombre] !== $valor) {
                $database->prepare("UPDATE `{$tablaOpciones}` SET value = ? WHERE BINARY name = ?")->execute([$fotoOpciones[$nombre], $nombre]);
            }
        }
    };
    $plantarOpcion = function (string $nombre, string $valor) use ($database, $tablaOpciones, &$plantadas): void {
        $plantadas[] = $nombre;
        $existe = $database->prepare("SELECT COUNT(*) FROM `{$tablaOpciones}` WHERE BINARY name = ?");
        $existe->execute([$nombre]);
        if ((int) $existe->fetchColumn() > 0) {
            $database->prepare("UPDATE `{$tablaOpciones}` SET value = ? WHERE BINARY name = ?")->execute([$valor, $nombre]);
        } else {
            $database->prepare("INSERT INTO `{$tablaOpciones}` (name, value) VALUES (?, ?)")->execute([$nombre, $valor]);
        }
    };

    //Lo que dejó una corrida anterior cortada a medias: todo cuelga del prefijo, por la carpeta o por el usuario.
    $retirarRestos = function () use ($database, $tablaUsuarios, $tablaOrganizaciones, $tablaAprobaciones, $tablaPerfiles): int {
        $patron = $database->quote('zz-prueba-datos-%');
        $orgs = $database->query("SELECT id FROM `{$tablaOrganizaciones}` WHERE folder LIKE {$patron}")->fetchAll(\PDO::FETCH_COLUMN);
        $listaOrgs = $orgs === [] ? '0' : implode(',', array_map('intval', $orgs));
        $users = $database->query("SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE {$patron} OR organization IN ({$listaOrgs})")->fetchAll(\PDO::FETCH_COLUMN);
        $listaUsers = $users === [] ? '0' : implode(',', array_map('intval', $users));
        $restos = count($orgs) + count($users);
        $database->exec("DELETE FROM `{$tablaAprobaciones}` WHERE referenceAlias LIKE {$patron} OR (referenceTable = '{$tablaUsuarios}' AND referenceValue IN ({$listaUsers})) OR (referenceTable = '{$tablaOrganizaciones}' AND referenceValue IN ({$listaOrgs})) OR createdBy IN ({$listaUsers})");
        $database->exec("DELETE FROM `{$tablaPerfiles}` WHERE belongsTo IN ({$listaUsers})");
        $database->exec("DELETE FROM `actions_log` WHERE createdBy IN ({$listaUsers})");
        $database->exec("DELETE FROM `login_attempts` WHERE userID IN ({$listaUsers}) OR usernameAttempt LIKE " . $database->quote("zz'zzlogin%"));
        $database->exec('DELETE FROM `' . DocumentsMapper::TABLE . "` WHERE folder LIKE {$patron}");
        $database->exec("DELETE FROM `forms_document_types` WHERE folder LIKE {$patron}");
        $database->exec("DELETE FROM `forms_categories` WHERE folder LIKE {$patron}");
        $database->exec('DELETE FROM `' . BuiltInBannerMapper::TABLE . "` WHERE folder LIKE {$patron}");
        $database->exec('DELETE FROM `' . PublicationMapper::TABLE . "` WHERE author IN ({$listaUsers}) OR createdBy IN ({$listaUsers})");
        $database->exec("DELETE FROM `news_elements` WHERE createdBy IN ({$listaUsers}) OR folder LIKE {$patron}");
        //Las categorías no tienen carpeta ni autor: se reconocen por su marcador.
        $database->exec("DELETE FROM `news_categories` WHERE name LIKE " . $database->quote("zz'zzncat%"));
        $database->exec("DELETE FROM `publications_categories` WHERE name LIKE " . $database->quote("zz'zzpcat%"));
        //Y en disco, las carpetas de subida de una corrida que murió sin limpiar.
        $raiz = append_to_path_system((string) get_config('upload_dir'), \Documents\Controllers\DocumentsController::UPLOAD_DIR);
        foreach ((array) glob($raiz . '/zz-prueba-datos-*-subida', GLOB_ONLYDIR) as $dir) {
            foreach ((array) glob($dir . '/{,.}*', GLOB_BRACE) as $archivo) {
                if (is_string($archivo) && is_file($archivo)) {
                    //RETORNO-IGNORADO: lo que no se borre lo vuelve a encontrar la corrida siguiente.
                    unlink($archivo);
                }
            }
            //RETORNO-IGNORADO: lo que no se borre lo vuelve a encontrar la corrida siguiente.
            rmdir((string) $dir);
        }
        $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE id IN ({$listaUsers}) AND organization IN ({$listaOrgs})");
        $database->exec("DELETE FROM `{$tablaOrganizaciones}` WHERE id IN ({$listaOrgs})");
        $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE id IN ({$listaUsers})");
        return $restos;
    };
    $restos = $retirarRestos();
    echoTerminal("   medido: restos de corridas anteriores retirados: {$restos} (usuarios y organizaciones)");

    try {
        $ahora = date('Y-m-d H:i:s');
        $lang = (string) get_config('default_lang');
        $meta = fn (array $extra = []): string => (string) json_encode(['baseLang' => $lang, 'langData' => new \stdClass] + $extra);

        $crearUsuario = function (int $tipo, int $organizacion, string $nombre, string $apellido, string $correo, string $usuario) use (&$usuarios): int {
            $u = new UsersModel();
            $u->username = $usuario;
            $u->email = $correo;
            $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
            $u->firstname = $nombre;
            $u->secondname = '';
            $u->firstLastname = $apellido;
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = $organizacion;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[] = (int) $u->id;
            return (int) $u->id;
        };
        $jwtDe = fn (int $id): string => SessionToken::generateToken(['id' => $id], null, null, false);
        $aprobar = function (string $tablaReferencia, int $id) use ($database, $tablaAprobaciones, $prefijo, $ahora, &$rootID): void {
            $database->prepare("INSERT INTO `{$tablaAprobaciones}` (referenceAlias, referenceValue, referenceTable, referenceDate, createdAt, createdBy, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute(["{$prefijo}-aprobacion", (string) $id, $tablaReferencia, $ahora, $ahora, $rootID, SystemApprovalsMapper::STATUS_APPROVED]);
        };

        $rootID = $crearUsuario(UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL, 'Zz', 'Datos', "{$prefijo}-root@example.com", "{$prefijo}-root");
        $jwtRoot = $jwtDe($rootID);

        //Una ubicación cualquiera de la base: el perfil completo la exige.
        $ubicacion = $database->query('SELECT c.id AS city, s.country AS country FROM `locations_cities` c JOIN `locations_states` s ON s.id = c.state LIMIT 1')->fetch(\PDO::FETCH_ASSOC);
        if (!$check(is_array($ubicacion), 'p2 hay una ciudad en la base para completar perfiles')) {
            return $balance();
        }
        $ciudad = (int) $ubicacion['city'];
        $pais = (int) $ubicacion['country'];

        //La organización O, con administrador, y O2, sin él.
        $insertarOrganizacion = function (array $columnas, array $metaExtra) use ($database, $tablaOrganizaciones, $meta, $prefijo, $ahora, $rootID, &$organizaciones): int {
            $columnas += ['code' => 'zz' . bin2hex(random_bytes(4)), 'nit' => 'zz-' . bin2hex(random_bytes(4)), 'folder' => "{$prefijo}-org-" . bin2hex(random_bytes(2)), 'createdAt' => $ahora, 'createdBy' => $rootID, 'status' => OrganizationMapper::ACTIVE, 'meta' => $meta($metaExtra)];
            $nombres = array_keys($columnas);
            $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (`" . implode('`, `', $nombres) . '`) VALUES (' . implode(', ', array_fill(0, count($nombres), '?')) . ')')->execute(array_values($columnas));
            $id = (int) $database->lastInsertId();
            $organizaciones[] = $id;
            return $id;
        };
        $orgID = $insertarOrganizacion([
            'name' => $m('zzo'), 'nit' => $m('zznit'), 'activitySector' => $m('zzs'), 'actionLines' => json_encode([$m('zzal')]),
            'country' => $pais, 'city' => $ciudad, 'address' => $m('zzaddr'), 'phone' => $m('zzph'), 'linkedinLink' => $m('zzoli'),
            'websiteLink' => $m('zzowe'), 'informativeEmail' => $m('zzie'), 'billingEmail' => $m('zzbe'), 'logo' => null,
        ], ['phoneCode' => '+57', 'latitude' => 10.5, 'longitude' => -74.5, 'affiliatedInstitutions' => [$m('zzinst')]]);
        $org2ID = $insertarOrganizacion(['name' => $m('zzot'), 'activitySector' => 'zz'], ['phoneCode' => '+57', 'latitude' => 10.5, 'longitude' => -74.5, 'affiliatedInstitutions' => []]);

        //El administrador de O: nombre, correo y usuario cortos (la tarjeta los recorta a 30).
        $adminID = $crearUsuario(UsersModel::TYPE_USER_ADMIN_ORG, $orgID, $m('zzn'), 'Zz', $m('zzm') . '@zz.test', $m('zzu' . $sufijo));
        $database->prepare("UPDATE `{$tablaOrganizaciones}` SET meta = ? WHERE id = ?")->execute([$meta(['phoneCode' => '+57', 'latitude' => 10.5, 'longitude' => -74.5, 'affiliatedInstitutions' => [$m('zzinst')], 'administrator' => $adminID]), $orgID]);
        //Los otros tipos con formulario de perfil propio, y un usuario de O2 que podría administrarla.
        $tipos = [UsersModel::TYPE_USER_GENERAL => 'zzmg', UsersModel::TYPE_USER_INSTITUCIONAL => 'zzmi', UsersModel::TYPE_USER_COMUNICACIONES => 'zzmc'];
        $porTipo = [UsersModel::TYPE_USER_ADMIN_ORG => $adminID];
        foreach ($tipos as $tipo => $clave) {
            $porTipo[$tipo] = $crearUsuario($tipo, $orgID, 'Zz', 'Tipo', $m($clave) . '@zz.test', "{$prefijo}-t{$tipo}");
        }
        $candidatoID = $crearUsuario(UsersModel::TYPE_USER_GENERAL, $org2ID, $m('zzn2'), 'Zz', "{$prefijo}-c@example.com", "{$prefijo}-c");
        //El perfil del administrador: incompleto (sin nacionalidad) hasta [c].
        $database->prepare("INSERT INTO `{$tablaPerfiles}` (jobPosition, phoneCode, phoneNumber, nationality, linkedinLink, websiteLink, country, city, latitude, longitude, belongsTo, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$m('zzjob'), '+57', $m('zzpn'), null, 'https://zz.test/?q=' . $m('zzlin'), "javascript:zzWeb('" . $crudo('zzweb') . "')", $pais, $ciudad, 10.5, -74.5, $adminID, $ahora, $rootID, $meta(['affiliatedInstitutions' => [$m('zzuinst')]])]);
        $aprobar($tablaUsuarios, $adminID);
        $aprobar($tablaOrganizaciones, $orgID);

        //──── [a] El propietario: panel, pie público y problemas ─────────────────────────────
        echoTerminal('[a] El propietario, plantado en la base, sale escapado en el menú del panel, el pie público y la página de problemas');
        $categoria = (int) (PublicationCategoryMapper::uncategorizedCategory()->id ?? 0);
        $rico = '<p class="zz-rico">Texto <strong>rico</strong> &amp; <a href="https://zz.test/rico">enlace</a><br><em>«dos»</em></p>';
        $publicacion = new PublicationMapper();
        $publicacion->baseLang = $lang;
        foreach (['title' => "{$prefijo} publicacion", 'content' => $rico, 'seoDescription' => 'zz', 'publicDate' => new \DateTime('-1 day'), 'startDate' => null, 'endDate' => null, 'category' => $categoria, 'visits' => 0, 'author' => $adminID, 'folder' => str_replace('.', '', uniqid()), 'featured' => PublicationMapper::UNFEATURED, 'mainImage' => 'statics/images/zz-prueba.jpg', 'thumbImage' => 'statics/images/zz-prueba.jpg', 'ogImage' => ''] as $campo => $valor) {
            $publicacion->setLangData($lang, $campo, $valor);
        }
        $publicacion->status = PublicationMapper::ACTIVE;
        //`save()` exige un usuario en sesión, y el terminal no tiene: se le presta y se devuelve el que había.
        $usuarioPrevio = get_config('current_user');
        $guardadoPrevio = get_config('pcsphp_current_user_stored');
        try {
            set_config('current_user', (object) ['id' => $rootID]);
            set_config('pcsphp_current_user_stored', null);
            $publicacion->save();
        } finally {
            set_config('current_user', $usuarioPrevio);
            set_config('pcsphp_current_user_stored', $guardadoPrevio);
        }
        $publicacionID = (int) $publicacion->id;
        $publicaciones[] = $publicacionID;
        $publica = new PublicationMapper($publicacionID);
        $rutaPublica = $camino(\Publications\Controllers\PublicationsPublicController::routeName('single', ['slug' => $publica->getSlug($lang)], true));
        $plantarOpcion('owner', $m('zzowner'));
        try {
            $panel = $pedir($camino(\PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName('', [], true)), $jwtRoot);
            $publicaPagina = $pedir($rutaPublica, null);
            $problemas = $pedir($camino(\PiecesPHP\UserSystem\Controllers\UserProblemsController::routeName('problems-list', [], true)), null);
        } finally {
            $reponerOpciones();
        }
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y el caso falla.
        preg_match('#<span only-expanded class="main">(.*?)</span>#s', $panel['body'], $enMenu);
        $check($panel['status'] === 200 && trim($enMenu[1] ?? '') === $esc($m('zzowner')), 'a1 menu.php: el propietario del pie del menú sale escapado', "HTTP {$panel['status']}, " . var_export(isset($enMenu[1]), true));
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y el caso falla.
        preg_match('#<div class="copy">(.*?)\|#s', $publicaPagina['body'], $enPie);
        $check($publicaPagina['status'] === 200 && trim($enPie[1] ?? '') === $esc($m('zzowner')), 'a2 footer.php: el propietario del pie público sale escapado', "HTTP {$publicaPagina['status']}, " . var_export(isset($enPie[1]), true));
        $check($problemas['status'] === 200 && str_contains($problemas['body'], $esc($m('zzowner'))) && !str_contains($problemas['body'], $crudo('zzowner')), 'a3 problems-list.php: el propietario sale escapado', "HTTP {$problemas['status']}");

        //──── [r] El canario del contenido enriquecido (P107) ───────────────────────────────
        echoTerminal('');
        echoTerminal('[r] El contenido enriquecido de una publicación sale EXACTAMENTE como se guardó');
        $check($publicaPagina['status'] === 200 && str_contains($publicaPagina['body'], $rico), 'r1 CANARIO: la página pública pinta el HTML del editor tal cual, sin escapar', "HTTP {$publicaPagina['status']}");
        $check(!str_contains($publicaPagina['body'], $esc($rico)), 'r2 y no aparece escapado en ningún sitio');

        //──── [b] El SEO ────────────────────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[b] La pantalla de SEO pinta escapados el título, el propietario y la descripción');
        $plantarOpcion('title_app', $m('zztitle'));
        $plantarOpcion('owner', $m('zzowner'));
        $plantarOpcion('description', $m('zzdesc'));
        try {
            $seo = $pedir($camino(\PiecesPHP\Settings\Controllers\SettingsController::routeName('appearance-seo', [], true)), $jwtRoot);
        } finally {
            $reponerOpciones();
        }
        $check(str_contains($seo['body'], 'name="titleApp" value="' . $esc($m('zztitle')) . '"'), 'b1 seo.php: el título del sitio, escapado en su value', "HTTP {$seo['status']}");
        $check(str_contains($seo['body'], 'name="owner" value="' . $esc($m('zzowner')) . '"'), 'b2 seo.php: el propietario, escapado en su value', "HTTP {$seo['status']}");
        $check((bool) preg_match('#<textarea required name="description"[^>]*>' . preg_quote($esc($m('zzdesc')), '#') . '</textarea>#', $seo['body']), 'b3 seo.php: la descripción, escapada dentro del textarea', "HTTP {$seo['status']}");
        $check($seo['status'] === 200 && !str_contains($seo['body'], $crudo('zztitle')) && !str_contains($seo['body'], $crudo('zzdesc')), 'b4 y ninguno de los dos sale crudo en la página', "HTTP {$seo['status']}");
        $check($database->query("SELECT name, value FROM `{$tablaOpciones}`")->fetchAll(\PDO::FETCH_KEY_PAIR) == $fotoOpciones, 'b5 la tabla de opciones queda como estaba');

        //──── [c] Perfiles: incompletos, completos y sus enlaces ─────────────────────────────
        echoTerminal('');
        echoTerminal('[c] Los perfiles de persona y de organización, incompletos y completos; los enlaces sin http(s) no van al href');
        $rutaPerfil = $camino(\MySpace\Controllers\ProfileController::routeName('profile', ['userID' => $adminID], true));
        $rutaPerfilOrg = $camino(\MySpace\Controllers\OrganizationProfileController::routeName('profile', ['organizationID' => $orgID], true));
        $r = $pedir($rutaPerfil, $jwtRoot);
        $escapado($r['body'], 'zzn', 'c1 profile/profile-not-completed.php: el nombre del usuario', $r['status']);
        $r = $pedir($rutaPerfilOrg, $jwtRoot);
        $escapado($r['body'], 'zzo', 'c2 profile-organization/profile-not-completed.php: el nombre de la organización', $r['status']);
        $database->prepare("UPDATE `{$tablaPerfiles}` SET nationality = ? WHERE belongsTo = ?")->execute(['zz-nacionalidad', $adminID]);
        $database->prepare("UPDATE `{$tablaOrganizaciones}` SET logo = ? WHERE id = ?")->execute(['statics/images/zz-prueba.jpg', $orgID]);
        $perfil = $pedir($rutaPerfil, $jwtRoot);
        foreach (['zzn' => 'el nombre', 'zzjob' => 'el cargo', 'zzuinst' => 'una institución', 'zzpn' => 'el teléfono', 'zzm' => 'el correo'] as $clave => $que) {
            $escapado($perfil['body'], $clave, "c3 profile/profile.php: {$que}", $perfil['status']);
        }
        $perfilOrg = $pedir($rutaPerfilOrg, $jwtRoot);
        foreach (['zzo' => 'el nombre', 'zzs' => 'el sector', 'zzn' => 'el administrador', 'zzinst' => 'una institución', 'zzpn' => 'el teléfono del administrador'] as $clave => $que) {
            $escapado($perfilOrg['body'], $clave, "c4 profile-organization/profile.php: {$que}", $perfilOrg['status']);
        }
        foreach (['profile.php' => $perfil, 'profile-organization/profile.php' => $perfilOrg] as $vista => $pagina) {
            $check(!str_contains($pagina['body'], "href='javascript:") && str_contains($pagina['body'], $esc("javascript:zzWeb('" . $crudo('zzweb') . "')")), "c5 {$vista}: la web «javascript:» sale como texto, sin enlace", "HTTP {$pagina['status']}");
            $check(str_contains($pagina['body'], "href='" . $esc('https://zz.test/?q=' . $m('zzlin')) . "'"), "c6 {$vista}: CANARIO, LinkedIn con https sí es enlace, escapado", "HTTP {$pagina['status']}");
        }

        $database->prepare("UPDATE `{$tablaPerfiles}` SET websiteLink = ? WHERE belongsTo = ?")->execute(["JaVaScRiPt:zzWeb('" . $crudo('zzweb') . "')", $adminID]);
        $r = $pedir($rutaPerfil, $jwtRoot);
        $check($r['status'] === 200 && stripos($r['body'], "href='javascript:") === false && str_contains($r['body'], $esc("JaVaScRiPt:zzWeb('" . $crudo('zzweb') . "')")), 'c7 profile.php: «JaVaScRiPt:» en mayúsculas mixtas tampoco llega al href', "HTTP {$r['status']}");

        //──── [d] Mi organización y la asignación de administrador ─────────────────────────────
        echoTerminal('');
        echoTerminal('[d] El formulario de la organización propia y el de asignar administrador');
        $rutaMiOrg = fn (int $id): string => $camino(\MySpace\Controllers\MyOrganizationProfileController::routeName('my-organization-profile', ['organizationID' => $id], true));
        $miOrg = $pedir($rutaMiOrg($orgID), $jwtRoot);
        foreach (['zzo' => 'el nombre', 'zzs' => 'el sector', 'zzie' => 'el correo informativo', 'zzph' => 'el teléfono', 'zzoli' => 'LinkedIn', 'zzowe' => 'la web', 'zzn' => 'el administrador (y su opción)', 'zzm' => 'el correo del administrador', 'zzinst' => 'las instituciones (opción y valor)'] as $clave => $que) {
            $escapado($miOrg['body'], $clave, "d1 my-organization-profile.php: {$que}", $miOrg['status']);
        }
        $check(str_contains($miOrg['body'], "<option selected value='" . $esc($m('zzinst')) . "'>" . $esc($m('zzinst')) . '</option>') || str_contains($miOrg['body'], "<option selected  value='" . $esc($m('zzinst')) . "'>"), 'd2 y la institución guardada sigue marcada como elegida', 'sin la opción marcada');
        $asignar = $pedir($rutaMiOrg($org2ID), $jwtRoot);
        $escapado($asignar['body'], 'zzot', 'd3 my-organization-profile-assign-administrator.php: el nombre de la organización', $asignar['status']);
        $escapado($asignar['body'], 'zzn2', 'd4 y el candidato a administrador en su opción', $asignar['status']);

        //──── [e] La edición de la organización ─────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[e] La edición de la organización');
        $edicionOrg = $pedir($camino(\Organizations\Controllers\OrganizationsController::routeName('forms-edit', ['id' => $orgID, 'lang' => $lang], true)), $jwtRoot);
        foreach (['zzo' => 'el nombre', 'zzs' => 'el sector', 'zzal' => 'una línea de acción añadida (opción y valor)', 'zzph' => 'el teléfono', 'zzie' => 'el correo informativo', 'zzoli' => 'LinkedIn', 'zzowe' => 'la web', 'zznit' => 'el NIT', 'zzaddr' => 'la dirección', 'zzbe' => 'el correo de facturación', 'zzn' => 'el administrador en su opción'] as $clave => $que) {
            $escapado($edicionOrg['body'], $clave, "e1 organizations/forms/edit.php: {$que}", $edicionOrg['status']);
        }
        $check((bool) preg_match("#<option\s+selected\s+value='" . preg_quote($esc($m('zzal')), '#') . "'#", $edicionOrg['body']), 'e2 y la línea añadida sigue marcada como elegida');

        //──── [f] Usuarios: formularios por tipo y la tarjeta del listado ───────────────────────
        echoTerminal('');
        echoTerminal('[f] Los formularios de usuario por tipo y la tarjeta del listado');
        //El {type} del alta va cifrado, como lo arma la pantalla de selección (UsersController::selectionTypeToCreate).
        $controladorUsuarios = new \ReflectionClass(\PiecesPHP\UserSystem\Controllers\UsersController::class);
        $ofuscado = (string) $controladorUsuarios->getProperty('ofuscate')->getDefaultValue();
        $claveTipo = (string) $controladorUsuarios->getProperty('password')->getDefaultValue();
        $tipoCifrado = fn (int $tipo): string => \PiecesPHP\Core\BaseHashEncryption::encrypt(strrev($ofuscado) . ":{$tipo}:" . $ofuscado, $claveTipo);
        foreach ([UsersModel::TYPE_USER_ADMIN_ORG => 'admin-organization', UsersModel::TYPE_USER_COMUNICACIONES => 'comunicaciones', UsersModel::TYPE_USER_GENERAL => 'general', UsersModel::TYPE_USER_INSTITUCIONAL => 'institucional'] as $tipo => $carpeta) {
            $r = $pedir($camino(\PiecesPHP\UserSystem\Controllers\UsersController::routeName('form-create', ['type' => $tipoCifrado($tipo)], true)), $jwtRoot);
            $escapado($r['body'], 'zzo', "f1 form-by-type/create/{$carpeta}: la organización en su opción", $r['status']);
            $r = $pedir($camino(\PiecesPHP\UserSystem\Controllers\UsersController::routeName('form-edit', ['id' => $porTipo[$tipo]], true)), $jwtRoot);
            $escapado($r['body'], 'zzo', "f2 form-by-type/edit/{$carpeta}: la organización en su opción", $r['status']);
        }
        foreach ([UsersModel::TYPE_USER_COMUNICACIONES => 'comunicaciones', UsersModel::TYPE_USER_GENERAL => 'general', UsersModel::TYPE_USER_INSTITUCIONAL => 'institucional'] as $tipo => $carpeta) {
            $r = $pedir($camino(\PiecesPHP\UserSystem\Controllers\UsersController::routeName('form-profile', [], true)), $jwtDe($porTipo[$tipo]));
            $check($r['status'] === 200 && str_contains($r['body'], 'name="email" value="' . $esc($m($tipos[$tipo]) . '@zz.test') . '"'), "f3 form-by-type/profile/{$carpeta}: el correo propio, escapado en su value", "HTTP {$r['status']}");
        }
        $tarjetas = $pedir($camino(\PiecesPHP\UserSystem\Controllers\UsersController::routeName('datatables', [], true)), $jwtRoot, $tabla);
        $celdas = $celdasDe($tarjetas['body'], 'rawData');
        foreach (['zzn' => 'el nombre', 'zzu' . $sufijo => 'el usuario', 'zzm' => 'el correo', 'zzo' => 'la organización'] as $clave => $que) {
            $escapado($celdas, $clave, "f4 usuarios/utils/user-card.php: {$que}", $tarjetas['status']);
        }

        //──── [g] Documentos y Formularios ──────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[g] Documentos, sus tipos y las categorías de Formularios');
        $database->prepare('INSERT INTO `forms_document_types` (documentTypeName, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?)')->execute([$m('zzdt'), "{$prefijo}-tipo", 1, $ahora, $rootID, $meta()]);
        $tipoID = (int) $database->lastInsertId();
        $tiposDocumento[] = $tipoID;
        $database->prepare('INSERT INTO `forms_categories` (categoryName, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?)')->execute([$m('zzcat'), "{$prefijo}-cat", 1, $ahora, $rootID, $meta()]);
        $categoriaFormID = (int) $database->lastInsertId();
        $categoriasFormularios[] = $categoriaFormID;
        $database->prepare('INSERT INTO `' . DocumentsMapper::TABLE . '` (documentType, documentName, description, document, documentImage, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$tipoID, $m('zzdn'), $m('zzdd'), 'statics/images/zz-prueba.jpg', 'statics/images/zz-prueba.jpg', "{$prefijo}-documento", 1, $ahora, $rootID, $meta()]);
        $documentoID = (int) $database->lastInsertId();
        $documentos[] = $documentoID;
        $r = $pedir($camino(\Documents\Controllers\DocumentsController::routeName('forms-add', [], true)), $jwtRoot);
        $escapado($r['body'], 'zzdt', 'g1 documents/forms/add.php: el tipo de documento en su opción', $r['status']);
        $r = $pedir($camino(\Documents\Controllers\DocumentsController::routeName('forms-edit', ['id' => $documentoID, 'lang' => $lang], true)), $jwtRoot);
        $escapado($r['body'], 'zzdn', 'g2 documents/forms/edit.php: el nombre del documento', $r['status']);
        $escapado($r['body'], 'zzdt', 'g3 y el tipo en su opción', $r['status']);
        $explorador = $pedir($camino(\Documents\Controllers\DocumentsController::routeName('datatables-explorer', [], true)), $jwtRoot, $tabla);
        $celdas = $celdasDe($explorador['body'], 'rawData');
        foreach (['zzdn' => 'el nombre', 'zzdd' => 'la descripción', 'zzdt' => 'el tipo'] as $clave => $que) {
            $escapado($celdas, $clave, "g4 documents/util/item-explorer.php: {$que}", $explorador['status']);
        }
        $r = $pedir($camino(\Forms\DocumentTypes\Controllers\DocumentTypesController::routeName('forms-edit', ['id' => $tipoID, 'lang' => $lang], true)), $jwtRoot);
        $escapado($r['body'], 'zzdt', 'g5 Forms/DocumentTypes/forms/edit.php: el nombre del tipo', $r['status']);
        $r = $pedir($camino(\Forms\Categories\Controllers\CategoriesController::routeName('forms-edit', ['id' => $categoriaFormID, 'lang' => $lang], true)), $jwtRoot);
        $escapado($r['body'], 'zzcat', 'g6 Forms/Categories/forms/edit.php: el nombre de la categoría', $r['status']);

        //──── [j] El nombre del archivo subido a Documents ─────────────────────────────────────
        //La subida guarda el nombre del navegador tal cual (handlerUpload); aquí Apache no puede subir: se siembra la fila.
        echoTerminal('');
        echoTerminal('[j] El nombre del archivo subido sale escapado, en el texto y en el enlace');
        $carpetaSubida = "{$prefijo}-subida";
        $nombreArchivo = "zz-doc'" . '"' . $crudo('zzfile') . ' ñ.txt';
        $raizDocumentos = append_to_path_system((string) get_config('upload_dir'), \Documents\Controllers\DocumentsController::UPLOAD_DIR);
        $urlDocumentos = trim(str_replace(base_url(), '', append_to_url((string) get_config('upload_dir_url'), \Documents\Controllers\DocumentsController::UPLOAD_DIR)), '/');
        $dirSubida = append_to_path_system($raizDocumentos, $carpetaSubida);
        $carpetasSubidas[] = $carpetaSubida;
        //Nace privado, como lo deja moveUploadedToPrivate: el nombre público más el sufijo.
        //Solo la subcarpeta: crear la raíz con el usuario del terminal dejaría al servidor web sin poder subir.
        $creado = is_dir($raizDocumentos) && (is_dir($dirSubida) || mkdir($dirSubida, 0755)) && file_put_contents(\PiecesPHP\Core\Statics\ProtectedUploads::privatePath($dirSubida . '/' . $nombreArchivo), 'zz-contenido-del-documento') !== false;
        $check($creado, 'j0 el archivo de la prueba existe en disco con el nombre del navegador', $dirSubida);
        $rutaGuardada = "{$urlDocumentos}/{$carpetaSubida}/{$nombreArchivo}";
        $database->prepare('INSERT INTO `' . DocumentsMapper::TABLE . '` (documentType, documentName, description, document, documentImage, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$tipoID, "{$prefijo}-subido", 'zz', $rutaGuardada, '', $carpetaSubida, 1, $ahora, $rootID, $meta()]);
        $subidoID = (int) $database->lastInsertId();
        $documentos[] = $subidoID;
        $explorador = $pedir($camino(\Documents\Controllers\DocumentsController::routeName('datatables-explorer', [], true)), $jwtRoot, $tabla);
        $celdas = $celdasDe($explorador['body'], 'rawData');
        $edicion = $pedir($camino(\Documents\Controllers\DocumentsController::routeName('forms-edit', ['id' => $subidoID, 'lang' => $lang], true)), $jwtRoot);
        $check(str_contains($celdas, $esc($nombreArchivo)) && !str_contains($celdas, $crudo('zzfile')), 'j1 item-explorer.php: el nombre sale escapado en el texto y en ningún sitio crudo', "HTTP {$explorador['status']}");
        $check($edicion['status'] === 200 && !str_contains($edicion['body'], $crudo('zzfile')), 'j2 documents/forms/edit.php: el enlace no lleva el nombre crudo', "HTTP {$edicion['status']}");
        //CANARIO con un nombre que se sirve: el servido no decodifica la URL, y un espacio, ñ o <> no baja ni antes ni ahora.
        $creado = $creado && file_put_contents(\PiecesPHP\Core\Statics\ProtectedUploads::privatePath($dirSubida . '/zz-doc-plano.txt'), 'zz-contenido-del-documento') !== false;
        $database->prepare('INSERT INTO `' . DocumentsMapper::TABLE . '` (documentType, documentName, description, document, documentImage, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$tipoID, "{$prefijo}-plano", 'zz', "{$urlDocumentos}/{$carpetaSubida}/zz-doc-plano.txt", '', $carpetaSubida, 1, $ahora, $rootID, $meta()]);
        $documentos[] = (int) $database->lastInsertId();
        $celdas = $celdasDe($pedir($camino(\Documents\Controllers\DocumentsController::routeName('datatables-explorer', [], true)), $jwtRoot, $tabla)['body'], 'rawData');
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y el caso falla.
        preg_match('#<a class="card" target="_blank" href="([^"]*zz-doc-plano\.txt)"#', $celdas, $enlace);
        $href = html_entity_decode($enlace[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $descarga = $href !== '' ? $pedir($camino(str_contains($href, '://') ? $href : '/' . ltrim($href, '/')), $jwtRoot) : ['status' => 0, 'body' => ''];
        $check($creado && $descarga['status'] === 200 && $descarga['body'] === 'zz-contenido-del-documento', 'j3 CANARIO: el enlace del explorador descarga el archivo', "HTTP {$descarga['status']}, href " . mb_substr($href, -70));

        //──── [h] Mapa de contenidos ───────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[h] El mapa de contenidos: el filtro de organizaciones y las tarjetas');
        $r = $pedir($camino(\ContentNavigationHub\Controllers\ContentNavigationHubController::routeName('contents-map', [], true)), $jwtRoot);
        $escapado($r['body'], 'zzo', 'h1 contents/map.php: la organización en su opción', $r['status']);
        $puntos = $pedir($camino(\GeoJSONManager\Controllers\GeoJsonManagerController::routeName('contents-geojson-features', [], true)), $jwtRoot, ['featuresType' => \GeoJSONManager\Enums\FeaturesTypes::PROFILES->value]);
        $geo = json_decode($puntos['body'], true);
        $tarjetaPersona = '';
        $tarjetaOrg = '';
        foreach ((is_array($geo) && is_array($geo['features'] ?? null) ? $geo['features'] : []) as $feature) {
            $card = (string) ($feature['properties']['cardHTML'] ?? '');
            //Por el marcador exacto, crudo o escapado: «zzo» también está dentro de «zzot».
            $tiene = fn (string $clave): bool => str_contains($card, $crudo($clave)) || str_contains($card, $esc($crudo($clave)));
            if (str_contains($card, 'profile-user') && $tiene('zzn')) {
                $tarjetaPersona = $card;
            }
            if (str_contains($card, 'profile-org') && $tiene('zzo')) {
                $tarjetaOrg = $card;
            }
        }
        $escapado($tarjetaPersona, 'zzn', 'h2 map-elements/profile-person-card.php: el nombre', $puntos['status']);
        $escapado($tarjetaPersona, 'zzjob', 'h3 y el cargo', $puntos['status']);
        $escapado($tarjetaOrg, 'zzo', 'h4 map-elements/profile-org-card.php: el nombre', $puntos['status']);
        $escapado($tarjetaOrg, 'zzs', 'h5 y el sector', $puntos['status']);

        //──── [i] Banner, autor de la publicación y registro de actividad ──────────────────────
        echoTerminal('');
        echoTerminal('[i] El banner, el autor de una publicación y el saludo del registro de actividad');
        $database->prepare('INSERT INTO `' . BuiltInBannerMapper::TABLE . '` (title, content, link, desktopImage, mobileImage, orderPosition, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$m('zzbt'), $rico, $m('zzbl'), 'statics/images/zz-prueba.jpg', '', 0, "{$prefijo}-banner", $ahora, $rootID, BuiltInBannerMapper::ACTIVE, $meta()]);
        $bannerID = (int) $database->lastInsertId();
        $banners[] = $bannerID;
        $r = $pedir($camino(\PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController::routeName('forms-edit', ['id' => $bannerID, 'lang' => $lang], true)), $jwtRoot);
        $escapado($r['body'], 'zzbt', 'i1 Banner/forms/edit.php: el título', $r['status']);
        $escapado($r['body'], 'zzbl', 'i2 Banner/forms/edit.php: el enlace', $r['status']);
        $r = $pedir($camino(\Publications\Controllers\PublicationsController::routeName('forms-edit', ['id' => $publicacionID], true)), $jwtRoot);
        $escapado($r['body'], 'zzn', 'i3 publications/forms/edit.php: el autor en su opción', $r['status']);
        //El registro de actividad saluda al usuario en sesión: uno principal con el marcador en el nombre.
        $principalMarcado = $crearUsuario(UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL, $m('zzlog'), 'Zz', "{$prefijo}-log@example.com", "{$prefijo}-" . $m('zzlogu'));
        $r = $pedir($camino(\EventsLog\Controllers\LogsController::routeName('list', [], true)), $jwtDe($principalMarcado));
        //La barra superior pinta el nombre propio aparte (ronda siguiente): aquí se mira el saludo y el alt del avatar.
        $nombreLog = $esc($m('zzlog') . ' Zz');
        $check($r['status'] === 200 && (bool) preg_match('#,&nbsp;</span>\s*' . preg_quote($nombreLog, '#') . '#', $r['body']) && !(bool) preg_match('#,&nbsp;</span>\s*' . preg_quote($m('zzlog'), '#') . '#', $r['body']), 'i4 EventsLog/log/list.php: el saludo pinta el nombre escapado (sin avatar no hay alt que mirar)', "HTTP {$r['status']}");

        //──── [k] Intentos de acceso y registro de actividad (listados por JSON) ──────────────
        echoTerminal('');
        echoTerminal('[k] El usuario tecleado en el login, sin sesión, sale escapado en el informe de intentos; y el autor en el registro');
        $usuarioTecleado = $m('zzlogin');
        $intentoPost = curl_init();
        curl_setopt_array($intentoPost, [
            CURLOPT_URL => $base . '/' . ltrim($camino(\PiecesPHP\UserSystem\Controllers\UsersController::routeName('login-request', [], true)), '/'),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'], CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['username' => $usuarioTecleado, 'password' => 'zz-no-es-la-clave']),
        ]);
        //RETORNO-IGNORADO: la respuesta del login no importa; k0 cuenta la fila que dejó.
        curl_exec($intentoPost);
        $guardados = (int) $database->query('SELECT COUNT(*) FROM `login_attempts` WHERE usernameAttempt = ' . $database->quote($usuarioTecleado))->fetchColumn();
        $check($guardados > 0, 'k0 medido: un intento anónimo con el usuario marcado se guarda tal cual', "filas {$guardados}");
        $informe = $pedir($camino(\PiecesPHP\UserSystem\Controllers\LoginAttemptsController::routeName('reports-ajax', ['type' => 'attempts'], true)), $jwtRoot, $tabla + ['xhr' => 'yes']);
        $escapado($celdasDe($informe['body']), 'zzlogin', 'k1 LoginAttemptsModel: el usuario tecleado sale escapado en el informe de intentos', $informe['status']);
        $database->prepare('INSERT INTO `actions_log` (textMessage, textMessageVariables, createdBy, createdAt) VALUES (?, ?, ?, ?)')->execute(['zz-registro', '[]', $principalMarcado, $ahora]);
        $registro = $pedir($camino(\EventsLog\Controllers\LogsController::routeName('datatables', [], true)), $jwtRoot, $tabla);
        $escapado($celdasDe($registro['body']), 'zzlogu', 'k2 LogsController: el usuario autor de una entrada del registro sale escapado', $registro['status']);

        //──── [l] Roles de contenido, dato propio, palabras clave, buscador y listado de usuarios ───
        echoTerminal('');
        echoTerminal('[l] Noticias, publicaciones, el submenú Blog, lo propio de cada usuario, las palabras clave, el buscador y el listado');
        //Noticias: categoría y noticia; el contenido es del editor y en su div tiene que seguir saliendo tal cual.
        $database->prepare('INSERT INTO `news_categories` (name, iconImage, color, meta) VALUES (?, ?, ?, ?)')->execute([$m('zzncat'), 'statics/images/zz-prueba.jpg', '#123456', $meta()]);
        $categoriaNoticiaID = (int) $database->lastInsertId();
        $categoriasNoticias[] = $categoriaNoticiaID;
        $database->prepare('INSERT INTO `news_elements` (newsTitle, profilesTarget, content, category, folder, startDate, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$m('zzntit'), '[]', $rico, $categoriaNoticiaID, "{$prefijo}-noticia", $ahora, $ahora, $rootID, 1, $meta()]);
        $noticiaID = (int) $database->lastInsertId();
        $noticias[] = $noticiaID;
        $r = $pedir($camino(\News\Controllers\NewsCategoryController::routeName('forms-edit', ['id' => $categoriaNoticiaID], true)), $jwtRoot);
        $escapado($r['body'], 'zzncat', 'l1 News/categories/forms/edit.php: el nombre de la categoría', $r['status']);
        $r = $pedir($camino(\News\Controllers\NewsController::routeName('forms-add', [], true)), $jwtRoot);
        $escapado($r['body'], 'zzncat', 'l2 News/news/forms/add.php: la categoría en su opción', $r['status']);
        $edicionNoticia = $pedir($camino(\News\Controllers\NewsController::routeName('forms-edit', ['id' => $noticiaID], true)), $jwtRoot);
        $escapado($edicionNoticia['body'], 'zzntit', 'l3 News/news/forms/edit.php: el título', $edicionNoticia['status']);
        $escapado($edicionNoticia['body'], 'zzncat', 'l4 y la categoría en su opción', $edicionNoticia['status']);
        $check(str_contains($edicionNoticia['body'], '">' . $rico . '</div>'), 'l5 CANARIO P106: el div del editor de la noticia pinta el contenido enriquecido tal cual', "HTTP {$edicionNoticia['status']}");
        $tarjetaNoticia = (string) (new \PiecesPHP\Core\BaseController())->setInstanceViewDir(basepath('app/classes/News/Views/news/'))->render('public/util/item', ['element' => new \News\Mappers\NewsMapper($noticiaID), 'langGroup' => 'news'], false, false);
        $escapado($tarjetaNoticia, 'zzntit', 'l6 News/news/public/util/item.php (en proceso): el título', 200);
        $escapado($tarjetaNoticia, 'zzncat', 'l7 y la categoría en el alt', 200);
        $check(str_contains($tarjetaNoticia, 'rico &amp; enlace') && !str_contains($tarjetaNoticia, '&amp;amp;'), 'l8 y el extracto del editor sale con su «&» escapado UNA vez', 'extracto');
        //Publicaciones: categoría, su tarjeta y el extracto del componente público.
        $database->prepare('INSERT INTO `publications_categories` (name, meta) VALUES (?, ?)')->execute([$m('zzpcat'), $meta()]);
        $categoriaPublicacionID = (int) $database->lastInsertId();
        $categoriasPublicaciones[] = $categoriaPublicacionID;
        $r = $pedir($camino(\Publications\Controllers\PublicationsCategoryController::routeName('forms-edit', ['id' => $categoriaPublicacionID], true)), $jwtRoot);
        $escapado($r['body'], 'zzpcat', 'l9 Publications/categories/forms/edit.php: el nombre', $r['status']);
        $r = $pedir($camino(\Publications\Controllers\PublicationsCategoryController::routeName('datatables', [], true)), $jwtRoot, $tabla);
        $escapado($celdasDe($r['body'], 'rawData'), 'zzpcat', 'l10 Publications/categories/util/list-card.php: el nombre', $r['status']);
        $r = $pedir($camino(\Publications\Controllers\PublicationsController::routeName('forms-add', [], true)), $jwtRoot);
        $escapado($r['body'], 'zzpcat', 'l11 publications/forms/add.php: la categoría en su opción', $r['status']);
        //Antes de cambiar el título: con él cambia el slug y la página pública redirige.
        $publica = $pedir($rutaPublica, null);
        $escapado($publica['body'], 'zzpcat', 'l15 view/layout/menu.php: la categoría en el submenú Blog', $publica['status']);
        $database->prepare('UPDATE `' . PublicationMapper::TABLE . '` SET title = ? WHERE id = ?')->execute([$m('zzptit'), $publicacionID]);
        $r = $pedir($camino(\Publications\Controllers\PublicationsController::routeName('forms-edit', ['id' => $publicacionID], true)), $jwtRoot);
        $escapado($r['body'], 'zzptit', 'l12 publications/forms/edit.php: el título', $r['status']);
        $escapado($r['body'], 'zzpcat', 'l13 y la categoría en su opción', $r['status']);
        $_GET['slugs'] = [(new PublicationMapper($publicacionID))->getSlug($lang)];
        try {
            $componente = (string) \Publications\Controllers\PublicationsPublicController::view('public/util/components', [], false);
        } finally {
            unset($_GET['slugs']);
        }
        $check(str_contains($componente, 'rico &amp; enlace') && !str_contains($componente, '&amp;amp;') && !str_contains($componente, '<strong>rico'), 'l14 publications/public/util/components.php (en proceso): el extracto, escapado una vez y sin etiquetas', 'longitud ' . strlen($componente));
        //Lo propio: el administrador sembrado mira sus pantallas (y la barra superior de cada una).
        $jwtAdmin = $jwtDe($adminID);
        foreach (['my-profile/my-profile.php' => $camino(\MySpace\Controllers\MyProfileController::routeName('my-profile', [], true)), 'user-security.php' => $camino(\MySpace\Controllers\MySpaceController::routeName('user-security', [], true))] as $vista => $ruta) {
            $r = $pedir($ruta, $jwtAdmin);
            $escapado($r['body'], 'zzn', "l16 {$vista} y la barra superior: el nombre propio", $r['status']);
            $escapado($r['body'], 'zzm', "l17 {$vista} y la barra superior: el correo propio", $r['status']);
        }
        $r = $pedir($camino(\MySpace\Controllers\MyProfileController::routeName('my-profile', [], true)), $jwtAdmin);
        $escapado($r['body'], 'zzjob', 'l18 my-profile.php: el cargo', $r['status']);
        $escapado($r['body'], 'zzuinst', 'l19 my-profile.php: las instituciones', $r['status']);
        $r = $pedir($camino(\MySpace\Controllers\MySpaceController::routeName('user-security', [], true)), $jwtAdmin);
        $escapado($r['body'], 'zzu' . $sufijo, 'l20 user-security.php: el usuario', $r['status']);
        $jwtLog = $jwtDe($principalMarcado);
        //Mi espacio y el panel redirigen al administrador de organización: los mira el principal marcado.
        foreach (['my-space.php' => $camino(\MySpace\Controllers\MySpaceController::routeName('my-space', [], true)), 'dashboard.php' => $camino(\PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName('', [], true))] as $vista => $ruta) {
            $r = $pedir($ruta, $jwtLog);
            $escapado($r['body'], 'zzlog', "l16 {$vista} y la barra superior: el nombre propio", $r['status']);
            $escapado($r['body'], 'zzlogu', "l17 {$vista} y la barra superior: el usuario propio", $r['status']);
        }
        $r = $pedir($camino(\MySpace\Controllers\MySpaceController::routeName('example-resources', [], true)), $jwtLog);
        $escapado($r['body'], 'zzlogu', 'l21 example-resources.php: el usuario', $r['status']);
        $r = $pedir($camino(\ReportsManage\Controllers\ReportsManageController::routeName('generic-report-view', [], true)), $jwtLog);
        $escapado($r['body'], 'zzlog', 'l22 generic-report-view.php: el nombre en el subtítulo', $r['status']);
        //Las palabras clave del SEO, plantadas en su fila.
        $plantarOpcion('keywords', (string) json_encode([$m('zzkw')]));
        try {
            $seo = $pedir($camino(\PiecesPHP\Settings\Controllers\SettingsController::routeName('appearance-seo', [], true)), $jwtRoot);
        } finally {
            $reponerOpciones();
        }
        $escapado($seo['body'], 'zzkw', 'l23 SettingsController (seo.php:74): la palabra clave en su opción, valor y texto', $seo['status']);
        $check(str_contains($seo['body'], 'selected') && (bool) preg_match("#<option\s+selected\s+value='" . preg_quote($esc($m('zzkw')), '#') . "'#", $seo['body']), 'l24 y sigue marcada como elegida');
        //El buscador de autores y el listado de usuarios.
        $r = $pedir($camino(\PiecesPHP\UserSystem\Controllers\UsersController::routeName('search-dropdown', [], true)), $jwtRoot, ['search' => "zz'zzn", 'typeResult' => 'RESULT_FULLNAME_USERNAME']);
        $escapado($r['body'], 'zzn', 'l25 searchDropdown: el nombre va escapado (Fomantic lo pinta como HTML)', $r['status']);
        $escapado($r['body'], 'zzu' . $sufijo, 'l26 searchDropdown: y el usuario', $r['status']);
        $r = $pedir($camino(\PiecesPHP\UserSystem\Controllers\UsersController::routeName('datatables', [], true)), $jwtRoot, $tabla);
        foreach (['zzn' => 'los nombres', 'zzm' => 'el correo', 'zzu' . $sufijo => 'el usuario'] as $clave => $que) {
            $escapado($celdasDe($r['body']), $clave, "l27 users-datatables: {$que} en `data`", $r['status']);
        }

        //──── [m] El texto del registro de actividad lleva el usuario por plantilla ─────────────
        echoTerminal('');
        echoTerminal('[m] Un usuario general con HTML en su usuario cierra sus sesiones; el registro lo pinta escapado');
        $generalMarcado = $crearUsuario(UsersModel::TYPE_USER_GENERAL, $orgID, 'Zz', 'Registro', "{$prefijo}-rev@example.com", "{$prefijo}-" . $m('zzrev'));
        $cierre = curl_init();
        curl_setopt_array($cierre, [
            CURLOPT_URL => $base . '/' . ltrim($camino(\MySpace\Controllers\MySpaceController::routeName('revoke-my-sessions', [], true)), '/'),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: " . $jwtDe($generalMarcado), 'X-Requested-With: XMLHttpRequest'], CURLOPT_POST => true, CURLOPT_POSTFIELDS => '',
        ]);
        //RETORNO-IGNORADO: la respuesta no importa; m0 cuenta la entrada que dejó.
        curl_exec($cierre);
        $entradas = (int) $database->query("SELECT COUNT(*) FROM `actions_log` WHERE createdBy = {$generalMarcado}")->fetchColumn();
        $check($entradas > 0, 'm0 medido: cerrar las propias sesiones deja la entrada en el registro, con el usuario dentro', "entradas {$entradas}");
        $registro = $pedir($camino(\EventsLog\Controllers\LogsController::routeName('datatables', [], true)), $jwtRoot, $tabla);
        $escapado($celdasDe($registro['body']), 'zzrev', 'm1 LogsController:160: el texto de la entrada lleva el usuario escapado', $registro['status']);

        //──── [n] Los listados de contenido por JSON (bloque 2 de #1002) ───────────────────────
        echoTerminal('');
        echoTerminal('[n] Los listados de banner, noticias, categorías de noticias y publicaciones escapan lo variable de cada celda');
        $database->prepare('UPDATE `' . BuiltInBannerMapper::TABLE . '` SET desktopImage = ?, mobileImage = ? WHERE id = ?')->execute(['statics/' . $m('zzbdi') . '.jpg', 'statics/' . $m('zzbmi') . '.jpg', $bannerID]);
        $database->prepare('UPDATE `news_categories` SET iconImage = ? WHERE id = ?')->execute(['statics/' . $m('zznic') . '.jpg', $categoriaNoticiaID]);
        $listados = [
            'banner' => [\PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController::routeName('datatables', [], true), ['zzbt' => 'el título', 'zzbdi' => 'la imagen de escritorio en data-image-preview', 'zzbmi' => 'la imagen móvil en data-image-preview']],
            'noticias' => [\News\Controllers\NewsController::routeName('datatables', [], true), ['zzntit' => 'el título', 'zzncat' => 'la categoría']],
            'categorías de noticias' => [\News\Controllers\NewsCategoryController::routeName('datatables', [], true), ['zzncat' => 'el nombre', 'zznic' => 'el icono en src']],
            'publicaciones' => [\Publications\Controllers\PublicationsController::routeName('datatables', [], true), ['zzptit' => 'el título', 'zzu' . $sufijo => 'el autor']],
        ];
        foreach ($listados as $listado => [$ruta, $claves]) {
            $r = $pedir($camino($ruta), $jwtRoot, $tabla);
            $celdas = $celdasDe($r['body']);
            foreach ($claves as $clave => $que) {
                $escapado($celdas, $clave, "n1 listado de {$listado}: {$que}", $r['status']);
            }
        }

        //──── [o] El color de una categoría de noticias: se valida al guardar y se respalda al pintar ───
        echoTerminal('');
        echoTerminal('[o] El color de una categoría de noticias solo admite un color, y una fila vieja con otra cosa se pinta con el de defecto');
        $validador = \PiecesPHP\Core\Validation\Validator::class;
        $check($validador::isColor('#ABC') && $validador::isColor('#ABCDEF') && $validador::isColor('#ABCDEF80') && $validador::isColor('rgba(255, 0, 0, 0.5)') && $validador::isColor('rgb(1,2,3)'), 'o1 Validator::isColor() admite hex de 3, 6 y 8 cifras y rgb()/rgba()');
        $check(!$validador::isColor('') && !$validador::isColor('red') && !$validador::isColor('#123;background:url(x)') && !$validador::isColor("rgba(1,2,3,0.5)'") && !$validador::isColor(['#000']), 'o2 y rechaza el vacío, un nombre, CSS añadido, una comilla y un array');
        $colorDe = fn (): string => (string) $database->query("SELECT color FROM `news_categories` WHERE id = {$categoriaNoticiaID}")->fetchColumn();
        $rutaGuardarCategoria = $camino(\News\Controllers\NewsCategoryController::routeName('actions-edit', [], true));
        $guardarCategoria = function (string $color) use ($base, $rutaGuardarCategoria, $cabeceraToken, $jwtRoot, $categoriaNoticiaID, $lang, $m): string {
            $peticion = curl_init();
            curl_setopt_array($peticion, [
                CURLOPT_URL => $base . '/' . ltrim($rutaGuardarCategoria, '/'), CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwtRoot}", 'X-Requested-With: XMLHttpRequest'], CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query(['id' => $categoriaNoticiaID, 'lang' => $lang, 'name' => $m('zzncat'), 'color' => $color]),
            ]);
            $respuesta = curl_exec($peticion);
            return is_string($respuesta) ? $respuesta : '';
        };
        $antes = $colorDe();
        $respuesta = $guardarCategoria('#123456;background:url(https://zz.test/x)');
        $check($colorDe() === $antes, 'o3 un color con CSS añadido se rechaza al guardar y la fila no cambia', mb_substr($respuesta, 0, 120));
        $respuesta = $guardarCategoria('rgba(10, 20, 30, 0.5)');
        $check($colorDe() === 'rgba(10, 20, 30, 0.5)', 'o4 CANARIO: un rgba() del selector sí se guarda', mb_substr($respuesta, 0, 120));
        //Una fila de antes de la validación.
        $database->prepare('UPDATE `news_categories` SET color = ? WHERE id = ?')->execute(["red;}" . $crudo('zzcss') . "{", $categoriaNoticiaID]);
        $r = $pedir($camino(\News\Controllers\NewsCategoryController::routeName('datatables', [], true)), $jwtRoot, $tabla);
        $celdas = $celdasDe($r['body']);
        $check(!str_contains($celdas, 'zzcss') && str_contains($celdas, 'background-color: ' . \News\Mappers\NewsCategoryMapper::DEFAULT_COLOR . ';'), 'o5 el listado pinta el color de defecto en vez del plantado', "HTTP {$r['status']}");
        $tarjetaNoticia = (string) (new \PiecesPHP\Core\BaseController())->setInstanceViewDir(basepath('app/classes/News/Views/news/'))->render('public/util/item', ['element' => new \News\Mappers\NewsMapper($noticiaID), 'langGroup' => 'news'], false, false);
        $check(!str_contains($tarjetaNoticia, 'zzcss') && str_contains($tarjetaNoticia, '--category-color: ' . \News\Mappers\NewsCategoryMapper::DEFAULT_COLOR . ';'), 'o6 news/public/util/item.php (en proceso): también el de defecto', 'longitud ' . strlen($tarjetaNoticia));

        //──── [q] Los listados de entidades por JSON (bloque 3 de #1002) ────────────────────────
        echoTerminal('');
        echoTerminal('[q] Los listados de organizaciones, documentos, tipos, categorías y perfiles escapan lo variable de cada celda');
        $entidades = [
            'organizaciones' => [\Organizations\Controllers\OrganizationsController::routeName('datatables', [], true), ['zzo' => 'el nombre', 'zznit' => 'el NIT']],
            'documentos' => [\Documents\Controllers\DocumentsController::routeName('datatables', [], true), ['zzdn' => 'el nombre', 'zzdt' => 'el tipo']],
            'tipos de documento' => [\Forms\DocumentTypes\Controllers\DocumentTypesController::routeName('datatables', [], true), ['zzdt' => 'el nombre']],
            'categorías de Formularios' => [\Forms\Categories\Controllers\CategoriesController::routeName('datatables', [], true), ['zzcat' => 'el nombre']],
            'perfiles' => [\MySpace\Controllers\AllProfilesController::routeName('datatables', [], true), ['zzn' => 'el nombre de la persona', 'zzo' => 'el nombre de la organización']],
        ];
        foreach ($entidades as $listado => [$ruta, $claves]) {
            $r = $pedir($camino($ruta), $jwtRoot, $tabla);
            $celdas = $celdasDe($r['body']);
            foreach ($claves as $clave => $que) {
                $escapado($celdas, $clave, "q1 listado de {$listado}: {$que}", $r['status']);
            }
        }

        //──── [s] La vista previa del banner, el fondo del ayudante de subida (bloque EU de #1005) ───
        echoTerminal('');
        echoTerminal('[s] El nombre subido al banner se sanea, su vista previa no interpola HTML y el fondo del ayudante falla cerrado');
        $sanear = [\PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController::class, 'safeUploadName'];
        $peligroso = $sanear('a" onerror="zzBn()" x=\'' . $crudo('zzbanimg'));
        $check(preg_match('/^[A-Za-z0-9_-]+\z/', $peligroso) === 1 && !str_contains($peligroso, '"') && !str_contains($peligroso, '<'), 's1 safeUploadName(): un nombre con comillas, < y > queda en letras, números, «_» y «-»', $peligroso);
        $check($sanear('banner_2026-final') === 'banner_2026-final' && $sanear('banner_2026.v2-final') === 'banner_2026-v2-final' && $sanear('foto ñ (1)') === 'foto-1', 's2 y un nombre ya seguro no cambia; el punto, los espacios, la ñ y los paréntesis pasan a guion', $sanear('banner_2026.v2-final'));
        $check($sanear('shell.php') === 'shell-php', 's2b sin punto interior no se forma «shell.php.png»', $sanear('shell.php'));
        $largo = $sanear(str_repeat('a', 150) . '.png');
        $check(strlen($largo) <= 100 && str_starts_with($largo, 'aaaa'), 's2c un nombre de más de 100 bytes se recorta a 100', (string) strlen($largo));
        $check(str_starts_with($sanear('...'), 'file_') && str_starts_with($sanear('<>'), 'file_') && str_starts_with($sanear('....'), 'file_'), 's3 y si no queda nada (solo puntos, solo signos), uno generado');
        $fuenteBanner = (string) file_get_contents(basepath('app/classes/PiecesPHP/BuiltIn/Banner/Controllers/BuiltInBannerController.php'));
        $posCorte = mb_strpos($fuenteBanner, '$name = mb_substr($name, 0, $lastPointIndex);');
        $posSaneo = mb_strpos($fuenteBanner, '$name = self::safeUploadName($name);');
        $posMover = mb_strpos($fuenteBanner, '$locations = $handler->moveTo($uploadDirPath, $name');
        $check($posCorte !== false && $posSaneo !== false && $posMover !== false && $posCorte < $posSaneo && $posSaneo < $posMover, 's4 handlerUpload sanea el nombre del navegador después de quitarle la extensión y antes de mover el archivo');
        $fuenteLista = (string) file_get_contents(basepath('app/classes/PiecesPHP/BuiltIn/Banner/Statics/js/list.js'));
        $check(!str_contains($fuenteLista, '<img src="${src}"') && str_contains($fuenteLista, ".attr('src', src)"), 's5 list.js: la ruta de la vista previa va por attr(), no dentro de una cadena de HTML');
        $ayudante = fn (array $datos): string => (string) simpleUploadPlaceholderWorkSpace($datos, false);
        $malo = $ayudante(['imagePreview' => "statics/x.png') ;}*{background:url(https://zz.test/" . $crudo('zzbg')]);
        $check(!str_contains($malo, 'background-image') && !str_contains($malo, 'zzbg'), 's6 workspace.php: una ruta con comillas o paréntesis no pinta fondo (falla cerrado)', mb_substr($malo, 0, 120));
        $bueno = $ayudante(['imagePreview' => 'statics/uploads/news-categories/zz/icono_1.png?v=2']);
        //La salida añade después su sello de caché (&cacheStamp=…) a las rutas de estáticos: se mira el comienzo.
        $check(str_contains($bueno, 'style="background-image: url(&#039;statics/uploads/news-categories/zz/icono_1.png?v=2'), 's7 CANARIO: una ruta normal sigue pintando su fondo, con el atributo escapado', mb_substr((string) strstr($bueno, 'overlay-element'), 0, 200));
        $sinFondo = $ayudante([]);
        $check(str_contains($sinFondo, 'class="overlay-element" style=""'), 's8 y sin imagePreview, igual que antes: style vacío');
        $database->prepare('UPDATE `news_categories` SET iconImage = ? WHERE id = ?')->execute(['statics/uploads/news-categories/zz/icono_1.png', $categoriaNoticiaID]);
        $r = $pedir($camino(\News\Controllers\NewsCategoryController::routeName('forms-edit', ['id' => $categoriaNoticiaID], true)), $jwtRoot);
        $check($r['status'] === 200 && str_contains($r['body'], 'background-image: url(&#039;statics/uploads/news-categories/zz/icono_1.png'), 's9 su único llamador con imagen (edición de categoría de noticias) sigue pintando el icono de fondo', "HTTP {$r['status']}");
        $database->prepare('UPDATE `news_categories` SET iconImage = ? WHERE id = ?')->execute(["statics/x.png');}" . $crudo('zzbg2'), $categoriaNoticiaID]);
        $r = $pedir($camino(\News\Controllers\NewsCategoryController::routeName('forms-edit', ['id' => $categoriaNoticiaID], true)), $jwtRoot);
        //Escapar el atributo no basta: el navegador lo decodifica dentro del style. Tiene que no pintarse.
        $check($r['status'] === 200 && !str_contains($r['body'], 'statics/x.png') && !str_contains($r['body'], 'zzbg2'), 's10 y un icono plantado con CSS dentro no se pinta (falla cerrado)', "HTTP {$r['status']}");

        //──── [t] Las rutas de imagen del banner y del recortador (bloque EU de #1008) ───────────
        echoTerminal('');
        echoTerminal('[t] El formulario del banner y el recortador escapan sus rutas de imagen; las pantallas con recortador se ven igual');
        $database->prepare('UPDATE `' . BuiltInBannerMapper::TABLE . '` SET desktopImage = ?, mobileImage = ? WHERE id = ?')->execute(['statics/' . $m('zzbed') . '.jpg', 'statics/' . $m('zzbem') . '.jpg', $bannerID]);
        $r = $pedir($camino(\PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController::routeName('forms-edit', ['id' => $bannerID, 'lang' => $lang], true)), $jwtRoot);
        $escapado($r['body'], 'zzbed', 't1 Banner/forms/edit.php: data-image de la imagen de escritorio', $r['status']);
        $escapado($r['body'], 'zzbem', 't2 Banner/forms/edit.php: data-image de la imagen móvil', $r['status']);
        $recortador = (string) simpleCropperAdapterWorkSpace(['image' => 'statics/' . $m('zzcrop') . '.jpg', 'referenceW' => '400', 'referenceH' => '400'], false);
        //El sello de caché se inserta después de renderizar, y parte la primera entidad (&#039;): se mira el resto del marcador.
        $check(str_contains($recortador, '&quot;&lt;zzcrop&gt;') && !str_contains($recortador, $crudo('zzcrop')), 't3 simple-cropper/workspace.php (en proceso): el src de la imagen sale escapado', mb_substr((string) strstr($recortador, 'class="preview"'), 0, 200));
        $check(str_contains($recortador, 'default-reference-image="') && str_contains($recortador, 'img-gen/400/400'), 't4 y el default-reference-image sigue apuntando a su imagen de referencia');
        //CANARIO: pantallas que pintan el recortador, como antes.
        foreach (['banner' => \PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController::routeName('forms-add', [], true), 'publicaciones' => \Publications\Controllers\PublicationsController::routeName('forms-edit', ['id' => $publicacionID], true), 'organizaciones' => \Organizations\Controllers\OrganizationsController::routeName('forms-edit', ['id' => $orgID, 'lang' => $lang], true), 'barra superior' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName('', [], true)] as $pantalla => $ruta) {
            $r = $pedir($camino($ruta), $jwtRoot);
            $check($r['status'] === 200 && str_contains($r['body'], '<img class="preview" default-reference-image="'), "t5 CANARIO: {$pantalla} sigue pintando su recortador", "HTTP {$r['status']}");
        }

        //──── [u] El plugin de anexos ya no vuelve HTML lo que lee de su atributo (bloque EU de #1008) ───
        echoTerminal('');
        echoTerminal('[u] AttachmentPlaceholder.js monta la imagen y el enlace con attr(); sus cuatro módulos siguen pintando su marcador');
        $fuentePlugin = (string) @file_get_contents(basepath('statics/core/own-plugins/AttachmentPlaceholder.js'));
        $check($fuentePlugin !== '' && !str_contains($fuentePlugin, '<img src="${') && !str_contains($fuentePlugin, 'href="${') && substr_count($fuentePlugin, ".attr('src', ") >= 2 && str_contains($fuentePlugin, ".attr('href', fileNoImageSetted)") && str_contains($fuentePlugin, 'filenameContainer.text(fileName)'), 'u1 la imagen y el enlace del archivo guardado van por attr(), y el nombre del archivo local por text()');
        //CANARIO, uno por módulo: la pantalla carga el plugin y pinta su marcador de anexo.
        $pantallasAnexo = [
            'home-image' => \PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController::routeName('forms-home-image', [], true),
            'banner' => \PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController::routeName('forms-edit', ['id' => $bannerID, 'lang' => $lang], true),
            'organizaciones' => \Organizations\Controllers\OrganizationsController::routeName('forms-edit', ['id' => $orgID, 'lang' => $lang], true),
            'publicaciones' => \Publications\Controllers\PublicationsController::routeName('forms-edit', ['id' => $publicacionID], true),
        ];
        //Noticias NO usa el plugin: su JS solo nombra la clase .attach-placeholder al validar.
        foreach ($pantallasAnexo as $pantalla => $ruta) {
            $r = $pedir($camino($ruta), $jwtRoot);
            $check($r['status'] === 200 && str_contains($r['body'], 'AttachmentPlaceholder.js') && str_contains($r['body'], 'attach-placeholder'), "u2 CANARIO: {$pantalla} carga el plugin y pinta su marcador de anexo", "HTTP {$r['status']}");
        }

        //──── [v] El saneo del nombre en el núcleo y las rutas de los anexos (bloque CH de #1012) ───
        echoTerminal('');
        echoTerminal('[v] safe_upload_name() sanea las cinco subidas y los anexos escapan sus 14 atributos y su nombre');
        $nucleo = safe_upload_name('a" onerror="zzSu()" x=\'' . $crudo('zzsun') . '.php');
        $check(preg_match('/^[A-Za-z0-9_-]+\z/', $nucleo) === 1 && !str_contains($nucleo, '.'), 'v1 safe_upload_name(): comillas, <, > y el punto quedan en letras, números, «_» y «-»', $nucleo);
        $check(safe_upload_name('...') !== '' && safe_upload_name('<>') !== '', 'v1b y un nombre que se queda sin nada devuelve uno no vacío');
        $peligrosoBanner = 'a" onerror="zzBn()" x=\'' . $crudo('zzbandel') . '.v2';
        $check(\PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController::safeUploadName($peligrosoBanner) === safe_upload_name($peligrosoBanner), 'v2 el banner delega: safeUploadName() y safe_upload_name() devuelven lo mismo');
        //Los tres handlerUpload que faltaban: saneo después de quitar la extensión y antes de mover, una sola vez.
        $subidas = [
            'app/classes/Organizations/Controllers/OrganizationsController.php' => 'ProtectedUploads::moveUploadedToPrivate(',
            'app/classes/Publications/Controllers/PublicationsController.php' => 'ProtectedUploads::moveUploadedToPrivate(',
            'app/classes/PiecesPHP/BuiltIn/Helpers/Controllers/GenericContentController.php' => '$handler->moveTo(',
            'app/classes/Documents/Controllers/DocumentsController.php' => 'ProtectedUploads::moveUploadedToPrivate(',
        ];
        foreach ($subidas as $archivo => $mover) {
            $fuente = (string) file_get_contents(basepath($archivo));
            $inicio = mb_strpos($fuente, 'function handlerUpload(');
            $posCorte = $inicio === false ? false : mb_strpos($fuente, '$name = mb_substr($name, 0, $lastPointIndex);', $inicio);
            $posSaneo = $inicio === false ? false : mb_strpos($fuente, '$name = safe_upload_name($name);', $inicio);
            $posMover = $inicio === false ? false : mb_strpos($fuente, $mover, $inicio);
            $check(substr_count($fuente, 'safe_upload_name(') === 1 && $posCorte !== false && $posSaneo !== false && $posMover !== false && $posCorte < $posSaneo && $posSaneo < $posMover, "v3 {$archivo}: handlerUpload sanea el nombre una vez, después de quitar la extensión y antes de mover");
        }
        //Censo, no lista (unidad: ocurrencias): todo sitio de src/app que lea el nombre que manda el navegador y lo lleve al disco lo sanea antes
        //de mover. Los que no lo llevan al disco se declaran aquí con su motivo; uno nuevo sin sanear ni declarar, falla.
        $exentos = [
            'app/classes/PiecesPHP/Settings/Controllers/SettingsController.php' => [2, 'solo toma la extensión; el nombre sale de su array $allowedImages'],
            'app/classes/DataImportExportUtility/Controllers/DataTransferController.php' => [2, 'solo valida la extensión; el archivo no se guarda con ese nombre (las dos, en la misma línea)'],
        ];
        $lecturas = [];
        $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(basepath('app'), \FilesystemIterator::SKIP_DOTS));
        foreach ($iterador as $archivo) {
            $ruta = str_replace('\\', '/', (string) $archivo->getPathname());
            if (!str_ends_with($ruta, '.php') || str_contains($ruta, '/local-tests/')) {
                continue;
            }
            $fuente = (string) file_get_contents($ruta);
            $cuantas = preg_match_all('/\$_FILES\[[^\]]+\]\[\'name\'\]/', $fuente, $hallados, \PREG_OFFSET_CAPTURE);
            if ($cuantas > 0) {
                $lecturas[(string) mb_substr($ruta, mb_strlen(rtrim(str_replace('\\', '/', basepath('')), '/')) + 1)] = [$fuente, $hallados[0]];
            }
        }
        $sinSanear = [];
        $saneados = 0;
        foreach ($lecturas as $relativa => [$fuente, $hallados]) {
            if (isset($exentos[$relativa])) {
                if (count($hallados) !== $exentos[$relativa][0]) {
                    $sinSanear[] = "{$relativa} (" . count($hallados) . " lecturas, declaradas {$exentos[$relativa][0]})";
                }
                continue;
            }
            foreach ($hallados as [, $pos]) {
                $resto = substr($fuente, $pos);
                $posMover = min(array_map(fn ($p) => $p === false ? \PHP_INT_MAX : $p, [strpos($resto, 'moveUploadedToPrivate('), strpos($resto, '->moveTo(')]));
                $posSaneo = min(array_map(fn ($p) => $p === false ? \PHP_INT_MAX : $p, [strpos($resto, '$name = safe_upload_name($name);'), strpos($resto, '$name = self::safeUploadName($name);')]));
                if ($posSaneo < $posMover) {
                    $saneados++;
                } else {
                    $sinSanear[] = $relativa . ':' . (substr_count(substr($fuente, 0, $pos), "\n") + 1);
                }
            }
        }
        $declaradosVistos = count(array_intersect_key($exentos, $lecturas));
        $check($sinSanear === [] && $saneados === 5 && $declaradosVistos === count($exentos), 'v6 censo de $_FILES[…][\'name\'] en src/app: las 5 lecturas que llegan al disco se sanean antes de mover, y las 4 exentas (en 2 archivos) están declaradas con su motivo', "saneadas {$saneados}/5; exentos vistos {$declaradosVistos}/" . count($exentos) . '; ' . implode(', ', $sinSanear));
        //Esperados: 15 sitios = 14 atributos + 1 nodo de texto. Cada uno por su forma escapada literal.
        $sitios = [
            'app/classes/PiecesPHP/BuiltIn/Helpers/Views/generic/forms/home-image.php' => [
                'data-image="<?= escape_html($value); ?>"' => 1,
            ],
            'app/classes/Organizations/Views/organizations/forms/edit.php' => [
                "data-image=\"<?= escape_html(\$element->getLangData(\$lang, 'logo', false, '')); ?>\"" => 1,
                "data-file=\"<?= escape_html(\$element->getLangData(\$lang, 'rut', false, '')); ?>\"" => 1,
            ],
            'app/classes/MySpace/Views/my-organization-profile/my-organization-profile.php' => [
                "data-image=\"<?= escape_html(\$organizationMapper->currentLangData('logo')); ?>\"" => 1,
            ],
            'app/classes/Publications/Views/publications/forms/edit.php' => [
                "data-image=\"<?= escape_html(\$element->getLangData(\$langCode, \$fieldName, false, '')); ?>\"" => 3,
                'value="<?= escape_html($attachmentElement->getDisplayName()); ?>"' => 1,
                'data-file-name="<?= escape_html($attachmentElement->getDisplayName()); ?>"' => 1,
                "\"{\$existingFileAttr}='\" . escape_html(\$fileLocation) . \"'\"" => 1,
                '<div class="title"><?= escape_html($attachmentElement->getDisplayName()); ?></div>' => 1,
            ],
            'app/classes/Publications/Views/publications/forms/add.php' => [
                "\"{\$existingFileAttr}='\" . escape_html(\$fileLocation) . \"'\"" => 1,
            ],
            'app/view/panel/built-in/utilities/cropper/workspace.php' => [
                "<canvas data-image='<?= escape_html(\$image); ?>'>" => 1,
                "\" value='\" . escape_html(\$imageName) . \"'\"" => 2,
            ],
        ];
        $encontrados = 0;
        $faltan = [];
        foreach ($sitios as $archivo => $formas) {
            $fuente = (string) file_get_contents(basepath($archivo));
            foreach ($formas as $forma => $esperados) {
                $hay = substr_count($fuente, $forma);
                $encontrados += min($hay, $esperados);
                if ($hay !== $esperados) {
                    $faltan[] = basename($archivo) . " ({$hay}/{$esperados})";
                }
            }
        }
        $check($encontrados === 15 && $faltan === [], 'v4 los 15 sitios van dentro de escape_html(): 14 atributos (home-image 1, organización 2, perfil 1, edición de publicación 6, alta 1, recortador 3) y 1 nodo de texto (el título del anexo)', "{$encontrados}/15 " . implode(', ', $faltan));
        $fuenteEdicion = (string) file_get_contents(basepath('app/classes/Publications/Views/publications/forms/edit.php'));
        $check(substr_count($fuenteEdicion, 'getDisplayName()') === 3 && substr_count($fuenteEdicion, 'getDisplayName()') === substr_count($fuenteEdicion, 'escape_html($attachmentElement->getDisplayName())'), 'v5 Publications/forms/edit.php no pinta getDisplayName() sin escape_html( en ningún sitio, ni atributo ni texto', (string) substr_count($fuenteEdicion, 'getDisplayName()'));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $reponerOpciones();
            //En disco, solo la carpeta que creó [j], con el prefijo de esta corrida.
            $raizDocumentos = append_to_path_system((string) get_config('upload_dir'), \Documents\Controllers\DocumentsController::UPLOAD_DIR);
            foreach ($carpetasSubidas as $carpeta) {
                $dir = append_to_path_system($raizDocumentos, $carpeta);
                if (str_starts_with($carpeta, $prefijo) && is_dir($dir)) {
                    foreach ((array) glob($dir . '/{,.}*', GLOB_BRACE) as $archivo) {
                        if (is_string($archivo) && is_file($archivo)) {
                            //RETORNO-IGNORADO: lo comprueba z3, que mira que la carpeta ya no exista.
                            unlink($archivo);
                        }
                    }
                    //RETORNO-IGNORADO: lo comprueba z3, que mira que la carpeta ya no exista.
                    rmdir($dir);
                }
            }
            foreach ($documentos as $id) {
                $database->exec('DELETE FROM `' . DocumentsMapper::TABLE . "` WHERE id = {$id}");
            }
            foreach ($tiposDocumento as $id) {
                $database->exec("DELETE FROM `forms_document_types` WHERE id = {$id}");
            }
            foreach ($categoriasFormularios as $id) {
                $database->exec("DELETE FROM `forms_categories` WHERE id = {$id}");
            }
            foreach ($noticias as $id) {
                $database->exec("DELETE FROM `news_elements` WHERE id = {$id}");
            }
            foreach ($categoriasNoticias as $id) {
                $database->exec("DELETE FROM `news_categories` WHERE id = {$id}");
            }
            foreach ($publicaciones as $id) {
                $database->exec("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = '" . PublicationMapper::TABLE . "' AND referenceValue = '{$id}'");
                $database->exec('DELETE FROM `' . PublicationMapper::TABLE . "` WHERE id = {$id}");
            }
            foreach ($categoriasPublicaciones as $id) {
                $database->exec("DELETE FROM `publications_categories` WHERE id = {$id}");
            }
            foreach ($banners as $id) {
                $database->exec('DELETE FROM `' . BuiltInBannerMapper::TABLE . "` WHERE id = {$id}");
            }
            $database->exec("DELETE FROM `{$tablaAprobaciones}` WHERE referenceAlias LIKE " . $database->quote("{$prefijo}%"));
            $database->prepare('DELETE FROM `login_attempts` WHERE usernameAttempt = ?')->execute([$m('zzlogin')]);
            foreach ($usuarios as $id) {
                $database->exec("DELETE FROM `actions_log` WHERE createdBy = {$id}");
                $database->exec("DELETE FROM `login_attempts` WHERE userID = {$id}");
                $database->exec("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = '{$tablaUsuarios}' AND referenceValue = '{$id}'");
                $database->exec("DELETE FROM `{$tablaPerfiles}` WHERE belongsTo = {$id}");
            }
            //Las claves van en círculo: los usuarios de las organizaciones sembradas, luego ellas (las creó el principal
            //sembrado) y al final el resto.
            $listaOrganizaciones = $organizaciones === [] ? '0' : implode(',', array_map('intval', $organizaciones));
            foreach ($usuarios as $id) {
                $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE id = {$id} AND organization IN ({$listaOrganizaciones})");
            }
            foreach ($organizaciones as $id) {
                $database->exec("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = '{$tablaOrganizaciones}' AND referenceValue = '{$id}'");
                $database->exec("DELETE FROM `{$tablaOrganizaciones}` WHERE id = {$id}");
            }
            foreach ($usuarios as $id) {
                $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE id = {$id}");
            }
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $cuenta = function (string $sql) use ($database): int {
            return (int) $database->query($sql)->fetchColumn();
        };
        $ids = fn (array $lista): string => $lista === [] ? '0' : implode(',', array_map('intval', $lista));
        $quedan = $cuenta("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE id IN ({$ids($usuarios)})")
            + $cuenta("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE id IN ({$ids($organizaciones)})")
            + $cuenta("SELECT COUNT(*) FROM `{$tablaPerfiles}` WHERE belongsTo IN ({$ids($usuarios)})")
            + $cuenta("SELECT COUNT(*) FROM `{$tablaAprobaciones}` WHERE referenceAlias LIKE " . $database->quote("{$prefijo}%"))
            + $cuenta('SELECT COUNT(*) FROM `' . PublicationMapper::TABLE . "` WHERE id IN ({$ids($publicaciones)})")
            + $cuenta('SELECT COUNT(*) FROM `' . BuiltInBannerMapper::TABLE . "` WHERE id IN ({$ids($banners)})")
            + $cuenta('SELECT COUNT(*) FROM `' . DocumentsMapper::TABLE . "` WHERE id IN ({$ids($documentos)})")
            + $cuenta("SELECT COUNT(*) FROM `forms_document_types` WHERE id IN ({$ids($tiposDocumento)})")
            + $cuenta("SELECT COUNT(*) FROM `forms_categories` WHERE id IN ({$ids($categoriasFormularios)})")
            + $cuenta("SELECT COUNT(*) FROM `actions_log` WHERE createdBy IN ({$ids($usuarios)})")
            + $cuenta("SELECT COUNT(*) FROM `news_elements` WHERE id IN ({$ids($noticias)})")
            + $cuenta("SELECT COUNT(*) FROM `news_categories` WHERE id IN ({$ids($categoriasNoticias)})")
            + $cuenta("SELECT COUNT(*) FROM `publications_categories` WHERE id IN ({$ids($categoriasPublicaciones)})")
            + $cuenta('SELECT COUNT(*) FROM `login_attempts` WHERE usernameAttempt = ' . $database->quote($m('zzlogin')));
        $quedanCarpetas = count(array_filter($carpetasSubidas, fn (string $carpeta): bool => is_dir(append_to_path_system(append_to_path_system((string) get_config('upload_dir'), \Documents\Controllers\DocumentsController::UPLOAD_DIR), $carpeta))));
        $check($quedanCarpetas === 0, 'z3 no queda en disco la carpeta de la prueba', "quedan {$quedanCarpetas}");
        $check($quedan === 0, 'z1 no queda ninguna fila sembrada (usuarios, perfiles, organizaciones, aprobaciones, publicación, banner, documentos, tipos, categorías)', "quedan {$quedan}");
        $check($database->query("SELECT name, value FROM `{$tablaOpciones}`")->fetchAll(\PDO::FETCH_KEY_PAIR) == $fotoOpciones, 'z2 la tabla de opciones queda como estaba');
    }

    return $balance();

})->setDescription('Lo que escribe cualquier usuario con sesión (perfiles, organizaciones, usuarios, documentos, categorías, banner, autor) y el propietario y el SEO se pintan escapados; los enlaces sin http(s) no llegan al href; y el contenido enriquecido sale tal cual. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
