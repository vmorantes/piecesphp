<?php
/**
 * TasksManager.php
 */

namespace PiecesPHP\ComposerTasks;

use Composer\Script\Event;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Helpers\Directories\FilesIgnore;

/**
 * TasksManager - Manejador de tareas composer
 *
 * @package     PiecesPHP\ComposerTasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class TasksManager
{
    /**
     * task
     *
     * @param Event $event
     * @return void
     */
    public static function task(Event $event)
    {
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

        if (isset($params['task']) && is_string($params['task'])) {

            $task = $params['task'];
            unset($params['task']);

            if (method_exists(TasksManager::class, $task)) {
                call_user_func(TasksManager::class . '::' . $task, $params);
            } else {
                echo "\r\nLa tarea $task no existe\r\n\r\n";
            }
        } else {
            echo "\r\nTareas disponibles:\r\n\r\n";
            echo "- bundle [zip=yes|no] [verbose=yes|no] \r\n";
            echo "- srcChmod [verbose=yes|no] \r\n";
            echo "- langsToExcels \r\n";
            echo "\r\n";
        }
    }

    /**
     * langsToExcels
     *
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

    /**
     * bundle
     *
     * @param array $args
     * @return void
     */
    public static function bundle(array $args)
    {

        $toZip = isset($args['zip']) ? $args['zip'] : 'no';
        $toZip = $toZip == 'yes' ? true : false;

        $verbose = isset($args['verbose']) ? $args['verbose'] : 'no';
        $verbose = $verbose == 'yes' ? true : false;

        $directory = new DirectoryObject(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src');
        $ignored = [];

        $directory->process(new FilesIgnore([
            'src/vendor',
            'src/node_modules',
            'gulpfile.js',
            'package.json',
            'package-lock.json',
            'composer.lock',
            'sass',
        ]), $ignored);

        if ($verbose) {
            echo "Ignored\r\n";
            var_dump($ignored);
        }

        $result = $directory->copyTo(__DIR__ . DIRECTORY_SEPARATOR . 'output/bundle', $toZip);

        if ($verbose) {
            echo "Copied\r\n";
            var_dump($result);
        }
    }

    /**
     * srcChmod
     *
     * @param array $args
     * @return void
     */
    public static function srcChmod(array $args)
    {

        $verbose = isset($args['verbose']) ? $args['verbose'] : 'no';
        $verbose = $verbose == 'yes' ? true : false;

        $directory = new DirectoryObject(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src');
        $ignored = [];

        $directory->process(new FilesIgnore([
            'INCLUDE_EXPR::^src/app$',
            'INCLUDE_EXPR::^src/app/logs$',
            'src/app',
            'src/statics/plugins',
            'src/vendor',
            'src/node_modules',
            'gulpfile.js',
            'package.json',
            'package-lock.json',
            'composer.lock',
            'sass',
        ]), $ignored);

        if ($verbose) {
            echo "Ignored\r\n";
            var_dump($ignored);
        }

        $result = $directory->chmod(0777, DirectoryObject::CHMOD_LEVEL_ALL, true);

        if ($verbose) {
            echo "Changed\r\n";
            var_dump($result);
        }
    }
}
