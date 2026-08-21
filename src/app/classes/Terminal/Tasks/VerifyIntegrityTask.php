<?php

/**
 * VerifyIntegrityTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\TerminalData;

/**
 * VerifyIntegrityTask.
 *
 * Verificación de integridad estructural del código fuente.
 *
 * Existe por un incidente concreto: una sesión que solo tocaba docblocks dejó uno sin
 * cerrar, el comentario se tragó la declaración del método siguiente y ese método dejó
 * de existir. `php -l` no lo detecta —un docblock sin cerrar NO es un error de
 * sintaxis— y las pruebas tampoco, porque no había ninguna que llamara a ese método.
 *
 * Comprueba dos cosas, que son las que habrían servido:
 *
 *   1. Docblocks sin cerrar.
 *   2. Desaparición de funciones y métodos, comparando contra una instantánea.
 *
 * Devuelve código de salida distinto de cero si algo falla, para poder usarse en CI.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class VerifyIntegrityTask extends TerminalTaskAbstract
{

    /**
     * Instantánea de firmas, relativa a la RAÍZ DEL REPOSITORIO, no a `src/`.
     * `basepath()` resuelve dentro de `src/`, y `files/dev/` vive un nivel por encima,
     * junto a `tests.md`.
     *
     * Se versiona a propósito: sin ella en el repositorio, la comprobación no puede
     * detectar nada en una máquina limpia ni en CI.
     *
     * @var string
     */
    const SNAPSHOT_RELATIVE_PATH = 'files/dev/integrity-signatures.json';

    /**
     * Ruta absoluta de la instantánea.
     *
     * @return string
     */
    protected static function snapshotPath(): string
    {
        return dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/' . self::SNAPSHOT_RELATIVE_PATH;
    }

    /**
     * Directorios analizados, relativos a `src/`.
     *
     * @var string[]
     */
    const SCAN_PATHS = [
        'app',
        'index.php',
    ];

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        //Procesar entrada
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'verify-integrity';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Verifica la integridad estructural del código fuente.\r\n",
            "\tComprueba docblocks sin cerrar y desaparición de funciones o métodos.\r\n",
            "\tDevuelve código de salida 1 si algo falla, para uso en CI.\r\n",
            "\tParámetros:\r\n",
            "\t  update-snapshot (yes|no) regenera la instantánea de firmas en vez de comparar. Por defecto: no",
        ]);
        $this->route = "{$startRoute}/verify-integrity[/]";
        $this->controller = self::class . '::main';
        $this->name = $name;
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray($permissions);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = "Verificando integridad del código";
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $updateSnapshot = TerminalData::instance()->getArgument('update-snapshot', 'no') === 'yes';

        $files = self::collectFiles();
        echoTerminal("\e[94mINFO:\e[39m " . count($files) . " archivos PHP analizados.");

        //──── 1. Docblocks sin cerrar ───────────────────────────────────────────────────
        $docblockFailures = self::checkDocblocks($files);

        //──── 2. Inventario de firmas ───────────────────────────────────────────────────
        $signatures = self::collectSignatures($files);
        $snapshotPath = self::snapshotPath();

        if ($updateSnapshot) {
            self::writeSnapshot($snapshotPath, $signatures);
            $total = array_sum(array_map('count', $signatures));
            echoTerminal("\e[34mInstantánea regenerada:\e[39m {$total} firmas en " . count($signatures) . " archivos.");
            echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
            exit(0);
        }

        $signatureFailures = self::compareSignatures($snapshotPath, $signatures);

        //──── Resultado ─────────────────────────────────────────────────────────────────
        $failures = count($docblockFailures) + count($signatureFailures);

        foreach ($docblockFailures as $line) {
            echoTerminal("\e[31mDOCBLOCK:\e[39m {$line}");
        }
        foreach ($signatureFailures as $line) {
            echoTerminal("\e[31mFIRMA:\e[39m {$line}");
        }

        if ($failures === 0) {
            echoTerminal("\e[32mOK:\e[39m sin docblocks sin cerrar y sin firmas desaparecidas.");
            echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
            exit(0);
        }

        echoTerminal("\e[31mFALLOS: {$failures}\e[39m");
        echoTerminal("\e[33mSi los cambios son intencionados, regenera la instantánea con:\e[39m");
        echoTerminal("  bin/cli verify-integrity update-snapshot=yes");
        echoTerminal("\e[31m*** {$titleTask}, tarea finalizada CON FALLOS ***\e[39m");
        exit(1);
    }

    /**
     * Archivos PHP a analizar, con ruta relativa a `src/`.
     *
     * @return string[]
     */
    protected static function collectFiles(): array
    {
        $base = rtrim(str_replace('\\', '/', basepath('')), '/');
        $resultado = [];

        foreach (self::SCAN_PATHS as $relativo) {
            $absoluto = $base . '/' . $relativo;

            if (is_file($absoluto)) {
                $resultado[] = $relativo;
                continue;
            }
            if (!is_dir($absoluto)) {
                continue;
            }

            $iterador = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absoluto, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterador as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }
                $ruta = str_replace('\\', '/', $file->getPathname());
                $resultado[] = ltrim(str_replace($base, '', $ruta), '/');
            }
        }

        sort($resultado);
        return $resultado;
    }

    /**
     * Docblocks sin cerrar.
     *
     * Dos señales, porque cada una cubre un caso que la otra no ve:
     *
     *   a) Recuento desbalanceado de `/**` frente a `* /`. Detecta que a un docblock le
     *      falta el cierre.
     *   b) Un token de docblock que contiene `function `. Eso solo puede pasar si el
     *      comentario se tragó código, que es el daño real: el método deja de existir.
     *
     * @param string[] $files
     * @return string[]
     */
    protected static function checkDocblocks(array $files): array
    {
        $failures = [];
        $base = rtrim(str_replace('\\', '/', basepath('')), '/');

        foreach ($files as $relativo) {
            $contenido = @file_get_contents($base . '/' . $relativo);
            if (!is_string($contenido)) {
                continue;
            }

            //Se usa el analizador léxico, NO un recuento de texto: '/*' aparece dentro
            //de cadenas —'image/*' es el caso típico— y contarlo a pelo produce decenas
            //de falsos positivos en las vistas.
            foreach (@token_get_all($contenido) ?: [] as $token) {
                if (!is_array($token)) {
                    continue;
                }
                if (!in_array($token[0], [\T_COMMENT, \T_DOC_COMMENT], true)) {
                    continue;
                }
                $texto = $token[1];
                if (!str_starts_with($texto, '/*')) {
                    continue; //comentario de línea
                }

                //(a) Comentario de bloque sin cerrar hasta el final del archivo.
                if (substr(rtrim($texto), -2) !== '*/') {
                    $failures[] = "{$relativo}:{$token[2]}: comentario de bloque sin cerrar";
                    continue;
                }

                //(b) El caso que motivó esta tarea: al docblock le falta el cierre, el
                //    comentario se traga la declaración siguiente y llega hasta el '*/'
                //    del docblock de más abajo. El método deja de existir sin que ni
                //    'php -l' ni PHPStan digan nada.
                if (preg_match('/\bfunction\s+\w+\s*\(/', $texto)) {
                    $failures[] = "{$relativo}:{$token[2]}: un docblock se tragó una declaración de función; le falta el cierre";
                }
            }
        }

        return $failures;
    }

    /**
     * Inventario de firmas por archivo.
     *
     * Se usa el analizador léxico de PHP en vez de expresiones regulares: así una
     * declaración comentada no cuenta, que es justo lo que hay que distinguir.
     *
     * @param string[] $files
     * @return array<string,string[]>
     */
    protected static function collectSignatures(array $files): array
    {
        $base = rtrim(str_replace('\\', '/', basepath('')), '/');
        $inventario = [];

        foreach ($files as $relativo) {
            $contenido = @file_get_contents($base . '/' . $relativo);
            if (!is_string($contenido)) {
                continue;
            }

            $tokens = @token_get_all($contenido);
            if (!is_array($tokens)) {
                continue;
            }

            $signatures = [];
            $contexto = '';
            $total = count($tokens);

            for ($i = 0; $i < $total; $i++) {
                $token = $tokens[$i];
                if (!is_array($token)) {
                    continue;
                }

                //Contexto: clase, interfaz, trait o enum
                if (in_array($token[0], [\T_CLASS, \T_INTERFACE, \T_TRAIT, \T_ENUM], true)) {
                    $name = self::nextName($tokens, $i, $total);
                    if ($name !== null) {
                        $contexto = $name;
                    }
                    continue;
                }

                if ($token[0] !== \T_FUNCTION) {
                    continue;
                }

                $name = self::nextName($tokens, $i, $total);
                if ($name === null) {
                    continue; //closure o función flecha: no tiene nombre que vigilar
                }

                $signatures[] = $contexto !== '' ? "{$contexto}::{$name}" : $name;
            }

            if (count($signatures) > 0) {
                sort($signatures);
                $inventario[$relativo] = array_values(array_unique($signatures));
            }
        }

        ksort($inventario);
        return $inventario;
    }

    /**
     * Siguiente token con nombre a partir de una posición, saltando espacios.
     *
     * @param array<int,array{0:int,1:string,2:int}|string> $tokens
     * @param int $desde
     * @param int $total
     * @return string|null
     */
    protected static function nextName(array $tokens, int $desde, int $total): ?string
    {
        for ($j = $desde + 1; $j < $total; $j++) {
            $siguiente = $tokens[$j];
            if (is_array($siguiente) && in_array($siguiente[0], [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT], true)) {
                continue;
            }
            if (is_array($siguiente) && $siguiente[0] === \T_STRING) {
                return $siguiente[1];
            }
            //`&` de retorno por referencia
            if ($siguiente === '&') {
                continue;
            }
            return null;
        }
        return null;
    }

    /**
     * @param string $path
     * @param array<string,string[]> $signatures
     * @return void
     */
    protected static function writeSnapshot(string $path, array $signatures): void
    {
        $directorio = dirname($path);
        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }
        $contenido = json_encode($signatures, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
        file_put_contents($path, is_string($contenido) ? $contenido . "\n" : '{}');
    }

    /**
     * Compara el inventario actual contra la instantánea.
     *
     * Solo se reportan DESAPARICIONES. Una firma nueva no es un fallo: es trabajo
     * normal. Lo que nunca debe pasar en silencio es que algo deje de existir.
     *
     * @param string $path
     * @param array<string,string[]> $signatures
     * @return string[]
     */
    protected static function compareSignatures(string $path, array $signatures): array
    {
        if (!is_file($path)) {
            return [
                "no existe la instantánea en " . self::SNAPSHOT_RELATIVE_PATH . "; genérala con: bin/cli verify-integrity update-snapshot=yes",
            ];
        }

        $crudo = @file_get_contents($path);
        $previo = is_string($crudo) ? json_decode($crudo, true) : null;
        if (!is_array($previo)) {
            return ["la instantánea " . self::SNAPSHOT_RELATIVE_PATH . " no es JSON válido"];
        }

        $failures = [];

        foreach ($previo as $file => $before) {
            if (!is_array($before)) {
                continue;
            }

            //Un archivo borrado a propósito no es un fallo estructural, pero sí conviene
            //verlo: es la diferencia entre «lo borré» y «se lo tragó un comentario».
            if (!array_key_exists($file, $signatures)) {
                if (!is_file(rtrim(str_replace('\\', '/', basepath('')), '/') . '/' . $file)) {
                    continue; //archivo eliminado; nada que comparar
                }
                $failures[] = "{$file}: el archivo existe pero ya no declara ninguna función (antes: " . count($before) . ")";
                continue;
            }

            $perdidas = array_diff($before, $signatures[$file]);
            foreach ($perdidas as $perdida) {
                $failures[] = "{$file}: desapareció {$perdida}()";
            }
        }

        return $failures;
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new VerifyIntegrityTask($startRoute, $namePrefix);
        $route = new Route(
            $instance->route,
            $instance->controller,
            $instance->name,
            $instance->method,
            $instance->requireLogin,
            null,
            $instance->rolesAllowed->getArrayCopy(),
            $instance->defaultParamsValues,
            $instance->middlewares
        );
        return $route;
    }

}
