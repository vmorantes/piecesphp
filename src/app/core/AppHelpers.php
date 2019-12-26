<?php

/**
 * AppHelpers.php
 * Funciones globales
 *
 * Grupo de funciones utilitarias de la aplicación.
 *
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
use App\Model\UsersModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Exceptions\RouteDuplicateNameException;
use PiecesPHP\Core\Roles;

/**
 * Obtiene el valor de la configuración solicitada.
 *
 * @param string $name Nombre de la configuración
 * @return mixed|boolean Devuelve el valor o false si no existe o es null
 */
function get_config(string $name)
{
    return Config::get_config($name);
}

/**
 * Establece el valor de la configuración señalada.
 *
 * @param string $name Nombre de la configuración
 * @param mixed $value Valor que se establecerá
 * @return void
 */
function set_config(string $name, $value)
{
    Config::set_config($name, $value);
}

/**
 * Obtiene el valor de $config['title]
 *
 * @param bool $appendTitleApp
 * @param string $separator
 *
 * @return string
 */
function get_title(bool $appendTitleApp = false, string $separator = ' - ')
{
    $title = get_config('title');
    $title_app = get_config('title_app');

    if ($title !== false) {
        if ($appendTitleApp && $title_app != $title) {
            return $title_app . $separator . get_config('title');
        } else {
            return get_config('title');
        }
    } else {

        if ($title_app !== false) {

            return $title_app;

        } else {

            return 'Web';

        }

    }
}

/**
 * Establece el valor de $config['title]
 *
 * @param string $title
 * @return bool
 */
function set_title(string $title)
{
    return set_config('title', $title);
}

/**
 * Obtiene PiecesPHP\Core\Config::app_url() y lo une a $resource
 *
 * @param string $resource
 * @return string
 */
function baseurl(string $resource = "")
{
    $app_url = Config::app_url();

    if (strlen($resource > 0) && $resource[0] == "/") {
        $resource = remove_first_char($resource);
    }

    if (last_char($app_url) == "/") {
        $app_url = remove_last_char($app_url);
    }

    $url = $app_url . '/' . $resource;

    return $url;
}

/**
 * Alias de baseurl()
 *
 * @param string $resource
 * @return string
 */
function base_url(string $resource = "")
{
    return baseurl($resource);
}

/**
 * Obtiene la url basado en el idioma actual
 * Nota: Solo si lang_by_url es true
 *
 * @param string $current_lang
 * @param string $target_lang
 * @return string
 */
function get_lang_url($current_lang = 'es', $target_lang = 'en')
{

    $default_lang = get_config('default_lang');

    if ($target_lang == $default_lang) {
        $target_lang = '';
    } else {
        $target_lang .= '/';
    }

    $base_url = baseurl($current_lang . '/');
    $current_url = get_current_url();
    $current_segment = str_replace($base_url, '', $current_url);
    $current_segment = str_replace(remove_last_char($base_url), '', $current_url);
    $current_segment = str_replace(baseurl(), '', $current_segment);
    $lang_url = baseurl($target_lang . $current_segment);
    $path = parse_url($lang_url, PHP_URL_PATH);
    $path_clean = str_replace('//', '/', $path);
    $lang_url = str_replace($path, $path_clean, $lang_url);
    return $lang_url;
}

/**
 * Convierte la url basado en el idioma actual en su par de otro idioma
 * Nota: Solo si lang_by_url es true
 *
 * @param string $url
 * @param string $current_lang
 * @param string $target_lang
 * @return string
 */
function convert_lang_url($url, $current_lang = 'es', $target_lang = 'en')
{

    $default_lang = get_config('default_lang');

    if ($target_lang == $default_lang) {
        $target_lang = '';
    } else {
        $target_lang .= '/';
    }

    $base_url = baseurl($current_lang . '/');
    $segment = str_replace($base_url, '', $url);
    $segment = str_replace(remove_last_char($base_url), '', $url);
    $segment = str_replace(baseurl(), '', $segment);
    $lang_url = baseurl($target_lang . $segment);
    $path = parse_url($lang_url, PHP_URL_PATH);
    $path_clean = str_replace('//', '/', $path);
    $lang_url = str_replace($path, $path_clean, $lang_url);
    return $lang_url;
}

