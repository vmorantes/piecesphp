<?php

/**
 * SiteLint.php
 */

namespace PiecesPHP\PublicSite;

/**
 * SiteLint - Las URL absolutas a servicios que el proyecto no conoce y los bloques copiados en vez de partes (ADR 0087,
 * puntos 4 y 10): lo mismo que externalUrls() y repeatedBlocks() de kit/pcs/check.js, con su mismo recorrido por texto
 * (no el de Dom\HTMLDocument) y blancos solo ASCII, para que CHECK.html y la importación den lo mismo. La lista sale de
 * rules.json.
 *
 * @package     PiecesPHP\PublicSite
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class SiteLint
{

    const VOID = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
    const RAW = ['script', 'style', 'textarea', 'title'];
    const URL_ATTRIBUTES = ['src', 'href', 'srcset', 'data-srcset', 'poster', 'data-src', 'data-bg', 'data-background', 'action', 'formaction'];
    const RESOURCE_RELS = ['stylesheet', 'preload', 'modulepreload', 'prefetch', 'icon', 'apple-touch-icon', 'manifest'];
    const SCRIPT_TYPES = ['', 'text/javascript', 'application/javascript', 'module'];
    const NAMESPACE_HOSTS = ['www.w3.org'];
    const BLANKS = "\t\n\f\r ";
    const TEXT_URL = '/[`\'"(][\t\n\f\r ]*((?:https?:)?\/\/(?:localhost|\d{1,3}(?:\.\d{1,3}){3}|[A-Za-z0-9][A-Za-z0-9.-]*\.[A-Za-z]{2,})(?::\d+)?[^\t\n\f\r \'"`()<>]*)/';
    const STYLE_URL = '/url\([\t\n\f\r ]*[\'"]?((?:https?:)?\/\/[^\'")\t\n\f\r ]+)/i';
    const TAG_PATTERN = '/<(\/?)([a-zA-Z][a-zA-Z0-9:-]*)((?:\s+[^\s"\'>\/=]+(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+))?)*)\s*(\/?)>/A';
    const ATTR_PATTERN = '/([^\s"\'>\/=]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+)))?/';
    const BLOCK_MINIMUM = 300;
    const BLOCK_PAGES = 3;

    /**
     * Las URL absolutas (http, https o //) a un host fuera de la lista, con su línea; path, el archivo del paquete que se
     * llama como su último segmento, y exact si además coincide el final de su ruta (desde site/ o site/assets/): la ruta
     * local disfrazada. En la navegación (<a>, <link> que no carga nada), solo la disfrazada.
     *
     * @param array<int,array{file:string,text:string,kind:string}> $inputs kind «html», «css» o «js»
     * @param string[] $knownHosts
     * @param string[] $packagePaths Las rutas de los archivos del paquete dentro de site/, ordenadas
     * @return array<int,array{file:string,line:int,url:string,host:string,path:string,exact:bool}>
     */
    public static function externalUrls(array $inputs, array $knownHosts, array $packagePaths): array
    {
        $found = [];
        $consider = function (string $file, int $line, string $value, bool $navigation) use (&$found, $knownHosts, $packagePaths): void {
            $url = trim($value, self::BLANKS);
            if (preg_match('/^(?:https?:)?\/\/([^\/?#]*)([^?#]*)/i', $url, $match) !== 1) {
                return;
            }
            $host = strtolower((string) preg_replace('/:\d*$/', '', (string) preg_replace('/^.*@/', '', $match[1])));
            if ($host === '' || in_array($host, self::NAMESPACE_HOSTS, true) || in_array($host, $knownHosts, true)) {
                return;
            }
            $urlPath = $match[2];
            $segments = explode('/', $urlPath);
            $last = (string) end($segments);
            $path = '';
            $exact = false;
            if ($last !== '') {
                foreach ($packagePaths as $candidate) {
                    $tails = str_starts_with($candidate, 'assets/') ? [$candidate, substr($candidate, 7)] : [$candidate];
                    $isExact = false;
                    //Exacta solo si coincide al menos una carpeta: «…/style.css» de otro dominio es solo el nombre.
                    foreach ($tails as $tail) {
                        $isExact = $isExact || (str_contains($tail, '/') && str_ends_with($urlPath, '/' . $tail));
                    }
                    if ($isExact && !$exact) {
                        $path = $candidate;
                        $exact = true;
                    } elseif (!$exact && $path === '' && basename($candidate) === $last) {
                        $path = $candidate;
                    }
                }
            }
            if ($path !== '' || !$navigation) {
                $found[] = ['file' => $file, 'line' => $line, 'url' => $url, 'host' => $host, 'path' => $path, 'exact' => $exact];
            }
        };
        foreach ($inputs as $input) {
            $text = $input['text'];
            if ($input['kind'] !== 'html') {
                $clean = self::withoutComments($text, $input['kind']);
                foreach (self::matches(self::TEXT_URL, $clean) as [$offset, $url]) {
                    $consider($input['file'], self::lineOf($clean, $offset), $url, false);
                }
                continue;
            }
            foreach (self::elements($text) as $node) {
                if ($node['tag'] === '#raw') {
                    $clean = self::withoutComments($node['text'], $node['attrs']['kind'] ?? 'js');
                    foreach (self::matches(self::TEXT_URL, $clean) as [$offset, $url]) {
                        $consider($input['file'], $node['line'] + self::lineOf($clean, $offset) - 1, $url, false);
                    }
                    continue;
                }
                $rels = explode(' ', strtolower((string) preg_replace('/\s+/', ' ', $node['attrs']['rel'] ?? '')));
                $navigation = $node['tag'] === 'a' || ($node['tag'] === 'link' && array_intersect($rels, self::RESOURCE_RELS) === []);
                foreach (self::URL_ATTRIBUTES as $name) {
                    if (!array_key_exists($name, $node['attrs'])) {
                        continue;
                    }
                    $values = $name === 'srcset' || $name === 'data-srcset'
                        ? array_map(fn (string $candidate): string => (string) (preg_split('/[\t\n\f\r ]+/', ltrim($candidate, self::BLANKS)) ?: [''])[0], explode(',', $node['attrs'][$name]))
                        : [$node['attrs'][$name]];
                    foreach ($values as $value) {
                        $consider($input['file'], $node['line'], $value, $navigation && $name === 'href');
                    }
                }
                if (array_key_exists('style', $node['attrs'])) {
                    foreach (self::matches(self::STYLE_URL, $node['attrs']['style']) as [, $url]) {
                        $consider($input['file'], $node['line'], $url, false);
                    }
                }
            }
        }
        return $found;
    }

    /**
     * Los elementos de 300 o más caracteres (sin espacios) idénticos en 3 o más páginas, fuera de <pcs-part>: cada bloque
     * una vez, el más externo, en la primera página (por nombre) donde lo es.
     *
     * @param array<string,string> $pages Ruta (site/x.html) => texto
     * @return array<int,array{file:string,line:int,pages:string[]}>
     */
    public static function repeatedBlocks(array $pages): array
    {
        ksort($pages, \SORT_STRING);
        //Por página, arrays paralelos por elemento: etiqueta, padre, línea y su marcado sin espacios (o null).
        $perPage = [];
        $pagesOf = [];
        foreach ($pages as $file => $text) {
            $file = (string) $file;
            /** @var string[] $tags */
            $tags = [];
            /** @var int[] $starts */
            $starts = [];
            /** @var int[] $ends */
            $ends = [];
            /** @var array<int,int|null> $parents */
            $parents = [];
            /** @var int[] $stack */
            $stack = [];
            $position = 0;
            $length = strlen($text);
            while ($position < $length) {
                $lt = strpos($text, '<', $position);
                if ($lt === false) {
                    break;
                }
                if (substr($text, $lt, 4) === '<!--') {
                    $end = strpos($text, '-->', $lt + 4);
                    $position = $end === false ? $length : $end + 3;
                    continue;
                }
                if (substr($text, $lt, 2) === '<!' || substr($text, $lt, 2) === '<?') {
                    $end = strpos($text, '>', $lt);
                    $position = $end === false ? $length : $end + 1;
                    continue;
                }
                if (preg_match(self::TAG_PATTERN, $text, $match, 0, $lt) !== 1) {
                    $position = $lt + 1;
                    continue;
                }
                $tag = strtolower($match[2]);
                $position = $lt + strlen($match[0]);
                if ($match[1] === '/') {
                    for ($index = count($stack) - 1; $index >= 0; $index--) {
                        if ($tags[$stack[$index]] === $tag) {
                            $ends[$stack[$index]] = $position;
                            array_splice($stack, $index);
                            break;
                        }
                    }
                    continue;
                }
                if (in_array($tag, self::VOID, true) || (($match[4] ?? '') === '/' && !str_contains($tag, '-'))) {
                    continue;
                }
                $id = count($tags);
                $tags[$id] = $tag;
                $starts[$id] = $lt;
                $ends[$id] = -1;
                $parents[$id] = $stack === [] ? null : $stack[count($stack) - 1];
                if (in_array($tag, self::RAW, true)) {
                    $endRaw = stripos($text, '</' . $tag, $position);
                    $closeEnd = $endRaw === false ? false : strpos($text, '>', $endRaw);
                    $ends[$id] = $closeEnd === false ? -1 : $closeEnd + 1;
                    $position = $closeEnd === false ? $length : $closeEnd + 1;
                    continue;
                }
                $stack[] = $id;
            }
            /** @var array<int,string|null> $norms */
            $norms = [];
            /** @var int[] $lines */
            $lines = [];
            foreach ($tags as $id => $tag) {
                $inPart = false;
                for ($up = $id; $up !== null; $up = $parents[$up]) {
                    $inPart = $inPart || $tags[$up] === 'pcs-part';
                }
                $norm = $ends[$id] === -1 || $inPart || in_array($tag, ['html', 'head', 'body'], true)
                    ? null : (string) preg_replace('/[\t\n\f\r ]+/', '', substr($text, $starts[$id], $ends[$id] - $starts[$id]));
                if ($norm !== null && mb_strlen($norm) >= self::BLOCK_MINIMUM) {
                    $pagesOf[$norm] ??= [];
                    if (!in_array($file, $pagesOf[$norm], true)) {
                        $pagesOf[$norm][] = $file;
                    }
                } else {
                    $norm = null;
                }
                $norms[$id] = $norm;
                $lines[$id] = self::lineOf($text, $starts[$id]);
            }
            $perPage[$file] = ['parents' => $parents, 'norms' => $norms, 'lines' => $lines];
        }
        $found = [];
        $reported = [];
        $qualifies = fn (?string $norm): bool => $norm !== null && count($pagesOf[$norm] ?? []) >= self::BLOCK_PAGES;
        foreach ($perPage as $file => $page) {
            foreach ($page['norms'] as $id => $norm) {
                if ($norm === null || !$qualifies($norm) || isset($reported[$norm])) {
                    continue;
                }
                for ($up = $page['parents'][$id]; $up !== null; $up = $page['parents'][$up]) {
                    if ($qualifies($page['norms'][$up])) {
                        continue 2;
                    }
                }
                $reported[$norm] = true;
                $found[] = ['file' => (string) $file, 'line' => $page['lines'][$id], 'pages' => $pagesOf[$norm] ?? []];
            }
        }
        return $found;
    }

    /**
     * Los elementos de un HTML con sus atributos y su línea, y el texto de cada <script> sin src: el recorrido de
     * parseHtml() de check.js, aplanado (el texto de <style> y de un <script> de JavaScript, como «#raw»).
     *
     * @param string $text
     * @return array<int,array{tag:string,attrs:array<string,string>,line:int,text:string}>
     */
    private static function elements(string $text): array
    {
        $nodes = [];
        $position = 0;
        $length = strlen($text);
        while ($position < $length) {
            $lt = strpos($text, '<', $position);
            if ($lt === false) {
                break;
            }
            if (substr($text, $lt, 4) === '<!--') {
                $end = strpos($text, '-->', $lt + 4);
                $position = $end === false ? $length : $end + 3;
                continue;
            }
            if (substr($text, $lt, 2) === '<!' || substr($text, $lt, 2) === '<?') {
                $end = strpos($text, '>', $lt);
                $position = $end === false ? $length : $end + 1;
                continue;
            }
            if (preg_match(self::TAG_PATTERN, $text, $match, 0, $lt) !== 1) {
                $position = $lt + 1;
                continue;
            }
            $position = $lt + strlen($match[0]);
            if ($match[1] === '/') {
                continue;
            }
            $tag = strtolower($match[2]);
            $attrs = [];
            $pairs = [];
            if (preg_match_all(self::ATTR_PATTERN, $match[3], $pairs, \PREG_SET_ORDER) === false) {
                $pairs = [];
            }
            foreach ($pairs as $pair) {
                $name = strtolower($pair[1]);
                if (!array_key_exists($name, $attrs)) {
                    $value = ($pair[2] ?? '') !== '' ? $pair[2] : (($pair[3] ?? '') !== '' ? $pair[3] : ($pair[4] ?? ''));
                    $attrs[$name] = self::decodeEntities($value);
                }
            }
            $line = self::lineOf($text, $lt);
            $nodes[] = ['tag' => $tag, 'attrs' => $attrs, 'line' => $line, 'text' => ''];
            if (in_array($tag, self::VOID, true) || (($match[4] ?? '') === '/' && !str_contains($tag, '-'))) {
                continue;
            }
            if (in_array($tag, self::RAW, true)) {
                $endRaw = stripos($text, '</' . $tag, $position);
                $stop = $endRaw === false ? $length : $endRaw;
                //El texto de un <style> y el de un <script> de JavaScript sin src (no el de un JSON-LD, con URL del propio sitio).
                if ($tag === 'style' || ($tag === 'script' && !array_key_exists('src', $attrs) && in_array(strtolower(trim($attrs['type'] ?? '')), self::SCRIPT_TYPES, true))) {
                    $nodes[] = ['tag' => '#raw', 'attrs' => ['kind' => $tag === 'style' ? 'css' : 'js'], 'line' => self::lineOf($text, $position), 'text' => substr($text, $position, $stop - $position)];
                }
                $closeEnd = $endRaw === false ? false : strpos($text, '>', $endRaw);
                $position = $closeEnd === false ? $length : $closeEnd + 1;
            }
        }
        return $nodes;
    }

    /**
     * Los comentarios en blanco, con sus saltos (las líneas no se mueven): /* *\/ en CSS; en JS, además // fuera de cadenas
     * y de expresiones regulares (una / tras ( , = : [ ! & | ? { } ; o al empezar). Límite: no es un analizador de JS.
     *
     * @param string $text
     * @param string $kind «css» o «js»
     * @return string
     */
    public static function withoutComments(string $text, string $kind): string
    {
        $out = '';
        $quote = '';
        $previous = '';
        $length = strlen($text);
        $blank = fn (string $piece): string => (string) preg_replace('/[^\n]/', ' ', $piece);
        for ($i = 0; $i < $length; $i++) {
            $ch = $text[$i];
            if ($quote !== '') {
                $out .= $ch;
                if ($ch === '\\') {
                    $out .= $i + 1 < $length ? $text[++$i] : '';
                } elseif ($ch === $quote) {
                    $quote = '';
                }
            } elseif ($ch === '"' || $ch === "'" || ($kind === 'js' && $ch === '`')) {
                $quote = $ch;
                $out .= $ch;
            } elseif ($kind === 'js' && $ch === '/' && ($text[$i + 1] ?? '') !== '/' && ($text[$i + 1] ?? '') !== '*' && str_contains('(,=:[!&|?{};', $previous === '' ? '(' : $previous)) {
                $inClass = false;
                $out .= $ch;
                for ($i++; $i < $length && $text[$i] !== "\n"; $i++) {
                    $out .= $text[$i];
                    if ($text[$i] === '\\') {
                        $out .= $i + 1 < $length ? $text[++$i] : '';
                    } elseif ($text[$i] === '[') {
                        $inClass = true;
                    } elseif ($text[$i] === ']') {
                        $inClass = false;
                    } elseif ($text[$i] === '/' && !$inClass) {
                        break;
                    }
                }
                $previous = '/';
                continue;
            } elseif ($ch === '/' && ($text[$i + 1] ?? '') === '*') {
                $close = strpos($text, '*/', $i + 2);
                $stop = $close === false ? $length : $close + 2;
                $out .= $blank(substr($text, $i, $stop - $i));
                $i = $stop - 1;
            } elseif ($kind === 'js' && $ch === '/' && ($text[$i + 1] ?? '') === '/') {
                $newline = strpos($text, "\n", $i);
                $end = $newline === false ? $length : $newline;
                $out .= $blank(substr($text, $i, $end - $i));
                $i = $end - 1;
            } else {
                $out .= $ch;
            }
            if (!str_contains(self::BLANKS, $ch)) {
                $previous = $ch;
            }
        }
        return $out;
    }

    /**
     * @param string $value
     * @return string
     */
    private static function decodeEntities(string $value): string
    {
        return strtr($value, ['&amp;' => '&', '&lt;' => '<', '&gt;' => '>', '&quot;' => '"', '&#39;' => "'", '&apos;' => "'"]);
    }

    /**
     * La línea (desde 1) de un desplazamiento en bytes.
     *
     * @param string $text
     * @param int $offset
     * @return int
     */
    private static function lineOf(string $text, int $offset): int
    {
        return substr_count(substr($text, 0, $offset), "\n") + 1;
    }

    /**
     * Las coincidencias del primer grupo, con su desplazamiento.
     *
     * @param string $pattern
     * @param string $text
     * @return array<int,array{0:int,1:string}>
     */
    private static function matches(string $pattern, string $text): array
    {
        if (preg_match_all($pattern, $text, $all, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE) === false) {
            return [];
        }
        return array_map(fn (array $match): array => [(int) $match[0][1], (string) $match[1][0]], $all);
    }
}
