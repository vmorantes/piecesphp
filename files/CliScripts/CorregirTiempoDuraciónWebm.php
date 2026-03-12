<?php

/* Procesamiento de entrada */
$cliArguments = [];
unset($argv[0]); //Eliminamos el primer argumento que es el nombre del script
array_map(function ($e) use (&$cliArguments) {
    $parts = explode('=', trim($e));
    if (count($parts) === 2) {
        $cliArguments[$parts[0]] = $parts[1];
    } else if (count($parts) == 1) {
        $cliArguments[$parts[0]] = '';
    }
}, $argv);

/* Función fallback para ejecutar comandos en el sistema */
$customExec = function(string $command): array {
    $disabledFunctions = explode(',', ini_get('disable_functions'));
    $disabledFunctions = array_map('trim', $disabledFunctions);
    
    $output = [];
    $resultCode = 0;

    // 1. Intentar con exec()
    if (function_exists('exec') && !in_array('exec', $disabledFunctions)) {
        exec($command, $output, $resultCode);
        return [
            'output' => $output,
            'resultCode' => $resultCode
        ];
    }

    // 2. Intentar con proc_open()
    if (function_exists('proc_open') && !in_array('proc_open', $disabledFunctions)) {
        $descriptorspec = [
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];
        
        $process = proc_open($command, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            $fullOutput = trim($stdout . "\n" . $stderr);
            if (!empty($fullOutput)) {
                $output = explode("\n", $fullOutput);
            }
            
            $resultCode = proc_close($process);
            return [
                'output' => $output,
                'resultCode' => $resultCode
            ];
        }
    }

    // 3. Fallback final (menos recomendado, pero salva la situación) con shell_exec()
    if (function_exists('shell_exec') && !in_array('shell_exec', $disabledFunctions)) {
        $rawOutput = shell_exec($command);
        if ($rawOutput !== null) {
            $output = explode("\n", trim($rawOutput));
            // Como shell_exec no da el código de error, asumiremos éxito si devolvió algo, 
            // aunque no es exacto. Es el trade-off de caer en este fallback.
            $resultCode = 0;
        } else {
            $resultCode = 1;
            $output[] = "Error ejecutando comando vía shell_exec() o salida nula.";
        }
        return [
            'output' => $output,
            'resultCode' => $resultCode
        ];
    }

    // 4. Si todo falló
    return [
        'output' => ["Error: No hay funciones disponibles para ejecutar comandos del sistema (exec, proc_open, shell_exec deshabilitados)."],
        'resultCode' => 1
    ];
};


/* Acciones en "help" */
if (isset($cliArguments['--help'])) {
    $helpMessages = [
        "\n",
        "Uso: php CorregirTiempoDuraciónWebm.php --updir=<directorio>",
        " [--glob=<pattern>]",
        " [--absolute]",
        " [--bkext=<ext>]",
        " [--fixext=<ext>]",
        " [--restorebk]",
        " [--preserveall]",
        "\n",
        "\n",
        "\t --updir: Siempre es relativo al directiorio de trabajo. A menos que se defina --absolute\n",
        "\t --glob: **/*.webm\n",
        "\t --absolute: Si está presente es true, si no false\n",
        "\t --bkext: .webm.bk\n",
        "\t --fixext: .fix.webm Se elimina al procesar\n",
        "\t --restorebk: Restaura el backup. Si está presente es true, si no false\n",
        "\t --preserveall: Preserva los tres archivos (fix, original y bk). Si está presente es true, si no false\n",
        "\t --preservefix: Preserva el archivo fix, aunque reemplaza el original. Si está presente es true, si no false\n",
        "\n",
    ];
    echo implode('', $helpMessages);
    exit(0);
}

/* Definición de parámetros esperados */
$isAbsolute = ($cliArguments['--absolute'] ?? null) !== null;
$isPreserveAll = ($cliArguments['--preserveall'] ?? null) !== null;
$isRestoreBk = ($cliArguments['--restorebk'] ?? null) !== null;
$isPreserveFix = ($cliArguments['--preservefix'] ?? null) !== null;
$uploadsDirectory = $cliArguments['--updir'] ?? uniqid('NON_EXISTS');
$globPattern = $cliArguments['--glob'] ?? '**/*.webm';
$bkExtension = $cliArguments['--bkext'] ?? '.bk.webm';
$fixedExtension = $cliArguments['--fixext'] ?? '.fix.webm';

//Ajustar directorio
if (!$isAbsolute) {
    $uploadsDirectory = str_replace(['//', '\\'], \DIRECTORY_SEPARATOR, getcwd() . DIRECTORY_SEPARATOR . $uploadsDirectory);
}
$uploadsDirectory = rtrim($uploadsDirectory, \DIRECTORY_SEPARATOR);

//Ajustar patrón
$globPattern = $uploadsDirectory . \DIRECTORY_SEPARATOR  . trim($globPattern, '/');

//Validar existencia del directorio
if (!file_exists($uploadsDirectory)) {
    echo "El directorio no existe" . PHP_EOL;
    exit(1);
}

/* Buscar */
$fileResults = glob($globPattern, GLOB_BRACE);

/* Iterar los resultados */
foreach ($fileResults as $filePath) {

    //Ignorar archivos fix y bk
    if (mb_strpos(basename($filePath), $fixedExtension) !== false || mb_strpos(basename($filePath), $bkExtension) !== false) {
        continue;
    }

    $fileDir = dirname($filePath);
    $fileExtension = '.webm';
    $fileName = mb_substr(basename($filePath), 0, mb_strpos(basename($filePath), $fileExtension));
    $tmpFilePath = $fileDir . \DIRECTORY_SEPARATOR  . $fileName . '.tmp.wav';
    $bkFilePath = $fileDir . \DIRECTORY_SEPARATOR  . $fileName . $bkExtension;
    $fixedFilePath = $fileDir . \DIRECTORY_SEPARATOR  . $fileName . $fixedExtension;
    $filePathEsc = escapeshellarg($filePath);
    $tmpFilePathEsc = escapeshellarg($tmpFilePath);
    $fixedFilePathEsc = escapeshellarg($fixedFilePath);

    if (!$isRestoreBk) {

        //-- Respaldar
        copy($filePath, $bkFilePath);

        //-- Procesar
        $shellActions = [
            "ffmpeg -y -v warning -i {$filePathEsc} {$tmpFilePathEsc} 2>&1",
            "ffmpeg -y -v warning -i {$tmpFilePathEsc} -c:a libopus {$fixedFilePathEsc} 2>&1",
            "rm -f {$tmpFilePathEsc} 2>&1",
        ];

        // '&&' asegura que si un comando falla, el resto no se ejecute
        $command = implode(' && ', $shellActions);

        // Llamar customExec
        $result = $customExec($command);
        $output = $result['output'];
        $resultCode = $result['resultCode'];

        if ($resultCode === 0) {
            echo "Exito al procesar: {$filePath}\n";
            if (!$isPreserveAll) {
                @unlink($filePath);
                if (!$isPreserveFix) {
                    @rename($fixedFilePath, $filePath);
                } else {
                    @copy($fixedFilePath, $filePath);
                }
            }
        } else {
            echo "Error ({$resultCode}) procesando: {$filePath}\n";
            echo "Detalles del error:\n" . implode("\n", $output) . "\n";
        }

    } else {
        if (file_exists($filePath) && file_exists($bkFilePath)) {
            if (@unlink($filePath)) {
                if (@copy($bkFilePath, $filePath)) {
                    @unlink($bkFilePath);
                    echo "Exito al restaurar: {$filePath}\n";
                }
            }
        }
    }
}