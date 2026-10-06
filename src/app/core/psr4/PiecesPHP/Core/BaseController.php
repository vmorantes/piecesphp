<?php

/**
 * BaseController.php
 */
namespace PiecesPHP\Core;

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ActiveRecordModel;
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
     * Se asigna un `BaseModel` genérico si $auto_model es true. Ya NO se deduce ningún modelo
     * por el nombre de la clase.
     * @param boolean $auto_model En true establece un modelo genérico por defecto.
     * @param string $group_database_model El grupo de configuraciones de base de datos por defecto. Nota: Esto si se está usando con las
     * configuraciones automáticas en PiecesPHP
     * @param boolean $system_models YA NO SE USA. Se conserva en la firma porque un clon puede
     * estar pasándolo: buscaba el modelo entre los predefinidos del sistema, y esa búsqueda se
     * retiró con la deducción por nombre de clase.
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

        //NO SE RETIRA: sin modelo propio hay un `BaseModel` genérico CON conexión, y de esa rama
        //dependen las 20 llamadas `new BaseController()` sin argumento. Suite: core/generic-model-on-construct.
        if ($auto_model && class_exists('\\PiecesPHP\\Core\\BaseModel')) {
            $this->model = new BaseModel(null, null, null, null, null, true, null, $group_database_model);
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

        if (get_config('cache_stamp_render_files') === true) {
            $output = self::addStaticVersions($output);
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

        if (get_config('cache_stamp_render_files') === true) {
            $output = self::addStaticVersions($output);
        }

        if ($mode === true) {
            echo $output;
        } else {
            return $output;
        }

    }
    /**
     * Marca con la versión de su archivo (ADR 0034) cada URL de imagen de la salida, POR ATRIBUTO: src de img y source,
     * cada candidato de srcset, poster, y url(…) de un style en línea. El valor se casa entre sus comillas tal como está
     * escrito: ni un src que es prefijo de otro ni un &amp; pueden desviarlo, que es lo que le pasaba al str_replace de la
     * URL suelta. Lo de dentro de <script> no se toca, una URL que ya lleva cacheStamp se deja, y un error de la expresión
     * devuelve la salida intacta.
     *
     * @param string $output
     * @return string
     */
    protected static function addStaticVersions(string $output): string
    {
        //El valor escrito, con su codificación: se añade el parámetro al final, antes del fragmento.
        $mark = function (string $written): string {
            $url = html_entity_decode($written, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
            if (trim($url) === '' || preg_match('/[?&]cacheStamp=/', $url) === 1 || str_starts_with(trim($url), 'data:')) {
                return $written;
            }
            $version = static_file_version($url);
            if ($version === 'none') {
                return $written;
            }
            $hashAt = strpos($written, '#');
            $beforeHash = $hashAt === false ? $written : substr($written, 0, $hashAt);
            $hash = $hashAt === false ? '' : substr($written, $hashAt);
            $separator = !str_contains($beforeHash, '?') ? '?' : (str_contains($beforeHash, '&amp;') ? '&amp;' : '&');
            return $beforeHash . $separator . 'cacheStamp=' . $version . $hash;
        };
        $markSrcset = function (string $written) use ($mark): string {
            $candidates = array_map(function (string $candidate) use ($mark): string {
                $marked = preg_replace_callback('/^(\s*)(\S+)/', fn(array $m): string => $m[1] . $mark($m[2]), $candidate);
                return is_string($marked) ? $marked : $candidate;
            }, explode(',', $written));
            return implode(',', $candidates);
        };
        $markStyle = function (string $written) use ($mark): string {
            $marked = preg_replace_callback('/url\(\s*(&quot;|&#039;|[\'"]?)(.*?)\1\s*\)/i', fn(array $m): string => 'url(' . $m[1] . $mark($m[2]) . $m[1] . ')', $written);
            return is_string($marked) ? $marked : $written;
        };
        $markTag = function (array $tag) use ($mark, $markSrcset, $markStyle): string {
            $name = mb_strtolower($tag[1]);
            $marked = preg_replace_callback('/(\s)(src|srcset|poster|style)(\s*=\s*)(["\'])(.*?)\4/is', function (array $a) use ($name, $mark, $markSrcset, $markStyle): string {
                $attribute = mb_strtolower($a[2]);
                $value = $a[5];
                if ($attribute === 'style') {
                    $value = $markStyle($value);
                } elseif ($attribute === 'srcset' && in_array($name, ['img', 'source'], true)) {
                    $value = $markSrcset($value);
                } elseif (($attribute === 'src' && in_array($name, ['img', 'source'], true)) || ($attribute === 'poster' && $name === 'video')) {
                    $value = $mark($value);
                }
                return $a[1] . $a[2] . $a[3] . $a[4] . $value . $a[4];
            }, $tag[0]);
            return is_string($marked) ? $marked : $tag[0];
        };
        $parts = preg_split('/(<script\b.*?<\/script>)/is', $output, -1, \PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $output;
        }
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                continue;
            }
            $marked = preg_replace_callback('/<([a-z][a-z0-9-]*)\b[^>]*>/i', $markTag, $part);
            if (!is_string($marked)) {
                return $output;
            }
            $parts[$i] = $marked;
        }
        return implode('', $parts);
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