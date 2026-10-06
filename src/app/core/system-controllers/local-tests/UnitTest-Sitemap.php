<?php

//El sitemap que sirve la aplicación (ADR 0032 §3): proveedores por módulo, TODAS las publicaciones visibles y solo esas, y
//cada URL igual a la canónica de su página. Crea usuarios y publicaciones zz-prueba-smap-* y lo retira todo al final.

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Sitemap\Sitemap;
use PiecesPHP\Core\Sitemap\SitemapItem;
use PiecesPHP\Settings\Controllers\SiteFilesController;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/sitemap', function ($args) {

    echoTerminal("\e[33m[TEST:Sitemap] El sitemap lleva todas las publicaciones visibles, solo esas, y cada URL es la canónica de su página\e[39m");
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

    //─── 0 · Sin HTTP: el registro de proveedores y el extracto ────────────────────────────────
    echoTerminal('[0] Los proveedores y el extracto, en el proceso');
    $nombres = Sitemap::providerNames();
    $check(in_array('core', $nombres, true) && in_array('publications', $nombres, true), '0a el núcleo y Publications registraron su proveedor', (string) json_encode($nombres));
    $mensaje = '';
    try {
        Sitemap::registerProvider('publications', fn() => []);
    } catch (\InvalidArgumentException $e) {
        $mensaje = $e->getMessage();
    }
    $check(str_contains($mensaje, 'publications'), '0b registrar dos veces el mismo nombre falla, y dice cuál', $mensaje);
    $marca = 'https://zz-prueba-smap.test/';
    Sitemap::registerProvider('zz-prueba-lanza', function (): array {
        throw new \RuntimeException('zz-prueba: el proveedor de prueba lanza a propósito');
    });
    Sitemap::registerProvider('zz-prueba-bien', fn(): array => [new SitemapItem($marca . '?a=1&b=2')]);
    try {
        $resultado = Sitemap::fromProviders();
        $xml0 = $resultado['sitemap']->getXML();
        $check($resultado['failed'] === ['zz-prueba-lanza'], '0c un proveedor que lanza se registra como fallido', (string) json_encode($resultado['failed']));
        $check(str_contains($xml0, $marca) && count($resultado['sitemap']->getLocations()) > 1, '0d y el sitemap sale con los demás');
        $documento0 = new \DOMDocument();
        $check(@$documento0->loadXML($xml0) === true && str_contains($xml0, '?a=1&amp;b=2'), '0e el XML es válido aunque una URL lleve & (se escapa en <loc>)');
    } finally {
        Sitemap::unregisterProvider('zz-prueba-lanza');
        Sitemap::unregisterProvider('zz-prueba-bien');
    }
    $recorte = new PublicationMapper();
    $recorte->baseLang = \PiecesPHP\Core\Config::get_lang();
    $recorte->setLangData($recorte->baseLang, 'title', str_repeat('ñ', 10));
    $recorte->setLangData($recorte->baseLang, 'content', '<p>' . str_repeat('á', 10) . '</p>');
    $check($recorte->excerptTitle(6) === 'ñññ...' && mb_check_encoding($recorte->excerptTitle(6), 'UTF-8'), '0f excerptTitle() corta por caracteres: una ñ no se parte', bin2hex($recorte->excerptTitle(6)));
    $check($recorte->excerpt(6) === 'ááá...', '0g y excerpt() también', bin2hex($recorte->excerpt(6)));
    echoTerminal(' ');

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que access-without-session.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    if ($base === '') {
        $matrixPath = dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/files/dev/permissions-matrix.json';
        $matrix = is_file($matrixPath) ? json_decode((string) file_get_contents($matrixPath), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $rutaSitemap = get_route('site-files-sitemap', [], true);
    $rutaSitemap = is_string($rutaSitemap) && str_contains($rutaSitemap, '://') ? (string) parse_url($rutaSitemap, \PHP_URL_PATH) : (string) $rutaSitemap;

    $fallosAntesDelCanario = $failed;
    echoTerminal('[canario] Lo que esta prueba da por hecho');
    $check($base !== '', 'c1 hay una base con la que pedir');
    $check($rutaSitemap !== '' && $rutaSitemap !== '/', 'c2 existe la ruta site-files-sitemap', $rutaSitemap);
    if ($failed > $fallosAntesDelCanario) {
        return $balance();
    }
    echoTerminal(' ');

    $pedir = function (string $metodo, string $url) use ($base): array {
        $recibidas = [];
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => str_contains($url, '://') ? $url : $base . '/' . ltrim($url, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CUSTOMREQUEST => $metodo === 'POST' ? 'POST' : 'GET',
            CURLOPT_HEADERFUNCTION => function (\CurlHandle $h, string $linea) use (&$recibidas): int {
                $partes = explode(':', $linea, 2);
                if (count($partes) === 2) {
                    $recibidas[mb_strtolower(trim($partes[0]))] = trim($partes[1]);
                }
                return strlen($linea);
            },
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => $status, 'body' => is_string($body) ? $body : '', 'h' => $recibidas];
    };
    $canonicaDe = function (string $url) use ($pedir): ?string {
        $documento = new \DOMDocument();
        $previo = libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="UTF-8">' . $pedir('GET', $url)['body']);
        libxml_clear_errors();
        libxml_use_internal_errors($previo);
        $enlaces = (new \DOMXPath($documento))->query('//head/link[@rel="canonical"]');
        $enlace = $enlaces !== false ? $enlaces->item(0) : null;
        return $enlace instanceof \DOMElement ? $enlace->getAttribute('href') : null;
    };

    //SOLO LETRAS: el slug de la URL descarta los dígitos, y dos títulos que solo difieran en un número darían la misma.
    $prefijo = 'zz-prueba-smap-' . strtr(bin2hex(random_bytes(3)), '0123456789', 'ghijklmnop');
    $usuario = function (string $sufijo, int $tipo) use ($prefijo): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Prueba';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        return (int) $u->id;
    };
    $idsPublicaciones = [];
    //`save()` firma con el usuario en sesión, y de quién firma depende que nazca aprobada: se le presta el autor.
    $publicacion = function (string $sufijo, int $autor, int $estado, ?\DateTime $desde = null, ?\DateTime $hasta = null, bool $conIngles = false) use ($prefijo, &$idsPublicaciones): PublicationMapper {
        //La categoría sale de las categorías, no de una publicación: sin publicaciones en la base, eso daba 0 y la clave foránea fallaba.
        $molde = \Publications\Mappers\PublicationCategoryMapper::model();
        $molde->resetAll();
        $molde->select('id')->execute(false, 1);
        $categoria = (int) (((array) $molde->result())[0]->id ?? \Publications\Mappers\PublicationCategoryMapper::uncategorizedCategory()->id);
        $lang = get_config('default_lang');
        $p = new PublicationMapper();
        $p->baseLang = $lang;
        foreach ($conIngles ? [$lang, 'en'] : [$lang] as $idioma) {
            $p->setLangData($idioma, 'title', "{$prefijo} {$sufijo} {$idioma}");
            $p->setLangData($idioma, 'content', 'Publicación de la prueba del sitemap.');
            $p->setLangData($idioma, 'seoDescription', '');
        }
        $p->setLangData($lang, 'publicDate', new \DateTime());
        $p->setLangData($lang, 'startDate', $desde);
        $p->setLangData($lang, 'endDate', $hasta);
        $p->setLangData($lang, 'category', $categoria);
        $p->setLangData($lang, 'visits', 0);
        $p->setLangData($lang, 'author', $autor);
        $p->setLangData($lang, 'folder', str_replace('.', '', uniqid()));
        $p->setLangData($lang, 'featured', PublicationMapper::UNFEATURED);
        $p->setLangData($lang, 'mainImage', 'statics/images/zz-prueba.jpg');
        $p->setLangData($lang, 'thumbImage', 'statics/images/zz-prueba.jpg');
        $p->setLangData($lang, 'ogImage', '');
        $p->status = $estado;
        $usuarioPrevio = get_config('current_user');
        $guardadoPrevio = get_config('pcsphp_current_user_stored');
        try {
            set_config('current_user', (object) ['id' => $autor]);
            set_config('pcsphp_current_user_stored', null);
            $p->save();
        } finally {
            set_config('current_user', $usuarioPrevio);
            set_config('pcsphp_current_user_stored', $guardadoPrevio);
        }
        $idsPublicaciones[] = (int) $p->id;
        return $p;
    };
    //La caché de una hora la escribe el servidor web: se retira para que la prueba vea lo que acaba de crear.
    $cache = SiteFilesController::sitemapCacheFile();
    $sinCache = fn(): bool => !is_file($cache) || unlink($cache);

    try {
        $root = $usuario('root', UsersModel::TYPE_USER_ROOT);
        $general = $usuario('general', UsersModel::TYPE_USER_GENERAL);

        //MÁS DE DIEZ a propósito: el listado público pagina a 10, y de ahí las leía antes el sitemap.
        $letras = range('a', 'l');
        foreach ($letras as $letra) {
            $publicacion("visible-{$letra}", $root, PublicationMapper::ACTIVE);
        }
        $traducida = $publicacion('traducida', $root, PublicationMapper::ACTIVE, null, null, true);
        $publicacion('borrador', $root, PublicationMapper::DRAFT);
        $publicacion('programada', $root, PublicationMapper::ACTIVE, new \DateTime('+2 days'), new \DateTime('+9 days'));
        //La firma un general: no se aprueba sola, y sin aprobar el público no la ve.
        $publicacion('sin-aprobar', $general, PublicationMapper::ACTIVE);
        //La fila rota: sin sus meta-propiedades, `objectToMapper()` no puede dar mapper.
        $rota = $publicacion('rota', $root, PublicationMapper::ACTIVE);
        $modelo = PublicationMapper::model();
        $modelo->resetAll();
        $modelo->update(['meta' => '{}'])->where(['id' => (int) $rota->id])->execute();
        $check(count($idsPublicaciones) === 17, 'banco: 17 publicaciones de la prueba', (string) count($idsPublicaciones));
        //La primera petición registra los elementos nuevos en las aprobaciones.
        $pedir('GET', '/');
        echoTerminal(' ');

        echoTerminal('[1] /sitemap.xml, sin sesión');
        $check(!is_file(basepath('sitemap.xml')), '1a no hay src/sitemap.xml en disco: si lo hubiera, Apache lo serviría antes que la ruta');
        $check($sinCache(), '1b banco: sin caché del sitemap antes de pedirlo');
        $r = $pedir('GET', $rutaSitemap);
        $check($r['status'] === 200 && ($r['h']['content-type'] ?? '') === 'application/xml; charset=utf-8' && ($r['h']['cache-control'] ?? '') === 'public, max-age=3600', '1c 200, application/xml; charset=utf-8 y caché de una hora', "HTTP {$r['status']} · " . ($r['h']['content-type'] ?? '') . ' · ' . ($r['h']['cache-control'] ?? ''));
        $documento = new \DOMDocument();
        $leido = @$documento->loadXML($r['body']) === true;
        $locs = [];
        if ($leido) {
            foreach ($documento->getElementsByTagName('loc') as $loc) {
                $locs[] = trim($loc->textContent);
            }
        }
        $check($leido && count($locs) > 0, '1d el XML se lee con un analizador y trae URL', (string) count($locs));
        $check(is_file($cache), '1e y queda guardado en app/cache/');
        echoTerminal('      MEDIDA: ' . count($locs) . ' URL en el sitemap');
        echoTerminal(' ');

        echoTerminal('[2] Entran todas las visibles, aunque pasen de diez; nada que el visitante no vea');
        $conSufijo = fn(string $sufijo, string $idioma): array => array_values(array_filter($locs, fn(string $l) => str_contains($l, "/{$prefijo}-{$sufijo}-{$idioma}-")));
        $faltan = array_values(array_filter($letras, fn(string $letra) => count($conSufijo("visible-{$letra}", 'es')) !== 1));
        $check(count($faltan) === 0, '2a las doce visibles de la prueba, una vez cada una', 'fuera o repetidas: ' . implode(', ', $faltan));
        foreach (['el borrador' => 'borrador', 'la programada a futuro' => 'programada', 'la que está sin aprobar' => 'sin-aprobar', 'la rota' => 'rota'] as $nombre => $sufijo) {
            $check(count($conSufijo($sufijo, 'es')) === 0, "2b {$nombre} no está");
        }
        $check(count($conSufijo('traducida', 'es')) === 1 && count($conSufijo('traducida', 'en')) === 1, '2c la traducida, una vez en cada idioma');
        $soloES = $conSufijo('visible-a', 'es');
        $check(count(array_filter($locs, fn(string $l) => str_contains($l, "/{$prefijo}-visible-a-") && str_contains($l, 'i18n=en'))) === 0, '2d una publicación solo en español no tiene entrada en inglés');
        echoTerminal(' ');

        echoTerminal('[3] Cada URL del sitemap es la canónica que sirve su página');
        foreach (['es', 'en'] as $idioma) {
            $canonica = $canonicaDe("/?i18n={$idioma}");
            $check($canonica !== null && in_array($canonica, $locs, true), "3a la portada en {$idioma}: su canónica está, tal cual, en el sitemap", (string) $canonica);
        }
        $locPublicacion = $soloES[0] ?? '';
        $check($locPublicacion !== '' && $canonicaDe($locPublicacion) === $locPublicacion, '3b una publicación: la URL del sitemap ES su canónica', $locPublicacion . ' → ' . (string) $canonicaDe($locPublicacion));
        $locTraducidaEN = $conSufijo('traducida', 'en')[0] ?? '';
        $check($locTraducidaEN !== '' && $canonicaDe($locTraducidaEN) === $locTraducidaEN, '3c y la traducida en inglés, también', $locTraducidaEN);
        $check(count(array_filter($locs, fn(string $l) => get_config('lang_by_cookie') === true && !str_contains($l, 'i18n='))) === 0, '3d con el idioma en la consulta, todas llevan ?i18n=');
        echoTerminal(' ');

        echoTerminal('[4] Lo de antes ya no existe');
        $check(!is_array(get_route_info('configurations-appearance-seo-sitemap')), '4a la ruta del botón no está registrada');
        $check($pedir('POST', '/configurations/appearance/seo/sitemap/')['status'] === 404, '4b y su URL da 404');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[Z] Limpieza');
        try {
            $aprobaciones = SystemApprovalsMapper::model();
            $publicaciones = PublicationMapper::model();
            foreach ($idsPublicaciones as $id) {
                $aprobaciones->resetAll();
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, PublicationMapper::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $publicaciones->resetAll();
                $publicaciones->delete(['id' => $id])->execute();
            }
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
            $perfiles = UserProfileMapper::model();
            foreach ((array) $usuarios->result() as $u) {
                $aprobaciones->resetAll();
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $u->id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles->resetAll();
                $perfiles->delete(['belongsTo' => (int) $u->id])->execute();
            }
            $usuarios->resetAll();
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
            //La caché guardada lleva las URL de la prueba: se retira con ellas.
            $cacheRetirada = $sinCache();
            echoTerminal('      - ' . count($idsPublicaciones) . " publicaciones, sus filas de aprobación, 2 usuarios {$prefijo}-* y la caché del sitemap");
        } catch (\Throwable $e) {
            $cacheRetirada = false;
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $restos = UsersModel::model();
        $restos->resetAll();
        $restos->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $quedanPublicaciones = count(array_filter($idsPublicaciones, fn(int $id) => PublicationMapper::existsByID($id)));
        $check(count((array) $restos->result()) === 0 && $quedanPublicaciones === 0 && $cacheRetirada, 'z1 limpieza: 0 usuarios, 0 publicaciones y sin caché con las URL de la prueba');
    }

    return $balance();

})->setDescription('El sitemap por ruta: proveedores por módulo, todas las publicaciones visibles y solo esas, cada URL la canónica de su página.')->setEffects([CliActions::EFFECT_NETWORK, CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
