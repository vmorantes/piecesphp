<?php

/**
 * SiteFilesController.php
 */
namespace PiecesPHP\Settings\Controllers;

use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Sitemap\Sitemap;
use PiecesPHP\Core\Sitemap\SitemapItem;
use PiecesPHP\Core\Utilities\Helpers\MetaTags;
use App\Controller\PublicAreaController;
use PiecesPHP\Settings\ORM\SettingsModel;
use Publications\Controllers\PublicationsPublicController;
use Publications\PublicationsRoutes;

/**
 * SiteFilesController.
 *
 * `robots.txt`, `humans.txt` y `llms.txt`, servidos por ruta (ADR 0032 §3): la base del framework, que vive aquí y se
 * mejora aquí, más lo que añada la instalación en «Archivos para buscadores». Los buscadores solo los leen en la RAÍZ
 * del dominio: una instalación en una subcarpeta los sirve donde no cuentan.
 *
 * @package     PiecesPHP\Settings\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SiteFilesController extends BaseController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $baseRouteName = 'site-files';

    const CONFIG_ROBOTS = 'site_files_robots';
    const CONFIG_HUMANS = 'site_files_humans';
    const CONFIG_LLMS = 'site_files_llms';
    const CONFIG_UPDATED = 'site_files_updated';

    /**
     * Segundos que vale el sitemap guardado en app/cache/.
     */
    const SITEMAP_CACHE_SECONDS = 3600;

    /**
     * Los campos que admite una línea añadida a `robots.txt`.
     */
    const ROBOTS_FIELDS = ['User-agent', 'Allow', 'Disallow', 'Sitemap', 'Crawl-delay'];

    /**
     * Lo privado de la instalación. `/statics/` NO: cerrarlo le quita al buscador el CSS, el JS y las imágenes.
     */
    const ROBOTS_DISALLOW = [
        '/admin/',
        '/*/admin/',
        '/users/',
        '/*/users/',
        '/terminal/',
        '/core/',
        '/components-provider/',
        '/organizations/',
        '/configurations/',
        '/tickets/',
        '/timing/',
        '/locations/',
        '/avatars/',
        '/tokens/',
    ];

    public function __construct()
    {
        parent::__construct(false);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function robots(Request $req, Response $res)
    {
        return self::respond($res, self::withAdded(self::robotsBase(), self::CONFIG_ROBOTS), 'text/plain');
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function humans(Request $req, Response $res)
    {
        return self::respond($res, self::withAdded(self::humansBase(), self::CONFIG_HUMANS), 'text/plain');
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function llms(Request $req, Response $res)
    {
        return self::respond($res, self::withAdded(self::llmsBase(), self::CONFIG_LLMS), 'text/markdown');
    }

    /**
     * El sitemap de todos los proveedores registrados, guardado una hora en app/cache/. Si la caché no se puede leer ni
     * escribir, se calcula y se sirve igual: la caché no puede tumbar el sitemap.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function sitemap(Request $req, Response $res)
    {
        $file = self::sitemapCacheFile();
        $cached = is_file($file) && (time() - (int) filemtime($file)) < self::SITEMAP_CACHE_SECONDS ? @file_get_contents($file) : false;
        if (is_string($cached) && $cached !== '') {
            return self::respond($res, $cached, 'application/xml');
        }
        $xml = Sitemap::fromProviders()['sitemap']->getXML();
        if (@file_put_contents($file, $xml) === false) {
            log_exception(new \RuntimeException("sitemap.xml: no se pudo escribir la caché en {$file}; se sirve calculado."));
        }
        return self::respond($res, $xml, 'application/xml');
    }

    /**
     * @return string
     */
    public static function sitemapCacheFile(): string
    {
        return basepath('app/cache/sitemap.xml');
    }

    /**
     * El proveedor de sitemap del núcleo: la portada y las páginas públicas sin parámetros de PublicAreaController, en
     * cada idioma, cada una con su canónica (MetaTags::canonicalFor()).
     *
     * @return SitemapItem[]
     */
    public static function coreSitemapItems(): array
    {
        $urls = [baseurl()];
        foreach (get_routes_by_controller(PublicAreaController::class) as $routeInfo) {
            $url = get_route_sample($routeInfo['name']);
            if (is_string($url) && $url !== '' && !str_contains($url, '{')) {
                $urls[] = str_contains($url, '://') ? $url : baseurl(ltrim($url, '/'));
            }
        }
        $items = [];
        foreach (array_unique($urls) as $url) {
            foreach (Config::get_allowed_langs() as $lang) {
                $items[] = new SitemapItem(MetaTags::canonicalFor($url, $lang));
            }
        }
        return $items;
    }

    /**
     * La base de `robots.txt`: lo privado cerrado y la línea `Sitemap:` absoluta.
     *
     * @return string
     */
    public static function robotsBase(): string
    {
        $lines = ['User-agent: *'];
        foreach (self::ROBOTS_DISALLOW as $path) {
            $lines[] = "Disallow: {$path}";
        }
        $lines[] = '';
        $lines[] = 'Sitemap: ' . baseurl('sitemap.xml');
        return implode("\n", $lines);
    }

    /**
     * La base de `humans.txt`, con el formato de humanstxt.org.
     *
     * @return string
     */
    public static function humansBase(): string
    {
        $owner = SettingsModel::getConfigValue(SettingsController::SEO_OPTION_OWNER, true);
        $updated = SettingsModel::getConfigValue(self::CONFIG_UPDATED);
        $lines = [
            '/* TEAM */',
            "\tOwner: " . (is_string($owner) ? $owner : ''),
            '',
            '/* SITE */',
        ];
        if (is_string($updated) && $updated !== '') {
            $lines[] = "\tLast update: {$updated}";
        }
        $lines[] = "\tLanguage: " . implode(', ', Config::get_allowed_langs());
        return implode("\n", $lines);
    }

    /**
     * La base de `llms.txt`, con el formato de llmstxt.org, en el idioma por omisión.
     *
     * @return string
     */
    public static function llmsBase(): string
    {
        $defaultLang = Config::get_default_lang();
        $title = SettingsModel::getConfigValue(SettingsController::SEO_OPTION_TITLE_APP, true);
        $description = SettingsModel::getConfigValue(SettingsController::SEO_OPTION_DESCRIPTION, true);
        $sections = ['- [' . lang(SettingsController::LANG_GROUP, 'Inicio', $defaultLang) . '](' . baseurl() . ')'];
        if (PublicationsRoutes::ENABLE) {
            $sections[] = '- [' . lang(SettingsController::LANG_GROUP, 'Publicaciones', $defaultLang) . '](' . self::absoluteURL((string) PublicationsPublicController::routeName('list', [], true)) . ')';
        }
        return implode("\n", array_merge([
            '# ' . (is_string($title) ? $title : ''),
            '',
            '> ' . trim(preg_replace('/\s+/', ' ', is_string($description) ? $description : '') ?? ''),
            '',
            '## ' . lang(SettingsController::LANG_GROUP, 'Secciones', $defaultLang),
            '',
        ], $sections));
    }

    /**
     * Si cada línea añadida a `robots.txt` es vacía, comentario o `Campo: valor` con un campo de ROBOTS_FIELDS.
     *
     * @param string $text
     * @return bool
     */
    public static function isValidRobotsAddition(string $text): bool
    {
        $fields = implode('|', array_map(fn(string $field): string => preg_quote($field, '/'), self::ROBOTS_FIELDS));
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '' && !str_starts_with($line, '#') && preg_match("/^({$fields})\s*:\s*\S.*$/i", $line) !== 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $base
     * @param string $configName
     * @return string
     */
    private static function withAdded(string $base, string $configName): string
    {
        $added = SettingsModel::getConfigValue($configName);
        $added = is_string($added) ? trim(str_replace("\r\n", "\n", $added)) : '';
        return $base . "\n" . ($added !== '' ? "\n{$added}\n" : '');
    }

    /**
     * @param string $url
     * @return string
     */
    private static function absoluteURL(string $url): string
    {
        return str_contains($url, '://') ? $url : baseurl(ltrim($url, '/'));
    }

    /**
     * @param Response $res
     * @param string $text
     * @param string $mime
     * @return Response
     */
    private static function respond(Response $res, string $text, string $mime): Response
    {
        return $res->write($text)
            ->withHeader('Content-Type', "{$mime}; charset=utf-8")
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Sin sesión y sin lista de roles, que es «para todos» (core/permissions-coherence): son públicos por definición.
     *
     * @param RouteGroup $group Uno sin prefijo: los buscadores solo los buscan en la raíz.
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $classname = self::class;
        $group->register([
            new Route('/robots.txt', $classname . ':robots', self::$baseRouteName . '-robots', 'GET', false),
            new Route('/humans.txt', $classname . ':humans', self::$baseRouteName . '-humans', 'GET', false),
            new Route('/llms.txt', $classname . ':llms', self::$baseRouteName . '-llms', 'GET', false),
            new Route('/sitemap.xml', $classname . ':sitemap', self::$baseRouteName . '-sitemap', 'GET', false),
        ]);
        Sitemap::registerProvider('core', [self::class, 'coreSitemapItems']);
        return $group;
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * PUNTO DE VARIACIÓN DEL MÓDULO. Aquí, y en ningún otro sitio, van las reglas de negocio
     * que oculten una ruta que los roles SÍ permiten. Está vacío a propósito: es la plantilla,
     * y su presencia dice dónde se escribe la regla el día que aparezca.
     *
     * Devolver `false` ESTRECHA lo que ya concedieron los roles; nunca ensancha. `routeName()`
     * llama a este método SIEMPRE, y `allowedRoute()` no hace más que preguntarle a
     * `routeName()` si devolvió cadena.
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    protected static function _allowedRoute(string $name, string $route, array $params = [])
    {
        return true;
    }
}
