<?php

declare (strict_types = 1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {

    $baseDir = realpath(dirname(__FILE__) . '/../../../');
    $filePaths = [];
    $descartadas = [];

    /**
     * DE DÓNDE SALE LA LISTA DE ARCHIVOS.
     *
     * Antes se leía PHPStanResult.txt con una expresión regular. Eso tenía un defecto
     * silencioso y caro: el formateador de tabla de PHPStan RECORTA la cabecera de cada
     * archivo al ancho de terminal, que con la salida redirigida es 80. Las rutas largas
     * llegaban cortadas a 73 caracteres, `file_exists()` fallaba, y el bucle las
     * descartaba sin decir nada. Medido: 34 de 195 archivos —el 17% de la superficie con
     * errores— nunca entraron al análisis. Rector no fallaba; simplemente no los veía,
     * y por eso parecía proponer tan poco.
     *
     * Ahora la fuente de verdad es PHPStanResult.json, que es salida de máquina y no
     * depende del ancho de terminal. El .txt queda solo como respaldo para cuando se
     * ejecuta Rector sin haber regenerado el JSON.
     */
    $jsonPath = $baseDir . '/PHPStanResult.json';
    $txtPath = $baseDir . '/PHPStanResult.txt';

    if (is_file($jsonPath)) {
        $reporte = json_decode((string) file_get_contents($jsonPath), true);
        foreach (array_keys($reporte['files'] ?? []) as $rutaAbsoluta) {
            $real = realpath((string) $rutaAbsoluta);
            if ($real !== false) {
                $filePaths[] = $real;
            } else {
                $descartadas[] = (string) $rutaAbsoluta;
            }
        }
    } elseif (is_file($txtPath)) {
        fwrite(STDERR, "[Rector] AVISO: no hay PHPStanResult.json; se usa la tabla, que puede traer rutas recortadas. Ejecuta bin/phpstan para regenerarlo.\n");
        $filePathsMatch = [];
        preg_match_all('/^project:\/\/(.*)$/m', (string) file_get_contents($txtPath), $filePathsMatch);
        foreach ($filePathsMatch[1] ?? [] as $filePath) {
            $real = realpath($baseDir . '/' . $filePath);
            if ($real !== false) {
                $filePaths[] = $real;
            } else {
                $descartadas[] = $filePath;
            }
        }
    }

    $filePaths = array_values(array_unique($filePaths));

    /**
     * Descartar en silencio es lo que ocultó el defecto durante meses. Si una ruta no
     * resuelve, que se vea: sin lista de archivos Rector no analiza nada y devolvería
     * «0 cambios», que es indistinguible de «todo está bien».
     */
    if ($descartadas !== []) {
        fwrite(STDERR, '[Rector] AVISO: ' . count($descartadas) . " ruta(s) del reporte no resuelven a un archivo y quedan FUERA del análisis:\n");
        foreach ($descartadas as $ruta) {
            fwrite(STDERR, '  - ' . $ruta . "\n");
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
