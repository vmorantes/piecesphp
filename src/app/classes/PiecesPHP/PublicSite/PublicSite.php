<?php

/**
 * PublicSite.php
 */

namespace PiecesPHP\PublicSite;

use App\Controller\PublicAreaController;
use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RouteGroupAdapter;

/**
 * PublicSite - El sitio público hecho con marcas (ADR 0084): qué sitio sirve el proyecto, dónde vive cada cosa y cómo se
 * llama la ruta de cada página. El sitio está en app/view/<sitio>/ (pages/, parts/, pcs.json) y sus estáticos en
 * statics/<sitio>/; lo activa `$config['public_site']`, vacío de fábrica.
 *
 * @package     PiecesPHP\PublicSite
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class PublicSite
{

    const CONFIG_KEY = 'public_site';

    /** Sube cuando cambia lo que escribe el compilador: invalida todas las vistas compiladas. */
    const ENGINE_VERSION = '5';

    const MANIFEST = 'pcs.json';

    const PAGES_DIRECTORY = 'pages';
    const PARTS_DIRECTORY = 'parts';
    const ASSETS_DIRECTORY = 'assets';

    /** Relativa a basepath(); app/cache no se sirve y git la ignora. */
    const CACHE_DIRECTORY = 'app/cache/public-site';

    /** Estas páginas toman la ruta que ya tenía el framework, con su nombre: los correos y el panel enlazan a ellas. */
    const FRAMEWORK_PAGES = [
        'index' => 'index',
        'contact' => 'contact',
    ];

    /** Su dirección chocaría con otra zona o con una ruta del framework: la comprobación las rechaza. */
    const RESERVED_PAGES = ['admin', 'core', 'statics', 'terminal', 'tokens', 'components', 'unsubscribe', 'general', 'site'];

    /**
     * @var bool
     */
    private static $misconfigurationLogged = false;

    /**
     * El sitio activo: `$config['public_site']` con forma de nombre y su app/view/<sitio>/pcs.json en disco; si no, null.
     *
     * @return string|null
     */
    public static function activeSite(): ?string
    {
        $site = get_config(self::CONFIG_KEY);
        if (!is_string($site) || trim($site) === '') {
            return null;
        }
        $site = trim($site);
        if (!self::isValidName($site) || !is_file(self::viewDirectory($site) . '/' . self::MANIFEST)) {
            //Una vez por proceso y como error único: se consulta en cada petición y en cada ruta.
            if (self::$misconfigurationLogged) {
                return null;
            }
            self::$misconfigurationLogged = true;
            self::logUnique(new \RuntimeException("\$config['" . self::CONFIG_KEY . "'] = «{$site}» no es un sitio importado (falta app/view/{$site}/" . self::MANIFEST . "): no se sirve."));
            return null;
        }
        return $site;
    }

    /**
     * Anota un error del sitio una sola vez en el registro, aunque se repita en cada petición.
     *
     * @param \Throwable $e
     * @return void
     */
    public static function logUnique(\Throwable $e): void
    {
        (new GenericHandler($e))->loggingUniqueMessage();
    }

    /**
     * Las direcciones ya registradas en los grupos de rutas (grupo + patrón, sin «[/]» ni barra final), leídas sin tocar
     * el registro: una página del sitio con la misma dirección haría que FastRoute tumbara todas las peticiones.
     *
     * @return array<string,string> Dirección => nombre de la ruta
     */
    public static function takenPaths(): array
    {
        $taken = [];
        $groups = (new \ReflectionProperty(RouteGroupAdapter::class, 'groups'))->getValue();
        $routesProperty = new \ReflectionProperty(RouteGroupAdapter::class, 'routes');
        foreach (is_array($groups) ? $groups : [] as $group) {
            if (!$group instanceof RouteGroupAdapter) {
                continue;
            }
            $routes = $routesProperty->getValue($group);
            foreach (is_array($routes) ? $routes : [] as $route) {
                $segment = $route instanceof Route ? $route->routeSegment() : null;
                $name = $route instanceof Route ? $route->name() : null;
                if (is_string($segment)) {
                    $taken[self::normalizePath(rtrim($group->getGroupSegment(), '/') . $segment)] = is_string($name) ? $name : '';
                }
            }
        }
        return $taken;
    }

    /**
     * @param string $path
     * @return string
     */
    public static function normalizePath(string $path): string
    {
        return '/' . trim((string) preg_replace('#\[/\]$#', '', $path), '/');
    }

    /**
     * Las páginas del sitio que reciben ruta propia: sin las del framework (index, contact), sin las reservadas y sin las
     * que ya tienen la dirección tomada por otra ruta (se anotan y se saltan).
     *
     * @param string[] $pages
     * @param string $groupSegment El segmento del grupo de la zona pública
     * @param array<string,string> $taken takenPaths()
     * @return array{pages:string[],skipped:array<string,string>} Página => la ruta que tenía su dirección
     */
    public static function registrablePages(array $pages, string $groupSegment, array $taken): array
    {
        $registrable = [];
        $skipped = [];
        foreach ($pages as $page) {
            if (array_key_exists($page, self::FRAMEWORK_PAGES) || in_array($page, self::RESERVED_PAGES, true)) {
                continue;
            }
            $path = self::normalizePath(rtrim($groupSegment, '/') . "/{$page}");
            if (isset($taken[$path])) {
                $skipped[$page] = $taken[$path];
                continue;
            }
            $registrable[] = $page;
        }
        return ['pages' => $registrable, 'skipped' => $skipped];
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidName(string $name): bool
    {
        return preg_match('/' . MarkRules::namePattern() . '/D', $name) === 1;
    }

    /**
     * @param string $site
     * @return string Absoluta, sin barra final
     */
    public static function viewDirectory(string $site): string
    {
        return rtrim(basepath("app/view/{$site}"), '/');
    }

    /**
     * @param string $site
     * @return string Absoluta, sin barra final
     */
    public static function staticsDirectory(string $site): string
    {
        return rtrim(basepath("statics/{$site}"), '/');
    }

    /**
     * La ruta de un estático del sitio para set_custom_assets() y baseurl().
     *
     * @param string $site
     * @param string $relative Relativa a la carpeta assets/ del diseñador
     * @return string
     */
    public static function staticPath(string $site, string $relative): string
    {
        return "statics/{$site}/" . ltrim($relative, '/');
    }

    /**
     * @return string Absoluta, sin barra final
     */
    public static function cacheDirectory(): string
    {
        return rtrim(basepath(self::CACHE_DIRECTORY), '/');
    }

    /**
     * El grupo de traducciones de las marcas pcs-text del sitio.
     *
     * @param string $site
     * @return string
     */
    public static function langGroup(string $site): string
    {
        return "public-site-{$site}";
    }

    /**
     * @param string $site
     * @return string[] Los nombres, ordenados
     */
    public static function pages(string $site): array
    {
        return self::namesIn(self::viewDirectory($site) . '/' . self::PAGES_DIRECTORY);
    }

    /**
     * @param string $site
     * @return string[] Los nombres, ordenados
     */
    public static function parts(string $site): array
    {
        return self::namesIn(self::viewDirectory($site) . '/' . self::PARTS_DIRECTORY);
    }

    /**
     * Los .html de una carpeta con forma de nombre, sin la extensión.
     *
     * @param string $directory
     * @return string[]
     */
    public static function namesIn(string $directory): array
    {
        $names = [];
        foreach (is_dir($directory) ? (scandir($directory) ?: []) : [] as $entry) {
            if (str_ends_with($entry, '.html') && is_file("{$directory}/{$entry}")) {
                $name = substr($entry, 0, -5);
                if (self::isValidName($name)) {
                    $names[] = $name;
                }
            }
        }
        sort($names);
        return $names;
    }

    /**
     * El sufijo de la ruta de una página en PublicAreaController: index y contact, los del framework; las demás, site-<página>.
     *
     * @param string $page
     * @return string
     */
    public static function routeSuffix(string $page): string
    {
        return self::FRAMEWORK_PAGES[$page] ?? "site-{$page}";
    }

    /**
     * La página de un sufijo de ruta de PublicAreaController, o null si no es de una página del sitio.
     *
     * @param string $suffix
     * @return string|null
     */
    public static function pageFromRouteSuffix(string $suffix): ?string
    {
        $page = array_search($suffix, self::FRAMEWORK_PAGES, true);
        if (is_string($page)) {
            return $page;
        }
        return str_starts_with($suffix, 'site-') ? substr($suffix, 5) : null;
    }

    /**
     * La URL de una página del sitio en el idioma actual; vacía si su ruta no existe.
     *
     * @param string $page
     * @return string
     */
    public static function pageURL(string $page): string
    {
        $url = PublicAreaController::routeName(self::routeSuffix($page), [], true);
        return is_string($url) ? $url : '';
    }

    /**
     * Los archivos de una carpeta, recursivos, relativos y ordenados; sin enlaces simbólicos ni nombres con «\\» (fuera de
     * Windows es un carácter más del nombre, y al convertirlo una ruta podría salirse de su carpeta).
     *
     * @param string $directory
     * @return string[]
     */
    public static function filesIn(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }
        $files = [];
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($items as $item) {
            if ($item instanceof \SplFileInfo && $item->isFile() && !$item->isLink()) {
                $relative = substr($item->getPathname(), strlen($directory));
                $relative = \DIRECTORY_SEPARATOR === '\\' ? str_replace('\\', '/', $relative) : $relative;
                if (str_contains($relative, '\\') || in_array('..', explode('/', $relative), true)) {
                    continue;
                }
                $files[] = ltrim($relative, '/');
            }
        }
        sort($files, \SORT_STRING);
        return $files;
    }
}
