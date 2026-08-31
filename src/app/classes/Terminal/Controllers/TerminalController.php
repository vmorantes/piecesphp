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
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
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

    use ControllerRoutingTrait;

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
     * Compone el NOMBRE de la ruta de terminal, sin resolver URL ni permisos.
     *
     * PÚBLICO A PROPÓSITO: lo llama `src/index.php:875` para descubrir qué ruta corresponde a
     * la acción de CLI en curso, antes de que exista petición. No lo sustituye el trait: el
     * trait compone el nombre DENTRO de `routeName()` y no lo expone. Ver T149.
     *
     * @param string $name
     * @return string
     */
    public static function routeID(string $name)
    {
        if (!is_null($name)) {
            $name = trim($name);
            $name = $name !== '' ? "-{$name}" : '';
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
            $tasksDirectory = realpath(dirname(__FILE__) . '/../Tasks/');
        $tasksDirectory = $tasksDirectory !== false ? $tasksDirectory : dirname(__FILE__) . '/../Tasks/';
        $tasksDirectoryPath = str_replace('/', \DIRECTORY_SEPARATOR, $tasksDirectory);
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
                //Una clase abstracta cumple method_exists() y revienta call_user_func: tumba la CLI.
                if (class_exists($qualifyName)
                    && method_exists($qualifyName, 'route')
                    && !(new \ReflectionClass($qualifyName))->isAbstract()) {
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
                    } catch (\Throwable) {}
                }
            }

            set_config('terminalTaskAvailablesVerbose', $terminalTaskAvailables);
            $group->register($routes);

        }

        return $group;
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * PUNTO DE VARIACIÓN DEL MÓDULO. Aquí, y en ningún otro sitio, van las reglas de negocio
     * que oculten una ruta que los roles SÍ permiten. Está vacío a propósito: es la plantilla,
     * y su presencia dice dónde se escribe la regla el día que aparezca.
     *
     * Devolver `false` ESTRECHA lo que ya concedieron los roles; nunca ensancha. `routeName()`
     * llama a este método SIEMPRE, y `allowedRoute()` no hace más que preguntarle a
     * `routeName()` si devolvió cadena.
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    protected static function _allowedRoute(string $name, string $route, array $params = [])
    {
        return true;
    }
}