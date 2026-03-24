<?php

declare (strict_types = 1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {

    $baseDir = realpath(dirname(__FILE__) . '/../../../');
    $filePathsMatch = [];
    $filePaths = [];
    preg_match_all('/^project:\/\/(.*)$/m', file_get_contents($baseDir . '/PHPStanResult.txt'), $filePathsMatch);

    foreach ($filePathsMatch[1] ?? [] as $filePath) {
        $fullPath = $baseDir . '/' . $filePath;
        if (file_exists($fullPath)) {
            $filePaths[] = realpath($fullPath);
        }
    }

    // 1. Dónde tiene que buscar (las rutas de tu reporte)
    $rectorConfig->paths($filePaths);

    // FUERZA la compatibilidad de salida a PHP 8.1
    // Al mantener 8.1 aquí, Rector evitará usar características de 8.2+ (como clases readonly o constantes con tipo)
    // pero permitirá corregir deprecaciones que son válidas en ambas versiones.
    $rectorConfig->phpVersion(PhpVersion::PHP_81);

    $rectorConfig->skip([
        $baseDir . '/src/app/vendor',
        $baseDir . '/src/app/core/Utilities.php',
        $baseDir . '/src/app/core/AppHelpers.php',
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_85, // Aplicar modernización hasta 8.5 (limitado por phpVersion 8.1)
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
    ]);

    // Reglas específicas para compatibilidad futura (8.2, 8.3, 8.4 -> 8.5)
    // que son seguras de aplicar en PHP 8.1 pero resuelven deprecaciones de versiones posteriores.
    $rectorConfig->rules([
        // PHP 8.4: Parámetros nulables implícitos (deprecado)
        // Ejemplo: function foo(string $p = null) -> function foo(?string $p = null)
        \Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector::class,
        
        // PHP 8.3: get_class() sin argumentos (deprecado)
        \Rector\Php83\Rector\FuncCall\RemoveGetClassGetParentClassNoArgsRector::class,

        // PHP 8.2: Interpolación de variables ${var} (deprecado) -> se pasa a {$p}
        \Rector\Php82\Rector\Encapsed\VariableInStringInterpolationFixerRector::class,

        // PHP 8.2: utf8_encode/decode (deprecado) -> se pasa a mb_convert_encoding
        \Rector\Php82\Rector\FuncCall\Utf8DecodeEncodeToMbConvertEncodingRector::class,
    ]);
};