/**
 * Devuelve un array con las urls de idiomas posibles de la URL pasado por parámetros o
 * la actual en caso de no proporcionarla
 * Nota: Solo si lang_by_url es true
 *
 * @param string $url
 * @param bool $short_lang Define si el índice de cada URL es el lenguaje corto o largo ej. ES o Español
 * @return string[]
 */
function get_current_langs_urls(string $url = null, bool $short_lang = false)
{

    $url = !is_null($url) ? $url : get_current_url();
    $urls = [];
    $langs = get_config('allowed_langs');

    foreach ($langs as $lang) {

        $urls[__($short_lang ? 'langShort' : 'lang', $lang)] = convert_lang_url($url, get_config('app_lang'), $lang);

    }

    return $urls;

}

/**
 * Obtiene PiecesPHP\Core\Config::app_path() y lo une a "/".$resource
 *
 * @param string $resource
 * @return string
 */
function basepath(string $resource = "")
{
    $path = Config::app_path() . "/" . $resource;

    $path = str_replace(["//", "\\\\"], ["/", "\\"], $path);

    if (file_exists($path)) {
        return realpath($path);
    } else {
        return $path;
    }
}

/**
 * Obtiene PiecesPHP\Core\Config::app_path() y lo une a "/app/".$resource
 *
 * @param string $resource
 * @return string
 */
function app_basepath($resource = "")
{
    $path = Config::app_path() . "/app/" . $resource;

    $path = str_replace(["//", "\\\\"], ["/", "\\"], $path);

    if (file_exists($path)) {
        return realpath($path);
    } else {
        return $path;
    }
}

/**
 * Obtiene PiecesPHP\Core\Config::app_base()
 *
 * @return string
 */
function appbase()
{
    return Config::app_base();
}

/**
 * Traduce el mensaje usando los archivos situados en app/lang.
 *
 * Si el mensaje no existe usa el ingresado tal cual.
 *
 * @param string $type Índice del tipo de mensaje
 * @param string $message Índice del mensaje en el tipo dado
 * @param boolean $echo Si es true hace echo, si no solo retorna el mensaje
 * @return string|void
 * Si $echo es true retorna el string y hace un echo de este.
 * Si $echo es false retorna un string correspondiente al mensaje.
 * Si $message es '' devuelve el array completo de mensajes en $type
 */
function __(string $type, string $message = '', bool $echo = false)
{
    $diccionario = [];
    $default_file = app_basepath("lang/default.php");
    if (file_exists($default_file)) {
        $default_file = include $default_file;
        foreach ($default_file as $group_name => $messages) {
            if (!isset($diccionario[$group_name])) {
                $diccionario[$group_name] = $messages;
            } else {
                $diccionario[$group_name] = array_merge($diccionario[$group_name], $messages);
            }
        }
    }
    $lang = Config::get_lang();
    $existFile = file_exists(app_basepath("lang/" . $lang . ".php"));
    $msg = $message;

    if ($existFile) {

        $lang_file = include app_basepath("lang/" . $lang . ".php");

        foreach ($lang_file as $group_name => $messages) {
            if (!isset($diccionario[$group_name])) {
                $diccionario[$group_name] = $messages;
            } else {
                $diccionario[$group_name] = array_merge($diccionario[$group_name], $messages);
            }
        }
    }

    if (array_key_exists($type, $diccionario)) {

        if ($message === '') {
            return $diccionario[$type];
        }

        if (array_key_exists($message, $diccionario[$type])) {

            $msg = $diccionario[$type][$message];
        }
    }

    if ($echo) {

        echo $msg;
    } else {

        return $msg;
    }
}

/**
 * Traduce el mensaje usando el archivo situado en
 * app/lang correspondiente al idioma escogido.
 *
 * Si el mensaje no existe intenta encontrarlo en los definidos en el idioma
 * de la aplicación usando __().
 *
 * @param string $type Índice del tipo de mensaje
 * @param string $message Índice del mensaje en el tipo dado
 * @param string $lang Idioma
 * @param boolean $echo Si es true hace echo, si no solo retorna el mensaje
 * @return mixed
 * Si $echo es true retorna el string y hace un echo de este.
 * Si $echo es false retorna un string correspondiente al mensaje.
 * Si $message es '' devuelve el array completo de mensajes en $type
 */
