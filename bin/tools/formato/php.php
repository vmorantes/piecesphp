<?php
//Lo que phpfmt 1.2.71 deja mal y no se evita con una opción ni quitando una pass, arreglado sobre tokens (PhpToken), y la
//puerta que compara los tokens de antes y de después. Lo llama bin/formatear tras phpfmt; no se usa a mano.
//
//  php bin/tools/formato/php.php <original> <formateado> <sin AlignPHPCode, o -> […]
//
//Por cada par arregla el formateado en su sitio y escribe una línea JSON: {"formateado": …, "error": null | "…"}. Con
//error, la puerta vio un cambio que no es de espacios ni de lo que se queda a propósito, y bin/formatear no lo escribe.
//  B1  «( ? \DateTime» → «(?\DateTime»: el ? de un tipo anulable va pegado a su tipo (la pass ResizeSpaces lo separa).
//  B2  la caja de los identificadores, como en el original: PSR2KeywordsLowerCase pasa el método isSet() a isset().
//  B3  el cuerpo de una función que phpfmt deja en una línea, «{return 'x';}», vuelve a una sentencia por línea.
//  D-LÍNEAS  el formato no junta ni parte líneas: se restaura la forma del original, salvo el cuerpo de una función de una
//      línea con contenido (b), dos sentencias en una línea (F5), los use y las llaves de un control de una línea (a), que
//      no se toman dentro de un bloque PHP de una sola línea. La puerta de saltos dice ERROR si algo se escapa (F6).
//  R1/R2  «): tipo» y «string|array», sin espacios, en los tipos.  R3  una continuación, un nivel por dentro de su unidad.
//  7.1  dentro de una línea, el espacio del original entre dos tokens se queda (i), salvo las normalizaciones que quitan
//      espacio (ii: R1, R2, «?Tipo», nada tras «(» ni antes de «)», «,» o «;»); los que phpfmt añade se aceptan (iv).
//  E1  dentro de una línea, una racha de dos o más espacios que no alinea en vertical queda en uno: ResizeSpaces deja
//      «\JSON_PRETTY_PRINT  |» y añade uno más en cada pasada a «!=  null» dentro de un «<?=» (no era idempotente).
//  B4  la cabecera PHP de una vista sin sangrar ni aplanar: el bloque que abre en la columna 0 sale de la pasada sin
//      AlignPHPCode (bin/formatear la hace en los archivos con «?\>»).
//Se queda, a propósito: los use ordenados y sin los no usados, las comas finales, las llaves de un if de una línea,
//TRUE → true y array() → [] (las passes declaradas).

const MODIFICADORES = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_READONLY, T_VAR];
const TIPOS = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE, T_ARRAY, T_CALLABLE, T_STATIC];
const SIN_CAJA = ['true', 'false', 'null'];

/**
 * @param PhpToken[] $tokens
 * @return string
 */
function texto(array $tokens): string
{
    return implode('', array_map(fn (PhpToken $t): string => $t->text, $tokens));
}

/**
 * El índice del token significativo anterior o siguiente a $i, o null.
 * @param PhpToken[] $tokens
 */
function vecino(array $tokens, int $i, int $paso): ?int
{
    for ($j = $i + $paso; $j >= 0 && $j < count($tokens); $j += $paso) {
        if (!$tokens[$j]->isIgnorable()) {
            return $j;
        }
    }
    return null;
}

/**
 * B1. El ? de un tipo anulable, pegado a su tipo; delante, nada tras «(» y un espacio tras «,», «:» o un modificador.
 * Nunca toca un salto de línea: un parámetro por línea se queda así (#2088).
 * @param PhpToken[] $tokens
 * @return PhpToken[]
 */
function anulables(array $tokens): array
{
    for ($i = 0; $i < count($tokens); $i++) {
        if ($tokens[$i]->text !== '?') {
            continue;
        }
        $antes = vecino($tokens, $i, -1);
        $despues = vecino($tokens, $i, 1);
        if ($antes === null || $despues === null || !in_array($tokens[$despues]->id, TIPOS, true)) {
            continue;
        }
        $previo = $tokens[$antes];
        $esTipo = in_array($previo->text, ['(', ','], true) || in_array($previo->id, MODIFICADORES, true)
            //«:» de un tipo de retorno: el que sigue al «)» de una lista de parámetros.
            || ($previo->text === ':' && ($cierre = vecino($tokens, $antes, -1)) !== null && $tokens[$cierre]->text === ')');
        if (!$esTipo) {
            continue;
        }
        //Lo de en medio solo puede ser espacio en la misma línea: con un comentario o un salto, ese lado se deja.
        $soloEspacio = function (int $desde, int $hasta) use ($tokens): bool {
            for ($j = $desde; $j < $hasta; $j++) {
                if ($tokens[$j]->id !== T_WHITESPACE || str_contains($tokens[$j]->text, "\n")) {
                    return false;
                }
            }
            return true;
        };
        if ($soloEspacio($i + 1, $despues)) {
            array_splice($tokens, $i + 1, $despues - $i - 1);
        }
        if ($soloEspacio($antes + 1, $i)) {
            $delante = $previo->text === '(' ? [] : [new PhpToken(T_WHITESPACE, ' ')];
            array_splice($tokens, $antes + 1, $i - $antes - 1, $delante);
            $i = $antes + 1 + count($delante);
        }
    }
    return $tokens;
}

