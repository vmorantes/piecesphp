<?php

/**
 * BaseController.php
 */
namespace PiecesPHP\Core;

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ActiveRecordModel;
use Spatie\Url\Url as URLManager;
use Throwable;

/**
 * BaseController - Implementación básica de controlador.
 *
 * Los controladores que heredan de este deben tener el nombre NombreController.
 *
 * Asigna un modelo con el nombre [Name]Model.
 *
 * Ejemplo: Al controlador ExampleController le asigna el modelo ExampleModel.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class BaseController
{
    /**
     * Se asigna la configuración estension=>'.php' (Usada para el método render).
     * Se asigna el directorio de las vistas.
     * Se asigna un modelo si existe y si no se le asigna el modelo por defecto.
     * @param boolean $auto_model En true establece un modelo por defecto. Nota: Esto si se está usando con las PiecesPHP
     * @param string $group_database_model El grupo de configuraciones de base de datos por defecto. Nota: Esto si se está usando con las
     * configuraciones automáticas en PiecesPHP
     * @param boolean $system_models Establece si se buscará en los modelos predefinidos
     * del sistema. Nota: Esto si se está usando con las PiecesPHP
     * @return BaseController
     */
    public function __construct(bool $auto_model = true, string $group_database_model = 'default', $system_models = false)
    {

        if (!get_config('lock_assets')) {
            clear_global_assets();
            clear_assets_imports();
            set_title('');
        }

        $this->setConfig(array(
            "extension" => ".php",
        ));

        $base_name_controller = str_replace([
            "App\\Controller\\",
            "Controller",
        ], "", get_class($this));

        $class_model = '\\App\\Model\\' . $base_name_controller . "Model";
        $class_model_system = $base_name_controller . "Model";

        if ($auto_model) {

            $class_exist = class_exists($class_model);
            $is_model = is_subclass_of($class_model, '\PiecesPHP\Core\BaseModel');
            $is_mapper = is_subclass_of($class_model, '\PiecesPHP\Core\BaseEntityMapper');

            if ($class_exist) {

                if ($is_model) {
                    $this->model = new $class_model(null, null, null, null, null, null, null, $group_database_model);
                } else if ($is_mapper) {
                    $this->model = new $class_model(null, 'primary_key', null, true, $group_database_model);
                }

            } else if (class_exists($class_model_system) && $system_models === true) {
                $this->model = new $class_model_system(null, null, null, null, null, true, null, $group_database_model);
            } else if (class_exists('\\PiecesPHP\\Core\\BaseModel')) {
                $this->model = new BaseModel(null, null, null, null, null, true, null, $group_database_model);
            }
        }

        if (static::$view_folder == '/../view/') {
            static::$view_folder = __DIR__ . \DIRECTORY_SEPARATOR . '..' . \DIRECTORY_SEPARATOR . "view" . \DIRECTORY_SEPARATOR;
        }
    }

    /**
     * Hace un require del archivo solicitado.
     * @param string $name Ubicación del archivo dentro de la carpeta app/view sin la extensión
     * @param array $data Un array asociativo que designa las variables que estarán disponibles dentro del archivo
     * @param bool $mode Modo de la salida si es true hace un echo de la plantilla, si es false la
     * devuelve como string
     * @param bool $format En true formatea la salida con \PiecesPHP\Core\HTML\FormatHtml si está disponible
     * @return void|string
     */
    public function render(string $name = "index", array $data = array(), bool $mode = true, bool $format = false)
    {
        $pcs_php__name_view__ = $name;

        extract($data);
        extract($this->global_variables);

        $output = '';
        ob_start();
        try {
            require $this->getInstanceViewDir() . $pcs_php__name_view__ . $this->config['extension'];
            $output = ob_get_contents();
            ob_end_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            set_flash_message('render_exception', $e, BaseController::class);
            log_exception($e);
            header('Location: ' . get_current_url());
            die;
        }

        if (!is_string($output)) {
            $output = '';
        }

        if (class_exists('\\PiecesPHP\\Core\\HTML\\FormatHtml') && $format) {
            $output = \PiecesPHP\Core\HTML\FormatHtml::format($output);
        }

        $cache_stamp_render_files = get_config('cache_stamp_render_files');
        $stamp = get_config('cacheStamp');

        if ($cache_stamp_render_files === true) {

            $outputBase = $output;

            try {

                $dom = new \DOMDocument();
                $dom->preserveWhiteSpace = true;
                $dom->formatOutput = false;
                $temporalDivID = "TEMPORA_" . uniqid() . "_ID";

                libxml_use_internal_errors(true);
                $dom->loadHTML("<div id='{$temporalDivID}'>{$output}</div>");
                libxml_clear_errors();

                $imgs = $dom->getElementsByTagName("img");

                $imagesSRCs = [];

                /**
                 * @var \DOMElement $img
                 */
                foreach ($imgs as $img) {
                    $baseSrc = $img->getAttribute('src');
                    $src = $baseSrc;
                    $src = rtrim($src, '/');
                    $src = URLManager::fromString($src);
                    $src = $stamp !== 'none' ? $src->withQueryParameter('cacheStamp', $stamp) : $url;
                    $src = $src->__toString();
                    $img->setAttribute('src', $src);
                    $imagesSRCs[$baseSrc] = $src;
                }

                if (!empty($imagesSRCs)) {

                    $changedSRCs = [];

                    foreach ($imagesSRCs as $from => $to) {

                        if (!in_array($from, $changedSRCs)) {
                            $output = str_replace($from, $to, $output);
                            $changedSRCs[] = $from;
                        }

                    }

                }

            } catch (\Exception $e) {
                $output = $outputBase;
            }

        }

        if ($mode === true) {
            echo $output;
        } else {
            return $output;
        }

    }
    /**
     * Hace un require del archivo solicitado.
     * @param string $name Ubicación del archivo dentro de la carpeta app/view con la extensión
     * @param array $data Un array asociativo que designa las variables que estarán disponibles dentro del archivo
     * @param bool $mode Modo de la salida si es true hace un echo de la plantilla, si es false la
     * devuelve como string
     * @param bool $format En true formatea la salida con \PiecesPHP\Core\HTML\FormatHtml si está disponible
     * @return void|string
     */
    public function _render($name = "index.php", $data = array(), bool $mode = true, bool $format = true)
    {
        $pcs_php__name_view__ = $name;

        extract($data);
        extract($this->global_variables);

        $output = '';
        ob_start();
        try {
            require $this->getInstanceViewDir() . $pcs_php__name_view__;
            $output = ob_get_contents();
            ob_end_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            set_flash_message('render_exception', $e, BaseController::class);
            header('Location: ' . get_current_url());
            die;
        }

        if (!is_string($output)) {
            $output = '';
        }

        if (class_exists('\\PiecesPHP\\Core\\HTML\\FormatHtml') && $format) {
            $output = \PiecesPHP\Core\HTML\FormatHtml::format($output);
        }

        $cache_stamp_render_files = get_config('cache_stamp_render_files');
        $stamp = get_config('cacheStamp');

        if ($cache_stamp_render_files === true) {

            $outputBase = $output;

            try {

                $dom = new \DOMDocument();
                $dom->preserveWhiteSpace = true;
                $dom->formatOutput = false;
                $temporalDivID = "TEMPORA_" . uniqid() . "_ID";

                libxml_use_internal_errors(true);
                $dom->loadHTML("<div id='{$temporalDivID}'>{$output}</div>");
                libxml_clear_errors();

                $imgs = $dom->getElementsByTagName("img");

                $imagesSRCs = [];

                /**
                 * @var \DOMElement $img
                 */
                foreach ($imgs as $img) {
                    $baseSrc = $img->getAttribute('src');
                    $src = $baseSrc;
                    $src = rtrim($src, '/');
                    $src = URLManager::fromString($src);
                    $src = $stamp !== 'none' ? $src->withQueryParameter('cacheStamp', $stamp) : $url;
                    $src = $src->__toString();
                    $img->setAttribute('src', $src);
                    $imagesSRCs[$baseSrc] = $src;
                }

                if (!empty($imagesSRCs)) {

                    $changedSRCs = [];

                    foreach ($imagesSRCs as $from => $to) {

                        if (!in_array($from, $changedSRCs)) {
                            $output = str_replace($from, $to, $output);
                            $changedSRCs[] = $from;
                        }

                    }

                }

            } catch (\Exception $e) {
                $output = $outputBase;
            }

        }

        if ($mode === true) {
            echo $output;
        } else {
            return $output;
        }

    }
    /**
     * Establece configuraciones  de uso interno para el controlador, según sea necesario.
     * @param array $config Un array asociativo que designa las configuraciones en orden nombre:valor
     * @return void
     */
    public function setConfig(array $config = array())
    {
        $this->config = $config;
    }

    /**
     * Establece variables que serán accesibles desde todos los archivos solicitados por los métodos _render y render.
     *
     * Nota: Estas variables sobreescriben a las pasadas por las funciones _render y render si tienen el mismo nombre.
     *
     * @param array $variables Un array asociativo que designa las variables que estarán disponibles dentro de los archivos
     * @return void
     */
    public function setVariables(array $variables = array())
    {
        $this->global_variables = $variables;
    }

    /**
     * Establece el directorio de las vistas en la instancia
     * @param string $dir Directorio de las vistas
     * @return static
     */
    public function setInstanceViewDir(string $dir)
    {
        $last_char = mb_substr($dir, mb_strlen($dir) - 1);
        $is_bar = ($last_char == '/' || $last_char == '\\');
        $this->instance_view_folder = $is_bar ? $dir : $dir . \DIRECTORY_SEPARATOR;
        return $this;
    }

    /**
     * Devuelve la ruta del directorio de las vistas de la instancia
     * @return string
     */
    public function getInstanceViewDir()
    {
        return $this->instance_view_folder !== null ? $this->instance_view_folder : self::$view_folder;
    }

    /**
     * Establece el directorio de las vistas
     * @param string $dir Directorio de las vistas
     * @return void
     */
    public static function setViewDir(string $dir)
    {
        $last_char = mb_substr($dir, mb_strlen($dir) - 1);
        $is_bar = ($last_char == '/' || $last_char == '\\');
        self::$view_folder = $is_bar ? $dir : $dir . \DIRECTORY_SEPARATOR;
    }

    /**
     * Devuelve la ruta del directorio de las vistas
     * @return string
     */
    public static function getViewDir()
    {
        return self::$view_folder;
    }

    /**
     * @return array
     */
    public function getGlobalVariables()
    {
        return $this->global_variables;
    }

    /**
     * Array de variables globales de las vistas
     *
     * @var array
     */
    protected $global_variables = [];

    /**
     * @var BaseModel|ActiveRecordModel
     */
    protected $model = null;

    /**
     * @var string
     */
    protected $instance_view_folder = null;

    /**
     * Directorio de vistas
     *
     * @ignore @var string
     */
    protected static $view_folder = "/../view/";

    /**
     * @ignore @var array $config Array de configuraciones
     */
    protected $config = [];
}