function lang(string $type, string $message = '', string $lang, bool $echo = false)
{
    $diccionario = [];
    $existFile = file_exists(app_basepath("lang/" . $lang . ".php"));
    $msg = null;

    if ($existFile) {

        $lang_file = include app_basepath("lang/" . $lang . ".php");

        $diccionario = array_merge($diccionario, $lang_file);
    }

    if (array_key_exists($type, $diccionario)) {

        if ($message === '') {
            return $diccionario[$type];
        }

        if (array_key_exists($message, $diccionario[$type])) {

            $msg = $diccionario[$type][$message];
        }
    }

    if ($msg == null) {
        $msg = __($type, $message);
    }

    if ($echo) {

        echo $msg;
    } else {

        return $msg;
    }
}

/** @var @ignore array ['js'=>array(),'css'=>array()] Array asociativo con las rutas de los js y css globales*/
set_config('global_assets', [
    'js' => [],
    'css' => [],
]);
/** @var @ignore array ['js'=>array(),'css'=>array()] Array asociativo con las rutas de los js y css específicos de cada vista.*/
set_config('custom_assets', [
    'js' => [],
    'css' => [],
]);

/** @ignore */
function load_js($config = array())
{
    $global_assets = get_config('global_assets');
    $custom_assets = get_config('custom_assets');

    ksort($global_assets['js']);
    foreach ($global_assets['js'] as $script) {
        $base_url = array_key_exists("base_url", $config) ? $config['base_url'] : "";
        $ruta = $base_url . $script;
        echo "<script src='$ruta'></script>" . "\r\n";
    }

    ksort($custom_assets['js']);
    foreach ($custom_assets['js'] as $script) {

        $base_url = array_key_exists("custom_url", $config) ?
        $config['custom_url'] : (array_key_exists("base_url", $config) ?
            $config['base_url'] : "");

        $ruta = $base_url . $script;
        echo "<script src='$ruta'></script>" . "\r\n";
    }
}

/** @ignore */
function load_css($config = array())
{
    $global_assets = get_config('global_assets');
    $custom_assets = get_config('custom_assets');

    ksort($global_assets['css']);
    foreach ($global_assets['css'] as $stylesheet) {

        $rel = array_key_exists("rel", $config) ? $config['rel'] : 'stylesheet';
        $base_url = array_key_exists("base_url", $config) ? $config['base_url'] : '';
        $path = $base_url . $stylesheet;
        $tag = "<link rel='$rel' href='$path'>";

        echo $tag . "\r\n";
    }

    ksort($custom_assets['css']);
    foreach ($custom_assets['css'] as $stylesheet) {

        $rel = array_key_exists("rel", $config) ? $config['rel'] : 'stylesheet';
        if (array_key_exists("custom_url", $config)) {
            $base_url = $config['custom_url'];
        } else if (array_key_exists("base_url", $config)) {
            $base_url = $config['base_url'];
        } else {
            $base_url = '';
        }
        $path = $base_url . $stylesheet;
        $tag = "<link rel='$rel' href='$path'>";

        echo $tag . "\r\n";
    }
}

/** @ignore */
function has_global_asset(string $asset, string $type)
{

    $global_assets = get_config('global_assets');
    $assets = [];
    $index_asset = false;

    if ($type == 'js') {
        $assets = $global_assets[$type];
    } else if ($type == 'css') {
        $assets = $global_assets[$type];
    }

    if ($type == 'js' || $type == 'css') {

        $index_asset = array_search($asset, $assets);

    }

    return $index_asset;
}

/** @ignore */
function add_global_asset($asset, $type)
{
    $global_assets = get_config('global_assets');
    if ($type == "js") {
        $global_assets['js'][count($global_assets['js'])] = $asset;
        set_config('global_assets', $global_assets);
    } else if ($type == "css") {
        $global_assets['css'][count($global_assets['css'])] = $asset;
        set_config('global_assets', $global_assets);
    }
}

/** @ignore */
function add_global_assets($custom_assets, $type)
{
    foreach ($custom_assets as $asset) {
        add_global_asset($asset, $type);
    }
}

/** @ignore */
function remove_global_asset(string $asset, string $type)
{

    $global_assets = get_config('global_assets');
    $index_asset = has_global_asset($asset, $type);

    if ($index_asset !== false) {
        unset($global_assets[$type][$index_asset]);
    }

    set_config('global_assets', $global_assets);

    return $index_asset;
}