/**
 * E1. Un solo espacio entre dos tokens de la misma línea, salvo una alineación vertical (7.1 iii). No toca la sangría
 * (va tras un salto), ni lo que precede a un comentario (un comentario alineado al final de línea), ni el interior de
 * cadenas, comentarios o HTML, que son tokens.
 * @param PhpToken[] $tokens
 * @return PhpToken[]
 */
function rachas(array $tokens): array
{
    for ($i = 1; $i < count($tokens) - 1; $i++) {
        $t = $tokens[$i];
        if ($t->id !== T_WHITESPACE || strlen($t->text) < 2 || str_contains($t->text, "\n") || str_contains($t->text, "\r")) {
            continue;
        }
        $antes = $tokens[$i - 1];
        $despues = $tokens[$i + 1];
        if ($antes->isIgnorable() || $despues->isIgnorable() || in_array($antes->id, [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_INLINE_HTML], true)) {
            continue;
        }
        //7.1 (iii): una alineación vertical se queda.
        if (alineado($tokens, $i + 1)) {
            continue;
        }
        $tokens[$i] = new PhpToken(T_WHITESPACE, ' ', $t->line, $t->pos);
    }
    //Sin espacio tras «(» ni antes de «)», «,» o «;» en la misma línea; salvo ante un comentario, en el hueco de una lista
    //(«[$a, , $b]») y en un for vacío («for (;;)»).
    for ($i = 1; $i < count($tokens) - 1; $i++) {
        $t = $tokens[$i];
        if ($t->id !== T_WHITESPACE || str_contains($t->text, "\n") || str_contains($t->text, "\r")) {
            continue;
        }
        $antes = $tokens[$i - 1];
        $despues = $tokens[$i + 1];
        if ($antes->isIgnorable() || $despues->isIgnorable() || in_array($antes->id, [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_INLINE_HTML], true)) {
            continue;
        }
        $tras = $antes->text === '(' && $despues->text !== ')';
        $ante = $despues->text === ')' || ($despues->text === ',' && !in_array($antes->text, ['(', '[', ','], true))
            || ($despues->text === ';' && !in_array($antes->text, ['(', ';'], true));
        if ($tras || $ante) {
            $tokens[$i] = new PhpToken(T_WHITESPACE, '', $t->line, $t->pos);
        }
    }
    return $tokens;
}

/**
 * 7.1 (iii). ¿El token $i empieza en la misma columna que ese mismo token en la línea anterior o en la siguiente?
 * @param PhpToken[] $tokens
 */
function alineado(array $tokens, int $i): bool
{
    //En caracteres, no en bytes: una clave con tildes se ve alineada aunque ocupe más bytes.
    $antes = texto(array_slice($tokens, 0, $i));
    $inicioLinea = strrpos($antes, "\n");
    $columna = mb_strlen(substr($antes, $inicioLinea === false ? 0 : $inicioLinea + 1));
    $lineas = explode("\n", $antes . texto(array_slice($tokens, $i)));
    $numero = substr_count($antes, "\n");
    $buscado = $tokens[$i]->text;
    foreach ([$numero - 1, $numero + 1] as $otra) {
        $linea = $lineas[$otra] ?? null;
        if ($linea !== null && $columna > 0 && mb_substr($linea, $columna, mb_strlen($buscado)) === $buscado && mb_substr($linea, $columna - 1, 1) === ' ') {
            return true;
        }
    }
    return false;
}

/**
 * Los T_STRING en orden, sin los de los use de primer nivel (phpfmt los quita o los reordena).
 * @param PhpToken[] $tokens
 * @return int[] índices
 */
function identificadores(array $tokens): array
{
    $indices = [];
    $nivel = 0;
    $enUse = false;
    foreach ($tokens as $i => $t) {
        if ($t->text === '{' || $t->id === T_CURLY_OPEN || $t->id === T_DOLLAR_OPEN_CURLY_BRACES) {
            $nivel++;
        } elseif ($t->text === '}') {
            $nivel--;
        }
        if ($t->id === T_USE && $nivel === 0) {
            $enUse = true;
        } elseif ($enUse && $t->text === ';') {
            $enUse = false;
        } elseif (!$enUse && $t->id === T_STRING && !in_array(strtolower($t->text), SIN_CAJA, true)) {
            $indices[] = $i;
        }
    }
    return $indices;
}

/**
 * B2. La caja de cada identificador, la del original: el n-ésimo T_STRING del formateado es el n-ésimo del original.
 * @param PhpToken[] $original
 * @param PhpToken[] $tokens
 * @return PhpToken[]
 */
function caja(array $original, array $tokens): array
{
    $antes = identificadores($original);
    $despues = identificadores($tokens);
    if (count($antes) !== count($despues)) {
        //No se emparejan: no se toca, y la puerta dirá si cambió algo.
        return $tokens;
    }
    foreach ($despues as $n => $i) {
        $deAntes = $original[$antes[$n]]->text;
        if ($tokens[$i]->text !== $deAntes && strtolower($tokens[$i]->text) === strtolower($deAntes)) {
            $tokens[$i] = new PhpToken(T_STRING, $deAntes, $tokens[$i]->line, $tokens[$i]->pos);
        }
    }
    return $tokens;
}

