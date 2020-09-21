<?php

/**
 * Config.php
 */
namespace PiecesPHP\Core;

use PiecesPHP\Core\Helpers\Directories\DirectoryObject;

/**
 * Config - Clase para manejar las configuraciones de la aplicación.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class Config
{

    /**
     * Grupo de configuración de conexión por defecto
     */
    const DATABASE_CONFIG_DEFAULT_GROUP = '';

    /**
     * Valores de configuración de conexión por defecto
     */
    const DATABASE_CONFIG_DEFAULT_VALUES = [
        'driver' => 'mysql',
        'host' => 'localhost',
        'db' => 'piecesphp',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8',
    ];

    /** @var string Título de la aplicación */
    protected $appTitle = null;

    /** @var string Ruta raíz de la aplicación (ruta de directorio) */
    protected $appPath = null;

    /** @var string Ruta relativa de la aplicación es como $appPath, pero sin el DocumentRoot */
    protected $appBase = null;

    /** @var string Protocolo de la aplicación http/https */
    protected $appProtocol = null;

    /** @var string URL de la aplicación es el HOST concatenado al $appBase */
    protected $appURL = null;

    /**
     * @var array Configuración de la base de datos
     */
    protected $appDB = null;

    /** @var string Llave usada para la encriptación en la aplicación */
    protected $appKey = null;

    /** @var array Configuración de las cookies para session.cookie_* de PHP*/
    protected $appCookies = null;

    /** @var string Lenguaje por defecto de la aplicación */
    protected static $defaultAppLang = 'es';

    /** @var string Lenguaje de la aplicación (actual) */
    protected static $appLang = 'es';

    /** @var string Prefijo de lenguaje de la aplicación */
    protected static $prefixLang = '';

    /** @var array Leguajes permitidos por la aplicación */
    protected static $appAllowedLangs = ['es'];

    /** @var array Configuraciones */
    protected static $configurations = [];

    /** @var array Traducciones */
    protected static $translations = [];

    /** @var Config Instancia */
    protected static $instance = null;

    /**
     * @ignore
     */
    private function __construct()
    {
        $this->initAppConfigs();
    }

    /**
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
        $this->initAppTranslations();
        $this->initAppBaseConfig();
        $this->initAppCookiesConfig();
        $this->initAppRolesConfig();
    }

    /**
     * Configura el título de la app (title_app)
     *
     * @return void
     */
    public function initTitleAppConfig()
    {
        $configName = 'title_app';
        $configValue = get_config($configName);

        if ($configValue !== false && is_string($configValue)) {
            $this->appTitle = $configValue;
        } else {
            $this->appTitle = "Title App";
        }
        set_config($configValue, $this->appTitle);

    }

    /**
     * Configura el ROOT path de la aplicación
     *
     * @return void
     */
    public function initAppPathConfig()
    {

        $this->appPath = __DIR__ . "/../../";

    }

    /**
     * Configura el protocolo de la aplicación
     *
     * @return void
     */
    public function initAppProtocolConfig()
    {

        $this->appProtocol = isset($_SERVER['HTTPS']) ? "https" : "http";

    }

    /**
     * Configura la base de datos de la aplicación
     *
     * @return void
     */
    public function initAppDBConfig()
    {
        if ($this->appDB === null) {

            $configName = 'database';
            $configValue = get_config($configName);

            if ($configValue !== false && is_array($configValue)) {

                $this->appDB = [];

                $configKeys = array_keys(self::DATABASE_CONFIG_DEFAULT_VALUES);

                foreach ($configValue as $groupName => $groupConfig) {

                    $groupName = is_string($groupName) && mb_strlen($groupName) > 0 ? $groupName : null;
                    $groupConfig = is_array($groupConfig) && count($groupConfig) > 0 ? $groupConfig : null;

                    if ($groupName !== null && $groupConfig !== null) {

                        if (!array_key_exists($groupName, $this->appDB)) {
                            $this->appDB[$groupName] = self::DATABASE_CONFIG_DEFAULT_VALUES;
                        }

                        foreach ($configKeys as $configKey) {

                            $inputConfig = isset($groupConfig[$configKey]) && is_string($groupConfig[$configKey]) ? $groupConfig[$configKey] : null;

                            if ($inputConfig !== null) {
                                $this->appDB[$groupName][$configKey] = $inputConfig;
                            } else if ($configKey == 'db') {
                                $this->appDB[$groupName][$configKey] = '';
                            }

                        }

                    } else {
                        $this->appDB = [
                            self::DATABASE_CONFIG_DEFAULT_GROUP => self::DATABASE_CONFIG_DEFAULT_VALUES,
                        ];
                    }

                }

            } else {

                $this->appDB = [
                    self::DATABASE_CONFIG_DEFAULT_GROUP => self::DATABASE_CONFIG_DEFAULT_VALUES,
                ];

            }

            set_config($configName, $this->appDB);

        }
    }

    /**
     * Configura la llave de la aplicación
     *
     * @return void
     */
    public function initAppKeyConfig()
    {

        $configName = 'app_key';
        $configValue = get_config($configName);

        if ($configValue !== false && is_string($configValue)) {
            $this->appKey = $configValue;
        } else {
            $this->appKey = 'secret';
        }
        set_config($configName, $this->appKey);
    }

    /**
     * Configura la si la aplicación tomará el idioma de la url
     *
     * @return void
     */
    public function initLangByURLConfig()
    {
        $configName = 'lang_by_url';
        $lang_by_url = get_config($configName) === true;
        set_config($configName, $lang_by_url);
    }

    /**
     * Configura los idiomas permitidos
     *
     * @return void
     */
    public function initAppAllowedLangsConfig()
    {

        $configName = 'allowed_langs';
        $configValue = get_config($configName);

        if ($configValue !== false && is_array($configValue)) {
            self::$appAllowedLangs = $configValue;
        } else {
            self::$appAllowedLangs = ['es'];
        }
        set_config($configName, self::$appAllowedLangs);
    }

    /**
     * Configura el idioma por defecto de la aplicación
     *
     * @return void
     */
    public function initAppLangConfig()
    {
        $defaultLangConfigName = 'default_lang';
        $appLangConfigName = 'app_lang';
        $allowedLangsConfigName = 'allowed_langs';

        $defaultAppLang = get_config($defaultLangConfigName);
        $appLang = get_config($appLangConfigName);

        $is_set_default_lang = $defaultAppLang !== false && is_string($defaultAppLang);
        $is_set_lang = $appLang !== false && is_string($appLang);

        if ($is_set_default_lang) {
            self::$defaultAppLang = $defaultAppLang;
        }

        if ($is_set_lang) {
            self::$appLang = $appLang;
        } else {
            self::$appLang = $defaultAppLang;
        }

        set_config($defaultLangConfigName, self::$defaultAppLang);
        set_config($appLangConfigName, self::$appLang);

        usort(self::$appAllowedLangs, function ($a, $b) use ($defaultAppLang) {
            if ($a == $defaultAppLang) {
                return -1;
            } elseif ($b == $defaultAppLang) {
                return 1;
            } else {
                return 0;
            }

        });

        set_config($allowedLangsConfigName, self::$appAllowedLangs);

    }

    /**
     * Configura las traducciones
     *
     * @return void
     */
    public function initAppTranslations()
    {

        $allowedLangs = self::$appAllowedLangs;
        $allowedLangs[] = 'default';

        foreach ($allowedLangs as $langName) {

            $langFile = __DIR__ . "/../lang/{$langName}.php";

            if (file_exists($langFile)) {

                $langData = include_once $langFile;

                if (is_array($langData)) {

                    foreach ($langData as $groupName => $messages) {

                        if (is_array($messages)) {

                            foreach ($messages as $messageKey => $messageValue) {

                                if (is_scalar($messageKey) && is_scalar($messageValue)) {
                                    self::addLangMessage($langName, $groupName, $messageKey, $messageValue);
                                }

                            }

                        }

                    }

                }

            }

        }

        $directoryFilesLang = new DirectoryObject(__DIR__ . "/../lang/files");

        $directoryFilesLang->process();

        $directories = $directoryFilesLang->getDirectories();

        foreach ($directories as $directory) {

            $groupName = $directory->getBasename();
            $files = $directory->getFiles();

            foreach ($files as $file) {

                $filename = $file->getBasename();

                if (strpos(mb_strtolower($filename), '.html') !== false) {

                    $messageKey = preg_replace("|^(.*)\.html$|i", '$1', $filename);
                    $belongToLang = mb_strtolower(preg_replace("|^.*\-(.*)\.html$|i", '$1', $filename));
                    $messageValue = @file_get_contents($file->getPath());

                    if (in_array($belongToLang, $allowedLangs)) {
                        $messageKey = preg_replace("|^(.*)\-{$belongToLang}$|i", '$1', $messageKey);
                    } else {
                        $belongToLang = 'default';
                    }

                    self::addLangMessage($belongToLang, $groupName, $messageKey, $messageValue);

                }

            }
        }
    }

    /**
     * Configura la la url base de la aplicación
     *
     * @return void
     */
    public function initAppBaseConfig()
    {

        $configBaseURLName = 'base_url';
        $configBaseURLValue = get_config($configBaseURLName);
        $configBaseAppName = 'base_app';

        if ($this->appBase === null) {

            if ($configBaseURLValue !== false && is_string($configBaseURLValue)) {

                $this->appURL = string_compare(last_char($configBaseURLValue), '/') ? $configBaseURLValue : $configBaseURLValue . "/";

                $this->appBase = str_replace([
                    "http://" . $_SERVER['HTTP_HOST'] . "/",
                    "https://" . $_SERVER['HTTP_HOST'] . "/",
                ], "", $this->appURL);

            } else {
                $this->appBase = self::get_app_base();
                $this->appURL = $this->appProtocol . "://" . str_replace("//", "/", $_SERVER['HTTP_HOST'] . "/" . $this->appBase);
            }

        }

        set_config($configBaseAppName, $this->appBase);
        set_config($configBaseURLName, $this->appURL);
    }

    /**
     * Configura las opciones de la cookies de la aplicación
     *
     * @return void
     */
    public function initAppCookiesConfig()
    {
        if ($this->appCookies === null) {

            $configName = 'cookies';
            $configValue = get_config($configName);

            $default = [
                'lifetime' => 0,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => false,
                'httponly' => false,
            ];

            if ($configValue !== false && is_array($configValue)) {

                $this->appCookies = $configValue;

                foreach ($default as $key => $value) {
                    if (!isset($this->appCookies[$key])) {
                        $this->appCookies[$key] = $value;
                    }
                }

            } else {
                $this->appCookies = [
                    'lifetime' => 0,
                    'path' => '/',
                    'domain' => $_SERVER['HTTP_HOST'],
                    'secure' => false,
                    'httponly' => false,
                ];
            }

            //Aplicando configuraciones
            if (session_status() == PHP_SESSION_NONE) {
                ini_set('session.cookie_lifetime', $this->appCookies['lifetime']);
                ini_set('session.cookie_path', $this->appCookies['path']);
                ini_set('session.cookie_domain', $this->appCookies['domain']);
                ini_set('session.cookie_secure', $this->appCookies['secure']);
                ini_set('session.cookie_httponly', $this->appCookies['httponly']);
            }

            set_config($configName, $this->appCookies);
        }
    }

    /**
     * Configura los roles de la aplicación
     *
     * @return void
     */
    public function initAppRolesConfig()
    {

        $configName = 'roles';
        $configValue = get_config($configName);

        if ($configValue !== false && is_array($configValue)) {

            $roles = $configValue;

            if ($roles['active']) {
                if (!Roles::hasRoles()) {
                    Roles::registerRoles($roles['types']);
                }
            }

        }
    }

    /**
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
     * Agrega una traducción
     *
     * @param string $lang
     * @param string $groupName
     * @param string $messageKey
     * @param string $messageValue
     * @return void
     */
    public static function addLangMessage(string $lang, string $groupName, string $messageKey, string $messageValue)
    {

        $t = self::$translations;

        if (!array_key_exists($lang, $t)) {
            $t[$lang] = [];
        }

        if (!array_key_exists($groupName, $t[$lang])) {
            $t[$lang][$groupName] = [];
        }

        $t[$lang][$groupName][$messageKey] = $messageValue;

        self::$translations = $t;

    }

    /**
     * Devuelve la traducción en caso de existir
     *
     * Si $echo es true retorna el string y hace un echo de este (el echo es omitido si $message es '').
     * Si $echo es false retorna un string correspondiente al mensaje.
     * Si $message es '' devuelve el array completo de mensajes en $groupName
     *
     * @param string $groupName
     * @param string $message
     * @param bool $echo
     * @param string $forceLang
     * @return string|string[]
     */
    public static function i18n(string $groupName, string $message = '', bool $echo = false, string $forceLang = null)
    {

        $t = self::$translations;
        $str = $message;

        $currentLang = self::get_config('app_lang');
        $searchOnLangs = [
            $forceLang,
            $currentLang,
            'default',
        ];

        foreach ($searchOnLangs as $lang) {

            if ($lang === null) {
                continue;
            }

            if (array_key_exists($lang, $t)) {

                $langData = $t[$lang];

                if (array_key_exists($groupName, $langData)) {

                    $groupData = $langData[$groupName];

                    if (array_key_exists($message, $groupData) || $message === '') {

                        if ($message === '') {
                            $str = $groupData;
                        } else {
                            $str = $groupData[$message];
                        }
                        break;
                    }

                }

            }

        }

        if ($echo && $message !== '') {
            echo $str;
        }

        return $str;

    }

    /**
     * Establece el valor de la configuración señalada.
     *
     * @param string $name Nombre de la configuración
     * @param mixed $value Valor que se establecerá
     * @return void
     */
    public static function set_config(string $name, $value)
    {
        self::$configurations[$name] = $value;

        if ($name == 'title_app' && self::$instance !== null) {
            $instance = self::get_instance();
            $instance->appTitle = $value;
        }
    }

    /**
     * Obtiene el valor de la configuración solicitada.
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
        if (in_array($lang, self::$appAllowedLangs)) {
            self::$appLang = $lang;
        } else {
            self::$appLang = 'es';
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
            if (in_array($url_lang, self::$appAllowedLangs)) {
                self::$appLang = $url_lang;
            } else {
                self::$appLang = self::$defaultAppLang;
            }
        }

        self::$prefixLang = self::$appLang == self::$defaultAppLang ? '' : '/' . self::$appLang;

        set_config('prefix_lang', self::$prefixLang);
        set_config('app_lang', self::$appLang);
    }

    /**
     * Obtiene el lenguaje por defecto de la aplicaión
     * @return string
     */
    public static function get_default_lang()
    {
        return self::$defaultAppLang;
    }

    /**
     * Obtiene el lenguaje de la aplicación (el lenguaje actual)
     * @return string
     */
    public static function get_lang()
    {
        return self::$appLang;
    }

    /**
     * Obtiene los lenguajes permitidos
     * @return string[]
     */
    public static function get_allowed_langs()
    {
        return self::$appAllowedLangs;
    }

    /**
     * Devuelve la ruta de directorio de la aplicación
     * @return string
     */
    public static function app_path()
    {
        $instance = self::get_instance();
        return realpath($instance->appPath);
    }

    /**
     * Devuelve la ruta relativa del directorio de la aplicación
     * @return string
     */
    public static function app_base()
    {
        $instance = self::get_instance();
        return $instance->appBase;

    }

    /**
     * Devuelve la url base de la aplicación
     * @return string
     */
    public static function app_url()
    {
        $instance = self::get_instance();
        return $instance->appURL;

    }

    /**
     * Devuelve la configuración de la base de datos de la aplicación
     * @param string $key Nombre de la configuración deseada
     * @return array
     */
    public static function app_db(string $key)
    {
        $instance = self::get_instance();
        return $instance->appDB[$key];

    }

    /**
     * Devuelve la llave de la app
     * @return string
     */
    public static function app_key()
    {
        $instance = self::get_instance();
        return $instance->appKey;

    }

    /**
     * Devuelve el título de la app
     * @return string
     */
    public static function app_title()
    {
        $instance = self::get_instance();
        return $instance->appTitle;

    }

    /**
     * Obtiene Config::app_url() y lo une a $resource
     *
     * @param string $resource
     * @return string
     */
    public static function baseurl(string $resource = "")
    {
        $app_url = Config::app_url();

        if (mb_strlen($resource > 0) && $resource[0] == "/") {
            $resource = remove_first_char($resource);
        }

        if (last_char($app_url) == "/") {
            $app_url = remove_last_char($app_url);
        }

        $url = $app_url . '/' . $resource;

        return $url;
    }

    /**
     * Obtiene PiecesPHP\Core\Config::app_path() y lo une a "/".$resource
     *
     * @param string $resource
     * @return string
     */
    public static function basepath(string $resource = "")
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
    public static function app_basepath($resource = "")
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
     * Devuelve el directorio relativo de la aplicación
     * @return string
     */
    private static function get_app_base()
    {
        $appBase = str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
        $appBase = mb_substr($appBase, 1);
        return $appBase;
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