/** @ignore */
function set_custom_assets($custom_assets, $type)
{
    $_custom_assets = get_config('custom_assets');
    if ($type == "js") {
        $_custom_assets['js'] = $custom_assets;
        set_config('custom_assets', $_custom_assets);
    } else if ($type == "css") {
        $_custom_assets['css'] = $custom_assets;
        set_config('custom_assets', $_custom_assets);
    }
}

/** @ignore */
function set_global_assets($assets, $type)
{
    $global_assets = get_config('global_assets');
    if ($type == "js") {
        $global_assets['js'] = $assets;
        set_config('global_assets', $global_assets);
    } else if ($type == "css") {
        $global_assets['css'] = $assets;
        set_config('global_assets', $global_assets);
    }
}
/**
 * Registra como assets globales la librería y los plugins definidos por parámetro
 *
 * @param string $name
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_front_library(string $name = '', array $plugins = ['calendar'], bool $all = false)
{
    $library_name = $name;

    $assets = get_config('default_assets');
    $library_exists = isset($assets[$library_name]);

    if ($library_exists) {
        $library = $assets[$library_name];
        $library_plugins = isset($library['plugins']) ? $library['plugins'] : [];

        $imported = get_config('imported_assets');
        $is_imported = isset($imported[$library_name]);

        if (!$is_imported) {
            $has_js = array_key_exists('js', $library);
            $has_css = array_key_exists('css', $library);
            $was_imported = false;

            if ($has_js) {
                $js = $library['js'];

                if (is_array($js)) {
                    add_global_assets($js, 'js');
                    $was_imported = true;
                }
            }
            if ($has_css) {
                $css = $library['css'];

                if (is_array($css)) {
                    add_global_assets($css, 'css');
                    $was_imported = true;
                }
            }
            if ($was_imported) {
                $imported[$library_name] = [];
                set_config('imported_assets', $imported);
            }
        }

        if ($all) {
            $plugins = [];
            foreach ($library_plugins as $name => $item) {
                $plugins[] = $name;
            }
        }

        foreach ($plugins as $plugin) {
            if (is_string($plugin)) {

                $plugin_is_imported = isset($imported[$library_name][$plugin]);

                if (!$plugin_is_imported) {
                    if (array_key_exists($plugin, $library_plugins)) {

                        $_plugin = $library_plugins[$plugin];
                        $plugin_has_js = array_key_exists('js', $_plugin);
                        $plugin_has_css = array_key_exists('css', $_plugin);
                        $plugin_was_imported = false;

                        if ($plugin_has_js) {
                            $_plugin_js = $_plugin['js'];

                            if (is_array($_plugin_js)) {
                                add_global_assets($_plugin_js, 'js');
                                $plugin_was_imported = true;
                            }
                        }

                        if ($plugin_has_css) {
                            $_plugin_css = $_plugin['css'];

                            if (is_array($_plugin_css)) {
                                add_global_assets($_plugin_css, 'css');
                                $plugin_was_imported = true;
                            }
                        }

                        if ($plugin_was_imported) {
                            $imported[$library_name][] = $plugin;
                            set_config('imported_assets', $imported);
                        }
                    }
                }
            }
        }
    }
}

/**
 * Registra jquery como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_jquery(array $plugins = [], bool $all = false)
{
    import_front_library('jquery', $plugins, $all);
}

/**
 * Registra semantic como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_semantic(array $plugins = ['calendar'], bool $all = false)
{
    import_front_library('semantic', $plugins, $all);
}

/**
 * Registra datatables como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_datatables(array $plugins = ['rowReorder', 'colReorder', 'responsive'], bool $all = false)
{
    import_front_library('datatables', $plugins, $all);
}

/**
 * Registra nprogress como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_nprogress(array $plugins = [], bool $all = false)
{
    import_front_library('nprogress', $plugins, $all);
}
/**
 * Registra sweetalert2 como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_swal2(array $plugins = [], bool $all = false)
{
    import_front_library('sweetalert2', $plugins, $all);
}
/**
 * Registra izitoast como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_izitoast(array $plugins = [], bool $all = false)
{
    import_front_library('izitoast', $plugins, $all);
}

/**
 * Registra cropper como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_cropper(array $plugins = [], bool $all = true)
{
    import_front_library('cropper', $plugins, $all);
}

/**
 * Registra jquerymask como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_jquerymask(array $plugins = [], bool $all = false)
{
    import_front_library('jquerymask', $plugins, $all);
}

/**
 * Registra quilljs como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_quilljs(array $plugins = [], bool $all = true)
{
    import_front_library('quilljs', $plugins, $all);
}

/**
 * Registra app_libraries como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_app_libraries(array $plugins = [], bool $all = false)
{
    import_front_library('app_libraries', $plugins, $all);
}

/**
 * Registra spectrum como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_spectrum(array $plugins = [], bool $all = true)
{
    import_front_library('spectrum', $plugins, $all);
}

/**
 * Registra DialogPCS como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_dialog_pcs(array $plugins = [], bool $all = true)
{
    import_front_library('dialgo_pcs', $plugins, $all);
}

/**
 * Registra una ruta o un conjunto de rutas
 *
 * register_routes([
 *         [
 *             'route'=>'ruta/{param}',
 *             'route_alias'=>'ruta_alternativa/{param}',
 *             'name'=>'nombre-de-la-ruta',
 *             'method'=>'GET|POST',
 *             'controller'=> Controller::class.':method',
 *             'require_login'=> true|false,
 *         ]
 * ])
 *
 * @param array $routes Rutas
 * @param array &$router Referencia al el enrutador de la aplicación
 * @return void
 */
