<?php

//El dato manda en PHPStanResult.json, no en la tabla: la tabla recorta las rutas al ancho de terminal.

//CLI
$cliArguments = [];
$argc--;
while ($argc > 0) {
    $parts = explode('=', $argv[$argc]);
    $argumentName = $parts[0] ?? null;
    $argumentValue = $parts[1] ?? true;
    if ($argumentName !== null) {
        $argumentName = mb_strpos($argumentName, '--') === 0 ? $argumentName : '--' . $argumentName;
        $cliArguments[$argumentName] = $argumentValue;
    }
    unset($argv[$argc]);
    $argc--;
}
$filterError = $cliArguments['--filter'] ?? null;

$basePath = realpath(dirname(__FILE__) . '/../');
$unionPath = $basePath . '/PHPStanResult.json';
$summaryPath = $basePath . '/PHPStanResult.Summary.txt';
$baselinePath = $basePath . '/PHPStanResult.Summary.baseline.txt';

$union = json_decode((string) @file_get_contents($unionPath), true);
if (!is_array($union) || !isset($union['files']) || !is_array($union['files'])) {
    fwrite(STDERR, "No se pudo leer PHPStanResult.json. La medición NO se hizo.\n");
    exit(1);
}

$errorTypesCounter = [];
$errorsByFile = [];
$versionCounter = ['8.4' => 0, '8.5' => 0, 'ambas' => 0];
$totalErrors = 0;

foreach ($union['files'] as $path => $data) {
    $relative = str_replace('\\', '/', (string) $path);
    $relative = str_replace(str_replace('\\', '/', $basePath) . '/', '', $relative);
    //project:// no es decoración: es lo que el plugin del editor usa para saltar al archivo.
    $labelled = 'project://' . $relative;

    foreach ($data['messages'] as $message) {
        $identifier = $message['identifier'] ?? '(sin identificador)';

        if ($filterError !== null && mb_strpos($identifier, (string) $filterError) === false) {
            continue;
        }

        $totalErrors++;
        $errorTypesCounter[$identifier] = ($errorTypesCounter[$identifier] ?? 0) + 1;
        $errorsByFile[$labelled][$identifier][] = $message['line'];

        $versions = (array) ($message['phpVersions'] ?? []);
        if (count($versions) > 1) {
            $versionCounter['ambas']++;
        } elseif (in_array('8.5', $versions, true)) {
            $versionCounter['8.5']++;
        } else {
            $versionCounter['8.4']++;
        }
    }
}

arsort($errorTypesCounter);
ksort($errorsByFile);

$errorTypes = [];
foreach ($errorTypesCounter as $identifier => $count) {
    $errorTypes[] = $identifier . ' (' . $count . ')';
}

$errorByFileStr = [];
foreach ($errorsByFile as $file => $identifiers) {
    $lines = [];
    foreach ($identifiers as $identifier => $lineNumbers) {
        $lines[] = $identifier . ' (Líneas: ' . implode(', ', $lineNumbers) . ')';
    }
    $errorByFileStr[] = $file . "\n\t" . implode("\n\t", $lines);
}

$content = mb_strtoupper("================[RESUMEN]================\n");
$content .= mb_strtoupper("\n[Total de archivos con errores]\n") . count($errorsByFile) . "\n";
$content .= mb_strtoupper("\n[Total de errores visibles]\n") . $totalErrors . "\n";
$content .= mb_strtoupper("\n[Unidad]\n");
$content .= "TRIPLETAS (ruta, línea, mensaje) DISTINTAS, no instancias: la unión de las dos\n";
$content .= "pasadas se deduplica por esa clave, así que un error idéntico repetido en el mismo\n";
$content .= "sitio cuenta UNA vez. Las cifras anteriores a la unión estaban en instancias.\n";
$content .= mb_strtoupper("\n[Reparto por versión de PHP]\n");
$content .= "En las dos (8.4 y 8.5): {$versionCounter['ambas']}\n";
$content .= "Solo en 8.5: {$versionCounter['8.5']}\n";
$content .= "Solo en 8.4: {$versionCounter['8.4']}\n";
$content .= mb_strtoupper("\n[Tipos de errores y cantidad]\n") . implode("\n", $errorTypes);
$content .= mb_strtoupper("\n\n[Errores por archivo]\n") . implode("\n", $errorByFileStr);

file_put_contents($summaryPath, $content);

//──── La TABLA legible, con el mismo tratamiento que tenía antes ────────────────────────
/**
 * Se conserva tal cual estaba: el prefijo `project://` para el plugin del editor, y
 * `Parameter Nr.` para que el `#1` no se lea como una almohadilla de Markdown.
 */
