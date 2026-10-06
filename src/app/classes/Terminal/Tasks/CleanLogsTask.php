<?php

/**
 * CleanLogsTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Helpers\Directories\FilesIgnore;
use PiecesPHP\Core\Logs\ExpiredSessionsLog;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;

/**
 * CleanLogsTask.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 * @see https://misc.flogisoft.com/bash/tip_colors_and_formatting Colores para texto de terminal
 */
class CleanLogsTask extends TerminalTaskAbstract
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
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'clean-logs';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Limpia los archivos de logs.\r\n",
            "\tParámetros:\r\n",
            "\t  N/A",
        ]);
        $this->route = "{$startRoute}/clean-logs[/]";
        $this->controller = self::class . '::main';
        $this->name = $name;
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray($permissions);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    /**
     * Borra los `.eml` del buzón en disco (`MailDelivery::outboxDirectory()`) y dice cuántos. Solo los
     * `.eml`: el directorio y lo demás que tenga se quedan. Con `$directory`, el de una prueba.
     *
     * @param string|null $directory
     * @return int
     */
    public static function emptyOutbox(?string $directory = null): int
    {
        $outbox = $directory ?? MailDelivery::outboxDirectory();
        $deleted = 0;
        foreach (glob($outbox . '/*.eml') ?: [] as $eml) {
            if (@unlink($eml)) {
                $deleted++;
            }
        }
        return $deleted;
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {

        //Mensaje de respuesta
        $titleTask = "Eliminando archivos de logs";
        $message = [
            "\e[32m*** {$titleTask} ***\e[39m",
        ];

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            $baseLogsDirectory = basepath("app/logs");
            $oldsErrorLogsDirectory = basepath("app/logs/olds");
            $expiredSessionsLogsDirectory = basepath("app/logs/expired-sessions");
            $errorLogFile = basepath("app/logs/error.log.json");
            $errorLogPlanFile = basepath("app/logs/error.plain.log");
            $deprecationsLogFile = basepath("app/logs/deprecations.log");

            if (file_exists($baseLogsDirectory)) {

                //Log de errores
                file_put_contents($errorLogFile, '[]');
                chmod($errorLogFile, 0664);
                $message[] = "\e[34merror.log.json vaciado.\e[39m";

                //Log de errores planto
                file_put_contents($errorLogPlanFile, '');
                chmod($errorLogPlanFile, 0664);
                $message[] = "\e[34merror.plain.log vaciado.\e[39m";
                //Sin esta limpieza el log de deprecaciones crece sin límite en producción.
                file_put_contents($deprecationsLogFile, '');
                chmod($deprecationsLogFile, 0664);
                $message[] = "\e[34mdeprecations.log vaciado.\e[39m";

                //Anotaciones de traducciones faltantes. Su consumidor es `scan-missing-lang`,
                //y sin limpieza el directorio crece sin fin: llegó a 1.586 archivos.
                $missingLangDirectory = app_basepath('lang/missing-lang-messages');
                if (is_dir($missingLangDirectory)) {
                    $borrados = 0;
                    foreach (glob($missingLangDirectory . '/*/*/*.to-translate') ?: [] as $missingFile) {
                        if (@unlink($missingFile)) {
                            $borrados++;
                        }
                    }
                    //Los directorios de grupo e idioma quedan vacíos: se retiran de dentro afuera.
                    foreach (glob($missingLangDirectory . '/*/*', \GLOB_ONLYDIR) ?: [] as $langDirectory) {
                        @rmdir($langDirectory);
                    }
                    foreach (glob($missingLangDirectory . '/*', \GLOB_ONLYDIR) ?: [] as $groupDirectory) {
                        @rmdir($groupDirectory);
                    }
                    $message[] = "\e[34mmissing-lang-messages vaciado ({$borrados} anotaciones).\e[39m";
                }

                //El buzón en disco del correo retenido: cada .eml lleva el cuerpo EN CLARO, con sus adjuntos.
                $borradosEml = self::emptyOutbox();
                $message[] = "\e[34mmail-outbox vaciado ({$borradosEml} .eml).\e[39m";

                //Histórico de logs de errores
                $oldsErrorLogsHandler = new DirectoryObject($oldsErrorLogsDirectory);
                if ($oldsErrorLogsHandler->directoryExists()) {
                    $oldsErrorLogsHandler->process(new FilesIgnore([
                        '\.keep',
                    ]));
                    $oldsErrorLogsHandler->delete(false);
                    $message[] = "\e[34mLogs de errores antiguos vaciado.\e[39m";
                }

                //Sesiones caducadas: el registro nuevo se vacía como los demás.
                $expiredSessionsLogFile = ExpiredSessionsLog::path();
                if (is_file($expiredSessionsLogFile)) {
                    //RETORNO-IGNORADO: como los demás vaciados de arriba, y en UNA línea: la marca cubre solo las dos siguientes.
                    file_put_contents($expiredSessionsLogFile, '');
                    chmod($expiredSessionsLogFile, 0664);
                    $message[] = "\e[34m" . ExpiredSessionsLog::FILE_NAME . " vaciado.\e[39m";
                }
                $expiredSessionsRotated = ExpiredSessionsLog::rotatedPath();
                if (is_file($expiredSessionsRotated) && @unlink($expiredSessionsRotated)) {
                    $message[] = "\e[34m" . ExpiredSessionsLog::ROTATED_FILE_NAME . " retirado.\e[39m";
                }

                //La carpeta del formato viejo: sus `.json` llevan un token y NO se borran aquí
                //(ADR 0039 §4). Solo se retira la carpeta si ya está vacía.
                if (is_dir($expiredSessionsLogsDirectory)) {
                    $quedan = array_values(array_filter(
                        scandir($expiredSessionsLogsDirectory) ?: [],
                        static fn (string $entrada): bool => !in_array($entrada, ['.', '..', '.keep'], true)
                    ));
                    if (count($quedan) === 0) {
                        $message[] = @rmdir($expiredSessionsLogsDirectory)
                            ? "\e[34mexpired-sessions/ estaba vacía y se retiró.\e[39m"
                            : "\e[33mexpired-sessions/ está vacía pero no se pudo retirar.\e[39m";
                    } else {
                        $message[] = "\e[33mexpired-sessions/ conserva " . count($quedan) . " archivo(s) del formato viejo: llevan un token y NO se borran desde aquí. Revísela y vacíela a mano.\e[39m";
                    }
                }

            }

        } catch (\Exception $e) {

            $message[] = "\e[31mHa ocurrido un error: {$e->getMessage()}\e[39m";
            log_exception($e);

        }

        $message[] = "\e[32m*** {$titleTask}, tarea finalizada ***\e[39m";
        if (count($message) > 1) {
            echoTerminal(implode("\r\n", $message));
        }
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new CleanLogsTask($startRoute, $namePrefix);
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