/**
 * B3. «{return 'x';}» en su propia línea, como cuerpo de una función: una sentencia por línea, sangradas cuatro más que
 * la llave. Solo si la llave abre la línea (las que phpfmt baja), sin comentarios ni llaves dentro.
 * @param PhpToken[] $tokens
 * @return PhpToken[]
 */
function cuerposDeUnaLinea(array $tokens): array
{
    for ($i = 0; $i < count($tokens); $i++) {
        if ($tokens[$i]->id !== T_FUNCTION) {
            continue;
        }
        $parentesis = 0;
        for ($abre = $i; $abre < count($tokens); $abre++) {
            $x = $tokens[$abre]->text;
            $parentesis += $x === '(' ? 1 : ($x === ')' ? -1 : 0);
            if ($parentesis === 0 && ($x === '{' || $x === ';')) {
                break;
            }
        }
        if ($abre >= count($tokens) || $tokens[$abre]->text !== '{') {
            continue;
        }
        $previo = $tokens[$abre - 1] ?? null;
        if ($previo === null || $previo->id !== T_WHITESPACE || !str_contains($previo->text, "\n")) {
            continue;
        }
        $sangria = substr($previo->text, strrpos($previo->text, "\n") + 1);
        $sentencias = [[]];
        for ($cierra = $abre + 1; $cierra < count($tokens); $cierra++) {
            $t = $tokens[$cierra];
            if ($t->text === '}') {
                break;
            }
            if (in_array($t->text, ['{', '"', '`'], true) || in_array($t->id, [T_COMMENT, T_DOC_COMMENT, T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES, T_START_HEREDOC], true)
                || ($t->id === T_WHITESPACE && str_contains($t->text, "\n"))) {
                continue 2;
            }
            $sentencias[count($sentencias) - 1][] = $t;
            if ($t->text === ';') {
                $sentencias[] = [];
            }
        }
        $lineas = array_values(array_filter(array_map(fn (array $s): string => trim(texto($s)), $sentencias), fn (string $s): bool => $s !== ''));
        //Un cuerpo vacío, «{}», se queda como venía.
        if ($lineas === []) {
            continue;
        }
        $cuerpo = "\n{$sangria}    " . implode("\n{$sangria}    ", $lineas) . "\n{$sangria}";
        $nuevo = PhpToken::tokenize('<?php ' . $cuerpo);
        array_shift($nuevo);
        array_splice($tokens, $abre + 1, $cierra - $abre - 1, $nuevo);
        $i = $abre;
    }
    return $tokens;
}

/**
 * Los bloques PHP: [índice de la etiqueta de apertura, índice de su cierre o el último token, si abre en la columna 0].
 * @param PhpToken[] $tokens
 * @return array<int, array{0: int, 1: int, 2: bool}>
 */
function bloques(array $tokens): array
{
    $bloques = [];
    $abre = null;
    for ($i = 0; $i < count($tokens); $i++) {
        $t = $tokens[$i];
        if ($t->id === T_OPEN_TAG || $t->id === T_OPEN_TAG_WITH_ECHO) {
            $abre = $i;
        } elseif ($t->id === T_CLOSE_TAG && $abre !== null) {
            $bloques[] = [$abre, $i, $abre === 0 || str_ends_with($tokens[$abre - 1]->text, "\n")];
            $abre = null;
        }
    }
    if ($abre !== null) {
        $bloques[] = [$abre, count($tokens) - 1, $abre === 0 || str_ends_with($tokens[$abre - 1]->text, "\n")];
    }
    return $bloques;
}

/**
 * B4. En un archivo con HTML, AlignPHPCode sangra y aplana el bloque que abre en la columna 0 (la cabecera de una vista)
 * y es la que alinea bien los bloques metidos en el HTML. Cada bloque de la columna 0 se toma de la pasada sin ella.
 * @param PhpToken[] $conAlineado
 * @param PhpToken[] $sinAlineado
 * @return PhpToken[]
 */
function cabeceraSinAlinear(array $conAlineado, array $sinAlineado): array
{
    $con = bloques($conAlineado);
    $sin = bloques($sinAlineado);
    $html = array_filter($conAlineado, fn (PhpToken $t): bool => $t->id === T_INLINE_HTML && trim($t->text) !== '') !== [];
    if (!$html || count($con) !== count($sin)) {
        return $conAlineado;
    }
    $salida = [];
    $desde = 0;
    foreach ($con as $n => [$abre, $cierra, $columnaCero]) {
        if (!$columnaCero) {
            continue;
        }
        $salida = array_merge($salida, array_slice($conAlineado, $desde, $abre - $desde), array_slice($sinAlineado, $sin[$n][0], $sin[$n][1] - $sin[$n][0] + 1));
        $desde = $cierra + 1;
    }
    return PhpToken::tokenize(texto(array_merge($salida, array_slice($conAlineado, $desde))), TOKEN_PARSE);
}

/**
 * La puerta: los tokens significativos, normalizados en lo que se queda a propósito.
 * @param PhpToken[] $tokens
 * @return string[]
 */
