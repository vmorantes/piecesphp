<?php

/**
 * VerifyIntegrityTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\LoadFailures;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\TerminalData;
use Terminal\Distribution;

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
 * CUÁNTAS COMPROBACIONES HAY LO DICE `main()`, no este comentario: decía «cuatro» mientras
 * enumeraba ocho y corrían diecisiete. La lista completa y al día vive en `.agents/context/21-pruebas-y-puertas.md`.
 * Aquí van solo las que explican POR QUÉ existe esto — las dos primeras son las que habrían
 * servido en aquel incidente, y el resto salió de fallos posteriores del mismo tipo:
 * estructurales, silenciosos y que ninguna prueba de comportamiento alcanza.
 *
 *   1. Docblocks sin cerrar.
 *   2. Desaparición de funciones y métodos, comparando contra una instantánea.
 *   3. Que toda clase se llame como su ruta PSR-4 manda y se pueda cargar de verdad.
 *   4. Que el núcleo no ECLIPSE una clase de un paquete declarando el mismo FQCN.
 *   5. Que ningún controlador sobreescriba `routeName`, `allowedRoute` o `_allowedRoute`
 *      sin estar en el registro, y que ninguna entrada del registro haya dejado de decidir.
 *   6. Que no aparezca ninguna FUNCIÓN DEPRECADA de las registradas.
 *   7. Que los cuatro paquetes `piecesphp/*` no se hayan desviado del instrumental común.
 *   8. Y, sin fallar, AVISA cuando la versión instalada de un paquete no es la última
 *      etiquetada en su repositorio hermano.
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
     * en la raíz del repositorio.
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

    /**
     * Carpetas de subidas públicas o sin archivos, relativo a la raíz del repositorio.
     *
     * @var string
     */
    const UPLOAD_DIRS_RELATIVE_PATH = 'files/dev/upload-dirs.json';

    /**
     * Las únicas rutas que se pueden pedir con get_route('nombre-literal'): sin controlador con el trait.
     */
    const GET_ROUTE_ALLOWED_RELATIVE_PATH = 'files/dev/get-route-direct-allowed.json';

    /**
     * Quién puede abrir cada ruta, congelado. Se versiona: sin esto en el repositorio la
     * comprobación no detecta nada en una máquina limpia.
     */
    const DECLARED_PERMISSIONS_RELATIVE_PATH = 'files/dev/permissions-declared.json';

    /** Los sitios donde el mensaje de una excepción llega a una respuesta, declarados uno a uno (P56). */
    const EXCEPTION_MESSAGE_DECLARED_RELATIVE_PATH = 'files/dev/exception-message-declared.json';

    /** Cada capacidad anunciada, con las rutas o acciones que la sirven y dónde se anuncia (comprobación 39). */
    const ANNOUNCED_CAPABILITIES_RELATIVE_PATH = 'files/dev/announced-capabilities.json';

    /** El censo de lo que la campaña borró o movió, con lo que hay que buscar de cada ruta (comprobación 40). */
    const CAMPAIGN_REMOVALS_RELATIVE_PATH = 'files/dev/campaign-removals.json';

    /** Restos reales admitidos: lo que aún nombra una ruta vieja sin ser a propósito. Está en 0, así que cualquier resto falla. */
    const CAMPAIGN_REMNANTS_DECLARED = 0;

    /** Dónde vive la configuración que tiene que decir de quién es (comprobación 41). */
    const CONFIG_OWNERSHIP_DIRS = ['src/app/config/', 'src/app/core/extensions/'];
    const CONFIG_OWNERSHIP_VALUES = ['framework', 'clon', 'ambos'];
    const CONFIG_MARK_FRAMEWORK = '//── Del framework: no lo edites ──';
    const CONFIG_MARK_CLON = '//── Del clon ──';
    const EXCEPTION_MESSAGE_CLASSES = ['usuario', 'tecnico', 'mezclado', 'interno', 'no-excepcion', 'solo-local', 'log', 'terminal'];

    /** Los de bin/ que viajan a la distribución (ADR 0049 §6). Un archivo nuevo de bin/ se clasifica: aquí o en bin/distribution-exclude.txt. */
    const BIN_TRAVELS = [
        'bin/.gitignore', 'bin/check-routes', 'bin/cli', 'bin/live-cache', 'bin/node/copyDependencies.php', 'bin/node/copyDependencies.sh',
        'bin/normaliza-eol', 'bin/package-css', 'bin/pieces-completion.bash', 'bin/pieces-completion.zsh', 'bin/tools/.gitignore',
        'bin/tools/check-routes.php', 'bin/tools/forbidden-routes.php', 'bin/tools/live-cache.php',
    ];

    /** La carpeta de las claves viaja solo con su protección, y nada más: algo de más puede ser una clave versionada (ADR 0050). */
    const SECURE_KEYS_DIR = 'secure-keys/';
    const SECURE_KEYS_VERSIONED = ['secure-keys/.editorconfig', 'secure-keys/.gitignore', 'secure-keys/.htaccess'];

    /** Las extensiones de PHPStan del núcleo, relativas a `src/`: cargan solo con bin/tools/vendor, así que no viajan (comprobación 47). */
    const PHPSTAN_EXTENSIONS_DIR = 'app/core/psr4/PiecesPHP/Core/PHPStan/';

    /**
     * Lo que cada comprobación necesita y NO viaja (ADR 0049), relativo a la raíz. En la distribución, sin eso, dice
     * `[NO APLICA EN LA DISTRIBUCIÓN]`; aquí lo que falte es un fallo. Una comprobación que lea algo así se añade aquí.
     */
    const DISTRIBUTION_REQUIREMENTS = [
        2 => [self::SNAPSHOT_RELATIVE_PATH],
        6 => [self::DEPRECATED_RELATIVE_PATH],
        7 => [self::TOOLCHAIN_RELATIVE_PATH],
        8 => [self::NARRATIVE_RELATIVE_PATH],
        11 => ['files/dev/volatile-state.json'],
        12 => [self::FORBIDDEN_RELATIVE_PATH, 'files/dev/route-inventory.json'],
        13 => ['bin/phpstan.neon', self::UNIVERSE_RELATIVE_PATH],
        18 => ['bin/tools/vendor/autoload.php'],
        19 => ['bin/censo-retornos-ignorados'],
        21 => ['bin/censo-claves-huerfanas'],
        23 => ['files/dev/route-inventory.json', self::PUBLIC_ROUTES_RELATIVE_PATH],
        24 => ['bin/censo-sql-concatenado'],
        25 => ['bin/censo-formas-de-lectura'],
        26 => [self::PHPSTAN_BASELINE_RELATIVE_PATH],
        27 => ['bin/censo-sql-identificadores'],
        28 => ['bin/censo-sql-interpolado'],
        29 => [self::UPLOAD_DIRS_RELATIVE_PATH],
        30 => [self::GET_ROUTE_ALLOWED_RELATIVE_PATH],
        32 => [self::EXCEPTION_MESSAGE_DECLARED_RELATIVE_PATH],
        34 => [self::LOGIN_USER_DATA_RELATIVE_PATH],
        35 => [self::DECLARED_PERMISSIONS_RELATIVE_PATH],
        36 => ['bin/censo-plantillas'],
        38 => ['files/dev/column-renames.json'],
        39 => [self::ANNOUNCED_CAPABILITIES_RELATIVE_PATH, 'bin/censo-claves-config'],
        40 => [self::CAMPAIGN_REMOVALS_RELATIVE_PATH, 'bin/censo-borrados'],
        44 => ['bin/censo-rutas-doc', 'files/dev/doc-path-exemptions.json'],
        45 => ['bin/censo-claves-config', 'files/dev/doc-config-exemptions.json'],
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

        $signatureFailures = Distribution::applies('2', self::DISTRIBUTION_REQUIREMENTS[2]) ? self::compareSignatures($snapshotPath, $signatures) : [];

        if (TerminalData::instance()->getArgument('update-permissions', 'no') === 'yes') {
            $permisos = self::collectDeclaredPermissions();
            $canarios = self::validateDeclaredPermissions($permisos);
            if (count($canarios) > 0) {
                foreach ($canarios as $line) {
                    echoTerminal("\e[31mPERMISO:\e[39m {$line}");
                }
                echoTerminal("\e[31mLa línea base NO se escribe: el instrumento no ve lo que dice ver.\e[39m");
                exit(1);
            }
            if (!self::writeDeclaredPermissions($permisos)) {
                echoTerminal("\e[31mPERMISO:\e[39m no se pudo escribir " . self::DECLARED_PERMISSIONS_RELATIVE_PATH . ": la línea base NO se ha regenerado.");
                exit(1);
            }
            echoTerminal("\e[34mLínea base de permisos regenerada:\e[39m " . count($permisos) . " rutas.");
            echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
            exit(0);
        }

        //──── 3. Las clases declaradas se pueden cargar ─────────────────────────────────
        $loadFailures = self::checkClassesAreLoadable($files);

        //──── 4. El núcleo no eclipsa clases de los paquetes ────────────────────────────
        $eclipseFailures = self::checkPackageEclipses($files);

        //──── 5. Las sobreescrituras de rutas están registradas y siguen decidiendo ─────
        $overrideFailures = self::checkRouteOverrides($files);

        //──── 6. No hay llamadas a funciones deprecadas ─────────────────────────────────
        $deprecatedFailures = Distribution::applies('6', self::DISTRIBUTION_REQUIREMENTS[6]) ? self::checkDeprecatedFunctions($files) : [];

        //──── 7. Los paquetes no se han desviado del instrumental común ─────────────────
        $toolchainFailures = Distribution::applies('7', self::DISTRIBUTION_REQUIREMENTS[7]) ? self::checkSharedToolchain() : [];

        //──── 8. No hay comentarios narrativos fuera del registro ───────────────────────
        $narrativeFailures = Distribution::applies('8', self::DISTRIBUTION_REQUIREMENTS[8]) ? self::checkNarrativeComments() : [];

        //──── 9. Los guiones de bin/ están marcados como ejecutables EN EL ÍNDICE ───────
        $executableFailures = self::checkExecutableBits();

        //──── 10. Los tipos declarados en los mappers existen ───────────────────────────
        $typeFailures = self::checkDeclaredTypes($files);

        //──── 11. La lista de tablas volátiles coincide con la que se deriva del código ─
        $volatileFailures = Distribution::applies('11', self::DISTRIBUTION_REQUIREMENTS[11]) ? self::checkVolatileTablesMatchCode() : [];

        //──── 12. La lista de rutas prohibidas vive en un solo sitio ────────────────────
        $forbiddenFailures = Distribution::applies('12', self::DISTRIBUTION_REQUIREMENTS[12]) ? self::checkForbiddenRoutesAreSingle() : [];

        //──── 13. Todo el PHP versionado se analiza o está declarado fuera ──────────────
        $universeFailures = Distribution::applies('13', self::DISTRIBUTION_REQUIREMENTS[13]) ? self::checkPhpStanUniverse() : [];

        //──── 14. Todo objectToMapper() siembra la instantánea de la fila ───────────────
        $seedingFailures = self::checkSnapshotSeeding($files);

        //──── 15. Ninguna propiedad se declara después del primer método ────────────────
        $orderFailures = self::checkPropertiesBeforeMethods();

        //──── 16. Ningún docblock quedó separado de lo que documenta ────────────────────
        $orphanFailures = self::checkOrphanDocblocks($files);

        //──── 17. La versión instalada de cada paquete contra la última etiquetada ──────
        $versiones = self::collectPackageVersions();

        //──── 18. Ningún `if/else` con las dos ramas iguales ───────────────────────────
        $twinFailures = Distribution::applies('18', self::DISTRIBUTION_REQUIREMENTS[18]) ? self::checkTwinBranches($files) : [];

        //──── 19. Los retornos ignorados sin declarar no han crecido ───────────────────
        $returnFailures = Distribution::applies('19', self::DISTRIBUTION_REQUIREMENTS[19]) ? self::checkIgnoredReturns() : [];

        //──── 20. Ningún enlace del árbol servido apunta al vacío ──────────────────────
        $symlinkFailures = self::checkDanglingDelegatedLinks();

        //──── 21. Las claves de traducción que nadie pide no han crecido ───────────────
        $langFailures = Distribution::applies('21', self::DISTRIBUTION_REQUIREMENTS[21]) ? self::checkOrphanLangKeys() : [];

        //──── 22. Las etiquetas de cada vista cuadran ──────────────────────────────────
        $tagFailures = self::checkViewTagBalance();

        //──── 23. Ninguna ruta de un módulo con control de acceso queda sin declarar ────
        $routeDeclFailures = Distribution::applies('23', self::DISTRIBUTION_REQUIREMENTS[23]) ? self::checkUndeclaredRoutesInGuardedModules() : [];

        //──── 24. Las concatenaciones de SQL con valor de petición no han crecido ───────
        $sqlFailures = Distribution::applies('24', self::DISTRIBUTION_REQUIREMENTS[24]) ? self::checkConcatenatedSql() : [];

        //──── 25. Ninguna forma «para leer» acaba ejecutándose sin declararlo ───────────
        $readingFailures = Distribution::applies('25', self::DISTRIBUTION_REQUIREMENTS[25]) ? self::checkReadingForms() : [];

        //──── 26. Las tres cifras de la línea base de PHPStan dicen lo mismo ────────────
        $baselineFailures = Distribution::applies('26', self::DISTRIBUTION_REQUIREMENTS[26]) ? self::checkPhpStanBaselineAgrees() : [];

        //──── 27. Ningún identificador de SQL viene de la petición ──────────────────────
        $identifierFailures = Distribution::applies('27', self::DISTRIBUTION_REQUIREMENTS[27]) ? self::checkSqlIdentifiers() : [];

        //──── 28. La interpolación de SQL con valor de petición no ha crecido ───────────
        $interpolationFailures = Distribution::applies('28', self::DISTRIBUTION_REQUIREMENTS[28]) ? self::checkInterpolatedSql() : [];

        //──── 29. Toda carpeta de subidas está protegida o declarada ────────────────────
        $uploadFailures = Distribution::applies('29', self::DISTRIBUTION_REQUIREMENTS[29]) ? self::checkUploadDirsProtected() : [];

        //──── 30. Ningún get_route() con nombre literal fuera de su lista ───────────────
        $getRouteFailures = Distribution::applies('30', self::DISTRIBUTION_REQUIREMENTS[30]) ? self::checkGetRouteLiterals() : [];

        //──── 31. Todas las suites y tareas cargaron al arrancar ────────────────────────
        $bootLoadFailures = self::checkBootLoadFailures();

        //──── 32. Ningún mensaje de excepción llega a la respuesta sin declararse ───────
        $exceptionMessageFailures = Distribution::applies('32', self::DISTRIBUTION_REQUIREMENTS[32]) ? self::checkExceptionMessages() : [];

        //──── 33. El nombre de la sesión de PHP y el del JavaScript no divergen ─────────
        $sessionNameFailures = self::checkSessionTokenName();

        //──── 34. Al navegador solo viajan los campos declarados del usuario ────────────
        $loginUserDataFailures = Distribution::applies('34', self::DISTRIBUTION_REQUIREMENTS[34]) ? self::checkLoginUserData() : [];

        //──── 35. Quién puede abrir cada ruta no ha cambiado sin declararse ─────────────
        $declaredPermissionFailures = Distribution::applies('35', self::DISTRIBUTION_REQUIREMENTS[35]) ? self::checkDeclaredPermissions() : [];

        //──── 36. Toda plantilla nombrada en un render(), un _render() o un view() existe ─
        $templateFailures = Distribution::applies('36', self::DISTRIBUTION_REQUIREMENTS[36]) ? self::checkRenderedTemplatesExist() : [];

        //──── 37. Todo controlador que dependa del modelo deducido resuelve a uno que existe ─
        $deducedModelFailures = self::checkModelIsAssigned();

        //──── 38. Ninguna columna del esquema lleva guion bajo sin estar declarada ──────
        $columnRenameFailures = Distribution::applies('38', self::DISTRIBUTION_REQUIREMENTS[38]) ? self::checkSchemaColumnNaming() : [];

        //──── 39. Toda capacidad anunciada tiene su ruta o su acción de terminal ────────
        $capabilityFailures = Distribution::applies('39', self::DISTRIBUTION_REQUIREMENTS[39]) ? self::checkAnnouncedCapabilities() : [];

        //──── 40. Nada nombra una ruta que la campaña borró o movió ─────────────────────
        $remnantFailures = Distribution::applies('40', self::DISTRIBUTION_REQUIREMENTS[40]) ? self::checkCampaignRemnants() : [];

        //──── 41. Cada archivo de configuración dice de quién es ────────────────────────
        $configOwnershipFailures = self::checkConfigOwnership();

        //──── 42. Ninguna ruta declara un alias ─────────────────────────────────────────
        $aliasFailures = self::checkNoRouteAlias();

        //──── 43. La decisión de la entrega del correo vive en un solo sitio ────────────
        $mailDecisionFailures = self::checkMailDeliveryDecision();

        //──── 44. La documentación no nombra rutas que no existen ───────────────────────
        $docPathFailures = Distribution::applies('44', self::DISTRIBUTION_REQUIREMENTS[44]) ? self::checkDocumentedPaths() : [];

        //──── 45. La documentación no nombra claves de configuración que no existen ─────
        $docConfigFailures = Distribution::applies('45', self::DISTRIBUTION_REQUIREMENTS[45]) ? self::checkDocumentedConfigKeys() : [];

        //──── 46. El número de registro ni se recorta ni sirve para ordenar ─────────────
        $paddingFailures = self::checkRegisterNumberPadding();
        //──── 47. La distribución pública lleva lo decidido y nada de la campaña ────────
        $distributionFailures = Distribution::applies('47', ['bin/make-distribution', 'bin/distribution-exclude.txt']) ? self::checkDistribution() : [];

        //──── Resultado ─────────────────────────────────────────────────────────────────
        $failures = count($docblockFailures) + count($signatureFailures)
            + count($loadFailures) + count($eclipseFailures) + count($overrideFailures)
            + count($deprecatedFailures) + count($toolchainFailures) + count($narrativeFailures)
            + count($executableFailures) + count($typeFailures) + count($volatileFailures)
            + count($forbiddenFailures) + count($universeFailures) + count($seedingFailures)
            + count($orderFailures) + count($orphanFailures) + count($mailDecisionFailures)
            + count($versiones['fallos']) + count($twinFailures) + count($returnFailures)
            + count($symlinkFailures) + count($langFailures) + count($tagFailures)
            + count($routeDeclFailures) + count($sqlFailures) + count($readingFailures)
            + count($baselineFailures) + count($identifierFailures) + count($interpolationFailures)
            + count($uploadFailures) + count($getRouteFailures) + count($bootLoadFailures) + count($exceptionMessageFailures)
            + count($sessionNameFailures) + count($loginUserDataFailures)
            + count($declaredPermissionFailures) + count($templateFailures)
            + count($deducedModelFailures) + count($columnRenameFailures) + count($capabilityFailures)
            + count($remnantFailures) + count($configOwnershipFailures) + count($aliasFailures)
            + count($docPathFailures) + count($docConfigFailures) + count($paddingFailures)
            + count($distributionFailures);

        foreach ($returnFailures as $line) {
            echoTerminal("\e[31mRETORNO:\e[39m {$line}");
        }
        foreach ($sessionNameFailures as $line) {
            echoTerminal("\e[31mSESIÓN:\e[39m {$line}");
        }
        foreach ($loginUserDataFailures as $line) {
            echoTerminal("\e[31mNAVEGADOR:\e[39m {$line}");
        }
        foreach ($declaredPermissionFailures as $line) {
            echoTerminal("\e[31mPERMISO:\e[39m {$line}");
        }
        foreach ($templateFailures as $line) {
            echoTerminal("\e[31mPLANTILLA:\e[39m {$line}");
        }
        foreach ($deducedModelFailures as $line) {
            echoTerminal("\e[31mMODELO:\e[39m {$line}");
        }
        foreach ($columnRenameFailures as $line) {
            echoTerminal("\e[31mCOLUMNA:\e[39m {$line}");
        }
        foreach ($capabilityFailures as $line) {
            echoTerminal("\e[31mCAPACIDAD:\e[39m {$line}");
        }
        foreach ($remnantFailures as $line) {
            echoTerminal("\e[31mRESTO:\e[39m {$line}");
        }
        foreach ($configOwnershipFailures as $line) {
            echoTerminal("\e[31mCONFIGURACIÓN:\e[39m {$line}");
        }
        foreach ($mailDecisionFailures as $line) {
            echoTerminal("\e[31mCORREO:\e[39m {$line}");
        }
        foreach ($aliasFailures as $line) {
            echoTerminal("\e[31mALIAS:\e[39m {$line}");
        }
        foreach ($paddingFailures as $line) {
            echoTerminal("\e[31mNÚMERO DE REGISTRO:\e[39m {$line}");
        }
        foreach ($distributionFailures as $line) {
            echoTerminal("\e[31mDISTRIBUCIÓN:\e[39m {$line}");
        }
        foreach ($docPathFailures as $line) {
            echoTerminal("\e[31mDOCUMENTACIÓN:\e[39m {$line}");
        }
        foreach ($docConfigFailures as $line) {
            echoTerminal("\e[31mDOCUMENTACIÓN:\e[39m {$line}");
        }
        foreach ($symlinkFailures as $line) {
            echoTerminal("\e[31mENLACE:\e[39m {$line}");
        }
        foreach ($langFailures as $line) {
            echoTerminal("\e[31mTRADUCCIÓN:\e[39m {$line}");
        }
        foreach ($tagFailures as $line) {
            echoTerminal("\e[31mETIQUETA:\e[39m {$line}");
        }
        foreach ($routeDeclFailures as $line) {
            echoTerminal("\e[31mRUTA SIN DECLARAR:\e[39m {$line}");
        }
        foreach ($sqlFailures as $line) {
            echoTerminal("\e[31mSQL CONCATENADO:\e[39m {$line}");
        }
        foreach ($readingFailures as $line) {
            echoTerminal("\e[31mFORMA DE LECTURA:\e[39m {$line}");
        }
        foreach ($baselineFailures as $line) {
            echoTerminal("\e[31mLÍNEA BASE:\e[39m {$line}");
        }
        foreach ($identifierFailures as $line) {
            echoTerminal("\e[31mSQL IDENTIFICADOR:\e[39m {$line}");
        }
        foreach ($interpolationFailures as $line) {
            echoTerminal("\e[31mSQL INTERPOLADO:\e[39m {$line}");
        }
        foreach ($uploadFailures as $line) {
            echoTerminal("\e[31mSUBIDAS:\e[39m {$line}");
        }
        foreach ($getRouteFailures as $line) {
            echoTerminal("\e[31mGET_ROUTE:\e[39m {$line}");
        }
        foreach ($exceptionMessageFailures as $line) {
            echoTerminal("\e[31mMENSAJE DE EXCEPCIÓN:\e[39m {$line}");
        }
        foreach ($bootLoadFailures as $line) {
            echoTerminal("\e[31mARRANQUE:\e[39m {$line}");
        }
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
        foreach ($forbiddenFailures as $line) {
            echoTerminal("\e[31mPROHIBIDAS:\e[39m {$line}");
        }
        foreach ($universeFailures as $line) {
            echoTerminal("\e[31mUNIVERSO:\e[39m {$line}");
        }
        foreach ($seedingFailures as $line) {
            echoTerminal("\e[31mINSTANTÁNEA:\e[39m {$line}");
        }
        foreach ($orderFailures as $line) {
            echoTerminal("\e[31mORDEN:\e[39m {$line}");
        }
        foreach ($orphanFailures as $line) {
            echoTerminal("\e[31mDOCBLOCK HUÉRFANO:\e[39m {$line}");
        }
        foreach ($versiones['fallos'] as $line) {
            echoTerminal("\e[31mVERSIÓN:\e[39m {$line}");
        }
        foreach ($versiones['avisos'] as $line) {
            echoTerminal("\e[33mVERSIÓN:\e[39m {$line}");
        }
        foreach ($twinFailures as $line) {
            echoTerminal("\e[31mRAMAS GEMELAS:\e[39m {$line}");
        }

        if ($failures === 0) {
            echoTerminal("\e[32mOK:\e[39m docblocks, firmas, carga, eclipses, rutas, deprecadas, instrumental, comentarios, bits de ejecución, tipos, volátiles, rutas prohibidas, universo de análisis, instantáneas, orden de propiedades, docblocks, ramas gemelas, línea base de PHPStan, permisos declarados, plantillas de las vistas, el modelo asignado de cada controlador, capacidades anunciadas, restos de la campaña, de quién es cada configuración, las rutas y las claves de configuración que nombra la documentación, la distribución pública y carga del arranque sin novedad. Las versiones de los paquetes se informan arriba: avisan, no fallan.");
            if (Distribution::announcedCount() > 0) {
                echoTerminal("\e[33mEN LA DISTRIBUCIÓN:\e[39m " . Distribution::announcedCount() . ' comprobación(es) NO se hicieron, cada una con su línea «'
                    . Distribution::NOT_APPLICABLE . '» arriba: el OK solo vale para las demás.');
            }
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

        $ilegibles = 0;

        foreach ($files as $relative) {
            $content = @file_get_contents($base . '/' . $relative);
            if (!is_string($content)) {
                //NO SE DESCARTA EN SILENCIO: se cuenta y se publica abajo. LEY 15.
                $ilegibles++;
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

        echoTerminal("\e[94mINFO:\e[39m " . (count($files) - $ilegibles) . " archivo(s) con sus docblocks comprobados"
            . ($ilegibles > 0 ? ", {$ilegibles} ilegible(s) y por tanto SIN comprobar." : ", ninguno ilegible."));

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
        //──── Propiedad del recurso ────────────────────────────────────────────────────
        'News\\Controllers\\NewsController::_allowedRoute' => 'actions-delete: solo el creador, o los tipos de NewsMapper::CAN_DELETE_ALL.',
        'PiecesPHP\\BuiltIn\\Banner\\Controllers\\BuiltInBannerController::_allowedRoute' => 'actions-delete: solo el creador, o BuiltInBannerMapper::CAN_DELETE_ALL.',
        'Documents\\Controllers\\DocumentsController::_allowedRoute' => 'Borrado y edición: solo el creador, o CAN_DELETE_ALL / CAN_EDIT_ALL.',
        'Forms\\DocumentTypes\\Controllers\\DocumentTypesController::_allowedRoute' => 'Borrado y edición: solo el creador, o CAN_DELETE_ALL / CAN_EDIT_ALL.',
        'Forms\\Categories\\Controllers\\CategoriesController::_allowedRoute' => 'Borrado y edición: solo el creador, o CAN_DELETE_ALL / CAN_EDIT_ALL.',

        //──── Propiedad del recurso MÁS pertenencia a la organización ──────────────────
        'Publications\\Controllers\\PublicationsController::_allowedRoute' => 'Borrado y edición: creador o autor, o administrador de la MISMA organización que el creador, o CAN_DELETE_ALL / CAN_EDIT_ALL.',
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
        //El cuerpo EXACTO de la plantilla neutra sale del propio trait: si el trait cambia, la
        //comparación cambia con él y ninguna copia queda exenta por parecerse.
        $canonicalAllowed = self::routeMethodBody($traitCode, '_allowedRoute');
        if ($canonical === null) {
            return [self::ROUTING_TRAIT_PATH . ' ya no declara routeName(): la comprobación no pudo mirar nada'];
        }

        $found = [];
        $plantillas = 0;

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

                //LA PLANTILLA NEUTRA NO SE REGISTRA: un cuerpo que no decide nada no tiene razón
                //que declarar. Va en las 41 por decisión del PROPIETARIO. Ver T149.
                if ($method === '_allowedRoute' && $canonicalAllowed !== null
                    && trim($body['body']) === trim($canonicalAllowed['body'])) {
                    $plantillas++;
                    continue;
                }

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

        echoTerminal("\e[94mINFO:\e[39m " . count($found) . " sobreescritura(s) de ruta comprobadas contra el registro, "
            . "de las que " . $plantillas . " son la plantilla neutra de `_allowedRoute`.");

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
        $sinSeguimiento = 0;

        foreach ((array) ($registry['packages'] ?? []) as $package) {
            $packageRoot = $packagesRoot . '/' . $package;
            if (!is_dir($packageRoot)) {
                continue; //No está clonado al lado: no es un fallo de este repositorio.
            }
            $present++;

            $omitidoSeguimiento = 0;
            $failures = array_merge($failures, self::checkToolchainTracking($package, $packageRoot, $registry, $omitidoSeguimiento));
            $sinSeguimiento += $omitidoSeguimiento;

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

        //LA VERSION DEL ANALIZADOR, no solo la marca: una marca presente no dice CON QUE se
        //midio, y los cinco repositorios miden hoy con tres phpstan distintos. Ver T165.
        $analizadores = 0;
        foreach ((array) ($registry['analyzers'] ?? []) as $repo => $declarado) {
            if (!is_array($declarado) || !isset($declarado['version'])) {
                continue;
            }
            $raiz = $repo === 'piecesphp' ? dirname($repoRoot) . '/bin/tools' : $packagesRoot . '/' . $repo;
            $instalado = self::installedAnalyzerVersion($raiz);
            if ($instalado === null) {
                $failures[] = $repo . ' — no se pudo leer la versión de phpstan en ' . $raiz . ': SIN COMPROBAR';
                continue;
            }
            $analizadores++;
            if ($instalado !== (string) $declarado['version']) {
                $failures[] = $repo . ' — phpstan DECLARADO ' . $declarado['version'] . ' e INSTALADO ' . $instalado
                    . '. Una cifra medida con otro analizador no es comparable.';
            }
        }

        echoTerminal("\e[94mINFO:\e[39m {$present} paquetes comprobados contra el instrumental común"
            . ($sinSeguimiento > 0 ? ", {$sinSeguimiento} sin estado de seguimiento comprobable." : ", todos con su estado de seguimiento.")
            . " {$analizadores} analizador(es) contra su versión declarada.");

        return $failures;
    }

    const NARRATIVE_RELATIVE_PATH = 'files/dev/narrative-comments.json';

    /**
     * La lista de rutas que un recorredor NUNCA pide. Vive aquí y en ningún otro sitio.
     */
    const FORBIDDEN_RELATIVE_PATH = 'files/dev/forbidden-routes.json';

    /**
     * Qué PHP del repositorio queda fuera del análisis estático, y con qué razón.
     */
    const UNIVERSE_RELATIVE_PATH = 'files/dev/phpstan-universe.json';

    /** Los paquetes con código propio que comparten esta convención. */
    const PACKAGES_WITH_SOURCE = ['database', 'datastructures', 'html', 'geojson'];

    /** Declaración de propiedad: visibilidad, nombre y `=` o `;`. Sin paréntesis por medio. */
    const PROPERTY_PATTERN = '/^\s*(?:(?:final|abstract)\s+)?(?:public|protected|private|var)(?:\s+static)?(?:\s+readonly)?(?:\s+[\w\|\\\\\?]+)?\s+\$\w+\s*(?:=|;)/';

    /** Firma de método. */
    const METHOD_PATTERN = '/^\s*(?:(?:final|abstract|public|protected|private|static)\s+)*function\s/';

    /** Apertura de clase, trait, interfaz o enum. */
    const CLASS_PATTERN = '/^\s*(?:(?:final|abstract|readonly)\s+)*(?:class|trait|interface|enum)\s/';


    /**
     * Un comentario que frena algo cabe en una línea (LEY 7).
     *
     * La regla anterior —«¿impide romper algo?»— no frenaba la deriva porque no hablaba del
     * TAMAÑO: un relato de doce líneas siempre encuentra una frase suya que sí impide romper
     * algo, y con esa se justifica entero.
     *
     * @return array{0: list<array{file: string, line: int, prose: int}>, 1: int}
     */
    protected static function collectNarrativeBlocks(): array
    {
        //`@codigo-comentado` exime al bloque: una línea de código comentada NO es relato, y
        //contarla como tal prohibía «comentar en vez de borrar». Se declara, no se adivina.
        $anotaciones = ['@param', '@return', '@var', '@package', '@author', '@throws', '@codigo-comentado'];
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
                        //`//+` y no `//`: una línea `/// <reference …>` dejaba una barra suelta delante,
                        //así que la exclusión de abajo no la reconocía y la contaba como prosa.
                        $t = trim((string) preg_replace('#^\s*(/\*\*?|\*/|\*|//+)\s?#', '', (string) $linea));
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
                    . ' La guarda cabe en una línea (LEY 7); el relato va al CHANGELOG.';
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

    /**
     * La lista de rutas prohibidas existe UNA sola vez, y todo el que la usa la lee de ahí.
     *
     * Existe porque estaba COPIADA en `bin/walk-routes` y en `bin/walk-attribute`. Lo que
     * cuesta que diverja no es ruido: un recorredor que pida una ruta de escritura ESCRIBE
     * creyendo que solo lee, se lo atribuye a una ruta de lectura, y deja inservible la foto
     * de la que depende E3. Cuando se midió, los 17 patrones aún coincidían, pero el
     * comentario que explicaba por qué se mira también la URL solo estaba en una de las dos:
     * la razón había divergido antes que el dato. Ver LEY 11 y T73.
     *
     * LÍMITE: esto LEE EL CUERPO de los archivos. Reconoce la copia por su forma y el uso por
     * el nombre `$isForbidden`; quien la reintroduzca con otro nombre pasa por delante. Avisa,
     * no demuestra. Lo que demuestra es la equivalencia de veredictos sobre el inventario.
     *
     * @return string[]
     */
    protected static function checkForbiddenRoutesAreSingle(): array
    {
        $root = rtrim(str_replace('\\', '/', basepath('..')), '/');
        $path = $root . '/' . self::FORBIDDEN_RELATIVE_PATH;

        if (!is_file($path)) {
            return ['no existe ' . self::FORBIDDEN_RELATIVE_PATH . ': la comprobación no pudo mirar nada.'];
        }

        $declared = json_decode((string) file_get_contents($path), true);
        $patterns = is_array($declared) ? ($declared['patterns'] ?? null) : null;
        if (!is_array($patterns) || count($patterns) === 0) {
            return [self::FORBIDDEN_RELATIVE_PATH . ' no declara `patterns` o está vacío.'];
        }

        $failures = [];
        $users = 0;

        foreach ((array) glob($root . '/bin/*') as $file) {
            if (!is_string($file) || !is_file($file)) {
                continue;
            }
            $content = (string) file_get_contents($file);
            $relative = 'bin/' . basename($file);

            //UNA SEGUNDA DECLARACIÓN LITERAL ES LA COPIA VOLVIENDO.
            if (preg_match('/\$forbidden\s*=\s*\[/', $content) === 1) {
                $failures[] = $relative . ' vuelve a declarar la lista con `$forbidden = [`.'
                    . ' Se lee de ' . self::FORBIDDEN_RELATIVE_PATH . ' con bin/tools/forbidden-routes.php.';
            }

            //Y QUIEN LA USA TIENE QUE LEERLA DE AHÍ, no traérsela por su cuenta.
            if (mb_strpos($content, '$isForbidden') === false) {
                continue;
            }
            $users++;
            if (mb_strpos($content, 'tools/forbidden-routes.php') === false) {
                $failures[] = $relative . ' filtra rutas prohibidas sin cargar bin/tools/forbidden-routes.php:'
                    . ' está decidiendo con una lista propia.';
            }
        }

        if ($users === 0) {
            $failures[] = 'ningún guion de bin/ usa la lista: o se dejó de filtrar, o la comprobación'
                . ' dejó de reconocer a quien lo hace.';
        }

        $failures = array_merge($failures, self::checkForbiddenAllowances($root, $declared, $patterns));

        echoTerminal("\e[94mINFO:\e[39m " . count($patterns) . ' patrón(es) de rutas prohibidas, leídos por '
            . $users . ' guion(es) desde un solo sitio.');

        return $failures;
    }

    /**
     * Las excepciones de la lista de prohibidas solo pueden liberar rutas GET.
     *
     * Una excepción se compara por subcadena igual que los patrones, así que puede pasarse de
     * ancha sin que nadie lo note. Y lo que hay al otro lado no es ruido: **una ruta de escritura
     * liberada hace que el recorredor ESCRIBA creyendo que solo lee**. Ver T100.
     *
     * @param array<string,mixed>|null $declared
     * @param array<int,string> $patterns
     * @return string[]
     */
    protected static function checkForbiddenAllowances(string $root, $declared, array $patterns): array
    {
        $allowances = is_array($declared) ? ($declared['allow'] ?? []) : [];
        if (!is_array($allowances) || $allowances === []) {
            return [];
        }

        $inventoryPath = $root . '/files/dev/route-inventory.json';
        if (!is_file($inventoryPath)) {
            return ['hay excepciones declaradas en ' . self::FORBIDDEN_RELATIVE_PATH
                . ' y no existe files/dev/route-inventory.json: no se pueden comprobar.'];
        }

        $inventory = json_decode((string) file_get_contents($inventoryPath), true);
        if (!is_array($inventory)) {
            return ['files/dev/route-inventory.json no se puede leer: las excepciones quedan sin comprobar.'];
        }

        $failures = [];
        $freed = 0;

        foreach ($allowances as $exception => $reason) {
            $exception = mb_strtolower((string) $exception);
            if (!is_string($reason) || trim($reason) === '') {
                $failures[] = 'la excepción «' . $exception . '» no declara su razón.';
            }

            $matches = 0;
            foreach ($inventory as $route) {
                if (!is_array($route)) {
                    continue;
                }
                $name = (string) ($route['name'] ?? '');
                $url = (string) ($route['url'] ?? '');
                $haystack = mb_strtolower($name . ' ' . $url);
                if (mb_strpos($haystack, $exception) === false) {
                    continue;
                }
                $vetoed = false;
                foreach ($patterns as $needle) {
                    if (mb_strpos($haystack, (string) $needle) !== false) {
                        $vetoed = true;
                        break;
                    }
                }
                if (!$vetoed) {
                    continue;
                }
                $matches++;
                //LO QUE NO PUEDE PASAR: liberar algo que no sea GET.
                if (mb_strtoupper((string) ($route['method'] ?? '')) !== 'GET') {
                    $failures[] = 'la excepción «' . $exception . '» libera ' . $name
                        . ', que es ' . (string) $route['method'] . ': el recorredor escribiría creyendo que lee.';
                }
            }

            if ($matches === 0) {
                $failures[] = 'la excepción «' . $exception . '» no libera ninguna ruta:'
                    . ' o sobra, o el patrón que la motivaba ya no existe.';
            }
            $freed += $matches;
        }

        echoTerminal("\e[94mINFO:\e[39m " . count($allowances) . ' excepción(es) declaradas liberan '
            . $freed . ' ruta(s), todas GET.');

        return $failures;
    }

    /**
     * Todo el PHP versionado está DENTRO del análisis estático o DECLARADO fuera.
     *
     * `paths` apunta a la raíz, así que TODO entra por defecto y lo único que saca código es
     * `excludePaths`. Una exclusión no baja la cifra de errores: la deja igual de verde midiendo
     * menos, que es la única forma en que uno de nuestros números puede AFIRMAR algo falso. Por
     * eso cada exclusión tiene que estar declarada con su razón. Ver T77 y T79.
     *
     * @return string[]
     */
    protected static function checkPhpStanUniverse(): array
    {
        $root = rtrim(str_replace('\\', '/', basepath('..')), '/');
        $neonPath = $root . '/bin/phpstan.neon';
        $universePath = $root . '/' . self::UNIVERSE_RELATIVE_PATH;

        if (!is_file($neonPath) || !is_file($universePath)) {
            return ['falta bin/phpstan.neon o ' . self::UNIVERSE_RELATIVE_PATH . ': la comprobación no pudo mirar nada.'];
        }

        //Solo el bloque de PRIMER nivel: el neon lleva muchos `paths:` dentro de ignoreErrors.
        $neon = str_replace("\r\n", "\n", (string) file_get_contents($neonPath));
        $blocks = ['paths' => [], 'excludePaths' => []];
        foreach (['paths', 'excludePaths'] as $key) {
            //Con comentarios y blancos dentro: el neon los lleva y son suyos, no del lector.
            if (preg_match('/^    ' . $key . ':\n((?:        .*\n|\n)*)/m', $neon, $matched) !== 1) {
                return ["bin/phpstan.neon ya no declara `{$key}:` de primer nivel: la comprobación no pudo mirar nada."];
            }
            $blocks[$key] = [];
            foreach (explode("\n", $matched[1]) as $line) {
                $line = trim($line);
                if ($line === '' || !str_starts_with($line, '- ')) {
                    continue;
                }
                //Las rutas del neon son relativas a bin/. `..` es la raíz del repositorio.
                $value = trim(mb_substr($line, 2));
                $resolved = $value === '..' ? '' : str_replace('../', '', $value);
                $blocks[$key][] = $resolved;
            }
            if (count($blocks[$key]) === 0) {
                return ["bin/phpstan.neon declara `{$key}:` sin una sola ruta: la comprobación no pudo mirar nada."];
            }
        }

        $declared = json_decode((string) file_get_contents($universePath), true);
        $reasons = is_array($declared) ? (array) ($declared['excluded'] ?? []) : [];

        //UNA EXCLUSIÓN SIN RAZÓN ESCRITA ES UN TROZO DE CÓDIGO QUE DEJA DE MEDIRSE EN SILENCIO.
        $failures = [];
        foreach ($blocks['excludePaths'] as $excluded) {
            if (!array_key_exists($excluded, $reasons)) {
                $failures[] = 'bin/phpstan.neon excluye «' . $excluded . '» y ' . self::UNIVERSE_RELATIVE_PATH
                    . ' no dice por qué. Una exclusión no baja la cifra: la deja igual midiendo menos.';
            }
        }
        foreach (array_keys($reasons) as $reason) {
            if (!in_array($reason, $blocks['excludePaths'], true)) {
                $failures[] = self::UNIVERSE_RELATIVE_PATH . ' justifica «' . $reason
                    . '», que el neon ya no excluye. La lista solo puede encoger: quítala.';
            }
        }

        $output = [];
        $status = 0;
        //`core.quotePath=false`: sin él git ENTRECOMILLA los nombres con acentos y el archivo
        //se pierde en silencio, que es justo lo que esta comprobación viene a impedir.
        exec('git -C ' . escapeshellarg($root) . ' -c core.quotePath=false ls-files 2>/dev/null', $output, $status);
        if ($status !== 0) {
            return [];
        }

        $uncovered = [];
        $analysed = 0;
        $excludedCount = 0;
        $total = 0;

        foreach ($output as $relative) {
            $file = $root . '/' . $relative;
            if (!is_file($file)) {
                continue;
            }
            if (!self::looksLikePhp($relative, $file)) {
                continue;
            }
            $total++;

            $under = static function (array $prefixes) use ($relative): bool {
                foreach ($prefixes as $prefix) {
                    //Cadena vacía = la raíz del repositorio: lo cubre todo.
                    if ($prefix === '' || $relative === $prefix || str_starts_with($relative, rtrim($prefix, '/') . '/')) {
                        return true;
                    }
                }
                return false;
            };

            if ($under($blocks['excludePaths'])) {
                $excludedCount++;
                continue;
            }
            if ($under($blocks['paths'])) {
                $analysed++;
                continue;
            }
            $uncovered[] = $relative;
        }

        foreach (array_slice($uncovered, 0, 10) as $relative) {
            $failures[] = $relative . ' — ni lo analiza PHPStan ni está excluido. El baseline no lo'
                . ' cuenta y aun así sale verde.';
        }
        if (count($uncovered) > 10) {
            $failures[] = '… y ' . (count($uncovered) - 10) . ' archivo(s) más en la misma situación.';
        }

        echoTerminal("\e[94mINFO:\e[39m {$total} archivo(s) PHP versionados: {$analysed} analizados y {$excludedCount} excluidos, "
            . 'con ' . count($reasons) . ' exclusión(es) declarada(s).');

        return $failures;
    }




    /**
     * Ningún docblock queda separado de lo que documenta.
     *
     * Un docblock con `@param` o `@return` seguido de OTRO docblock significa que alguien
     * insertó algo entre la documentación y su firma. Pasó dos veces en una sola sesión, las
     * dos por editar anclando en la línea `function` sin mirar lo que llevaba encima. Ver T91.
     *
     * @param string[] $files
     * @return string[]
     */
    protected static function checkOrphanDocblocks(array $files): array
    {
        $failures = [];
        $checked = 0;

        foreach ($files as $relative) {
            $path = basepath($relative);
            if (!is_file($path)) {
                continue;
            }
            $checked++;
            $lines = explode("\n", str_replace("\r\n", "\n", (string) file_get_contents($path)));
            $index = 0;
            $count = count($lines);

            while ($index < $count) {
                if (!str_starts_with(trim($lines[$index]), '/**')) {
                    $index++;
                    continue;
                }
                $end = $index;
                $documents = false;
                while ($end < $count && !str_ends_with(trim($lines[$end]), '*/')) {
                    if (preg_match('/@(param|return)\b/', $lines[$end]) === 1) {
                        $documents = true;
                    }
                    $end++;
                }
                $next = $end + 1;
                while ($next < $count && trim($lines[$next]) === '') {
                    $next++;
                }
                if ($documents && $next < $count && str_starts_with(trim($lines[$next]), '/**')) {
                    $failures[] = $relative . ':' . ($index + 1) . ' — un docblock con @param/@return'
                        . ' va seguido de otro docblock: quedó separado de lo que documenta.';
                }
                $index = $end + 1;
            }
        }

        echoTerminal("\e[94mINFO:\e[39m {$checked} archivo(s) comprobados: ningún docblock separado de su firma.");

        return $failures;
    }
    /**
     * Ninguna propiedad se declara DESPUÉS del primer método de su clase.
     *
     * Corre sobre los cinco repositorios, no solo sobre este: la convención es común y una
     * comprobación que solo mira su propia casa no cierra nada. Ver T89.
     *
     * @return string[]
     */
    protected static function checkPropertiesBeforeMethods(): array
    {
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $packagesRoot = dirname(dirname($repoRoot));
        $roots = [dirname($repoRoot) . '/src', dirname($repoRoot) . '/bin', dirname($repoRoot) . '/tasks'];

        foreach (self::PACKAGES_WITH_SOURCE as $package) {
            $roots[] = $packagesRoot . '/' . $package . '/src';
        }

        $failures = [];
        $scanned = 0;

        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $entry) {
                $path = str_replace('\\', '/', $entry->getPathname());
                if (!str_ends_with($path, '.php')) {
                    continue;
                }
                if (preg_match('#/(vendor|node_modules|phpstan-src|test-data)/#', $path) === 1) {
                    continue;
                }
                $scanned++;
                foreach (self::propertiesAfterMethods($path) as $line => $code) {
                    $relative = str_replace($packagesRoot . '/', '', $path);
                    $failures[] = (is_array($relative) ? implode('', $relative) : $relative) . ":{$line} — " . $code
                        . ' se declara después del primer método. Las propiedades van arriba.';
                }
            }
        }

        echoTerminal("\e[94mINFO:\e[39m {$scanned} archivo(s) PHP comprobados en los cinco repositorios: propiedades antes que métodos.");

        return $failures;
    }

    /**
     * @return array<int, string> Línea => código, de cada propiedad mal colocada.
     */
    protected static function propertiesAfterMethods(string $path): array
    {
        $raw = (string) @file_get_contents($path);
        $lines = explode("\n", str_replace("\r\n", "\n", $raw));

        $firstMethod = null;
        $promoted = 0;
        $found = [];

        foreach ($lines as $index => $line) {
            //Cada clase empieza de cero: un archivo con dos clases no arrastra la primera.
            if (preg_match(self::CLASS_PATTERN, $line) === 1) {
                $firstMethod = null;
                continue;
            }
            if (str_contains($line, 'function') && preg_match(self::METHOD_PATTERN, $line) === 1) {
                if ($firstMethod === null) {
                    $firstMethod = $index;
                }
                //Las propiedades promovidas van dentro de la lista de parámetros: no se pueden subir.
                $promoted = substr_count($line, '(') - substr_count($line, ')');
                continue;
            }
            if ($promoted > 0) {
                $promoted += substr_count($line, '(') - substr_count($line, ')');
                continue;
            }
            if ($firstMethod !== null && preg_match(self::PROPERTY_PATTERN, $line) === 1) {
                $found[$index + 1] = trim($line);
            }
        }

        return $found;
    }
    /**
     * Todo `objectToMapper()` siembra la instantánea de la fila.
     *
     * La fila entera ya llega como argumento —para eso existe el método—, así que sembrarla es
     * una línea. Sin ella, esos objetos dirían «no lo sé» y son 113 llamadas: el camino de los
     * listados. Ver T87.
     *
     * @param string[] $files
     * @return string[]
     */
    protected static function checkSnapshotSeeding(array $files): array
    {
        $failures = [];
        $seeded = 0;

        foreach ($files as $relative) {
            $path = basepath($relative);
            if (!is_file($path)) {
                continue;
            }
            $content = (string) file_get_contents($path);
            //La DECLARACIÓN, no la mención: si no, esta comprobación se cuenta a sí misma.
            if (preg_match('/public static function objectToMapper\s*\(/', $content) !== 1) {
                continue;
            }
            if (mb_strpos($content, 'seedSnapshotFrom(') === false) {
                $failures[] = $relative . ' define objectToMapper() y no siembra la instantánea:'
                    . ' falta `$mapper->seedSnapshotFrom($element);`.';
                continue;
            }
            $seeded++;
        }

        echoTerminal("\e[94mINFO:\e[39m {$seeded} objectToMapper() comprobados: todos siembran la instantánea.");

        return $failures;
    }
    /**
     * ¿Es PHP? Por extensión, o por almohadilla-admiración: `bin/walk-routes` no lleva `.php`.
     *
     * @return bool
     */
    protected static function looksLikePhp(string $relative, string $file): bool
    {
        if (str_ends_with(mb_strtolower($relative), '.php')) {
            return true;
        }

        $handle = @fopen($file, 'rb');
        if ($handle === false) {
            return false;
        }
        $firstLine = (string) fgets($handle, 512);
        fclose($handle);

        return mb_substr($firstLine, 0, 2) === '#!' && mb_strpos($firstLine, 'php') !== false;
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
    protected static function checkToolchainTracking(string $package, string $packageRoot, array $registry, ?int &$omitido = null): array
    {
        $omitido = 0;
        $tracking = $registry['tracking'] ?? null;
        if (!is_array($tracking)) {
            $omitido = 1;
            return [];
        }
        if (!is_dir($packageRoot . '/.git')) {
            //Sin repositorio no hay estado de seguimiento que comprobar. SE CUENTA, no se calla.
            $omitido = 1;
            return [];
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

    /**
     * Compara la versión INSTALADA de cada paquete `piecesphp/*` con la última ETIQUETADA
     * en el repositorio hermano.
     *
     * NO produce fallos: que la instalada vaya por detrás de la etiquetada puede ser
     * deliberado —una etiqueta preparada y todavía sin empujar es un estado legítimo—. Lo
     * que no puede es no verse. Ver T107.
     *
     * @return array{avisos: string[], fallos: string[]}
     */
    protected static function collectPackageVersions(): array
    {
        $avisos = [];
        $fallos = [];
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $lockPath = $repoRoot . '/composer.lock';

        $raw = @file_get_contents($lockPath);
        $lock = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($lock)) {
            //Sin el lock no hay nada que comparar, y eso SÍ es un fallo: la comprobación no miró nada.
            return ['avisos' => [], 'fallos' => ['no se pudo leer src/composer.lock: la comparación de versiones NO se hizo.']];
        }

        $packagesRoot = dirname(dirname($repoRoot));
        $instalados = [];
        foreach (array_merge((array) ($lock['packages'] ?? []), (array) ($lock['packages-dev'] ?? [])) as $paquete) {
            $nombre = (string) ($paquete['name'] ?? '');
            if (!str_starts_with($nombre, 'piecesphp/')) {
                continue;
            }
            $instalados[$nombre] = (string) ($paquete['version'] ?? '');
        }

        if (count($instalados) === 0) {
            return ['avisos' => ['src/composer.lock no declara ningún paquete `piecesphp/*`.'], 'fallos' => []];
        }

        $comparados = 0;
        $alDia = 0;
        $descartadasForma = 0;
        $descartadasPre = 0;
        $etiquetasVistas = 0;
        foreach ($instalados as $nombre => $instalada) {
            $directorio = $packagesRoot . '/' . substr($nombre, strlen('piecesphp/'));
            if (!is_dir($directorio . '/.git')) {
                //Un despliegue no tiene por qué tener los paquetes al lado: se DICE, no se aprueba en silencio.
                $avisos[] = $nombre . ' — instalada ' . $instalada . '; sin veredicto: no está clonado al lado.';
                continue;
            }
            $descartes = null;
            $etiqueta = self::latestLocalTag($directorio, $descartes);
            $descartadasForma += (int) ($descartes['forma'] ?? 0);
            $descartadasPre += (int) ($descartes['prelanzamiento'] ?? 0);
            $etiquetasVistas += (int) ($descartes['aceptadas'] ?? 0);
            if ($etiqueta === null) {
                $avisos[] = $nombre . ' — instalada ' . $instalada . '; sin veredicto: no tiene ninguna etiqueta con forma de versión.';
                continue;
            }
            $comparados++;
            if (version_compare(ltrim($instalada, 'v'), ltrim($etiqueta, 'v'), '==')) {
                $alDia++;
                continue;
            }
            $avisos[] = $nombre . ' — INSTALADA ' . $instalada . ', ETIQUETADA ' . $etiqueta
                . '. Puede ser deliberado; queda dicho.';
        }

        $avisos[] = $comparados . ' paquete(s) comparado(s), ' . $alDia . ' al día.';
        //LO QUE SE TIRA SE DICE: el filtro anterior descartaba `v3`, `v4` y las `vX.Y` sin
        //contarlas, y acertaba por suerte. Ver T165.
        $avisos[] = $etiquetasVistas . ' etiqueta(s) con forma de versión aceptadas; '
            . $descartadasForma . ' descartada(s) por forma y '
            . $descartadasPre . ' por ser pre-lanzamiento, que se ignoran a propósito.';
        return ['avisos' => $avisos, 'fallos' => $fallos];
    }

    /**
     * Última etiqueta local con forma de versión, ordenada por versión y no alfabéticamente.
     *
     * ACEPTA `vX`, `vX.Y` y `vX.Y.Z`, y NORMALIZA a tres partes antes de comparar. El filtro
     * anterior exigía exactamente tres, así que descartaba `v3`, `v4` y las dieciocho `vX.Y`
     * de `piecesphp` **sin decirlo**: acertaba por suerte, y el día que la mayor se etiquetara
     * `v8` habría dicho que la última es `v7.1.0`. Ver la sección «Etiquetas de versión» de
     * `.agents/context/12-convenciones.md` y T165.
     *
     * LOS PRE-LANZAMIENTOS SE IGNORAN A PROPÓSITO —`-beta`, `-beta.N`—: una etiqueta de prueba
     * no es la última publicada. Ignorar por decisión y descartar por descuido se ven igual en
     * el resultado; lo que los separa es que uno está escrito y contado.
     *
     * @param string $directorio Raíz del repositorio del paquete.
     * @param array<string,int>|null $descartes Recibe `forma` y `prelanzamiento` si se pasa.
     * @return string|null
     */
    protected static function latestLocalTag(string $directorio, ?array &$descartes = null): ?string
    {
        $descartes = ['forma' => 0, 'prelanzamiento' => 0, 'aceptadas' => 0];
        $salida = [];
        $estado = 0;
        exec('git -C ' . escapeshellarg($directorio) . ' tag --list 2>/dev/null', $salida, $estado);
        if ($estado !== 0) {
            return null;
        }
        $versiones = [];
        foreach ($salida as $linea) {
            $etiqueta = trim((string) $linea);
            if ($etiqueta === '') {
                continue;
            }
            if (preg_match('/^v?\d+(\.\d+){0,2}$/', $etiqueta) !== 1) {
                if (preg_match('/^v?\d+(\.\d+){0,2}-/', $etiqueta) === 1) {
                    $descartes['prelanzamiento']++;
                } else {
                    $descartes['forma']++;
                }
                continue;
            }
            //A TRES PARTES ANTES DE COMPARAR: `v3` es `3.0.0`, y sin esto `version_compare`
            //ordenaría `3` por debajo de `3.0.1` de forma que nadie espera.
            $partes = explode('.', ltrim($etiqueta, 'v'));
            while (count($partes) < 3) {
                $partes[] = '0';
            }
            $versiones[] = ['etiqueta' => $etiqueta, 'normal' => implode('.', $partes)];
        }
        $descartes['aceptadas'] = count($versiones);
        if (count($versiones) === 0) {
            return null;
        }
        usort($versiones, static fn (array $a, array $b): int => version_compare($a['normal'], $b['normal']));
        $ultima = end($versiones);
        return is_array($ultima) ? (string) $ultima['etiqueta'] : null;
    }

    /**
     * Ruta del autocargador del utillaje, donde vive el analizador de sintaxis.
     *
     * @return string
     */
    protected static function toolchainAutoloadPath(): string
    {
        return dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/bin/tools/vendor/autoload.php';
    }

    /**
     * Versión de `phpstan/phpstan` instalada bajo una raíz, leída de su `installed.json`.
     *
     * @param string $raiz Directorio que contiene `vendor/`.
     * @return string|null
     */
    protected static function installedAnalyzerVersion(string $raiz): ?string
    {
        $ruta = $raiz . '/vendor/composer/installed.json';
        $crudo = @file_get_contents($ruta);
        if (!is_string($crudo)) {
            return null;
        }
        $json = json_decode($crudo, true);
        if (!is_array($json)) {
            return null;
        }
        $paquetes = isset($json['packages']) && is_array($json['packages']) ? $json['packages'] : $json;
        foreach ($paquetes as $paquete) {
            if (is_array($paquete) && ($paquete['name'] ?? '') === 'phpstan/phpstan') {
                return (string) ($paquete['version'] ?? '');
            }
        }
        return null;
    }

    /**
     * Las claves de traducción que NADIE pide no han crecido.
     *
     * Delega en `bin/censo-claves-huerfanas --trinquete`, que es donde vive el método.
     *
     * Existe porque el paso 6 de la plantilla de lote censaba IDENTIFICADORES y nunca
     * censó TEXTO VISIBLE: los cuatro lotes de E3 dieron cero honesto dentro de ese
     * universo y aun así dejaron 39 cadenas huérfanas en los diccionarios de los módulos
     * que se conservan. Ver T144.
     *
     * @return string[]
     */
    protected static function checkOrphanLangKeys(): array
    {
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $script = $root . '/bin/censo-claves-huerfanas';

        if (!is_file($script)) {
            //Una comprobación que no encuentra su instrumento NO reporta «todo bien». LEY 18.
            return ['no existe ' . $script . ': el trinquete de traducciones NO se ha comprobado'];
        }

        $output = [];
        $status = 0;
        //RETORNO-IGNORADO: `exec()` devuelve la última línea, y aquí lo que decide es $status.
        exec('cd ' . escapeshellarg($root) . ' && ' . escapeshellarg($script) . ' --trinquete 2>&1', $output, $status);

        $line = '';
        foreach ($output as $candidate) {
            if (mb_strpos($candidate, 'TRINQUETE') === 0) {
                $line = $candidate;
            }
        }

        if ($status !== 0) {
            return [$line !== '' ? $line : 'el censo de claves salió con código ' . $status];
        }

        echoTerminal("\e[94mINFO:\e[39m " . ($line !== '' ? mb_substr($line, mb_strlen('TRINQUETE: ')) : 'claves de traducción comprobadas.'));
        return [];
    }

    /**
     * Ninguna función «para leer» acaba ejecutándose sin estar declarada.
     *
     * `getCompiledSQL()` sin argumento produce SQL NO EJECUTABLE —quita los dos puntos del
     * marcador a propósito—, y `DataTablesHelper` la ejecutaba. Solo se nota cuando hay valores
     * de reemplazo, y por eso durmió años. El censo mira las 22 formas de los cinco
     * repositorios; la lista de EJECUTADA declaradas solo puede encoger. Ver T160, T161 y T164.
     *
     * @return string[]
     */
    protected static function checkReadingForms(): array
    {
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $script = $root . '/bin/censo-formas-de-lectura';

        if (!is_file($script)) {
            //Una comprobación que no encuentra su instrumento NO reporta «todo bien». LEY 18.
            return ['no existe ' . $script . ': el trinquete de formas de lectura NO se ha comprobado'];
        }

        $output = [];
        $status = 0;
        //RETORNO-IGNORADO: `exec()` devuelve la última línea, y aquí lo que decide es $status.
        exec('cd ' . escapeshellarg($root) . ' && ' . escapeshellarg($script) . ' --trinquete 2>&1', $output, $status);

        $line = '';
        $extra = [];
        foreach ($output as $candidate) {
            if (mb_strpos($candidate, 'TRINQUETE') === 0) {
                $line = $candidate;
            }
            if (mb_strpos($candidate, '                ') === 0 && mb_strpos($candidate, '.php:') !== false) {
                $extra[] = trim($candidate);
            }
        }

        if ($status !== 0) {
            return array_merge([$line !== '' ? $line : 'el censo de formas de lectura salió con código ' . $status], $extra);
        }

        echoTerminal("\e[94mINFO:\e[39m " . ($line !== '' ? mb_substr($line, mb_strlen('TRINQUETE: ')) : 'formas de lectura comprobadas.'));
        return [];
    }

    /** La línea base de PHPStan. Su cifra vive en el campo; los otros dos sitios tienen que coincidir. */
    const PHPSTAN_BASELINE_RELATIVE_PATH = 'PHPStanResult.Summary.baseline.txt';

    /**
     * Las tres cifras de la línea base de PHPStan dicen lo mismo.
     *
     * El archivo nombra la cifra vigente en TRES sitios: el campo `[TOTAL DE ERRORES VISIBLES]`
     * de su instantánea, el lado izquierdo del `[REPARTO]` más reciente, y la cabecera si nombra
     * alguna. Derivaban cada uno por su lado —749 en la cabecera, 747 en el campo, 744 en el
     * reparto, que era la verdad—, y `bin/phpstan-process-result.php` solo mira el campo.
     *
     * Lee con las convenciones de ese guion: el campo es la ÚLTIMA coincidencia y los repartos
     * van del más reciente al más antiguo. Ese orden no se supone: se comprueba que cada reparto
     * parta de donde llega el siguiente. Ver T168.
     *
     * @return string[]
     */
    protected static function checkPhpStanBaselineAgrees(): array
    {
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $path = $root . '/' . self::PHPSTAN_BASELINE_RELATIVE_PATH;

        if (!is_file($path)) {
            //Una comprobación que no encuentra lo que compara NO reporta «todo bien». LEY 18.
            return ['no existe ' . self::PHPSTAN_BASELINE_RELATIVE_PATH . ': la línea base de PHPStan NO se ha comprobado'];
        }
        $text = (string) file_get_contents($path);

        if (preg_match_all('/\[TOTAL DE ERRORES VISIBLES\]\s*\R\s*(\d+)/u', $text, $campos) < 1) {
            return [self::PHPSTAN_BASELINE_RELATIVE_PATH . ': no tiene campo [TOTAL DE ERRORES VISIBLES]; sin él no hay cifra que comparar'];
        }
        $campo = (int) $campos[1][count($campos[1]) - 1];

        if (preg_match_all('/\[REPARTO\]\s*(\d+)\s*<-\s*(\d+)/u', $text, $repartos, \PREG_SET_ORDER) < 1) {
            return [self::PHPSTAN_BASELINE_RELATIVE_PATH . ': no tiene ningún [REPARTO]; sin él la cifra no dice de dónde viene'];
        }

        $failures = [];
        $total = count($repartos);
        for ($i = 0; $i < $total - 1; $i++) {
            //LA CADENA PRUEBA EL ORDEN: sin ella, «el primero es el más reciente» sería una suposición.
            if ((int) $repartos[$i][2] !== (int) $repartos[$i + 1][1]) {
                $failures[] = 'la cadena de [REPARTO] se rompe: «' . trim($repartos[$i][0]) . '» parte de '
                    . $repartos[$i][2] . ' y el siguiente, «' . trim($repartos[$i + 1][0]) . '», llega a '
                    . $repartos[$i + 1][1] . '. Sin cadena no se sabe cuál es el más reciente.';
            }
        }

        $ultimo = (int) $repartos[0][1];
        if ($campo !== $ultimo) {
            $failures[] = 'el campo [TOTAL DE ERRORES VISIBLES] dice ' . $campo . ' y el [REPARTO] más reciente llega a '
                . $ultimo . ' («' . trim($repartos[0][0]) . '»). Son la misma cifra y no concuerdan.';
        }

        $cabecera = '';
        if (preg_match('/\[NOTA DE MEDICIÓN\][^\n]*\R\s*(.+?)(?:\R[ \t]*\R|\z)/su', $text, $nota) === 1) {
            $cabecera = $nota[1];
        }
        $enCabecera = [];
        if (preg_match_all('/(\d{1,3}(?:\.\d{3})+|\d+)\s+(?:tripletas?|errores)\b/iu', $cabecera, $nombradas) > 0) {
            $enCabecera = array_map(fn ($n) => (int) str_replace('.', '', $n), $nombradas[1]);
        }
        foreach ($enCabecera as $nombrada) {
            if ($nombrada !== $campo) {
                $failures[] = 'la cabecera nombra ' . $nombrada . ' y el campo [TOTAL DE ERRORES VISIBLES] dice ' . $campo
                    . '. La cabecera dice DE DÓNDE sale la cifra, no la repite.';
            }
        }

        if (count($failures) === 0) {
            echoTerminal("\e[94mINFO:\e[39m línea base de PHPStan: " . $campo . ' en el campo, igual que el [REPARTO] más reciente ('
                . $ultimo . ' <- ' . $repartos[0][2] . '); ' . $total . ' reparto(s) encadenados; la cabecera '
                . (count($enCabecera) > 0 ? 'nombra la misma cifra.' : 'no nombra cifra.'));
        }

        return $failures;
    }

    /**
     * Las concatenaciones de SQL con valor de petición no han crecido.
     *
     * `where(string)` y `having(string)` CONCATENAN; la vía preparada —array, `WhereSegment`,
     * `HavingSegment`— ya existe. El censo tokeniza y traza hacia atrás dentro del método.
     * Ver T150 y T151.
     *
     * @return string[]
     */
    protected static function checkConcatenatedSql(): array
    {
        return self::runSqlCensusRatchet('censo-sql-concatenado', 'SQL concatenado', 'SQL concatenado comprobado.');
    }

    /**
     * Ningún identificador de SQL (tabla, columna, lista de campos) viene de la petición. Para
     * un identificador no hay marcador: la cota es cero. Ver #020 y #024.
     *
     * @return string[]
     */
    protected static function checkSqlIdentifiers(): array
    {
        return self::runSqlCensusRatchet('censo-sql-identificadores', 'identificadores de SQL', 'identificadores de SQL comprobados.');
    }

    /**
     * La interpolación de SQL con valor de petición no ha crecido: la forma que el censo de
     * concatenaciones no ve. Las C de `processFromQuery()` se declaran con `count` EXACTO (#024).
     *
     * @return string[]
     */
    protected static function checkInterpolatedSql(): array
    {
        return self::runSqlCensusRatchet('censo-sql-interpolado', 'SQL interpolado', 'SQL interpolado comprobado.');
    }

    /**
     * Corre un censo de SQL con `--trinquete`: si falla, devuelve su línea TRINQUETE y los sitios
     * que lista; si pasa, imprime esa línea como INFO.
     *
     * @return string[]
     */
    protected static function runSqlCensusRatchet(string $scriptName, string $what, string $infoFallback): array
    {
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $script = $root . '/bin/' . $scriptName;

        if (!is_file($script)) {
            //Una comprobación que no encuentra su instrumento NO reporta «todo bien». LEY 18.
            return ['no existe ' . $script . ': el trinquete de ' . $what . ' NO se ha comprobado'];
        }

        $output = [];
        $status = 0;
        //RETORNO-IGNORADO: `exec()` devuelve la última línea, y aquí lo que decide es $status.
        exec('cd ' . escapeshellarg($root) . ' && ' . escapeshellarg($script) . ' --trinquete 2>&1', $output, $status);

        $line = '';
        $extra = [];
        foreach ($output as $candidate) {
            if (mb_strpos($candidate, 'TRINQUETE') === 0) {
                $line = $candidate;
            }
            if (mb_strpos($candidate, '                ') === 0 && mb_strpos($candidate, '.php:') !== false) {
                $extra[] = trim($candidate);
            }
        }

        if ($status !== 0) {
            return array_merge([$line !== '' ? $line : 'el censo de ' . $what . ' salió con código ' . $status], $extra);
        }

        echoTerminal("\e[94mINFO:\e[39m " . ($line !== '' ? mb_substr($line, mb_strlen('TRINQUETE: ')) : $infoFallback));
        return [];
    }

    /** Rutas públicas declaradas dentro de un módulo con control de acceso. Solo encoge. */
    /**
     * Ruta absoluta de la línea base de permisos declarados.
     *
     * @return string
     */
    protected static function declaredPermissionsPath(): string
    {
        return dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/' . self::DECLARED_PERMISSIONS_RELATIVE_PATH;
    }

    /**
     * Quién puede abrir cada ruta, preguntándoselo al framework arrancado.
     *
     * Se pregunta con `get_route_roles_allowed()`, que combina LAS DOS FUENTES: lo que declara la
     * ruta y las listas de `src/app/config/roles.php`. Leer solo `rolesAllowed` fue el origen de la
     * afirmación falsa «a users-list no la puede abrir nadie».
     *
     * El mapa va POR NOMBRE, no por entrada: el inventario trae `terminal-help` dos veces.
     *
     * @return array<string,array{requireLogin:bool,roles:array<int|string>}>
     */
    protected static function collectDeclaredPermissions(): array
    {
        $routes = get_routes();
        if (!is_array($routes)) {
            return [];
        }

        $mapa = [];

        foreach ($routes as $route) {
            $name = $route['name'] ?? null;
            if (!is_string($name) || $name === '' || isset($mapa[$name])) {
                continue;
            }

            $codigos = [];
            foreach ((array) get_route_roles_allowed($name, 'code') as $code) {
                //Un código que no sea número se deja TAL CUAL: castear a int lo convertiría en 0,
                //que es el código de root, y el diff mentiría en el sentido más caro.
                $esNumero = is_int($code) || (is_string($code) && $code !== '' && ctype_digit($code));
                $codigos[] = $esNumero ? (int) $code : $code;
            }
            $codigos = array_values(array_unique($codigos, \SORT_REGULAR));
            sort($codigos);

            $mapa[$name] = [
                'requireLogin' => (bool) ($route['require_login'] ?? false),
                'roles' => $codigos,
            ];
        }

        ksort($mapa);

        return $mapa;
    }

    /**
     * Los dos canarios del instrumento. Ningún censo reporta un cero sin probar que ve.
     *
     * @param array<string,array{requireLogin:bool,roles:array<int|string>}> $mapa
     * @return string[]
     */
    protected static function validateDeclaredPermissions(array $mapa): array
    {
        $fallos = [];

        //Canario de LAS DOS FUENTES: `users-list` no declara roles en la ruta y la abren root y
        //administrador general por las listas de roles.php. Si sale vacía, se está leyendo una sola.
        $roles = $mapa['users-list']['roles'] ?? null;
        if (!is_array($roles) || !in_array(0, $roles, true) || !in_array(1, $roles, true)) {
            $fallos[] = 'CANARIO CAÍDO: «users-list» tendría que traer 0 y 1 y trae ['
                . (is_array($roles) ? implode(', ', array_map('strval', $roles)) : 'nada')
                . ']. Se está leyendo solo lo declarado en la ruta, no las listas de roles.php.';
        }

        //Canario de COBERTURA: la matriz por HTTP recorre 204 rutas. Si esta puerta trae esa cifra,
        //está midiendo lo mismo y no cubre nada nuevo.
        if (count($mapa) <= 300) {
            $fallos[] = 'CANARIO CAÍDO: la línea base trae ' . count($mapa) . ' rutas y tendría que'
                . ' traer más de 300. Con 204 se estaría midiendo lo mismo que la matriz por HTTP.';
        }

        return $fallos;
    }

    /**
     * Escribe la línea base de permisos declarados.
     *
     * @param array<string,array{requireLogin:bool,roles:array<int|string>}> $mapa
     * @return bool `false` si no se pudo escribir: una línea base que no se escribió no se anuncia.
     */
    protected static function writeDeclaredPermissions(array $mapa): bool
    {
        $documento = [
            'medido' => [
                'fecha' => date('Y-m-d H:i'),
                'metodo' => 'bin/cli verify-integrity update-permissions=yes: get_route_roles_allowed($nombre, \'code\') sobre cada ruta de get_routes(), sin pedir ninguna URL',
                'fuentes' => 'las dos: los roles declarados en la ruta y las listas de src/app/config/roles.php',
                'rutas' => count($mapa),
            ],
            'rutas' => $mapa,
        ];
        $contenido = json_encode($documento, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
        return file_put_contents(self::declaredPermissionsPath(), is_string($contenido) ? $contenido . "\n" : '{}') !== false;
    }

    /**
     * Quién puede abrir cada ruta no ha cambiado sin declararse.
     *
     * La matriz de `bin/walk-matrix` solo recorre GET: 204 de 322 rutas. Un permiso de crear,
     * editar o borrar se podía revertir y la puerta seguía en verde, con el usuario general
     * borrando un registro real (medido en la ronda #545). Esta puerta no pide ninguna URL, porque
     * pedir un POST EJECUTA la acción: comprobar quién puede borrar pidiendo el borrado es borrar.
     *
     * NO sustituye a la matriz: la matriz demuestra que el servidor obedece lo declarado, y esta
     * solo que lo declarado no se movió. Hacen falta las dos.
     *
     * @return string[]
     */
    protected static function checkDeclaredPermissions(): array
    {
        $path = self::declaredPermissionsPath();
        $baseline = json_decode((string) @file_get_contents($path), true);
        if (!is_array($baseline) || !isset($baseline['rutas']) || !is_array($baseline['rutas'])) {
            return ['no se pudo leer ' . self::DECLARED_PERMISSIONS_RELATIVE_PATH . ': la comprobación no miró nada.'];
        }

        $ahora = self::collectDeclaredPermissions();

        $fallos = self::validateDeclaredPermissions($ahora);
        if (count($fallos) > 0) {
            return $fallos;
        }

        $etiquetas = UsersModel::getTypesUser();
        $conNombre = static function (array $codigos) use ($etiquetas): string {
            $partes = [];
            foreach ($codigos as $codigo) {
                $etiqueta = $etiquetas[$codigo] ?? null;
                $partes[] = is_string($etiqueta) ? $codigo . ' (' . $etiqueta . ')' : (string) $codigo;
            }
            return $partes === [] ? 'nadie' : implode(', ', $partes);
        };

        $sugerencia = ' Si es intencionado, regenera con: bin/cli verify-integrity update-permissions=yes';

        foreach ($baseline['rutas'] as $nombre => $antes) {
            $nombre = (string) $nombre;
            if (!isset($ahora[$nombre])) {
                $fallos[] = $nombre . ' — estaba en la línea base y YA NO EXISTE.' . $sugerencia;
                continue;
            }
            $rolesAntes = array_values((array) ($antes['roles'] ?? []));
            $rolesAhora = $ahora[$nombre]['roles'];
            if ($rolesAntes !== $rolesAhora) {
                $entran = array_values(array_diff($rolesAhora, $rolesAntes));
                $salen = array_values(array_diff($rolesAntes, $rolesAhora));
                $fallos[] = $nombre . ' — quién puede abrirla CAMBIÓ. Antes: ' . $conNombre($rolesAntes)
                    . '. Ahora: ' . $conNombre($rolesAhora)
                    . ($entran !== [] ? '. ENTRAN: ' . $conNombre($entran) : '')
                    . ($salen !== [] ? '. SALEN: ' . $conNombre($salen) : '')
                    . '.' . $sugerencia;
            }
            $loginAntes = ($antes['requireLogin'] ?? false) === true;
            if ($loginAntes !== $ahora[$nombre]['requireLogin']) {
                $fallos[] = $nombre . ' — requireLogin pasa de ' . ($loginAntes ? 'true' : 'false')
                    . ' a ' . ($ahora[$nombre]['requireLogin'] ? 'true' : 'false')
                    . ($loginAntes ? '. La ruta deja de ser pública.' : '. LA RUTA PASA A SER PÚBLICA.')
                    . $sugerencia;
            }
        }

        foreach ($ahora as $nombre => $datos) {
            if (!isset($baseline['rutas'][$nombre])) {
                $fallos[] = $nombre . ' — ruta NUEVA, sin línea base. La abren: ' . $conNombre($datos['roles'])
                    . '.' . $sugerencia;
            }
        }

        return $fallos;
    }

    /**
     * Toda plantilla nombrada en un `render()` o en un `_render()` con cadena literal existe.
     *
     * Los dos componen la ruta en tiempo de ejecución y la cargan con `require`
     * (BaseController.php:137 y :238). Una cadena mal escrita NO falla al analizar ni al arrancar:
     * lanza dentro de un `try`, acaba en `global_custom_exception_handler()` y muere. Se descubre
     * cuando un usuario visita esa ruta, y se lleva la página entera.
     *
     * Son DOS cargadores y no componen igual: `_render()` no añade extensión, porque el nombre ya
     * la trae. Y hay una TERCERA familia: diez controladores exponen un `view()` estático que
     * compone la ruta y llama al `render()` de su clase, así que la cadena real la pone quien llama
     * a `view()` y el censo la sigue en dos saltos. El censo cuenta las tres aparte por eso.
     *
     * NO lleva línea base: la puerta es «ninguna rota», no «no han crecido». Y el censo declara su
     * propia cobertura, incluido lo que NO mira, porque una llamada descartada en silencio es una
     * cadena sin vigilar.
     *
     * @return string[]
     */
    protected static function checkRenderedTemplatesExist(): array
    {
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $script = $root . '/bin/censo-plantillas';

        if (!is_file($script)) {
            //Una comprobación que no encuentra su instrumento NO reporta «todo bien». LEY 18.
            return ['no existe ' . $script . ': las plantillas de las vistas NO se han comprobado'];
        }

        $output = [];
        $status = 0;
        //RETORNO-IGNORADO: `exec()` devuelve la última línea, y aquí lo que se lee es $output entero.
        exec('cd ' . escapeshellarg($root) . ' && ' . escapeshellarg($script) . ' --json 2>/dev/null', $output, $status);

        $documento = json_decode(implode("\n", $output), true);
        if (!is_array($documento) || !isset($documento['rotas']) || !is_array($documento['medido'] ?? null)) {
            return ['el censo de plantillas no devolvió un JSON legible (código ' . $status . '): las plantillas NO se han comprobado'];
        }

        $medido = $documento['medido'];
        $resueltas = (int) ($medido['llamadas_resueltas'] ?? 0);
        if ($resueltas < 200) {
            //El canario de cobertura: con el árbol de hoy son más de 200. Si baja de golpe, el censo
            //dejó de ver, y un cero suyo no significaría nada.
            return ['el censo de plantillas solo resolvió ' . $resueltas . ' llamada(s) y con este árbol'
                . ' pasan de 200: el instrumento ha dejado de ver y la comprobación NO vale'];
        }

        $failures = [];
        foreach ($documento['rotas'] as $rota) {
            if (!is_array($rota)) {
                continue;
            }
            //`_render()` no anade extension, asi que ahi las piezas son DOS y el mensaje lo dice:
            //un mensaje que promete tres piezas manda a buscar una que no existe.
            $familia = (string) ($rota['familia'] ?? 'render');
            $cuantas = $familia === 'render' ? 'Las tres piezas' : 'Las dos piezas (este cargador NO anade extension)';
            //En una cadena de dos saltos, saber en cuál de los dos está el error es la mitad del
            //arreglo: quien llamó, por qué `view()` pasó, y dónde acabó buscando.
            $saltos = '';
            if ($familia === 'view') {
                $cuantas = 'Las piezas';
                $saltos = ' DOS SALTOS: lo llama ' . ($rota['salto1'] ?? '?')
                    . ' y pasa por el view() de ' . ($rota['salto2'] ?? '?')
                    . ', que es quien decide la carpeta y el prefijo.';
            }
            if ($familia === 'tabla') {
                $cuantas = 'Las piezas';
                $saltos = ' LA CADENA NO ESTA EN LA LLAMADA: esta escrita en ' . ($rota['salto1'] ?? '?')
                    . ', dentro de una tabla de datos, y la consume el render() de ' . ($rota['salto2'] ?? '?')
                    . '. Se corrige donde esta escrita, no donde se usa.';
            }
            //`tabla` no es un método: se nombra como lo que es, para no inventar una llamada.
            $comoSeLlama = $familia === 'tabla' ? 'la clave \'view\' de la tabla' : $familia . '()';
            $failures[] = ($rota['archivo'] ?? '?') . ':' . ($rota['linea'] ?? '?')
                . ' — ' . $comoSeLlama . ' con \'' . ($rota['cadena'] ?? '?') . '\' nombra una plantilla que NO EXISTE.'
                . ' Buscada en: ' . ($rota['resuelta'] ?? '?') . '.' . $saltos
                . ' ' . $cuantas . ': directorio «' . ($rota['directorio'] ?? '?') . '» (lo fija '
                . ($rota['directorio_lo_fija'] ?? '?') . '), prefijo «' . ($rota['prefijo'] ?? '') . '»'
                . ($rota['prefijo_lo_pone'] !== null ? ' (lo pone ' . $rota['prefijo_lo_pone'] . ')' : ' (ninguna clase lo pone)')
                . ' y ' . ($familia === 'tabla' ? 'la cadena de la tabla' : 'la cadena del propio ' . $familia . '()')
                . '. Si moviste la vista, la cadena va con ella.';
        }

        $porFamilia = [];
        foreach ((array) ($medido['por_familia'] ?? []) as $familia => $cuantas) {
            //`tabla` no es un método: es dónde está escrita la cadena, así que no lleva paréntesis.
            $porFamilia[] = ($familia === 'tabla' ? 'tabla de datos' : $familia . '()') . ' ' . (int) $cuantas;
        }
        echoTerminal("\e[94mINFO:\e[39m " . $resueltas . ' plantilla(s) nombradas con cadena literal, todas existen'
            . ($porFamilia !== [] ? ' (' . implode(' · ', $porFamilia) . ')' : '')
            . '. El censo declara lo que no mira: bin/censo-plantillas.');

        return $failures;
    }

    /**
     * Ninguna columna del esquema lleva guion bajo si no está declarada en el artefacto.
     *
     * La tabla de convenciones decía «camelCase, ocho excepciones, y la lista no crece», y creció:
     * La novena entró el 2026-09-24 con la revocación de sesiones y nadie lo notó, porque no había
     * nada que lo mirara.
     * Según se cierran los renombrados, sus entradas salen del artefacto y esta puerta se estrecha
     * sola; cuando no quede ninguna, una columna con guion bajo ya no puede volver a entrar.
     *
     * @param string|null $schemaPath Solo para provocarla con un esquema de juguete.
     * @param string|null $artifactPath Idem con otro artefacto.
     * @return string[]
     */
    protected static function checkSchemaColumnNaming(?string $schemaPath = null, ?string $artifactPath = null): array
    {
        $schemaPath ??= basepath('../databases/piecesphp_structure.sql');
        $artifactPath ??= basepath('../files/dev/column-renames.json');

        $schema = @file_get_contents($schemaPath);
        if (!is_string($schema) || $schema === '') {
            return ['no se pudo leer el esquema en ' . $schemaPath . ': sin él esta comprobación no mira nada.'];
        }
        $artifact = json_decode((string) @file_get_contents($artifactPath), true);
        if (!is_array($artifact) || !isset($artifact['entries']) || !is_array($artifact['entries'])) {
            return ['no se pudo leer ' . $artifactPath . ' o no trae «entries»: la lista de excepciones es ese archivo.'];
        }

        $declared = [];
        foreach ($artifact['entries'] as $entry) {
            if (is_array($entry) && isset($entry['column']) && is_string($entry['column'])) {
                $declared[$entry['column']] = true;
            }
        }

        //Las columnas se leen de las definiciones: una línea de CREATE TABLE que empieza por un
        //nombre entre acentos graves. Las claves e índices (KEY, CONSTRAINT) NO son columnas.
        $columns = [];
        $indexes = [];
        $inTable = false;
        foreach (preg_split('/\R/', $schema) ?: [] as $line) {
            $trimmed = trim($line);
            if (preg_match('/^CREATE TABLE/i', $trimmed) === 1) {
                $inTable = true;
                continue;
            }
            if ($inTable && preg_match('/^\)/', $trimmed) === 1) {
                $inTable = false;
                continue;
            }
            //Los nombres de ÍNDICE también van en camelCase. Los `CONSTRAINT` no: los genera MySQL.
            if ($inTable && preg_match('/^(?:UNIQUE\s+|FULLTEXT\s+|SPATIAL\s+)?(?:KEY|INDEX)\s+`([^`]+)`/i', $trimmed, $indice) === 1) {
                $indexes[$indice[1]] = true;
                continue;
            }
            if (!$inTable || preg_match('/^(PRIMARY |UNIQUE |KEY |CONSTRAINT |FOREIGN )/i', $trimmed) === 1) {
                continue;
            }
            if (preg_match('/^`([A-Za-z0-9_]+)`/', $trimmed, $match) === 1) {
                $columns[$match[1]] = true;
            }
        }

        if (count($columns) < 50) {
            //Canario de cobertura: con este esquema son más de cien. Si baja de golpe, el recorrido
            //dejó de ver y un cero suyo no significaría nada.
            return ['solo ' . count($columns) . ' columna(s) leídas del esquema y con este árbol son más de cien:'
                . ' el recorrido ha dejado de ver y la comprobación NO vale'];
        }

        $failures = [];
        $indexesWithUnderscore = [];
        foreach (array_keys($indexes) as $index) {
            if (mb_strpos($index, '_') === false) {
                continue;
            }
            $indexesWithUnderscore[] = $index;
            if (!isset($declared[$index])) {
                $failures[] = 'el índice `' . $index . '` lleva guion bajo y NO está declarado en ' . $artifactPath
                    . '. Los nombres de índice van en camelCase como las columnas; los CONSTRAINT no cuentan,'
                    . ' que los genera MySQL.';
            }
        }
        sort($indexesWithUnderscore);

        $withUnderscore = [];
        foreach (array_keys($columns) as $column) {
            if (mb_strpos($column, '_') === false) {
                continue;
            }
            $withUnderscore[] = $column;
            if (!isset($declared[$column])) {
                $failures[] = '`' . $column . '` lleva guion bajo y NO está declarada en ' . $artifactPath
                    . '. Las columnas van en camelCase (.agents/context/12-convenciones.md); si es una excepción'
                    . ' que hay que renombrar más adelante, declárala ahí con su forma nueva.';
            }
        }
        sort($withUnderscore);

        echoTerminal("\e[94mINFO:\e[39m " . count($columns) . ' nombre(s) de columna distintos y ' . count($indexes) . ' índice(s) en el esquema; '
            . count($withUnderscore) . ' nombre(s) y ' . count($indexesWithUnderscore) . ' índice(s) con guion bajo, '
            . count($declared) . ' declarada(s) en el artefacto'
            . (count($withUnderscore) === 0 && count($indexesWithUnderscore) === 0
                ? '. Ninguna lleva guion bajo HOY; las declaradas siguen admitidas por el artefacto.' : '.'));

        return $failures;
    }

    /**
     * Toda capacidad anunciada tiene la ruta o la acción de terminal que la sirve.
     *
     * Nace del 2026-09-26 y de la rama `antigravity`: por dos veces se ofreció en pantalla una función cuyo método o ruta
     * no existía, y nada lo miraba. El artefacto nombra cada capacidad, las rutas y acciones que la sirven y dónde se
     * anuncia; la puerta falla si falta una ruta, una acción o el archivo que la anuncia.
     *
     * Las acciones de terminal se miran como rutas: `TerminalController` registra cada tarea con el prefijo
     * `terminal-`, y `bin/cli` arranca como terminal. Fuera del terminal la puerta no ve las acciones, y lo dice.
     *
     * **Cinco vías de prueba**: ruta, acción, clave de configuración (`config`, del universo de
     * `bin/censo-claves-config --universo`, que es el mismo cálculo de la 45), aviso registrado (`alert`) o guion de
     * `bin/` versionado y ejecutable (`scripts`). Y desde el 2026-10-05 **cada `## Nuevo` de las secciones de la MAYOR en
     * curso del CHANGELOG lo nombra alguna capacidad** en su `changelog`: se anunciaba `mail-demo` y nadie lo había
     * declarado. Solo obliga a los `## Nuevo`. Es la mayor y no la última sección: una 8.0.1 no deja sin anunciar lo de la 8.0.0.
     *
     * @param string|null $artifactPath Solo para provocarla con otro artefacto.
     * @param string|null $changelogPath Solo para provocarla con otro CHANGELOG.
     * @return string[]
     */
    protected static function checkAnnouncedCapabilities(?string $artifactPath = null, ?string $changelogPath = null): array
    {
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $artifactPath ??= $root . '/' . self::ANNOUNCED_CAPABILITIES_RELATIVE_PATH;

        $rutas = array_keys(get_routes());
        $esTerminal = TerminalData::getInstance()->isTerminal();

        //Las claves que el código lee: del censo de la 45, tras su canario. Un solo cálculo, no dos que diverjan.
        $claves = self::configKeysUniverse($root);
        if ($claves === null) {
            return ['bin/censo-claves-config --universo no dio su lista de claves: las capacidades por clave no se pudieron comprobar'];
        }

        //Canario de dos caras por cada vía: lo que existe pasa y lo inventado falla, con el mismo validador.
        $sembrado = [
            'zz-canario-existe' => ['routes' => ['users-form-login'], 'actions' => [], 'config' => ['upload_dir'], 'alert' => ['invalid-route-pattern'], 'scripts' => ['bin/cli'], 'announced' => []],
            'zz-canario-falta' => ['routes' => ['zz-ruta-que-no-existe'], 'actions' => [], 'announced' => []],
            'zz-canario-clave' => ['config' => ['zz_clave_inventada'], 'announced' => []],
            'zz-canario-aviso' => ['alert' => ['zz-aviso-inventado'], 'announced' => []],
            'zz-canario-guion' => ['scripts' => ['bin/zz-guion-inventado'], 'announced' => []],
        ];
        $canario = self::validateAnnouncedCapabilities($sembrado, $rutas, $root, $claves);
        if (count($canario) !== 4 || !str_contains($canario[0], 'zz-ruta-que-no-existe')
            || !str_contains($canario[1], 'zz_clave_inventada') || !str_contains($canario[2], 'zz-aviso-inventado')
            || !str_contains($canario[3], 'zz-guion-inventado')) {
            return ['canario muerto: el validador no distingue lo que existe de lo inventado (ruta, clave, aviso o guion); la comprobación no miró nada'];
        }
        if (!$esTerminal) {
            return ['fuera del terminal las acciones de terminal no están registradas: esta comprobación solo vale desde bin/cli'];
        }

        $registro = json_decode((string) @file_get_contents($artifactPath), true);
        $capacidades = is_array($registro) && is_array($registro['capabilities'] ?? null) ? $registro['capabilities'] : null;
        if ($capacidades === null || count($capacidades) === 0) {
            return ['no se pudo leer ' . self::ANNOUNCED_CAPABILITIES_RELATIVE_PATH . ' o no declara ninguna capacidad: la comprobación no miró nada'];
        }

        $failures = self::validateAnnouncedCapabilities($capacidades, $rutas, $root, $claves);

        //El CHANGELOG: cada «## Nuevo» de la mayor en curso, cubierto; cada `changelog`, un encabezado que existe.
        $changelogPath ??= $root . '/CHANGELOG.md';
        $encabezados = self::currentChangelogHeadings((string) @file_get_contents($changelogPath));
        if ($encabezados['todos'] === []) {
            $failures[] = 'no se pudieron leer los encabezados de la mayor en curso de ' . basename($changelogPath) . ': la comprobación del CHANGELOG no miró nada';
        }
        $secciones = implode(', ', $encabezados['secciones']);
        $nombrados = [];
        foreach ($capacidades as $nombre => $capacidad) {
            $texto = is_array($capacidad) && is_string($capacidad['changelog'] ?? null) ? $capacidad['changelog'] : null;
            if ($texto === null) {
                continue;
            }
            $nombrados[$texto] = true;
            if ($encabezados['todos'] !== [] && !in_array($texto, $encabezados['todos'], true)) {
                $failures[] = "{$nombre}: su `changelog` nombra «{$texto}», que no es un encabezado de las secciones {$secciones} del CHANGELOG (¿se renombró?)";
            }
        }
        foreach ($encabezados['nuevo'] as $nuevo) {
            if (!isset($nombrados[$nuevo])) {
                $failures[] = "el CHANGELOG anuncia «## {$nuevo}» y ninguna capacidad de " . self::ANNOUNCED_CAPABILITIES_RELATIVE_PATH . ' lo nombra en su `changelog`: declárala con su ruta, acción, clave, aviso o guion';
            }
        }

        if (count($failures) === 0) {
            $n = ['routes' => 0, 'actions' => 0, 'config' => 0, 'alert' => 0, 'scripts' => 0];
            foreach ($capacidades as $capacidad) {
                foreach (array_keys($n) as $via) {
                    $n[$via] += count((array) ($capacidad[$via] ?? []));
                }
            }
            echoTerminal("\e[94mINFO:\e[39m capacidades anunciadas: " . count($capacidades) . " en " . self::ANNOUNCED_CAPABILITIES_RELATIVE_PATH
                . "; {$n['routes']} ruta(s), {$n['actions']} acción(es) de terminal, {$n['config']} clave(s) de configuración, {$n['alert']} aviso(s) y {$n['scripts']} guion(es),"
                . ' todos existen, y sus anuncios también. ' . count($encabezados['nuevo']) . " «## Nuevo» de las secciones {$secciones} del CHANGELOG, todos con capacidad."
                . ' Solo obliga a los «## Nuevo»: lo anunciado dentro de otros encabezados no está obligado.');
        }
        return $failures;
    }

    /**
     * Las claves de configuración que el código lee, del censo de la 45, o null si no se pudieron leer.
     *
     * @param string $root
     * @return string[]|null
     */
    protected static function configKeysUniverse(string $root): ?array
    {
        $censo = $root . '/bin/censo-claves-config';
        if (!is_file($censo)) {
            return null;
        }
        $salida = [];
        $codigo = 0;
        //RETORNO-IGNORADO: devuelve la última línea; lo que se mira es $salida entera y $codigo.
        exec(escapeshellarg($censo) . ' --universo 2>&1', $salida, $codigo);
        foreach ($codigo === 0 ? $salida : [] as $linea) {
            if (str_starts_with($linea, 'UNIVERSO-JSON ')) {
                $lista = json_decode(substr($linea, strlen('UNIVERSO-JSON ')), true);
                return is_array($lista) ? array_values(array_map('strval', $lista)) : null;
            }
        }
        return null;
    }

    /**
     * Los encabezados `##` de la MAYOR en curso del CHANGELOG —desde el primer `# X.Y.Z` hasta antes del primero de otra
     * mayor— y, aparte, los `## Nuevo` y las versiones de esas secciones. Sin el `## ` delante.
     *
     * @param string $texto
     * @return array{todos:string[],nuevo:string[],secciones:string[]}
     */
    protected static function currentChangelogHeadings(string $texto): array
    {
        $todos = [];
        $nuevo = [];
        $secciones = [];
        $mayor = null;
        foreach (preg_split('/\R/', $texto) ?: [] as $linea) {
            if (preg_match('/^# (\d+)\.(\d+)\.(\d+)/', $linea, $version) === 1) {
                $mayor ??= $version[1];
                if ($version[1] !== $mayor) {
                    break;
                }
                $secciones[] = $version[1] . '.' . $version[2] . '.' . $version[3];
                continue;
            }
            if ($mayor === null) {
                continue;
            }
            if (preg_match('/^## (.+?)\s*$/u', $linea, $m) === 1) {
                $todos[] = $m[1];
                if (str_starts_with($m[1], 'Nuevo')) {
                    $nuevo[] = $m[1];
                }
            }
        }
        return ['todos' => $todos, 'nuevo' => $nuevo, 'secciones' => $secciones];
    }

    /**
     * Nada del árbol nombra una ruta, una carpeta, una plantilla o una clase que la campaña borró o movió.
     *
     * Nace del 2026-10-01: `gulp init-project` cayó en la máquina del PO porque el gulpfile seguía nombrando una carpeta
     * movida. `bin/censo-borrados` hace el censo entero, que tarda minutos, y deja en su artefacto qué buscar de cada ruta;
     * esta puerta lo busca otra vez en segundos. La cota solo baja, y lo que nombra una ruta vieja a propósito va declarado
     * en el artefacto con su motivo. No ve lo que el censo no ve: la documentación, ni una clase por su nombre corto.
     *
     * @param string|null $artifactPath Solo para provocarla con otro artefacto.
     * @return string[]
     */
    protected static function checkCampaignRemnants(?string $artifactPath = null): array
    {
        $start = microtime(true);
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $artifactPath ??= $root . '/' . self::CAMPAIGN_REMOVALS_RELATIVE_PATH;

        //Canario de dos caras: la ruta vieja se ve, y la misma ruta con un carácter más detrás no.
        $canario = self::findCampaignRemnants([['forma' => 'ruta', 'busca' => 'src/zz-canario/borrado.php']], [
            'zz/nombra.php' => "<?php\n\$x = 'src/zz-canario/borrado.php';\n",
            'zz/otra.php' => "<?php\n\$x = 'src/zz-canario/borrado.php5';\n",
        ]);
        if ($canario !== ['zz/nombra.php:2 — ruta «src/zz-canario/borrado.php»']) {
            return ['canario muerto: el buscador no distingue una ruta vieja de otra más larga; la comprobación no miró nada'];
        }

        $registro = json_decode((string) @file_get_contents($artifactPath), true);
        $filas = is_array($registro) && is_array($registro['filas'] ?? null) ? $registro['filas'] : null;
        if ($filas === null || count($filas) === 0) {
            return ['no se pudo leer ' . self::CAMPAIGN_REMOVALS_RELATIVE_PATH . ' o no tiene filas: la comprobación no miró nada'];
        }
        $aProposito = is_array($registro['a_proposito'] ?? null) ? $registro['a_proposito'] : [];
        $buscas = [];
        foreach ($filas as $fila) {
            foreach ((array) ($fila['buscas'] ?? []) as $busca) {
                if (is_array($busca) && is_string($busca['busca'] ?? null) && is_string($busca['forma'] ?? null)) {
                    $buscas[] = $busca;
                }
            }
        }
        if (count($buscas) === 0) {
            return [self::CAMPAIGN_REMOVALS_RELATIVE_PATH . ' no dice qué buscar (`buscas`): regenéralo con bin/censo-borrados'];
        }

        $output = [];
        $status = 0;
        //`core.quotePath=false`: sin él git entrecomilla los nombres con acentos y el archivo se pierde en silencio.
        //RETORNO-IGNORADO: el resultado de git llega en $status, que se mira justo debajo.
        exec('git -C ' . escapeshellarg($root) . ' -c core.quotePath=false ls-files 2>/dev/null', $output, $status);
        if ($status !== 0) {
            return ['git ls-files falló: la comprobación no miró nada'];
        }
        $textos = [];
        foreach ($output as $relative) {
            //El mismo ámbito que bin/censo-borrados: sin documentación, sin vendor y sin el propio censo.
            if (str_ends_with($relative, '.md') || preg_match('#^(\.agents/|source-docs/|src/vendor/)#', $relative) === 1
                || in_array($relative, [self::CAMPAIGN_REMOVALS_RELATIVE_PATH, 'bin/censo-borrados', 'CHANGELOG.md'], true)
                || (preg_match('#^(src/|bin/|files/dev/|databases/)#', $relative) !== 1 && str_contains($relative, '/'))
                || !is_file($root . '/' . $relative)) {
                continue;
            }
            $texto = (string) @file_get_contents($root . '/' . $relative);
            if ($relative === self::PHPSTAN_BASELINE_RELATIVE_PATH && str_contains($texto, '================[RESUMEN]')) {
                //Su nota de medición es historia y nombra rutas viejas a propósito: cuenta solo el resumen, con sus números de línea.
                [$nota, $resumen] = explode('================[RESUMEN]', $texto, 2);
                $texto = str_repeat("\n", substr_count($nota, "\n")) . '================[RESUMEN]' . $resumen;
            }
            $textos[$relative] = $texto;
        }

        //EL CENSO RANCIO, en segundos: el entero tarda minutos y se corre antes de etiquetar, pero lo que se queda
        //atrás cada día —un archivo añadido o quitado— se ve contando su universo contra el de hoy.
        $rancio = self::campaignCensusStaleness($registro, array_keys($textos), $root);

        $reales = [];
        $declarados = 0;
        foreach (self::findCampaignRemnants($buscas, $textos) as $resto) {
            $archivo = explode(':', $resto, 2)[0];
            $aguja = preg_match('/«(.*)»$/u', $resto, $m) === 1 ? $m[1] : '';
            //Por archivo Y por texto: otra ruta vieja en un archivo declarado sigue siendo un resto.
            if (in_array($aguja, (array) ($aProposito[$archivo]['busca'] ?? []), true)) {
                $declarados++;
            } else {
                $reales[] = $resto;
            }
        }

        $failures = $rancio;
        if (count($reales) > self::CAMPAIGN_REMNANTS_DECLARED) {
            foreach ($reales as $resto) {
                $failures[] = $resto . ': nombra una ruta que la campaña borró o movió. Cámbiala por la de hoy (files/dev/campaign-removals.json dice cuál)'
                    . ' o, si es a propósito, decláralo con su motivo en bin/censo-borrados.';
            }
        }
        if (count($failures) === 0) {
            echoTerminal("\e[94mINFO:\e[39m restos de la campaña: " . count($buscas) . ' texto(s) de ' . count($filas) . ' ruta(s) borradas o movidas, buscados en '
                . count($textos) . ' archivo(s) en ' . number_format(microtime(true) - $start, 1) . ' s: ' . count($reales) . ' resto(s) real(es), igual que la cota; '
                . $declarados . ' a propósito, declarados con su motivo. No mira la documentación ni una clase por su nombre corto.');
            echoTerminal("\e[94mINFO:\e[39m censo de borrados: al día con este árbol (" . count($textos) . ' archivo(s), los mismos que conoce su artefacto).'
                . ' Lo que NO ve: una fila nueva de borrado o movido, que solo da el censo entero —bin/censo-borrados --check, antes de etiquetar—.');
        }
        return $failures;
    }

    /**
     * Si el artefacto del censo de borrados se quedó atrás: su universo contra el de hoy. Si difieren, nombra los
     * archivos añadidos o quitados desde el commit del artefacto, del mismo ámbito, para que se vea qué lo dejó rancio.
     *
     * @param array<mixed> $registro El artefacto ya leído.
     * @param string[] $universo Los archivos que la comprobación recorre hoy.
     * @param string $root
     * @return string[]
     */
    protected static function campaignCensusStaleness(array $registro, array $universo, string $root): array
    {
        $conocidos = is_int($registro['archivos_en_el_censo'] ?? null) ? $registro['archivos_en_el_censo'] : null;
        if ($conocidos === null) {
            return [self::CAMPAIGN_REMOVALS_RELATIVE_PATH . ' no dice cuántos archivos censó (`archivos_en_el_censo`): regenéralo con bin/censo-borrados'];
        }
        if ($conocidos === count($universo)) {
            return [];
        }
        $cambiados = [];
        $commit = is_string($registro['commit'] ?? null) ? $registro['commit'] : '';
        if (preg_match('/^[0-9a-f]{7,40}$/', $commit) === 1) {
            $salida = [];
            $codigo = 0;
            //RETORNO-IGNORADO: el resultado de git llega en $codigo; si falla, se dice la cifra sin los nombres.
            exec('git -C ' . escapeshellarg($root) . ' -c core.quotePath=false diff --name-status --diff-filter=AD ' . escapeshellarg($commit) . ' 2>/dev/null', $salida, $codigo);
            $enUniverso = array_flip($universo);
            foreach ($codigo === 0 ? $salida : [] as $linea) {
                [$estado, $ruta] = array_pad(explode("\t", $linea, 2), 2, '');
                if (($estado === 'A' && isset($enUniverso[$ruta])) || ($estado === 'D' && preg_match('#^(src/(?!vendor/)|bin/|files/dev/|databases/)#', $ruta) === 1 && !str_ends_with($ruta, '.md'))) {
                    $cambiados[] = ($estado === 'A' ? 'nuevo ' : 'quitado ') . $ruta;
                }
            }
        }
        return ['el censo de borrados está RANCIO: su artefacto conoce ' . $conocidos . ' archivo(s) y hoy hay ' . count($universo)
            . ($cambiados !== [] ? ' (' . implode(', ', $cambiados) . ')' : '') . '. Regenéralo con bin/censo-borrados y commitéalo.'];
    }

    /**
     * Ninguna ruta declara un alias: el mecanismo se retiró en el bloque CX (P92).
     *
     * El sexto argumento de `Route` sigue existiendo —son 221 declaraciones posicionales— pero no
     * hace nada, así que declararlo ahora es escribir código que nadie honra. Cota 0.
     *
     * @return string[]
     */
    protected static function checkNoRouteAlias(): array
    {
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));

        //CANARIO de dos caras: la forma que se busca tiene que verse, y la inerte no.
        if (self::routeAliasProblem('        $this->alias = null;') !== null
            || self::routeAliasProblem("        \$this->alias = \$alias;") === null) {
            return ['el canario de la comprobación de alias no distingue un alias declarado de `null`: no se miró nada'];
        }

        $output = [];
        $status = 0;
        //RETORNO-IGNORADO: el resultado de la orden se mira en `$status`, no en lo que devuelve exec().
        exec('git -C ' . escapeshellarg($root) . ' -c core.quotePath=false ls-files -- ' . escapeshellarg('src/app') . ' 2>/dev/null', $output, $status);
        if ($status !== 0) {
            return ['no se pudo listar src/app con git: la comprobación de alias no miró nada'];
        }

        $failures = [];
        $revisados = 0;
        foreach ($output as $relative) {
            if (!str_ends_with($relative, '.php')) {
                continue;
            }
            $contents = (string) @file_get_contents($root . '/' . $relative);
            if ($contents === '') {
                continue;
            }
            if (!str_contains($contents, 'alias')) {
                continue;
            }
            //Este archivo queda fuera: lleva las formas del canario como texto, y se detectaría a sí mismo.
            if (str_ends_with($relative, 'Terminal/Tasks/VerifyIntegrityTask.php')) {
                continue;
            }
            $revisados++;
            foreach (explode("\n", $contents) as $numero => $linea) {
                $problema = self::routeAliasProblem($linea);
                if ($problema !== null) {
                    $failures[] = "{$relative}:" . ($numero + 1) . " — {$problema}";
                }
            }
        }

        if (count($failures) === 0) {
            echoTerminal("\e[94mINFO:\e[39m alias de ruta: 0 declarados en {$revisados} archivo(s) que nombran «alias»."
                . ' El mecanismo se retiró en el bloque CX; el sexto argumento de Route queda ignorado.');
        }

        return $failures;
    }

    /**
     * 47. La distribución pública lleva lo decidido y nada de la campaña (ADR 0049 §7, ADR 0050).
     *
     * Cuatro partes. Tres miran el PLAN: que `bin/distribution-exclude.txt` no nombre nada del universo de
     * `.agents/capas.json` (dos autoridades sobre una ruta es la trampa del §2), que `capas.py` pase y que cada ruta de
     * los dos mapas de «no aplica» exista aquí (la 4, que corre antes de la 3 porque la 3 puede salir pronto). **La 3
     * mira el RESULTADO, y es la que vale**: genera la distribución de la última etiqueta en un temporal propio, la
     * recorre y lo borra al terminar, también si falla.
     *
     * @return string[]
     */
    protected static function checkDistribution(): array
    {
        $failures = [];
        $root = Distribution::root();

        //──── 1. Las dos autoridades no se solapan ─────────────────────────────────────
        $manifest = json_decode((string) @file_get_contents($root . '/.agents/capas.json'), true);
        $exclude = @file($root . '/bin/distribution-exclude.txt', \FILE_IGNORE_NEW_LINES);
        if (!is_array($manifest) || !is_array($manifest['universo'] ?? null) || !is_array($exclude)) {
            return ['no se pudo leer .agents/capas.json o bin/distribution-exclude.txt: la distribución NO se comprobó.'];
        }
        $universe = array_map('strval', $manifest['universo']);
        $patterns = 0;
        foreach ($exclude as $line) {
            $line = trim((string) $line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $patterns++;
            $bareLine = rtrim($line, '/');
            foreach ($universe as $entry) {
                $bareEntry = rtrim($entry, '/');
                if ($bareLine === $bareEntry || str_starts_with($bareLine, $bareEntry . '/') || str_starts_with($bareEntry, $bareLine . '/') || fnmatch($line, $bareEntry)) {
                    $failures[] = "bin/distribution-exclude.txt nombra «{$line}», del universo de .agents/capas.json («{$entry}»):"
                        . ' dos autoridades sobre la misma ruta (ADR 0049 §2). Lo del andamiaje lo decide capas.json.';
                }
            }
        }

        //──── 2. Nada del universo se queda sin capa ───────────────────────────────────
        $capas = [];
        $capasStatus = 0;
        //RETORNO-IGNORADO: `exec()` devuelve la última línea, y aquí lo que decide es $status.
        exec('cd ' . escapeshellarg($root) . ' && python3 -B .agents/scripts/capas.py 2>&1', $capas, $capasStatus);
        if ($capasStatus !== 0) {
            $failures[] = '.agents/scripts/capas.py falla (salida ' . $capasStatus . '): ' . implode(' | ', array_slice($capas, 0, 4));
        }

        //──── 4. Los dos mapas de «no aplica» nombran lo que existe aquí ───────────────
        $maps = [
            'VerifyIntegrityTask::DISTRIBUTION_REQUIREMENTS' => self::DISTRIBUTION_REQUIREMENTS,
            'GatesTask::SUITE_DISTRIBUTION_REQUIREMENTS' => GatesTask::SUITE_DISTRIBUTION_REQUIREMENTS,
        ];
        $mapPaths = 0;
        foreach ($maps as $mapName => $map) {
            foreach ($map as $key => $paths) {
                foreach (Distribution::missing($paths, $root) as $relative) {
                    $failures[] = "{$mapName}[{$key}] nombra {$relative}, que no existe aquí: el mapa quedó rancio, y en el clon esa"
                        . ' comprobación fallaría en vez de decir «no aplica».';
                }
                $mapPaths += count($paths);
            }
        }

        //──── 3. La distribución generada cumple lo decidido ───────────────────────────
        $tag = self::latestVersionTag($root);
        if ($tag === null) {
            $failures[] = 'ninguna etiqueta con forma de versión alcanzable desde HEAD: la distribución NO se generó.';
            return $failures;
        }
        $dest = rtrim(sys_get_temp_dir(), '/') . '/piecesphp-integridad-47-' . getmypid();
        $started = microtime(true);
        try {
            self::removeTree($dest);
            $generation = [];
            $generationStatus = 0;
            //RETORNO-IGNORADO: `exec()` devuelve la última línea, y aquí lo que decide es $status.
            exec(escapeshellarg($root . '/bin/make-distribution') . ' ' . escapeshellarg($tag) . ' ' . escapeshellarg($dest) . ' 2>&1', $generation, $generationStatus);
            $byAuthority = array_values(array_filter($generation, fn (string $l): bool => str_starts_with($l, 'Borrados por autoridad:')));
            if ($generationStatus !== 0) {
                $failures[] = "bin/make-distribution {$tag} falló (salida {$generationStatus}): " . (string) end($generation);
            } else {
                $failures = array_merge($failures, self::distributionProblems($root, $tag, $dest, $manifest, $byAuthority[0] ?? 'sin línea de borrados', $started));
            }
        } finally {
            self::removeTree($dest);
            if (file_exists($dest)) {
                $failures[] = "no se pudo borrar el temporal {$dest}: bórralo a mano.";
            }
        }

        if (count($failures) === 0) {
            echoTerminal("\e[94mINFO:\e[39m distribución: {$patterns} patrón(es) en bin/distribution-exclude.txt, ninguno del universo de capas; "
                . trim((string) ($capas[0] ?? '')) . "; {$mapPaths} ruta(s) en los dos mapas de «no aplica», todas existen aquí.");
        }
        return $failures;
    }

    /**
     * Lo que la distribución generada en `$dest` lleva y no debería, o le falta.
     *
     * @param string $root
     * @param string $tag
     * @param string $dest
     * @param array<mixed> $currentManifest
     * @param string $byAuthority La línea de `make-distribution` con lo borrado por cada autoridad.
     * @param float $started
     * @return string[]
     */
    protected static function distributionProblems(string $root, string $tag, string $dest, array $currentManifest, string $byAuthority, float $started): array
    {
        $failures = [];
        //Con -z: sin él git entrecomilla las rutas con tildes, y nueve archivos de la capa C no se veían.
        $listed = self::nulSeparated('git -C ' . escapeshellarg($dest) . ' ls-files -z');
        $files = array_flip($listed);
        $tagFiles = self::nulSeparated('git -C ' . escapeshellarg($root) . ' ls-tree -r -z --name-only ' . escapeshellarg('refs/tags/' . $tag));
        $tagManifestRaw = [];
        //RETORNO-IGNORADO: `exec()` devuelve la última línea; aquí se lee la salida entera, y sin ella el json_decode falla.
        exec('git -C ' . escapeshellarg($root) . ' show ' . escapeshellarg('refs/tags/' . $tag . ':.agents/capas.json') . ' 2>/dev/null', $tagManifestRaw);
        $manifest = json_decode(implode("\n", $tagManifestRaw), true);
        if (count($listed) === 0 || count($tagFiles) === 0 || !is_array($manifest) || !is_array($manifest['capas'] ?? null)) {
            return ["la distribución de {$tag} no se pudo recorrer: la comprobación no miró nada."];
        }

        //La capa de cada archivo del universo, calculada aquí y no leída del guion que la borró.
        $universe = array_map('strval', (array) ($manifest['universo'] ?? $currentManifest['universo']));
        $layers = ['A' => [], 'B' => [], 'C' => []];
        foreach ($tagFiles as $file) {
            $inUniverse = false;
            foreach ($universe as $entry) {
                $inUniverse = $inUniverse || $file === $entry || (str_ends_with($entry, '/') && str_starts_with($file, $entry));
            }
            if (!$inUniverse) {
                continue;
            }
            foreach ($manifest['capas'] as $layer => $definition) {
                foreach ((array) ($definition['patrones'] ?? []) as $pattern) {
                    if (isset($layers[$layer]) && fnmatch((string) $pattern, $file)) {
                        $layers[$layer][$file] = true;
                        continue 3;
                    }
                }
            }
        }
        $travelledC = array_keys(array_intersect_key($layers['C'], $files));
        if (count($layers['C']) === 0) {
            $failures[] = "la etiqueta {$tag} no tiene ningún archivo de la capa C: el canario no puede decir si se borra.";
        }
        foreach (array_slice($travelledC, 0, 8) as $file) {
            $failures[] = "viaja un archivo de la capa C, que se queda (ADR 0049 §3): {$file}";
        }
        if (count($travelledC) > 8) {
            $failures[] = '… y ' . (count($travelledC) - 8) . ' más de la capa C.';
        }
        foreach (['A', 'B'] as $layer) {
            if (count(array_intersect_key($layers[$layer], $files)) === 0) {
                $failures[] = "no viaja ningún archivo de la capa {$layer}, que viaja (ADR 0049 §3).";
            }
        }
        foreach (['AGENTS.md', 'CLAUDE.md'] as $entry) {
            if (!isset($files[$entry])) {
                $failures[] = "falta {$entry}: es el punto de entrada de las capas que viajan (ADR 0049 §3).";
            }
        }

        $secureKeys = array_values(array_filter($listed, fn (string $f): bool => str_starts_with($f, self::SECURE_KEYS_DIR)));
        sort($secureKeys);
        $expected = self::SECURE_KEYS_VERSIONED;
        sort($expected);
        if ($secureKeys !== $expected) {
            $failures[] = self::SECURE_KEYS_DIR . ' tiene que llevar exactamente ' . implode(', ', $expected) . ' (ADR 0050), y lleva: '
                . (count($secureKeys) > 0 ? implode(', ', $secureKeys) : 'nada') . '. Algo de más puede ser una clave versionada por error.';
        }

        $extensions = array_values(array_filter($listed, fn (string $f): bool => str_starts_with($f, 'src/' . self::PHPSTAN_EXTENSIONS_DIR)));
        if (count($extensions) > 0) {
            $failures[] = 'viajan ' . count($extensions) . ' extensión(es) de PHPStan de src/' . self::PHPSTAN_EXTENSIONS_DIR
                . ', que dependen de bin/tools/vendor y se quedan: ' . implode(', ', array_slice($extensions, 0, 3));
        }

        $analysis = array_values(array_filter($listed, fn (string $f): bool => str_starts_with($f, 'PHPStanResult')));
        if (count($analysis) > 0) {
            $failures[] = 'viajan artefactos de PHPStan, que se quedan (ADR 0049 §5): ' . implode(', ', $analysis);
        }

        $bin = array_values(array_filter($listed, fn (string $f): bool => str_starts_with($f, 'bin/')));
        sort($bin);
        $binExpected = self::BIN_TRAVELS;
        sort($binExpected);
        foreach (array_diff($bin, $binExpected) as $file) {
            $failures[] = "viaja {$file}, que no es de los " . count($binExpected) . ' de bin/ que viajan (ADR 0049 §6). Si viaja, va en BIN_TRAVELS; si no, en bin/distribution-exclude.txt.';
        }
        foreach (array_diff($binExpected, $bin) as $file) {
            $failures[] = "no viaja {$file}, que es de los de bin/ que viajan (ADR 0049 §6).";
        }

        $mark = (string) @file_get_contents($dest . '/' . Distribution::MARK_FILE);
        if (!isset($files[Distribution::MARK_FILE]) || mb_strpos($mark, 'etiqueta: ' . $tag . "\n") !== 0) {
            $failures[] = 'la distribución no lleva su marca ' . Distribution::MARK_FILE . " con «etiqueta: {$tag}»: un clon no sabría que lo es.";
        }

        if (count($failures) === 0) {
            echoTerminal("\e[94mINFO:\e[39m distribución de {$tag} generada y recorrida en " . number_format(microtime(true) - $started, 1) . ' s: '
                . count($listed) . ' archivo(s); capa C 0 de ' . count($layers['C']) . ', A ' . count(array_intersect_key($layers['A'], $files))
                . ', B ' . count(array_intersect_key($layers['B'], $files)) . '; ' . count($secureKeys) . ' en ' . self::SECURE_KEYS_DIR
                . ', 0 de PHPStan, 0 extensiones de PHPStan, ' . count($bin) . ' de bin/; con su marca. ' . $byAuthority . '.');
        }
        return $failures;
    }

    /**
     * La salida de una orden con `-z`, partida por NUL.
     *
     * @param string $command
     * @return string[]
     */
    protected static function nulSeparated(string $command): array
    {
        $output = shell_exec($command . ' 2>/dev/null');
        return is_string($output) ? array_values(array_filter(explode("\0", $output), fn (string $f): bool => $f !== '')) : [];
    }

    /**
     * La etiqueta con forma de versión más alta alcanzable desde HEAD, pre-versiones incluidas.
     *
     * @param string $root
     * @return string|null
     */
    protected static function latestVersionTag(string $root): ?string
    {
        $tags = [];
        //RETORNO-IGNORADO: `exec()` devuelve la última línea; aquí se lee la lista entera, y vacía da null.
        exec('git -C ' . escapeshellarg($root) . ' tag --merged HEAD -l ' . escapeshellarg('v*') . ' 2>/dev/null', $tags);
        $latest = null;
        foreach ($tags as $tag) {
            if (preg_match('/^v\d+\.\d+\.\d+(-(alpha|beta|rc)\.\d+)?$/', $tag) !== 1) {
                continue;
            }
            if ($latest === null || version_compare(ltrim($tag, 'v'), ltrim($latest, 'v'), '>')) {
                $latest = $tag;
            }
        }
        return $latest;
    }

    /**
     * Borra un árbol del temporal sin seguir enlaces. Solo lo usa la comprobación 47, sobre su propio temporal.
     *
     * @param string $path
     * @return void
     */
    protected static function removeTree(string $path): void
    {
        if (!str_starts_with($path, rtrim(sys_get_temp_dir(), '/') . '/piecesphp-integridad-47-') || !file_exists($path)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $entry) {
            /** @var \SplFileInfo $entry */
            if ($entry->isDir() && !$entry->isLink()) {
                //RETORNO-IGNORADO: lo que no se borre lo dice quien llama, que mira si el temporal sigue ahí.
                @rmdir($entry->getPathname());
            } else {
                //RETORNO-IGNORADO: lo que no se borre lo dice quien llama, que mira si el temporal sigue ahí.
                @unlink($entry->getPathname());
            }
        }
        //RETORNO-IGNORADO: quien llama comprueba con file_exists() que el temporal ya no está.
        @rmdir($path);
    }

    /**
     * 46. El número de registro de los listados ni se recorta ni sirve para ordenar (P95).
     *
     * `LPAD(id, 5, 0)` **recorta en silencio**: desde el 100.000, el 123456 salía como `12345`, que es el
     * número de OTRO registro. Y ordenar por `idPadding` es ordenar texto y sin índice. Lo que se pinta lleva
     * `GREATEST(5, CHAR_LENGTH(id))`; lo que ordena es el `id` real. Cota 0. Los comentarios no cuentan:
     * no ordenan nada.
     *
     * @return string[]
     */
    protected static function checkRegisterNumberPadding(): array
    {
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));

        //CANARIO de dos caras: las formas defectuosas se ven, y las buenas no.
        $canario = self::registerNumberProblem('"LPAD({$table}.id, 5, 0) AS idPadding",') !== null
            && self::registerNumberProblem("            'idPadding',") !== null
            && self::registerNumberProblem("            'idPadding' => 'DESC',") !== null
            && self::registerNumberProblem("        '`idPadding` DESC',") !== null
            && self::registerNumberProblem('"LPAD({$table}.id, GREATEST(5, CHAR_LENGTH({$table}.id)), \'0\') AS idPadding",') === null
            && self::registerNumberProblem('                $columns[] = $e->idPadding;') === null
            && self::registerNumberProblem("        //Por el id REAL: `idPadding` es una cadena.") === null;
        if (!$canario) {
            return ['el canario del número de registro no distingue la forma que recorta u ordena de la buena: no se miró nada'];
        }

        $output = [];
        $status = 0;
        //RETORNO-IGNORADO: el resultado de la orden se mira en `$status`, no en lo que devuelve exec().
        exec('git -C ' . escapeshellarg($root) . ' -c core.quotePath=false ls-files -- ' . escapeshellarg('src/app') . ' 2>/dev/null', $output, $status);
        if ($status !== 0) {
            return ['no se pudo listar src/app con git: la comprobación del número de registro no miró nada'];
        }

        $failures = [];
        $revisados = 0;
        $expresiones = 0;
        foreach ($output as $relative) {
            //Fuera, los dos que llevan las formas del defecto como texto: este, por su canario, y la suite que
            //prueba la expresión, que evalúa la vieja para ver que recorta. Ninguno pinta ni ordena un listado.
            if (!str_ends_with($relative, '.php') || str_ends_with($relative, 'Terminal/Tasks/VerifyIntegrityTask.php')
                || str_ends_with($relative, 'local-tests/UnitTest-RegisterNumber.php')) {
                continue;
            }
            $contents = (string) @file_get_contents($root . '/' . $relative);
            if (!str_contains($contents, 'LPAD') && !str_contains($contents, 'idPadding')) {
                continue;
            }
            $revisados++;
            $expresiones += substr_count($contents, 'LPAD(');
            foreach (explode("\n", $contents) as $numero => $linea) {
                $problema = self::registerNumberProblem($linea);
                if ($problema !== null) {
                    $failures[] = "{$relative}:" . ($numero + 1) . " — {$problema}";
                }
            }
        }

        if (count($failures) === 0) {
            echoTerminal("\e[94mINFO:\e[39m número de registro: {$expresiones} LPAD( en {$revisados} archivo(s) de src/app que nombran LPAD o idPadding;"
                . ' ninguno recorta y ninguno ordena por idPadding. Excluidos 2 por llevar el defecto como texto (este y'
                . ' UnitTest-RegisterNumber). No ve un LPAD escrito con otra forma, ni un archivo sin seguimiento en git, ni un orden armado en tiempo de ejecución.');
        }

        return $failures;
    }

    /**
     * @param string $linea
     * @return string|null
     */
    protected static function registerNumberProblem(string $linea): ?string
    {
        $codigo = ltrim($linea);
        if (str_starts_with($codigo, '//') || str_starts_with($codigo, '*') || str_starts_with($codigo, '/*')) {
            return null;
        }
        if (preg_match('/LPAD\(\s*[^,()]+,\s*\d+\s*,/i', $codigo) === 1) {
            return 'LPAD con un ancho fijo recorta en silencio desde el 100.000: use GREATEST(5, CHAR_LENGTH(id))';
        }
        if (preg_match('/[\'"`]idPadding[\'"`]/', $codigo) === 1) {
            return 'idPadding como columna de orden: ordena texto y sin índice; ordene por el id real, calificado con su tabla';
        }
        return null;
    }

    /**
     * 44. La documentación no manda al lector a archivos que no existen.
     *
     * Delega en `bin/censo-rutas-doc`, que es quien sabe de bases y exenciones, y aquí solo se
     * traduce su veredicto. **Una guía de instalación que nombra archivos inexistentes es la
     * trampa embarcada en su forma más pura**: el clon nuevo la sigue. Y `source-docs/` viaja al
     * repositorio público (ADR 0049 §8), así que esto es lo que el lector de fuera va a leer.
     *
     * Cota 0: ninguna ruta no existente y no eximida.
     *
     * @return string[]
     */
    protected static function checkDocumentedPaths(): array
    {
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $censo = $root . '/bin/censo-rutas-doc';
        if (!is_file($censo)) {
            return ['no se encontró bin/censo-rutas-doc: la comprobación de las rutas de la documentación no miró nada'];
        }

        $salida = [];
        $codigo = 0;
        //`2>&1` para que un canario caído del censo llegue aquí en vez de perderse.
        //RETORNO-IGNORADO: devuelve la última línea; lo que se mira es $salida entera y $codigo.
        exec(escapeshellarg($censo) . ' 2>&1', $salida, $codigo);
        $texto = implode("\n", $salida);

        //El canario del propio censo tiene que haber corrido: si no está su línea, no midió nada.
        if (!str_contains($texto, 'canario:')) {
            return ['bin/censo-rutas-doc no emitió su línea de canario: no se puede dar por bueno lo que dice'];
        }

        if ($codigo === 0) {
            foreach ($salida as $linea) {
                if (str_starts_with(trim($linea), 'documentos leidos')
                    || str_starts_with(trim($linea), 'rutas comprobables')
                    || str_starts_with(trim($linea), 'eximidas por declaracion')
                    || str_starts_with(trim($linea), 'declaradas sin aparecer')) {
                    echoTerminal("\e[94mINFO:\e[39m documentación: " . trim($linea));
                }
            }
            echoTerminal("\e[94mINFO:\e[39m documentación: 0 ruta(s) inexistentes en source-docs/. Comprueba que el archivo EXISTA,"
                . ' no que sea el que el texto quiere decir. Exenciones declaradas en files/dev/doc-path-exemptions.json.');
            return [];
        }

        //Un fallo que no dice dónde está la mentira no sirve: se reconstruyen los pares
        //ruta -> documento de la cola de la salida del censo.
        $fallos = [];
        $rutaActual = '';
        foreach ($salida as $linea) {
            if (preg_match('/^  (\S.*)$/', $linea, $m) === 1 && !str_contains($linea, '(fuera de alcance)') && !str_contains($linea, '(eximida/')) {
                $rutaActual = trim($m[1]);
                continue;
            }
            if (preg_match('/^      <- (.+)$/', $linea, $m) === 1 && $rutaActual !== '') {
                $fallos[] = "«{$rutaActual}» no existe y la nombra " . trim($m[1])
                    . '. Si es un marcador, un endpoint, una ruta elidida o un generado, declárala en files/dev/doc-path-exemptions.json con su motivo.';
            }
        }
        if ($fallos === []) {
            $fallos[] = 'bin/censo-rutas-doc salió con código ' . $codigo . ' y no se pudo leer qué ruta falló. Córrelo a mano: bin/censo-rutas-doc';
        }
        return $fallos;
    }

    /**
     * 45. La documentación no manda al lector a claves de configuración que no existen.
     *
     * Nace de la única mentira medida el 2026-10-03: `structure.md` mandaba configurar el SMTP
     * escribiendo una clave «mailing_settings» —con la contraseña en claro— que no se lee en ningún
     * sitio. **La comprobación 44 no podía verla**: su patrón exige una barra y «mailing.php» no la
     * tiene. Una clase de afirmación sin puerta es una clase de afirmación que miente.
     *
     * Y por eso este docblock NO escribe la llamada en su forma literal: el censo la buscaría aquí
     * y daría la clave por existente, tapando lo que esta puerta viene a cazar.
     *
     * Cota 0: ninguna clave citada que no exista y no esté eximida.
     *
     * @return string[]
     */
    protected static function checkDocumentedConfigKeys(): array
    {
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $censo = $root . '/bin/censo-claves-config';
        if (!is_file($censo)) {
            return ['no se encontró bin/censo-claves-config: la comprobación de las claves de la documentación no miró nada'];
        }

        $salida = [];
        $codigo = 0;
        //RETORNO-IGNORADO: devuelve la última línea; lo que se mira es $salida entera y $codigo.
        exec(escapeshellarg($censo) . ' 2>&1', $salida, $codigo);
        $texto = implode("\n", $salida);

        if (!str_contains($texto, 'canario:')) {
            return ['bin/censo-claves-config no emitió su línea de canario: no se puede dar por bueno lo que dice'];
        }

        if ($codigo === 0) {
            foreach ($salida as $linea) {
                $limpia = trim($linea);
                if (str_starts_with($limpia, 'universo del codigo')
                    || str_starts_with($limpia, 'NO resolubles')
                    || str_starts_with($limpia, 'claves que cita la doc')
                    || str_starts_with($limpia, 'eximidas')) {
                    echoTerminal("\e[94mINFO:\e[39m claves de configuración: {$limpia}");
                }
            }
            echoTerminal("\e[94mINFO:\e[39m claves de configuración: 0 clave(s) inexistentes en source-docs/."
                . ' El universo del código es una cota INFERIOR: las llamadas cuyo argumento es una variable no se resuelven.');
            return [];
        }

        $fallos = [];
        $claveActual = '';
        foreach ($salida as $linea) {
            if (preg_match('/^  (\S.*)$/', $linea, $m) === 1 && !str_contains($linea, '(eximida/')) {
                $claveActual = trim($m[1]);
                continue;
            }
            if (preg_match('/^      <- (.+)$/', $linea, $m) === 1 && $claveActual !== '') {
                $fallos[] = "la clave «{$claveActual}» no existe en el código y la nombra " . trim($m[1])
                    . '. Si es un ejemplo de un módulo inventado, declárala en files/dev/doc-config-exemptions.json con su motivo.';
            }
        }
        if ($fallos === []) {
            $fallos[] = 'bin/censo-claves-config salió con código ' . $codigo . ' y no se pudo leer qué clave falló. Córrelo a mano: bin/censo-claves-config';
        }
        return $fallos;
    }

    /**
     * 43. Quién decide si el correo sale. Desde el ADR 0043 §1 lo decide `MailDelivery` y nadie
     * mas: antes estaba repartido entre `test_mode` y `is_local()`, y la ausencia del archivo de
     * entorno acababa enviando de verdad. Cota 0.
     *
     * Mira el CUERPO de `testModeActive()`, no cada linea del archivo: el getter `testMode()`
     * devuelve `$this->testMode` de forma legitima y una busqueda por linea lo caza como si fuera
     * la decision. Medido el 2026-10-03: ese falso positivo salio en la primera version.
     *
     * @return string[]
     */
    protected static function checkMailDeliveryDecision(): array
    {
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $relative = 'src/app/core/psr4/PiecesPHP/Core/ConfigHelpers/MailConfig.php';
        $contents = (string) @file_get_contents($root . '/' . $relative);
        if ($contents === '') {
            return ["no se pudo leer {$relative}: la comprobacion de la entrega del correo no miro nada"];
        }

        //CANARIO de dos caras: la decision vieja tiene que verse como problema, y la nueva no.
        $vieja = '    public function testModeActive(): bool' . "\n" . '    {' . "\n"
            . '        if ($this->testMode === self::TEST_MODE_ON) {' . "\n"
            . '            return true;' . "\n"
            . '        }' . "\n"
            . '        return function_exists(\'is_local\') && is_local();' . "\n"
            . '    }';
        $nueva = '    public function testModeActive(): bool' . "\n" . '    {' . "\n"
            . '        return MailDelivery::goesToSink();' . "\n"
            . '    }';
        if (self::mailDecisionProblem($vieja) === null || self::mailDecisionProblem($nueva) !== null) {
            return ['el canario de la comprobacion de la entrega del correo no distingue la decision vieja de la nueva: no se miro nada'];
        }

        $problema = self::mailDecisionProblem($contents);
        if ($problema !== null) {
            return ["{$relative} — {$problema}"];
        }

        echoTerminal("\e[94mINFO:\e[39m entrega del correo: la decide MailDelivery y nadie mas."
            . ' Se mira el cuerpo de testModeActive(), no el archivo: el getter testMode() devuelve su valor de forma legitima.');

        return [];
    }

    /**
     * Qué tiene de malo el código que decide si el correo sale, o null si no tiene nada.
     *
     * @param string $codigo Un archivo completo o el cuerpo de un metodo.
     * @return string|null
     */
    protected static function mailDecisionProblem(string $codigo): ?string
    {
        if (preg_match('/function\s+testModeActive\s*\([^)]*\)\s*:\s*bool\s*\{(.*?)\n    \}/s', $codigo, $matches) !== 1) {
            return 'no se encontro el cuerpo de testModeActive(): si se renombro, esta comprobacion dejo de mirar lo que dice mirar';
        }
        $cuerpo = $matches[1];
        if (preg_match('/\bis_local\s*\(/', $cuerpo) === 1) {
            return 'la decision de la entrega vuelve a leer is_local(): la decide MailDelivery (ADR 0043 §1)';
        }
        if (preg_match('/\$this->testMode\b/', $cuerpo) === 1) {
            return 'la decision de la entrega vuelve a leer test_mode: la decide MailDelivery (ADR 0043 §1)';
        }
        if (!str_contains($cuerpo, 'MailDelivery::goesToSink()')) {
            return 'la decision de la entrega ya no pasa por MailDelivery::goesToSink(): vuelve a estar repartida';
        }
        return null;
    }

    /**
     * Qué tiene de malo una línea que asigna un alias de ruta, o null si no tiene nada.
     *
     * @param string $linea
     * @return string|null
     */
    protected static function routeAliasProblem(string $linea): ?string
    {
        if (preg_match('/->alias\s*=\s*(.+?);/', $linea, $m) !== 1) {
            return null;
        }
        $valor = trim($m[1]);
        if ($valor === 'null') {
            return null;
        }
        return "declara un alias de ruta («{$valor}»), y el mecanismo se retiró: no se registra ninguna segunda ruta";
    }

    /**
     * Cada archivo de configuración dice si es del framework, del clon o de los dos, y qué conviene editar en él.
     *
     * Nace del bloque CO (P91): el PO mezclaba lo nuclear con lo del clon y no sabía qué podía tocar. La cabecera va en el
     * primer docblock del archivo; un «ambos» lleva además las dos marcas de sección, sin mover código.
     *
     * @return string[]
     */
    protected static function checkConfigOwnership(): array
    {
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));

        //Canario de dos caras: una cabecera completa pasa y la misma sin su valor válido falla.
        $buena = "<?php\n\n/**\n * @pcsphp-config clon\n * Qué conviene editar aquí: todo.\n */\n\$x = 1;\n";
        $mala = "<?php\n\n/**\n * @pcsphp-config otro\n * Qué conviene editar aquí: todo.\n */\n\$x = 1;\n";
        if (self::configOwnershipProblem($buena) !== null || self::configOwnershipProblem($mala) === null) {
            return ['canario muerto: el validador no distingue una cabecera válida de otra que no lo es; la comprobación no miró nada'];
        }

        $output = [];
        $status = 0;
        //RETORNO-IGNORADO: el resultado de git llega en $status, que se mira justo debajo.
        exec('git -C ' . escapeshellarg($root) . ' -c core.quotePath=false ls-files -- ' . implode(' ', array_map('escapeshellarg', self::CONFIG_OWNERSHIP_DIRS)) . ' 2>/dev/null', $output, $status);
        if ($status !== 0) {
            return ['git ls-files falló: la comprobación no miró nada'];
        }

        $failures = [];
        $porValor = array_fill_keys(self::CONFIG_OWNERSHIP_VALUES, 0);
        foreach ($output as $relative) {
            if (!str_ends_with($relative, '.php') || !is_file($root . '/' . $relative)) {
                continue;
            }
            $texto = (string) @file_get_contents($root . '/' . $relative);
            $problema = self::configOwnershipProblem($texto);
            if ($problema !== null) {
                $failures[] = "{$relative}: {$problema}";
                continue;
            }
            if (preg_match('/@pcsphp-config\s+(\w+)/u', $texto, $valor) === 1) {
                $porValor[$valor[1]]++;
            }
        }
        if (count($failures) === 0) {
            $total = array_sum($porValor);
            echoTerminal("\e[94mINFO:\e[39m de quién es cada configuración: {$total} archivo(s) en " . implode(' y ', self::CONFIG_OWNERSHIP_DIRS)
                . " con su cabecera: {$porValor['framework']} del framework, {$porValor['clon']} del clon y {$porValor['ambos']} de los dos, estos con sus dos marcas de sección.");
        }
        return $failures;
    }

    /**
     * Qué le falta a la cabecera de un archivo de configuración, o null si está completa.
     *
     * @param string $texto El contenido del archivo.
     * @return string|null
     */
    protected static function configOwnershipProblem(string $texto): ?string
    {
        $docblock = null;
        foreach (token_get_all($texto) as $token) {
            if (is_array($token) && $token[0] === T_DOC_COMMENT) {
                $docblock = $token[1];
                break;
            }
        }
        if ($docblock === null) {
            return 'no tiene docblock: le falta la cabecera «@pcsphp-config framework|clon|ambos».';
        }
        if (preg_match('/@pcsphp-config\s+(\w+)/u', $docblock, $valor) !== 1 || !in_array($valor[1], self::CONFIG_OWNERSHIP_VALUES, true)) {
            return 'su primer docblock no dice «@pcsphp-config framework|clon|ambos».';
        }
        if (preg_match('/Qué conviene editar aquí:\s*\S/u', $docblock) !== 1) {
            return 'su cabecera no dice «Qué conviene editar aquí: …».';
        }
        if ($valor[1] === 'ambos' && (!str_contains($texto, self::CONFIG_MARK_FRAMEWORK) || !str_contains($texto, self::CONFIG_MARK_CLON))) {
            return 'es «ambos» y le falta alguna de sus dos marcas de sección («' . self::CONFIG_MARK_FRAMEWORK . '» y «' . self::CONFIG_MARK_CLON . '»).';
        }
        return null;
    }

    /**
     * Dónde aparece cada texto buscado, con los límites de bin/censo-borrados, como «archivo:línea — forma «texto»».
     *
     * Cada archivo se trocea en palabras una sola vez y un texto solo se busca donde aparece su último segmento: buscar
     * los mil y pico textos en cada archivo costaba nueve segundos.
     *
     * @param array<array<string,string>> $buscas Cada una con `forma`, `busca` y, si la tiene, `ambito`.
     * @param array<string,string> $textos El contenido de cada archivo, por su ruta.
     * @return string[]
     */
    protected static function findCampaignRemnants(array $buscas, array $textos): array
    {
        $porUltimo = [];
        foreach ($buscas as $busca) {
            $partes = preg_split('~[/\\\\]+~', $busca['busca'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $ultimo = (string) end($partes);
            $porUltimo[$ultimo][] = $busca;
        }
        $limites = [
            'clase' => ['(?<![\\w\\\\])', '(?![\\w])'],
            'plantilla' => ['(?<![\\w\\-.\\/])', '(?![\\w\\-])'],
        ];
        $hallados = [];
        foreach ($textos as $archivo => $texto) {
            $palabras = array_flip(preg_split('~[^\\p{L}\\p{N}_.\\-]+~u', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: []);
            $lineas = null;
            foreach ($porUltimo as $ultimo => $grupo) {
                if (!isset($palabras[$ultimo])) {
                    continue;
                }
                foreach ($grupo as $busca) {
                    $aguja = $busca['busca'];
                    if (!str_contains($texto, $aguja)
                        || ($busca['forma'] === 'plantilla' && !str_ends_with($archivo, '.php'))
                        || (isset($busca['ambito']) && !str_starts_with($archivo, $busca['ambito']))) {
                        continue;
                    }
                    [$izquierda, $derecha] = $limites[$busca['forma']] ?? ['(?<![\\w\\-.])', '(?![\\w\\-])'];
                    $patron = '~' . $izquierda . preg_quote($aguja, '~') . $derecha . '~u';
                    $lineas ??= explode("\n", $texto);
                    foreach ($lineas as $indice => $linea) {
                        if (str_contains($linea, $aguja) && preg_match($patron, $linea) === 1) {
                            $hallados[$archivo . ':' . ($indice + 1) . ' — ' . $busca['forma'] . ' «' . $aguja . '»'] = true;
                        }
                    }
                }
            }
        }
        return array_keys($hallados);
    }

    /**
     * Por qué un guion no sirve de prueba de una capacidad, o null si sirve: tiene que estar en `bin/`, versionado y
     * marcado ejecutable en el índice (el disco no basta: el chmod no viaja con el clon).
     *
     * @param string $root
     * @param string $script Ruta relativa a la raíz, como `bin/make-distribution`.
     * @return string|null
     */
    protected static function scriptCapabilityProblem(string $root, string $script): ?string
    {
        if (!str_starts_with($script, 'bin/') || str_contains($script, '..')) {
            return 'no es una ruta de bin/';
        }
        if (!is_file($root . '/' . $script)) {
            return 'NO EXISTE';
        }
        $indice = [];
        //RETORNO-IGNORADO: se lee la salida entera; vacía es «no versionado».
        exec('git -C ' . escapeshellarg($root) . ' ls-files -s -- ' . escapeshellarg($script) . ' 2>/dev/null', $indice);
        $modo = substr((string) ($indice[0] ?? ''), 0, 6);
        if ($modo === '') {
            return 'no está versionado';
        }
        if ($modo !== '100755') {
            return "no es ejecutable en el índice ({$modo})";
        }
        return null;
    }

    /**
     * Lo que falta de cada capacidad: rutas sin registrar, acciones sin su ruta `terminal-` y anuncios sin archivo.
     *
     * @param array<mixed> $capacidades
     * @param array<int|string> $rutas Los nombres de `get_routes()`.
     * @param string $root La raíz del repositorio, para los anuncios.
     * @param string[] $claves Las claves de configuración que el código lee.
     * @return string[]
     */
    protected static function validateAnnouncedCapabilities(array $capacidades, array $rutas, string $root, array $claves = []): array
    {
        $existentes = array_flip(array_map('strval', $rutas));
        $clavesLeidas = array_flip($claves);
        $failures = [];
        foreach ($capacidades as $nombre => $capacidad) {
            $capacidad = is_array($capacidad) ? $capacidad : [];
            $servidas = (array) ($capacidad['routes'] ?? []);
            $acciones = (array) ($capacidad['actions'] ?? []);
            $porClave = (array) ($capacidad['config'] ?? []);
            $porAviso = (array) ($capacidad['alert'] ?? []);
            $guiones = (array) ($capacidad['scripts'] ?? []);
            if (count($servidas) + count($acciones) + count($porClave) + count($porAviso) + count($guiones) === 0) {
                $failures[] = "{$nombre}: no nombra ninguna ruta, acción, clave, aviso ni guion; una capacidad sin quien la sirva no se anuncia";
            }
            foreach ($guiones as $guion) {
                $problema = self::scriptCapabilityProblem($root, (string) $guion);
                if ($problema !== null) {
                    $failures[] = "{$nombre}: el guion `{$guion}` {$problema}";
                }
            }
            foreach ($porClave as $clave) {
                if (!isset($clavesLeidas[(string) $clave])) {
                    $failures[] = "{$nombre}: la clave de configuración `{$clave}` NO la lee el código (bin/censo-claves-config --universo)";
                }
            }
            foreach ($porAviso as $aviso) {
                if (SystemAlertRegistry::get((string) $aviso) === null) {
                    $failures[] = "{$nombre}: el aviso `{$aviso}` NO está registrado en SystemAlertRegistry";
                }
            }
            foreach ($servidas as $ruta) {
                if (!isset($existentes[(string) $ruta])) {
                    $failures[] = "{$nombre}: la ruta `{$ruta}` NO EXISTE; se anuncia una función que nadie sirve";
                }
            }
            foreach ($acciones as $accion) {
                if (!isset($existentes['terminal-' . $accion])) {
                    $failures[] = "{$nombre}: la acción de terminal `{$accion}` NO EXISTE (no hay ruta terminal-{$accion})";
                }
            }
            foreach ((array) ($capacidad['announced'] ?? []) as $anuncio) {
                if (!is_file($root . '/' . $anuncio)) {
                    $failures[] = "{$nombre}: se dice anunciada en `{$anuncio}`, que no existe";
                }
            }
        }
        return $failures;
    }

    /**
     * Ningún archivo LEE `$this->model` sin asignarlo.
     *
     * Hasta el 2026-09-25 `BaseController::__construct()` DEDUCÍA el modelo del nombre de la clase
     * con un `str_replace`, y esta comprobación vigilaba que el nombre compuesto existiera. La
     * deducción se retiró: ya no hay nombre que componer, y con ella se fue la trampa de que un
     * controlador cambiado de espacio de nombres se quedara con un modelo genérico sin enterarse.
     *
     * LO QUE QUEDA VIGILADO ES LA CONSECUENCIA. Sin deducción, un controlador que no asigne su
     * modelo recibe un `BaseModel` GENÉRICO, sin tabla ni campos —esa rama se conservó a propósito,
     * porque de ella dependen las llamadas directas `new BaseController()`—. Así que leer
     * `$this->model` sin haberlo asignado sigue dando un objeto que no es el que se espera, y sigue
     * sin fallar: los errores empiezan en la primera consulta.
     *
     * SE MIDE SOBRE TOKENS, NO SOBRE TEXTO, y no es un lujo: dos archivos del árbol nombran
     * `$this->model` DENTRO DE UN COMENTARIO —uno de ellos este mismo docblock—, y un `grep` los
     * cuenta como lecturas. Medido el 2026-09-25: 29 archivos lo nombran, 27 lo leen y lo asignan,
     * y los 2 restantes son comentarios.
     *
     * @return string[]
     */
    protected static function checkModelIsAssigned(): array
    {
        $base = rtrim(str_replace('\\', '/', basepath('')), '/');
        $failures = [];
        $leen = 0;
        $mirados = 0;

        foreach (self::collectFiles() as $relative) {
            $absolute = $base . '/' . $relative;
            $source = (string) @file_get_contents($absolute);
            if ($source === '' || mb_strpos($source, 'this->model') === false) {
                continue;
            }
            $mirados++;

            //Se recorre el archivo en tokens y se cuentan por separado las LECTURAS y las
            //ASIGNACIONES de `$this->model`, saltándose comentarios y docblocks.
            $lecturas = 0;
            $asignaciones = 0;
            $tokens = token_get_all($source);
            $total = count($tokens);
            for ($i = 0; $i < $total; $i++) {
                $token = $tokens[$i];
                if (!is_array($token) || $token[0] !== T_VARIABLE || $token[1] !== '$this') {
                    continue;
                }
                //`$this` `->` `model`
                $flecha = $tokens[$i + 1] ?? null;
                $nombre = $tokens[$i + 2] ?? null;
                if (!is_array($flecha) || $flecha[0] !== T_OBJECT_OPERATOR) {
                    continue;
                }
                if (!is_array($nombre) || $nombre[0] !== T_STRING || $nombre[1] !== 'model') {
                    continue;
                }
                //Lo que sigue decide si es asignación: un `=` que no sea comparación.
                $siguiente = null;
                for ($j = $i + 3; $j < $total; $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                        continue;
                    }
                    $siguiente = $tokens[$j];
                    break;
                }
                if ($siguiente === '=') {
                    $asignaciones++;
                } else {
                    $lecturas++;
                }
                $i += 2;
            }

            if ($lecturas > 0) {
                $leen++;
            }
            if ($lecturas > 0 && $asignaciones === 0) {
                $failures[] = $relative . ' — lee `$this->model` ' . $lecturas . ' vez/veces y NO lo asigna nunca.'
                    . ' Sin la deducción retirada el 2026-09-25, lo que recibe es un BaseModel GENÉRICO, sin'
                    . ' tabla ni campos, y no falla hasta la primera consulta.'
                    . ' Se arregla asignando el modelo en el constructor: $this->model = (new SuMapper())->getModel().';
            }
        }

        if ($mirados < 10) {
            //Canario de cobertura: con este árbol son 29. Si baja de golpe, el recorrido dejó de ver.
            return ['solo ' . $mirados . ' archivo(s) nombran `$this->model` y con este árbol son casi treinta:'
                . ' el recorrido ha dejado de ver y la comprobación NO vale'];
        }

        echoTerminal("\e[94mINFO:\e[39m " . $leen . ' archivo(s) leen `$this->model` de ' . $mirados . ' que lo nombran'
            . ' (la diferencia son comentarios, y por eso se mide en tokens); todos lo asignan.'
            . ' La deducción por nombre de clase se retiró: lo vigila la suite core/generic-model-on-construct.');

        return $failures;
    }

    const PUBLIC_ROUTES_RELATIVE_PATH = 'files/dev/public-routes-in-guarded-modules.json';

    const LOGIN_USER_DATA_RELATIVE_PATH = 'files/dev/login-user-data-declared.json';

    /**
     * Ninguna ruta de un módulo con control de acceso queda sin declarar.
     *
     * `DefaultAccessControlModules` decide el acceso con `routeName()`, que SIN USUARIO CONCEDE.
     * La otra capa —`src/index.php` §8— solo mira `require_login`. Una ruta que no declare ni
     * `require_login` ni `roles_allowed` no la ve ninguna de las dos: NACE PÚBLICA, y nada avisa.
     * Medido en AH y AJ: de 306 rutas, 95 no declaran nada, y dentro de los módulos con control
     * son CUATRO. Ver T148 y T150.
     *
     * @return string[]
     */
    protected static function checkUndeclaredRoutesInGuardedModules(): array
    {
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $root = dirname($repoRoot);

        $inventario = json_decode((string) @file_get_contents($root . '/files/dev/route-inventory.json'), true);
        if (!is_array($inventario)) {
            return ['no se pudo leer files/dev/route-inventory.json: la comprobación no miró nada'];
        }
        $registro = json_decode((string) @file_get_contents($root . '/' . self::PUBLIC_ROUTES_RELATIVE_PATH), true);
        if (!is_array($registro) || !isset($registro['entries']) || !is_array($registro['entries'])) {
            return ['no se pudo leer ' . self::PUBLIC_ROUTES_RELATIVE_PATH . ': la comprobación no miró nada'];
        }

        //Las bases SALEN DEL ÁRBOL: un módulo nuevo que instale el middleware entra solo, y una
        //clase que lo cite sin declarar `$baseRouteName` —el propio trait— no aporta base.
        $bases = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($repoRoot . '/app', \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $p = str_replace('\\', '/', (string) $file->getPathname());
            if (!str_ends_with($p, '.php') || mb_strpos($p, '/logs/') !== false) {
                continue;
            }
            $contenido = (string) file_get_contents($p);
            if (mb_strpos($contenido, 'DefaultAccessControlModules') === false) {
                continue;
            }
            if (preg_match("/baseRouteName\s*=\s*'([^']+)'/", $contenido, $m) === 1) {
                $bases[] = $m[1];
            }
        }
        $bases = array_values(array_unique($bases));

        if (count($bases) === 0) {
            return ['ninguna clase declara base con DefaultAccessControlModules: el censo no vio nada'];
        }

        $declaradas = [];
        foreach ($registro['entries'] as $entry) {
            $declaradas[] = (string) ($entry['route'] ?? '');
        }

        $failures = [];
        $sinDeclarar = [];
        foreach ($inventario as $ruta) {
            $nombre = (string) ($ruta['name'] ?? '');
            $delModulo = false;
            foreach ($bases as $base) {
                if ($nombre === $base || str_starts_with($nombre, $base . '-')) {
                    $delModulo = true;
                    break;
                }
            }
            if (!$delModulo) {
                continue;
            }
            $pideLogin = ($ruta['requireLogin'] ?? false) === true;
            $tieneRoles = is_array($ruta['rolesAllowed'] ?? null) && count($ruta['rolesAllowed']) > 0;
            if ($pideLogin || $tieneRoles) {
                continue;
            }
            $sinDeclarar[] = $nombre;
            if (in_array($nombre, $declaradas, true)) {
                continue;
            }
            $failures[] = $nombre . ' — está en un módulo que instala DefaultAccessControlModules y no'
                . ' declara ni require_login ni roles_allowed: NACE PÚBLICA. Si es a propósito, va a '
                . self::PUBLIC_ROUTES_RELATIVE_PATH . ' con su razón.';
        }

        foreach ($declaradas as $nombre) {
            if (!in_array($nombre, $sinDeclarar, true)) {
                $failures[] = $nombre . ' — figura como pública declarada y ya no lo es (o ya no existe).'
                    . ' Quita la entrada: la lista solo puede encoger.';
            }
        }

        echoTerminal("\e[94mINFO:\e[39m " . count($inventario) . ' ruta(s) del inventario contra '
            . count($bases) . ' módulo(s) con control de acceso: ' . count($sinDeclarar)
            . ' sin declaración, ' . count($declaradas) . ' declarada(s) como públicas.');

        return $failures;
    }
    /**
     * Las vistas cuyas etiquetas NO cuadran a propósito. Como `KNOWN_ECLIPSES`: solo encoge.
     */
    const KNOWN_UNBALANCED_VIEWS = [
        'src/app/view/panel/layout/header.php' => [
            'reason' => 'Abre el armazón de la página que cierra footer.php: el desbalance es '
                . 'el reparto entre los dos archivos, no un error. Medido: 9 <div> y 7 </div>.',
            'retiredWhen' => 'Cuando el armazón del panel deje de repartirse entre dos archivos.',
        ],
        'src/app/view/panel/layout/footer.php' => [
            'reason' => 'Cierra lo que abrió header.php. Medido: 0 <div> y 2 </div>.',
            'retiredWhen' => 'Cuando el armazón del panel deje de repartirse entre dos archivos.',
        ],
    ];

    /**
     * Las etiquetas de cada vista cuadran.
     *
     * Existe por dos incidentes de la misma forma: anclar una edición en un `</div>` A
     * SANGRÍA FIJA. La sangría no dice qué cierra ese `</div>`, y las dos veces cerró un
     * contenedor INTERIOR: en `generic-report-view.php` (bloque AB) se fueron las mitades
     * de arriba de dos tarjetas y quedaron dos pies huérfanos RENDIDOS EN PANTALLA, con sus
     * etiquetas visibles. Lo vio el PROPIETARIO, no la puerta.
     *
     * VIABILIDAD MEDIDA ANTES DE CONSTRUIRLA, porque un `<div>` dentro de un `if` con su
     * cierre en el `else` daría desbalance legítimo: de 178 vistas del árbol, **176 cuadran**
     * en las ocho etiquetas. Las dos que no son el armazón del panel, y van declaradas.
     *
     * @return string[]
     */
    protected static function checkViewTagBalance(): array
    {
        $tags = ['div', 'section', 'table', 'form', 'ul', 'li', 'tr', 'td'];

        //CANARIO: un desbalance conocido tiene que salir, y uno equilibrado no. Sin las dos
        //caras, un «todas cuadran» no significaría nada. LEY 16.
        $roto = self::tagBalance('<div class="a"><div></div>', 'div');
        $sano = self::tagBalance('<div class="a"><div></div></div>', 'div');
        if ($roto[0] === $roto[1] || $sano[0] !== $sano[1]) {
            return ['CANARIO CAÍDO: el contador de etiquetas no distingue un desbalance de un '
                . 'equilibrio. La comprobación NO se hace.'];
        }

        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $root = dirname($repoRoot);
        $views = [];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($repoRoot . '/app', \FilesystemIterator::SKIP_DOTS));

        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $p = str_replace('\\', '/', (string) $file->getPathname());
            if (!str_ends_with($p, '.php')) {
                continue;
            }
            if (mb_strpos($p, '/Views/') === false && mb_strpos($p, '/view/') === false) {
                continue;
            }
            $views[] = $p;
        }

        sort($views);

        $failures = [];
        $descuadradas = [];

        foreach ($views as $p) {
            $relativo = ltrim(str_replace($root, '', $p), '/');
            $contenido = (string) file_get_contents($p);
            $malas = [];

            foreach ($tags as $tag) {
                [$abren, $cierran] = self::tagBalance($contenido, $tag);
                if ($abren !== $cierran) {
                    $malas[] = "<{$tag}> {$abren}/{$cierran}";
                }
            }

            if (count($malas) === 0) {
                continue;
            }

            $descuadradas[] = $relativo;

            if (array_key_exists($relativo, self::KNOWN_UNBALANCED_VIEWS)) {
                continue;
            }

            $failures[] = $relativo . ' — ' . implode(', ', $malas)
                . '. Una edición en una vista se cierra CONTANDO ETIQUETAS: si el desbalance es'
                . ' a propósito, va a KNOWN_UNBALANCED_VIEWS con su razón y su condición de retirada.';
        }

        foreach (self::KNOWN_UNBALANCED_VIEWS as $relativo => $entry) {
            if (!in_array($relativo, $descuadradas, true)) {
                $failures[] = $relativo . ' — figura como desbalance declarado y ya cuadra.'
                    . ' Quita la entrada: la lista solo puede encoger.';
            }
        }

        echoTerminal("\e[94mINFO:\e[39m " . count($views) . ' vista(s) comprobadas en '
            . count($tags) . ' etiquetas, ' . count(self::KNOWN_UNBALANCED_VIEWS) . ' desbalance(s) declarado(s).');

        return $failures;
    }

    /**
     * Cuántas veces abre y cuántas cierra una etiqueta en un texto.
     *
     * @param string $contenido
     * @param string $tag
     * @return array{0: int, 1: int}
     */
    protected static function tagBalance(string $contenido, string $tag): array
    {
        $abren = preg_match_all('#<' . $tag . '[\s>/]#i', $contenido);
        $cierran = preg_match_all('#</' . $tag . '\s*>#i', $contenido);

        return [is_int($abren) ? $abren : 0, is_int($cierran) ? $cierran : 0];
    }

    /**
     * Ningún enlace de `statics/server-delegated/` apunta al vacío.
     *
     * `ServerStatics::createDynamicSymlink()` crea enlaces al servir y NUNCA retira uno
     * cuyo destino desapareció. Borrar los `Statics/` de un módulo deja un enlace roto por
     * archivo, y el directorio está declarado volátil, así que la foto no los ve.
     *
     * SE COMPRUEBA AQUÍ Y NO SE RETIRA EN CALIENTE, por dos medidas:
     *
     *  1. El retorno temprano de `createDynamicSymlink()` ocurre ANTES de normalizar la
     *     ruta —`realpath()` falla y devuelve—, así que borrar ahí actuaría sobre una ruta
     *     sin normalizar, que es donde un `../` se vuelve un borrado.
     *  2. Ese camino solo corre cuando ALGUIEN PIDE el asset. Nadie pide los de un módulo
     *     muerto, así que la limpieza en caliente no llegaría nunca a los que importan.
     *
     * Ver T142.
     *
     * @return string[]
     */
    protected static function checkDanglingDelegatedLinks(): array
    {
        $base = basepath('statics/server-delegated');
        if (!is_dir($base)) {
            echoTerminal("\e[94mINFO:\e[39m no hay árbol servido delegado que comprobar.");
            return [];
        }

        $broken = [];
        $checked = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $entry) {
            $path = (string) $entry->getPathname();
            if (!is_link($path)) {
                continue;
            }
            $checked++;
            //`file_exists()` SIGUE el enlace: da false justo cuando el destino no está.
            if (!file_exists($path)) {
                $broken[] = str_replace(basepath(), '', $path) . ' — su destino ya no existe';
            }
        }

        echoTerminal("\e[94mINFO:\e[39m {$checked} enlace(s) del árbol servido comprobados contra su destino.");
        return $broken;
    }

    /**
     * Los retornos ignorados SIN DECLARAR no han crecido.
     *
     * Delega en `bin/censo-retornos-ignorados --trinquete`, que es donde vive el método:
     * duplicarlo aquí crearía dos verdades sin puerta entre ellas (LEY 11).
     *
     * @return string[]
     */
    protected static function checkIgnoredReturns(): array
    {
        $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $script = $root . '/bin/censo-retornos-ignorados';

        if (!is_file($script)) {
            //Una comprobación que no encuentra su instrumento NO reporta «todo bien». LEY 18.
            return ['no existe ' . $script . ': el trinquete de retornos NO se ha comprobado'];
        }

        $output = [];
        $status = 0;
        //RETORNO-IGNORADO: `exec()` devuelve la última línea, y aquí lo que decide es $status.
        exec('cd ' . escapeshellarg($root) . ' && ' . escapeshellarg($script) . ' --trinquete 2>&1', $output, $status);

        $line = '';
        foreach ($output as $candidate) {
            if (mb_strpos($candidate, 'TRINQUETE') === 0) {
                $line = $candidate;
            }
        }

        if ($status !== 0) {
            return [$line !== '' ? $line : 'el censo de retornos salió con código ' . $status];
        }

        echoTerminal("\e[94mINFO:\e[39m " . ($line !== '' ? mb_substr($line, mb_strlen('TRINQUETE: ')) : 'retornos ignorados comprobados.'));
        return [];
    }

    /**
     * Al navegador solo viajan los campos del usuario que están DECLARADOS.
     *
     * Antes el login mandaba «lo que tuviera la tabla menos dos campos quitados a mano», así que una columna nueva en
     * `pcsphp_users` viajaba sola al navegador de todos los usuarios sin que nadie lo decidiera. Esta comprobación cierra
     * las tres puertas: la constante contra la lista declarada, la lista contra las columnas que existen, y las columnas
     * nuevas contra la lista.
     *
     * @return string[]
     */
    protected static function checkLoginUserData(): array
    {
        $failures = [];
        $relative = self::LOGIN_USER_DATA_RELATIVE_PATH;
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $raw = @file_get_contents(dirname($repoRoot) . '/' . $relative);
        $declared = is_string($raw) ? json_decode($raw, true) : null;

        if (!is_array($declared) || !isset($declared['fields']) || !is_array($declared['fields'])) {
            //Una comprobación que no encuentra su declaración NO reporta «todo bien». LEY 18.
            return ['no se pudo leer ' . $relative . ': los campos que viajan al navegador NO se han comprobado'];
        }

        $declaredFields = array_values(array_filter($declared['fields'], 'is_string'));
        $inCode = \PiecesPHP\UserSystem\Controllers\UsersController::LOGIN_USER_DATA_FIELDS;
        $excluded = is_array($declared['excluded'] ?? null) ? array_keys($declared['excluded']) : [];

        $sobranEnCodigo = array_values(array_diff($inCode, $declaredFields));
        $faltanEnCodigo = array_values(array_diff($declaredFields, $inCode));
        if (count($sobranEnCodigo) > 0) {
            $failures[] = 'UsersController::LOGIN_USER_DATA_FIELDS manda campos que ' . $relative
                . ' no declara: ' . implode(', ', $sobranEnCodigo) . '.';
        }
        if (count($faltanEnCodigo) > 0) {
            $failures[] = $relative . ' declara campos que el login ya no manda: ' . implode(', ', $faltanEnCodigo)
                . '. Una lista que miente es peor que ninguna.';
        }

        //Y contra la TABLA: una columna nueva tiene que estar declarada, aunque sea para excluirla.
        $columns = array_keys((new \PiecesPHP\UserSystem\ORM\UsersModel())->getFields());
        $sinDecidir = array_values(array_diff($columns, $declaredFields, $excluded));
        if (count($sinDecidir) > 0) {
            $failures[] = 'la tabla de usuarios tiene columnas que ' . $relative . ' no menciona: '
                . implode(', ', $sinDecidir) . '. Decide si viajan al navegador o no, y escríbelo.';
        }
        $inventadas = array_values(array_diff($declaredFields, $columns));
        if (count($inventadas) > 0) {
            $failures[] = $relative . ' declara campos que no son columnas de la tabla: ' . implode(', ', $inventadas) . '.';
        }

        if (count($failures) === 0) {
            echoTerminal("\e[94mINFO:\e[39m navegador: " . count($declaredFields) . ' campo(s) del usuario declarados y '
                . count($excluded) . ' excluidos, contra ' . count($columns) . ' columnas de la tabla.');
        }

        return $failures;
    }

    /**
     * El nombre de la sesión vive en DOS lados —PHP y el JavaScript del navegador— y tienen que decir lo mismo.
     *
     * El valor real sale de la configuración, pero los dos lados llevan un valor POR DEFECTO escrito, y hasta hoy nada
     * impedía que se separaran: el literal estaba copiado en cuatro sitios y coincidía por costumbre, no por contrato.
     * Si divergen, el navegador guardaría la sesión con un nombre y el servidor la buscaría con otro.
     *
     * @return string[]
     */
    protected static function checkSessionTokenName(): array
    {
        $failures = [];
        $phpFile = 'app/core/psr4/PiecesPHP/Core/SessionToken.php';
        $jsFile = 'statics/core/js/user-system/PiecesPHPSystemUserHelper.js';

        $php = @file_get_contents(basepath($phpFile));
        $js = @file_get_contents(basepath($jsFile));

        if (!is_string($php) || !is_string($js)) {
            //Una comprobación que no encuentra sus archivos NO reporta «todo bien». LEY 18.
            return ['no se pudieron leer ' . $phpFile . ' y ' . $jsFile . ': el nombre de la sesión NO se ha comprobado'];
        }

        $phpDefault = preg_match("/const\s+TOKEN_NAME\s*=\s*'([^']+)'/", $php, $m) === 1 ? $m[1] : null;
        $jsDefault = preg_match("/static\s+defaultJWTAuthName\s*=\s*'([^']+)'/", $js, $m) === 1 ? $m[1] : null;

        if ($phpDefault === null) {
            $failures[] = $phpFile . ' — no se encontró la constante TOKEN_NAME: sin ella no se puede comparar nada.';
        }
        if ($jsDefault === null) {
            $failures[] = $jsFile . ' — no se encontró «static defaultJWTAuthName»: sin él no se puede comparar nada.';
        }
        if ($phpDefault !== null && $jsDefault !== null && $phpDefault !== $jsDefault) {
            $failures[] = 'el nombre de la sesión por defecto DIVERGE: «' . $phpDefault . '» en ' . $phpFile
                . ' y «' . $jsDefault . '» en ' . $jsFile . '. El navegador guardaría con uno y el servidor buscaría con otro.';
        }

        //Y que el JavaScript siga LEYENDO el del servidor: si deja de hacerlo, el nombre configurado no llegaría.
        if (!str_contains($js, 'frontConfigurationsFromBackend.sessionTokenName')) {
            $failures[] = $jsFile . ' — ya no lee «sessionTokenName» de las configuraciones del frente: el nombre'
                . ' configurado en el servidor no llegaría al navegador.';
        }
        if (!str_contains((string) @file_get_contents(basepath('app/config/final-configurations.php')), "add_to_front_configurations('sessionTokenName'")) {
            $failures[] = 'app/config/final-configurations.php — ya no inyecta «sessionTokenName»: el navegador se'
                . ' quedaría con su valor de último recurso.';
        }

        if (count($failures) === 0) {
            echoTerminal("\e[94mINFO:\e[39m nombre de la sesión: «" . (string) $phpDefault . "» por defecto en los dos lados, y el JavaScript lo recibe del servidor.");
        }

        return $failures;
    }

    /**
     * Suites y tareas que no cargaron al arrancar (LoadFailures): se saltaron para no tumbar bin/cli, y aquí fallan.
     *
     * @return string[]
     */
    protected static function checkBootLoadFailures(): array
    {
        $failures = [];
        foreach (LoadFailures::all() as $failure) {
            $failures[] = "{$failure['type']}: no se cargó " . LoadFailures::describe($failure);
        }

        //Canario (LEY 15): sin fallos, se dice cuánto se cargó. Cero suites o cero tareas es que no se miró nada.
        $suites = LoadFailures::loadedCount(LoadFailures::TYPE_SUITE);
        $tasks = LoadFailures::loadedCount(LoadFailures::TYPE_TASK);
        if ($suites === 0 || $tasks === 0) {
            $failures[] = "{$suites} suite(s) y {$tasks} tarea(s) cargadas; con cero, la carga no pasó por LoadFailures y la comprobación no miró nada";
        }
        echoTerminal("\e[94mINFO:\e[39m arranque: {$suites} archivo(s) de suite y {$tasks} de tarea cargados; " . count(LoadFailures::all()) . " sin cargar.");

        return $failures;
    }

    /**
     * Ningún mensaje de excepción llega a una respuesta salvo lo declarado (P56).
     *
     * Un sitio es un `$x->getMessage()` cuyo valor va a setMessage(), a setValue() o a una clave 'message'/'error'.
     * Se identifica por archivo y por los tipos del catch que declara `$x` ('-' si no hay catch): sin número de línea,
     * que se mueve con cada cambio. La lista es un multiconjunto y cuadra en los dos sentidos.
     *
     * @return string[]
     */
    protected static function checkExceptionMessages(): array
    {
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $root = dirname($repoRoot);

        //Canario: los tres destinos, dentro de un catch, se ven; y el catch se lee.
        $sembrado = self::exceptionMessageSites("<?php\ntry{}catch(\\PDOException \$e){ \$r->setMessage(\$e->getMessage()); \$r->setValue('x', \$e->getMessage()); return ['error' => \$e->getMessage()]; }\n\$y = \$e->getMessage();\n");
        $canarioVivo = count($sembrado) === 3 && $sembrado[0]['exception'] === '\\PDOException';
        if (!$canarioVivo) {
            return ['canario muerto: el tokenizador no ve los sitios sembrados o no lee su catch: la comprobación no miró nada'];
        }

        $registro = json_decode((string) @file_get_contents($root . '/' . self::EXCEPTION_MESSAGE_DECLARED_RELATIVE_PATH), true);
        $declarados = is_array($registro) && is_array($registro['sites'] ?? null) ? $registro['sites'] : null;
        if ($declarados === null) {
            return ['no se pudo leer ' . self::EXCEPTION_MESSAGE_DECLARED_RELATIVE_PATH . ': la comprobación no miró nada'];
        }

        $failures = [];
        $pendientes = [];
        foreach ($declarados as $i => $sitio) {
            $archivo = is_array($sitio) ? ($sitio['file'] ?? null) : null;
            $excepcion = is_array($sitio) ? ($sitio['exception'] ?? null) : null;
            $clase = is_array($sitio) ? ($sitio['class'] ?? null) : null;
            if (!is_string($archivo) || !is_string($excepcion) || !in_array($clase, self::EXCEPTION_MESSAGE_CLASSES, true)) {
                $failures[] = self::EXCEPTION_MESSAGE_DECLARED_RELATIVE_PATH . ": la entrada {$i} no tiene file, exception y una clase válida (" . implode('|', self::EXCEPTION_MESSAGE_CLASSES) . ')';
                continue;
            }
            $pendientes["{$archivo}|{$excepcion}"] = ($pendientes["{$archivo}|{$excepcion}"] ?? 0) + 1;
        }

        //Universo: src/app sin vendor, logs ni las pruebas.
        $archivos = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($repoRoot . '/app', \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $p = str_replace('\\', '/', (string) $file->getPathname());
            if ($file->isFile() && str_ends_with($p, '.php') && mb_strpos($p, '/vendor/') === false && mb_strpos($p, '/logs/') === false
                && mb_strpos($p, '/system-controllers/local-tests/') === false) {
                $archivos[] = $p;
            }
        }

        $vistos = 0;
        foreach ($archivos as $archivo) {
            $contenido = (string) file_get_contents($archivo);
            if (mb_strpos($contenido, 'getMessage') === false) {
                continue;
            }
            $relativo = mb_substr($archivo, mb_strlen($root) + 1);
            foreach (self::exceptionMessageSites($contenido) as $sitio) {
                $vistos++;
                $clave = "{$relativo}|{$sitio['exception']}";
                if (($pendientes[$clave] ?? 0) > 0) {
                    $pendientes[$clave]--;
                } else {
                    $failures[] = "{$relativo}:{$sitio['line']}: el mensaje de una excepción ({$sitio['exception']}) va a la respuesta sin declararse; responde con CustomSlimErrorHandler::genericMessage(log_exception(\$e)) o decláralo en " . self::EXCEPTION_MESSAGE_DECLARED_RELATIVE_PATH;
                }
            }
        }
        foreach ($pendientes as $clave => $sobran) {
            if ($sobran > 0) {
                [$archivo, $excepcion] = explode('|', $clave, 2);
                $failures[] = self::EXCEPTION_MESSAGE_DECLARED_RELATIVE_PATH . ": {$sobran} declaración(es) de más para {$archivo} ({$excepcion}); la lista solo encoge";
            }
        }

        if (count($failures) === 0) {
            echoTerminal("\e[94mINFO:\e[39m mensajes de excepción: " . count($archivos) . " archivo(s) en el universo (src/app, sin vendor, logs ni local-tests); {$vistos} sitio(s) que llegan a una respuesta, todos declarados (" . count($declarados) . ').');
        }
        return $failures;
    }

    /**
     * Los sitios de un código PHP donde `$x->getMessage()` va a setMessage(), setValue() o una clave 'message'/'error'.
     *
     * @param string $code
     * @return array<int,array{line:int,exception:string}>
     */
    protected static function exceptionMessageSites(string $code): array
    {
        $t = token_get_all($code);
        $n = count($t);
        $txt = fn(int $i): string => is_array($t[$i]) ? $t[$i][1] : $t[$i];
        $salta = function (int $i, int $dir) use ($t, $n): int {
            do {
                $i += $dir;
            } while ($i >= 0 && $i < $n && is_array($t[$i]) && in_array($t[$i][0], [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT], true));
            return $i;
        };
        $sitios = [];
        for ($i = 0; $i < $n; $i++) {
            if (!is_array($t[$i]) || $t[$i][0] !== \T_STRING || $t[$i][1] !== 'getMessage') {
                continue;
            }
            $flecha = $salta($i, -1);
            if ($flecha < 0 || $txt($flecha) !== '->') {
                continue;
            }
            $variable = $salta($flecha, -1);
            $nombre = $variable >= 0 ? $txt($variable) : '';
            $profundidad = 0;
            $funciones = [];
            $claveArray = null;
            for ($j = $variable - 1; $j >= 0; $j--) {
                $x = $txt($j);
                if ($x === ')') {
                    $profundidad++;
                } elseif ($x === '(') {
                    if ($profundidad === 0) {
                        $funciones[] = $txt($salta($j, -1));
                    } else {
                        $profundidad--;
                    }
                } elseif (($x === ';' || $x === '{' || $x === '}') && $profundidad === 0) {
                    break;
                } elseif ($x === '=>' && $profundidad === 0 && $claveArray === null) {
                    $claveArray = trim($txt($salta($j, -1)), "'\"");
                }
            }
            if (!in_array('setMessage', $funciones, true) && !in_array('setValue', $funciones, true) && !in_array($claveArray, ['message', 'error'], true)) {
                continue;
            }
            $excepcion = '-';
            for ($j = $i; $j >= 0; $j--) {
                if (is_array($t[$j]) && $t[$j][0] === \T_CATCH) {
                    $k = $j;
                    $tipos = '';
                    while ($k + 1 < $n && $txt($k) !== ')') {
                        $k++;
                        $tipos .= $txt($k);
                    }
                    if ($nombre !== '' && str_contains($tipos, $nombre)) {
                        $excepcion = trim(str_replace($nombre, '', rtrim($tipos, ')')), '( ');
                        $excepcion = (string) preg_replace('/\s*\|\s*/', ' | ', $excepcion);
                        break;
                    }
                }
            }
            $sitios[] = ['line' => (int) $t[$i][2], 'exception' => $excepcion];
        }
        return $sitios;
    }

    /**
     * get_route('nombre-literal') está vetado (P36): se pide X::routeName('sufijo'). Por tokens, nunca por texto.
     *
     * @return string[]
     */
    protected static function checkGetRouteLiterals(): array
    {
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $root = dirname($repoRoot);

        //Canario de dos caras: ve una llamada literal sembrada y NO cuenta la de un comentario.
        $sembrado = self::getRouteCalls("<?php\necho get_route('zz-canario', []);\n// get_route('zz-comentario')\n\$x = get_route(\$nombre);\n");
        $canarioVivo = count($sembrado) === 2 && $sembrado[0]['name'] === 'zz-canario' && $sembrado[1]['name'] === null;
        $definicion = str_contains((string) @file_get_contents($repoRoot . '/app/core/AppHelpers.php'), 'function get_route(');
        if (!$canarioVivo || !$definicion) {
            return ['canario muerto: el tokenizador no ve la llamada sembrada, cuenta un comentario, o AppHelpers.php ya no define get_route(): la comprobación no miró nada'];
        }

        $registro = json_decode((string) @file_get_contents($root . '/' . self::GET_ROUTE_ALLOWED_RELATIVE_PATH), true);
        $permitidas = is_array($registro) && is_array($registro['allowed'] ?? null) ? $registro['allowed'] : null;
        if ($permitidas === null) {
            return ['no se pudo leer ' . self::GET_ROUTE_ALLOWED_RELATIVE_PATH . ': la comprobación no miró nada'];
        }

        //Universo: src/app y src/index.php, sin vendor, logs ni las pruebas (comparan contra la URL real a propósito).
        $archivos = [$repoRoot . '/index.php'];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($repoRoot . '/app', \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $p = str_replace('\\', '/', (string) $file->getPathname());
            if ($file->isFile() && str_ends_with($p, '.php') && mb_strpos($p, '/vendor/') === false && mb_strpos($p, '/logs/') === false
                && mb_strpos($p, '/system-controllers/local-tests/') === false) {
                $archivos[] = $p;
            }
        }

        $failures = [];
        $usadas = [];
        $literales = 0;
        $conExpresion = 0;
        foreach ($archivos as $archivo) {
            $contenido = (string) file_get_contents($archivo);
            if (mb_strpos($contenido, 'get_route') === false) {
                continue;
            }
            foreach (self::getRouteCalls($contenido) as $llamada) {
                if ($llamada['name'] === null) {
                    $conExpresion++;
                    continue;
                }
                $literales++;
                $sitio = mb_substr($archivo, mb_strlen($root) + 1) . ':' . $llamada['line'];
                if (array_key_exists($llamada['name'], $permitidas)) {
                    $usadas[$llamada['name']] = true;
                } else {
                    $failures[] = "{$sitio}: get_route('{$llamada['name']}') con nombre literal; usa X::routeName('sufijo') del controlador que la registra";
                }
            }
        }
        foreach (array_keys($permitidas) as $nombre) {
            if (!array_key_exists((string) $nombre, $usadas)) {
                $failures[] = self::GET_ROUTE_ALLOWED_RELATIVE_PATH . ": `{$nombre}` ya no aparece en ninguna llamada; la lista solo encoge";
            }
        }

        if (count($failures) === 0) {
            echoTerminal("\e[94mINFO:\e[39m get_route(): " . count($archivos) . " archivo(s) en el universo (src/app y src/index.php, sin vendor, logs ni local-tests); {$literales} con nombre literal, todas en la lista (" . count($permitidas) . " nombre(s)); {$conExpresion} con variable o expresión, que no se juzgan.");
        }
        return $failures;
    }

    /**
     * Las llamadas a get_route() de un código PHP: línea y nombre si el primer argumento es literal (null si no).
     *
     * @param string $code
     * @return array<int,array{line:int,name:string|null}>
     */
    protected static function getRouteCalls(string $code): array
    {
        $tokens = array_values(array_filter(token_get_all($code), fn ($t) => !is_array($t) || !in_array($t[0], [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT], true)));
        $calls = [];
        foreach ($tokens as $i => $token) {
            if (!is_array($token) || !in_array($token[0], [\T_STRING, \T_NAME_FULLY_QUALIFIED], true) || ltrim($token[1], '\\') !== 'get_route') {
                continue;
            }
            $anterior = $tokens[$i - 1] ?? null;
            //La definición y los métodos ajenos con el mismo nombre no son llamadas a la función.
            if (is_array($anterior) && in_array($anterior[0], [\T_FUNCTION, \T_OBJECT_OPERATOR, \T_NULLSAFE_OBJECT_OPERATOR, \T_DOUBLE_COLON], true)) {
                continue;
            }
            if (($tokens[$i + 1] ?? null) !== '(') {
                continue;
            }
            $argumento = $tokens[$i + 2] ?? null;
            $siguiente = $tokens[$i + 3] ?? null;
            $literal = is_array($argumento) && $argumento[0] === \T_CONSTANT_ENCAPSED_STRING && in_array($siguiente, [',', ')'], true);
            $calls[] = ['line' => (int) $token[2], 'name' => $literal ? stripslashes(mb_substr($argumento[1], 1, -1)) : null];
        }
        return $calls;
    }

    /**
     * Toda carpeta de subidas —el valor de una constante `UPLOAD_DIR` de `src/app`— está
     * registrada en `ProtectFileMiddleware::protect()` o declarada en `files/dev/upload-dirs.json`.
     *
     * Lo protegido se lee de `getProtectedDirectories()` y no del texto de `protected-files.php`:
     * `bin/cli` arranca por `src/index.php`, que ya lo incluyó, y leer el texto daría por buena
     * una línea comentada.
     *
     * @return string[]
     */
    protected static function checkUploadDirsProtected(): array
    {
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $root = dirname($repoRoot);

        $registro = json_decode((string) @file_get_contents($root . '/' . self::UPLOAD_DIRS_RELATIVE_PATH), true);
        $publicas = is_array($registro) ? ($registro['publicas'] ?? null) : null;
        $sinArchivos = is_array($registro) ? ($registro['sin_archivos'] ?? null) : null;
        if (!is_array($publicas) || !is_array($sinArchivos)) {
            return ['no se pudo leer ' . self::UPLOAD_DIRS_RELATIVE_PATH . ': la comprobación no miró nada'];
        }
        $protegidas = array_keys(\PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware::getProtectedDirectories());
        if (count($protegidas) === 0) {
            //SIN REGISTRACIONES NO HAY CON QUÉ COMPARAR: no es «nada protegido», es no haber mirado. LEY 18.
            return ['ProtectFileMiddleware no tiene ninguna carpeta registrada: protected-files.php no se cargó y la comprobación no miró nada'];
        }
        $uploadsDir = (string) get_config('upload_dir');

        //El universo SALE DEL ÁRBOL, por tokens: un UPLOAD_DIR nuevo entra solo, y un comentario no cuenta.
        $constantes = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($repoRoot . '/app', \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $p = str_replace('\\', '/', (string) $file->getPathname());
            if (!$file->isFile() || !str_ends_with($p, '.php') || mb_strpos($p, '/vendor/') !== false || mb_strpos($p, '/logs/') !== false) {
                continue;
            }
            $contenido = (string) file_get_contents($p);
            if (mb_strpos($contenido, 'UPLOAD_DIR') === false) {
                continue;
            }
            $tokens = array_values(array_filter(token_get_all($contenido), fn ($t) => !is_array($t) || !in_array($t[0], [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT], true)));
            foreach ($tokens as $i => $token) {
                $nombre = $tokens[$i + 1] ?? null;
                if (!is_array($token) || $token[0] !== \T_CONST || !is_array($nombre) || $nombre[1] !== 'UPLOAD_DIR') {
                    continue;
                }
                $sitio = mb_substr($p, mb_strlen($repoRoot) + 1) . ':' . $nombre[2];
                $valor = $tokens[$i + 3] ?? null;
                $esLiteral = ($tokens[$i + 2] ?? null) === '=' && is_array($valor) && $valor[0] === \T_CONSTANT_ENCAPSED_STRING;
                $constantes[] = ['sitio' => $sitio, 'valor' => $esLiteral ? stripslashes(mb_substr($valor[1], 1, -1)) : null];
            }
        }
        if (count($constantes) === 0) {
            return ['ninguna constante UPLOAD_DIR en src/app: el censo no vio nada'];
        }

        $failures = [];
        $vistos = [];
        $cuenta = ['protegidas' => 0, 'publicas' => 0, 'sin_archivos' => 0];
        foreach ($constantes as $constante) {
            $valor = $constante['valor'];
            if ($valor === null) {
                $failures[] = "{$constante['sitio']}: UPLOAD_DIR no es un literal y no se puede resolver";
                continue;
            }
            $vistos[] = $valor;
            $real = realpath(append_to_path_system($uploadsDir, $valor));
            $protegida = $real !== false && in_array($real, $protegidas, true);
            $esPublica = array_key_exists($valor, $publicas);
            $esSinArchivos = array_key_exists($valor, $sinArchivos);
            if ((int) $protegida + (int) $esPublica + (int) $esSinArchivos > 1) {
                $failures[] = "{$constante['sitio']}: `{$valor}` está en más de un sitio a la vez (protegida, pública o sin archivos)";
            } elseif ($protegida) {
                $cuenta['protegidas']++;
            } elseif ($esPublica) {
                $cuenta['publicas']++;
            } elseif ($esSinArchivos) {
                $cuenta['sin_archivos']++;
            } else {
                $failures[] = "{$constante['sitio']}: la carpeta de subidas `{$valor}` no está protegida ni declarada en " . self::UPLOAD_DIRS_RELATIVE_PATH;
            }
        }
        foreach (array_merge(array_keys($publicas), array_keys($sinArchivos)) as $declarada) {
            if (!in_array((string) $declarada, $vistos, true)) {
                $failures[] = self::UPLOAD_DIRS_RELATIVE_PATH . ": `{$declarada}` ya no casa con ningún UPLOAD_DIR; la lista solo encoge";
            }
        }

        if (count($failures) === 0) {
            echoTerminal("\e[94mINFO:\e[39m " . count($constantes) . " carpeta(s) de subidas: {$cuenta['protegidas']} protegida(s), {$cuenta['publicas']} pública(s) y {$cuenta['sin_archivos']} sin archivos, declaradas.");
        }
        return $failures;
    }

    /**
     * Un `if/else` cuyas DOS ramas hacen lo mismo: la condición no decide nada.
     *
     * Se compara el ÁRBOL DE SINTAXIS, no el texto: con expresiones regulares esto es
     * indetectable —cambia el sangrado, el orden de los comentarios o una línea en blanco y
     * el patrón deja de casar— y además el `grep` de una máquina puede no ser el que crees.
     * Ver bloque S y LEY 16.
     *
     * Solo `if/else` de dos ramas SIN `elseif`. Un `elseif` que repite el cuerpo de otro es
     * legítimo —varias condiciones distintas con la misma salida, como un comparador—; dos
     * ramas de un `if/else` idénticas no lo son nunca: la condición se evalúa y se tira.
     *
     * @param string[] $files
     * @return string[]
     */
    protected static function checkTwinBranches(array $files): array
    {
        $autoload = self::toolchainAutoloadPath();
        if (!is_file($autoload)) {
            //NO SE APRUEBA EN SILENCIO: sin analizador la comprobación no miró nada (LEY 13).
            return ['no existe bin/tools/vendor/autoload.php: la comprobación de ramas gemelas NO se hizo. Ejecuta `composer install` en bin/tools.'];
        }
        require_once $autoload;
        if (!class_exists(\PhpParser\ParserFactory::class)) {
            return ['bin/tools/vendor no trae nikic/php-parser: la comprobación de ramas gemelas NO se hizo.'];
        }

        $base = rtrim(str_replace('\\', '/', basepath('')), '/');
        $parser = (new \PhpParser\ParserFactory())->createForNewestSupportedVersion();
        $printer = new \PhpParser\PrettyPrinter\Standard();

        $failures = [];
        $analysed = 0;

        foreach ($files as $relative) {
            $path = $base . '/' . $relative;
            $code = @file_get_contents($path);
            if (!is_string($code)) {
                continue;
            }
            try {
                $ast = $parser->parse($code);
            } catch (\Throwable $exception) {
                $failures[] = $relative . ' — no se pudo analizar: ' . $exception->getMessage();
                continue;
            }
            if ($ast === null) {
                continue;
            }
            $analysed++;

            //PROMOVIDAS: una propiedad suelta aquí la ve la comprobación 15 como fuera de sitio.
            $visitor = new class ($printer) extends \PhpParser\NodeVisitorAbstract {
                public function __construct(
                    private \PhpParser\PrettyPrinter\Standard $printer,
                    public array $found = []
                ) {
                }
                public function enterNode(\PhpParser\Node $node)
                {
                    if (!$node instanceof \PhpParser\Node\Stmt\If_) {
                        return null;
                    }
                    if ($node->else === null || count($node->elseifs) > 0) {
                        return null;
                    }
                    if ($this->printer->prettyPrint($node->stmts) === $this->printer->prettyPrint($node->else->stmts)) {
                        $this->found[] = $node->getStartLine();
                    }
                    return null;
                }
            };
            $traverser = new \PhpParser\NodeTraverser();
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            foreach ($visitor->found as $line) {
                $failures[] = $relative . ':' . $line . ' — las DOS ramas de este `if/else` hacen lo mismo:'
                    . ' la condición se evalúa y su respuesta se tira. O decide algo, o sobra.';
            }
        }

        $veredicto = count($failures) === 0
            ? 'ningún `if/else` con las dos ramas iguales'
            : count($failures) . ' con las dos ramas iguales';
        echoTerminal("\e[94mINFO:\e[39m {$analysed} archivo(s) analizados por sintaxis: {$veredicto}.");

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
