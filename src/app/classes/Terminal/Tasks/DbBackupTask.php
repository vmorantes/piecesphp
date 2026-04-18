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
            $dbName = $db->getDatabaseName();

            // Preparar Exportador
            $exporter = new Exporter($db, $dbName);
            $exporter->setFormatPlugin(new SqlFormat());

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
                $exporter->export([
                    'filename' => $outputFile,
                    'include_data' => $withData,
                    'include_views' => $withViews,
                    'routines' => $withRoutines,
                    'remove_definer' => !$withDefiner,
                    'table_style' => TableStyle::DROP_CREATE,
                    'data_style' => DataStyle::INSERT,
                    'single_transaction' => true,
                    'auto_increment' => true,
                    'triggers' => true,
                    'exclude_tables' => [
                        'pcs_unit_tests_core_database_exporter_v1',
                    ],
                    'where' => [
                        "TABLE_NAME" => 'WHERE COMPLETAMENTE FORMADO SIN LA PALABRA WHERE',
                    ],
                    'transformations' => [
                        UsersModel::TABLE => [
                            'password' => function ($val) {
                                return BaseHashEncryption::encrypt($val, 'ENCRYPTION_KEY');
                            },
                        ],
                    ],
                ]);
                $outputPath = $outputPlugin->getFilename();

                if (file_exists($outputPath)) {
                    if ($changePermissions) {
                        chmod($outputPath, 0644);
                    }
                    $responseText = "Operación exitosa\r\n";
                    $responseText .= "Archivo generado: " . basename($outputPath) . "\r\n";
                    $success = true;
                } else {
                    $errors = implode("\n", $exporter->getErrors());
                    $responseText = "Ha ocurrido un error durante la exportación:\n{$errors}\r\n";
                    $exceptionToThrow = new \Exception($responseText);
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

        return $success;
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
