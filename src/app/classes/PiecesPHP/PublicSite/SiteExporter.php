<?php

/**
 * SiteExporter.php
 */

namespace PiecesPHP\PublicSite;

/**
 * SiteExporter - `bin/cli public-site-export`: arma desde el proyecto el paquete del diseñador. site/ es el sitio tal
 * cual (las páginas en su raíz, index.html la portada; parts/, assets/, data/ y el lector oculto .pcs/); en la raíz,
 * CHECK.html, GUIDE.html, pcs.json y el andamiaje de su agente (AGENTS.md, CLAUDE.md, .agents/, .claude/, .cursor/).
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
     */
    public function __construct(
        private readonly string $site,
        private readonly string $destination,
        private readonly bool $zip = false,
        ?string $kitDirectory = null,
        ?string $siteDirectory = null,
        ?string $staticsDirectory = null,
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
     * @return array{ok:bool,problems:array<int,array{level:string,file:string,line:int,message:string,fix:string}>,files:int,zip:string|null,trace:string}
     */
    public function export(): array
    {
        $problems = [];
        $fail = function (string $file, string $message, string $fix, string $trace = '') use (&$problems): array {
            $problems[] = ['level' => 'error', 'file' => $file, 'line' => 0, 'message' => $message, 'fix' => $fix];
            return ['ok' => false, 'problems' => $problems, 'files' => 0, 'zip' => null, 'trace' => $trace];
        };
        $destination = rtrim($this->destination, '/');
        $parent = dirname($destination);
        if (!PublicSite::isValidName($this->site) || !is_file("{$this->siteDirectory}/" . PublicSite::MANIFEST)) {
            return $fail("app/view/{$this->site}/", "«{$this->site}» no es un sitio importado (falta su pcs.json).", 'Importa antes el sitio con bin/cli public-site-import, o revisa el nombre.');
        }
        if ($destination === '' || file_exists($destination) || !is_dir($parent)) {
            return $fail($destination, 'El destino ya existe o su carpeta padre no existe.', 'Indica una carpeta nueva dentro de una que exista: no se sobrescribe nada.');
        }
        $repository = dirname(rtrim(basepath(), '/'));
        $realParent = realpath($parent);
        if ($realParent !== false && str_starts_with($realParent . '/', $repository . '/')) {
            return $fail($destination, 'El destino queda dentro del proyecto.', 'Exporta fuera del repositorio.');
        }
        if ($this->zip && (file_exists($this->zipPath()) || !class_exists(\ZipArchive::class))) {
            return $fail($this->zipPath(), class_exists(\ZipArchive::class) ? 'El .zip ya existe.' : 'Este PHP no tiene ZipArchive: no se puede crear el .zip.', class_exists(\ZipArchive::class) ? 'Muévelo o bórralo antes.' : 'Exporta sin zip=yes y comprímelo a mano.');
        }
        try {
            $packageFiles = $this->packageFiles();
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
            foreach (PublicSite::namesIn("{$this->siteDirectory}/" . PublicSite::PAGES_DIRECTORY) as $name) {
                SiteImporter::copyFile("{$this->siteDirectory}/pages/{$name}.html", "{$staging}/{$site}/{$name}.html");
            }
            foreach (PublicSite::namesIn("{$this->siteDirectory}/" . PublicSite::PARTS_DIRECTORY) as $name) {
                SiteImporter::copyFile("{$this->siteDirectory}/parts/{$name}.html", "{$staging}/{$site}/parts/{$name}.html");
            }
            SiteImporter::ensureDirectory("{$staging}/{$site}/parts");
            foreach (PublicSite::filesIn($this->staticsDirectory) as $relative) {
                if (preg_match(DesignerKit::HIDDEN_PATTERN, $relative) === 1) {
                    continue;
                }
                SiteImporter::copyFile("{$this->staticsDirectory}/{$relative}", "{$staging}/{$site}/assets/{$relative}");
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
            SiteImporter::write("{$staging}/" . PublicSite::MANIFEST, DesignerKit::manifest($this->site, $siteHashes, $protected));

            //Exentos: el andamiaje, pcs.json (lista sus rutas en protected) y el lector, que comprueba esas rutas.
            $exempt = array_merge(array_keys($protected), [PublicSite::MANIFEST]);
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
        return ['ok' => true, 'problems' => $problems, 'files' => count(PublicSite::filesIn($destination)), 'zip' => $this->zip ? $this->zipPath() : null, 'trace' => $trace];
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
