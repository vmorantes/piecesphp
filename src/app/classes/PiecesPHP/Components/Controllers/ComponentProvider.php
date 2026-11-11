<?php

/**
 * ComponentProvider.php
 */

namespace PiecesPHP\Components\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Components\ComponentProviderLang;
use PiecesPHP\Components\ComponentProviderRoutes;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * ComponentProvider.
 *
 * @package     PiecesPHP\Components\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class ComponentProvider extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = '';
    /**
     * @var string
     */
    protected static $baseRouteName = 'components-provider';

    const BASE_JS_DIR = 'js';
    const LANG_GROUP = ComponentProviderLang::LANG_GROUP;

    /** La forma de un nombre de grupo: el de Views/ acaba en un require, y uno registrado comparte el mismo espacio. */
    const GROUP_NAME_PATTERN = '/^[A-Za-z0-9_-]+$/';

    /** La única fuente de la carpeta de grupos: registrar y servir miran la misma, o un nombre que choca se colaría. */
    const VIEWS_DIR = __DIR__ . '/../Views/';

    /**
     * Los grupos que registra el código del proyecto (registerGroup()): nombre => lo que pinta su HTML. Viven lo que dura la
     * petición o la orden de bin/cli: el arranque los vuelve a registrar en cada una.
     *
     * @var array<string,callable>
     */
    private static array $registeredGroups = [];

    public function __construct()
    {
        parent::__construct();
        $this->setInstanceViewDir(self::VIEWS_DIR);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function provide(Request $request, Response $response)
    {

        //UN GRUPO DE Views/ ACABA EN UN require: solo pasa un nombre con forma y que esté en Views/ o registrado, sin depender
        //de que el servidor normalice los «..»; el registrado no llega al require. Sin try que trague lo que lance el render.
        $requestedGroup = $request->getAttribute('group');
        if (!is_string($requestedGroup) || preg_match(self::GROUP_NAME_PATTERN, $requestedGroup) !== 1 || !in_array($requestedGroup, $this->availableGroups(), true)) {
            throw new NotFoundException($request, $response);
        }

        if (isset(self::$registeredGroups[$requestedGroup])) {
            //Lo que pinta se captura como el render de una vista; si lanza, se descarta lo pintado y sale de provide().
            $level = ob_get_level();
            ob_start();
            try {
                (self::$registeredGroups[$requestedGroup])();
                //También lo de un búfer que $render dejara abierto: ob_get_clean() solo daría el más interno.
                $output = '';
                while (ob_get_level() > $level) {
                    $output = (string) ob_get_clean() . $output;
                }
            } finally {
                while (ob_get_level() > $level) {
                    ob_end_clean();
                }
            }
            //La marca de caché que render() pone a los estáticos de un grupo de Views/, y con la misma opción.
            if (get_config('cache_stamp_render_files') === true) {
                $output = self::addStaticVersions($output);
            }
        } else {
            $output = $this->render("{$requestedGroup}/components", [], false);
        }
        $response = $response->write(is_string($output) ? $output : '');

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Los grupos que se pueden servir: los directorios de Views/ con un components.php, y con forma de nombre, más los
     * registrados con registerGroup().
     *
     * @return string[]
     */
    public function availableGroups(): array
    {
        return array_merge(self::viewGroups(self::VIEWS_DIR), array_keys(self::$registeredGroups));
    }

    /**
     * Registra un grupo de componentes del proyecto: `components-provider-provide` lo sirve como a los de Views/.
     *
     * EXISTE PARA QUE EL PROYECTO NO ESCRIBA DENTRO DEL FRAMEWORK: un grupo puesto en PiecesPHP/Components/Views/ se pierde
     * al actualizar, sin ningún error. Se llama desde el `routes()` del módulo del proyecto (como Sitemap::registerProvider
     * en PublicationsRoutes), sin condición de sesión: en un `init()` o en un gancho previo, el anónimo recibiría un 404.
     *
     * LA RUTA ES PÚBLICA: `provide` no pide sesión ni mira roles, así que lo que pinte $render lo ve cualquiera. Lo que
     * exija sesión no va en un grupo: va en una ruta del módulo con `require_login`. $render no recibe nada; lo que lea de
     * $_GET es entrada anónima: se escapa todo lo que pinta y se pone tope al trabajo por petición (como `publications`).
     *
     * Un nombre repetido, con un grupo de Views/ o con otro registrado, NO pisa al anterior: lanza, y el arranque entero se
     * cae (también bin/cli). Por eso el nombre lleva un prefijo propio del proyecto: un grupo nuevo del framework no chocará.
     *
     * @param string $group El nombre: letras, números, guion y guion bajo, como un directorio de Views/.
     * @param callable $render Pinta el HTML del grupo con echo; provide() lo captura y responde text/html.
     * @return void
     * @throws \InvalidArgumentException Si el nombre no tiene la forma permitida.
     * @throws \LogicException Si el grupo ya existe.
     */
    public static function registerGroup(string $group, callable $render): void
    {
        if (preg_match(self::GROUP_NAME_PATTERN, $group) !== 1) {
            throw new \InvalidArgumentException("El nombre del grupo de componentes «{$group}» no es válido: solo letras, números, guion y guion bajo.");
        }
        if (isset(self::$registeredGroups[$group]) || in_array($group, self::viewGroups(self::VIEWS_DIR), true)) {
            throw new \LogicException("El grupo de componentes «{$group}» ya existe: elige otro nombre, un grupo no puede sustituir a otro.");
        }
        self::$registeredGroups[$group] = $render;
    }

    /**
     * Los directorios de esa carpeta con un components.php y con forma de nombre.
     *
     * @param string $directory
     * @return string[]
     */
    private static function viewGroups(string $directory): array
    {
        $groups = [];
        foreach (scandir($directory) ?: [] as $entry) {
            if (is_string($entry) && preg_match(self::GROUP_NAME_PATTERN, $entry) === 1 && is_file($directory . $entry . '/components.php')) {
                $groups[] = $entry;
            }
        }
        return $groups;
    }

    /**
     * @return string
     */
    public static function pathJSModule()
    {
        return ComponentProviderRoutes::staticRoute('js/ComponentsProvider.js');
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $routes = [];

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = ($lastIsBar ? '' : '/') . self::$URLDirectory;

        $classname = self::class;

        /**
         * @var array<string>
         */
        $allRoles = array_keys(UsersModel::TYPES_USERS);

        //Permisos
        $provide = $allRoles;

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route( //Las piezas HTML de un grupo: Views/<grupo>/components.php o uno registrado con registerGroup()
                "{$startRoute}/provide/{group}[/]",
                $classname . ':provide',
                self::$baseRouteName . '-provide',
                'GET',
                false,
                null,
                $provide
            ),
        ];

        $group->register($routes);

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
