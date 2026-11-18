<?php

/**
 * DbBackupTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\Backups\BackupPolicy;
use PiecesPHP\Core\BaseEventDispatcher;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\Database;
use PiecesPHP\Core\Database\Export\Enums\DataStyle;
use PiecesPHP\Core\Database\Export\Enums\TableStyle;
use PiecesPHP\Core\Database\Export\Exporter;
use PiecesPHP\Core\Database\Export\Plugins\FileOutput;
use PiecesPHP\Core\Database\Export\Plugins\GzipFileOutput;
use PiecesPHP\Core\Database\Export\Plugins\SqlFormat;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * DbBackupTask.
 *
 * Respaldar toda la base de datos
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 * @see https://misc.flogisoft.com/bash/tip_colors_and_formatting Colores para texto de terminal
 */
class DbBackupTask extends TerminalTaskAbstract
{

    /**
     * Tablas que NO entran en el respaldo, y por qué.
     *
     * Vive aquí y no dentro del array de opciones para que la suite pueda leerla en vez de
     * copiarla: una lista duplicada se queda corta en uno de los dos sitios. Ver LEY 11.
     *
     * @var string[]
     */
    const EXCLUDED_TABLES = [
        //Andamiaje de `unit-tests:core/database-exporter`: se crea y se tira en cada corrida.
        'pcs_unit_tests_core_database_exporter_v1',
    ];

    /**
     * El archivo del último respaldo CORRECTO de este proceso, o null.
     *
     * Lo lee el cronjob para pasárselo a la conservación, que nunca borra el recién escrito.
     * Se pone a null al empezar cada pasada: si esta falla, nadie puede leer el de la
     * anterior y creer que acaba de escribirse.
     *
     * @var string|null
     */
    protected static ?string $lastWrittenFile = null;

    /**
     * Las vistas rotas que omitió el último respaldo correcto de este proceso.
     *
     * Se vacía al empezar cada pasada, como lastWrittenFile(): la tarea programada la lee para dejarla en su registro.
     *
     * @var string[]
     */
    protected static array $lastSkippedViews = [];

    /**
     * La ruta del último respaldo correcto de este proceso, o null si no hubo ninguno.
     *
     * @return string|null
     */
    public static function lastWrittenFile(): ?string
    {
        return self::$lastWrittenFile;
    }

