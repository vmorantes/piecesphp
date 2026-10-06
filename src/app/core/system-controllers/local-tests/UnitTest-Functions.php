<?php

//`systemOutFormatted()` tiene DOS modos y los dos son la función: embellecer en terminal y
//salir limpia hacia una tubería o un archivo. Se prueban los dos, sin pseudo-terminal. Ver T124.

use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\CliActions;

$langGroup = 'TestPCSPHP-Lang';
$cliArguments = TerminalData::instance()->arguments();
$cliTaskName = 'unit-tests';
$unitTests = [
    [
        'name' => 'functions/systemOutFormatted',
        'description' => 'Pruebas unitarias de la función systemOutFormatted, en sus DOS modos',
        'callback' => function ($args) {

            echoTerminal('[TEST:systemOutFormatted] Iniciando suite de pruebas unitarias...', true, "\r\n", '33');
            echoTerminal('');

            $passed = 0;
            $failed = 0;

            //Sin `$requiresTty` y sin omitidas: la condición de terminal se PASA, no se detecta.
            $checkResult = function ($condition, $name, $details = null) use (&$passed, &$failed) {
                $condition ? $passed++ : $failed++;
                $status = $condition ? "\e[32m[PASÓ]\e[33m" : "\e[31m[FALLÓ]\e[33m";
                echoTerminal("   $status $name");
                if ($details !== null) {
                    echoTerminal("      - Detalles: $details");
                }
                return $condition;
            };

            $ver = function (string $texto): string {
                return str_replace("\033", "\\e", $texto);
            };
            //La rama sin terminal no puede dejar NI UNA secuencia: lo que sale va a un archivo.
            $sinAnsi = function (string $texto): bool {
                return preg_match('/\033\[/', $texto) !== 1;
            };

            // --- PRUEBA 1: Formato Básico (Sin formato) ---
            echoTerminal('[1/9] Probando salida básica sin formato...');
            $out = systemOutFormatted('Normal', ['newLine' => false, 'color' => 'default', 'background' => 'default'], true);
            $hasNoCodes = strpos($out, "\033[") === false || $out === "\033[39;49mNormal\033[0m" || $out === "Normal";
            $checkResult($hasNoCodes, "Salida plana / Base", "Obtenido: " . $ver($out));
            echoTerminal(' ');

            // --- PRUEBA 2: Colores por nombre vs numérico ---
            echoTerminal('[2/9] Probando colores por nombre vs numérico...');
            $outName = systemOutFormatted('Rojo', ['color' => 'red', 'newLine' => false], true);
            $outNum = systemOutFormatted('Rojo', ['color' => 31, 'newLine' => false], true);
            $checkResult($outName === $outNum && strpos($outName, '31') !== false, "CON terminal: nombre 'red' == código 31", "Obtenido: " . $ver($outName));
            $plano = systemOutFormatted('Rojo', ['color' => 'red', 'newLine' => false], false);
            $checkResult($sinAnsi($plano) && $plano === 'Rojo', "SIN terminal: sale 'Rojo' y ni una secuencia", "Obtenido: " . $ver($plano));
            echoTerminal(' ');

            // --- PRUEBA 3: Fondo por nombre vs numérico ---
            echoTerminal('[3/9] Probando fondo por nombre vs numérico...');
            $outBgName = systemOutFormatted('Fondo Azul', ['background' => 'blue', 'newLine' => false], true);
            $outBgNum = systemOutFormatted('Fondo Azul', ['background' => 44, 'newLine' => false], true);
            $checkResult($outBgName === $outBgNum && strpos($outBgName, '44') !== false, "CON terminal: nombre 'blue' == código 44", "Obtenido: " . $ver($outBgName));
            $plano = systemOutFormatted('Fondo Azul', ['background' => 'blue', 'newLine' => false], false);
            $checkResult($sinAnsi($plano) && $plano === 'Fondo Azul', "SIN terminal: sale el texto y ni una secuencia", "Obtenido: " . $ver($plano));
            echoTerminal(' ');

            // --- PRUEBA 4: Opciones de estilo (Negrita, Itálica, etc.) ---
            echoTerminal('[4/9] Probando opciones de estilo booleanas...');
            $estilos = ['bold' => true, 'italic' => true, 'underline' => true, 'newLine' => false];
            $outStyles = systemOutFormatted('Estilizado', $estilos, true);
            $hasBold = strpos($outStyles, '1') !== false;
            $hasItalic = strpos($outStyles, '3') !== false;
            $hasUnderline = strpos($outStyles, '4') !== false;
            $checkResult($hasBold && $hasItalic && $hasUnderline, "CON terminal: 1 (bold), 3 (italic) y 4 (underline)", "Obtenido: " . $ver($outStyles));
            $plano = systemOutFormatted('Estilizado', $estilos, false);
            $checkResult($sinAnsi($plano) && $plano === 'Estilizado', "SIN terminal: ningún estilo se cuela", "Obtenido: " . $ver($plano));
            echoTerminal(' ');

            // --- PRUEBA 5: Formato en lista (Simplificado) ---
            echoTerminal('[5/9] Probando formato en lista plana...');
            $lista = ['red', 'bold', 'italic', 'newLine' => false];
            $outList = systemOutFormatted('Lista', $lista, true);
            $checkResult(strpos($outList, '31') !== false && strpos($outList, '1') !== false && strpos($outList, '3') !== false, "CON terminal: lista ['red', 'bold', 'italic']", "Obtenido: " . $ver($outList));
            $plano = systemOutFormatted('Lista', $lista, false);
            $checkResult($sinAnsi($plano) && $plano === 'Lista', "SIN terminal: la lista tampoco se cuela", "Obtenido: " . $ver($plano));
            echoTerminal(' ');

            // --- PRUEBA 6: Formato mixto ---
            echoTerminal('[6/9] Probando formato mixto (asociativo + lista)...');
            $mixto = ['color' => 'yellow', 'background' => 'red', 'underline' => true, 'bold', 'newLine' => false];
            $outMixed = systemOutFormatted('Mixto', $mixto, true);
            $allPresent = true;
            foreach (['33', '41', '1', '4'] as $c) {
                if (strpos($outMixed, $c) === false) {
                    $allPresent = false;
                }
            }
            $checkResult($allPresent, "CON terminal: Yellow (33), BgRed (41), Underline (4), Bold (1)", "Obtenido: " . $ver($outMixed));
            $plano = systemOutFormatted('Mixto', $mixto, false);
            $checkResult($sinAnsi($plano) && $plano === 'Mixto', "SIN terminal: el mixto sale limpio", "Obtenido: " . $ver($plano));
            echoTerminal(' ');

            // --- PRUEBA 7: Configuraciones Globales (terminal_color) ---
            echoTerminal('[7/9] Probando herencia de terminal_color...');
            set_config('terminal_color', 'magenta');
            $outGlobal = systemOutFormatted('Global', ['newLine' => false], true);
            $checkResult(strpos($outGlobal, '35') !== false, "CON terminal: hereda 'magenta' (35) de get_config", "Obtenido: " . $ver($outGlobal));
            $plano = systemOutFormatted('Global', ['newLine' => false], false);
            $checkResult($sinAnsi($plano) && $plano === 'Global', "SIN terminal: la herencia global tampoco pinta", "Obtenido: " . $ver($plano));
            set_config('terminal_color', null);
            echoTerminal(' ');

            // --- PRUEBA 8: Configuraciones Globales (terminal_format_options) ---
            echoTerminal('[8/9] Probando herencia de terminal_format_options...');
            set_config('terminal_format_options', ['bold' => true, 'underline' => true]);
            $outGlobalOpt = systemOutFormatted('GlobalOpts', ['newLine' => false], true);
            $checkResult(strpos($outGlobalOpt, '1') !== false && strpos($outGlobalOpt, '4') !== false, "CON terminal: hereda negrita (1) y subrayado (4)", "Obtenido: " . $ver($outGlobalOpt));
            $plano = systemOutFormatted('GlobalOpts', ['newLine' => false], false);
            $checkResult($sinAnsi($plano) && $plano === 'GlobalOpts', "SIN terminal: las opciones globales tampoco", "Obtenido: " . $ver($plano));

            $outOverride = systemOutFormatted('Override', ['bold' => false, 'newLine' => false], true);
            $checkResult(strpos($outOverride, '1') === false, "Sobrescritura local de opción global (bold => false)", "Obtenido: " . $ver($outOverride));

            set_config('terminal_format_options', null);
            echoTerminal(' ');

            // --- PRUEBA 9: NewLine y NewLineChars ---
            echoTerminal('[9/9] Probando NewLine y NewLineChars...');
            $outNL = systemOutFormatted('Línea', ['newLine' => true, 'newLineChars' => "\n"], true);
            $checkResult(strpos($outNL, 'Línea') !== false, "Ejecución sin errores de NewLine", "Obtenido: " . $ver($outNL));
            echoTerminal(' ');

            $total = $passed + $failed;
            echoTerminal(' ');
            echoTerminal(str_repeat('=', 80));
            echoTerminal(" BALANCE FINAL: {$passed}/{$total} PASADAS" . ($failed > 0 ? ", {$failed} FALLIDAS" : '') . ' ');
            echoTerminal(str_repeat('=', 80));
            echoTerminal('[TEST:systemOutFormatted] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
            echoTerminal('');

            //Antes devolvía `true` SIEMPRE: la suite no podía poner el corredor en rojo. Ver T124.
            return [
                'success' => $failed === 0,
                'message' => "{$passed}/{$total}",
            ];
        },
    ],
];

foreach ($unitTests as $unitTest) {
    $cliTaskFlag = $unitTest['name'];
    $cliTaskDescription = $unitTest['description'];
    $cliTaskCallback = $unitTest['callback'];
    CliActions::make("{$cliTaskName}:{$cliTaskFlag}", $cliTaskCallback)->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NONE])->register();
}
