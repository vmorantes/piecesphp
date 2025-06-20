<?php

/**
 * Config.php
 */
namespace PiecesPHP\Core;

use Collator;
use PiecesPHP\LangInjector;
use ReflectionClass;

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

    /** @var array Lenguajes permitidos por la aplicación */
    protected static $appAllowedLangs = ['es'];

    /** @var array Códigos de localidad según lenguaje */
    protected static $appLocaleLangs = [
        'es' => 'es_CO.utf8',
    ];

    /** @var string Valor de localidad establecida */
    protected static $appSettedLocale = 'es_CO.utf8';

    /** @var array Configuraciones */
    protected static $configurations = [];

    /** @var array Traducciones */
    protected static $translations = [];

    /** @var Config Instancia */
    protected static $instance = null;

    /** @var ReflectionClass */
    protected static $reflectedClass = null;

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
        $this->initLangByCookieConfig();
        $this->initLangByBrowserConfig();
        $this->initLangByURLConfig();
        $this->initAppLangConfig();
        $this->initAppLocaleLangs();
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

        $directoySeparator = \DIRECTORY_SEPARATOR;
        $this->appPath = __DIR__ . "{$directoySeparator}..{$directoySeparator}..{$directoySeparator}";

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
                    $groupConfig = is_array($groupConfig) && !empty($groupConfig) ? $groupConfig : null;

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
     * Configura si la aplicación tomará el lenguaje de la cookie
     *
     * @return void
     */
    public function initLangByCookieConfig()
    {
        $configName = 'lang_by_cookie';
        $lang_by_cookie = get_config($configName) === true;
        set_config($configName, $lang_by_cookie);
    }

    /**
     * Configura si la aplicación tomará el lenguaje por defecto del navegador
     *
     * @return void
     */
    public function initLangByBrowserConfig()
    {
        $configName = 'lang_by_browser';
        $lang_by_browser = get_config($configName) === true;
        set_config($configName, $lang_by_browser);
    }

    /**
     * Configura si la aplicación tomará el lenguaje de la url
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
     * Configura los lenguajes permitidos
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
     * Configura el lenguaje por defecto de la aplicación
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
     * Configura los códigos de localidad según lenguaje
     *
     * @return void
     */
    public function initAppLocaleLangs()
    {
        $defaultLangConfigName = 'default_lang';
        $appLocaleLangsConfigName = 'locale_langs';
        $appSettedLocaleConfigName = 'current_locale';

        $defaultAppLang = get_config($defaultLangConfigName);
        $appLocaleLangs = get_config($appLocaleLangsConfigName);

        self::$appLocaleLangs = $appLocaleLangs;

        set_config($appLocaleLangsConfigName, self::$appLocaleLangs);

        uksort(self::$appLocaleLangs, function ($a, $b) use ($defaultAppLang) {
            if ($a == $defaultAppLang) {
                return -1;
            } elseif ($b == $defaultAppLang) {
                return 1;
            } else {
                return 0;
            }

        });

        set_config($appLocaleLangsConfigName, self::$appLocaleLangs);
        set_config($appSettedLocaleConfigName, self::$appSettedLocale);

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
        $langInjector = new LangInjector(__DIR__ . "/../lang/", $allowedLangs);
        $langInjector->inject();
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
                    Roles::registerRoles($roles['types'], true);
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
            self::$instance = new static();
            self::set_lang_strategy();
            self::set_locale_from_current_lang();
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
        self::set_config('pcsphp_system_translations', self::$translations);

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

        //Preparación de configuraciones de búsqueda para mensajes
        $t = self::$translations;
        $str = $message;
        $messageIsEmpty = $message === '';

        $currentLang = self::get_config('app_lang');

        $searchOnLangs = [
            $currentLang,
            'default',
        ];

        if ($forceLang !== null) {

            $searchOnLangs = [
                $forceLang,
                'default',
                $currentLang,
            ];

            if ($forceLang === self::get_config('default_lang')) {

                $searchOnLangs = [
                    $forceLang,
                    'default',
                ];

            }

        }

        //Perparación de configuraciones de registro de mensajes no traducidos
        $messageHash = sha1($message);
        $missingMessagesBaseFolderName = 'lang/missing-lang-messages';
        $missingMessagesBaseFolderPath = app_basepath("{$missingMessagesBaseFolderName}");
        $missingMessagesGroupFolderPath = app_basepath("{$missingMessagesBaseFolderName}/{$groupName}");
        $missingMessagesLangFolderPath = app_basepath("{$missingMessagesBaseFolderName}/{$groupName}/{$currentLang}");
        $missingMessagesMessageFilePath = app_basepath("{$missingMessagesBaseFolderName}/{$groupName}/{$currentLang}/{$messageHash}.to-translate");

        //Buscar
        foreach ($searchOnLangs as $lang) {

            $isMissingMessage = true;

            if ($lang !== null) {

                if (array_key_exists($lang, $t)) {

                    $langData = $t[$lang];

                    if (array_key_exists($groupName, $langData)) {

                        $groupData = $langData[$groupName];

                        if (array_key_exists($message, $groupData) || $messageIsEmpty) {

                            $isMissingMessage = false;
                            if ($messageIsEmpty) {
                                $str = $groupData;
                            } else {
                                $str = $groupData[$message];
                                //Si existe, eliminarlo de la lista de faltantes
                                if (file_exists($missingMessagesMessageFilePath)) {
                                    @unlink($missingMessagesMessageFilePath);
                                }
                            }
                            break;
                        }

                    }

                }

                //Si el mensaje no existe y no fue un mensaje vacío
                $noScanLangs = get_config('no_scan_langs');
                $noScanLangs = is_array($noScanLangs) ? $noScanLangs : [];
                $noScanLangGroups = get_config('no_scan_lang_groups');
                $noScanLangGroups = is_array($noScanLangGroups) ? $noScanLangGroups : [];
                if (!in_array($currentLang, $noScanLangs) && !in_array($groupName, $noScanLangGroups)) {
                    if ($isMissingMessage && !$messageIsEmpty) {
                        $missingMessagesFoldersPaths = [
                            $missingMessagesBaseFolderPath,
                            $missingMessagesGroupFolderPath,
                            $missingMessagesLangFolderPath,
                        ];
                        array_map(fn($e) => (!file_exists($e) ? make_directory($e) : null), $missingMessagesFoldersPaths);
                        if (!file_exists($missingMessagesMessageFilePath)) {
                            file_put_contents($missingMessagesMessageFilePath, $message);
                            chmod($missingMessagesMessageFilePath, 0777);
                        }

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

        $namesOnInstance = [
            'title_app' => 'appTitle',
            'database' => 'appDB',
            'app_key' => 'appKey',
        ];

        $namesOnStatic = [
            'allowed_langs' => 'appAllowedLangs',
            'default_lang' => 'defaultAppLang',
            'app_lang' => 'appLang',
            'prefix_lang' => 'prefixLang',
            'locale_langs' => 'appLocaleLangs',
            'current_locale' => 'appSettedLocale',
        ];

        if (self::$reflectedClass === null) {
            self::$reflectedClass = new ReflectionClass(self::class);
        }

        if (array_key_exists($name, $namesOnStatic)) {

            $propertyToSet = $namesOnStatic[$name];
            $reflectedProperty = self::$reflectedClass->getProperty($propertyToSet);
            $reflectedProperty->setAccessible(true);
            $reflectedProperty->setValue($value);

            if ($name == 'app_lang') {
                //Establecer nuevamente setlocale en caso de cambiar el lenguaje
                self::set_locale_from_current_lang();
            }

        } elseif (self::$instance !== null) {

            if (array_key_exists($name, $namesOnInstance)) {

                $instance = self::get_instance();
                $propertyToSet = $namesOnInstance[$name];
                $instance->$propertyToSet = $value;

            }

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
     * Establece setlocale a partir del lenguaje actual de la aplicación.
     * @param int[] $categories Por defecto [\LC_COLLATE, \LC_CTYPE, \LC_TIME, \LC_MESSAGES (Solo si existe) ]
     * @return void
     */
    public static function set_locale_from_current_lang(array $categories = [\LC_COLLATE, \LC_CTYPE, \LC_TIME, 'LC_MESSAGES'])
    {
        $allowedCategories = [
            \LC_ALL,
            \LC_COLLATE,
            \LC_CTYPE,
            \LC_TIME,
            \LC_MONETARY,
            \LC_NUMERIC,
            'LC_MESSAGES',
        ];

        $usedCategories = [];
        $localeLang = isset(self::$appLocaleLangs[self::$appLang]) ? self::$appLocaleLangs[self::$appLang] : null;
        $localeLang = is_scalar($localeLang) ? [$localeLang] : (is_array($localeLang) ? $localeLang : null);

        if (is_array($localeLang)) {

            foreach ($localeLang as $k => $i) {

                if (!is_scalar($i)) {
                    unset($localeLang[$k]);
                }

            }

            $settedLocale = self::$appSettedLocale;
            if ((is_string($settedLocale) && mb_strlen($settedLocale) == 0) || !is_string($settedLocale)) {
                $settedLocale = 'en_US';
            }

            foreach ($categories as $category) {

                if ($category === 'LC_MESSAGES') {

                    if (defined('LC_MESSAGES')) {
                        $category = \LC_MESSAGES;
                    } else {
                        $category = null;
                    }

                }

                if (in_array($category, $allowedCategories)) {

                    if (!in_array($category, $usedCategories)) {
                        $setLocaleResult = setlocale($category, $localeLang);
                        if (is_string($setLocaleResult) && mb_strlen($setLocaleResult) > 0) {
                            $settedLocale = $setLocaleResult;
                        }
                    }

                }

            }

            self::$appSettedLocale = $settedLocale;

        }

    }

    /**
     * Establece el lenguaje de la aplicación (actual y por defecto) según diversas estrategias, de momento: Cookie, Navegador y cookie. En ese orden de prioridad.
     * @return void
     */
    public static function set_lang_strategy()
    {
        $lang_by_url = get_config('lang_by_url');
        $lang_by_cookie = get_config('lang_by_cookie');
        $lang_by_browser = get_config('lang_by_browser');
        $appLangByURL = null;
        $appLangByCookie = null;
        $appLangByBrowser = null;
        $allowedLangs = self::$appAllowedLangs;

        /* Selección de lenguaje ACTUAL */

        /**
         * Por URL, se define según el primer segmento de la URL
         */
        $url_lang = get_part_request(1);
        if ($lang_by_url === true) {
            if (in_array($url_lang, $allowedLangs)) {
                $appLangByURL = $url_lang;
            } else {
                $appLangByURL = self::$defaultAppLang;
            }
        }

        /**
         * Por navegador, se define según el lenguaje preferido del navegador
         */
        if ($lang_by_browser === true) {
            $preferedLang = getPreferredLanguageByHeader($allowedLangs, self::$defaultAppLang);
            $appLangByBrowser = $preferedLang;
        }

        /**
         * Por cookie, se define según el valor de la cookie
         * El valor de usado es la configuración 'cookie_lang_definer', para establecer el cambio de lenguaje por primera vez
         * se usa el parámetro de URL 'i18n'
         */
        if ($lang_by_cookie === true) {

            $cookieName = get_config('cookie_lang_definer');
            $cookieName = is_string($cookieName) && mb_strlen(trim($cookieName)) > 0 ? $cookieName : uniqid();
            $urlParamLangName = 'i18n';

            //Configurar cookie desde URL o usar el último valor
            $i18nURLValue = isset($_GET) && array_key_exists($urlParamLangName, $_GET) ? $_GET[$urlParamLangName] : null;
            $i18nURLValue = $i18nURLValue !== null ? $i18nURLValue : getCookie($cookieName);
            $selectedLang = null;
            if (is_string($i18nURLValue)) {
                if ($i18nURLValue == 'default') {
                    setCookieByConfig($cookieName, null);
                } else {
                    $selectedLang = $i18nURLValue;
                    setCookieByConfig($cookieName, $i18nURLValue);
                }
            }

            //Definir el lenguaje
            $selectedLang = is_string($selectedLang) ? $selectedLang : '-1';
            if (in_array($selectedLang, $allowedLangs)) {
                $appLangByCookie = $selectedLang;
            }
        }

        /* Establecer lenguaje actual según prioridad de las estrategias usadas */
        $isLangByURL = false;
        $isLangByBrowser = false;
        $isLangByCookie = false;
        $prefixLang = '';
        if ($appLangByCookie !== null) {
            self::$appLang = $appLangByCookie;
            $isLangByCookie = true;
        } else if ($appLangByBrowser !== null) {
            self::$appLang = $appLangByBrowser;
            $isLangByBrowser = true;
        } else if ($appLangByURL !== null) {
            self::$appLang = $appLangByURL;
            $prefixLang = self::$appLang == self::$defaultAppLang ? '' : '/' . self::$appLang;
            $isLangByURL = true;
        } else {
            self::$appLang = self::$defaultAppLang;
        }

        /* Ajustes según prioridades */

        //Caso: No se seleecionó mediante URL
        if (!$isLangByURL) {

            //De cualquier forma, se establece el prefijo de lenguaje según la selección por URL para evitar conflictos si está activo el soporte de URL
            $prefixLang = $appLangByURL == self::$defaultAppLang ? '' : '/' . $appLangByURL;

            //Caso: Está activo el soporte por URL pero se seleccionó mediante navegador
            if ($isLangByBrowser) {
                $hasPrefix = mb_strlen($prefixLang) > 0;
                //Caso: Existe declaración explícita de lenguaje en URL
                if ($hasPrefix) {
                    //Se usa el lenguaje de la URL
                    self::$appLang = $appLangByURL;
                }
            }

        }
        self::$prefixLang = $prefixLang;

        //Establecer configuración de prefijo de lenguaje y lenguaje actual
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
     * @param bool $order
     * @param string $firsLang
     * @return string[]
     */
    public static function get_allowed_langs(bool $order = false, ?string $firsLang = null)
    {
        $appAllowedLangs = self::$appAllowedLangs;
        $currentLang = self::get_lang();

        if ($order) {
            $collator = new Collator($currentLang);
            $collator->sort($appAllowedLangs);
        }

        if ($firsLang !== null) {
            $appAllowedLangs = array_merge([$firsLang], array_diff($appAllowedLangs, [$firsLang]));
        }

        return $appAllowedLangs;
    }

    /**
     * Verifica si un lenguaje está permitido
     * @param string $lang
     * @return bool
     */
    public static function is_allowed_lang($lang)
    {
        $appAllowedLangs = self::$appAllowedLangs;
        return is_string($lang) && in_array($lang, $appAllowedLangs);
    }

    /**
     * Códigos de localidad según lenguaje
     * @return array
     */
    public static function get_locale_langs()
    {
        return self::$appLocaleLangs;
    }

    /**
     * Valor de localidad establecida
     * @return string
     */
    public static function get_current_locale()
    {
        return self::$appSettedLocale;
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
     * @param bool $withLang
     * @return string
     */
    public static function baseurl(string $resource = "", bool $withLang = false)
    {
        $app_url = Config::app_url();
        $defaultLang = Config::get_default_lang();
        $currentLang = Config::get_lang();

        if (mb_strlen($resource > 0) && $resource[0] == "/") {
            $resource = remove_first_char($resource);
        }

        if (last_char($app_url) == "/") {
            $app_url = remove_last_char($app_url);
        }

        if ($withLang && $currentLang !== $defaultLang) {
            $app_url .= '/' . $currentLang;
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
     * Las traducciones registradas
     *
     * @return array
     */
    public static function get_translations()
    {
        return self::$translations;
    }

    /**
     * Devuelve una instancia de Config
     * @return static
     */
    private static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

}
