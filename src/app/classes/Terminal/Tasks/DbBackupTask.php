<?php

/**
 * DbBackupTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use Ifsnop\Mysqldump\Mysqldump;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
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

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        //Mensajes de respuesta
        $responseText = "";

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
            $dbHost = $db->getHost();
            $dbUser = $db->getUsername();
            $dbPassword = $db->getPassword();
            $dbPassword = $dbPassword !== null ? $dbPassword : '';

            $dumpSettingsDefault = [
                'compress' => $gz ? Mysqldump::GZIP : Mysqldump::NONE,
                'add-drop-table' => true,
                'default-character-set' => Mysqldump::UTF8MB4,
                'routines' => $withRoutines,
                'single-transaction' => true,
                'skip-definer' => !$withDefiner,
                'disable-foreign-keys-check' => true,
                'complete-insert' => true,
                'no-data' => !$withData,
            ];

            if (!$withViews) {

                $pdo = new \PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, (string)$dbPassword);
                $queryViews = "SHOW FULL TABLES WHERE Table_type = 'VIEW'";
                $stmt = $pdo->query($queryViews);
                $viewsList = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                if (count($viewsList) > 0) {
                    $dumpSettingsDefault['exclude-tables'] = $viewsList;
                }
            }

            if ($dbUser !== null) {

                $dump = new Mysqldump("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPassword, $dumpSettingsDefault);
                $fileName = date('d-m-Y_H-i-s-A') . ($gz ? '.sql.gz' : '.sql');
                $dumpDirectory = basepath("dumps");
                $htaccess = "{$dumpDirectory}/.htaccess";

                if (!file_exists($dumpDirectory)) {
                    mkdir($dumpDirectory, 0777, true);
                }

                if (!file_exists($htaccess)) {
                    $htaccessContent = "<IfVersion >= 2.4>\r\n";
                    $htaccessContent .= "\tRequire all denied\r\n";
                    $htaccessContent .= "</IfVersion>\r\n";
                    $htaccessContent .= "<IfVersion < 2.4>\r\n";
                    $htaccessContent .= "\tOrder deny,allow\r\n";
                    $htaccessContent .= "\tDeny from All\r\n";
                    $htaccessContent .= "</IfVersion>";
                    @file_put_contents($htaccess, $htaccessContent);
                }

                try {

                    $output = "{$dumpDirectory}/{$fileName}";
                    $changePermissions = !file_exists($output);
                    $dump->start($output);

                    if ($changePermissions) {
                        chmod($output, 0777);
                    }

                    $responseText = "Operación exitosa\r\n";

                } catch (\Exception $e) {
                    $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
                    log_exception($e);
                }

            } else {
                $responseText = "No se pudo seleccionar ningún usuario para la conexión a la base de datos.\r\n";
            }

        } catch (MissingRequiredParamaterException $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        } catch (ParsedValueException $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        } catch (InvalidParameterValueException $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        } catch (\Exception $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        }

        systemOutFormatted($responseText);
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
