<?php

/**
 * PublicationsController.php
 */

namespace Publications\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use PiecesPHP\Core\Cache\CacheControllersCriteries;
use PiecesPHP\Core\Cache\CacheControllersCritery;
use PiecesPHP\Core\Cache\CacheControllersManager;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Pagination\PageQuery;
use PiecesPHP\Core\Pagination\PaginationResult;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoutePiecesPHP as Request;
use PiecesPHP\Core\Routing\ResponseRoutePiecesPHP as Response;
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
use Publications\Exceptions\DuplicateException;
use Publications\Exceptions\SafeException;
use Publications\Mappers\AttachmentPublicationMapper;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use Publications\PublicationsLang;
use Publications\PublicationsRoutes;
use Publications\Util\AttachmentPackage;

/**
 * PublicationsController.
 *
 * @package     Publications\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class PublicationsController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'publications';
    /**
     * @var string
     */
    protected static $baseRouteName = 'publications-admin';
    /**
     * @var string
     */
    protected static $title = 'Publicación';
    /**
     * @var string
     */
    protected static $pluralTitle = 'Publicaciones';

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

    const BASE_VIEW_DIR = 'publications';
    const BASE_JS_DIR = 'js/publications';
    const BASE_CSS_DIR = 'css';
    const UPLOAD_DIR = 'publications';
    const UPLOAD_DIR_TMP = 'publications/tmp';
    const LANG_GROUP = PublicationsLang::LANG_GROUP;

    const RESPONSE_SOURCE_STATIC_CACHE = 'STATIC_CACHE';
    const RESPONSE_SOURCE_NORMAL_RESULT = 'NORMAL_RESULT';
    const ENABLE_CACHE = true;

    public function __construct()
    {
        parent::__construct();

        self::$title = __(self::LANG_GROUP, self::$title);
        self::$pluralTitle = __(self::LANG_GROUP, self::$pluralTitle);

        $this->model = (new PublicationMapper())->getModel();
        set_title(self::$title);

        $baseURL = base_url();
        $pcsUploadDir = get_config('upload_dir');
        $pcsUploadDirURL = get_config('upload_dir_url');

        $this->uploadDir = append_to_path_system($pcsUploadDir, self::UPLOAD_DIR);
        $this->uploadTmpDir = append_to_path_system($pcsUploadDir, self::UPLOAD_DIR_TMP);
        $this->uploadDirURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR));
        $this->uploadDirTmpURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR_TMP));

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(PublicationsRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(PublicationsRoutes::staticRoute(self::BASE_CSS_DIR . '/publications.css'), 'css');

        PublicationCategoryMapper::uncategorizedCategory();

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addForm(Request $request, Response $response)
    {

        set_custom_assets([
            PublicationsRoutes::staticRoute(self::BASE_JS_DIR . '/forms.js'),
        ], 'js');

        import_cropper();
        import_default_rich_editor();
        import_simple_upload_placeholder();

        $attachmentGroup1 = [
            new AttachmentPackage(-1, 'attachment1', AttachmentPublicationMapper::ATTACHMENT_TYPE_1, FileValidator::TYPE_ANY, '*'),
            new AttachmentPackage(-1, 'attachment2', AttachmentPublicationMapper::ATTACHMENT_TYPE_2, FileValidator::TYPE_PDF, ['pdf', 'PDF']),
        ];

        $action = self::routeName('actions-add');
        $backLink = self::routeName('list');
        $allCategories = array_to_html_options(PublicationCategoryMapper::allForSelect(), null);
        $searchUsersURL = get_route('users-search-dropdown');

        $data = [];
        $data['action'] = $action;
        $data['langGroup'] = self::LANG_GROUP;
        $data['backLink'] = $backLink;
        $data['title'] = __(self::LANG_GROUP, 'Gestión de publicaciones');
        $data['allCategories'] = $allCategories;
        $data['attachmentGroup1'] = $attachmentGroup1;
        $data['searchUsersURL'] = append_to_url($searchUsersURL, '?search={query}');

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

        $element = new PublicationMapper($id);

        if ($element->id !== null && PublicationMapper::existsByID($element->id)) {

            set_custom_assets([
                PublicationsRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
                PublicationsRoutes::staticRoute(self::BASE_JS_DIR . '/forms.js'),
            ], 'js');

            import_cropper();
            import_default_rich_editor();
            import_simple_upload_placeholder();

            $attachmentGroup1 = [
                new AttachmentPackage($element->id, 'attachment1', AttachmentPublicationMapper::ATTACHMENT_TYPE_1, FileValidator::TYPE_ANY, '*'),
                new AttachmentPackage($element->id, 'attachment2', AttachmentPublicationMapper::ATTACHMENT_TYPE_2, FileValidator::TYPE_PDF, ['pdf', 'PDF']),
            ];
            $attachmentGroup1 = array_map(function ($e) use ($lang) {
                return $e->setLang($lang);
            }, $attachmentGroup1);

            $action = self::routeName('actions-edit');
            $backLink = self::routeName('list');
            $manyLangs = count($allowedLangs) > 1;
            $allCategories = array_to_html_options(PublicationCategoryMapper::allForSelect(), $element->category->id);
            $allowedLangs = array_to_html_options(self::allowedLangsForSelect($lang, $element->id), $lang);
            $searchUsersURL = get_route('users-search-dropdown');

            $data = [];
            $data['action'] = $action;
            $data['element'] = $element;
            $data['deleteRoute'] = self::routeName('actions-delete', ['id' => $element->id]);
            $data['allowDelete'] = self::allowedRoute('actions-delete', ['id' => $element->id]);
            $data['langGroup'] = self::LANG_GROUP;
            $data['backLink'] = $backLink;
            $data['title'] = __(self::LANG_GROUP, 'Gestión de publicaciones');
            $data['allCategories'] = $allCategories;
            $data['attachmentGroup1'] = $attachmentGroup1;
            $data['allowedLangs'] = $allowedLangs;
            $data['manyLangs'] = $manyLangs;
            $data['lang'] = $lang;
            $data['searchUsersURL'] = append_to_url($searchUsersURL, '?search={query}');

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
    public function listView(Request $request, Response $response)
    {

        $backLink = get_route('admin');
        $addLink = self::routeName('forms-add');
        $addCategoryLink = PublicationsCategoryController::routeName('forms-add');
        $listCategoriesLink = PublicationsCategoryController::routeName('list');
        $processTableLink = self::routeName('datatables');

        $title = self::$pluralTitle;

        $data = [];
        $data['processTableLink'] = $processTableLink;
        $data['langGroup'] = self::LANG_GROUP;
        $data['backLink'] = $backLink;
        $data['addLink'] = $addLink;
        $data['hasPermissionsAdd'] = strlen($addLink) > 0;
        $data['addCategoryLink'] = $addCategoryLink;
        $data['hasPermissionsAddCategory'] = strlen($addCategoryLink) > 0;
        $data['listCategoriesLink'] = $listCategoriesLink;
        $data['hasPermissionsListCategories'] = strlen($listCategoriesLink) > 0;
        $data['title'] = $title;

        set_custom_assets([
            PublicationsRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
            PublicationsRoutes::staticRoute(self::BASE_JS_DIR . '/list.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header');
        self::view('list', $data);
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
                    return ctype_digit($value) || is_int($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'author',
                null,
                function ($value) {
                    return ctype_digit($value) || is_int($value);
                },
                false,
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
                false,
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
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'seoDescription',
                null,
                function ($value) {
                    return $value === null || is_string($value);
                },
                true,
                function ($value) {
                    return is_string($value) && strlen(trim($value)) > 0 ? clean_string($value) : '';
                }
            ),
            new Parameter(
                'category',
                null,
                function ($value) {
                    return ctype_digit($value) || is_int($value) || $value == PublicationCategoryMapper::UNCATEGORIZED_ID;
                },
                false,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'publicDate',
                null,
                function ($value) {
                    return Validator::isDate($value, 'd-m-Y');
                },
                false,
                function ($value) {
                    return \DateTime::createFromFormat('d-m-Y H:i:s', "{$value} 00:00:00");
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
                'featured',
                PublicationMapper::UNFEATURED,
                function ($value) {
                    return ctype_digit($value) || is_int($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'draft',
                false,
                function ($value) {
                    return $value === 'yes' || $value === true;
                },
                true,
                function ($value) {
                    return $value === 'yes' || $value === true;
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Publicación'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'La publicación que intenta modificar no existe.');
        $successCreateMessage = __(self::LANG_GROUP, 'Publicación creada.');
        $successEditMessage = __(self::LANG_GROUP, 'Datos guardados.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');
        $categoryNotExistsMessage = __(self::LANG_GROUP, 'No hay existe la categoría.');
        $notAllowedLangMessage = __(self::LANG_GROUP, 'El idioma "%s" no está permitido.');

        //──── Anexos ────────────────────────────────────────────────────────────────────────────

        $attachmentsConfigure = [
            new AttachmentPackage(null, 'attachment1', AttachmentPublicationMapper::ATTACHMENT_TYPE_1, FileValidator::TYPE_ANY, '*'),
            new AttachmentPackage(null, 'attachment2', AttachmentPublicationMapper::ATTACHMENT_TYPE_2, FileValidator::TYPE_PDF, ['pdf', 'PDF']),
        ];

        $attachmentsExpectedParameters = [];

        foreach ($attachmentsConfigure as $attachmentConfigure) {
            $attachmentsExpectedParameters[] = new Parameter(
                $attachmentConfigure->baseNameAppend('Type'),
                null,
                function ($value) {
                    return is_string($value) && strlen(trim($value)) > 0;
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            );
        }

        $expectedParameters->addParameters($attachmentsExpectedParameters);

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario
            /**
             * @var int $id
             * @var int $author
             * @var string $lang
             * @var string $title
             * @var string $content
             * @var string $seoDescription
             * @var int $category
             * @var \DateTime $publicDate
             * @var \DateTime|null $startDate
             * @var \DateTime|null $endDate
             * @var int $featured
             * @var int|null $draft
             * @var array $attachmentsTypes
             */
            $id = $expectedParameters->getValue('id');
            $author = $expectedParameters->getValue('author');
            $lang = $expectedParameters->getValue('lang');
            $title = $expectedParameters->getValue('title');
            $content = $expectedParameters->getValue('content');
            $seoDescription = $expectedParameters->getValue('seoDescription');
            $category = $expectedParameters->getValue('category');
            $publicDate = $expectedParameters->getValue('publicDate');
            $startDate = $expectedParameters->getValue('startDate');
            $endDate = $expectedParameters->getValue('endDate');
            $featured = $expectedParameters->getValue('featured') == PublicationMapper::FEATURED ? PublicationMapper::FEATURED : PublicationMapper::UNFEATURED;
            $draft = $expectedParameters->getValue('draft');

            $attachmentsTypes = [];
            foreach ($attachmentsConfigure as $attachmentConfigure) {
                if ($attachmentConfigure->getType() == $expectedParameters->getValue($attachmentConfigure->baseNameAppend('Type'))) {
                    $attachmentConfigure->setPublicationID($id);
                    $attachmentConfigure->setLang($lang);
                    $attachmentsTypes[$attachmentConfigure->baseNameAppend('Type')] = $attachmentConfigure;
                }
            }

            //Se define si es edición o creación
            $isEdit = $id !== -1;

            try {

                $allowedLangs = Config::get_allowed_langs();

                if (!PublicationCategoryMapper::existsByID($category)) {
                    throw new SafeException($categoryNotExistsMessage);
                }

                if ($isEdit) {
                    if (!in_array($lang, $allowedLangs)) {
                        throw new SafeException(vsprintf($notAllowedLangMessage, [$lang]));
                    }
                } else {
                    $lang = get_config('default_lang');
                }

                if (!$isEdit) {
                    //Nuevo

                    $mapper = new PublicationMapper();

                    $mapper->setLangData($lang, 'title', $title);
                    $mapper->setLangData($lang, 'content', $content);
                    $mapper->setLangData($lang, 'seoDescription', $seoDescription);
                    $mapper->setLangData($lang, 'publicDate', $publicDate);
                    $mapper->setLangData($lang, 'startDate', $startDate);
                    $mapper->setLangData($lang, 'endDate', $endDate);
                    $mapper->setLangData($lang, 'category', $category);
                    $mapper->setLangData($lang, 'visits', 0);
                    $mapper->setLangData($lang, 'author', $author);
                    $mapper->setLangData($lang, 'folder', str_replace('.', '', uniqid()));
                    $mapper->setLangData($lang, 'featured', $featured);

                    if ($draft) {
                        $mapper->status = PublicationMapper::DRAFT;
                    }

                    $mainImage = self::handlerUpload('mainImage', $mapper->folder);
                    $thumbImage = self::handlerUpload('thumbImage', $mapper->folder);
                    $ogImage = self::handlerUpload('ogImage', $mapper->folder);

                    $mapper->setLangData($lang, 'mainImage', $mainImage);
                    $mapper->setLangData($lang, 'thumbImage', $thumbImage);
                    $mapper->setLangData($lang, 'ogImage', $ogImage);

                    $saved = $mapper->save();
                    $resultOperation->setSuccessOnSingleOperation($saved);

                    if ($saved) {

                        /**
                         * @var AttachmentPackage[] $attachmentsTypes
                         */
                        foreach ($attachmentsTypes as $attachmentConfig) {

                            $attachBasename = $attachmentConfig->getBaseName();
                            $attachMapper = $attachmentConfig->getMapper();
                            $attachMapper = $attachMapper !== null ? $attachMapper : new AttachmentPublicationMapper();
                            $attachType = $attachmentConfig->getType();
                            $attachValidTypes = $attachmentConfig->getValidTypes();
                            $attachFileName = $attachmentConfig->getTypeFilename();

                            $attachMapper->publication = $mapper->id;
                            $attachMapper->attachmentType = $attachType;
                            $attachMapper->lang = $lang;
                            $attachMapper->folder = $mapper->folder . '/' . 'attachments';

                            $attachFile = self::handlerUpload($attachmentConfig->baseNameAppend('File'), $attachMapper->folder, null, $attachValidTypes, false, $attachFileName);

                            if (mb_strlen($attachFile) > 0) {
                                $attachMapper->fileLocation = $attachFile;
                                $attachMapper->id !== null ? $attachMapper->update() : $attachMapper->save();
                            }

                        }

                        $resultOperation
                            ->setMessage($successCreateMessage)
                            ->setValue('redirect', true)
                            ->setValue('redirect_to', self::routeName('forms-edit', [
                                'id' => $mapper->id,
                            ]));

                    } else {
                        $resultOperation->setMessage($unknowErrorMessage);
                    }

                } else {
                    //Existente

                    $mapper = new PublicationMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        $mapper->setLangData($lang, 'title', $title);
                        $mapper->setLangData($lang, 'content', $content);
                        $mapper->setLangData($lang, 'seoDescription', $seoDescription);
                        $mapper->setLangData($lang, 'publicDate', $publicDate);
                        $mapper->setLangData($lang, 'startDate', $startDate);
                        $mapper->setLangData($lang, 'endDate', $endDate);
                        $mapper->setLangData($lang, 'category', $category);
                        $mapper->setLangData($lang, 'featured', $featured);
                        $mapper->setLangData($lang, 'author', $author);

                        if ($draft) {
                            $mapper->status = PublicationMapper::DRAFT;
                        } else {
                            $mapper->status = PublicationMapper::ACTIVE;
                        }

                        $mainImageSetted = $mapper->getLangData($lang, 'mainImage', false, null);
                        $thumbImageSetted = $mapper->getLangData($lang, 'thumbImage', false, null);
                        $ogImageSetted = $mapper->getLangData($lang, 'ogImage', false, null);

                        if (is_string($ogImageSetted) && mb_strlen(trim($ogImageSetted)) < 1) {
                            $ogImageSetted = null;
                        }

                        if ($mainImageSetted !== null) {
                            $mainImage = self::handlerUpload('mainImage', '', $mainImageSetted);
                        } else {
                            $mainImage = self::handlerUpload('mainImage', $mapper->folder, null);
                        }

                        if ($thumbImageSetted !== null) {
                            $thumbImage = self::handlerUpload('thumbImage', '', $thumbImageSetted);
                        } else {
                            $thumbImage = self::handlerUpload('thumbImage', $mapper->folder, null);
                        }

                        if ($ogImageSetted !== null) {
                            $ogImage = self::handlerUpload('ogImage', '', $ogImageSetted);
                        } else {
                            $ogImage = self::handlerUpload('ogImage', $mapper->folder, null);
                        }

                        if (mb_strlen($mainImage) > 0) {
                            $mapper->setLangData($lang, 'mainImage', $mainImage);
                        }
                        if (mb_strlen($thumbImage) > 0) {
                            $mapper->setLangData($lang, 'thumbImage', $thumbImage);
                        }
                        if (mb_strlen($ogImage) > 0) {
                            $mapper->setLangData($lang, 'ogImage', $ogImage);
                        }

                        $defaultLang = Config::get_default_lang();

                        /**
                         * @var AttachmentPackage[] $attachmentsTypes
                         */
                        foreach ($attachmentsTypes as $attachmentConfig) {

                            $attachBasename = $attachmentConfig->getBaseName();
                            $attachMapper = $attachmentConfig->getMapper();
                            $attachMapper = $attachMapper !== null ? $attachMapper : new AttachmentPublicationMapper();
                            $attachType = $attachmentConfig->getType();
                            $attachValidTypes = $attachmentConfig->getValidTypes();
                            $attachFileName = $attachmentConfig->getTypeFilename();
                            $langSuffix = $defaultLang != $lang ? "_{$lang}" : '';

                            $attachMapper->attachmentType = $attachType;
                            $attachMapper->publication = $mapper->id;
                            $attachMapper->lang = $lang;
                            $attachMapper->folder = $mapper->folder . '/' . 'attachments';

                            if ($attachMapper->id !== null) {
                                $attachFile = self::handlerUpload($attachmentConfig->baseNameAppend('File'), '', $attachMapper->fileLocation, $attachValidTypes, false, $attachFileName . $langSuffix);
                            } else {
                                $attachFile = self::handlerUpload($attachmentConfig->baseNameAppend('File'), $attachMapper->folder, null, $attachValidTypes, false, $attachFileName . $langSuffix);
                            }

                            if (mb_strlen($attachFile) > 0) {
                                $attachMapper->fileLocation = $attachFile;
                                $attachMapper->id !== null ? $attachMapper->update() : $attachMapper->save();
                            }

                        }

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
        $expectedParameters->setInputValues($inputData);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Eliminar publicación'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setValue('received', $inputData);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'La publicación que intenta eliminar no existe.');
        $successMessage = __(self::LANG_GROUP, 'Publicación eliminada.');
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

                $exists = PublicationMapper::existsByID($id);

                if ($exists) {

                    //Dirección de redirección en caso de creación
                    $redirectURLOn = self::routeName('list');

                    $table = PublicationMapper::TABLE;
                    $inactive = PublicationMapper::INACTIVE;

                    $transactionSQLDeleteQueries = [
                        [
                            'query' => "UPDATE {$table} SET {$table}.status = {$inactive} WHERE id = :ID",
                            'aliasConfig' => [
                                ':ID' => $id,
                            ],
                        ],
                    ];

                    $pdo = PublicationMapper::model()::getDb(Config::app_db('default')['db']);

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
                'category',
                null,
                function ($value) {
                    return (ctype_digit($value) || is_int($value)) || $value == PublicationCategoryMapper::UNCATEGORIZED_ID;
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
                    return ctype_digit($value) || is_int($value) || (is_string($value) && mb_strtoupper($value) == 'ANY');
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
            new Parameter(
                'featured',
                null,
                function ($value) {
                    return (ctype_digit($value) || is_int($value)) || is_null($value);
                },
                true,
                function ($value) {
                    return ctype_digit($value) || is_int($value) ? (int) $value : null;
                }
            ),
        ]);

        $expectedParameters->setInputValues($request->getQueryParams());
        $expectedParameters->validate();

        /**
         * @var int $page
         * @var int $perPage
         * @var int $category
         * @var int $status
         * @var string $title
         * @var int $featured
         */
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');
        $category = $expectedParameters->getValue('category');
        $status = $expectedParameters->getValue('status');
        $title = $expectedParameters->getValue('title');
        $featured = $expectedParameters->getValue('featured');

        $ignoreStatus = $status === 'ANY';
        $status = $status === 'ANY' ? null : $status;

        if (self::ENABLE_CACHE) {

            //=================Definir política de cache=================//

            //Datos para la verificación
            $currentLang = Config::get_lang();
            $activesByDateIDs = PublicationMapper::activesByDateIDs();
            $lastModifiedElement = PublicationMapper::lastModifiedElement(true);
            $lastModification = \DateTime::createFromFormat('d-m-Y H:i:s', '01-01-1990 00:00:00');
            if ($lastModifiedElement !== null) {
                $lastModification = $lastModifiedElement->updatedAt !== null ? $lastModifiedElement->updatedAt : $lastModifiedElement->createdAt;
            }
            $checksumData = [
                $currentLang,
                $page,
                $perPage,
                $category,
                $status,
                $title,
                $featured,
                sha1($activesByDateIDs . ':' . $lastModification->getTimestamp()),
            ];
            $checksum = sha1(json_encode($checksumData));

            //Validar cacheo por cabeceras
            $headersAndStatus = generateCachingHeadersAndStatus($request, $lastModification, $checksum);
            foreach ($headersAndStatus['headers'] as $header => $value) {
                $response = $response->withHeader($header, $value);
            }
            $response = $response->withStatus($headersAndStatus['status']);
            $shouldBeRecached = $response->getStatusCode() == 200; //La información cambió o debe ser actualizada
            $hasCache = false;

            //Verificar si ya hay archivos estáticos generados para esta petición
            if ($shouldBeRecached) {
                $cacheHandler = new CacheControllersManager(self::class, 'all', 3600 * 24 * 7 * 4);
                $cacheCriteries = new CacheControllersCriteries([
                    new CacheControllersCritery('checksum', $checksum),
                ]);
                $hasCache = $cacheHandler->setCriteries($cacheCriteries)->process()->hasCachedData();
            }

            //=================Fin de política de cache=================//

            $sourceData = self::RESPONSE_SOURCE_NORMAL_RESULT;

            if ($shouldBeRecached) {

                if (!$hasCache) {

                    $result = self::_all($page, $perPage, $category, $status, $featured, $title, $ignoreStatus);
                    $response = $response->withJson($result);

                    //Definir respuesta para la generación del archivo estático
                    $cacheHandler->setDataCache(json_encode($result), CacheControllersManager::CONTENT_TYPE_JSON);

                } else {
                    $response = $response
                        ->withHeader('Content-Type', $cacheHandler->getContentType())
                        ->write($cacheHandler->getCachedData(false));
                    $sourceData = self::RESPONSE_SOURCE_STATIC_CACHE;
                }

            }

        } else {

            $sourceData = self::RESPONSE_SOURCE_NORMAL_RESULT;
            $result = self::_all($page, $perPage, $category, $status, $featured, $title, $ignoreStatus);
            $response = $response->withJson($result);

        }

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
        $table = PublicationMapper::TABLE;
        $inactive = PublicationMapper::INACTIVE;

        $where = [
            "{$table}.status != {$inactive}",
        ];

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        $selectFields = PublicationMapper::fieldsToSelect();

        $columnsOrder = [
            'idPadding',
            'title',
            'categoryName',
            'visits',
            'publicDateFormat',
            'authorUser',
            'visibilityText',
            'featuredDisplay',
        ];

        $customOrder = [
            'idPadding' => 'DESC',
            'createdAt' => 'DESC',
            'updatedAt' => 'DESC',
            'authorUser' => 'DESC',
            'categoryName' => 'DESC',
            'featuredDisplay' => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([

            'where_string' => $whereString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new PublicationMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {

                $mapper = PublicationMapper::objectToMapper($e);

                $buttons = [];
                $hasEdit = self::allowedRoute('forms-edit', ['id' => $e->id]);
                $hasDelete = self::allowedRoute('actions-delete', ['id' => $e->id]);
                $hasPreview = PublicationsPublicController::allowedRoute('single', ['slug' => $mapper->getSlug()]);

                if ($hasEdit) {
                    $editLink = self::routeName('forms-edit', ['id' => $e->id]);
                    $editText = __(self::LANG_GROUP, 'Editar');
                    $editIcon = "<i class='icon edit'></i>";
                    $editButton = "<a title='{$editText}' href='{$editLink}' class='ui button brand-color icon'>{$editIcon}</a>";
                    $buttons[] = $editButton;
                }
                if ($hasPreview) {
                    $previewLink = PublicationsPublicController::routeName('single', ['slug' => $mapper->getSlug()]);
                    $previewText = __(self::LANG_GROUP, 'Ver');
                    $previewIcon = "<i class='icon external alternate'></i>";
                    $previewButton = "<a target='_blank' title='{$previewText}' href='{$previewLink}' class='ui button brand-color alt icon'>{$previewIcon}</a>";
                    $buttons[] = $previewButton;
                }
                if ($hasDelete) {
                    $deleteLink = self::routeName('actions-delete', ['id' => $mapper->id]);
                    $deleteText = __(self::LANG_GROUP, 'Eliminar');
                    $deleteIcon = "<i class='icon trash'></i>";
                    $deleteButton = "<a title='{$deleteText}' data-route='{$deleteLink}' class='ui button brand-color alt2 icon' delete-publication-button>{$deleteIcon}</a>";
                    $buttons[] = $deleteButton;
                }

                $buttons = implode('', $buttons);
                $columns = [];

                $tagColor = PublicationMapper::VISIBILITIES_COLORS[$e->visibility];
                $tag = "<span class='ui {$tagColor} tag label'>{$e->visibilityText}</span>";
                $title = mb_strlen($e->title) <= 54 ? $e->title : mb_substr($e->title, 0, 51) . '...';

                $columns[] = $e->idPadding;
                $columns[] = $title;
                $columns[] = $e->categoryName;
                $columns[] = $e->visits;
                $columns[] = $e->publicDateFormat;
                $columns[] = $e->authorUser;
                $columns[] = $tag;
                $columns[] = $e->featuredDisplay;
                $columns[] = $buttons;
                return $columns;
            },

        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * @param int $page =1
     * @param int $perPage =10
     * @param int $category =null
     * @param int $status =PublicationMapper::ACTIVE
     * @param int $featured =null
     * @param string $title =null
     * @param bool $ignoreStatus =false
     * @param bool $ignoreDateLimit =false
     * @return PaginationResult
     */
    public static function _all(
        int $page = null,
        int $perPage = null,
        int $category = null,
        int $status = null,
        int $featured = null,
        string $title = null,
        bool $ignoreStatus = false,
        bool $ignoreDateLimit = false
    ) {
        $page = $page === null ? 1 : $page;
        $perPage = $perPage === null ? 10 : $perPage;
        $status = $status === null ? PublicationMapper::ACTIVE : $status;

        $table = PublicationMapper::TABLE;
        $fields = PublicationMapper::fieldsToSelect();
        $jsonExtractExists = PublicationMapper::jsonExtractExistsMySQL();

        $whereString = null;
        $where = [];
        $and = 'AND';

        if ($category !== null) {

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$table}.category = {$category}";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        if (!$ignoreStatus) {

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$table}.status = {$status}";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        if ($title !== null) {

            $beforeOperator = !empty($where) ? $and : '';
            $titleField = PublicationMapper::fieldCurrentLangForSQL('title');
            $critery = "UPPER({$titleField}) LIKE UPPER('%{$title}%')";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        if ($featured !== null) {

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$table}.featured = {$featured}";
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

            if ($jsonExtractExists) {
                $beforeOperator = !empty($where) ? $and : '';
                $critery = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}')) IS NOT NULL";
                $where[] = "{$beforeOperator} ({$critery})";
            } else {
                $beforeOperator = !empty($where) ? $and : '';
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

        $sqlSelect .= " ORDER BY " . implode(', ', PublicationMapper::ORDER_BY_PREFERENCE);

        $pageQuery = new PageQuery($sqlSelect, $sqlCount, $page, $perPage, 'total');

        $parser = function ($element) {
            $element = PublicationMapper::objectToMapper($element);
            $element = PublicationsPublicController::view('public/util/item', [
                'element' => $element,
            ], false, false);
            return $element;
        };
        $each = !$jsonExtractExists ? function ($element) {
            $mapper = PublicationMapper::objectToMapper($element);
            $element = PublicationMapper::translateEntityObject($element);
            $element->link = PublicationsPublicController::routeName('single', ['slug' => $mapper->getSlug()]);
            return $element;
        } : function ($element) {
            $mapper = PublicationMapper::objectToMapper($element);
            $element->link = PublicationsPublicController::routeName('single', ['slug' => $mapper->getSlug()]);
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
     * @param string $name
     * @param array $data
     * @param bool $mode
     * @param bool $format
     * @return void|string
     */
    public static function view(string $name, array $data = [], bool $mode = true, bool $format = true)
    {
        return (new PublicationsController)->render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
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
                    $publication = PublicationMapper::getBy($id, 'id');

                    if ($publication !== null) {

                        $createdByID = (int) $publication->createdBy;
                        $authorID = (int) $publication->author;
                        $allow = $createdByID == $currentUserID || $authorID == $currentUserID;

                        if (in_array($currentUserType, PublicationMapper::CAN_DELETE_ALL)) {
                            $allow = true;
                        }

                    }

                }

            }

        }

        return $allow;
    }

    public static function pathFrontPublicationsAdapter()
    {
        return PublicationsRoutes::staticRoute('js/PublicationsAdapter.js');
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

                $instance = new PublicationsController;
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

        $group->addMiddleware(function (\PiecesPHP\Core\Routing\RequestRoutePiecesPHP $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }
}
