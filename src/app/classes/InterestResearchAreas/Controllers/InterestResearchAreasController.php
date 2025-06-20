<?php

/**
 * InterestResearchAreasController.php
 */

namespace InterestResearchAreas\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use InterestResearchAreas\Exceptions\DuplicateException;
use InterestResearchAreas\Exceptions\SafeException;
use InterestResearchAreas\InterestResearchAreasLang;
use InterestResearchAreas\InterestResearchAreasRoutes;
use InterestResearchAreas\Mappers\InterestResearchAreasMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Config;
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
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\SystemApprovalsRoutes;

/**
 * InterestResearchAreasController.
 *
 * @package     InterestResearchAreas\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class InterestResearchAreasController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'interest-research-areas';
    /**
     * @var string
     */
    protected static $baseRouteName = 'interest-research-areas-admin';
    /**
     * @var string
     */
    protected static $title = 'Áreas de investigación';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_VIEW_DIR = 'interest-research-areas';
    const BASE_JS_DIR = 'js/interest-research-areas';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = InterestResearchAreasLang::LANG_GROUP;

    const RESPONSE_SOURCE_STATIC_CACHE = 'STATIC_CACHE';
    const RESPONSE_SOURCE_NORMAL_RESULT = 'NORMAL_RESULT';
    const ENABLE_CACHE = true;

    public function __construct()
    {
        parent::__construct();

        $this->model = (new InterestResearchAreasMapper())->getModel();

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(InterestResearchAreasRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(InterestResearchAreasRoutes::staticRoute(self::BASE_CSS_DIR . '/interest-research-areas.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addForm(Request $request, Response $response)
    {

        import_spectrum();
        set_custom_assets([
            InterestResearchAreasRoutes::staticRoute(self::BASE_JS_DIR . '/add-form.js'),
        ], 'js');

        $action = self::routeName('actions-add');
        $backLink = self::routeName('list');

        $title = __(self::LANG_GROUP, 'Agregar área de investigación');
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
            __(self::LANG_GROUP, 'Áreas de investigación') => [
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

        $element = new InterestResearchAreasMapper($id);

        if ($element->id !== null && InterestResearchAreasMapper::existsByID($element->id)) {

            import_spectrum();
            set_custom_assets([
                InterestResearchAreasRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
                InterestResearchAreasRoutes::staticRoute(self::BASE_JS_DIR . '/edit-form.js'),
            ], 'js');

            $action = self::routeName('actions-edit');
            $backLink = self::routeName('list');

            $title = __(self::LANG_GROUP, 'Edición de área de investigación');
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
            $data['selectedLang'] = $selectedLang;
            $data['breadcrumbs'] = get_breadcrumbs([
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => get_route('admin'),
                ],
                __(self::LANG_GROUP, 'Áreas de investigación') => [
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

        $title = __(self::LANG_GROUP, 'Áreas de investigación');
        $description = __(self::LANG_GROUP, 'Listado de áreas de investigación');

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
            InterestResearchAreasRoutes::staticRoute(self::BASE_JS_DIR . '/delete-config.js'),
            InterestResearchAreasRoutes::staticRoute(self::BASE_JS_DIR . '/list.js'),
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
                'areaName',
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
                'color',
                null,
                function ($value) {
                    return is_string($value) && strlen(trim($value)) > 0;
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Área de investigación'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'El área de investigación que intenta modificar no existe.');
        $successCreateMessage = __(self::LANG_GROUP, 'Área de investigación creada.');
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
             * @var string $baseLang
             * @var string $areaName
             * @var string $color
             */
            $id = $expectedParameters->getValue('id');
            $lang = $expectedParameters->getValue('lang');
            $baseLang = $expectedParameters->getValue('baseLang');
            $areaName = $expectedParameters->getValue('areaName');
            $color = $expectedParameters->getValue('color');

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
                    $mapper = new InterestResearchAreasMapper();

                    $mapper->baseLang = $baseLang;
                    $mapper->addDataManyLangs('areaName', $areaName, array_keys($areaName));
                    $mapper->color = $color;

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

                    $mapper = new InterestResearchAreasMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        $mapper->addDataManyLangs('areaName', $areaName, array_keys($areaName));
                        $mapper->color = $color;

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

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Eliminar área de investigación'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setValue('received', $inputData);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'El área de investigación que intenta eliminar no existe.');
        $successMessage = __(self::LANG_GROUP, 'Área de investigación eliminada.');
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

                $exists = InterestResearchAreasMapper::existsByID($id);

                if ($exists) {

                    //Dirección de redirección en caso de creación
                    $redirectURLOn = self::routeName('list');

                    $table = InterestResearchAreasMapper::TABLE;
                    $inactive = InterestResearchAreasMapper::INACTIVE;

                    $transactionSQLDeleteQueries = [
                        [
                            'query' => "UPDATE {$table} SET {$table}.status = {$inactive} WHERE id = :ID",
                            'aliasConfig' => [
                                ':ID' => $id,
                            ],
                        ],
                    ];

                    $pdo = InterestResearchAreasMapper::model()::getDb(Config::app_db('default')['db']);
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
        $table = InterestResearchAreasMapper::TABLE;
        $inactive = InterestResearchAreasMapper::INACTIVE;

        $where = [
            "{$table}.status != {$inactive}",
        ];
        $having = [];

        //Restricciones según organización (a menos que pueda verlas todas por InterestResearchAreasMapper::CAN_VIEW_ALL)
        if (!in_array($currentUserType, InterestResearchAreasMapper::CAN_VIEW_ALL)) {

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

        $selectFields = InterestResearchAreasMapper::fieldsToSelect('%M %d, %Y');

        $columnsOrder = [
            'idPadding',
            'areaName',
            'color',
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
            'mapper' => new InterestResearchAreasMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {

                $mapper = InterestResearchAreasMapper::objectToMapper($e);

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
                    $deleteButton = "<a title='{$deleteText}' data-route='{$deleteLink}' class='ui button brand-color alt2 icon' delete-interest-research-area-button>{$deleteIcon}</a>";
                    $buttons[] = $deleteButton;
                }

                $buttons = implode('', $buttons);
                $columns = [];

                $cssSampleColor = [
                    "display: inline-block;",
                    "background-color: {$mapper->color};",
                    "width: 45px;",
                    "height: 45px;",
                    "border-radius: 8px;",
                    "box-shadow: 0px 0px 15px #00000061;",
                ];
                $cssSampleColor = implode(' ', $cssSampleColor);
                $columns[] = $e->idPadding;
                $columns[] = $e->areaName;
                $columns[] = "<span class='sample-color' style='{$cssSampleColor}'></span>";
                $columns[] = $buttons;
                return $columns;
            },

        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * @param int $page =1
     * @param int $perPage =10
     * @param int $status =InterestResearchAreasMapper::ACTIVE
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
        $status = $status === null ? InterestResearchAreasMapper::ACTIVE : $status;

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
        $organizations = array_key_exists('organizations', $others) ? $others['organizations'] : null;
        $contentType = array_key_exists('contentType', $others) ? $others['contentType'] : null;
        $financingType = array_key_exists('financingType', $others) ? $others['financingType'] : null;
        $startDate = array_key_exists('startDate', $others) ? $others['startDate'] : null;
        $endDate = array_key_exists('endDate', $others) ? $others['endDate'] : null;

        $table = InterestResearchAreasMapper::TABLE;
        $fields = InterestResearchAreasMapper::fieldsToSelect();
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
            $titleField = InterestResearchAreasMapper::fieldCurrentLangForSQL('title');
            $critery = "UPPER({$titleField}) LIKE UPPER('%{$title}%')";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        if ($search !== null) {
            $beforeOperator = !empty($having) ? $and : '';
            $titleField = InterestResearchAreasMapper::fieldCurrentLangForSQL('title');
            $contentField = InterestResearchAreasMapper::fieldCurrentLangForSQL('content');
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

        $orderBy = InterestResearchAreasMapper::ORDER_BY_PREFERENCE;
        if (isset($_GET['random']) && $_GET['random'] === 'yes') {
            $orderBy = ['RAND()'];
        }
        $sqlSelect .= " ORDER BY " . implode(', ', $orderBy);

        $pageQuery = new PageQuery($sqlSelect, $sqlCount, $page, $perPage, 'total');

        $parser = function ($element) use ($internalItem) {
            $element = InterestResearchAreasMapper::objectToMapper($element);
            return $element;
        };
        $each = function ($element) use ($internalItem) {
            if (!$internalItem) {
                $mapper = InterestResearchAreasMapper::objectToMapper($element);
                unset($element->idPadding);
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
                    $interestResearchArea = InterestResearchAreasMapper::getBy($id, 'id');

                    if ($interestResearchArea !== null) {

                        $createdByID = (int) $interestResearchArea->createdBy;
                        $createdByOrganizationID = (int) UsersModel::getBy($interestResearchArea->createdBy, 'id')->organization;
                        $createdByOrganizationRecord = OrganizationMapper::getBy($createdByOrganizationID, 'id', true);
                        $createdByOrganizationAdminID = $createdByOrganizationRecord !== null ? $createdByOrganizationRecord->administrator->id : null;
                        $currentIsSameOrg = $currentOrganizationMapper !== null ? $createdByOrganizationID == $currentOrganizationMapper->id : false;
                        $currentIsOrgAdmin = $createdByOrganizationAdminID == $currentUserID;
                        $allowByOrg = $currentIsSameOrg && $currentIsOrgAdmin;

                        $allow = $createdByID == $currentUserID;

                        if (in_array($currentUserType, InterestResearchAreasMapper::CAN_DELETE_ALL) || $allowByOrg) {
                            $allow = true;
                        }

                    }

                } elseif ($name == 'forms-edit') {

                    $allow = false;
                    $id = ($getParam)('id');
                    $interestResearchArea = InterestResearchAreasMapper::getBy($id, 'id');

                    if ($interestResearchArea !== null) {

                        $createdByID = (int) $interestResearchArea->createdBy;
                        $createdByOrganizationID = (int) UsersModel::getBy($interestResearchArea->createdBy, 'id')->organization;
                        $createdByOrganizationRecord = OrganizationMapper::getBy($createdByOrganizationID, 'id', true);
                        $createdByOrganizationAdminID = $createdByOrganizationRecord !== null ? $createdByOrganizationRecord->administrator->id : null;
                        $currentIsSameOrg = $currentOrganizationMapper !== null ? $createdByOrganizationID == $currentOrganizationMapper->id : false;
                        $currentIsOrgAdmin = $createdByOrganizationAdminID == $currentUserID;
                        $allowByOrg = $currentIsSameOrg && $currentIsOrgAdmin;

                        $allow = $createdByID == $currentUserID;

                        if (in_array($currentUserType, InterestResearchAreasMapper::CAN_EDIT_ALL) || $allowByOrg) {
                            $allow = true;
                        }

                    }

                }

            }

        }

        return $allow;
    }

    public static function pathFrontInterestResearchAreaAdapter()
    {
        return InterestResearchAreasRoutes::staticRoute('js/InterestResearchAreaAdapter.js');
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
