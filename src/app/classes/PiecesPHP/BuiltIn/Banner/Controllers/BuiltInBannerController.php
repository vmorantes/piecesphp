<?php

/**
 * BuiltInBannerController.php
 */

namespace PiecesPHP\BuiltIn\Banner\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use PiecesPHP\BuiltIn\Banner\BuiltInBannerLang;
use PiecesPHP\BuiltIn\Banner\BuiltInBannerRoutes;
use PiecesPHP\BuiltIn\Banner\Exceptions\DuplicateException;
use PiecesPHP\BuiltIn\Banner\Exceptions\SafeException;
use PiecesPHP\BuiltIn\Banner\Mappers\BuiltInBannerMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Pagination\PageQuery;
use PiecesPHP\Core\Pagination\PaginationResult;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;

/**
 * BuiltInBannerController.
 *
 * @package     PiecesPHP\BuiltIn\Banner\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class BuiltInBannerController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'built-in-banner';
    /**
     * @var string
     */
    protected static $baseRouteName = 'built-in-banner-admin';
    /**
     * @var string
     */
    protected static $title = 'Banner';

    /**
     * @var string
     */
    protected $uploadDir = '';
    /**
     * @var string
     */
    protected $uploadTmpDir = '';
    /**
     * @var string
     */
    protected $uploadDirURL = '';
    /**
     * @var string
     */
    protected $uploadDirTmpURL = '';
    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_JS_DIR = 'js';
    const BASE_CSS_DIR = 'css';
    const UPLOAD_DIR = 'built-in-banner';
    const UPLOAD_DIR_TMP = 'built-in-banner/tmp';
    const LANG_GROUP = BuiltInBannerLang::LANG_GROUP;

    const RESPONSE_SOURCE_STATIC_CACHE = 'STATIC_CACHE';
    const RESPONSE_SOURCE_NORMAL_RESULT = 'NORMAL_RESULT';
    const ENABLE_CACHE = true;

    public function __construct()
    {
        parent::__construct();

        $this->model = (new BuiltInBannerMapper())->getModel();

        $baseURL = base_url();
        $pcsUploadDir = get_config('upload_dir');
        $pcsUploadDirURL = get_config('upload_dir_url');

        $this->uploadDir = append_to_path_system($pcsUploadDir, self::UPLOAD_DIR);
        $this->uploadTmpDir = append_to_path_system($pcsUploadDir, self::UPLOAD_DIR_TMP);
        $this->uploadDirURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR));
        $this->uploadDirTmpURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR_TMP));

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(BuiltInBannerRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(BuiltInBannerRoutes::staticRoute(self::BASE_CSS_DIR . '/built-in-banner.css'), 'css');

        $aspectRatioDesktopDefault = 16 / 9;
        $aspectRatioMobileDefault = 4 / 3;
        $outputWidthDesktopDefault = 1920;
        $outputWidthMobileDefault = 800;

        if (get_config('BuiltInBannerConfiguration') === false) {
            set_config('BuiltInBannerConfiguration', [
                'desktop' => [
                    'aspectRatio' => 12 / 5,
                    'outputWidth' => 1920,
                    'referenceW' => 1920,
                    'referenceH' => 800,
                    'sizeRecommendedText' => strReplaceTemplate(__(self::LANG_GROUP, 'Tamaño {%1}'), [
                        '{%1}' => "1920x800",
                    ]),
                ],
                'mobile' => [
                    'aspectRatio' => 4 / 3,
                    'outputWidth' => 800,
                    'referenceW' => 800,
                    'referenceH' => 600,
                    'sizeRecommendedText' => strReplaceTemplate(__(self::LANG_GROUP, 'Tamaño {%1}'), [
                        '{%1}' => "800x600",
                    ]),
                ],
            ]);

        }

        add_to_front_configurations('BuiltInBannerConfiguration', get_config('BuiltInBannerConfiguration'));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addForm(Request $request, Response $response)
    {

        set_custom_assets([
            BuiltInBannerRoutes::staticRoute(self::BASE_JS_DIR . '/forms.js'),
        ], 'js');

        import_cropper();
        import_default_rich_editor();

        $action = self::routeName('actions-add');
        $backLink = self::routeName('list');

        $title = __(self::LANG_GROUP, 'Agregar banner');
        $description = '';

        set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

        $data = [];
        $data['action'] = $action;
        $data['langGroup'] = self::LANG_GROUP;
        $data['title'] = $title;
        $data['description'] = $description;
        $data['breadcrumbs'] = get_breadcrumbs([
            __(self::LANG_GROUP, 'Inicio') => [
                'url' => get_route('admin'),
            ],
            __(self::LANG_GROUP, 'Banners') => [
                'url' => $backLink,
            ],
            $title,
        ]);

        $this->helpController->render('panel/layout/header');
        $this->render('forms/add', $data);
        $this->helpController->render('panel/layout/footer');

        return $response;

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function editForm(Request $request, Response $response)
    {

        $id = $request->getAttribute('id', null);
        $id = Validator::isInteger($id) ? (int) $id : null;

        $lang = $request->getAttribute('lang', null);
        $lang = is_string($lang) ? $lang : null;

        $allowedLangs = Config::get_allowed_langs();

        if ($lang === null || !in_array($lang, $allowedLangs)) {
            throw new NotFoundException($request, $response);
        }

        $element = new BuiltInBannerMapper($id);

        if ($element->id !== null && BuiltInBannerMapper::existsByID($element->id)) {

            set_custom_assets([
                BuiltInBannerRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
                BuiltInBannerRoutes::staticRoute(self::BASE_JS_DIR . '/forms.js'),
            ], 'js');

            import_cropper();
            import_default_rich_editor();

            $action = self::routeName('actions-edit');
            $backLink = self::routeName('list');
            $manyLangs = count($allowedLangs) > 1;
            $allowedLangs = array_to_html_options(self::allowedLangsForSelect($lang, $element->id), $lang);

            $title = __(self::LANG_GROUP, 'Edición de banner');
            $description = '';

            set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

            $data = [];
            $data['action'] = $action;
            $data['element'] = $element;
            $data['deleteRoute'] = self::routeName('actions-delete', ['id' => $element->id]);
            $data['allowDelete'] = self::allowedRoute('actions-delete', ['id' => $element->id]);
            $data['langGroup'] = self::LANG_GROUP;
            $data['title'] = $title;
            $data['description'] = $description;
            $data['allowedLangs'] = $allowedLangs;
            $data['manyLangs'] = $manyLangs;
            $data['lang'] = $lang;
            $data['breadcrumbs'] = get_breadcrumbs([
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => get_route('admin'),
                ],
                __(self::LANG_GROUP, 'Banners') => [
                    'url' => $backLink,
                ],
                $title,
            ]);

            $this->helpController->render('panel/layout/header');
            $this->render('forms/edit', $data, true, false);
            $this->helpController->render('panel/layout/footer');

            return $response;

        } else {
            throw new NotFoundException($request, $response);
        }

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function listView(Request $request, Response $response)
    {

        $addLink = self::routeName('forms-add');
        $processTableLink = self::routeName('datatables');

        $title = __(self::LANG_GROUP, 'Banners');
        $description = __(self::LANG_GROUP, 'Listado de banners');

        set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

        $data = [];
        $data['processTableLink'] = $processTableLink;
        $data['langGroup'] = self::LANG_GROUP;
        $data['addLink'] = $addLink;
        $data['hasPermissionsAdd'] = strlen($addLink) > 0;
        $data['title'] = $title;
        $data['description'] = $description;
        $data['breadcrumbs'] = get_breadcrumbs([
            __(self::LANG_GROUP, 'Inicio') => [
                'url' => get_route('admin'),
            ],
            $title,
        ]);

        set_custom_assets([
            BuiltInBannerRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
            BuiltInBannerRoutes::staticRoute(self::BASE_JS_DIR . '/list.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header');
        $this->render('list', $data);
        $this->helpController->render('panel/layout/footer');

    }

    /**
     * Creación/Edición
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function action(Request $request, Response $response)
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'id',
                -1,
                function ($value) {
                    return Validator::isInteger($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'lang',
                null,
                function ($value) {
                    return is_string($value) && strlen(trim($value)) > 0;
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'title',
                null,
                function ($value) {
                    return is_string($value) && strlen(trim($value)) > 0;
                },
                true,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'content',
                null,
                function ($value) {
                    return is_string($value) && strlen(trim($value)) > 0;
                },
                true,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'startDate',
                null,
                function ($value) {
                    return $value === null || Validator::isDate($value, 'd-m-Y h:i A');
                },
                true,
                function ($value) {
                    return $value === null ? $value : \DateTime::createFromFormat('d-m-Y h:i A', $value);
                }
            ),
            new Parameter(
                'endDate',
                null,
                function ($value) {
                    return $value === null || Validator::isDate($value, 'd-m-Y h:i A');
                },
                true,
                function ($value) {
                    return $value === null ? $value : \DateTime::createFromFormat('d-m-Y h:i A', $value);
                }
            ),
            new Parameter(
                'orderPosition',
                0,
                function ($value) {
                    return Validator::isInteger($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'link',
                null,
                function ($value) {
                    return is_string($value);
                },
                true,
                function ($value) {
                    return is_string($value) ? clean_string($value) : '';
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Banner'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'El banner que intenta modificar no existe.');
        $successCreateMessage = __(self::LANG_GROUP, 'Banner creado.');
        $successEditMessage = __(self::LANG_GROUP, 'Datos guardados.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');
        $notAllowedLangMessage = __(self::LANG_GROUP, 'El idioma "%s" no está permitido.');

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario
            /**
             * @var int $id
             * @var string $lang
             * @var string|null $title
             * @var string|null $content
             * @var \DateTime|null $startDate
             * @var \DateTime|null $endDate
             * @var string $orderPosition
             * @var string|null $link
             */
            $id = $expectedParameters->getValue('id');
            $lang = $expectedParameters->getValue('lang');
            $title = $expectedParameters->getValue('title');
            $content = $expectedParameters->getValue('content');
            $startDate = $expectedParameters->getValue('startDate');
            $endDate = $expectedParameters->getValue('endDate');
            $orderPosition = $expectedParameters->getValue('orderPosition');
            $link = $expectedParameters->getValue('link');

            //Se define si es edición o creación
            $isEdit = $id !== -1;

            try {

                $allowedLangs = Config::get_allowed_langs();

                if ($isEdit) {
                    if (!in_array($lang, $allowedLangs)) {
                        throw new SafeException(vsprintf($notAllowedLangMessage, [$lang]));
                    }
                } else {
                    $lang = get_config('default_lang');
                }

                if (!$isEdit) {
                    //Nuevo

                    $mapper = new BuiltInBannerMapper();

                    $mapper->setLangData($lang, 'title', $title);
                    $mapper->setLangData($lang, 'content', $content);
                    $mapper->setLangData($lang, 'startDate', $startDate);
                    $mapper->setLangData($lang, 'endDate', $endDate);
                    $mapper->setLangData($lang, 'orderPosition', $orderPosition);
                    $mapper->setLangData($lang, 'link', $link);
                    $mapper->setLangData($lang, 'folder', str_replace('.', '', uniqid()));

                    $desktopImage = self::handlerUpload('desktopImage', $mapper->folder);
                    $mobileImage = self::handlerUpload('mobileImage', $mapper->folder);

                    $mapper->setLangData($lang, 'desktopImage', $desktopImage);
                    $mapper->setLangData($lang, 'mobileImage', $mobileImage);

                    $saved = $mapper->save();
                    $resultOperation->setSuccessOnSingleOperation($saved);

                    if ($saved) {

                        $resultOperation
                            ->setMessage($successCreateMessage)
                            ->setValue('redirect', true)
                            ->setValue('redirect_to', self::routeName('list'));

                    } else {
                        $resultOperation->setMessage($unknowErrorMessage);
                    }

                } else {
                    //Existente

                    $mapper = new BuiltInBannerMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        $mapper->setLangData($lang, 'title', $title);
                        $mapper->setLangData($lang, 'content', $content);
                        $mapper->setLangData($lang, 'startDate', $startDate);
                        $mapper->setLangData($lang, 'endDate', $endDate);
                        $mapper->setLangData($lang, 'orderPosition', $orderPosition);
                        $mapper->setLangData($lang, 'link', $link);
                        $mapper->status = BuiltInBannerMapper::ACTIVE;

                        $desktopImageSetted = $mapper->getLangData($lang, 'desktopImage', false, null);
                        $mobileImageSetted = $mapper->getLangData($lang, 'mobileImage', false, null);

                        if (is_string($mobileImageSetted) && mb_strlen(trim($mobileImageSetted)) < 1) {
                            $mobileImageSetted = null;
                        }

                        if ($desktopImageSetted !== null) {
                            $desktopImage = self::handlerUpload('desktopImage', '', $desktopImageSetted);
                        } else {
                            $desktopImage = self::handlerUpload('desktopImage', $mapper->folder, null);
                        }

                        if ($mobileImageSetted !== null) {
                            $mobileImage = self::handlerUpload('mobileImage', '', $mobileImageSetted);
                        } else {
                            $mobileImage = self::handlerUpload('mobileImage', $mapper->folder, null);
                        }

                        if (mb_strlen($desktopImage) > 0) {
                            $mapper->setLangData($lang, 'desktopImage', $desktopImage);
                        }
                        if (mb_strlen($mobileImage) > 0) {
                            $mapper->setLangData($lang, 'mobileImage', $mobileImage);
                        }

                        $defaultLang = Config::get_default_lang();

                        $updated = $mapper->update();
                        $resultOperation->setSuccessOnSingleOperation($updated);

                        if ($updated) {

                            $resultOperation
                                ->setMessage($successEditMessage)
                                ->setValue('redirect', true)
                                ->setValue('redirect_to', self::routeName('list'));

                        } else {

                            $resultOperation->setMessage($unknowErrorMessage);

                        }

                    } else {

                        $resultOperation->setMessage($notExistsMessage);

                    }

                }

            } catch (SafeException | DuplicateException $e) {

                $resultOperation->setMessage($e->getMessage());

            } catch (\Exception $e) {

                $resultOperation->setMessage($e->getMessage());
                log_exception($e);

            }

        } catch (SafeException $e) {

            $resultOperation->setMessage($e->getMessage());

        } catch (ParsedValueException $e) {

            $resultOperation->setMessage($unknowErrorWithValuesMessage);
            log_exception($e);

        } catch (MissingRequiredParamaterException | InvalidParameterValueException | \Exception $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

        }

        return $response->withJson($resultOperation);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function toDelete(Request $request, Response $response, array $args)
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'id',
                -1,
                function ($value) {
                    return Validator::isInteger($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $args;

        //Asignación de datos para procesar
        $expectedParameters->setInputValues($inputData);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Eliminar banner'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setValue('received', $inputData);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'El banner que intenta eliminar no existe.');
        $successMessage = __(self::LANG_GROUP, 'Banner eliminado.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario

            /**
             * @var int $id
             */
            $id = $expectedParameters->getValue('id');

            try {

                $exists = BuiltInBannerMapper::existsByID($id);

                if ($exists) {

                    //Dirección de redirección en caso de creación
                    $redirectURLOn = self::routeName('list');

                    $table = BuiltInBannerMapper::TABLE;
                    $inactive = BuiltInBannerMapper::INACTIVE;

                    $transactionSQLDeleteQueries = [
                        [
                            'query' => "UPDATE {$table} SET {$table}.status = {$inactive} WHERE id = :ID",
                            'aliasConfig' => [
                                ':ID' => $id,
                            ],
                        ],
                    ];

                    $pdo = BuiltInBannerMapper::model()::getDb(Config::app_db('default')['db']);
                    if ($pdo === null) {
                        throw new \Exception(__(self::LANG_GROUP, 'No pudo conectarse a la base de datos'));
                    }

                    try {

                        $pdo->beginTransaction();

                        foreach ($transactionSQLDeleteQueries as $sqlQueryConfig) {

                            $query = $sqlQueryConfig['query'];
                            $aliasConfig = $sqlQueryConfig['aliasConfig'];

                            $preparedStatement = $pdo->prepare($query);
                            $preparedStatement->execute($aliasConfig);

                        }

                        $pdo->commit();

                        $resultOperation->setSuccessOnSingleOperation(true);

                        $resultOperation
                            ->setMessage($successMessage)
                            ->setValue('redirect', true)
                            ->setValue('redirect_to', $redirectURLOn);

                    } catch (\Exception $e) {
                        $pdo->rollBack();
                        $resultOperation->setValue('transactionError', $e->getMessage());
                        $resultOperation->setMessage($unknowErrorMessage);
                        log_exception($e);
                    }

                } else {
                    $resultOperation->setMessage($notExistsMessage);
                }

            } catch (\Exception $e) {

                $resultOperation->setMessage($e->getMessage());
                log_exception($e);

            }

        } catch (MissingRequiredParamaterException $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

        } catch (ParsedValueException $e) {

            $resultOperation->setMessage($unknowErrorWithValuesMessage);
            log_exception($e);

        } catch (InvalidParameterValueException $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

        }

        return $response->withJson($resultOperation);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function all(Request $request, Response $response)
    {

        $expectedParameters = new Parameters([
            new Parameter(
                'page',
                1,
                function ($value) {
                    return Validator::isInteger($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'per_page',
                10,
                function ($value) {
                    return Validator::isInteger($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'status',
                null,
                function ($value) {
                    return Validator::isInteger($value) || (is_string($value) && mb_strtoupper($value) == 'ANY');
                },
                true,
                function ($value) {
                    return (is_string($value) && mb_strtoupper($value) == 'ANY') ? 'ANY' : (int) $value;
                }
            ),
            new Parameter(
                'title',
                null,
                function ($value) {
                    return is_scalar($value) && mb_strlen((string) $value) > 0;
                },
                true,
                function ($value) {
                    return (string) $value;
                }
            ),
        ]);

        $expectedParameters->setInputValues($request->getQueryParams());
        $expectedParameters->validate();

        /**
         * @var int $page
         * @var int $perPage
         * @var int $status
         * @var string $title
         */
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');
        $status = $expectedParameters->getValue('status');
        $title = $expectedParameters->getValue('title');
        $ignoreStatus = $status === 'ANY';
        $status = $status === 'ANY' ? null : $status;

        $sourceData = self::RESPONSE_SOURCE_NORMAL_RESULT;
        $result = self::_all($page, $perPage, $status, $title, $ignoreStatus);
        $response = $response->withJson($result);

        $response = $response->withHeader('PCSPHP-Response-Source', $sourceData);

        return $response;

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dataTables(Request $request, Response $response)
    {

        $whereString = null;
        $havingString = null;
        $and = 'AND';
        $table = BuiltInBannerMapper::TABLE;
        $inactive = BuiltInBannerMapper::INACTIVE;

        $where = [
            "{$table}.status != {$inactive}",
        ];
        $having = [];

        if (false) {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = "CRITERY = VALUE";
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
        }

        $selectFields = BuiltInBannerMapper::fieldsToSelect();

        $columnsOrder = [
            'idPadding',
            'title',
            'orderPosition',
            'desktopImage',
            'mobileImage',
        ];

        $customOrder = [
            'idPadding' => 'DESC',
            'createdAt' => 'DESC',
            'updatedAt' => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([

            'where_string' => $whereString,
            'having_string' => $havingString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new BuiltInBannerMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {

                $mapper = BuiltInBannerMapper::objectToMapper($e);

                $buttons = [];
                $hasEdit = self::allowedRoute('forms-edit', ['id' => $e->id]);
                $hasDelete = self::allowedRoute('actions-delete', ['id' => $e->id]);

                if ($hasEdit) {
                    $editLink = self::routeName('forms-edit', ['id' => $e->id]);
                    $editText = __(self::LANG_GROUP, 'Editar');
                    $editIcon = "<i class='icon edit'></i>";
                    $editButton = "<a title='{$editText}' href='{$editLink}' class='ui button brand-color icon'>{$editIcon}</a>";
                    $buttons[] = $editButton;
                }
                if ($hasDelete) {
                    $deleteLink = self::routeName('actions-delete', ['id' => $mapper->id]);
                    $deleteText = __(self::LANG_GROUP, 'Eliminar');
                    $deleteIcon = "<i class='icon trash'></i>";
                    $deleteButton = "<a title='{$deleteText}' data-route='{$deleteLink}' class='ui button brand-color alt2 icon' delete-built-in-banner-button>{$deleteIcon}</a>";
                    $buttons[] = $deleteButton;
                }

                $buttons = implode('', $buttons);
                $columns = [];

                $title = is_string($e->title) ? (mb_strlen($e->title) <= 54 ? $e->title : mb_substr($e->title, 0, 51) . '...') : 'N/A';
                $startDateFormat = $e->startDateFormat;
                $endDateFormat = $e->endDateFormat;
                $dates = array_filter([
                    $e->startDate !== null ? $startDateFormat : null,
                    $e->endDate !== null ? $endDateFormat : null,
                ], function ($e) {return $e !== null;});
                $dates = !empty($dates) ? implode(' a ', $dates) : 'N/A';

                $columns[] = $e->idPadding;
                $columns[] = $title;
                $columns[] = $e->orderPosition;
                $columns[] = $e->desktopImage !== null ? "<a class='ui button icon blue' href='#' data-image-preview='{$e->desktopImage}'><i class='icon image'></i></a>" : 'N/A';
                $columns[] = $e->mobileImage !== null && mb_strlen($e->mobileImage) > 0 ? "<a class='ui button icon blue' href='#' data-image-preview='{$e->mobileImage}'><i class='icon image'></i></a>" : 'N/A';
                $columns[] = $dates;
                $columns[] = $buttons;
                return $columns;
            },

        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * @param int $page =1
     * @param int $perPage =10
     * @param int $status =BuiltInBannerMapper::ACTIVE
     * @param string $title =null
     * @param bool $ignoreStatus =false
     * @param bool $ignoreDateLimit =false
     * @return PaginationResult
     */
    public static function _all(
        int $page = null,
        int $perPage = null,
        int $status = null,
        string $title = null,
        bool $ignoreStatus = false,
        bool $ignoreDateLimit = false
    ) {
        $page = $page === null ? 1 : $page;
        $perPage = $perPage === null ? 10 : $perPage;
        $status = $status === null ? BuiltInBannerMapper::ACTIVE : $status;

        $table = BuiltInBannerMapper::TABLE;
        $fields = BuiltInBannerMapper::fieldsToSelect();

        $whereString = null;
        $where = [];
        $and = 'AND';

        if (!$ignoreStatus) {

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$table}.status = {$status}";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        if ($title !== null) {

            $beforeOperator = !empty($where) ? $and : '';
            $titleField = BuiltInBannerMapper::fieldCurrentLangForSQL('title');
            $critery = "UPPER({$titleField}) LIKE UPPER('%{$title}%')";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        $now = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:00'));
        $now = $now->getTimestamp();
        $unixNowDate = "FROM_UNIXTIME({$now})";
        $startDateSQL = "{$table}.startDate";
        $endDateSQL = "{$table}.endDate";

        if (!$ignoreDateLimit) {

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$startDateSQL} <= {$unixNowDate} OR {$table}.startDate IS NULL";
            $where[] = "{$beforeOperator} ({$critery})";

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$endDateSQL} > {$unixNowDate} OR {$table}.endDate IS NULL";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        //Verificación de idioma
        $defaultLang = Config::get_default_lang();
        $currentLang = Config::get_lang();

        if ($currentLang != $defaultLang) {
            $beforeOperator = !empty($where) ? $and : '';
            $critery = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}')) IS NOT NULL";
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = implode(' ', $where);
        }

        $fields = implode(', ', $fields);
        $sqlSelect = "SELECT {$fields} FROM {$table}";
        $sqlCount = "SELECT COUNT({$table}.id) AS total FROM {$table}";

        if ($whereString !== null) {
            $sqlSelect .= " WHERE {$whereString}";
            $sqlCount .= " WHERE {$whereString}";
        }

        $sqlSelect .= " ORDER BY " . implode(', ', BuiltInBannerMapper::ORDER_BY_PREFERENCE);

        $pageQuery = new PageQuery($sqlSelect, $sqlCount, $page, $perPage, 'total');

        $parser = function ($element) {
            $element = BuiltInBannerMapper::objectToMapper($element);
            $element = BuiltInBannerPublicController::view('public/util/item', [
                'element' => $element,
            ], false, false);
            return $element;
        };
        $each = function ($element) {
            $mapper = BuiltInBannerMapper::objectToMapper($element);
            return $element;
        };

        $pagination = $pageQuery->getPagination($parser, $each);

        return $pagination;
    }

    /**
     * @param string $currentLang
     * @param int $elementID
     * @return array
     */
    public static function allowedLangsForSelect(string $currentLang, int $elementID)
    {

        $allowedLangsForSelect = [];

        $allowedLangs = Config::get_allowed_langs();

        $allowedLangs = array_filter($allowedLangs, function ($l) use ($currentLang) {
            return $l != $currentLang;
        });

        array_unshift($allowedLangs, $currentLang);

        foreach ($allowedLangs as $i) {

            $value = self::routeName('forms-edit', ['id' => $elementID, 'lang' => $i]);

            $allowedLangsForSelect[$value] = __('lang', $i);

        }

        return $allowedLangsForSelect;

    }

    /**
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        return parent::render(trim($name, '/'), $data, $mode, $format);
    }

    /**
     * Verificar si una ruta es permitida
     *
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {
        $route = self::routeName($name, $params, true);
        $allow = strlen($route) > 0;
        return $allow;
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    private static function _allowedRoute(string $name, string $route, array $params = [])
    {

        $getParam = function ($paramName) use ($params) {
            $_POST = isset($_POST) && is_array($_POST) ? $_POST : [];
            $_GET = isset($_GET) && is_array($_GET) ? $_GET : [];
            $paramValue = isset($params[$paramName]) ? $params[$paramName] : null;
            $paramValue = $paramValue !== null ? $paramValue : (isset($_GET[$paramName]) ? $_GET[$paramName] : null);
            $paramValue = $paramValue !== null ? $paramValue : (isset($_POST[$paramName]) ? $_POST[$paramName] : null);
            return $paramValue;
        };

        $allow = strlen($route) > 0;

        if ($allow) {

            $currentUser = getLoggedFrameworkUser();

            if ($currentUser !== null) {

                $currentUserType = $currentUser->type;
                $currentUserID = $currentUser->id;

                if ($name == 'actions-delete') {

                    $allow = false;
                    $id = ($getParam)('id');
                    $builtInBanner = BuiltInBannerMapper::getBy($id, 'id');

                    if ($builtInBanner !== null) {

                        $createdByID = (int) $builtInBanner->createdBy;
                        $allow = $createdByID == $currentUserID;

                        if (in_array($currentUserType, BuiltInBannerMapper::CAN_DELETE_ALL)) {
                            $allow = true;
                        }

                    }

                }

            }

        }

        return $allow;
    }

    public static function pathFrontBuiltInBannerAdapter()
    {
        return BuiltInBannerRoutes::staticRoute('js/BuiltInBannerAdapter.js');
    }

    /**
     * @param string $nameOnFiles
     * @param string $folder
     * @param string $currentRoute
     * @param array $allowedTypes
     * @param bool $setNameByInput
     * @param string $name
     * @return string
     * @throws \Exception
     */
    protected static function handlerUpload(string $nameOnFiles, string $folder, string $currentRoute = null, array $allowedTypes = null, bool $setNameByInput = true, string $name = null)
    {
        if ($allowedTypes === null) {
            $allowedTypes = [
                FileValidator::TYPE_ALL_IMAGES,
            ];
        }
        $handler = new FileUpload($nameOnFiles, $allowedTypes);
        $valid = false;
        $relativeURL = '';

        $name = $name !== null ? $name : 'file_' . uniqid();
        $oldFile = null;

        if ($handler->hasInput()) {

            try {

                $valid = $handler->validate();

                $instance = new BuiltInBannerController;
                $uploadDirPath = $instance->uploadDir;
                $uploadDirRelativeURL = $instance->uploadDirURL;

                if ($setNameByInput && $valid) {

                    $name = $_FILES[$nameOnFiles]['name'];
                    $lastPointIndex = mb_strrpos($name, '.');

                    if ($lastPointIndex !== false) {
                        $name = mb_substr($name, 0, $lastPointIndex);
                    }

                }

                if (!is_null($currentRoute)) {
                    //Si ya existe
                    $oldFile = append_to_url(basepath(), $currentRoute);
                    $oldFile = file_exists($oldFile) ? $oldFile : null;

                    if (mb_strlen(trim($folder)) < 1) {
                        //Si folder está vacío
                        $folder = str_replace($uploadDirRelativeURL, '', $currentRoute);
                        $folder = str_replace(basename($currentRoute), '', $folder);
                        $folder = trim($folder, '/');
                    }

                }

                $uploadDirPath = append_to_path_system($uploadDirPath, $folder);
                $uploadDirRelativeURL = append_to_url($uploadDirRelativeURL, $folder);

                if ($valid) {

                    $locations = $handler->moveTo($uploadDirPath, $name, null, false, true);

                    if (!empty($locations)) {

                        $url = $locations[0];
                        $nameCurrent = basename($url);
                        $relativeURL = trim(append_to_url($uploadDirRelativeURL, $nameCurrent), '/');

                        //Eliminar archivo anterior
                        if (!is_null($oldFile)) {

                            if (basename($oldFile) != $nameCurrent) {
                                unlink($oldFile);
                            }

                        }

                        //Se elimina cualquier otro archivo
                        foreach ($locations as $file) {
                            if ($url != $file) {
                                if (is_string($file) && file_exists($file)) {
                                    unlink($file);
                                }
                            }
                        }

                    }

                } else {
                    throw new \Exception(implode('<br>', $handler->getErrorMessages()));
                }

            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }

        }

        return $relativeURL;
    }

    /**
     * Obtener URL de una ruta
     *
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {

        $simpleName = !is_null($name) ? $name : '';

        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = getLoggedFrameworkUser();

        if ($current_user !== null) {
            $allowed = Roles::hasPermissions($name, $current_user->type);
        } else {
            $allowed = true;
        }

        $route = '';

        if ($allowed) {
            $route = get_route(
                $name,
                $params,
                $silentOnNotExists
            );
            $route = !is_string($route) ? '' : $route;
        }

        $allow = self::_allowedRoute($simpleName, $route, $params);

        return $allow ? $route : '';
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
        $list = $allRoles;
        $creation = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_GENERAL,
            UsersModel::TYPE_USER_INSTITUCIONAL,
        ];
        $edition = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_GENERAL,
            UsersModel::TYPE_USER_INSTITUCIONAL,
        ];
        $deletion = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_GENERAL,
            UsersModel::TYPE_USER_INSTITUCIONAL,
        ];
        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route( //Vista del listado
                "{$startRoute}/list[/]",
                $classname . ':listView',
                self::$baseRouteName . '-list',
                'GET',
                true,
                null,
                $list
            ),
            new Route( //Formulario de crear
                "{$startRoute}/forms/add[/]",
                $classname . ':addForm',
                self::$baseRouteName . '-forms-add',
                'GET',
                true,
                null,
                $creation
            ),
            new Route( //Formulario de editar
                "{$startRoute}/forms/edit/{id}/{lang}[/]",
                $classname . ':editForm',
                self::$baseRouteName . '-forms-edit',
                'GET',
                true,
                null,
                $edition,
                [
                    'lang' => Config::get_default_lang(),
                ]
            ),

            //JSON
            new Route( //JSON con todos los elementos
                "{$startRoute}/all[/]",
                $classname . ':all',
                self::$baseRouteName . '-ajax-all',
                'GET',
                true,
                null,
                $list
            ),
            new Route( //Datos para datatables
                "{$startRoute}/datatables[/]",
                $classname . ':dataTables',
                self::$baseRouteName . '-datatables',
                'GET',
                true,
                null,
                $list
            ),

            //──── POST ──────────────────────────────────────────────────────────────────────────────

            new Route( //Acción de crear
                "{$startRoute}/action/add[/]",
                $classname . ':action',
                self::$baseRouteName . '-actions-add',
                'POST',
                true,
                null,
                $creation
            ),
            new Route( //Acción de editar
                "{$startRoute}/action/edit[/]",
                $classname . ':action',
                self::$baseRouteName . '-actions-edit',
                'POST',
                true,
                null,
                $edition
            ),
            new Route( //Acción de eliminar
                "{$startRoute}/action/delete/{id}[/]",
                $classname . ':toDelete',
                self::$baseRouteName . '-actions-delete',
                'POST',
                true,
                null,
                $deletion
            ),

        ];

        $group->register($routes);

        $group->addMiddleware(function (\PiecesPHP\Core\Routing\RequestRoute $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }
}
