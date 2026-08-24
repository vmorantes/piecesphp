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
 *   6. Que no aparezca ninguna FUNCIÓN DEPRECADA de las registradas.
 *   7. Que los cuatro paquetes `piecesphp/*` no se hayan desviado del instrumental común.
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
            "\trutas PSR-4, eclipses, sobreescrituras, deprecadas e instrumental común.\r\n",
            "\tDevuelve código de salida 1 si algo falla, para uso en CI.\r\n",
            "\tParámetros:\r\n",
            "\t  update-snapshot (yes|no) regenera la instantánea de firmas en vez de comparar. Por defecto: no",
            "\t  list-narrative (yes|no) lista los bloques narrativos con su archivo, línea y prosa. Por defecto: no",
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

        //Sin esto no se puede recortar: la puerta agrega por archivo y hacen falta las líneas.
        if (TerminalData::instance()->getArgument('list-narrative', 'no') === 'yes') {
            [$bloques, $prosa] = self::collectNarrativeBlocks();
            foreach ($bloques as $b) {
                echoTerminal($b['file'] . ':' . $b['line'] . ':' . $b['prose']);
            }
            echoTerminal('TOTAL ' . count($bloques) . ' bloques, ' . $prosa . ' líneas de prosa');
            exit(0);
        }

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

        //──── 6. No hay llamadas a funciones deprecadas ─────────────────────────────────
        $deprecatedFailures = self::checkDeprecatedFunctions($files);

        //──── 7. Los paquetes no se han desviado del instrumental común ─────────────────
        $toolchainFailures = self::checkSharedToolchain();

        //──── 8. No hay comentarios narrativos fuera del registro ───────────────────────
        $narrativeFailures = self::checkNarrativeComments();

        //──── 9. Los guiones de bin/ están marcados como ejecutables EN EL ÍNDICE ───────
        $executableFailures = self::checkExecutableBits();

        //──── 10. Los tipos declarados en los mappers existen ───────────────────────────
        $typeFailures = self::checkDeclaredTypes($files);

        //──── 11. La lista de tablas volátiles coincide con la que se deriva del código ─
        $volatileFailures = self::checkVolatileTablesMatchCode();

        //──── Resultado ─────────────────────────────────────────────────────────────────
        $failures = count($docblockFailures) + count($signatureFailures)
            + count($loadFailures) + count($eclipseFailures) + count($overrideFailures)
            + count($deprecatedFailures) + count($toolchainFailures) + count($narrativeFailures)
            + count($executableFailures) + count($typeFailures) + count($volatileFailures);

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
        foreach ($deprecatedFailures as $line) {
            echoTerminal("\e[31mDEPRECADA:\e[39m {$line}");
        }
        foreach ($toolchainFailures as $line) {
            echoTerminal("\e[31mINSTRUMENTAL:\e[39m {$line}");
        }
        foreach ($executableFailures as $line) {
            echoTerminal("\e[31mEJECUTABLE:\e[39m {$line}");
        }
        foreach ($typeFailures as $line) {
            echoTerminal("\e[31mTIPO:\e[39m {$line}");
        }
        foreach ($volatileFailures as $line) {
            echoTerminal("\e[31mVOLÁTIL:\e[39m {$line}");
        }
        foreach ($narrativeFailures as $line) {
            echoTerminal("\e[31mCOMENTARIO:\e[39m {$line}");
        }

        if ($failures === 0) {
            echoTerminal("\e[32mOK:\e[39m docblocks, firmas, carga, eclipses, rutas, deprecadas, instrumental, comentarios, bits de ejecución, tipos y volátiles sin novedad.");
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

            //Se tokeniza y no se cuenta texto: «image/*» dentro de una cadena produce decenas de falsos positivos.
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

                //Se exige que la línea no empiece por «*»: sin esa condición, un docblock que MENCIONE una firma da falso positivo.
                if (self::hasSwallowedDeclaration($text)) {
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

            //En proceso y no en subproceso: el framework registra su propio autoloader además del de Composer.
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

        //Cero paquetes NO es un aprobado: la puerta no está mirando, y tiene que decirlo en voz alta.
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
                //UN CUERPO INERTE NO SIGNIFICA UN MÉTODO INERTE si lo que llama está
                //sobreescrito en la misma clase: la decisión vive una llamada más abajo.
                $delega = false;
                foreach (self::ROUTE_METHODS as $otro) {
                    if ($otro === $method) {
                        continue;
                    }
                    if (mb_strpos($body['body'], 'self::' . $otro . '(') === false
                        && mb_strpos($body['body'], 'static::' . $otro . '(') === false) {
                        continue;
                    }
                    if (mb_strpos($code, 'function ' . $otro) !== false) {
                        $delega = true;
                        break;
                    }
                }

                if (!$delega && !self::routeMethodDecides($method, $body, $canonical)) {
                    //Aviso, no orden: esta comprobación LEE el cuerpo, y nadie verifica una orden
                    //antes de obedecerla. Ver T21.
                    $failures[] = $key . ' — está registrado y su cuerpo se limita a devolver si la ruta'
                        . ' vino vacía, que es lo que hace el trait. ¿SIGUE DECIDIENDO ALGO? Compruébalo'
                        . ' antes de borrarlo: esta comprobación lee el cuerpo y no ve lo que se delega.';
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

    /**
     * Registro de funciones deprecadas, relativo a la RAÍZ DEL REPOSITORIO.
     *
     * @var string
     */
    const DEPRECATED_RELATIVE_PATH = 'files/dev/deprecated-functions.json';

    /**
     * Comprueba que no se llame a ninguna función deprecada de las registradas.
     *
     * **Existe porque los dos detectores que teníamos fallaron a la vez y nadie lo notó.**
     * PHPStan no reportaba las nueve llamadas a `imagedestroy()`, `finfo_close()` y
     * `curl_close()` que había, y el detector de ejecución —`bootstrap.php` promueve
     * `E_DEPRECATED` a excepción— **solo dispara si alguien pisa la línea**. Una de esas
     * nueve tumbaba la generación de imágenes con un 400, y para verlo había que pedir esa
     * imagen concreta.
     *
     * Esta es determinista: mira el código, no la ejecución. **Por TOKENS**, así que no le
     * afecta que el nombre aparezca dentro de una cadena o de un comentario — que fue el
     * error que dio 32 falsos positivos en la primera `verify-integrity`.
     *
     * La lista vive en un ARCHIVO editable con la versión en que cada función se deprecó,
     * para ampliarla sin tocar esta tarea.
     *
     * @param string[] $files rutas relativas a `src/`
     * @return string[]
     */
    protected static function checkDeprecatedFunctions(array $files): array
    {
        $failures = [];
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $srcRoot = is_dir($repoRoot . '/src/app') ? $repoRoot . '/src' : $repoRoot;
        $registryPath = dirname($repoRoot) . '/' . self::DEPRECATED_RELATIVE_PATH;

        if (!is_file($registryPath)) {
            $registryPath = $repoRoot . '/' . self::DEPRECATED_RELATIVE_PATH;
        }

        $raw = @file_get_contents($registryPath);
        $registry = is_string($raw) ? json_decode($raw, true) : null;

        if (!is_array($registry) || !isset($registry['deprecated']) || !is_array($registry['deprecated'])) {
            return ['no se pudo leer ' . self::DEPRECATED_RELATIVE_PATH . ': la comprobación no pudo mirar nada'];
        }

        $deprecated = $registry['deprecated'];
        $usedAllowance = [];

        foreach ($files as $relative) {
            $relative = str_replace('\\', '/', $relative);
            $code = @file_get_contents($srcRoot . '/' . $relative);
            if ($code === false) {
                continue;
            }
            //Filtro barato antes de tokenizar: la mayoría de archivos no menciona ninguna.
            $mentions = false;
            foreach ($deprecated as $name => $entry) {
                if (mb_strpos($code, $name) !== false) {
                    $mentions = true;
                    break;
                }
            }
            if (!$mentions) {
                continue;
            }

            foreach (self::calledFunctions($code) as $name => $lines) {
                if (!array_key_exists($name, $deprecated)) {
                    continue;
                }
                $allowed = $deprecated[$name]['allowedPaths'] ?? [];
                if (in_array($relative, (array) $allowed, true)) {
                    $usedAllowance[$name . '|' . $relative] = true;
                    continue;
                }
                $since = $deprecated[$name]['since'] ?? '?';
                $failures[] = $relative . ':' . implode(',', $lines) . ' — llama a ' . $name . '()'
                    . ', deprecada en PHP ' . $since . '. ' . ($deprecated[$name]['note'] ?? '');
            }
        }

        foreach ($deprecated as $name => $entry) {
            foreach ((array) ($entry['allowedPaths'] ?? []) as $allowedPath) {
                if (!array_key_exists($name . '|' . $allowedPath, $usedAllowance)) {
                    $failures[] = $name . '() ya no aparece en ' . $allowedPath
                        . ', pero sigue permitida ahí. Retira la ruta de allowedPaths.';
                }
            }
        }

        echoTerminal("\e[94mINFO:\e[39m " . count($deprecated) . " funciones deprecadas vigiladas.");

        return $failures;
    }

    /**
     * ¿Este comentario de bloque se ha tragado una declaración de función?
     *
     * @param string $text
     * @return bool
     */
    protected static function hasSwallowedDeclaration(string $text): bool
    {
        foreach (explode("\n", $text) as $line) {
            $trimmed = ltrim($line);
            if ($trimmed === '' || $trimmed[0] === '*' || mb_strpos($trimmed, '/*') === 0) {
                continue;
            }
            if (preg_match('/\bfunction\s+\w+\s*\(/', $line) === 1) {
                return true;
            }
        }

        return false;
    }
    /**
     * Funciones LLAMADAS en un código, con las líneas donde se llaman.
     *
     * Descarta lo que no es una llamada a función global: métodos (`->x()`, `::x()`),
     * declaraciones (`function x()`), y nombres dentro de cadenas o comentarios — que los
     * tokens ya separan solos.
     *
     * @param string $code
     * @return array<string,int[]>
     */
    protected static function calledFunctions(string $code): array
    {
        $tokens = @token_get_all($code);
        $total = count($tokens);
        $found = [];

        for ($i = 0; $i < $total; $i++) {
            $token = $tokens[$i];
            if (!is_array($token) || $token[0] !== T_STRING) {
                continue;
            }

            //Lo siguiente que no sea espacio tiene que ser un paréntesis de apertura.
            $next = null;
            for ($j = $i + 1; $j < $total; $j++) {
                if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $next = $tokens[$j];
                break;
            }
            if ($next !== '(') {
                continue;
            }

            //Lo anterior no puede ser `->`, `::`, `function`, `new` ni `?->`.
            $previous = null;
            for ($k = $i - 1; $k >= 0; $k--) {
                if (is_array($tokens[$k]) && in_array($tokens[$k][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $previous = $tokens[$k];
                break;
            }
            if (is_array($previous) && in_array($previous[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW, T_NULLSAFE_OBJECT_OPERATOR], true)) {
                continue;
            }

            $found[$token[1]][] = $token[2];
        }

        return $found;
    }

    /**
     * Registro del instrumental común a los cinco repositorios, relativo a la RAÍZ.
     *
     * @var string
     */
    const TOOLCHAIN_RELATIVE_PATH = 'files/dev/shared-toolchain.json';

    /**
     * Comprueba que los cuatro paquetes `piecesphp/*` no se hayan desviado del instrumental.
     *
     * **Existe porque ya pasó.** Los cuatro paquetes se declararon verdes sobre EXACTAMENTE
     * la misma configuración que había cegado a piecesphp —`phpVersion` como rango, que
     * reporta la intersección y no la unión— y nadie lo comprobó. La pregunta «¿están los
     * cinco al día?» no puede depender de que alguien se acuerde de hacerla.
     *
     * No compara los archivos byte a byte: legítimamente difieren en rutas y nombres. Busca
     * MARCAS —trozos de texto que solo existen si la propiedad está implementada—.
     *
     * Si los paquetes no están clonados al lado, la comprobación lo DICE y no aprueba en
     * silencio; pero tampoco falla, porque un despliegue no tiene por qué tenerlos.
     *
     * @return string[]
     */
    protected static function checkSharedToolchain(): array
    {
        $failures = [];
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $registryPath = dirname($repoRoot) . '/' . self::TOOLCHAIN_RELATIVE_PATH;
        if (!is_file($registryPath)) {
            $registryPath = $repoRoot . '/' . self::TOOLCHAIN_RELATIVE_PATH;
        }

        $raw = @file_get_contents($registryPath);
        $registry = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($registry) || !isset($registry['files']) || !is_array($registry['files'])) {
            return ['no se pudo leer ' . self::TOOLCHAIN_RELATIVE_PATH . ': la comprobación no pudo mirar nada'];
        }

        $packagesRoot = dirname(dirname($repoRoot));
        $present = 0;

        foreach ((array) ($registry['packages'] ?? []) as $package) {
            $packageRoot = $packagesRoot . '/' . $package;
            if (!is_dir($packageRoot)) {
                continue; //No está clonado al lado: no es un fallo de este repositorio.
            }
            $present++;

            $failures = array_merge($failures, self::checkToolchainTracking($package, $packageRoot, $registry));

            foreach ($registry['files'] as $relative => $entry) {
                $file = $packageRoot . '/' . $relative;
                if (!is_file($file)) {
                    if (($entry['optional'] ?? false) === true) {
                        continue; //Solo se exige a los paquetes que lo tengan.
                    }
                    $failures[] = $package . ' — le falta ' . $relative . '. ' . ($entry['why'] ?? '');
                    continue;
                }
                $content = (string) @file_get_contents($file);
                $missing = [];
                foreach ((array) ($entry['markers'] ?? []) as $marker) {
                    if (mb_strpos($content, (string) $marker) === false) {
                        $missing[] = $marker;
                    }
                }
                if (count($missing) > 0) {
                    $failures[] = $package . '/' . $relative . ' — se ha desviado: no contiene «'
                        . implode('», «', $missing) . '». ' . ($entry['why'] ?? '');
                }
            }
        }

        if ($present === 0) {
            echoTerminal("\e[33mAVISO:\e[39m ningún paquete clonado junto al repositorio: el instrumental común NO se comprobó.");
            return $failures;
        }

        echoTerminal("\e[94mINFO:\e[39m {$present} paquetes comprobados contra el instrumental común.");

        return $failures;
    }

    const NARRATIVE_RELATIVE_PATH = 'files/dev/narrative-comments.json';

    /**
     * Un comentario que frena algo cabe en una línea (T0, punto 7).
     *
     * La regla anterior —«¿impide romper algo?»— no frenaba la deriva porque no hablaba del
     * TAMAÑO: un relato de doce líneas siempre encuentra una frase suya que sí impide romper
     * algo, y con esa se justifica entero.
     *
     * @return array{0: list<array{file: string, line: int, prose: int}>, 1: int}
     */
    protected static function collectNarrativeBlocks(): array
    {
        $anotaciones = ['@param', '@return', '@var', '@package', '@author', '@throws'];
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $roots = [$repoRoot . '/app', dirname($repoRoot) . '/bin'];
        $excluir = ['/vendor/', '/node_modules/', '/bin/tools/', '.min.', '/statics/core/', '/statics/plugins/'];

        $bloques = [];
        $prosaTotal = 0;
        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $p = str_replace('\\', '/', (string) $file->getPathname());
                if (!preg_match('/\.(php|js|ts)$/', $p)) {
                    continue;
                }
                foreach ($excluir as $skip) {
                    if (mb_strpos($p, $skip) !== false) {
                        continue 2;
                    }
                }
                $lines = preg_split('/\R/', (string) file_get_contents($p)) ?: [];
                $n = count($lines);
                for ($i = 0; $i < $n; $i++) {
                    $l = trim((string) $lines[$i]);
                    $inicio = null;
                    $fin = null;
                    if (str_starts_with($l, '/*')) {
                        $inicio = $i;
                        while ($i < $n && mb_strpos((string) $lines[$i], '*/') === false) {
                            $i++;
                        }
                        $fin = min($i, $n - 1);
                    } elseif (str_starts_with($l, '//')) {
                        $inicio = $i;
                        while ($i + 1 < $n && preg_match('#^\s*//#', (string) $lines[$i + 1]) === 1) {
                            $i++;
                        }
                        $fin = $i;
                    }
                    if ($inicio === null || $fin === null) {
                        continue;
                    }
                    $cuerpo = array_slice($lines, $inicio, $fin - $inicio + 1);
                    $texto = implode("\n", $cuerpo);
                    foreach ($anotaciones as $a) {
                        if (mb_strpos($texto, $a) !== false) {
                            continue 2;
                        }
                    }
                    $prosa = 0;
                    foreach ($cuerpo as $linea) {
                        $t = trim((string) preg_replace('#^\s*(/\*\*?|\*/|\*|//)\s?#', '', (string) $linea));
                        $t = trim(str_replace('*/', '', $t));
                        if ($t === '' || str_starts_with($t, '<reference') || str_starts_with($t, '@ts-')) {
                            continue;
                        }
                        $prosa++;
                    }
                    if ($prosa > 2) {
                        $relativo = ltrim(str_replace(dirname($repoRoot), '', $p), '/');
                        $bloques[] = ['file' => $relativo, 'line' => $inicio + 1, 'prose' => $prosa];
                        $prosaTotal += $prosa;
                    }
                }
            }
        }
        usort($bloques, static fn (array $a, array $b): int => [$a['file'], $a['line']] <=> [$b['file'], $b['line']]);

        return [$bloques, $prosaTotal];
    }

    /**
     * Falla ante cualquier bloque narrativo que no esté en el registro.
     *
     * Misma forma que `KNOWN_ECLIPSES`: la lista SOLO PUEDE ENCOGER. Y guarda las líneas de
     * prosa de cada entrada para que «encoger» sea medible en líneas y no solo en entradas —
     * un bloque que crece de 4 a 30 líneas mantiene el conteo de entradas y empeora el
     * archivo.
     *
     * El registro se ancla por ARCHIVO, no por línea: cualquier edición encima desplazaría
     * los números y volvería la puerta un generador de ruido.
     *
     * @return string[]
     */
    protected static function checkNarrativeComments(): array
    {
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $registryPath = dirname($repoRoot) . '/' . self::NARRATIVE_RELATIVE_PATH;
        $raw = @file_get_contents($registryPath);
        $registry = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($registry) || !isset($registry['entries']) || !is_array($registry['entries'])) {
            return ['no se pudo leer ' . self::NARRATIVE_RELATIVE_PATH . ': la comprobación no pudo mirar nada'];
        }

        [$bloques, $prosaTotal] = self::collectNarrativeBlocks();

        $permitidos = [];
        foreach ($registry['entries'] as $entry) {
            $permitidos[(string) ($entry['file'] ?? '')] = [
                'blocks' => (int) ($entry['blocks'] ?? 0),
                'prose' => (int) ($entry['prose'] ?? 0),
            ];
        }

        $porArchivo = [];
        foreach ($bloques as $b) {
            $porArchivo[$b['file']]['blocks'] = ($porArchivo[$b['file']]['blocks'] ?? 0) + 1;
            $porArchivo[$b['file']]['prose'] = ($porArchivo[$b['file']]['prose'] ?? 0) + $b['prose'];
        }

        $failures = [];
        foreach ($porArchivo as $file => $datos) {
            if (!array_key_exists($file, $permitidos)) {
                $failures[] = $file . ' — ' . $datos['blocks'] . ' bloque(s) narrativo(s), '
                    . $datos['prose'] . ' líneas de prosa, y el archivo NO está en el registro.'
                    . ' La guarda cabe en una línea (T0, punto 7); el relato va al CHANGELOG.';
                continue;
            }
            if ($datos['prose'] > $permitidos[$file]['prose']) {
                $failures[] = $file . ' — la prosa narrativa CRECIÓ de '
                    . $permitidos[$file]['prose'] . ' a ' . $datos['prose'] . ' líneas.'
                    . ' El registro solo puede encoger.';
            }
        }
        foreach ($permitidos as $file => $datos) {
            if (!array_key_exists($file, $porArchivo)) {
                $failures[] = $file . ' — figura en el registro y ya no tiene comentarios narrativos.'
                    . ' Quita la entrada: la lista solo puede encoger, y encoger incluye vaciarse.';
            }
        }

        echoTerminal("\e[94mINFO:\e[39m " . count($bloques) . ' bloque(s) narrativo(s) en '
            . count($porArchivo) . ' archivo(s), ' . $prosaTotal . ' líneas de prosa registradas.');

        return $failures;
    }
    /**
     * Lo que las herramientas PRODUCEN también es instrumental compartido.
     *
     * Esta comprobación existe porque la de las marcas aprobó en verde cuatro repositorios
     * cuyo estado de seguimiento divergía: `PHPStanResult.json` versionado aquí y ni versionado
     * ni ignorado en los paquetes, y un `bin/Preview/` generado colándose en `html`. Las líneas
     * de `.gitignore` de los intermedios sí se habían propagado; la decisión sobre el archivo
     * de la unión, no. **El defecto no era la divergencia: era el alcance de la puerta.**
     *
     * @param array<string, mixed> $registry
     * @return string[]
     */
    /**
     * Las tablas del acuñado de slug declaradas en `volatile-state.json` tienen que coincidir
     * con las que el código descubre.
     *
     * Existe porque la lista está COPIADA: sale de `PreferSlugsFiller::mappersWithSlug()`, pero
     * una vez escrita nada detectaba que divergiera. Añadir un módulo con `preferSlug` la dejaba
     * corta en silencio, y el recorredor reportaría un hallazgo falso. Ver T64.
     *
     * @return string[]
     */
    protected static function checkVolatileTablesMatchCode(): array
    {
        $path = dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/files/dev/volatile-state.json';
        if (!is_file($path)) {
            return ['no existe files/dev/volatile-state.json: la comprobación de volátiles NO se hizo.'];
        }
        $declared = json_decode((string) file_get_contents($path), true);
        if (!is_array($declared)) {
            return ['files/dev/volatile-state.json no es JSON válido.'];
        }

        $derived = array_keys(\Terminal\Jobs\PreferSlugsFiller::mappersWithSlug());
        $declaredTables = array_keys((array) ($declared['tables'] ?? []));

        sort($derived);
        $missing = array_values(array_diff($derived, $declaredTables));
        //Solo se exige que estén las derivadas: `volatile-state.json` tiene además entradas de
        //otra naturaleza, como `login_attempts`, que no salen de este descubrimiento.
        $extra = [];
        foreach ($declaredTables as $table) {
            if (in_array($table, $derived, true)) {
                continue;
            }
            //Se reconoce por la razón escrita: si dice que es acuñado de slug, tiene que estar.
            $reason = (string) ($declared['tables'][$table] ?? '');
            if (mb_strpos($reason, 'ACUÑADO PEREZOSO DEL SLUG') !== false) {
                $extra[] = $table;
            }
        }

        $failures = [];
        foreach ($missing as $table) {
            $failures[] = "«{$table}» tiene `preferSlug` y NO está declarada en volatile-state.json.";
        }
        foreach ($extra as $table) {
            $failures[] = "«{$table}» está declarada como acuñado de slug y el código YA NO la descubre.";
        }

        echoTerminal("\e[94mINFO:\e[39m " . count($derived) . ' tabla(s) con acuñado de slug comprobadas contra el registro de volátiles.');

        return $failures;
    }

    /**
     * Todo tipo declarado en un `$fields` tiene que existir en el vocabulario de EntityMapper.
     *
     * Existe porque `'type' => 'test'` —«text» mal escrito— sobrevivió años en
     * `SystemApprovalsMapper`. Medido: con un tipo desconocido, `validateType()` devuelve
     * TRUE PARA TODO, `castPHPToSQLTypes()` no convierte, y `SchemeCreator` lo copia al DDL.
     * O sea: el campo no se valida y la tabla no se puede crear. Ver T54.
     *
     * Se lee del código, sin instanciar nada: instanciar un mapper abre conexión.
     *
     * @param string[] $files
     * @return string[]
     */
    protected static function checkDeclaredTypes(array $files): array
    {
        $vocabulary = (new \ReflectionClass(\PiecesPHP\Core\Database\EntityMapper::class))
            ->getDefaultProperties()['supportedTypes'] ?? [];
        if (!is_array($vocabulary) || count($vocabulary) === 0) {
            return ['no se pudo leer EntityMapper::$supportedTypes: la comprobación de tipos NO se hizo.'];
        }

        $base = rtrim(str_replace('\\', '/', basepath('')), '/');
        $failures = [];
        $checked = 0;
        foreach ($files as $file) {
            $contents = (string) @file_get_contents($base . '/' . $file);
            if (mb_strpos($contents, '$fields') === false) {
                continue;
            }
            //Solo dentro del bloque `$fields = [ … ];`, para no confundirlo con otros arrays.
            if (preg_match('/\$fields\s*=\s*\[(.*?)\n\s*\];/s', $contents, $block) !== 1) {
                continue;
            }
            preg_match_all("/'type'\s*=>\s*'([^']*)'/", $block[1], $declared, \PREG_SET_ORDER);
            foreach ($declared as $match) {
                $checked++;
                if (!in_array(mb_strtolower($match[1]), $vocabulary, true)) {
                    $failures[] = $file . " declara «{$match[1]}», que no está en el vocabulario de EntityMapper ("
                        . implode('|', $vocabulary) . ').';
                }
            }
        }

        echoTerminal("\e[94mINFO:\e[39m {$checked} tipo(s) declarados comprobados contra el vocabulario.");

        return $failures;
    }

    /**
     * Un guion con almohadilla-admiración tiene que estar marcado como ejecutable EN EL ÍNDICE.
     *
     * Este repositorio tiene `core.fileMode = false`, así que `chmod +x` funciona en el disco y
     * git NO lo registra: el guion corre aquí y llega sin permisos a quien clone. Pasó con
     * `bin/live-cache`, y es la clase de regla que solo se cumple si alguien se acuerda (LEY 11).
     *
     * @return string[]
     */
    protected static function checkExecutableBits(): array
    {
        $root = rtrim(str_replace('\\', '/', basepath('..')), '/');
        if (!is_dir($root . '/.git')) {
            return [];
        }

        $output = [];
        $status = 0;
        exec('git -C ' . escapeshellarg($root) . ' ls-files -s -- bin 2>/dev/null', $output, $status);
        if ($status !== 0) {
            return [];
        }

        $failures = [];
        $checked = 0;
        foreach ($output as $line) {
            if (preg_match('/^(\d{6})\s+\S+\s+\d+\t(.+)$/', $line, $matched) !== 1) {
                continue;
            }
            [$all, $mode, $relative] = $matched;
            $file = $root . '/' . $relative;
            if (!is_file($file)) {
                continue;
            }
            $handle = @fopen($file, 'rb');
            if ($handle === false) {
                continue;
            }
            $firstLine = (string) fgets($handle, 512);
            fclose($handle);
            if (mb_substr($firstLine, 0, 2) !== '#!') {
                continue;
            }
            $checked++;
            //Un guion con CRLF NO ARRANCA: `env` busca un intérprete llamado «php\r». Pasó con
            //bin/live-cache, que quedó inservible sin que nada lo dijera.
            if (mb_strpos($firstLine, "\r") !== false) {
                $failures[] = $relative . ' — su primera línea termina en CRLF, así que NO ARRANCA:'
                    . ' `env` busca un intérprete con un retorno de carro en el nombre.';
            }
            if ($mode !== '100755') {
                $failures[] = $relative . ' — empieza por «#!» pero git lo tiene como ' . $mode
                    . '. Este repositorio ignora el chmod del disco: «git update-index --chmod=+x ' . $relative . '».';
            }
        }

        echoTerminal("\e[94mINFO:\e[39m {$checked} guion(es) de bin/ comprobados contra su bit de ejecución.");

        return $failures;
    }

    protected static function checkToolchainTracking(string $package, string $packageRoot, array $registry): array
    {
        $tracking = $registry['tracking'] ?? null;
        if (!is_array($tracking)) {
            return [];
        }
        if (!is_dir($packageRoot . '/.git')) {
            return []; //Sin repositorio no hay estado de seguimiento que comprobar.
        }

        $failures = [];
        $git = 'git -C ' . escapeshellarg($packageRoot) . ' ';

        foreach ((array) ($tracking['tracked'] ?? []) as $relative => $why) {
            $out = trim((string) @shell_exec($git . 'ls-files -- ' . escapeshellarg((string) $relative) . ' 2>/dev/null'));
            if ($out === '') {
                $failures[] = $package . ' — ' . $relative . ' NO está versionado y debería estarlo. ' . (string) $why;
            }
        }

        foreach ((array) ($tracking['executable'] ?? []) as $relative => $why) {
            if (!is_file($packageRoot . '/' . $relative)) {
                continue; //Solo se exige a los paquetes que lo tengan.
            }
            $modo = trim((string) @shell_exec($git . 'ls-files -s -- ' . escapeshellarg((string) $relative) . ' 2>/dev/null'));
            if ($modo !== '' && !str_starts_with($modo, '100755')) {
                $failures[] = $package . ' — ' . $relative . ' está en git sin el bit de ejecución. ' . (string) $why;
            }
        }

        foreach ((array) ($tracking['ignored'] ?? []) as $relative => $why) {
            $seguido = trim((string) @shell_exec($git . 'ls-files -- ' . escapeshellarg((string) $relative) . ' 2>/dev/null'));
            if ($seguido !== '') {
                $failures[] = $package . ' — ' . $relative . ' está VERSIONADO y debería estar ignorado. ' . (string) $why;
                continue;
            }
            //`check-ignore` devuelve 1 cuando NO está ignorado: se mira la salida, no el código.
            $ignorado = trim((string) @shell_exec($git . 'check-ignore -- ' . escapeshellarg((string) $relative) . ' 2>/dev/null'));
            if ($ignorado === '') {
                $failures[] = $package . ' — ' . $relative . ' no está ignorado ni versionado: la política no se ha propagado. ' . (string) $why;
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
