<?php

/**
 * FixWebmDurationTask.php
 */

namespace Terminal\Tasks;

use API\Adapters\FfmpegAudioAdapter;
use App\Model\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;

/**
 * FixWebmDurationTask.
 *
 * Repara el tiempo de duración interno de archivos WebM usando FFMpeg
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class FixWebmDurationTask extends TerminalTaskAbstract
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
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'fix-webm-duration';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];

        //Establecer propiedades
        $this->description = new StringArray([
            "Corrige iterativamente el tiempo de duración en metadatos de archivos guardados en formato WebM.\r\n",
            "\tParámetros soportados (mediante variables GET):\r\n",
            "\t  --updir=<directorio>\t\tDirectorio padre donde buscar \r\n",
            "\t  --glob=<pattern>\t\tPatrón de búsqueda (default **/*.webm)\r\n",
            "\t  --absolute\t\t\tTrata updir como ruta absoluta del sistema\r\n",
            "\t  --bkext=<ext>\t\t\tExtensión para el respaldo (default .bk.webm)\r\n",
            "\t  --fixext=<ext>\t\tExtensión de archivo reparado (default .fix.webm)\r\n",
            "\t  --restorebk\t\t\tRestaura todos los archivos de respaldo\r\n",
            "\t  --preserveall\t\t\tMantiene archivos intermedios listos\r\n",
            "\t  --preservefix\t\t\tMantiene el archivo .fix.webm original \r\n",
            "\t  --force\t\t\tReprocesa los archivos\r\n",
        ]);
        $this->route = "{$startRoute}/fix-webm-duration[/]";
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
        $titleTask = "Reparación de Duración WebM";
        $message = [
            "\e[35m=======================================\e[39m",
            "\e[36m*** {$titleTask} ***\e[39m",
            "\e[35m=======================================\e[39m",
        ];

        $cliArguments = TerminalData::getInstance()->arguments();

        try {
            // Emular el parser de banderas bash basándonos en GET params nativamente o $parameters
            // CLI pasa los argumentos como GET params a la ruta HTTP parseada internamente.
            $isForce = isset($cliArguments['--force']);
            $isAbsolute = isset($cliArguments['--absolute']);
            $isPreserveAll = isset($cliArguments['--preserveall']);
            $isRestoreBk = isset($cliArguments['--restorebk']);
            $isPreserveFix = isset($cliArguments['--preservefix']);
            $uploadsDirectory = $cliArguments['--updir'] ?? uniqid('NON_DEFINED_DIR_');
            $globPattern = $cliArguments['--glob'] ?? '**/*.webm';
            $bkExtension = $cliArguments['--bkext'] ?? '.bk.webm';
            $fixedExtension = $cliArguments['--fixext'] ?? '.fix.webm';

            if (!$isAbsolute) {
                $uploadsDirectory = str_replace(['//', '\\'], \DIRECTORY_SEPARATOR, getcwd() . DIRECTORY_SEPARATOR . $uploadsDirectory);
            }
            $uploadsDirectory = rtrim($uploadsDirectory, \DIRECTORY_SEPARATOR);
            $globPattern = $uploadsDirectory . \DIRECTORY_SEPARATOR  . trim($globPattern, '/');

            if (!file_exists($uploadsDirectory)) {
                $message[] = "\e[31mError:\e[39m El directorio no existe: {$uploadsDirectory}";
                echoTerminal(implode("\r\n", $message));
                return;
            }

            $message[] = "\e[94mINFO:\e[39m Escaneando {$globPattern}";
            echoTerminal(implode("\r\n", $message));
            $message = []; // Reiniciamos el búfer para imprimir progreso vivo

            $fileResults = glob($globPattern, GLOB_BRACE);

            // Instanciar nuestro Wrapper de procesamiento robusto FFMpeg
            $audioAdapter = new FfmpegAudioAdapter(null, null);

            $processedCount = 0;
            $errorCount = 0;
            $restoredCount = 0;

            foreach ($fileResults as $filePath) {

                // Ignorar procesar backups y fixes
                if (mb_strpos(basename($filePath), $fixedExtension) !== false || mb_strpos(basename($filePath), $bkExtension) !== false) {
                    continue;
                }

                $fileDir = dirname($filePath);
                $fileExtension = '.webm';
                $fileName = mb_substr(basename($filePath), 0, mb_strpos(basename($filePath), $fileExtension));

                $tmpFilePath = $fileDir . \DIRECTORY_SEPARATOR  . $fileName . '.tmp.wav';
                $bkFilePath = $fileDir . \DIRECTORY_SEPARATOR  . $fileName . $bkExtension;
                $fixedFilePath = $fileDir . \DIRECTORY_SEPARATOR  . $fileName . $fixedExtension;
                $processedFlagFilePath = $fileDir . \DIRECTORY_SEPARATOR  . $fileName . '.processed';

                if (!$isRestoreBk) {

                    if (file_exists($processedFlagFilePath) && !$isForce) {
                        //Ya se procesó
                        continue;
                    }

                    if (!file_exists($bkFilePath)) {
                        if (!@copy($filePath, $bkFilePath)) {
                            echoTerminal("\e[33mWARN:\e[39m No se pudo respaldar {$filePath}");
                            continue;
                        }
                    }

                    // Delegar al adaptador que usa Alchemy/Process
                    $success = $audioAdapter->fixWebmDuration($filePath, $tmpFilePath, $fixedFilePath);

                    if ($success) {
                        echoTerminal("\e[32mExito al procesar:\e[39m {$filePath}");
                        $processedCount++;

                        if (!$isPreserveAll) {
                            @unlink($filePath);
                            if (!$isPreserveFix) {
                                @rename($fixedFilePath, $filePath);
                            } else {
                                @copy($fixedFilePath, $filePath);
                            }
                        }

                        touch($processedFlagFilePath);

                    } else {
                        echoTerminal("\e[31mError procesando:\e[39m {$filePath}");
                        $errorCount++;
                    }

                } else {
                    if (file_exists($filePath) && file_exists($bkFilePath)) {
                        if (@unlink($filePath)) {
                            if (@copy($bkFilePath, $filePath)) {
                                @unlink($bkFilePath);
                                if (file_exists($processedFlagFilePath)) {
                                    @unlink($processedFlagFilePath);
                                }
                                echoTerminal("\e[32mExito al restaurar:\e[39m {$filePath}");
                                $restoredCount++;
                            }
                        }
                    }
                }
            }

            echoTerminal("\n\e[36m============================\e[39m");
            if ($isRestoreBk) {
                echoTerminal("\e[32mRestaurados:\e[39m {$restoredCount}");
            } else {
                echoTerminal("\e[32mReparados exitosamente:\e[39m {$processedCount}");
                if ($errorCount > 0) {
                    echoTerminal("\e[31mFallos:\e[39m {$errorCount}");
                }
            }

        } catch (\Exception $e) {
            $message[] = "\e[31mHa ocurrido un error inesperado al procesar: {$e->getMessage()}\e[39m";
            log_exception($e);
            echoTerminal(implode("\r\n", $message));
        }
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new FixWebmDurationTask($startRoute, $namePrefix);
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