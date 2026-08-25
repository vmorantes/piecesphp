<?php
/**
 * TasksManager.php
 */

namespace PiecesPHP\ComposerTasks;

use Composer\Script\Event;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;

/**
 * TasksManager - Manejador de tareas composer
 *
 * @package     PiecesPHP\ComposerTasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class TasksManager
{
    /**
     * @param Event $event
     * @return void
     */
    public static function task(Event $event)
    {

        //COMPOSER_DEV_MODE lo pone Composer para sus scripts. Se lee del entorno y no del
        //$event para no depender de una clase que el análisis no ve. Ver T84.
        if (getenv('COMPOSER_DEV_MODE') !== '0') {
            self::setupDevTools();
        } else {
            echo "[PiecesPHP] Sin modo desarrollo: no se tocan las herramientas de bin/tools.\n";
        }

        $params_raw = $event->getArguments();
        $params = [];

        foreach ($params_raw as $param) {
            $param = explode('=', $param);
            if (count($param) == 2) {
                $name = $param[0];
                $value = $param[1];
                $params[$name] = $value;
            }
        }

        if (isset($params['task']) && count(explode('-', $params['task'])) > 0) {

            $taskName = explode('-', $params['task']);
            $isFirstWord = true;

            foreach ($taskName as $k => $i) {

                if ($isFirstWord) {
                    $taskName[$k] = mb_strtolower($i);
                    $isFirstWord = false;
                } else {
                    $i = mb_strtolower($i);
                    $iStrlen = mb_strlen($i);
                    if ($iStrlen > 1) {
                        $iFirstChar = mb_substr($i, 0, 1);
                        $iThen = mb_substr($i, 1, $iStrlen - 1);
                        $i = mb_strtoupper($iFirstChar) . $iThen;
                    } else {
                        $i = mb_strtoupper($i);
                    }
                    $taskName[$k] = $i;
                }

            }

            $params['task'] = implode('', $taskName);

        }

        if (isset($params['task']) && is_string($params['task'])) {
            $task = $params['task'];
            unset($params['task']);

            if (method_exists(TasksManager::class, $task)) {
                call_user_func(TasksManager::class . '::' . $task, $params);
            } else {
                echo "\r\nLa tarea $task no existe\r\n\r\n";
            }
        }
    }

    /**
     * Instala tools y crea symlink vendor-dev
     */
    protected static function setupDevTools(): void
    {
        $root = realpath(__DIR__ . '/../'); // src/
        $toolsDir = realpath($root . '/bin/tools');

        if ($toolsDir && is_dir($toolsDir)) {

            // 1. Instalar tools si no están instaladas
            $isInstalled = is_dir($toolsDir . '/vendor');
            chdir($toolsDir);
            $action = $isInstalled ? 'update' : 'install';
            echo $isInstalled
                ? "[PiecesPHP] Actualizando herramientas de desarrollo...\n"
                : "[PiecesPHP] Instalando herramientas de desarrollo...\n";
            self::runComposer($action);

            // 2. Instalar repositorio de phpstan para intellisense
            $phpstanRepoDir = $toolsDir . '/phpstan-src';
            if (!file_exists($phpstanRepoDir)) {
                mkdir($phpstanRepoDir, 0777, true);
            }
            if (is_dir($phpstanRepoDir) && !file_exists($phpstanRepoDir . '/phpstancode.zip')) {
                chdir($phpstanRepoDir);
                echo "[PiecesPHP] Instalando repositorio de phpstan para intellisense...\n";
                self::run('wget https://github.com/phpstan/phpstan-src/archive/refs/heads/2.2.x.zip -O phpstancode.zip');
                self::run('unzip phpstancode.zip -d .');
            } else {
                echo "[PiecesPHP] Repositorio de phpstan para intellisense ya instalado\n";
            }

        }
    }

    /**
     * Corre un comando y avisa si falla, en vez de tragárselo.
     *
     * @return void
     */
    protected static function run(string $command): void
    {
        $output = [];
        $status = 0;
        exec($command . ' 2>&1', $output, $status);
        if ($status !== 0) {
            echo "[PiecesPHP] AVISO: «{$command}» terminó con código {$status}.\n";
            foreach ($output as $line) {
                echo '    ' . $line . "\n";
            }
        }
    }

    /**
     * El PHP con el que hay que correr Composer, que NO es necesariamente el del PATH.
     *
     * Composer es un guion PHP y toma el `php` del PATH. Si ese está por debajo del piso
     * declarado, se niega a resolver y no toca nada. Es la misma trampa que motivó el selector
     * de `bin/cli`, y aquí llevaba fallando desde el 2026-08-20. Ver T81 y T84.
     *
     * @return string
     */
    protected static function phpBinary(): string
    {
        $declared = getenv('PCSPHP_PHP_BIN');
        if (is_string($declared) && $declared !== '') {
            return $declared;
        }

        foreach (['php8.5', 'php8.4'] as $candidate) {
            $found = trim((string) shell_exec('command -v ' . escapeshellarg($candidate) . ' 2>/dev/null'));
            if ($found !== '') {
                return $candidate;
            }
        }

        return 'php';
    }

    /**
     * Corre Composer con el binario correcto y NO se traga el fallo.
     *
     * @return void
     */
    protected static function runComposer(string $action): void
    {
        //Composer pone su propia ruta aquí cuando ejecuta un script: es el phar que está corriendo.
        $composer = (string) ($_SERVER['COMPOSER_BINARY'] ?? '');
        if ($composer === '' || !is_file($composer)) {
            $composer = trim((string) shell_exec('command -v composer 2>/dev/null'));
        }

        if ($composer === '') {
            self::childFailed($action, 1, ['No se encontró el ejecutable de Composer.']);
            return;
        }

        $command = escapeshellarg(self::phpBinary()) . ' ' . escapeshellarg($composer) . ' ' . $action
            . ' --no-interaction 2>&1';

        $output = [];
        $status = 0;
        exec($command, $output, $status);

        foreach ($output as $line) {
            echo '    ' . $line . "\n";
        }

        if ($status !== 0) {
            self::childFailed($action, $status, $output);
        }
    }

    /**
     * Un comando que termina bien mientras su hijo falla es una puerta omitida. Ver LEY 13.
     *
     * @param string[] $output
     * @return void
     */
    protected static function childFailed(string $action, int $status, array $output): void
    {
        $rule = str_repeat('=', 78);
        echo "\n{$rule}\n";
        echo "[PiecesPHP] FALLO: `composer {$action}` de bin/tools terminó con código {$status}.\n";
        echo "El instrumental de desarrollo NO se ha instalado ni actualizado.\n";
        echo "Si el mensaje de arriba habla de la versión de PHP, es el PHP del PATH, no el de la web:\n";
        echo "  PCSPHP_PHP_BIN=php8.5 composer install\n";
        echo "{$rule}\n\n";

        throw new \RuntimeException("composer {$action} de bin/tools falló con código {$status}.");
    }

    /**
     * @param array $args
     * @return void
     */
    public static function langsToExcels(array $args)
    {
        $langsDir = realpath(__DIR__ . '/../src/app/lang');
        $dirMapper = new DirectoryObject($langsDir, $langsDir);

        $dirMapper->process();

        $files = $dirMapper->getFiles();
        $langsData = [];
        $langs = [];

        foreach ($files as $file) {

            $name = $file->getBasename();
            $onlyName = pathinfo($name, \PATHINFO_FILENAME);
            $extension = pathinfo($name, \PATHINFO_EXTENSION);

            if ($extension == 'php') {

                $langsData[$onlyName] = include $file->getPath();
                $langs[] = $onlyName;

            }

        }

        foreach ($langs as $lang) {

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $first = true;
            $added = [];

            foreach ($langsData[$lang] as $group => $messages) {

                if ($first) {
                    $sheet = $spreadsheet->getActiveSheet();
                    $first = false;
                } else {
                    $sheet = $spreadsheet->createSheet();
                }

                $sheet->setTitle($group);

                $sheet->setCellValue('A1', 'Nombre');
                $sheet->setCellValue('B1', 'Mensaje');

                $row = 2;

                foreach ($messages as $name => $message) {

                    $validAdded = $group . '::' . $name;

                    if (!in_array($validAdded, $added)) {

                        $sheet->setCellValue("A{$row}", $name);
                        $sheet->setCellValue("B{$row}", $message);

                        $added[] = $validAdded;
                        $row++;

                    }

                }

            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($langsDir . "/{$lang}.xlsx");

        }

    }

}