function huella(array $tokens): array
{
    $salida = [];
    $nivel = 0;
    $enUse = false;
    $corchetes = [];
    $parentesis = [];
    foreach ($tokens as $i => $t) {
        if ($t->id === T_WHITESPACE) {
            continue;
        }
        if ($t->text === '{' || $t->id === T_CURLY_OPEN || $t->id === T_DOLLAR_OPEN_CURLY_BRACES) {
            $nivel++;
        } elseif ($t->text === '}') {
            $nivel--;
        }
        //Los use de primer nivel: phpfmt los ordena y quita los no usados (se queda).
        if ($t->id === T_USE && $nivel === 0) {
            $enUse = true;
            continue;
        }
        if ($enUse) {
            $enUse = $t->text !== ';';
            continue;
        }
        $texto = $t->text;
        if (in_array($t->id, [T_COMMENT, T_DOC_COMMENT, T_INLINE_HTML], true)) {
            $texto = preg_replace('/\s+/', ' ', trim($texto)) ?? $texto;
            if ($texto === '') {
                continue;
            }
        } elseif (in_array($t->id, [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG], true)) {
            $texto = trim($texto);
        } elseif ($t->id !== T_STRING && $t->id !== T_VARIABLE && $t->id !== T_CONSTANT_ENCAPSED_STRING && $t->id !== T_ENCAPSED_AND_WHITESPACE && preg_match('/^[a-z_]+$/i', $texto) === 1) {
            //Las palabras clave, sin caja: TRUE → true y compañía (se queda).
            $texto = strtolower($texto);
        } elseif ($t->id === T_STRING && in_array(strtolower($texto), SIN_CAJA, true)) {
            $texto = strtolower($texto);
        }
        //array(…) → […] (se queda): el paréntesis que abre un array() cierra con «]».
        if ($t->id === T_ARRAY && ($siguiente = vecino($tokens, $i, 1)) !== null && $tokens[$siguiente]->text === '(') {
            $corchetes[$siguiente] = true;
            continue;
        }
        if ($texto === '(') {
            $parentesis[] = isset($corchetes[$i]);
            if (isset($corchetes[$i])) {
                $texto = '[';
            }
        } elseif ($texto === ')' && array_pop($parentesis) === true) {
            $texto = ']';
        }
        //Las comas finales (se quedan): una coma justo antes del cierre no cuenta.
        if ($texto === ')' || $texto === ']') {
            if (end($salida) === ',') {
                array_pop($salida);
            }
        }
        $salida[] = $texto;
    }
    //La etiqueta de cierre al final de un archivo solo PHP: PSR-2 la quita.
    if (end($salida) === '?>') {
        array_pop($salida);
    }
    return llavesDeUnaLinea($salida);
}

/**
 * Las llaves que phpfmt pone en un if, elseif, else, for, foreach o while de una línea (se quedan): fuera, en los dos
 * lados, las de cualquier bloque de control, para que «if (a) b;» y «if (a) { b; }» den lo mismo.
 * @param string[] $huella
 * @return string[]
 */
function llavesDeUnaLinea(array $huella): array
{
    $salida = [];
    $pila = [];
    foreach ($huella as $x) {
        if ($x === '{') {
            $previo = end($salida);
            $esControl = $previo === 'else' || ($previo === ')' && controlAntesDe($salida));
            $pila[] = $esControl;
            if ($esControl) {
                continue;
            }
        } elseif ($x === '}') {
            if (array_pop($pila) === true) {
                continue;
            }
        }
        $salida[] = $x;
    }
    return $salida;
}

/**
 * ¿El «)» que cierra $salida es el de un if, elseif, for, foreach o while?
 * @param string[] $salida
 */
function controlAntesDe(array $salida): bool
{
    $nivel = 0;
    for ($i = count($salida) - 1; $i >= 0; $i--) {
        if ($salida[$i] === ')' || $salida[$i] === ']') {
            $nivel++;
        } elseif ($salida[$i] === '(' || $salida[$i] === '[') {
            $nivel--;
            if ($nivel === 0) {
                return in_array($salida[$i - 1] ?? '', ['if', 'elseif', 'for', 'foreach', 'while'], true);
            }
        }
    }
    return false;
}

/**
 * La sangría de la línea donde está el token $i: lo que va tras el último salto anterior, hasta el primer no espacio.
 * @param PhpToken[] $tokens
 */
function sangriaDe(array $tokens, int $i): string
{
    $resto = '';
    for ($j = $i - 1; $j >= 0; $j--) {
        $t = $tokens[$j]->text;
        $salto = strrpos($t, "\n");
        if ($salto !== false) {
            $resto = substr($t, $salto + 1) . $resto;
            break;
        }
        $resto = $t . $resto;
    }
    return substr($resto, 0, strlen($resto) - strlen(ltrim($resto, " \t")));
}

/**
 * El primer token de la unidad a la que pertenece $i: desde el «;», «{», «}», «,», «(» o «[» que la abre, al mismo
 * nivel de paréntesis. Es la línea de la que cuelga una continuación.
 * @param PhpToken[] $tokens
 */
