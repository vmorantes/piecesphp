<?php

/**
 * TokenIndex.php
 */

namespace PiecesPHP\Core\Atlas;

/**
 * TokenIndex - El índice de tokens de src/app para el mapa del código (ADR 0091, R1): cada `new Route(` con sus
 * argumentos, cada `const` de clase con su línea y su comentario, y cada uso `Clase::CONSTANTE`.
 *
 * Se calcula en vivo con PhpToken, una vez por proceso, sobre src/app sin vendor ni local-tests. No ejecuta el código
 * que lee: los valores los evalúa Expression.
 *
 * @package     PiecesPHP\Core\Atlas
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class TokenIndex
{
    const ROUTE_CLASS = 'PiecesPHP\\Core\\Route';
    const MENU_FILE = 'app/config/menu.php';

    /**
     * Cuántos archivos guardan sus tokens a la vez: el resto se vuelve a leer cuando hace falta.
     */
    const TOKEN_CACHE_SIZE = 16;

    /**
     * Los nombres de los argumentos del constructor de Route, en su orden (RouteAdapter::__construct).
     */
    const ROUTE_ARGUMENTS = ['route', 'controller', 'name', 'method', 'requireLogin', 'alias', 'rolesAllowed', 'defaultParamsValues', 'middlewares'];

    /**
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * @var array<string,array{namespace:string,uses:array<string,string>,classes:array<int,array{name:string,start:int,end:int,parent:?string}>,functions:array<int,array{start:int,end:int}>}>
     */
    private array $files = [];

    /**
     * Los tokens de los últimos archivos pedidos (guardarlos todos ocupaba 78 MB).
     *
     * @var array<string,\PhpToken[]>
     */
    private array $tokenCache = [];

    /**
     * @var array<int,array{file:string,line:int,class:?string,arguments:array<string,array{start:int,end:int}>}>
     */
    private array $routes = [];

    /**
     * @var array<string,array{file:string,line:int,comment:string}>
     */
    private array $constants = [];

    /**
     * @var array<string,string> nombre de clase en minúsculas => archivo relativo a src/
     */
    private array $classNames = [];

    /**
     * @var array<string,array<int,array{file:string,line:int,context:string}>>
     */
    private array $constantUses = [];

    private float $milliseconds = 0.0;

    private int $fileCount = 0;

    private string $src;

    /**
     * @param string $src La carpeta src/ (sin barra final).
     */
    private function __construct(string $src)
    {
        $this->src = $src;
        $started = hrtime(true);
        foreach (self::phpFiles($src . '/app') as $path) {
            $this->indexFile($path);
        }
        $this->milliseconds = (hrtime(true) - $started) / 1e6;
    }

    /**
     * El índice del proceso; se calcula la primera vez.
     *
     * @return self
     */
    public static function get(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(rtrim(str_replace('\\', '/', basepath('')), '/'));
        }
        return self::$instance;
    }

    /**
     * Olvida el índice: el próximo get() lo vuelve a calcular. Para las suites.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Cuánto tardó el índice en milisegundos y cuántos archivos leyó.
     *
     * @return array{milliseconds:float,files:int}
     */
    public function stats(): array
    {
        return ['milliseconds' => round($this->milliseconds, 1), 'files' => $this->fileCount];
    }

    /**
     * @return array<int,array{file:string,line:int,class:?string,arguments:array<string,array{start:int,end:int}>}>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * @param string $symbol `Clase\Completa::NOMBRE`
     * @return array{file:string,line:int,comment:string}|null
     */
    public function constant(string $symbol): ?array
    {
        return $this->constants[$symbol] ?? null;
    }

    /**
     * Si una clase, interfaz, trait o enum se declara en src/app (sin vendor ni local-tests).
     *
     * @param string $class
     * @return bool
     */
    public function declaresClass(string $class): bool
    {
        return isset($this->classNames[strtolower(ltrim($class, '\\'))]);
    }

    /**
     * @param string $symbol `Clase\Completa::NOMBRE`
     * @return array<int,array{file:string,line:int,context:string}>
     */
    public function constantUses(string $symbol): array
    {
        return $this->constantUses[$symbol] ?? [];
    }

    /**
     * Los usos de una constante escritos a través de cualquier clase (`Subclase::NOMBRE`, `self::NOMBRE`): clase => usos.
     *
     * @param string $name
     * @return array<string,array<int,array{file:string,line:int,context:string}>>
     */
    public function constantUsesByName(string $name): array
    {
        $found = [];
        foreach ($this->constantUses as $symbol => $uses) {
            if (str_ends_with($symbol, '::' . $name)) {
                $found[mb_substr($symbol, 0, -mb_strlen('::' . $name))] = $uses;
            }
        }
        return $found;
    }

    /**
     * Los tokens de un archivo indexado, con su espacio de nombres, sus `use`, sus clases y sus funciones.
     *
     * @param string $file Relativo a src/.
     * @return array{tokens:\PhpToken[],namespace:string,uses:array<string,string>,classes:array<int,array{name:string,start:int,end:int,parent:?string}>,functions:array<int,array{start:int,end:int}>}|null
     */
    public function file(string $file): ?array
    {
        if (!isset($this->files[$file])) {
            return null;
        }
        if (!isset($this->tokenCache[$file])) {
            if (count($this->tokenCache) >= self::TOKEN_CACHE_SIZE) {
                array_shift($this->tokenCache);
            }
            $code = @file_get_contents($this->src . '/' . $file);
            $this->tokenCache[$file] = is_string($code) ? \PhpToken::tokenize($code) : [];
        }
        return ['tokens' => $this->tokenCache[$file]] + $this->files[$file];
    }

    /**
     * El archivo relativo a src/ de una ruta absoluta, o null si no es de src/.
     *
     * @param string $path
     * @return string|null
     */
    public function relative(string $path): ?string
    {
        $path = str_replace('\\', '/', $path);
        return str_starts_with($path, $this->src . '/') ? mb_substr($path, mb_strlen($this->src) + 1) : null;
    }

    /**
     * Resuelve un nombre de clase como lo haría PHP en ese archivo.
     *
     * @param string $name Tal como se escribió: Route, Core\Route, \PiecesPHP\Core\Route.
     * @param string $namespace
     * @param array<string,string> $uses alias en minúsculas => nombre completo
     * @return string
     */
    public static function resolveClass(string $name, string $namespace, array $uses): string
    {
        if (str_starts_with($name, '\\')) {
            return ltrim($name, '\\');
        }
        if (str_starts_with(strtolower($name), 'namespace\\')) {
            return ltrim($namespace . '\\' . mb_substr($name, 10), '\\');
        }
        $parts = explode('\\', $name);
        $first = strtolower($parts[0]);
        if (isset($uses[$first])) {
            $parts[0] = $uses[$first];
            return implode('\\', $parts);
        }
        return ltrim($namespace . '\\' . $name, '\\');
    }

    /**
     * La clase que contiene la posición $index de un archivo, o null.
     *
     * @param array{classes:array<int,array{name:string,start:int,end:int,parent:?string}>} $file
     * @param int $index
     * @return array{name:string,start:int,end:int,parent:?string}|null
     */
    public static function classAt(array $file, int $index): ?array
    {
        $found = null;
        foreach ($file['classes'] as $class) {
            if ($class['start'] <= $index && $index <= $class['end']) {
                $found = $class;
            }
        }
        return $found;
    }

    /**
     * Los archivos PHP de una carpeta, sin vendor ni local-tests, en orden.
     *
     * @param string $root
     * @return string[]
     */
    private static function phpFiles(string $root): array
    {
        $paths = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                continue;
            }
            $path = str_replace('\\', '/', $entry->getPathname());
            if (!str_ends_with($path, '.php') || str_contains($path, '/vendor/') || str_contains($path, '/local-tests/')) {
                continue;
            }
            $paths[] = $path;
        }
        sort($paths);
        return $paths;
    }

    /**
     * @param string $path
     * @return void
     */
    private function indexFile(string $path): void
    {
        $code = @file_get_contents($path);
        $relative = $this->relative($path);
        if (!is_string($code) || $relative === null) {
            return;
        }
        $this->fileCount++;
        if (!str_contains($code, '::') && !str_contains($code, 'const ') && !str_contains($code, 'Route(')) {
            return;
        }
        $tokens = \PhpToken::tokenize($code);
        $count = count($tokens);
        $namespace = '';
        $uses = [];
        $classes = [];
        $functions = [];
        $classEnds = [];
        $functionEnds = [];
        $openClasses = [];
        $openFunctions = [];
        $pendingClass = null;
        $pendingFunction = false;
        $braceDepth = 0;
        $routeCalls = [];
        $constantUses = [];

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token->isIgnorable()) {
                continue;
            }
            if ($token->is(T_NAMESPACE) && $braceDepth === 0) {
                $next = $this->next($tokens, $i);
                if ($next !== null && $tokens[$next]->is([T_STRING, T_NAME_QUALIFIED])) {
                    $namespace = $tokens[$next]->text;
                }
                continue;
            }
            if ($token->is(T_USE) && $braceDepth === 0) {
                //`function () use ($x)` de una closure de primer nivel no es un import.
                $previous = $this->previous($tokens, $i);
                if ($previous === null || $tokens[$previous]->text !== ')') {
                    $this->readUse($tokens, $i, $uses);
                }
                continue;
            }
            if ($token->is([T_CLASS, T_TRAIT, T_INTERFACE, T_ENUM])) {
                $previous = $this->previous($tokens, $i);
                $next = $this->next($tokens, $i);
                if ($previous !== null && $tokens[$previous]->is([T_DOUBLE_COLON, T_NEW])) {
                    continue;
                }
                if ($next !== null && $tokens[$next]->is(T_STRING)) {
                    $parent = null;
                    for ($j = $next + 1; $j < $count && $tokens[$j]->text !== '{'; $j++) {
                        if ($tokens[$j]->is(T_EXTENDS)) {
                            $parentToken = $this->next($tokens, $j);
                            if ($parentToken !== null) {
                                $parent = self::resolveClass($tokens[$parentToken]->text, $namespace, $uses);
                            }
                        }
                    }
                    $pendingClass = ltrim($namespace . '\\' . $tokens[$next]->text, '\\');
                    $classes[] = ['name' => $pendingClass, 'start' => $i, 'end' => $count - 1, 'parent' => $parent];
                }
                continue;
            }
            if ($token->is(T_FUNCTION) || $token->is(T_FN)) {
                $pendingFunction = $token->is(T_FUNCTION);
                continue;
            }
            if ($token->text === '{' || $token->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                $braceDepth++;
                if ($pendingClass !== null && $token->text === '{') {
                    $openClasses[$braceDepth] = count($classes) - 1;
                    $pendingClass = null;
                } elseif ($pendingFunction && $token->text === '{') {
                    $functions[] = ['start' => $i, 'end' => $count - 1];
                    $openFunctions[$braceDepth] = count($functions) - 1;
                    $pendingFunction = false;
                }
                continue;
            }
            if ($token->text === ';' && $pendingFunction) {
                //Un método abstracto o de interfaz: sin cuerpo.
                $pendingFunction = false;
                continue;
            }
            if ($token->text === '}') {
                if (isset($openClasses[$braceDepth])) {
                    $classEnds[$openClasses[$braceDepth]] = $i;
                    unset($openClasses[$braceDepth]);
                }
                if (isset($openFunctions[$braceDepth])) {
                    $functionEnds[$openFunctions[$braceDepth]] = $i;
                    unset($openFunctions[$braceDepth]);
                }
                $braceDepth--;
                continue;
            }
            if ($token->is(T_CONST)) {
                $this->readConstants($tokens, $i, $classes, $relative);
                continue;
            }
            if ($token->is(T_NEW)) {
                $name = $this->next($tokens, $i);
                if ($name !== null && $tokens[$name]->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])
                    && self::resolveClass($tokens[$name]->text, $namespace, $uses) === self::ROUTE_CLASS) {
                    $open = $this->next($tokens, $name);
                    if ($open !== null && $tokens[$open]->text === '(') {
                        $routeCalls[] = ['open' => $open, 'line' => $token->line];
                    }
                }
                continue;
            }
            if ($token->is(T_DOUBLE_COLON)) {
                $use = $this->readConstantUse($tokens, $i, $namespace, $uses, $classes);
                if ($use !== null) {
                    $constantUses[] = $use;
                }
            }
        }

        //Los finales se guardan aparte mientras se lee: antes de cerrarse, una clase o una función llega al final del archivo.
        foreach ($classEnds as $position => $end) {
            $classes[$position] = ['name' => $classes[$position]['name'], 'start' => $classes[$position]['start'], 'end' => $end, 'parent' => $classes[$position]['parent']];
        }
        foreach ($functionEnds as $position => $end) {
            $functions[$position] = ['start' => $functions[$position]['start'], 'end' => $end];
        }
        $this->files[$relative] = ['namespace' => $namespace, 'uses' => $uses, 'classes' => $classes, 'functions' => $functions];
        foreach ($classes as $class) {
            $this->classNames[strtolower($class['name'])] = $relative;
        }
        $spans = [];
        foreach ($routeCalls as $call) {
            $class = self::classAt(['classes' => $classes], $call['open']);
            $arguments = $this->readArguments($tokens, $call['open']);
            $this->routes[] = [
                'file' => $relative,
                'line' => $call['line'],
                'class' => $class['name'] ?? null,
                'arguments' => $arguments,
            ];
            $ends = array_column($arguments, 'end');
            $spans[] = [$call['open'], $ends === [] ? $call['open'] : max($ends)];
        }
        foreach ($constantUses as $use) {
            $context = $relative === self::MENU_FILE ? 'menú' : 'otro';
            foreach ($spans as [$from, $to]) {
                if ($from <= $use['index'] && $use['index'] <= $to) {
                    $context = 'declaración de ruta';
                }
            }
            $this->constantUses[$use['symbol']][] = ['file' => $relative, 'line' => $use['line'], 'context' => $context];
        }
    }

    /**
     * Los argumentos de una llamada, por su nombre de Route (posicional o con nombre): el tramo de tokens de cada uno.
     *
     * @param \PhpToken[] $tokens
     * @param int $open El índice del `(`.
     * @return array<string,array{start:int,end:int}>
     */
    private function readArguments(array $tokens, int $open): array
    {
        $arguments = [];
        $position = 0;
        $depth = 0;
        $start = $open + 1;
        $count = count($tokens);
        for ($i = $open; $i < $count; $i++) {
            $text = $tokens[$i]->text;
            if ($text === '(' || $text === '[' || $text === '{' || $tokens[$i]->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                $depth++;
                continue;
            }
            $closes = $text === ')' || $text === ']' || $text === '}';
            if (($text === ',' && $depth === 1) || ($closes && $depth === 1)) {
                $first = $start;
                while ($first < $i && $tokens[$first]->isIgnorable()) {
                    $first++;
                }
                if ($first < $i) {
                    $name = self::ROUTE_ARGUMENTS[$position] ?? 'extra-' . $position;
                    $colon = $this->next($tokens, $first);
                    if ($tokens[$first]->is(T_STRING) && $colon !== null && $colon < $i && $tokens[$colon]->text === ':' && in_array($tokens[$first]->text, self::ROUTE_ARGUMENTS, true)) {
                        $name = $tokens[$first]->text;
                        $first = $colon + 1;
                    }
                    $arguments[$name] = ['start' => $first, 'end' => $i];
                    $position++;
                }
                $start = $i + 1;
            }
            if ($closes) {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
        }
        return $arguments;
    }

    /**
     * @param \PhpToken[] $tokens
     * @param int $index
     * @param array<string,string> $uses
     * @return void
     */
    private function readUse(array $tokens, int $index, array &$uses): void
    {
        $count = count($tokens);
        $prefix = '';
        $current = '';
        for ($i = $index + 1; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token->isIgnorable()) {
                continue;
            }
            if ($token->is([T_FUNCTION, T_CONST])) {
                return;
            }
            if ($token->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])) {
                $current = ltrim($token->text, '\\');
                $as = $this->next($tokens, $i);
                if ($as !== null && $tokens[$as]->is(T_AS)) {
                    $alias = $this->next($tokens, $as);
                    if ($alias !== null) {
                        $full = ltrim($prefix . $current, '\\');
                        $uses[strtolower($tokens[$alias]->text)] = $full;
                        $i = $alias;
                        $current = '';
                    }
                }
                continue;
            }
            if ($token->text === '{') {
                $prefix = rtrim($current, '\\') . '\\';
                $current = '';
                continue;
            }
            if ($token->text === ',' || $token->text === '}' || $token->text === ';') {
                if ($current !== '') {
                    $full = ltrim($prefix . $current, '\\');
                    $parts = explode('\\', $full);
                    $uses[strtolower((string) end($parts))] = $full;
                    $current = '';
                }
                if ($token->text === ';') {
                    return;
                }
            }
        }
    }

    /**
     * @param \PhpToken[] $tokens
     * @param int $index El índice de T_CONST.
     * @param array<int,array{name:string,start:int,end:int,parent:?string}> $classes
     * @param string $relative
     * @return void
     */
    private function readConstants(array $tokens, int $index, array $classes, string $relative): void
    {
        $class = null;
        foreach ($classes as $candidate) {
            if ($candidate['start'] <= $index && $index <= $candidate['end']) {
                $class = $candidate['name'];
            }
        }
        if ($class === null) {
            return;
        }
        $comment = '';
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];
            if ($token->is(T_WHITESPACE) || $token->is([T_PUBLIC, T_PROTECTED, T_PRIVATE, T_FINAL])) {
                continue;
            }
            if ($token->is([T_DOC_COMMENT, T_COMMENT])) {
                $comment = trim($token->text);
            }
            break;
        }
        $count = count($tokens);
        $depth = 0;
        $expectName = true;
        for ($i = $index + 1; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token->isIgnorable()) {
                continue;
            }
            if ($expectName && $token->is(T_STRING)) {
                $next = $this->next($tokens, $i);
                if ($next !== null && $tokens[$next]->text === '=') {
                    $this->constants[$class . '::' . $token->text] = ['file' => $relative, 'line' => $token->line, 'comment' => $comment];
                    $expectName = false;
                }
                continue;
            }
            if ($token->text === '(' || $token->text === '[') {
                $depth++;
            } elseif ($token->text === ')' || $token->text === ']') {
                $depth--;
            } elseif ($token->text === ',' && $depth === 0) {
                $expectName = true;
            } elseif ($token->text === ';' && $depth === 0) {
                return;
            }
        }
    }

    /**
     * @param \PhpToken[] $tokens
     * @param int $index El índice de `::`.
     * @param string $namespace
     * @param array<string,string> $uses
     * @param array<int,array{name:string,start:int,end:int,parent:?string}> $classes
     * @return array{symbol:string,line:int,index:int}|null
     */
    private function readConstantUse(array $tokens, int $index, string $namespace, array $uses, array $classes): ?array
    {
        $left = $this->previous($tokens, $index);
        $right = $this->next($tokens, $index);
        if ($left === null || $right === null || !$tokens[$right]->is(T_STRING) || $tokens[$right]->text === 'class') {
            return null;
        }
        $after = $this->next($tokens, $right);
        if ($after !== null && $tokens[$after]->text === '(') {
            return null;
        }
        $name = $tokens[$left]->text;
        if (!$tokens[$left]->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_STATIC])) {
            return null;
        }
        $current = null;
        foreach ($classes as $candidate) {
            if ($candidate['start'] <= $index && $index <= $candidate['end']) {
                $current = $candidate;
            }
        }
        $lower = strtolower($name);
        if ($lower === 'self' || $lower === 'static') {
            if ($current === null) {
                return null;
            }
            $class = $current['name'];
        } elseif ($lower === 'parent') {
            if ($current === null || $current['parent'] === null) {
                return null;
            }
            $class = $current['parent'];
        } else {
            $class = self::resolveClass($name, $namespace, $uses);
        }
        return ['symbol' => $class . '::' . $tokens[$right]->text, 'line' => $tokens[$right]->line, 'index' => $right];
    }

    /**
     * El siguiente token significativo, o null.
     *
     * @param \PhpToken[] $tokens
     * @param int $index
     * @return int|null
     */
    public function next(array $tokens, int $index): ?int
    {
        $count = count($tokens);
        for ($i = $index + 1; $i < $count; $i++) {
            if (!$tokens[$i]->isIgnorable()) {
                return $i;
            }
        }
        return null;
    }

    /**
     * El token significativo anterior, o null.
     *
     * @param \PhpToken[] $tokens
     * @param int $index
     * @return int|null
     */
    public function previous(array $tokens, int $index): ?int
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            if (!$tokens[$i]->isIgnorable()) {
                return $i;
            }
        }
        return null;
    }
}