$tablePath = $basePath . '/PHPStanResult.txt';
if (is_file($tablePath)) {
    $table = (string) file_get_contents($tablePath);
    if (mb_strpos($table, 'project://') === false) {
        $table = (string) preg_replace('/(  )Line   (.*)/im', '$1project://src/$2', $table);
        $table = (string) preg_replace('/Parameter #(\d)/im', 'Parameter Nr.$1', $table);
        file_put_contents($tablePath, mb_strtoupper("\n================[PHPSTAN]================\n\n") . $table);
    }
}

//──── Vista rápida de las líneas con error ──────────────────────────────────────────────
$copyPathDir = dirname(__FILE__) . '/Preview';
if (!is_dir($copyPathDir)) {
    mkdir($copyPathDir, 0777, true);
    chmod($copyPathDir, 0777);
}
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($copyPathDir)) as $file) {
    if ($file->isFile() && $file->getExtension() === 'md') {
        unlink($file->getPathname());
    }
}
foreach ($errorsByFile as $labelled => $identifiers) {
    $relative = str_replace('project://', '', $labelled);
    $originalPath = $basePath . '/' . $relative;
    if (!file_exists($originalPath)) {
        continue;
    }
    $originalContent = file($originalPath);
    if ($originalContent === false) {
        continue;
    }
    $copyPath = $copyPathDir . '/' . str_replace('.php', '.md', $relative);
    $copyContent = ['Ver: ' . $labelled, '```php'];
    $allLines = [];
    foreach ($identifiers as $lineNumbers) {
        $allLines = array_merge($allLines, $lineNumbers);
    }
    sort($allLines);
    foreach (array_unique($allLines) as $line) {
        $lineIndex = ((int) $line) - 1;
        if (!isset($originalContent[$lineIndex])) {
            continue;
        }
        $copyContent[] = '/* Línea ' . $line . ': */' . $originalContent[$lineIndex];
    }
    $copyContent[] = '```';
    $copyRecursiveDir = dirname($copyPath);
    if (!is_dir($copyRecursiveDir)) {
        mkdir($copyRecursiveDir, 0777, true);
        chmod($copyRecursiveDir, 0777);
    }
    file_put_contents($copyPath, implode("\n", $copyContent));
}

//──── TRINQUETE ─────────────────────────────────────────────────────────────────────────
//Sin este trinquete, «el baseline solo baja» es una frase en un documento y nada lo comprueba.
$readTotal = static function (string $file): ?int {
    if (!is_file($file)) {
        return null;
    }
    $text = (string) file_get_contents($file);
    //Se toma la ÚLTIMA coincidencia: el baseline cita la cabecera dentro de su nota de método.
    if (preg_match_all('/\[TOTAL DE ERRORES VISIBLES\]\s*\R\s*(\d+)/u', $text, $matches) < 1) {
        return null;
    }
    return (int) end($matches[1]);
};

if ($filterError !== null) {
    exit(0); //Con filtro, el total no es comparable con el baseline.
}

$baselineTotal = $readTotal($baselinePath);
$currentTotal = $readTotal($summaryPath);

if ($baselineTotal === null || $currentTotal === null) {
    fwrite(STDERR, "TRINQUETE: no se pudo leer el total de una de las dos medidas. La comparación NO se hizo.\n");
    exit(1);
}

if ($currentTotal > $baselineTotal) {
    $delta = $currentTotal - $baselineTotal;
    fwrite(STDERR, "\nTRINQUETE ROTO: {$currentTotal} errores contra un baseline de {$baselineTotal} (+{$delta}).\n");
    fwrite(STDERR, "Se arregla, o se justifica por escrito y se actualiza el baseline con su nota de medición.\n");
    exit(1);
}

//Cada cifra del baseline declara «[REPARTO] n <- anterior = x arreglos + y supresiones», o esto no pasa.
//«+ z destapados»: la cifra sube porque creció el universo, no el daño. Ver T79.
//«+ w murieron»: E3 BORRA CODIGO, y un error que se va con su archivo no es un arreglo ni una
//supresion. MEDIDO en el primer lote: de 39, solo 17 murieron con su archivo y 22 se fueron con
//codigo retirado de archivos que SE QUEDAN. Son la misma familia y se suman juntos. Ver T139.
$baselineText = (string) @file_get_contents($baselinePath);
if (preg_match_all('/\[REPARTO\]\s*(\d+)\s*<-\s*(\d+)\s*=\s*(\d+)\s+arreglos?\s*\+\s*(\d+)\s+supresi\S*(?:\s*\+\s*(\d+)\s+destapad\S*)?(?:\s*\+\s*(\d+)\s+murieron)?/u', $baselineText, $splits, \PREG_SET_ORDER) > 0) {
    $splitsByTotal = [];
    foreach ($splits as $s) {
        $splitsByTotal[(int) $s[1]] = [
            'from' => (int) $s[2],
            'fixed' => (int) $s[3],
            'muted' => (int) $s[4],
            'uncovered' => (int) ($s[5] ?? 0),
            'died' => (int) ($s[6] ?? 0),
        ];
    }
} else {
    $splitsByTotal = [];
}