function inicioDeUnidad(array $tokens, int $i): int
{
    $nivel = 0;
    $inicio = $i;
    for ($j = $i - 1; $j >= 0; $j--) {
        $t = $tokens[$j];
        if ($t->isIgnorable()) {
            continue;
        }
        $x = $t->text;
        //Una cadena con variables dentro («"…{$x}…"») o un heredoc: se salta entero, sus llaves no cuentan.
        if ($x === '"' || $x === '`' || $t->id === T_END_HEREDOC) {
            for ($j--; $j > 0 && $tokens[$j]->text !== $x && $tokens[$j]->id !== T_START_HEREDOC; $j--) {
            }
            $inicio = $j;
            continue;
        }
        if ($x === ')' || $x === ']') {
            $nivel++;
        } elseif ($x === '(' || $x === '[') {
            if ($nivel === 0) {
                break;
            }
            $nivel--;
        } elseif ($nivel === 0 && (in_array($x, [';', '{', '}', ','], true) || in_array($t->id, [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG, T_INLINE_HTML], true))) {
            break;
        }
        $inicio = $j;
    }
    return $inicio;
}

/**
 * a) Un bloque PHP que abre y cierra en la misma línea («<?php if (…) continue; ?>») se queda como estaba: ni llaves
 * ni saltos. Se toma entero del original.
 * @param PhpToken[] $original
 * @param PhpToken[] $tokens
 * @return PhpToken[]
 */
function bloquesDeUnaLinea(array $original, array $tokens): array
{
    $antes = bloques($original);
    $despues = bloques($tokens);
    if (count($antes) !== count($despues)) {
        return $tokens;
    }
    $salida = [];
    $desde = 0;
    foreach ($antes as $n => [$abre, $cierra]) {
        if ($original[$cierra]->id !== T_CLOSE_TAG) {
            continue;
        }
        $bloque = texto(array_slice($original, $abre, $cierra - $abre + 1));
        //El cierre puede llevar el salto de línea que le sigue: solo cuenta el que haya antes.
        if (str_contains(rtrim($bloque, "\r\n"), "\n")) {
            continue;
        }
        [$abreF, $cierraF] = $despues[$n];
        $salida = array_merge($salida, array_slice($tokens, $desde, $abreF - $desde), array_slice($original, $abre, $cierra - $abre + 1));
        $desde = $cierraF + 1;
    }
    return $desde === 0 ? $tokens : PhpToken::tokenize(texto(array_merge($salida, array_slice($tokens, $desde))), TOKEN_PARSE);
}

/**
 * Los tokens significativos con la clave con que se emparejan el original y el formateado.
 * @param PhpToken[] $tokens
 * @return array{0: int[], 1: string[]}
 */
function significativos(array $tokens): array
{
    $indices = [];
    $claves = [];
    foreach ($tokens as $i => $t) {
        if ($t->isIgnorable() || in_array($t->id, [T_INLINE_HTML], true)) {
            continue;
        }
        $indices[] = $i;
        $claves[] = $t->id === T_STRING || $t->id === T_VARIABLE || $t->id === T_CONSTANT_ENCAPSED_STRING ? $t->text : strtolower(trim($t->text));
    }
    return [$indices, $claves];
}

/**
 * ¿Hay salto de línea entre los tokens $a y $b? Null si en medio hay algo que no es espacio (un comentario).
 * @param PhpToken[] $tokens
 */
function saltoEntre(array $tokens, int $a, int $b): ?bool
{
    $salto = str_ends_with($tokens[$a]->text, "\n");
    for ($j = $a + 1; $j < $b; $j++) {
        if ($tokens[$j]->id !== T_WHITESPACE) {
            return null;
        }
        $salto = $salto || str_contains($tokens[$j]->text, "\n");
    }
    return $salto;
}

/**
 * Los pares de tokens consecutivos que el formato no ha tocado alrededor (emparejados en el original y en el
 * formateado), con el salto de cada lado: [índice original a, b, índice formateado a, b].
 * @param PhpToken[] $original
 * @param PhpToken[] $tokens
 * @return array<int, array{0: int, 1: int, 2: int, 3: int}>
 */
function paresEmparejados(array $original, array $tokens): array
{
    [$io, $co] = significativos($original);
    [$if, $cf] = significativos($tokens);
    $pares = [];
    $parejas = emparejar($co, $cf);
    for ($n = 0; $n + 1 < count($parejas); $n++) {
        [$a, $b] = $parejas[$n];
        [$a2, $b2] = $parejas[$n + 1];
        if ($a2 === $a + 1 && $b2 === $b + 1) {
            $pares[] = [$io[$a], $io[$a2], $if[$b], $if[$b2]];
        }
    }
    return $pares;
}

/**
 * Las regiones del original donde partir líneas está permitido: el cuerpo de una función de una línea con contenido
 * (b), desde el token anterior a su «{» hasta su «}».
 * @param PhpToken[] $original
 * @return array<int, true>
 */
