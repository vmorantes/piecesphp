<?php

/**
 * ConfigOptions.php
 */
namespace PiecesPHP\Core\ConfigHelpers;

/**
 * ConfigOptions - El catálogo de las opciones a las que la base de datos puede dar valor (ADR 0055).
 *
 * Cada módulo declara las suyas donde registra sus rutas, una vez y con su clase como dueña. El arranque aplica las
 * filas de la tabla de configuración; las que nadie declaró no se aplican y se avisan una vez (segunda versión).
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ConfigOptions
{

    const SHAPE_TEXT = 'text';
    const SHAPE_INTEGER = 'integer';
    const SHAPE_BOOL = 'bool';
    const SHAPE_COLOR = 'color';
    const SHAPE_JSON = 'json';
    const SHAPE_SEALED = 'sealed';

    const SHAPES = [
        self::SHAPE_TEXT,
        self::SHAPE_INTEGER,
        self::SHAPE_BOOL,
        self::SHAPE_COLOR,
        self::SHAPE_JSON,
        self::SHAPE_SEALED,
    ];

    /**
     * Los ya avisados (relativo a basepath(); clean-cache lo borra), con la corrida de prueba que avisó cada uno o null si
     * fue real: una prueba no gasta el aviso de verdad, que se poda con ella (ADR 0063).
     */
    const WARNED_FILE = 'app/cache/config-options-warned.json';

    /**
     * @var array<string,array{owner:string,shape:string|null}>
     */
    protected static array $options = [];

    /**
     * Declara una opción. Otro dueño para el mismo nombre lanza: una sola fuente (condición del PO, ADR 0055 §2).
     *
     * @param string $name El nombre de la fila en la tabla de configuración
     * @param string $owner La clase dueña (::class)
     * @param string|null $shape Una de SHAPES, o null si no se sabe
     * @return void
     * @throws \InvalidArgumentException si el nombre está vacío o la forma no es una de SHAPES
     * @throws \LogicException si ya la declaró otro dueño, o el mismo con otra forma
     */
    public static function declare(string $name, string $owner, ?string $shape = null): void
    {
        if ($name === '' || $owner === '') {
            throw new \InvalidArgumentException('Una opción se declara con nombre y dueño.');
        }
        if ($shape !== null && !in_array($shape, self::SHAPES, true)) {
            throw new \InvalidArgumentException("La forma «{$shape}» de la opción «{$name}» no es una de ConfigOptions::SHAPES.");
        }
        if (array_key_exists($name, self::$options)) {
            $previous = self::$options[$name];
            //Repetir la misma declaración no cambia nada (un módulo puede registrar sus rutas más de una vez).
            if ($previous['owner'] === $owner && $previous['shape'] === $shape) {
                return;
            }
            throw new \LogicException("La opción «{$name}» ya la declara {$previous['owner']}: no puede declararla también {$owner}.");
        }
        self::$options[$name] = ['owner' => $owner, 'shape' => $shape];
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isDeclared(string $name): bool
    {
        return array_key_exists($name, self::$options);
    }

    /**
     * @return array<string,array{owner:string,shape:string|null}> nombre => dueño y forma, por nombre
     */
    public static function all(): array
    {
        $options = self::$options;
        ksort($options);
        return $options;
    }

    /**
     * Los nombres de la lista que nadie declaró, sin repetir y en el orden en que llegan.
     *
     * @param array<int|string,mixed> $names
     * @return string[]
     */
    public static function undeclared(array $names): array
    {
        $undeclared = [];
        foreach ($names as $name) {
            $name = (string) $name;
            if (!self::isDeclared($name) && !in_array($name, $undeclared, true)) {
                $undeclared[] = $name;
            }
        }
        return $undeclared;
    }

    /**
     * Aplica con set_config() solo las opciones de la base que algún módulo declaró, y devuelve las que no aplicó.
     * Segunda versión del ADR 0055: una fila sin dueño deja de pisar la configuración.
     *
     * @param array<int|string,mixed> $configurations nombre => valor, lo que cargó el arranque de la base
     * @return string[] Los nombres que no se aplicaron, en el orden en que llegan
     */
    public static function applyDeclared(array $configurations): array
    {
        $skipped = [];
        foreach ($configurations as $name => $value) {
            $name = (string) $name;
            if (self::isDeclared($name)) {
                set_config($name, $value);
            } else {
                $skipped[] = $name;
            }
        }
        return $skipped;
    }

    /**
     * Registra con log_exception() cada nombre no declarado que aún no se avisó, y lo apunta en WARNED_FILE. Segunda
     * versión (ADR 0055): la fila ya no se aplica (applyDeclared); esto solo avisa. El mensaje lleva el nombre, nunca
     * el valor.
     *
     * @param array<int|string,mixed> $names Los nombres de las filas que cargó el arranque
     * @return string[] Los nombres avisados en esta llamada
     */
    public static function warnUndeclared(array $names): array
    {
        $undeclared = self::undeclared($names);
        if ($undeclared === []) {
            return [];
        }
        $run = \PiecesPHP\Core\CustomErrorsHandlers\GenericHandler::testRun();
        $warned = self::warned();
        $new = array_values(array_filter($undeclared, fn (string $name): bool => !array_key_exists($name, $warned) || ($warned[$name] !== null && $warned[$name] !== $run)));
        foreach ($new as $name) {
            log_exception(new \RuntimeException("La opción «{$name}» de la tabla de configuración no está declarada en el catálogo (ConfigOptions, ADR 0055): no se aplica. Si es legítima, la declara su módulo; si no, se borra su fila."));
            $warned[$name] = $run;
        }
        if ($new !== []) {
            self::saveWarned($warned);
        }
        return $new;
    }

    /**
     * Quita un nombre de los ya avisados, para que vuelva a avisarse (lo usan las pruebas al limpiar lo suyo).
     *
     * @param string $name
     * @return void
     */
    public static function forgetWarned(string $name): void
    {
        $warned = self::warned();
        if (array_key_exists($name, $warned)) {
            unset($warned[$name]);
            self::saveWarned($warned);
        }
    }

    /**
     * @return array<string,string|null> nombre => corrida de prueba que lo avisó, o null si fue real
     */
    protected static function warned(): array
    {
        $file = basepath(self::WARNED_FILE);
        $content = is_file($file) ? @file_get_contents($file) : false;
        $warned = is_string($content) ? json_decode($content, true) : null;
        $result = [];
        foreach (is_array($warned) ? $warned : [] as $name => $run) {
            if (is_string($name) && ($run === null || is_string($run))) {
                $result[$name] = $run;
            }
        }
        return $result;
    }

    /**
     * Sin permiso de escritura no se apunta: se volverá a avisar, que es mejor que tumbar la petición.
     *
     * @param array<string,string|null> $warned
     * @return void
     */
    protected static function saveWarned(array $warned): void
    {
        $file = basepath(self::WARNED_FILE);
        $directory = dirname($file);
        if ((is_file($file) && is_writable($file)) || (!is_file($file) && is_dir($directory) && is_writable($directory))) {
            //Como objeto siempre, también vacío: un array vacío de JSON no distingue lista de mapa.
            //RETORNO-IGNORADO: si no se apunta, se vuelve a avisar; eso es todo lo que puede pasar.
            @file_put_contents($file, json_encode((object) $warned, \JSON_UNESCAPED_UNICODE));
        }
    }
}
