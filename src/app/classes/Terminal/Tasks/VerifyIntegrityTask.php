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
 * Comprueba cuatro cosas. Las dos primeras son las que habrían servido en aquel
 * incidente; las otras dos salieron de fallos posteriores del mismo tipo — estructurales,
 * silenciosos y que ninguna prueba de comportamiento alcanza:
 *
 *   1. Docblocks sin cerrar.
 *   2. Desaparición de funciones y métodos, comparando contra una instantánea.
 *   3. Que toda clase se llame como su ruta PSR-4 manda y se pueda cargar de verdad.
 *   4. Que el núcleo no ECLIPSE una clase de un paquete declarando el mismo FQCN.
 *   5. Que ningún controlador sobreescriba `routeName`, `allowedRoute` o `_allowedRoute`
 *      sin estar en el registro, y que ninguna entrada del registro haya dejado de decidir.
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
            "\tComprueba docblocks sin cerrar, desaparición de funciones o métodos,\r\n",
            "\trutas PSR-4, eclipses de clases y sobreescrituras de rutas.\r\n",
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

        //──── 3. Las clases declaradas se pueden cargar ─────────────────────────────────
        $loadFailures = self::checkClassesAreLoadable($files);

        //──── 4. El núcleo no eclipsa clases de los paquetes ────────────────────────────
        $eclipseFailures = self::checkPackageEclipses($files);

        //──── 5. Las sobreescrituras de rutas están registradas y siguen decidiendo ─────
        $overrideFailures = self::checkRouteOverrides($files);

        //──── Resultado ─────────────────────────────────────────────────────────────────
        $failures = count($docblockFailures) + count($signatureFailures)
            + count($loadFailures) + count($eclipseFailures) + count($overrideFailures);

        foreach ($docblockFailures as $line) {
            echoTerminal("\e[31mDOCBLOCK:\e[39m {$line}");
        }
        foreach ($signatureFailures as $line) {
            echoTerminal("\e[31mFIRMA:\e[39m {$line}");
        }
        foreach ($loadFailures as $line) {
            echoTerminal("\e[31mCARGA:\e[39m {$line}");
        }
        foreach ($eclipseFailures as $line) {
            echoTerminal("\e[31mECLIPSE:\e[39m {$line}");
        }
        foreach ($overrideFailures as $line) {
            echoTerminal("\e[31mRUTA:\e[39m {$line}");
        }

        if ($failures === 0) {
            echoTerminal("\e[32mOK:\e[39m docblocks, firmas, rutas PSR-4, eclipses y sobreescrituras sin novedad.");
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
        $result = [];

        foreach (self::SCAN_PATHS as $relative) {
            $absolute = $base . '/' . $relative;

            if (is_file($absolute)) {
                $result[] = $relative;
                continue;
            }
            if (!is_dir($absolute)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absolute, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }
                $path = str_replace('\\', '/', $file->getPathname());
                $result[] = ltrim(str_replace($base, '', $path), '/');
            }
        }

        sort($result);
        return $result;
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

        foreach ($files as $relative) {
            $content = @file_get_contents($base . '/' . $relative);
            if (!is_string($content)) {
                continue;
            }

            //Se usa el analizador léxico, NO un recuento de texto: '/*' aparece dentro
            //de cadenas —'image/*' es el caso típico— y contarlo a pelo produce decenas
            //de falsos positivos en las vistas.
            foreach (@token_get_all($content) ?: [] as $token) {
                if (!is_array($token)) {
                    continue;
                }
                if (!in_array($token[0], [\T_COMMENT, \T_DOC_COMMENT], true)) {
                    continue;
                }
                $text = $token[1];
                if (!str_starts_with($text, '/*')) {
                    continue; //comentario de línea
                }

                //(a) Comentario de bloque sin cerrar hasta el final del archivo.
                if (substr(rtrim($text), -2) !== '*/') {
                    $failures[] = "{$relative}:{$token[2]}: comentario de bloque sin cerrar";
                    continue;
                }

                //(b) El caso que motivó esta tarea: al docblock le falta el cierre, el
                //    comentario se traga la declaración siguiente y llega hasta el '*/'
                //    del docblock de más abajo. El método deja de existir sin que ni
                //    'php -l' ni PHPStan digan nada.
                if (preg_match('/\bfunction\s+\w+\s*\(/', $text)) {
                    $failures[] = "{$relative}:{$token[2]}: un docblock se tragó una declaración de función; le falta el cierre";
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
        $inventory = [];

        foreach ($files as $relative) {
            $content = @file_get_contents($base . '/' . $relative);
            if (!is_string($content)) {
                continue;
            }

            $tokens = @token_get_all($content);
            if (!is_array($tokens)) {
                continue;
            }

            $signatures = [];
            $context = '';
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
                        $context = $name;
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

                $signatures[] = $context !== '' ? "{$context}::{$name}" : $name;
            }

            if (count($signatures) > 0) {
                sort($signatures);
                $inventory[$relative] = array_values(array_unique($signatures));
            }
        }

        ksort($inventory);
        return $inventory;
    }

    /**
     * Siguiente token con nombre a partir de una posición, saltando espacios.
     *
     * @param array<int,array{0:int,1:string,2:int}|string> $tokens
     * @param int $from
     * @param int $total
     * @return string|null
     */
    protected static function nextName(array $tokens, int $from, int $total): ?string
    {
        for ($j = $from + 1; $j < $total; $j++) {
            $next = $tokens[$j];
            if (is_array($next) && in_array($next[0], [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT], true)) {
                continue;
            }
            if (is_array($next) && $next[0] === \T_STRING) {
                return $next[1];
            }
            //`&` de retorno por referencia
            if ($next === '&') {
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
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $content = json_encode($signatures, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
        file_put_contents($path, is_string($content) ? $content . "\n" : '{}');
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

        $rawContent = @file_get_contents($path);
        $previousInventory = is_string($rawContent) ? json_decode($rawContent, true) : null;
        if (!is_array($previousInventory)) {
            return ["la instantánea " . self::SNAPSHOT_RELATIVE_PATH . " no es JSON válido"];
        }

        $failures = [];

        foreach ($previousInventory as $file => $before) {
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

            $missingSignatures = array_diff($before, $signatures[$file]);
            foreach ($missingSignatures as $missing) {
                $failures[] = "{$file}: desapareció {$missing}()";
            }
        }

        return $failures;
    }

    /**
     * Raíces PSR-4 del proyecto: prefijo de namespace => directorio relativo a `src/`.
     *
     * `src/app/classes` lo registra el autoloader PROPIO (`config/autoloads.php`) como raíz
     * sin prefijo; `app/core/psr4/PiecesPHP/Core` lo registra Composer. Si se añade una
     * raíz nueva en cualquiera de los dos sitios, va aquí también o deja de comprobarse.
     *
     * @var array<string,string>
     */
    const PSR4_ROOTS = [
        '' => 'app/classes',
        'PiecesPHP\\Core\\' => 'app/core/psr4/PiecesPHP/Core',
    ];

    /**
     * Comprueba que toda clase declarada bajo una raíz PSR-4 se llame como su ruta manda y
     * se pueda CARGAR de verdad.
     *
     * Dos comprobaciones, porque ninguna cubre a la otra:
     *
     *   1. RUTA CONTRA NAMESPACE. El FQCN esperado sale de la RUTA; el declarado, del
     *      archivo. Es la única forma de detectar un `namespace` perdido o equivocado —
     *      derivar el nombre del propio archivo es circular y no detecta nada.
     *   2. CARGA REAL con `class_exists($fqcn, true)`, que atrapa un padre, interfaz o trait
     *      que no resuelve.
     *
     * `composer dump-autoload --strict-psr` NO sirve aquí: el `psr-4` de `composer.json`
     * solo declara `PiecesPHP\Core\`, así que no ve nada de `src/app/classes`, que es donde
     * vive la mayoría del código propio.
     *
     * LO QUE NINGUNA ATRAPA, para que nadie confíe de más en esta puerta: un `use` que falta
     * y solo se referencia DENTRO del cuerpo de un método. La clase se declara y se carga
     * sin problema; el fallo aparece al ejecutar esa línea, y eso solo lo caza una prueba.
     *
     * @param string[] $files rutas relativas a `src/`
     * @return string[]
     */
    protected static function checkClassesAreLoadable(array $files): array
    {
        $failures = [];
        //`basepath('')` resuelve a la raíz del repositorio; el código vive un nivel dentro.
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $srcRoot = is_dir($repoRoot . '/src/app') ? $repoRoot . '/src' : $repoRoot;

        $checked = 0;

        foreach ($files as $relative) {
            $relative = str_replace('\\', '/', $relative);

            $prefix = null;
            $rootDir = null;
            foreach (self::PSR4_ROOTS as $namespacePrefix => $directory) {
                if (mb_strpos($relative, $directory . '/') === 0) {
                    $prefix = $namespacePrefix;
                    $rootDir = $directory;
                    break;
                }
            }
            if ($rootDir === null) {
                continue;
            }

            $code = @file_get_contents($srcRoot . '/' . $relative);
            if ($code === false) {
                continue;
            }

            $declared = self::declaredClass($code);
            if ($declared === null) {
                //Vistas y archivos de configuración no declaran clase: no es un fallo.
                continue;
            }

            $withoutExtension = mb_substr($relative, mb_strlen($rootDir) + 1, -4);
            $expected = $prefix . str_replace('/', '\\', $withoutExtension);
            $checked++;

            if ($declared !== $expected) {
                $failures[] = $relative . ' — declara ' . $declared . ' y su ruta exige ' . $expected;
                continue;
            }

            /**
             * En proceso, no en subproceso: el framework registra su propio autoloader
             * además del de Composer, y un subproceso que solo requiere `vendor/autoload.php`
             * no resuelve nada del código propio. Un padre que no existe lanza `Error`, que
             * es capturable, así que aislar no hace falta.
             */
            try {
                $exists = class_exists($declared, true)
                    || interface_exists($declared, true)
                    || trait_exists($declared, true)
                    || enum_exists($declared, true);
                if (!$exists) {
                    $failures[] = $relative . ' — ' . $declared . ' no se puede cargar';
                }
            } catch (\Throwable $e) {
                $failures[] = $relative . ' — ' . mb_substr($e->getMessage(), 0, 90);
            }
        }

        echoTerminal("\e[94mINFO:\e[39m {$checked} clases comprobadas contra su ruta PSR-4.");

        return $failures;
    }

    /**
     * FQCN declarado en un archivo, o null si no declara ninguna clase con nombre.
     *
     * Se lee por ESTRUCTURA con `token_get_all()`, nunca por línea ni por expresión regular:
     * es la tercera regla del proyecto y hay tres incidentes detrás.
     *
     * @param string $code
     * @return string|null
     */
    protected static function declaredClass(string $code): ?string
    {
        $tokens = @token_get_all($code);
        $total = count($tokens);
        $namespace = '';

        for ($i = 0; $i < $total; $i++) {
            $token = $tokens[$i];
            if (!is_array($token)) {
                continue;
            }
            if ($token[0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < $total; $j++) {
                    if ($tokens[$j] === ';' || $tokens[$j] === '{') {
                        break;
                    }
                    if (is_array($tokens[$j]) && !in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                        $namespace .= $tokens[$j][1];
                    }
                }
                continue;
            }
            if (in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                //`Foo::class` no declara nada, y una clase anónima no tiene nombre.
                $previous = $tokens[$i - 1] ?? null;
                if (is_array($previous) && $previous[0] === T_DOUBLE_COLON) {
                    continue;
                }
                $name = self::nextName($tokens, $i + 1, $total);
                if ($name !== null) {
                    return ($namespace !== '' ? $namespace . '\\' : '') . $name;
                }
            }
        }

        return null;
    }

    /**
     * Eclipses CONOCIDOS y aceptados, con su razón y la condición que los retira.
     *
     * Esta lista no es una lista de excepciones cómodas: es un registro. Un eclipse que
     * no esté aquí hace fallar la comprobación, y una entrada de aquí cuyo eclipse ya no
     * exista TAMBIÉN la hace fallar. Lo segundo importa tanto como lo primero: una
     * supresión que sobrevive a su motivo es una mentira que nadie vuelve a leer.
     *
     * @var array<string,array{reason:string,retiredWhen:string}>
     */
    const KNOWN_ECLIPSES = [
        'PiecesPHP\\Core\\Database\\Meta\\MetaProperty' => [
            'reason' => 'Dos LINAJES distintos, no dos versiones: la del núcleo se apoya en '
                . 'EntityMapper y la del paquete en ORM. Aquí solo está poblado el primero '
                . '(35 clases contra 0), así que la del núcleo es la única que funciona. '
                . 'Ver T16 en .agents/context/18-siguientes-ventanas.md.',
            'retiredWhen' => 'Cuando EntityMapper y ORM se unifiquen, o cuando el núcleo '
                . 'deje de declarar esta clase.',
        ],
    ];

    /**
     * Comprueba que el núcleo no ECLIPSE una clase de ningún paquete `piecesphp/*`.
     *
     * El mecanismo es estructural y no accidental: PSR-4 resuelve por PREFIJO MÁS LARGO.
     * Los paquetes registran `PiecesPHP\` => `src/`; el proyecto registra
     * `PiecesPHP\Core\` => `app/core/psr4/PiecesPHP/Core`. Como el segundo prefijo es más
     * largo, CUALQUIER clase que el núcleo declare bajo `PiecesPHP\Core\` gana siempre, y
     * lo hace EN SILENCIO: no hay aviso, no hay error, y las dos clases pueden no tener
     * nada que ver entre sí.
     *
     * Ya pasó una vez —`MetaProperty`— y el coste no fue el eclipse en sí, sino que un
     * arreglo aplicado al archivo del paquete no llegaba aquí y nadie tenía forma de
     * saberlo. Esta puerta es lo único que impide que se repita.
     *
     * Los prefijos de cada paquete se LEEN de su `composer.json`, no se dan por sabidos:
     * si un paquete cambia su `psr-4`, la comprobación se adapta en vez de dejar de mirar.
     *
     * @param string[] $files rutas relativas a `src/`
     * @return string[]
     */
    protected static function checkPackageEclipses(array $files): array
    {
        $failures = [];
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $srcRoot = is_dir($repoRoot . '/src/app') ? $repoRoot . '/src' : $repoRoot;
        $vendorDir = $srcRoot . '/vendor/piecesphp';

        //Prefijo PSR-4 => directorio absoluto, por paquete.
        $packages = [];
        foreach (glob($vendorDir . '/*', \GLOB_ONLYDIR) ?: [] as $packageDir) {
            $manifest = $packageDir . '/composer.json';
            if (!is_file($manifest)) {
                continue;
            }
            $raw = @file_get_contents($manifest);
            $data = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                continue;
            }
            $psr4 = $data['autoload']['psr-4'] ?? [];
            if (!is_array($psr4) || count($psr4) === 0) {
                continue;
            }
            $roots = [];
            foreach ($psr4 as $prefix => $directories) {
                foreach ((array) $directories as $directory) {
                    $roots[(string) $prefix][] = rtrim($packageDir . '/' . trim((string) $directory, '/'), '/');
                }
            }
            $packages[basename($packageDir)] = $roots;
        }

        /**
         * Sin paquetes no hay nada que comparar, y eso NO es un aprobado: esta tarea corre
         * dentro del framework arrancado, así que el vendor existe siempre que se ejecuta.
         * Cero paquetes significa que la puerta no está mirando, y una puerta que no mira
         * tiene que decirlo en voz alta.
         */
        if (count($packages) === 0) {
            return ['no se encontró ningún paquete en ' . $vendorDir . ': la comprobación no pudo mirar nada'];
        }

        $eclipsed = [];

        foreach ($files as $relative) {
            $relative = str_replace('\\', '/', $relative);

            $insideRoot = false;
            foreach (self::PSR4_ROOTS as $directory) {
                if (mb_strpos($relative, $directory . '/') === 0) {
                    $insideRoot = true;
                    break;
                }
            }
            if (!$insideRoot) {
                continue;
            }

            $code = @file_get_contents($srcRoot . '/' . $relative);
            if ($code === false) {
                continue;
            }
            $declared = self::declaredClass($code);
            if ($declared === null) {
                continue;
            }

            foreach ($packages as $packageName => $roots) {
                foreach ($roots as $prefix => $directories) {
                    if ($prefix !== '' && mb_strpos($declared, $prefix) !== 0) {
                        continue;
                    }
                    $sub = mb_substr($declared, mb_strlen($prefix));
                    foreach ($directories as $directory) {
                        $candidate = $directory . '/' . str_replace('\\', '/', $sub) . '.php';
                        if (is_file($candidate)) {
                            $eclipsed[$declared][$packageName] = $relative;
                        }
                    }
                }
            }
        }

        foreach ($eclipsed as $fqcn => $packagesByName) {
            if (array_key_exists($fqcn, self::KNOWN_ECLIPSES)) {
                continue;
            }
            $failures[] = $fqcn . ' — declarada en ' . reset($packagesByName)
                . ' y también en el paquete ' . implode(', ', array_keys($packagesByName))
                . '. El núcleo gana por prefijo más largo y la del paquete no se ejecuta jamás aquí.'
                . ' Si es a propósito, va a KNOWN_ECLIPSES con su razón y su condición de retirada.';
        }

        foreach (self::KNOWN_ECLIPSES as $fqcn => $entry) {
            if (!array_key_exists($fqcn, $eclipsed)) {
                $failures[] = $fqcn . ' — figura en KNOWN_ECLIPSES pero ya no colisiona con ningún paquete.'
                    . ' La entrada sobrevivió a su motivo: retírala.';
            }
        }

        $total = count($eclipsed);
        echoTerminal("\e[94mINFO:\e[39m " . count($packages) . " paquetes examinados, {$total} eclipses encontrados"
            . ($total > 0 ? ' (' . count(self::KNOWN_ECLIPSES) . ' registrados).' : '.'));

        return $failures;
    }

    /**
     * Ruta del trait que aporta los tres métodos de ruta, relativa a `src/`.
     *
     * @var string
     */
    const ROUTING_TRAIT_PATH = 'app/core/psr4/PiecesPHP/Core/Routing/ControllerRoutingTrait.php';

    /**
     * Los tres métodos que el trait aporta y que un controlador puede sobreescribir.
     *
     * @var string[]
     */
    const ROUTE_METHODS = ['routeName', 'allowedRoute', '_allowedRoute'];

    /**
     * SOBREESCRITURAS DE RUTA AUTORIZADAS: `FQCN::método` => razón.
     *
     * Cada entrada es una respuesta escrita a una sola pregunta: **¿este método DECIDE algo
     * que el trait no decide?** Si la respuesta es no, el método sobra y se borra; el
     * criterio no es el parecido con el cuerpo canónico, que fue el criterio anterior y
     * dejaba vivas dieciséis copias que no hacían nada.
     *
     * @var array<string,string>
     */
    const KNOWN_ROUTE_OVERRIDES = [
        //──── Nombran la ruta de otra forma: el trait no puede servirles ────────────────
        'App\\Locations\\Controllers\\City::routeName' => 'Prefijo de dos niveles ($prefixParentEntity + $prefixEntity). NO declara $baseRouteName, que es lo que usa el trait.',
        'App\\Locations\\Controllers\\Country::routeName' => 'Ídem City.',
        'App\\Locations\\Controllers\\Point::routeName' => 'Ídem City.',
        'App\\Locations\\Controllers\\Region::routeName' => 'Ídem City.',
        'App\\Locations\\Controllers\\State::routeName' => 'Ídem City.',
        'App\\Controller\\ContactFormsController::routeName' => 'Usa self::$prefixNameRoutes. NO declara $baseRouteName.',
        'App\\Controller\\PublicAreaController::routeName' => 'Usa self::$prefixNameRoutes. NO declara $baseRouteName.',
        'Terminal\\Controllers\\TerminalController::routeName' => 'OTRA FIRMA: routeName(?string, bool), sin $params, y arma el nombre con self::routeID().',
        'Terminal\\Controllers\\TerminalController::allowedRoute' => 'OTRA FIRMA: allowedRoute(string), coherente con su propio routeName de dos parámetros.',
        'DataImportExportUtility\\Controllers\\DataImportExportUtilityController::routeName' => 'Toma el usuario de get_config(\'current_user\') en vez de getLoggedFrameworkUser(). NO se ha demostrado equivalente: si el constructor de UserDataPackage lanza, el trait trataría al usuario como anónimo y CONCEDERÍA, mientras esta copia sigue comprobando permisos. Es más restrictiva en ese borde. Módulo condenado (T6): se conserva hasta que muera con él.',

        //──── Propiedad del recurso ────────────────────────────────────────────────────
        'News\\Controllers\\NewsController::_allowedRoute' => 'actions-delete: solo el creador, o los tipos de NewsMapper::CAN_DELETE_ALL.',
        'PiecesPHP\\BuiltIn\\Banner\\Controllers\\BuiltInBannerController::_allowedRoute' => 'actions-delete: solo el creador, o BuiltInBannerMapper::CAN_DELETE_ALL.',
        'Documents\\Controllers\\DocumentsController::_allowedRoute' => 'Borrado y edición: solo el creador, o CAN_DELETE_ALL / CAN_EDIT_ALL.',
        'Forms\\DocumentTypes\\Controllers\\DocumentTypesController::_allowedRoute' => 'Borrado y edición: solo el creador, o CAN_DELETE_ALL / CAN_EDIT_ALL.',
        'Forms\\Categories\\Controllers\\CategoriesController::_allowedRoute' => 'Borrado y edición: solo el creador, o CAN_DELETE_ALL / CAN_EDIT_ALL.',
        'ImagesRepository\\Controllers\\ImagesRepositoryController::_allowedRoute' => 'Borrado y edición: solo el creador, o CAN_DELETE_ALL / CAN_EDIT_ALL. Módulo condenado (T6).',
        'MySpace\\Controllers\\MyProfileController::_allowedRoute' => 'actions-delete-experience: solo experiencias del propio perfil, o CAN_DELETE_ALL.',

        //──── Propiedad del recurso MÁS pertenencia a la organización ──────────────────
        'Publications\\Controllers\\PublicationsController::_allowedRoute' => 'Borrado y edición: creador o autor, o administrador de la MISMA organización que el creador, o CAN_DELETE_ALL / CAN_EDIT_ALL.',
        'ApplicationCalls\\Controllers\\ApplicationCallsController::_allowedRoute' => 'Borrado y edición: creador, o administrador de la misma organización, o CAN_*_ALL. Módulo condenado (T6). ES EL EJEMPLO CANÓNICO documentado en 13-recetas.md.',
        'InterestResearchAreas\\Controllers\\InterestResearchAreasController::_allowedRoute' => 'Ídem ApplicationCalls. Módulo condenado (T6).',
        'MySpace\\Controllers\\MyOrganizationProfileController::_allowedRoute' => 'Rutas del perfil de organización: solo el administrador de esa organización, o PROFILE_EDITOR_SUPER. Sin organización, deniega.',
        'Organizations\\Controllers\\OrganizationsController::_allowedRoute' => 'Borrado y edición: protege la organización inicial (INITIAL_ID_GLOBAL), y permite al editor de su propia organización o a su administrador. Respeta DISABLE_NORMAL_EDIT_FORM.',

        //──── Conflicto de interés ─────────────────────────────────────────────────────
        'SystemApprovals\\Controllers\\SystemApprovalsController::_allowedRoute' => 'forms-approval y actions-approval: IMPIDE APROBARSE A UNO MISMO comparando el id del usuario con el del registro.',

        //──── Registro protegido ───────────────────────────────────────────────────────
        'News\\Controllers\\NewsCategoryController::_allowedRoute' => 'actions-delete: impide borrar la categoría UNCATEGORIZED_ID, a la que caen las noticias sin categoría.',
        'Publications\\Controllers\\PublicationsCategoryController::_allowedRoute' => 'actions-delete: impide borrar la categoría UNCATEGORIZED_ID.',
    ];

    /**
     * Comprueba las sobreescrituras de `routeName`, `allowedRoute` y `_allowedRoute`.
     *
     * DOS DIRECCIONES, y hacen falta las dos:
     *
     *   1. Un controlador declara uno de los tres y NO está en `KNOWN_ROUTE_OVERRIDES`.
     *   2. Una entrada del registro **ha dejado de decidir algo**, o su declaración ya no
     *      existe. Una sobreescritura que no decide es andamio, y el andamio vuelve solo:
     *      así empezó esto, con dieciséis copias que no hacían nada.
     *
     * El veredicto lo da `routeMethodDecides()`, **el mismo clasificador con el que se
     * construyó el registro**: la puerta no puede separarse del criterio.
     *
     * LO QUE NO ATRAPA, para que nadie confíe de más: el clasificador razona sobre el cuerpo
     * del método, no sobre quién lo llama. Un `if ($name == 'SAMPLE') { $allow = false; }`
     * cuenta como que decide, aunque ninguna ruta se llame así. Esa clase de hueco solo la
     * cierra mirar los sitios de llamada, y eso no lo hace esta puerta.
     *
     * @param string[] $files rutas relativas a `src/`
     * @return string[]
     */
    protected static function checkRouteOverrides(array $files): array
    {
        $failures = [];
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $srcRoot = is_dir($repoRoot . '/src/app') ? $repoRoot . '/src' : $repoRoot;

        $traitCode = @file_get_contents($srcRoot . '/' . self::ROUTING_TRAIT_PATH);
        if (!is_string($traitCode)) {
            return ['no se encontró ' . self::ROUTING_TRAIT_PATH . ': la comprobación no pudo mirar nada'];
        }
        $canonical = self::routeMethodBody($traitCode, 'routeName');
        if ($canonical === null) {
            return [self::ROUTING_TRAIT_PATH . ' ya no declara routeName(): la comprobación no pudo mirar nada'];
        }

        $found = [];

        foreach ($files as $relative) {
            $relative = str_replace('\\', '/', $relative);
            if ($relative === self::ROUTING_TRAIT_PATH) {
                continue;
            }
            $code = @file_get_contents($srcRoot . '/' . $relative);
            if ($code === false) {
                continue;
            }
            $declared = null;
            foreach (self::ROUTE_METHODS as $method) {
                if (mb_strpos($code, 'function ' . $method) === false) {
                    continue;
                }
                $body = self::routeMethodBody($code, $method);
                if ($body === null) {
                    continue;
                }
                $declared ??= self::declaredClass($code);
                if ($declared === null) {
                    continue;
                }
                $key = $declared . '::' . $method;
                $found[$key] = true;

                if (!array_key_exists($key, self::KNOWN_ROUTE_OVERRIDES)) {
                    $failures[] = $key . ' — sobreescribe un método del trait sin estar registrado.'
                        . ' Si decide algo, va a KNOWN_ROUTE_OVERRIDES con su razón; si no, se borra.';
                    continue;
                }
                if (!self::routeMethodDecides($method, $body, $canonical)) {
                    $failures[] = $key . ' — está registrado pero YA NO DECIDE NADA: su cuerpo se limita a'
                        . ' devolver si la ruta vino vacía, que es lo que hace el trait. Bórralo.';
                }
            }
        }

        foreach (self::KNOWN_ROUTE_OVERRIDES as $key => $reason) {
            if (!array_key_exists($key, $found)) {
                $failures[] = $key . ' — figura en KNOWN_ROUTE_OVERRIDES pero ya no se declara. Retira la entrada.';
            }
        }

        echoTerminal("\e[94mINFO:\e[39m " . count($found) . " sobreescrituras de ruta comprobadas contra el registro.");

        return $failures;
    }

    /**
     * Firma y cuerpo normalizados de un método, sin comentarios. Por TOKENS.
     *
     * @param string $code
     * @param string $method
     * @return array{signature:string,body:string}|null
     */
    protected static function routeMethodBody(string $code, string $method): ?array
    {
        $tokens = @token_get_all($code);
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
                continue;
            }
            $name = null;
            for ($j = $i + 1; $j < $total; $j++) {
                if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $name = $tokens[$j][1];
                }
                break;
            }
            if ($name !== $method) {
                continue;
            }

            $signature = '';
            $k = $j;
            for (; $k < $total; $k++) {
                $token = $tokens[$k];
                if ($token === '{') {
                    break;
                }
                if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $signature .= is_array($token) ? $token[1] : $token;
            }
            if ($k >= $total) {
                return null;
            }

            $body = '';
            $depth = 0;
            for (; $k < $total; $k++) {
                $token = $tokens[$k];
                $text = is_array($token) ? $token[1] : $token;
                if ($text === '{') {
                    $depth++;
                    if ($depth === 1) {
                        continue;
                    }
                }
                if ($text === '}') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                }
                if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                if (is_array($token) && $token[0] === T_WHITESPACE) {
                    $text = ' ';
                }
                $body .= $text;
            }

            return [
                'signature' => (string) preg_replace('/\s+/', ' ', trim($signature)),
                'body' => trim((string) preg_replace('/\s+/', ' ', $body)),
            ];
        }

        return null;
    }

    /**
     * ¿Este método DECIDE algo que el trait no decida?
     *
     * CONSERVADOR A PROPÓSITO: solo devuelve `false` para un conjunto CERRADO de formas
     * demostrablemente inertes. Todo lo que no puede probar cuenta como que decide, que es
     * el lado seguro — un falso «decide» deja un método de más; un falso «no decide» borra
     * una regla de autorización.
     *
     * @param string $method
     * @param array{signature:string,body:string} $body
     * @param array{signature:string,body:string} $canonical cuerpo de `routeName` en el trait
     * @return bool
     */
    protected static function routeMethodDecides(string $method, array $body, array $canonical): bool
    {
        $text = $body['body'];

        if ($method === 'routeName') {
            //Inerte solo si es EXACTAMENTE el cuerpo canónico.
            return $text !== $canonical['body'];
        }

        //Formas aceptadas de «la ruta no vino vacía».
        $emptyChecks = [
            '$route !== \'\'',
            '(string) $route !== \'\'',
            'strlen($route) > 0',
            'mb_strlen($route) > 0',
        ];

        preg_match_all('/\$allow\s*=\s*([^;]+);/', $text, $assignments);
        preg_match_all('/return\s+([^;]+);/', $text, $returns);

        $assigned = array_map('trim', $assignments[1]);
        //El closure `$getParam` trae su propio `return $paramValue;`: no es del método.
        $returned = array_values(array_filter(
            array_map('trim', $returns[1]),
            fn (string $expression) => $expression !== '$paramValue'
        ));

        if (count($assigned) !== 1 || !in_array($assigned[0], $emptyChecks, true)) {
            return true;
        }
        if (count($returned) !== 1 || $returned[0] !== '$allow') {
            return true;
        }
        if ($method === 'allowedRoute' && !str_contains($text, 'self::routeName($name, $params, true)')) {
            return true;
        }

        return false;
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