function register_routes($routes, &$router)
{
    $_routes = get_config('_routes_');

    if (count($routes) < 1) {
        return;
    }

    if ($_routes === false) {
        set_config('_routes_', []);
        $_routes = [];
    }

    $routes = is_array($routes) ? $routes : [$routes];

    foreach ($routes as $route) {

        $route_segment = $route['route'];

        $methods = explode('|', $route['method']);

        if (count($methods) > 0) {
            foreach ($methods as $key => $method) {
                $methods[$key] = strtolower($method);
            }
        }

        $name = isset($route['name']) ? $route['name'] : uniqid();
        $route_alias = isset($route['route_alias']) ? (is_string($route['route_alias']) ? $route['route_alias'] : null) : null;
        $controller = $route['controller'];
        $require_login = isset($route['require_login']) ? $route['require_login'] : false;
        $require_login = $require_login === true ? true : false;
        $roles_allowed = isset($route['roles_allowed']) ? $route['roles_allowed'] : [];
        $roles_allowed = is_array($roles_allowed) ? $roles_allowed : [$roles_allowed];
        $parameters = isset($route['parameters']) ? $route['parameters'] : [];
        $parameters = is_array($parameters) ? $parameters : [$parameters];

        if (array_key_exists($name, $_routes)) {
            throw new RouteDuplicateNameException();
        }

        foreach ($methods as $key => $method) {
            $methods[$key] = strtoupper($method);
        }

        if (is_string($name) && $name !== null && $name !== '') {
            $router->map($methods, $route_segment, $controller)->setName($name);
        } else {
            $router->map($methods, $route_segment, $controller);
        }

        if ($route_alias !== null) {
            $router->map($methods, $route_alias, $controller);
        }

        if (is_string($name) && $name !== null && $name !== '') {

            $_routes[$name]['route'] = $route_segment;
            $_routes[$name]['name'] = $name;
            $_routes[$name]['controller'] = $controller;
            $_routes[$name]['method'] = implode('|', $methods);
            $_routes[$name]['require_login'] = $require_login;
            $_routes[$name]['roles_allowed'] = $roles_allowed;
            $_routes[$name]['parameters'] = $parameters;

            if ($route_alias !== null) {
                $_routes[$name]['route_alias'] = $route_alias;
            }

            foreach ($roles_allowed as $role) {
                Roles::addPermission($name, $role);
            }

            set_config('_routes_', $_routes);

        }
    }
}

/**
 * Devuelve todas las rutas de la aplicación
 *
 * @return array
 */
function get_routes()
{
    return get_config('_routes_');
}

/**
 * Devuelve todas las rutas de la aplicación asociadas a un controlador
 *
 * @param string $name_controller Nombre del controlador
 * @param string $method Si se define este parámetro busca rutas asociadas a un método específico
 * @param array $params Array Clave:Valor con los nombre de los parámetros de las rutas que lo requieran
 * del controlador
 *
 * @return array
 */