function regionesDeCuerpos(array $original): array
{
    $permitidos = [];
    for ($i = 0; $i < count($original); $i++) {
        if ($original[$i]->id !== T_FUNCTION) {
            continue;
        }
        $nivel = 0;
        for ($abre = $i; $abre < count($original); $abre++) {
            $x = $original[$abre]->text;
            $nivel += $x === '(' ? 1 : ($x === ')' ? -1 : 0);
            if ($nivel === 0 && ($x === '{' || $x === ';')) {
                break;
            }
        }
        if ($abre >= count($original) || $original[$abre]->text !== '{') {
            continue;
        }
        $nivel = 0;
        $contenido = false;
        for ($cierra = $abre; $cierra < count($original); $cierra++) {
            $x = $original[$cierra]->text;
            if ($x === '{' || $original[$cierra]->id === T_CURLY_OPEN || $original[$cierra]->id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $nivel++;
            } elseif ($x === '}') {
                $nivel--;
                if ($nivel === 0) {
                    break;
                }
            } elseif ($cierra > $abre && !$original[$cierra]->isIgnorable()) {
                $contenido = true;
            }
        }
        if ($cierra < count($original) && $contenido && $original[$abre]->line === $original[$cierra]->line) {
            $previo = vecino($original, $abre, -1) ?? $abre;
            for ($k = $previo; $k <= $cierra; $k++) {
                $permitidos[$k] = true;
            }
        }
    }
    return $permitidos;
}

/**
 * ¿Es un salto que el formato puede cambiar? b) en el cuerpo de una función de una línea, y F5: tras un «;».
 * @param PhpToken[] $original
 * @param array<int, true> $cuerpos
 */
function partidaPermitida(array $original, array $cuerpos, int $a, int $b): bool
{
    return (isset($cuerpos[$a]) && isset($cuerpos[$b])) || $original[$a]->text === ';';
}

/**
 * D-LÍNEAS. El formato no junta ni parte líneas: donde el original tenía un salto y el formateado no, vuelve (a la
 * altura de la línea del token anterior si lo que sigue es «{», o un nivel por dentro de su unidad); donde no lo tenía
 * y ahora sí, se quita con el espacio del original. Salvo b) y F5 (partidaPermitida); los use y las llaves añadidas
 * no se emparejan y no cuentan.
 * @param PhpToken[] $original
 * @param PhpToken[] $tokens
 * @return PhpToken[]
 */
function estructura(array $original, array $tokens): array
{
    $cuerpos = regionesDeCuerpos($original);
    $cambios = [];
    foreach (paresEmparejados($original, $tokens) as [$a, $b, $fa, $fb]) {
        $antes = saltoEntre($original, $a, $b);
        $ahora = saltoEntre($tokens, $fa, $fb);
        if ($antes === null || $ahora === null || $antes === $ahora || str_ends_with($tokens[$fa]->text, "\n")) {
            continue;
        }
        if ($antes) {
            $sangria = $tokens[$fb]->text === '{' ? sangriaDe($tokens, $fa) : sangriaDe($tokens, inicioDeUnidad($tokens, $fb)) . '    ';
            $cambios[$fa] = [$fb, "\n" . $sangria];
        } elseif (!partidaPermitida($original, $cuerpos, $a, $b)) {
            $cambios[$fa] = [$fb, texto(array_slice($original, $a + 1, $b - $a - 1))];
        }
    }
    if ($cambios === []) {
        return $tokens;
    }
    $salida = [];
    for ($i = 0; $i < count($tokens); $i++) {
        $salida[] = $tokens[$i];
        if (isset($cambios[$i])) {
            [$hasta, $espacio] = $cambios[$i];
            if ($espacio !== '') {
                $salida[] = new PhpToken(T_WHITESPACE, $espacio);
            }
            $i = $hasta - 1;
        }
    }
    return PhpToken::tokenize(texto($salida), TOKEN_PARSE);
}

/**
 * 7.1 (i). Dentro de una línea, donde el original tenía espacio entre dos tokens emparejados y el formateado tiene otro,
 * se queda el del original («fn (», «array =>», «{ $x; }», «[...$a», «__LINE__ + 2»). Donde no había y phpfmt pone uno,
 * se acepta (iv). Las normalizaciones que sí quitan espacio van después (anulables, tipos, rachas).
 * @param PhpToken[] $original
 * @param PhpToken[] $tokens
 * @return PhpToken[]
 */
function espaciosDelOriginal(array $original, array $tokens): array
{
    $cambios = [];
    foreach (paresEmparejados($original, $tokens) as [$a, $b, $fa, $fb]) {
        $antes = texto(array_slice($original, $a + 1, $b - $a - 1));
        $ahora = texto(array_slice($tokens, $fa + 1, $fb - $fa - 1));
        //Un espacio que phpfmt añade se acepta (iv), salvo tras «(» o «[» («[...$a» → «[ ...$a»): ahí, nada.
        $abre = in_array($tokens[$fa]->text, ['(', '['], true);
        if ($antes === $ahora || ($antes === '' && !$abre) || trim($antes) !== '' || trim($ahora) !== '' || str_contains($antes . $ahora, "\n")
            || str_ends_with($original[$a]->text, "\n") || str_ends_with($tokens[$fa]->text, "\n")) {
            continue;
        }
        $cambios[$fa] = [$fb, $antes];
    }
    if ($cambios === []) {
        return $tokens;
    }
    $salida = [];
    for ($i = 0; $i < count($tokens); $i++) {
        $salida[] = $tokens[$i];
        if (isset($cambios[$i])) {
            [$hasta, $espacio] = $cambios[$i];
            $salida[] = new PhpToken(T_WHITESPACE, $espacio);
            $i = $hasta - 1;
        }
    }
    return PhpToken::tokenize(texto($salida), TOKEN_PARSE);
}

