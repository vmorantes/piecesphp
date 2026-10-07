<?php

//Lo que se pinta sin escapar (pendientes.md 396), por HTTP: las tres salidas públicas, las claves de IA que no viajan al
//HTML, el contenido de una publicación aislado en su aprobación, los dos textarea y la longitud. Siembra zz- y lo retira.

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\BuiltIn\Banner\Mappers\BuiltInBannerMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Email\MailDelivery;
use Newsletter\Mappers\NewsletterSuscriberMapper;
use News\Mappers\NewsMapper;
use Documents\Mappers\DocumentsMapper;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/view-escapes', function ($args) {

    echoTerminal("\e[33m[TEST:ViewEscapes] Lo que escribe un usuario se pinta escapado, y las claves de IA no viajan al HTML\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la pantalla de respaldos.
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
    //Un manejador con sus cookies en memoria: el login recibe la URI pedida por un mensaje de sesión.
    $conCookies = curl_init();
    $pedir = function (string $path, ?string $jwt, ?array $post = null, bool $cookies = false, bool $xhr = true) use ($base, $cabeceraToken, $conCookies): array {
        $handle = $cookies ? $conCookies : curl_init();
        $cabeceras = $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [];
        if ($xhr) {
            $cabeceras[] = 'X-Requested-With: XMLHttpRequest';
        }
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $cabeceras,
            CURLOPT_POST => $post !== null,
        ];
        if ($post !== null) {
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        if ($cookies) {
            $opciones[CURLOPT_COOKIEFILE] = '';
        }
        curl_setopt_array($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    $escapar = fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $prefijo = 'zz-prueba-vistas-' . bin2hex(random_bytes(3));
    $marca = '\'zzq"zzd<zzl>';
    $tablaUsuarios = UsersModel::TABLE;
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $tablaAprobaciones = SystemApprovalsMapper::TABLE;
    $usuarios = [];
    $organizaciones = [];
    $publicaciones = [];
    $banners = [];
    $suscriptores = [];
    //Las claves de IA de verdad se guardan en memoria para reponerlas, y NUNCA se imprimen.
    $clavesOriginales = [];
    //Las opciones de secretos, CRUDAS (el correo va cifrado): se reponen tal cual. null = no existía la fila.
    $opcionesCrudas = [];
    $noticias = [];
    $categoriasNoticias = [];
    $documentos = [];
    $tiposDocumento = [];

    try {
        $ahora = date('Y-m-d H:i:s');
        $root = new UsersModel();
        $root->username = "{$prefijo}-root";
        $root->email = "{$prefijo}-root@example.com";
        $root->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $root->firstname = 'Zz';
        $root->secondname = '';
        $root->firstLastname = 'Vistas';
        $root->secondLastname = '';
        $root->type = UsersModel::TYPE_USER_ROOT;
        $root->status = UsersModel::STATUS_USER_ACTIVE;
        $root->failedAttempts = 0;
        $root->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $root->createdAt = new \DateTime();
        $root->modifiedAt = $root->createdAt;
        $root->save();
        $rootID = (int) $root->id;
        $usuarios[] = $rootID;
        $jwtRoot = SessionToken::generateToken(['id' => $rootID], null, null, false);

        //──── [a] Las tres públicas ─────────────────────────────────────────────────────────
        echoTerminal('[a] Las salidas públicas pintan escapada la URL pedida y el correo del suscriptor');
        $marcaURL = "?zz={$marca}";
        $r = $pedir("/admin/about/{$marcaURL}", null, null, true, false);
        $check($r['status'] === 302, 'a0 banco: una ruta protegida sin sesión redirige al login y deja la URI pedida', "HTTP {$r['status']}");
        $login = $pedir($camino(\PiecesPHP\UserSystem\Controllers\UsersController::routeName('form-login', [], true)), null, null, true, false);
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y el caso de abajo falla.
        preg_match("#last-uri='([^']*)'#", $login['body'], $atributo);
        $valor = $atributo[1] ?? '';
        $check($valor !== '' && str_contains($valor, $escapar($marca)) && !str_contains($login['body'], "?zz={$marca}"), 'a1 el login pinta la URI pedida escapada dentro de last-uri', "HTTP {$login['status']}, atributo " . mb_substr($valor, -60));
        $contacto = $pedir("/contact/{$marcaURL}", null, null, false, false);
        $check($contacto['status'] === 200 && str_contains($contacto['body'], $escapar($marca)) && !str_contains($contacto['body'], "?zz={$marca}"), 'a2 el formulario de contacto pinta la URL actual escapada', "HTTP {$contacto['status']}");
        $database->prepare('INSERT INTO `newsletter_sucribers` (name, email, acceptUpdates, createdAt) VALUES (?, ?, ?, ?)')->execute(["{$prefijo}-suscriptor", "{$prefijo}{$marca}@example.com", 1, $ahora]);
        $suscriptor = (int) $database->lastInsertId();
        $suscriptores[] = $suscriptor;
        $edicion = $pedir($camino(\Newsletter\Controllers\NewsletterController::routeName('forms-edit', ['id' => $suscriptor], true)), $jwtRoot, null, false, false);
        $check($edicion['status'] === 200 && str_contains($edicion['body'], $escapar("{$prefijo}{$marca}@example.com")) && !str_contains($edicion['body'], "{$prefijo}{$marca}"), 'a3 la edición del suscriptor pinta su correo escapado', "HTTP {$edicion['status']}");

        //──── [b] Las claves de IA ──────────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[b] Las claves de IA no viajan al HTML, y vacías al guardar se conservan');
        $claves = ['OpenAIApiKey', 'MistralAIApiKey'];
        foreach ($claves as $nombre) {
            $clavesOriginales[$nombre] = SettingsModel::getConfigValue($nombre);
        }
        $falsa = 'zz-clave-falsa-' . bin2hex(random_bytes(8));
        $ponerClave = function (string $nombre, string $valor): void {
            $opcion = new SettingsModel($nombre);
            $opcion->value = $valor;
            if ($opcion->id !== null) {
                $opcion->update();
            } else {
                $opcion->name = $nombre;
                $opcion->save();
            }
        };
        foreach ($claves as $nombre) {
            $ponerClave($nombre, "{$falsa}-{$nombre}");
        }
        $rutaIA = $camino(\PiecesPHP\Settings\Controllers\SettingsController::routeName('integrations-ai', [], true));
        $pagina = $pedir($rutaIA, $jwtRoot, null, false, false);
        $check($pagina['status'] === 200 && !str_contains($pagina['body'], $falsa), 'b1 la página de IA no contiene las claves guardadas en la base', "HTTP {$pagina['status']}, contiene " . var_export(str_contains($pagina['body'], $falsa), true));
        //Lo que pintaba la vista vieja es la clave EN USO, que sale de secure-keys y pisa la de la base (api-keys.php,
        //override): se compara con esa, sin imprimirla. Sin clave en uso no hay nada que medir, y se dice.
        foreach ($claves as $nombre) {
            $enUso = (string) get_config($nombre);
            if ($enUso === '') {
                echoTerminal("   medido: {$nombre} no tiene clave en uso (sin secure-keys): b1b no mide nada para ella");
                continue;
            }
            $check(!str_contains($pagina['body'], $enUso), "b1b la página no contiene la clave en uso de {$nombre} (comparada, no impresa)", "HTTP {$pagina['status']}");
        }
        $check(str_contains($pagina['body'], 'name="OpenAIApiKey" value=""'), 'b2 y el campo de la clave va vacío', "HTTP {$pagina['status']}");
        //El resto de los campos, con su valor de ahora: el guardado no debe cambiar nada más.
        $resto = [];
        foreach (['modelOpenAI', 'modelMistral', 'translationAI'] as $nombre) {
            $resto[$nombre] = (string) (SettingsModel::getConfigValue($nombre) ?? get_config($nombre) ?? '');
        }
        $traduccion = SettingsModel::getConfigValue('translationAIEnable') ?? get_config('translationAIEnable');
        if ($traduccion === true || $traduccion === 1 || $traduccion === '1') {
            $resto['translationAIEnable'] = 1;
        }
        $guardar = $pedir($rutaIA, $jwtRoot, $resto + ['OpenAIApiKey' => '', 'MistralAIApiKey' => '']);
        $check(SettingsModel::getConfigValue('OpenAIApiKey') === "{$falsa}-OpenAIApiKey" && SettingsModel::getConfigValue('MistralAIApiKey') === "{$falsa}-MistralAIApiKey", 'b3 guardar con las claves vacías las conserva', "HTTP {$guardar['status']}");
        $guardar = $pedir($rutaIA, $jwtRoot, $resto + ['OpenAIApiKey' => "{$falsa}-nueva", 'MistralAIApiKey' => '']);
        $check(SettingsModel::getConfigValue('OpenAIApiKey') === "{$falsa}-nueva" && SettingsModel::getConfigValue('MistralAIApiKey') === "{$falsa}-MistralAIApiKey", 'b4 CANARIO: guardar otra clave la cambia (y la vacía sigue igual)', "HTTP {$guardar['status']}");

        //──── [c] El contenido de una publicación, en su aprobación y en su edición ──────────
        echoTerminal('');
        echoTerminal('[c] El contenido se lee aislado en la aprobación, y en los textarea no se sale del campo');
        $contenido = '<p>zz-contenido</p></textarea><script>zzScript()</script><img src="x" onerror="zzOnerror()">';
        $categoria = (int) (PublicationCategoryMapper::uncategorizedCategory()->id ?? 0);
        $lang = (string) get_config('default_lang');
        $publicacion = new PublicationMapper();
        $publicacion->baseLang = $lang;
        foreach (['title' => "{$prefijo} publicación", 'content' => $contenido, 'seoDescription' => '</textarea><script>zzSeo()</script>', 'publicDate' => new \DateTime(), 'startDate' => null, 'endDate' => null, 'category' => $categoria, 'visits' => 0, 'author' => $rootID, 'folder' => str_replace('.', '', uniqid()), 'featured' => PublicationMapper::UNFEATURED, 'mainImage' => 'statics/images/zz-prueba.jpg', 'thumbImage' => 'statics/images/zz-prueba.jpg', 'ogImage' => ''] as $campo => $valor) {
            $publicacion->setLangData($lang, $campo, $valor);
        }
        $publicacion->status = PublicationMapper::DRAFT;
        //`save()` exige un usuario en sesión, y el terminal no tiene: se le presta el autor y se devuelve el que había.
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
        $idPublicacion = (int) $publicacion->id;
        $publicaciones[] = $idPublicacion;
        $database->prepare("INSERT INTO `{$tablaAprobaciones}` (referenceAlias, referenceValue, referenceTable, referenceDate, createdAt, createdBy, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute(["{$prefijo}-publicacion", (string) $idPublicacion, PublicationMapper::TABLE, $ahora, $ahora, $rootID, SystemApprovalsMapper::STATUS_PENDING]);
        $filaPublicacion = (int) $database->lastInsertId();
        $aprobacion = $pedir($camino(\SystemApprovals\Controllers\SystemApprovalsController::routeName('forms-approval', ['id' => $filaPublicacion], true)), $jwtRoot, null, false, false);
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y el caso de abajo falla.
        preg_match('#<iframe[^>]*srcdoc="[^"]*"[^>]*>#', $aprobacion['body'], $marco);
        $marco = $marco[0] ?? '';
        $check($marco !== '' && str_contains($marco, 'sandbox=""') && !str_contains($marco, 'allow-scripts'), 'c1 la aprobación pinta el contenido en un iframe sandbox sin allow-scripts', "HTTP {$aprobacion['status']}, iframe " . var_export($marco !== '', true));
        $check(str_contains($marco, $escapar($contenido)) && !str_contains($aprobacion['body'], '<script>zzScript()'), 'c2 y dentro de srcdoc va escapado: el script no entra en la página', "HTTP {$aprobacion['status']}");
        $edicion = $pedir($camino(\Publications\Controllers\PublicationsController::routeName('forms-edit', ['id' => $idPublicacion], true)), $jwtRoot, null, false, false);
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y el caso de abajo falla.
        preg_match('#<textarea name="content"[^>]*>(.*?)</textarea>#s', $edicion['body'], $campo);
        $dentro = $campo[1] ?? null;
        $check($edicion['status'] === 200 && !str_contains($edicion['body'], '</textarea><script>zzScript()'), 'c3 la edición de la publicación no deja que el contenido se salga del textarea', "HTTP {$edicion['status']}");
        //El navegador decodifica el texto de un textarea: lo que el editor carga es lo que se guardó.
        $check($dentro !== null && html_entity_decode($dentro, ENT_QUOTES | ENT_HTML5, 'UTF-8') === $contenido, 'c4 y lo que hay dentro, decodificado, es el contenido guardado tal cual', 'campo ' . var_export($dentro !== null, true));
        $database->prepare('INSERT INTO `' . BuiltInBannerMapper::TABLE . '` (title, content, link, desktopImage, mobileImage, orderPosition, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute(["{$prefijo}-banner", $contenido, '', 'statics/images/zz-prueba.jpg', '', 0, "{$prefijo}-banner", $ahora, $rootID, BuiltInBannerMapper::ACTIVE, json_encode(['baseLang' => $lang, 'langData' => new \stdClass])]);
        $banner = (int) $database->lastInsertId();
        $banners[] = $banner;
        $edicion = $pedir($camino(\PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController::routeName('forms-edit', ['id' => $banner, 'lang' => $lang], true)), $jwtRoot, null, false, false);
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y el caso de abajo falla.
        preg_match('#<textarea name="content"[^>]*>(.*?)</textarea>#s', $edicion['body'], $campo);
        $dentro = $campo[1] ?? null;
        $check($edicion['status'] === 200 && !str_contains($edicion['body'], '</textarea><script>zzScript()') && $dentro !== null && html_entity_decode($dentro, ENT_QUOTES | ENT_HTML5, 'UTF-8') === $contenido, 'c5 la edición del banner tampoco: dentro, decodificado, el contenido tal cual', "HTTP {$edicion['status']}, campo " . var_export($dentro !== null, true));

        //Los otros textarea: el seoDescription de la publicación, las noticias y los documentos.
        $edicion = $pedir($camino(\Publications\Controllers\PublicationsController::routeName('forms-edit', ['id' => $idPublicacion], true)), $jwtRoot, null, false, false);
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y el caso de abajo falla.
        preg_match('#<textarea name="seoDescription"[^>]*>(.*?)</textarea>#s', $edicion['body'], $campo);
        $dentro = $campo[1] ?? null;
        $check(!str_contains($edicion['body'], '</textarea><script>zzSeo()') && $dentro !== null && html_entity_decode($dentro, ENT_QUOTES | ENT_HTML5, 'UTF-8') === '</textarea><script>zzSeo()</script>', 'c6 el seoDescription de la publicación no se sale de su textarea', "HTTP {$edicion['status']}, campo " . var_export($dentro !== null, true));
        $database->prepare('INSERT INTO `news_categories` (name, iconImage, color, meta) VALUES (?, ?, ?, ?)')->execute(["{$prefijo}-categoria", 'statics/images/zz-prueba.jpg', '#000000', json_encode(['baseLang' => $lang, 'langData' => new \stdClass])]);
        $categoriaNoticia = (int) $database->lastInsertId();
        $categoriasNoticias[] = $categoriaNoticia;
        $database->prepare('INSERT INTO `' . NewsMapper::TABLE . '` (newsTitle, profilesTarget, content, category, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute(["{$prefijo}-noticia", '[]', $contenido, $categoriaNoticia, "{$prefijo}-noticia", $ahora, $rootID, 1, json_encode(['baseLang' => $lang, 'langData' => new \stdClass])]);
        $noticia = (int) $database->lastInsertId();
        $noticias[] = $noticia;
        $edicion = $pedir($camino(\News\Controllers\NewsController::routeName('forms-edit', ['id' => $noticia], true)), $jwtRoot, null, false, false);
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y el caso de abajo falla.
        preg_match('#<textarea name="content\[[a-z_-]+\]"[^>]*>(.*?)</textarea>#s', $edicion['body'], $campo);
        $dentro = $campo[1] ?? null;
        //El div del editor de noticias pinta el contenido crudo (fuera de esta ronda): aquí solo se mira el textarea.
        $check($edicion['status'] === 200 && $dentro !== null && str_contains($dentro, '&lt;/textarea&gt;') && html_entity_decode($dentro, ENT_QUOTES | ENT_HTML5, 'UTF-8') === $contenido, 'c7 el textarea de la noticia lleva el contenido escapado y, decodificado, tal cual', "HTTP {$edicion['status']}, campo " . var_export($dentro !== null, true));
        $database->prepare('INSERT INTO `forms_document_types` (documentTypeName, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?)')->execute(["{$prefijo}-tipo", "{$prefijo}-tipo", 1, $ahora, $rootID, json_encode(['baseLang' => $lang, 'langData' => new \stdClass])]);
        $tipoDocumento = (int) $database->lastInsertId();
        $tiposDocumento[] = $tipoDocumento;
        $descripcion = '</textarea><script>zzDoc()</script>';
        $database->prepare('INSERT INTO `' . DocumentsMapper::TABLE . '` (documentType, documentName, description, document, documentImage, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$tipoDocumento, "{$prefijo}-documento", $descripcion, 'statics/images/zz-prueba.jpg', 'statics/images/zz-prueba.jpg', "{$prefijo}-documento", 1, $ahora, $rootID, json_encode(['baseLang' => $lang, 'langData' => new \stdClass])]);
        $documento = (int) $database->lastInsertId();
        $documentos[] = $documento;
        $edicion = $pedir($camino(\Documents\Controllers\DocumentsController::routeName('forms-edit', ['id' => $documento, 'lang' => $lang], true)), $jwtRoot, null, false, false);
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y el caso de abajo falla.
        preg_match('#<textarea name="description"[^>]*>(.*?)</textarea>#s', $edicion['body'], $campo);
        $dentro = $campo[1] ?? null;
        $check($edicion['status'] === 200 && !str_contains($edicion['body'], '</textarea><script>zzDoc()') && $dentro !== null && html_entity_decode($dentro, ENT_QUOTES | ENT_HTML5, 'UTF-8') === $descripcion, 'c8 la descripción del documento no se sale de su textarea', "HTTP {$edicion['status']}, campo " . var_export($dentro !== null, true));

        //──── [e] El boletín ───────────────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[e] La suscripción pública exige un correo válido, y el listado del panel lo escapa');
        $rutaAlta = $camino(\Newsletter\Controllers\NewsletterController::routeName('add', [], true));
        $malo = '<img src=x onerror=zzNl()>@zz.test';
        $alta = $pedir($rutaAlta, null, ['email' => $malo]);
        $guardados = (int) $database->query('SELECT COUNT(*) FROM `newsletter_sucribers` WHERE email = ' . $database->quote($malo))->fetchColumn();
        $datos = json_decode($alta['body'], true);
        $check($guardados === 0 && ($datos['success'] ?? null) !== true, 'e1 un alta anónima con «<img …>@zz.test» se rechaza y no se guarda', "HTTP {$alta['status']}, guardados {$guardados}");
        //El alta no envía correo (addSuscriber solo guarda): el buzón es de mailinator igual.
        $bueno = $prefijo . '@mailinator.com';
        echoTerminal("   correo del alta válida (no se envía nada): {$bueno}");
        $alta = $pedir($rutaAlta, null, ['email' => $bueno]);
        $idBueno = (int) $database->query('SELECT id FROM `newsletter_sucribers` WHERE email = ' . $database->quote($bueno))->fetchColumn();
        if ($idBueno > 0) {
            $suscriptores[] = $idBueno;
        }
        $check($idBueno > 0, 'e2 CANARIO: un correo válido se suscribe', "HTTP {$alta['status']}");
        //Un registro de antes de la validación, con ese correo: el listado lo pinta escapado.
        $viejo = new NewsletterSuscriberMapper();
        $viejo->name = "{$prefijo}-viejo<b>";
        $viejo->email = $malo;
        $viejo->acceptUpdates = NewsletterSuscriberMapper::ACCEPT_UPDATES_YES;
        $viejo->save();
        $idViejo = (int) $database->query('SELECT id FROM `newsletter_sucribers` WHERE email = ' . $database->quote($malo))->fetchColumn();
        if ($idViejo > 0) {
            $suscriptores[] = $idViejo;
        }
        $listado = $pedir($camino(\Newsletter\Controllers\NewsletterController::routeName('datatables', [], true)) . '?' . http_build_query(['draw' => 1, 'start' => 0, 'length' => 5000]), $jwtRoot);
        $filas = json_decode($listado['body'], true);
        $celdas = '';
        foreach ((is_array($filas) && is_array($filas['data'] ?? null) ? $filas['data'] : []) as $fila) {
            $celdas .= implode(' ', array_map(fn ($c) => is_scalar($c) ? (string) $c : '', is_array($fila) ? $fila : []));
        }
        $check($idViejo > 0 && !str_contains($celdas, $malo) && str_contains($celdas, $escapar($malo)) && str_contains($celdas, $escapar("{$prefijo}-viejo<b>")), 'e3 el listado pinta escapados el correo y el nombre de un registro viejo', "HTTP {$listado['status']}, crudo " . var_export(str_contains($celdas, $malo), true));

        //──── [f] La contraseña SMTP y la clave de osTicket ─────────────────────────────────
        echoTerminal('');
        echoTerminal('[f] La contraseña SMTP y la clave de osTicket no viajan al HTML, y vacías se conservan');
        //La columna CRUDA, por SQL: el mapper decodifica el JSON, y reponer lo decodificado cambiaría el formato guardado.
        $tablaOpciones = (new SettingsModel())->getModel()->getTable();
        $clavesSoloRoot = [\PiecesPHP\Core\Utilities\Helpers\ExtraScripts::CONFIG_NAME, \PiecesPHP\Core\SessionToken::MINIMUM_DATE_CONFIG, \PiecesPHP\Core\Backups\BackupPolicy::CONFIG_NAME, \DataImportExportUtility\Controllers\DataTransferController::DISABLED_CONFIG, \PiecesPHP\SystemStatus\SystemAlertRegistry::HIDDEN_CONFIG];
        $coloresMarca = \PiecesPHP\Settings\Controllers\SettingsController::GENERIC_SAVE_ALLOWED;
        $fueraDeLista = ['roles', \PiecesPHP\Core\Utilities\Helpers\ExtraScripts::CONFIG_NAME, \Terminal\Tasks\SettingsMigrateExtraScriptsTask::OLD_OPTION, 'GoogleReCaptchaV3Controller', \Terminal\Tasks\RepairEscapedTextTask::MARKER, 'zz_inventado_' . bin2hex(random_bytes(3))];
        foreach (array_unique(array_merge(['mail', MailDelivery::CONFIG_NAME, 'osTicketAPI', 'osTicketAPIKey', 'owner'], $clavesSoloRoot, $coloresMarca, $fueraDeLista)) as $nombre) {
            $consulta = $database->prepare("SELECT value FROM `{$tablaOpciones}` WHERE name = ?");
            $consulta->execute([$nombre]);
            $crudo = $consulta->fetchColumn();
            $opcionesCrudas[$nombre] = $crudo === false ? null : (string) $crudo;
        }
        //MailConfig lee `mail` de la configuración cargada al arrancar: para medir lo guardado hay que volcar antes la base.
        $correoEnBase = function (): MailConfig {
            set_config('mail', (new SettingsModel('mail'))->value);
            return new MailConfig();
        };
        //Los getters de MailConfig devuelven el valor o el propio objeto: se estrecha por tipo, no con un cast.
        $texto = fn ($valor): string => is_string($valor) ? $valor : '';
        $entero = fn ($valor): int => is_int($valor) ? $valor : 0;
        $correo = $correoEnBase();
        $contrasena = $texto($correo->password());
        $rutaCorreo = $camino(\PiecesPHP\Settings\Controllers\SettingsController::routeName('integrations-mail', [], true));
        $pagina = $pedir($rutaCorreo, $jwtRoot, null, false, false);
        if ($contrasena === '') {
            echoTerminal('   medido: no hay contraseña SMTP guardada: f1 no mide nada');
        } else {
            $check(!str_contains($pagina['body'], $escapar($contrasena)) && !str_contains($pagina['body'], htmlentities($contrasena)), 'f1 la página de correo no contiene la contraseña guardada (comparada, no impresa)', "HTTP {$pagina['status']}");
        }
        $check(str_contains($pagina['body'], 'name="password" value=""'), 'f2 y su campo va vacío', "HTTP {$pagina['status']}");
        $camposCorreo = [
            'auto_tls' => $correo->autoTls() ? 1 : 0,
            'auth' => $correo->auth() ? 1 : 0,
            'host' => $texto($correo->host()),
            'protocol' => $texto($correo->protocol()),
            'port' => $entero($correo->port()),
            'user' => $texto($correo->user()),
            'name' => $texto($correo->name()),
            'mail_delivery' => (string) (SettingsModel::getConfigValue(MailDelivery::CONFIG_NAME) ?? MailDelivery::AUTO),
            'test_host' => $texto($correo->testHost()),
            'test_port' => $entero($correo->testPort()),
        ];
        $guardar = $pedir($rutaCorreo, $jwtRoot, $camposCorreo + ['password' => '']);
        $check($texto($correoEnBase()->password()) === $contrasena, 'f3 guardar el correo con la contraseña vacía la conserva', "HTTP {$guardar['status']}");
        $falsaSmtp = 'zz-contrasena-falsa-' . bin2hex(random_bytes(6));
        $guardar = $pedir($rutaCorreo, $jwtRoot, $camposCorreo + ['password' => $falsaSmtp]);
        $check($texto($correoEnBase()->password()) === $falsaSmtp, 'f4 CANARIO: guardar otra contraseña la cambia', "HTTP {$guardar['status']}, mensaje " . mb_substr((string) (json_decode($guardar['body'], true)['message'] ?? ''), 0, 160));
        $claveOs = (string) (SettingsModel::getConfigValue('osTicketAPIKey') ?? '');
        $rutaOs = $camino(\PiecesPHP\Settings\Controllers\SettingsController::routeName('integrations-osticket', [], true));
        if ($claveOs === '') {
            $ponerClave('osTicketAPIKey', "{$falsa}-osticket");
            $claveOs = "{$falsa}-osticket";
        }
        $pagina = $pedir($rutaOs, $jwtRoot, null, false, false);
        $check($pagina['status'] === 200 && !str_contains($pagina['body'], $claveOs) && str_contains($pagina['body'], 'name="key" value=""'), 'f5 la página de osTicket no contiene la clave guardada y su campo va vacío', "HTTP {$pagina['status']}");
        $urlOs = (string) (SettingsModel::getConfigValue('osTicketAPI') ?? '');
        $guardar = $pedir($rutaOs, $jwtRoot, ['url' => $urlOs, 'key' => '']);
        $check(SettingsModel::getConfigValue('osTicketAPIKey') === $claveOs, 'f6 guardar osTicket con la clave vacía la conserva', "HTTP {$guardar['status']}");
        $guardar = $pedir($rutaOs, $jwtRoot, ['url' => $urlOs, 'key' => "{$falsa}-osticket-nueva"]);
        $check(SettingsModel::getConfigValue('osTicketAPIKey') === "{$falsa}-osticket-nueva", 'f7 CANARIO: guardar otra clave de osTicket la cambia', "HTTP {$guardar['status']}");

        //──── [g] El boletín, por marcador; el nombre; y las credenciales, solo del principal ──
        echoTerminal('');
        echoTerminal('[g] Un correo con comilla se guarda y se reconoce por marcador; el nombre sale escapado; las credenciales, solo del principal');
        //Una comilla simple es legal en la parte local de un correo: con la consulta concatenada, rompía el literal.
        $conComilla = "{$prefijo}.o'brien@mailinator.com";
        echoTerminal("   correo con comilla del alta (no se envía nada): {$conComilla}");
        $alta = $pedir($rutaAlta, null, ['email' => $conComilla]);
        $datos = json_decode($alta['body'], true);
        $idComilla = (int) $database->query('SELECT id FROM `newsletter_sucribers` WHERE email = ' . $database->quote($conComilla))->fetchColumn();
        if ($idComilla > 0) {
            $suscriptores[] = $idComilla;
        }
        $check($idComilla > 0 && ($datos['success'] ?? null) === true, 'g1 un alta anónima con o\'brien en la parte local se guarda', "HTTP {$alta['status']}");
        $otra = $pedir($rutaAlta, null, ['email' => $conComilla]);
        $datos = json_decode($otra['body'], true);
        $repetidos = (int) $database->query('SELECT COUNT(*) FROM `newsletter_sucribers` WHERE email = ' . $database->quote($conComilla))->fetchColumn();
        $check($otra['status'] === 200 && ($datos['success'] ?? null) !== true && $repetidos === 1, 'g2 la segunda igual no se guarda otra vez y responde sin error de servidor', "HTTP {$otra['status']}, filas {$repetidos}");
        $check(NewsletterSuscriberMapper::existByEmail($conComilla) && !NewsletterSuscriberMapper::existByEmail("{$prefijo}.otro'@mailinator.com"), 'g3 existByEmail reconoce el correo con comilla, y no otro con comilla que no existe');
        $database->prepare('UPDATE `newsletter_sucribers` SET name = ? WHERE id = ?')->execute(["{$prefijo}-suscriptor{$marca}", $suscriptor]);
        $edicion = $pedir($camino(\Newsletter\Controllers\NewsletterController::routeName('forms-edit', ['id' => $suscriptor], true)), $jwtRoot, null, false, false);
        $check($edicion['status'] === 200 && str_contains($edicion['body'], $escapar("{$prefijo}-suscriptor{$marca}")) && !str_contains($edicion['body'], "-suscriptor{$marca}"), 'g4 la edición del suscriptor pinta su nombre escapado', "HTTP {$edicion['status']}");
        //Las credenciales no se escriben por la acción genérica salvo el principal: cambiar el destino y dejar el secreto
        //vacío lo enviaría a otro servidor.
        $admin = new UsersModel();
        $admin->username = "{$prefijo}-admin";
        $admin->email = "{$prefijo}-admin@example.com";
        $admin->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $admin->firstname = 'Zz';
        $admin->secondname = '';
        $admin->firstLastname = 'Vistas';
        $admin->secondLastname = '';
        $admin->type = UsersModel::TYPE_USER_ADMIN_GRAL;
        $admin->status = UsersModel::STATUS_USER_ACTIVE;
        $admin->failedAttempts = 0;
        $admin->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $admin->createdAt = new \DateTime();
        $admin->modifiedAt = $admin->createdAt;
        $admin->save();
        $usuarios[] = (int) $admin->id;
        $jwtAdmin = SessionToken::generateToken(['id' => (int) $admin->id], null, null, false);
        $rutaGenerica = $camino(\PiecesPHP\Settings\Controllers\SettingsController::routeName('generic-save', [], true));
        foreach (['mail', MailDelivery::CONFIG_NAME, 'osTicketAPI', 'osTicketAPIKey'] as $n => $nombre) {
            $r = $pedir($rutaGenerica, $jwtAdmin, ['name' => $nombre, 'value' => 'zz-no-debe-guardarse']);
            $check($r['status'] === 403, 'g' . (5 + $n) . " un administrador general no escribe «{$nombre}» por la acción genérica", "HTTP {$r['status']}");
        }
        //Desde 406 la acción genérica solo guarda colores de marca, también para el principal: el canario es un color.
        $r = $pedir($rutaGenerica, $jwtRoot, ['name' => 'osTicketAPI', 'value' => 'zz-no-debe-guardarse']);
        $check($r['status'] === 403, 'g9 y el principal tampoco: osTicket tiene su pantalla', "HTTP {$r['status']}");

        //──── [h] La pantalla del correo, solo del principal; y la lista contra la fila que se escribe ──
        echoTerminal('');
        echoTerminal('[h] La pantalla del correo es solo del principal, y la lista de solo root se compara con la fila que se escribe');
        $r = $pedir($rutaCorreo, $jwtAdmin, null, false, false);
        $check($r['status'] === 403, 'h1 el administrador general no abre la pantalla del correo (GET)', "HTTP {$r['status']}");
        $r = $pedir($rutaCorreo, $jwtAdmin, $camposCorreo + ['password' => '']);
        $check($r['status'] === 403, 'h2 ni la guarda (POST)', "HTTP {$r['status']}");
        $r = $pedir($rutaCorreo, $jwtRoot, null, false, false);
        $check($r['status'] === 200, 'h3 CANARIO: el principal sí la abre', "HTTP {$r['status']}");
        //Con utf8mb4_bin (PAD SPACE), «mail » encuentra la fila «mail»: la lista tiene que mirar esa fila, no el texto.
        //Lo de antes, leído ahora: f4 ya cambió la contraseña (se repone al final).
        $consulta = $database->prepare("SELECT value FROM `{$tablaOpciones}` WHERE BINARY name = 'mail'");
        $consulta->execute();
        $mailAntes = $consulta->fetchColumn();
        $mailAntes = $mailAntes === false ? null : (string) $mailAntes;
        $r = $pedir($rutaGenerica, $jwtAdmin, ['name' => 'mail ', 'value' => 'zz-no-debe-guardarse']);
        $consulta->execute();
        $mailDespues = $consulta->fetchColumn();
        //Desde 405 lo frena antes la validación del nombre (un espacio no es de clave); antes, la lista contra la fila.
        $datos = json_decode($r['body'], true);
        $check(($r['status'] === 403 || ($datos['success'] ?? null) === false) && ($mailDespues === false ? null : (string) $mailDespues) === $mailAntes, 'h4 «mail » con espacio final, que la base iguala a «mail», se rechaza y no la toca', "HTTP {$r['status']}");
        //En mayúsculas la colación no la iguala: no llega a la fila «mail» (se mide), y la fila suelta que cree se retira.
        $r = $pedir($rutaGenerica, $jwtAdmin, ['name' => 'MAIL', 'value' => 'zz-no-debe-guardarse']);
        $consulta->execute();
        $mailDespues = $consulta->fetchColumn();
        $check(($mailDespues === false ? null : (string) $mailDespues) === $mailAntes, 'h5 «MAIL» en mayúsculas no llega a la fila «mail» (la colación distingue mayúsculas)', "HTTP {$r['status']}");
        $database->exec("DELETE FROM `{$tablaOpciones}` WHERE BINARY name = 'MAIL'");

        //──── [i] Las claves con pantalla o tarea solo del principal, y los nombres que no son de clave ──
        echoTerminal('');
        echoTerminal('[i] Las claves solo del principal no se escriben por la acción genérica, y un nombre que no es de clave no crea fila');
        $crudoDe = function (string $nombre) use ($database, $tablaOpciones): ?string {
            $consulta = $database->prepare("SELECT value FROM `{$tablaOpciones}` WHERE BINARY name = ?");
            $consulta->execute([$nombre]);
            $valor = $consulta->fetchColumn();
            return $valor === false ? null : (string) $valor;
        };
        $n = 1;
        foreach ($clavesSoloRoot as $nombre) {
            $antes = $crudoDe($nombre);
            $r = $pedir($rutaGenerica, $jwtAdmin, ['name' => $nombre, 'value' => 'zz-no-debe-guardarse']);
            $check($r['status'] === 403 && $crudoDe($nombre) === $antes, "i{$n} un administrador general no escribe «{$nombre}» (403, la fila no cambia)", "HTTP {$r['status']}");
            $n++;
        }
        //Desde 406 tampoco el principal: esas claves tienen su pantalla (los colores, en [k], son el canario).
        $sistemaOcultos = \PiecesPHP\SystemStatus\SystemAlertRegistry::HIDDEN_CONFIG;
        $antes = $crudoDe($sistemaOcultos);
        $r = $pedir($rutaGenerica, $jwtRoot, ['name' => $sistemaOcultos, 'value' => 'zz-no-debe-guardarse']);
        $check($r['status'] === 403 && $crudoDe($sistemaOcultos) === $antes, "i{$n} y el principal tampoco escribe «{$sistemaOcultos}» por aquí", "HTTP {$r['status']}");
        $n++;
        $r = $pedir($rutaGenerica, $jwtAdmin, ['name' => 'MAIL', 'value' => 'zz-no-debe-guardarse']);
        $check($r['status'] === 403, "i{$n} «MAIL» en mayúsculas da 403: la lista no distingue mayúsculas", "HTTP {$r['status']}");
        $n++;
        $r = $pedir($rutaGenerica, $jwtRoot, ['name' => 'zz_clave_prueba ', 'value' => 'zz']);
        $filas = (int) $database->query("SELECT COUNT(*) FROM `{$tablaOpciones}` WHERE name LIKE 'zz_clave_prueba%'")->fetchColumn();
        $datos = json_decode($r['body'], true);
        $check($filas === 0 && ($datos['success'] ?? null) !== true, "i{$n} un nombre con espacio final se rechaza y no crea fila", "HTTP {$r['status']}, filas {$filas}");
        $n++;
        $r = $pedir($rutaGenerica, $jwtRoot, ['name' => 'zz_clave_prueba', 'value' => 'zz']);
        $filas = (int) $database->query("SELECT COUNT(*) FROM `{$tablaOpciones}` WHERE BINARY name = 'zz_clave_prueba'")->fetchColumn();
        $check($r['status'] === 403 && $filas === 0, "i{$n} un nombre de clave inventado se rechaza (403) y no crea fila", "HTTP {$r['status']}, filas {$filas}");
        $database->exec("DELETE FROM `{$tablaOpciones}` WHERE name LIKE 'zz_clave_prueba%'");
        $n++;
        $avisoCorreo = \PiecesPHP\SystemStatus\SystemAlertRegistry::all()['mail-test-mode'] ?? null;
        $check($avisoCorreo !== null && $avisoCorreo->audience() === [UsersModel::TYPE_USER_ROOT], "i{$n} el aviso del correo retenido es solo para el principal", $avisoCorreo !== null ? (string) json_encode($avisoCorreo->audience()) : 'sin aviso');

        //──── [k] La acción genérica: solo colores de marca ─────────────────────────────────
        echoTerminal('');
        echoTerminal('[k] La acción genérica solo guarda los colores de marca; cualquier otro nombre se rechaza, también al principal');
        $n = 1;
        foreach ($coloresMarca as $color) {
            //CANARIO: con su mismo valor (y la fila se repone en crudo al final).
            //Como la pestaña: el color en claro (el de la configuración en uso) y parse «uppercase».
            $enUso = get_config($color);
            $r = $pedir($rutaGenerica, $jwtRoot, ['name' => $color, 'value' => is_string($enUso) && $enUso !== '' ? $enUso : '#000000', 'parse' => 'uppercase']);
            $datos = json_decode($r['body'], true);
            $check($r['status'] === 200 && ($datos['success'] ?? null) === true, "k{$n} CANARIO: el color «{$color}» se guarda", "HTTP {$r['status']}");
            $n++;
        }
        //Si una guarda faltara, la fila se repone AL MOMENTO: una fila «roles» apagaría el control por roles del sitio.
        $reponerAhora = function (string $nombre, ?string $crudo) use ($database, $tablaOpciones): void {
            if ($crudo === null) {
                $database->prepare("DELETE FROM `{$tablaOpciones}` WHERE BINARY name = ?")->execute([$nombre]);
            } else {
                $database->prepare("UPDATE `{$tablaOpciones}` SET value = ? WHERE BINARY name = ?")->execute([$crudo, $nombre]);
            }
        };
        foreach ($fueraDeLista as $nombre) {
            $antes = $crudoDe($nombre);
            $r = $pedir($rutaGenerica, $jwtRoot, ['name' => $nombre, 'value' => 'zz-no-debe-guardarse']);
            $despues = $crudoDe($nombre);
            $reponerAhora($nombre, $antes);
            $check($r['status'] === 403 && $despues === $antes, "k{$n} «{$nombre}» se rechaza (403) y su fila no cambia", "HTTP {$r['status']}");
            $n++;
        }
        //A1: el valor tiene que ser un color, como lo manda la pestaña. Nada de esto escribe.
        $malos = [
            'una lista' => ['name' => 'second_brand_color', 'value' => ['zz'], 'parse' => 'uppercase'],
            'un cierre de estilo' => ['name' => 'second_brand_color', 'value' => '#FFF</style><b>zz', 'parse' => 'uppercase'],
            'merge' => ['name' => 'second_brand_color', 'value' => '#FFFFFF', 'parse' => 'uppercase', 'merge' => 'true'],
            'otro parse' => ['name' => 'second_brand_color', 'value' => '#FFFFFF', 'parse' => 'json_decode'],
            'sin parse' => ['name' => 'second_brand_color', 'value' => '#FFFFFF'],
            '#FFF donde va #RRGGBB' => ['name' => 'main_brand_color', 'value' => '#FFF', 'parse' => 'uppercase'],
            'rgba con texto' => ['name' => 'menu_color_mark', 'value' => 'rgba(1, 2, 3, 0.5) zz', 'parse' => 'uppercase'],
        ];
        foreach ($malos as $como => $cuerpo) {
            $antes = $crudoDe($cuerpo['name']);
            $r = $pedir($rutaGenerica, $jwtRoot, $cuerpo);
            $despues = $crudoDe($cuerpo['name']);
            $reponerAhora($cuerpo['name'], $antes);
            $datos = json_decode($r['body'], true);
            $check(($datos['success'] ?? null) !== true && $despues === $antes, "k{$n} un color con {$como} se rechaza y la fila no cambia", "HTTP {$r['status']}");
            $n++;
        }
        $check(\PiecesPHP\Settings\Controllers\SettingsController::isBrandColorValue('menu_color_mark', 'rgba(255, 255, 255, 0.2)') && \PiecesPHP\Settings\Controllers\SettingsController::isBrandColorValue('main_brand_color', '#6435C9') && \PiecesPHP\Settings\Controllers\SettingsController::isBrandColorValue('font_color_one', ''), "k{$n} CANARIO: los valores de hoy (rgba(...) y #RRGGBB) y el vacío casan");
        $n++;
        $rolesAntes = $crudoDe('roles');
        $r = $pedir($rutaGenerica, $jwtAdmin, ['name' => 'roles', 'value' => 'zz-no-debe-guardarse']);
        $despues = $crudoDe('roles');
        $reponerAhora('roles', $rolesAntes);
        $check($r['status'] === 403 && $despues === $rolesAntes, "k{$n} y el administrador general tampoco escribe «roles»: la fila no cambia", "HTTP {$r['status']}");
        $n++;
        //Lo que envía la pestaña «Colores» está en la lista: un campo nuevo sin su entrada daría 403 con todo en verde.
        $pestana = (string) file_get_contents(basepath('app/classes/PiecesPHP/Settings/Views/panel/pages/app_configurations/inc/configuration-tabs/general.php'));
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y el caso de abajo falla.
        preg_match_all('#name="name" value="([^"]+)"#', $pestana, $enviados);
        $fuera = array_diff($enviados[1], $coloresMarca);
        $check(count($enviados[1]) > 0 && $fuera === [], "k{$n} los " . count($enviados[1]) . ' nombres que envía la pestaña «Colores» están en GENERIC_SAVE_ALLOWED', implode(', ', $fuera));

        //──── [l] Las plantillas de correo escapan la marca ───────────────────────────────────
        echoTerminal('');
        echoTerminal('[l] La plantilla de los correos escapa el propietario y el color de marca, aunque la fila venga plantada de antes');
        $ponerCrudo = function (string $nombre, string $valor) use ($database, $tablaOpciones, $crudoDe): void {
            if ($crudoDe($nombre) === null) {
                $database->prepare("INSERT INTO `{$tablaOpciones}` (name, value) VALUES (?, ?)")->execute([$nombre, $valor]);
            } else {
                $database->prepare("UPDATE `{$tablaOpciones}` SET value = ? WHERE BINARY name = ?")->execute([$valor, $nombre]);
            }
        };
        $ownerAntes = $crudoDe('owner');
        $colorAntes = $crudoDe('main_brand_color');
        $ponerCrudo('owner', 'Zz' . $marca);
        $ponerCrudo('main_brand_color', '#123456</style><b>zzColor');
        $muestra = $pedir($camino(\MySpace\Controllers\MySpaceController::routeName('iframe-sources', ['source' => 'mail-users-template'], true)), $jwtRoot, null, false, false);
        $reponerAhora('owner', $ownerAntes);
        $reponerAhora('main_brand_color', $colorAntes);
        $check($muestra['status'] === 200 && !str_contains($muestra['body'], 'Zz' . $marca) && str_contains($muestra['body'], $escapar('Zz' . $marca)), 'l1 la muestra de correo pinta el propietario escapado', "HTTP {$muestra['status']}");
        $check(!str_contains($muestra['body'], '</style><b>zzColor') && str_contains($muestra['body'], $escapar('#123456</style><b>zzColor')), 'l2 y el color de marca, escapado dentro del estilo', "HTTP {$muestra['status']}");
        //El pie por defecto de las dos plantillas (la muestra pasa el suyo): en proceso, con owner fijado y devuelto.
        $ownerEnUso = get_config('owner');
        set_config('owner', 'Zz' . $marca);
        try {
            $plantillas = [];
            foreach (['mailing/template_base', 'mailing/template_base_no_style'] as $plantilla) {
                $plantillas[$plantilla] = (string) (new \PiecesPHP\Components\Controllers\HelperController())->render($plantilla, [], false, false);
            }
        } finally {
            set_config('owner', $ownerEnUso);
        }
        foreach ($plantillas as $plantilla => $html) {
            $check(!str_contains($html, 'Zz' . $marca) && str_contains($html, $escapar('Zz' . $marca)), "l4 el pie por defecto de {$plantilla} pinta el propietario escapado", 'longitud ' . strlen($html));
        }
        $check(htmlspecialchars('#6435C9', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') === '#6435C9' && htmlspecialchars('rgba(255, 255, 255, 0.2)', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') === 'rgba(255, 255, 255, 0.2)', 'l3 CANARIO: un color válido no cambia al escaparlo');

        //──── [m] La acción SEO solo acepta los idiomas de la instalación ───────────────────
        echoTerminal('');
        echoTerminal('[m] La acción SEO rechaza un idioma que no es de la instalación sin crear fila');
        //Foto de la tabla entera: el canario guarda opciones de SEO, y todo se repone tal cual al momento.
        $fotoOpciones = $database->query("SELECT name, value FROM `{$tablaOpciones}`")->fetchAll(\PDO::FETCH_KEY_PAIR);
        $reponerTabla = function () use ($database, $tablaOpciones, $fotoOpciones): void {
            $ahora = $database->query("SELECT name, value FROM `{$tablaOpciones}`")->fetchAll(\PDO::FETCH_KEY_PAIR);
            foreach ($ahora as $nombre => $valor) {
                if (!array_key_exists($nombre, $fotoOpciones)) {
                    $database->prepare("DELETE FROM `{$tablaOpciones}` WHERE BINARY name = ?")->execute([$nombre]);
                } elseif ($fotoOpciones[$nombre] !== $valor) {
                    $database->prepare("UPDATE `{$tablaOpciones}` SET value = ? WHERE BINARY name = ?")->execute([$fotoOpciones[$nombre], $nombre]);
                }
            }
        };
        $rutaSeo = $camino(\PiecesPHP\Settings\Controllers\SettingsController::routeName('appearance-seo', [], true));
        //Con todos los campos, como el formulario: lo único que falla es el idioma.
        $r = $pedir($rutaSeo, $jwtRoot, ['lang' => 'zz', 'titleApp' => 'zz-no-debe-guardarse', 'owner' => 'zz', 'description' => 'zz', 'keywords' => ['zz']]);
        $nuevas = array_diff(array_keys($database->query("SELECT name, value FROM `{$tablaOpciones}`")->fetchAll(\PDO::FETCH_KEY_PAIR)), array_keys($fotoOpciones));
        $reponerTabla();
        $datos = json_decode($r['body'], true);
        $check(($datos['success'] ?? null) !== true && $nuevas === [], 'm1 «lang=zz» se rechaza y no crea ninguna fila', "HTTP {$r['status']}, filas nuevas " . count($nuevas));
        $idiomaBase = (string) get_config('default_lang');
        $valorSeo = function (string $nombre, string $respaldo) use ($idiomaBase): string {
            $valor = SettingsModel::getConfigValue("{$nombre}_{$idiomaBase}") ?? get_config($nombre);
            return is_string($valor) && $valor !== '' ? $valor : $respaldo;
        };
        $r = $pedir($rutaSeo, $jwtRoot, ['lang' => $idiomaBase, 'titleApp' => $valorSeo('title_app', 'PiecesPHP'), 'owner' => $valorSeo('owner', 'PiecesPHP'), 'description' => $valorSeo('description', 'PiecesPHP'), 'keywords' => (function () use ($idiomaBase): array {
            $lista = SettingsModel::getConfigValue("keywords_{$idiomaBase}") ?? get_config('keywords');
            $lista = is_array($lista) ? array_values(array_filter($lista, 'is_string')) : [];
            return $lista !== [] ? $lista : ['PiecesPHP'];
        })()]);
        $reponerTabla();
        $datos = json_decode($r['body'], true);
        $check(($datos['success'] ?? null) === true, "m2 CANARIO: con el idioma de la instalación ({$idiomaBase}) guarda (y se repone)", "HTTP {$r['status']}, " . mb_substr((string) ($datos['message'] ?? ''), 0, 80));
        $quedaIgual = $database->query("SELECT name, value FROM `{$tablaOpciones}`")->fetchAll(\PDO::FETCH_KEY_PAIR) == $fotoOpciones;
        $check($quedaIgual, 'm3 y la tabla de opciones queda como estaba');

        //──── [d] La longitud ──────────────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[d] La aprobación de una organización pinta su longitud en «Longitud»');
        $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-organizacion", 'zz-' . bin2hex(random_bytes(4)), "{$prefijo}-organizacion", $ahora, $rootID, OrganizationMapper::ACTIVE, json_encode(['baseLang' => $lang, 'langData' => new \stdClass, 'latitude' => 1.25, 'longitude' => 7.75])]);
        $organizacion = (int) $database->lastInsertId();
        $organizaciones[] = $organizacion;
        $database->prepare("INSERT INTO `{$tablaAprobaciones}` (referenceAlias, referenceValue, referenceTable, referenceDate, createdAt, createdBy, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute(["{$prefijo}-organizacion", (string) $organizacion, $tablaOrganizaciones, $ahora, $ahora, $rootID, SystemApprovalsMapper::STATUS_PENDING]);
        $filaOrganizacion = (int) $database->lastInsertId();
        $aprobacion = $pedir($camino(\SystemApprovals\Controllers\SystemApprovalsController::routeName('forms-approval', ['id' => $filaOrganizacion], true)), $jwtRoot, null, false, false);
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y el caso de abajo falla.
        preg_match('#Longitud</div>\s*<div class="base-text">([^<]*)</div>#', $aprobacion['body'], $longitud);
        $check(trim($longitud[1] ?? '') === '7.75', 'd1 «Longitud» pinta 7.75, la longitud, y no la latitud (1.25)', "HTTP {$aprobacion['status']}, pinta " . var_export($longitud[1] ?? null, true));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            //Las claves de IA, a como estaban: la fila se repone o, si no existía, se borra. Sin imprimirlas.
            foreach ($clavesOriginales as $nombre => $original) {
                $opcion = new SettingsModel($nombre);
                if ($original === null) {
                    if ($opcion->id !== null) {
                        $database->prepare('DELETE FROM `' . (new SettingsModel())->getModel()->getTable() . '` WHERE name = ?')->execute([$nombre]);
                    }
                } elseif ($opcion->id !== null) {
                    $opcion->value = $original;
                    $opcion->update();
                }
            }
            //Las opciones de secretos, a su valor crudo de antes (el correo, cifrado tal cual), sin imprimirlas.
            $tablaOpciones = (new SettingsModel())->getModel()->getTable();
            foreach ($opcionesCrudas as $nombre => $crudo) {
                if ($crudo === null) {
                    $database->prepare("DELETE FROM `{$tablaOpciones}` WHERE name = ?")->execute([$nombre]);
                } else {
                    $database->prepare("UPDATE `{$tablaOpciones}` SET value = ? WHERE name = ?")->execute([$crudo, $nombre]);
                }
            }
            $database->exec("DELETE FROM `{$tablaOpciones}` WHERE name LIKE 'zz_clave_prueba%'");
            foreach ($noticias as $id) {
                $database->exec('DELETE FROM `' . NewsMapper::TABLE . "` WHERE id = {$id}");
            }
            foreach ($categoriasNoticias as $id) {
                $database->exec("DELETE FROM `news_categories` WHERE id = {$id}");
            }
            foreach ($documentos as $id) {
                $database->exec("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = '" . DocumentsMapper::TABLE . "' AND referenceValue = '{$id}'");
                $database->exec('DELETE FROM `' . DocumentsMapper::TABLE . "` WHERE id = {$id}");
            }
            foreach ($tiposDocumento as $id) {
                $database->exec("DELETE FROM `forms_document_types` WHERE id = {$id}");
            }
            foreach ($publicaciones as $id) {
                $database->exec("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = '" . PublicationMapper::TABLE . "' AND referenceValue = '{$id}'");
                $database->exec('DELETE FROM `' . PublicationMapper::TABLE . "` WHERE id = {$id}");
            }
            foreach ($banners as $id) {
                $database->exec('DELETE FROM `' . BuiltInBannerMapper::TABLE . "` WHERE id = {$id}");
            }
            foreach ($suscriptores as $id) {
                $database->exec("DELETE FROM `newsletter_sucribers` WHERE id = {$id}");
            }
            //Y el alta mala si llegó a guardarse (solo pasa si falta la validación): no tiene id apuntado.
            $database->prepare('DELETE FROM `newsletter_sucribers` WHERE email = ?')->execute(['<img src=x onerror=zzNl()>@zz.test']);
            foreach ($organizaciones as $id) {
                $database->exec("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = '{$tablaOrganizaciones}' AND referenceValue = '{$id}'");
                $database->exec("DELETE FROM `{$tablaOrganizaciones}` WHERE id = {$id}");
            }
            foreach ($usuarios as $id) {
                $database->exec("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = '{$tablaUsuarios}' AND referenceValue = '{$id}'");
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"));
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query('SELECT COUNT(*) FROM `newsletter_sucribers` WHERE name LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query('SELECT COUNT(*) FROM `' . BuiltInBannerMapper::TABLE . '` WHERE title LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query("SELECT COUNT(*) FROM `{$tablaAprobaciones}` WHERE referenceAlias LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query('SELECT COUNT(*) FROM `newsletter_sucribers` WHERE email LIKE ' . $database->quote("{$prefijo}%") . ' OR email = ' . $database->quote('<img src=x onerror=zzNl()>@zz.test'))->fetchColumn()
            + (int) $database->query('SELECT COUNT(*) FROM `' . NewsMapper::TABLE . '` WHERE newsTitle LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query('SELECT COUNT(*) FROM `' . DocumentsMapper::TABLE . '` WHERE documentName LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario, organización, suscriptor, banner ni fila de aprobación de la prueba', "quedan {$quedan}");
        $reponer = true;
        foreach ($clavesOriginales as $nombre => $original) {
            $reponer = $reponer && SettingsModel::getConfigValue($nombre) === $original;
        }
        $check($reponer, 'z2 las claves de IA quedan como estaban (comparadas, no impresas)');
        $opcionesIguales = true;
        $tablaOpciones = (new SettingsModel())->getModel()->getTable();
        foreach ($opcionesCrudas as $nombre => $crudo) {
            $consulta = $database->prepare("SELECT value FROM `{$tablaOpciones}` WHERE name = ?");
            $consulta->execute([$nombre]);
            $ahoraCrudo = $consulta->fetchColumn();
            $opcionesIguales = $opcionesIguales && ($ahoraCrudo === false ? null : (string) $ahoraCrudo) === $crudo;
        }
        $check($opcionesIguales, 'z3 el correo y osTicket quedan con su valor crudo de antes (comparado, no impreso)');
    }

    return $balance();

})->setDescription('Lo que escribe un usuario se pinta escapado (URL pedida, boletín, textarea, plantillas de correo) y los secretos no viajan al HTML; el boletín comprueba el correo por marcador; la pantalla del correo y osTicket son del principal; la acción genérica solo guarda colores de marca válidos; y el SEO solo acepta los idiomas de la instalación. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
