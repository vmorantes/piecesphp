<?php

declare (strict_types = 1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {

    $baseDir = realpath(dirname(__FILE__) . '/../../../');
    $filePaths = [];
    $droppedPaths = [];

    /**
     * La fuente de verdad es el JSON, no la tabla: la tabla recorta las rutas al ancho de
     * terminal y las largas dejan de resolver. El .txt es solo respaldo.
     */
    $jsonPath = $baseDir . '/PHPStanResult.json';
    $txtPath = $baseDir . '/PHPStanResult.txt';

    if (is_file($jsonPath)) {
        $report = json_decode((string) file_get_contents($jsonPath), true);
        foreach (array_keys($report['files'] ?? []) as $absolutePath) {
            $resolved = realpath((string) $absolutePath);
            if ($resolved !== false) {
                $filePaths[] = $resolved;
            } else {
                $droppedPaths[] = (string) $absolutePath;
            }
        }
    } elseif (is_file($txtPath)) {
        fwrite(STDERR, "[Rector] AVISO: no hay PHPStanResult.json; se usa la tabla, que puede traer rutas recortadas. Ejecuta bin/phpstan para regenerarlo.\n");
        $filePathsMatch = [];
        preg_match_all('/^project:\/\/(.*)$/m', (string) file_get_contents($txtPath), $filePathsMatch);
        foreach ($filePathsMatch[1] ?? [] as $filePath) {
            $resolved = realpath($baseDir . '/' . $filePath);
            if ($resolved !== false) {
                $filePaths[] = $resolved;
            } else {
                $droppedPaths[] = $filePath;
            }
        }
    }

    $filePaths = array_values(array_unique($filePaths));

    //Nunca descartar en silencio: «0 cambios» y «no miré nada» son indistinguibles.
    if ($droppedPaths !== []) {
        fwrite(STDERR, '[Rector] AVISO: ' . count($droppedPaths) . " ruta(s) del reporte no resuelven a un archivo y quedan FUERA del análisis:\n");
        foreach ($droppedPaths as $path) {
            fwrite(STDERR, '  - ' . $path . "\n");
        }
    }

    if ($filePaths === []) {
        fwrite(STDERR, "[Rector] ERROR: la lista de archivos está vacía. Ejecuta bin/phpstan antes que bin/rector.\n");
        exit(1);
    }

    fwrite(STDERR, '[Rector] ' . count($filePaths) . " archivo(s) entran al análisis.\n");

    // 1. Dónde tiene que buscar (las rutas de tu reporte)
    $rectorConfig->paths($filePaths);

    // FUERZA la compatibilidad de salida al PISO de src/composer.json (>=8.4.1 <8.6).
    // Estaba en PHP_81 desde antes de la migración a 8.4: se corrigió el de piecesphp/database
    // y este se quedó atrás. Rector no emitirá sintaxis por encima del piso.
    $rectorConfig->phpVersion(PhpVersion::PHP_84);

    /**
     * Los módulos marcados para borrarse no reciben NADA, tampoco de Rector: refactorizar
     * código que va a desaparecer es trabajo tirado. Ver `.agents/context/18-siguientes-ventanas.md`,
     * sección T6, que es donde se decide el destino de cada módulo.
     */
    $rectorConfig->skip([
        $baseDir . '/src/app/vendor',
        $baseDir . '/src/app/core/Utilities.php',
        $baseDir . '/src/app/core/AppHelpers.php',
        $baseDir . '/src/app/classes/ImagesRepository',
        $baseDir . '/src/app/classes/ApplicationCalls',
        $baseDir . '/src/app/classes/InterestResearchAreas',
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_85, // Aplicar modernización hasta 8.5 (limitado por phpVersion 8.1)
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
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
