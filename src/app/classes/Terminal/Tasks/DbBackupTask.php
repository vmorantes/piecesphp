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
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
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
            "\t  gz (yes|no) define si se comprime o no. Por defecto: yes",
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

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'gz',
                true,
                function ($value) {
                    return is_string($value) || is_bool($value);
                },
                true,
                function ($value) {
                    return is_string($value) ? mb_strtolower(clean_string($value)) === 'yes' : $value === true;
                }
            ),
        ]);

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(TerminalData::getInstance()->arguments());

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        //Mensajes de respuesta
        $responseText = "";

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información de los parámetros
            /**
             * @var string $gz
             */
            $gz = $expectedParameters->getValue('gz');

            $db = (new BaseModel())->getDatabase();
            $dbName = $db->getDatabaseName();
            $dbHost = $db->getHost();
            $dbUser = $db->getUsername();
            $dbPassword = $db->getPassword();
            $dbPassword = $dbPassword !== null ? $dbPassword : '';

            $dumpSettingsDefault = [
                'compress' => $gz ? Mysqldump::GZIP : Mysqldump::NONE,
                'add-drop-table' => true,
                'default-character-set' => Mysqldump::UTF8,
                'routines' => true,
                'single-transaction' => true,
                'skip-definer' => true,
                'disable-foreign-keys-check' => true,
            ];

            if ($dbUser !== null) {

                $dump = new Mysqldump("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPassword, $dumpSettingsDefault);
                $fileName = date('d-m-Y_H-i-s-A') . ($gz ? '.sql.gz' : '.sql');
                $dumpDirectory = basepath("dumps");
                $htaccess = "{$dumpDirectory}/.htaccess";

                if (!file_exists($dumpDirectory)) {
                    mkdir($dumpDirectory, 0777, true);
                }

                if (!file_exists($htaccess)) {
                    $htaccessContent = "<IfVersion > 2.4>\r\n";
                    $htaccessContent .= "\tDeny from All\r\n";
                    $htaccessContent .= "</IfVersion>\r\n";
                    $htaccessContent .= "<IfVersion <= 2.4>\r\n";
                    $htaccessContent .= "\tRequire all denied\r\n";
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

        echoTerminal($responseText);
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