/**
 * F6, la puerta de saltos: lo que quede junto o partido fuera de b) y F5. Null si nada.
 * @param PhpToken[] $original
 * @param PhpToken[] $tokens
 */
function puertaDeSaltos(array $original, array $tokens): ?string
{
    $cuerpos = regionesDeCuerpos($original);
    foreach (paresEmparejados($original, $tokens) as [$a, $b, $fa, $fb]) {
        $antes = saltoEntre($original, $a, $b);
        $ahora = saltoEntre($tokens, $fa, $fb);
        if ($antes === null || $ahora === null || $antes === $ahora) {
            continue;
        }
        if ($antes || !partidaPermitida($original, $cuerpos, $a, $b)) {
            return sprintf('la puerta de saltos: %s entre «%s» y «%s» (línea %d del original)', $antes ? 'se junta' : 'se parte', $original[$a]->text, $original[$b]->text, $original[$a]->line);
        }
    }
    return null;
}

/**
 * ¿Es el «)» $i el que cierra los parámetros de una función, un fn o el use de un cierre?
 * @param PhpToken[] $tokens
 */
function cierraParametros(array $tokens, int $i): bool
{
    $nivel = 0;
    for ($j = $i; $j >= 0; $j--) {
        $x = $tokens[$j]->text;
        if ($x === ')') {
            $nivel++;
        } elseif ($x === '(') {
            $nivel--;
            if ($nivel === 0) {
                $previo = vecino($tokens, $j, -1);
                if ($previo === null) {
                    return false;
                }
                if (in_array($tokens[$previo]->id, [T_FUNCTION, T_FN, T_USE], true)) {
                    return true;
                }
                $antes = $tokens[$previo]->id === T_STRING ? vecino($tokens, $previo, -1) : null;
                if ($antes !== null && $tokens[$antes]->text === '&') {
                    $antes = vecino($tokens, $antes, -1);
                }
                return $antes !== null && $tokens[$antes]->id === T_FUNCTION;
            }
        }
    }
    return false;
}

/**
 * R1 y R2. «): tipo» sin espacio antes de los dos puntos y con uno después, y los tipos unión e intersección sin
 * espacios («string|array»), en los tipos de retorno, de parámetros y de propiedades. Nunca toca un salto.
 * @param PhpToken[] $tokens
 * @return PhpToken[]
 */
function tipos(array $tokens): array
{
    $espacio = function (int $i) use (&$tokens): bool {
        return isset($tokens[$i]) && $tokens[$i]->id === T_WHITESPACE && !str_contains($tokens[$i]->text, "\n");
    };
    $enTipo = [];
    for ($i = 0; $i < count($tokens); $i++) {
        $t = $tokens[$i];
        //El tipo de retorno: tras «):» hasta «{», «;» o «=>».
        if ($t->text === ':' && ($cierre = vecino($tokens, $i, -1)) !== null && $tokens[$cierre]->text === ')' && cierraParametros($tokens, $cierre)) {
            if ($espacio($i - 1) && $i - 1 > $cierre) {
                $tokens[$i - 1] = new PhpToken(T_WHITESPACE, '');
            }
            if ($espacio($i + 1)) {
                $tokens[$i + 1] = new PhpToken(T_WHITESPACE, ' ');
            }
            for ($j = $i + 1; $j < count($tokens) && !in_array($tokens[$j]->text, ['{', ';', '=>'], true) && $tokens[$j]->id !== T_DOUBLE_ARROW; $j++) {
                $enTipo[$j] = true;
            }
        }
        //Parámetros y propiedades: lo que va antes de la variable, desde «(», «,» o un modificador.
        if ($t->id === T_VARIABLE) {
            $previo = vecino($tokens, $i, -1);
            for ($j = $previo; $j !== null && $j >= 0; $j = vecino($tokens, $j, -1)) {
                $x = $tokens[$j];
                if (in_array($x->text, ['(', ',', ';', '{', '}'], true) || in_array($x->id, MODIFICADORES, true) || $x->id === T_OPEN_TAG) {
                    break;
                }
                if (!in_array($x->id, array_merge(TIPOS, [T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG, T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG, T_ELLIPSIS]), true) && !in_array($x->text, ['|', '?', '(', ')'], true)) {
                    continue 2;
                }
                $enTipo[$j] = true;
            }
        }
    }
    for ($i = 0; $i < count($tokens); $i++) {
        $t = $tokens[$i];
        if (isset($enTipo[$i]) && ($t->text === '|' || $t->id === T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG)) {
            foreach ([$i - 1, $i + 1] as $j) {
                if ($espacio($j)) {
                    $tokens[$j] = new PhpToken(T_WHITESPACE, '');
                }
            }
        }
    }
    return PhpToken::tokenize(texto($tokens), TOKEN_PARSE);
}

/**
 * R3. Una línea que continúa una expresión («? …», «: …», «?? …», «-> …», «. …», «&& …», «|| …») va un nivel por dentro
 * de la línea de su unidad: phpfmt la deja a su altura.
 * @param PhpToken[] $tokens
 * @return PhpToken[]
 */
