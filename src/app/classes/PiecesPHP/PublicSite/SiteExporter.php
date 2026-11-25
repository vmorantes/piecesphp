<?php

/**
 * SiteExporter.php
 */

namespace PiecesPHP\PublicSite;

/**
 * SiteExporter - `bin/cli public-site-export`: arma desde el proyecto el paquete del diseñador. site/ es el sitio tal
 * cual (las páginas en su raíz, index.html la portada; parts/, assets/, data/ y el lector oculto .pcs/); en la raíz,
 * CHECK.html, GUIDE.html, pcs.json, DESIGN.md si el sitio lo trae, PROJECT.md (las reglas del proyecto) y el andamiaje
 * de su agente (AGENTS.md, CLAUDE.md, .agents/, .claude/, .cursor/).
 *
 * El origen (ADR 0086/0087): una carpeta con site/ y pcs.json (from=), un sitio importado, o, si el sitio no está
 * importado, el ejemplo del kit. El destino: fuera del proyecto, el paquete entero; dentro de files/design/, solo el
 * sitio (site/, pcs.json y DESIGN.md), que es lo que el proyecto versiona.
 *
 * Qué sale del kit y adónde: PACKAGE_FILES, más kit/package-map.json si existe ({"files": {destino: origen | null}}).
 * El andamiaje solo puede caer en ALLOWED_AGENT_PATTERNS, y el paquete pasa por la comprobación de rastro de
 * entregar-al-cliente con esos archivos como única excepción: si queda otro rastro, falla y no deja nada.
 *
 * @package     PiecesPHP\PublicSite
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class SiteExporter
{

    /** Las reglas de Cursor y el alwaysApply que debe llevar cada una. */
    const CURSOR_RULES = ['designer' => true, 'adapt-design' => false];

    /** Destino en el paquete => origen en el kit. Todos obligatorios. */
    const PACKAGE_FILES = [
        'CHECK.html' => 'CHECK.html',
        'GUIDE.html' => 'GUIDE.html',
        'AGENTS.md' => 'AGENTS.md',
        'CLAUDE.md' => 'CLAUDE.md',
        '.agents/skills/designer/SKILL.md' => 'skills/designer/SKILL.md',
        '.agents/skills/adapt-design/SKILL.md' => 'skills/adapt-design/SKILL.md',
        '.claude/skills/designer/SKILL.md' => 'skills/designer/SKILL.md',
        '.claude/skills/adapt-design/SKILL.md' => 'skills/adapt-design/SKILL.md',
        '.claude/agents/design-explorer.md' => 'subagents/design-explorer.md',
        '.claude/agents/design-adapter.md' => 'subagents/design-adapter.md',
        '.claude/agents/design-reviewer.md' => 'subagents/design-reviewer.md',
        '.cursor/rules/designer.mdc' => 'cursor-rules/designer.mdc',
        '.cursor/rules/adapt-design.mdc' => 'cursor-rules/adapt-design.mdc',
    ];

    /** El mapa que puede dejar el kit para añadir o quitar archivos (hoy, los ajustes de su agente). */
    const PACKAGE_MAP = 'package-map.json';

    /** Dónde puede caer el andamiaje del agente del diseñador (fnmatch por segmentos). Lo demás fuera de site/, no. */
    const ALLOWED_AGENT_PATTERNS = [
        'AGENTS.md',
        'CLAUDE.md',
        '.agents/skills/designer/*',
        '.agents/skills/adapt-design/*',
        '.claude/skills/designer/*',
        '.claude/skills/adapt-design/*',
        '.claude/agents/design-explorer.md',
        '.claude/agents/design-adapter.md',
        '.claude/agents/design-reviewer.md',
        '.claude/settings.json',
        '.cursor/rules/designer.mdc',
        '.cursor/rules/adapt-design.mdc',
    ];

    /** Lo del kit fuera del andamiaje y de site/. */
    const KIT_PAGES = ['CHECK.html', 'GUIDE.html'];

    /** Dónde busca el lector en el kit, en orden. */
    const READER_SOURCES = ['site/.pcs', '.pcs', 'pcs'];

    /** Las notas del diseño: viajan con el sitio en los dos sentidos, byte a byte, y el diseñador puede editarlas. */
    const DESIGN_FILE = 'DESIGN.md';

    /** Las reglas del proyecto para el diseñador: solo van al paquete, protegidas; la importación las ignora. */
    const PROJECT_FILE = 'PROJECT.md';

    /** La línea que PROJECT.md tiene que llevar tal cual (ADR 0087, precisión 7). */
    const PENDING_HEADING = '## Pendientes conocidos';

    /** Donde el proyecto guarda el diseño, relativa a la raíz: el único destino permitido dentro del proyecto. */
    const DESIGN_DIRECTORY = 'files/design';

    /** El sitio de ejemplo del kit, origen de un sitio que aún no existe. */
    const EXAMPLE_DIRECTORY = 'example';

    /** La comprobación de rastro, relativa a la raíz: la del clon (bin/clonar-proyecto la instala ahí) o su plantilla. */
    const TRACE_CHECKS = ['bin/entregar-al-cliente', 'bin/tools/clonar-proyecto/entregar-al-cliente'];

    /**
     * @var string
     */
    private $kitDirectory;

    /**
     * @var string
     */
    private $siteDirectory;

    /**
     * @var string
     */
    private $staticsDirectory;

    /**
     * @param string $site
     * @param string $destination La carpeta que se crea; no puede existir
     * @param bool $zip Además, <sitio>-<AAAA-MM-DD>.zip junto al destino
     * @param string|null $kitDirectory null, el kit del framework
     * @param string|null $siteDirectory null, app/view/<sitio>
     * @param string|null $staticsDirectory null, statics/<sitio>
     * @param string|null $from Una carpeta con site/ y pcs.json como origen (relativa a la raíz o absoluta)
     * @param string|null $projectFile null, <raíz>/files/design/PROJECT.md (relativa a la raíz o absoluta)
     * @param string|null $repositoryRoot null, la raíz del repositorio
     */
    public function __construct(
        private readonly string $site,
        private readonly string $destination,
        private readonly bool $zip = false,
        ?string $kitDirectory = null,
        ?string $siteDirectory = null,
        ?string $staticsDirectory = null,
        private readonly ?string $from = null,
        private readonly ?string $projectFile = null,
        private readonly ?string $repositoryRoot = null,
    ) {
        $this->kitDirectory = rtrim($kitDirectory ?? DesignerKit::KIT_DIRECTORY, '/');
        $this->siteDirectory = rtrim($siteDirectory ?? PublicSite::viewDirectory($site), '/');
        $this->staticsDirectory = rtrim($staticsDirectory ?? PublicSite::staticsDirectory($site), '/');
    }

    /**
     * La ruta del .zip que se crea con zip=yes.
     *
     * @return string
     */
    public function zipPath(): string
    {
        return dirname(rtrim($this->destination, '/')) . "/{$this->site}-" . date('Y-m-d') . '.zip';
    }

    /**
     * Qué se copia del kit: PACKAGE_FILES con lo que añada o quite kit/package-map.json.
     *
     * @return array<string,string> Destino en el paquete => origen en el kit
     * @throws \RuntimeException Si el mapa del kit no tiene la forma esperada
     */
    public function packageFiles(): array
    {
        $files = self::PACKAGE_FILES;
        $mapFile = "{$this->kitDirectory}/" . self::PACKAGE_MAP;
        if (is_file($mapFile)) {
            $map = json_decode((string) file_get_contents($mapFile), true);
            if (!is_array($map) || !is_array($map['files'] ?? null)) {
                throw new \RuntimeException(self::PACKAGE_MAP . ' del kit no tiene la forma {"files": {destino: origen}}');
            }
            foreach ($map['files'] as $destination => $source) {
                if ($source === null) {
                    unset($files[$destination]);
                } elseif (is_string($destination) && is_string($source)) {
                    $files[$destination] = $source;
                }
            }
        }
        ksort($files, \SORT_STRING);
        return $files;
    }

    /**
     * Lo que no vale en el mapa: rutas vacías, absolutas, con «\\» o «..», destinos en site/ o pcs.json, y orígenes
     * que no existen o que, resueltos, quedan fuera del kit.
     *
     * @param array<string,string> $packageFiles
     * @return string[]
     */
    public function packageFileProblems(array $packageFiles): array
    {
        $isRelative = fn (string $path): bool => $path !== '' && !str_starts_with($path, '/') && !str_contains($path, '\\') && !str_contains($path, "\0") && preg_match('/^[a-zA-Z]:/', $path) !== 1 && !in_array('..', explode('/', $path), true) && !in_array('.', explode('/', $path), true);
        $kit = realpath($this->kitDirectory);
        $problems = [];
        foreach ($packageFiles as $to => $from) {
            if (!$isRelative($to) || str_starts_with($to, DesignerKit::SITE_DIRECTORY . '/') || $to === PublicSite::MANIFEST) {
                $problems[] = "destino «{$to}»";
            }
            $real = $isRelative($from) ? realpath("{$this->kitDirectory}/{$from}") : false;
            if ($kit === false || $real === false || !str_starts_with($real, $kit . '/')) {
                $problems[] = "origen «{$from}»";
            }
        }
        return $problems;
    }

    /**
     * Si un destino del paquete es del andamiaje del agente del diseñador (no es site/ ni una página del kit).
     *
     * @param string $path
     * @return bool
     */
    public static function isAgentFile(string $path): bool
    {
        return !str_starts_with($path, DesignerKit::SITE_DIRECTORY . '/') && !in_array($path, self::KIT_PAGES, true) && $path !== PublicSite::MANIFEST;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function isAllowedAgentFile(string $path): bool
    {
        foreach (self::ALLOWED_AGENT_PATTERNS as $pattern) {
            if (fnmatch($pattern, $path, \FNM_PATHNAME | \FNM_PERIOD)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array{ok:bool,problems:array<int,array{level:string,file:string,line:int,message:string,fix:string}>,files:int,zip:string|null,trace:string,origin:string,destination?:string}
     */
    public function export(): array
    {
        $problems = [];
        $fail = function (string $file, string $message, string $fix, string $trace = '') use (&$problems): array {
            //Sin órdenes de terminal: el nombre o un archivo pueden venir del diseñador (su pcs.json).
            $problems[] = ['level' => 'error', 'file' => MarkRules::printable($file), 'line' => 0, 'message' => MarkRules::printable($message), 'fix' => MarkRules::printable($fix)];
            return ['ok' => false, 'problems' => $problems, 'files' => 0, 'zip' => null, 'trace' => MarkRules::printable($trace), 'origin' => ''];
        };
        $repository = rtrim($this->repositoryRoot ?? dirname(rtrim(basepath(), '/')), '/');
        $resolve = fn (string $path): string => str_starts_with($path, '/') ? $path : "{$repository}/{$path}";
        if (!PublicSite::isValidName($this->site)) {
            return $fail('', "El nombre del sitio «{$this->site}» no vale.", 'Usa solo minúsculas, números y guiones, por ejemplo site=mi-sitio.');
        }

        //El origen: una carpeta (from=), el sitio importado, o el ejemplo del kit si el sitio no existe todavía.
        $imported = is_file("{$this->siteDirectory}/" . PublicSite::MANIFEST);
        if ($this->from !== null && $imported) {
            return $fail('', "«{$this->site}» ya es un sitio importado y además se pide exportar desde una carpeta (from=).", 'Usa uno de los dos: site=<sitio importado> sin from, o from=<carpeta> con un nombre de sitio que no esté importado.');
        }
        $folder = null;
        if ($this->from !== null) {
            $folder = rtrim($resolve($this->from), '/');
            $origin = "la carpeta {$folder}";
        } elseif (!$imported) {
            $folder = "{$this->kitDirectory}/" . self::EXAMPLE_DIRECTORY;
            $origin = 'el sitio de ejemplo del kit (el sitio no está importado)';
        } else {
            $origin = "el sitio importado «{$this->site}»";
        }
        if ($folder !== null) {
            if (!is_dir("{$folder}/" . DesignerKit::SITE_DIRECTORY) || !is_file("{$folder}/" . PublicSite::MANIFEST)) {
                return $fail($this->from ?? 'kit/' . self::EXAMPLE_DIRECTORY, 'La carpeta de origen no tiene site/ y pcs.json.', 'Indica con from= una carpeta del diseño: la que tiene site/ y pcs.json (por ejemplo files/design/adaptado).');
            }
            //Las mismas comprobaciones que la importación: lo que no se podría importar tampoco se exporta, y sus avisos (lo
            //que no se importa tampoco viaja) se dicen.
            $sourceChecks = (new SiteImporter($folder, $this->site, dirname($this->siteDirectory), dirname($this->staticsDirectory)))->check();
            //El ejemplo se llama «example» en su pcs.json: que el sitio nuevo se llame de otra forma es lo normal.
            if ($this->from === null) {
                $sourceChecks = array_values(array_filter($sourceChecks, fn (array $p): bool => !($p['level'] === 'aviso' && $p['file'] === PublicSite::MANIFEST && str_starts_with($p['message'], 'El manifiesto dice que el sitio es'))));
            }
            $problems = $sourceChecks;
            if (array_filter($sourceChecks, fn (array $p): bool => $p['level'] === 'error') !== []) {
                return $fail($this->from ?? 'kit/' . self::EXAMPLE_DIRECTORY, 'La carpeta de origen no pasa las comprobaciones de la importación (arriba).', 'Corrígela antes de exportarla.');
            }
            $pagesDirectory = "{$folder}/" . DesignerKit::SITE_DIRECTORY;
            $partsDirectory = "{$pagesDirectory}/" . PublicSite::PARTS_DIRECTORY;
            $assetsDirectory = "{$pagesDirectory}/" . PublicSite::ASSETS_DIRECTORY;
            $designFile = "{$folder}/" . self::DESIGN_FILE;
        } else {
            $pagesDirectory = "{$this->siteDirectory}/" . PublicSite::PAGES_DIRECTORY;
            $partsDirectory = "{$this->siteDirectory}/" . PublicSite::PARTS_DIRECTORY;
            $assetsDirectory = $this->staticsDirectory;
            $designFile = "{$this->siteDirectory}/" . self::DESIGN_FILE;
        }
        if (is_link($designFile)) {
            return $fail(self::DESIGN_FILE, 'DESIGN.md es un enlace simbólico.', 'Cópialo como archivo normal.');
        }
        //Se lee una vez: lo que se escribe son estos bytes, aunque el archivo cambie después.
        $designBytes = is_file($designFile) ? file_get_contents($designFile) : null;
        if ($designBytes === false) {
            return $fail(self::DESIGN_FILE, 'No se pudo leer DESIGN.md.', 'Revisa sus permisos.');
        }

        //El destino: fuera del proyecto, el paquete del diseñador; dentro, solo bajo files/design/ y solo el sitio.
        //Relativo, contra la raíz del repositorio, como from= y project-file= (bin/cli corre desde src/).
        $destination = rtrim($this->destination === '' ? '' : $resolve($this->destination), '/');
        $parent = dirname($destination);
        if ($destination === '' || file_exists($destination) || is_link($destination) || !is_dir($parent)) {
            return $fail($destination, 'El destino ya existe o su carpeta padre no existe.', 'Indica una carpeta nueva dentro de una que exista: no se sobrescribe nada.');
        }
        $realParent = realpath($parent);
        $realRepository = realpath($repository);
        $realDesign = realpath($repository . '/' . self::DESIGN_DIRECTORY);
        //files/design/ cuenta solo si es esa carpeta de verdad: un enlace la haría apuntar a otro sitio del repositorio o fuera.
        $designPath = $realRepository === false ? null : $realRepository . '/' . self::DESIGN_DIRECTORY;
        if ($realDesign !== false && $realDesign !== $designPath) {
            return $fail(self::DESIGN_DIRECTORY . '/', self::DESIGN_DIRECTORY . '/ es un enlace a otra carpeta.', 'Que sea una carpeta normal del repositorio.');
        }
        $inProject = $realParent !== false && $realRepository !== false && str_starts_with($realParent . '/', $realRepository . '/');
        $inDesign = $inProject && $realDesign !== false && str_starts_with($realParent . '/', $realDesign . '/');
        if ($realParent === false || ($inProject && !$inDesign)) {
            return $fail($destination, 'El destino queda dentro del proyecto, fuera de ' . self::DESIGN_DIRECTORY . '/.', 'Exporta fuera del repositorio (el paquete del diseñador) o dentro de ' . self::DESIGN_DIRECTORY . '/ (solo el sitio).');
        }
        if ($inDesign && $this->zip) {
            return $fail($destination, 'zip=yes no vale dentro de ' . self::DESIGN_DIRECTORY . '/: ahí solo se deja el sitio.', 'Quita zip=yes, o exporta fuera del repositorio.');
        }
        if ($this->zip && (file_exists($this->zipPath()) || !class_exists(\ZipArchive::class))) {
            return $fail($this->zipPath(), class_exists(\ZipArchive::class) ? 'El .zip ya existe.' : 'Este PHP no tiene ZipArchive: no se puede crear el .zip.', class_exists(\ZipArchive::class) ? 'Muévelo o bórralo antes.' : 'Exporta sin zip=yes y comprímelo a mano.');
        }

        //PROJECT.md, solo para el paquete: dentro del repositorio, archivo normal y con su sección de pendientes.
        $projectFile = null;
        $projectBytes = null;
        if (!$inDesign) {
            $projectFile = $resolve($this->projectFile ?? self::DESIGN_DIRECTORY . '/' . self::PROJECT_FILE);
            $realProject = is_link($projectFile) ? false : realpath($projectFile);
            if ($realProject === false || !is_file($realProject) || $realRepository === false || !str_starts_with($realProject, $realRepository . '/')) {
                return $fail($projectFile, 'No está PROJECT.md (las reglas del proyecto para el diseñador), o no es un archivo normal dentro del repositorio.', 'Escríbelo en ' . self::DESIGN_DIRECTORY . '/' . self::PROJECT_FILE . ' (o indica otro dentro del repositorio con project-file=), con su sección «' . self::PENDING_HEADING . '».');
            }
            $projectBytes = file_get_contents($realProject);
            if ($projectBytes === false || !in_array(self::PENDING_HEADING, array_map(fn (string $line): string => rtrim($line, "\r"), explode("\n", $projectBytes)), true)) {
                return $fail($projectFile, 'PROJECT.md no tiene la línea «' . self::PENDING_HEADING . '».', 'Añádela tal cual, con lo que queda pendiente debajo (o «Ninguno»): sin ella no se exporta.');
            }
            $projectFile = $realProject;
        }

        try {
            $packageFiles = $inDesign ? [] : $this->packageFiles();
        } catch (\RuntimeException $e) {
            return $fail('kit/' . self::PACKAGE_MAP, 'El mapa del kit está dañado.', 'Corrígelo: {"files": {"<destino en el paquete>": "<origen en el kit>"}}.');
        }
        $reader = null;
        foreach (self::READER_SOURCES as $candidate) {
            if (is_file("{$this->kitDirectory}/{$candidate}/pcs.js")) {
                $reader = "{$this->kitDirectory}/{$candidate}";
                break;
            }
        }
        $missing = array_values(array_unique(array_filter($packageFiles, fn (string $source): bool => !is_file("{$this->kitDirectory}/{$source}"))));
        if ($reader === null) {
            $missing[] = 'el lector (' . implode(' o ', array_map(fn (string $c): string => "{$c}/pcs.js", self::READER_SOURCES)) . ')';
        }
        if ($missing !== [] || $reader === null) {
            return $fail('kit/', 'Faltan piezas del kit del diseñador: ' . implode(', ', $missing) . '.', 'Sin ellas el paquete no sirve al diseñador: hay que depositarlas en ' . $this->kitDirectory . '.');
        }
        $badEntries = $this->packageFileProblems($packageFiles);
        if ($badEntries !== []) {
            return $fail('kit/' . self::PACKAGE_MAP, 'El mapa del kit tiene rutas que no valen: ' . implode('; ', $badEntries) . '.', 'Cada destino y cada origen es una ruta relativa, sin «..», sin «\\» y dentro del paquete o del kit; site/ y pcs.json no se mapean.');
        }
        $notAllowed = array_values(array_filter(array_keys($packageFiles), fn (string $path): bool => self::isAgentFile($path) && !self::isAllowedAgentFile($path)));
        if ($notAllowed !== []) {
            return $fail('kit/' . self::PACKAGE_MAP, 'El mapa pone archivos fuera del andamiaje permitido del diseñador: ' . implode(', ', $notAllowed) . '.', 'Solo valen: ' . implode(', ', self::ALLOWED_AGENT_PATTERNS) . '.');
        }
        foreach (self::CURSOR_RULES as $rule => $alwaysApply) {
            $source = $packageFiles[".cursor/rules/{$rule}.mdc"] ?? null;
            $problem = $source !== null ? self::cursorRuleProblem((string) file_get_contents("{$this->kitDirectory}/{$source}"), $alwaysApply) : null;
            if ($problem !== null) {
                return $fail("kit/{$source}", "La regla de Cursor no es válida: {$problem}.", 'Corrige su cabecera: «---», description, globs y alwaysApply, y «---».');
            }
        }

        $site = DesignerKit::SITE_DIRECTORY;
        $staging = "{$parent}/." . basename($destination) . '.pcs-exporting-' . bin2hex(random_bytes(3));
        $zipTemporary = $this->zip ? $this->zipPath() . '.tmp-' . bin2hex(random_bytes(3)) : null;
        $trace = '';
        $previousUmask = umask(0002);
        try {
            foreach (PublicSite::namesIn($pagesDirectory) as $name) {
                SiteImporter::copyFile("{$pagesDirectory}/{$name}.html", "{$staging}/{$site}/{$name}.html");
            }
            foreach (PublicSite::namesIn($partsDirectory) as $name) {
                SiteImporter::copyFile("{$partsDirectory}/{$name}.html", "{$staging}/{$site}/parts/{$name}.html");
            }
            SiteImporter::ensureDirectory("{$staging}/{$site}/parts");
            foreach (PublicSite::filesIn($assetsDirectory) as $relative) {
                if (preg_match(DesignerKit::HIDDEN_PATTERN, $relative) === 1) {
                    continue;
                }
                SiteImporter::copyFile("{$assetsDirectory}/{$relative}", "{$staging}/{$site}/assets/{$relative}");
            }
            SiteImporter::ensureDirectory("{$staging}/{$site}/assets");
            foreach (SiteImporter::generated("{$staging}/{$site}/parts", "{$staging}/{$site}") as $relative => $content) {
                SiteImporter::write("{$staging}/{$site}/{$relative}", $content);
            }
            foreach (PublicSite::filesIn($reader) as $relative) {
                SiteImporter::copyFile("{$reader}/{$relative}", "{$staging}/{$site}/" . DesignerKit::READER_DIRECTORY . "/{$relative}");
            }
            SiteImporter::write("{$staging}/{$site}/" . DesignerKit::RULES_SCRIPT, DesignerKit::rulesScript());
            foreach ($packageFiles as $to => $from) {
                SiteImporter::copyFile("{$this->kitDirectory}/{$from}", "{$staging}/{$to}");
            }
            if (is_string($designBytes)) {
                SiteImporter::write("{$staging}/" . self::DESIGN_FILE, $designBytes);
            }
            if (is_string($projectBytes)) {
                SiteImporter::write("{$staging}/" . self::PROJECT_FILE, $projectBytes);
            }
            $siteHashes = [];
            foreach (PublicSite::filesIn("{$staging}/{$site}") as $relative) {
                $siteHashes[$relative] = (string) hash_file('sha256', "{$staging}/{$site}/{$relative}");
            }
            $protected = [];
            foreach ($siteHashes as $relative => $hash) {
                if (str_starts_with($relative, DesignerKit::READER_DIRECTORY . '/')) {
                    $protected["{$site}/{$relative}"] = $hash;
                }
            }
            foreach (array_keys($packageFiles) as $to) {
                if (self::isAgentFile($to)) {
                    $protected[$to] = (string) hash_file('sha256', "{$staging}/{$to}");
                }
            }
            //PROJECT.md, protegido: son las reglas del proyecto. DESIGN.md no: es del diseñador.
            if (is_string($projectBytes)) {
                $protected[self::PROJECT_FILE] = (string) hash_file('sha256', "{$staging}/" . self::PROJECT_FILE);
            }
            SiteImporter::write("{$staging}/" . PublicSite::MANIFEST, DesignerKit::manifest($this->site, $siteHashes, $protected));

            //Exentos: el andamiaje, pcs.json (lista sus rutas en protected), el lector, que comprueba esas rutas, y las notas
            //del diseño y del proyecto, que pueden nombrar AGENTS.md sin ser rastro de la metodología.
            $exempt = array_merge(array_keys($protected), [PublicSite::MANIFEST, self::DESIGN_FILE, self::PROJECT_FILE]);
            [$clean, $trace] = self::traceCheck($staging, $exempt);
            if (!$clean) {
                SiteImporter::removeTree($staging);
                return $fail('', 'El paquete lleva rastro de la metodología del proyecto: no se deja nada.', 'Revisa lo que señala la comprobación (abajo) y quítalo del kit o del sitio.', $trace);
            }
            if ($zipTemporary !== null) {
                self::zipFolder($staging, $zipTemporary, basename($destination));
            }
            if (!rename($staging, $destination)) {
                throw new \RuntimeException("no se pudo mover el paquete a {$destination}");
            }
            if ($zipTemporary !== null && !rename($zipTemporary, $this->zipPath())) {
                throw new \RuntimeException('no se pudo dejar el .zip en ' . $this->zipPath());
            }
        } catch (\Throwable $e) {
            SiteImporter::removeTree($staging);
            if ($zipTemporary !== null && is_file($zipTemporary)) {
                @unlink($zipTemporary); //RETORNO-IGNORADO: un .zip a medias que queda lleva sufijo .tmp-: no se confunde
            }
            return $fail('', 'No se pudo exportar: ' . $e->getMessage(), 'Revisa los permisos del destino.');
        } finally {
            umask($previousUmask);
        }
        return ['ok' => true, 'problems' => $problems, 'files' => count(PublicSite::filesIn($destination)), 'zip' => $this->zip ? $this->zipPath() : null, 'trace' => MarkRules::printable($trace), 'origin' => MarkRules::printable($origin), 'destination' => $destination];
    }

    /**
     * Lo que falla en la cabecera de una regla de Cursor (.mdc): YAML entre dos «---» con solo description, globs y
     * alwaysApply, alwaysApply con el valor esperado y, si no se aplica siempre, una description que diga cuándo.
     * Análisis mínimo, sin un lector de YAML: no hay ninguno en las dependencias.
     *
     * @param string $content
     * @param bool $alwaysApply
     * @return string|null Null si vale
     */
    public static function cursorRuleProblem(string $content, bool $alwaysApply): ?string
    {
        if (preg_match('/^\x{FEFF}?---\r?\n(.*?)\r?\n---\r?(\n|$)/su', $content, $match) !== 1) {
            return 'no empieza por una cabecera entre dos líneas «---»';
        }
        $keys = [];
        foreach (preg_split('/\r?\n/', $match[1]) ?: [] as $line) {
            if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
                continue;
            }
            if (preg_match('/^([A-Za-z]+):\s*(.*)$/', $line, $pair) !== 1) {
                return "la línea «{$line}» no es «clave: valor»";
            }
            if (!in_array($pair[1], ['description', 'globs', 'alwaysApply'], true)) {
                return "la clave «{$pair[1]}» no es de Cursor (description, globs, alwaysApply)";
            }
            $keys[$pair[1]] = trim($pair[2]);
        }
        if (!in_array($keys['alwaysApply'] ?? null, ['true', 'false'], true)) {
            return 'alwaysApply tiene que ser true o false';
        }
        if (($keys['alwaysApply'] === 'true') !== $alwaysApply) {
            return 'alwaysApply tiene que ser ' . ($alwaysApply ? 'true' : 'false');
        }
        if (!$alwaysApply && trim($keys['description'] ?? '', " \t\"'") === '') {
            return 'sin alwaysApply necesita una description que diga cuándo usarla';
        }
        return null;
    }

    /**
     * La comprobación de rastro de entregar-al-cliente sobre una carpeta, con esos archivos como únicas excepciones.
     *
     * @param string $folder
     * @param string[] $allowed Rutas exactas, relativas a la carpeta
     * @return array{0:bool,1:string} Si está limpia, y su salida
     */
    public static function traceCheck(string $folder, array $allowed): array
    {
        $root = dirname(rtrim(basepath(), '/'));
        $script = null;
        foreach (self::TRACE_CHECKS as $candidate) {
            if (is_file("{$root}/{$candidate}")) {
                $script = "{$root}/{$candidate}";
                break;
            }
        }
        if ($script === null) {
            return [false, 'No está la comprobación de rastro (' . implode(' ni ', self::TRACE_CHECKS) . '): sin ella no se exporta.'];
        }
        $command = ['bash', $script, '--comprobar', $folder];
        foreach ($allowed as $file) {
            array_push($command, '--permitir', $file);
        }
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return [false, 'No se pudo ejecutar la comprobación de rastro (bash y python3).'];
        }
        $output = (string) stream_get_contents($pipes[1]) . (string) stream_get_contents($pipes[2]);
        return [proc_close($process) === 0, trim($output)];
    }

    /**
     * Comprime una carpeta en un .zip bajo una carpeta raíz, con archivos (nunca enlaces) y en orden de ruta.
     *
     * @param string $folder
     * @param string $zipFile
     * @param string $rootName
     * @return void
     * @throws \RuntimeException
     */
    private static function zipFolder(string $folder, string $zipFile, string $rootName): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::EXCL) !== true) {
            throw new \RuntimeException("no se pudo crear {$zipFile}");
        }
        foreach (PublicSite::filesIn($folder) as $relative) {
            if (!$zip->addFile("{$folder}/{$relative}", "{$rootName}/{$relative}")) {
                $zip->close();
                throw new \RuntimeException("no se pudo añadir {$relative} al .zip");
            }
        }
        if (!$zip->close()) {
            throw new \RuntimeException("no se pudo cerrar {$zipFile}");
        }
    }
}
