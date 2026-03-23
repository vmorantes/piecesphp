<?php

declare (strict_types = 1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {

    $baseDir = realpath(dirname(__FILE__) . '/../../../');
    $filePathsMatch = [];
    $filePaths = [];
    preg_match_all('/^project:\/\/(.*)$/m', file_get_contents($baseDir . '/PHPStanResult.txt'), $filePathsMatch);
    
    
    foreach($filePathsMatch[1] ?? [] as $filePath) {
        $fullPath = $baseDir . '/' . $filePath;
        if(file_exists($fullPath)) {
            $filePaths[] = realpath($fullPath);
        }
    }

    // 1. Dónde tiene que buscar (las rutas de tu reporte)
    $rectorConfig->paths($filePaths);

    $rectorConfig->skip([
        $baseDir . '/src/app/vendor',
        $baseDir . '/src/app/core/Utilities.php',
        $baseDir . '/src/app/core/AppHelpers.php',
    ]);

    // 2. Qué reglas va a aplicar
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        // Esto ayuda a inferir y arreglar tipos (soluciona muchos argument.type)
        SetList::TYPE_DECLARATION,
    ]);
};