function get_routes_by_controller(string $name_controller = '', string $method = null, array $params = null)
{

    $routes = get_config('_routes_');
    $controller_routes = [];

    foreach ($routes as $route) {
        $controller = explode(':', $route['controller'])[0];
        $method_controller = explode(':', $route['controller'])[1];
        if ($controller == $name_controller) {
            if (is_string($method) && $method_controller == $method) {

                $tmp = $route;
                $has_params = preg_match('/\{.*\}/', $tmp['route']);

                if ($has_params === 1) {
                    if (is_array($params)) {

                        $route_with_params_values = $tmp['route'];

                        foreach ($params as $key => $value) {
                            $route_with_params_values = str_replace("{" . $key . "}", $value, $route_with_params_values);
                        }

                        $has_params = preg_match('/\{.*\}/', $route_with_params_values);

                        if ($has_params) {
                            $tmp['url'] = null;
                            $tmp['nota'] = 'Esta ruta requiere parámetros para mostrar la URL correctamente.';
                        } else {
                            $tmp['url'] = get_route($route['name'], $params);
                        }
                    } else {
                        $tmp['url'] = null;
                        $tmp['nota'] = 'Esta ruta requiere parámetros para mostrar la URL correctamente.';
                    }
                } else {

                    $tmp['url'] = get_route($route['name']);
                }

                $controller_routes = $tmp;
            } else if (!is_string($method)) {

                $tmp = $route;
                $has_params = preg_match('/\{.*\}/', $tmp['route']);

                if ($has_params === 1) {
                    if (is_array($params)) {

                        $route_with_params_values = $tmp['route'];

                        foreach ($params as $key => $value) {
                            $route_with_params_values = str_replace("{" . $key . "}", $value, $route_with_params_values);
                        }

                        $has_params = preg_match('/\{.*\}/', $route_with_params_values);

                        if ($has_params) {
                            $tmp['url'] = null;
                            $tmp['nota'] = 'Esta ruta requiere parámetros para mostrar la URL correctamente.';
                        } else {
                            $tmp['url'] = get_route($route['name'], $params);
                        }
                    } else {
                        $tmp['url'] = null;
                        $tmp['nota'] = 'Esta ruta requiere parámetros para mostrar la URL correctamente.';
                    }
                } else {

                    $tmp['url'] = get_route($route['name']);
                }

                $controller_routes[] = $tmp;
            }
        }
    }
    return $controller_routes;
}

/**
 * Devuelve la URL correspondiente al método del controlador indicado.
 *
 * @param string $name_controller Nombre del controlador
 * @param string $method_controller Método del controlador
 * @param array $params Array Clave:Valor con los nombre de los parámetros de la ruta
 * @param string $method_request Método de la ruta POST|GET|PUT etc..
 * @return string
 */
function get_route_by_controller(string $name_controller, string $method_controller, array $params = null, string $method_request = 'GET')
{
    $route = get_routes_by_controller($name_controller, $method_controller, $params);
    return (is_array($route) && count($route) > 0) ? $route['url'] : null;
}

/**
 * Devuelve la información de la ruta indicada
 *
 * @param string $name Nombre de la ruta
 * @return array La información de la ruta o null si no existe
 */
function get_route_info(string $name)
{
    $routes = get_routes();
    $route_info = isset($routes[$name]) ? $routes[$name] : null;
    return $route_info;
}

/**
 * Devuelve la URL correspondiente al nombre de ruta indicado.
 *
 * @param string $name Nombre de la ruta
 * @param array $params Array asociativo para los parámetros de la ruta.
 * @param bool $silentOnNotExists Si es true devuelve NULL en caso de que no exista; si no, Slim lanzará una excepción
 * @return string|null
 */
function get_route(string $name, array $params = [], bool $silentOnNotExists = false)
{
    $exists = isset(get_routes()[$name]);
    if ($exists || !$silentOnNotExists) {

        if ($exists) {

            $route_info = get_routes()[$name];
            $parameters = $route_info['parameters'];

            foreach ($parameters as $param_name => $value) {
                if (!array_key_exists($param_name, $params) && !is_null($value)) {
                    $params[$param_name] = $value;
                } elseif (array_key_exists($param_name, $params) && is_null($params[$param_name])) {
                    unset($params[$param_name]);
                }
            }
        }

        $route = get_config('slim_app')->getContainer()->get('router')->pathFor($name, $params);

        $app_base = appbase();
        $app_base_position = strlen($app_base) > 0 ? strpos($route, $app_base) : false;
        $app_base_length = strlen($app_base);

        if ($app_base_position !== false) {
            $route = mb_substr($route, $app_base_position + $app_base_length);
        }

        $route = baseurl($route);
        $base_len = strlen(baseurl());
        $route = mb_substr($route, $base_len);

        if (is_string($route) && strlen($route) > 0 && $route[0] == '/') {
            $route = remove_first_char($route);
        }

        $route = baseurl($route);

        return $route;
    } else {
        return null;
    }
}

