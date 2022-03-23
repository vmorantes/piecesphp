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

use App\Controller\AppConfigController;
use App\Model\UsersModel;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Exceptions\RouteDuplicateNameException;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\StringManipulate;
use Spatie\Url\Url as URLManager;

/**
 * Obtiene el valor de la configuración solicitada.
 *
 * @param string $name Nombre de la configuración
 * @return mixed|boolean Devuelve el valor o false si no existe o es null
 */
function get_config(string $name)
{
    if (array_key_exists($name, array_flip(AppConfigController::SEO_OPTIONS_CONFIG_NAME_BY_FORM_NAME))) {

        $defaultLang = Config::get_default_lang();
        $currentLang = Config::get_lang();

        if ($defaultLang !== $currentLang) {
            $name .= "_{$currentLang}";
        }

    }
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
 * @param bool $reverse
 *
 * @return string
 */
function get_title(bool $appendTitleApp = false, string $separator = null, bool $reverse = true)
{
    $title = get_config('title');
    $title_app = get_config('title_app');
    $separator = $separator !== null ? $separator : ' - ';

    if ($title !== false) {
        if ($appendTitleApp && $title_app != $title) {
            return $reverse ? $title_app . $separator . get_config('title') : get_config('title') . $separator . $title_app;
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
 * @return void
 */
function set_title(string $title)
{
    mb_strlen($title) > 0 ? set_config('title', $title) : set_config('title', false);
}

/**
 * Obtiene el formato de fecha preferido para el idioma actual
 * @return string
 */
function get_default_format_date()
{
    $default = 'Y-m-d';
    $format = get_config('format_date_lang');
    $format = is_array($format) && isset($format[Config::get_lang()]) ? $format[Config::get_lang()] : null;
    return is_string($format) ? $format : $default;
}

/**
 * @param string $resource
 * @param bool $withLang
 * @return string
 */
function baseurl(string $resource = "", bool $withLang = false)
{
    return Config::baseurl($resource, $withLang);
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
    return convert_lang_url(get_current_url(), $current_lang, $target_lang);
}

/**
 * Convierte la url basado en el idioma actual en su par de otro idioma
 * Nota: Solo si lang_by_url es true
 *
 * @param string $input_url
 * @param string $current_lang
 * @param string $target_lang
 * @return string
 */
function convert_lang_url($input_url, $current_lang = 'es', $target_lang = 'en')
{

    $default_lang = get_config('default_lang');
    $target_is_default = $target_lang == $default_lang;
    $current_is_default = $current_lang == $default_lang;

    $current_is_same_target = $current_lang == $target_lang || Config::get_lang() == $target_lang;

    $lang_url = '';

    if (!$current_is_same_target) {

        $input_url_end_slash = last_char($input_url) === '/';

        $base_url = !$current_is_default ? base_url($current_lang) : base_url();

        $protocol_input_url = mb_strpos($input_url, 'https://') !== false ? 'https://' : 'http://';
        $protocol_base_url = mb_strpos($base_url, 'https://') !== false ? 'https://' : 'http://';

        $input_url = str_replace($protocol_input_url, '', $input_url);
        $base_url = str_replace($protocol_base_url, '', $base_url);
        $segment_url = str_replace($base_url, '', $input_url);

        $segments_url = array_filter(explode('/', $segment_url), function ($e) {
            return mb_strlen(trim($e)) > 0;
        });
        $segment_url = implode('/', $segments_url);

        $lang_url = !$target_is_default ? baseurl("{$target_lang}/$segment_url") : baseurl("$segment_url");
        $lang_url = str_replace($protocol_base_url, $protocol_input_url, $lang_url);

        $lang_url_end_slash = last_char($lang_url) === '/';

        if ($input_url_end_slash && !$lang_url_end_slash) {
            $lang_url .= '/';
        }

    } else {
        $lang_url = $input_url;
    }

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
    return Config::basepath($resource);
}

/**
 * Obtiene PiecesPHP\Core\Config::app_path() y lo une a "/app/".$resource
 *
 * @param string $resource
 * @return string
 */
function app_basepath($resource = "")
{
    return Config::app_basepath($resource);
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
 * @return string|string[]
 * Si $echo es true retorna el string y hace un echo de este.
 * Si $echo es false retorna un string correspondiente al mensaje.
 * Si $message es '' devuelve el array completo de mensajes en $type
 */
function __(string $type, string $message = '', bool $echo = false)
{
    return Config::i18n($type, $message, $echo);
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
 * @return string|string[]
 * Si $echo es true retorna el string y hace un echo de este.
 * Si $echo es false retorna un string correspondiente al mensaje.
 * Si $message es '' devuelve el array completo de mensajes en $type
 */
function lang(string $type, string $message, string $lang, bool $echo = false)
{
    return Config::i18n($type, $message, $echo, $lang);
}

/**
 * @param bool $update
 * @return string
 */
function static_files_cache_stamp(bool $update = false)
{
    $stamp = 'none';
    $fileCache = basepath('app/cache/statics-files-stamp.txt');

    if (file_exists($fileCache) && !$update) {
        $stamp = file_get_contents($fileCache);
        $stamp = substr($stamp, 0, 40);
    } else {
        $stamp = sha1(uniqid());
        file_put_contents($fileCache, $stamp);
    }
    return $stamp;
}

/**
 * Imprime los scripts js cargados con las funciones auxiliares de assets
 *
 * @param array $config
 * @var string $config['base_url']
 * @var string $config['custom_url']
 * @var array<string,string> $config['attr']
 * @var array<string,string[]> $config['attrApplyTo']
 * @var array<string,string[]> $config['attrNoApplyTo']
 * @return void
 */
function load_js(array $config = array())
{
    $global_assets = get_config('global_assets');
    $custom_assets = get_config('custom_assets');

    ksort($global_assets['js']);
    ksort($custom_assets['js']);

    $jsGlobal = $global_assets['js'];
    $jsCustom = $custom_assets['js'];

    /**
     * @return array<string,string>
     */
    $processAttr = function (array $config, array $currentAttr, string $src) {

        $attrApplyTo = array_key_exists('attrApplyTo', $config) ? $config['attrApplyTo'] : [];
        $attrApplyTo = is_array($attrApplyTo) ? $attrApplyTo : [];

        foreach ($attrApplyTo as $k => $i) {

            $unsetElement = false;

            if (!is_string($k)) {
                $unsetElement = true;
            }

            if (!is_array($i)) {
                $unsetElement = true;
            } else {
                foreach ($i as $ki => $ii) {
                    if (!is_string($ii)) {
                        $unsetElement = true;
                        break;
                    }
                }
            }

            if ($unsetElement) {
                unset($attrApplyTo[$k]);
            }

        }

        $attrNoApplyTo = array_key_exists('attrNoApplyTo', $config) ? $config['attrNoApplyTo'] : [];
        $attrNoApplyTo = is_array($attrNoApplyTo) ? $attrNoApplyTo : [];

        foreach ($attrNoApplyTo as $k => $i) {

            $unsetElement = false;

            if (!is_string($k)) {
                $unsetElement = true;
            }

            if (!is_array($i)) {
                $unsetElement = true;
            } else {
                foreach ($i as $ki => $ii) {
                    if (!is_string($ii)) {
                        $unsetElement = true;
                        break;
                    }
                }
            }

            if ($unsetElement) {
                unset($attrNoApplyTo[$k]);
            }

        }

        $attr = array_key_exists('attr', $config) ? $config['attr'] : [];
        $attr = is_array($attr) ? $attr : [];

        foreach ($attr as $k => $i) {

            $unsetElement = false;

            if (!is_string($k)) {
                $unsetElement = true;
            }

            if (!is_string($i)) {
                $unsetElement = true;
            }

            if ($unsetElement) {
                unset($attr[$k]);
            }

        }

        foreach ($attr as $k => $i) {

            $allowedTo = array_key_exists($k, $attrApplyTo) ? $attrApplyTo[$k] : null;
            $excludeTo = array_key_exists($k, $attrNoApplyTo) ? $attrNoApplyTo[$k] : null;

            if (is_array($allowedTo)) {

                $isAllowed = false;

                foreach ($allowedTo as $regexp) {

                    $regexp = str_replace('/', '\/', $regexp);
                    $isMatch = preg_match('/' . $regexp . '/i', $src) === 1;

                    if ($isMatch) {
                        $isAllowed = true;
                        break;
                    }

                }

                if (is_array($excludeTo)) {

                    foreach ($excludeTo as $regexp) {

                        $regexp = str_replace('/', '\/', $regexp);
                        $isMatch = preg_match('/' . $regexp . '/i', $src) === 1;

                        if ($isMatch) {
                            $isAllowed = false;
                            break;
                        }

                    }

                }

                if ($isAllowed) {
                    $currentAttr[$k] = $i;
                }

            }

        }

        return $currentAttr;

    };

    /**
     * @return string
     */
    $processElement = function (array $config, string $src, array $ingoreConfig = []) use ($processAttr) {

        $defaultConfig = [
            'base_url' => [
                'outputName' => 'baseURL',
                'default' => '',
            ],
            'custom_url' => [
                'outputName' => 'baseURL',
                'default' => '',
                'overwrite' => true,
            ],
        ];

        $configValues = [];
        $attributes = [];

        foreach ($defaultConfig as $configName => $configOptions) {

            $outputName = array_key_exists('outputName', $configOptions) ? $configOptions['outputName'] : $configName;
            $defaultValue = array_key_exists('default', $configOptions) ? $configOptions['default'] : null;
            $onAttribute = array_key_exists('onAttribute', $configOptions) ? $configOptions['onAttribute'] : null;
            $overwrite = array_key_exists('overwrite', $configOptions) ? $configOptions['overwrite'] : false;

            if (!in_array($configName, $ingoreConfig)) {

                $isConfigured = array_key_exists($outputName, $configValues);
                $exists = array_key_exists($configName, $config);
                $value = $exists ? $config[$configName] : $defaultValue;

                if (!$isConfigured || ($exists && $overwrite)) {

                    $configValues[$outputName] = $value;

                    if (is_string($onAttribute)) {
                        $attributes[$onAttribute] = $value;
                    }

                }

            }

        }

        $path = $configValues['baseURL'] . $src;
        $attributes['src'] = $path;

        $attributes = ($processAttr)($config, $attributes, $src);

        $attributesString = [];

        foreach ($attributes as $ka => $ia) {
            unset($attributes[$ka]);
            $attributesString[] = "{$ka}='{$ia}'";
        }

        $attributesString = implode(' ', $attributesString);
        $element = "<script {$attributesString}></script>";
        return $element;

    };

    $stamp = static_files_cache_stamp();
    foreach ($jsGlobal as $script) {
        $url = URLManager::fromString($script);
        $url = $stamp !== 'none' ? $url->withQueryParameter('cacheStamp', $stamp) : $url;
        $script = $url->__toString();
        $tag = ($processElement)($config, $script, [
            'custom_url',
        ]);
        echo $tag . "\n";
    }

    foreach ($jsCustom as $script) {
        $url = URLManager::fromString($script);
        $url = $stamp !== 'none' ? $url->withQueryParameter('cacheStamp', $stamp) : $url;
        $script = $url->__toString();
        $tag = ($processElement)($config, $script);
        echo $tag . "\n";
    }
}

/**
 * Imprime los link css cargados con las funciones auxiliares de assets
 *
 * @param array $config
 * @var string $config['rel']
 * @var string $config['base_url']
 * @var string $config['custom_url']
 * @var array<string,string> $config['attr']
 * @var array<string,string[]> $config['attrApplyTo']
 * @var array<string,string[]> $config['attrNoApplyTo']
 * @return void
 */
function load_css(array $config = array())
{
    $global_assets = get_config('global_assets');
    $custom_assets = get_config('custom_assets');

    ksort($global_assets['css']);
    ksort($custom_assets['css']);

    $cssGlobal = $global_assets['css'];
    $cssCustom = $custom_assets['css'];

    /**
     * @return array<string,string>
     */
    $processAttr = function (array $config, array $currentAttr, string $src) {

        $attrApplyTo = array_key_exists('attrApplyTo', $config) ? $config['attrApplyTo'] : [];
        $attrApplyTo = is_array($attrApplyTo) ? $attrApplyTo : [];

        foreach ($attrApplyTo as $k => $i) {

            $unsetElement = false;

            if (!is_string($k)) {
                $unsetElement = true;
            }

            if (!is_array($i)) {
                $unsetElement = true;
            } else {
                foreach ($i as $ki => $ii) {
                    if (!is_string($ii)) {
                        $unsetElement = true;
                        break;
                    }
                }
            }

            if ($unsetElement) {
                unset($attrApplyTo[$k]);
            }

        }

        $attrNoApplyTo = array_key_exists('attrNoApplyTo', $config) ? $config['attrNoApplyTo'] : [];
        $attrNoApplyTo = is_array($attrNoApplyTo) ? $attrNoApplyTo : [];

        foreach ($attrNoApplyTo as $k => $i) {

            $unsetElement = false;

            if (!is_string($k)) {
                $unsetElement = true;
            }

            if (!is_array($i)) {
                $unsetElement = true;
            } else {
                foreach ($i as $ki => $ii) {
                    if (!is_string($ii)) {
                        $unsetElement = true;
                        break;
                    }
                }
            }

            if ($unsetElement) {
                unset($attrNoApplyTo[$k]);
            }

        }

        $attr = array_key_exists('attr', $config) ? $config['attr'] : [];
        $attr = is_array($attr) ? $attr : [];

        foreach ($attr as $k => $i) {

            $unsetElement = false;

            if (!is_string($k)) {
                $unsetElement = true;
            }

            if (!is_string($i)) {
                $unsetElement = true;
            }

            if ($unsetElement) {
                unset($attr[$k]);
            }

        }

        foreach ($attr as $k => $i) {

            $allowedTo = array_key_exists($k, $attrApplyTo) ? $attrApplyTo[$k] : null;
            $excludeTo = array_key_exists($k, $attrNoApplyTo) ? $attrNoApplyTo[$k] : null;

            if (is_array($allowedTo)) {

                $isAllowed = false;

                foreach ($allowedTo as $regexp) {

                    $regexp = str_replace('/', '\/', $regexp);
                    $isMatch = preg_match('/' . $regexp . '/i', $src) === 1;

                    if ($isMatch) {
                        $isAllowed = true;
                        break;
                    }

                }

                if (is_array($excludeTo)) {

                    foreach ($excludeTo as $regexp) {

                        $regexp = str_replace('/', '\/', $regexp);
                        $isMatch = preg_match('/' . $regexp . '/i', $src) === 1;

                        if ($isMatch) {
                            $isAllowed = false;
                            break;
                        }

                    }

                }

                if ($isAllowed) {
                    $currentAttr[$k] = $i;
                }

            }

        }

        return $currentAttr;

    };

    /**
     * @return string
     */
    $processElement = function (array $config, string $src, array $ingoreConfig = []) use ($processAttr) {

        $defaultConfig = [
            'rel' => [
                'outputName' => 'rel',
                'default' => 'stylesheet',
                'onAttribute' => 'rel',
            ],
            'base_url' => [
                'outputName' => 'baseURL',
                'default' => '',
            ],
            'custom_url' => [
                'outputName' => 'baseURL',
                'default' => '',
                'overwrite' => true,
            ],
        ];

        $configValues = [];
        $attributes = [];

        foreach ($defaultConfig as $configName => $configOptions) {

            $outputName = array_key_exists('outputName', $configOptions) ? $configOptions['outputName'] : $configName;
            $defaultValue = array_key_exists('default', $configOptions) ? $configOptions['default'] : null;
            $onAttribute = array_key_exists('onAttribute', $configOptions) ? $configOptions['onAttribute'] : null;
            $overwrite = array_key_exists('overwrite', $configOptions) ? $configOptions['overwrite'] : false;

            if (!in_array($configName, $ingoreConfig)) {

                $isConfigured = array_key_exists($outputName, $configValues);
                $exists = array_key_exists($configName, $config);
                $value = $exists ? $config[$configName] : $defaultValue;

                if (!$isConfigured || ($exists && $overwrite)) {

                    $configValues[$outputName] = $value;

                    if (is_string($onAttribute)) {
                        $attributes[$onAttribute] = $value;
                    }

                }

            }

        }

        $path = $configValues['baseURL'] . $src;
        $attributes['href'] = $path;

        $attributes = ($processAttr)($config, $attributes, $src);

        $attributesString = [];

        foreach ($attributes as $ka => $ia) {
            unset($attributes[$ka]);
            $attributesString[] = "{$ka}='{$ia}'";
        }

        $attributesString = implode(' ', $attributesString);
        $element = "<link {$attributesString}>";
        return $element;

    };

    $stamp = static_files_cache_stamp();
    foreach ($cssGlobal as $stylesheet) {
        $url = URLManager::fromString($stylesheet);
        $url = $stamp !== 'none' ? $url->withQueryParameter('cacheStamp', $stamp) : $url;
        $stylesheet = $url->__toString();
        $tag = ($processElement)($config, $stylesheet, [
            'custom_url',
        ]);
        echo $tag . "\n";
    }

    foreach ($cssCustom as $stylesheet) {
        $url = URLManager::fromString($stylesheet);
        $url = $stamp !== 'none' ? $url->withQueryParameter('cacheStamp', $stamp) : $url;
        $stylesheet = $url->__toString();
        $tag = ($processElement)($config, $stylesheet);
        echo $tag . "\n";
    }

}
/**
 * Imprime los link de fuentes cargados con las funciones auxiliares de assets
 *
 * @param array $config
 * @var string $config['base_url']
 * @var string $config['custom_url']
 * @var array<string,string> $config['attr']
 * @var array<string,string[]> $config['attrApplyTo']
 * @var array<string,string[]> $config['attrNoApplyTo']
 * @return void
 */
function load_font(array $config = array())
{
    $global_assets = get_config('global_assets');
    $custom_assets = get_config('custom_assets');

    ksort($global_assets['font']);
    ksort($custom_assets['font']);

    $fontGlobal = $global_assets['font'];
    $fontCustom = $custom_assets['font'];

    /**
     * @return array<string,string>
     */
    $processAttr = function (array $config, array $currentAttr, string $src) {

        $attrApplyTo = array_key_exists('attrApplyTo', $config) ? $config['attrApplyTo'] : [];
        $attrApplyTo = is_array($attrApplyTo) ? $attrApplyTo : [];

        foreach ($attrApplyTo as $k => $i) {

            $unsetElement = false;

            if (!is_string($k)) {
                $unsetElement = true;
            }

            if (!is_array($i)) {
                $unsetElement = true;
            } else {
                foreach ($i as $ki => $ii) {
                    if (!is_string($ii)) {
                        $unsetElement = true;
                        break;
                    }
                }
            }

            if ($unsetElement) {
                unset($attrApplyTo[$k]);
            }

        }

        $attrNoApplyTo = array_key_exists('attrNoApplyTo', $config) ? $config['attrNoApplyTo'] : [];
        $attrNoApplyTo = is_array($attrNoApplyTo) ? $attrNoApplyTo : [];

        foreach ($attrNoApplyTo as $k => $i) {

            $unsetElement = false;

            if (!is_string($k)) {
                $unsetElement = true;
            }

            if (!is_array($i)) {
                $unsetElement = true;
            } else {
                foreach ($i as $ki => $ii) {
                    if (!is_string($ii)) {
                        $unsetElement = true;
                        break;
                    }
                }
            }

            if ($unsetElement) {
                unset($attrNoApplyTo[$k]);
            }

        }

        $attr = array_key_exists('attr', $config) ? $config['attr'] : [];
        $attr = is_array($attr) ? $attr : [];

        foreach ($attr as $k => $i) {

            $unsetElement = false;

            if (!is_string($k)) {
                $unsetElement = true;
            }

            if (!is_string($i)) {
                $unsetElement = true;
            }

            if ($unsetElement) {
                unset($attr[$k]);
            }

        }

        foreach ($attr as $k => $i) {

            $allowedTo = array_key_exists($k, $attrApplyTo) ? $attrApplyTo[$k] : null;
            $excludeTo = array_key_exists($k, $attrNoApplyTo) ? $attrNoApplyTo[$k] : null;

            if (is_array($allowedTo)) {

                $isAllowed = false;

                foreach ($allowedTo as $regexp) {

                    $regexp = str_replace('/', '\/', $regexp);
                    $isMatch = preg_match('/' . $regexp . '/i', $src) === 1;

                    if ($isMatch) {
                        $isAllowed = true;
                        break;
                    }

                }

                if (is_array($excludeTo)) {

                    foreach ($excludeTo as $regexp) {

                        $regexp = str_replace('/', '\/', $regexp);
                        $isMatch = preg_match('/' . $regexp . '/i', $src) === 1;

                        if ($isMatch) {
                            $isAllowed = false;
                            break;
                        }

                    }

                }

                if ($isAllowed) {
                    $currentAttr[$k] = $i;
                }

            }

        }

        return $currentAttr;

    };

    /**
     * @return string
     */
    $processElement = function (array $config, string $src, array $ingoreConfig = []) use ($processAttr) {

        $defaultConfig = [
            'base_url' => [
                'outputName' => 'baseURL',
                'default' => '',
            ],
            'custom_url' => [
                'outputName' => 'baseURL',
                'default' => '',
                'overwrite' => true,
            ],
        ];

        $configValues = [];
        $attributes = [];

        foreach ($defaultConfig as $configName => $configOptions) {

            $outputName = array_key_exists('outputName', $configOptions) ? $configOptions['outputName'] : $configName;
            $defaultValue = array_key_exists('default', $configOptions) ? $configOptions['default'] : null;
            $onAttribute = array_key_exists('onAttribute', $configOptions) ? $configOptions['onAttribute'] : null;
            $overwrite = array_key_exists('overwrite', $configOptions) ? $configOptions['overwrite'] : false;

            if (!in_array($configName, $ingoreConfig)) {

                $isConfigured = array_key_exists($outputName, $configValues);
                $exists = array_key_exists($configName, $config);
                $value = $exists ? $config[$configName] : $defaultValue;

                if (!$isConfigured || ($exists && $overwrite)) {

                    $configValues[$outputName] = $value;

                    if (is_string($onAttribute)) {
                        $attributes[$onAttribute] = $value;
                    }

                }

            }

        }

        $path = $configValues['baseURL'] . $src;
        $attributes['href'] = $path;
        $attributes['is-preload-custom'] = 'yes';
        $attributes['rel'] = 'preload';
        $attributes['as'] = 'style';

        $attributes = ($processAttr)($config, $attributes, $src);

        $attributesString = [];

        foreach ($attributes as $ka => $ia) {
            unset($attributes[$ka]);
            $attributesString[] = "{$ka}='{$ia}'";
        }

        $attributesString = implode(' ', $attributesString);
        $element = "<link {$attributesString}>";
        return $element;

    };

    $stamp = static_files_cache_stamp();
    foreach ($fontGlobal as $stylesheet) {
        $url = URLManager::fromString($stylesheet);
        $url = $stamp !== 'none' ? $url->withQueryParameter('cacheStamp', $stamp) : $url;
        $stylesheet = $url->__toString();
        $tag = ($processElement)($config, $stylesheet, [
            'custom_url',
        ]);
        echo $tag . "\n";
    }

    foreach ($fontCustom as $stylesheet) {
        $url = URLManager::fromString($stylesheet);
        $url = $stamp !== 'none' ? $url->withQueryParameter('cacheStamp', $stamp) : $url;
        $stylesheet = $url->__toString();
        $tag = ($processElement)($config, $stylesheet);
        echo $tag . "\n";
    }

}

/**
 * Verifica que el asset global solicitado exista, de ser así devuelve el índice en el array
 * de assets globales según el tipo que corresponda, si no existe devuelve false
 *
 * @param string $asset
 * @param string $type
 * @return int|false
 */
function has_global_asset(string $asset, string $type)
{

    $global_assets = get_config('global_assets');
    $assets = [];
    $index_asset = false;

    if ($type == 'js') {
        $assets = $global_assets[$type];
    } else if ($type == 'css') {
        $assets = $global_assets[$type];
    } else if ($type == 'font') {
        $assets = $global_assets[$type];
    }

    if ($type == 'js' || $type == 'css' || $type == 'font') {

        $index_asset = array_search($asset, $assets);

    }

    return $index_asset;
}

/**
 * Añade un asset global del tipo definido, devuelve true en caso de que la operación sea existosa, esto es; cuando
 * agrega el elemento o el elemento ya existe, false en caso contrario.
 *
 * @param string $asset
 * @param string $type
 * @return bool
 */
function add_global_asset(string $asset, string $type)
{
    $global_assets = get_config('global_assets');
    $exists = isset($global_assets[$type]) && in_array($asset, $global_assets[$type]);

    if (is_string($asset) && ($type == "js" || $type == "css" || $type == "font")) {

        if (!$exists) {

            if ($type == "js") {

                $global_assets['js'][count($global_assets['js'])] = $asset;
                set_config('global_assets', $global_assets);

                return true;

            } else if ($type == "css") {

                $global_assets['css'][count($global_assets['css'])] = $asset;
                set_config('global_assets', $global_assets);

                return true;

            } else if ($type == "font") {

                $global_assets['font'][count($global_assets['font'])] = $asset;
                set_config('global_assets', $global_assets);

                return true;

            }

        }

        return true;

    } else {
        return false;
    }

}

/**
 * Verifica si existe el asset en los globales y si es de tipo requerido
 *
 * @param string $asset
 * @param string $type
 * @return bool
 */
function is_global_required_asset(string $asset, string $type)
{
    return index_global_required_asset($asset, $type) !== null;
}

/**
 * Busca el índice del asset global requerido del tipo que corresponda, si existe devuelve el índice,
 * de lo contrario devuelve null
 *
 * @param string $asset
 * @param string $type
 * @return int|null
 */
function index_global_required_asset(string $asset, string $type)
{
    $global_requireds_assets = get_config('global_requireds_assets');
    $index = null;

    if (is_array($global_requireds_assets) && mb_strlen($asset) > 0 && ($type == 'css' || $type == 'js' || $type == 'font')) {

        if (array_key_exists($type, $global_requireds_assets)) {

            $assets = $global_requireds_assets[$type];

            if (in_array($asset, $assets)) {
                $index = array_search($asset, $assets);
            }

        }

    }

    return $index;

}

/**
 * Añade un asset del tipo correspondiente a la lista de globales requeridos
 *
 * @param string $asset
 * @param string $type
 * @return void
 */
function add_global_required_asset(string $asset, string $type)
{
    $global_requireds_assets = get_config('global_requireds_assets');
    $global_requireds_assets = is_array($global_requireds_assets) ? $global_requireds_assets : [
        'css' => [],
        'js' => [],
        'font' => [],
    ];

    $add = add_global_asset($asset, $type);

    if ($add) {
        $global_requireds_assets[$type][] = $asset;
        set_config('global_requireds_assets', $global_requireds_assets);
    }

}

/**
 * Añade múltiples assets del tipo correspondiente a la lista de globales (itera sobre add_global_asset)
 *
 * @param string[] $custom_assets
 * @param string $type
 * @return void
 */
function add_global_assets(array $custom_assets, string $type)
{
    foreach ($custom_assets as $asset) {
        add_global_asset($asset, $type);
    }
}

/**
 * Añade múltiples assets del tipo correspondiente a la lista de globales requeridos (itera sobre add_global_required_asset)
 *
 * @param string[] $custom_assets
 * @param string $type
 * @return void
 */
function add_global_requireds_assets(array $custom_assets, string $type)
{
    foreach ($custom_assets as $asset) {
        add_global_required_asset($asset, $type);
    }
}

/**
 * Remueve un asset global, devuelve el índice si fue removido, devuelve false si no existe
 * o en caso de no removerse por ser requerido (para lo cual debe usarse remove_global_required_asset)
 *
 * @param string $asset
 * @param string $type
 * @return false|int
 */
function remove_global_asset(string $asset, string $type)
{

    if (!is_global_required_asset($asset, $type)) {

        $global_assets = get_config('global_assets');
        $index_asset = has_global_asset($asset, $type);

        if ($index_asset !== false) {
            unset($global_assets[$type][$index_asset]);
        }

        set_config('global_assets', $global_assets);

        return $index_asset;

    } else {
        return false;
    }

}

/**
 * Remueve un asset global requerido
 *
 * @param string $asset
 * @param string $type
 * @return void
 */
function remove_global_required_asset(string $asset, string $type)
{
    $index = index_global_required_asset($asset, $type);

    if ($index !== null) {
        $global_requireds_assets = get_config('global_requireds_assets');
        unset($global_requireds_assets[$type][$index]);
        remove_global_asset($asset, $type);
    }

}

/**
 * Remueve un asset importado
 *
 * @param string $assetName
 * @return void
 */
function remove_imported_asset(string $assetName)
{

    $importedAssets = get_config('imported_assets');
    $defaultAssets = get_config('default_assets');
    if (array_key_exists($assetName, $defaultAssets) && array_key_exists($assetName, $importedAssets)) {

        $assetsSources = $defaultAssets[$assetName];
        $extractScalar = function ($array) use (&$extractScalar) {
            $scalars = [];
            foreach ($array as $element) {

                if (is_array($element)) {
                    $subScalars = ($extractScalar)($element);
                    foreach ($subScalars as $subElement) {
                        $scalars[] = $subElement;
                    }
                } else {
                    $scalars[] = $element;
                }

            }
            return $scalars;
        };

        $sourcesToRemove = array_unique(($extractScalar)($assetsSources));

        foreach ($sourcesToRemove as $source) {
            remove_global_required_asset($source, 'css');
            remove_global_required_asset($source, 'js');
            remove_global_required_asset($source, 'font');
            remove_global_asset($source, 'css');
            remove_global_asset($source, 'js');
            remove_global_asset($source, 'font');
        }
        unset($importedAssets[$assetName]);
        set_config('imported_assets', $importedAssets);
    }

}

/**
 * Define la lista de assets no globales, segun el tipo que corresponda.
 * Es una definición por fuerza bruta, cuide de pasar los valores adecuados.
 *
 * @param string[] $custom_assets
 * @param string $type
 * @return void
 */
function set_custom_assets(array $custom_assets, string $type)
{
    $_custom_assets = get_config('custom_assets');
    if ($type == "js") {
        $_custom_assets['js'] = $custom_assets;
        set_config('custom_assets', $_custom_assets);
    } else if ($type == "css") {
        $_custom_assets['css'] = $custom_assets;
        set_config('custom_assets', $_custom_assets);
    } else if ($type == "font") {
        $_custom_assets['font'] = $custom_assets;
        set_config('custom_assets', $_custom_assets);
    }
}

/**
 * Define la lista de assets globales, segun el tipo que corresponda.
 * Es una definición por fuerza bruta, cuide de pasar los valores adecuados.
 *
 * @param string[] $assets
 * @param string $type
 * @return void
 */
function set_global_assets(array $assets, string $type)
{
    $global_assets = get_config('global_assets');
    if ($type == "js") {
        $global_assets['js'] = $assets;
        set_config('global_assets', $global_assets);
    } else if ($type == "css") {
        $global_assets['css'] = $assets;
        set_config('global_assets', $global_assets);
    } else if ($type == "font") {
        $global_assets['font'] = $assets;
        set_config('global_assets', $global_assets);
    }
}

/**
 * Borra todos las librerías front importadas por import_front_library
 *
 * @return void
 */
function clear_assets_imports()
{

    $libraries = get_config('default_assets');
    $imported = get_config('imported_assets');
    $cssFiles = [];
    $jsFiles = [];
    $fontFiles = [];

    set_config('imported_assets', []);

    foreach ($imported as $name => $plugins) {

        $library = $libraries[$name];
        $library_css = is_array($library) && isset($library['css']) && is_array($library['css']) ? $library['css'] : [];
        $library_js = is_array($library) && isset($library['js']) && is_array($library['js']) ? $library['js'] : [];
        $library_font = is_array($library) && isset($library['font']) && is_array($library['font']) ? $library['font'] : [];
        $plugins = is_array($plugins) ? $plugins : [];

        foreach ($library_css as $i) {
            if (is_string($i) && mb_strlen($i) > 0) {
                $cssFiles[] = $i;
            }
        }

        foreach ($library_js as $i) {
            if (is_string($i) && mb_strlen($i) > 0) {
                $jsFiles[] = $i;
            }
        }

        foreach ($library_font as $i) {
            if (is_string($i) && mb_strlen($i) > 0) {
                $fontFiles[] = $i;
            }
        }

        foreach ($plugins as $plugin) {

            $plugin_data = $library['plugins'][$plugin];
            $plugin_css = is_array($plugin_data) && isset($plugin_data['css']) && is_array($plugin_data['css']) ? $plugin_data['css'] : [];
            $plugin_js = is_array($plugin_data) && isset($plugin_data['js']) && is_array($plugin_data['js']) ? $plugin_data['js'] : [];
            $plugin_font = is_array($plugin_data) && isset($plugin_data['font']) && is_array($plugin_data['font']) ? $plugin_data['font'] : [];

            foreach ($plugin_css as $i) {
                if (is_string($i) && mb_strlen($i) > 0) {
                    $cssFiles[] = $i;
                }
            }

            foreach ($plugin_js as $i) {
                if (is_string($i) && mb_strlen($i) > 0) {
                    $jsFiles[] = $i;
                }
            }

            foreach ($plugin_font as $i) {
                if (is_string($i) && mb_strlen($i) > 0) {
                    $fontFiles[] = $i;
                }
            }

        }

    }

    foreach ($cssFiles as $i) {
        remove_global_asset($i, 'css');
    }

    foreach ($jsFiles as $i) {
        remove_global_asset($i, 'js');
    }

    foreach ($fontFiles as $i) {
        remove_global_asset($i, 'font');
    }
}

/**
 * Borra todos las globales (front)
 *
 * @return void
 */
function clear_global_assets()
{

    $global_assets = get_config('global_assets');
    $global_assets = is_array($global_assets) ? $global_assets : [
        'css' => [],
        'js' => [],
        'font' => [],
    ];

    $css = isset($global_assets['css']) && is_array($global_assets['css']) ? $global_assets['css'] : [];
    $js = isset($global_assets['js']) && is_array($global_assets['js']) ? $global_assets['js'] : [];
    $font = isset($global_assets['font']) && is_array($global_assets['font']) ? $global_assets['font'] : [];

    foreach ($css as $i) {

        if (is_string($i) && mb_strlen($i) > 0) {
            remove_global_asset($i, 'css');
        }

    }

    foreach ($js as $i) {

        if (is_string($i) && mb_strlen($i) > 0) {
            remove_global_asset($i, 'js');
        }

    }

    foreach ($font as $i) {

        if (is_string($i) && mb_strlen($i) > 0) {
            remove_global_asset($i, 'font');
        }

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
            $has_font = array_key_exists('font', $library);
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
            if ($has_font) {
                $font = $library['font'];

                if (is_array($font)) {
                    add_global_assets($font, 'font');
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
                        $plugin_has_font = array_key_exists('font', $_plugin);
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

                        if ($plugin_has_font) {
                            $_plugin_font = $_plugin['font'];

                            if (is_array($_plugin_font)) {
                                add_global_assets($_plugin_font, 'font');
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

        $imported = get_config('imported_assets');

        $plugins_importeds = isset($imported) && isset($imported[$name]) ? $imported[$name] : [];
        $plugins_to_delete = [];

        foreach ($plugins_importeds as $plugin_imported) {
            if (!in_array($plugin_imported, $plugins)) {
                $plugins_to_delete[] = $plugin_imported;
                unset($imported[$name][array_search($plugin_imported, $imported[$name])]);
            }
        }

        set_config('imported_assets', $imported);

        foreach ($plugins_to_delete as $plugin_to_delete) {

            $plugin_files = $library['plugins'][$plugin_to_delete];
            $plugin_css_files = is_array($plugin_files) && isset($plugin_files['css']) ? $plugin_files['css'] : [];
            $plugin_js_files = is_array($plugin_files) && isset($plugin_files['js']) ? $plugin_files['js'] : [];
            $plugin_font_files = is_array($plugin_files) && isset($plugin_files['font']) ? $plugin_files['font'] : [];

            foreach ($plugin_css_files as $plugin_css_file) {
                remove_global_asset($plugin_css_file, 'css');
            }

            foreach ($plugin_js_files as $plugin_js_file) {
                remove_global_asset($plugin_js_file, 'js');
            }

            foreach ($plugin_font_files as $plugin_font_file) {
                remove_global_asset($plugin_font_file, 'font');
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
 * Registra jqueryui como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_jqueryui(array $plugins = [], bool $all = false)
{
    import_front_library('jqueryui', $plugins, $all);
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
 * Registra el editor por defecto como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_default_rich_editor(array $plugins = [], bool $all = true)
{
    import_front_library('defaultRichEditor', $plugins, $all);
    if (function_exists('import_elfinder')) {
        import_elfinder();
    }
}

/**
 * Registra app_front_libraries como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_app_front_libraries(array $plugins = [], bool $all = false)
{
    import_front_library('app_front_libraries', $plugins, $all);
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
 * Registra simple_upload_placeholder como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_simple_upload_placeholder(array $plugins = [], bool $all = true)
{
    import_front_library('simple_upload_placeholder', $plugins, $all);
}

/**
 * Registra fancybox3 como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_fancybox3(array $plugins = [], bool $all = true)
{
    import_front_library('fancybox3', $plugins, $all);
}

/**
 * Registra elfinder como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_elfinder(array $plugins = [], bool $all = true)
{
    import_front_library('elfinder', $plugins, $all);
}

/**
 * Registra google_captcha_v3_adapter como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_google_captcha_v3_adapter(array $plugins = [], bool $all = true)
{
    import_front_library('google_captcha_v3_adapter', $plugins, $all);
}

/**
 * Registra locations como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins Plugins disponibles: autoInit
 * @param bool $all
 * @param bool $locationsPointBehavior Usa import_mapbox y statics/features/locations/js/locations-config.js
 * @return void
 */
function import_locations(array $plugins = [], bool $all = false, bool $locationsPointBehavior = false)
{
    if ($all || in_array('autoInit', $plugins)) {
        $locationsPointBehavior = true;
    }
    if ($locationsPointBehavior) {
        import_mapbox([], true);
    }
    import_front_library('locations', $plugins, $all);
}

/**
 * Registra mapbox como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins Plugins disponibles: mapBoxAdapter
 * @param bool $all
 * @return void
 */
function import_mapbox(array $plugins = [], bool $all = true)
{
    import_front_library('mapbox', $plugins, $all);
}

/**
 * Registra indexeDB_adapter como assets globales y los plugins definidos por parámetro
 *
 * @param array $plugins
 * @param bool $all
 * @return void
 */
function import_indexeDB_adapter(array $plugins = [], bool $all = true)
{
    import_front_library('indexeDB_adapter', $plugins, $all);
}

/**
 * Registra una ruta o un conjunto de rutas
 *
 * @param array $routes Rutas
 * @param object &$router Referencia al el enrutador de la aplicación
 * @return void
 */
function register_routes($routes, &$router)
{

    $routes = is_array($routes) ? $routes : [$routes];

    if (count($routes) < 1) {
        return;
    }

    foreach ($routes as $route) {
        register_route($route, $router);
    }

}

/**
 * @param array $route
 * @param string $route[route]
 * @param string|callable $route[controller]
 * @param string|string[] $route[method]
 * @param string|null $route[name]
 * @param string|null $route[route_alias]
 * @param bool $route[require_login]
 * @param array $route[roles_allowed]
 * @param array $route[parameters]
 * @param array<string|callable> $route[middlewares]
 * @param \Slim\App $router
 * @return void
 */
function register_route(array $route, \Slim\App &$router)
{

    $routesSetted = get_config('_routes_');

    if ($routesSetted === false) {
        set_config('_routes_', []);
        $routesSetted = [];
    }

    $instanceRoute = \PiecesPHP\Core\Route::instanceFromArray($route);

    $routeSegment = $instanceRoute->routeSegment();
    $methods = $instanceRoute->method(null, true);
    $name = $instanceRoute->name();
    $alias = $instanceRoute->alias();
    $controller = $instanceRoute->controller();
    $rolesAllowed = $instanceRoute->rolesAllowed();
    $middlewares = array_reverse($instanceRoute->middlewares());

    if (array_key_exists($name, $routesSetted)) {
        throw new RouteDuplicateNameException();
    }

    $settedRoute = null;
    $settedRouteAlias = null;

    if (is_string($name) && $name !== null && $name !== '') {
        $settedRoute = $router->map($methods, $routeSegment, $controller)->setName($name);
    } else {
        $settedRoute = $router->map($methods, $routeSegment, $controller);
    }

    if ($alias !== null) {
        $settedRouteAlias = $router->map($methods, $alias, $controller);
    }

    foreach ($middlewares as $mw) {
        $settedRoute = $settedRoute->add($mw);
        if ($settedRouteAlias != null) {
            $settedRouteAlias->add($mw);
        }
    }

    if (is_string($name) && $name !== null && $name !== '') {

        $routesSetted[$name] = $instanceRoute->toArray();

        foreach ($rolesAllowed as $role) {
            Roles::addPermission($name, $role);
        }

        set_config('_routes_', $routesSetted);

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

        if (!is_string($route['controller'])) {
            continue;
        }

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
    return (is_array($route) && !empty($route)) ? $route['url'] : null;
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
        $app_base_position = mb_strlen($app_base) > 0 ? mb_strpos($route, $app_base) : false;
        $app_base_length = mb_strlen($app_base);

        if ($app_base_position !== false) {
            $route = mb_substr($route, $app_base_position + $app_base_length);
        }

        $route = baseurl($route);
        $base_len = mb_strlen(baseurl());
        $route = mb_substr($route, $base_len);

        if (is_string($route) && mb_strlen($route) > 0 && $route[0] == '/') {
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

/**
 * A partir de un string de fecha válido para \DateTime::__construct()
 * devuelve el nombre del mes en español
 *
 * @param string $date Fecha para formatear
 * @return string El nombre del mes en español
 */
function num_month_to_text(string $date)
{
    $date = date_format(date_create($date), 'd-m-Y');

    $date_array = explode('-', $date);

    $langGroup = 'calendar';
    $month = '';

    if ($date_array[1] == "01") {
        $month = __($langGroup, 'Enero');
    }

    if ($date_array[1] == "02") {
        $month = __($langGroup, 'Febrero');
    }

    if ($date_array[1] == "03") {
        $month = __($langGroup, 'Marzo');
    }

    if ($date_array[1] == "04") {
        $month = __($langGroup, 'Abril');
    }

    if ($date_array[1] == "05") {
        $month = __($langGroup, 'Mayo');
    }

    if ($date_array[1] == "06") {
        $month = __($langGroup, 'Junio');
    }

    if ($date_array[1] == "07") {
        $month = __($langGroup, 'Julio');
    }

    if ($date_array[1] == "08") {
        $month = __($langGroup, 'Agosto');
    }

    if ($date_array[1] == "09") {
        $month = __($langGroup, 'Septiembre');
    }

    if ($date_array[1] == "10") {
        $month = __($langGroup, 'Octubre');
    }

    if ($date_array[1] == "11") {
        $month = __($langGroup, 'Noviembre');
    }

    if ($date_array[1] == "12") {
        $month = __($langGroup, 'Diciembre');
    }

    return $month;
}

/**
 * Una cadena para url amigables
 * @param string $string Cadena para formatear
 * @param int $maxWords Cantidad máxima de palabras
 * @param bool $legacy
 * @return string Cadena formateada
 */
function friendly_url(string $string, int $maxWords = null, bool $legacy = false)
{
    return $legacy ? StringManipulate::friendlyURLStringLegacy($string, $maxWords) : StringManipulate::friendlyURLString($string, $maxWords);
}

/**
 * Aplica stripslashes seguido de addslashes
 *
 * @param string $str
 * @return string La cadena escapada
 */
function escapeString(string $str)
{
    return \addslashes(\stripslashes($str));
}

/**
 * @param string $str
 * @param array $template
 * @return string
 */
function strReplaceTemplate(string $str, array $template)
{
    return str_replace(array_keys($template), array_values($template), $str);
}

/**
 * Devuelve el HTML del adaptador de CropperJS integrado
 *
 * @param array $data
 * @param bool $echo
 * @return string|void
 */
function cropperAdapterWorkSpace(array $data = [], bool $echo = true)
{

    $lockAssets = get_config('lock_assets');

    if ($lockAssets == false) {
        set_config('lock_assets', true);
    }

    $controller = new BaseController();

    if ($lockAssets == false) {
        set_config('lock_assets', false);
    }

    return $controller->_render('panel/built-in/utilities/cropper/workspace.php', $data, $echo);
}

/**
 * Devuelve el HTML del adaptador de SimpleUploadPlaceholder integrado
 *
 * @param array $data
 * @param bool $echo
 * @return string|void
 */
function simpleUploadPlaceholderWorkSpace(array $data = [], bool $echo = true)
{

    $lockAssets = get_config('lock_assets');

    if ($lockAssets == false) {
        set_config('lock_assets', true);
    }

    $controller = new BaseController();

    if ($lockAssets == false) {
        set_config('lock_assets', false);
    }

    return $controller->_render('panel/built-in/utilities/simple-upload-placeholder/workspace.php', $data, $echo);
}

/**
 * @param \Slim\Http\Request $request
 * @param \Slim\Http\Response $response
 * @return \Slim\Http\Response
 */
function throw403(\Slim\Http\Request $request, \Slim\Http\Response $response)
{
    $response = $response->withStatus(403);

    if (!$request->isXhr()) {
        $controller = new PiecesPHP\Core\BaseController(false);
        $controller->render('pages/403');
    } else {
        $response = $response->withJson("403 Forbidden");
    }

    return $response;
}

/**
 * Genera una salida en la terminal
 *
 * @param string $text
 * @param boolean $newLine
 * @param string $newLineChars
 * @return void
 */
function echoTerminal(string $text, bool $newLine = true, string $newLineChars = "\r\n")
{
    fwrite(STDOUT, $text . ($newLine ? $newLineChars : ''));
    flush();
}

/**
 * @return string
 */
function getCurrentProcessOwnerUser()
{
    $userInfo = posix_getpwuid(posix_getuid());
    return $userInfo['name'];
}

/**
 * @return string
 */
function getCurrentProcessOwnerGroup()
{
    $groupInfo = posix_getgrgid(posix_getgid());
    return $groupInfo['name'];
}
