<?php

/**
 * TerminalController.php
 */

namespace Terminal\Controllers;

use App\Controller\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\TerminalData;

/**
 * TerminalController.
 *
 * @package     Terminal\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 * @see https://misc.flogisoft.com/bash/tip_colors_and_formatting Colores para texto de terminal
 */
class TerminalController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = '';
    /**
     * @var string
     */
    protected static $baseRouteName = 'terminal';

    public function __construct()
    {
        parent::__construct();
        $this->model = new BaseModel();
    }

    /**
     * Verificar si una ruta es permitida
     *
     * @param string $name
     * @return bool
     */
    public static function allowedRoute(string $name)
    {
        $route = self::routeName($name, true);
        $allow = strlen($route) > 0;
        return $allow;
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * @param string $name
     * @param string $route
     * @return bool
     */
    private static function _allowedRoute(string $name, string $route)
    {

        $allow = strlen($route) > 0;

        if ($allow) {

            if ($name == 'sample') { //do something
            }

        }

        return $allow;
    }

    /**
     * Obtener URL de una ruta
     *
     * @param string $name
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(?string $name = null, bool $silentOnNotExists = false)
    {

        $simpleName = !is_null($name) ? $name : '';
        $name = self::routeID($name);

        $allowed = false;
        $current_user = getLoggedFrameworkUser();

        if ($current_user !== null) {
            $allowed = Roles::hasPermissions($name, $current_user->type);
        } else {
            $allowed = true;
        }

        $route = '';

        if ($allowed) {
            $route = get_route(
                $name,
                [],
                $silentOnNotExists
            );
            $route = !is_string($route) ? '' : $route;
        }

        $allow = self::_allowedRoute($simpleName, $route);

        return $allow ? $route : '';
    }

    /**
     * @param string $name
     * @return string
     */
    public static function routeID(string $name)
    {
        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        return !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        if (TerminalData::getInstance()->isTerminal()) {

            $routes = [];
            $terminalTaskAvailables = [];

            $groupSegmentURL = $group->getGroupSegment();

            $lastIsBar = last_char($groupSegmentURL) == '/';
            $startRoute = ($lastIsBar ? '' : '/') . self::$URLDirectory;

            $classname = self::class;

            /* Buscar tareas */
            $classesDirectoryPath = str_replace('/', \DIRECTORY_SEPARATOR, basepath('app/classes'));
            $tasksDirectoryPath = str_replace('/', \DIRECTORY_SEPARATOR, realpath(dirname(__FILE__) . '/../Tasks/'));
            $tasksDirectory = new DirectoryObject($tasksDirectoryPath);
            $tasksDirectory->process();
            foreach ($tasksDirectory->getFiles() as $file) {
                include_once $file->getPath();
                $qualifyName = str_replace(
                    [
                        $classesDirectoryPath,
                        '/',
                        '.php',
                    ],
                    [
                        '',
                        '\\',
                        '',
                    ],
                    $file->getPath()
                );
                if (class_exists($qualifyName) && method_exists($qualifyName, 'route')) {
                    $routes[] = call_user_func([$qualifyName, 'route'], $startRoute, self::$baseRouteName);
                    try {
                        $taskInstance = new $qualifyName();
                        $descriptionRoute = $taskInstance->getDescription()->getArrayCopy();
                        if (is_array($descriptionRoute)) {
                            $descriptionRoute = implode('', $descriptionRoute);
                        }
                        $terminalTaskAvailables[] = [
                            'name' => str_replace(self::$baseRouteName . '-', '', $taskInstance->getName()),
                            'description' => $descriptionRoute,
                        ];
                    } catch (\Throwable $e) {}
                }
            }

            set_config('terminalTaskAvailablesVerbose', $terminalTaskAvailables);
            $group->register($routes);

        }

        return $group;
    }
}