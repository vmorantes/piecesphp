<?php

/**
 * ImagesRepositoryController.php
 */

namespace ImagesRepository\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use ImagesRepository\ImagesRepositoryLang;
use ImagesRepository\ImagesRepositoryRoutes;
use ImagesRepository\Mappers\ImagesRepositoryMapper;
use PDOException;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Pagination\PageQuery;
use PiecesPHP\Core\Pagination\PaginationResult;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\ExifHelper;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use Slim\Exception\NotFoundException;
use Slim\Http\Request as Request;
use Slim\Http\Response as Response;

/**
 * ImagesRepositoryController.
 *
 * @package     ImagesRepository\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class ImagesRepositoryController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'images-repository';
    /**
     * @var string
     */
    protected static $baseRouteName = 'images-repository-admin';
    /**
     * @var string
     */
    protected static $title = 'Fotografía';
    /**
     * @var string
     */
    protected static $pluralTitle = 'Registro fotográfico';

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

    const BASE_VIEW_DIR = 'images-repository';
    const BASE_JS_DIR = 'js/images-repository';
    const BASE_CSS_DIR = 'css/images-repository';
    const UPLOAD_DIR = 'images-repository';
    const UPLOAD_DIR_TMP = 'images-repository/tmp';
    const LANG_GROUP = ImagesRepositoryLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        self::$title = __(self::LANG_GROUP, self::$title);
        self::$pluralTitle = __(self::LANG_GROUP, self::$pluralTitle);

        $this->model = (new ImagesRepositoryMapper())->getModel();
        set_title(self::$pluralTitle);

        $baseURL = base_url();
        $pcsUploadDir = get_config('upload_dir');
        $pcsUploadDirURL = get_config('upload_dir_url');

        $this->uploadDir = append_to_url($pcsUploadDir, self::UPLOAD_DIR);
        $this->uploadTmpDir = append_to_url($pcsUploadDir, self::UPLOAD_DIR_TMP);
        $this->uploadDirURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR));
        $this->uploadDirTmpURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR_TMP));

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(ImagesRepositoryRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(ImagesRepositoryRoutes::staticRoute(self::BASE_CSS_DIR . '/images-repository-style.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function previewImage(Request $request, Response $response)
    {
        $id = $request->getAttribute('id', null);
        $id = is_int($id) || (is_string($id) && ctype_digit($id)) ? (int) $id : null;
        $image = $id !== null ? ImagesRepositoryMapper::getByID($id) : null;

        if ($image !== null) {

            $relativePath = $image->image;

            if (is_string($relativePath) && mb_strlen($relativePath) > 0) {

                $imagePath = basepath($relativePath);

                if (file_exists($imagePath)) {

                    imageToThumbnail($imagePath, 300, 225, 70);

                    $lastModification = filemtime($imagePath);
                    $lastModification = \DateTime::createFromFormat('d-m-Y H:i:s', date('d-m-Y H:i:s', $lastModification));
                    $headersAndStatus = generateCachingHeadersAndStatus($request, $lastModification);

                    foreach ($headersAndStatus['headers'] as $header => $value) {
                        $response = $response->withHeader($header, $value);
                    }

                    $response = $response->withHeader('Content-Type', 'image/jpg');
                    $response = $response->withHeader('Content-Disposition', "filename=\"image_{$id}.jpg\"");
                    $response = $response->withStatus($headersAndStatus['status']);

                    return $response;

                }

            }

        }

        return $response->withStatus(404)->write("<h1>404 el recurso no existe.</h1>");

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function friendlyImageName(Request $request, Response $response)
    {
        $image = null;
        $maskNameInput = $request->getAttribute('name', null);
        $maskName = is_string($maskNameInput) ? trim($maskNameInput) : null;
        $maskName = $maskName !== null && mb_strlen($maskName) > 0 ? explode('_', $maskName) : null;
        $maskName = count($maskName) == 2 ? $maskName[1] : null;

        $pointIndex = strpos($maskName, '.');

        if ($pointIndex !== false) {
            $maskName = substr($maskName, 0, $pointIndex);
            $imageID = is_int($maskName) || (is_string($maskName) && ctype_digit($maskName)) ? (int) $maskName : null;
            $image = $imageID !== null ? ImagesRepositoryMapper::getByID($imageID) : null;
        }

        if ($image !== null) {

            $relativePath = $image->image;

            if (is_string($relativePath) && mb_strlen($relativePath) > 0) {

                $imagePath = basepath($relativePath);

                if (file_exists($imagePath)) {

                    $lastModification = filemtime($imagePath);
                    $lastModification = \DateTime::createFromFormat('d-m-Y H:i:s', date('d-m-Y H:i:s', $lastModification));
                    $headersAndStatus = generateCachingHeadersAndStatus($request, $lastModification);

                    foreach ($headersAndStatus['headers'] as $header => $value) {
                        $response = $response->withHeader($header, $value);
                    }

                    $response = $response->withHeader('Content-Type', mime_content_type($imagePath));
                    $response = $response->withHeader('Content-Disposition', "filename=\"{$maskNameInput}\"");
                    $response = $response->withStatus($headersAndStatus['status']);

                    readfile($imagePath);

                    return $response;

                }

            }

        }

        return $response->withStatus(404)->write("<h1>404 el recurso no existe.</h1>");

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function friendlyAuthorizationName(Request $request, Response $response)
    {
        $maskNameInput = $request->getAttribute('name', null);
        $maskName = is_string($maskNameInput) ? trim($maskNameInput) : null;
        $maskName = $maskName !== null && mb_strlen($maskName) > 0 ? explode('_', $maskName) : null;
        $maskName = count($maskName) == 2 ? $maskName[1] : null;

        $pointIndex = strpos($maskName, '.');

        $element = null;
        if ($pointIndex !== false) {
            $maskName = substr($maskName, 0, $pointIndex);
            $elementID = is_int($maskName) || (is_string($maskName) && ctype_digit($maskName)) ? (int) $maskName : null;
            $element = $elementID !== null ? ImagesRepositoryMapper::getByID($elementID) : null;
        }

        if ($element !== null) {

            $relativePath = $element->authorization;

            if (is_string($relativePath) && mb_strlen($relativePath) > 0) {

                $path = basepath($relativePath);

                if (file_exists($path)) {

                    $lastModification = filemtime($path);
                    $lastModification = \DateTime::createFromFormat('d-m-Y H:i:s', date('d-m-Y H:i:s', $lastModification));
                    $headersAndStatus = generateCachingHeadersAndStatus($request, $lastModification);

                    foreach ($headersAndStatus['headers'] as $header => $value) {
                        $response = $response->withHeader($header, $value);
                    }

                    $response = $response->withHeader('Content-Type', mime_content_type($path));
                    $response = $response->withHeader('Content-Disposition', "filename=\"{$maskNameInput}\"");
                    $response = $response->withStatus($headersAndStatus['status']);

                    readfile($path);

                    return $response;

                }

            }

        }

        return $response->withStatus(404)->write("<h1>404 el recurso no existe.</h1>");

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function listView(Request $request, Response $response)
    {

        $backLink = get_route('admin');

        $processTableLink = self::routeName('datatables');

        $action = self::routeName('actions-add');

        $title = __(self::LANG_GROUP, 'Gestión de') . ' ' . self::$pluralTitle;

        $data = [];
        $data['langGroup'] = self::LANG_GROUP;
        $data['processTableLink'] = $processTableLink;
        $data['backLink'] = $backLink;
        $data['title'] = self::$pluralTitle;
        $data['subtitle'] = $title;
        $data['formVariables'] = [
            'action' => $action,
            'langGroup' => self::LANG_GROUP,
            'standalone' => false,
            'submitButtonText' => __(self::LANG_GROUP, 'Guardar Imagen'),
        ];

        import_simple_upload_placeholder();
        set_custom_assets([
            ImagesRepositoryRoutes::staticRoute(self::BASE_JS_DIR . '/utils.js'),
            ImagesRepositoryRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
            ImagesRepositoryRoutes::staticRoute(self::BASE_JS_DIR . '/list.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header');
        self::view('list', $data);
        $this->helpController->render('panel/layout/footer');

        return $response;

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function filterView(Request $request, Response $response)
    {
        $year = $request->getQueryParam('year', null);
        $search = $request->getQueryParam('searchText', null);

        $backLink = get_route('admin');

        $filterURLWhitoutParams = self::routeName('filter-view');
        $filterURL = $filterURLWhitoutParams;
        $filterURLNoYear = $filterURLWhitoutParams;
        $processTableLink = self::routeName('datatables-filter-view');

        $urlQuery = array_filter([
            is_scalar($year) ? "year={$year}" : null,
            is_scalar($search) ? "searchText={$search}" : null,
        ], function ($e) {return is_string($e);});
        $urlQueryNoYear = (function ($params) {
            unset($params[0]);
            return $params;
        })($urlQuery);

        $urlQuery = !empty($urlQuery) ? implode('&', $urlQuery) : null;
        $urlQueryNoYear = !empty($urlQueryNoYear) ? implode('&', $urlQueryNoYear) : null;

        if ($urlQuery !== null) {
            $processTableLink .= "?{$urlQuery}";
            $filterURL .= "?{$urlQuery}";
            $filterURLNoYear .= "?{$urlQueryNoYear}";
        }

        $title = self::$pluralTitle;

        $currentUser = $this->user;
        $currentUserType = (int) $currentUser->type;
        $currentUserID = (int) $currentUser->id;
        $canViewAll = in_array($currentUserType, ImagesRepositoryMapper::CAN_VIEW_ALL);

        $data = [];
        $data['langGroup'] = self::LANG_GROUP;
        $data['processTableLink'] = $processTableLink;
        $data['backLink'] = $backLink;
        $data['title'] = $title;
        $data['years'] = ImagesRepositoryMapper::getYears();
        $data['filterURLWhitoutParams'] = $filterURLWhitoutParams;
        $data['filterURL'] = $filterURL;
        $data['filterURLNoYear'] = $filterURLNoYear;
        $data['year'] = is_string($year) ? $year : '';
        $data['search'] = is_string($search) ? $search : '';

        set_custom_assets([
            ImagesRepositoryRoutes::staticRoute(self::BASE_JS_DIR . '/filter-view.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header', [
            'containerClasses' => [
                'with-standard-sidebar',
            ],
        ]);
        self::view('filter-view', $data);
        $this->helpController->render('panel/layout/footer');

        return $response;

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addForm(Request $request, Response $response)
    {

        import_simple_upload_placeholder();
        set_custom_assets([
            ImagesRepositoryRoutes::staticRoute(self::BASE_JS_DIR . '/utils.js'),
            ImagesRepositoryRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
            ImagesRepositoryRoutes::staticRoute(self::BASE_JS_DIR . '/add-form.js'),
        ], 'js');

        $action = self::routeName('actions-add');
        $backLink = self::routeName('list');
        $standalone = true;

        $data = [];
        $data['action'] = $action;
        $data['langGroup'] = self::LANG_GROUP;
        $data['backLink'] = $backLink;
        $data['title'] = self::$title;
        $data['standalone'] = $standalone;

        $this->helpController->render('panel/layout/header');
        self::view('forms/add', $data);
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
        $id = !is_null($id) && ctype_digit($id) ? (int) $id : null;

        $lang = $request->getAttribute('lang', null);
        $lang = is_string($lang) ? $lang : null;

        $allowedLangs = Config::get_allowed_langs();

        if ($lang === null || !in_array($lang, $allowedLangs)) {
            throw new NotFoundException($request, $response);
        }

        $element = new ImagesRepositoryMapper($id);

        if ($element->id !== null && ImagesRepositoryMapper::existsByID($element->id)) {

            import_simple_upload_placeholder();
            set_custom_assets([
                ImagesRepositoryRoutes::staticRoute(self::BASE_JS_DIR . '/utils.js'),
                ImagesRepositoryRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
                ImagesRepositoryRoutes::staticRoute(self::BASE_JS_DIR . '/edit-form.js'),
            ], 'js');

            $action = self::routeName('actions-edit');
            $backLink = self::routeName('list');
            $manyLangs = count($allowedLangs) > 1;
            $allowedLangs = array_to_html_options(self::allowedLangsForSelect($lang, $element->id), $lang);
            $data = [];
            $data['action'] = $action;
            $data['element'] = $element;
            $data['deleteRoute'] = self::routeName('actions-delete', ['id' => $element->id]);
            $data['allowDelete'] = self::allowedRoute('actions-delete', ['id' => $element->id]);
            $data['langGroup'] = self::LANG_GROUP;
            $data['backLink'] = $backLink;
            $data['title'] = self::$title;
            $data['allowedLangs'] = $allowedLangs;
            $data['manyLangs'] = $manyLangs;
            $data['lang'] = $lang;

            $this->helpController->render('panel/layout/header');
            self::view('forms/edit', $data, true, false);
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
    public function singleView(Request $request, Response $response)
    {

        $slug = $request->getAttribute('slug', '');
        $slugID = ImagesRepositoryMapper::extractIDFromSlug($slug);
        $element = new ImagesRepositoryMapper($slugID);

        if ($element->id !== null) {

            set_custom_assets([
                ImagesRepositoryRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
                ImagesRepositoryRoutes::staticRoute(self::BASE_JS_DIR . '/single.js'),
            ], 'js');

            //URL alternativas según el idioma
            Config::set_config('alternatives_url', $element->getURLAlternatives());

            $toManageLink = self::routeName('list');
            $toListLink = self::routeName('filter-view');

            $title = $element->getFriendlyImageName(false);

            set_title(self::$title . ' - ' . $title);

            $data = [
                'editLink' => self::routeName('forms-edit', [
                    'id' => $element->id,
                ]),
                'hasEdit' => self::allowedRoute('forms-edit', [
                    'id' => $element->id,
                ]),
                'deleteRoute' => self::routeName('actions-delete', ['id' => $element->id]),
                'hasDelete' => self::allowedRoute('actions-delete', ['id' => $element->id]),
            ];
            $data['langGroup'] = self::LANG_GROUP;
            $data['element'] = $element;
            $data['toManageLink'] = $toManageLink;
            $data['toListLink'] = $toListLink;
            $data['title'] = $title;

            $this->helpController->render('panel/layout/header');
            self::view('single', $data);
            $this->helpController->render('panel/layout/footer');

        } else {
            throw new NotFoundException($request, $response);
        }

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
                    return ctype_digit($value) || is_int($value);
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
                'author',
                null,
                function ($value) {
                    return is_string($value) && mb_strlen($value) > 0;
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'description',
                '',
                function ($value) {
                    return is_string($value);
                },
                true,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'resolution',
                null,
                function ($value) {
                    return is_string($value) && mb_strlen($value) > 0;
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'captureDate',
                null,
                function ($value) {
                    return Validator::isDate($value, 'd-m-Y');
                },
                false,
                function ($value) {
                    return \DateTime::createFromFormat('d-m-Y H:i:s', "{$value} 00:00:00");
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Fotografía'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'La fotografía que intenta modificar no existe.');
        $successCreateMessage = __(self::LANG_GROUP, 'Fotografía creada.');
        $successEditMessage = __(self::LANG_GROUP, 'Datos guardados.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');
        $notUploadImageMessage = __(self::LANG_GROUP, 'No ha cargado ninguna fotografía.');
        $notAllowedLangMessage = __(self::LANG_GROUP, 'El idioma "%s" no está permitido.');

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario
            /**
             * @var string $lang
             * @var int $id
             * @var string $author
             * @var string $description
             * @var string $resolution
             * @var \DateTime $captureDate
             */
            $lang = $expectedParameters->getValue('lang');
            $id = $expectedParameters->getValue('id');
            $author = $expectedParameters->getValue('author');
            $description = $expectedParameters->getValue('description');
            $resolution = $expectedParameters->getValue('resolution');
            $captureDate = $expectedParameters->getValue('captureDate');

            //Se define si es edición o creación
            $isEdit = $id !== -1;

            try {

                $allowedLangs = Config::get_allowed_langs();

                if ($isEdit) {
                    if (!in_array($lang, $allowedLangs)) {
                        throw new \Exception(vsprintf($notAllowedLangMessage, [$lang]));
                    }
                } else {
                    $lang = get_config('default_lang');
                }

                if (!$isEdit) {
                    //Nuevo

                    $mapper = new ImagesRepositoryMapper();

                    $mapper->id = $id;
                    $mapper->author = $author;
                    $mapper->setLangData($lang, 'description', $description);
                    $mapper->resolution = $resolution;
                    $mapper->captureDate = $captureDate;
                    $mapper->folder = str_replace('.', '', uniqid());

                    $image = self::handlerUpload('image', $mapper->folder, null, false, [
                        FileValidator::TYPE_ALL_IMAGES,
                    ]);
                    $authorization = self::handlerUpload('authorization', $mapper->folder);
                    $mapper->image = mb_strlen($image) > 0 ? $image : '';
                    $mapper->authorization = mb_strlen($authorization) > 0 ? $authorization : null;

                    $saved = false;

                    if (mb_strlen($mapper->image) > 0) {

                        $imagePath = basepath($mapper->image);
                        $mapper->size = round(filesize($imagePath) / 1000 / 1000, 2);

                        if (@exif_read_data($imagePath, null) !== false) {
                            $exifData = new ExifHelper($imagePath);
                            $mapper->coordinates = $exifData->getGPSCoordinates();
                        }

                        $saved = $mapper->save();

                    } else {
                        $unknowErrorMessage = $notUploadImageMessage;
                    }

                    $resultOperation->setSuccessOnSingleOperation($saved);

                    if ($saved) {

                        $mapper->id = $mapper->getInsertIDOnSave();

                        $resultOperation
                            ->setMessage($successCreateMessage)
                            ->setValue('redirect', true)
                            ->setValue('redirect_to', self::routeName('list'));

                    } else {
                        $resultOperation->setMessage($unknowErrorMessage);
                    }

                } else {
                    //Existente

                    $mapper = new ImagesRepositoryMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        $mapper->id = $id;
                        $mapper->author = $author;
                        $mapper->setLangData($lang, 'description', $description);
                        $mapper->resolution = $resolution;
                        $mapper->captureDate = $captureDate;

                        $image = self::handlerUpload('image', $mapper->folder, $mapper->image, false, [
                            FileValidator::TYPE_ALL_IMAGES,
                        ]);
                        $authorization = self::handlerUpload('authorization', $mapper->folder, $mapper->authorization);
                        $mapper->image = mb_strlen($image) > 0 ? $image : $mapper->image;
                        $mapper->authorization = mb_strlen($authorization) > 0 ? $authorization : $mapper->authorization;

                        $updated = false;

                        if (mb_strlen($mapper->image) > 0) {

                            if (mb_strlen($image) > 0) {
                                $imagePath = basepath($mapper->image);
                                $mapper->size = round(filesize($imagePath) / 1000 / 1000, 2);

                                if (@exif_read_data($imagePath, null) !== false) {
                                    $exifData = new ExifHelper($imagePath);
                                    $mapper->coordinates = $exifData->getGPSCoordinates();
                                }
                            }

                            $updated = $mapper->update();

                        } else {
                            $unknowErrorMessage = $notUploadImageMessage;
                        }

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

        } catch (\Exception $e) {

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
                    return ctype_digit($value) || is_int($value);
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
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Eliminar fotografía'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setValue('received', $inputData);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'La fotografía que intenta eliminar no existe.');
        $successMessage = __(self::LANG_GROUP, 'Fotografía eliminada.');
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

                $element = new ImagesRepositoryMapper($id);
                $exists = $element->id !== null;

                if ($exists) {

                    $table = ImagesRepositoryMapper::TABLE;

                    $transactionSQLDeleteQueries = [
                        [
                            'query' => "DELETE FROM {$table} WHERE id = :ID",
                            'aliasConfig' => [
                                ':ID' => $id,
                            ],
                        ],
                    ];

                    $pdo = ImagesRepositoryMapper::model()::getDb(Config::app_db('default')['db']);

                    try {

                        $pdo->beginTransaction();

                        foreach ($transactionSQLDeleteQueries as $sqlQueryConfig) {

                            $query = $sqlQueryConfig['query'];
                            $aliasConfig = $sqlQueryConfig['aliasConfig'];

                            $preparedStatement = $pdo->prepare($query);
                            $preparedStatement->execute($aliasConfig);

                        }

                        $imageRemoved = $element->imageRemove();
                        $authorizationRemoved = $element->authorizationRemove();
                        $folderRemove = $element->folderRemove();

                        $pdo->commit();

                        $resultOperation->setSuccessOnSingleOperation(true);

                        //Dirección de redirección
                        $redirectURLOn = self::routeName('list');

                        $resultOperation
                            ->setMessage($successMessage)
                            ->setValue('redirect', true)
                            ->setValue('redirect_to', $redirectURLOn);

                    } catch (\Exception $e) {
                        if ($e instanceof PDOException) {
                            $pdo->rollBack();
                            $resultOperation->setValue('transactionError', $e->getMessage());
                        }
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
                    return ctype_digit($value) || is_int($value);
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
                    return ctype_digit($value) || is_int($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'description',
                null,
                function ($value) {
                    return is_string($value) && mb_strlen($value) > 0;
                },
                true,
                function ($value) {
                    return clean_string($value);
                }
            ),
        ]);

        $expectedParameters->setInputValues($request->getQueryParams());
        $expectedParameters->validate();

        /**
         * @var int $page
         * @var int $perPage
         * @var string $description
         */
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');
        $description = $expectedParameters->getValue('description');

        $result = self::_all($page, $perPage, $description);

        return $response->withJson($result);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dataTables(Request $request, Response $response)
    {
        $table = ImagesRepositoryMapper::TABLE;

        $whereString = null;
        $where = [];

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        $selectFields = ImagesRepositoryMapper::fieldsToSelect();

        $columnsOrder = [
            'idPadding',
            'description',
            'author',
        ];
        $customOrder = [
            'idPadding' => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_string' => $whereString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new ImagesRepositoryMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {
                return [
                    '',
                    '',
                    '',
                ];
            },

        ]);

        $rawData = $result->getValue('rawData');

        foreach ($rawData as $index => $element) {

            $mapper = new ImagesRepositoryMapper($element->id);

            $rawData[$index] = self::view(
                'util/list-card',
                [
                    'mapper' => $mapper,
                    'editLink' => self::routeName('forms-edit', [
                        'id' => $mapper->id,
                    ]),
                    'singleLink' => self::routeName('single', [
                        'slug' => $mapper->getSlug(),
                    ]),
                    'hasEdit' => self::allowedRoute('forms-edit', [
                        'id' => $mapper->id,
                    ]),
                    'deleteRoute' => self::routeName('actions-delete', ['id' => $mapper->id]),
                    'hasDelete' => self::allowedRoute('actions-delete', ['id' => $mapper->id]),
                    'hasSingle' => self::allowedRoute('single', ['slug' => $mapper->getSlug()]),
                    'langGroup' => self::LANG_GROUP,
                ],
                false
            );

        }

        $result->setValue('rawData', $rawData);

        return $response->withJson($result->getValues());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dataTablesFilterView(Request $request, Response $response)
    {

        //──── Parámetros ────────────────────────────────────────────────────────────────────────
        $year = $request->getQueryParam('year', null);
        $year = is_int($year) || ctype_digit($year) ? (int) $year : null;

        $search = $request->getQueryParam('searchText', null);
        $search = is_string($search) && mb_strlen(trim($search)) > 0 ? trim($search) : null;

        //──── Usuario actual ────────────────────────────────────────────────────────────────────
        $currentUser = $this->user;
        $currentUserType = (int) $currentUser->type;
        $currentUserID = (int) $currentUser->id;
        $canViewAll = in_array($currentUserType, ImagesRepositoryMapper::CAN_VIEW_ALL);

        //──── Consulta ──────────────────────────────────────────────────────────────────────────
        $table = ImagesRepositoryMapper::TABLE;

        $whereString = null;
        $where = [];
        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        $havingString = null;
        $having = [];
        if ($year !== null) {
            $operator = !empty($having) ? ' AND ' : '';
            $critery = "imageYear = {$year}";
            $having[] = "{$operator}({$critery})";
        }
        if ($search != null) {

            $operator = !empty($having) ? ' AND ' : '';
            $critery = "({$table}.description LIKE '{$search}%' LIKE '{$search}%' OR {$table}.author LIKE '{$search}%')";
            $having[] = "{$operator}({$critery})";

        }
        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
        }

        $selectFields = ImagesRepositoryMapper::fieldsToSelect();

        $columnsOrder = [
            'idPadding',
        ];
        $customOrder = [
            'idPadding' => 'DESC',
        ];

        //──── Procesamiento ─────────────────────────────────────────────────────────────────────
        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_string' => $whereString,
            'having_string' => $havingString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new ImagesRepositoryMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {
                return [
                    '',
                    '',
                ];
            },

        ]);

        $rawData = $result->getValue('rawData');

        foreach ($rawData as $index => $element) {

            $mapper = new ImagesRepositoryMapper($element->id);

            $rawData[$index] = self::view(
                'util/list-card-filter-view',
                [
                    'mapper' => $mapper,
                    'singleLink' => self::routeName('single', [
                        'slug' => $mapper->getSlug(),
                    ]),
                    'hasSingle' => self::allowedRoute('single', ['slug' => $mapper->getSlug()]),
                    'langGroup' => self::LANG_GROUP,
                ],
                false
            );

        }

        $result->setValue('rawData', $rawData);

        return $response->withJson($result->getValues());
    }

    /**
     * @param int $page =1
     * @param int $perPage =10
     * @param string $description =null
     * @return PaginationResult
     */
    public static function _all(
        int $page = null,
        int $perPage = null,
        string $description
    ) {
        $page = $page === null ? 1 : $page;
        $perPage = $perPage === null ? 10 : $perPage;

        $table = ImagesRepositoryMapper::TABLE;
        $fields = ImagesRepositoryMapper::fieldsToSelect();
        $jsonExtractExists = ImagesRepositoryMapper::jsonExtractExistsMySQL();

        $whereString = null;
        $where = [];

        if ($description !== null) {

            $beforeOperator = !empty($where) ? 'AND' : '';
            $descriptionField = ImagesRepositoryMapper::fieldCurrentLangForSQL('description');
            $critery = "UPPER({$descriptionField}) LIKE UPPER('{$description}%')";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        //Verificación de idioma
        $defaultLang = Config::get_default_lang();
        $currentLang = Config::get_lang();

        if ($currentLang != $defaultLang) {

            if ($jsonExtractExists) {
                $beforeOperator = !empty($where) ? 'AND' : '';
                $critery = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}')) IS NOT NULL";
                $where[] = "{$beforeOperator} ({$critery})";
            } else {
                $beforeOperator = !empty($where) ? 'AND' : '';
                $critery = "POSITION('\"{$currentLang}\":{' IN meta) != 0 || POSITION(\"'{$currentLang}':{\" IN meta) != 0";
                $where[] = "{$beforeOperator} ({$critery})";
            }

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

        $sqlSelect .= " ORDER BY " . implode(', ', ImagesRepositoryMapper::ORDER_BY_PREFERENCE);

        $pageQuery = new PageQuery($sqlSelect, $sqlCount, $page, $perPage, 'total');

        $parser = null;
        $each = null;

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
     * @param string $name
     * @param array $data
     * @param bool $mode
     * @param bool $format
     * @return void|string
     */
    public static function view(string $name, array $data = [], bool $mode = true, bool $format = true)
    {
        return (new static )->render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
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

        $allow = strlen($route) > 0;

        if ($allow) {

            $currentUser = get_config('current_user');

            if (is_object($currentUser)) {

                $currentUserType = (int) $currentUser->type;
                $currentUserID = (int) $currentUser->id;
                $canViewAll = in_array($currentUserType, ImagesRepositoryMapper::CAN_VIEW_ALL);
                $candAddAll = in_array($currentUserType, ImagesRepositoryMapper::CAN_ADD_ALL);

                if ($name == 'actions-delete') {

                    $allow = false;
                    $id = $params['id'];
                    $element = ImagesRepositoryMapper::getBy($id, 'id');
                    $currentUser = get_config('current_user');

                    if ($element !== null) {

                        $createdByID = (int) $element->createdBy;
                        $allow = $createdByID == $currentUserID;

                        if (in_array($currentUserType, ImagesRepositoryMapper::CAN_DELETE_ALL)) {
                            $allow = true;
                        }

                    }

                } elseif ($name == 'forms-edit' || $name == 'actions-edit') {

                    $id = isset($params['id']) ? $params['id'] : null;
                    $id = $id !== null ? $id : (isset($_GET['id']) ? $_GET['id'] : null);
                    $id = $id !== null ? $id : (isset($_POST['id']) ? $_POST['id'] : null);

                    if ($id !== null) {

                        $element = ImagesRepositoryMapper::getBy($id, 'id');
                        $currentUser = get_config('current_user');

                        if ($element !== null) {

                            $allow = false;

                            $createdByID = (int) $element->createdBy;
                            $allow = $createdByID == $currentUserID;

                            if (in_array($currentUserType, ImagesRepositoryMapper::CAN_EDIT_ALL)) {
                                $allow = true;
                            }

                        }

                    }

                } elseif ($name == 'forms-add' || $name == 'actions-add') {

                    $allow = false;

                    if (!$candAddAll) {
                        $allow = true;
                    } else {
                        $allow = true;
                    }

                } elseif ($name == 'single') {

                    $allow = false;
                    $slug = isset($params['slug']) ? $params['slug'] : null;
                    $slugID = ImagesRepositoryMapper::extractIDFromSlug($slug);
                    $element = new ImagesRepositoryMapper($slugID);
                    $allow = true;

                }

                $checkNames = [
                    'list',
                ];

                if (in_array($name, $checkNames)) {

                    $allow = false;

                    if (!$canViewAll) {
                        $allow = true;
                    } else {
                        $allow = true;
                    }

                }

            }

        }

        return $allow;
    }

    /**
     * @param string $nameOnFiles
     * @param string $folder
     * @param string $currentRoute
     * @param bool $setNameByInput
     * @param string[] $validTypes
     * @return string
     * @throws \Exception
     */
    protected static function handlerUpload(string $nameOnFiles, string $folder, string $currentRoute = null, bool $setNameByInput = false, array $validTypes = null)
    {
        $validTypes = $validTypes !== null ? $validTypes : [
            FileValidator::TYPE_XLSX,
            FileValidator::TYPE_XLS,
            FileValidator::TYPE_PDF,
            FileValidator::TYPE_DOC,
            FileValidator::TYPE_DOCX,
        ];
        $handler = new FileUpload($nameOnFiles, $validTypes);
        $valid = false;
        $relativeURL = '';

        $name = 'file_' . uniqid();
        $oldFile = null;

        if ($handler->hasInput()) {

            try {

                $valid = $handler->validate();

                $uploadDirPath = (new static )->uploadDir;
                $uploadDirRelativeURL = (new static )->uploadDirURL;

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

                $uploadDirPath = append_to_url($uploadDirPath, $folder);
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

        $simpleName = $name;

        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = get_config('current_user');

        if ($current_user !== false) {
            $allowed = Roles::hasPermissions($name, (int) $current_user->type);
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

        $allRoles = array_keys(UsersModel::TYPES_USERS);

        //Permisos
        $list = $allRoles;
        $filterView = $allRoles;
        $creation = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
            UsersModel::TYPE_USER_GENERAL,
        ];
        $edition = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
            UsersModel::TYPE_USER_GENERAL,
        ];
        $deletion = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
            UsersModel::TYPE_USER_GENERAL,
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
            new Route(
                "{$startRoute}/filter-view[/]",
                $classname . ':filterView',
                self::$baseRouteName . '-filter-view',
                'GET',
                true,
                null,
                $filterView
            ),
            new Route( //Vista del listado
                "{$startRoute}/single/{slug}[/]",
                $classname . ':singleView',
                self::$baseRouteName . '-single',
                'GET',
                true,
                null,
                $list
            ),
            new Route(
                "{$startRoute}/preview/{id}[/]",
                $classname . ':previewImage',
                self::$baseRouteName . '-image-preview',
                'GET',
                true,
                null,
                $list
            ),
            new Route(
                "{$startRoute}/friendly/{name}[/]",
                $classname . ':friendlyImageName',
                self::$baseRouteName . '-image-friendly',
                'GET',
                true,
                null,
                $list
            ),
            new Route(
                "{$startRoute}/friendly-auth/{name}[/]",
                $classname . ':friendlyAuthorizationName',
                self::$baseRouteName . '-authorization-friendly',
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
                'GET'
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
            new Route( //Datos para datatables
                "{$startRoute}/datatables-filter-view[/]",
                $classname . ':dataTablesFilterView',
                self::$baseRouteName . '-datatables-filter-view',
                'GET',
                true,
                null,
                $filterView
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

        $group->addMiddleware(function (\Slim\Http\Request $request, \Slim\Http\Response $response, callable $next) {

            $route = $request->getAttribute('route');
            $routeName = $route->getName();
            $routeArguments = $route->getArguments();
            $routeArguments = is_array($routeArguments) ? $routeArguments : [];
            $basenameRoute = self::$baseRouteName . '-';

            if (strpos($routeName, $basenameRoute) !== false) {

                $simpleName = str_replace($basenameRoute, '', $routeName);
                $routeURL = self::routeName($simpleName, $routeArguments);
                $allowed = mb_strlen($routeURL) > 0;

                if (!$allowed) {
                    return throw403($request, $response);
                }

            }
            return $next($request, $response);
        });

        return $group;
    }
}