/**
 * @param string $name
 * @param bool $silentOnNotExists
 * @return string|null
 */
function get_route_sample(string $name, bool $silentOnNotExists = false)
{
    $information = get_route_info($name);
    $parameters = $information['parameters'];
    foreach ($parameters as $name => $value) {
        $parameters[$name] = "{" . $name . "}";
    }
    return get_route($information['name'], $parameters, $silentOnNotExists);
}

/**
 * @param string $name
 * @param string $type
 * @return array
 */
function get_route_roles_allowed(string $name, string $type = 'code')
{
    $information = get_route_info($name);
    $roles_permissions = Roles::getRoles();
    $roles = UsersModel::getTypesUser();

    $roles_allowed = array_map(function ($e) use ($roles, $type) {
        if ($type == 'name') {
            return $roles[$e];
        } elseif ($type == 'code') {
            return $e;
        }
    }, $information['roles_allowed']);

    foreach ($roles_permissions as $data) {
        $name = $data['name'];
        $code = $data['code'];
        $all = $data['all'];
        $allowed_routes = $data['allowed_routes'];
        if ($all || in_array($information['name'], $allowed_routes)) {
            if (!in_array($name, $roles_allowed)) {
                if ($type == 'name') {
                    $roles_allowed[] = $name;
                } elseif ($type == 'code') {
                    $roles_allowed[] = $code;
                }
            }
        }
    }

    return $roles_allowed;
}

/**
 * Establece un mensaje flash
 *
 * @param string $name Nombre
 * @param mixed $value Valor
 * @return void
 */
function set_flash_message(string $name, $value)
{
    \PiecesPHP\Core\FlashMessages::addMessage($name, $value);
}

/**
 * Obtiene todos los mensajes flash
 *
 * @param string $name Nombre
 * @param mixed $value Valor
 * @return array
 */
function get_flash_messages()
{
    return \PiecesPHP\Core\FlashMessages::getMessages();
}

//──── HELPERS PARA FORMULARIOS HTML ─────────────────────────────────────────────────────

/**
 * Devuelve un label
 *
 * @param string $name
 * @param string $for
 * @param array $classes
 * @return string
 */
function form_label(string $text, string $for = null, array $classes = [])
{
    $label = \PiecesPHP\Core\HTML\Form::label($text, $for);
    $label->setAttribute('class', $classes);
    return (string) $label;
}

/**
 * Devuelve un campo input
 *
 * @param string $name
 * @param mixed $value
 * @param bool $required
 * @param string $placeholder
 * @param string $type
 * @param array $classes
 * @param \PiecesPHP\Core\HTML\Attribute|array $attributes
 * @return string
 */
function form_input(string $name, $value = '', bool $required = false, string $placeholder = null, string $type = 'text', array $classes = [], $attributes = null)
{
    $value = ((string) $value);
    $input = new \PiecesPHP\Core\HTML\Input($name, $value, $type, $placeholder, $attributes);
    $input->setAttribute('class', $classes);

    if ($required) {
        $input->setAttribute('required', '');
    }
    return (string) $input;
}

/**
 * Devuelve un campo select
 *
 * @param string $name
 * @param array $values
 * @param bool $required
 * @param mixed $selectedDefaultValue
 * @param \PiecesPHP\Core\HTML\Attribute|array $attributes
 * @return string
 */
function form_select(string $name, array $values, bool $required = false, $selectedDefaultValue = null, $attributes = null)
{
    $selectedDefaultValue = is_null($selectedDefaultValue) ? __('general', 'select_element') : $selectedDefaultValue;

    $select = new \PiecesPHP\Core\HTML\Select($name, $selectedDefaultValue, __('general', 'select_element'), '', $attributes);

    if ($required) {
        $select->setAttribute('required', '');
    }
    $select->setOptions($values);
    return (string) $select;
}

