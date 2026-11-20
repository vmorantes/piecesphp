<?php

/**
 * FormActionFields.php
 */

namespace Terminal;

/**
 * FormActionFields - Lo que envía cada formulario de las vistas contra lo que lee la acción que lo recibe (comprobación 70, H423).
 *
 * Lectura estática: los `<form>` de las vistas de `src/app`, su acción resuelta por el nombre de la ruta (en la vista o en
 * el controlador que la pinta) y los parámetros que lee el método de esa ruta. Nace de H421: el formulario de Publicaciones
 * enviaba `status` y la acción leía `draft`.
 *
 * Lo que NO ve: los campos que añade el JavaScript, los de una vista parcial incluida dentro del formulario, los nombres que
 * no se resuelven a un literal (se cuentan como dinámicos, sin juzgar) y las lecturas fuera del método de la ruta y de los
 * métodos de su clase a los que llama (dos niveles).
 *
 * @package     Terminal
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class FormActionFields
{

    /**
     * Las excepciones, relativas a la raíz del repositorio.
     *
     * @var string
     */
    const DECLARED_RELATIVE_PATH = 'files/dev/form-action-fields.json';

    /**
     * Lo que no es una vista del producto: las suites y el manual, que genera `bin/manual`.
     *
     * @var string[]
     */
    const EXCLUDED_PREFIXES = ['src/app/core/system-controllers/local-tests/', 'src/app/classes/MySpace/Views/manual/'];

    /**
     * Las secciones del archivo de excepciones.
     *
     * @var string[]
     */
    const SECTIONS = ['fuera', 'accion-declarada', 'enviados-sin-lector', 'leidos-sin-envio'];

    /**
     * Profundidad de los métodos de la misma clase que se siguen desde el de la ruta.
     *
     * @var int
     */
    const READ_DEPTH = 2;

    /**
     * Los formularios de las vistas, cada uno con su acción y su cruce.
     *
     * @param string $root Raíz del repositorio.
     * @param array<string,string> $overrides Ruta relativa => código que sustituye (o añade) una vista.
     * @param callable|null $routeOff fn(string $route): bool, si la ruta es de un módulo apagado.
     * @return array{forms: list<array<string,mixed>>, views: int}
     */
    public static function census(string $root, array $overrides = [], ?callable $routeOff = null): array
    {
        $views = [];
        $listed = self::nulSeparated('git -C ' . escapeshellarg($root) . ' -c core.quotePath=false ls-files -z --cached --others --exclude-standard -- src/app');
        foreach (array_merge($listed, array_keys($overrides)) as $relative) {
            if (!str_ends_with($relative, '.php') || str_starts_with($relative, 'src/vendor/') || str_contains($relative, '/vendor/')) {
                continue;
            }
            foreach (self::EXCLUDED_PREFIXES as $prefix) {
                if (str_starts_with($relative, $prefix)) {
                    continue 2;
                }
            }
            $code = $overrides[$relative] ?? (is_file($root . '/' . $relative) ? (string) file_get_contents($root . '/' . $relative) : '');
            if (preg_match('/<form\b/i', $code) === 1) {
                $views[$relative] = $code;
            }
        }
        ksort($views);

        $index = self::renderIndex($root, $listed);
        $routes = function_exists('get_routes') && is_array(get_routes()) ? get_routes() : [];
        $forms = [];
        foreach ($views as $relative => $code) {
            $parsed = self::parseView($code);
            foreach (self::formsIn($parsed) as $n => $form) {
                $key = $relative . '#' . ($n + 1);
                $entry = ['key' => $key, 'file' => $relative, 'line' => $form['line'], 'sent' => $form['sent'], 'dynamic' => $form['dynamic'], 'targets' => [], 'out' => null, 'expression' => $form['action']];
                if (count($form['sent']) === 0 && count($form['dynamic']) === 0) {
                    $entry['out'] = 'sin name';
                } elseif ($form['action'] === null) {
                    $entry['out'] = 'sin action';
                } else {
                    $targets = self::resolveViewTarget($form['action'], $form['segment'], $parsed, $relative, $index);
                    if (count($targets) === 0) {
                        $entry['out'] = 'acción sin resolver';
                    }
                    foreach ($targets as $route) {
                        if ($routeOff !== null && $routeOff($route) === true) {
                            $entry['out'] = 'módulo apagado';
                            continue;
                        }
                        $entry['targets'][$route] = self::handlerReads($routes[$route]['controller'] ?? null);
                    }
                    if (count($targets) > 0 && count($entry['targets']) === 0 && $entry['out'] === null) {
                        $entry['out'] = 'ruta sin registrar';
                    }
                }
                $forms[] = $entry;
            }
        }

        return ['forms' => $forms, 'views' => count($views)];
    }

    /**
     * Compara el censo con las excepciones declaradas.
     *
     * @param list<array<string,mixed>> $forms
     * @param array<string,mixed> $declared
     * @param array<string,mixed> $routes El registro de rutas, para resolver las acciones declaradas a mano.
     * @return array{failures: string[], stats: array<string,int>, outReasons: array<string,int>}
     */
    public static function compare(array $forms, array $declared, array $routes = []): array
    {
        $failures = [];
        $out = is_array($declared['fuera'] ?? null) ? $declared['fuera'] : [];
        $manual = is_array($declared['accion-declarada'] ?? null) ? $declared['accion-declarada'] : [];
        $sentDeclared = is_array($declared['enviados-sin-lector'] ?? null) ? $declared['enviados-sin-lector'] : [];
        $readDeclared = is_array($declared['leidos-sin-envio'] ?? null) ? $declared['leidos-sin-envio'] : [];
        $used = ['fuera' => [], 'accion-declarada' => [], 'enviados-sin-lector' => [], 'leidos-sin-envio' => []];
        $stats = ['forms' => count($forms), 'pairs' => 0, 'crossedForms' => 0, 'sent' => 0, 'read' => 0, 'out' => 0, 'off' => 0, 'dynamic' => 0, 'sentUnread' => 0, 'readUnsent' => 0];
        $outReasons = [];
        $offKeys = [];

        foreach ($forms as $form) {
            $key = (string) $form['key'];
            $targets = $form['targets'];
            if ($form['out'] === 'módulo apagado' && count($targets) === 0) {
                $offKeys[$key] = true;
                $stats['off']++;
                continue;
            }
            if ($form['out'] !== null && count($targets) === 0 && array_key_exists($key, $manual)) {
                $used['accion-declarada'][$key] = true;
                $route = is_array($manual[$key]) ? (string) ($manual[$key]['ruta'] ?? '') : '';
                if (!array_key_exists($route, $routes)) {
                    $failures[] = self::DECLARED_RELATIVE_PATH . ": accion-declarada `{$key}` nombra la ruta `{$route}`, que no está registrada";
                    continue;
                }
                $targets = [$route => self::handlerReads($routes[$route]['controller'] ?? null)];
            } elseif (array_key_exists($key, $manual)) {
                $used['accion-declarada'][$key] = true;
                $failures[] = self::DECLARED_RELATIVE_PATH . ": accion-declarada `{$key}` sobra: la acción se resuelve sola o el formulario está fuera; quítala";
            }

            //Un formulario cruzable también se puede sacar declarándolo en `fuera`: cuenta aparte, con su motivo.
            if (count($targets) > 0 && array_key_exists($key, $out) && !array_key_exists($key, $manual)) {
                $targets = [];
                $form['out'] = 'excluido por declaración';
            }
            if (count($targets) === 0) {
                $stats['out']++;
                $reason = (string) $form['out'];
                $outReasons[$reason] = ($outReasons[$reason] ?? 0) + 1;
                if (array_key_exists($key, $out)) {
                    $used['fuera'][$key] = true;
                } else {
                    $failures[] = "{$form['file']}:{$form['line']}: formulario fuera del cruce ({$reason}) sin declarar; declara `{$key}` en `fuera` de " . self::DECLARED_RELATIVE_PATH . ' con su motivo, o haz que su acción se resuelva';
                }
                continue;
            }

            $stats['crossedForms']++;
            $stats['dynamic'] += count($form['dynamic']);
            foreach ($targets as $route => $reads) {
                $stats['pairs']++;
                $pair = "{$key} → {$route}";
                $sent = $form['sent'];
                $stats['sent'] += count($sent);
                $stats['read'] += count($reads);
                $declaredSent = is_array($sentDeclared[$pair] ?? null) ? $sentDeclared[$pair] : [];
                $declaredRead = is_array($readDeclared[$pair] ?? null) ? $readDeclared[$pair] : [];
                foreach (self::crossProblems($sent, $reads) as $problem) {
                    [$kind, $field] = $problem;
                    if ($kind === 'enviado') {
                        $stats['sentUnread']++;
                        if (array_key_exists($field, $declaredSent)) {
                            $used['enviados-sin-lector'][$pair . ' · ' . $field] = true;
                        } else {
                            $failures[] = "{$form['file']}:{$form['line']}: envía `{$field}` y la acción de `{$route}` no lo lee; si es legítimo, decláralo en `enviados-sin-lector` · `{$pair}`";
                        }
                    } else {
                        $stats['readUnsent']++;
                        if (array_key_exists($field, $declaredRead)) {
                            $used['leidos-sin-envio'][$pair . ' · ' . $field] = true;
                        } else {
                            $failures[] = "{$form['file']}:{$form['line']}: la acción de `{$route}` lee `{$field}` y el formulario no lo envía; si es legítimo, decláralo en `leidos-sin-envio` · `{$pair}`";
                        }
                    }
                }
            }
        }

        $isOff = static function (string $key) use ($offKeys): bool {
            return array_key_exists(explode(' → ', $key)[0], $offKeys);
        };
        foreach (['fuera' => $out, 'accion-declarada' => $manual] as $section => $entries) {
            foreach (array_keys($entries) as $key) {
                if (!array_key_exists((string) $key, $used[$section]) && !$isOff((string) $key)) {
                    $failures[] = self::DECLARED_RELATIVE_PATH . ": `{$section}` · `{$key}` ya no casa con ningún formulario fuera del cruce; quítala";
                }
            }
        }
        foreach (['enviados-sin-lector' => $sentDeclared, 'leidos-sin-envio' => $readDeclared] as $section => $entries) {
            foreach ($entries as $pair => $fields) {
                foreach (array_keys(is_array($fields) ? $fields : []) as $field) {
                    if (!array_key_exists($pair . ' · ' . $field, $used[$section]) && !$isOff((string) $pair)) {
                        $failures[] = self::DECLARED_RELATIVE_PATH . ": `{$section}` · `{$pair}` · `{$field}` ya no casa con nada; quítala";
                    }
                }
            }
        }

        return ['failures' => $failures, 'stats' => $stats, 'outReasons' => $outReasons];
    }

    /**
     * Los dos lados del cruce: lo enviado que nadie lee y lo leído que nadie envía.
     *
     * @param string[] $sent
     * @param string[] $read
     * @return list<array{0: string, 1: string}>
     */
    public static function crossProblems(array $sent, array $read): array
    {
        $problems = [];
        foreach (array_values(array_diff($sent, $read)) as $field) {
            $problems[] = ['enviado', $field];
        }
        foreach (array_values(array_diff($read, $sent)) as $field) {
            $problems[] = ['leido', $field];
        }
        return $problems;
    }

    /**
     * El canario: un formulario y una acción sintéticos con las dos mitades de H421. Devuelve el problema, o null si ve.
     *
     * @return string|null
     */
    public static function canary(): ?string
    {
        $view = "<?php \$fieldName = 'status'; ?>\n<form method=\"POST\" action=\"<?= \$action; ?>\">\n"
            . "<input type=\"hidden\" name=\"id\" value=\"1\">\n<input type=\"checkbox\" name=\"<?= \$fieldName; ?>\" value=\"yes\">\n"
            . "<select name=\"tags[]\"></select>\n</form>\n";
        $body = "<?php\n\$p = new Parameters([new Parameter('id', -1), new Parameter(\n 'draft', null), new Parameter('tags', [])]);\n"
            . "//new Parameter('comentada', 1)\n\$x = \$request->getParsedBodyParam('lang');\n\$in = \$request->getParsedBody();\n\$y = \$in['extra'] ?? null;\n";
        $forms = self::formsIn(self::parseView($view));
        if (count($forms) !== 1 || $forms[0]['sent'] !== ['id', 'status', 'tags'] || $forms[0]['action'] === null) {
            return 'el canario no ve los campos del formulario sintético (id, status por variable, tags[]): ' . json_encode($forms[0]['sent'] ?? null);
        }
        $reads = self::readsIn($body);
        if ($reads !== ['draft', 'extra', 'id', 'lang', 'tags']) {
            return 'el canario no ve las lecturas sintéticas (Parameter, getParsedBodyParam, getParsedBody()[…]) o ve una comentada: ' . json_encode($reads);
        }
        $problems = self::crossProblems($forms[0]['sent'], $reads);
        if (!in_array(['enviado', 'status'], $problems, true) || !in_array(['leido', 'draft'], $problems, true)) {
            return 'el canario no cae por sus dos mitades (status enviado sin lector, draft leído sin envío): ' . json_encode($problems);
        }
        return null;
    }

    /**
     * La vista partida en HTML con marcadores y sus trozos de PHP, para que un `>` del PHP no corte una etiqueta.
     *
     * @param string $code
     * @return array{html: string, segments: list<array{code: string, assigns: list<array{0: string, 1: string}>}>}
     */
    public static function parseView(string $code): array
    {
        $html = '';
        $segments = [];
        $current = null;
        foreach (token_get_all($code) as $token) {
            $id = is_array($token) ? $token[0] : null;
            $text = is_array($token) ? $token[1] : $token;
            if ($current === null) {
                if ($id === T_INLINE_HTML) {
                    $html .= $text;
                    continue;
                }
                $current = '';
            }
            if ($id === T_OPEN_TAG || $id === T_OPEN_TAG_WITH_ECHO) {
                $current = str_repeat("\n", substr_count($text, "\n"));
                continue;
            }
            if ($id === T_CLOSE_TAG) {
                $html .= self::closeSegment($segments, $current, substr_count($text, "\n"));
                $current = null;
                continue;
            }
            $current .= ($id === T_COMMENT || $id === T_DOC_COMMENT) ? str_repeat("\n", substr_count($text, "\n")) : $text;
        }
        if ($current !== null) {
            $html .= self::closeSegment($segments, $current, 0);
        }
        return ['html' => $html, 'segments' => $segments];
    }

    /**
     * Cierra un trozo de PHP: lo guarda con sus asignaciones y devuelve su marcador, con sus saltos para no mover las líneas.
     *
     * @param list<array{code: string, assigns: list<array{0: string, 1: string}>}> $segments
     * @param string $code
     * @param int $extraLines
     * @return string
     */
    protected static function closeSegment(array &$segments, string $code, int $extraLines): string
    {
        $assigns = [];
        if (preg_match_all('/\$(\w+)\s*=(?![=>])\s*/', $code, $matches, PREG_OFFSET_CAPTURE) > 0) {
            foreach ($matches[1] as $i => $match) {
                $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
                $assigns[] = [$match[0], self::expressionAt($code, $start)];
            }
        }
        $segments[] = ['code' => $code, 'assigns' => $assigns];
        return "\x01" . (count($segments) - 1) . "\x02" . str_repeat("\n", substr_count($code, "\n") + $extraLines);
    }

    /**
     * La expresión que empieza en `$start`, hasta la coma, el cierre o el punto y coma de su nivel.
     *
     * @param string $code
     * @param int $start
     * @return string
     */
    protected static function expressionAt(string $code, int $start): string
    {
        $depth = 0;
        $quote = null;
        $length = strlen($code);
        for ($i = $start; $i < $length; $i++) {
            $char = $code[$i];
            if ($quote !== null) {
                if ($char === '\\') {
                    $i++;
                } elseif ($char === $quote) {
                    $quote = null;
                }
                continue;
            }
            if ($char === '"' || $char === "'") {
                $quote = $char;
            } elseif ($char === '(' || $char === '[' || $char === '{') {
                $depth++;
            } elseif ($char === ')' || $char === ']' || $char === '}') {
                if ($depth === 0) {
                    break;
                }
                $depth--;
            } elseif (($char === ',' || $char === ';') && $depth === 0) {
                break;
            }
        }
        return trim(substr($code, $start, $i - $start));
    }

    /**
     * Los formularios de una vista: su línea, la expresión de su action, el trozo donde está y los `name` que envía.
     *
     * @param array{html: string, segments: list<array{code: string, assigns: list<array{0: string, 1: string}>}>} $parsed
     * @return list<array{line: int, action: string|null, segment: int, sent: string[], dynamic: string[]}>
     */
    public static function formsIn(array $parsed): array
    {
        $html = $parsed['html'];
        $forms = [];
        if (preg_match_all('/<form\b([^>]*)>/i', $html, $matches, PREG_OFFSET_CAPTURE) === 0) {
            return [];
        }
        foreach ($matches[0] as $i => $match) {
            $start = $match[1];
            $bodyStart = $start + strlen($match[0]);
            $end = strlen($html);
            if (preg_match('/<\/form\s*>|<form\b/i', $html, $close, PREG_OFFSET_CAPTURE, $bodyStart) === 1) {
                $end = $close[0][1];
            }
            $attributes = self::attributes($matches[1][$i][0]);
            $action = $attributes['action'] ?? null;
            $segmentBefore = self::lastSegmentBefore($html, $start);
            $actionExpression = null;
            if ($action !== null && preg_match('/^\s*\x01(\d+)\x02\s*$/', $action, $placeholder) === 1) {
                $actionExpression = rtrim(trim($parsed['segments'][(int) $placeholder[1]]['code']), ';');
            } elseif ($action !== null && !in_array(trim($action), ['', '.', '#'], true) && !str_contains($action, "\x01")) {
                $actionExpression = var_export(trim($action), true);
            }
            $sent = [];
            $dynamic = [];
            $body = substr($html, $bodyStart, $end - $bodyStart);
            if (preg_match_all('/<(?:input|select|textarea|button)\b([^>]*)>/i', $body, $fields) > 0) {
                foreach ($fields[1] as $fieldAttributes) {
                    $name = self::attributes($fieldAttributes)['name'] ?? null;
                    if ($name === null || trim($name) === '') {
                        continue;
                    }
                    $resolved = self::resolveName($name, $parsed);
                    if ($resolved === null) {
                        $dynamic[] = $name;
                        continue;
                    }
                    $base = explode('[', $resolved, 2)[0];
                    if ($base !== '') {
                        $sent[$base] = true;
                    }
                }
            }
            //Lo que pinta una vista parcial o un cierre de la propia vista con el name por argumento: `'inputName' => 'x'`, `$campo('x'`.
            if (preg_match_all('/\x01(\d+)\x02/', $body, $inner) > 0) {
                foreach ($inner[1] as $segment) {
                    $code = $parsed['segments'][(int) $segment]['code'];
                    $found = preg_match_all('/[\'"]inputName[\'"]\s*=>\s*\'([\w-]+)/', $code, $byKey) > 0 ? $byKey[1] : [];
                    foreach (self::fieldClosures($parsed) as $closure) {
                        if (preg_match_all('/\(?\$' . preg_quote($closure, '/') . '\)?\(\s*\'([\w-]+)/', $code, $byCall) > 0) {
                            $found = array_merge($found, $byCall[1]);
                        }
                    }
                    foreach ($found as $name) {
                        $sent[$name] = true;
                    }
                }
            }
            $sentNames = array_keys($sent);
            sort($sentNames);
            $forms[] = ['line' => substr_count(substr($html, 0, $start), "\n") + 1, 'action' => $actionExpression, 'segment' => $segmentBefore, 'sent' => $sentNames, 'dynamic' => $dynamic];
        }
        return $forms;
    }

    /**
     * Los cierres de la vista que pintan un campo con el name de su primer argumento.
     *
     * @param array{html: string, segments: list<array{code: string, assigns: list<array{0: string, 1: string}>}>} $parsed
     * @return string[]
     */
    protected static function fieldClosures(array $parsed): array
    {
        $nameVariables = [];
        if (preg_match_all('/name="\x01(\d+)\x02"/', $parsed['html'], $echoed) > 0) {
            foreach ($echoed[1] as $segment) {
                if (preg_match('/^\s*\$(\w+)\s*;?\s*$/', $parsed['segments'][(int) $segment]['code'], $variable) === 1) {
                    $nameVariables[$variable[1]] = true;
                }
            }
        }
        $code = implode("\n", array_column($parsed['segments'], 'code'));
        if (preg_match_all('/(?:name=\\\\?"\'\s*\.\s*(?:escape_html\(|\$escape\()?|[\'"]inputName[\'"]\s*=>\s*)\$(\w+)/', $code, $concatenated) > 0) {
            foreach ($concatenated[1] as $variable) {
                $nameVariables[$variable] = true;
            }
        }
        $closures = [];
        if (preg_match_all('/\$(\w+)\s*=\s*(?:static\s+)?(?:function|fn)\s*\(\s*(?:\??[\\\\\w]+\s+)?\$(\w+)/', $code, $definitions, PREG_SET_ORDER) > 0) {
            foreach ($definitions as $definition) {
                if (array_key_exists($definition[2], $nameVariables)) {
                    $closures[] = $definition[1];
                }
            }
        }
        return $closures;
    }

    /**
     * El índice del último trozo de PHP antes de una posición del HTML.
     *
     * @param string $html
     * @param int $position
     * @return int
     */
    protected static function lastSegmentBefore(string $html, int $position): int
    {
        $last = -1;
        if (preg_match_all('/\x01(\d+)\x02/', substr($html, 0, $position), $found) > 0) {
            $last = (int) end($found[1]);
        }
        return $last;
    }

    /**
     * Los atributos de una etiqueta; el PHP ya va en marcadores, así que las comillas no engañan.
     *
     * @param string $text
     * @return array<string,string>
     */
    protected static function attributes(string $text): array
    {
        $attributes = [];
        if (preg_match_all('/([\w:-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>"\']+))/', $text, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $name = mb_strtolower($match[1]);
                if (!array_key_exists($name, $attributes)) {
                    $attributes[$name] = ($match[2] ?? '') . ($match[3] ?? '') . ($match[4] ?? '');
                }
            }
        }
        return $attributes;
    }

    /**
     * La parte de un `name` antes del primer corchete, resuelta si cada marcador es un literal, una constante de clase o una
     * variable con su última asignación anterior que se resuelve igual.
     *
     * @param string $name
     * @param array{html: string, segments: list<array{code: string, assigns: list<array{0: string, 1: string}>}>} $parsed
     * @return string|null
     */
    protected static function resolveName(string $name, array $parsed): ?string
    {
        $uses = self::usesIn(implode("\n", array_column($parsed['segments'], 'code')));
        $unresolved = false;
        $resolved = preg_replace_callback('/\x01(\d+)\x02/', function (array $match) use ($parsed, $uses, &$unresolved): string {
            $segment = (int) $match[1];
            $value = self::resolveValue(rtrim(trim($parsed['segments'][$segment]['code']), ';'), $parsed, $segment, $uses, 0);
            if ($value === null) {
                $unresolved = true;
                return '';
            }
            return $value;
        }, explode('[', $name, 2)[0]);
        return $unresolved || $resolved === null ? null : trim($resolved);
    }

    /**
     * El valor de cadena de una expresión de la vista: literal, constante de clase o variable asignada antes a uno de ellos.
     *
     * @param string $code
     * @param array{html: string, segments: list<array{code: string, assigns: list<array{0: string, 1: string}>}>} $parsed
     * @param int $segment
     * @param array<string,string> $uses
     * @param int $depth
     * @return string|null
     */
    protected static function resolveValue(string $code, array $parsed, int $segment, array $uses, int $depth): ?string
    {
        $literal = self::literal($code);
        if ($literal !== null) {
            return $literal;
        }
        if (preg_match('/^\s*([\\\\\w]+)::([A-Z][A-Z0-9_]*)\s*$/', $code, $constant) === 1) {
            $name = self::fullClass($constant[1], $uses) . '::' . $constant[2];
            $value = defined($name) ? constant($name) : null;
            return is_string($value) || is_int($value) ? (string) $value : null;
        }
        if ($depth < 3 && preg_match('/^(?:[\\\\\w]+\(|\$escape\()?\s*\$(\w+)\s*(?:\)|,[^;]*\))?$/', trim($code), $variable) === 1) {
            $assigned = self::lastAssignment($parsed, $variable[1], $segment);
            return $assigned !== null ? self::resolveValue($assigned, $parsed, $segment, $uses, $depth + 1) : null;
        }
        return null;
    }

    /**
     * La última expresión asignada a una variable en los trozos de PHP anteriores a uno dado.
     *
     * @param array{html: string, segments: list<array{code: string, assigns: list<array{0: string, 1: string}>}>} $parsed
     * @param string $variable
     * @param int $before
     * @return string|null
     */
    protected static function lastAssignment(array $parsed, string $variable, int $before): ?string
    {
        $found = null;
        foreach ($parsed['segments'] as $i => $segment) {
            if ($i >= $before) {
                break;
            }
            foreach ($segment['assigns'] as $assign) {
                if ($assign[0] === $variable) {
                    $found = $assign[1];
                }
            }
        }
        return $found;
    }

    /**
     * El valor de un literal de cadena simple, o null.
     *
     * @param string $code
     * @return string|null
     */
    protected static function literal(string $code): ?string
    {
        if (preg_match('/^\s*\'([^\'\\\\]*)\'\s*$/', $code, $match) === 1 || preg_match('/^\s*"([^"\\\\$]*)"\s*$/', $code, $match) === 1) {
            return $match[1];
        }
        return null;
    }

    /**
     * Las rutas a las que apunta la action de un formulario: en la propia vista o en el controlador que la pinta.
     *
     * @param string $expression
     * @param int $segment
     * @param array{html: string, segments: list<array{code: string, assigns: list<array{0: string, 1: string}>}>} $parsed
     * @param string $relative
     * @param list<array<string,mixed>> $index
     * @return string[]
     */
    protected static function resolveViewTarget(string $expression, int $segment, array $parsed, string $relative, array $index): array
    {
        $uses = self::usesIn(implode("\n", array_column($parsed['segments'], 'code')));
        $assign = static fn (string $variable): array => array_values(array_filter([self::lastAssignment($parsed, $variable, $segment + 1)]));
        $direct = self::resolveExpression($expression, null, $uses, $assign, 0);
        if ($direct !== null) {
            return $direct;
        }
        if (preg_match('/^(?:[\\\\\w]+\(|\$escape\()?\s*\$(\w+)\s*(?:\)|,[^;]*\))?$/', trim($expression), $variable) !== 1) {
            return [];
        }
        return self::resolveFromRenderers($variable[1], $relative, $index, 0);
    }

    /**
     * Las rutas que recibe la variable de una vista desde quien la pinta: un controlador del mismo módulo que la asigna,
     * o una vista que la incluye (y entonces, desde quien pinta a esa).
     *
     * @param string $name
     * @param string $relative
     * @param list<array<string,mixed>> $index
     * @param int $depth
     * @return string[]
     */
    protected static function resolveFromRenderers(string $name, string $relative, array $index, int $depth): array
    {
        $moduleRoot = str_contains($relative, '/Views/') ? explode('/Views/', $relative, 2)[0] . '/' : 'src/app/';
        $viewPath = mb_substr($relative, 0, -4);
        $targets = [];
        $matching = array_filter($index, static fn (array $render): bool => (string) $render['file'] !== $relative && str_starts_with((string) $render['file'], $moduleRoot)
            && str_ends_with($viewPath, '/' . ltrim((string) $render['view'], '/')));
        //Gana el pintado más largo: `news/forms/add` de quien declara BASE_VIEW_DIR frente al `forms/add` de otro controlador del módulo.
        $longest = array_reduce($matching, static fn (int $carry, array $render): int => max($carry, strlen(ltrim((string) $render['view'], '/'))), 0);
        foreach ($matching as $render) {
            if (strlen(ltrim((string) $render['view'], '/')) !== $longest) {
                continue;
            }
            if ($render['class'] === null) {
                foreach ($depth < 2 ? self::resolveFromRenderers($name, (string) $render['file'], $index, $depth + 1) : [] as $route) {
                    $targets[$route] = true;
                }
                continue;
            }
            $body = (string) $render['body'];
            $pattern = '/(?:[\'"]' . preg_quote($name, '/') . '[\'"]\s*=>|\[\s*[\'"]' . preg_quote($name, '/') . '[\'"]\s*\]\s*=(?![=>]))\s*/';
            if (preg_match_all($pattern, $body, $matches, PREG_OFFSET_CAPTURE) === 0) {
                continue;
            }
            $bodyAssign = static function (string $variable) use ($body): array {
                $found = [];
                if (preg_match_all('/\$' . preg_quote($variable, '/') . '\s*=(?![=>])\s*/', $body, $assignments, PREG_OFFSET_CAPTURE) > 0) {
                    foreach ($assignments[0] as $assignment) {
                        $found[] = self::expressionAt($body, $assignment[1] + strlen($assignment[0]));
                    }
                }
                return $found;
            };
            foreach ($matches[0] as $match) {
                $resolved = self::resolveExpression(self::expressionAt($body, $match[1] + strlen($match[0])), (string) $render['class'], $render['uses'], $bodyAssign, 0);
                foreach ($resolved ?? [] as $route) {
                    $targets[$route] = true;
                }
            }
        }
        $routes = array_keys($targets);
        sort($routes);
        return $routes;
    }

    /**
     * Las rutas que nombra una expresión: `X::routeName('s')`, `get_route('n')` o una variable que se asigna a una de ellas.
     *
     * @param string $expression
     * @param string|null $class La clase de `self` y `static`.
     * @param array<string,string> $uses
     * @param callable $assign fn(string $variable): string[]
     * @param int $depth
     * @return string[]|null
     */
    protected static function resolveExpression(string $expression, ?string $class, array $uses, callable $assign, int $depth): ?array
    {
        if (preg_match('/([\\\\\w]+)::routeName\(\s*[^,()]*\?\s*([\'"])([^\'"]*)\2\s*:\s*([\'"])([^\'"]*)\4\s*[,)]/', $expression, $match) === 1) {
            $owner = in_array(mb_strtolower($match[1]), ['self', 'static'], true) ? $class : self::fullClass($match[1], $uses);
            $base = $owner !== null ? self::baseRouteName($owner) : null;
            return $base === null || str_contains($match[3] . $match[5], '$') ? null : [$base . '-' . $match[3], $base . '-' . $match[5]];
        }
        if (preg_match('/([\\\\\w]+)::routeName\(\s*(?:([\'"])([^\'"]*)\2)?\s*[,)]/', $expression, $match) === 1) {
            $owner = in_array(mb_strtolower($match[1]), ['self', 'static'], true) ? $class : self::fullClass($match[1], $uses);
            $base = $owner !== null ? self::baseRouteName($owner) : null;
            if ($base === null) {
                return null;
            }
            $suffix = trim($match[3] ?? '');
            if (str_contains($suffix, '$')) {
                return null;
            }
            return [$suffix !== '' ? $base . '-' . $suffix : $base];
        }
        if (preg_match('/get_route\(\s*([\'"])([^\'"]+)\1/', $expression, $match) === 1) {
            return [$match[2]];
        }
        if ($depth < 4 && preg_match('/^(?:[\\\\\w]+\(|\$escape\()?\s*\$(\w+)\s*(?:\)|,[^;]*\))?$/', trim($expression), $variable) === 1) {
            $routes = [];
            foreach ($assign($variable[1]) as $assigned) {
                foreach (self::resolveExpression((string) $assigned, $class, $uses, $assign, $depth + 1) ?? [] as $route) {
                    $routes[$route] = true;
                }
            }
            return count($routes) > 0 ? array_keys($routes) : null;
        }
        return null;
    }

    /**
     * El `$baseRouteName` de un controlador, o null si no lo tiene.
     *
     * @param string $class
     * @return string|null
     */
    protected static function baseRouteName(string $class): ?string
    {
        if (!class_exists($class)) {
            return null;
        }
        $reflection = new \ReflectionClass($class);
        if (!$reflection->hasProperty('baseRouteName')) {
            return null;
        }
        $value = $reflection->getProperty('baseRouteName')->getValue();
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * El nombre completo de una clase según los `use` del archivo.
     *
     * @param string $name
     * @param array<string,string> $uses
     * @return string
     */
    protected static function fullClass(string $name, array $uses): string
    {
        if (str_starts_with($name, '\\')) {
            return ltrim($name, '\\');
        }
        $first = explode('\\', $name, 2);
        if (array_key_exists($first[0], $uses)) {
            return $uses[$first[0]] . (isset($first[1]) ? '\\' . $first[1] : '');
        }
        $namespaced = ($uses[''] ?? '') . $name;
        return ($uses[''] ?? '') !== '' && class_exists($namespaced) ? $namespaced : $name;
    }

    /**
     * Los `use` de un archivo: alias => clase.
     *
     * @param string $code
     * @return array<string,string>
     */
    protected static function usesIn(string $code): array
    {
        $uses = [];
        if (preg_match_all('/^\s*use\s+([\\\\\w]+)(?:\s+as\s+(\w+))?\s*;/m', $code, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $class = ltrim($match[1], '\\');
                $alias = ($match[2] ?? '') !== '' ? $match[2] : substr((string) strrchr('\\' . $class, '\\'), 1);
                $uses[$alias] = $class;
            }
        }
        return $uses;
    }

    /**
     * Cada pintado de vista de los PHP que no son vistas: la vista, el cuerpo del método, su clase y sus `use`.
     *
     * @param string $root
     * @param string[] $listed
     * @return list<array<string,mixed>>
     */
    protected static function renderIndex(string $root, array $listed): array
    {
        $index = [];
        foreach ($listed as $relative) {
            if (!str_ends_with($relative, '.php') || str_contains($relative, '/vendor/')) {
                continue;
            }
            foreach (self::EXCLUDED_PREFIXES as $prefix) {
                if (str_starts_with($relative, $prefix)) {
                    continue 2;
                }
            }
            $code = is_file($root . '/' . $relative) ? (string) file_get_contents($root . '/' . $relative) : '';
            if (preg_match('/[\'"][\w.\/-]*\/[\w.\/-]*[\'"]|[rR]ender\(/', $code) !== 1) {
                continue;
            }
            $isView = str_contains($relative, '/Views/') || str_starts_with($relative, 'src/app/view/');
            $namespace = preg_match('/^\s*namespace\s+([\\\\\w]+)\s*;/m', $code, $ns) === 1 ? $ns[1] . '\\' : '';
            $class = preg_match('/^\s*(?:(?:final|abstract)\s+)*class\s+(\w+)/m', $code, $cl) === 1 ? $namespace . $cl[1] : null;
            $uses = self::usesIn($code) + ['' => $namespace];
            $baseViewDir = preg_match('/\bconst\s+BASE_VIEW_DIR\s*=\s*\'([\w\/.-]+)\'\s*;/', $code, $bvd) === 1 ? trim($bvd[1], '/') . '/' : '';
            foreach ($isView ? ['' => $code] : self::methodBodies($code) as $body) {
                $views = [];
                //Un literal con barra que casa con la ruta de la vista cuenta como pintado: `render('x/y')`, `localRender($p . '/x')`, un mapa de vistas.
                if (preg_match_all('/\'([\w.-]*\/[\w.\/-]*)\'/', $body, $literals) > 0) {
                    foreach ($literals[1] as $literal) {
                        $views[$literal] = true;
                    }
                }
                if (preg_match_all('/(?:->|::)\w*[rR]ender\(\s*\'([\w.\/-]+)\'/', $body, $plain) > 0) {
                    foreach ($plain[1] as $literal) {
                        $views[$literal] = true;
                    }
                }
                if (preg_match_all('/(?:->|::)\w*[rR]ender\(\s*("(?:[^"\\\\]|\\\\.)*")/', $body, $renders) > 0) {
                    foreach ($renders[1] as $argument) {
                        $view = self::renderArgument($argument, $body);
                        if ($view !== null) {
                            $views[$view] = true;
                        }
                    }
                }
                foreach (array_keys($views) as $view) {
                    $index[] = ['file' => $relative, 'view' => (string) $view, 'body' => $body, 'class' => $isView ? null : $class, 'uses' => $uses];
                    if ($baseViewDir !== '') {
                        $index[] = ['file' => $relative, 'view' => $baseViewDir . ltrim((string) $view, '/'), 'body' => $body, 'class' => $isView ? null : $class, 'uses' => $uses];
                    }
                }
            }
        }
        return $index;
    }

    /**
     * El primer argumento de un pintado, con las variables interpoladas que se asignan a un literal en el mismo método.
     *
     * @param string $argument
     * @param string $body
     * @return string|null
     */
    protected static function renderArgument(string $argument, string $body): ?string
    {
        if ($argument[0] === "'") {
            return substr($argument, 1, -1);
        }
        $unresolved = false;
        $value = preg_replace_callback('/\{?\$(\w+)\}?/', function (array $match) use ($body, &$unresolved): string {
            if (preg_match('/\$' . preg_quote($match[1], '/') . '\s*=\s*([\'"])([^\'"$]*)\1\s*;/', $body, $literal) === 1) {
                return $literal[2];
            }
            $unresolved = true;
            return '';
        }, substr($argument, 1, -1));
        return $unresolved || $value === null ? null : $value;
    }

    /**
     * Los cuerpos de los métodos de un archivo, con sus cierres anónimos dentro.
     *
     * @param string $code
     * @return array<string,string> Nombre => cuerpo.
     */
    protected static function methodBodies(string $code): array
    {
        $tokens = token_get_all($code);
        $bodies = [];
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
                continue;
            }
            $j = $i + 1;
            while ($j < $count && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT], true)) {
                $j++;
            }
            if ($j >= $count || !is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) {
                continue;
            }
            $name = $tokens[$j][1];
            while ($j < $count && $tokens[$j] !== '{' && $tokens[$j] !== ';') {
                $j++;
            }
            if ($j >= $count || $tokens[$j] === ';') {
                continue;
            }
            $depth = 0;
            $body = '';
            for ($k = $j; $k < $count; $k++) {
                $text = is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
                $opens = $tokens[$k] === '{' || (is_array($tokens[$k]) && in_array($tokens[$k][0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true));
                if ($opens) {
                    $depth++;
                } elseif ($tokens[$k] === '}') {
                    $depth--;
                }
                $body .= $text;
                if ($depth === 0) {
                    break;
                }
            }
            $bodies[$name] = $body;
            $i = $k;
        }
        return $bodies;
    }

    /**
     * Los parámetros que lee el método de una ruta y los de su clase a los que llama, hasta `READ_DEPTH` niveles.
     *
     * @param mixed $controller `Clase:método` del registro de rutas.
     * @return string[]
     */
    public static function handlerReads($controller): array
    {
        if (!is_string($controller) || !str_contains($controller, ':')) {
            return [];
        }
        [$class, $method] = explode(':', $controller, 2);
        if (!class_exists($class) || !method_exists($class, $method)) {
            return [];
        }
        $reads = [];
        $pending = [[$class, $method, 0]];
        $seen = [];
        while (count($pending) > 0) {
            [$currentClass, $current, $depth] = array_shift($pending);
            if (array_key_exists($currentClass . '::' . $current, $seen) || !class_exists($currentClass) || !method_exists($currentClass, $current)) {
                continue;
            }
            $seen[$currentClass . '::' . $current] = true;
            $reflection = new \ReflectionMethod($currentClass, $current);
            $file = (string) $reflection->getFileName();
            if (str_contains(str_replace('\\', '/', $file), '/vendor/')) {
                continue;
            }
            $body = self::methodSource($reflection);
            $code = is_file($file) ? (string) file_get_contents($file) : '';
            $uses = self::usesIn($code) + ['' => substr($currentClass, 0, (int) strrpos($currentClass, '\\') + 1)];
            foreach (self::readsIn($body, $currentClass, $uses) as $read) {
                $reads[$read] = true;
            }
            if ($depth >= self::READ_DEPTH) {
                continue;
            }
            if (preg_match_all('/(?:\$this->|self::|static::|parent::)(\w+)\s*\(/', $body, $calls) > 0) {
                foreach ($calls[1] as $call) {
                    $pending[] = [$currentClass, $call, $depth + 1];
                }
            }
            //Lo que delega en otra clase del producto: `(new X)->m(` y `X::m(`, con los `use` de su archivo.
            if (preg_match_all('/\(new\s+([\\\\\w]+)(?:\(\))?\)->(\w+)\s*\(|(?<![\w$>:])([A-Z][\\\\\w]*)::(\w+)\s*\(/', $body, $foreign, PREG_SET_ORDER) > 0) {
                foreach ($foreign as $call) {
                    $other = ($call[1] ?? '') !== '' ? $call[1] : ($call[3] ?? '');
                    $otherMethod = ($call[2] ?? '') !== '' ? $call[2] : ($call[4] ?? '');
                    $pending[] = [self::fullClass($other, $uses), $otherMethod, $depth + 1];
                }
            }
        }
        $names = array_keys($reads);
        sort($names);
        return $names;
    }

    /**
     * El código de un método, leído de su archivo por sus líneas.
     *
     * @param \ReflectionMethod $method
     * @return string
     */
    protected static function methodSource(\ReflectionMethod $method): string
    {
        $file = $method->getFileName();
        $start = $method->getStartLine();
        $end = $method->getEndLine();
        if (!is_string($file) || !is_int($start) || !is_int($end)) {
            return '';
        }
        $lines = file($file);
        return is_array($lines) ? implode('', array_slice($lines, $start - 1, $end - $start + 1)) : '';
    }

    /**
     * Los nombres que lee un código: `new Parameter('x'`, los `get…Param('x')` y los índices de lo que viene de la petición.
     *
     * @param string $body
     * @param string|null $class La de `self::` y `static::` en un argumento constante, como `new Parameter(self::KEY`.
     * @param array<string,string> $uses Los `use` del archivo, para una constante de otra clase.
     * @return string[]
     */
    public static function readsIn(string $body, ?string $class = null, array $uses = []): array
    {
        $code = '';
        foreach (token_get_all(str_starts_with(ltrim($body), '<?php') ? $body : "<?php\n" . $body) as $token) {
            if (is_array($token) && ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT)) {
                continue;
            }
            $code .= is_array($token) ? $token[1] : $token;
        }
        $patterns = [
            '/new\s+Parameter\(\s*([\'"])([^\'"]+)\1/',
            '/->(?:getParsedBodyParam|getQueryParam|getParam)\(\s*([\'"])([^\'"]+)\1/',
            '/\$_(?:POST|GET|REQUEST|FILES)\[\s*([\'"])([^\'"]+)\1/',
            '/(?:getParsedBody|getQueryParams|getUploadedFiles)\(\)\s*\[\s*([\'"])([^\'"]+)\1/',
        ];
        $reads = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $code, $matches) > 0) {
                foreach ($matches[2] as $name) {
                    $reads[$name] = true;
                }
            }
            $constantPattern = str_replace('([\'"])([^\'"]+)\1', '((?:self|static|[\\\\\w]+))::([A-Z][A-Z0-9_]*)\b(?!\s*\()', $pattern);
            if ($class !== null && preg_match_all($constantPattern, $code, $constants, PREG_SET_ORDER) > 0) {
                foreach ($constants as $constant) {
                    $owner = in_array(mb_strtolower($constant[1]), ['self', 'static'], true) ? $class : self::fullClass($constant[1], $uses);
                    $value = defined($owner . '::' . $constant[2]) ? constant($owner . '::' . $constant[2]) : null;
                    if (is_string($value)) {
                        $reads[$value] = true;
                    }
                }
            }
        }
        if (preg_match_all('/\$(\w+)\s*=\s*(?:\(array\)\s*)?(?:\$\w+(?:->\w+)*->(?:getParsedBody|getQueryParams|getUploadedFiles)\(\)|\$_(?:POST|GET|REQUEST|FILES))\s*(?:\?\?\s*\[\]\s*)?;/', $code, $sources) > 0) {
            foreach (array_unique($sources[1]) as $variable) {
                if (preg_match_all('/\$' . preg_quote($variable, '/') . '\[\s*([\'"])([^\'"]+)\1/', $code, $matches) > 0) {
                    foreach ($matches[2] as $name) {
                        $reads[$name] = true;
                    }
                }
            }
        }
        $names = array_values(array_filter(array_map('strval', array_keys($reads)), static fn (string $name): bool => !str_contains($name, '$')));
        sort($names);
        return $names;
    }

    /**
     * Las excepciones declaradas, o null si el archivo no se puede leer.
     *
     * @param string $root
     * @return array<string,mixed>|null
     */
    public static function loadDeclared(string $root): ?array
    {
        $declared = json_decode((string) @file_get_contents($root . '/' . self::DECLARED_RELATIVE_PATH), true);
        if (!is_array($declared)) {
            return null;
        }
        foreach (self::SECTIONS as $section) {
            if (!is_array($declared[$section] ?? null)) {
                return null;
            }
        }
        return $declared;
    }

    /**
     * La salida de una orden partida por NUL.
     *
     * @param string $command
     * @return string[]
     */
    protected static function nulSeparated(string $command): array
    {
        $output = shell_exec($command);
        return is_string($output) ? array_values(array_filter(explode("\0", $output), static fn (string $item): bool => $item !== '')) : [];
    }

}
