<?php
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

//Procesamiento
$resultPath = __DIR__ . '/../PHPStanResult.txt';
$additionalContentPath = __DIR__ . '/../PHPStanResult.Summary.txt';

if (file_exists($resultPath)) {
    $content = file_get_contents($resultPath);
    $content = preg_replace('/(  )Line   (.*)/im', '$1project://src/$2', $content);
    $content = preg_replace('/Parameter #(\d)/im', 'Parameter Nr.$1', $content);
    $lines = explode("\n", $content);
    $errorType = [];
    $errorsByFile = [];
    $lastFile = '';
    $prevLine = '';
    $prevLineWithNumber = '';
    $regExpLineNumber = '/ :(\d{1,5}).*/';
    foreach ($lines as $index => $line) {
        if (mb_strpos($line, 'project://') !== false) {
            $lines[$index] = str_replace(' ', '', $line);
            $lastFile = $lines[$index];
        }
        if (mb_strpos($line, '🪪')) {
            $errorLine = trim($line);
            $filterPassed = $filterError === null || mb_strpos($errorLine, $filterError) !== false;
            if ($filterPassed) {
                $errorType[] = $errorLine;
            }
            if (is_string($lastFile) && mb_strlen($lastFile) > 0 && $filterPassed) {
                $errorsByFile[$lastFile] ??= [];
                if (!in_array($errorLine, $errorsByFile[$lastFile])) {
                    $lineNumber = preg_filter($regExpLineNumber, '$1', $prevLineWithNumber, 1);
                    $lineNumber = is_string($lineNumber) ? (int) trim($lineNumber) : '';
                    $addError = $lineNumber == '' || !isset($errorsByFile[$lastFile][$errorLine]) || !in_array($lineNumber, $errorsByFile[$lastFile][$errorLine]);
                    if ($addError) {
                        $errorsByFile[$lastFile][$errorLine][] = $lineNumber;
                    }
                }
            }
        }
        $prevLine = $line;
        if (preg_match($regExpLineNumber, $line)) {
            $prevLineWithNumber = $line;
        }
    }
    $content = implode("\n", $lines);
    if (is_string($content) && mb_strlen($content) > 0) {
        //---Añadir tipos de error

        //Contar errores
        $totalErrors = 0;
        $errorTypesCounter = [];
        foreach ($errorType as $error) {
            if (!array_key_exists($error, $errorTypesCounter)) {
                $errorTypesCounter[$error] = 0;
            }
            $errorTypesCounter[$error]++;
            $totalErrors++;
        }

        //Ordenar por cantidad de errores y ajustar texto con cantidad
        arsort($errorTypesCounter);
        $errorTypes = array_map(function ($error) use ($errorTypesCounter) {
            return $error . ' (' . $errorTypesCounter[$error] . ')';
        }, array_keys($errorTypesCounter));

        //---Añadir errores por archivo

        //Ordenar por archivo
        ksort($errorsByFile);
        $errorByFileStr = [];
        $totalFiles = 0;
        foreach ($errorsByFile as $file => $errors) {
            $errorsWithLines = [];
            foreach ($errors as $errorName => $erroLines) {
                $filterPassed = $filterError === null || mb_strpos($errorName, $filterError) !== false;
                if ($filterPassed) {
                    $errorsWithLines[] = $errorName . ' (Líneas: ' . implode(', ', $erroLines) . ')';
                }
            }
            if (count($errorsWithLines) > 0) {
                $errorByFileStr[] = $file . "\n\t" . implode("\n\t", $errorsWithLines);
                $totalFiles++;
            }
        }

        //---Concatenar información adicional

        $additionalContent = mb_strtoupper("================[RESUMEN]================\n");
        $additionalContent .= mb_strtoupper("\n[Total de archivos con errores]\n") . $totalFiles . "\n";
        $additionalContent .= mb_strtoupper("\n[Total de errores visibles]\n") . $totalErrors . "\n";

        //Concatenar errores con cantidad
        $additionalContent .= mb_strtoupper("\n[Tipos de errores y cantidad]\n") . implode("\n", $errorTypes);

        //Concatenar errores por archivo
        $additionalContent .= mb_strtoupper("\n\n[Errores por archivo]\n") . implode("\n", $errorByFileStr);

        //Concatenar información adicional
        $content = mb_strtoupper("\n================[PHPSTAN]================\n\n") . $content;

        //---Escribir
        file_put_contents($resultPath, $content);
        file_put_contents($additionalContentPath, $additionalContent);
    }

    //Exportar un copias de las líneas con errores para un vistazo rápido de evaluación
    $currentDir = dirname(__FILE__);
    $basePath = realpath(dirname(__FILE__) . '/../');
    $copyPathDir = $currentDir . '/Preview';
    if (!is_dir($copyPathDir)) {
        mkdir($copyPathDir, 0777, true);
        chmod($copyPathDir, 0777);
    }
    $prevFilesForDeleteIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($copyPathDir)
    );
    foreach ($prevFilesForDeleteIterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'md') {
            unlink($file->getPathname());
        }
    }
    foreach ($errorsByFile as $filePath => $errors) {
        $orignalRelativePath = str_replace('project://', '', $filePath);
        $orignalPath = $basePath . '/' . $orignalRelativePath;
        if (file_exists($orignalPath)) {
            $originalContent = file($orignalPath);
            $copyPath = $copyPathDir . '/' . str_replace('.php', '.md', $orignalRelativePath);
            $copyContent = [
                "Ver: {$filePath}",
                '```php',
            ];
            foreach ($errors as $error => $lines) {
                if ($filterError !== null && mb_strpos($error, $filterError) === false) {
                    continue;
                }
                $copyContent[] = "//" . $error;
                foreach ($lines as $line) {
                    $copyContent[] = "/* Línea " . $line . ": */" . $originalContent[$line - 1];
                }
            }
            $copyContent[] = '```';

            $copyRecursiveDir = dirname($copyPath);
            if (!is_dir($copyRecursiveDir)) {
                mkdir($copyRecursiveDir, 0777, true);
                chmod($copyRecursiveDir, 0777);
            }
            touch($copyPath);
            chmod($copyPath, 0777);
            file_put_contents($copyPath, implode("\n", $copyContent));
        }
    }
}