/**
 * Devuelve uno o varios campos radio
 *
 * @param string $name
 * @param array $values
 * @param mixed $selected_value
 * @param string $wrapper
 * @param bool $required
 * @param array $classes
 * @param \PiecesPHP\Core\HTML\Attribute|array $attributes
 * @return string
 */
function form_radio(
    string $name,
    array $values = [],
    $selected_value = null,
    bool $required = false,
    string $wrapper = null,
    array $classes = [],
    $attributes = null
) {

    $inputs = [];
    $type = 'radio';
    $has_selected = false;

    if (empty($selected_value) && $selected_value !== 0) {
        $selected_value = null;
    }

    $values = array_flip($values);

    foreach ($values as $key => $value) {
        $val = ((string) $value);
        $input = new \PiecesPHP\Core\HTML\Input($name, $key, $type, null, $attributes);
        $input->setAttribute('class', $classes);
        if (!is_null($selected_value) && $selected_value == $key && !$has_selected) {
            $input->setAttribute('checked', '');
            $has_selected = true;
        }

        if ($required) {
            $input->setAttribute('required', '');
        }
        $input = (string) $input;
        $inputs[] = "<label>$val</label>$input";
    }

    if (!is_null($wrapper)) {
        foreach ($inputs as $pos => $input) {
            $inputs[$pos] = str_replace('{element}', $input, $wrapper);
        }
    }

    $input = trim(implode('', $inputs));

    return $input;
}

/**
 * Devuelve uno o varios campos checkbox
 *
 * @param string $name
 * @param array $values
 * @param mixed $selected_value
 * @param string $wrapper
 * @param bool $required
 * @param array $classes
 * @param \PiecesPHP\Core\HTML\Attribute|array $attributes
 * @return string
 */
function form_checkbox(
    string $name,
    array $values = [],
    $selected_value = null,
    bool $required = false,
    string $wrapper = null,
    array $classes = [],
    $attributes = null
) {

    $inputs = [];
    $type = 'checkbox';
    $has_selected = false;

    if (empty($selected_value) && $selected_value !== 0) {
        $selected_value = null;
    }

    $values = array_flip($values);

    foreach ($values as $key => $value) {

        $val = ((string) $value);

        $input = new \PiecesPHP\Core\HTML\Input($name, $key, $type, null, $attributes);

        $input->setAttribute('class', $classes);

        if (!is_null($selected_value) && $selected_value == $key && !$has_selected) {
            $input->setAttribute('checked', '');
        }

        if ($required) {
            $input->setAttribute('required', '');
        }

        $input = (string) $input;
        $inputs[] = "<label>$val</label>$input";
    }

    if (!is_null($wrapper)) {
        foreach ($inputs as $pos => $input) {
            $inputs[$pos] = str_replace('{element}', $input, $wrapper);
        }
    }

    $input = trim(implode('', $inputs));

    return $input;
}

/**
 * Devuelve un botón tipo submit
 *
 * @param string $text
 * @param array $classes
 * @return string
 */
function form_submit(string $text, $classes = [])
{
    $button = new \PiecesPHP\Core\HTML\Button($text, 'submit');
    $button->setAttribute('class', $classes);
    return (string) $button;
}

/**
 * Moves the uploaded file to the upload directory and assigns it a unique name
 * to avoid overwriting an existing uploaded file.
 *
 * @param string $directory directory to which the file is moved
 * @param \Slim\Http\UploadedFile $uploaded file uploaded file to move
 * @return string filename of moved file
 */
function move_uploaded_file_to($directory, \Slim\Http\UploadedFile $uploadedFile, string $basename = null, string $extension = null)
{
    try {

        if (!file_exists($directory)) {
            make_directory($directory);
        }

        if (is_null($extension)) {
            $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        }

        if (is_null($basename)) {
            $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        }

        $filename = sprintf('%s.%0.8s', $basename, $extension);

        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    } catch (\Exception $e) {
        $handler = new \PiecesPHP\Core\CustomErrorsHandlers\GenericHandler($e);
        $handler->logging();
        return false;
    }
}

/**
 * log_exception
 *
 * @param \Exception|\Error $e
 * @return void
 */
function log_exception($e)
{
    if (!$e instanceof \Exception && !$e instanceof \Error) {
        throw new \TypeError('Error type unexpected.');
    }

    $handler = new \PiecesPHP\Core\CustomErrorsHandlers\GenericHandler($e);
    $handler->logging();

}
