<?php

/**
 * ConfigOptions.php
 */
namespace PiecesPHP\Core\ConfigHelpers;

use PiecesPHP\Core\Crypto\Sealed;
use PiecesPHP\Settings\ORM\SettingsModel;

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
    /**
     * Texto que se guarda sellado (Sealed::PURPOSE_CONFIG_SECRET) y que applyDeclared() abre al cargar: get_config() da
     * la clave en claro. A diferencia de SHAPE_SEALED, que abre su dueño.
     */
    const SHAPE_SECRET = 'secret';

    const SHAPES = [
        self::SHAPE_TEXT,
        self::SHAPE_INTEGER,
        self::SHAPE_BOOL,
        self::SHAPE_COLOR,
        self::SHAPE_JSON,
        self::SHAPE_SEALED,
        self::SHAPE_SECRET,
    ];

    const SECRET_EMPTY = 'empty';
    const SECRET_OPENED = 'opened';
    const SECRET_UNREADABLE = 'unreadable';
    const SECRET_LEGACY = 'legacy';

    /**
     * Solo para las suites: hace fallar el guardado sellado de lo de antes, para medir que la clave en claro queda intacta.
     */
    protected static bool $failSealLegacyForTesting = false;

    /**
     * Las SHAPE_SECRET ya avisadas en este proceso: sin permiso para escribir WARNED_FILE (la terminal, si lo creó el
     * servidor) no se repite el aviso en cada carga del mismo proceso.
     *
     * @var array<string,true>
     */
    protected static array $unreadableWarnedInProcess = [];

    /**
     * Los ya avisados de verdad (relativo a basepath(); clean-cache lo borra), cada uno con null. Lo de una corrida de
     * prueba no entra: queda en $warnedInTestRun y una prueba no gasta el aviso de verdad (ADR 0063, H55).
     */
    const WARNED_FILE = 'app/cache/config-options-warned.json';

    /**
     * Lo avisado en una corrida de prueba: solo en la memoria del proceso, nunca en WARNED_FILE (H55).
     *
     * @var array<string,string>
     */
    protected static array $warnedInTestRun = [];

    /**
     * Si ya se dijo en este proceso que WARNED_FILE no se puede escribir.
     */
    protected static bool $unwritableWarnedLogged = false;

    /**
     * Los nombres a los que el archivo de claves dio valor en este proceso (set_configs_from_secure_keys()): manda él.
     *
     * @var array<string,true>
     */
    protected static array $fixedByFile = [];

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
            if (self::isFixedByFile($name)) {
                //Manda el archivo de claves (P318): la fila no se aplica, pero si es un secreto en claro se sella igual (P310 a).
                if ((self::$options[$name]['shape'] ?? null) === self::SHAPE_SECRET && is_string($value) && $value !== '' && !Sealed::isSealed($value)) {
                    self::sealLegacySecret($name, $value);
                }
                continue;
            }
            if (self::isDeclared($name)) {
                set_config($name, self::$options[$name]['shape'] === self::SHAPE_SECRET ? self::loadSecret($name, $value) : $value);
            } else {
                $skipped[] = $name;
            }
        }
        return $skipped;
    }

    /**
     * Apunta que el archivo de claves dio valor a esta configuración: applyDeclared() ya no la pisa con la fila.
     *
     * @param string $name
     * @return void
     */
    public static function markFixedByFile(string $name): void
    {
        self::$fixedByFile[$name] = true;
    }

    /**
     * @return string[] Los nombres a los que el archivo de claves dio valor en este proceso
     */
    public static function fixedByFile(): array
    {
        return array_keys(self::$fixedByFile);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isFixedByFile(string $name): bool
    {
        return isset(self::$fixedByFile[$name]);
    }

    /**
     * SOLO PARA PRUEBAS: cambia los nombres fijados por el archivo y devuelve los de antes, para reponerlos.
     *
     * @internal
     * @param string[]|null $restore
     * @return string[]
     */
    public static function swapFixedByFileForTesting(?array $restore = null): array
    {
        $previous = self::fixedByFile();
        self::$fixedByFile = array_fill_keys($restore ?? [], true);
        return $previous;
    }

    /**
     * Qué hacer con lo guardado de una SHAPE_SECRET (pura): vacío o no texto, tal cual; sellado, lo abierto, o '' si no
     * abre; en claro (lo de antes), tal cual y para sellar.
     *
     * @param mixed $stored Lo que trae la base
     * @param string|null $opened Sealed::open() de lo guardado, si estaba sellado y abrió
     * @return array{0:mixed,1:string} El valor para get_config() y uno de SECRET_*
     */
    public static function decideSecret(mixed $stored, ?string $opened): array
    {
        if (!is_string($stored) || $stored === '') {
            return [$stored, self::SECRET_EMPTY];
        }
        if (Sealed::isSealed($stored)) {
            return $opened !== null ? [$opened, self::SECRET_OPENED] : ['', self::SECRET_UNREADABLE];
        }
        return [$stored, self::SECRET_LEGACY];
    }

    /**
     * El sitio único que sella una clave antes de guardarla: vacía sigue vacía y lo ya sellado no se vuelve a sellar.
     *
     * @param string $plain
     * @return string
     */
    public static function sealSecret(string $plain): string
    {
        return $plain === '' || Sealed::isSealed($plain) ? $plain : Sealed::seal($plain, Sealed::PURPOSE_CONFIG_SECRET);
    }

    /**
     * Lo que se escribe en la base para una opción declarada: sellado si es SHAPE_SECRET (sealSecret()), tal cual si no.
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public static function valueToStore(string $name, mixed $value): mixed
    {
        $isSecret = (self::$options[$name]['shape'] ?? null) === self::SHAPE_SECRET;
        return $isSecret && is_string($value) ? self::sealSecret($value) : $value;
    }

    /**
     * @param bool $fail
     * @return void
     */
    public static function failSealLegacyForTesting(bool $fail): void
    {
        self::$failSealLegacyForTesting = $fail;
    }

    /**
     * La clave en claro de una SHAPE_SECRET al cargar. Si no abre (otra app_key) da '' y lo avisa una vez, con el nombre
     * y nunca el valor; lo de antes (en claro) se guarda sellado una vez.
     *
     * @param string $name
     * @param mixed $stored
     * @return mixed
     */
    protected static function loadSecret(string $name, mixed $stored): mixed
    {
        $opened = is_string($stored) && Sealed::isSealed($stored) ? Sealed::open($stored, Sealed::PURPOSE_CONFIG_SECRET) : null;
        [$value, $state] = self::decideSecret($stored, $opened);
        if ($state === self::SECRET_UNREADABLE) {
            self::warnUnreadableSecret($name);
        } elseif ($state === self::SECRET_LEGACY && is_string($stored)) {
            self::sealLegacySecret($name, $stored);
        }
        return $value;
    }

    /**
     * Lo de antes se guarda sellado una vez (como MailConfig::sealLegacy()), solo si lo leído es lo que hay en la base:
     * una prueba que fija la opción en memoria no escribe en ella. Si falla, la clave en claro queda como estaba.
     *
     * @param string $name
     * @param string $stored
     * @return void
     */
    protected static function sealLegacySecret(string $name, string $stored): void
    {
        try {
            if (SettingsModel::getConfigValue($name) !== $stored) {
                return;
            }
            if (self::$failSealLegacyForTesting) {
                throw new \RuntimeException('Fallo provocado por la suite.');
            }
            //RETORNO-IGNORADO: si no se guarda, sigue en claro y se intenta en la próxima carga.
            SettingsModel::setConfigValue($name, self::sealSecret($stored));
        } catch (\Throwable $exception) {
            log_soft_error('No se pudo volver a guardar cifrada la opción ' . $name, ['error' => get_class($exception)]);
        }
    }

    /**
     * @param string $name
     * @return void
     */
    protected static function warnUnreadableSecret(string $name): void
    {
        $key = "secret-unreadable:{$name}";
        $run = \PiecesPHP\Core\CustomErrorsHandlers\GenericHandler::testRun();
        $warned = self::warned();
        if (isset(self::$unreadableWarnedInProcess[$key]) || (array_key_exists($key, $warned) && ($warned[$key] === null || $warned[$key] === $run))) {
            return;
        }
        self::$unreadableWarnedInProcess[$key] = true;
        log_exception(new \RuntimeException("La opción «{$name}» de la tabla de configuración está cifrada con otra clave de la aplicación: no se pudo abrir y se usa vacía. Vuelva a guardarla desde el panel."));
        $warned[$key] = $run;
        self::saveWarned($warned);
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
        return array_merge($result, self::$warnedInTestRun);
    }

    /**
     * Al archivo, solo lo real (null); lo de una corrida de prueba queda en memoria. Sin permiso de escritura se dice una
     * vez por proceso y se volverá a avisar, que es mejor que tumbar la petición.
     *
     * @param array<string,string|null> $warned
     * @return void
     */
    protected static function saveWarned(array $warned): void
    {
        self::$warnedInTestRun = array_filter($warned, fn (?string $run): bool => $run !== null);
        $real = array_filter($warned, fn (?string $run): bool => $run === null);
        $file = basepath(self::WARNED_FILE);
        $directory = dirname($file);
        $exists = is_file($file);
        $written = false;
        if (($exists && is_writable($file)) || (!$exists && is_dir($directory) && is_writable($directory))) {
            //umask 0002 al crearlo: 0664 del grupo heredado de app/cache (setgid), para que la terminal también escriba.
            $oldUmask = umask(0002);
            try {
                //Como objeto siempre, también vacío: un array vacío de JSON no distingue lista de mapa.
                $written = @file_put_contents($file, json_encode((object) $real, \JSON_UNESCAPED_UNICODE)) !== false;
            } finally {
                umask($oldUmask);
            }
        }
        if (!$written && !self::$unwritableWarnedLogged) {
            self::$unwritableWarnedLogged = true;
            log_exception(new \RuntimeException('No se puede escribir ' . self::WARNED_FILE . ': los avisos de las opciones se repetirán en cada proceso.'));
        }
    }
}
