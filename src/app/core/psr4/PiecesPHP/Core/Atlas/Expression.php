<?php

/**
 * Expression.php
 */

namespace PiecesPHP\Core\Atlas;

use PiecesPHP\UserSystem\UserTypes;

/**
 * Expression - Evalúa, sin ejecutar el código del framework, una expresión PHP leída por TokenIndex: literales, constantes
 * de clase (por reflexión), propiedades estáticas, concatenaciones, arrays, la asignación de una variable en el mismo
 * método (o en el que lo envuelve), `$this->propiedad` asignada en la clase y un puñado de funciones puras. Lo demás
 * queda sin resolver y lo dice.
 *
 * @package     PiecesPHP\Core\Atlas
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class Expression
{
    const MAX_DEPTH = 8;

    /**
     * Las funciones que se ejecutan al evaluar: puras sobre arrays.
     */
    const PURE_FUNCTIONS = ['array_merge', 'array_values', 'array_unique'];

    /**
     * Las llamadas estáticas que se ejecutan al evaluar: leen y no escriben.
     */
    const PURE_STATIC_CALLS = [UserTypes::class . '::codes'];

    /**
     * Las clases envoltorio cuyo primer argumento es el valor (`new IntegerArray($permisos)`).
     */
    const WRAPPERS = ['PiecesPHP\\Core\\DataStructures\\IntegerArray', 'PiecesPHP\\Core\\DataStructures\\StringArray', 'PiecesPHP\\DataStructures\\IntegerArray', 'PiecesPHP\\DataStructures\\StringArray'];

    /**
     * El resultado de evaluar el tramo [$start, $end) de un archivo indexado.
     *
     * - kind: literal, constante, variable, propiedad, llamada u otro (la forma escrita en el sitio).
     * - resolved: si se pudo calcular el valor.
     * - symbol: la constante `Clase::NOMBRE` de la que sale todo el valor, si la hay.
     * - at: dónde está escrito el valor que habría que editar (la constante, la asignación o el propio sitio).
     *
     * @param TokenIndex $index
     * @param string $file Relativo a src/.
     * @param int $start
     * @param int $end
     * @param int $depth
     * @return array{kind:string,text:string,resolved:bool,value:mixed,symbol:?string,at:array{file:string,line:int},reason:?string}
     */
    public static function evaluate(TokenIndex $index, string $file, int $start, int $end, int $depth = 0): array
    {
        $data = $index->file($file);
        if ($data === null) {
            return self::unresolved('otro', '', $file, 0, 'archivo sin indexar');
        }
        $tokens = $data['tokens'];
        $positions = [];
        for ($i = $start; $i < $end; $i++) {
            if (!$tokens[$i]->isIgnorable()) {
                $positions[] = $i;
            }
        }
        $text = trim((string) preg_replace('/\s+/', ' ', implode('', array_map(fn(int $i): string => $tokens[$i]->text, $positions))));
        $line = $positions !== [] ? $tokens[$positions[0]]->line : 0;
        if ($positions === []) {
            return self::unresolved('otro', '', $file, $line, 'vacía');
        }
        if ($depth > self::MAX_DEPTH) {
            return self::unresolved('otro', $text, $file, $line, 'demasiadas indirecciones');
        }

        //Una concatenación de primer nivel: cada trozo, y todos escalares.
        $parts = self::splitTopLevel($tokens, $positions, '.');
        if (count($parts) > 1) {
            $value = '';
            foreach ($parts as $part) {
                $piece = self::evaluate($index, $file, $part[0], end($part) + 1, $depth + 1);
                if (!$piece['resolved'] || !is_scalar($piece['value'])) {
                    return self::unresolved('otro', $text, $file, $line, 'un trozo de la concatenación no se resuelve: ' . $piece['text']);
                }
                $value .= (string) $piece['value'];
            }
            return self::resolved('otro', $text, $value, null, $file, $line);
        }

        $first = $tokens[$positions[0]];
        $last = $tokens[end($positions)];
        $count = count($positions);

        //Paréntesis que envuelven todo.
        if ($first->text === '(' && $last->text === ')' && self::closes($tokens, $positions, 0) === $count - 1) {
            return self::evaluate($index, $file, $positions[1], end($positions), $depth + 1);
        }

        //Un array literal.
        if (($first->text === '[' && $last->text === ']' && self::closes($tokens, $positions, 0) === $count - 1)
            || ($first->is(T_ARRAY) && $count > 2 && $tokens[$positions[1]]->text === '(' && $last->text === ')')) {
            $inner = $first->text === '[' ? array_slice($positions, 1, -1) : array_slice($positions, 2, -1);
            $values = [];
            foreach (self::splitTopLevel($tokens, $inner, ',') as $element) {
                $arrow = null;
                foreach (self::topLevel($tokens, $element) as $position) {
                    if ($tokens[$position]->is(T_DOUBLE_ARROW)) {
                        $arrow = $position;
                    }
                }
                $spread = $tokens[$element[0]]->is(T_ELLIPSIS);
                $from = $arrow !== null ? self::after($element, $arrow) : ($spread ? ($element[1] ?? null) : $element[0]);
                if ($from === null) {
                    continue;
                }
                $item = self::evaluate($index, $file, $from, end($element) + 1, $depth + 1);
                if (!$item['resolved']) {
                    return self::unresolved('literal', $text, $file, $line, 'un elemento no se resuelve: ' . $item['text']);
                }
                if ($spread && is_array($item['value'])) {
                    foreach ($item['value'] as $spreadValue) {
                        $values[] = $spreadValue;
                    }
                } else {
                    $values[] = $item['value'];
                }
            }
            return self::resolved('literal', $text, $values, null, $file, $line);
        }

        if ($count === 1) {
            if ($first->is(T_LNUMBER)) {
                return self::resolved('literal', $text, intval(str_replace('_', '', $first->text), 0), null, $file, $line);
            }
            if ($first->is(T_CONSTANT_ENCAPSED_STRING)) {
                return self::resolved('literal', $text, self::unquote($first->text), null, $file, $line);
            }
            if ($first->is(T_STRING) && in_array(strtolower($first->text), ['true', 'false', 'null'], true)) {
                $literal = ['true' => true, 'false' => false, 'null' => null][strtolower($first->text)];
                return self::resolved('literal', $text, $literal, null, $file, $line);
            }
            if ($first->is(T_VARIABLE)) {
                return self::variable($index, $file, $positions[0], $text, $depth);
            }
        }

        //Clase::CONSTANTE, Clase::class, Clase::$estatica.
        if ($count === 3 && $tokens[$positions[1]]->is(T_DOUBLE_COLON)) {
            $class = self::classOf($index, $file, $positions[0]);
            $member = $tokens[$positions[2]];
            if ($class === null) {
                return self::unresolved('constante', $text, $file, $line, 'no se sabe de qué clase es «' . $first->text . '»');
            }
            if ($member->is(T_CLASS)) {
                return self::resolved('literal', $text, $class, null, $file, $line);
            }
            if ($member->is(T_STRING)) {
                return self::constant($index, $class, $member->text, $text, $file, $line);
            }
            if ($member->is(T_VARIABLE) && class_exists($class)) {
                try {
                    $property = new \ReflectionProperty($class, ltrim($member->text, '$'));
                    if ($property->isStatic()) {
                        return self::resolved('propiedad', $text, $property->getValue(), null, $file, $line);
                    }
                } catch (\ReflectionException) {
                }
                return self::unresolved('propiedad', $text, $file, $line, 'propiedad estática que no existe');
            }
        }

        //$this->propiedad o $variable->propiedad, con ->getArrayCopy() opcional.
        if ($first->is(T_VARIABLE) && $count >= 3 && $tokens[$positions[1]]->is(T_OBJECT_OPERATOR) && $tokens[$positions[2]]->is(T_STRING)) {
            $rest = array_slice($positions, 3);
            $restText = implode('', array_map(fn(int $i): string => $tokens[$i]->text, $rest));
            if ($restText === '' || $restText === '->getArrayCopy()') {
                return self::property($index, $file, $positions[0], $tokens[$positions[2]]->text, $text, $line, $depth);
            }
        }

        //Una llamada: Clase::metodo(...) o funcion(...), solo las puras.
        $open = self::firstTopLevel($tokens, $positions, '(');
        if ($open !== null && $last->text === ')' && self::closes($tokens, $positions, $open) === $count - 1) {
            return self::call($index, $file, array_slice($positions, 0, $open), array_slice($positions, $open + 1, -1), $text, $line, $depth);
        }

        return self::unresolved($first->is(T_VARIABLE) ? 'variable' : 'otro', $text, $file, $line, 'forma que no se evalúa');
    }

    /**
     * @param TokenIndex $index
     * @param string $class
     * @param string $name
     * @param string $text
     * @param string $file
     * @param int $line
     * @return array{kind:string,text:string,resolved:bool,value:mixed,symbol:?string,at:array{file:string,line:int},reason:?string}
     */
    private static function constant(TokenIndex $index, string $class, string $name, string $text, string $file, int $line): array
    {
        if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
            return self::unresolved('constante', $text, $file, $line, "la clase {$class} no existe");
        }
        try {
            $reflection = new \ReflectionClassConstant($class, $name);
        } catch (\ReflectionException) {
            return self::unresolved('constante', $text, $file, $line, "la constante {$class}::{$name} no existe");
        }
        $declaring = $reflection->getDeclaringClass()->getName();
        $symbol = $declaring . '::' . $name;
        $declared = $index->constant($symbol);
        $result = self::resolved('constante', $text, $reflection->getValue(), $symbol, $file, $line);
        if ($declared !== null) {
            $result['at'] = ['file' => $declared['file'], 'line' => $declared['line']];
        }
        return $result;
    }

    /**
     * La última asignación de la variable antes de su uso, en la función que la contiene o en las que la envuelven.
     *
     * @param TokenIndex $index
     * @param string $file
     * @param int $position
     * @param string $text
     * @param int $depth
     * @return array{kind:string,text:string,resolved:bool,value:mixed,symbol:?string,at:array{file:string,line:int},reason:?string}
     */
    private static function variable(TokenIndex $index, string $file, int $position, string $text, int $depth): array
    {
        $data = $index->file($file);
        $line = $data !== null ? $data['tokens'][$position]->line : 0;
        if ($data === null || $text === '$this') {
            return self::unresolved('variable', $text, $file, $line, 'variable sin asignación que leer');
        }
        $tokens = $data['tokens'];
        $enclosing = array_filter($data['functions'], fn(array $f): bool => $f['start'] < $position && $position <= $f['end']);
        usort($enclosing, fn(array $a, array $b): int => $b['start'] <=> $a['start']);
        foreach ($enclosing as $function) {
            for ($i = $position - 1; $i > $function['start']; $i--) {
                if (!$tokens[$i]->is(T_VARIABLE) || $tokens[$i]->text !== $text) {
                    continue;
                }
                $equals = $index->next($tokens, $i);
                if ($equals === null || $tokens[$equals]->text !== '=') {
                    continue;
                }
                $end = self::statementEnd($tokens, $equals + 1);
                $assigned = self::evaluate($index, $file, $equals + 1, $end, $depth + 1);
                $assigned['kind'] = 'variable';
                $assigned['text'] = $text;
                if ($assigned['symbol'] === null) {
                    $assigned['at'] = ['file' => $file, 'line' => $tokens[$i]->line];
                }
                return $assigned;
            }
        }
        return self::unresolved('variable', $text, $file, $line, 'no se encontró su asignación en el método');
    }

    /**
     * `$this->propiedad` (o `$objeto->propiedad` con `$objeto = new Clase(...)`): la asignación `$this->propiedad = …` de esa clase.
     *
     * @param TokenIndex $index
     * @param string $file
     * @param int $position La variable.
     * @param string $name
     * @param string $text
     * @param int $line
     * @param int $depth
     * @return array{kind:string,text:string,resolved:bool,value:mixed,symbol:?string,at:array{file:string,line:int},reason:?string}
     */
    private static function property(TokenIndex $index, string $file, int $position, string $name, string $text, int $line, int $depth): array
    {
        $data = $index->file($file);
        if ($data === null) {
            return self::unresolved('propiedad', $text, $file, $line, 'archivo sin indexar');
        }
        $tokens = $data['tokens'];
        $class = null;
        if ($tokens[$position]->text === '$this') {
            $class = TokenIndex::classAt($data, $position)['name'] ?? null;
        } else {
            //La última asignación `$objeto = new Clase(` antes del uso, en la función que lo contiene.
            $function = array_filter($data['functions'], fn(array $f): bool => $f['start'] < $position && $position <= $f['end']);
            $from = $function !== [] ? max(array_column($function, 'start')) : 0;
            for ($i = $position - 1; $i > $from; $i--) {
                if (!$tokens[$i]->is(T_VARIABLE) || $tokens[$i]->text !== $tokens[$position]->text) {
                    continue;
                }
                $equals = $index->next($tokens, $i);
                $new = $equals !== null ? $index->next($tokens, $equals) : null;
                $className = $new !== null ? $index->next($tokens, $new) : null;
                if ($equals !== null && $tokens[$equals]->text === '=' && $new !== null && $tokens[$new]->is(T_NEW) && $className !== null) {
                    $class = $tokens[$className]->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])
                        ? self::classOf($index, $file, $className) : null;
                    break;
                }
            }
        }
        if ($class === null || !class_exists($class)) {
            return self::unresolved('propiedad', $text, $file, $line, 'no se sabe de qué clase es el objeto');
        }
        $classFile = $index->relative((string) (new \ReflectionClass($class))->getFileName());
        $classData = $classFile !== null ? $index->file($classFile) : null;
        if ($classFile === null || $classData === null) {
            return self::unresolved('propiedad', $text, $file, $line, 'la clase está fuera del índice');
        }
        $classTokens = $classData['tokens'];
        $found = null;
        $count = count($classTokens);
        for ($i = 0; $i < $count; $i++) {
            if ($classTokens[$i]->text !== '$this') {
                continue;
            }
            $arrow = $index->next($classTokens, $i);
            $member = $arrow !== null ? $index->next($classTokens, $arrow) : null;
            $equals = $member !== null ? $index->next($classTokens, $member) : null;
            if ($arrow !== null && $member !== null && $equals !== null && $classTokens[$arrow]->is(T_OBJECT_OPERATOR)
                && $classTokens[$member]->text === $name && $classTokens[$equals]->text === '=') {
                $found = $equals;
            }
        }
        if ($found === null) {
            return self::unresolved('propiedad', $text, $file, $line, "la clase no asigna \$this->{$name}");
        }
        $assigned = self::evaluate($index, $classFile, $found + 1, self::statementEnd($classTokens, $found + 1), $depth + 1);
        $assigned['kind'] = 'propiedad';
        $assigned['text'] = $text;
        return $assigned;
    }

    /**
     * @param TokenIndex $index
     * @param string $file
     * @param int[] $callee
     * @param int[] $argumentPositions
     * @param string $text
     * @param int $line
     * @param int $depth
     * @return array{kind:string,text:string,resolved:bool,value:mixed,symbol:?string,at:array{file:string,line:int},reason:?string}
     */
    private static function call(TokenIndex $index, string $file, array $callee, array $argumentPositions, string $text, int $line, int $depth): array
    {
        $data = $index->file($file);
        if ($data === null) {
            return self::unresolved('llamada', $text, $file, $line, 'archivo sin indexar');
        }
        $tokens = $data['tokens'];
        $arguments = [];
        foreach (self::splitTopLevel($tokens, $argumentPositions, ',') as $argument) {
            $arguments[] = $argument;
        }
        //new Envoltorio($valor): el valor.
        if (count($callee) === 2 && $tokens[$callee[0]]->is(T_NEW)) {
            $class = self::classOf($index, $file, $callee[1]);
            if ($class !== null && in_array($class, self::WRAPPERS, true) && isset($arguments[0])) {
                $inner = self::evaluate($index, $file, $arguments[0][0], end($arguments[0]) + 1, $depth + 1);
                $inner['text'] = $text;
                return $inner;
            }
            return self::unresolved('llamada', $text, $file, $line, 'un objeto que no se evalúa');
        }
        $name = null;
        if (count($callee) === 1 && $tokens[$callee[0]]->is(T_STRING)) {
            $name = strtolower($tokens[$callee[0]]->text);
        } elseif (count($callee) === 3 && $tokens[$callee[1]]->is(T_DOUBLE_COLON) && $tokens[$callee[2]]->is(T_STRING)) {
            $class = self::classOf($index, $file, $callee[0]);
            $name = $class !== null ? $class . '::' . $tokens[$callee[2]]->text : null;
        }
        if ($name !== null && in_array($name, self::PURE_STATIC_CALLS, true) && $arguments === []) {
            return self::resolved('llamada', $text, call_user_func($name), null, $file, $line);
        }
        if ($name === null || !in_array($name, self::PURE_FUNCTIONS, true)) {
            return self::unresolved('llamada', $text, $file, $line, 'llamada que no se ejecuta al evaluar');
        }
        $values = [];
        $symbol = null;
        foreach ($arguments as $argument) {
            $value = self::evaluate($index, $file, $argument[0], end($argument) + 1, $depth + 1);
            if (!$value['resolved'] || !is_array($value['value'])) {
                return self::unresolved('llamada', $text, $file, $line, 'un argumento no se resuelve: ' . $value['text']);
            }
            $values[] = $value['value'];
            $symbol = count($arguments) === 1 ? $value['symbol'] : null;
        }
        $result = match ($name) {
            'array_merge' => array_merge(...$values),
            'array_values' => array_values($values[0] ?? []),
            default => array_values(array_unique($values[0] ?? [], SORT_REGULAR)),
        };
        return self::resolved('llamada', $text, $result, $symbol, $file, $line);
    }

    /**
     * La clase que nombra el token de una posición (self, static, parent o un nombre).
     *
     * @param TokenIndex $index
     * @param string $file
     * @param int $position
     * @return string|null
     */
    private static function classOf(TokenIndex $index, string $file, int $position): ?string
    {
        $data = $index->file($file);
        if ($data === null) {
            return null;
        }
        $name = $data['tokens'][$position]->text;
        $lower = strtolower($name);
        $current = TokenIndex::classAt($data, $position);
        if ($lower === 'self' || $lower === 'static') {
            return $current['name'] ?? null;
        }
        if ($lower === 'parent') {
            return $current['parent'] ?? null;
        }
        return TokenIndex::resolveClass($name, $data['namespace'], $data['uses']);
    }

    /**
     * @param \PhpToken[] $tokens
     * @param int $from
     * @return int El índice del `;` de primer nivel (o del final).
     */
    private static function statementEnd(array $tokens, int $from): int
    {
        $depth = 0;
        $count = count($tokens);
        for ($i = $from; $i < $count; $i++) {
            $text = $tokens[$i]->text;
            if ($text === '(' || $text === '[' || $text === '{' || $tokens[$i]->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                $depth++;
            } elseif ($text === ')' || $text === ']' || $text === '}') {
                $depth--;
                if ($depth < 0) {
                    return $i;
                }
            } elseif ($text === ';' && $depth === 0) {
                return $i;
            }
        }
        return $count;
    }

    /**
     * Las posiciones de primer nivel (fuera de paréntesis, corchetes y llaves).
     *
     * @param \PhpToken[] $tokens
     * @param int[] $positions
     * @return int[]
     */
    private static function topLevel(array $tokens, array $positions): array
    {
        $depth = 0;
        $top = [];
        foreach ($positions as $position) {
            $text = $tokens[$position]->text;
            if ($text === ')' || $text === ']' || $text === '}') {
                $depth--;
            }
            if ($depth === 0) {
                $top[] = $position;
            }
            if ($text === '(' || $text === '[' || $text === '{' || $tokens[$position]->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                $depth++;
            }
        }
        return $top;
    }

    /**
     * @param \PhpToken[] $tokens
     * @param int[] $positions
     * @param string $separator
     * @return array<int,int[]>
     */
    private static function splitTopLevel(array $tokens, array $positions, string $separator): array
    {
        $top = array_flip(self::topLevel($tokens, $positions));
        $parts = [];
        $current = [];
        foreach ($positions as $position) {
            if (isset($top[$position]) && $tokens[$position]->text === $separator) {
                if ($current !== []) {
                    $parts[] = $current;
                }
                $current = [];
                continue;
            }
            $current[] = $position;
        }
        if ($current !== []) {
            $parts[] = $current;
        }
        return $parts;
    }

    /**
     * El índice (dentro de $positions) del primer token de primer nivel con ese texto.
     *
     * @param \PhpToken[] $tokens
     * @param int[] $positions
     * @param string $text
     * @return int|null
     */
    private static function firstTopLevel(array $tokens, array $positions, string $text): ?int
    {
        foreach (self::topLevel($tokens, $positions) as $position) {
            if ($tokens[$position]->text === $text) {
                $found = array_search($position, $positions, true);
                return is_int($found) ? $found : null;
            }
        }
        return null;
    }

    /**
     * El índice (dentro de $positions) que cierra el que abre en $openAt.
     *
     * @param \PhpToken[] $tokens
     * @param int[] $positions
     * @param int $openAt
     * @return int|null
     */
    private static function closes(array $tokens, array $positions, int $openAt): ?int
    {
        $depth = 0;
        $count = count($positions);
        for ($k = $openAt; $k < $count; $k++) {
            $text = $tokens[$positions[$k]]->text;
            if ($text === '(' || $text === '[' || $text === '{' || $tokens[$positions[$k]]->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                $depth++;
            } elseif ($text === ')' || $text === ']' || $text === '}') {
                $depth--;
                if ($depth === 0) {
                    return $k;
                }
            }
        }
        return null;
    }

    /**
     * @param int[] $element
     * @param int $position
     * @return int|null La posición siguiente a $position dentro de $element.
     */
    private static function after(array $element, int $position): ?int
    {
        $key = array_search($position, $element, true);
        return is_int($key) ? ($element[$key + 1] ?? null) : null;
    }

    /**
     * @param string $literal Con sus comillas.
     * @return string
     */
    private static function unquote(string $literal): string
    {
        $quote = $literal[0];
        $inner = mb_substr($literal, 1, -1);
        return $quote === "'" ? str_replace(["\\'", '\\\\'], ["'", '\\'], $inner) : stripcslashes($inner);
    }

    /**
     * @param string $kind
     * @param string $text
     * @param mixed $value
     * @param string|null $symbol
     * @param string $file
     * @param int $line
     * @return array{kind:string,text:string,resolved:bool,value:mixed,symbol:?string,at:array{file:string,line:int},reason:?string}
     */
    private static function resolved(string $kind, string $text, mixed $value, ?string $symbol, string $file, int $line): array
    {
        return ['kind' => $kind, 'text' => $text, 'resolved' => true, 'value' => $value, 'symbol' => $symbol, 'at' => ['file' => $file, 'line' => $line], 'reason' => null];
    }

    /**
     * @param string $kind
     * @param string $text
     * @param string $file
     * @param int $line
     * @param string $reason
     * @return array{kind:string,text:string,resolved:bool,value:mixed,symbol:?string,at:array{file:string,line:int},reason:?string}
     */
    private static function unresolved(string $kind, string $text, string $file, int $line, string $reason): array
    {
        return ['kind' => $kind, 'text' => $text, 'resolved' => false, 'value' => null, 'symbol' => null, 'at' => ['file' => $file, 'line' => $line], 'reason' => $reason];
    }
}
