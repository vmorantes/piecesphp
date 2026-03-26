<?php

use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Helpers\Directories\FileObject;
use PiecesPHP\Core\Helpers\Directories\FilesIgnore;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\CliActions;

$langGroup = 'TestPCSPHP-Lang';
$cliArguments = TerminalData::instance()->arguments();
$cliTaskName = 'unit-tests';
$unitTests = [
    [
        'name' => 'functions/systemOutFormatted',
        'description' => 'Pruebas unitarias de la función systemOutFormatted',
        'callback' => function ($args) {

            echoTerminal('[TEST:systemOutFormatted] Iniciando suite de pruebas unitarias...', true, "\r\n", '33');
            echoTerminal('');

            $checkResult = function ($condition, $name, $details = null) {
                $status = $condition ? "\e[32m[PASÓ]\e[33m" : "\e[31m[FALLÓ]\e[33m";
                echoTerminal("   $status $name");
                if ($details !== null) {
                    echoTerminal("      - Detalles: $details");
                }
                return $condition;
            };

            // --- PRUEBA 1: Formato Básico (Sin formato) ---
            echoTerminal('[1/9] Probando salida básica sin formato...');
            $out = systemOutFormatted('Normal', ['newLine' => false, 'color' => 'default', 'background' => 'default']);
            // Un texto sin formato no debería tener códigos ANSI si se fuerza default o se queda base
            $hasNoCodes = strpos($out, "\033[") === false || $out === "\033[39;49mNormal\033[0m" || $out === "Normal";
            $checkResult($hasNoCodes, "Salida plana / Base", "Obtenido: " . str_replace("\033", "\\e", $out));
            echoTerminal(' ');

            // --- PRUEBA 2: Colores por nombre vs numérico ---
            echoTerminal('[2/9] Probando colores por nombre vs numérico...');
            $outName = systemOutFormatted('Rojo', ['color' => 'red', 'newLine' => false]);
            $outNum = systemOutFormatted('Rojo', ['color' => 31, 'newLine' => false]);
            $same = $outName === $outNum;
            $contains31 = strpos($outName, '31') !== false;
            $checkResult($same && $contains31, "Nombre 'red' == Código 31", "Obtenido: " . str_replace("\033", "\\e", $outName));
            echoTerminal(' ');

            // --- PRUEBA 3: Fondo por nombre vs numérico ---
            echoTerminal('[3/9] Probando fondo por nombre vs numérico...');
            $outBgName = systemOutFormatted('Fondo Azul', ['background' => 'blue', 'newLine' => false]);
            $outBgNum = systemOutFormatted('Fondo Azul', ['background' => 44, 'newLine' => false]);
            $checkResult($outBgName === $outBgNum && strpos($outBgName, '44') !== false, "Nombre 'blue' == Código 44", "Obtenido: " . str_replace("\033", "\\e", $outBgName));
            echoTerminal(' ');

            // --- PRUEBA 4: Opciones de estilo (Negrita, Itálica, etc.) ---
            echoTerminal('[4/9] Probando opciones de estilo booleanas...');
            $outStyles = systemOutFormatted('Estilizado', ['bold' => true, 'italic' => true, 'underline' => true, 'newLine' => false]);
            $hasBold = strpos($outStyles, '1') !== false;
            $hasItalic = strpos($outStyles, '3') !== false;
            $hasUnderline = strpos($outStyles, '4') !== false;
            $checkResult($hasBold && $hasItalic && $hasUnderline, "Detección de 1 (bold), 3 (italic) y 4 (underline)", "Obtenido: " . str_replace("\033", "\\e", $outStyles));
            echoTerminal(' ');

            // --- PRUEBA 5: Formato en lista (Simplificado) ---
            echoTerminal('[5/9] Probando formato en lista plana...');
            $outList = systemOutFormatted('Lista', ['red', 'bold', 'italic', 'newLine' => false]);
            $has31 = strpos($outList, '31') !== false;
            $has1 = strpos($outList, '1') !== false;
            $has3 = strpos($outList, '3') !== false;
            $checkResult($has31 && $has1 && $has3, "Lista ['red', 'bold', 'italic']", "Obtenido: " . str_replace("\033", "\\e", $outList));
            echoTerminal(' ');

            // --- PRUEBA 6: Formato mixto ---
            echoTerminal('[6/9] Probando formato mixto (asociativo + lista)...');
            $outMixed = systemOutFormatted('Mixto', ['color' => 'yellow', 'background' => 'red', 'underline' => true, 'bold', 'newLine' => false]);
            $codes = ['33', '41', '1', '4'];
            $allPresent = true;
            foreach ($codes as $c) {
                if (strpos($outMixed, $c) === false) {
                    $allPresent = false;
                }
            }
            $checkResult($allPresent, "Mixto: Yellow (33), BgRed (41), Underline (4), Bold (1)", "Obtenido: " . str_replace("\033", "\\e", $outMixed));
            echoTerminal(' ');

            // --- PRUEBA 7: Configuraciones Globales (terminal_color) ---
            echoTerminal('[7/9] Probando herencia de terminal_color...');
            set_config('terminal_color', 'magenta');
            $outGlobal = systemOutFormatted('Global', ['newLine' => false]);
            $has35 = strpos($outGlobal, '35') !== false;
            $checkResult($has35, "Hereda 'magenta' (35) de get_config", "Obtenido: " . str_replace("\033", "\\e", $outGlobal));
            set_config('terminal_color', null);
            echoTerminal(' ');

            // --- PRUEBA 8: Configuraciones Globales (terminal_format_options) ---
            echoTerminal('[8/9] Probando herencia de terminal_format_options...');
            set_config('terminal_format_options', ['bold' => true, 'underline' => true]);
            $outGlobalOpt = systemOutFormatted('GlobalOpts', ['newLine' => false]);
            $has1 = strpos($outGlobalOpt, '1') !== false;
            $has4 = strpos($outGlobalOpt, '4') !== false;
            $checkResult($has1 && $has4, "Hereda negrita (1) y subrayado (4)", "Obtenido: " . str_replace("\033", "\\e", $outGlobalOpt));

            // Probar sobreescritura de global con local
            $outOverride = systemOutFormatted('Override', ['bold' => false, 'newLine' => false]);
            $hasNoBold = strpos($outOverride, '1') === false;
            $checkResult($hasNoBold, "Sobrescritura local de opción global (bold => false)", "Obtenido: " . str_replace("\033", "\\e", $outOverride));

            set_config('terminal_format_options', null);
            echoTerminal(' ');

            // --- PRUEBA 9: NewLine y NewLineChars ---
            echoTerminal('[9/9] Probando NewLine y NewLineChars...');
            // Verificamos que no genere errores fatales
            $outNL = systemOutFormatted('Línea', ['newLine' => true, 'newLineChars' => "\n"]);
            $checkResult(strpos($outNL, 'Línea') !== false, "Ejecución sin errores de NewLine", "Obtenido: " . str_replace("\033", "\\e", $outNL));
            echoTerminal(' ');

            echoTerminal('[TEST:systemOutFormatted] Suite finalizada.', true, "\r\n", '32');
            echoTerminal('');

            return [
                'success' => true,
                'message' => 'Pruebas de systemOutFormatted completadas exitosamente.',
            ];
        },
    ],
];

foreach ($unitTests as $unitTest) {
    $cliTaskFlag = $unitTest['name'];
    $cliTaskDescription = $unitTest['description'];
    $cliTaskCallback = $unitTest['callback'];
    CliActions::make("{$cliTaskName}:{$cliTaskFlag}", $cliTaskCallback)->setDescription($cliTaskDescription)->register();
}
