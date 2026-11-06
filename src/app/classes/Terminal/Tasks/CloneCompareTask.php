<?php

/**
 * CloneCompareTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * CloneCompareTask - Compara un proyecto clonado con el framework, archivo por archivo por hash, y dice de qué versión
 * partió (H209). Solo lee: ni el proyecto ni el framework se escriben, y del proyecto solo salen rutas, categorías y cifras.
 *
 * Categorías: igual (mismo blob de git, con CRLF pasado a LF en los archivos de texto); igual-nivel-1 (además, sin espacios
 * ni tabuladores al final de cada línea ni saltos de línea al final del archivo); igual-nivel-2 (solo .php: además, sin
 * comentarios y con cada tramo de espacios reducido a uno, por token_get_all); modificado; solo-framework-no-viaja (casa con
 * bin/distribution-exclude.txt o con la capa C de .agents/capas.json, leídos del framework en HEAD); solo-framework;
 * solo-proyecto.
 *
 * Partida, en dos cifras sobre las etiquetas v*: nacimiento, la de más rutas en común (de la que se clonó; empate → más
 * idénticos → la más reciente); masParecida, la de más archivos idénticos en ruta y blob (de la que más copió; empate → la
 * más reciente), que es contra= por omisión. Un clon que copió archivos de una versión posterior da dos cifras distintas.
 * Origen de cada archivo: la etiqueta MÁS RECIENTE que lo tiene idéntico, o null.
 *
 * Módulo de cada ruta, regla fija: src/app/classes/<X>/… → classes/<X>; src/statics/<X>/… → statics/<X>; src/app/<X>/… →
 * app/<X>; un archivo directo en src/app/classes, src/statics o src/app → classes, statics o app; src/<archivo> → src;
 * src/<X>/… (otra carpeta) → src/<X>; bin/… → bin; un archivo en la raíz → raíz; otra carpeta de la raíz → su nombre.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class CloneCompareTask extends TerminalTaskAbstract
{

    const FORMAT = 1;

    const CATEGORIES = [
        'igual',
        'igual-nivel-1',
        'igual-nivel-2',
        'modificado',
        'solo-framework',
        'solo-framework-no-viaja',
        'solo-proyecto',
    ];

    /** Desde la raíz del árbol, en los dos lados. */
    const EXCLUDED_PREFIXES = [
        'src/vendor/',
        'src/app/cache/',
        'src/app/logs/',
        'src/statics/uploads/',
        'src/statics/server-delegated/',
        'src/dumps/',
        'secure-keys/',
    ];

    /** En cualquier nivel: src/node_modules también. */
    const EXCLUDED_SEGMENTS = ['.git', 'node_modules'];

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $this->description = new StringArray([
            "Compara un proyecto clonado con el framework, archivo por archivo por hash, y dice de qué versión partió. Solo lee.\r\n",
            "\tParámetros:\r\n",
            "\t  ruta=<carpeta>       la raíz del proyecto (obligatorio)\r\n",
            "\t  framework=<carpeta>  el repositorio git del framework (por omisión, este)\r\n",
            "\t  contra=<etiqueta>    la versión contra la que comparar (por omisión, la detectada)\r\n",
            "\t  salida=<archivo.json> escribe el informe entero (no dentro de ruta=)\r\n",
            "\t  detalle=yes          lista en pantalla las rutas de cada categoría\r\n",
            "\tSale con 1 si un parámetro no vale o git falla.",
        ]);
        $this->route = "{$startRoute}/clone-compare[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'clone-compare';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $terminal = TerminalData::instance();
        $params = [];
        foreach (['ruta', 'framework', 'contra', 'salida', 'detalle'] as $name) {
            $value = $terminal->getArgument($name, null);
            if (is_string($value)) {
                $params[$name] = trim($value);
            } elseif ($value === true) {
                $params[$name] = 'yes';
            }
        }
        $started = microtime(true);
        $outcome = self::execute($params);
        if ($outcome['error'] !== null || $outcome['result'] === null) {
            echoTerminal("\e[31mERROR:\e[39m " . ($outcome['error'] ?? 'sin resultado.'));
            exit(1);
        }
        self::printReport($outcome['result'], mb_strtolower($params['detalle'] ?? '') === 'yes', microtime(true) - $started);
    }

    /**
     * Valida, compara y, con salida=, escribe el JSON. Nunca escribe en ruta=.
     *
     * @param array<string,string> $params ruta, framework, contra, salida (las de la tarea)
     * @return array{error: string|null, result: array<string,mixed>|null}
     */
    public static function execute(array $params): array
    {
        $fail = fn(string $message): array => ['error' => $message, 'result' => null];

        $projectGiven = $params['ruta'] ?? '';
        $project = $projectGiven !== '' ? realpath($projectGiven) : false;
        if ($project === false || !is_dir($project)) {
            return $fail("ruta= tiene que ser una carpeta que exista («{$projectGiven}»).");
        }

        $frameworkGiven = ($params['framework'] ?? '') !== '' ? $params['framework'] : dirname(rtrim(basepath(), '/'));
        $framework = realpath($frameworkGiven);
        if ($framework === false || self::gitTopLevel($framework) !== $framework) {
            return $fail("framework= tiene que ser la raíz de un repositorio git («{$frameworkGiven}»).");
        }

        $output = null;
        if (($params['salida'] ?? '') !== '') {
            $outputGiven = $params['salida'];
            $outputDir = realpath(dirname($outputGiven));
            if (mb_strtolower(pathinfo($outputGiven, \PATHINFO_EXTENSION)) !== 'json' || $outputDir === false || !is_dir($outputDir)) {
                return $fail("salida= tiene que ser un archivo .json en una carpeta que exista («{$outputGiven}»).");
            }
            $output = $outputDir . '/' . basename($outputGiven);
            //Un enlace ya existente podría apuntar dentro del proyecto: se mira también su destino.
            $outputReal = file_exists($output) ? realpath($output) : $output;
            $inside = fn(string $path): bool => $path === $project || str_starts_with($path, rtrim($project, '/') . '/');
            if (is_link($output) || $outputReal === false || $inside($outputDir) || $inside($outputReal)) {
                return $fail("salida= no puede quedar dentro de ruta=: la tarea no escribe en el proyecto («{$output}»).");
            }
        }

        $contra = ($params['contra'] ?? '') !== '' ? $params['contra'] : null;
        if ($contra !== null) {
            //Sin guion delante: git lo leería como una opción.
            if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._\/-]*$/', $contra) !== 1 || self::git($framework, ['rev-parse', '--verify', '--quiet', "refs/tags/{$contra}^{tree}"])[0] !== 0) {
                return $fail("contra= tiene que ser una etiqueta del framework («{$contra}»).");
            }
        }

        [$code, $tagsText, $error] = self::git($framework, ['for-each-ref', '--sort=-creatordate', '--format=%(refname:strip=2)', 'refs/tags/v*']);
        if ($code !== 0) {
            return $fail("git for-each-ref falló en el framework: {$error}");
        }
        $tags = array_values(array_filter(explode("\n", $tagsText), fn(string $tag): bool => $tag !== ''));
        if ($tags === [] && $contra === null) {
            return $fail('el framework no tiene etiquetas v*: no hay contra qué comparar; da contra=.');
        }

        [$projectFiles, $unreadable, $projectError] = self::projectFiles($project);
        if ($projectError !== null) {
            return $fail($projectError);
        }

        //Una pasada por etiqueta, de la más reciente a la más antigua: rutas en común, idénticos (ruta y blob) y, por
        //archivo, la primera etiqueta que lo tiene idéntico, que es la más reciente.
        $scores = [];
        $trees = [];
        $origins = array_fill_keys(array_keys($projectFiles), null);
        foreach ($tags as $tag) {
            [$tree, $treeError] = self::tree($framework, $tag);
            if ($treeError !== null) {
                return $fail($treeError);
            }
            $common = 0;
            $identical = 0;
            foreach ($projectFiles as $path => $blob) {
                if (!isset($tree[$path])) {
                    continue;
                }
                $common++;
                if ($tree[$path] === $blob) {
                    $identical++;
                    $origins[$path] = $origins[$path] ?? $tag;
                }
            }
            $scores[] = ['etiqueta' => $tag, 'rutasComunes' => $common, 'identicos' => $identical];
            if ($tag === $contra) {
                $trees[$tag] = $tree;
            }
        }
        $total = count($projectFiles);
        //usort es estable: en empate queda delante la más reciente, que es como vienen las etiquetas.
        $pick = function (callable $order) use ($scores, $total): array {
            usort($scores, $order);
            $withPercent = array_map(fn(array $score): array => $score + ['porcentaje' => $total > 0 ? round($score['identicos'] * 100 / $total, 1) : 0.0], array_slice($scores, 0, 3));
            $best = $withPercent[0] ?? ['etiqueta' => null, 'rutasComunes' => 0, 'identicos' => 0, 'porcentaje' => 0.0];
            return $best + ['empate' => isset($scores[1]) && $order($scores[0], $scores[1]) === 0, 'candidatas' => $withPercent];
        };
        $start = [
            'nacimiento' => $pick(fn(array $a, array $b): int => [$b['rutasComunes'], $b['identicos']] <=> [$a['rutasComunes'], $a['identicos']]),
            'masParecida' => $pick(fn(array $a, array $b): int => $b['identicos'] <=> $a['identicos']),
        ];
        ksort($origins, \SORT_STRING);
        $originCount = [];
        foreach ($origins as $origin) {
            $key = $origin ?? 'ninguna';
            $originCount[$key] = ($originCount[$key] ?? 0) + 1;
        }
        arsort($originCount);

        $contraReason = $contra !== null ? 'parametro' : 'masParecida';
        $contra = $contra ?? (string) $start['masParecida']['etiqueta'];
        if (!isset($trees[$contra])) {
            [$tree, $treeError] = self::tree($framework, $contra);
            if ($treeError !== null) {
                return $fail($treeError);
            }
            $trees[$contra] = $tree;
        }
        $frameworkFiles = $trees[$contra];
        $notShipped = self::notShippedPatterns($framework);

        $files = array_fill_keys(self::CATEGORIES, []);
        $candidatesForLevels = [];
        foreach ($projectFiles as $path => $blob) {
            if (!isset($frameworkFiles[$path])) {
                $files['solo-proyecto'][] = $path;
            } elseif ($frameworkFiles[$path] === $blob) {
                $files['igual'][] = $path;
            } else {
                $candidatesForLevels[$path] = $frameworkFiles[$path];
            }
        }
        foreach ($frameworkFiles as $path => $blob) {
            if (!isset($projectFiles[$path]) && !self::isUnreadable($path, $unreadable)) {
                $files[self::matchesAny($path, $notShipped) ? 'solo-framework-no-viaja' : 'solo-framework'][] = $path;
            }
        }
        [$contents, $catError] = self::blobContents($framework, array_values(array_unique($candidatesForLevels)));
        if ($catError !== null) {
            return $fail($catError);
        }
        foreach ($candidatesForLevels as $path => $blob) {
            //Un enlace no se sigue: su destino puede estar fuera del proyecto o no terminar nunca (/dev/zero).
            $isLink = is_link($project . '/' . $path);
            $theirs = $contents[$blob] ?? null;
            $mine = $isLink ? null : self::readProjectFile($project . '/' . $path);
            $category = 'modificado';
            if ($theirs !== null && $mine !== null) {
                $mine = self::normalizeEol($mine);
                if (self::normalizeLevel1($mine) === self::normalizeLevel1($theirs)) {
                    $category = 'igual-nivel-1';
                } elseif (str_ends_with($path, '.php') && self::normalizeLevel2($mine) === self::normalizeLevel2($theirs)) {
                    $category = 'igual-nivel-2';
                }
            }
            $files[$category][] = $path;
        }

        $summary = [];
        $byModule = [];
        foreach ($files as $category => $paths) {
            sort($paths, \SORT_STRING);
            $files[$category] = $paths;
            $summary[$category] = count($paths);
            foreach ($paths as $path) {
                $module = self::moduleOf($path);
                $byModule[$module] = $byModule[$module] ?? array_fill_keys(self::CATEGORIES, 0);
                $byModule[$module][$category]++;
            }
        }
        ksort($byModule, \SORT_STRING);
        $unreadableList = array_keys($unreadable);
        sort($unreadableList, \SORT_STRING);

        $result = [
            'formato' => self::FORMAT,
            'framework' => ['ruta' => $framework, 'commit' => self::headCommit($framework)],
            'proyecto' => ['ruta' => $project, 'commit' => self::gitTopLevel($project) === $project ? self::headCommit($project) : null],
            'partida' => $start,
            'contra' => $contra,
            'contraMotivo' => $contraReason,
            'resumen' => $summary,
            'porModulo' => $byModule,
            'archivos' => $files,
            'origenes' => $origins,
            'origenPorEtiqueta' => $originCount,
            //Lo que no se pudo leer (permisos): no entra en ninguna categoría ni cuenta como «solo-framework».
            'ilegibles' => $unreadableList,
        ];

        if ($output !== null) {
            $json = json_encode($result, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
            if ($json === false) {
                return $fail('no se pudo codificar el informe en JSON: ' . json_last_error_msg());
            }
            if (file_put_contents($output, $json . "\n") === false) {
                return $fail("no se pudo escribir {$output}.");
            }
        }
        return ['error' => null, 'result' => $result];
    }

    /**
     * El hash que git daría al archivo: sha1("blob <tamaño>\0<contenido>"), con CRLF pasado a LF si es texto.
     *
     * @param string $content
     * @return string
     */
    public static function blobHash(string $content): string
    {
        $content = self::isBinary($content) ? $content : self::normalizeEol($content);
        return sha1('blob ' . strlen($content) . "\0" . $content);
    }

    /**
     * @param string $content
     * @return bool
     */
    public static function isBinary(string $content): bool
    {
        return str_contains(substr($content, 0, 8000), "\0");
    }

    /**
     * @param string $content
     * @return string
     */
    public static function normalizeEol(string $content): string
    {
        return self::isBinary($content) ? $content : str_replace("\r\n", "\n", $content);
    }

    /**
     * Sin espacios ni tabuladores al final de cada línea ni saltos de línea al final del archivo.
     *
     * @param string $content Con los finales ya en LF.
     * @return string
     */
    public static function normalizeLevel1(string $content): string
    {
        return rtrim(preg_replace('/[ \t]+$/m', '', $content) ?? $content, "\n");
    }

    /**
     * PHP sin comentarios y con cada tramo de espacios (y la etiqueta de apertura) reducido a un espacio.
     *
     * @param string $content
     * @return string
     */
    public static function normalizeLevel2(string $content): string
    {
        $out = '';
        $lastWasSpace = false;
        foreach (token_get_all($content) as $token) {
            if (is_array($token)) {
                if ($token[0] === \T_COMMENT || $token[0] === \T_DOC_COMMENT) {
                    continue;
                }
                if ($token[0] === \T_WHITESPACE || $token[0] === \T_OPEN_TAG) {
                    $out .= $token[0] === \T_OPEN_TAG ? rtrim($token[1]) : '';
                    if (!$lastWasSpace) {
                        $out .= ' ';
                        $lastWasSpace = true;
                    }
                    continue;
                }
                $out .= $token[1];
            } else {
                $out .= $token;
            }
            $lastWasSpace = false;
        }
        return trim($out);
    }

    /**
     * @param string $path Relativa a la raíz del proyecto.
     * @return string
     */
    public static function moduleOf(string $path): string
    {
        $parts = explode('/', $path);
        $count = count($parts);
        if ($count === 1) {
            return 'raíz';
        }
        if ($parts[0] === 'bin') {
            return 'bin';
        }
        if ($parts[0] !== 'src') {
            return $parts[0];
        }
        if ($count === 2) {
            return 'src';
        }
        if ($parts[1] === 'app' && $parts[2] === 'classes') {
            return $count > 4 ? "classes/{$parts[3]}" : 'classes';
        }
        if ($parts[1] === 'statics' || $parts[1] === 'app') {
            return $count > 3 ? "{$parts[1]}/{$parts[2]}" : $parts[1];
        }
        return "src/{$parts[1]}";
    }

    /**
     * Si la ruta es un archivo ilegible o cuelga de una carpeta ilegible (las carpetas van con «/» al final).
     *
     * @param string $path
     * @param array<string,true> $unreadable
     * @return bool
     */
    private static function isUnreadable(string $path, array $unreadable): bool
    {
        if (isset($unreadable[$path])) {
            return true;
        }
        foreach (array_keys($unreadable) as $entry) {
            if (str_ends_with($entry, '/') && str_starts_with($path, $entry)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function isExcluded(string $path): bool
    {
        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }
        return array_intersect(explode('/', $path), self::EXCLUDED_SEGMENTS) !== [];
    }

    /**
     * Una línea de la lista casa con la ruta exacta, con lo que cuelga de ella como carpeta o como patrón de fnmatch,
     * donde «*» cruza «/» (como en bin/make-distribution y capas.py).
     *
     * @param string $path
     * @param string[] $patterns
     * @return bool
     */
    public static function matchesAny(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($path === $pattern || str_starts_with($path, rtrim($pattern, '/') . '/') || fnmatch($pattern, $path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string,mixed> $result
     * @param bool $detail
     * @param float $seconds
     * @return void
     */
    private static function printReport(array $result, bool $detail, float $seconds): void
    {
        $start = $result['partida'];
        echoTerminal("Proyecto: {$result['proyecto']['ruta']}" . ($result['proyecto']['commit'] !== null ? " (commit {$result['proyecto']['commit']})" : ' (sin git)'));
        echoTerminal("Framework: {$result['framework']['ruta']} (commit {$result['framework']['commit']})");
        $labels = [
            'nacimiento' => 'Nacimiento (la de más rutas en común: de la que se clonó)',
            'masParecida' => 'Más parecida (la de más archivos idénticos: de la que más copió)',
        ];
        foreach ($labels as $key => $label) {
            $pick = $start[$key];
            echoTerminal("{$label}: {$pick['etiqueta']}" . ($pick['empate'] ? ' · EMPATE: hay otra con las mismas cifras' : ''));
            foreach ($pick['candidatas'] as $index => $candidate) {
                echoTerminal('   ' . ($index + 1) . ". {$candidate['etiqueta']}: {$candidate['rutasComunes']} rutas en común, {$candidate['identicos']} idénticos ({$candidate['porcentaje']} %)");
            }
        }
        echoTerminal("Comparado contra: {$result['contra']} (" . ($result['contraMotivo'] === 'parametro' ? 'contra=' : 'la más parecida') . ')');
        foreach ($result['resumen'] as $category => $count) {
            echoTerminal(sprintf('   %-24s %d', $category, $count));
        }
        if ($result['ilegibles'] !== []) {
            echoTerminal('   ' . count($result['ilegibles']) . ' archivo(s) del proyecto sin permiso de lectura: fuera de la cuenta.');
        }
        $modified = array_filter(array_map(fn(array $counts): int => $counts['modificado'], $result['porModulo']));
        arsort($modified);
        echoTerminal('Módulos con más modificados:');
        foreach (array_slice($modified, 0, 10, true) as $module => $count) {
            echoTerminal('   ' . mb_str_pad((string) $module, 40) . " {$count}");
        }
        echoTerminal('Origen de cada archivo (la etiqueta más reciente que lo tiene idéntico):');
        $origins = $result['origenPorEtiqueta'];
        $none = $origins['ninguna'] ?? 0;
        unset($origins['ninguna']);
        foreach (array_slice($origins, 0, 5, true) as $tag => $count) {
            echoTerminal('   ' . mb_str_pad((string) $tag, 24) . " {$count}");
        }
        echoTerminal('   ' . mb_str_pad('ninguna', 24) . " {$none}");
        if ($detail) {
            foreach ($result['archivos'] as $category => $paths) {
                echoTerminal("== {$category} (" . count($paths) . ')');
                foreach ($paths as $path) {
                    //Un nombre de archivo puede llevar secuencias de escape: a la terminal no llegan.
                    echoTerminal('   ' . (preg_replace('/[\x00-\x1F\x7F]/', '?', $path) ?? '?'));
                }
            }
        }
        echoTerminal(sprintf('clone-compare ejecutado: %d archivo(s) del proyecto, contra %s, en %.1f s.', array_sum($result['resumen']) - $result['resumen']['solo-framework'] - $result['resumen']['solo-framework-no-viaja'], $result['contra'], $seconds));
    }

    /**
     * Los archivos del proyecto con su blob: los de git (versionados y nuevos no ignorados) si ruta= es la raíz de un
     * repositorio; si no, todo el disco. Los enlaces se cuentan como git: el blob es su destino.
     *
     * @param string $project
     * @return array{0: array<string,string>, 1: array<string,true>, 2: string|null}
     */
    private static function projectFiles(string $project): array
    {
        $paths = [];
        $unreadable = [];
        $root = rtrim($project, '/') . '/';
        if (self::gitTopLevel($project) === $project) {
            [$code, $listed, $error] = self::git($project, ['ls-files', '-co', '--exclude-standard', '-z']);
            if ($code !== 0) {
                return [[], [], "git ls-files falló en el proyecto: {$error}"];
            }
            $paths = array_filter(explode("\0", $listed), fn(string $path): bool => $path !== '');
        } elseif (file_exists($root . '.git')) {
            //Sin esto, un repositorio que git no quiere leer (safe.directory, otro dueño) se recorrería como disco,
            //con lo ignorado dentro.
            return [[], [], "ruta= tiene .git pero git no la lee como repositorio (¿safe.directory? ¿otro dueño?): {$root}"];
        } else {
            $relative = fn(\SplFileInfo $file): string => substr($file->getPathname(), strlen($root));
            //Una carpeta sin permiso no se abre (lanzaría): se anota entera como ilegible, con «/» al final.
            $keep = function (\SplFileInfo $file) use ($relative, &$unreadable): bool {
                $isDir = $file->isDir() && !$file->isLink();
                $path = $relative($file) . ($isDir ? '/' : '');
                if (self::isExcluded($path)) {
                    return false;
                }
                if ($isDir && !$file->isReadable()) {
                    $unreadable[$path] = true;
                    return false;
                }
                return true;
            };
            $iterator = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(new \RecursiveDirectoryIterator($project, \FilesystemIterator::SKIP_DOTS), $keep));
            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo) {
                    $paths[] = $relative($file);
                }
            }
        }
        $files = [];
        foreach ($paths as $path) {
            $full = $root . $path;
            if (self::isExcluded($path) || (!is_link($full) && !is_file($full))) {
                continue;
            }
            if (is_link($full)) {
                $target = readlink($full);
                if ($target === false) {
                    $unreadable[$path] = true;
                    continue;
                }
                $files[$path] = sha1('blob ' . strlen($target) . "\0" . $target);
                continue;
            }
            $content = self::readProjectFile($full);
            if ($content === null) {
                $unreadable[$path] = true;
                continue;
            }
            $files[$path] = self::blobHash($content);
        }
        return [$files, $unreadable, null];
    }

    /**
     * @param string $full
     * @return string|null
     */
    private static function readProjectFile(string $full): ?string
    {
        if (!is_readable($full)) {
            return null;
        }
        $content = @file_get_contents($full);
        return is_string($content) ? $content : null;
    }

    /**
     * Las rutas de una etiqueta con su blob, sin lo excluido.
     *
     * @param string $framework
     * @param string $tag
     * @return array{0: array<string,string>, 1: string|null}
     */
    private static function tree(string $framework, string $tag): array
    {
        [$code, $listed, $error] = self::git($framework, ['ls-tree', '-r', '-z', "refs/tags/{$tag}"]);
        if ($code !== 0) {
            return [[], "git ls-tree {$tag} falló en el framework: {$error}"];
        }
        $tree = [];
        foreach (explode("\0", $listed) as $entry) {
            $tab = strpos($entry, "\t");
            if ($tab === false) {
                continue;
            }
            $meta = explode(' ', substr($entry, 0, $tab));
            $path = substr($entry, $tab + 1);
            if (($meta[1] ?? '') === 'blob' && !self::isExcluded($path)) {
                $tree[$path] = $meta[2] ?? '';
            }
        }
        return [$tree, null];
    }

    /**
     * El contenido de varios blobs del framework de una vez, con git cat-file --batch; normalizado a LF si es texto.
     *
     * @param string $framework
     * @param string[] $blobs
     * @return array{0: array<string,string>, 1: string|null}
     */
    private static function blobContents(string $framework, array $blobs): array
    {
        if ($blobs === []) {
            return [[], null];
        }
        [$code, $out, $error] = self::git($framework, ['cat-file', '--batch'], implode("\n", $blobs) . "\n");
        if ($code !== 0) {
            return [[], "git cat-file falló en el framework: {$error}"];
        }
        $contents = [];
        $offset = 0;
        $length = strlen($out);
        while ($offset < $length) {
            $end = strpos($out, "\n", $offset);
            if ($end === false) {
                break;
            }
            $header = explode(' ', substr($out, $offset, $end - $offset));
            $offset = $end + 1;
            if (($header[1] ?? '') !== 'blob' || !isset($header[2])) {
                continue;
            }
            $size = (int) $header[2];
            $contents[$header[0]] = self::normalizeEol(substr($out, $offset, $size));
            $offset += $size + 1;
        }
        return [$contents, null];
    }

    /**
     * Las líneas de bin/distribution-exclude.txt y los patrones de la capa C de .agents/capas.json, del framework en HEAD.
     * Si faltan (un framework sin ellos), ninguna.
     *
     * @param string $framework
     * @return string[]
     */
    private static function notShippedPatterns(string $framework): array
    {
        $patterns = [];
        [$code, $list] = self::git($framework, ['show', 'HEAD:bin/distribution-exclude.txt']);
        if ($code === 0) {
            foreach (explode("\n", $list) as $line) {
                $line = trim($line);
                if ($line !== '' && !str_starts_with($line, '#')) {
                    $patterns[] = $line;
                }
            }
        }
        [$code, $layers] = self::git($framework, ['show', 'HEAD:.agents/capas.json']);
        $decoded = $code === 0 ? json_decode($layers, true) : null;
        $layerC = is_array($decoded) ? ($decoded['capas']['C']['patrones'] ?? []) : [];
        foreach (is_array($layerC) ? $layerC : [] as $pattern) {
            if (is_string($pattern) && $pattern !== '') {
                $patterns[] = $pattern;
            }
        }
        return $patterns;
    }

    /**
     * @param string $dir
     * @return string|null La raíz real del repositorio que contiene $dir, o null si no está en uno.
     */
    private static function gitTopLevel(string $dir): ?string
    {
        [$code, $out] = self::git($dir, ['rev-parse', '--show-toplevel']);
        $top = $code === 0 ? realpath(trim($out)) : false;
        return $top !== false ? $top : null;
    }

    /**
     * @param string $dir
     * @return string|null
     */
    private static function headCommit(string $dir): ?string
    {
        [$code, $out] = self::git($dir, ['rev-parse', 'HEAD']);
        return $code === 0 ? trim($out) : null;
    }

    /**
     * git con un arreglo de argumentos (sin shell) y sin cerrojos opcionales: no reescribe el índice del repositorio que
     * lee, que puede estar en uso. Sin las GIT_* heredadas (GIT_DIR mandaría sobre -C) y sin core.fsmonitor, que el
     * .git/config del proyecto podría usar para ejecutar algo. La entrada y el error van por temporales para no bloquear.
     *
     * @param string $dir
     * @param list<string> $args
     * @param string|null $input
     * @return array{0: int, 1: string, 2: string}
     */
    private static function git(string $dir, array $args, ?string $input = null): array
    {
        $stdin = tmpfile();
        $stderr = tmpfile();
        if ($stdin === false || $stderr === false) {
            return [1, '', 'no se pudo crear un temporal'];
        }
        if ($input !== null && (fwrite($stdin, $input) !== strlen($input) || !rewind($stdin))) {
            return [1, '', 'no se pudo preparar la entrada de git'];
        }
        $env = array_filter(getenv(), fn(string $name): bool => !str_starts_with($name, 'GIT_'), \ARRAY_FILTER_USE_KEY);
        $command = array_merge(['git', '--no-optional-locks', '-c', 'core.fsmonitor=false', '-C', $dir], $args);
        $process = proc_open($command, [0 => $stdin, 1 => ['pipe', 'w'], 2 => $stderr], $pipes, null, $env);
        if (!is_resource($process)) {
            return [1, '', 'no se pudo arrancar git'];
        }
        $out = (string) stream_get_contents($pipes[1]);
        //RETORNO-IGNORADO: cerrar una tubería propia ya leída.
        fclose($pipes[1]);
        $code = proc_close($process);
        $error = rewind($stderr) ? trim((string) stream_get_contents($stderr)) : '';
        return [$code, $out, $error !== '' ? $error : "código {$code}"];
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new CloneCompareTask($startRoute, $namePrefix);
        return new Route(
            $instance->route,
            $instance->controller,
            $instance->name,
            $instance->method,
            $instance->requireLogin,
            null,
            $instance->rolesAllowed->getArrayCopy(),
            $instance->defaultParamsValues,
            $instance->middlewares
        );
    }
}
