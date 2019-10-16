<?php

/**
 * PublicAreaController.php
 */

namespace App\Controller;

use App\Model\AvatarModel;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\ExifHelper;
use PiecesPHP\Core\Utilities\OsTicket\OsTicketAPI;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * PublicAreaController.
 *
 * Controlador del área pública
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class PublicAreaController extends \PiecesPHP\Core\BaseController
{

    /**
     * $prefixNameRoutes
     *
     * @var string
     */
    private static $prefixNameRoutes = 'public';

    /**
     * $startSegmentRoutes
     *
     * @var string
     */
    private static $startSegmentRoutes = '';

    /**
     * $automaticImports
     *
     * @var bool
     */
    private static $automaticImports = true;

    /**
     * $user
     *
     * Usuario logueado
     *
     * @var \stdClass
     */
    protected $user = null;

    /**
     * __construct
     *
     * @return static
     */
    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente

        $this->init();

        if (self::$automaticImports === true) {

            /* JQuery */
            import_jquery();
            /* Semantic */
            import_semantic();
            /* NProgress */
            import_nprogress();
            /* Librerías de la aplicación */
            import_app_libraries();

            add_global_asset(base_url('statics/css/global.css'), 'css');

        }
    }

    /**
     * indexView
     *
     * Vista principal
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function indexView(Request $req, Response $res, array $args)
    {

        $exifResult = '';

        try {

            $exifImage = new FileUpload('exif-image', [
                FileValidator::TYPE_JPEG,
            ]);

            if ($exifImage->hasInput()) {

                if ($exifImage->validate()) {

                    $fileData = $exifImage->getFileInformation();
                    $exifHelper = new ExifHelper($fileData['tmp_name']);

                    //Fechas
                    $originalDate = $exifHelper->getOriginalDate();
                    $digitizedDate = $exifHelper->getDigitizedDate();
                    $uploadDate = $exifHelper->getFileDate();

                    $originalDate = !is_null($originalDate) ? $originalDate->format('d-m-Y h:i:s A') : 'Sin información';
                    $digitizedDate = !is_null($digitizedDate) ? $digitizedDate->format('d-m-Y h:i:s A') : 'Sin información';
                    $uploadDate = !is_null($uploadDate) ? $uploadDate->format('d-m-Y h:i:s A') : 'Sin información';

                    //Coordenadas

                    $baseLongitude = $exifHelper->getGPSDataToNumber(ExifHelper::GPS_TYPE_LONGITUDE);
                    $baseLatitude = $exifHelper->getGPSDataToNumber(ExifHelper::GPS_TYPE_LATITUDE);

                    $referenceSignLongitude = $exifHelper->getGPSSign(ExifHelper::GPS_TYPE_LONGITUDE);
                    $referenceSignLatitude = $exifHelper->getGPSSign(ExifHelper::GPS_TYPE_LATITUDE);

                    $longitude = $exifHelper->getGPSLongitude();
                    $latitude = $exifHelper->getGPSLatitude();

                    $baseLongitude = !is_null($baseLongitude) ? $baseLongitude : 'Sin información';
                    $baseLatitude = !is_null($baseLatitude) ? $baseLatitude : 'Sin información';

                    $referenceSignLongitude =

                    is_int($referenceSignLongitude) ?
                    (
                        $referenceSignLongitude > 0 ?
                        '+' :
                        '-'
                    ) :
                    'Sin información';

                    $referenceSignLatitude =

                    is_int($referenceSignLatitude) ?
                    (
                        $referenceSignLatitude > 0 ?
                        '+' :
                        '-'
                    ) :
                    'Sin información';

                    $longitude = !is_null($longitude) ? $longitude : 'Sin información';
                    $latitude = !is_null($latitude) ? $latitude : 'Sin información';

                    $exifResult = [
                        'Información procesada' => [
                            'Fechas' => [
                                'Captura' => $originalDate,
                                'Digitalización' => $digitizedDate,
                                'Subida del archivo' => $uploadDate,
                            ],
                            'GPS' => [
                                'Signos de referencia' => [
                                    'Longitud' => $referenceSignLongitude,
                                    'Latitud' => $referenceSignLatitude,
                                ],
                                'Coordenadas' => [
                                    'lng' => $longitude,
                                    'lat' => $latitude,
                                ],
                                'Coordenadas sin signos' => [
                                    'lng' => $baseLongitude,
                                    'lat' => $baseLatitude,
                                ],
                            ],
                        ],
                        'Datos completos' => $exifHelper->getExifData(),
                    ];

                } else {

                    $exifResult = $exifImage->getErrorMessages();

                }

            }

        } catch (\Exception $e) {
            $exifResult = [
                'exceptionMessage' => $e->getMessage(),
                'exceptionFile' => $e->getFile(),
                'exceptionLine' => $e->getLine(),
            ];
        }

        $exifResult = self::arrayToHTMLList($exifResult);

        import_quilljs();
		import_cropper();
		import_izitoast();

        set_custom_assets([
            'statics/js/main.js',
        ], 'js');

        $this->render('layout/header');
        $this->render('pages/sample-public', [
            'exifResult' => $exifResult,
        ]);
        $this->render('layout/footer');

        return $res;
    }

    /**
     * arrayToHTMLList
     *
     * @param mixed $array
     * @return string
     */
    protected static function arrayToHTMLList($array)
    {

        if (is_array($array)) {

            foreach ($array as $key => $value) {

                $title = ctype_digit($key) || is_int($key) ? 'Índice ' . $key : $key;

                if (is_array($value)) {
                    $array[$key] = "<div class='item'><strong>$title:</strong><br/>" . self::arrayToHTMLList($value) . "</div>";
                } else {
                    $array[$key] = "<div class='item'><strong>$title:</strong> $value</div>";
                }

            }

            $array = implode(' ', $array);

        } else {

            $array = '';

        }

        return "<div class='ui celled list'>$array</div>";

    }

    /**
     * routeName
     *
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {
        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$prefixNameRoutes . $name : self::$prefixNameRoutes;

        $allowed = false;
        $current_user = get_config('current_user');

        if ($current_user != false) {
            $allowed = Roles::hasPermissions($name, (int) $current_user->type);
        } else {
            $allowed = true;
        }

        if ($allowed) {
            return get_route(
                $name,
                $params,
                $silentOnNotExists
            );
        } else {
            return '';
        }
    }

    /**
     * routes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';

        //Otras rutas
        $namePrefix = self::$prefixNameRoutes;
        $startRoute .= self::$startSegmentRoutes;

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //Generales
        $group->register([
            new Route(
                "{$startRoute}[/]",
                self::class . ":indexView",
                "{$namePrefix}-index",
                'POST|GET'
            ),
        ]);

        //──── POST ─────────────────────────────────────────────────────────────────────────

        return $group;
    }

    /**
     * init
     *
     * @return void
     */
    protected function init()
    {
        $api_url = get_config('osTicketAPI');
        $api_key = get_config('osTicketAPIKey');

        OsTicketAPI::setBaseURL($api_url);
        OsTicketAPI::setBaseAPIKey($api_key);

        $view_data = [];
        $this->user = get_config('current_user');

        if ($this->user instanceof \stdClass) {
            $view_data['user'] = $this->user;
            $this->user->avatar = AvatarModel::getAvatar($this->user->id);
            $this->user->hasAvatar = !is_null($this->user->avatar);
            $this->user->id = BaseHashEncryption::encrypt(base64_encode($this->user->id), self::class);
            unset($this->user->password);
        }

        $this->setVariables($view_data);

    }
}