$firstTotal = null;
if (preg_match_all('/\[REPARTO\]\s*(\d+)\s*<-\s*(\d+)/u', $baselineText, $all, \PREG_SET_ORDER) > 0) {
    $firstTotal = (int) $all[count($all) - 1][2]; //La cifra más antigua citada.
}

if (!array_key_exists($baselineTotal, $splitsByTotal) && $baselineTotal !== $firstTotal) {
    fwrite(STDERR, "\nTRINQUETE SIN REPARTO: el baseline declara {$baselineTotal} y no dice cuántos son arreglo y cuántos supresión.\n");
    fwrite(STDERR, "Añade a " . basename($baselinePath) . " una línea con la forma:\n");
    fwrite(STDERR, "  [REPARTO] {$baselineTotal} <- <anterior> = <n> arreglos + <n> supresiones\n");
    fwrite(STDERR, "No es para prohibir suprimir: es para que una bajada por supresión no se lea como progreso.\n");
    exit(1);
}

//COTA A LA MENTIRA, que no es verificacion: una supresion cambia el .neon, un arreglo cambia
//el codigo. No demuestra que la atribucion sea cierta, pero descarta la declaracion imposible.
$countIgnores = static function (string $file): int {
    $text = (string) @file_get_contents($file);
    return preg_match_all('/^\s*-\s*(identifier:|message:|$)/mu', $text, $ignored);
};
$neonPath = dirname($baselinePath) . '/bin/phpstan.neon';
$neonPath = is_file($neonPath) ? $neonPath : dirname(__FILE__) . '/phpstan.neon';
$ignoresNow = $countIgnores($neonPath);
if (preg_match('/\[ENTRADAS-NEON\]\s*(\d+)/u', $baselineText, $entradas) === 1) {
    $ignoresBefore = (int) $entradas[1];
    $muted = $splitsByTotal[$baselineTotal]['muted'] ?? 0;
    $grew = $ignoresNow - $ignoresBefore;
    if ($muted === 0 && $grew > 0) {
        fwrite(STDERR, "\nTRINQUETE: LA DECLARACION NO CUADRA CON EL .NEON. Se declaran 0 supresiones y el bloque de "
            . "ignoreErrors creció en {$grew} entrada(s).\n");
        exit(1);
    }
    if ($muted > 0 && $grew <= 0) {
        fwrite(STDERR, "\nTRINQUETE: LA DECLARACION NO CUADRA CON EL .NEON. Se declaran {$muted} supresiones y el bloque "
            . "de ignoreErrors NO creció.\n");
        exit(1);
    }
    echo "TRINQUETE: cota del .neon — {$ignoresBefore} -> {$ignoresNow} entradas de ignoreErrors, coherente con lo declarado.\n";
}

if (array_key_exists($baselineTotal, $splitsByTotal)) {
    $s = $splitsByTotal[$baselineTotal];
    $declared = $s['fixed'] + $s['muted'] + $s['died'] - $s['uncovered'];
    $real = $s['from'] - $baselineTotal;
    if ($declared !== $real) {
        fwrite(STDERR, "\nTRINQUETE: EL REPARTO NO CUADRA. De {$s['from']} a {$baselineTotal} van {$real}, y se declaran "
            . "{$s['fixed']} arreglos + {$s['muted']} supresiones + {$s['died']} murieron - {$s['uncovered']} destapados = {$declared}.\n");
        exit(1);
    }
    $uncovered = $s['uncovered'] > 0 ? ", {$s['uncovered']} destapados al ampliar el universo" : '';
    $died = $s['died'] > 0 ? ", {$s['died']} murieron con el código borrado" : '';
    echo "TRINQUETE: reparto declarado y cuadrado — {$s['from']} -> {$baselineTotal}: {$s['fixed']} por arreglo, {$s['muted']} por supresión{$died}{$uncovered}.\n";
}

echo $currentTotal < $baselineTotal
    ? "TRINQUETE: {$currentTotal} contra un baseline de {$baselineTotal} (-" . ($baselineTotal - $currentTotal) . "). Actualiza el baseline con su nota de medición Y su [REPARTO].\n"
    : "TRINQUETE: {$currentTotal}, igual que el baseline.\n";
