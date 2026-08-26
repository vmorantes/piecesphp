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

    const FORMATTER_CLASS = '\\PiecesPHP\\Core\\HTML\\FormatHtml';

    /**
     * Array de variables globales de las vistas
     *
     * @var array
     */
    protected $global_variables = [];

    /**
     * @var BaseModel|ActiveRecordModel|BaseEntityMapper
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

        $this->setConfig([
            "extension" => ".php",
        ]);

        $base_name_controller = str_replace([
            "App\\Controller\\",
            "Controller",
        ], "", $this::class);

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
            static::$view_folder = __DIR__ . \DIRECTORY_SEPARATOR  . '..' . \DIRECTORY_SEPARATOR  . "view" . \DIRECTORY_SEPARATOR;
        }
    }

    /**
     * Hace un require del archivo solicitado.
     * @param string $name Ubicación del archivo dentro de la carpeta app/view sin la extensión
     * @param array $data Un array asociativo que designa las variables que estarán disponibles dentro del archivo
     * @param bool $mode Modo de la salida si es true hace un echo de la plantilla, si es false la
     * devuelve como string
     * @param bool $format En true formatea la salida con self::FORMATTER_CLASS si está disponible
     * @return void|string
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
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
            log_exception($e);
            global_custom_exception_handler($e, static::class);
            die;
        }

        if (!is_string($output)) {
            $output = '';
        }

        if (class_exists(self::FORMATTER_CLASS) && $format) {
            $output = call_user_func(self::FORMATTER_CLASS . '::format', $output);
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
                    $src = $stamp !== 'none' ? $src->withQueryParameter('cacheStamp', $stamp) : $src;
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

            } catch (\Exception) {
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
     * @param bool $format En true formatea la salida con self::FORMATTER_CLASS si está disponible
     * @return void|string
     */
    public function _render($name = "index.php", $data = [], bool $mode = true, bool $format = true)
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
            log_exception($e);
            global_custom_exception_handler($e, static::class);
            die;
        }

        if (!is_string($output)) {
            $output = '';
        }

        if (class_exists(self::FORMATTER_CLASS) && $format) {
            $output = call_user_func(self::FORMATTER_CLASS . '::format', $output);
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
                    $src = $stamp !== 'none' ? $src->withQueryParameter('cacheStamp', $stamp) : $src;
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

            } catch (\Exception) {
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
    public function setConfig(array $config = []): void
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
    public function setVariables(array $variables = []): void
    {
        $this->global_variables = $variables;
    }

    /**
     * Establece el directorio de las vistas en la instancia
     * @param string $dir Directorio de las vistas
     * @return static
     */
    public function setInstanceViewDir(string $dir): static
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
        return $this->instance_view_folder ?? self::$view_folder;
    }

    /**
     * Establece el directorio de las vistas
     * @param string $dir Directorio de las vistas
     * @return void
     */
    public static function setViewDir(string $dir): void
    {
        $last_char = mb_substr($dir, mb_strlen($dir) - 1);
        $is_bar = ($last_char == '/' || $last_char == '\\');
        self::$view_folder = $is_bar ? $dir : $dir . \DIRECTORY_SEPARATOR;
    }

    /**
     * Grupo de idioma de los mensajes que emite este contrato.
     *
     * @var string
     */
    const OPERATION_LANG_GROUP = 'operation-route';

    /**
     * Sufijos de ruta que declaran la operación. La ruta manda; el cuerpo, no.
     *
     * @var array<string,bool>
     */
    const OPERATION_ROUTE_SUFFIXES = [
        '-actions-add' => false,
        '-actions-edit' => true,
    ];

    /**
     * ¿Esta petición entró por la ruta de EDICIÓN?
     *
     * La operación la decide el NOMBRE DE LA RUTA, que es lo mismo que concede el permiso.
     * Derivarla del cuerpo —`$isEdit = $id !== -1`— dejaba que el cliente eligiera la rama
     * mientras la comprobación miraba la puerta. Ver T120.
     *
     * @param \PiecesPHP\Core\Routing\RequestRoute $request
     * @return bool
     * @throws \UnexpectedValueException Si la ruta no declara ninguna de las dos operaciones.
     */
    public static function isEditRoute(\PiecesPHP\Core\Routing\RequestRoute $request): bool
    {
        $route = $request->getRoute();
        $name = $route !== null ? (string) $route->getName() : '';

        foreach (self::OPERATION_ROUTE_SUFFIXES as $suffix => $isEdit) {
            if (str_ends_with($name, $suffix)) {
                return $isEdit;
            }
        }

        //NO SE ADIVINA. Una ruta que llega aquí sin declarar su operación es un error de
        //registro, y elegir una rama por defecto sería reponer el defecto que esto arregla.
        throw new \UnexpectedValueException(
            'La ruta «' . $name . '» llega a una acción de alta/edición y no declara cuál es: '
            . 'su nombre tiene que terminar en ' . implode(' o ', array_keys(self::OPERATION_ROUTE_SUFFIXES)) . '.'
        );
    }

    /**
     * Respuesta al desajuste entre la ruta y el `id` recibido. IDÉNTICA en los 13 sitios.
     *
     * No se resuelve eligiendo una rama: se rechaza. Un `id` en la ruta de alta, o su ausencia
     * en la de edición, solo puede venir de un cliente que no es el formulario.
     *
     * @param \PiecesPHP\Core\Routing\RequestRoute $request
     * @param \PiecesPHP\Core\Routing\ResponseRoute $response
     * @param bool $isEditRoute Operación que declara la ruta.
     * @param int $id Identificador recibido en el cuerpo.
     * @return \PiecesPHP\Core\Routing\ResponseRoute
     */
    public static function rejectOperationMismatch(
        \PiecesPHP\Core\Routing\RequestRoute $request,
        \PiecesPHP\Core\Routing\ResponseRoute $response,
        bool $isEditRoute,
        int $id
    ): \PiecesPHP\Core\Routing\ResponseRoute {
        $route = $request->getRoute();
        $name = $route !== null ? (string) $route->getName() : '';

        $result = new \PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations(
            [],
            __(self::OPERATION_LANG_GROUP, 'Operación')
        );
        $result->setSingleOperation(true);
        $result->setSuccessOnSingleOperation(false);
        $result->setValue('redirect', false);
        $result->setValue('redirect_to', null);
        $result->setValue('reload', false);
        $result->setMessage(__(
            self::OPERATION_LANG_GROUP,
            'La operación solicitada no corresponde con la ruta utilizada.'
        ));

        //SE REGISTRA: un desajuste no lo produce el formulario, así que interesa que deje rastro.
        log_exception(new \UnexpectedValueException(
            'Desajuste de operación en «' . $name . '»: la ruta declara '
            . ($isEditRoute ? 'EDICIÓN' : 'ALTA') . ' y el cuerpo trae id=' . $id . '.'
        ));

        return $response->withJson($result, 400);
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

}