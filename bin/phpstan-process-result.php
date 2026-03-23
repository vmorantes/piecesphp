<?php

$resultPath = __DIR__ . '/../PHPStanResult.txt';

if (file_exists($resultPath)) {
    $content = file_get_contents($resultPath);
    $content = preg_replace('/(  )Line   (.*)/im', '$1project://src/$2', $content);
    $content = preg_replace('/Parameter #(\d)/im', 'Parameter Nr.$1', $content);
    $lines = explode("\n", $content);
    $errorType = [];
    $errorsByFile = [];
    $lastFile = '';
    foreach ($lines as $index => $line) {
        if (mb_strpos($line, 'project://') !== false) {
            $lines[$index] = str_replace(' ', '', $line);
            $lastFile = $lines[$index];
        }
        if (mb_strpos($line, '🪪')) {
            $errorLine = trim($line);
            $errorType[] = $errorLine;
            if (is_string($lastFile) && mb_strlen($lastFile) > 0) {
                $errorsByFile[$lastFile] ??= [];
                if (!in_array($errorLine, $errorsByFile[$lastFile])) {
                    $errorsByFile[$lastFile][] = $errorLine;
                }
            }
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
        foreach ($errorsByFile as $file => $errors) {
            $errorByFileStr[] = $file . "\n\t" . implode("\n\t", $errors);
        }

        //---Concatenar información adicional

        $additionalContent = mb_strtoupper("================[RESUMEN]================\n");

        //Concatenar errores con cantidad
        $errorTypes = implode("\n", $errorTypes);
        $additionalContent .= mb_strtoupper("\n[Total de errores visibles]\n") . $totalErrors . "\n";
        $additionalContent .= mb_strtoupper("\n[Tipos de errores y cantidad]\n") . $errorTypes;

        //Concatenar errores por archivo
        $errorsByFile = implode("\n", $errorByFileStr);
        $additionalContent .= mb_strtoupper("\n\n[Errores por archivo]\n") . $errorsByFile;

        //Concatenar información adicional
        $content = $additionalContent . mb_strtoupper("\n================[PHPSTAN]================\n\n") . $content;

        //---Escribir
        file_put_contents($resultPath, $content);
    }
}