function continuaciones(array $tokens): array
{
    $operadores = ['?', ':', '??', '->', '?->', '.', '&&', '||', 'and', 'or', '+', '-', '*', '/'];
    for ($i = 1; $i < count($tokens); $i++) {
        $t = $tokens[$i];
        $previo = $tokens[$i - 1];
        if (!in_array(strtolower($t->text), $operadores, true) || $previo->id !== T_WHITESPACE || !str_contains($previo->text, "\n")) {
            continue;
        }
        $anterior = vecino($tokens, $i, -1);
        //Un ? tras «(» o «,» es un tipo anulable, y un «:» tras «)» de parámetros es un tipo de retorno: no continúan.
        if ($anterior === null || ($t->text === '?' && in_array($tokens[$anterior]->text, ['(', ','], true)) || ($t->text === ':' && $tokens[$anterior]->text === ')' && cierraParametros($tokens, $anterior))) {
            continue;
        }
        $base = sangriaDe($tokens, inicioDeUnidad($tokens, $i));
        $actual = substr($previo->text, strrpos($previo->text, "\n") + 1);
        if (strlen($actual) <= strlen($base)) {
            $tokens[$i - 1] = new PhpToken(T_WHITESPACE, substr($previo->text, 0, strrpos($previo->text, "\n") + 1) . $base . '    ');
        }
    }
    return $tokens;
}

/**
 * Empareja dos secuencias casi iguales: avanza mientras coinciden y, en un desacuerdo, busca el salto más corto que
 * vuelve a casar tres claves seguidas (los use quitados, las comas y llaves añadidas). Devuelve [índice en a, en b].
 * @param string[] $a
 * @param string[] $b
 * @return array<int, array{0: int, 1: int}>
 */
function emparejar(array $a, array $b): array
{
    $parejas = [];
    $i = 0;
    $j = 0;
    $na = count($a);
    $nb = count($b);
    $casan = function (int $x, int $y) use ($a, $b, $na, $nb): bool {
        for ($k = 0; $k < 3; $k++) {
            if ($x + $k >= $na || $y + $k >= $nb) {
                return $x + $k >= $na && $y + $k >= $nb && $k > 0;
            }
            if ($a[$x + $k] !== $b[$y + $k]) {
                return false;
            }
        }
        return true;
    };
    while ($i < $na && $j < $nb) {
        if ($a[$i] === $b[$j]) {
            $parejas[] = [$i, $j];
            $i++;
            $j++;
            continue;
        }
        $saltado = false;
        for ($d = 1; $d <= 600 && !$saltado; $d++) {
            for ($x = 0; $x <= $d; $x++) {
                if ($casan($i + $x, $j + $d - $x)) {
                    $i += $x;
                    $j += $d - $x;
                    $saltado = true;
                    break;
                }
            }
        }
        if (!$saltado) {
            $i++;
            $j++;
        }
    }
    return $parejas;
}

if (!isset($argv)) {
    exit(2);
}
$ternas = array_slice($argv, 1);
if ($ternas === [] || count($ternas) % 3 !== 0) {
    fwrite(STDERR, "uso: php bin/tools/formato/php.php <original> <formateado> <sin AlignPHPCode, o -> […]\n");
    exit(2);
}
for ($p = 0; $p < count($ternas); $p += 3) {
    [$rutaOriginal, $rutaFormateada, $rutaSinAlinear] = [$ternas[$p], $ternas[$p + 1], $ternas[$p + 2]];
    $error = null;
    try {
        $original = PhpToken::tokenize((string) file_get_contents($rutaOriginal), TOKEN_PARSE);
        $tokens = PhpToken::tokenize((string) file_get_contents($rutaFormateada), TOKEN_PARSE);
        if ($rutaSinAlinear !== '-') {
            $tokens = cabeceraSinAlinear($tokens, PhpToken::tokenize((string) file_get_contents($rutaSinAlinear), TOKEN_PARSE));
        }
        $tokens = bloquesDeUnaLinea($original, $tokens);
        $tokens = cuerposDeUnaLinea(caja($original, $tokens));
        $tokens = espaciosDelOriginal($original, estructura($original, $tokens));
        $tokens = rachas(continuaciones(tipos(anulables($tokens))));
        if (file_put_contents($rutaFormateada, texto($tokens)) === false) {
            $error = 'no se pudo escribir el formateado';
        } else {
            $antes = huella($original);
            $despues = huella(PhpToken::tokenize(texto($tokens), TOKEN_PARSE));
            if ($antes !== $despues) {
                $k = 0;
                while ($k < min(count($antes), count($despues)) && $antes[$k] === $despues[$k]) {
                    $k++;
                }
                $error = sprintf('la puerta de tokens: cambia algo más que espacios, en el token %d: «%s» pasa a «%s»', $k, mb_substr(implode(' ', array_slice($antes, max(0, $k - 2), 5)), 0, 120), mb_substr(implode(' ', array_slice($despues, max(0, $k - 2), 5)), 0, 120));
            } else {
                $error = puertaDeSaltos($original, PhpToken::tokenize(texto($tokens), TOKEN_PARSE));
            }
        }
    } catch (\Throwable $e) {
        $error = get_class($e) . ': ' . $e->getMessage();
    }
    echo json_encode(['formateado' => $rutaFormateada, 'error' => $error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}
