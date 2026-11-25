<?php

/**
 * SiteRenderer.php
 */

namespace PiecesPHP\PublicSite;

use PiecesPHP\Core\Utilities\Helpers\ExtraScripts;
use PiecesPHP\Core\Utilities\Helpers\MetaTags;
use PiecesPHP\Core\Utilities\Helpers\StructuredData;
use PiecesPHP\LocalizationSystem\Controllers\LocalizationSystemController;
use PiecesPHP\Settings\Controllers\SettingsController;

/**
 * SiteRenderer - Pinta las páginas y partes de un sitio desde su vista compilada en caché. Cada vista se compila una vez
 * por huella (la del HTML, la del motor, las reglas y los registros) y se guarda en app/cache/public-site/<sitio>/.
 *
 * Formato de la caché: <tipo>-<nombre>-<huella>.php (la vista) y .json (estáticos, partes, título, descripción); al
 * cambiar la huella se escribe la nueva y se borran las anteriores de esa página o parte.
 *
 * @package     PiecesPHP\PublicSite
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class SiteRenderer
{

    /** Cómo llega el SEO a una página: por defecto (sin pcs-meta), o lo pinta pcs-meta (con o sin datos estructurados). */
    const SEO_DEFAULT = 'default';
    const SEO_MARK = 'mark';
    const SEO_MARK_WITHOUT_STRUCTURED_DATA = 'mark-without-structured-data';

    /** Lo del <head> del diseñador que el framework sustituye por lo suyo (designerHead()). */
    const DESIGNER_SEO = 'seo';
    const DESIGNER_ROBOTS = 'robots';
    const DESIGNER_THEME_COLOR = 'theme-color';
    const DESIGNER_STRUCTURED_DATA = 'structured-data';
    const DESIGNER_FAVICON = 'favicon';

    /** El cargador del framework, suelto, para las páginas sin configurations.min.js. */
    const LOADER_CSS = 'statics/core/css/pcsphp-loader.css';
    const LOADER_JS = 'statics/core/js/pcsphp-loader.js';
    const LOADER_ELEMENT_JS = 'statics/core/js/helpers-lib/GenericLoaderElement.js';

    /** Partes dentro de partes: más allá, la parte se pinta vacía y se anota. */
    const MAX_PART_DEPTH = 8;

    /** Partes pintadas en una misma página: más allá, vacías y anotadas. La importación rechaza una página que pase. */
    const MAX_PART_RENDERS = 200;

    /**
     * @var int
     */
    private static $compilations = 0;

    /**
     * @var int
     */
    private $depth = 0;

    /**
     * @var int
     */
    private $partRenders = 0;

    /**
     * Las vistas ya resueltas por este objeto (uno por petición): una parte repetida no relee disco ni rehace su huella.
     * @var array<string,array{file:string,meta:array{css:string[],js:string[],parts:string[],formActions:string[],usesImage:bool,title:string,description:string}}|null>
     */
    private $resolved = [];

    /**
     * @var array<string,string[]>
     */
    private $listings = [];

    /**
     * @var string
     */
    private $siteDirectory;

    /**
     * @var string
     */
    private $cacheDirectory;

    /**
     * @param string $site
     * @param string|null $siteDirectory La carpeta con pages/ y parts/; null, app/view/<sitio>
     * @param string|null $cacheDirectory null, app/cache/public-site
     */
    public function __construct(public readonly string $site, ?string $siteDirectory = null, ?string $cacheDirectory = null)
    {
        $this->siteDirectory = rtrim($siteDirectory ?? PublicSite::viewDirectory($site), '/');
        $this->cacheDirectory = rtrim($cacheDirectory ?? PublicSite::cacheDirectory(), '/') . '/' . $site;
    }

    /**
     * Cuántas vistas se han compilado en este proceso: las pruebas miran que sin cambios no se recompila.
     *
     * @return int
     */
    public static function compilations(): int
    {
        return self::$compilations;
    }

    /**
     * @return string[]
     */
    public function pages(): array
    {
        return $this->listings['pages'] ??= PublicSite::namesIn($this->siteDirectory . '/' . PublicSite::PAGES_DIRECTORY);
    }

    /**
     * @return string[]
     */
    public function parts(): array
    {
        return $this->listings['parts'] ??= PublicSite::namesIn($this->siteDirectory . '/' . PublicSite::PARTS_DIRECTORY);
    }

    /**
     * Lo que el controlador necesita antes de pintar: título y descripción del diseñador, y los estáticos de la página y
     * de todas sus partes, en orden. Null si la página no existe.
     *
     * @param string $page
     * @return array{title:string,description:string,css:string[],js:string[],usesForm:bool}|null
     */
    public function pageInfo(string $page): ?array
    {
        $compiled = $this->compiled(Compiler::KIND_PAGE, $page);
        if ($compiled === null) {
            return null;
        }
        $meta = $compiled['meta'];
        $css = $meta['css'];
        $js = $meta['js'];
        $formActions = $meta['formActions'];
        $pending = $meta['parts'];
        $seen = [];
        while ($pending !== [] && count($seen) < 100) {
            $part = (string) array_shift($pending);
            if (isset($seen[$part])) {
                continue;
            }
            $seen[$part] = true;
            $partCompiled = $this->compiled(Compiler::KIND_PART, $part);
            if ($partCompiled === null) {
                continue;
            }
            $css = array_merge($css, $partCompiled['meta']['css']);
            $js = array_merge($js, $partCompiled['meta']['js']);
            $formActions = array_merge($formActions, $partCompiled['meta']['formActions']);
            $pending = array_merge($pending, $partCompiled['meta']['parts']);
        }
        foreach (array_unique($formActions) as $action) {
            $js = array_merge($js, FormActionRegistry::resolve($action)['js'] ?? []);
        }
        return [
            'title' => $meta['title'],
            'description' => $meta['description'],
            'css' => array_values(array_unique($css)),
            'js' => array_values(array_unique($js)),
            'usesForm' => $formActions !== [],
        ];
    }

    /**
     * La página pintada; vacía si no existe.
     *
     * @param string $page
     * @return string
     */
    public function renderPage(string $page): string
    {
        $compiled = $this->compiled(Compiler::KIND_PAGE, $page);
        $this->partRenders = 0;
        return $compiled !== null ? $this->includeView($compiled['file'], new MarkContext($this->site, $page, $this)) : '';
    }

    /**
     * Una parte pintada con el contexto de la página que la pide; vacía si no existe o si se anida demasiado.
     *
     * @param string $name
     * @param MarkContext $context
     * @return string
     */
    public function renderPart(string $name, MarkContext $context): string
    {
        if ($this->depth >= self::MAX_PART_DEPTH || $this->partRenders >= self::MAX_PART_RENDERS) {
            MarkRegistry::report($context, "la parte «{$name}» pasa de " . self::MAX_PART_DEPTH . ' niveles o de ' . self::MAX_PART_RENDERS . ' partes por página: se pinta vacía');
            return '';
        }
        $this->partRenders++;
        $compiled = $this->compiled(Compiler::KIND_PART, $name);
        if ($compiled === null) {
            MarkRegistry::report($context, "la parte «{$name}» no existe: se pinta vacía");
            return '';
        }
        $this->depth++;
        try {
            return $this->includeView($compiled['file'], $context);
        } finally {
            $this->depth--;
        }
    }

    /**
     * La vista compilada de una página o parte, compilándola si su huella cambió. Null si su HTML no existe o si
     * compilar o guardar falla (queda en el registro de errores y el controlador sirve la página del framework).
     *
     * @param string $kind
     * @param string $name
     * @return array{file:string,meta:array{css:string[],js:string[],parts:string[],formActions:string[],usesImage:bool,title:string,description:string}}|null
     */
    public function compiled(string $kind, string $name): ?array
    {
        $key = "{$kind}\0{$name}";
        if (!array_key_exists($key, $this->resolved)) {
            try {
                $this->resolved[$key] = $this->compileOrLoad($kind, $name);
            } catch (\Throwable $e) {
                log_exception($e);
                $this->resolved[$key] = null;
            }
        }
        return $this->resolved[$key];
    }

    /**
     * @param string $kind
     * @param string $name
     * @return array{file:string,meta:array{css:string[],js:string[],parts:string[],formActions:string[],usesImage:bool,title:string,description:string}}|null
     */
    private function compileOrLoad(string $kind, string $name): ?array
    {
        $directory = $kind === Compiler::KIND_PAGE ? PublicSite::PAGES_DIRECTORY : PublicSite::PARTS_DIRECTORY;
        $source = "{$this->siteDirectory}/{$directory}/{$name}.html";
        if (!PublicSite::isValidName($name) || !is_file($source)) {
            return null;
        }
        $html = (string) file_get_contents($source);
        $pages = $this->pages();
        $parts = $this->parts();
        $fingerprint = substr(hash('sha256', implode("\0", [
            PublicSite::ENGINE_VERSION,
            MarkRules::fingerprint(),
            implode(',', MarkRegistry::names()),
            json_encode(array_map(fn (ResourceContract $c): array => $c->fields, ResourceRegistry::all())) ?: '',
            implode(',', FormActionRegistry::names()),
            implode(',', MarkRegistry::brandNames()),
            $this->site,
            implode(',', $pages),
            implode(',', $parts),
            $kind,
            $name,
            hash('sha256', $html),
        ])), 0, 24);
        $base = "{$this->cacheDirectory}/{$kind}-{$name}-{$fingerprint}";
        $metaRaw = is_file("{$base}.php") && is_file("{$base}.json") ? file_get_contents("{$base}.json") : false;
        $meta = is_string($metaRaw) ? json_decode($metaRaw, true) : null;
        if (is_array($meta)) {
            return ['file' => "{$base}.php", 'meta' => self::normalizeMeta($meta)];
        }

        self::$compilations++;
        $result = (new Compiler($this->site, $pages, $parts))->compile($html, $kind, $name);
        $context = new MarkContext($this->site, $name);
        foreach ($result['problems'] as $problem) {
            MarkRegistry::report($context, "{$directory}/{$name}.html, línea {$problem['line']}: {$problem['message']}");
        }
        $meta = self::normalizeMeta($result);
        $previousUmask = umask(0002);
        try {
            if (!is_dir($this->cacheDirectory) && !mkdir($this->cacheDirectory, 0775, true) && !is_dir($this->cacheDirectory)) {
                throw new \RuntimeException("No se pudo crear la caché del sitio público: {$this->cacheDirectory}");
            }
            self::writeAtomically("{$base}.php", $result['php']);
            self::writeAtomically("{$base}.json", json_encode($meta, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR));
        } catch (\Throwable $e) {
            log_exception($e);
            return null;
        } finally {
            umask($previousUmask);
        }
        foreach (glob("{$this->cacheDirectory}/{$kind}-{$name}-*") ?: [] as $old) {
            //Con la huella exacta: «page-a-*» también casa con las de la página «a-b».
            if (!str_starts_with($old, "{$base}.") && preg_match('/^' . preg_quote("{$kind}-{$name}-", '/') . '[0-9a-f]{24}\.(php|json)$/', basename($old)) === 1) {
                @unlink($old); //RETORNO-IGNORADO: una vista vieja que no se borra no se vuelve a leer: su huella ya no casa
            }
        }
        return ['file' => "{$base}.php", 'meta' => $meta];
    }

    /**
     * Borra todas las vistas compiladas de un sitio (la importación lo hace al cambiar el sitio).
     *
     * @return int Las que no se pudieron borrar
     */
    public function clearCache(): int
    {
        $left = 0;
        foreach (glob("{$this->cacheDirectory}/*") ?: [] as $file) {
            $left += is_file($file) && unlink($file) ? 0 : 1;
        }
        return $left;
    }

    /**
     * Lo que va al final del <head> de cada página: lo que lee el JavaScript del framework, el SEO (si la página no trae
     * pcs-meta), el favicon, la regla de las imágenes de marca, las fuentes, la hoja del cargador y los scripts extra.
     *
     * @param MarkContext $context
     * @param string $seoMode SEO_DEFAULT, SEO_MARK o SEO_MARK_WITHOUT_STRUCTURED_DATA: lo decide el compilador
     * @return string
     */
    public static function headFragment(MarkContext $context, string $seoMode = self::SEO_DEFAULT): string
    {
        $metas = [
            'cache-stamp' => (string) get_config('cacheStamp'),
            'lang-messages-from-server-url' => base64_encode((string) LocalizationSystemController::routeName('get-lang-messages-by-group', [], true)),
            'config-admin-url' => base64EncodeOrDefault(jsonEncodeFallbackNull(get_config('admin_url')), '{}'),
            'front-configurations' => base64EncodeOrDefault(jsonEncodeFallbackNull(get_front_configurations()), '[]'),
        ];
        $html = '';
        foreach ($metas as $name => $value) {
            $html .= '<meta name="' . $name . '" value="' . escape_html($value) . '">';
        }
        if ($seoMode === self::SEO_DEFAULT && !PublicSite::isGlobalExcluded('seo')) {
            $html .= MetaTags::getMetaTagsGeneric() . MetaTags::getMetaTagsOpenGraph() . StructuredData::get();
        }
        $html .= self::faviconTag();
        $dark = '.brand-image-light{display:none}html[data-theme="%1$s"] .brand-image-dark{display:revert}html[data-theme="%1$s"] .invert-on-dark{filter:invert(1)}';
        $html .= '<style>.brand-image-dark{display:none}html[data-theme="dark"] ' . sprintf($dark, 'dark') . '@media (prefers-color-scheme:dark){html[data-theme="auto"] ' . sprintf($dark, 'auto') . '}</style>';
        if (!PublicSite::isGlobalExcluded('fonts')) {
            ob_start();
            load_font();
            $html .= (string) ob_get_clean();
        }
        if (!PublicSite::isGlobalExcluded('loader')) {
            $html .= '<link rel="stylesheet" href="' . escape_html(add_cache_stamp_to_url(baseurl(self::LOADER_CSS))) . '">';
        }
        return $html . (PublicSite::isGlobalExcluded('scripts') ? '' : ExtraScripts::getScriptsFor(ExtraScripts::ZONE_PUBLIC, ExtraScripts::POSITION_HEAD));
    }

    /**
     * Lo que va al principio del <body>: los scripts extra del panel de ese punto.
     *
     * @param MarkContext $context
     * @return string
     */
    public static function bodyStartFragment(MarkContext $context): string
    {
        return PublicSite::isGlobalExcluded('scripts') ? '' : ExtraScripts::getScriptsFor(ExtraScripts::ZONE_PUBLIC, ExtraScripts::POSITION_BODY_START);
    }

    /**
     * Lo que va al final del <body>: el JavaScript del cargador (si no viene ya con configurations.min.js) y los scripts extra.
     *
     * @param MarkContext $context
     * @return string
     */
    public static function bodyEndFragment(MarkContext $context): string
    {
        $html = '';
        if (!PublicSite::isGlobalExcluded('loader') && !self::frameworkJsLoaded()) {
            //El elemento antes que su envoltorio: así no tiene que pedirlo él (su red de seguridad).
            $html .= '<script src="' . escape_html(add_cache_stamp_to_url(baseurl(self::LOADER_ELEMENT_JS))) . '"></script>';
            $html .= '<script src="' . escape_html(add_cache_stamp_to_url(baseurl(self::LOADER_JS))) . '"></script>';
        }
        return $html . (PublicSite::isGlobalExcluded('scripts') ? '' : ExtraScripts::getScriptsFor(ExtraScripts::ZONE_PUBLIC, ExtraScripts::POSITION_BODY_END));
    }

    /**
     * Los atributos del <html> que se deciden al servir, escapados y con un espacio delante: data-theme y data-loader-*.
     *
     * @param array<string,string> $designer Los mismos atributos del diseñador: se pintan los suyos si ese global se excluye
     * @return string
     */
    public static function htmlAttributes(array $designer): string
    {
        $values = [];
        $designerLoader = array_filter($designer, fn (string $name): bool => str_starts_with($name, 'data-loader-'), \ARRAY_FILTER_USE_KEY);
        if (PublicSite::isGlobalExcluded('theme')) {
            $values += array_key_exists('data-theme', $designer) ? ['data-theme' => $designer['data-theme']] : [];
        } else {
            $values['data-theme'] = SettingsController::accessibilityThemeMode();
        }
        $values += PublicSite::isGlobalExcluded('loader') ? $designerLoader : SettingsController::loaderAttributeValues();
        $attributes = '';
        foreach ($values as $name => $value) {
            if (MarkRegistry::isSafeAttributeName($name)) {
                $attributes .= ' ' . $name . '="' . escape_html($value) . '"';
            }
        }
        return $attributes;
    }

    /**
     * Un elemento del <head> del diseñador que el framework sustituye: se pinta solo si el framework no pinta el suyo.
     *
     * @param string $kind Un DESIGNER_*
     * @param string $html El elemento, ya reescrito por el compilador
     * @param string $seoMode SEO_DEFAULT, SEO_MARK o SEO_MARK_WITHOUT_STRUCTURED_DATA
     * @return string
     */
    public static function designerHead(string $kind, string $html, string $seoMode): string
    {
        $frameworkSeo = $seoMode !== self::SEO_DEFAULT || !PublicSite::isGlobalExcluded('seo');
        $replaced = match ($kind) {
            self::DESIGNER_FAVICON => self::faviconTag() !== '',
            self::DESIGNER_ROBOTS => $frameworkSeo && MetaTags::getRobots() !== null,
            self::DESIGNER_THEME_COLOR => $frameworkSeo && MetaTags::getThemeColor() !== null,
            self::DESIGNER_STRUCTURED_DATA => $frameworkSeo && $seoMode !== self::SEO_MARK_WITHOUT_STRUCTURED_DATA,
            default => $frameworkSeo,
        };
        return $replaced ? '' : $html;
    }

    /**
     * Qué es, para el framework, un elemento del <head> del diseñador (DESIGNER_*), o null si no lo sustituye.
     *
     * @param string $tag
     * @param string $name
     * @param string $property
     * @param string $rel
     * @param string $hreflang
     * @param string $type
     * @return string|null
     */
    public static function designerHeadKind(string $tag, string $name, string $property, string $rel, string $hreflang, string $type): ?string
    {
        $name = mb_strtolower(trim($name));
        $property = mb_strtolower(trim($property));
        $rels = preg_split('/\s+/', mb_strtolower(trim($rel)), -1, \PREG_SPLIT_NO_EMPTY) ?: [];
        return match (true) {
            $tag === 'title' => self::DESIGNER_SEO,
            $tag === 'meta' && $name === 'robots' => self::DESIGNER_ROBOTS,
            $tag === 'meta' && $name === 'theme-color' => self::DESIGNER_THEME_COLOR,
            $tag === 'meta' && (in_array($name, ['description', 'keywords', 'author'], true) || str_starts_with($name, 'twitter:') || str_starts_with($property, 'og:') || str_starts_with($property, 'twitter:')) => self::DESIGNER_SEO,
            $tag === 'link' && in_array('icon', $rels, true) => self::DESIGNER_FAVICON,
            $tag === 'link' && (in_array('canonical', $rels, true) || (in_array('alternate', $rels, true) && trim($hreflang) !== '')) => self::DESIGNER_SEO,
            $tag === 'script' && mb_strtolower(trim($type)) === 'application/ld+json' => self::DESIGNER_STRUCTURED_DATA,
            default => null,
        };
    }

    /**
     * El favicon de Configuración; vacío si se excluyó o no hay.
     *
     * @return string
     */
    public static function faviconTag(): string
    {
        $favicon = get_config('favicon');
        if (PublicSite::isGlobalExcluded('favicon') || !is_string($favicon) || trim($favicon) === '') {
            return '';
        }
        return '<link rel="icon" href="' . escape_html(add_cache_stamp_to_url(trim($favicon))) . '">';
    }

    /**
     * Si la página ya carga configurations.min.js, que trae el cargador: con pcs-form o useShared('framework-js').
     *
     * @return bool
     */
    private static function frameworkJsLoaded(): bool
    {
        $assets = get_config('global_assets');
        $js = is_array($assets) && is_array($assets['js'] ?? null) ? $assets['js'] : [];
        foreach ($js as $file) {
            if (is_string($file) && str_ends_with((string) strtok($file, '?#'), 'configurations.min.js')) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $file Una vista que escribió compiled()
     * @param MarkContext $context
     * @return string
     */
    private function includeView(string $file, MarkContext $context): string
    {
        $level = ob_get_level();
        ob_start();
        try {
            (static function (string $pcsViewFile, MarkContext $pcs): void {
                include $pcsViewFile;
            })($file, $context);
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            log_exception($e);
            return '';
        }
        return (string) ob_get_clean();
    }

    /**
     * @param array<string,mixed> $meta
     * @return array{css:string[],js:string[],parts:string[],formActions:string[],usesImage:bool,title:string,description:string}
     */
    private static function normalizeMeta(array $meta): array
    {
        $strings = fn (mixed $list): array => is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
        return [
            'css' => $strings($meta['css'] ?? []),
            'js' => $strings($meta['js'] ?? []),
            'parts' => $strings($meta['parts'] ?? []),
            'formActions' => $strings($meta['formActions'] ?? []),
            'usesImage' => ($meta['usesImage'] ?? false) === true,
            'title' => is_string($meta['title'] ?? null) ? $meta['title'] : '',
            'description' => is_string($meta['description'] ?? null) ? $meta['description'] : '',
        ];
    }

    /**
     * @param string $file
     * @param string $content
     * @return void
     * @throws \RuntimeException
     */
    private static function writeAtomically(string $file, string $content): void
    {
        $temporary = $file . '.tmp-' . bin2hex(random_bytes(4));
        if (file_put_contents($temporary, $content, \LOCK_EX) !== strlen($content) || !rename($temporary, $file)) {
            @unlink($temporary); //RETORNO-IGNORADO: el temporal a medias no se queda; si no existe, no hay nada que borrar
            throw new \RuntimeException("No se pudo guardar la vista compilada: {$file}");
        }
    }
}
