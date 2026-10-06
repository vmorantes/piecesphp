<?php

//Los metadatos de la cabecera y el JSON-LD por HTTP, leídos con un analizador de HTML (ADR 0036 y 0037).
//Crea un principal y dos publicaciones zz-prueba-seo-*, y escribe los campos para compartir; lo devuelve todo como estaba.

use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Utilities\Helpers\MetaTags;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/seo-metadata', function ($args) {

    echoTerminal("\e[33m[TEST:SeoMetadata] La cabecera dice la verdad: canónica, idiomas, tarjetas, robots y JSON-LD\e[39m");
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

    //─── Puro · La canónica según viaje el idioma ─────────────────────────────────────────────
    echoTerminal('[0] MetaTags::canonicalURL(), sin HTTP');
    $servidorPrevio = [$_SERVER['HTTP_HOST'] ?? null, $_SERVER['REQUEST_URI'] ?? null, $_SERVER['HTTPS'] ?? null];
    $cookiePrevia = get_config('lang_by_cookie');
    try {
        $_SERVER['HTTP_HOST'] = 'app.ejemplo.test';
        $_SERVER['REQUEST_URI'] = '/ruta/pagina/?page=2&i18n=en#seccion';
        $_SERVER['HTTPS'] = 'on';
        set_config('lang_by_cookie', true);
        $conCookie = MetaTags::canonicalURL();
        $check($conCookie === 'https://app.ejemplo.test/ruta/pagina/?i18n=' . \PiecesPHP\Core\Config::get_lang(), '0a con lang_by_cookie: sin consulta ni fragmento, más ?i18n= del idioma actual', $conCookie);
        set_config('lang_by_cookie', false);
        $sinCookie = MetaTags::canonicalURL();
        $check($sinCookie === 'https://app.ejemplo.test/ruta/pagina/', '0b sin lang_by_cookie (el idioma va en la ruta): sin consulta', $sinCookie);
    } finally {
        [$_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'], $_SERVER['HTTPS']] = $servidorPrevio;
        set_config('lang_by_cookie', $cookiePrevia);
    }
    echoTerminal(' ');

    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $ruta = function (string $nombre, array $parametros = []): string {
        $url = get_route($nombre, $parametros, true);
        return is_string($url) && $url !== '' ? '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/') : '';
    };
    $fallosAntesDelCanario = $failed;
    echoTerminal('[canario] Lo que esta prueba da por hecho');
    $check($base !== '', 'c1 hay una base con la que pedir');
    //Se resuelven UNA vez: una ruta vacía pediría la portada, y un 200 de la portada no prueba nada (pendientes 272.1).
    $rutas = [
        'panel' => $ruta('configurations-index'),
        'seo' => $ruta('configurations-appearance-seo'),
        'acceso' => $ruta('users-form-login'),
        'token' => $ruta('generic-token-view', ['handler' => 'commentary', 'token' => 'zz-selector']),
    ];
    foreach ($rutas as $clave => $valor) {
        $check($valor !== '' && $valor !== '/', "c2 se resuelve la ruta de {$clave}", $valor);
    }
    $check(get_config('lang_by_cookie') === true, 'c3 la instalación lleva el idioma en ?i18n= (lang_by_cookie): lo que miden los casos HTTP');
    $check(in_array('en', \PiecesPHP\Core\Config::get_allowed_langs(), true) && \PiecesPHP\Core\Config::get_default_lang() === 'es', 'c4 idiomas es (por omisión) y en');
    if ($failed > $fallosAntesDelCanario) {
        return $balance();
    }
    echoTerminal(' ');

    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $metodo, string $path, ?string $jwt = null, array $datos = []) use ($base, $cabeceraToken): array {
        $cabeceras = $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [];
        $url = str_contains($path, '://') ? $path : $base . '/' . ltrim($path, '/');
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $cabeceras,
        ]);
        if ($metodo === 'POST') {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($datos));
        }
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
    };
    //La cabecera, por el analizador de HTML y no por texto: una etiqueta rota se ve como rota.
    $cabecera = function (string $html): array {
        $documento = new \DOMDocument();
        $previo = libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previo);
        $x = new \DOMXPath($documento);
        $valor = fn(string $consulta, string $atributo): array => array_map(fn(\DOMElement $e) => $e->getAttribute($atributo), array_values(array_filter(iterator_to_array($x->query($consulta) ?: []), fn($n) => $n instanceof \DOMElement)));
        $hreflang = [];
        foreach (iterator_to_array($x->query('//head/link[@rel="alternate"][@hreflang]') ?: []) as $n) {
            if ($n instanceof \DOMElement) {
                $hreflang[$n->getAttribute('hreflang')] = $n->getAttribute('href');
            }
        }
        $meta = [];
        foreach (iterator_to_array($x->query('//head/meta[@property or @name]') ?: []) as $n) {
            if ($n instanceof \DOMElement) {
                $meta[$n->getAttribute('property') ?: $n->getAttribute('name')][] = $n->getAttribute('content');
            }
        }
        $ld = [];
        foreach (iterator_to_array($x->query('//script[@type="application/ld+json"]') ?: []) as $n) {
            if ($n instanceof \DOMElement) {
                $ld[] = $n->textContent;
            }
        }
        $titulo = $x->query('//head/title');
        $nodoTitulo = $titulo !== false ? $titulo->item(0) : null;
        return [
            'canonical' => $valor('//head/link[@rel="canonical"]', 'href'),
            'hreflang' => $hreflang,
            'meta' => $meta,
            'ld' => $ld,
            'title' => $nodoTitulo instanceof \DOMElement ? trim($nodoTitulo->textContent) : null,
        ];
    };
    $uno = fn(array $c, string $clave): ?string => $c['meta'][$clave][0] ?? null;
    $grafo = function (array $c): ?array {
        if (count($c['ld']) !== 1) {
            return null;
        }
        $datos = json_decode($c['ld'][0], true);
        return is_array($datos) && is_array($datos['@graph'] ?? null) ? $datos['@graph'] : null;
    };
    $nodo = function (?array $grafo, string $tipo): ?array {
        foreach ($grafo ?? [] as $n) {
            if (is_array($n) && ($n['@type'] ?? null) === $tipo) {
                return $n;
            }
        }
        return null;
    };

    $prefijo = 'zz-prueba-seo-' . bin2hex(random_bytes(3));
    $opciones = [
        SettingsController::SEO_OPTION_TITLE_APP, SettingsController::SEO_OPTION_OWNER, SettingsController::SEO_OPTION_DESCRIPTION,
        SettingsController::SEO_OPTION_KEYWORDS, SettingsController::SEO_OPTION_SHARE_TITLE, SettingsController::SEO_OPTION_SHARE_DESCRIPTION,
        SettingsController::SEO_OPTION_X_ACCOUNT,
    ];
    $previas = [];
    foreach ($opciones as $opcion) {
        $previas[$opcion] = [SettingsModel::optionExists($opcion), SettingsModel::getConfigValue($opcion)];
    }
    $ids = [];
    $publicaciones = [];
    $selectorToken = null;

    try {
        //─── Banco ───────────────────────────────────────────────────────────────────────────────
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Autor Seo';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $ids['root'] = (int) $u->id;
        $root = SessionToken::generateToken(['id' => $ids['root']], null, null, false);

        //La categoría sale de las categorías, no de una publicación: sin publicaciones en la base, eso daba 0 y la clave foránea fallaba.
        $molde = \Publications\Mappers\PublicationCategoryMapper::model();
        $molde->resetAll();
        $molde->select('id')->execute(false, 1);
        $categoria = (int) (((array) $molde->result())[0]->id ?? \Publications\Mappers\PublicationCategoryMapper::uncategorizedCategory()->id);
        $tituloRaro = "{$prefijo} </script> \"comillas\" y 'simples'";
        $crearPublicacion = function (string $sufijo, string $tituloES, ?string $tituloEN) use ($prefijo, $categoria, $ids): int {
            $p = new PublicationMapper();
            $p->baseLang = 'es';
            $idiomas = $tituloEN !== null ? ['es' => $tituloES, 'en' => $tituloEN] : ['es' => $tituloES];
            foreach ($idiomas as $lang => $titulo) {
                $p->setLangData($lang, 'title', $titulo);
                $p->setLangData($lang, 'content', "Contenido {$lang} de {$prefijo}-{$sufijo}.");
                $p->setLangData($lang, 'seoDescription', "Descripción {$lang} de {$prefijo}-{$sufijo}.");
                $p->setLangData($lang, 'mainImage', 'statics/images/open_graph.jpg');
                $p->setLangData($lang, 'thumbImage', 'statics/images/open_graph.jpg');
                $p->setLangData($lang, 'ogImage', '');
            }
            $p->setLangData('es', 'publicDate', new \DateTime('-1 day'));
            $p->setLangData('es', 'startDate', null);
            $p->setLangData('es', 'endDate', null);
            $p->setLangData('es', 'category', $categoria);
            $p->setLangData('es', 'visits', 0);
            $p->setLangData('es', 'author', $ids['root']);
            $p->setLangData('es', 'folder', str_replace('.', '', uniqid()));
            $p->setLangData('es', 'featured', PublicationMapper::UNFEATURED);
            $p->status = PublicationMapper::ACTIVE;
            //`save()` exige un usuario en sesión; el que firma es el principal de la prueba, y lo firmado por él queda aprobado.
            $usuarioPrevio = get_config('current_user');
            $guardadoPrevio = get_config('pcsphp_current_user_stored');
            try {
                set_config('current_user', (object) ['id' => $ids['root']]);
                set_config('pcsphp_current_user_stored', null);
                $p->save();
            } finally {
                set_config('current_user', $usuarioPrevio);
                set_config('pcsphp_current_user_stored', $guardadoPrevio);
            }
            return (int) $p->id;
        };
        $publicaciones['dos'] = $crearPublicacion('dos', $tituloRaro, "{$prefijo} english title");
        $publicaciones['es'] = $crearPublicacion('es', "{$prefijo} solo en español", null);
        //La primera petición registra los elementos nuevos en las aprobaciones.
        $pedir('GET', '/');
        $urlDe = fn(int $id, string $lang): string => $ruta('publications-single', ['slug' => (new PublicationMapper($id))->getSlug($lang)]) . "?i18n={$lang}";
        $pubDosES = $pedir('GET', $urlDe($publicaciones['dos'], 'es'));
        $pubDosEN = $pedir('GET', $urlDe($publicaciones['dos'], 'en'));
        $pubES = $pedir('GET', $urlDe($publicaciones['es'], 'es'));
        $check($ids['root'] > 0 && $pubDosES['status'] === 200 && $pubDosEN['status'] === 200 && $pubES['status'] === 200, 'banco: el principal y las dos publicaciones visibles (es+en y solo es)', "{$pubDosES['status']} / {$pubDosEN['status']} / {$pubES['status']}");
        echoTerminal(' ');

        //─── A · La portada ─────────────────────────────────────────────────────────────────────
        echoTerminal('[A] La portada, en los dos idiomas');
        $portadaES = $cabecera($pedir('GET', '/?i18n=es')['body']);
        $portadaEN = $cabecera($pedir('GET', '/?i18n=en')['body']);
        $portada = $cabecera($pedir('GET', '/')['body']);
        $raiz = $base . '/';
        $check($portadaES['canonical'] === ["{$raiz}?i18n=es"], 'a1 canónica en español: sin consulta más ?i18n=es', (string) json_encode($portadaES['canonical']));
        $check($portadaEN['canonical'] === ["{$raiz}?i18n=en"] && ($portadaEN['hreflang']['en'] ?? null) === $portadaEN['canonical'][0], 'a2 en inglés: canónica …/?i18n=en y el hreflang en es la MISMA cadena', (string) json_encode($portadaEN['hreflang']));
        $check(($portadaES['hreflang']['es'] ?? null) === ($portadaES['canonical'][0] ?? '') && isset($portadaES['hreflang']['en']), 'a3 en español: hreflang es = canónica, y anuncia en');
        $check(($portada['hreflang']['x-default'] ?? null) === $raiz && ($portada['canonical'][0] ?? '') === "{$raiz}?i18n=es", 'a4 sin consulta: x-default a …/ y la canónica en el idioma servido', (string) json_encode($portada['canonical']));
        $check($uno($portadaES, 'og:locale') === 'es_CO' && ($portadaES['meta']['og:locale:alternate'] ?? []) === ['en_US'], 'a5 og:locale es_CO y su alternativa en_US');
        $check($uno($portadaEN, 'og:locale') === 'en_US' && ($portadaEN['meta']['og:locale:alternate'] ?? []) === ['es_CO'], 'a6 en inglés, al revés');
        $check($uno($portadaES, 'og:url') === ($portadaES['canonical'][0] ?? '') && $uno($portadaES, 'og:type') === 'website', 'a7 og:url = canónica y og:type website');
        $check(!isset($portadaES['meta']['robots']), 'a8 la portada no lleva robots');
        $imagen = (string) $uno($portadaES, 'og:image');
        //La base servida, no la del terminal: `baseurl()` aquí es http://localhost.
        $archivo = str_starts_with($imagen, $raiz) ? basepath(mb_substr($imagen, mb_strlen($raiz))) : '';
        $tamano = $archivo !== '' && is_file($archivo) ? getimagesize($archivo) : false;
        $check(is_array($tamano) && $uno($portadaES, 'og:image:width') === (string) $tamano[0] && $uno($portadaES, 'og:image:height') === (string) $tamano[1], 'a9 og:image:width y height, los del archivo', (string) json_encode([$uno($portadaES, 'og:image:width'), $uno($portadaES, 'og:image:height'), $tamano]));
        $check($uno($portadaES, 'og:image:alt') !== null && $uno($portadaES, 'twitter:card') === 'summary_large_image' && $uno($portadaES, 'twitter:image') === $imagen, 'a10 og:image:alt y la tarjeta grande de Twitter con la imagen');
        $grafoPortada = $grafo($portadaES);
        $organizacion = $nodo($grafoPortada, 'Organization');
        $check($nodo($grafoPortada, 'WebSite') !== null && $organizacion !== null && str_starts_with((string) ($organizacion['logo'] ?? ''), 'http'), 'a11 JSON-LD: se decodifica, con WebSite y Organization de logotipo absoluto', (string) json_encode($grafoPortada));
        echoTerminal(' ');

        //─── B · La publicación ─────────────────────────────────────────────────────────────────
        echoTerminal('[B] La publicación');
        $dosES = $cabecera($pubDosES['body']);
        $dosEN = $cabecera($pubDosEN['body']);
        $soloES = $cabecera($pubES['body']);
        $check($uno($dosES, 'og:type') === 'article', 'b1 og:type article');
        $check(($dosES['hreflang']['es'] ?? null) === ($dosES['canonical'][0] ?? '') && isset($dosES['hreflang']['en']) && str_contains((string) ($dosES['canonical'][0] ?? ''), '?i18n=es'), 'b2 canónica = hreflang es, con ?i18n=es, y anuncia en', (string) json_encode($dosES['hreflang']));
        $check(($dosEN['hreflang']['en'] ?? null) === ($dosEN['canonical'][0] ?? '') && $uno($dosEN, 'og:locale') === 'en_US', 'b3 en inglés: canónica = hreflang en y og:locale en_US');
        $check(!isset($soloES['hreflang']['en']) && !isset($soloES['meta']['og:locale:alternate']), 'b4 la que no tiene inglés NO anuncia hreflang en ni locale alternativo', (string) json_encode($soloES['hreflang']));
        $check(isset($soloES['hreflang']['x-default']) && !str_contains($soloES['hreflang']['x-default'], 'i18n='), 'b5 y su x-default va sin ?i18n=');
        $articulo = $nodo($grafo($dosES), 'Article');
        $check($articulo !== null, 'b6 el JSON-LD se decodifica aunque el título lleve </script> y comillas', (string) json_encode($dosES['ld']));
        $check(($articulo['headline'] ?? null) === $tituloRaro, 'b7 Article.headline es el título tal cual se escribió', (string) ($articulo['headline'] ?? 'sin Article'));
        $check(isset($articulo['datePublished'], $articulo['author']['name'], $articulo['image']) && !isset($articulo['dateModified']), 'b8 Article con fecha, autor e imagen, y sin dateModified, que la vista no enseña', (string) json_encode($articulo));
        $check(($articulo['author']['name'] ?? '') === (new UsersModel($ids['root']))->getFullName(), 'b9 el autor es el que enseña la vista');
        //Lo que escribe el editor sale escapado en el cuerpo: leído por el analizador, es el texto tal cual.
        $cuerpo = new \DOMDocument();
        $previoCuerpo = libxml_use_internal_errors(true);
        $cuerpo->loadHTML('<?xml encoding="UTF-8">' . $pubDosES['body']);
        libxml_clear_errors();
        libxml_use_internal_errors($previoCuerpo);
        $xCuerpo = new \DOMXPath($cuerpo);
        $h2 = $xCuerpo->query('//h2[contains(@class, "segment-title")]');
        $imagenPrincipal = $xCuerpo->query('//div[contains(@class, "post-image")]/img');
        $nodoH2 = $h2 !== false ? $h2->item(0) : null;
        $nodoImagen = $imagenPrincipal !== false ? $imagenPrincipal->item(0) : null;
        $check($nodoH2 instanceof \DOMElement && trim($nodoH2->textContent) === $tituloRaro, 'b10 el <h2> de la publicación enseña el título tal cual, sin romperse', $nodoH2 instanceof \DOMElement ? trim($nodoH2->textContent) : 'sin h2');
        $check($nodoImagen instanceof \DOMElement && $nodoImagen->getAttribute('alt') === $tituloRaro, 'b11 y el alt de su imagen también', $nodoImagen instanceof \DOMElement ? $nodoImagen->getAttribute('alt') : 'sin img');
        echoTerminal(' ');

        //─── C · Robots ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[C] Lo privado no se indexa');
        $panel = $cabecera($pedir('GET', $rutas['panel'], $root)['body']);
        //Un token real de comentario: el controlador busca la fila por selector. Se borra al limpiar.
        $urlToken = (string) \PiecesPHP\Tokens\Controllers\GenericTokenController::createTokenURL('commentary', ['zz' => $prefijo], 5);
        $selectorToken = basename(rtrim((string) parse_url($urlToken, \PHP_URL_PATH), '/'));
        $token = $pedir('GET', str_replace('zz-selector', $selectorToken, $rutas['token']));
        $acceso = $cabecera($pedir('GET', $rutas['acceso'])['body']);
        $check(($panel['meta']['robots'] ?? []) === ['noindex, nofollow'] && $panel['canonical'] === [] && $panel['ld'] === [], 'c1 el panel: noindex, sin canónica y sin JSON-LD', (string) json_encode($panel['meta']['robots'] ?? null));
        $check($token['status'] === 200 && ($cabecera($token['body'])['meta']['robots'] ?? []) === ['noindex, nofollow'], 'c2 el de tokens: noindex', "HTTP {$token['status']}");
        $check(($acceso['meta']['robots'] ?? []) === ['noindex, nofollow'], 'c3 el acceso sin sesión: noindex');
        echoTerminal(' ');

        //─── D · Los campos para compartir ──────────────────────────────────────────────────────
        echoTerminal('[D] «Identidad y SEO»: título, descripción y cuenta de X');
        $actual = fn(string $o): mixed => SettingsModel::getConfigValue($o);
        $formulario = [
            'lang' => 'es',
            'titleApp' => (string) $actual(SettingsController::SEO_OPTION_TITLE_APP),
            'owner' => (string) $actual(SettingsController::SEO_OPTION_OWNER),
            'description' => (string) $actual(SettingsController::SEO_OPTION_DESCRIPTION),
            'keywords' => array_values(array_filter((array) $actual(SettingsController::SEO_OPTION_KEYWORDS), 'is_string')),
        ];
        $antes = json_encode(array_map(fn($o) => $actual($o), [SettingsController::SEO_OPTION_SHARE_TITLE, SettingsController::SEO_OPTION_X_ACCOUNT]));
        $mala = $pedir('POST', $rutas['seo'], $root, $formulario + ['shareTitle' => 'no debe quedar', 'xAccount' => 'sin arroba']);
        $check(($mala['json']['success'] ?? null) === false && json_encode(array_map(fn($o) => $actual($o), [SettingsController::SEO_OPTION_SHARE_TITLE, SettingsController::SEO_OPTION_X_ACCOUNT])) === $antes, 'd1 una cuenta de X inválida: rechazo y NADA escrito', mb_substr($mala['body'], 0, 160));
        $buena = $pedir('POST', $rutas['seo'], $root, $formulario + ['shareTitle' => "{$prefijo} tarjeta", 'shareDescription' => "{$prefijo} descripción para compartir", 'xAccount' => '@zz_prueba']);
        $check(($buena['json']['success'] ?? null) === true && $actual(SettingsController::SEO_OPTION_SHARE_TITLE) === "{$prefijo} tarjeta" && $actual(SettingsController::SEO_OPTION_X_ACCOUNT) === '@zz_prueba', 'd2 los tres campos se guardan por la acción real', mb_substr($buena['body'], 0, 160));
        $portadaD = $cabecera($pedir('GET', '/?i18n=es')['body']);
        $check($uno($portadaD, 'og:title') === "{$prefijo} tarjeta" && $uno($portadaD, 'twitter:title') === "{$prefijo} tarjeta", 'd3 la portada comparte con el «Título para compartir»', (string) $uno($portadaD, 'og:title'));
        $check($portadaD['title'] === $portadaES['title'], 'd4 y su <title> no cambia', (string) $portadaD['title']);
        $check($uno($portadaD, 'og:description') === "{$prefijo} descripción para compartir" && $uno($portadaD, 'twitter:site') === '@zz_prueba', 'd5 la descripción para compartir y twitter:site');
        $dosD = $cabecera($pedir('GET', $urlDe($publicaciones['dos'], 'es'))['body']);
        $check(str_contains((string) $uno($dosD, 'og:title'), $tituloRaro) && $uno($dosD, 'og:description') === "Descripción es de {$prefijo}-dos.", 'd6 la publicación conserva su título y su descripción', (string) $uno($dosD, 'og:title'));
        $check($uno($portadaES, 'og:title') !== null && str_contains((string) $uno($portadaES, 'og:title'), ' - '), 'd7 antes de fijarlo, la portada compartía «Inicio - …»', (string) $uno($portadaES, 'og:title'));

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[Z] Limpieza');
        try {
            foreach ($previas as $opcion => [$existia, $valor]) {
                if ($existia) {
                    SettingsModel::setConfigValue($opcion, $valor);
                } else {
                    SettingsModel::model()->delete(['name' => $opcion])->execute();
                }
            }
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ([PublicationMapper::TABLE => $publicaciones, UsersModel::TABLE => $ids] as $tabla => $idsDeTabla) {
                foreach ($idsDeTabla as $id) {
                    $aprobaciones->resetAll();
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tabla),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                }
            }
            if ($selectorToken !== null) {
                (new \PiecesPHP\Tokens\ORM\TokenModel())->delete(['selector' => $selectorToken])->execute();
            }
            foreach ($publicaciones as $id) {
                $pub = PublicationMapper::model();
                $pub->resetAll();
                $pub->delete(['id' => $id])->execute();
            }
            foreach ($ids as $id) {
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
            echoTerminal('      - ' . count($publicaciones) . " publicaciones y " . count($ids) . " usuario {$prefijo}-*; opciones de SEO como estaban");
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = array_filter($publicaciones, fn($id) => PublicationMapper::existsByID($id));
        $opcionesBien = true;
        foreach ($previas as $opcion => [$existia, $valor]) {
            $opcionesBien = $opcionesBien && SettingsModel::optionExists($opcion) === $existia && json_encode(SettingsModel::getConfigValue($opcion)) === json_encode($valor);
        }
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $tokensQuedan = 0;
        if ($selectorToken !== null) {
            $t = new \PiecesPHP\Tokens\ORM\TokenModel();
            $t->select()->where(['selector' => $selectorToken])->execute();
            $tokensQuedan = count((array) $t->result());
        }
        $check($quedan === [] && count((array) $usuarios->result()) === 0 && $opcionesBien && $tokensQuedan === 0, 'z1 limpieza: 0 publicaciones, 0 usuarios y 0 tokens de la prueba, y las opciones de SEO como estaban');
    }

    return $balance();

})->setDescription('Los metadatos y el JSON-LD por HTTP: canónica con ?i18n=, hreflang, og:locale, tarjetas, robots, Article y los campos para compartir.')->setEffects([CliActions::EFFECT_NETWORK, CliActions::EFFECT_DATABASE])->register();
