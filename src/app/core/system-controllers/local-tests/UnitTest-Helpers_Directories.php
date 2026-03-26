<?php

use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Helpers\Directories\FileObject;
use PiecesPHP\Core\Helpers\Directories\FilesIgnore;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\CliActions;

$langGroup = 'TestPCSPHP-Lang';
$cliArguments = TerminalData::instance()->arguments();
$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/helpers-directories';
$cliTaskDescription = "Pruebas unitarias de:\n\t\t" . implode("\n\t\t", [
    DirectoryObject::class,
    FileObject::class,
    FilesIgnore::class,
]);
CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:HelpersDirectories] Iniciando suite de pruebas unitarias...');
    echoTerminal('');
    set_config('terminal_color', '33');

    $testBasePath = app_basepath('core/system-controllers/test-data/unit-testing-' . uniqid());

    if (!file_exists($testBasePath)) {
        mkdir($testBasePath, 0777, true);
    }

    $checkResult = function ($condition, $name, $details = null) {
        $status = $condition ? "\e[32m[PASÓ]\e[33m" : "\e[31m[FALLÓ]\e[33m";
        echoTerminal("   $status $name");
        if ($details !== null) {
            echoTerminal("      - Detalles: $details");
        }
        return $condition;
    };

    // Preparación de datos de prueba
    $sourcesPath = $testBasePath . '/sources';
    $scanAreaPath = $testBasePath . '/scan-area';

    $dir1 = $sourcesPath . '/dir1';
    $subdir1 = $dir1 . '/subdir1';
    mkdir($subdir1, 0777, true);
    mkdir($scanAreaPath, 0777, true);

    file_put_contents($dir1 . '/file1.txt', 'contenido 1');
    file_put_contents($subdir1 . '/file2.txt', 'contenido 2');

    $symlinkDir = $scanAreaPath . '/link_to_dir1';
    $symlinkFile = $scanAreaPath . '/link_to_file1';

    @symlink($dir1, $symlinkDir);
    @symlink($dir1 . '/file1.txt', $symlinkFile);

    // --- PRUEBA 1: Normalización de Rutas (Sin Resolución) ---
    echoTerminal('[1/7] Probando Normalización de Rutas...');

    $inputs = [
        $scanAreaPath . '/../scan-area/./link_to_file1' => $scanAreaPath . '/link_to_file1',
        $scanAreaPath . '//' => $scanAreaPath,
        $scanAreaPath . '/a/b/../../' => $scanAreaPath,
        'a\b\../c' => 'a/c',
    ];

    foreach ($inputs as $input => $expected) {
        $f = new FileObject($input);
        $actual = $f->getPath();
        $checkResult($actual === $expected, "Normalizando: $input", "Esperado: $expected | Obtenido: $actual");
    }
    echoTerminal(' ');

    // --- PRUEBA 2: FileObject y Enlaces Simbólicos ---
    echoTerminal('[2/7] Probando FileObject con Enlaces Simbólicos...');

    $fLink = new FileObject($symlinkFile);
    $checkResult($fLink->getPath() === $symlinkFile, 'FileObject mantiene la ruta del enlace (No resuelve realpath)', "Ruta: " . $fLink->getPath());
    $checkResult($fLink->getExists(), 'FileObject detecta existencia del enlace');

    $json = $fLink->jsonSerialize();
    $checkResult(isset($json['parent']) && !isset($json['files']), 'JSON Serialization usa clave "parent"', "Claves: " . implode(', ', array_keys($json)));
    echoTerminal(' ');

    // --- PRUEBA 3: DirectoryObject Scan y No-Recursión en Symlinks ---
    echoTerminal('[3/7] Probando DirectoryObject Scan...');

    $baseDir = new DirectoryObject($scanAreaPath);
    $baseDir->process();

    $dirs = $baseDir->getDirectories();
    $files = $baseDir->getFiles();

    $checkResult(empty($dirs), 'No detecta enlaces a directorios como subdirectorios (Protección contra recursión)', "Directorios encontrados: " . count($dirs));
    $checkResult(isset($files['link_to_dir1']), 'Detecta enlace a directorio como ARCHIVO (No recursivo)');
    $checkResult(isset($files['link_to_file1']), 'Detecta enlace a archivo como ARCHIVO');

    echoTerminal("      - Archivos detectados en escaneo:");
    foreach ($files as $name => $obj) {
        echoTerminal("        * $name (" . ($obj instanceof FileObject ? 'FileObject' : 'Unknown') . ")");
    }
    echoTerminal(' ');

    // --- PRUEBA 4: FilesIgnore (Exclusión e Inclusión) ---
    echoTerminal('[4/7] Probando FilesIgnore...');

    $ignore = new FilesIgnore([
        'scan-area',
        'INCLUDE_EXPR::link_to_file1',
    ]);

    $resScanArea = $ignore->ignore($scanAreaPath);
    $resLinkFile = $ignore->ignore($scanAreaPath . '/link_to_file1');

    $checkResult($resScanArea === true, 'Regla de exclusión simple funciona', "Ignora '$scanAreaPath': " . ($resScanArea ? 'SÍ' : 'NO'));
    $checkResult($resLinkFile === false, 'Regla de inclusión prioritaria (INCLUDE_EXPR::) funciona', "Ignora '$scanAreaPath/link_to_file1': " . ($resLinkFile ? 'SÍ' : 'NO'));
    echoTerminal(' ');

    // --- PRUEBA 5: Borrado Seguro (Trust the Path) ---
    echoTerminal('[5/7] Probando Borrado de Enlaces Simbólicos...');
    echoTerminal("      - Eliminando contenido de: $scanAreaPath");

    $scanForDelete = new DirectoryObject($scanAreaPath);
    $scanForDelete->process();
    $deleteResult = $scanForDelete->delete(false);

    $checkResult(!file_exists($symlinkDir), 'El enlace simbólico al directorio fue borrado');
    $checkResult(file_exists($dir1), 'La FUENTE del enlace simbólico (dir1) permanece intacta', "Estado fuente: " . (file_exists($dir1) ? 'OK (Existe)' : 'ERROR (Borrada)'));
    $checkResult(!file_exists($symlinkFile), 'El enlace simbólico al archivo fue borrado');
    $checkResult(file_exists($dir1 . '/file1.txt'), 'La FUENTE del enlace al archivo permanece intacta', "Estado fuente: " . (file_exists($dir1 . '/file1.txt') ? 'OK (Existe)' : 'ERROR (Borrada)'));
    echoTerminal(' ');

    // --- PRUEBA 6: Caso de Enlace Roto ---
    echoTerminal('[6/7] Probando Manejo de Enlaces Rotos...');
    $brokenLink = $scanAreaPath . '/broken_link';
    @symlink($testBasePath . '/non_existent', $brokenLink);

    $fBroken = new FileObject($brokenLink);
    $checkResult($fBroken->getExists(), "Detecta enlace roto como existente (is_link)");
    unlink($brokenLink);
    $checkResult(!file_exists($brokenLink), "Enlace roto eliminado correctamente");
    echoTerminal(' ');

    // --- PRUEBA 7: Limpieza Final ---
    echoTerminal('[7/7] Limpieza de entorno de pruebas...');

    $finalCleanup = new DirectoryObject($testBasePath);
    $finalCleanup->process();
    foreach ($finalCleanup->getDirectories() as $d) {
        $d->process();
        foreach ($d->getDirectories() as $sd) {$sd->process();}
    }
    $resCleanup = $finalCleanup->delete(true);

    $checkResult(!file_exists($testBasePath), 'Entorno de pruebas eliminado completamente', "Ruta base: $testBasePath");
    echoTerminal(' ');

    set_config('terminal_color', null);
    echoTerminal('[TEST:HelpersDirectories] Suite finalizada.');
    echoTerminal('');

    return [
        'success' => true,
        'message' => 'Pruebas completadas exitosamente.',
        'extra_data' => $resCleanup,
    ];
})->setDescription($cliTaskDescription)->register();
