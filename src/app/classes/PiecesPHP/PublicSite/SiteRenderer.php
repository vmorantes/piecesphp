<?php

/**
 * SiteRenderer.php
 */

namespace PiecesPHP\PublicSite;

use PiecesPHP\Core\Utilities\Helpers\ExtraScripts;
use PiecesPHP\LocalizationSystem\Controllers\LocalizationSystemController;

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
     * Lo que va al final del <head> de cada página: lo que lee el JavaScript del framework, la regla de las imágenes de
     * marca con versión oscura (solo con data-theme="dark" en <html>) y los scripts extra del panel.
     *
     * @param MarkContext $context
     * @return string
     */
    public static function headFragment(MarkContext $context): string
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
        $html .= '<style>.brand-image-dark{display:none}html[data-theme="dark"] .brand-image-light{display:none}html[data-theme="dark"] .brand-image-dark{display:revert}html[data-theme="dark"] .invert-on-dark{filter:invert(1)}</style>';
        return $html . ExtraScripts::getScriptsFor('public', 'head');
    }

    /**
     * Lo que va al final del <body>: los scripts extra del panel.
     *
     * @param MarkContext $context
     * @return string
     */
    public static function bodyEndFragment(MarkContext $context): string
    {
        return ExtraScripts::getScriptsFor('public', 'body_end');
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
