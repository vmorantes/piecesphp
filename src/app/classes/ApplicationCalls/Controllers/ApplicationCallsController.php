<?php

/**
 * ApplicationCallsController.php
 */

namespace ApplicationCalls\Controllers;

use ApplicationCalls\ApplicationCallsLang;
use ApplicationCalls\ApplicationCallsRoutes;
use ApplicationCalls\Exceptions\DuplicateException;
use ApplicationCalls\Exceptions\SafeException;
use ApplicationCalls\Mappers\ApplicationCallsMapper;
use ApplicationCalls\Mappers\AttachmentApplicationCallsMapper;
use ApplicationCalls\Util\AttachmentPackage;
use App\Controller\AdminPanelController;
use App\Locations\Mappers\CountryMapper;
use App\Model\UsersModel;
use Organizations\Mappers\OrganizationMapper;
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
use PiecesPHP\UserSystem\Profile\SubMappers\InterestResearchAreasMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\SystemApprovalsRoutes;

/**
 * ApplicationCallsController.
 *
 * @package     ApplicationCalls\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class ApplicationCallsController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'application-calls';
    /**
     * @var string
     */
    protected static $baseRouteName = 'application-calls-admin';
    /**
     * @var string
     */
    protected static $title = 'Contenido';

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

    const BASE_VIEW_DIR = 'application-calls';
    const BASE_JS_DIR = 'js/application-calls';
    const BASE_CSS_DIR = 'css';
    const UPLOAD_DIR = 'application-calls';
    const UPLOAD_DIR_TMP = 'application-calls/tmp';
    const LANG_GROUP = ApplicationCallsLang::LANG_GROUP;

    const RESPONSE_SOURCE_STATIC_CACHE = 'STATIC_CACHE';
    const RESPONSE_SOURCE_NORMAL_RESULT = 'NORMAL_RESULT';
    const ENABLE_CACHE = true;

    public function __construct()
    {
        parent::__construct();

        $this->model = (new ApplicationCallsMapper())->getModel();

        $baseURL = base_url();
        $pcsUploadDir = get_config('upload_dir');
        $pcsUploadDirURL = get_config('upload_dir_url');

        $this->uploadDir = append_to_path_system($pcsUploadDir, self::UPLOAD_DIR);
        $this->uploadTmpDir = append_to_path_system($pcsUploadDir, self::UPLOAD_DIR_TMP);
        $this->uploadDirURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR));
        $this->uploadDirTmpURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR_TMP));

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(ApplicationCallsRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(ApplicationCallsRoutes::staticRoute(self::BASE_CSS_DIR . '/application-calls.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addForm(Request $request, Response $response)
    {

        set_custom_assets([
            ApplicationCallsRoutes::staticRoute(self::BASE_JS_DIR . '/add-form.js'),
        ], 'js');

        import_cropper();
        import_default_rich_editor();

        $dynamicAttachments = [
        ];

        $action = self::routeName('actions-add');
        $backLink = self::routeName('list');

        $title = __(self::LANG_GROUP, 'Agregar contenido');
        $description = '';

        set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

        $contentTypesOptions = array_to_html_options(ApplicationCallsMapper::contentTypesForSelect(), null);
        $financingTypesOptions = array_to_html_options(ApplicationCallsMapper::financingTypesForSelect(), ApplicationCallsMapper::FINANCING_TYPE_NA);
        $targetCountriesOptions = array_to_html_options(CountryMapper::allForSelect(), null, true);
        $currenciesOptions = array_to_html_options(ApplicationCallsMapper::currenciesForSelect(), 'USD');
        $interestResearchAreasOptions = array_to_html_options(getInteresResearchAreas(), null, true);

        $data = [];
        $data['action'] = $action;
        $data['langGroup'] = self::LANG_GROUP;
        $data['title'] = $title;
        $data['description'] = $description;
        $data['contentTypesOptions'] = $contentTypesOptions;
        $data['financingTypesOptions'] = $financingTypesOptions;
        $data['targetCountriesOptions'] = $targetCountriesOptions;
        $data['currenciesOptions'] = $currenciesOptions;
        $data['interestResearchAreasOptions'] = $interestResearchAreasOptions;
        $data['dynamicAttachments'] = $dynamicAttachments;
        $data['breadcrumbs'] = get_breadcrumbs([
            __(self::LANG_GROUP, 'Inicio') => [
                'url' => get_route('admin'),
            ],
            __(self::LANG_GROUP, 'Contenidos') => [
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

        $selectedLang = $request->getQueryParam('lang', Config::get_default_lang());

        $element = new ApplicationCallsMapper($id);

        if ($element->id !== null && ApplicationCallsMapper::existsByID($element->id)) {

            set_custom_assets([
                ApplicationCallsRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
                ApplicationCallsRoutes::staticRoute(self::BASE_JS_DIR . '/edit-form.js'),
            ], 'js');

            import_cropper();
            import_default_rich_editor();

            $action = self::routeName('actions-edit');
            $backLink = self::routeName('list');

            $title = __(self::LANG_GROUP, 'Edición de contenido');
            $description = '';

            set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

            $targetCountries = array_map(fn($e) => $e->id, $element->targetCountries);
            $interestResearhAreas = array_map(fn($e) => $e->id, $element->interestResearhAreas);
            $contentTypesOptions = array_to_html_options(ApplicationCallsMapper::contentTypesForSelect(), $element->contentType);
            $financingTypesOptions = array_to_html_options(ApplicationCallsMapper::financingTypesForSelect(), $element->financingType);
            $targetCountriesOptions = array_to_html_options(CountryMapper::allForSelect(), $targetCountries, true);
            $currenciesOptions = array_to_html_options(ApplicationCallsMapper::currenciesForSelect(), $element->currency);
            $interestResearchAreasOptions = array_to_html_options(getInteresResearchAreas(), $interestResearhAreas, true);
            $participatingInstitutionsOptions = array_to_html_options(array_combine($element->participatingInstitutions, $element->participatingInstitutions), $element->participatingInstitutions, true);

            $data = [];
            $data['action'] = $action;
            $data['element'] = $element;
            $data['deleteRoute'] = self::routeName('actions-delete', ['id' => $element->id]);
            $data['allowDelete'] = self::allowedRoute('actions-delete', ['id' => $element->id]);
            $data['langGroup'] = self::LANG_GROUP;
            $data['title'] = $title;
            $data['description'] = $description;
            $data['contentTypesOptions'] = $contentTypesOptions;
            $data['financingTypesOptions'] = $financingTypesOptions;
            $data['targetCountriesOptions'] = $targetCountriesOptions;
            $data['currenciesOptions'] = $currenciesOptions;
            $data['interestResearchAreasOptions'] = $interestResearchAreasOptions;
            $data['participatingInstitutionsOptions'] = $participatingInstitutionsOptions;
            $data['selectedLang'] = $selectedLang;
            $data['breadcrumbs'] = get_breadcrumbs([
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => get_route('admin'),
                ],
                __(self::LANG_GROUP, 'Contenidos') => [
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

        $title = __(self::LANG_GROUP, 'Contenidos');
        $description = __(self::LANG_GROUP, 'Listado de contenidos');

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
            ApplicationCallsRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
            ApplicationCallsRoutes::staticRoute(self::BASE_JS_DIR . '/list.js'),
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
                'baseLang',
                null,
                function ($value) {
                    $valid = is_string($value) && mb_strlen(trim($value)) > 0;
                    $allowedLangs = Config::get_allowed_langs();
                    if ($valid) {
                        $valid = in_array($value, $allowedLangs);
                        if (!$valid) {
                            throw new SafeException(__(self::LANG_GROUP, 'El idioma seleccionado no es válido.'));
                        }
                    }
                    return $valid;
                },
                true,
                function ($value) {
                    return is_string($value) && mb_strlen(trim($value)) > 0 ? $value : Config::get_default_lang();
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
                'contentType',
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
                    $isArray = is_array($value);
                    $valid = is_null($value) || (is_string($value) && mb_strlen(trim($value)) > 0) || $isArray;
                    $allowedLangs = Config::get_allowed_langs();
                    if ($isArray) {
                        foreach ($value as $key => $v) {
                            if (!(
                                is_string($key) &&
                                in_array($key, $allowedLangs) &&
                                is_string($v) &&
                                mb_strlen(trim($v)) > 0
                            )) {
                                $valid = false;
                            }
                        }
                    }

                    return $valid;
                },
                false,
                function ($value) {
                    $parsed = [];
                    $allowedLangs = Config::get_allowed_langs();
                    if (is_array($value)) {
                        foreach ($value as $key => $v) {
                            if ((
                                is_string($key) &&
                                in_array($key, $allowedLangs) &&
                                is_string($v) &&
                                mb_strlen(trim($v)) > 0
                            )) {
                                $parsed[$key] = clean_string($v);
                            }
                        }
                        return $parsed;
                    } else {
                        return is_string($value) ? clean_string($value) : $value;
                    }
                }
            ),
            new Parameter(
                'financingType',
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
                'targetCountries',
                null,
                function ($value) {
                    $isArray = is_array($value);
                    $valid = $isArray && !empty($value);
                    if ($valid) {
                        foreach ($value as $i) {
                            if ($i instanceof CountryMapper) {
                                if ($i->id == null) {
                                    throw new SafeException(__(self::LANG_GROUP, 'El país no existe'));
                                }
                            } else if (!Validator::isInteger($i)) {
                                return false;
                            }
                        }
                    }
                    return $valid;
                },
                false,
                function ($value) {
                    return is_array($value) ? array_map(function ($e) {
                        if (Validator::isInteger($e)) {
                            return new CountryMapper($e);
                        }
                    }, $value) : [];
                }
            ),
            new Parameter(
                'currency',
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
                'amount',
                null,
                function ($value) {
                    return Validator::isDouble($value);
                },
                false,
                function ($value) {
                    return Validator::isDouble($value) ? (double) $value : $value;
                }
            ),
            new Parameter(
                'interestResearhAreas',
                null,
                function ($value) {
                    $isArray = is_array($value);
                    $valid = $isArray && !empty($value);
                    if ($valid) {
                        foreach ($value as $i) {
                            if ($i instanceof InterestResearchAreasMapper) {
                                if ($i->id == null) {
                                    throw new SafeException(__(self::LANG_GROUP, 'El área de interés no es válida'));
                                }
                            } else if (!Validator::isInteger($i)) {
                                return false;
                            }
                        }
                    }
                    return $valid;
                },
                false,
                function ($value) {
                    return is_array($value) ? array_map(function ($e) {
                        if (Validator::isInteger($e)) {
                            return new InterestResearchAreasMapper($e);
                        }
                    }, $value) : [];
                }
            ),
            new Parameter(
                'participatingInstitutions',
                [],
                function ($value) {
                    $isArray = is_array($value);
                    $valid = $isArray || is_null($value);
                    if ($valid) {
                        foreach ($value as $i) {
                            if (!is_scalar($i)) {
                                return false;
                            }
                        }
                    }
                    return $valid;
                },
                true,
                function ($value) {
                    return is_array($value) ? array_map(function ($e) {
                        if (is_scalar($e)) {
                            return clean_string((string) $e);
                        } else {
                            return null;
                        }
                    }, $value) : [];
                }
            ),
            new Parameter(
                'applicationLink',
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
                'startDate',
                null,
                function ($value) {
                    return $value === null || Validator::isDate($value, 'd-m-Y h:i A');
                },
                false,
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
                false,
                function ($value) {
                    return $value === null ? $value : \DateTime::createFromFormat('d-m-Y h:i A', $value);
                }
            ),
            new Parameter(
                'content',
                null,
                function ($value) {
                    $isArray = is_array($value);
                    $valid = is_null($value) || (is_string($value) && mb_strlen(trim($value)) > 0) || $isArray;
                    $allowedLangs = Config::get_allowed_langs();
                    if ($isArray) {
                        foreach ($value as $key => $v) {
                            if (!(
                                is_string($key) &&
                                in_array($key, $allowedLangs) &&
                                is_string($v) &&
                                mb_strlen(trim($v)) > 0
                            )) {
                                $valid = false;
                            }
                        }
                    }

                    return $valid;
                },
                false,
                function ($value) {
                    $parsed = [];
                    $allowedLangs = Config::get_allowed_langs();
                    if (is_array($value)) {
                        foreach ($value as $key => $v) {
                            if ((
                                is_string($key) &&
                                in_array($key, $allowedLangs) &&
                                is_string($v) &&
                                mb_strlen(trim($v)) > 0
                            )) {
                                $parsed[$key] = clean_string($v);
                            }
                        }
                        return $parsed;
                    } else {
                        return is_string($value) ? clean_string($value) : $value;
                    }
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Contenido'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'El contenido que intenta modificar no existe.');
        $successCreateMessage = __(self::LANG_GROUP, 'Contenido creado.');
        $successEditMessage = __(self::LANG_GROUP, 'Datos guardados.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');
        $notAllowedLangMessage = __(self::LANG_GROUP, 'El idioma "%s" no está permitido.');

        //──── Anexos ────────────────────────────────────────────────────────────────────────────

        $baseAttachmentIDKey = 'attachmentsID_';
        $baseAttachmentNameKey = 'attachmentsName_';
        $baseAttachmentFileKey = 'attachmentsFile_';
        $attachmentIDKeys = array_filter(array_keys($_POST), fn($e) => str_starts_with($e, $baseAttachmentIDKey));
        $attachmentNamesKeys = array_filter(array_keys($_POST), fn($e) => str_starts_with($e, $baseAttachmentNameKey));
        $attachmentFilesKeys = array_filter(array_keys($_FILES), fn($e) => str_starts_with($e, $baseAttachmentFileKey));
        $attachmentExistsByIndex = function ($index) use ($baseAttachmentNameKey, $baseAttachmentFileKey, $baseAttachmentIDKey, $attachmentNamesKeys, $attachmentFilesKeys, $attachmentIDKeys) {
            $nameIndex = "{$baseAttachmentNameKey}{$index}";
            $fileIndex = "{$baseAttachmentFileKey}{$index}";
            $idIndex = "{$baseAttachmentIDKey}{$index}";
            $pairFileName = in_array($nameIndex, $attachmentNamesKeys) && in_array($fileIndex, $attachmentFilesKeys);
            $pairIDName = in_array($nameIndex, $attachmentNamesKeys) && in_array($idIndex, $attachmentIDKeys);
            return $pairFileName || $pairIDName;
        };
        $attachmentIndexes = array_map(fn($e) => explode('_', $e)[1], $attachmentNamesKeys);
        $attachmentsUploaded = array_map(fn($e) => ($attachmentExistsByIndex)($e) ? [
            'id' => array_key_exists("{$baseAttachmentIDKey}{$e}", $_POST) ? $_POST["{$baseAttachmentIDKey}{$e}"] : null,
            'nameOnFiles' => array_key_exists("{$baseAttachmentFileKey}{$e}", $_FILES) ? "{$baseAttachmentFileKey}{$e}" : null,
            'name' => $_POST["{$baseAttachmentNameKey}{$e}"],
            'file' => array_key_exists("{$baseAttachmentFileKey}{$e}", $_FILES) ? $_FILES["{$baseAttachmentFileKey}{$e}"] : null,
        ] : null, $attachmentIndexes);
        $attachmentsUploaded = array_filter($attachmentsUploaded, fn($e) => $e !== null);

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario
            /**
             * @var int $id
             * @var string $lang
             * @var string $baseLang
             * @var string $contentType
             * @var string $title
             * @var string $financingType
             * @var CountryMapper[] $targetCountries
             * @var string $currency
             * @var double $amount
             * @var InterestResearchAreasMapper[] $interestResearhAreas
             * @var string[] $participatingInstitutions
             * @var string $applicationLink
             * @var \DateTime $startDate
             * @var \DateTime $endDate
             * @var string $content
             */
            $id = $expectedParameters->getValue('id');
            $lang = $expectedParameters->getValue('lang');
            $baseLang = $expectedParameters->getValue('baseLang');
            $contentType = $expectedParameters->getValue('contentType');
            $title = $expectedParameters->getValue('title');
            $financingType = $expectedParameters->getValue('financingType');
            $targetCountries = $expectedParameters->getValue('targetCountries');
            $currency = $expectedParameters->getValue('currency');
            $amount = $expectedParameters->getValue('amount');
            $interestResearhAreas = $expectedParameters->getValue('interestResearhAreas');
            $participatingInstitutions = $expectedParameters->getValue('participatingInstitutions');
            $applicationLink = $expectedParameters->getValue('applicationLink');
            $startDate = $expectedParameters->getValue('startDate');
            $endDate = $expectedParameters->getValue('endDate');
            $content = $expectedParameters->getValue('content');

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

                    //En creación $lang es el idioma base
                    $lang = $baseLang;
                    $mapper = new ApplicationCallsMapper();

                    $mapper->baseLang = $baseLang;
                    $mapper->setLangData($lang, 'contentType', $contentType);
                    $mapper->addDataManyLangs('title', $title, array_keys($title));
                    $mapper->setLangData($lang, 'financingType', $financingType);
                    $mapper->targetCountries = $targetCountries;
                    $mapper->setLangData($lang, 'currency', $currency);
                    $mapper->setLangData($lang, 'amount', $amount);
                    $mapper->interestResearhAreas = $interestResearhAreas;
                    $mapper->setLangData($lang, 'participatingInstitutions', $participatingInstitutions);
                    $mapper->setLangData($lang, 'applicationLink', $applicationLink);
                    $mapper->setLangData($lang, 'startDate', $startDate);
                    $mapper->setLangData($lang, 'endDate', $endDate);
                    $mapper->addDataManyLangs('content', $content, array_keys($content));
                    $mapper->setLangData($lang, 'folder', str_replace('.', '', uniqid()));

                    $mainImage = self::handlerUpload('mainImage', $mapper->folder);
                    $thumbImage = self::handlerUpload('thumbImage', $mapper->folder);

                    $mapper->setLangData($lang, 'mainImage', $mainImage);
                    $mapper->setLangData($lang, 'thumbImage', $thumbImage);

                    $saved = $mapper->save();
                    $resultOperation->setSuccessOnSingleOperation($saved);

                    if ($saved) {

                        foreach ($attachmentsUploaded as $attachmentUploaded) {

                            if ($attachmentUploaded['file'] !== null) {
                                $attachmentConfig = new AttachmentPackage($mapper->id, -1, $attachmentUploaded['name'], false, $lang);
                                $attachMapper = $attachmentConfig->getMapper();
                                $attachMapper = $attachMapper !== null ? $attachMapper : new AttachmentApplicationCallsMapper();
                                $attachMapper->applicationCall = $mapper->id;
                                $attachMapper->lang = $lang;
                                $attachMapper->attachmentName = $attachmentConfig->getDisplayName();
                                $attachMapper->folder = $mapper->folder . '/' . 'attachments';

                                $attachFile = self::handlerUpload($attachmentUploaded['nameOnFiles'], $attachMapper->folder, null, [
                                    FileValidator::TYPE_ALL_IMAGES,
                                    FileValidator::TYPE_PDF,
                                    FileValidator::TYPE_DOC,
                                    FileValidator::TYPE_DOCX,
                                    FileValidator::TYPE_XLS,
                                    FileValidator::TYPE_XLSX,
                                ], false, friendly_url($attachMapper->attachmentName));

                                if (mb_strlen($attachFile) > 0) {
                                    $attachMapper->fileLocation = $attachFile;
                                    $attachMapper->id !== null ? $attachMapper->update() : $attachMapper->save();
                                }
                            }

                        }

                        $resultOperation
                            ->setMessage($successCreateMessage)
                            ->setValue('redirect', true)
                            ->setValue('redirect_to', self::routeName('list'));

                    } else {
                        $resultOperation->setMessage($unknowErrorMessage);
                    }

                } else {
                    //Existente

                    $mapper = new ApplicationCallsMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        $baseLang = $mapper->baseLang;
                        $isBaseLang = $lang == $baseLang;
                        $translationData = clone $mapper->langData;
                        $translationExists = !$isBaseLang ? property_exists($translationData, $lang) : true;

                        $mapper->setLangData($lang, 'contentType', $contentType);
                        $mapper->addDataManyLangs('title', $title, array_keys($title));
                        $mapper->setLangData($lang, 'financingType', $financingType);
                        $mapper->targetCountries = $targetCountries;
                        $mapper->setLangData($lang, 'currency', $currency);
                        $mapper->setLangData($lang, 'amount', $amount);
                        $mapper->interestResearhAreas = $interestResearhAreas;
                        $mapper->setLangData($lang, 'participatingInstitutions', $participatingInstitutions);
                        $mapper->setLangData($lang, 'applicationLink', $applicationLink);
                        $mapper->setLangData($lang, 'startDate', $startDate);
                        $mapper->setLangData($lang, 'endDate', $endDate);
                        $mapper->addDataManyLangs('content', $content, array_keys($content));

                        $mainImageSetted = $translationExists ? $mapper->getLangData($lang, 'mainImage', false, null) : null;
                        $thumbImageSetted = $translationExists ? $mapper->getLangData($lang, 'thumbImage', false, null) : null;
                        $suffixLangName = !$translationExists ? $lang : '';

                        if ($mainImageSetted !== null) {
                            $mainImage = self::handlerUpload('mainImage', '', $mainImageSetted);
                        } else {
                            $mainImage = self::handlerUpload('mainImage', $mapper->folder, null, null, true, null, $suffixLangName);
                        }

                        if ($thumbImageSetted !== null) {
                            $thumbImage = self::handlerUpload('thumbImage', '', $thumbImageSetted);
                        } else {
                            $thumbImage = self::handlerUpload('thumbImage', $mapper->folder, null, null, true, null, $suffixLangName);
                        }

                        if (mb_strlen($mainImage) > 0) {
                            $mapper->setLangData($lang, 'mainImage', $mainImage);
                        }
                        if (mb_strlen($thumbImage) > 0) {
                            $mapper->setLangData($lang, 'thumbImage', $thumbImage);
                        }

                        foreach ($attachmentsUploaded as $attachmentUploaded) {

                            $attachmentID = $attachmentUploaded['id'];
                            $attachmentID = Validator::isInteger($attachmentID) ? (int) $attachmentID : -1;
                            $attachmentConfig = new AttachmentPackage($mapper->id, $attachmentID, $attachmentUploaded['name'], false, $lang);
                            $attachMapper = $attachmentConfig->getMapper();
                            $attachMapper = $attachMapper !== null ? $attachMapper : new AttachmentApplicationCallsMapper();
                            $langSuffix = $baseLang != $lang ? "_{$lang}" : '';
                            $attachMapper->applicationCall = $mapper->id;
                            $attachMapper->lang = $lang;
                            $attachMapper->attachmentName = $attachmentConfig->getDisplayName();
                            $attachMapper->folder = $mapper->folder . '/' . 'attachments';

                            if ($attachMapper->id !== null) {
                                if ($attachmentUploaded['nameOnFiles'] !== null) {
                                    $attachFile = self::handlerUpload($attachmentUploaded['nameOnFiles'], '', $attachMapper->fileLocation, [
                                        FileValidator::TYPE_ALL_IMAGES,
                                        FileValidator::TYPE_PDF,
                                        FileValidator::TYPE_DOC,
                                        FileValidator::TYPE_DOCX,
                                        FileValidator::TYPE_XLS,
                                        FileValidator::TYPE_XLSX,
                                    ], false, friendly_url($attachMapper->attachmentName) . $langSuffix);
                                } else {
                                    $attachFile = $attachMapper->fileLocation;
                                }
                            } else {
                                $attachFile = self::handlerUpload($attachmentUploaded['nameOnFiles'], $attachMapper->folder, null, [
                                    FileValidator::TYPE_ALL_IMAGES,
                                    FileValidator::TYPE_PDF,
                                    FileValidator::TYPE_DOC,
                                    FileValidator::TYPE_DOCX,
                                    FileValidator::TYPE_XLS,
                                    FileValidator::TYPE_XLSX,
                                ], false, friendly_url($attachMapper->attachmentName) . $langSuffix);
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
                                ->setValue('reload', false)
                                ->setValue('redirect', false)
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

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Eliminar contenido'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setValue('received', $inputData);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'El contenido que intenta eliminar no existe.');
        $successMessage = __(self::LANG_GROUP, 'Contenido eliminado.');
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

                $exists = ApplicationCallsMapper::existsByID($id);

                if ($exists) {

                    //Dirección de redirección en caso de creación
                    $redirectURLOn = self::routeName('list');

                    $table = ApplicationCallsMapper::TABLE;
                    $inactive = ApplicationCallsMapper::INACTIVE;

                    $transactionSQLDeleteQueries = [
                        [
                            'query' => "UPDATE {$table} SET {$table}.status = {$inactive} WHERE id = :ID",
                            'aliasConfig' => [
                                ':ID' => $id,
                            ],
                        ],
                    ];

                    $pdo = ApplicationCallsMapper::model()::getDb(Config::app_db('default')['db']);
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
            new Parameter(
                'ignoreSlugs',
                [],
                function ($value) {
                    $valid = is_array($value);
                    if ($valid) {
                        foreach ($value as $slug) {
                            $valid = is_scalar($slug) && mb_strlen((string) $slug) > 0;
                        }
                    }
                    return $valid;
                },
                true,
                function ($value) {
                    $result = [];
                    $value = is_array($value) ? $value : [$value];
                    foreach ($value as $key => $slug) {
                        if (is_scalar($slug) && mb_strlen((string) $slug) > 0) {
                            $result[$key] = (string) $slug;
                        }
                    }
                    return $result;
                }
            ),
            //Filtros consultas
            new Parameter(
                'search',
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
                'researchAreas',
                [],
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return is_array($value);
                },
                true,
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return array_map(fn($e) => is_scalar($e) ? (
                        Validator::isInteger($e) ? (int) $e : -1
                    ) : -1, $value);
                }
            ),
            new Parameter(
                'organizations',
                [],
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return is_array($value);
                },
                true,
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return array_map(fn($e) => is_scalar($e) ? (
                        Validator::isInteger($e) ? (int) $e : -1
                    ) : -1, $value);
                }
            ),
            new Parameter(
                'contentType',
                [],
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return is_array($value);
                },
                true,
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return array_map(fn($e) => is_scalar($e) ? (string) $e : '-1', $value);
                }
            ),
            new Parameter(
                'financingType',
                [],
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return is_array($value);
                },
                true,
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return array_map(fn($e) => is_scalar($e) ? (string) $e : '-1', $value);
                }
            ),

            new Parameter(
                'startDate',
                null,
                function ($value) {
                    return $value === null || Validator::isDate($value, 'd-m-Y');
                },
                true,
                function ($value) {
                    return $value === null ? $value : \DateTime::createFromFormat('d-m-Y', $value);
                }
            ),
            new Parameter(
                'endDate',
                null,
                function ($value) {
                    return $value === null || Validator::isDate($value, 'd-m-Y');
                },
                true,
                function ($value) {
                    return $value === null ? $value : \DateTime::createFromFormat('d-m-Y', $value);
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
         * @var string[] $ignoreSlugs
         */
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');
        $status = $expectedParameters->getValue('status');
        $title = $expectedParameters->getValue('title');
        $ignoreSlugs = $expectedParameters->getValue('ignoreSlugs');
        $internalItem = $request->getQueryParam('internal') === 'yes';
        //Filtros consultas
        $search = $expectedParameters->getValue('search');
        $researchAreas = $expectedParameters->getValue('researchAreas');
        $organizations = $expectedParameters->getValue('organizations');
        $contentType = $expectedParameters->getValue('contentType');
        $financingType = $expectedParameters->getValue('financingType');
        $startDate = $expectedParameters->getValue('startDate');
        $endDate = $expectedParameters->getValue('endDate');

        $ignoreStatus = $status === 'ANY';
        $status = $status === 'ANY' ? null : $status;

        $sourceData = self::RESPONSE_SOURCE_NORMAL_RESULT;
        $result = self::_all($page, $perPage, $status, $title, $ignoreStatus, false, $ignoreSlugs, $internalItem, [
            'search' => $search,
            'researchAreas' => $researchAreas,
            'organizations' => $organizations,
            'contentType' => $contentType,
            'financingType' => $financingType,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
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

        $currentUser = getLoggedFrameworkUser();
        $currentUserID = $currentUser->id;
        $currentUserType = $currentUser->type;
        $currentOrganizationMapper = $currentUser->organizationMapper;
        $organizationAdmin = $currentOrganizationMapper !== null ? $currentOrganizationMapper->administrator : null;
        $whereString = null;
        $havingString = null;
        $and = 'AND';
        $table = ApplicationCallsMapper::TABLE;
        $inactive = ApplicationCallsMapper::INACTIVE;

        $where = [
            "{$table}.status != {$inactive}",
        ];
        $having = [];

        //Restricciones según organización (a menos que pueda verlas todas por ApplicationCallsMapper::CAN_VIEW_ALL)
        if (!in_array($currentUserType, ApplicationCallsMapper::CAN_VIEW_ALL)) {

            if ($currentOrganizationMapper !== null) {

                //Ver solo las de su organización
                $beforeOperator = !empty($having) ? $and : '';
                $critery = "organizationID = {$currentOrganizationMapper->id}";
                $having[] = "{$beforeOperator} ({$critery})";

                //Si no es el adminstrador, solo ver las propias
                if ($organizationAdmin->id !== $currentUserID) {
                    $beforeOperator = !empty($having) ? $and : '';
                    $critery = "createdBy = {$currentUserID}";
                    $having[] = "{$beforeOperator} ({$critery})";
                }
            }

        }

        if (false) {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = "FIELD = VALUE";
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
        }

        $selectFields = ApplicationCallsMapper::fieldsToSelect('%M %d, %Y');

        $columnsOrder = [
            'contentTypeAndFinancingTypeText',
            'title',
            'interestResearhAreasColorsNames',
            'endDateFormat',
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
            'mapper' => new ApplicationCallsMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {

                $mapper = ApplicationCallsMapper::objectToMapper($e);

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
                    $deleteButton = "<a title='{$deleteText}' data-route='{$deleteLink}' class='ui button brand-color alt2 icon' delete-application-call-button>{$deleteIcon}</a>";
                    $buttons[] = $deleteButton;
                }

                $buttons = implode('', $buttons);
                $columns = [];
                $title = mb_strlen($e->title) <= 54 ? $e->title : mb_substr($e->title, 0, 51) . '...';
                $areaTags = [];
                $areasNamesColors = is_string($e->interestResearhAreasColorsNames) ? explode('|@|', $e->interestResearhAreasColorsNames) : [];
                foreach ($areasNamesColors as $areasNameColor) {
                    $areaColor = explode(':', $areasNameColor)[0];
                    $areaName = explode(':', $areasNameColor)[1];
                    $areaTags[] = "<span class='area-tag' style='--area-color: {$areaColor}'>{$areaName}</span>";
                }
                $areaTags = "<div class='area-tags'>" . implode(' ', $areaTags) . "</div>";

                $columns[] = $e->contentTypeAndFinancingTypeText;
                $columns[] = $title;
                $columns[] = $areaTags;
                $columns[] = ucfirst($e->endDateFormat);
                $columns[] = $buttons;
                return $columns;
            },

        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * @param int $page =1
     * @param int $perPage =10
     * @param int $status =ApplicationCallsMapper::ACTIVE
     * @param string $title =null
     * @param bool $ignoreStatus =false
     * @param bool $ignoreDateLimit =false
     * @param string[] $ignoreSlugs =[]
     * @param bool $internalItem =false
     * @param array $others
     * @return PaginationResult
     */
    public static function _all(
        int $page = null,
        int $perPage = null,
        int $status = null,
        string $title = null,
        bool $ignoreStatus = false,
        bool $ignoreDateLimit = false,
        array $ignoreSlugs = [],
        bool $internalItem = false,
        array $others = []
    ) {
        $page = $page === null ? 1 : $page;
        $perPage = $perPage === null ? 10 : $perPage;
        $status = $status === null ? ApplicationCallsMapper::ACTIVE : $status;

        /**
         * Otros criterios
         * @var string|null $search
         * @var int[]|null $researchAreas
         * @var int[]|null $organizations
         * @var string[]|null $contentType
         * @var string[]|null $financingType
         * @var \DateTime|null $startDate
         * @var \DateTime|null $endDate
         */
        $search = array_key_exists('search', $others) ? $others['search'] : null;
        $researchAreas = array_key_exists('researchAreas', $others) ? $others['researchAreas'] : null;
        $organizations = array_key_exists('organizations', $others) ? $others['organizations'] : null;
        $contentType = array_key_exists('contentType', $others) ? $others['contentType'] : null;
        $financingType = array_key_exists('financingType', $others) ? $others['financingType'] : null;
        $startDate = array_key_exists('startDate', $others) ? $others['startDate'] : null;
        $endDate = array_key_exists('endDate', $others) ? $others['endDate'] : null;

        $table = ApplicationCallsMapper::TABLE;
        $fields = ApplicationCallsMapper::fieldsToSelect();
        $validateSystemApprovals = SystemApprovalsRoutes::ENABLE && !empty(array_filter($fields, fn($e) => mb_strpos($e, 'systemApprovalStatus')));

        $whereString = null;
        $where = [];
        $havingString = null;
        $having = [];
        $and = 'AND';

        if (!$ignoreStatus) {

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$table}.status = {$status}";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        if (!empty($ignoreSlugs)) {

            $beforeOperator = !empty($where) ? $and : '';
            $ignoreSlugs = implode('","', $ignoreSlugs);
            $ignoreSlugs = '"' . $ignoreSlugs . '"';
            $critery = "{$table}.preferSlug NOT IN ({$ignoreSlugs})";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        if ($title !== null) {

            $beforeOperator = !empty($where) ? $and : '';
            $titleField = ApplicationCallsMapper::fieldCurrentLangForSQL('title');
            $critery = "UPPER({$titleField}) LIKE UPPER('%{$title}%')";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        if ($search !== null) {
            $beforeOperator = !empty($having) ? $and : '';
            $titleField = ApplicationCallsMapper::fieldCurrentLangForSQL('title');
            $contentField = ApplicationCallsMapper::fieldCurrentLangForSQL('content');
            $fields[] = "{$titleField} AS titleForQuerySearch";
            $fields[] = "{$contentField} AS contentForQuerySearch";
            $critery = [
                "UPPER(titleForQuerySearch) LIKE UPPER('%{$search}%')",
                "UPPER(contentForQuerySearch) LIKE UPPER('%{$search}%')",
                "TRIM(UPPER(targetCountriesNames)) COLLATE utf8_general_ci LIKE TRIM(UPPER('%{$search}%'))",
            ];
            $critery = implode(' OR ', $critery);
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($researchAreas)) {
            $beforeOperator = !empty($where) ? $and : '';
            $critery = [];
            foreach ($researchAreas as $researchArea) {
                $critery[] = "JSON_CONTAINS(JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.interestResearhAreas')), {$researchArea})";
            }
            $critery = implode(' OR ', $critery);
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($organizations)) {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = [];
            foreach ($organizations as $organization) {
                $critery[] = "organizationID = {$organization}";
            }
            $critery = implode(' OR ', $critery);
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($contentType)) {
            $beforeOperator = !empty($where) ? $and : '';
            $contentType = implode("','", $contentType);
            $critery = "{$table}.contentType IN ('{$contentType}')";
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($financingType)) {
            $beforeOperator = !empty($where) ? $and : '';
            $financingType = implode("','", $financingType);
            $critery = "{$table}.financingType IN ('{$financingType}')";
            $where[] = "{$beforeOperator} ({$critery})";
        }

        $startDateStr = $startDate !== null ? $startDate->format('Y-m-d 00:00:00') : '';
        $endDateStr = $endDate !== null ? $endDate->format('Y-m-d 00:00:00') : '';
        if ($startDate !== null && $endDate !== null) {
            $beforeOperator = !empty($where) ? $and : '';
            $startDateCritery = "DATE({$table}.startDate) >= '{$startDateStr}'";
            $endDateCritery = "DATE({$table}.endDate) <= '{$endDateStr}'";
            $critery = "({$startDateCritery}) AND ({$endDateCritery})";
            $where[] = "{$beforeOperator} ({$critery})";
        } else {
            if ($startDate !== null) {
                $beforeOperator = !empty($where) ? $and : '';
                $critery = "DATE({$table}.endDate) >= '{$startDateStr}' AND '{$startDateStr}' <= DATE({$table}.startDate)";
                $where[] = "{$beforeOperator} ({$critery})";
            }
            if ($endDate !== null) {
                $beforeOperator = !empty($where) ? $and : '';
                $critery = "DATE({$table}.startDate) <= '{$endDateStr}' AND DATE({$table}.endDate) >= '{$endDateStr}'";
                $where[] = "{$beforeOperator} ({$critery})";
            }
        }

        if ($validateSystemApprovals) {
            $approved = SystemApprovalsMapper::STATUS_APPROVED;
            $beforeOperator = !empty($having) ? $and : '';
            $critery = "systemApprovalStatus = '{$approved}'";
            $having[] = "{$beforeOperator} ({$critery})";
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

        //Verificar idiomas
        $showAlways = false; //Define si se muestra siempre aunque no tenga traducción
        $currentLang = Config::get_lang();

        if (!$showAlways) {
            $beforeOperator = !empty($where) ? $and : '';
            $critery = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}')) IS NOT NULL || JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.baseLang')) = '{$currentLang}'";
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = implode(' ', $where);
        }
        if (!empty($having)) {
            $havingString = implode(' ', $having);
        }

        $fields = implode(', ', $fields);
        $sqlSelect = "SELECT {$fields} FROM {$table}";

        if ($whereString !== null) {
            $sqlSelect .= " WHERE {$whereString}";
        }

        if ($havingString !== null) {
            $sqlSelect .= " HAVING {$havingString}";
        }

        $sqlCount = "SELECT COUNT(subQuery.id) AS total FROM ({$sqlSelect}) " . 'AS subQuery';

        $orderBy = ApplicationCallsMapper::ORDER_BY_PREFERENCE;
        if (isset($_GET['random']) && $_GET['random'] === 'yes') {
            $orderBy = ['RAND()'];
        }
        $sqlSelect .= " ORDER BY " . implode(', ', $orderBy);

        $pageQuery = new PageQuery($sqlSelect, $sqlCount, $page, $perPage, 'total');

        $parser = function ($element) use ($internalItem) {
            $element = ApplicationCallsMapper::objectToMapper($element);
            $viewName = !$internalItem ? 'public/util/item' : 'public/util/item-internal';
            $element = mb_convert_encoding(ApplicationCallsPublicController::view($viewName, [
                'element' => $element,
            ], false, false), 'UTF-8');
            return $element;
        };
        $each = function ($element) use ($internalItem) {
            if (!$internalItem) {
                $mapper = ApplicationCallsMapper::objectToMapper($element);
                $excerpt = $mapper->excerpt(253);
                $excerptAlt = $mapper->excerpt(102);
                $element->excerpt = mb_strpos($excerpt, '...') ? $excerpt : $excerpt . '...';
                $element->excerptAlt = mb_strpos($excerptAlt, '...') ? $excerptAlt : $excerptAlt . '...';
                unset($element->folder);
                unset($element->idPadding);
                unset($element->isActiveByDate);
                unset($element->meta);
            }
            foreach ($element as $key => $value) {
                if (is_string($value)) {
                    $element->$key = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            }
            return $element;
        };

        $pagination = $pageQuery->getPagination($parser, $each);

        return $pagination;
    }

    /**
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        return parent::render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
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
                $currentOrganizationMapper = $currentUser->organizationMapper;

                if ($name == 'actions-delete') {

                    $allow = false;
                    $id = ($getParam)('id');
                    $applicationCall = ApplicationCallsMapper::getBy($id, 'id');

                    if ($applicationCall !== null) {

                        $createdByID = (int) $applicationCall->createdBy;
                        $createdByOrganizationID = (int) UsersModel::getBy($applicationCall->createdBy, 'id')->organization;
                        $createdByOrganizationRecord = OrganizationMapper::getBy($createdByOrganizationID, 'id', true);
                        $createdByOrganizationAdminID = $createdByOrganizationRecord !== null ? $createdByOrganizationRecord->administrator->id : null;
                        $currentIsSameOrg = $currentOrganizationMapper !== null ? $createdByOrganizationID == $currentOrganizationMapper->id : false;
                        $currentIsOrgAdmin = $createdByOrganizationAdminID == $currentUserID;
                        $allowByOrg = $currentIsSameOrg && $currentIsOrgAdmin;

                        $allow = $createdByID == $currentUserID;

                        if (in_array($currentUserType, ApplicationCallsMapper::CAN_DELETE_ALL) || $allowByOrg) {
                            $allow = true;
                        }

                    }

                } elseif ($name == 'forms-edit') {

                    $allow = false;
                    $id = ($getParam)('id');
                    $applicationCall = ApplicationCallsMapper::getBy($id, 'id');

                    if ($applicationCall !== null) {

                        $createdByID = (int) $applicationCall->createdBy;
                        $createdByOrganizationID = (int) UsersModel::getBy($applicationCall->createdBy, 'id')->organization;
                        $createdByOrganizationRecord = OrganizationMapper::getBy($createdByOrganizationID, 'id', true);
                        $createdByOrganizationAdminID = $createdByOrganizationRecord !== null ? $createdByOrganizationRecord->administrator->id : null;
                        $currentIsSameOrg = $currentOrganizationMapper !== null ? $createdByOrganizationID == $currentOrganizationMapper->id : false;
                        $currentIsOrgAdmin = $createdByOrganizationAdminID == $currentUserID;
                        $allowByOrg = $currentIsSameOrg && $currentIsOrgAdmin;

                        $allow = $createdByID == $currentUserID;

                        if (in_array($currentUserType, ApplicationCallsMapper::CAN_EDIT_ALL) || $allowByOrg) {
                            $allow = true;
                        }

                    }

                }

            }

        }

        return $allow;
    }

    public static function pathFrontApplicationCallAdapter()
    {
        return ApplicationCallsRoutes::staticRoute('js/ApplicationCallAdapter.js');
    }

    /**
     * @param string $nameOnFiles
     * @param string $folder
     * @param string $currentRoute
     * @param array $allowedTypes
     * @param bool $setNameByInput
     * @param string $name
     * @param string $suffixName
     * @return string
     * @throws \Exception
     */
    protected static function handlerUpload(string $nameOnFiles, string $folder, string $currentRoute = null, array $allowedTypes = null, bool $setNameByInput = true, string $name = null, string $suffixName = '')
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

                $instance = new ApplicationCallsController;
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
                if (mb_strlen($suffixName) > 0) {
                    $name .= "_{$suffixName}";
                }

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
        $list = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_GENERAL,
        ];
        $creation = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_GENERAL,
        ];
        $edition = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_GENERAL,
        ];
        $deletion = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
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
                "{$startRoute}/forms/edit/{id}[/]",
                $classname . ':editForm',
                self::$baseRouteName . '-forms-edit',
                'GET',
                true,
                null,
                $edition
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
