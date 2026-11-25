<?php

/**
 * SiteImporter.php
 */

namespace PiecesPHP\PublicSite;

/**
 * SiteImporter - `bin/cli public-site-import`: comprueba site/ del paquete del diseñador con las reglas de rules.json
 * (las de CHECK.html), el contrato de los recursos y lo que existe, y la coloca en app/view/<sitio>/ y statics/<sitio>/.
 * DESIGN.md, si está en la raíz del paquete, va byte a byte a app/view/<sitio>/DESIGN.md. Lo demás del paquete
 * (CHECK.html, GUIDE.html, PROJECT.md, AGENTS.md, CLAUDE.md, .agents/, .claude/, .cursor/, site/.pcs/) se ignora.
 *
 * Coloca de forma atómica: prepara todo en carpetas temporales hermanas y cambia nombres; lo anterior se guarda en
 * app/cache/public-site/backups/. Si algo falla, no queda nada a medias y el sitio anterior sigue sirviendo.
 *
 * @package     PiecesPHP\PublicSite
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class SiteImporter
{

    /** Lo que el servidor web ejecutaría o leería como configuración si estuviera en statics/. */
    const FORBIDDEN_FILES_PATTERN = '/\.(php\d?|phtml|phar|pht|phps|cgi|pl|py|sh|asp|aspx|jsp|shtml?)(\.|$)|(^|\/)(\.htaccess|\.user\.ini|web\.config)$/iD';

    /** Nombres de sitio que no se pueden usar: chocan con carpetas propias del motor. */
    const RESERVED_SITES = ['backups'];

    /**
     * @var array<int,array{level:string,file:string,line:int,message:string,fix:string}>
     */
    private $problems = [];

    /**
     * @var string
     */
    private $viewRoot;

    /**
     * @var string
     */
    private $staticsRoot;

    /**
     * @var string
     */
    private $backupRoot;

    /**
     * @param string $folder La carpeta del diseñador
     * @param string $site
     * @param string|null $viewRoot null, app/view
     * @param string|null $staticsRoot null, statics
     * @param string|null $backupRoot null, app/cache/public-site/backups
     */
    public function __construct(
        private readonly string $folder,
        private readonly string $site,
        ?string $viewRoot = null,
        ?string $staticsRoot = null,
        ?string $backupRoot = null,
    ) {
        $this->viewRoot = rtrim($viewRoot ?? basepath('app/view'), '/');
        $this->staticsRoot = rtrim($staticsRoot ?? basepath('statics'), '/');
        $this->backupRoot = rtrim($backupRoot ?? PublicSite::cacheDirectory() . '/backups', '/');
    }

    /**
     * Comprueba sin tocar nada. Solo mira site/ del paquete (y pcs.json); lo demás es del kit o del agente del diseñador.
     *
     * @return array<int,array{level:string,file:string,line:int,message:string,fix:string}> Con level «error» o «aviso»
     */
    public function check(): array
    {
        $this->problems = [];
        $folder = rtrim($this->folder, '/');
        $siteRoot = $folder . '/' . DesignerKit::SITE_DIRECTORY;
        if (!PublicSite::isValidName($this->site) || in_array($this->site, self::RESERVED_SITES, true)) {
            $this->error('', 0, "El nombre del sitio «{$this->site}» no vale.", 'Usa solo minúsculas, números y guiones, por ejemplo site=mi-sitio (y no ' . implode(', ', self::RESERVED_SITES) . ').');
            return $this->problems;
        }
        //Un site/ o un pcs.json enlazados harían leer (e importar, y servir) otra carpeta que la del paquete.
        if (is_link($siteRoot) || is_link("{$folder}/" . PublicSite::MANIFEST)) {
            $this->error(is_link($siteRoot) ? DesignerKit::SITE_DIRECTORY . '/' : PublicSite::MANIFEST, 0, 'Es un enlace simbólico.', 'Copia la carpeta del paquete con archivos y carpetas normales.');
            return $this->problems;
        }
        if (!is_dir($siteRoot)) {
            $this->error($folder, 0, 'No encuentro la carpeta site/ del paquete.', 'Indica con folder= la carpeta del paquete: la que tiene CHECK.html, pcs.json y site/.');
            return $this->problems;
        }
        $this->checkDestination();
        $this->checkManifest($folder);
        if (is_link("{$folder}/" . SiteExporter::DESIGN_FILE)) {
            $this->error(SiteExporter::DESIGN_FILE, 0, 'Es un enlace simbólico.', 'Cópialo como archivo normal.');
        }

        foreach ($this->filesWithLinks($siteRoot) as $relative => $isLink) {
            $path = DesignerKit::SITE_DIRECTORY . "/{$relative}";
            $top = explode('/', $relative)[0];
            if ($isLink) {
                $this->error($path, 0, 'Es un enlace simbólico.', 'Cópialo como archivo normal.');
            } elseif (str_contains($relative, '\\')) {
                $this->error($path, 0, 'El nombre lleva una barra invertida «\\».', 'Renómbralo sin ella.');
            } elseif (preg_match(self::FORBIDDEN_FILES_PATTERN, $relative) === 1) {
                $this->error($path, 0, 'Este tipo de archivo no se admite en un sitio: el servidor podría ejecutarlo.', 'Quítalo; el sitio solo lleva HTML, CSS, JavaScript, imágenes, fuentes y documentos.');
            } elseif (!str_contains($relative, '/')) {
                if (!str_ends_with($relative, '.html')) {
                    $this->warning($path, 0, 'En la raíz de site/ solo van las páginas .html: este archivo no se importa.', 'Muévelo a site/assets/ y enlázalo desde ahí.');
                } elseif (!PublicSite::isValidName(substr($relative, 0, -5))) {
                    $this->error($path, 0, 'El nombre de la página no vale.', 'Renómbrala solo con minúsculas, números y guiones (por ejemplo «sobre-nosotros.html») y cambia sus enlaces.');
                }
            } elseif ($top === PublicSite::PARTS_DIRECTORY && $relative !== DesignerKit::PARTS_SCRIPT && (substr_count($relative, '/') > 1 || !str_ends_with($relative, '.html') || !PublicSite::isValidName(substr(basename($relative), 0, -5)))) {
                $this->error($path, 0, 'En site/parts/ solo van archivos .html sueltos con nombre de minúsculas, números y guiones.', 'Renómbralo (por ejemplo «menu-principal.html») o muévelo a site/assets/.');
            } elseif (!in_array($top, [PublicSite::PARTS_DIRECTORY, PublicSite::ASSETS_DIRECTORY, 'data'], true)) {
                $this->warning($path, 0, 'No es parte del formato y no se importa.', 'Si es tuyo, muévelo a site/assets/.');
            }
        }

        $pages = PublicSite::namesIn($siteRoot);
        $parts = PublicSite::namesIn("{$siteRoot}/parts");
        if ($pages === []) {
            $this->error('site/', 0, 'No hay ninguna página.', 'Las páginas van sueltas en site/, con extensión .html; la portada es site/index.html.');
        } elseif (!in_array('index', $pages, true)) {
            $this->warning('site/index.html', 0, 'No hay portada (site/index.html): la portada del proyecto seguirá siendo la del framework.', 'Si el sitio tiene portada, llámala index.html.');
        }
        $this->checkPageNames($pages);
        $compiler = new Compiler($this->site, $pages, $parts);
        $includes = ['' => [], 'parts/' => []];
        foreach (['' => $pages, 'parts/' => $parts] as $directory => $names) {
            foreach ($names as $name) {
                $file = "{$directory}{$name}.html";
                $result = $compiler->compile((string) file_get_contents("{$siteRoot}/{$file}"), $directory === '' ? Compiler::KIND_PAGE : Compiler::KIND_PART, $name);
                $includes[$directory][$name] = $result['parts'];
                foreach ($result['problems'] as $problem) {
                    $this->error(DesignerKit::SITE_DIRECTORY . "/{$file}", $problem['line'], ucfirst(preg_replace('/: (se pinta vacía|se sale como texto y no se ejecuta|sale vacío|se quita)$/', '', $problem['message']) ?? '') . '.', self::fixFor($problem['message']));
                }
            }
        }
        $this->checkPartGraph($includes['parts/'], $includes['']);
        $this->checkLint($siteRoot, $pages, $parts);
        return $this->problems;
    }

    /**
     * Las URL absolutas a servicios que el proyecto no conoce y los bloques copiados en vez de partes: avisos, como en
     * CHECK.html (allí la ruta local disfrazada es error; aquí, aviso: el compilador no la mira).
     *
     * @param string $siteRoot
     * @param string[] $pages
     * @param string[] $parts
     * @return void
     */
    private function checkLint(string $siteRoot, array $pages, array $parts): void
    {
        $site = DesignerKit::SITE_DIRECTORY;
        $inputs = [];
        $pageTexts = [];
        foreach ($pages as $name) {
            $text = (string) file_get_contents("{$siteRoot}/{$name}.html");
            $inputs[] = ['file' => "{$site}/{$name}.html", 'text' => $text, 'kind' => 'html'];
            $pageTexts["{$site}/{$name}.html"] = $text;
        }
        foreach ($parts as $name) {
            $inputs[] = ['file' => "{$site}/parts/{$name}.html", 'text' => (string) file_get_contents("{$siteRoot}/parts/{$name}.html"), 'kind' => 'html'];
        }
        $packagePaths = [];
        $all = PublicSite::filesIn($siteRoot);
        sort($all, \SORT_STRING);
        foreach ($all as $relative) {
            if (preg_match(DesignerKit::HIDDEN_PATTERN, $relative) === 1) {
                continue;
            }
            $packagePaths[] = $relative;
            if (preg_match('#^assets/.+\.(css|js)$#i', $relative, $extension) === 1) {
                $inputs[] = ['file' => "{$site}/{$relative}", 'text' => (string) file_get_contents("{$siteRoot}/{$relative}"), 'kind' => strtolower($extension[1])];
            }
        }
        foreach (SiteLint::externalUrls($inputs, MarkRules::knownHosts(), $packagePaths) as $url) {
            if ($url['exact']) {
                $this->warning($url['file'], $url['line'], "Es una ruta local disfrazada: «{$url['url']}» apunta a un archivo que está en el paquete ({$url['path']}).", "Enlázalo con su ruta relativa dentro del paquete, la de site/{$url['path']}.");
            } elseif ($url['path'] !== '') {
                $this->warning($url['file'], $url['line'], "Quizá una ruta local disfrazada: «{$url['url']}» se llama como un archivo del paquete ({$url['path']}).", "Si es ese archivo, enlázalo con su ruta relativa (site/{$url['path']}); si es otro de fuera, déjalo.");
            } else {
                $this->warning($url['file'], $url['line'], "Una dirección absoluta a un servicio que el proyecto no conoce ({$url['host']}).", 'Si el archivo es del sitio, va en el paquete con su ruta relativa; si es un servicio de fuera que el sitio necesita, el aviso es solo informativo.');
            }
        }
        foreach (SiteLint::repeatedBlocks($pageTexts) as $block) {
            $pageList = implode(', ', array_map(fn (string $path): string => substr($path, strlen($site) + 1), $block['pages']));
            $this->warning($block['file'], $block['line'], 'Este bloque aparece igual en ' . count($block['pages']) . " páginas ({$pageList}): parece una parte.", 'Pásalo a site/parts/<nombre>.html y ponlo en cada página con <pcs-part name="<nombre>"></pcs-part>.');
        }
    }

    /**
     * Partes que se incluyen en círculo, o una página que acabaría pintando más de MAX_PART_INCLUSIONS partes.
     *
     * @param array<string,string[]> $partIncludes Parte => las partes que incluye
     * @param array<string,string[]> $pageIncludes Página => las partes que incluye
     * @return void
     */
    private function checkPartGraph(array $partIncludes, array $pageIncludes): void
    {
        $state = [];
        $cycles = [];
        $visit = function (string $part, array $path) use (&$visit, &$state, &$cycles, $partIncludes): void {
            if (($state[$part] ?? 0) === 2) {
                return;
            }
            if (($state[$part] ?? 0) === 1) {
                $cycles[] = implode(' → ', array_merge(array_slice($path, (int) array_search($part, $path, true)), [$part]));
                return;
            }
            $state[$part] = 1;
            foreach ($partIncludes[$part] ?? [] as $child) {
                $visit($child, array_merge($path, [$part]));
            }
            $state[$part] = 2;
        };
        foreach (array_keys($partIncludes) as $part) {
            $visit((string) $part, []);
        }
        foreach (array_unique($cycles) as $cycle) {
            $this->error('site/parts/', 0, "Las partes se incluyen en círculo: {$cycle}.", 'Quita una de esas <pcs-part> para romper el círculo.');
        }
        if ($cycles !== []) {
            return;
        }
        $memo = [];
        $count = function (string $part) use (&$count, &$memo, $partIncludes): int {
            if (!isset($memo[$part])) {
                $memo[$part] = 0;
                $total = 0;
                foreach ($partIncludes[$part] ?? [] as $child) {
                    $total += 1 + $count($child);
                }
                $memo[$part] = min($total, PHP_INT_MAX >> 1);
            }
            return $memo[$part];
        };
        foreach ($pageIncludes as $page => $parts) {
            $total = 0;
            foreach ($parts as $part) {
                $total += 1 + $count($part);
            }
            if ($total > SiteRenderer::MAX_PART_RENDERS) {
                $this->error("site/{$page}.html", 0, "La página acabaría pintando {$total} partes (más de " . SiteRenderer::MAX_PART_RENDERS . ').', 'Reduce las partes que se incluyen unas dentro de otras.');
            }
        }
    }

    /**
     * Comprueba y, si no hay errores, coloca el sitio: las páginas de site/ van a app/view/<sitio>/pages/, sus partes a
     * parts/ y site/assets/ a statics/<sitio>/. site/data/ no se guarda: la exportación la genera de los recursos.
     *
     * @return array{ok:bool,problems:array<int,array{level:string,file:string,line:int,message:string,fix:string}>,backup:string|null}
     */
    public function import(): array
    {
        $problems = $this->check();
        if (array_filter($problems, fn (array $p): bool => $p['level'] === 'error') !== []) {
            return ['ok' => false, 'problems' => $problems, 'backup' => null];
        }
        $siteRoot = rtrim($this->folder, '/') . '/' . DesignerKit::SITE_DIRECTORY;
        $suffix = date('YmdHis') . '-' . bin2hex(random_bytes(3));
        $viewTarget = "{$this->viewRoot}/{$this->site}";
        $staticsTarget = "{$this->staticsRoot}/{$this->site}";
        $viewStaging = "{$this->viewRoot}/.pcs-staging-{$this->site}-{$suffix}";
        $staticsStaging = "{$this->staticsRoot}/.pcs-staging-{$this->site}-{$suffix}";
        $backup = "{$this->backupRoot}/{$this->site}-{$suffix}";
        $done = [];
        $previousUmask = umask(0002);
        try {
            $hashes = [];
            foreach (PublicSite::namesIn($siteRoot) as $name) {
                self::copyFile("{$siteRoot}/{$name}.html", "{$viewStaging}/pages/{$name}.html");
                $hashes["{$name}.html"] = (string) hash_file('sha256', "{$siteRoot}/{$name}.html");
            }
            foreach (PublicSite::namesIn("{$siteRoot}/parts") as $name) {
                self::copyFile("{$siteRoot}/parts/{$name}.html", "{$viewStaging}/parts/{$name}.html");
                $hashes["parts/{$name}.html"] = (string) hash_file('sha256', "{$siteRoot}/parts/{$name}.html");
            }
            self::ensureDirectory("{$viewStaging}/parts");
            foreach (PublicSite::filesIn("{$siteRoot}/assets") as $relative) {
                if (preg_match(DesignerKit::HIDDEN_PATTERN, $relative) === 1) {
                    continue;
                }
                self::copyFile("{$siteRoot}/assets/{$relative}", "{$staticsStaging}/{$relative}");
                $hashes["assets/{$relative}"] = (string) hash_file('sha256', "{$siteRoot}/assets/{$relative}");
            }
            self::ensureDirectory($staticsStaging);
            //Las notas del diseño viajan con el sitio, dentro de la misma escritura atómica; PROJECT.md no.
            $designFile = rtrim($this->folder, '/') . '/' . SiteExporter::DESIGN_FILE;
            if (is_file($designFile) && !is_link($designFile)) {
                $designBytes = file_get_contents($designFile);
                if ($designBytes === false) {
                    throw new \RuntimeException("no se pudo leer {$designFile}");
                }
                self::write("{$viewStaging}/" . SiteExporter::DESIGN_FILE, $designBytes);
            }
            $generated = self::generated("{$viewStaging}/parts", "{$viewStaging}/pages");
            self::write("{$viewStaging}/" . DesignerKit::PARTS_SCRIPT, $generated[DesignerKit::PARTS_SCRIPT]);
            foreach ($generated as $relative => $content) {
                $hashes[$relative] = hash('sha256', $content);
            }
            self::write("{$viewStaging}/" . PublicSite::MANIFEST, DesignerKit::manifest($this->site, $hashes));

            self::ensureDirectory($backup);
            if (is_dir($viewTarget)) {
                self::move($viewTarget, "{$backup}/view");
                $done[] = ["{$backup}/view", $viewTarget];
            }
            if (is_dir($staticsTarget)) {
                self::move($staticsTarget, "{$backup}/statics");
                $done[] = ["{$backup}/statics", $staticsTarget];
            }
            self::move($viewStaging, $viewTarget);
            $done[] = [$viewTarget, null];
            self::move($staticsStaging, $staticsTarget);
            $done[] = [$staticsTarget, null];
        } catch (\Throwable $e) {
            //Se deshace en orden inverso: lo nuevo sale y lo anterior vuelve a su sitio.
            foreach (array_reverse($done) as [$from, $to]) {
                if ($to === null) {
                    self::removeTree($from);
                } else {
                    @rename($from, $to); //RETORNO-IGNORADO: si no vuelve, el respaldo sigue en $backup y el reporte lo dice
                }
            }
            self::removeTree($viewStaging);
            self::removeTree($staticsStaging);
            if (is_dir($backup) && (scandir($backup) ?: []) === ['.', '..']) {
                @rmdir($backup); //RETORNO-IGNORADO: un respaldo vacío que queda no estorba
            }
            $this->problems[] = ['level' => 'error', 'file' => '', 'line' => 0, 'message' => MarkRules::printable('No se pudo colocar el sitio: ' . $e->getMessage()), 'fix' => MarkRules::printable("No se cambió nada. Revisa los permisos de app/view/, statics/ y app/cache/; si quedó un respaldo, está en {$backup}.")];
            return ['ok' => false, 'problems' => $this->problems, 'backup' => null];
        } finally {
            umask($previousUmask);
        }
        (new SiteRenderer($this->site))->clearCache();
        $hasBackup = is_dir("{$backup}/view") || is_dir("{$backup}/statics");
        if (!$hasBackup) {
            @rmdir($backup); //RETORNO-IGNORADO: era la primera importación: no había nada que respaldar
        }
        //El sitio queda como el paquete: sin su DESIGN.md, el anterior sale (y se dice dónde quedó).
        if (is_file("{$backup}/view/" . SiteExporter::DESIGN_FILE) && !is_file("{$viewTarget}/" . SiteExporter::DESIGN_FILE)) {
            $problems[] = ['level' => 'aviso', 'file' => SiteExporter::DESIGN_FILE, 'line' => 0, 'message' => 'El paquete no trae DESIGN.md: se quitó el anterior, que está en el respaldo.', 'fix' => MarkRules::printable("Si lo querías, cópialo de {$backup}/view/" . SiteExporter::DESIGN_FILE . ' al paquete y vuelve a importar.')];
        }
        return ['ok' => true, 'problems' => $problems, 'backup' => $hasBackup ? $backup : null];
    }

    /**
     * parts/parts.js y los data/<recurso>.js (relativos a site/), generados igual al importar que al exportar.
     *
     * @param string $partsDirectory
     * @param string $pagesDirectory
     * @return array<string,string> Ruta relativa a site/ => contenido
     */
    public static function generated(string $partsDirectory, string $pagesDirectory): array
    {
        $parts = [];
        foreach (PublicSite::namesIn($partsDirectory) as $name) {
            $parts[$name] = (string) file_get_contents("{$partsDirectory}/{$name}.html");
        }
        return [DesignerKit::PARTS_SCRIPT => DesignerKit::partsScript($parts, PublicSite::namesIn($pagesDirectory))] + DesignerKit::dataScripts();
    }

    /**
     * El sitio no pisa una carpeta del framework: si app/view/<sitio> o statics/<sitio> existen, tienen que ser de un
     * sitio importado antes (con su pcs.json).
     *
     * @return void
     */
    private function checkDestination(): void
    {
        $view = "{$this->viewRoot}/{$this->site}";
        $imported = is_file("{$view}/" . PublicSite::MANIFEST);
        if (is_dir($view) && !$imported) {
            $this->error("app/view/{$this->site}/", 0, 'Esa carpeta ya existe y no es de un sitio importado.', 'Elige otro nombre de sitio con site=.');
        }
        if (is_dir("{$this->staticsRoot}/{$this->site}") && !$imported) {
            $this->error("statics/{$this->site}/", 0, 'Esa carpeta de estáticos ya existe y no es de un sitio importado.', 'Elige otro nombre de sitio con site=.');
        }
    }

    /**
     * @param string $folder
     * @return void
     */
    private function checkManifest(string $folder): void
    {
        $raw = is_file("{$folder}/pcs.json") ? file_get_contents("{$folder}/pcs.json") : false;
        $manifest = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($manifest)) {
            $this->error('pcs.json', 0, 'Falta el manifiesto pcs.json o está dañado.', 'Abre CHECK.html y pulsa «Actualizar el paquete»: lo vuelve a escribir.');
            return;
        }
        $version = $manifest['formatVersion'] ?? null;
        if (!is_int($version) || $version > MarkRules::formatVersion()) {
            $this->error('pcs.json', 0, 'El sitio usa una versión del formato (' . json_encode($version) . ') que este proyecto no conoce (' . MarkRules::formatVersion() . ').', 'Actualiza el framework del proyecto, o exporta de nuevo el sitio desde él.');
        }
        if (is_string($manifest['name'] ?? null) && $manifest['name'] !== '' && $manifest['name'] !== $this->site) {
            $this->warning('pcs.json', 0, "El manifiesto dice que el sitio es «{$manifest['name']}» y se importa como «{$this->site}».", 'Si no es lo que quieres, repite la orden con site=' . $manifest['name'] . '.');
        }
    }

    /**
     * Una página no puede tomar la dirección de otra ruta: ni las reservadas ni una ya registrada.
     *
     * @param string[] $pages
     * @return void
     */
    private function checkPageNames(array $pages): void
    {
        //Por la URL, no por el patrón: el patrón guardado no lleva el prefijo de su grupo («/about[/]» es admin/about/).
        $routes = function_exists('get_routes') ? get_routes() : [];
        $base = rtrim(baseurl(), '/') . '/';
        $taken = [];
        foreach (is_array($routes) ? array_keys($routes) : [] as $name) {
            if (!is_string($name) || str_starts_with($name, 'public-site-')) {
                continue;
            }
            try {
                $url = (string) get_route($name, [], true);
            } catch (\Throwable) {
                continue;
            }
            $path = str_starts_with($url, $base) ? trim(substr($url, strlen($base)), '/') : '';
            if ($path !== '' && !str_contains($path, '/')) {
                $taken[$path] = $name;
            }
        }
        foreach ($pages as $page) {
            if (array_key_exists($page, PublicSite::FRAMEWORK_PAGES)) {
                continue;
            }
            if (in_array($page, PublicSite::RESERVED_PAGES, true) || isset($taken[$page])) {
                $this->error(DesignerKit::SITE_DIRECTORY . "/{$page}.html", 0, "La página «{$page}» usaría una dirección que ya es del sistema" . (isset($taken[$page]) ? " (ruta {$taken[$page]})" : '') . '.', 'Renómbrala, por ejemplo «' . $page . '-info.html», y cambia sus enlaces.');
            }
        }
    }

    /**
     * Qué hacer ante un problema del compilador, en llano.
     *
     * @param string $message
     * @return string
     */
    private static function fixFor(string $message): string
    {
        return match (true) {
            str_contains($message, 'su propio <base>') => 'Quita tu <base>; el sitio pone el suyo.',
            str_contains($message, 'pide un recurso que no existe') => 'Ese recurso no está registrado en el proyecto: lo registra el equipo del proyecto en el routes() de uno de sus módulos (ResourceRegistry). Pídeselo o corrige el nombre.',
            str_contains($message, 'pide una acción que no existe') => 'Esa acción no está registrada en el proyecto: la registra el equipo del proyecto en el routes() de uno de sus módulos (FormActionRegistry). Pídesela o corrige el nombre.',
            str_contains($message, 'código de servidor') => 'Quítalo: el sitio no puede llevar código de servidor; lo dinámico va con marcas <pcs-…>.',
            str_contains($message, 'no es una marca conocida') => 'Revisa cómo se escribe la marca; las que existen están en GUIDE.html.',
            str_contains($message, 'le falta el atributo'), str_contains($message, 'no admite el atributo'), str_contains($message, 'vale «') => 'Corrige el atributo como indica GUIDE.html.',
            str_contains($message, 'no es un campo') => 'Usa solo los campos que trae data/ para ese recurso.',
            str_contains($message, 'página que no existe'), str_contains($message, 'que no existe') => 'Crea el archivo que falta o corrige el nombre.',
            default => 'Corrígelo y vuelve a abrir CHECK.html antes de importar.',
        };
    }

    /**
     * @param string $directory
     * @return array<string,bool> Ruta relativa => si es un enlace simbólico
     */
    private function filesWithLinks(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }
        $files = [];
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($items as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }
            $relative = substr($item->getPathname(), strlen($directory));
            $relative = ltrim(\DIRECTORY_SEPARATOR === '\\' ? str_replace('\\', '/', $relative) : $relative, '/');
            if (preg_match(DesignerKit::HIDDEN_PATTERN, $relative) === 1) {
                continue;
            }
            if ($item->isLink() || $item->isFile()) {
                $files[$relative] = $item->isLink();
            }
        }
        ksort($files, \SORT_STRING);
        return $files;
    }

    /**
     * @param string $file
     * @param int $line
     * @param string $message
     * @param string $fix
     * @return void
     */
    private function error(string $file, int $line, string $message, string $fix): void
    {
        $this->problems[] = ['level' => 'error', 'file' => MarkRules::printable($file), 'line' => $line, 'message' => MarkRules::printable($message), 'fix' => MarkRules::printable($fix)];
    }

    /**
     * @param string $file
     * @param int $line
     * @param string $message
     * @param string $fix
     * @return void
     */
    private function warning(string $file, int $line, string $message, string $fix): void
    {
        $this->problems[] = ['level' => 'aviso', 'file' => MarkRules::printable($file), 'line' => $line, 'message' => MarkRules::printable($message), 'fix' => MarkRules::printable($fix)];
    }

    /**
     * @param string $directory
     * @return void
     * @throws \RuntimeException
     */
    public static function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException("no se pudo crear {$directory}");
        }
    }

    /**
     * Copia y comprueba que llegó entero.
     *
     * @param string $from
     * @param string $to
     * @return void
     * @throws \RuntimeException
     */
    public static function copyFile(string $from, string $to): void
    {
        self::ensureDirectory(dirname($to));
        if (!copy($from, $to) || hash_file('sha256', $from) !== hash_file('sha256', $to)) {
            throw new \RuntimeException("no se pudo copiar {$from}");
        }
    }

    /**
     * @param string $file
     * @param string $content
     * @return void
     * @throws \RuntimeException
     */
    public static function write(string $file, string $content): void
    {
        self::ensureDirectory(dirname($file));
        if (file_put_contents($file, $content, \LOCK_EX) !== strlen($content)) {
            throw new \RuntimeException("no se pudo escribir {$file}");
        }
    }

    /**
     * @param string $from
     * @param string $to
     * @return void
     * @throws \RuntimeException
     */
    private static function move(string $from, string $to): void
    {
        if (!rename($from, $to)) {
            throw new \RuntimeException("no se pudo mover {$from} a {$to}");
        }
    }

    /**
     * Borra una carpeta entera; sin seguir enlaces.
     *
     * @param string $directory
     * @return int Las entradas que no se pudieron borrar
     */
    public static function removeTree(string $directory): int
    {
        if (!is_dir($directory) || is_link($directory)) {
            return 0;
        }
        $left = 0;
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            if ($item instanceof \SplFileInfo) {
                $left += ($item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname())) ? 0 : 1;
            }
        }
        return $left + (rmdir($directory) ? 0 : 1);
    }
}
