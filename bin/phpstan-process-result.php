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

echo $currentTotal < $baselineTotal
    ? "TRINQUETE: {$currentTotal} contra un baseline de {$baselineTotal} (-" . ($baselineTotal - $currentTotal) . "). Actualiza el baseline con su nota de medición.\n"
    : "TRINQUETE: {$currentTotal}, igual que el baseline.\n";