    /**
     * Las vistas rotas omitidas por el último respaldo correcto de este proceso.
     *
     * @return string[]
     */
    public static function lastSkippedViews(): array
    {
        return self::$lastSkippedViews;
    }

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        //Procesar entrada
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'db-backup';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Respalda la base de datos por defecto.\r\n",
            "\tParámetros:\r\n",
            "\t  gz (yes|no) define si se comprime o no. Por defecto: yes\r\n",
            "\t  data (yes|no) incluir datos de las tablas. Por defecto: yes\r\n",
            "\t  routines (yes|no) incluir funciones y procedimientos. Por defecto: yes\r\n",
            "\t  views (yes|no) incluir vistas. Por defecto: yes\r\n",
            "\t  definer (yes|no) incluir DEFINER en los objetos. Por defecto: no\r\n",
        ]);
        $this->route = "{$startRoute}/db-backup[/]";
        $this->controller = self::class . '::main';
        $this->name = $name;
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray($permissions);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = [], bool $throwExceptions = false): bool
    {

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        //Mensajes de respuesta
        $responseText = "";
        $success = false;
        $exceptionToThrow = null;
        $brokenViewsNotice = "";
        self::$lastWrittenFile = null;
        self::$lastSkippedViews = [];

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Información de los parámetros
            $gz = TerminalData::instance()->getArgument('gz', 'yes') === 'yes';
            $withData = TerminalData::instance()->getArgument('data', 'yes') === 'yes';
            $withRoutines = TerminalData::instance()->getArgument('routines', 'yes') === 'yes';
            $withViews = TerminalData::instance()->getArgument('views', 'yes') === 'yes';
            $withDefiner = TerminalData::instance()->getArgument('definer', 'no') === 'yes';

            $db = (new BaseModel())->getDatabase();
            if ($db === null) {
                //Sin conexión no hay respaldo, y decirlo es mejor que reventar más abajo.
                throw new \Exception('No hay conexión a la base de datos: no se puede respaldar.');
            }
            $dbName = $db->getDatabaseName();

            // Preparar Exportador
            $format = new SqlFormat();
            $exporter = new Exporter($db, $dbName);
            $exporter->setFormatPlugin($format);

            // Seleccionar plugin de salida
            $outputPlugin = $gz ? new GzipFileOutput() : new FileOutput();
            $exporter->setOutputPlugin($outputPlugin);

            // Nombre y ruta del archivo
            $fileName = date('d-m-Y_H-i-s-A') . ($gz ? '.sql.gz' : '.sql');
            $dumpDirectory = BackupPolicy::dumpsDirectory();
            $htaccess = "{$dumpDirectory}/.htaccess";
            $outputFile = "{$dumpDirectory}/{$fileName}";

            $umask = umask(0);
            try {
                if (!file_exists($dumpDirectory)) {
                    mkdir($dumpDirectory, 0755, true);
                }
            } finally {
                umask($umask);
            }

            if (!file_exists($htaccess)) {
                $htaccessContent = "<IfVersion >= 2.4>\r\n";
                $htaccessContent .= "\tRequire all denied\r\n";
                $htaccessContent .= "</IfVersion>\r\n";
                $htaccessContent .= "<IfVersion < 2.4>\r\n";
                $htaccessContent .= "\tOrder deny,allow\r\n";
                $htaccessContent .= "\tDeny from All\r\n";
                $htaccessContent .= "</IfVersion>";
                file_put_contents($htaccess, $htaccessContent);
            }

            //Una vista sin sus tablas (un módulo apagado que borró las suyas) tumbaba el respaldo
            //entero con 1356: se omite y se avisa. Cualquier otro error de una vista sigue cayendo. Ver H399.
            $tables = $exporter->getTables();
            $brokenViews = [];
            if ($withViews) {
                $brokenViews = self::brokenViews($db, $format, array_diff($tables, self::EXCLUDED_TABLES));
                foreach ($brokenViews as $brokenView => $reason) {
                    $brokenViewsNotice .= "Vista rota omitida: {$brokenView} ({$reason})\r\n";
                }
            }
            $composition = self::backupComposition($tables, self::EXCLUDED_TABLES, array_keys($brokenViews));

            try {

                $changePermissions = !file_exists($outputFile);

                // Ejecutar exportación
                $exported = $exporter->export([
                    'filename' => $outputFile,
                    'include_data' => $withData,
                    'include_views' => $withViews,
                    'routines' => $withRoutines,
                    'remove_definer' => !$withDefiner,
                    //Las tablas ya iban con DROP+CREATE y las rutinas no: un volcado que no se
                    //puede volver a aplicar no es un respaldo. Ver T96.
                    'drop_if_exists_on_functions' => true,
                    'table_style' => TableStyle::DROP_CREATE,
                    'data_style' => DataStyle::INSERT,
                    'single_transaction' => true,
                    'auto_increment' => true,
                    'triggers' => true,
                    'exclude_tables' => $composition['exclude_tables'],
                    //El `where` solo entra en el SELECT de los datos: la estructura sale entera.
                    'where' => array_fill_keys(BackupPolicy::dataExcludedTables(), '1=0'),
                    //Solo las columnas de BackupPolicy::transformColumn(). El password NO se cifra: nadie lo
                    //descifraba al restaurar, asi que la restauracion dejaba a todos sin poder entrar.
                    'transformations' => BackupPolicy::transformations(),
                ]);
                //Si el plugin no dice qué escribió, se informa sobre el que se le pidió.
                $outputPath = $outputPlugin->getFilename();
                $outputPath = is_string($outputPath) ? $outputPath : $outputFile;

                //EL VALOR DEVUELTO SE LEE. `file_exists` no prueba nada: el archivo existe
                //igual aunque la exportación reviente a mitad. Ver T141.
                if ($exported !== true) {
                    $errors = implode("\n", $exporter->getErrors());
                    $responseText = "El respaldo FALLÓ: el exportador devolvió false.\r\n";
                    $responseText .= $errors !== '' ? "Motivo:\n{$errors}\r\n" : "Sin motivo declarado.\r\n";
                    $responseText .= "Archivo INCOMPLETO: " . self::setAsideFailed($outputPath) . "\r\n";
                    $exceptionToThrow = new \Exception($responseText);
                } elseif (!file_exists($outputPath)) {
                    $errors = implode("\n", $exporter->getErrors());
                    $responseText = "Ha ocurrido un error durante la exportación:\n{$errors}\r\n";
                    $exceptionToThrow = new \Exception($responseText);
                } else {

                    //Lo esperado sale de la BASE y lo escrito se lee del ARCHIVO: dos caminos.
                    //Las rotas omitidas no se esperan: se cuentan aparte, no como ausencias.
                    $expected = $composition['expected'];
                    if (!$withViews) {
                        //`views=no` es una petición legítima: lo que no se pidió no se echa
                        //en falta. Medido: sin esto, `views=no` reportaba 5 ausencias falsas.
                        $expected = array_filter($expected, static fn (string $o): bool => !$format->isView($db, $o));
                    }
                    $expected = array_values($expected);
                    $written = self::objectsInDump($outputPath);
                    $missing = array_values(array_diff($expected, $written));
                    sort($missing);

                    if (count($missing) > 0) {
                        $responseText = "El respaldo está INCOMPLETO: faltan " . count($missing);
                        $responseText .= " de " . count($expected) . " objeto(s).\r\n";
                        $responseText .= "Faltan: " . implode(', ', $missing) . "\r\n";
                        $responseText .= "Archivo NO fiable: " . self::setAsideFailed($outputPath) . "\r\n";
                        $exceptionToThrow = new \Exception($responseText);
                    } else {
                        if ($changePermissions) {
                            chmod($outputPath, 0664);
                        }
                        $responseText = "Operación exitosa\r\n";
                        $responseText .= "Archivo generado: " . basename($outputPath) . "\r\n";
                        $responseText .= "Verificado: " . count($expected) . " objeto(s) esperados, ";
                        $responseText .= count($expected) . " escrito(s)";
                        $responseText .= count($brokenViews) > 0 ? "; " . count($brokenViews) . " vista(s) rota(s) omitida(s).\r\n" : ".\r\n";
                        $success = true;
                        self::$lastWrittenFile = $outputPath;
                        self::$lastSkippedViews = array_keys($brokenViews);
                        $size = @filesize($outputPath);
                        BaseEventDispatcher::dispatch('Backups', 'BackupCreated', [
                            'file' => $outputPath,
                            'size' => is_int($size) ? $size : null,
                            'tables' => count($expected),
                            'skipped_views' => array_keys($brokenViews),
                        ]);
                    }

                }

            } catch (\Exception $e) {
                $exceptionToThrow = $e;
                $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
                $writtenPath = $outputPlugin->getFilename();
                $writtenPath = is_string($writtenPath) ? $writtenPath : $outputFile;
                //Si ya había salido bien (lanzó un escuchador de BackupCreated), el archivo es bueno y no se aparta.
                if (!$success && file_exists($writtenPath)) {
                    $responseText .= "Archivo a medias: " . self::setAsideFailed($writtenPath) . "\r\n";
                }
                log_exception($e);
            }

        } catch (\Exception $e) {

            $exceptionToThrow = $e;
            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        }

        if (!$success) {
            //Un solo punto para los cuatro caminos de fallo: sin conexión, el exportador en
            //false, el archivo que no aparece y el volcado incompleto.
            BaseEventDispatcher::dispatch('Backups', 'BackupFailed', [
                'reason' => trim($responseText),
            ]);
        }

        systemOutFormatted($brokenViewsNotice . $responseText);

        if ($throwExceptions && $exceptionToThrow !== null) {
            throw $exceptionToThrow;
        }

        //Un respaldo que falla tiene que NOTARSE fuera del proceso. Mismo trato que
        //`db-restore`, y solo en terminal: por HTTP el `exit` truncaría la respuesta.
        if (!$success && TerminalData::getInstance()->isTerminal()) {
            exit(1);
        }

        return $success;
    }

    /**
     * Las vistas que no se pueden volcar porque faltan sus tablas, con su motivo.
     *
     * Se pregunta con `SHOW COLUMNS`, la misma consulta con la que el exportador escribe la vista
     * provisional: lo que pasa aquí es lo que pasaría allí. Un 1356 solo omite la vista si de verdad
     * falta una tabla o vista de su definición (missingViewTables()); si están todas, el 1356 es de permisos
     * o de una columna, y se relanza. Cualquier otro error se relanza también: el respaldo cae.
     * Una vista sobre otra vista rota nombra una que existe: por eso las rotas cuentan como ausentes y
     * el recorrido se repite hasta que ninguna vuelta añade otra, con tantas vueltas como vistas fallan.
     *
     * @param Database $db
     * @param SqlFormat $format
     * @param string[] $objects Tablas y vistas que entrarían en el respaldo.
     * @return array<string,string> Nombre de la vista => motivo.
     * @throws \PDOException
     */
    protected static function brokenViews(Database $db, SqlFormat $format, array $objects): array
    {
        $failing = [];
        foreach ($objects as $object) {
            if (!$format->isView($db, $object)) {
                continue;
            }
            try {
                $db->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $object) . '`');
            } catch (\PDOException $e) {
                if (!self::isBrokenViewError($e)) {
                    throw $e;
                }
                $failing[$object] = $e;
            }
        }
        if ($failing === []) {
            return [];
        }

        $databaseName = (string) $db->query('SELECT DATABASE()')->fetchColumn();
        $existingTables = $db->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchAll(\PDO::FETCH_COLUMN);
        //Una función de la base nombrada sin su «(» no es una tabla que falte: ante la duda, existe.
        $routines = $db->query('SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE()')->fetchAll(\PDO::FETCH_COLUMN);
        $definitionQuery = $db->prepare('SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $definitions = [];
        foreach (array_keys($failing) as $view) {
            $definition = $definitionQuery->execute([$view]) ? $definitionQuery->fetchColumn() : false;
            $definitions[$view] = is_string($definition) ? $definition : '';
        }

        $broken = [];
        for ($round = 0; $round < count($failing); $round++) {
            //La foto es de la vuelta entera: el orden de las vistas no cambia el resultado.
            $present = array_merge(array_diff($existingTables, array_keys($broken)), $routines);
            $added = [];
            foreach ($failing as $view => $e) {
                if (isset($broken[$view])) {
                    continue;
                }
                $missing = self::missingViewTables($definitions[$view], $databaseName, $present);
                if ($missing !== []) {
                    $added[$view] = 'error 1356; faltan: ' . implode(', ', $missing);
                }
            }
            if ($added === []) {
                break;
            }
            $broken = array_merge($broken, $added);
        }
        foreach ($failing as $view => $e) {
            if (!isset($broken[$view])) {
                throw $e;
            }
        }
        return $broken;
    }

    /**
     * Las tablas de la base actual que nombra la definición de una vista y no están entre las existentes.
     *
     * MariaDB y MySQL guardan la definición con cada referencia completa: `base`.`tabla` y `base`.`tabla`.`columna`.
     * Solo cuentan las que empiezan por la base actual: un alias (`t1`.`columna`) o una base ajena no son
     * prueba de que falte nada, y una definición vacía (sin SHOW VIEW) tampoco. Sin prueba, la lista sale
     * vacía y el respaldo cae: omitir una vista sana daría por bueno un respaldo incompleto.
     * Las mayúsculas no distinguen, por lo mismo: ante la duda, «existe».
     * Lo que va seguido de «(» es una llamada (`base`.`funcion`(…)), no una tabla.
     *
     * @param string $definition La de information_schema.VIEWS.VIEW_DEFINITION.
     * @param string $databaseName La base actual.
     * @param array<int,mixed> $existingTables Tablas, vistas sanas y rutinas de la base actual.
     * @return string[] Las que faltan, sin repetir, en el orden de la definición.
     */
    public static function missingViewTables(string $definition, string $databaseName, array $existingTables): array
    {
        $existing = [];
        foreach ($existingTables as $table) {
            if (is_string($table)) {
                $existing[mb_strtolower($table)] = true;
            }
        }
        $database = mb_strtolower($databaseName);

        $missing = [];
        $matched = preg_match_all('/`((?:[^`]|``)++)`\s*\.\s*`((?:[^`]|``)++)`(?!\s*\()/u', $definition, $matches, PREG_SET_ORDER);
        if ($matched === false || $database === '') {
            return [];
        }
        foreach ($matches as $match) {
            if (mb_strtolower(str_replace('``', '`', $match[1])) !== $database) {
                continue;
            }
            $table = str_replace('``', '`', $match[2]);
            if (!isset($existing[mb_strtolower($table)])) {
                $missing[$table] = true;
            }
        }
        return array_keys($missing);
    }

    /**
     * Qué se excluye del volcado y qué se espera encontrar en él, desde las mismas listas.
     *
     * Las dos salen de aquí para que no puedan separarse: una vista rota que se excluye pero se espera
     * daría el respaldo por incompleto, y una que se espera sin excluir lo tumbaría en el exportador.
     *
     * @param string[] $tables Tablas y vistas de la base.
     * @param string[] $excluded Las de EXCLUDED_TABLES.
     * @param string[] $brokenViews Las vistas rotas que se omiten.
     * @return array{exclude_tables: string[], expected: string[]}
     */
    public static function backupComposition(array $tables, array $excluded, array $brokenViews): array
    {
        return [
            'exclude_tables' => array_values(array_unique(array_merge($excluded, $brokenViews))),
            'expected' => array_values(array_diff($tables, $excluded, $brokenViews)),
        ];
    }

    /**
     * Si el error es el de MySQL/MariaDB 1356: la vista referencia tablas o columnas no válidas, o le faltan permisos.
     *
     * Es solo el primer filtro: el mismo 1356 sale por un permiso que falta, así que brokenViews()
     * exige además que falte una tabla de la definición. Un permiso de otro tipo o una base caída
     * no pasan ni este filtro: omitirlos daría por bueno un respaldo al que le falta algo.
     *
     * @param \Throwable $e
     * @return bool
     */
    public static function isBrokenViewError(\Throwable $e): bool
    {
        if (!$e instanceof \PDOException || !is_array($e->errorInfo)) {
            return false;
        }
        return ($e->errorInfo[0] ?? null) === 'HY000' && (int) ($e->errorInfo[1] ?? 0) === 1356;
    }

    /**
     * Aparta el archivo de un respaldo fallido: lo renombra a `<nombre>.fallido` en la misma carpeta.
     *
     * Con su nombre de respaldo, `BackupRotation::latestDate()` lo contaría como el último: `isDue()` no
     * reintentaría hasta pasado un intervalo entero y la rotación podría conservarlo en lugar de uno bueno.
     * Si el renombrado falla no se lanza nada: el respaldo ya ha fallado, y el texto lo dice.
     *
     * @param string $path
     * @return string El nombre con el que queda, para el texto del fallo.
     */
    protected static function setAsideFailed(string $path): string
    {
        $failedPath = $path . '.fallido';
        //Con @: un aviso de rename() aborta en local, y aquí el fallo se informa en el texto.
        if (@rename($path, $failedPath)) {
            return basename($failedPath);
        }
        return basename($path) . ' (NO se pudo renombrar a ' . basename($failedPath) . ': sigue contando como el último respaldo; renómbrelo o bórrelo a mano)';
    }

    /**
     * Los objetos que un volcado CONTIENE de verdad, leídos del archivo.
     *
     * Camino distinto al del exportador a propósito: si se le preguntara a él, la
     * comprobación sería el productor confirmándose a sí mismo. Ver LEY 19.
     *
     * @param string $path Ruta del volcado, comprimido o no.
     * @return string[] Nombres de tabla y de vista hallados.
     */
    protected static function objectsInDump(string $path): array
    {
        $content = '';

        if (mb_substr($path, -3) === '.gz') {
            $handle = @gzopen($path, 'rb');
            if ($handle === false) {
                return [];
            }
            while (!gzeof($handle)) {
                $chunk = gzread($handle, 262144);
                if ($chunk === false) {
                    break;
                }
                $content .= $chunk;
            }
            gzclose($handle);
        } else {
            $content = (string) @file_get_contents($path);
        }

        //DOS FORMAS, y las dos hacen falta: las tablas salen con `IF NOT EXISTS` y las vistas
        //se emiten antes como tabla de mentira, sin él. Medido sobre un volcado real.
        $found = [];
        if (preg_match_all('/^CREATE TABLE (?:IF NOT EXISTS )?`([^`]+)`/m', $content, $matches) > 0) {
            $found = $matches[1];
        }

        return array_values(array_unique($found));
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new DbBackupTask($startRoute, $namePrefix);
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
