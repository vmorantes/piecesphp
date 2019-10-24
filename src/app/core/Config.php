<?php

/**
 * Config.php
 */
namespace PiecesPHP\Core;

/**
 * Config - Clase para manejar las configuraciones de la aplicación.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class Config
{

    /** @var string $app_title Título de la aplicación */
    protected $app_title = null;

    /** @var string $app_path Ruta raíz de la aplicación (ruta de directorio) */
    protected $app_path = null;

    /** @var string $app_base Ruta relativa de la aplicación es como $app_path, pero sin el DocumentRoot */
    protected $app_base = null;

    /** @var string $app_protocol Protocolo de la aplicación http/https */
    protected $app_protocol = null;

    /** @var string $app_title URL de la aplicación es el HOST concatenado al $app_base */
    protected $app_url = null;

    /**
     * @var array $app_db Configuración de la base de datos
     * <pre>
     * [
     *  'group_name' => [
     *      'DB' => 'db',
     *      'USER' => 'user',
     *      'PASSWORD' => 'password',
     *      'HOST' => 'host',
     *  ],...
     * ]
     * </pre>
     */
    protected $app_db = null;

    /** @var string $app_key Llave usada para la encriptación en la aplicación */
    protected $app_key = null;

    /** @var array $app_cookies Configuración de las cookies */
    protected $app_cookies = null;

    /** @var string $default_app_lang Lenguaje por defecto de la aplicación */
    protected static $default_app_lang = 'es';

    /** @var string $app_lang Lenguaje de la aplicación */
    protected static $app_lang = 'es';

    /** @var string $prefix_lang Prefijo de lenguaje de la aplicación */
    protected static $prefix_lang = '';

    /** @var array $app_lang Leguajes permitidos por la aplicación */
    protected static $app_allowed_langs = ['es'];

    /** @var Config $instance Instancia */
    protected static $instance = null;

    /** @var array $configurations Configuraciones */
    protected static $configurations = [];

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->initAppConfigs();
    }

    /**
     * initAppConfigs
     *
     * Establece todas las configuraciones de la aplicación
     *
     * @return void
     */
    public function initAppConfigs()
    {
        $this->initTitleAppConfig();
        $this->initAppPathConfig();
        $this->initAppProtocolConfig();
        $this->initAppDBConfig();
        $this->initAppKeyConfig();
        $this->initAppAllowedLangsConfig();
        $this->initLangByURLConfig();
        $this->initAppLangConfig();
        $this->initAppBaseConfig();
        $this->initAppCookiesConfig();
        $this->initAppRolesConfig();
    }

    /**
     * initTitleAppConfig
     *
     * Configura el título de la app
     *
     * @return void
     */
    public function initTitleAppConfig()
    {

        if (get_config('title_app') !== false && is_string(get_config('title_app'))) {
            $this->app_title = get_config('title_app');
        } else {
            $this->app_title = "Title App";
        }
        set_config("title_app", $this->app_title);

    }

    /**
     * initAppPathConfig
     *
     * Configura el ROOT path de la aplicación
     *
     * @return void
     */
    public function initAppPathConfig()
    {

        $this->app_path = __DIR__ . "/../../";

    }

    /**
     * initAppProtocolConfig
     *
     * Configura el protocolo de la aplicación
     *
     * @return void
     */
    public function initAppProtocolConfig()
    {

        $this->app_protocol = isset($_SERVER['HTTPS']) ? "https" : "http";

    }

    /**
     * initAppDBConfig
     *
     * Configura la base de datos de la aplicación
     *
     * @return void
     */
    public function initAppDBConfig()
    {
        if ($this->app_db === null) {
            if (get_config('database') !== false && is_array(get_config('database'))) {

                $app_db = get_config('database');

                $this->app_db = [];

                foreach ($app_db as $grupo => $db) {

                    if (!isset($app_db[$grupo]['driver'])) {
                        $this->app_db[$grupo]['driver'] = 'mysql';
                    } else {
                        $this->app_db[$grupo]['driver'] = $db['driver'];
                    }

                    if (!isset($app_db[$grupo]['host'])) {
                        $this->app_db[$grupo]['host'] = 'localhost';
                    } else {
                        $this->app_db[$grupo]['host'] = $db['host'];
                    }

                    if (!isset($app_db[$grupo]['db'])) {
                        $this->app_db[$grupo]['db'] = 'piecesphp';
                    } else {
                        $this->app_db[$grupo]['db'] = $db['db'];
                    }

                    if (!isset($app_db[$grupo]['user'])) {
                        $this->app_db[$grupo]['user'] = 'root';
                    } else {
                        $this->app_db[$grupo]['user'] = $db['user'];
                    }

                    if (!isset($app_db[$grupo]['password'])) {
                        $this->app_db[$grupo]['password'] = '';
                    } else {
                        $this->app_db[$grupo]['password'] = $db['password'];
                    }

                    if (!isset($app_db[$grupo]['charset'])) {
                        $this->app_db[$grupo]['charset'] = 'utf8';
                    } else {
                        $this->app_db[$grupo]['charset'] = $db['charset'];
                    }

                }

            } else {

                $this->app_db['dafault'] = [
                    'driver' => 'mysql',
                    'db' => 'piecesphp',
                    'user' => 'root',
                    'password' => '',
                    'host' => 'localhost',
                    'charset' => 'utf8',
                ];

            }

            set_config("database", $this->app_db);

        }
    }

    /**
     * initAppKeyConfig
     *
     * Configura la llave de la aplicación
     *
     * @return void
     */
    public function initAppKeyConfig()
    {
        if (get_config('app_key') !== false && is_string(get_config('app_key'))) {
            $this->app_key = get_config('app_key');
        } else {
            $this->app_key = 'secret';
        }
        set_config('app_key', $this->app_key);
    }

    /**
     * initLangByURLConfig
     *
     * Configura la si la aplicación tomará el idioma de la url
     *
     * @return void
     */
    public function initLangByURLConfig()
    {
        $lang_by_url = get_config('lang_by_url') === true;
        set_config('lang_by_url', $lang_by_url);
    }

    /**
     * initAppAllowedLangsConfig
     *
     * Configura los idiomas permitidos
     *
     * @return void
     */
    public function initAppAllowedLangsConfig()
    {
        if (get_config('allowed_langs') !== false && is_array(get_config('allowed_langs'))) {
            self::$app_allowed_langs = get_config('allowed_langs');
        } else {
            self::$app_allowed_langs = ['es'];
		}
        set_config('allowed_langs', self::$app_allowed_langs);
    }

    /**
     * initAppLangConfig
     *
     * Configura el idioma por defecto de la aplicación
     *
     * @return void
     */
    public function initAppLangConfig()
    {
        $default_app_lang = get_config('default_lang');
        $app_lang = get_config('app_lang');

        $is_set_default_lang = $default_app_lang !== false && is_string($default_app_lang);
        $is_set_lang = $app_lang !== false && is_string($app_lang);

        if ($is_set_default_lang) {
            self::$default_app_lang = $default_app_lang;
        }

        if ($is_set_lang) {
            self::$app_lang = $app_lang;
        } else {
            self::$app_lang = $default_app_lang;
        }

        set_config('default_lang', self::$default_app_lang);
		set_config('app_lang', self::$app_lang);

		usort(self::$app_allowed_langs, function($a, $b) use ($default_app_lang){
			if($a == $default_app_lang){
				return -1;
			}elseif($b == $default_app_lang){
				return 1;
			}else{
				return 0;
			}

		});

		set_config('allowed_langs', self::$app_allowed_langs);
		
    }

    /**
     * initAppBaseConfig
     *
     * Configura la la url base de la aplicación
     *
     * @return void
     */
    public function initAppBaseConfig()
    {
        if ($this->app_base === null) {
            if (get_config('base_url') !== false && is_string(get_config('base_url'))) {
                $base_url = get_config('base_url');
                $this->app_url = string_compare(last_char($base_url), '/') ? $base_url : $base_url . "/";
                $this->app_base = str_replace([
                    "http://" . $_SERVER['HTTP_HOST'] . "/",
                    "https://" . $_SERVER['HTTP_HOST'] . "/",
                ], "", $this->app_url);
            } else {
                $this->app_base = self::get_app_base();
                $this->app_url = $this->app_protocol . "://" . str_replace("//", "/", $_SERVER['HTTP_HOST'] . "/" . $this->app_base);
            }
        }

        set_config("base_app", $this->app_base);
        set_config("base_url", $this->app_url);
    }

    /**
     * initAppCookiesConfig
     *
     * Configura las opciones de la cookies de la aplicación
     *
     * @return void
     */
    public function initAppCookiesConfig()
    {
        if ($this->app_cookies === null) {
            if (get_config('cookies') !== false && is_array(get_config('cookies'))) {

                $this->app_cookies = get_config('cookies');

                if (!isset($this->app_cookies['lifetime'])) {
                    $this->app_cookies['lifetime'] = 0;
                }

                if (!isset($this->app_cookies['path'])) {
                    $this->app_cookies['path'] = '/';
                }
                if (!isset($this->app_cookies['domain'])) {
                    $this->app_cookies['domain'] = $_SERVER['HTTP_HOST'];
                }
                if (!isset($this->app_cookies['secure'])) {
                    $this->app_cookies['secure'] = false;
                }
                if (!isset($this->app_cookies['httponly'])) {
                    $this->app_cookies['httponly'] = false;
                }
            } else {
                $this->app_cookies = [
                    'lifetime' => 0,
                    'path' => '/',
                    'domain' => $_SERVER['HTTP_HOST'],
                    'secure' => false,
                    'httponly' => false,
                ];
            }

            //Aplicando configuraciones
            if (session_status() == PHP_SESSION_NONE) {
                ini_set('session.cookie_lifetime', $this->app_cookies['lifetime']);
                ini_set('session.cookie_path', $this->app_cookies['path']);
                ini_set('session.cookie_domain', $this->app_cookies['domain']);
                ini_set('session.cookie_secure', $this->app_cookies['secure']);
                ini_set('session.cookie_httponly', $this->app_cookies['httponly']);
            }

            set_config("cookies", $this->app_cookies);
        }
    }

    /**
     * initAppRolesConfig
     *
     * Configura los roles de la aplicación
     *
     * @return void
     */
    public function initAppRolesConfig()
    {
        if (get_config('roles') !== false && is_array(get_config('roles'))) {

            $roles = get_config('roles');

            if ($roles['active']) {
                if (!Roles::hasRoles()) {
                    Roles::registerRoles($roles['types']);
                }
            }

        }
    }

    /**
     * init
     *
     * Establece todas las configuraciones de la aplicación
     *
     * @return void
     */
    public static function init()
    {
        if (self::$instance === null) {
            self::$instance = new Config();
            self::set_lang_by_url();
        }
    }

    /**
     * set_config
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public static function set_config(string $name, $value)
    {
        self::$configurations[$name] = $value;
    }

    /**
     * get_config
     *
     * @param string $name
     * @return mixed|boolean Devuelve el valor o false si no existe o es null
     */
    public static function get_config(string $name)
    {
        if (isset(self::$configurations[$name]) && self::$configurations[$name] !== null) {
            return self::$configurations[$name];
        } else {
            return false;
        }
    }

    /**
     * Establece el lenguaje de la aplicación
     * @param string $lang Lo ideal es que sea el nombre del archivo correspondiente en app/lang
     * @return void
     */
    public static function set_lang($lang = "es")
    {
        if (in_array($lang, self::$app_allowed_langs)) {
            self::$app_lang = $lang;
        } else {
            self::$app_lang = 'es';
        }
    }

    /**
     * Establece el lenguaje de la aplicación según la URL
     * @param string $lang Lo ideal es que sea el nombre del archivo correspondiente en app/lang
     * @return void
     */
    public static function set_lang_by_url()
    {
        $url_lang = get_part_request(1);
        $lang_by_url = get_config('lang_by_url');
        if ($lang_by_url === true) {
            if (in_array($url_lang, self::$app_allowed_langs)) {
                self::$app_lang = $url_lang;
            } else {
                self::$app_lang = self::$default_app_lang;
            }
        }

        self::$prefix_lang = self::$app_lang == self::$default_app_lang ? '' : '/' . self::$app_lang;

        set_config('prefix_lang', self::$prefix_lang);
        set_config('app_lang', self::$app_lang);
    }

    /**
     * Obtiene el lenguaje de la aplicación
     * @return string
     */
    public static function get_lang()
    {
        return self::$app_lang;
    }

    /**
     * Obtiene los lenguajes permitidos
     * @return string[]
     */
    public static function get_allowed_langs()
    {
        return self::$app_allowed_langs;
    }

    /**
     * Devuelve la ruta de directorio de la aplicación
     * @return string
     */
    public static function app_path()
    {
        $instance = self::get_instance();
        return realpath($instance->app_path);
    }

    /**
     * Devuelve la ruta relativa del directorio de la aplicación
     * @return string
     */
    public static function app_base()
    {
        $instance = self::get_instance();
        return $instance->app_base;

    }

    /**
     * Devuelve la url base de la aplicación
     * @return string
     */
    public static function app_url()
    {
        $instance = self::get_instance();
        return $instance->app_url;

    }

    /**
     * Devuelve la configuración de la base de datos de la aplicación
     * @param string $key Nombre de la configuración deseada
     * @return array
     */
    public static function app_db(string $key)
    {
        $instance = self::get_instance();
        return $instance->app_db[$key];

    }

    /**
     * Devuelve la llave de la app
     * @return string
     */
    public static function app_key()
    {
        $instance = self::get_instance();
        return $instance->app_key;

    }

    /**
     * Devuelve el título de la app
     * @return string
     */
    public static function app_title()
    {
        $instance = self::get_instance();
        return $instance->app_title;

    }

    /**
     * Devuelve el directorio relativo de la aplicación
     * @return string
     */
    private static function get_app_base()
    {
        $app_base = str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
        $app_base = mb_substr($app_base, 1);
        return $app_base;
    }

    /**
     * Devuelve una instancia de Config
     * @return Config
     */
    private static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }

        return self::$instance;
    }

}
