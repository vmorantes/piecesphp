<?php

/**
 * OrganizationsController.php
 */

namespace Organizations\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use MySpace\Controllers\MyOrganizationProfileController;
use MySpace\Controllers\OrganizationProfileController;
use Organizations\Exceptions\DuplicateException;
use Organizations\Exceptions\SafeException;
use Organizations\Mappers\OrganizationMapper;
use Organizations\OrganizationsLang;
use Organizations\OrganizationsRoutes;
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

/**
 * OrganizationsController.
 *
 * @package     Organizations\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class OrganizationsController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'admin';
    /**
     * @var string
     */
    protected static $baseRouteName = 'organizations-admin';
    /**
     * @var string
     */
    protected static $title = 'Organización';

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

    const BASE_VIEW_DIR = 'organizations';
    const BASE_JS_DIR = 'js/organizations';
    const BASE_CSS_DIR = 'css';
    const UPLOAD_DIR = 'organizations';
    const UPLOAD_DIR_TMP = 'organizations/tmp';
    const LANG_GROUP = OrganizationsLang::LANG_GROUP;

    const RESPONSE_SOURCE_STATIC_CACHE = 'STATIC_CACHE';
    const RESPONSE_SOURCE_NORMAL_RESULT = 'NORMAL_RESULT';
    const ENABLE_CACHE = true;

    public function __construct()
    {
        parent::__construct();

        $this->model = (new OrganizationMapper())->getModel();

        $baseURL = base_url();
        $pcsUploadDir = get_config('upload_dir');
        $pcsUploadDirURL = get_config('upload_dir_url');

        $this->uploadDir = append_to_path_system($pcsUploadDir, self::UPLOAD_DIR);
        $this->uploadTmpDir = append_to_path_system($pcsUploadDir, self::UPLOAD_DIR_TMP);
        $this->uploadDirURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR));
        $this->uploadDirTmpURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR_TMP));

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(OrganizationsRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(OrganizationsRoutes::staticRoute(self::BASE_CSS_DIR . '/organizations.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addForm(Request $request, Response $response)
    {

        remove_imported_asset('locations');
        import_locations([], false, true);
        import_cropper();
        set_custom_assets([
            OrganizationsRoutes::staticRoute(self::BASE_JS_DIR . '/forms.js'),
        ], 'js');

        $action = self::routeName('actions-add');
        $backLink = self::routeName('list');
        $optionsStatus = array_to_html_options(OrganizationMapper::statusesForSelect(), null);
        $optionsSizes = array_to_html_options(OrganizationMapper::sizesForSelect(), null);
        $optionsActionLines = array_to_html_options(OrganizationMapper::actionLinesForSelect(), null, true);
        $optionsEsal = array_to_html_options(OrganizationMapper::esalOptionsForSelect(), null);

        $title = __(self::LANG_GROUP, 'Agregar organización');
        $description = '';

        set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

        $data = [];
        $data['action'] = $action;
        $data['langGroup'] = self::LANG_GROUP;
        $data['title'] = $title;
        $data['description'] = $description;
        $data['optionsStatus'] = $optionsStatus;
        $data['optionsSizes'] = $optionsSizes;
        $data['optionsActionLines'] = $optionsActionLines;
        $data['optionsEsal'] = $optionsEsal;
        $data['breadcrumbs'] = get_breadcrumbs([
            __(self::LANG_GROUP, 'Inicio') => [
                'url' => get_route('admin'),
            ],
            __(self::LANG_GROUP, 'Organizaciones') => [
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

        $element = new OrganizationMapper($id);

        if ($element->id !== null && OrganizationMapper::existsByID($element->id)) {

            remove_imported_asset('locations');
            import_locations([], false, true);
            import_cropper();
            set_custom_assets([
                OrganizationsRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
                OrganizationsRoutes::staticRoute(self::BASE_JS_DIR . '/forms.js'),
            ], 'js');

            $action = self::routeName('actions-edit', [
                'id' => $element->id,
            ]);
            $backLink = self::routeName('list');
            $optionsStatus = array_to_html_options(OrganizationMapper::statusesForSelect(), $element->status);
            $optionsSizes = array_to_html_options(OrganizationMapper::sizesForSelect(), $element->size);
            $actionLines = is_array($element->actionLines) ? $element->actionLines : [];
            $optionsActionLines = array_to_html_options(OrganizationMapper::actionLinesForSelect('', '', $actionLines), $element->actionLines, true);
            $optionsEsal = array_to_html_options(OrganizationMapper::esalOptionsForSelect(), $element->esal);
            $optionsUsersAdministrators = array_to_html_options(UsersModel::allOrganizationUsersCanBeAdminForSelect($element->id), $element->administrator->id);
            $manyLangs = count($allowedLangs) > 1;
            $allowedLangs = array_to_html_options(self::allowedLangsForSelect($lang, $element->id), $lang);

            $title = __(self::LANG_GROUP, 'Edición de organización');
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
            $data['optionsStatus'] = $optionsStatus;
            $data['optionsSizes'] = $optionsSizes;
            $data['optionsActionLines'] = $optionsActionLines;
            $data['optionsEsal'] = $optionsEsal;
            $data['optionsUsersAdministrators'] = $optionsUsersAdministrators;
            $data['allowedLangs'] = $allowedLangs;
            $data['manyLangs'] = $manyLangs;
            $data['lang'] = $lang;
            $data['breadcrumbs'] = get_breadcrumbs([
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => get_route('admin'),
                ],
                __(self::LANG_GROUP, 'Organizaciones') => [
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
        $processTableActivesLink = self::routeName('datatables') . "?status=" . OrganizationMapper::ACTIVE;
        $processTableInactivesLink = self::routeName('datatables') . "?status=" . OrganizationMapper::INACTIVE;
        $processTablePendingsLink = self::routeName('datatables') . "?status=" . OrganizationMapper::PENDING_APPROVAL;

        $title = __(self::LANG_GROUP, 'Organizaciones');
        $description = __(self::LANG_GROUP, 'Listado de organizaciones');

        set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

        $data = [];
        $data['processTableActivesLink'] = $processTableActivesLink;
        $data['processTableInactivesLink'] = $processTableInactivesLink;
        $data['processTablePendingsLink'] = $processTablePendingsLink;
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
            OrganizationsRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
            OrganizationsRoutes::staticRoute(self::BASE_JS_DIR . '/list.js'),
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
                Config::get_lang(),
                function ($value) {
                    return is_string($value) && strlen(trim($value)) > 0;
                },
                true,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                "status",
                null,
                function ($value) {
                    return Validator::isInteger($value);
                },
                true,
                function ($value) {
                    $value = (int) $value;
                    return $value;
                }
            ),
            new Parameter(
                "name",
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
                "nit",
                null,
                function ($value) {
                    return is_string($value) && mb_strlen(trim($value)) > 0;
                },
                true,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                "size",
                null,
                function ($value) {
                    return is_null($value) || (is_string($value) && strlen(trim($value)) > 0);
                },
                true,
                function ($value) {
                    return is_string($value) ? clean_string($value) : $value;
                }
            ),
            new Parameter(
                "activitySector",
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
                true,
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
                "actionLines",
                null,
                function ($value) {
                    return is_array($value) || is_null($value);
                },
                true,
                function ($value) {
                    return $value;
                }
            ),
            new Parameter(
                "esal",
                null,
                function ($value) {
                    return is_null($value) || (is_string($value) && strlen(trim($value)) > 0);
                },
                true,
                function ($value) {
                    return is_string($value) ? clean_string($value) : $value;
                }
            ),
            new Parameter(
                "country",
                null,
                function ($value) {
                    return is_null($value) || Validator::isInteger($value);
                },
                true,
                function ($value) {
                    return Validator::isInteger($value) ? (int) $value : $value;
                }
            ),
            new Parameter(
                "city",
                null,
                function ($value) {
                    return is_null($value) || Validator::isInteger($value);
                },
                true,
                function ($value) {
                    return Validator::isInteger($value) ? (int) $value : $value;
                }
            ),
            new Parameter(
                "longitude",
                null,
                function ($value) {
                    return is_null($value) || Validator::isDouble($value);
                },
                true,
                function ($value) {
                    return Validator::isDouble($value) ? (double) $value : $value;
                }
            ),
            new Parameter(
                "latitude",
                null,
                function ($value) {
                    return is_null($value) || Validator::isDouble($value);
                },
                true,
                function ($value) {
                    return Validator::isDouble($value) ? (double) $value : $value;
                }
            ),
            new Parameter(
                "address",
                null,
                function ($value) {
                    return is_null($value) || (is_string($value) && strlen(trim($value)) > 0);
                },
                true,
                function ($value) {
                    return is_string($value) ? clean_string($value) : $value;
                }
            ),
            new Parameter(
                "phoneCode",
                null,
                function ($value) {
                    return is_null($value) || (is_string($value) && strlen(trim($value)) > 0);
                },
                true,
                function ($value) {
                    return is_string($value) ? clean_string($value) : $value;
                }
            ),
            new Parameter(
                "phone",
                null,
                function ($value) {
                    return is_null($value) || (is_string($value) && strlen(trim($value)) > 0);
                },
                true,
                function ($value) {
                    return is_string($value) ? clean_string($value) : $value;
                }
            ),
            new Parameter(
                "linkedinLink",
                null,
                function ($value) {
                    return is_null($value) || (is_string($value) && strlen(trim($value)) > 0);
                },
                true,
                function ($value) {
                    return is_string($value) ? clean_string($value) : $value;
                }
            ),
            new Parameter(
                "websiteLink",
                null,
                function ($value) {
                    return is_null($value) || (is_string($value) && strlen(trim($value)) > 0);
                },
                true,
                function ($value) {
                    return is_string($value) ? clean_string($value) : $value;
                }
            ),
            new Parameter(
                "informativeEmail",
                null,
                function ($value) {
                    return is_null($value) || (is_string($value) && strlen(trim($value)) > 0);
                },
                true,
                function ($value) {
                    return is_string($value) ? clean_string($value) : $value;
                }
            ),
            new Parameter(
                "billingEmail",
                null,
                function ($value) {
                    return is_null($value) || (is_string($value) && strlen(trim($value)) > 0);
                },
                true,
                function ($value) {
                    return is_string($value) ? clean_string($value) : $value;
                }
            ),
            new Parameter(
                'interestResearhAreas',
                [],
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
                true,
                function ($value) {
                    return is_array($value) ? array_map(function ($e) {
                        if (Validator::isInteger($e)) {
                            return new InterestResearchAreasMapper($e);
                        }
                    }, $value) : [];
                }
            ),
            new Parameter(
                'affiliatedInstitutions',
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
                'administrator',
                null,
                function ($value) {
                    return Validator::isInteger($value);
                },
                true,
                function ($value) {
                    return Validator::isInteger($value) ? (int) $value : -1;
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Organización'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'La organización que intenta modificar no existe.');
        $successCreateMessage = __(self::LANG_GROUP, 'Organización creada.');
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
             * @var string $name
             * @var string $nit
             * @var string|null $size
             * @var string|array|null $activitySector
             * @var string[]|null $actionLines
             * @var string|null $esal
             * @var string|null $country
             * @var string|null $city
             * @var double|null $longitude
             * @var double|null $latitude
             * @var string|null $address
             * @var string|null $phoneCode
             * @var string|null $phone
             * @var string|null $linkedinLink
             * @var string|null $websiteLink
             * @var string|null $informativeEmail
             * @var string|null $billingEmail
             * @var InterestResearchAreasMapper[] $interestResearhAreas
             * @var string[] $affiliatedInstitutions
             * @var int $status
             * @var int $administrator
             */
            $id = $expectedParameters->getValue('id');
            $lang = $expectedParameters->getValue('lang');
            $name = $expectedParameters->getValue('name');
            $nit = $expectedParameters->getValue('nit');
            $size = $expectedParameters->getValue('size');
            $activitySector = $expectedParameters->getValue('activitySector');
            $actionLines = $expectedParameters->getValue('actionLines');
            $esal = $expectedParameters->getValue('esal');
            $country = $expectedParameters->getValue('country');
            $city = $expectedParameters->getValue('city');
            $longitude = $expectedParameters->getValue('longitude');
            $latitude = $expectedParameters->getValue('latitude');
            $address = $expectedParameters->getValue('address');
            $phoneCode = $expectedParameters->getValue('phoneCode');
            $phone = $expectedParameters->getValue('phone');
            $linkedinLink = $expectedParameters->getValue('linkedinLink');
            $websiteLink = $expectedParameters->getValue('websiteLink');
            $informativeEmail = $expectedParameters->getValue('informativeEmail');
            $billingEmail = $expectedParameters->getValue('billingEmail');
            $interestResearhAreas = $expectedParameters->getValue('interestResearhAreas');
            $affiliatedInstitutions = $expectedParameters->getValue('affiliatedInstitutions');
            $status = $expectedParameters->getValue('status');
            $administrator = $expectedParameters->getValue('administrator');

            if (Validator::isDouble($latitude) && Validator::isDouble($longitude)) {
                //NOTE: Esto debería ser provisional
                //Opcional: Modifico arbitrariamente las coordenadas
                $maxOffset = 0.6000;
                $latOffset = (mt_rand(-1000, 1000) / 10000) * $maxOffset;
                $lngOffset = (mt_rand(-1000, 1000) / 10000) * $maxOffset;
                $latitude = $latitude + $latOffset;
                $longitude = $longitude + $lngOffset;
            }

            //Se define si es edición o creación
            $isEdit = $id !== -1;

            try {

                $currentUser = getLoggedFrameworkUser();
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

                    $mapper = new OrganizationMapper();

                    $mapper->setLangData($lang, 'name', $name);
                    $mapper->setLangData($lang, 'nit', $nit);
                    $mapper->setLangData($lang, 'size', $size);
                    $mapper->setLangData($lang, 'actionLines', $actionLines);
                    $mapper->setLangData($lang, 'esal', $esal);
                    $mapper->setLangData($lang, 'country', $country);
                    $mapper->setLangData($lang, 'city', $city);
                    $mapper->longitude = $longitude;
                    $mapper->latitude = $latitude;
                    $mapper->setLangData($lang, 'address', $address);
                    $mapper->phoneCode = $phoneCode;
                    $mapper->setLangData($lang, 'phone', $phone);
                    $mapper->setLangData($lang, 'linkedinLink', $linkedinLink);
                    $mapper->setLangData($lang, 'websiteLink', $websiteLink);
                    $mapper->setLangData($lang, 'informativeEmail', $informativeEmail);
                    $mapper->setLangData($lang, 'billingEmail', $billingEmail);
                    $mapper->interestResearhAreas = $interestResearhAreas;
                    $mapper->affiliatedInstitutions = $affiliatedInstitutions;
                    $mapper->setLangData($lang, 'folder', str_replace('.', '', uniqid()));
                    if ($currentUser !== null && OrganizationMapper::canModifyAnyOrganization($currentUser->type)) {
                        if ($status !== null) {
                            $mapper->setLangData($lang, 'status', $status);
                        }
                    } else {
                        $mapper->setLangData($lang, 'status', OrganizationMapper::PENDING_APPROVAL);
                    }

                    if (is_array($activitySector)) {
                        foreach ($activitySector as $l => $v) {
                            $mapper->setLangData($l, 'activitySector', $v);
                        }
                    } else {
                        $mapper->setLangData($lang, 'activitySector', $activitySector);
                    }

                    $rut = self::handlerUpload('rut', $mapper->folder);
                    $logo = self::handlerUpload('logo', $mapper->folder);

                    $mapper->setLangData($lang, 'rut', $rut);
                    $mapper->setLangData($lang, 'logo', $logo);

                    $saved = $mapper->save();
                    $resultOperation->setSuccessOnSingleOperation($saved);

                    if ($saved) {

                        $formsEdit = self::routeName('forms-edit', [
                            'id' => $mapper->id,
                        ]);
                        $resultOperation
                            ->setMessage($successCreateMessage)
                            ->setValue('orgID', $mapper->id)
                            ->setValue('redirect', true)
                            ->setValue('redirect_to', mb_strlen($formsEdit) > 0 ? $formsEdit : self::routeName('list'));

                    } else {
                        $resultOperation->setMessage($unknowErrorMessage);
                    }

                } else {

                    //Existente
                    $mapper = new OrganizationMapper((int) $id);
                    $exists = !is_null($mapper->id);
                    $valueOnNotRequest = uniqid();

                    /* Comprobaciones en edición para no sobreescribir valores que no están ingresando en la solicitud */
                    if ($exists) {

                        //Valores procesados
                        $inputsValues = [
                            'id' => $id,
                            'lang' => $lang,
                            'name' => $name,
                            'nit' => $nit,
                            'size' => $size,
                            'activitySector' => $activitySector,
                            'actionLines' => $actionLines,
                            'esal' => $esal,
                            'country' => $country,
                            'city' => $city,
                            'longitude' => $longitude,
                            'latitude' => $latitude,
                            'address' => $address,
                            'phoneCode' => $phoneCode,
                            'phone' => $phone,
                            'linkedinLink' => $linkedinLink,
                            'websiteLink' => $websiteLink,
                            'informativeEmail' => $informativeEmail,
                            'billingEmail' => $billingEmail,
                            'interestResearhAreas' => $interestResearhAreas,
                            'affiliatedInstitutions' => $affiliatedInstitutions,
                            'status' => $status,
                            'administrator' => $administrator,
                        ];
                        //Valores interpretados como vacíos
                        $emptyInputs = array_filter($inputsValues, function ($e) {
                            return $e === '' || $e === null || (is_array($e) && empty($e));
                        });
                        //Revisar si realmente no entraron en la solicitud
                        foreach ($emptyInputs as $emptyInputName => $emptyInputvalue) {
                            $inputValueInRequest = array_key_exists($emptyInputName, $inputData) ? $inputData[$emptyInputName] : $valueOnNotRequest;
                            if ($inputValueInRequest == $valueOnNotRequest) {
                                //Si no entraron se asigna un valor de control
                                $$emptyInputName = $valueOnNotRequest;
                            }
                        }
                    }

                    if ($exists) {

                        $invalidVal = $valueOnNotRequest;
                        if ($name !== $invalidVal) {$mapper->setLangData($lang, 'name', $name);}
                        if ($nit !== $invalidVal) {$mapper->setLangData($lang, 'nit', $nit);}
                        if ($size !== $invalidVal) {$mapper->setLangData($lang, 'size', $size);}
                        if ($actionLines !== $invalidVal) {$mapper->setLangData($lang, 'actionLines', $actionLines);}
                        if ($esal !== $invalidVal) {$mapper->setLangData($lang, 'esal', $esal);}
                        if ($country !== $invalidVal) {$mapper->setLangData($lang, 'country', $country);}
                        if ($city !== $invalidVal) {$mapper->setLangData($lang, 'city', $city);}
                        if ($longitude !== $invalidVal) {$mapper->longitude = $longitude;}
                        if ($latitude !== $invalidVal) {$mapper->latitude = $latitude;}
                        if ($address !== $invalidVal) {$mapper->setLangData($lang, 'address', $address);}
                        if ($phoneCode !== $invalidVal) {$mapper->phoneCode = $phoneCode;}
                        if ($phone !== $invalidVal) {$mapper->setLangData($lang, 'phone', $phone);}
                        if ($linkedinLink !== $invalidVal) {$mapper->setLangData($lang, 'linkedinLink', $linkedinLink);}
                        if ($websiteLink !== $invalidVal) {$mapper->setLangData($lang, 'websiteLink', $websiteLink);}
                        if ($informativeEmail !== $invalidVal) {$mapper->setLangData($lang, 'informativeEmail', $informativeEmail);}
                        if ($billingEmail !== $invalidVal) {$mapper->setLangData($lang, 'billingEmail', $billingEmail);}
                        if ($interestResearhAreas !== $invalidVal) {$mapper->interestResearhAreas = $interestResearhAreas;}
                        if ($affiliatedInstitutions !== $invalidVal) {$mapper->affiliatedInstitutions = $affiliatedInstitutions;}
                        if (OrganizationMapper::canModifyAnyOrganization(getLoggedFrameworkUser()->type)) {
                            if ($status !== null) {
                                if ($status !== $invalidVal) {$mapper->setLangData($lang, 'status', $status);}
                            }
                            if ($administrator !== null) {
                                if ($administrator !== $invalidVal) {$mapper->administrator = $administrator;}
                            }
                        }

                        if ($activitySector !== $invalidVal) {
                            if (is_array($activitySector)) {
                                foreach ($activitySector as $l => $v) {
                                    $mapper->setLangData($l, 'activitySector', $v);
                                }
                            } else {
                                $mapper->setLangData($lang, 'activitySector', $activitySector);
                            }
                        }

                        $rutSetted = $mapper->getLangData($lang, 'rut', false, null);
                        $logoSetted = $mapper->getLangData($lang, 'logo', false, null);

                        if (is_string($logoSetted) && mb_strlen(trim($logoSetted)) < 1) {
                            $logoSetted = null;
                        }

                        if ($rutSetted !== null) {
                            $rut = self::handlerUpload('rut', '', $rutSetted);
                        } else {
                            $rut = self::handlerUpload('rut', $mapper->folder, null);
                        }

                        if ($logoSetted !== null) {
                            $logo = self::handlerUpload('logo', '', $logoSetted);
                        } else {
                            $logo = self::handlerUpload('logo', $mapper->folder, null);
                        }

                        if (mb_strlen($rut) > 0) {
                            $mapper->setLangData($lang, 'rut', $rut);
                        }
                        if (mb_strlen($logo) > 0) {
                            $mapper->setLangData($lang, 'logo', $logo);
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

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Eliminar organización'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setValue('received', $inputData);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'La organización que intenta eliminar no existe.');
        $successMessage = __(self::LANG_GROUP, 'Organización eliminada.');
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

                $exists = OrganizationMapper::existsByID($id);

                if ($exists) {

                    //Dirección de redirección en caso de creación
                    $redirectURLOn = self::routeName('list');

                    $table = OrganizationMapper::TABLE;
                    $deleted = OrganizationMapper::DELETED;

                    $transactionSQLDeleteQueries = [
                        [
                            'query' => "UPDATE {$table} SET {$table}.status = {$deleted} WHERE id = :ID",
                            'aliasConfig' => [
                                ':ID' => $id,
                            ],
                        ],
                    ];

                    $pdo = OrganizationMapper::model()::getDb(Config::app_db('default')['db']);
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
                'name',
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
         * @var string $name
         */
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');
        $status = $expectedParameters->getValue('status');
        $name = $expectedParameters->getValue('name');

        $ignoreStatus = $status === 'ANY';
        $status = $status === 'ANY' ? null : $status;

        $sourceData = self::RESPONSE_SOURCE_NORMAL_RESULT;
        $result = self::_all($page, $perPage, $status, $name, $ignoreStatus);
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

        $status = $request->getQueryParam('status', null);

        $whereString = null;
        $havingString = null;
        $and = 'AND';
        $table = OrganizationMapper::TABLE;
        $active = OrganizationMapper::ACTIVE;
        $inactive = OrganizationMapper::INACTIVE;

        $where = [
            "{$table}.status != {$inactive}",
        ];
        $having = [];

        if ($status !== null) {
            $statusToCritery = in_array($status, array_keys(OrganizationMapper::STATUSES)) ? $status : -1;
            $beforeOperator = !empty($having) ? $and : '';
            $critery = "{$table}.status = {$statusToCritery}";
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
        }

        $selectFields = OrganizationMapper::fieldsToSelect();

        $columnsOrder = [
            'idPadding',
            'nit',
            'name',
            'countryName',
            'cityName',
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
            'mapper' => new OrganizationMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {

                $mapper = OrganizationMapper::objectToMapper($e);

                $buttons = [];
                $hasEdit = self::allowedRoute('forms-edit', ['id' => $e->id]);
                $hasDelete = self::allowedRoute('actions-delete', ['id' => $e->id]);
                $editProfileLink = MyOrganizationProfileController::routeName('my-organization-profile', [
                    'organizationID' => $e->id,
                ], true);
                $hasEditProfile = mb_strlen($editProfileLink) > 0;
                $profileViewLink = OrganizationProfileController::routeName('profile', [
                    'organizationID' => $e->id,
                ], true);
                $hasProfileViewLink = mb_strlen($profileViewLink) > 0;

                if ($hasEdit) {
                    $editLink = self::routeName('forms-edit', ['id' => $e->id]);
                    $editText = __(self::LANG_GROUP, 'Editar');
                    $editIcon = "<i class='icon edit'></i>";
                    $editButton = "<a title='{$editText}' href='{$editLink}' class='ui button brand-color icon'>{$editIcon}</a>";
                    $buttons[] = $editButton;
                }
                if ($hasEditProfile) {
                    $editProfileText = __(self::LANG_GROUP, 'Editar perfil');
                    $editProfileIcon = "<i class='icon edit'></i>";
                    $editProfileButton = "<a title='{$editProfileText}' href='{$editProfileLink}' class='ui button brand-color alt icon'>{$editProfileIcon}</a>";
                    $buttons[] = $editProfileButton;
                }
                if ($hasDelete) {
                    $deleteLink = self::routeName('actions-delete', ['id' => $mapper->id]);
                    $deleteText = __(self::LANG_GROUP, 'Eliminar');
                    $deleteIcon = "<i class='icon trash'></i>";
                    $deleteButton = "<a title='{$deleteText}' data-route='{$deleteLink}' class='ui button brand-color alt2 icon' delete-organization-button>{$deleteIcon}</a>";
                    $buttons[] = $deleteButton;
                }
                if ($hasProfileViewLink && $e->id != OrganizationMapper::INITIAL_ID_GLOBAL) {
                    $profileViewText = __(self::LANG_GROUP, 'Ver perfil');
                    $profileViewIcon = "<i class='icon address card outline'></i>";
                    $profileViewButton = "<a title='{$profileViewText}' href='{$profileViewLink}' class='ui button blue icon'>{$profileViewIcon}</a>";
                    $buttons[] = $profileViewButton;
                }

                $buttons = implode('', $buttons);
                $columns = [];

                $name = mb_strlen($e->name) <= 54 ? $e->name : mb_substr($e->name, 0, 51) . '...';

                $columns[] = $e->id == OrganizationMapper::INITIAL_ID_GLOBAL ? str_pad(0, 5, "0") : $e->idPadding;
                $columns[] = $e->nit;
                $columns[] = $name;
                $columns[] = $e->countryName;
                $columns[] = $e->cityName;
                $columns[] = $buttons;
                return $columns;
            },

        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * @param int $page =1
     * @param int $perPage =10
     * @param int $status =OrganizationMapper::ACTIVE
     * @param string $name =null
     * @param bool $ignoreStatus =false
     * @return PaginationResult
     */
    public static function _all(
        int $page = null,
        int $perPage = null,
        int $status = null,
        string $name = null,
        bool $ignoreStatus = false
    ) {
        $page = $page === null ? 1 : $page;
        $perPage = $perPage === null ? 10 : $perPage;
        $status = $status === null ? OrganizationMapper::ACTIVE : $status;

        $table = OrganizationMapper::TABLE;
        $fields = OrganizationMapper::fieldsToSelect();
        $jsonExtractExists = OrganizationMapper::jsonExtractExistsMySQL();

        $whereString = null;
        $where = [];
        $and = 'AND';

        if (!$ignoreStatus) {

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$table}.status = {$status}";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        if ($name !== null) {

            $beforeOperator = !empty($where) ? $and : '';
            $nameField = OrganizationMapper::fieldCurrentLangForSQL('name');
            $critery = "UPPER({$nameField}) LIKE UPPER('%{$name}%')";
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

        $sqlSelect .= " ORDER BY " . implode(', ', OrganizationMapper::ORDER_BY_PREFERENCE);

        $pageQuery = new PageQuery($sqlSelect, $sqlCount, $page, $perPage, 'total');

        $parser = function ($element) {
            $element = OrganizationMapper::objectToMapper($element);
            return $element;
        };
        $each = !$jsonExtractExists ? function ($element) {
            $mapper = OrganizationMapper::objectToMapper($element);
            $element = OrganizationMapper::translateEntityObject($element);
            return $element;
        } : function ($element) {
            $mapper = OrganizationMapper::objectToMapper($element);
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

                if ($name == 'actions-delete') {

                    $allow = false;
                    $id = ($getParam)('id');
                    $id = Validator::isInteger($id) ? (int) $id : -1;
                    $initialInmutableID = OrganizationMapper::INITIAL_ID_GLOBAL;
                    $organization = OrganizationMapper::getBy($id, 'id');

                    if ($organization !== null && $initialInmutableID != $id) {

                        $createdByID = (int) $organization->createdBy;
                        $allow = $createdByID == $currentUserID;

                        if (in_array($currentUserType, OrganizationMapper::CAN_DELETE_ALL)) {
                            $allow = true;
                        }

                    }

                } elseif ($name == 'forms-edit' || $name == 'actions-edit') {

                    $allow = false;
                    $id = ($getParam)('id');
                    $id = Validator::isInteger($id) ? (int) $id : -1;
                    $organization = OrganizationMapper::getBy($id, 'id');
                    $organizationMeta = json_decode($organization->meta);
                    $organizationAdministratorID = is_object($organizationMeta) && property_exists($organizationMeta, 'administrator') ? $organizationMeta->administrator : -1;
                    $isEditor = in_array($currentUserType, OrganizationMapper::EDITORS);
                    $initialInmutableID = OrganizationMapper::INITIAL_ID_GLOBAL;
                    $isAdministrator = $currentUserID == $organizationAdministratorID;

                    if ($organization !== null) {

                        $currentUserOrganization = $currentUser->organization;
                        $allow = $isEditor && $currentUserOrganization == $organization->id && $currentUserOrganization !== $initialInmutableID;

                        if (in_array($currentUserType, OrganizationMapper::CAN_MODIFY_ALL)) {
                            $allow = true;
                        } else if (!$allow) {
                            if ($currentUserOrganization !== null) {
                                $allow = $isAdministrator;
                            }
                        }

                    }

                    if (OrganizationMapper::DISABLE_NORMAL_EDIT_FORM) {
                        $allowedOnDisableEditForm = [
                            UsersModel::TYPE_USER_ROOT,
                        ];
                        if (!in_array($currentUserType, $allowedOnDisableEditForm)) {
                            $allow = false;
                        }
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
                FileValidator::TYPE_ANY,
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

                $instance = new OrganizationsController;
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
                        if (!is_null($oldFile) && is_file($oldFile)) {

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
        $baseList = OrganizationMapper::CAN_VIEW_BASE_LIST;
        $list = array_merge(OrganizationMapper::CAN_VIEW_ALL, OrganizationMapper::CAN_MODIFY_ALL, OrganizationMapper::CAN_VIEW_BASE_LIST);
        $creation = OrganizationMapper::CAN_MODIFY_ALL;
        $edition = array_merge(OrganizationMapper::EDITORS, OrganizationMapper::CAN_MODIFY_ALL, OrganizationMapper::PROFILE_EDITOR);
        $deletion = [
            UsersModel::TYPE_USER_ROOT,
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
                $baseList
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
