<?php

/**
 * DbBackupTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\BaseModel;
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
            $dumpDirectory = basepath("dumps");
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
                    'exclude_tables' => self::EXCLUDED_TABLES,
                    'where' => [
                        "TABLE_NAME" => 'WHERE COMPLETAMENTE FORMADO SIN LA PALABRA WHERE',
                    ],
                    //No cifres el password al exportar: se cifraba y NADIE lo descifraba al
                    //restaurar, asi que la restauracion dejaba a todos sin poder entrar.
                    'transformations' => [],
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
                    $responseText .= "Archivo INCOMPLETO: " . basename($outputPath) . "\r\n";
                    $exceptionToThrow = new \Exception($responseText);
                } elseif (!file_exists($outputPath)) {
                    $errors = implode("\n", $exporter->getErrors());
                    $responseText = "Ha ocurrido un error durante la exportación:\n{$errors}\r\n";
                    $exceptionToThrow = new \Exception($responseText);
                } else {

                    //Lo esperado sale de la BASE y lo escrito se lee del ARCHIVO: dos caminos.
                    $expected = array_diff($exporter->getTables(), self::EXCLUDED_TABLES);
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
                        $responseText .= "Archivo NO fiable: " . basename($outputPath) . "\r\n";
                        $exceptionToThrow = new \Exception($responseText);
                    } else {
                        if ($changePermissions) {
                            chmod($outputPath, 0664);
                        }
                        $responseText = "Operación exitosa\r\n";
                        $responseText .= "Archivo generado: " . basename($outputPath) . "\r\n";
                        $responseText .= "Verificado: " . count($expected) . " objeto(s) esperados, ";
                        $responseText .= count($expected) . " escrito(s).\r\n";
                        $success = true;
                    }

                }

            } catch (\Exception $e) {
                $exceptionToThrow = $e;
                $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
                log_exception($e);
            }

        } catch (\Exception $e) {

            $exceptionToThrow = $e;
            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        }

        systemOutFormatted($responseText);

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
