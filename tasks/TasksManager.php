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

        self::setupDevTools();

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
            if (!$isInstalled) {
                echo "[PiecesPHP] Instalando herramientas de desarrollo...\n";
                shell_exec('composer install');
            } else {
                echo "[PiecesPHP] Actualizando herramientas de desarrollo...\n";
                shell_exec('composer update');
            }

            // 2. Instalar repositorio de phpstan para intellisense
            $phpstanRepoDir = $toolsDir . '/phpstan-src';
            if (!file_exists($phpstanRepoDir)) {
                mkdir($phpstanRepoDir, 0777, true);
            }
            if (is_dir($phpstanRepoDir) && !file_exists($phpstanRepoDir . '/phpstancode.zip')) {
                chdir($phpstanRepoDir);
                echo "[PiecesPHP] Instalando repositorio de phpstan para intellisense...\n";
                shell_exec('wget https://github.com/phpstan/phpstan-src/archive/refs/heads/2.2.x.zip -O phpstancode.zip');
                shell_exec('unzip phpstancode.zip -d .');
            } else {
                echo "[PiecesPHP] Repositorio de phpstan para intellisense ya instalado\n";
            }

        }
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
